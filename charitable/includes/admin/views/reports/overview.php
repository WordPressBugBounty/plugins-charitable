<?php
/**
 * Display the main reports "overview" page.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8,1
 * @version   1.8.1
 */

$start_date  = false;
$end_date    = false;
$filter      = false;
$campaign_id = false;
$category_id = false;
$status      = false; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

$charitable_reports = Charitable_Reports::get_instance();

// Check the transients via charitable_report_overview_args.
$report_data_args = false; // get_transient( 'charitable-report-overview-args' );

if ( false !== $report_data_args ) {

	$start_date  = ( false === $start_date && ! empty( $report_data_args['start_date'] ) ) ? $charitable_reports->get_valid_date_string( $report_data_args['start_date'] ) : false;
	$end_date    = ( false === $end_date && ! empty( $report_data_args['end_date'] ) ) ? $charitable_reports->get_valid_date_string( $report_data_args['end_date'] ) : false;
	$filter      = ( false === $filter && ! empty( $report_data_args['filter'] ) ) ? intval( $report_data_args['filter'] ) : 'test';
	$campaign_id = ( false === $campaign_id && ! empty( $report_data_args['campaign_id'] ) ) ? intval( $report_data_args['campaign_id'] ) : false;
	$category_id = ( false === $category_id && ! empty( $report_data_args['category_id'] ) ) ? intval( $report_data_args['category_id'] ) : false;

}

if ( false === $start_date || false === $end_date || false === $filter ) {
	// so nothing from the transient, so check the $_GET.
	$start_date  = isset( $_GET['start_date'] ) ? $charitable_reports->get_valid_date_string( sanitize_text_field( $_GET['start_date'] ) ) : false; // phpcs:ignore
	$end_date    = isset( $_GET['end_date'] ) ? $charitable_reports->get_valid_date_string( sanitize_text_field( $_GET['end_date'] ) ) : false; // phpcs:ignore
	$filter      = isset( $_GET['filter'] ) ? intval( $_GET['filter'] ) : false; // phpcs:ignore
	$campaign_id = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : false; // phpcs:ignore
	$category_id = isset( $_GET['category_id'] ) ? intval( $_GET['category_id'] ) : false; // phpcs:ignore
}

if ( false === $start_date || false === $end_date || false === $filter ) {
	// If still nothing assign defaults.
	$start_date  = gmdate( 'Y/m/d', strtotime( '-7 days' ) );
	$end_date    = gmdate( 'Y/m/d' );
	$filter      = 7;
	$campaign_id = -1; // cannot be zero because that would be false.
	$category_id = 0;
}

$charitable_report_overview_args = apply_filters(
	'charitable_report_overview_args',
	array(
		'start_date'  => $start_date,
		'end_date'    => $end_date,
		'filter'      => $filter,
		'campaign_id' => $campaign_id,
		'category_id' => $category_id,
	)
);

$charitable_reports->init_with_array( 'overview', $charitable_report_overview_args );

// main activity.
$report_activity = $charitable_reports->get_activity();

// donations.
$donations              = $charitable_reports->get_donations();
$total_count_donations  = count( $donations );
$total_amount_donations = $charitable_reports->get_donations_total();
$donation_breakdown     = $charitable_reports->get_donations_by_day();
$payment_breakdown      = $charitable_reports->get_donations_by_payment();
$donation_average       = ( $total_amount_donations > 0 && $total_amount_donations > 0 ) ? ( charitable_format_money( $total_amount_donations / $total_count_donations, 2, true ) ) : charitable_format_money( 0 );
$donation_total         = charitable_format_money( $total_amount_donations );

// donors.
$top_donors         = $charitable_reports->get_top_donors_overview();
$total_count_donors = $charitable_reports->get_donors_count();
$total_donors_label = ( 1 === $total_count_donors ) ? esc_html__( 'Donor', 'charitable' ) : esc_html__( 'Donors', 'charitable' );

// refunds.
$refunds_data         = $charitable_reports->get_refunds();
$total_amount_refunds = $refunds_data['total_amount'];
$total_count_refunds  = $refunds_data['total_count'];

// campaigns.
$args              = array( 'posts_per_page' => apply_filters( 'charitable_overview_top_campaigns_amount', 20 ) );
$top_campaigns     = Charitable_Campaigns::ordered_by_amount( $args );
$campaign_dropdown = array();
if ( ! empty( $top_campaigns->posts ) ) :
	$campaign_dropdown = wp_list_pluck( $top_campaigns->posts, 'post_title', 'ID' );
	$campaign_dropdown = array_map( 'esc_html', $campaign_dropdown );
	// sort by title.
	asort( $campaign_dropdown );
endif;

