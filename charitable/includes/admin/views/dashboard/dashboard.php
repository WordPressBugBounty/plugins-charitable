<?php
/**
 * Display the main reports page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.1
 */

require_once ABSPATH . 'wp-admin/includes/translation-install.php';

$gateways        = Charitable_Gateways::get_instance()->get_active_gateways_names();
$campaigns       = wp_count_posts( 'campaign' );
$campaigns_count = $campaigns->publish + $campaigns->draft + $campaigns->future + $campaigns->pending + $campaigns->private;
$emails          = charitable_get_helper( 'emails' )->get_enabled_emails_names();
$install         = isset( $_GET['install'] ) && $_GET['install']; // phpcs:ignore
$languages       = function_exists( 'wp_get_available_translations' ) ? wp_get_available_translations() : array();
$locale          = get_locale(); // phpcs:ignore
$language        = isset( $languages[ $locale ]['native_name'] ) ? $languages[ $locale ]['native_name'] : $locale;
$currency        = charitable_get_default_currency();
$currencies      = charitable_get_currency_helper()->get_all_currencies();

$charitable_dashboard = Charitable_Dashboard::get_instance();
$start_date           = $charitable_dashboard->get_start_date();
$end_date             = $charitable_dashboard->get_end_date();
$days                 = $charitable_dashboard->get_days();

$notices = $charitable_dashboard->get_notices();

$show_gt_chart_notice          = apply_filters( 'charitable_show_growth_tools_dashboard_notice', $charitable_dashboard->maybe_show_dashboard_growth_tool_chart_notice() );
$show_gt_chart_notice_headline = '';

if ( $show_gt_chart_notice ) {

	$total_donations_array         = (array) wp_count_posts( 'donation' ); // the function caches this, so we shouldn't have to.
	$total_donations               = array_sum( $total_donations_array );
	$show_gt_chart_notice_headline = ( false !== $total_donations_array && is_array( $total_donations_array ) && $total_donations > 0 ) ? esc_html__( 'No donations recently? Let Charitable help!', 'charitable' ) . ' 🚀' : esc_html__( 'Excited to make your first donation? Let Charitable help!', 'charitable' ) . ' 🚀';

	if ( $total_donations_array['publish'] > 0 ) {
		$show_gt_chart_notice = false;
	}
	$suggestion = Charitable_Guide_Tools::get_instance()->get_suggestion( 'dashboard', $total_donations );
	if ( false === $suggestion || ! is_array( $suggestion ) || empty( $suggestion ) ) {
		$show_gt_chart_notice = false;
	}
}

$is_cached = $charitable_dashboard->maybe_cache_dashboard();
$cached    = $charitable_dashboard->is_dashboard_data_cached();

$html = $charitable_dashboard->generate_dashboard_report_html();

