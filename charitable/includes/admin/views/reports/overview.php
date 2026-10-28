<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main reports "overview" page.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8,1
 * @version   1.8.1
 * @version   1.8.8.6
 * @version   1.8.14
 */

$charitable_start_date  = false;
$charitable_end_date    = false;
$charitable_filter      = false;
$charitable_campaign_id = false;
$charitable_category_id = false;
$status      = false; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

$charitable_reports = Charitable_Reports::get_instance();

// Check the transients via charitable_report_overview_args.
$charitable_report_data_args = false; // get_transient( 'charitable-report-overview-args' );

if ( false !== $charitable_report_data_args ) {

	$charitable_start_date  = ( false === $charitable_start_date && ! empty( $charitable_report_data_args['start_date'] ) ) ? $charitable_reports->get_valid_date_string( $charitable_report_data_args['start_date'] ) : false;
	$charitable_end_date    = ( false === $charitable_end_date && ! empty( $charitable_report_data_args['end_date'] ) ) ? $charitable_reports->get_valid_date_string( $charitable_report_data_args['end_date'] ) : false;
	$charitable_filter      = ( false === $charitable_filter && ! empty( $charitable_report_data_args['filter'] ) ) ? intval( $charitable_report_data_args['filter'] ) : 'test';
	$charitable_campaign_id = ( false === $charitable_campaign_id && ! empty( $charitable_report_data_args['campaign_id'] ) ) ? intval( $charitable_report_data_args['campaign_id'] ) : false;
	$charitable_category_id = ( false === $charitable_category_id && ! empty( $charitable_report_data_args['category_id'] ) ) ? intval( $charitable_report_data_args['category_id'] ) : false;

}

if ( false === $charitable_start_date || false === $charitable_end_date || false === $charitable_filter ) {
	// so nothing from the transient, so check the $_GET.
	$charitable_start_date  = isset( $_GET['start_date'] ) ? $charitable_reports->get_valid_date_string( sanitize_text_field( $_GET['start_date'] ) ) : false; // phpcs:ignore
	$charitable_end_date    = isset( $_GET['end_date'] ) ? $charitable_reports->get_valid_date_string( sanitize_text_field( $_GET['end_date'] ) ) : false; // phpcs:ignore
	$charitable_filter      = isset( $_GET['filter'] ) ? intval( $_GET['filter'] ) : false; // phpcs:ignore
	$charitable_campaign_id = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : false; // phpcs:ignore
	$charitable_category_id = isset( $_GET['category_id'] ) ? intval( $_GET['category_id'] ) : false; // phpcs:ignore
}

/*
REMEMBER THE LAST FILTER. Sits between the URL and the defaults on purpose: an explicit
start_date in the URL always wins, and only a visit with nothing specified falls back to what
this browser last looked at. Reloading the page therefore keeps the range you had.

A cookie rather than a transient or user meta, for two reasons. The transient this file used to
read - 'charitable-report-overview-args', still written by the AJAX handler - is SITE-wide, so one
admin's filter would follow every other admin around; that is why reading it here stayed commented
out. And the value is a per-browser view preference, the same thing the collapse states already
keep in cookies on this page.

Written by charitable-admin-reporting.js when a filter is applied, because the view cannot set a
cookie once output has started. Values are re-validated here rather than trusted - it is a cookie,
so it is user input.

@since 1.8.14
*/
if ( ( false === $charitable_start_date || false === $charitable_end_date ) && ! empty( $_COOKIE['charitable_reports_range'] ) ) {
	$charitable_remembered = array_map( 'sanitize_text_field', explode( '|', wp_unslash( (string) $_COOKIE['charitable_reports_range'] ) ) );

	if ( count( $charitable_remembered ) >= 2 ) {
		/*
		THE SHAPE IS CHECKED HERE, because get_valid_date_string() COERCES rather than rejects.

		It returns '1970-01-01' for anything it cannot read - "garbage", an empty string, a
		script tag - and never returns false. So the obvious guard, `false !== $start`, can
		never fire, and an earlier version of this block carried a comment claiming the pair
		was ignored unless both ends parsed. That claim was simply untrue: a corrupted or
		hand-edited cookie silently loaded a range beginning at the Unix epoch, which on this
		page means a ~56-year span - month granularity and the largest query the screen can
		make, which is the long-range timeout risk COMPARISON_MAX_DAYS exists to avoid.

		Not a security hole: the value is sanitised, every date reaches SQL through a
		placeholder, and the probes confirm nothing injects. It is a correctness and
		performance bug, and it was invisible precisely because the guard looked right.

		A plain shape test on the SANITISED part, before trusting the parse. Both ends must
		match, or the cookie is ignored and the defaults below take over - which is the
		behaviour the old comment described and did not deliver.

		@since 1.8.14
		*/
		$charitable_cookie_date_shape = '/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/';

		$charitable_cookie_shape_ok = (bool) preg_match( $charitable_cookie_date_shape, $charitable_remembered[0] )
			&& (bool) preg_match( $charitable_cookie_date_shape, $charitable_remembered[1] );

		$charitable_remembered_start = $charitable_cookie_shape_ok ? $charitable_reports->get_valid_date_string( $charitable_remembered[0] ) : false;
		$charitable_remembered_end   = $charitable_cookie_shape_ok ? $charitable_reports->get_valid_date_string( $charitable_remembered[1] ) : false;

		// Both ends must parse, or the pair is ignored - half a range is worse than none.
		if ( false !== $charitable_remembered_start && false !== $charitable_remembered_end ) {
			$charitable_start_date = $charitable_remembered_start;
			$charitable_end_date   = $charitable_remembered_end;
			$charitable_filter     = 1;

			if ( isset( $charitable_remembered[2] ) && '' !== $charitable_remembered[2] ) {
				$charitable_campaign_id = intval( $charitable_remembered[2] );
			}

			if ( isset( $charitable_remembered[3] ) && '' !== $charitable_remembered[3] ) {
				$charitable_category_id = intval( $charitable_remembered[3] );
			}
		}
	}
}

if ( false === $charitable_start_date || false === $charitable_end_date || false === $charitable_filter ) {
	// If still nothing assign defaults.
	/*
	 * Site-local, not UTC: post_date is stored in site-local time, so a bound
	 * built from bare UTC excludes part of today's donations on any site whose
	 * timezone is ahead of UTC.
	 *
	 * @since 1.8.13
	 */
	$charitable_start_date  = gmdate( 'Y/m/d', current_time( 'timestamp', 0 ) - ( 7 * DAY_IN_SECONDS ) );
	$charitable_end_date    = gmdate( 'Y/m/d', current_time( 'timestamp', 0 ) );
	$charitable_filter      = 7;
	$charitable_campaign_id = -1; // cannot be zero because that would be false.
	$charitable_category_id = 0;
}

$charitable_report_overview_args = apply_filters(
	'charitable_report_overview_args',
	array(
		'start_date'  => $charitable_start_date,
		'end_date'    => $charitable_end_date,
		'filter'      => $charitable_filter,
		'campaign_id' => $charitable_campaign_id,
		'category_id' => $charitable_category_id,
	)
);

$charitable_reports->init_with_array( 'overview', $charitable_report_overview_args );

// main activity.
$charitable_report_activity = $charitable_reports->get_activity();

// donations.
$charitable_donations              = $charitable_reports->get_donations();
$charitable_total_count_donations  = count( $charitable_donations );
$charitable_total_amount_donations = $charitable_reports->get_donations_total();
$charitable_donation_breakdown     = $charitable_reports->get_donations_by_day();
$charitable_payment_breakdown      = $charitable_reports->get_donations_by_payment();
$charitable_donation_average       = ( $charitable_total_amount_donations > 0 && $charitable_total_amount_donations > 0 ) ? ( charitable_format_money( $charitable_total_amount_donations / $charitable_total_count_donations, false, true ) ) : charitable_format_money( 0 );
$charitable_donation_total         = charitable_format_money( $charitable_total_amount_donations );

// donors.
$charitable_top_donors         = $charitable_reports->get_top_donors_overview();
$charitable_total_count_donors = $charitable_reports->get_donors_count();
$charitable_total_donors_label = ( 1 === $charitable_total_count_donors ) ? esc_html__( 'Donor', 'charitable' ) : esc_html__( 'Donors', 'charitable' );

// refunds.
$charitable_refunds_data         = $charitable_reports->get_refunds();
$charitable_total_amount_refunds = $charitable_refunds_data['total_amount'];
$charitable_total_count_refunds  = $charitable_refunds_data['total_count'];

// campaigns.
$charitable_args              = array( 'posts_per_page' => apply_filters( 'charitable_overview_top_campaigns_amount', 20 ) );
$charitable_top_campaigns     = Charitable_Campaigns::ordered_by_amount( $charitable_args );
$charitable_campaign_dropdown = array();
if ( ! empty( $charitable_top_campaigns->posts ) ) :
	$charitable_campaign_dropdown = wp_list_pluck( $charitable_top_campaigns->posts, 'post_title', 'ID' );
	$charitable_campaign_dropdown = array_map( 'esc_html', $charitable_campaign_dropdown );
	// sort by title.
	asort( $charitable_campaign_dropdown );
