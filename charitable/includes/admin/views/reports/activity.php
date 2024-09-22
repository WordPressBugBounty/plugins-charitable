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

$start_date    = false;
$end_date      = false;
$campaign_id   = false;
$activity_type = false;

// first check and see if the database tables for activity exist.
$charitable_donation_activities_db = Charitable_Donation_Activities_DB::get_instance();
$charitable_campaign_activities_db = Charitable_Campaign_Activities_DB::get_instance();
$db_tables_installed               = false;
$install_link                      = admin_url( 'index.php?page=charitable-upgrades&charitable-upgrade=create_activity_tables&nonce=' . wp_create_nonce( 'charitable-upgrade' ) );

// check to see if db tables exist.
if ( $charitable_donation_activities_db->table_exists() && $charitable_campaign_activities_db->table_exists() ) {

	$db_tables_installed = true;

	$charitable_reports = Charitable_Reports::get_instance();

	// Check the transients via charitable_report_overview_args.
	$report_data_args = false; // get_transient( 'charitable-report-activity-args' );

	if ( false !== $report_data_args ) {

		$start_date    = ( false === $start_date && ! empty( $report_data_args['start_date'] ) ) ? $charitable_reports->get_valid_date_string( $report_data_args['start_date'] ) : false;
		$end_date      = ( false === $end_date && ! empty( $report_data_args['end_date'] ) ) ? $charitable_reports->get_valid_date_string( $report_data_args['end_date'] ) : false;
		$campaign_id   = ( false === $campaign_id && ! empty( $report_data_args['campaign_id'] ) ) ? intval( $report_data_args['campaign_id'] ) : false;
		$activity_type = ( false === $activity_type && ! empty( $report_data_args['activity_type'] ) ) ? esc_attr( $report_data_args['activity_type'] ) : false;
	}

	if ( false === $start_date || false === $end_date || false === $campaign_id ) {
		// so nothing from the transient, so check the $_GET.
		$start_date    = isset( $_GET['start_date'] ) ? $charitable_reports->get_valid_date_string( sanitize_text_field( $_GET['start_date'] ) ) : false; // phpcs:ignore
		$end_date      = isset( $_GET['end_date'] ) ? $charitable_reports->get_valid_date_string( sanitize_text_field( $_GET['end_date'] ) ) : false; // phpcs:ignore
		$campaign_id   = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : false; // phpcs:ignore
		$activity_type = isset( $_GET['activity_type'] ) ? esc_attr( $_GET['activity_type'] ) : false; // phpcs:ignore
	}

	if ( false === $start_date || false === $end_date || false === $campaign_id ) {
		// If still nothing assign defaults.
		$charitable_report_overview_defaults = apply_filters(
			'charitable_report_activity_defaults',
			array(
				'start_date'    => gmdate( 'Y/m/d', strtotime( '-7 days' ) ),
				'end_date'      => gmdate( 'Y/m/d' ),
				'campaign_id'   => -1,
				'activity_type' => '',
				'limit'         => false,
			)
		);

		$start_date    = $charitable_report_overview_defaults['start_date'];
		$end_date      = $charitable_report_overview_defaults['end_date'];
		$campaign_id   = $charitable_report_overview_defaults['campaign_id'];
		$activity_type = $charitable_report_overview_defaults['activity_type'];

	}

		$args = array(
			'start_date'  => $start_date,
			'end_date'    => $end_date,
			'campaign_id' => $campaign_id,
		);
		$charitable_reports->init_with_array( 'activity', $args );

		// activity (filter and action) types.

		// Activity types is a filter from the dropdown on the activity page.
		// Empty means no filter. Example: 'campaigns-created'.
		// $activity_filter_types = array( 'campaigns-created' );.
		$activity_filter_types = array();

		// Activity action types are actions found in the database for filtering.
		// For example this is filtering for donation and campaign activities...
		// $activity_action_types = array(
		// 'donation' => array( 'charitable-completed' ),
		// 'campaign' => array( 'charitable-campaign-created', 'charitable-campaign-ended', 'charitable-campaign-goal-reached' ),
		// );.
		$activity_action_types = array();

		// cammpaigns.
		$campaign_args       = false;
		$available_campaigns = Charitable_Campaigns::get_campaign_title_id( $campaign_args );
		$campaign_dropdown   = array();
		if ( ! empty( $available_campaigns ) ) :
			$campaign_dropdown = wp_list_pluck( $available_campaigns, 'title', 'id' );
			// sort by title (array value) alphabetically.
			asort( $campaign_dropdown );
		endif;

		$activity_args   = array(
			'campaign_id'           => $campaign_id,
			'activity_filter_types' => $activity_filter_types,
			'activity_action_types' => $activity_action_types,
		);
		$report_activity = $charitable_reports->get_activity( $activity_args );

}