ob_start();
?>
<div id="charitable-reports" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<?php
		/**
		 * Do or render something right before the dashboard area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_before_admin_dashboard' );
	?>

		<div class="tablenav top">
			<div class="alignleft actions">
				<h1><?php echo esc_html__( 'Dashboard', 'charitable' ); ?> <?php
				if ( $cached && $is_cached ) :
					echo '<span class="badge">Last cached ' . esc_html( $cached ) . '</span>';
				endif;
				?>
				</h1>
			</div>
			<div class="alignright">

				<form action="" method="post" target="_blank" class="charitable-report-print" id="charitable-dashboard-print">
					<input name="charitable_report_action" type="hidden" value="charitable_report_print_dashboard" />
					<input name="start_date" type="hidden" value="<?php echo esc_attr( $start_date ); ?>" />
					<input name="end_date" type="hidden" value="<?php echo esc_attr( $end_date ); ?>" />
					<input name="days" type="hidden" value="<?php echo esc_attr( $days ); ?>" />
					<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
					<button value="Print" type="submit" class="button with-icon charitable-report-print-button" title="<?php echo esc_html__( 'Print Summary', 'charitable' ); ?>" class="button with-icon charitable-report-ui" data-nonce="<?php echo wp_create_nonce( 'charitable_export_report' ); // phpcs:ignore ?>"><label><?php echo esc_html__( 'Print', 'charitable' ); ?></label><img width="15" height="15" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/print.svg' ); ?>" alt=""></button>
				</form>

				<?php echo $charitable_dashboard->get_filter_dropdown(); // phpcs:ignore ?>

				<input type="hidden" value="<?php echo esc_html( $start_date ); ?>" name="charitable-dashboard-report-start-date" id="charitable-dashboard-report-start-date">
				<input type="hidden" value="<?php echo esc_html( $end_date ); ?>" name="charitable-dashboard-report-end-date" id="charitable-dashboard-report-end-date">

			</div>
			<br class="clear">
		</div>

		<?php
		/**
		 * Do or render something after the dashboard title bar but before the reporting.
		 *
		 * @since 1.8.2
		 */
		do_action( 'charitable_before_admin_dashboard_reports' );
		?>

		<div id="charitable-dashboard-report-container">

			<div class="charitable-dashboard-report">

				<?php /* if ( $notices ) : ?>

					<div class="charitable-container charitable-report-card charitable-important" data-notice-ids="donation-security">
						<div class="header">
							<h4><img class="charitable-icon" src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/sms.svg'; ?>" /><?php echo esc_html__( 'Important', 'charitable' ); ?></h4>
							<a href="#" class="charitable-remove-dashboard-notice">X</a>
						</div>
						<div class="charitable-important-message">
							<?php echo $notices; ?>
							<p><a class="charitable-remove-dashboard-notice-link" href="#"><?php echo esc_html__( 'Dismiss this notice', 'charitable' ); ?></a></p>
						</div>
					</div>

				<?php endif; */ ?>

				<!--- dashboard welcome start -->

				<?php
				/**
				 * Do or render something after the dashboard title bar but before the reporting.
				 *
				 * @since 1.8.2
				 */
				do_action( 'charitable_admin_dashboard_notifications' );
				?>

				<div class="charitable-dashboard-title-cards">

					<?php /*

					<div class="charitable-container charitable-dashboard-title-card thank-you">

						<div class="charitable-title-card-content">

							<h1><?php echo esc_html__( 'Thank you for installing Charitable!', 'charitable' ); ?></h1>

							<p><?php echo esc_html__( 'Charitable is everything you need to start accepting donations today. Stripe, PayPal and offline donations work right out of the box, and when your organization is ready to grow, our extensions give you the tools you need to move forward.', 'charitable' ); ?></p>
							<a class="button-link" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>"><?php echo esc_html__( 'Create Campaign', 'charitable' ); ?></a>
							<?php if ( ! charitable_is_pro() ) : ?>
								<a class="button-link charitable-button-link-upgrade" target="_blank" href="https://wpcharitable.com/lite-vs-pro?referrer=chariable-dashboard"><?php echo esc_html__( 'Upgrade To Pro', 'charitable' ); ?></a>
							<?php endif; ?>
						</div>

					</div>

					<div class="charitable-container charitable-dashboard-title-card getting-started">

						<div class="charitable-title-card-content">

							<h3><?php echo esc_html__( 'Getting Started', 'charitable' ); ?></h3>
							<ul class="checklist">
								<?php if ( count( $gateways ) > 0 ) : ?>
									<li class="done">
									<?php
										printf(
											_x( 'You have activated %1$s. <a href="%2$s">Change settings</a>', 'You have activated x and y. Change gateway settings.', 'charitable' ), // phpcs:ignore
											charitable_list_to_sentence_part( $gateways ), // phpcs:ignore
											admin_url( 'admin.php?page=charitable-settings&tab=gateways' ) // phpcs:ignore
										);
									?>
									</li>
								<?php else : ?>
									<li class="not-done"><a href="<?php echo admin_url( 'admin.php?page=charitable-settings&tab=gateways' ); // phpcs:ignore ?>"><?php _e( 'You need to enable a payment gateway', 'charitable' ); // phpcs:ignore ?></a></li>
								<?php endif ?>
								<?php if ( $campaigns_count > 0 ) : ?>
									<li class="done">
									<?php
										printf(
											__( 'You have created your first campaign. <a href="%s">Create another one.</a>', 'charitable' ), // phpcs:ignore
											admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) // phpcs:ignore
										);
									?>
									</li>
								<?php else : ?>
									<li class="not-done"><a href="<?php echo admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ); // phpcs:ignore ?>"><?php _e( 'Create your first campaign', 'charitable' ); ?></a></li>
								<?php endif ?>
								<?php if ( count( $emails ) > 0 ) : ?>
									<li class="done">
									<?php
										printf(
											_x( 'You have turned on the %1$s. <a href="%2$s">Change settings</a>', 'You have activated x and y. Change email settings.', 'charitable' ), // phpcs:ignore
											charitable_list_to_sentence_part( $emails ), // phpcs:ignore
											admin_url( 'admin.php?page=charitable-settings&tab=emails' ) // phpcs:ignore
										);
									?>
									</li>
								<?php else : ?>
									<li class="not-done"><a href="<?php echo admin_url( 'admin.php?page=charitable-settings&tab=emails' ); // phpcs:ignore ?>"><?php _e( 'Turn on email notifications', 'charitable' ); // phpcs:ignore ?></a></li>
								<?php endif ?>
							</ul>

						</div>

					</div> */ ?>

					<?php
						/**
						 * Do or render something above dashboard items (like a notice).
						 *
						 * @since 1.8.1.15
						 */
						do_action( 'charitable_after_getting_started_dashboard' );
					?>

				</div>

				<!--- dashboard welcome end -->

				<div class="charitable-headline-reports">

					<div id="charitable-dashboard-report-cards">
						<?php echo $html['charitable_cards']; // phpcs:ignore ?>
					</div>

					<div class="charitable-container charitable-report-ui charitable-headline-graph-container
					<?php
					if ( $show_gt_chart_notice ) :
						?>
						charitable-with-growth-tools<?php endif; ?>">

						<?php if ( $show_gt_chart_notice ) : ?>

							<div id="charitable-growth-tools-notice" class="charitable-growth-tools-notice charitable-growth-tools-dashboard charitable-hidden" data-notice-type="dashboard" data-nonce="<?php echo wp_create_nonce( 'charitable_dismiss_growth_tools' ); // phpcs:ignore ?>">

								<div class="charitable-growth-tools-notice-interior">

									<?php

										$notice_html = Charitable_Guide_Tools::get_instance()->get_dashboard_notice_html( $suggestion, $show_gt_chart_notice_headline );
										echo $notice_html; // phpcs:ignore

									?>

								</div>

							</div>

						<?php endif; ?>

						<div id="charitable-headline-graph" class="charitable-headline-graph"></div>

					</div>

				</div>

				<div class="charitable-section charitable-section-flexible">

					<div id="charitable-dashboard-report-sections">
						<?php echo $html['charitable_reports']; // phpcs:ignore ?>
					</div>

				</div>

			</div>

		</div>

	<?php
		/**
		 * Do or render something right after the dashboard area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_after_admin_dashboard' );
	?>
</div>
<?php
echo ob_get_clean(); // phpcs:ignore