endif;

/*
Every change below is gated on this, so the shipping Reports page is byte-for-byte what it was
when the flag is off. See includes/admin/reports/charitable-reports-v3.php for the option, the
filter and why Lite's gate is its own rather than Pro's dashboard one.

@since 1.8.14
*/
$charitable_reports_v3 = function_exists( 'charitable_reports_v3_enabled' )
	&& charitable_reports_v3_enabled();

/*
Bucket the breakdown once, and let BOTH the table and the chart read the result.

Before this the table rendered a row per day and the chart a point per day, so the two crowded
independently - the chart squeezed 30+ labels together while the table grew past 1,800px - and
each had its own workaround. One bucketing means they cannot disagree about granularity.

Assigning the axes onto the reports object works because report_vars() runs on admin_footer at
priority 100, after this view, and the AJAX filter path reads the same two public properties. So
the first paint and every subsequent filter get the bucketed series from one place.
*/
$charitable_reports_granularity = 'day';

if ( $charitable_reports_v3 ) {

	// bucket_for_range() also publishes the axes onto the object, which report_vars() reads on
	// admin_footer. The AJAX handler calls the same method, so a filtered page and a typed URL
	// cannot end up at different granularities.
	$charitable_bucketed = $charitable_reports->bucket_for_range( $charitable_donation_breakdown, $charitable_refunds_data, $charitable_start_date, $charitable_end_date );

	$charitable_reports_granularity = $charitable_bucketed['granularity'];
	$charitable_donation_breakdown  = $charitable_bucketed['rows'];
	$charitable_refunds_data        = $charitable_bucketed['refunds'];
}

$charitable_headline_tiles = array(
	'captions' => array(
		'donations' => '',
		'average'   => '',
		'donors'    => '',
		'refunds'   => '',
	),
	'trends'   => array(
		'donations' => '',
		'average'   => '',
		'donors'    => '',
		'refunds'   => '',
	),
);

if ( $charitable_reports_v3 ) {
	/*
	One call for the captions AND the trend markup - see Charitable_Reports::get_headline_tiles().
	The AJAX filter handler calls the same method, so a filtered page and a freshly loaded one
	cannot disagree about what these tiles say.
	*/
	$charitable_headline_tiles = $charitable_reports->get_headline_tiles( $charitable_report_overview_args );
}

$charitable_campaign_categories  = $charitable_reports->get_campaign_categories();
$charitable_campaign_category_id = 0;

?>

