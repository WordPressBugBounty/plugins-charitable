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

$report_type = charitable_reports_get_donor_report_type();

$args           = array();
$args['limit']  = isset( $_GET['limit'] ) ? intval( $_GET['limit'] ) : charitable_reports_get_pagination_per_page( $report_type ); // phpcs:ignore
$args['offset'] = isset( $_GET['offset'] ) ? intval( $_GET['offset'] ) : 0; // phpcs:ignore
$args['ppage']  = isset( $_GET['ppage'] ) ? intval( $_GET['ppage'] ) : 1; // phpcs:ignore

$charitable_reports = Charitable_Reports::get_instance();
$charitable_reports->init_with_array( $report_type, $args );

$donor_breakdown = $charitable_reports->get_donor_report_by_type( $report_type, $args );

?>

<div class="tablenav top with-margin">
	<div class="alignleft actions">
		<?php if ( ! charitable_is_pro() ) : ?>
			<label for="report-donor-type-filter" class="screen-reader-text"><?php echo esc_html__( 'Available Reports', 'charitable' ); ?></label>
			<div class="charitable-datepicker-container"><label for=""><?php echo esc_html__( 'Available Reports', 'charitable' ); ?>:</label>
				<?php echo $charitable_reports->get_donor_report_type_dropdown( $report_type ); // phpcs:ignore ?>
			</div>
		<?php else : ?>
			<label for="report-donor-type-filter" class="screen-reader-text"><?php echo esc_html__( 'Select Report', 'charitable' ); ?></label>
			<div class="charitable-datepicker-container"><label for="report-donor-type-filter"><?php echo esc_html__( 'Select Report', 'charitable' ); ?>:</label>
				<?php echo $charitable_reports->get_donor_report_type_dropdown( $report_type ); // phpcs:ignore ?>
				<input type="hidden" name="report-donor-limit" id="report-donor-limit" value="<?php echo intval( $args['limit'] ); ?>">
				<input type="hidden" name="report-donor-ppage" id="report-donor-ppage" value="<?php echo intval( $args['ppage'] ); ?>">
				<input type="hidden" name="report-donor-offset" id="report-donor-offset" value="<?php echo intval( $args['offset'] ); ?>">
			</div>
		<?php endif; ?>
	</div>
	<div class="alignright">
		<?php if ( charitable_is_pro() ) : ?>
			<div class="charitable-datepicker-container"><a href="#" class="button button-primary" id="charitable-reports-filter-button" data-filter-type="donor"><?php echo esc_html__( 'Filter', 'charitable' ); ?></a></div>
		<?php endif; ?>
	</div>
	<br class="clear">
</div>

<div class="charitable-donors-report">

	<div class="charitable-container charitable-title-card" id="charitable-report-donor-title-card">

		<div class="charitable-title-card-content">

			<?php echo $charitable_reports->generate_title_card_html( $report_type ); // phpcs:ignore ?>

		</div>

	</div>

	<div id="charitable-report-donor-container" class="tablenav charitable-section">

		<?php
			echo $charitable_reports->generate_donor_breakdown_table_html( $report_type, $donor_breakdown, $args['ppage'], $args['limit'] ); // phpcs:ignore
		?>

	</div>

</div>