$campaign_categories  = $charitable_reports->get_campaign_categories();
$campaign_category_id = 0;

?>

<div class="tablenav top">
	<div class="alignleft actions">
		<label for="report-campaign-filter" class="screen-reader-text"><?php echo esc_html__( 'Select Campaign', 'charitable' ); ?></label>
		<select name="action" id="report-campaign-filter">
			<option value="-1"><?php echo esc_html__( 'Showing Results for', 'charitable' ); ?> <strong><?php echo esc_html__( 'All Campaigns', 'charitable' ); ?></strong></option>
			<?php if ( ! empty( $campaign_dropdown ) ) : ?>
				<?php foreach ( $campaign_dropdown as $campaign_dropdown_id => $campaign_title ) : ?>
				<option value="<?php echo intval( $campaign_dropdown_id ); ?>"><?php echo esc_html( $campaign_title ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<?php if ( ! empty( $campaign_categories ) ) : ?>
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
						'selected'          => $campaign_category_id,
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
	</div>
	<div class="alignright">

		<form action="" method="post" target="_blank" class="charitable-report-print" id="charitable-overview-print">
			<input name="charitable_report_action" type="hidden" value="charitable_report_print_overview" />
			<input name="start_date" type="hidden" value="<?php echo esc_attr( $start_date ); ?>" />
			<input name="end_date" type="hidden" value="<?php echo esc_attr( $end_date ); ?>" />
			<input name="campaign_id" type="hidden" value="<?php echo intval( $campaign_id ); ?>" />
			<input name="category_id" type="hidden" value="<?php echo intval( $category_id ); ?>" />
			<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
			<button value="Download CSV" type="submit" class="button with-icon charitable-report-print-button" title="<?php echo esc_html__( 'Print Summary', 'charitable' ); ?>" class="button with-icon charitable-report-ui" data-nonce="<?php echo wp_create_nonce( 'charitable_export_report' ); // phpcs:ignore ?>"><label><?php echo esc_html__( 'Print', 'charitable' ); ?></label><img width="15" height="15" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/print.svg' ); ?>" alt=""></button>
		</form>

		<input type="text" id="charitable-reports-topnav-datepicker" class="charitable-reports-datepicker charitable-datepicker-ranged" data-start-date="<?php echo esc_attr( $start_date ); ?>" data-end-date="<?php echo esc_attr( $end_date ); ?>" value="<?php echo esc_attr( $start_date ); ?> - <?php echo esc_attr( $end_date ); ?>" />

		<div class="charitable-datepicker-container"><a href="#" class="button button-primary" id="charitable-reports-filter-button" data-filter-type="overview"><?php echo esc_html__( 'Filter', 'charitable' ); ?></a></div>

	</div>
	<br class="clear">
</div>

<div class="charitable-overview-report">

	<div class="charitable-headline-reports">

		<div class="charitable-cards">
			<div class="charitable-container charitable-report-ui charitable-card">
				<strong><span id="charitable-top-donation-total-amount"><?php echo esc_html( $donation_total ); ?></span></strong>
				<p><span id="charitable-top-donation-total-count"><?php echo count( $donations ); ?></span> <?php echo esc_html__( 'Total Donations (Net)', 'charitable' ); ?></p>
			</div>
			<div class="charitable-container charitable-report-ui charitable-card">
				<strong><span id="charitable-top-donation-average"><?php echo esc_html( $donation_average ); ?></span></strong>
				<p><?php echo esc_html__( 'Average Donation', 'charitable' ); ?></p>
			</div>
			<div class="charitable-container charitable-report-ui charitable-card">
				<strong><span id="charitable-top-donor-count"><?php echo intval( $total_count_donors ); ?></span></strong>
				<p><?php echo esc_html( $total_donors_label ); ?></p>
			</div>
			<div class="charitable-container charitable-report-ui charitable-card">
				<strong><span id="charitable-top-refund-total-amount"><?php echo charitable_format_money( $total_amount_refunds ); // phpcs:ignore  ?></span></strong>
				<p><span id="charitable-top-refund-count"><?php echo intval( $total_count_refunds ); ?></span> <?php echo esc_html__( 'Refunds', 'charitable' ); ?></p>
			</div>
		</div>

		<div class="charitable-container charitable-report-ui charitable-headline-graph-container">
			<div id="charitable-headline-graph" class="charitable-headline-graph">
			</div>
		</div>

	</div>

	<div class="tablenav charitable-section">

		<div class="alignleft actions">
			<h2><?php echo esc_html__( 'Donations Breakdown', 'charitable' ); ?></h2>
		</div>
		<div class="alignright">

			<form target="_blank" action="" method="post" class="charitable-report-download-form" id="charitable-donations-breakdown-download-form">
				<input name="charitable_report_action" type="hidden" value="charitable_report_download_donation_breakdown" />
				<input name="start_date" type="hidden" value="<?php echo esc_attr( $start_date ); ?>" />
				<input name="end_date" type="hidden" value="<?php echo esc_attr( $end_date ); ?>" />
				<input name="campaign_id" type="hidden" value="<?php echo intval( $campaign_id ); ?>" />
				<input name="category_id" type="hidden" value="<?php echo intval( $category_id ); ?>" />
				<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
				<button value="Download CSV" type="submit" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" class="button with-icon charitable-report-ui" data-nonce="<?php echo wp_create_nonce( 'charitable_export_report' ); // phpcs:ignore ?>"><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>
			</form>

		</div>
		<br class="clear">

		<table class="wp-list-table widefat fixed striped table-view-list donations-breakdown charitable-report-ui">
			<thead>
				<tr>
					<th scope="col" id="date" class="manage-column column-date"><span><?php echo esc_html__( 'Date', 'charitable' ); ?></span></a></th>
					<th scope="col" id="donations" class="manage-column column-donations"><?php echo esc_html__( 'Donations', 'charitable' ); ?></th>
					<th scope="col" id="no-of-donors" class="manage-column column-donors"><?php echo esc_html__( 'No. of Donors', 'charitable' ); ?></th>
					<th scope="col" id="refunds" class="manage-column column-refunds"><?php echo esc_html__( 'Refunds', 'charitable' ); ?></th>
					<th scope="col" id="net" class="manage-column column-net"><?php echo esc_html__( 'Net', 'charitable' ); ?></th>
				</tr>
			</thead>
			<tbody id="donations-breakdown-list">
				<?php echo $charitable_reports->generate_donations_breakdown_rows( $donation_breakdown, $refunds_data ); // phpcs:ignore ?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="col" id="title" class="manage-column column-titl"><span><?php echo esc_html__( 'Date', 'charitable' ); ?></span></a></th>
					<th scope="col" id="creator" class="manage-column column-creator"><?php echo esc_html__( 'Donations', 'charitable' ); ?></th>
					<th scope="col" id="donated" class="manage-column column-donated"><?php echo esc_html__( 'No. of Donors', 'charitable' ); ?></th>
					<th scope="col" id="status" class="manage-column column-status"><?php echo esc_html__( 'Refunds', 'charitable' ); ?></th>
					<th scope="col" id="end_date" class="manage-column column-end_date"><?php echo esc_html__( 'Net', 'charitable' ); ?></th>
				</tr>
			</tfoot>
		</table>

		<br class="clear">

	</div>

	<div class="charitable-section-grid charitable-section-grid-column-flexible">

		<div class="charitable-container charitable-report-card charitable-activity-report" data-section-name="activity">
			<div class="header">
				<h4><?php echo esc_html__( 'Activity', 'charitable' ); ?></h4>
				<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
				<div class="the-list">
					<?php echo $charitable_reports->generate_activity_list( $report_activity ); // phpcs:ignore ?>
				</div>
			</div>
		</div>

		<div class="charitable-container charitable-report-card charitable-top-donors-report">
			<div class="header">
				<h4><?php echo esc_html__( 'Top Donors', 'charitable' ); ?></h4>
				<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
				<?php echo $charitable_reports->generate_top_donors( $top_donors ); // phpcs:ignore ?>
			</div>
		</div>

	</div>

	<div class="charitable-section-grid one-third charitable-section-grid-column-flexible">

		<div class="charitable-container charitable-report-card charitable-top-campaigns-report">
			<div class="header">
				<h4><?php echo esc_html__( 'Top Campaigns', 'charitable' ); ?></h4>
				<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
			<?php if ( ! empty( $top_campaigns->posts ) ) : ?>
				<div class="the-list">
					<ul id="charitable-top-campaigns-list">
						<?php echo $charitable_reports->generate_top_campaigns( $top_campaigns ); // phpcs:ignore ?>
					</ul>
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
		</div>

		<div class="charitable-container charitable-report-card charitable-payment-methods-report">
			<div class="header">
				<h4><?php echo esc_html__( 'Payment Methods', 'charitable' ); ?></h4>
				<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
			</div>
			<div class="charitable-toggle-container charitable-report-ui">
				<div class="the-graph">
					<div id="charitable-payment-methods-graph" class="charitable-payment-methods-graph">
					</div>
					<div class="the-legend">
						<ul id="charitable-payment-methods-list">
							<?php echo $charitable_reports->generate_payment_methods_list( $payment_breakdown ); // phpcs:ignore ?>
						</ul>
					</div> <!-- the legend -->
				</div>
			</div>
		</div>

	</div>

</div>