<div class="tablenav top<?php echo $charitable_reports_v3 ? ' charitable-reports-v3-toolbar' : ''; ?>">
	<div class="alignleft actions">
		<label for="report-campaign-filter" class="screen-reader-text"><?php echo esc_html__( 'Select Campaign', 'charitable' ); ?></label>
		<select name="action" id="report-campaign-filter">
			<?php
			/*
			v3 drops the "Showing Results for" prefix. A <select> already reads as "this is the
			current value", so the prefix restated the control's job inside its own first option -
			and <strong> inside an <option> is stripped by every browser anyway, so the emphasis it
			was reaching for never rendered.
			*/
			?>
			<?php if ( $charitable_reports_v3 ) : ?>
				<option value="-1"><?php echo esc_html__( 'All Campaigns', 'charitable' ); ?></option>
			<?php else : ?>
				<option value="-1"><?php echo esc_html__( 'Showing Results for', 'charitable' ); ?> <strong><?php echo esc_html__( 'All Campaigns', 'charitable' ); ?></strong></option>
			<?php endif; ?>
			<?php if ( ! empty( $charitable_campaign_dropdown ) ) : ?>
				<?php foreach ( $charitable_campaign_dropdown as $charitable_campaign_dropdown_id => $charitable_campaign_title ) : ?>
				<option value="<?php echo intval( $charitable_campaign_dropdown_id ); ?>"><?php echo esc_html( $charitable_campaign_title ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<?php if ( ! empty( $charitable_campaign_categories ) ) : ?>
		<label for="report-category-filter" class="screen-reader-text"><?php echo esc_html__( 'Select Category', 'charitable' ); ?></label>
			<?php

				// create a dropdown of WordPress csategories hierarchically.
				wp_dropdown_categories(
					array(
						'show_option_all'   => esc_html__( 'All Categories', 'charitable' ),
						'show_option_none'  => '',
						'option_none_value' => '-1',
						'orderby'           => 'name',
						'order'             => 'ASC',
						'show_count'        => 1,
						'hide_empty'        => 0,
						'child_of'          => 0,
						'exclude'           => '',
						'echo'              => 1,
						'selected'          => $charitable_campaign_category_id,
						'hierarchical'      => 1,
						'name'              => 'action',
						'id'                => 'report-category-filter',
						'class'             => '',
						'depth'             => 0,
						'tab_index'         => 0,
						'taxonomy'          => 'campaign_category',
					)
				);

			?>
		<?php endif; ?>

		<?php
		/*
		v3 moves the date range and the Filter button here, so everything that NARROWS the data
		sits together and the right-hand side holds only actions. The shipping order had the date
		range and Filter on the right next to Print, which put a control and an export in the same
		group and left the two selects looking like the whole of the filtering.
		*/
		?>
		<?php if ( $charitable_reports_v3 ) : ?>
			<input type="text" id="charitable-reports-topnav-datepicker" class="charitable-reports-datepicker charitable-datepicker-ranged" data-start-date="<?php echo esc_attr( $charitable_start_date ); ?>" data-end-date="<?php echo esc_attr( $charitable_end_date ); ?>" value="<?php echo esc_attr( $charitable_start_date ); ?> - <?php echo esc_attr( $charitable_end_date ); ?>" />
			<div class="charitable-datepicker-container"><a href="#" class="button button-primary" id="charitable-reports-filter-button" data-filter-type="overview"><?php echo esc_html__( 'Filter', 'charitable' ); ?></a></div>
		<?php endif; ?>
	</div>
	<div class="alignright">

		<?php
		/*
		Export CSV, at last. It waited until there was a handler behind it: the only export on this
		page was the Donations Breakdown card's own per-day table, and offering that at page level
		would have misdescribed its scope. download_overview_summary() exports the headline figures,
		the comparison and the payment split - the summary somebody actually wants from this screen.

		Same treatment as Print, by wearing the same class rather than a new one, so the two cannot
		drift apart. It sits to Print's left because the page-level export is the broader action and
		reads first.

		@since 1.8.14
		*/
		?>
		<?php if ( $charitable_reports_v3 ) : ?>
			<form action="" method="post" class="charitable-report-print" id="charitable-overview-export">
				<input name="charitable_report_action" type="hidden" value="charitable_report_download_overview_summary" />
				<input name="start_date" type="hidden" value="<?php echo esc_attr( $charitable_start_date ); ?>" />
				<input name="end_date" type="hidden" value="<?php echo esc_attr( $charitable_end_date ); ?>" />
				<input name="campaign_id" type="hidden" value="<?php echo intval( $charitable_campaign_id ); ?>" />
				<input name="category_id" type="hidden" value="<?php echo intval( $charitable_category_id ); ?>" />
				<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
				<button type="submit" class="button with-icon charitable-report-print-button" title="<?php echo esc_attr__( 'Export this summary as a CSV', 'charitable' ); ?>"><label><?php echo esc_html__( 'Export CSV', 'charitable' ); ?></label><img width="15" height="15" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt="" /></button>
			</form>
		<?php endif; ?>

		<form action="" method="post" target="_blank" class="charitable-report-print" id="charitable-overview-print">
			<input name="charitable_report_action" type="hidden" value="charitable_report_print_overview" />
			<input name="start_date" type="hidden" value="<?php echo esc_attr( $charitable_start_date ); ?>" />
			<input name="end_date" type="hidden" value="<?php echo esc_attr( $charitable_end_date ); ?>" />
			<input name="campaign_id" type="hidden" value="<?php echo intval( $charitable_campaign_id ); ?>" />
			<input name="category_id" type="hidden" value="<?php echo intval( $charitable_category_id ); ?>" />
			<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
			<button value="Download CSV" type="submit" class="button with-icon charitable-report-print-button" title="<?php echo esc_html__( 'Print Summary', 'charitable' ); ?>" class="button with-icon charitable-report-ui" data-nonce="<?php echo wp_create_nonce( 'charitable_export_report' ); // phpcs:ignore ?>"><label><?php echo esc_html__( 'Print', 'charitable' ); ?></label><img width="15" height="15" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/print.svg' ); ?>" alt=""></button>
		</form>

		<?php if ( ! $charitable_reports_v3 ) : ?>
		<input type="text" id="charitable-reports-topnav-datepicker" class="charitable-reports-datepicker charitable-datepicker-ranged" data-start-date="<?php echo esc_attr( $charitable_start_date ); ?>" data-end-date="<?php echo esc_attr( $charitable_end_date ); ?>" value="<?php echo esc_attr( $charitable_start_date ); ?> - <?php echo esc_attr( $charitable_end_date ); ?>" />

		<div class="charitable-datepicker-container"><a href="#" class="button button-primary" id="charitable-reports-filter-button" data-filter-type="overview"><?php echo esc_html__( 'Filter', 'charitable' ); ?></a></div>
		<?php endif; ?>

	</div>
	<br class="clear">
</div>

<div class="charitable-overview-report">

	<?php if ( $charitable_reports_v3 ) : ?>
		<?php
		/*
		WHAT THE PAGE SAYS WHILE IT RELOADS.

		Filtering dimmed every .charitable-report-ui to 25% opacity and said nothing, so the screen
		went pale and grey with no indication anything was happening - on a long range that lasts
		long enough to look broken.

		Deliberately OUTSIDE the report content: the dimming applies to .charitable-report-ui, and a
		panel placed inside one would fade out with the very thing it is explaining.
		*/
		?>
		<div class="charitable-reports-v3-loading" hidden>
			<div class="charitable-reports-v3-loading-panel" role="status" aria-live="polite">
				<span class="charitable-reports-v3-spinner" aria-hidden="true"></span>
				<p class="charitable-reports-v3-loading-title"><?php echo esc_html__( 'Updating your report', 'charitable' ); ?></p>
				<p class="charitable-reports-v3-loading-note"><?php echo esc_html__( 'Pulling donations for the dates you picked. Longer ranges take a few seconds.', 'charitable' ); ?></p>
			</div>
		</div>
	<?php endif; ?>

	<div class="charitable-headline-reports">

		<?php
		/*
		The four headline figures, as tiles rather than centred blocks: label above, figure, trend
		on its own line, then a caption naming what the trend is measured against. The shipping
		card put the figure first with its label underneath and folded the count into that label
		("1 Total Donations (Net)"), so the label carried two facts and the percentage carried none
		of its own.

		The captions and the trend markup are built in Charitable_Reports::get_headline_tiles() -
		see $charitable_headline_tiles above - and NOT here, because the AJAX handler cannot run
		the view, so anything computed here would be left behind on a filter change.
		*/
		?>
		<div class="charitable-cards<?php echo $charitable_reports_v3 ? ' charitable-reports-v3-tiles' : ''; ?>">

			<?php if ( $charitable_reports_v3 ) : ?>

				<div class="charitable-container charitable-report-ui charitable-card" data-charitable-tile="donations">
					<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Net donations', 'charitable' ); ?></span>
					<strong><span id="charitable-top-donation-total-amount"><?php echo esc_html( $charitable_donation_total ); ?></span></strong>
					<span class="charitable-reports-v3-tile-trend"><?php echo $charitable_headline_tiles['trends']['donations']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="charitable-reports-v3-tile-caption"><?php echo esc_html( $charitable_headline_tiles['captions']['donations'] ); ?></span>
				</div>

				<div class="charitable-container charitable-report-ui charitable-card" data-charitable-tile="average">
					<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Average donation', 'charitable' ); ?></span>
					<strong><span id="charitable-top-donation-average"><?php echo esc_html( $charitable_donation_average ); ?></span></strong>
					<span class="charitable-reports-v3-tile-trend"><?php echo $charitable_headline_tiles['trends']['average']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="charitable-reports-v3-tile-caption"><?php echo esc_html( $charitable_headline_tiles['captions']['average'] ); ?></span>
				</div>

				<div class="charitable-container charitable-report-ui charitable-card" data-charitable-tile="donors">
					<span class="charitable-reports-v3-tile-label"><?php echo esc_html( $charitable_total_donors_label ); ?></span>
					<strong><span id="charitable-top-donor-count"><?php echo intval( $charitable_total_count_donors ); ?></span></strong>
					<span class="charitable-reports-v3-tile-trend"><?php echo $charitable_headline_tiles['trends']['donors']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="charitable-reports-v3-tile-caption"><?php echo esc_html( $charitable_headline_tiles['captions']['donors'] ); ?></span>
				</div>

				<div class="charitable-container charitable-report-ui charitable-card" data-charitable-tile="refunds">
					<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Refunds', 'charitable' ); ?></span>
					<strong><span id="charitable-top-refund-total-amount"><?php echo charitable_format_money( $charitable_total_amount_refunds ); // phpcs:ignore ?></span></strong>
					<span class="charitable-reports-v3-tile-trend"><?php echo $charitable_headline_tiles['trends']['refunds']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="charitable-reports-v3-tile-caption"><?php echo esc_html( $charitable_headline_tiles['captions']['refunds'] ); ?></span>
				</div>

			<?php else : ?>

				<div class="charitable-container charitable-report-ui charitable-card">
					<strong><span id="charitable-top-donation-total-amount"><?php echo esc_html( $charitable_donation_total ); ?></span></strong>
					<p><span id="charitable-top-donation-total-count"><?php echo count( $charitable_donations ); ?></span> <?php echo esc_html__( 'Total Donations (Net)', 'charitable' ); ?></p>
				</div>
				<div class="charitable-container charitable-report-ui charitable-card">
					<strong><span id="charitable-top-donation-average"><?php echo esc_html( $charitable_donation_average ); ?></span></strong>
					<p><?php echo esc_html__( 'Average Donation', 'charitable' ); ?></p>
				</div>
				<div class="charitable-container charitable-report-ui charitable-card">
					<strong><span id="charitable-top-donor-count"><?php echo intval( $charitable_total_count_donors ); ?></span></strong>
					<p><?php echo esc_html( $charitable_total_donors_label ); ?></p>
				</div>
				<div class="charitable-container charitable-report-ui charitable-card">
					<strong><span id="charitable-top-refund-total-amount"><?php echo charitable_format_money( $charitable_total_amount_refunds ); // phpcs:ignore  ?></span></strong>
					<p><span id="charitable-top-refund-count"><?php echo intval( $charitable_total_count_refunds ); ?></span> <?php echo esc_html__( 'Refunds', 'charitable' ); ?></p>
				</div>

			<?php endif; ?>
		</div>

		<?php
		/*
		The columns start HERE, not after the chart.

		Only the four stat tiles stay full width - they are one row of equal figures and read as a
		strip. The chart belongs in the main column with the rest of the analysis: full width it was
		1685px of axis for a line that changes a few times, and the rail had nothing beside it to sit
		against.

		Real wrapper divs rather than placing these cards into a page-level CSS grid. Grid fills rows
		in order, so the rail's short cards would align to the rows of the much taller main-column
		cards and leave gaps between them; there is no masonry in CSS grid, so the rail has to be its
		own container.
		*/
		?>
		<?php if ( $charitable_reports_v3 ) : ?>
			</div><!-- /.charitable-headline-reports -->

			<div class="charitable-reports-v3-cols">
				<div class="charitable-reports-v3-main">
		<?php endif; ?>

		<?php
		/*
		In v3 the chart is NOT a card of its own: it is emitted inside the Donations Breakdown
		card further down. Classic keeps its own container, which is why this is a branch rather
		than a deletion.
		*/
		?>
		<?php if ( ! $charitable_reports_v3 ) : ?>
			<div class="charitable-container charitable-report-ui charitable-headline-graph-container">
				<div id="charitable-headline-graph" class="charitable-headline-graph">
				</div>
			</div>
		<?php endif; ?>

	<?php
	// In v3 .charitable-headline-reports closed before the columns opened, above the chart.
	if ( ! $charitable_reports_v3 ) :
		?>
	</div>
	<?php endif; ?>

	<?php
	/*
	charitable-container and data-section-name are what make the toggle work: the handler in
	charitable-admin-reporting.js resolves its target as
	closest('.charitable-container').find('.charitable-toggle-container'), and reads the section
	name for the cookie that remembers the state. No JS change needed.

	ONE class on the card says "this range has nothing", and both the chart and the table read it.
	With nothing to show, the card was printing the message twice: "No donations in this date
	range." over the chart, and then a full table header above a row saying the same thing.
	*/
	?>
	<div class="tablenav charitable-section<?php echo $charitable_reports_v3 ? ' charitable-container' : ''; ?><?php echo ( $charitable_reports_v3 && empty( $charitable_donations ) ) ? ' charitable-reports-v3-breakdown-empty' : ''; ?>"<?php echo $charitable_reports_v3 ? ' data-section-name="donations_breakdown"' : ''; ?>>

		<div class="alignleft actions">
			<h2><?php echo esc_html__( 'Donations Breakdown', 'charitable' ); ?></h2>
			<?php
			/*
			Say what a row is when it is not a day. A table of "September 2026" rows under a
			heading that says nothing about months makes a reader check the dates to work out what
			they are looking at.

			Always emitted, even when empty. The AJAX filter rewrites its text, and a span that
			only exists at some granularities is a span the filter cannot find - which is how
			"BY WEEK" would survive a switch to Last 7 Days.
			*/
			?>
			<?php if ( $charitable_reports_v3 ) : ?>
				<span class="charitable-reports-v3-granularity"><?php echo esc_html( $charitable_reports->get_breakdown_granularity_label( $charitable_reports_granularity ) ); ?></span>
			<?php endif; ?>
		</div>
		<div class="alignright">

			<form target="_blank" action="" method="post" class="charitable-report-download-form" id="charitable-donations-breakdown-download-form">
				<input name="charitable_report_action" type="hidden" value="charitable_report_download_donation_breakdown" />
				<input name="start_date" type="hidden" value="<?php echo esc_attr( $charitable_start_date ); ?>" />
				<input name="end_date" type="hidden" value="<?php echo esc_attr( $charitable_end_date ); ?>" />
				<input name="campaign_id" type="hidden" value="<?php echo intval( $charitable_campaign_id ); ?>" />
				<input name="category_id" type="hidden" value="<?php echo intval( $charitable_category_id ); ?>" />
				<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
				<button value="Download CSV" type="submit" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" class="button with-icon charitable-report-ui" data-nonce="<?php echo wp_create_nonce( 'charitable_export_report' ); // phpcs:ignore ?>"><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>
			</form>

			<?php
			/*
			The collapse chevron sits at the FAR RIGHT of the header, after the button.

			It was beside the heading here, which made this one card read differently from every
			other card on the page and on the dashboard, and put the affordance for hiding a card
			next to its title rather than in the corner a reader goes to for it. Anything else in
			that corner moves to the chevron's left, which is why this is emitted after the
			download form rather than before it.
			*/
			?>
			<?php if ( $charitable_reports_v3 ) : ?>
				<a href="#" class="charitable-toggle charitable-reports-v3-breakdown-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			<?php endif; ?>

		</div>
		<br class="clear">

		<?php if ( $charitable_reports_v3 ) : ?>
		<div class="charitable-toggle-container charitable-report-ui">
			<?php
			/*
			The chart lives here, inside the Donations Breakdown card and above the table it
			summarises - one card headed "Donations Breakdown" holding the graph and the rows. It
			was its own untitled card directly above, so the page showed a chart with no heading
			and then a table that was the same data.

			ApexCharts resolves this element by id on DOM ready, so moving it down the document
			costs the chart nothing.

			An empty range drew axes and a flat line on zero, which reads as a broken chart rather
			than as "nothing happened here". Said plainly instead. Rendered on the server so it is
			right on first paint, and toggled by the filter's response afterwards.
			*/
			?>
			<div class="charitable-reports-v3-graph-wrap">
				<div id="charitable-headline-graph" class="charitable-headline-graph"></div>
				<p class="charitable-reports-v3-graph-none"><?php echo esc_html__( 'No donations in this date range.', 'charitable' ); ?></p>
			</div>
		<?php endif; ?>
		<?php
		/*
		v3 drops 'striped'. The mockup's rows are plain with a hairline between them, and removing
		the class is honest about that - fighting WP's .striped nth-child rule from another
		stylesheet would leave two rules disagreeing about what this table looks like.

		ONLY the table scrolls, and only downwards. The height cap and overflow sit on a wrapper
		around the table alone rather than on .charitable-toggle-container, which holds the chart
		as well - setting overflow-y on that made overflow-x compute to auto too, so the card grew
		a horizontal scrollbar as soon as the full-bleed table extended past the container's
		content box.
		*/
		?>
		<?php if ( $charitable_reports_v3 ) : ?>
		<div class="charitable-reports-v3-breakdown-scroll">
		<?php endif; ?>
		<table class="wp-list-table widefat fixed<?php echo $charitable_reports_v3 ? '' : ' striped'; ?> table-view-list donations-breakdown charitable-report-ui">
			<thead>
				<tr>
					<th scope="col" id="date" class="manage-column column-date"><span><?php echo esc_html__( 'Date', 'charitable' ); ?></span></a></th>
					<th scope="col" id="donations" class="manage-column column-donations"><?php echo esc_html__( 'Donations', 'charitable' ); ?></th>
					<th scope="col" id="no-of-donors" class="manage-column column-donors"><?php echo $charitable_reports_v3 ? esc_html__( 'Donors', 'charitable' ) : esc_html__( 'No. of Donors', 'charitable' ); ?></th>
					<th scope="col" id="refunds" class="manage-column column-refunds"><?php echo esc_html__( 'Refunds', 'charitable' ); ?></th>
					<th scope="col" id="net" class="manage-column column-net"><?php echo esc_html__( 'Net', 'charitable' ); ?></th>
				</tr>
			</thead>
			<tbody id="donations-breakdown-list">
				<?php echo $charitable_reports->generate_donations_breakdown_rows( $charitable_donation_breakdown, $charitable_refunds_data ); // phpcs:ignore ?>
			</tbody>
			<?php if ( ! $charitable_reports_v3 ) : ?>
			<tfoot>
				<tr>
					<th scope="col" id="title" class="manage-column column-titl"><span><?php echo esc_html__( 'Date', 'charitable' ); ?></span></a></th>
					<th scope="col" id="creator" class="manage-column column-creator"><?php echo esc_html__( 'Donations', 'charitable' ); ?></th>
					<th scope="col" id="donated" class="manage-column column-donated"><?php echo esc_html__( 'No. of Donors', 'charitable' ); ?></th>
					<th scope="col" id="status" class="manage-column column-status"><?php echo esc_html__( 'Refunds', 'charitable' ); ?></th>
					<th scope="col" id="end_date" class="manage-column column-end_date"><?php echo esc_html__( 'Net', 'charitable' ); ?></th>
				</tr>
			</tfoot>
			<?php endif; ?>
		</table>
		<?php if ( $charitable_reports_v3 ) : ?>
		</div>
		<?php endif; ?>

		<?php
		/*
		The "quiet rows are hidden" line. Read straight off the property the row generator just
		set, so this count cannot disagree with the number of rows it marked. The rows are in the
		DOM already, so the toggle is a class on the table and needs no second request.

		It comes from Charitable_Reports::get_breakdown_quiet_note_html(), which returns an empty
		string when nothing is hidden - so the guard lives in the method and the AJAX handler can
		send the same markup. Inline here, it could not be refreshed.
		*/
		if ( $charitable_reports_v3 ) {
			echo $charitable_reports->get_breakdown_quiet_note_html( $charitable_reports_granularity, $charitable_reports->breakdown_empty_days ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the method.
		}
		?>
		<?php if ( $charitable_reports_v3 ) : ?>
			<script>
			/*
			Delegated off the document, and emitted whether or not a note is on the page right now:
			the AJAX filter can ADD one to a table that had none, and a listener bound only when a
			note existed would never fire for it.

			Inline because it is eight lines against one element on one screen, and the alternative
			is a file enqueued on every Charitable page to toggle a class here.
			*/
			( function () {
				document.addEventListener( 'click', function ( e ) {
					var btn = e.target.closest( '.charitable-reports-v3-empty-days-toggle' );
					if ( ! btn ) { return; }
					var wrap = btn.closest( '.charitable-section' );
					if ( ! wrap ) { return; }
					var shown = wrap.classList.toggle( 'charitable-reports-v3-show-empty' );
					btn.textContent = shown ? btn.dataset.hide : btn.dataset.show;
					btn.setAttribute( 'aria-expanded', shown ? 'true' : 'false' );
				} );
			}() );
			</script>
		<?php endif; ?>

		<?php if ( $charitable_reports_v3 ) : ?>
		</div>
		<?php endif; ?>

		<br class="clear">

	</div>

	<div class="charitable-section-grid charitable-section-grid-column-flexible">

		<div class="charitable-container charitable-report-card charitable-activity-report" data-section-name="activity">
			<div class="header">
				<h4><?php echo esc_html__( 'Activity', 'charitable' ); ?></h4>
				<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
				<div class="the-list">
					<?php echo $charitable_reports->generate_activity_list( $charitable_report_activity ); // phpcs:ignore ?>
				</div>
			</div>
			<?php if ( $charitable_reports_v3 ) : ?>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-reports&tab=activity' ) ); ?>"><?php echo esc_html__( 'View All Activity', 'charitable' ); ?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			<?php endif; ?>
		</div>

		<div class="charitable-container charitable-report-card charitable-top-donors-report" data-section-name="top_donors">
			<div class="header">
				<h4><?php echo esc_html__( 'Top Donors', 'charitable' ); ?></h4>
				<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
				<?php echo $charitable_reports->generate_top_donors( $charitable_top_donors ); // phpcs:ignore ?>
			</div>
			<?php if ( $charitable_reports_v3 ) : ?>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-donors' ) ); ?>"><?php echo esc_html__( 'View All Donors', 'charitable' ); ?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			<?php endif; ?>
		</div>

	</div>

	<?php
	/*
	Top Campaigns and Payment Methods shared a "one-third" grid row. In v3 they are split across
	the two columns - the table needs width, the three-row breakdown does not - so the grid that
	paired them is not emitted and each lands in its own column instead.
	*/
	?>
	<?php if ( ! $charitable_reports_v3 ) : ?>
	<div class="charitable-section-grid one-third charitable-section-grid-column-flexible">
	<?php endif; ?>

		<div class="charitable-container charitable-report-card charitable-top-campaigns-report" data-section-name="top_campaigns">
			<div class="header">
				<h4><?php echo esc_html__( 'Top Campaigns', 'charitable' ); ?></h4>
				<?php
				// Says what "Pacing" is measuring, so the column needs no tooltip.
				?>
				<?php if ( $charitable_reports_v3 ) : ?>
					<span class="charitable-reports-v3-locked-note"><?php echo esc_html__( 'Pacing against goal and end date', 'charitable' ); ?></span>
				<?php endif; ?>
				<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
			<?php if ( ! empty( $charitable_top_campaigns->posts ) ) : ?>
				<div class="the-list">
					<?php
					/*
					v3 renders these as a table so the campaigns can be compared down a column,
					which is what a "top campaigns" card is for. The generator emits rows or list
					items depending on the same flag - see generate_top_campaigns().
					*/
					?>
					<?php if ( $charitable_reports_v3 ) : ?>
						<div class="tablewrap">
							<table class="charitable-reports-v3-campaigns">
								<thead>
									<tr>
										<th><?php echo esc_html__( 'Campaign', 'charitable' ); ?></th>
										<th class="num"><?php echo esc_html__( 'Raised', 'charitable' ); ?></th>
										<th class="num"><?php echo esc_html__( 'Goal', 'charitable' ); ?></th>
										<th><?php echo esc_html__( 'Pacing', 'charitable' ); ?></th>
										<th class="num"><?php echo esc_html__( 'Ends', 'charitable' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php echo $charitable_reports->generate_top_campaigns( $charitable_top_campaigns ); // phpcs:ignore ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<ul id="charitable-top-campaigns-list">
							<?php echo $charitable_reports->generate_top_campaigns( $charitable_top_campaigns ); // phpcs:ignore ?>
						</ul>
				<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="no-items">
					<div class="the-list">
						<p><strong><?php echo esc_html__( 'There are no campaigns at the moment.', 'charitable' ); ?></strong></p>
						<p class="link"><a href="<?php echo admin_url( 'edit.php?post_type=campaign&create=campaign' ); ?>"><?php echo esc_html__( 'Add New Campaign', 'charitable' ); ?><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a></p>
					</div>
				</div>
			<?php endif; ?>
			</div>
			<?php if ( $charitable_reports_v3 ) : ?>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=campaign' ) ); ?>"><?php echo esc_html__( 'View All Campaigns', 'charitable' ); ?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			<?php endif; ?>
		</div>

		<?php
		/*
		RECURRING REVENUE and DONATION RECOVERY: TWO STATES EACH, live or locked.

		BOTH ADDONS CAN RUN ON LITE, which is the fact that shapes everything below. Recurring
		Donations gates only on Charitable core at 1.6.51+ - no Pro check, no licence check at all.
		Donation Recovery gates on charitable_is_pro(), and that is LICENCE-derived rather than
		edition-derived: it delegates to Charitable_Licenses::is_pro(), which reads the stored
		licence, so a Lite install carrying a valid licence satisfies it. (Its own code comment
		reads "Require Charitable Pro (not Lite)", which describes an intent the implementation
		does not enforce.)

		So a Lite site can genuinely have recurring giving collecting money and abandoned donations
		being recovered. Showing either of those sites a blurred "upgrade" card would be telling
		somebody to buy what they already own, which is the worst version of an upsell. Both cards
		therefore render the real report whenever the addon's capability class is present.

		LOCKED is for the sites that really do not have it, and the CTA says which of the three
		reasons applies - addon installed but off, entitled but not installed, licence expired, or
		no licence - via charitable_reports_v3_locked_cta(). One helper, two call sites, so the
		branching cannot drift.

		Every value inside a masked block is invented, and the panel on top says so. The blur plus
		aria-hidden means no screen reader reads those numbers as data.

		@since 1.8.14
		*/
		$charitable_recurring = $charitable_reports_v3 && method_exists( $charitable_reports, 'get_recurring_revenue' )
			? $charitable_reports->get_recurring_revenue()
			: null;
		?>
		<?php if ( is_array( $charitable_recurring ) ) : ?>

			<?php
			/*
			LIVE. Three tiles and the renewals table, from the addon's own public accessors.

			"Due Next 30 Days" leads rather than a monthly-recurring figure, and that is not a
			layout preference: the addon's get_mrr_data() values a plan at zero whenever it cannot
			reach the amount through its join, while still counting the plan as active. The figure
			that leads here is derived in get_recurring_due_soon() from accessors that cannot fail
			silently.

			IT IGNORES THE DATE FILTER, alone among the cards on this page, which is why every tile
			label names its own window. Nothing on it is range-shaped.
			*/
			?>
			<div class="charitable-container charitable-report-card charitable-reports-v3-recurring" data-section-name="recurring_revenue">
				<div class="header">
					<h4><?php echo esc_html__( 'Recurring Revenue', 'charitable' ); ?></h4>
					<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</div>
				<div class="charitable-toggle-container charitable-report-ui">
					<div class="charitable-reports-v3-recurring-stats">
						<div>
							<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Due Next 30 Days', 'charitable' ); ?></span>
							<strong><?php echo charitable_format_money( $charitable_recurring['due']['amount'], false, true ); // phpcs:ignore ?></strong>
							<span class="charitable-reports-v3-tile-caption">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: number of scheduled charges. */
										_n( '%s charge scheduled', '%s charges scheduled', (int) $charitable_recurring['due']['count'], 'charitable' ),
										number_format_i18n( (int) $charitable_recurring['due']['count'] )
									)
								);
								?>
							</span>
						</div>
						<div>
							<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Active Plans', 'charitable' ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $charitable_recurring['active'] ) ); ?></strong>
							<span class="charitable-reports-v3-tile-caption">
								<?php
								/*
								Empty only when every active plan carries a period the addon does
								not define, which is not a state worth inventing copy for - the
								span stays so the three tiles keep the same internal rhythm.
								*/
								echo esc_html( $charitable_recurring['periods'] );
								?>
							</span>
						</div>
						<?php
						/*
						The one tile here that is not about money arriving. On-hold and expired
						plans are giving that has stopped for a reason the site can usually act
						on - a card expiring, a term running out - so the count is the start of a
						task rather than a statistic.
						*/
						?>
						<div>
							<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Needs Attention', 'charitable' ); ?></span>
							<strong class="<?php echo $charitable_recurring['attention']['total'] > 0 ? 'charitable-reports-v3-recurring-alert' : ''; ?>"><?php echo esc_html( number_format_i18n( $charitable_recurring['attention']['total'] ) ); ?></strong>
							<span class="charitable-reports-v3-tile-caption">
								<?php
								if ( $charitable_recurring['attention']['total'] > 0 ) {
									printf(
										/* translators: 1: number of plans on hold. 2: number of expired plans. */
										esc_html__( '%1$s on hold, %2$s expired', 'charitable' ),
										esc_html( number_format_i18n( $charitable_recurring['attention']['onhold'] ) ),
										esc_html( number_format_i18n( $charitable_recurring['attention']['expired'] ) )
									);
								} else {
									echo esc_html__( 'Every plan is collecting', 'charitable' );
								}
								?>
							</span>
						</div>
					</div>

					<?php if ( ! empty( $charitable_recurring['due']['plans'] ) ) : ?>
						<div class="tablewrap">
							<table class="charitable-reports-v3-recurring-renewals">
								<thead>
									<tr>
										<th><?php echo esc_html__( 'Renewal Date', 'charitable' ); ?></th>
										<th><?php echo esc_html__( 'Donor', 'charitable' ); ?></th>
										<th><?php echo esc_html__( 'Campaign', 'charitable' ); ?></th>
										<th class="num"><?php echo esc_html__( 'Amount', 'charitable' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $charitable_recurring['due']['plans'] as $charitable_rr_plan ) : ?>
										<tr>
											<td><?php echo esc_html( $charitable_rr_plan['date'] ); ?></td>
											<td><?php echo esc_html( $charitable_rr_plan['donor'] ); ?></td>
											<td><?php echo esc_html( $charitable_rr_plan['campaign'] ); ?></td>
											<td class="num"><?php echo charitable_format_money( $charitable_rr_plan['amount'], false, true ); // phpcs:ignore ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<?php
							/*
							Said only when the list is actually short of the total, so the line is
							information rather than furniture. The tile carries the full count
							either way.
							*/
							?>
							<?php if ( count( $charitable_recurring['due']['plans'] ) < (int) $charitable_recurring['due']['count'] ) : ?>
								<p class="charitable-reports-v3-recurring-note">
									<?php
									printf(
										/* translators: 1: number of renewals listed. 2: total number of scheduled charges. */
										esc_html__( 'Showing the next %1$s of %2$s scheduled charges.', 'charitable' ),
										esc_html( number_format_i18n( count( $charitable_recurring['due']['plans'] ) ) ),
										esc_html( number_format_i18n( (int) $charitable_recurring['due']['count'] ) )
									);
									?>
								</p>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<?php
						/*
						No table when there is nothing to put in it - the same rule the Donations
						Breakdown follows. An empty table with a header row repeats the message in
						a shape that invites reading it as a fault.
						*/
						?>
						<p class="charitable-reports-v3-recurring-note charitable-reports-v3-recurring-note--empty"><?php echo esc_html__( 'No renewals are scheduled in the next 30 days.', 'charitable' ); ?></p>
					<?php endif; ?>
				</div>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=recurring_donation' ) ); ?>"><?php
					/*
					"Recurring Donations", matching the screen it opens - edit.php for the
					recurring_donation post type is titled exactly that. Pro's label reads
					"View All Recurring Plans", which names the domain object rather than the
					destination, and every other card foot on this page names its destination
					(View All Campaigns, View All Donors, View All Activity).
					*/
					echo esc_html__( 'View All Recurring Donations', 'charitable' );
					?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			</div>

		<?php elseif ( $charitable_reports_v3 ) : ?>

			<?php
			$charitable_rr_cta = charitable_reports_v3_locked_cta(
				array(
					'slug'        => 'charitable-recurring',
					'name'        => __( 'Recurring Donations', 'charitable' ),
					'product_url' => 'https://www.wpcharitable.com/extensions/charitable-recurring-donations/',
					'utm'         => 'reports-overview',
				)
			);
			?>
			<div class="charitable-container charitable-report-card charitable-reports-v3-locked" data-section-name="recurring_revenue">
				<div class="header">
					<h4><?php echo esc_html__( 'Recurring Revenue', 'charitable' ); ?></h4>
					<span class="charitable-reports-v3-pro-pill"><?php echo esc_html__( 'Pro', 'charitable' ); ?></span>
					<?php
					/*
					The locked card gets a chevron too. The rule on this page is that every card
					carries one in the same corner - and a card that cannot be collapsed when its
					neighbours can is an exception a reader has to notice and explain to themselves.
					*/
					?>
					<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</div>

				<div class="charitable-reports-v3-locked-body charitable-toggle-container">
					<div class="charitable-reports-v3-locked-masked" aria-hidden="true">
						<div class="charitable-reports-v3-recurring-stats">
							<div>
								<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Due Next 30 Days', 'charitable' ); ?></span>
								<strong>$1,284.00</strong>
								<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( '34 charges scheduled', 'charitable' ); ?></span>
							</div>
							<div>
								<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Active Plans', 'charitable' ); ?></span>
								<strong>42</strong>
								<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( '38 monthly, 4 yearly', 'charitable' ); ?></span>
							</div>
							<div>
								<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Needs Attention', 'charitable' ); ?></span>
								<strong>3</strong>
								<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( '2 on hold, 1 expired', 'charitable' ); ?></span>
							</div>
						</div>

						<table class="charitable-reports-v3-locked-sample">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Renewal Date', 'charitable' ); ?></th>
									<th><?php echo esc_html__( 'Donor', 'charitable' ); ?></th>
									<th><?php echo esc_html__( 'Campaign', 'charitable' ); ?></th>
									<th class="num"><?php echo esc_html__( 'Amount', 'charitable' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr><td>Oct 14, 2026</td><td>Marcus Webb</td><td>Winter Appeal</td><td class="num">$25.00</td></tr>
								<tr><td>Oct 15, 2026</td><td>Priya Raman</td><td>Clean Water Project</td><td class="num">$50.00</td></tr>
								<tr><td>Oct 17, 2026</td><td>Dana Whitfield</td><td>Animal Rescue</td><td class="num">$15.00</td></tr>
								<tr><td>Oct 19, 2026</td><td>Tomas Beltran</td><td>Winter Appeal</td><td class="num">$100.00</td></tr>
								<tr><td>Oct 21, 2026</td><td>Ellen Muir</td><td>Hurricane Relief Fund</td><td class="num">$30.00</td></tr>
								<tr><td>Oct 23, 2026</td><td>Joseph Adeyemi</td><td>Animal Rescue</td><td class="num">$20.00</td></tr>
							</tbody>
						</table>
					</div>

					<div class="charitable-reports-v3-locked-overlay">
						<div class="charitable-reports-v3-locked-card">
							<h5><?php echo esc_html__( 'Recurring Donations', 'charitable' ); ?></h5>
							<p>
								<?php
								/*
								The note comes first and the pitch second, because on a site whose
								licence covers this the note is the whole message and the pitch is
								noise. It is empty only in the no-licence state, where the pitch is
								the point.
								*/
								echo '' !== $charitable_rr_cta['note']
									? esc_html( $charitable_rr_cta['note'] ) . ' '
									: '';
								echo esc_html__( 'Let supporters give every month, and see what is already committed before the month begins. The figures above are an example.', 'charitable' );
								?>
							</p>
							<div class="charitable-reports-v3-locked-actions">
								<a class="button button-primary" href="<?php echo esc_url( $charitable_rr_cta['url'] ); ?>"<?php echo $charitable_rr_cta['external'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $charitable_rr_cta['label'] ); ?></a>
								<a class="button" href="<?php echo esc_url( charitable_ga_url( 'https://www.wpcharitable.com/extensions/charitable-recurring-donations/', 'reports-overview', 'Recurring Revenue learn more' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Learn More', 'charitable' ); ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>

		<?php endif; ?>

		<?php
		/*
		Donation Recovery, same two states. data-section-name is "abandoned_donations" rather than
		a name matching the title - it is the collapse cookie's key, it is invisible, and it
		matches Pro so a site moving between the two keeps its saved collapse state.

		Its own conditional, not a branch of the recurring one above: the two addons are
		independent, and nesting this inside the recurring `elseif` would have rendered Donation
		Recovery only on sites where recurring happened to be locked.
		*/
		$charitable_abandoned = $charitable_reports_v3 && method_exists( $charitable_reports, 'get_abandoned_donations' )
			? $charitable_reports->get_abandoned_donations( $charitable_report_overview_args )
			: null;
		?>
		<?php if ( is_array( $charitable_abandoned ) ) : ?>

			<div class="charitable-container charitable-report-card charitable-reports-v3-abandoned" data-section-name="abandoned_donations">
				<div class="header">
					<h4><?php echo esc_html__( 'Donation Recovery', 'charitable' ); ?></h4>
					<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</div>
				<div class="charitable-toggle-container charitable-report-ui">
					<div class="charitable-reports-v3-recurring-stats">
						<div>
							<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Not completed', 'charitable' ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $charitable_abandoned['abandoned'] ) ); ?></strong>
							<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( 'Started and never finished', 'charitable' ); ?></span>
						</div>
						<div>
							<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Recovered', 'charitable' ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $charitable_abandoned['recovered'] ) ); ?></strong>
							<span class="charitable-reports-v3-tile-caption">
								<?php
								printf(
									/* translators: %s: the recovery rate as a percentage. */
									esc_html__( '%s%% recovery rate', 'charitable' ),
									esc_html( number_format_i18n( $charitable_abandoned['rate'], 1 ) )
								);
								?>
							</span>
						</div>
						<div>
							<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Recovered value', 'charitable' ); ?></span>
							<strong><?php echo charitable_format_money( $charitable_abandoned['revenue'], false, true ); // phpcs:ignore ?></strong>
							<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( 'Would otherwise have been lost', 'charitable' ); ?></span>
						</div>
					</div>
				</div>
				<?php
				/*
				The card foot goes to the addon's own REPORTS tab, which the addon registers on the
				charitable_reports_tabs filter and which therefore only exists while the addon is
				active - exactly when this branch renders, so the link cannot outlive its
				destination.
				*/
				?>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-reports&tab=donation_recovery' ) ); ?>"><?php echo esc_html__( 'Donation Recovery Reports', 'charitable' ); ?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			</div>

		<?php elseif ( $charitable_reports_v3 ) : ?>

			<?php
			$charitable_dr_cta = charitable_reports_v3_locked_cta(
				array(
					'slug'        => 'charitable-donation-recovery',
					'name'        => __( 'Donation Recovery', 'charitable' ),
					'product_url' => 'https://www.wpcharitable.com/extensions/charitable-donation-recovery/',
					'utm'         => 'reports-overview',
				)
			);
			?>
			<div class="charitable-container charitable-report-card charitable-reports-v3-locked" data-section-name="abandoned_donations">
				<div class="header">
					<h4><?php echo esc_html__( 'Donation Recovery', 'charitable' ); ?></h4>
					<span class="charitable-reports-v3-pro-pill"><?php echo esc_html__( 'Pro', 'charitable' ); ?></span>
					<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</div>

				<div class="charitable-reports-v3-locked-body charitable-toggle-container">
					<div class="charitable-reports-v3-locked-masked" aria-hidden="true">
						<div class="charitable-reports-v3-recurring-stats">
							<div>
								<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Not completed', 'charitable' ); ?></span>
								<strong>168</strong>
								<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( 'Started and never finished', 'charitable' ); ?></span>
							</div>
							<div>
								<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Recovered', 'charitable' ); ?></span>
								<strong>34</strong>
								<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( '20.2% recovery rate', 'charitable' ); ?></span>
							</div>
							<div>
								<span class="charitable-reports-v3-tile-label"><?php echo esc_html__( 'Recovered value', 'charitable' ); ?></span>
								<strong>$2,410.00</strong>
								<span class="charitable-reports-v3-tile-caption"><?php echo esc_html__( 'Would otherwise have been lost', 'charitable' ); ?></span>
							</div>
						</div>

						<?php
						/*
						Sample rows behind the CTA, so the preview shows the SHAPE of the report
						and not just three figures. It also does the layout job asked of it - the
						blurred area has to be at least as tall as the CTA panel, and three stat
						tiles alone were shorter.
						*/
						?>
						<table class="charitable-reports-v3-locked-sample">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Donor', 'charitable' ); ?></th>
									<th><?php echo esc_html__( 'Campaign', 'charitable' ); ?></th>
									<th class="num"><?php echo esc_html__( 'Amount', 'charitable' ); ?></th>
									<th class="num"><?php echo esc_html__( 'Abandoned', 'charitable' ); ?></th>
									<th class="num"><?php echo esc_html__( 'Status', 'charitable' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr><td>Marcus Webb</td><td>Winter Appeal</td><td class="num">$120.00</td><td class="num">2 days ago</td><td class="num">Recovered</td></tr>
								<tr><td>Priya Raman</td><td>Hurricane Relief Fund</td><td class="num">$45.00</td><td class="num">3 days ago</td><td class="num">Reminder sent</td></tr>
								<tr><td>Dana Whitfield</td><td>Animal Rescue</td><td class="num">$250.00</td><td class="num">4 days ago</td><td class="num">Recovered</td></tr>
								<tr><td>Tomas Beltran</td><td>Winter Appeal</td><td class="num">$30.00</td><td class="num">6 days ago</td><td class="num">No response</td></tr>
								<tr><td>Ellen Muir</td><td>Clean Water Project</td><td class="num">$75.00</td><td class="num">6 days ago</td><td class="num">Reminder sent</td></tr>
								<tr><td>Joseph Adeyemi</td><td>Animal Rescue</td><td class="num">$500.00</td><td class="num">8 days ago</td><td class="num">Recovered</td></tr>
							</tbody>
						</table>
					</div>

					<div class="charitable-reports-v3-locked-overlay">
						<div class="charitable-reports-v3-locked-card">
							<h5><?php echo esc_html__( 'Donation Recovery', 'charitable' ); ?></h5>
							<p>
								<?php
								// See the recurring card above for why the note leads.
								echo '' !== $charitable_dr_cta['note']
									? esc_html( $charitable_dr_cta['note'] ) . ' '
									: '';
								echo esc_html__( 'Some supporters start a donation and never finish. See how many, and win a share of them back automatically. The figures above are an example.', 'charitable' );
								?>
							</p>
							<div class="charitable-reports-v3-locked-actions">
								<a class="button button-primary" href="<?php echo esc_url( $charitable_dr_cta['url'] ); ?>"<?php echo $charitable_dr_cta['external'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $charitable_dr_cta['label'] ); ?></a>
								<a class="button" href="<?php echo esc_url( charitable_ga_url( 'https://www.wpcharitable.com/extensions/charitable-donation-recovery/', 'reports-overview', 'Donation Recovery learn more' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Learn More', 'charitable' ); ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>

		<?php endif; ?>

		<?php
		/*
		End of the main column, start of the rail.
		*/
		?>
		<?php if ( $charitable_reports_v3 ) : ?>
		</div><!-- /.charitable-reports-v3-main -->

		<div class="charitable-reports-v3-rail">
			<?php
			/*
			SITE ANALYSIS.

			Charitable_Dashboard::render_site_analysis_section() called directly - the same card
			the dashboard rail shows, rather than a second implementation of score rendering. It
			brings its own styles, and its button points at this page's own site-analysis tab,
			which from here is a jump down the page rather than a trip to another screen.

			ONE STATE, not two. Pro renders a dedicated scored card - score ring, band, four area
			bars - built from Charitable_Site_Analysis::get_last_known_score(). Lite's
			Charitable_Site_Analysis has no get_last_known_score() and its Charitable_Dashboard has
			none of the three band/scale colour helpers that card needs, so there is nothing here
			to build it from. The dashboard's own section already handles both the scored and
			unscored states, so Lite shows that and a Lite user sees the same card on this page
			that they already see on the Dashboard. If those four methods are ever ported, this is
			where the scored branch goes.

			Captured rather than printed so the collapse can take the section's CONTENT and leave
			its header standing - wrapping the whole thing collapses the title with it, and a card
			showing only a footer link does not say what it is. Guarded, not assumed: the
			replacement is counted, and if the dashboard's markup ever changes this falls back to
			wrapping the lot. The needle is the full opening tag because that class name also
			appears inside the <style> the method emits, where replacing it would corrupt the rule
			instead.
			*/
			$charitable_sa_dash = class_exists( 'Charitable_Dashboard' ) ? Charitable_Dashboard::get_instance() : null;

			if ( $charitable_sa_dash && method_exists( $charitable_sa_dash, 'render_site_analysis_section' ) ) :

				ob_start();
				$charitable_sa_dash->render_site_analysis_section();
				$charitable_sa_html = (string) ob_get_clean();

				$charitable_sa_swaps = 0;
				$charitable_sa_html  = str_replace(
					'<div class="charitable-dashboard-v2-section-content">',
					'<div class="charitable-dashboard-v2-section-content charitable-toggle-container">',
					$charitable_sa_html,
					$charitable_sa_swaps
				);
				?>
				<div class="charitable-container charitable-report-card charitable-reports-v3-analysis" data-section-name="site_analysis">
					<a href="#" class="charitable-toggle charitable-reports-v3-analysis-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
					<?php if ( 1 === $charitable_sa_swaps ) : ?>
						<?php echo $charitable_sa_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- dashboard-generated markup, escaped at source. ?>
					<?php else : ?>
						<div class="charitable-toggle-container charitable-report-ui">
							<?php echo $charitable_sa_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- dashboard-generated markup, escaped at source. ?>
						</div>
					<?php endif; ?>
				</div>
				<?php

			endif;
			?>

			<?php
			/*
			PRO ADDONS.

			Pro lists the addons a site is ALREADY running, with a live figure from each and an
			"Open" link. On Lite there is nothing to list, so the card answers the neighbouring
			question instead: what this page would also report on.

			The four here are deliberately NOT the two already upsold in the main column. Repeating
			Recurring and Recovery in the rail would spend the card restating what the reader has
			just scrolled past; these are the report-shaped addons that have no card of their own.

			Lettered tiles rather than artwork. Pro reads icons from the addon catalogue, which is
			keyed on what is installed - and inventing icons for things that are not would have to
			be unpicked the moment the catalogue is the source.
			*/
			$charitable_locked_addons = array(
				array(
					'label' => __( 'Ambassadors', 'charitable' ),
					'stat'  => __( 'Peer-to-peer and team fundraising', 'charitable' ),
					'slug'  => 'charitable-ambassadors',
				),
				array(
					'label' => __( 'Fee Relief', 'charitable' ),
					'stat'  => __( 'Let donors cover the processing fee', 'charitable' ),
					'slug'  => 'charitable-fee-relief',
				),
				array(
					'label' => __( 'PDF Receipts', 'charitable' ),
					'stat'  => __( 'Automatic tax receipts for every gift', 'charitable' ),
					'slug'  => 'charitable-pdf-receipts',
				),
				/*
				Annual Receipts rather than Tributes, for two reasons that happen to agree.

				Lite ships no Tributes icon - only Pro does, and Pro's is 176x160 from an older
				artwork generation, so it renders as a filled green glyph among three black
				line-art icons at 400x400. Importing it to ship in Lite for one upsell row would
				add an asset AND look wrong.

				It is also the better row for a REPORTS rail: year-end tax receipts is reporting,
				where in-memory giving is a donation-form feature.
				*/
				array(
					'label' => __( 'Annual Receipts', 'charitable' ),
					/*
					Not "year-end tax receipts for every donor": it wrapped to a second line,
					making this row taller than its three neighbours, and it repeated "tax
					receipts" from the PDF Receipts row directly above. This says the thing that
					actually distinguishes the two - one consolidated statement per donor per
					year, against one receipt per gift.
					*/
					'stat'  => __( 'One yearly statement per donor', 'charitable' ),
					'slug'  => 'charitable-annual-receipts',
				),
			);
			?>
			<div class="charitable-container charitable-report-card charitable-reports-v3-addons" data-section-name="pro_addons">
				<div class="header">
					<h4><?php echo esc_html__( 'Pro Addons', 'charitable' ); ?></h4>
					<span class="charitable-reports-v3-pro-pill"><?php echo esc_html__( 'Pro', 'charitable' ); ?></span>
					<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</div>
				<div class="charitable-toggle-container charitable-report-ui">
					<ul class="charitable-reports-v3-addon-list">
						<?php
						foreach ( $charitable_locked_addons as $charitable_addon_row ) :
							/*
							The addon's own artwork, falling back to a lettered tile.

							Same files the Addons screen reads, so the two screens cannot show
							different art for the same addon. A letter only appears where Lite
							genuinely ships no icon - which is honest about that, where the Addons
							screen's own last-resort placeholder (the Stripe logo) would not be.
							*/
							$charitable_addon_icon = function_exists( 'charitable_reports_v3_addon_icon' )
								? charitable_reports_v3_addon_icon( $charitable_addon_row['slug'] )
								: '';
							?>
							<li>
								<?php if ( '' !== $charitable_addon_icon ) : ?>
									<span class="charitable-reports-v3-addon-icon"><img src="<?php echo esc_url( $charitable_addon_icon ); ?>" alt="" width="26" height="26" /></span>
								<?php else : ?>
									<span class="charitable-reports-v3-addon-icon charitable-reports-v3-addon-icon--letter" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $charitable_addon_row['label'], 0, 1 ) : substr( $charitable_addon_row['label'], 0, 1 ) ); ?></span>
								<?php endif; ?>
								<span class="charitable-reports-v3-addon-meta">
									<b><?php echo esc_html( $charitable_addon_row['label'] ); ?></b>
									<span><?php echo esc_html( $charitable_addon_row['stat'] ); ?></span>
								</span>
								<a class="charitable-reports-v3-addon-link" href="<?php echo esc_url( charitable_ga_url( 'https://www.wpcharitable.com/extensions/' . $charitable_addon_row['slug'] . '/', 'reports-overview', $charitable_addon_row['label'] . ' addon card' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Learn', 'charitable' ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-addons' ) ); ?>"><?php echo esc_html__( 'View All Addons', 'charitable' ); ?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			</div>
		<?php endif; ?>

		<div class="charitable-container charitable-report-card charitable-payment-methods-report" data-section-name="payment_methods">
			<div class="header">
				<h4><?php echo esc_html__( 'Payment Methods', 'charitable' ); ?></h4>
				<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
				<div class="the-graph">
					<div id="charitable-payment-methods-graph" class="charitable-payment-methods-graph">
					</div>
					<div class="the-legend">
						<ul id="charitable-payment-methods-list">
							<?php echo $charitable_reports->generate_payment_methods_list( $charitable_payment_breakdown ); // phpcs:ignore ?>
						</ul>
					</div> <!-- the legend -->
				</div>
			</div>
			<?php if ( $charitable_reports_v3 ) : ?>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings&tab=gateways' ) ); ?>"><?php echo esc_html__( 'Payment Methods', 'charitable' ); ?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			<?php endif; ?>
		</div>

		<?php if ( $charitable_reports_v3 ) : ?>
			<?php
			/*
			DONATION SOURCES.

			The one analytics figure this page can show without a Google connection. The Google
			Analytics addon captures the donor's UTM parameters at donation insert and stores them
			as ordinary post meta, so "where did this money come from" is answerable locally - see
			Charitable_Reports::get_donation_attribution().

			It sits in the rail beside Payment Methods on purpose: both answer "how did this
			donation reach us", one by instrument and one by channel, and they share a layout and a
			colour ladder rather than each inventing one.

			NOT gated on the addon being active, and on Lite that is the point rather than a
			detail. The meta is post meta and outlives both deactivation and a DOWNGRADE, so a site
			that ran Pro with tracking for a season and then fell back to Lite still has a season of
			attribution worth reading. The two empty states distinguish "nothing was ever captured"
			from "tracking ran but this period has none", because those need different actions.
			*/
			$charitable_sources = $charitable_reports->get_donation_attribution( $charitable_report_overview_args );
			?>
			<div class="charitable-container charitable-report-card charitable-reports-v3-sources" data-section-name="donation_sources">
				<div class="header">
					<h4><?php echo esc_html__( 'Donation Sources', 'charitable' ); ?></h4>
					<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</div>
				<?php
				/*
				The id is the AJAX target. Filtering re-queries and injects regenerated markup per
				card, so this body is replaced wholesale on every filter change - and it is
				rendered through the same generate_* method the AJAX path uses, so the two can
				never produce different markup for the same figures.
				*/
				?>
				<div id="charitable-donation-sources" class="charitable-toggle-container charitable-report-ui">
					<?php echo $charitable_reports->generate_donation_sources_list( $charitable_sources, $charitable_reports->addon_is_installed( 'charitable-google-analytics' ) ); // phpcs:ignore ?>
				</div>
				<footer class="charitable-reports-v3-cardfoot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings&tab=marketing&sub_tab=google_analytics' ) ); ?>"><?php echo esc_html__( 'Google Analytics', 'charitable' ); ?><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></footer>
			</div>

			<?php
			/*
			EMAIL THIS REPORT - a locked preview, and in Lite that is all it ever is.

			The people who most want these figures - a board, a director, a treasurer - are the
			least likely to log in and look at them. A scheduled summary is the only thing on this
			page that reaches past the admin, which is why it sits here.

			In Pro this card is live: a real Charitable email, Charitable_Email_Report_Summary,
			registered in Charitable_Emails::register_emails(), with its own on/off toggle,
			subject, body template and recipients field, and an AJAX handler behind the switch.
			NONE of that machinery is in Lite and none of it is being added - this is static markup
			only. The switch carries `disabled`, the button carries aria-disabled and no href, and
			there is no handler to post to, so nothing here can be turned on by a crafted request
			or by removing an attribute in a devtools panel.

			The disabled control is SHOWN rather than hidden for the same reason the Pro card shows
			its button when the summary is off: hiding it makes the card look like it has nothing
			to offer, where a visibly inert control says there is something to switch on.

			aria-disabled plus a removed href on the <a>, not the disabled attribute: an <a> has no
			disabled state, and leaving the href in place would let a keyboard or a middle-click
			through a control that reads as inert.

			@since 1.8.14
			*/
			?>
			<div class="charitable-container charitable-report-card charitable-reports-v3-digest charitable-reports-v3-locked" data-section-name="email_report">
				<div class="header">
					<?php
					/*
					THE PILL GOES INSIDE THE HEADING, which is where the Site Analysis card puts
					its own - a <span> after the title text, dropped onto its own line by
					`display: table; margin: 5px 0 0` in this page's stylesheet. Put in the card
					BODY instead it would sit below the header's divider and read as part of the
					copy rather than as a label on the title.
					*/
					?>
					<h4><?php echo esc_html__( 'Email This Report', 'charitable' ); ?>
						<span class="charitable-reports-v3-pro-pill"><?php echo esc_html__( 'Pro', 'charitable' ); ?></span>
					</h4>
					<a href="#" class="charitable-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</div>
				<div class="charitable-reports-v3-locked-body charitable-toggle-container">
					<div class="charitable-reports-v3-locked-masked" aria-hidden="true">
						<div class="charitable-reports-v3-digest-body">
							<p><?php echo esc_html__( 'Send this overview at the start of each week, so your board sees the numbers without logging in.', 'charitable' ); ?></p>
							<div class="charitable-reports-v3-digest-controls">
								<label class="charitable-checkbox-label charitable-toggle-label" for="charitable-digest-toggle">
									<input type="checkbox" id="charitable-digest-toggle" class="charitable-toggle-input" value="1" disabled />
									<span class="charitable-toggle-switch" aria-hidden="true"></span>
								</label>
								<a class="button button-primary" id="charitable-digest-manage" aria-disabled="true"><?php echo esc_html__( 'Manage Weekly Summary', 'charitable' ); ?></a>
							</div>
							<p class="charitable-reports-v3-digest-state"><?php echo esc_html__( 'The weekly summary is off.', 'charitable' ); ?></p>
						</div>
					</div>

					<div class="charitable-reports-v3-locked-overlay">
						<div class="charitable-reports-v3-locked-card">
							<h5><?php echo esc_html__( 'Email This Report', 'charitable' ); ?></h5>
							<p><?php echo esc_html__( 'Send this overview to your board or your team at the start of each week, so the numbers reach the people who never log in.', 'charitable' ); ?></p>
							<div class="charitable-reports-v3-locked-actions">
								<a class="button button-primary" href="<?php echo esc_url( charitable_admin_upgrade_link( 'reports-overview', 'Email This Report Card' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Upgrade to Charitable Pro', 'charitable' ); ?></a>
								<?php
								/*
								The PUBLISHED slug. This pointed at /documentation/charitable-pro-email-report/,
								which has never existed - the page was written as "How to Email a Weekly
								Fundraising Summary in Charitable" and the link was never moved onto it, so
								Learn More returned a 404. Pro hardcodes the same dead url in
								charitable_report_digest_docs_url(), and that one is reachable from the working
								feature rather than from an upsell, so a redirect on wpcharitable.com is still
								owed for every copy already shipped.
								*/
								?>
								<a class="button" href="<?php echo esc_url( charitable_ga_url( 'https://www.wpcharitable.com/documentation/how-to-email-a-weekly-fundraising-summary-in-charitable/', 'reports-overview', 'Email This Report learn more' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Learn More', 'charitable' ); ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

	<?php if ( $charitable_reports_v3 ) : ?>
		</div><!-- /.charitable-reports-v3-rail -->
	</div><!-- /.charitable-reports-v3-cols -->
	<?php else : ?>
	</div>
	<?php endif; ?>

</div>