if ( $db_tables_installed ) :

	?>

	<?php if ( charitable_is_pro() ) : ?>
	<div class="tablenav top with-margin">
		<div class="alignleft actions">

			<label for="report-campaign-filter" class="screen-reader-text"><?php echo esc_html__( 'Show Activity', 'charitable' ); ?></label>
			<select name="category_id" id="report-campaign-filter">
				<option value="-1"><?php echo esc_html__( 'Showing activity for', 'charitable' ); ?> <?php echo esc_html__( 'All Campaigns', 'charitable' ); ?></option>
				<?php if ( is_array( $campaign_dropdown ) && ! empty( $campaign_dropdown ) ) : ?>
					<?php foreach ( $campaign_dropdown as $campaign_dropdown_id => $campaign_title ) : ?>
					<option value="<?php echo intval( $campaign_dropdown_id ); ?>"><?php echo esc_html( $campaign_title ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>

		</div>
		<div class="alignright">

			<?php echo $charitable_reports->get_activity_report_filter_dropdown( $activity_filter_types ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<input type="text" id="charitable-reports-topnav-datepicker" class="charitable-reports-datepicker charitable-datepicker-ranged" data-start-date="<?php echo esc_html( $start_date ); ?>" data-end-date="<?php echo esc_html( $end_date ); ?>" value="<?php echo esc_html( $start_date ); ?> - <?php echo esc_html( $end_date ); ?>" />

			<div class="charitable-datepicker-container"><a href="#" class="button button-primary" id="charitable-reports-filter-button" data-filter-type="activity"><?php echo esc_html__( 'Filter', 'charitable' ); ?></a></div>

		</div>
		<br class="clear">
	</div>
	<?php endif; ?>
<?php endif; ?>

<div class="charitable-activity-report">

	<div class="charitable-container charitable-title-card">

		<div class="charitable-title-card-content">

			<h1><?php echo esc_html__( 'Campaign Activity', 'charitable' ); ?></h1>

			<p><?php echo esc_html__( 'This report shows what donations and events are happening on the various Charitable campaigns on your site.', 'charitable' ); ?></p>

			<p><strong><?php echo esc_html__( 'Why This Is Important', 'charitable' ); ?>:</strong> <?php echo esc_html__( 'This gives you a "heads up" timeline in chronological order of what is happening on your site, allowing you to quickly see if a particular campaign is suddenly receiving donations or is getting popular - allowing you to act accordingly.', 'charitable' ); ?> </p>

			<?php do_action( 'charitable_after_activity_report_description' ); ?>

		</div>

	</div>

	<?php if ( ! $db_tables_installed ) : ?>

		<div class="charitable-reports-notice">
			<p><?php echo esc_html__( 'The database tables for the activity report have not been installed. Please click the button below to install them.', 'charitable' ); ?></p>
			<p><a href="<?php echo esc_url( $install_link ); ?>" class="button button-primary"><?php echo esc_html__( 'Install Database Tables', 'charitable' ); ?></a></p>
		</div>

	<?php else : ?>

	<div class="tablenav charitable-section charitable-tablenav-activity">

		<div class="alignleft actions">
				<h2><?php echo esc_html__( 'Activities', 'charitable' ); ?></h2>
		</div>
		<div class="alignright">

			<?php if ( ! charitable_is_pro() ) : ?>

				<button disabled="disabled" value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/download.svg'; // phpcs:ignore ?>" alt=""></button>

			<?php else : ?>

			<form action="" method="post" class="charitable-report-download-form" id="charitable-activity-download-form">
				<input name="charitable_report_action" type="hidden" value="charitable_report_download_activity">
				<input name="start_date" type="hidden" value="<?php echo esc_html( $start_date ); ?>" />
				<input name="end_date" type="hidden" value="<?php echo esc_html( $end_date ); ?>" />
				<input name="campaign_id" type="hidden" value="<?php echo intval( $campaign_id ); ?>" />
				<input name="activity_type" type="hidden" value="<?php echo esc_attr( $activity_type ); ?>">
				<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
				<button
				<?php
				if ( empty( $report_activity ) ) :
					?>
					disabled="true"<?php endif; ?> value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" type="submit" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/download.svg'; // phpcs:ignore ?>" alt=""></button>
			</form>

			<?php endif; ?>

		</div>

		<br class="clear">

		<div class="charitable-activity-list-container">

			<div class="charitable-report-table-container">

				<?php do_action( 'charitable_report_before_activity_table' ); ?>

				<?php
				if ( ! charitable_is_pro() ) :
					?>
					<div class="charitable-restricted"><div class="restricted-access-overlay"></div><?php endif; ?>

					<?php

					if ( ! empty( $report_activity ) ) :

						?>

						<div class="the-list charitable-the-list-container charitable-no-scroll">
							<?php

							echo $charitable_reports->generate_activity_list( $report_activity ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							?>
						</div>

					<?php else : ?>

						<div class="charitable-no-results">
							<p><?php echo esc_html__( 'No activity found.', 'charitable' ); ?></p>
						</div>

					<?php endif; ?>

				<?php
				if ( ! charitable_is_pro() ) :
					?>
					</div><?php endif; ?>

				<?php do_action( 'charitable_report_after_activity_table' ); ?>

			</div>

		</div>

	</div> <!-- .charitable-section -->

	<?php endif; ?>

</div> <!-- .charitable-activity-report -->

<?php
