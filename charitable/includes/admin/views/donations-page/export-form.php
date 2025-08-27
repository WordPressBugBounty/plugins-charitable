<?php
/**
 * Display the export button in the donation filters box.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Donations Page
 * @since   1.0.0
 * @version 1.6.39
 */

/**
 * Filter the class to use for the modal window.
 *
 * @since 1.0.0
 *
 * @param string $class The class name.
 */
$modal_class = apply_filters( 'charitable_modal_window_class', 'charitable-modal' );

$start_date  = isset( $_GET['start_date'] ) ? charitable_sanitize_date_export_format( $_GET['start_date'] ) : null; // phpcs:ignore
$end_date    = isset( $_GET['end_date'] ) ? charitable_sanitize_date_export_format( $_GET['end_date'] ) : null; // phpcs:ignore
$post_status = isset( $_GET['post_status'] ) ? esc_html( $_GET['post_status'] ) : 'all'; // phpcs:ignore
$report_type = isset( $_GET['report_type'] ) ? esc_html( $_GET['report_type'] ) : 'donations'; // phpcs:ignore

$campaigns = get_posts(
	array(
		'post_type'      => 'campaign',
		'post_status'    => array( 'draft', 'pending', 'private', 'publish' ),
		'perm'           => 'readable',
		'posts_per_page' => -1,
	)
);

/**
 * Filter the type of exportable report types.
 *
 * @since 1.6.0
 *
 * @param array $types Types of reports.
 */
$report_types = apply_filters(
	'charitable_donation_export_report_types',
	array(
		'donations' => __( 'Donations', 'charitable' ),
	)
);
?>
<div id="charitable-donations-export-modal" style="display: none;" class="charitable-donations-modal <?php echo esc_attr( $modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php esc_html_e( 'Export Donations', 'charitable' ); ?></h3>
	<form class="charitable-donations-modal-form charitable-modal-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<?php wp_nonce_field( 'charitable_export_donations', '_charitable_export_nonce' ); ?>
		<input type="hidden" name="charitable_action" value="export_donations" />
		<input type="hidden" name="page" value="charitable-donations-table" />
		<fieldset>
			<legend><?php esc_html_e( 'Filter by Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-export-start_date" name="start_date" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $start_date ); ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-export-end_date" name="end_date" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $end_date ); ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-donations-export-status"><?php esc_html_e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-donations-export-status" name="status">
			<option value="all" <?php selected( $post_status, 'all' ); ?>><?php esc_html_e( 'All', 'charitable' ); ?></option>
			<?php foreach ( charitable_get_valid_donation_statuses() as $key => $status ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $post_status, $key ); ?>><?php echo esc_html( $status ); ?></option>
			<?php endforeach ?>
		</select>
		<label for="charitable-donations-export-campaign"><?php esc_html_e( 'Filter by Campaign', 'charitable' ); ?></label>
		<select id="charitable-donations-export-campaign" name="campaign_id">
			<option value="all"><?php esc_html_e( 'All Campaigns', 'charitable' ); ?></option>
			<?php
			foreach ( $campaigns as $campaign ) :
				?>
				<option value="<?php echo esc_attr( $campaign->ID ); ?>"><?php echo esc_html( get_the_title( $campaign->ID ) ); ?></option>
			<?php endforeach ?>
		</select>
		<?php if ( count( $report_types ) > 1 ) : ?>
			<label for="charitable-donations-export-report-type"><?php esc_html_e( 'Type of Report', 'charitable' ); ?></label>
			<select id="charitable-donations-export-report-type" name="report_type">
			<?php foreach ( $report_types as $key => $report_label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $report_label ); ?></option>
			<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input type="hidden" name="report_type" value="<?php echo esc_attr( key( $report_types ) ); ?>" />
		<?php endif ?>
		<?php
		/**
		 * Add additional fields to the end of the donations export form.
		 *
		 * @since 1.4.0
		 */
		do_action( 'charitable_export_donations_form' );
		?>
		<button name="charitable-export-donations" class="button button-primary"><?php esc_html_e( 'Export', 'charitable' ); ?></button>
	</form>
</div>
