<?php
/**
 * Charitable Dashboard class.
 *
 * @package   Charitable/Classes/Charitable_Dashboard
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.9.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Dashboard' ) ) :

	/**
	 * Charitable_Dashboard
	 *
	 * @final
	 * @since 1.8.8
	 */
	final class Charitable_Dashboard {

	/**
	 * ========================================
	 * TEMPORARY TESTING MODE - EASY REVERT
	 * ========================================
	 *
	 * To show empty dashboard state for testing:
	 * - Set SHOW_EMPTY_DASHBOARD = true
	 *
	 * To revert to normal behavior:
	 * - Set SHOW_EMPTY_DASHBOARD = false
	 * - OR delete this entire constant block
	 *
	 * This affects: stats, campaigns, donations, donors, comments
	 * ========================================
	 */
	const SHOW_EMPTY_DASHBOARD = false;

	/**
	 * The single instance of this class.
	 *
	 * @var  Charitable_Dashboard|null
	 */
	private static $instance = null;

	/**
	 * Source of blog posts content.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	const BLOG_POSTS_SOURCE_URL = 'https://plugin.wpcharitable.com/wp-content/charitable-blog-posts.json';

	/**
	 * URL for the v2 Latest Updates feed (hosted on Cloudflare Pages).
	 *
	 * @since 1.8.12
	 * @var string
	 */
	const LATEST_UPDATES_SOURCE_URL = 'https://plugin.wpcharitable.com/wp-content/charitable-latest-updates.json';

	/**
	 * Cached version information from the API.
	 *
	 * @since 1.8.8
	 *
	 * @var array|null
	 */
	private $version_info = null;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.8
		 */
		public function __construct() {
			// Constructor - AJAX handlers are registered in charitable-admin-hooks.php

			// Hook into Charitable's cache clearing mechanism
			add_action( 'charitable_after_clear_expired_options', array( $this, 'clear_dashboard_stats_cache' ) );

			// Refresh the "resume setup wizard" notification entry just before
			// the dashboard rail renders.
			add_action( 'charitable_before_admin_dashboard_v2', array( $this, 'sync_resume_wizard_notification' ) );
		}

		/**
		 * Register or refresh the "resume setup wizard" entry in the dashboard
		 * notification rail when an onboarding session is in progress; remove
		 * any stale entry when it isn't.
		 *
		 * Fires on `charitable_before_admin_dashboard_v2`, immediately before
		 * `render_dashboard_notifications()` reads the same option.
		 *
		 * @since 1.8.10.5
		 *
		 * @return void
		 */
		public function sync_resume_wizard_notification() {

			$notifications = (array) get_option( 'charitable_dashboard_notifications', array() );
			$started       = 1 === (int) get_option( 'charitable_started_onboarding', 0 );
			$present       = isset( $notifications['resume_setup_wizard'] );
			$dismissed     = $present && isset( $notifications['resume_setup_wizard']['dismissed'] );

			if ( $started && ! $dismissed ) {

				$notifications['resume_setup_wizard'] = array(
					'type'        => 'notice',
					'priority'    => 3,
					'title'       => __( 'Finish Setting Up Charitable', 'charitable' ),
					'message'     => '<p>' . esc_html__( "Looks like you started the setup wizard but didn't finish. Pick up where you left off and we'll have you ready to accept donations in just a couple more steps.", 'charitable' ) . '</p>',
					'custom_css'  => 'charitable-notification-type-notice',
					'button_url'  => add_query_arg( array( 'resume' => 'true' ), charitable_get_onboarding_url() ),
					'button_text' => __( 'Resume Setup Wizard', 'charitable' ),
				);

				update_option( 'charitable_dashboard_notifications', $notifications );

				return;
			}

			if ( $present && ! $started ) {
				unset( $notifications['resume_setup_wizard'] );
				update_option( 'charitable_dashboard_notifications', $notifications );
			}
		}


		/**
		 * Render the dashboard new page.
		 *
		 * @since   1.8.8
		 * @version 1.8.8.1
		 */
		public function render_dashboard_new() {
			?>
			<div id="charitable-dashboard-v2" class="wrap">
				<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

				<?php do_action( 'charitable_maybe_show_notification' ); ?>

				<?php
				/**
				 * Do or render something right before the dashboard new area.
				 *
				 * @since 1.8.8
				 */
				do_action( 'charitable_before_admin_dashboard_v2' );
				?>

				<?php $this->render_dashboard_header(); ?>

				<?php $this->render_dashboard_notifications(); ?>

				<div class="charitable-dashboard-v2-content">
					<div class="charitable-dashboard-v2-layout">
						<!-- Left Column -->
						<div class="charitable-dashboard-v2-left-column">
							<?php $this->render_stats_section(); ?>
							<?php $this->render_tabs_section(); ?>
							<?php $this->render_upgrade_section(); ?>
							<?php
							// Show Quick Access in left column for Pro users (when upgrade section is hidden)
							if ( charitable_is_pro() ) {
								$this->render_quick_access_section();
							}
							?>
						</div>

						<!-- Right Column -->
						<div class="charitable-dashboard-v2-right-column">
							<?php $this->render_enhance_campaign_section(); ?>
							<?php $this->render_latest_updates_section(); ?>
							<?php
							// Show Quick Access in right column for non-Pro users
							if ( ! charitable_is_pro() ) {
								$this->render_quick_access_section();
							}
							?>
						</div>
					</div>
				</div>

				<?php
				/**
				 * Do or render something right after the dashboard new area.
				 *
				 * @since 1.8.8
				 */
				do_action( 'charitable_after_admin_dashboard_v2' );
				?>

				<?php $this->render_dashboard_scripts(); ?>
			</div>
			<?php
		}


		/**
		 * Render the dashboard header section.
		 *
		 * @since 1.8.8
		 */
		public function render_dashboard_header() {
			?>
			<div class="charitable-dashboard-v2-header">
				<div class="charitable-dashboard-v2-header-content">
					<div class="charitable-dashboard-v2-header-left">
						<h1><?php echo esc_html__( 'Overview', 'charitable' ); ?></h1>
					</div>
					<div class="charitable-dashboard-v2-header-right">
						<?php if ( charitable_disable_legacy_campaigns() ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" class="button button-primary charitable-button">
								<?php echo esc_html__( '+ Add New Campaign', 'charitable' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>" class="button button-primary charitable-button">
								<?php echo esc_html__( '+ Add New Campaign', 'charitable' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render dashboard notifications.
		 *
		 * @since 1.8.8
		 */
		public function render_dashboard_notifications() {
			// Get dashboard notifications
			$notifications = get_option( 'charitable_dashboard_notifications', array() );

			if ( empty( $notifications ) || ! is_array( $notifications ) ) {
				return;
			}

			// Remove any notifications that don't have a 'type' key
			$notifications = array_filter(
				$notifications,
				function ( $notification ) {
					return isset( $notification['type'] );
				}
			);

			// Remove any notifications that have a 'dismissed' key
			$notifications = array_filter(
				$notifications,
				function ( $notification ) {
					return ! isset( $notification['dismissed'] );
				}
			);

			if ( empty( $notifications ) ) {
				return;
			}

			// Sort notifications by type = 'error' first, then 'warning', followed by 'notice'.
			// Within the same type, sort by priority (lower number = shown first, default 10).
			uasort(
				$notifications,
				function ( $a, $b ) {
					$types = array( 'error', 'warning', 'notice' );
					$pos_a = array_search( $a['type'], $types );
					$pos_b = array_search( $b['type'], $types );

					if ( $pos_a !== $pos_b ) {
						return $pos_a - $pos_b;
					}

					$priority_a = isset( $a['priority'] ) ? (int) $a['priority'] : 10;
					$priority_b = isset( $b['priority'] ) ? (int) $b['priority'] : 10;

					return $priority_a - $priority_b;
				}
			);

			$notifications_count = 1;
			$notifications_total = count( $notifications );

			?>
			<div class="charitable-container charitable-dashboard-notifications charitable-dashboard-v2-notifications">
				<div class="charitable-dashboard-notification-bar">
					<?php if ( (int) $notifications_total > 1 ) : ?>
						<div class="charitable-dashboard-notification-navigation">
							<a class="prev">
								<span class="screen-reader-text"><?php esc_attr_e( 'Previous message', 'charitable' ); ?></span>
								<span aria-hidden="true">&lsaquo;</span>
							</a>
							<span class="charitable-dashboard-notification-counter">
								<?php
								printf(
									/* translators: 1: current notification number, 2: total notifications */
									esc_html__( '%1$d of %2$d', 'charitable' ),
									1,
									(int) $notifications_total
								);
								?>
							</span>
							<a class="next">
								<span class="screen-reader-text"><?php esc_attr_e( 'Next message', 'charitable' ); ?></span>
								<span aria-hidden="true">&rsaquo;</span>
							</a>
						</div>
					<?php else : ?>
						<div class="charitable-dashboard-notification-navigation"></div>
					<?php endif; ?>

					<a href="#" class="charitable-remove-dashboard-notification"></a>
				</div>

				<?php foreach ( $notifications as $notification_slug => $notification ) : ?>
					<?php
					$css_class     = ! empty( $notification['custom_css'] ) ? $notification['custom_css'] : '';
					$css_class    .= $notifications_count === 1 ? '' : ' charitable-hidden';
					$message_title = ! empty( $notification['title'] ) ? sanitize_text_field( $notification['title'] ) : esc_html__( 'Important', 'charitable' );
					$message       = ! empty( $notification['message'] ) ? $notification['message'] : '';
					$message       = wp_kses(
						$message,
						array(
							'a'      => array(
								'href'  => array(),
								'title' => array(),
							),
							'strong' => array(),
							'p'      => array(),
							'ol'     => array(),
							'ul'     => array(),
							'li'     => array(),
							'br'     => array(),
							'h1'     => array(),
							'h2'     => array(),
							'h3'     => array(),
							'h4'     => array(),
							'h5'     => array(),
						)
					);
					$notification_type = ! empty( $notification['type'] ) ? $notification['type'] : 'notice';
					$type_icons        = array(
						'notice'  => 'dashicons-info',
						'warning' => 'dashicons-warning',
						'error'   => 'dashicons-dismiss',
					);
					$icon_class        = isset( $type_icons[ $notification_type ] ) ? $type_icons[ $notification_type ] : 'dashicons-info';
					$button_url        = ! empty( $notification['button_url'] ) ? esc_url( $notification['button_url'] ) : '';
					$button_text       = ! empty( $notification['button_text'] ) ? sanitize_text_field( $notification['button_text'] ) : '';
					?>

					<div class="charitable-dashboard-notification <?php echo esc_attr( $css_class ); ?>" data-notification-number="<?php echo (int) $notifications_count; ?>" data-notification-id="<?php echo esc_attr( $notification_slug ); ?>" data-notification-type="<?php echo esc_attr( $notification_type ); ?>">
						<div class="charitable-dashboard-notification-message">
							<h4 class="charitable-dashboard-notification-headline">
								<span class="dashicons <?php echo esc_attr( $icon_class ); ?> charitable-notification-type-icon"></span>
								<?php echo esc_html( $message_title ); ?>
							</h4>
							<?php echo $message; // phpcs:ignore ?>
							<?php if ( $button_url && $button_text ) : ?>
								<a href="<?php echo esc_url( $button_url ); ?>" class="charitable-notification-cta-button"><?php echo esc_html( $button_text ); ?></a>
							<?php endif; ?>
						</div>
					</div>

					<?php ++$notifications_count; ?>
				<?php endforeach; ?>
			</div>
			<?php
		}


		/**
		 * Render the stats section with time period filter and chart.
		 *
		 * @since 1.8.8
		 */
		public function render_stats_section() {
			?>
			<section class="charitable-dashboard-v2-section charitable-dashboard-v2-stats-row-section">
				<div class="charitable-dashboard-v2-header-bar">
					<div class="charitable-dashboard-v2-header-bar-left">
						<select name="time-period" id="time-period-filter">
							<option value="last-7-days" selected><?php esc_html_e( 'Last 7 Days', 'charitable' ); ?></option>
							<option value="last-14-days"><?php esc_html_e( 'Last 14 Days', 'charitable' ); ?></option>
							<option value="last-30-days"><?php esc_html_e( 'Last 30 Days', 'charitable' ); ?></option>
							<?php /* <option value="this-month">This Month</option>
							<option value="last-3-months">Last 3 Months</option>
							<option value="last-6-months">Last 6 Months</option>
							<option value="last-year">Last Year</option>
							<option value="custom">Custom Range</option> */ ?>
						</select>
					</div>
					<div class="charitable-dashboard-v2-header-bar-right">
						<?php /* <button class="charitable-dashboard-v2-download-button">
							<?php esc_html_e( 'Download Summary', 'charitable' ); ?>
							<svg class="charitable-dashboard-v2-download-icon" width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M7.85908 10.5L3.45604 6.125L4.68889 4.85625L6.97847 7.13125V0H8.73969V7.13125L11.0293 4.85625L12.2621 6.125L7.85908 10.5ZM2.57543 14C2.09109 14 1.67647 13.8286 1.33157 13.4859C0.986662 13.1432 0.814209 12.7312 0.814209 12.25V9.625H2.57543V12.25H13.1427V9.625H14.904V12.25C14.904 12.7312 14.7315 13.1432 14.3866 13.4859C14.0417 13.8286 13.6271 14 13.1427 14H2.57543Z" fill="#0E2121" fill-opacity="0.6"/>
							</svg>
						</button> */ ?>

						<?php
						// Get current date range for print functionality
						// phpcs:disable WordPress.Security.NonceVerification.Recommended
						$time_period = isset( $_GET['time-period'] ) ? sanitize_text_field( wp_unslash( $_GET['time-period'] ) ) : 'last-7-days';
						// phpcs:enable WordPress.Security.NonceVerification.Recommended
						$date_range = $this->get_date_range_for_period( $time_period );
						?>
						<form action="" method="post" target="_blank" class="charitable-report-print" id="charitable-dashboard-v2-print">
							<input name="charitable_report_action" type="hidden" value="charitable_report_print_dashboard" />
							<input name="start_date" type="hidden" value="<?php echo esc_attr( $date_range['start_date'] ); ?>" />
							<input name="end_date" type="hidden" value="<?php echo esc_attr( $date_range['end_date'] ); ?>" />
							<input name="days" type="hidden" value="<?php echo esc_attr( $time_period ); ?>" />
							<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
							<button type="submit" class="charitable-dashboard-v2-print-button" title="<?php echo esc_html__( 'Print Summary', 'charitable' ); ?>">
								<svg class="charitable-dashboard-v2-print-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M6 9V2H18V9M6 9H4C2.89543 9 2 9.89543 2 11V17C2 18.1046 2.89543 19 4 19H6V22H18V19H20C21.1046 19 22 18.1046 22 17V11C22 9.89543 21.1046 9 20 9H18M6 9V13H18V9M6 15H18V17H6V15Z" stroke="#0E2121" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
								</svg>
							</button>
						</form>
					</div>
				</div>
				<?php $this->render_stats_row(); ?>
				<div class="charitable-dashboard-v2-header-bar-chart">
					<div id="charitable-dashboard-v2-headline-graph" class="charitable-headline-graph"></div>
				</div>
			</section>
			<?php
		}

		/**
		 * Render the stats row with donation statistics.
		 *
		 * @since 1.8.8
		 */
	private function render_stats_row() {
		// Get the selected time period from request, default to 'last-7-days'
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$time_period = isset( $_GET['time-period'] ) ? sanitize_text_field( wp_unslash( $_GET['time-period'] ) ) : 'last-7-days';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$stats = $this->get_dashboard_stats( $time_period );
			?>
			<div class="charitable-dashboard-v2-stats-row">
				<div class="charitable-dashboard-v2-stat-item">
					<div class="charitable-dashboard-v2-stat-title"><?php esc_html_e( 'Total Donations', 'charitable' ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount">
						<?php echo esc_html( $stats['total_donations'] ); ?>
						<?php if ( '0%' !== $stats['donations_change'] ) : ?>
							<span class="charitable-dashboard-v2-stat-change">
								<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
									<mask id="mask0_1904_1526" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="13" height="13">
										<rect y="0.25415" width="12.9062" height="12.3612" fill="#D9D9D9"/>
									</mask>
									<g mask="url(#mask0_1904_1526)">
										<path d="M1.82849 9.52507L1.07562 8.804L5.05505 4.96689L7.20609 7.02708L10.0024 4.37458H8.60426V3.34448H11.8308V6.43477H10.7553V5.09565L7.20609 8.49497L5.05505 6.43477L1.82849 9.52507Z" fill="#31714c"/>
									</g>
								</svg>
								<?php echo esc_html( $stats['donations_change'] ); ?>%
							</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="charitable-dashboard-v2-stat-item">
					<div class="charitable-dashboard-v2-stat-title"><?php esc_html_e( 'Avg. Donations', 'charitable' ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount">
						<?php echo esc_html( $stats['avg_donations'] ); ?>
						<?php if ( '0%' !== $stats['avg_change'] ) : ?>
							<span class="charitable-dashboard-v2-stat-change">
								<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
									<mask id="mask0_1904_1526" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="13" height="13">
										<rect y="0.25415" width="12.9062" height="12.3612" fill="#D9D9D9"/>
									</mask>
									<g mask="url(#mask0_1904_1526)">
										<path d="M1.82849 9.52507L1.07562 8.804L5.05505 4.96689L7.20609 7.02708L10.0024 4.37458H8.60426V3.34448H11.8308V6.43477H10.7553V5.09565L7.20609 8.49497L5.05505 6.43477L1.82849 9.52507Z" fill="#31714c"/>
									</g>
								</svg>
								<?php echo esc_html( $stats['avg_change'] ); ?>%
							</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="charitable-dashboard-v2-stat-item">
					<div class="charitable-dashboard-v2-stat-title"><?php esc_html_e( 'Total Donors', 'charitable' ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount"><?php echo esc_html( $stats['total_donors'] ); ?></div>
				</div>
				<div class="charitable-dashboard-v2-stat-item">
					<?php
					/*
					THE WHOLE TITLE comes from the stats array, pluralised there by _n().

					Two things were wrong with building it here as a number plus a bare
					__( 'Refunds' ). It always read "1 Refunds", because a bare string has no
					singular form. And it was the only stat title carrying a value, so
					updateStats() - which rewrites .charitable-dashboard-v2-stat-amount and
					nothing else - left the count on whatever the first page render produced:
					change the period and the amount moved while the count did not, which is how
					a site with one $15 refund showed "0 Refunds" above "$15.00".

					The label is ONE translated string rather than a number in its own span with
					the word beside it. A span would let the JS rewrite just the digits, but it
					also fixes the number to the left of the word, and it cannot carry a locale
					whose plural rules need more than two forms - Russian needs three, Arabic
					six. _n() resolves those from the locale; string concatenation cannot.
					@since 1.8.15
					*/
					?>
					<div class="charitable-dashboard-v2-stat-title"><?php echo esc_html( $stats['total_refunds_label'] ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount"><?php echo esc_html( $stats['total_refunds'] ); ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Get dashboard statistics data.
		 *
		 * @since 1.8.8
		 * @param string $time_period The selected time period.
		 * @return array
		 */
	public function get_dashboard_stats( $time_period = 'last-7-days' ) {
		// Clear stats cache if requested via URL parameter
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['charitable_clear_stats_cache'] ) && '1' === $_GET['charitable_clear_stats_cache'] ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			$this->clear_dashboard_stats_cache();
		}

		// TEMPORARY TESTING: Return empty stats if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			// Decode HTML entities in currency-formatted strings to prevent double-encoding in JSON.
			$empty_total = html_entity_decode( charitable_format_money( 0 ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$empty_avg = html_entity_decode( charitable_format_money( 0 ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$empty_refunds = html_entity_decode( charitable_format_money( 0 ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return array(
				'total_donations' => $empty_total,
				'donations_change' => '0%',
				'avg_donations' => $empty_avg,
				'avg_change' => '0%',
				'total_donors' => 0,
				'total_refunds' => $empty_refunds,
				'total_refunds_count' => 0,
				/* translators: %s: number of refunds in the selected period */
				'total_refunds_label' => sprintf( _n( '%s Refund', '%s Refunds', 0, 'charitable' ), number_format_i18n( 0 ) )
			);
		}

		// Calculate dates based on time period
		$date_range = $this->get_date_range_for_period( $time_period );
		$start_date = $date_range['start_date'];
		$end_date = $date_range['end_date'];
		$days = $date_range['days'];

		// Initialize Charitable Reports
		$charitable_reports = Charitable_Reports::get_instance();
		$args = array(
			'start_date' => $start_date,
			'end_date'   => $end_date,
			'days'       => $days,
		);
		$charitable_reports->init_with_array( 'dashboard', $args );

		// Get donations data
		$donations = $charitable_reports->get_donations();
		$total_count_donations = count( $donations );
		$total_amount_donations = $charitable_reports->get_donations_total();
		$donation_average = ( $total_amount_donations > 0 && $total_count_donations > 0 ) ? ( charitable_format_money( $total_amount_donations / $total_count_donations, false, true ) ) : charitable_format_money( 0 );
		$donation_total = charitable_format_money( $total_amount_donations );

		// Get donors count
		$total_count_donors = $charitable_reports->get_donors_count();

		// Get refunds data
		$refunds_data = $charitable_reports->get_refunds();
		$total_amount_refunds = charitable_format_money( $refunds_data['total_amount'] );
		$total_count_refunds = $refunds_data['total_count'];

		// Decode HTML entities in currency-formatted strings to prevent double-encoding in JSON.
		// charitable_format_money() returns HTML entities (e.g., &#36; for $), so we decode them
		// before sending via AJAX to avoid double-encoding issues.
		$donation_total = html_entity_decode( $donation_total, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$donation_average = html_entity_decode( $donation_average, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$total_amount_refunds = html_entity_decode( $total_amount_refunds, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Calculate percentage changes
		$changes = $this->calculate_percentage_changes( $time_period, $total_amount_donations, $donation_average );
		$donations_change = $changes['donations_change'];
		$avg_change = $changes['avg_change'];

		// Defensive guard: if current average is zero, hide avg change
		if ( 0.0 === (float) $this->parse_money_amount( $donation_average ) ) {
			$avg_change = '';
		}

		// Allow filtering to disable percentage displays completely
		$show_percentage_changes = apply_filters( 'charitable_dashboard_show_percentage_changes', true );
		if ( ! $show_percentage_changes ) {
			$donations_change = '';
			$avg_change = '';
		}

		return array(
			'total_donations' => $donation_total,
			'donations_change' => $donations_change,
			'avg_donations' => $donation_average,
			'avg_change' => $avg_change,
			'total_donors' => $total_count_donors,
			'total_refunds' => $total_amount_refunds,
			'total_refunds_count' => $total_count_refunds,
			/*
			Pluralised here rather than in the template, because the AJAX refresh needs the same
			string and the JS has no gettext. Shipping the finished label means PHP stays the only
			place that knows the locale's plural rules. @since 1.8.15
			*/
			/* translators: %s: number of refunds in the selected period */
			'total_refunds_label' => sprintf( _n( '%s Refund', '%s Refunds', (int) $total_count_refunds, 'charitable' ), number_format_i18n( (int) $total_count_refunds ) )
		);
	}

		/**
		 * Get date range for a given time period.
		 *
		 * @since 1.8.8
		 * @param string $time_period The time period.
		 * @return array
		 */
		public function get_date_range_for_period( $time_period ) {
			/*
			 * Site-local, not UTC: this range feeds Charitable_Reports::get_data(),
			 * which filters on p.post_date (site-local time), so a bound built from
			 * bare UTC excludes part of today's donations on any site whose
			 * timezone is ahead of UTC. Every case below is anchored to the same
			 * site-local "now" so the range is internally consistent.
			 *
			 * @since 1.8.13
			 */
			$now      = current_time( 'timestamp', 0 );
			$end_date = gmdate( 'Y/m/d', $now );

			switch ( $time_period ) {
				case 'last-7-days':
					$start_date = gmdate( 'Y/m/d', strtotime( '-7 days', $now ) );
					$days = 7;
					break;
				case 'last-14-days':
					$start_date = gmdate( 'Y/m/d', strtotime( '-14 days', $now ) );
					$days = 14;
					break;
				case 'last-30-days':
					$start_date = gmdate( 'Y/m/d', strtotime( '-30 days', $now ) );
					$days = 30;
					break;
				case 'this-month':
					$start_date = gmdate( 'Y/m/d', strtotime( 'first day of this month', $now ) );
					$days = gmdate( 'j', $now ); // Current day of month
					break;
				case 'last-3-months':
					$start_date = gmdate( 'Y/m/d', strtotime( '-3 months', $now ) );
					$days = 90;
					break;
				case 'last-6-months':
					$start_date = gmdate( 'Y/m/d', strtotime( '-6 months', $now ) );
					$days = 180;
					break;
				case 'last-year':
					$start_date = gmdate( 'Y/m/d', strtotime( '-1 year', $now ) );
					$days = 365;
					break;
				default:
					$start_date = gmdate( 'Y/m/d', strtotime( '-7 days', $now ) );
					$days = 7;
					break;
			}

			return array(
				'start_date' => $start_date,
				'end_date' => $end_date,
				'days' => $days
			);
		}

		/* ---------------------------------------------------------------------
		 * Chart and time-period helpers, ported from Charitable Pro 1.8.19.5.
		 *
		 * Byte-identical in Pro 1.8.19.5 and 1.8.20 (method bodies hashed before
		 * porting), so there is no newer Pro version of any of these to prefer.
		 * ------------------------------------------------------------------ */


		private function get_default_time_period() {

			$default = 'last-7-days';

			// An explicit choice is never second-guessed.
			if ( isset( $_GET['time-period'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return sanitize_text_field( wp_unslash( $_GET['time-period'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			/*
			Pro gates this on charitable_dashboard_v3_enabled(). Lite has no such function - 1.8.14
			deliberately gave the Reports port its own gate rather than importing Pro's, so the
			equivalent here is charitable_reports_v3_enabled(). Guarded the way
			views/reports/overview.php guards it, because this class is also reachable from AJAX,
			where the gate file is not guaranteed to have loaded.
			@since 1.8.15
			*/
			if ( ! ( function_exists( 'charitable_reports_v3_enabled' ) && charitable_reports_v3_enabled() ) ) {
				return $default;
			}

			// Query and ladder live in get_narrowest_period_with_donations(), shared with the
			// empty-state card. Only widens - it never narrows past the 7-day default.
			$resolved = $this->get_narrowest_period_with_donations();

			return $resolved['any'] ? $resolved['period'] : $default;
		}


		/**
		 * The human label for a period slug, for copy that names one.
		 *
		 * @since 1.8.15
		 *
		 * @param  string $slug Period slug.
		 * @return string
		 */
		private function get_time_period_label( $slug ) {

			$labels = array(
				'last-7-days'   => __( 'Last 7 Days', 'charitable' ),
				'last-14-days'  => __( 'Last 14 Days', 'charitable' ),
				'last-30-days'  => __( 'Last 30 Days', 'charitable' ),
				'last-3-months' => __( 'Last 3 Months', 'charitable' ),
				'last-6-months' => __( 'Last 6 Months', 'charitable' ),
				'last-year'     => __( 'Last Year', 'charitable' ),
			);

			return isset( $labels[ $slug ] ) ? $labels[ $slug ] : $labels['last-7-days'];
		}


		/**
		 * Completed donations inside the currently selected period.
		 *
		 * One COUNT(*) bounded on post_date, against post_type + post_status - not a per-day walk,
		 * which is the mistake that made Reports Overview run 17,998 queries on a long range. Reuses
		 * get_date_range_for_period() so the window is defined in exactly one place and cannot drift
		 * from the one the chart itself plots.
		 *
		 * @since 1.8.15
		 *
		 * @return int
		 */
		private function get_period_donation_count( $period = null ) {

			// Keyed by period: the AJAX path asks about a period other than the one on the URL.
			static $memo = array();

			$period = null === $period ? $this->get_default_time_period() : $period;

			if ( isset( $memo[ $period ] ) ) {
				return $memo[ $period ];
			}

			$range = $this->get_date_range_for_period( $period );

			if ( empty( $range['start_date'] ) || empty( $range['end_date'] ) ) {
				$memo[ $period ] = 1; // Unknown window: say "not empty" rather than claim an empty one.
				return $memo[ $period ];
			}

			global $wpdb;

			$memo[ $period ] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					 WHERE post_type = %s AND post_status = %s AND post_date >= %s AND post_date <= %s",
					'donation',
					'charitable-completed',
					str_replace( '/', '-', $range['start_date'] ) . ' 00:00:00',
					str_replace( '/', '-', $range['end_date'] ) . ' 23:59:59'
				)
			);

			return $memo[ $period ];
		}



		/**
		 * The period to preselect: the narrowest one that actually contains a donation.
		 *
		 * A site whose most recent donation is five weeks old opened on "Last 7 Days" and showed a
		 * flat line and four zeroes, which reads as "this plugin recorded nothing" rather than "look
		 * further back". This widens the default just far enough to show something.
		 *
		 * Cost is ONE indexed query for MAX(post_date), not a range query per candidate period. That
		 * matters: the obvious implementation - ask each period in turn whether it has data - would
		 * run up to six aggregate queries on every dashboard load for every user, to answer a question
		 * a single MAX() settles. Result is cached for an hour and busted by a new donation.
		 *
		 * Only ever WIDENS, and only when the user has not chosen a period themselves: an explicit
		 * ?time-period= wins, because silently overriding a deliberate choice is worse than an empty
		 * chart.
		 *
		 * @since 1.8.15
		 *
		 * @return string Period slug.
		 */
		/**
		 * The narrowest preset period that still reaches the most recent donation.
		 *
		 * Extracted from get_default_time_period(), which used to hold this query and ladder inline.
		 * It has two callers now: that method, to widen the preselected period on load, and
		 * get_chart_empty_state(), to name a period worth switching to when the chosen one is empty.
		 * Both need the same answer, and a second copy of a MAX(post_date) query and a six-rung
		 * ladder is exactly the kind of duplication that drifts.
		 *
		 * @since 1.8.15
		 *
		 * @return array { @type string $period Slug. @type bool $any Whether any donation exists. }
		 */
		private function get_narrowest_period_with_donations() {

			static $memo = null;

			if ( null !== $memo ) {
				return $memo;
			}

			$cached = get_transient( 'charitable_dashboard_default_period' );

			if ( is_array( $cached ) && isset( $cached['period'] ) ) {
				$memo = $cached;
				return $memo;
			}

			global $wpdb;

			// One indexed MAX() over post_type + post_status, not a per-day walk.
			$latest = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX( post_date ) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
					'donation',
					'charitable-completed'
				)
			);

			if ( empty( $latest ) ) {
				$memo = array(
					'period' => 'last-7-days',
					'any'    => false,
				);

				set_transient( 'charitable_dashboard_default_period', $memo, HOUR_IN_SECONDS );

				return $memo;
			}

			$days_ago = (int) floor( ( time() - strtotime( $latest ) ) / DAY_IN_SECONDS );

			// Ordered narrowest first; the first window that reaches the newest donation wins.
			$ladder = array(
				7   => 'last-7-days',
				14  => 'last-14-days',
				30  => 'last-30-days',
				90  => 'last-3-months',
				180 => 'last-6-months',
				365 => 'last-year',
			);

			$period = 'last-7-days';

			foreach ( $ladder as $window => $slug ) {
				if ( $days_ago <= $window ) {
					$period = $slug;
					break;
				}
			}

			/*
			Older than a year: stop at the widest preset rather than inventing a wider one. Anything
			beyond a year hits the month-day axis collapse documented elsewhere on this class, so
			widening further would trade an empty chart for a wrong one.
			*/
			if ( $days_ago > 365 ) {
				$period = 'last-year';
			}

			$memo = array(
				'period' => $period,
				'any'    => true,
			);

			set_transient( 'charitable_dashboard_default_period', $memo, HOUR_IN_SECONDS );

			return $memo;
		}



	/**
	 * Fold a daily chart series into weekly or monthly buckets on long ranges.
	 *
	 * WHY, precisely: at "Last Year" the chart plots 365 points across roughly 980px - 2.7px per
	 * point - and on a typical site only a handful are non-zero. Measured on zeta: 6 non-zero values
	 * among 359 zeros, which renders as a flat line with spikes too narrow to see.
	 *
	 * Weekly was the first answer and it was not enough at a year: 53 points means 53 axis labels in
	 * "Sep 18" form, which ApexCharts rotates and overlaps into an unreadable band. A year belongs in
	 * months - 13 buckets, 13 short labels, no rotation - which is also how every analytics tool
	 * presents a trailing twelve months, so it needs no explaining to a reader.
	 *
	 * Thresholds, and what reaches them:
	 *
	 *   <= 30 days   daily    last-7-days, last-14-days, last-30-days
	 *   31-180       weekly   last-3-months (13 points), last-6-months (26)
	 *   > 180        monthly  last-year (13)
	 *
	 * These dropped a notch from >180 weekly / >300 monthly, because Last 3 Months was still
	 * plotting 90 daily points: 90 rotated labels AND 90 circle markers, which is as much noise as
	 * the 365-day case this method was written for. Every period now lands between 7 and 30 plotted
	 * points.
	 *
	 * The trade is real and worth stating: a single large day inside a week now reads as that
	 * week's total, so a $70 spike appears as a $70 week rather than a $70 Tuesday. The spike is
	 * still visible and still in the right place; only its width changes. Day-level resolution for
	 * a 90-day window is available by keeping the daily series and thinning only the axis labels,
	 * which was the alternative considered and not taken - it leaves the markers behind.
	 *
	 * This is NOT a correctness fix, and it is worth being precise about that because it is easy to
	 * assume otherwise. The daily series reconciles exactly with the headline total ($265.55 both
	 * ways on zeta), and the 365-vs-366 length difference is a single orphaned trailing label: the
	 * series is month-day keyed, so a 366-day window contains one month-day twice - "Sep 18" at both
	 * ends - and those two collapse into one point. Indices 0..364 line up correctly. Truncating the
	 * labels to the series length below drops that orphan, which is the only alignment change made.
	 *
	 * Summing, not averaging: the series is money per day, so a bucket's value is the money raised
	 * in that bucket. Averaging would make the axis mean something different from the headline figure.
	 *
	 * @since 1.8.15
	 *
	 * @param  array $donation_axis Per-day values.
	 * @param  array $date_axis     Per-day labels.
	 * @param  int   $days          Length of the requested range in days.
	 * @return array { @type array $donation_axis. @type array $date_axis. }
	 */
	public function bucket_chart_axes( $donation_axis, $date_axis, $days ) {

		$donation_axis = array_values( (array) $donation_axis );
		$date_axis     = array_values( (array) $date_axis );

		$days = (int) $days;

		if ( $days > 180 ) {
			$granularity = 'month';
		} elseif ( $days > 30 ) {
			$granularity = 'week';
		} else {
			// Below the threshold a daily series is still legible, so leave it entirely alone.
			return array(
				'donation_axis' => $donation_axis,
				'date_axis'     => $date_axis,
			);
		}

		// Drop the orphaned trailing label described above so the two arrays stay in step.
		$count = min( count( $donation_axis ), count( $date_axis ) );

		if ( $count < 1 ) {
			return array(
				'donation_axis' => $donation_axis,
				'date_axis'     => $date_axis,
			);
		}

		$donation_axis = array_slice( $donation_axis, 0, $count );
		$date_axis     = array_slice( $date_axis, 0, $count );

		$values = array();
		$labels = array();

		if ( 'month' === $granularity ) {

			/*
			Grouped by RUNS of the same month name, not by a fixed chunk size, because months are
			28-31 days long and a fixed chunk would drift a few days further out of phase every
			bucket. A run is exactly one calendar month: consecutive days sharing a month name
			cannot be two different months, and the same month a year apart is not consecutive, so
			the two "Sep" buckets at the ends of a 365-day window stay separate.

			The month name comes out of the label rather than being recomputed from a date, because
			the label is all this method is given. chart_label_month() finds the non-numeric token
			wherever it sits, so a locale that formats "18 Sep" groups the same as "Sep 18".

			Labelled with the bare month, so a year reads Sep...Sep across 13 short labels. The
			repeated name at the two ends is the partial first and last month, which is what a
			trailing-twelve-months axis looks like everywhere else.
			*/
			$current_month = null;

			foreach ( $date_axis as $index => $label ) {

				$month = $this->chart_label_month( $label );

				if ( $month !== $current_month ) {
					$current_month = $month;
					$values[]      = 0;
					$labels[]      = $month;
				}

				$last = count( $values ) - 1;

				$values[ $last ] += $this->chart_axis_value( $donation_axis[ $index ] );
			}

			foreach ( $values as $index => $value ) {
				$values[ $index ] = round( $value, 2 );
			}

			return array(
				'donation_axis' => $values,
				'date_axis'     => $labels,
			);
		}

		$value_chunks = array_chunk( $donation_axis, 7 );
		$label_chunks = array_chunk( $date_axis, 7 );

		foreach ( $value_chunks as $index => $chunk ) {
			$sum = 0;

			foreach ( $chunk as $value ) {
				$sum += $this->chart_axis_value( $value );
			}

			$values[] = round( $sum, 2 );

			// The week is labelled by the day it starts on, which is what a reader assumes.
			$labels[] = isset( $label_chunks[ $index ][0] ) ? $label_chunks[ $index ][0] : '';
		}

		return array(
			'donation_axis' => $values,
			'date_axis'     => $labels,
		);
	}



	/**
	 * A chart series value as a float.
	 *
	 * The series arrives as formatted currency strings in some code paths and as numbers in others,
	 * so anything that is not a digit, a dot or a minus sign is stripped before casting.
	 *
	 * @since 1.8.15
	 *
	 * @param  mixed $value Raw series value.
	 * @return float
	 */
	private function chart_axis_value( $value ) {

		return (float) preg_replace( '/[^0-9.\-]/', '', (string) $value );
	}



	/**
	 * The month name out of a chart axis label.
	 *
	 * Returns the first token that is not a number, so "Sep 18", "18 Sep" and "Sep 18, 2026" all
	 * yield "Sep". Falls back to the whole trimmed label if every token is numeric, which keeps
	 * grouping working - one bucket per distinct label - rather than collapsing the series into one.
	 *
	 * @since 1.8.15
	 *
	 * @param  string $label Axis label.
	 * @return string
	 */
	private function chart_label_month( $label ) {

		$label = trim( (string) $label );
		$parts = preg_split( '/[\s,]+/', $label );

		if ( is_array( $parts ) ) {
			foreach ( $parts as $part ) {
				if ( '' !== $part && ! is_numeric( $part ) ) {
					return $part;
				}
			}
		}

		return $label;
	}


		private function get_chart_empty_state( $period = null ) {

			/*
			Memoised per period, not once. render_stats_section() asks twice on a page load - once to
			flag the section, once to render - but the AJAX path asks about whichever period the
			dropdown just selected, which is not the one on the URL. A single static would have
			handed it the page-load answer.
			*/
			static $memo = array();

			$period = null === $period ? $this->get_default_time_period() : $period;

			if ( array_key_exists( $period, $memo ) ) {
				return $memo[ $period ];
			}

			$campaign_counts = wp_count_posts( 'campaign' );
			$published       = isset( $campaign_counts->publish ) ? (int) $campaign_counts->publish : 0;
			$drafts          = ( isset( $campaign_counts->draft ) ? (int) $campaign_counts->draft : 0 )
				+ ( isset( $campaign_counts->pending ) ? (int) $campaign_counts->pending : 0 );

			$donation_counts = wp_count_posts( 'donation' );
			$donations       = isset( $donation_counts->{'charitable-completed'} ) ? (int) $donation_counts->{'charitable-completed'} : 0;

			/*
			Preview override. The states below are all "new install" states, so on an established
			site none of them fire and the effect cannot be seen at all. This makes both variants
			inspectable without touching any data:

			  ?charitable_preview_empty=campaigns
			  ?charitable_preview_empty=donations
			  ?charitable_preview_empty=draft
			  ?charitable_preview_empty=period
			*/
			$forced = isset( $_GET['charitable_preview_empty'] ) && current_user_can( 'manage_options' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				? sanitize_key( wp_unslash( $_GET['charitable_preview_empty'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				: '';

			if ( in_array( $forced, array( 'campaigns', 'donations', 'draft', 'period' ), true ) ) {
				$mode = $forced;
			} elseif ( 0 === $published && $drafts > 0 ) {
				$mode = 'draft';
			} elseif ( 0 === $published ) {
				$mode = 'campaigns';
			} elseif ( 0 === $donations ) {
				$mode = 'donations';
			} elseif ( 0 === $this->get_period_donation_count( $period ) ) {
				/*
				The fourth state, and the only one that is NOT an onboarding case: an established site
				with campaigns and donations that simply had none inside the selected window.

				Without it the chart drew a flat line across six \$0.00 gridlines with no explanation,
				which reads as a broken chart rather than a quiet fortnight - and it is the one empty
				state a real site will actually meet, since the other three only fire before the first
				campaign or the first donation exists.
				*/
				$mode = 'period';
			} else {
				$memo[ $period ] = null;
				return $memo[ $period ];
			}

			$new_campaign_url = charitable_disable_legacy_campaigns()
				? admin_url( 'admin.php?page=charitable-campaign-builder&view=template' )
				: admin_url( 'post-new.php?post_type=campaign' );

			/*
			Secondary action, resolved by usefulness rather than by a fixed link: finish the setup
			checklist if it is unfinished, otherwise publish a waiting draft, otherwise fall back
			to the guide. The fallback is last precisely because sending somebody to a docs page is
			the least actionable of the three.
			*/
			$secondary = null;

			$checklist_incomplete = false;

			if ( class_exists( 'Charitable_Checklist' ) ) {
				$stats = Charitable_Checklist::get_instance()->get_steps_stats();

				if ( is_array( $stats ) && ! empty( $stats['total'] ) ) {
					$checklist_incomplete = (int) $stats['completed'] < (int) $stats['total'];
				}
			}

			if ( $checklist_incomplete && 'draft' !== $mode ) {
				$secondary = array(
					'label' => __( 'Finish Setup', 'charitable' ),
					'url'   => admin_url( 'admin.php?page=charitable-setup-checklist' ),
				);
			} elseif ( 'draft' !== $mode && $drafts > 0 ) {
				$secondary = array(
					'label' => __( 'Publish a Draft', 'charitable' ),
					'url'   => admin_url( 'edit.php?post_type=campaign&post_status=draft' ),
				);
			} else {
				$guide = 'https://wpcharitable.com/start-here/';

				if ( function_exists( 'charitable_utm_link' ) ) {
					$guide = charitable_utm_link( $guide, 'dashboard-chart-empty', 'getting-started' );
				}

				$secondary = array(
					'label'    => __( 'Getting Started Guide', 'charitable' ),
					'url'      => $guide,
					'external' => true,
				);
			}

			// Named in the 'period' copy below; memoised, so asking here costs nothing extra.
			$charitable_period_suggestion = $this->get_narrowest_period_with_donations();

			$states = array(
				'draft'     => array(
					'title'   => __( 'Publish Your First Campaign!', 'charitable' ),
					'body'    => __( 'You have a campaign ready as a draft. Publish it and it can start taking donations right away.', 'charitable' ),
					'primary' => array(
						'label' => __( 'Publish Your Campaign', 'charitable' ),
						'url'   => admin_url( 'edit.php?post_type=campaign&post_status=draft' ),
					),
				),
				'campaigns' => array(
					'title'   => __( 'Get Started With A Campaign!', 'charitable' ),
					'body'    => __( 'Create your first campaign and this chart fills in as donations arrive.', 'charitable' ),
					// Second line of the description. Split into its own string rather than carrying a <br>
					// so both halves stay esc_html'd and a translator gets two whole sentences to work
					// with instead of one containing markup. Only this state has one. @since 1.8.15
					'body2'   => __( 'Start from a template or import your data.', 'charitable' ),
					'primary' => array(
						'label' => __( 'Create a Campaign', 'charitable' ),
						'url'   => $new_campaign_url,
					),
				),
				'period'    => array(
					/*
					Reassurance, not an alarm. The site is working; the window is just quiet. So the
					headline states the fact plainly and the primary action is the thing that actually
					helps - jumping to a range that does contain activity, named rather than described,
					so the click is predictable.
					*/
					'title'   => __( 'No Donations In This Date Range', 'charitable' ),
					'body'    => __( 'Your campaigns are live and this chart fills in as donations arrive.', 'charitable' ),
					'body2'   => $charitable_period_suggestion['any']
						? sprintf(
							/* translators: %s: a time period name, e.g. "Last 3 Months". */
							__( 'Your most recent donation falls within %s.', 'charitable' ),
							$this->get_time_period_label( $charitable_period_suggestion['period'] )
						)
						: '',
					'primary' => array(
						'label' => sprintf(
							/* translators: %s: a time period name, e.g. "Last 3 Months". */
							__( 'Show %s', 'charitable' ),
							$this->get_time_period_label( $charitable_period_suggestion['period'] )
						),
						'url'   => add_query_arg( 'time-period', $charitable_period_suggestion['period'], admin_url( 'admin.php?page=charitable-dashboard' ) ),
					),
				),
				'donations' => array(
					'title'   => __( 'Ready For Your First Donation!', 'charitable' ),
					'body'    => __( 'Your campaign is live and ready to receive. Share it with your supporters, or record an offline donation here.', 'charitable' ),
					'primary' => array(
						'label' => __( 'Add a Donation', 'charitable' ),
						'url'   => admin_url( 'post-new.php?post_type=donation' ),
					),
				),
			);

			$state              = $states[ $mode ];
			/*
			The shared secondary is an onboarding action - Finish Setup, or the Getting Started guide.
			Both are wrong on the 'period' state, which only fires on a site that already has campaigns
			AND donations: telling somebody with live donations to finish setting up is worse than
			offering nothing. That state carries its own primary and needs no second action.
			*/
			$state['secondary'] = 'period' === $mode ? array() : $secondary;
			$state['mode']      = $mode;
			$memo[ $period ] = $state;

			return $memo[ $period ];
		}



		/**
		 * Decide whether the chart has nothing real to show, and what to say about it.
		 *
		 * Three genuinely distinct states, deliberately NOT collapsed into one "no data" message,
		 * because the useful next action is different in each:
		 *
		 *   draft      - a campaign exists but is not published. Nothing can be donated to yet, so
		 *                the action is to publish it, not to create another one.
		 *   campaigns  - nothing exists at all.
		 *   donations  - a live campaign exists and has never received a donation.
		 *
		 * Returns null when there IS data, in which case the real chart renders untouched.
		 *
		 * SCOPE: all three conditions are period-INDEPENDENT - they ask "ever", not "in the last 7
		 * days" - so the overlay stays correct when the period dropdown changes without any
		 * involvement from the AJAX handler. The fourth conceivable state, "donations exist but
		 * none inside the selected period", is deliberately not handled here: it would need the
		 * AJAX response to carry the flag and the shipping JS to toggle the overlay, and a
		 * server-rendered version of it would go stale the moment the dropdown moved. That is why
		 * a site with older donations shows a flat real chart rather than this prompt.
		 *
		 * @since 1.8.15
		 *
		 * @return array|null
		 */
		/**
		 * The "no donations in this range" overlay as HTML, for the AJAX period switch.
		 *
		 * The dropdown does NOT reload the page - it refreshes the stats and the chart over AJAX -
		 * so an overlay decided at render time never appeared when somebody switched into an empty
		 * range, or disappeared when they switched out of one. That is the exact case a real site
		 * meets, which made the state nearly unreachable through the UI that causes it.
		 *
		 * Returns markup rather than a flag so the copy, the suggested period and the button URL stay
		 * in PHP with the other three states. The alternative - shipping the strings to JS and
		 * building the card there - would have put translatable copy in two places.
		 *
		 * ONLY ever returns the 'period' state. The other three are properties of the site, not of
		 * the selected range: they do not change when the dropdown does, the page already rendered
		 * one if it applied, and returning it here would paint a second copy over it.
		 *
		 * @since 1.8.15
		 *
		 * @param  string $period Period slug from the AJAX request.
		 * @return string Markup, or '' when the range has donations.
		 */
		public function get_chart_empty_state_html( $period ) {

			// Lite's gate, not Pro's charitable_dashboard_v3_enabled(). See get_default_time_period().
			if ( ! ( function_exists( 'charitable_reports_v3_enabled' ) && charitable_reports_v3_enabled() ) ) {
				return '';
			}

			$state = $this->get_chart_empty_state( $period );

			if ( ! is_array( $state ) || ! isset( $state['mode'] ) || 'period' !== $state['mode'] ) {
				return '';
			}

			ob_start();
			$this->render_chart_empty_state( $state );

			return (string) ob_get_clean();
		}



		/**
		 * Render the placeholder chart and prompt card.
		 *
		 * The art is a static inline SVG rather than sample data fed to ApexCharts. Feeding the
		 * real chart fake numbers would mean the live chart path could render invented figures,
		 * which is the kind of thing that eventually escapes into a screenshot or a support
		 * thread; a decorative SVG cannot be mistaken for data. It is also aria-hidden and blurred,
		 * so it reads as texture rather than as a reading someone might try to interpret.
		 *
		 * @since 1.8.15
		 *
		 * @param  array $state From get_chart_empty_state().
		 * @return void
		 */
		private function render_chart_empty_state( array $state ) {
			?>
			<div class="charitable-chart-empty charitable-chart-empty--<?php echo esc_attr( $state['mode'] ); ?>">

				<?php
				/*
				The blurred placeholder graph is for the three onboarding states, where there is no real
				chart to stand on - it gives the card something to sit over. The 'period' state DOES have
				a real chart behind it (a flat line, which is the honest picture of a quiet fortnight), so
				drawing fake art on top of real axes would be inventing data.
				*/
				if ( 'period' !== $state['mode'] ) :
					?>
				<svg class="charitable-chart-empty-art" viewBox="0 0 1000 260" preserveAspectRatio="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
					<?php // Gridlines, echoing the real chart's horizontal rules. ?>
					<g stroke="#191d2d" stroke-opacity="0.07" stroke-width="1">
						<line x1="0" y1="30" x2="1000" y2="30" />
						<line x1="0" y1="86" x2="1000" y2="86" />
						<line x1="0" y1="142" x2="1000" y2="142" />
						<line x1="0" y1="198" x2="1000" y2="198" />
						<line x1="0" y1="254" x2="1000" y2="254" />
					</g>
					<?php // Fill and stroke use #31714c, the colour the live chart is configured with. ?>
					<path d="M0 232 C 90 232 130 150 200 120 C 270 90 320 66 400 70 C 480 74 540 140 620 168 C 700 196 760 150 840 112 C 900 84 950 96 1000 118 L 1000 260 L 0 260 Z" fill="#31714c" fill-opacity="0.2" />
					<path d="M0 232 C 90 232 130 150 200 120 C 270 90 320 66 400 70 C 480 74 540 140 620 168 C 700 196 760 150 840 112 C 900 84 950 96 1000 118" fill="none" stroke="#31714c" stroke-opacity="0.7" stroke-width="3" stroke-linecap="round" />
					<g fill="#fff" stroke="#31714c" stroke-opacity="0.7" stroke-width="3">
						<circle cx="200" cy="120" r="5" />
						<circle cx="620" cy="168" r="5" />
						<circle cx="840" cy="112" r="5" />
					</g>
				</svg>
				<?php endif; ?>

				<div class="charitable-chart-empty-card">
					<h3 class="charitable-chart-empty-title"><?php echo esc_html( $state['title'] ); ?></h3>
					<p class="charitable-chart-empty-body"><?php echo esc_html( $state['body'] ); ?><?php if ( ! empty( $state['body2'] ) ) : ?><span class="charitable-chart-empty-body-line"><?php echo esc_html( $state['body2'] ); ?></span><?php endif; ?></p>
					<div class="charitable-chart-empty-actions">
						<a href="<?php echo esc_url( $state['primary']['url'] ); ?>" class="charitable-chart-empty-button charitable-chart-empty-button--primary"><?php echo esc_html( $state['primary']['label'] ); ?></a>
						<?php if ( ! empty( $state['secondary'] ) ) : ?>
							<a href="<?php echo esc_url( $state['secondary']['url'] ); ?>" class="charitable-chart-empty-button charitable-chart-empty-button--secondary"<?php echo ! empty( $state['secondary']['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $state['secondary']['label'] ); ?></a>
						<?php endif; ?>
					</div>
					<?php
					/*
					Migration route, under the buttons. It lives here rather than in a footer strip
					on the card because this is the only moment it is relevant: somebody looking at
					an empty chart is either starting out or arriving from somewhere else. On an
					established site the prompt never renders at all, which is strictly better than
					a permanent strip asking a long-time user about a migration.

					Third in the hierarchy on purpose - plain grey text, no button, no arrow - so it
					sits below the two real actions without competing with them. @since 1.8.15
					*/
					?>
					<?php
					/*
					Not on the 'period' state. The comment above says "on an established site the prompt
					never renders at all" - that was true when every state here meant a new install, and
					stopped being true the moment a state fired for a site with donations already in it.
					*/
					if ( 'period' !== $state['mode'] ) :
						?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=import&sub_tab=givewp' ) ); ?>" class="charitable-chart-empty-import"><?php esc_html_e( 'Import From GiveWP', 'charitable' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		/* --- Dashboard UI primitives, ported from Charitable Pro 1.8.19.5. --- */



		/**
		 * The collapse chevron, matching the one Quick Access already ships.
		 *
		 * Deliberately the SAME markup and classes as the existing collapsible section rather than a
		 * new mechanism: charitable-admin-dashboard.js already binds
		 * .charitable-dashboard-v2-toggle and toggles .charitable-dashboard-v2-collapsed on the
		 * nearest .charitable-dashboard-v2-collapsible, including the max-height animation and the
		 * arrow rotation. Adding the two classes to a card is therefore all it takes - no new JS.
		 *
		 * NOT applied to the tabbed card or the stats/chart card: the tab strip is the header of that
		 * card so there is nowhere for a chevron to sit without competing with the tabs, and the
		 * chart card's header is the period dropdown and print button.
		 *
		 * @since 1.8.15
		 *
		 * @return void
		 */
		private function render_collapse_toggle() {
			?>
			<a href="#" class="charitable-dashboard-v2-toggle" aria-label="<?php echo esc_attr__( 'Collapse or expand this section', 'charitable' ); ?>">
				<i class="fa fa-angle-down charitable-dashboard-v2-angle-down"></i>
			</a>
			<?php
		}


		/**
		 * Render the stats row with donation statistics.
		 *
		 * @since 1.8.15
		 */
		/**
		 * One stat's percentage change: the trend arrow and the figure.
		 *
		 * Replaces two inline copies of this SVG, both of which hardcoded the RISING arrow in
		 * green and appended a second '%' to a string that already carries one. Neither bug was
		 * ever visible: the stats row is visibility:hidden until the AJAX response swaps it, so
		 * this markup is a placeholder nobody sees. They were still traps for whoever unhides it.
		 *
		 * The modifier class is the part that matters. charitable-admin-dashboard.css has carried
		 * -positive / -negative / -zero since 1.8.8 and NOTHING has ever emitted them, so the base
		 * class's hardcoded green applied to every value including falls. The live path had the
		 * same omission - see the matching fix in charitable-admin-dashboard.js.
		 *
		 * The <mask> the old markup wrapped this path in is dropped: its rect covered the whole
		 * 13x13 viewBox while the path spans 1.07-11.83 by 3.34-9.53, so it clipped nothing and
		 * only put the same DOM id on the page twice.
		 *
		 * @since 1.8.15
		 *
		 * @param  string $change Signed percentage, e.g. "+18.4%" or "-98.8%". Empty renders nothing.
		 * @return void
		 */
		private function render_stat_change( $change ) {

			// One implementation, shared with the Reports Overview - see
			// charitable_get_stat_change_html() in charitable-core-admin-functions.php.
			if ( ! function_exists( 'charitable_get_stat_change_html' ) ) {
				return;
			}

			echo charitable_get_stat_change_html( $change ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}


		/**
		 * The shared body of the upgrade card: the feature list, then the CTA pair.
		 *
		 * Both layouts render this, so there is one copy of the feature strings and one copy of the
		 * two links regardless of which chrome is around them.
		 *
		 * The button class is the one thing that differs, and it has to. An inline <style> block in
		 * render_enhance_campaign_section() prints
		 *
		 *     .charitable-dashboard-v2-upgrade-button {
		 *         background-color: #E38632 !important; color: white !important; border-color: #E38632 !important;
		 *     }
		 *
		 * unscoped, so that class is painted #E38632 wherever it appears no matter what any
		 * stylesheet says - the shipping rule's own rgba(218,144,33,1) never lands, and the
		 * :hover rule below it repeats #E38632, so the largest button on the dashboard currently
		 * has no hover feedback at all. v3 therefore carries its own class and stays out of that
		 * rule's way, which is the same move render_raise_more_section() already makes when it
		 * strips this class off its own buttons. The classic path keeps the legacy class and is
		 * pixel-identical to before.
		 *
		 * @since 1.8.15
		 *
		 * @param  bool $v3 Whether the v3 layout is rendering.
		 * @return void
		 */
		private function render_upgrade_body( $v3 = false ) {

			$button_class = $v3
				? 'charitable-dashboard-v2-upgrade-cta'
				: 'charitable-dashboard-v2-upgrade-button';
			?>
			<div class="charitable-dashboard-v2-upgrade-features">
				<div class="charitable-dashboard-v2-upgrade-column">
					<?php $this->render_upgrade_features_left(); ?>
				</div>
				<div class="charitable-dashboard-v2-upgrade-column">
					<?php $this->render_upgrade_features_right(); ?>
				</div>
			</div>
			<div class="charitable-dashboard-v2-upgrade-actions">
				<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/pricing', 'Dashboard Upgrade Section', 'Upgrade To Pro Button' ) ); ?>" target="_blank" rel="noopener noreferrer" class="<?php echo esc_attr( $button_class ); ?>"><?php esc_html_e( 'Upgrade To Pro', 'charitable' ); ?></a>
				<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/pricing', 'Dashboard Upgrade Section', 'Learn More About Features Link' ) ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-learn-more-link"><?php esc_html_e( 'Learn more about all features →', 'charitable' ); ?></a>
			</div>
			<?php
		}

		/* --- Captcha state and version pill, ported from Charitable Pro 1.8.19.5. --- */


	private function get_captcha_state() {

		$provider = (string) charitable_get_option( 'captcha_provider', 'disabled' );
		$enabled  = '' !== $provider && 'disabled' !== $provider;
		$has      = function ( $key ) {
			return ! empty( charitable_get_option( $key, '' ) );
		};

		$configured = false;

		switch ( $provider ) {
			case 'google-recaptcha':
				// v2 moved to prefixed keys; the unprefixed pair is the pre-migration fallback.
				$configured = ( $has( 'recaptcha_v2_site_key' ) || $has( 'recaptcha_site_key' ) )
					&& ( $has( 'recaptcha_v2_secret_key' ) || $has( 'recaptcha_secret_key' ) );
				break;
			case 'google-recaptcha-v3':
				$configured = $has( 'recaptcha_v3_site_key' ) && $has( 'recaptcha_v3_secret_key' );
				break;
			case 'hcaptcha':
				$configured = $has( 'hcaptcha_sitekey' ) && $has( 'hcaptcha_secret_key' );
				break;
			case 'cloudflare-turnstile':
				$configured = $has( 'cloudflare_turnstile_site_key' ) && $has( 'cloudflare_turnstile_secret_key' );
				break;
		}

		return array(
			'provider'   => $provider,
			'enabled'    => $enabled,
			'configured' => $enabled && $configured,
		);
	}



	/**
	 * The Settings > Security URL for a given sub-tab.
	 *
	 * Captcha and Donation Guard both live under tab=security, NOT tab=advanced, which is where
	 * the recommendation rules were sending people - advanced is the Misc tab. Centralised here so
	 * the status notes and the recommendations cannot drift apart again. @since 1.8.15
	 *
	 * @param  string $sub_tab Sub-tab slug.
	 * @return string
	 */
	private function get_security_settings_url( $sub_tab = 'captcha' ) {

		/*
		NO sub_tab ON LITE, and the argument is accepted only so the ported call sites read the same
		as Pro's.

		Pro appends &sub_tab=captcha or &sub_tab=security__donationguard. Lite's security settings
		have no sub-tabs at all - "sub_tab" appears nowhere under includes/admin/security/ - so
		appending one would at best be inert and at worst send the reader somewhere that does not
		resolve. Lite's own captcha banner links to tab=security with nothing after it
		(class-charitable-security-settings.php:256), and that is the path verified to work, so it is
		the one used here.

		The parameter is kept rather than removed so that a future port from Pro does not have to
		rewrite every call site, and so this comment stays attached to the decision.
		@since 1.8.15
		*/
		unset( $sub_tab );

		return admin_url( 'admin.php?page=charitable-settings&tab=security' );
	}

	/**
	 * A captcha provider's display name, for the rail's Spam Protection note.
	 *
	 * NOT IN PRO, and needed only because Lite's note names the provider where Pro's lists three
	 * protections instead. The strings are the ones the captcha module's own setting uses, minus its
	 * parenthetical "(Invisible)" qualifiers, which say nothing useful in a two-line cell. Keeping
	 * them aligned with that setting matters: a reader who clicks through should meet the same
	 * product name on the screen they land on.
	 *
	 * Falls back to the raw slug rather than to a guess or an empty string. An unknown provider means
	 * somebody filtered one in, and echoing what they set is more use than pretending the field is
	 * blank. It is escaped at the point of output like every other value on these cards.
	 *
	 * @since 1.8.15
	 *
	 * @param  string $provider Provider slug from get_captcha_state().
	 * @return string
	 */
	private function captcha_provider_label( $provider ) {

		$labels = array(
			'google-recaptcha'     => __( 'Google reCAPTCHA v2', 'charitable' ),
			'google-recaptcha-v3'  => __( 'Google reCAPTCHA v3', 'charitable' ),
			'hcaptcha'             => __( 'hCaptcha', 'charitable' ),
			'cloudflare-turnstile' => __( 'Cloudflare Turnstile', 'charitable' ),
		);

		$provider = (string) $provider;

		return isset( $labels[ $provider ] ) ? $labels[ $provider ] : $provider;
	}



	/**
	 * Render the latest updates section.
	 *
	 * @since 1.8.15
	 */
	/**
	 * Resolve the dashboard's version state once, for both the header pill and the card alert.
	 *
	 * Lifted verbatim out of render_latest_updates_section() when v3 moved the compact pill under
	 * the page title, which renders BEFORE that card - so the numbers had to become reachable from
	 * two places without being computed twice or drifting apart.
	 *
	 * Two details preserved deliberately from the original:
	 *
	 *   - get_latest_updates() is called first, because it is what populates $this->version_info
	 *     as a side effect. Its static memo means calling it early costs nothing.
	 *   - the threshold check is isset(), NOT ! empty() or array_key_exists(). get_latest_updates()
	 *     stores a null sentinel when the feed omits behind_threshold, so isset() correctly falls
	 *     through to the default of 3, while a feed-supplied 0 still overrides to "always alert".
	 *
	 * @since 1.8.15
	 *
	 * @return array {
	 *     @type int  $updates_behind      How many versions behind the install is.
	 *     @type bool $show_version_update Past the threshold: show the big in-card alert.
	 *     @type bool $on_latest           Remote latest known and install is current.
	 *     @type bool $mildly_behind       1+ behind but not past the threshold.
	 *     @type string $version           The installed version.
	 * }
	 */
	private function get_dashboard_version_state() {

		static $memo = null;

		if ( null !== $memo ) {
			return $memo;
		}

		// Populates $this->version_info as a side effect; read below.
		$this->get_latest_updates();

		// Threshold resolves in order: feed meta.behind_threshold → WP filter → hardcoded default (3).
		$updates_behind = $this->get_updates_behind();
		$threshold      = 3;

		if ( isset( $this->version_info['behind_threshold'] ) ) {
			$threshold = (int) $this->version_info['behind_threshold'];
		}

		/**
		 * Filter the "X versions behind" alert threshold on the dashboard.
		 *
		 * @since 1.8.15
		 *
		 * @param int $threshold The number of versions a user must be behind for the alert to show.
		 */
		$threshold           = (int) apply_filters( 'charitable_latest_updates_behind_threshold', $threshold );
		$show_version_update = $updates_behind > $threshold;

		$latest_known_version = $this->get_latest_version();

		$memo = array(
			'updates_behind'      => $updates_behind,
			'show_version_update' => $show_version_update,
			'on_latest'           => ! empty( $latest_known_version ) && 0 === $updates_behind,
			'mildly_behind'       => ! empty( $latest_known_version ) && $updates_behind > 0 && ! $show_version_update,
			'version'             => charitable()->get_version(),
		);

		return $memo;
	}



	/**
	 * Render the compact version pill at the top of the right rail.
	 *
	 * v3 only. Shows whichever of the two compact states applies, or nothing: when the install is
	 * far enough behind to earn the big alert, that alert renders inside the What's New card
	 * instead and this stays silent, preserving the original mutual exclusivity.
	 *
	 * Called from views/dashboard/dashboard.php rather than from a render_*_section(), because it
	 * is deliberately NOT a card - it sits above the first rail card as a bare status line, so the
	 * rail does not gain another bordered box for one sentence.
	 *
	 * Reuses the shipping .charitable-dashboard-v2-latest-updates-uptodate / -behind classes so
	 * the existing pill styling carries over untouched; only the position is new.
	 *
	 * @since 1.8.15
	 *
	 * @return void
	 */
	public function render_dashboard_version_pill() {

		$state = $this->get_dashboard_version_state();

		if ( empty( $state['on_latest'] ) && empty( $state['mildly_behind'] ) ) {
			return;
		}
		?>
		<div class="charitable-dash-v3-version-line">
			<?php if ( ! empty( $state['on_latest'] ) ) : ?>
				<div class="charitable-dashboard-v2-latest-updates-uptodate">
					<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="14" height="14" aria-hidden="true">
						<path d="M16.7 5.3L7.5 14.6L3.3 10.4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span><?php
						echo wp_kses(
							sprintf(
								/* translators: %s: current installed version number */
								__( "You're running the latest version (<strong>%s</strong>).", 'charitable' ),
								esc_html( $state['version'] )
							),
							[ 'strong' => [] ]
						);
					?></span>
				</div>
			<?php else : ?>
				<div class="charitable-dashboard-v2-latest-updates-behind">
					<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="14" height="14" aria-hidden="true">
						<circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2"/>
						<path d="M10 9v4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
						<circle cx="10" cy="6.3" r="1.1" fill="currentColor"/>
					</svg>
					<span><?php
						$versions_label = sprintf(
							/* translators: %d: number of versions behind */
							_n( '%d version', '%d versions', $state['updates_behind'], 'charitable' ),
							$state['updates_behind']
						);
						echo wp_kses(
							sprintf(
								/* translators: 1: versions-behind label (e.g. "2 versions"), 2: plugins page URL */
								__( 'You are behind <strong>%1$s</strong>. <a href="%2$s">Update now</a>', 'charitable' ),
								esc_html( $versions_label ),
								esc_url( admin_url( 'plugins.php?s=charitable' ) )
							),
							[ 'strong' => [], 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
						);
					?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

		/* --- Rail status cards, ported from Charitable Pro 1.8.19.5 and adapted for Lite. --- */



	/**
	 * The Site Status rows: what is on, what is off, and how many gateways are live.
	 *
	 * Treatment D of four that were mocked up - a dense two-column list rather than tiles or
	 * chips - chosen because every state says a WORD as well as carrying a colour, so it still
	 * reads for someone who cannot tell the green dot from the amber one. Two lines, no buttons.
	 *
	 * DELIBERATELY ACTIONLESS. No fix links here, even on an off row. Recommended sits directly
	 * below this and already owns every action with a real button; putting a second, quieter
	 * action on the same problem would give the card two competing places to click for one fix.
	 * This area answers "what is the state of things", the list below answers "what should I do".
	 *
	 * Every signal is read the same way the recommendation rules read it - same functions, same
	 * plugin path - so the two surfaces cannot contradict each other. That matters more than
	 * precision here: if this row said Secure Email was On while the list below still recommended
	 * WP Mail SMTP, the card would be arguing with itself. The known limitation that comes with
	 * that is WP Mail SMTP active but with no mailer configured, which both surfaces count as On.
	 *
	 * Colour: #5AA152 / #dba617 / #d63638 are the Site Analysis band palette, already in the v3
	 * stylesheet, so these states match the score ring in the tab rather than introducing a
	 * fourth traffic-light. Red is reserved for one case - no live gateway, meaning the site
	 * cannot take money at all. An unauthenticated email still sends, so that is amber.
	 *
	 * @since 1.8.15
	 *
	 * @return array List of { @type string $label. @type string $value. @type string $tone. }
	 */
	private function get_site_status_rows() {

		/*
		NO DONATION GUARD ROW ON LITE, and no rate limiting either.

		Pro's card is titled "Donation Guard" and reports three switches: captcha, Donation Guard
		and rate limiting. Lite has exactly one of those. That is not an inference from the settings
		being empty - includes/admin/security/modules/ contains a single entry, captcha, so there is
		no module behind the other two and nothing to read even in principle.

		So the card is "Spam Protection" here, with the captcha row alone. The title matters: calling
		it "Donation Guard" would name a Pro feature and then report it off, which reads as a thing
		the reader has failed to switch on rather than a thing their edition does not include.

		$guard_on is therefore gone, and every former use of it now reads $captcha['configured'] -
		which is safe because $captcha is assigned below, before any of those use sites. Captcha
		state itself needed no adaptation at all: Lite ships all four providers get_captcha_state()
		branches on.
		@since 1.8.15
		*/

		/*
		AI Ready is a property of Pro rather than a setting, so in practice it is always on - but
		it is read off the class that actually provides it rather than hardcoded true, so the row
		cannot claim a capability that is not loaded.
		*/
		$ai_on = class_exists( 'Charitable_Abilities' );

		/*
		Abilities we actually register, counted rather than hardcoded. Names are namespaced
		"charitable/<slug>", so the prefix test separates ours from core's - wp_get_abilities()
		returns everything registered site-wide (59 on a site like this one, of which 29 are ours),
		and a total would be reporting other plugins' work as Charitable's.
		*/
		$ability_count = 0;

		if ( $ai_on && function_exists( 'wp_get_abilities' ) ) {
			foreach ( (array) wp_get_abilities() as $key => $ability ) {
				$name = is_object( $ability ) && method_exists( $ability, 'get_name' ) ? $ability->get_name() : (string) $key;

				if ( 0 === strpos( $name, 'charitable/' ) ) {
					++$ability_count;
				}
			}
		}

		/*
		What an assistant may actually DO, not merely whether the API is present.

		Charitable_Abilities_Registrar::write_enabled() is the canonical read - the same call the
		telemetry and System Info both make. It reads ai_mcp_write_enabled out of the shared
		charitable_settings option (the toggle on Tools > AI MCP, saved over AJAX) and then runs it
		through the charitable_abilities_allow_write filter, so a site that forces the gate in code
		is reported as it really behaves rather than as the stored setting claims.

		Three states, and only two of them are a choice:

		  Read/Write  abilities registered and the write toggle on
		  Read Only   abilities registered, write toggle off - the default on a fresh site
		  Disabled    no Charitable abilities registered at all

		There is NO setting that turns abilities off wholesale - Pro registers them unconditionally,
		and only the write half has a gate. So Disabled is not something an administrator can pick;
		it means the registrars did not load, which is a fault rather than a preference. It gets the
		amber pill for that reason. @since 1.8.15
		*/
		$ai_writes = class_exists( 'Charitable_Abilities_Registrar' ) && Charitable_Abilities_Registrar::write_enabled();

		if ( ! $ai_on || $ability_count < 1 ) {
			$ai_value = __( 'Disabled', 'charitable' );
			// Red, like Donation Guard being down: nothing is registered, so nothing is running.
			$ai_tone  = 'bad';
		} else {
			// "Read Only" rather than "Read": on this row the useful half is what is NOT permitted.
			$ai_value = $ai_writes
				? __( 'Read/Write', 'charitable' )
				: __( 'Read Only', 'charitable' );
			$ai_tone  = 'good';
		}

		// Same plugin path and the same is_plugin_active() call as the 'smtp' recommendation.
		$smtp_file    = 'wp-mail-smtp/wp_mail_smtp.php';
		$smtp_on      = function_exists( 'is_plugin_active' ) && is_plugin_active( $smtp_file );
		$smtp_present = file_exists( WP_PLUGIN_DIR . '/' . $smtp_file );

		/*
		Live gateways and their names. Offline is excluded - it is a record-keeping method, not a
		way for a donor to pay - and it is excluded BY ID rather than by name, because the name is
		translated and a name test would stop working the moment the admin is not in English.

		Instantiating each gateway to read its label is core's own approach: both
		Charitable_Gateways::get_gateway_choices() and ::get_active_gateways_names() do exactly
		this, and the legacy dashboard view already calls the latter on render. The only reason
		this does not just call that helper is that it returns an unkeyed list of names, which
		leaves no way to drop offline except by matching the translated string.
		*/
		$gateway_names = array();

		if ( function_exists( 'charitable_get_helper' ) ) {
			$helper = charitable_get_helper( 'gateways' );

			if ( is_object( $helper ) && method_exists( $helper, 'get_active_gateways' ) ) {
				foreach ( (array) $helper->get_active_gateways() as $gateway_id => $gateway_class ) {

					if ( 'offline' === $gateway_id || ! class_exists( $gateway_class ) ) {
						continue;
					}

					$gateway_object = $helper->get_gateway_object( $gateway_id );

					if ( is_object( $gateway_object ) && method_exists( $gateway_object, 'get_name' ) ) {
						$gateway_names[] = $gateway_object->get_name();
					}
				}
			}
		}

		$live = count( $gateway_names );

		/*
		The blocked-attack count that used to feed the Donation Guard note is GONE, and with it the
		one query this method was paying for: get_bot_blocked_count() runs a get_posts() with a
		date_query, and the note it fed now reports CAPTCHA instead. Worth knowing if it comes back
		- the date window is also why a site can hold bot records and still read zero (zeta has 4
		closed attacks, all of them 4 months old), which reads like a broken count and is not one.
		*/

		// Provider-aware, unlike the check this replaced - see get_captcha_state().
		$captcha = $this->get_captcha_state();

		$on  = __( 'On', 'charitable' );
		$off = __( 'Off', 'charitable' );

		/*
		Each note is a fact line and then, on its own line beneath it, a link. Two escaped pieces
		rather than one string containing an <a>, so there is no wp_kses call and the link label
		stays translatable on its own instead of being buried in markup a translator must preserve.

		The link always breaks onto its own line. That is a deliberate shape, not a wrap: at 207px
		a cell cannot hold "Captcha Not Installed. View Settings" on one line anyway, and letting
		it reflow put the break in a different place in every cell. Fixing the link to line two
		makes all four cells the same two-line object.

		Why these facts: the line should say something the On/Off cannot. The Donation Guard note
		reports CAPTCHA rather than Guard itself, deliberately - the value beside it already says
		whether Guard is on, whereas CAPTCHA is the other half of the same defence and has no row.
		*/
		$status_notes = array(
			/*
			Three named protections and whether each is on, in a fixed order, rather than a sentence.

			The line this replaced read "Card testing is not blocked. Turn it on to stop it." - accurate
			and useless to anyone who does not already know what card testing is. A reader does not need
			the threat explained; they need to know which switches are down.

			Order is fixed so the line is scannable in the same shape on every site, and the CSS clamps it
			to two lines, so on a narrow rail the third item can fall out of view. That is deliberate: the
			button beside it goes to the screen where all three live, so nothing is only reachable here.

			Captcha comes first because it is the one that stops a human-driven attempt as well as a bot.
			*/
			/*
			ONE protection on Lite, not three, so this reports captcha and names it.

			Pro's line is "Captcha On · Donation Guard On · Rate Limiting On". Dropping two thirds of
			that and leaving "Captcha Off" alone would be accurate but thin, so the note says which
			protection it means rather than leaving the reader to infer it from a single word.

			The three states get three different lines, because the middle one is the one that
			matters and an On/Off pair cannot express it: a provider selected with no keys saved is
			the silent donation-failure mode - the form does nothing, no donation arrives, and the
			logs stay empty. Reporting that as "On" would hide the only fault here worth surfacing.
			@since 1.8.15
			*/
			'guard'    => array(
				'text'  => $captcha['configured']
					? sprintf(
						/* translators: %s: the captcha provider's name, e.g. "hCaptcha". */
						__( 'Captcha is on, using %s.', 'charitable' ),
						$this->captcha_provider_label( $captcha['provider'] )
					)
					: ( $captcha['enabled']
						? __( 'A captcha provider is selected but its keys are missing, so no captcha runs.', 'charitable' )
						: __( 'No captcha is protecting your donation forms.', 'charitable' )
					),
				/*
				'warn' rather than 'bad' for the unconfigured case even though it is the more broken
				of the two: the reader has already tried to do the right thing, and the amber is
				paired with wording that says exactly what is missing.
				*/
				'tone'  => $captcha['configured'] ? 'good' : ( $captcha['enabled'] ? 'warn' : 'bad' ),
				'label' => $captcha['configured']
					? __( 'View Settings', 'charitable' )
					: __( 'Turn On', 'charitable' ),
				// Lite's captcha sub-tab, not Pro's security__donationguard.
				'url'   => $this->get_security_settings_url( 'captcha' ),
			),
			'ai'       => array(
				/*
				"29+" from the real count rather than a bare 29 or a hardcoded figure: the number is
				exact today but grows with every release, and a floor reads as a capability where an
				exact number reads as a limit.
				*/
				'text'  => $ability_count > 0
					? sprintf(
						/* translators: %s: number of Charitable abilities available to AI assistants. */
						__( '%s+ Abilities available to AI assistants.', 'charitable' ),
						number_format_i18n( $ability_count )
					)
					: '',
				'tone'  => 'good',
				'label' => __( 'Connect Assistant', 'charitable' ),
				'url'   => admin_url( 'admin.php?page=charitable-tools&tab=ai-mcp' ),
			),
			'smtp'     => $smtp_on
				? array(
					'text'  => __( 'Secure. Sending through WP Mail SMTP.', 'charitable' ),
					'tone'  => 'good',
					/*
					A way in even when all is well, matching Donation Guard's "View Settings" in its
					good state: Emails > Status is where deliverability is checked and tested, so it
					is the natural next stop from this row either way. @since 1.8.15
					*/
					'label' => __( 'View Settings', 'charitable' ),
					'url'   => admin_url( 'admin.php?page=charitable-settings&tab=emails&sub_tab=status' ),
				)
				: array(
					'text'  => __( 'Not Secure. Recommend WP Mail SMTP.', 'charitable' ),
					'tone'  => 'warn',
					'label' => __( 'Learn More', 'charitable' ),
					/*
					Stays inside wp-admin, and goes somewhere different depending on which of the two
					problems the site actually has.

					PLUGIN ON DISK BUT INACTIVE: the Plugins screen filtered to inactive is both the
					explanation and the fix - the row is sitting right there with an Activate link.

					PLUGIN ABSENT: Charitable's own SMTP page, which exists for exactly this and
					leads with "Install the WP Mail SMTP plugin by clicking this button". It was
					pointing at plugin-install.php?s=wp+mail+smtp, a keyword search whose first
					result is the right plugin but which also asks the reader to pick it out of a
					result set and trust they chose correctly. The dedicated page installs the known
					plugin in one click, so it is both fewer steps and unambiguous.

					Registered at class-charitable-admin-pages.php with capability
					manage_charitable_settings, the same capability that gates this dashboard, so
					anyone who can see this card can open it. Confirmed resolving rather than
					assumed - the SMTP submenu ITEM rotates with Privacy Compliance, so the menu
					entry is not always visible, but the page itself is registered unconditionally
					and the URL loads.
					@since 1.8.15
					*/
					'url'   => $smtp_present
						? admin_url( 'plugins.php?s=wp+mail+smtp&plugin_status=inactive' )
						: admin_url( 'admin.php?page=charitable-smtp' ),
				),
			'gateways' => $live > 0
				? array(
					/*
					Names the gateway rather than repeating "1 live", which the value already says. One
					gateway gets the full sentence - "Stripe is Connected" reads as a reassurance, where a
					bare "Stripe" reads as a label and leaves you wondering whether it works. Two or more
					stay a plain list: "Stripe, PayPal are Connected" would need pluralised grammar in every
					translation for no gain.
					*/
					'text'  => 1 === $live
						? sprintf(
							/* translators: %s: name of the connected payment gateway, e.g. "Stripe". */
							__( '%s is Connected. Donations can be taken.', 'charitable' ),
							$gateway_names[0]
						)
						: implode( ', ', $gateway_names ),
					'tone'  => 'good',
					'label' => __( 'Manage', 'charitable' ),
					'url'   => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
				)
				: array(
					'text'  => __( 'No gateway connected. Donations cannot be taken.', 'charitable' ),
					'tone'  => 'warn',
					'label' => __( 'Connect Stripe', 'charitable' ),
					'url'   => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
				),
		);
		/*
		Source order fills the two-column grid row by row, so this order puts the two always-good
		rows on the top line and anything likely to need attention on the second, where the eye
		lands after reading the version line above.
		*/
		/*
		Row icons. Where a product has a real mark we use it and leave it its own colour: the
		DonationGuard shield is the same filled #2271b1 path as .charitable-donationguard-icon in
		charitable-admin-legacy.scss, and WP Mail SMTP is the bundled SVG - so these match what
		Settings > Security and the recommendation list already show.

		AI Ready and Gateways have no mark to use. A sparkle and a card are stroked glyphs in the
		same muted grey as the recommendation icons, which is the honest answer rather than inventing
		two logos. Deliberately NOT tinted to the row's state colour: an icon that turns amber would
		compete with the pill that is already saying exactly that. @since 1.8.15
		*/
		$glyph = function ( $paths ) {
			return '<svg viewBox="0 0 20 20" width="15" height="15" fill="none" stroke="rgba(25,29,45,.45)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">' . $paths . '</svg>';
		};

		$guard_icon = '<svg viewBox="0 0 20 20" width="15" height="15" fill="#2271b1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M10 1.5L3 4v5.5c0 4 2.8 7.5 7 8.5 4.2-1 7-4.5 7-8.5V4l-7-2.5z"/></svg>';

		return array(
			array(
				'icon'  => $guard_icon,
				/*
				"Spam Protection", not Pro's "Donation Guard". See the note at the top of this method:
				naming a Pro feature and then reporting it off would read as something the reader has
				failed to switch on, rather than something their edition does not include.
				@since 1.8.15
				*/
				'label' => __( 'Spam Protection', 'charitable' ),
				'value' => $captcha['configured'] ? $on : $off,
				/*
				Three tones for three states, matching the note beside it. 'bad' only when nothing is
				protecting the form at all; a provider chosen but unconfigured takes 'warn', because
				the reader has already tried to do the right thing and the wording says what is
				missing.
				*/
				'tone'  => $captcha['configured'] ? 'good' : ( $captcha['enabled'] ? 'warn' : 'bad' ),
				'note'  => $status_notes['guard'],
			),
			array(
				'icon'  => $glyph( '<path d="M10 2.6 11.5 8 17 9.5 11.5 11 10 16.4 8.5 11 3 9.5 8.5 8 10 2.6Z"/>' ),
				// The ability count sits on the note line above the link, not in the label.
				'label' => __( 'AI Ready', 'charitable' ),
				'value' => $ai_value,
				'tone'  => $ai_tone,
				'note'  => $status_notes['ai'],
			),
			array(
				/*
				An envelope, not the WP Mail SMTP mark. This row is about the state of email on the site,
				which is true whether or not that particular plugin is the answer - and the note below it
				already names WP Mail SMTP as a recommendation rather than a requirement, so leading the
				row with its logo overstated it. The recommendation list still uses the real mark, where
				naming one specific plugin IS the point.
				*/
				'icon'  => $glyph( '<rect x="2.5" y="4.5" width="15" height="11" rx="1.6"/><path d="m3 5.5 7 5 7-5"/>' ),
				/*
				"Email Status" in both states, replacing the Secure Email / Secure Email Needed pair. The
				pill already says On or Off, so the label spent a word repeating it - and the fact line
				below now carries the verdict ("Not Secure."), which is where it belongs.
				*/
				'label' => __( 'Email Status', 'charitable' ),
				/*
				"Basic", not "Off". Email is not off when WP Mail SMTP is inactive - WordPress still sends
				through PHP mail(), it just sends unauthenticated, which is why receipts land in spam
				rather than not arriving. Telling somebody their email is Off when donors are receiving
				receipts is the kind of wrong that costs the whole card its credibility.

				It also keeps the amber, while genuinely-off things now go red. That gives the card three
				honest tiers rather than two: working, working-but-weaker, and not running at all.
				*/
				'value' => $smtp_on ? $on : __( 'Basic', 'charitable' ),
				'tone'  => $smtp_on ? 'good' : 'warn',
				'note'  => $status_notes['smtp'],
			),
			array(
				'icon'  => $glyph( '<rect x="2.4" y="4.8" width="15.2" height="10.4" rx="1.8"/><path d="M2.4 8.6h15.2M5.4 12.4h3"/>' ),
				'label' => __( 'Gateways', 'charitable' ),
				/*
				2026-09-23: was a count - _n( '%s live', ... ) rendering "1 LIVE". David asked for
				the verdict without the number. The count answered a question nobody asks of this
				card: the card exists to say whether money can be taken, and one live gateway
				answers that exactly as well as four. The number is still on the Gateways settings
				screen for anyone who wants it, and $live stays in use below for the same verdict
				in machine form.
				*/
				'value' => $live > 0
					? __( 'Live', 'charitable' )
					: __( 'Not live', 'charitable' ),
				/*
				'good', not the 'neutral' this carried while it was a count - the old comment read
				"a count is a fact, not a verdict, so it carries no dot unless it is zero", and that
				reasoning retires with the count. Zero pixels change: --neutral was already
				overridden to the same #276c35 as --good in charitable-admin-dashboard-v3.css,
				precisely because a live gateway is good news. That override is now unreferenced -
				this row was its only consumer - so it is dead CSS awaiting a cleanup pass.
				*/
				'tone'  => $live > 0 ? 'good' : 'bad',
				'note'  => $status_notes['gateways'],
			),
		);
	}



	/**
	 * Recommendations for the rail card, highest priority first.
	 *
	 * Titles are phrased to stand alone: there is no description line, so "Use WP Mail SMTP For
	 * Secure Email" has to do the work "Your email is not secure" plus a paragraph used to do.
	 *
	 * Every entry is gated on a REAL signal - there are no placeholder rows. Verified on zeta while
	 * building: captcha_provider is set to cloudflare-turnstile but both keys are empty, which is
	 * exactly the half-finished state worth surfacing and which a simple "is a provider chosen"
	 * check would have called done.
	 *
	 * Order is by what it costs the organisation, not by how easy it is to fix:
	 *   1. no live gateway      - cannot take money at all, so nothing else matters
	 *   2. donations unprotected - card testing and spam hit real money
	 *   3. receipts may not send - donors do not get confirmation
	 *   4. never measured        - optimisation, genuinely last
	 *
	 * "offline" is excluded when counting gateways on purpose: it is registered as a gateway but
	 * takes no payment, so a site with only Offline active cannot actually receive a donation and
	 * should still be told to connect one.
	 *
	 * @since 1.8.15
	 *
	 * @return array
	 */
	private function get_dashboard_recommendations() {

		static $memo = null;

		if ( null !== $memo ) {
			return $memo;
		}

		$settings = get_option( 'charitable_settings', array() );
		$items    = array();

		$icon = function ( $paths ) {
			return '<svg viewBox="0 0 20 20" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">' . $paths . '</svg>';
		};

		// ---- 1. A live payment gateway. ----
		$gateways = array();

		if ( function_exists( 'charitable_get_helper' ) ) {
			$helper = charitable_get_helper( 'gateways' );

			if ( is_object( $helper ) && method_exists( $helper, 'get_active_gateways' ) ) {
				$gateways = array_keys( (array) $helper->get_active_gateways() );
			}
		}

		$live = array_diff( $gateways, array( 'offline' ) );

		if ( empty( $live ) ) {
			$items[] = array(
				'key'   => 'gateway',
				'title' => __( 'Connect Stripe To Accept Donations', 'charitable' ),
				'cta'   => __( 'Connect Stripe', 'charitable' ),
				'url'   => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
				'cat'   => 'setup',
				'icon'  => $icon( '<rect x="2.4" y="4.8" width="15.2" height="10.4" rx="1.8"/><path d="M2.4 8.6h15.2M5.4 12.4h3"/>' ),
			);
		}

		// ---- 2. CAPTCHA. Provider chosen is not the same as configured. ----
		/*
		BUG FIXED HERE. This used to read cloudflare_turnstile_site_key / _secret_key directly,
		WHATEVER provider was selected. A site on reCAPTCHA v3 or hCaptcha with valid keys was told
		to "Add Your Missing CAPTCHA Keys" and could never clear it, because the keys being checked
		belong to a provider it is not using. get_captcha_state() does it provider by provider.
		@since 1.8.15
		*/
		$captcha    = $this->get_captcha_state();
		$provider   = $captcha['enabled'] ? $captcha['provider'] : '';
		$captcha_on = $captcha['configured'];

		/*
		Provider marks are INLINED rather than referenced as files, because the bundled SVGs in
		assets/images/bot-protection/ are all fill="currentColor". Loaded through an <img> there is no
		colour context for currentColor to inherit, so they resolved to black - Cloudflare's cloud
		rendered as a dark blob. Inlining lets each mark carry its own brand colour: Cloudflare orange
		#F6821F here, taken from their brand guidance, with the path itself copied unchanged from
		cloudflare-turnstile.svg.
		*/
		$captcha_marks = array(
			'cloudflare-turnstile' => '<svg viewBox="0 0 40 40" width="17" height="17" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path fill="#F6821F" d="M31.4 22.3c.1-.5.2-1 .2-1.5 0-3-2.4-5.4-5.4-5.4-.6 0-1.2.1-1.7.3-1.4-2.9-4.4-4.9-7.9-4.9-4.7 0-8.6 3.7-8.8 8.3C5.4 19.7 4 21.6 4 23.8c0 2.8 2.3 5.1 5.1 5.1h21.6c2.4 0 4.3-1.9 4.3-4.3 0-1.1-.4-2.1-1-2.9-.7-.9-1.7-1.4-2.6-1.4z"/></svg>',
		);

		$captcha_mark = isset( $captcha_marks[ $provider ] ) ? $captcha_marks[ $provider ] : '';

		if ( ! $captcha_on ) {
			$items[] = array(
				'key'   => 'captcha',
				'title' => '' !== $provider
					? __( 'Add Your Missing CAPTCHA Keys', 'charitable' )
					: __( 'Turn On CAPTCHA To Block Spam', 'charitable' ),
				'cta'   => __( 'Set Up', 'charitable' ),
				// tab=security, not tab=advanced - advanced is Misc. See get_security_settings_url().
				'url'   => $this->get_security_settings_url( 'captcha' ),
				'cat'   => 'security',
				/*
				The chosen provider's own logo when we have it - assets/images/bot-protection/ ships
				marks for all three. More useful than a generic shield: it tells you WHICH provider is
				half-configured without opening settings.
				*/
				'icon'  => '' !== $captcha_mark
					? $captcha_mark
					: $icon( '<path d="M10 2.6 16 4.8v5c0 4-2.5 6.6-6 7.6-3.5-1-6-3.6-6-7.6v-5z"/><path d="M7.6 9.9 9.6 12l3.2-3.6"/>' ),
			);
		}

		/*
		---- 3. Donation Guard: REMOVED FOR LITE. ----

		Pro recommends activating Donation Guard here. Lite has no Donation Guard - there is no module
		for it under includes/admin/security/modules/, which holds captcha and nothing else - so the
		recommendation is removed rather than left to gate on a function that does not exist.

		It is NOT replaced with an upsell. The captcha rule above already covers spam protection on
		Lite and links to a screen that works; adding "upgrade for Donation Guard" directly beneath it
		would make two of the handful of recommendation slots say the same thing, one of them
		unactionable. The numbering below is left alone so these rules stay comparable with Pro's.
		@since 1.8.15
		*/

		// ---- 4. Email deliverability. ----
		$smtp_file    = 'wp-mail-smtp/wp_mail_smtp.php';
		$smtp_present = file_exists( WP_PLUGIN_DIR . '/' . $smtp_file );
		$smtp_active  = function_exists( 'is_plugin_active' ) && is_plugin_active( $smtp_file );

		if ( ! $smtp_active ) {
			$items[] = array(
				'key'   => 'smtp',
				'title' => __( 'Use WP Mail SMTP For Secure Email', 'charitable' ),
				'cta'   => $smtp_present ? __( 'Activate', 'charitable' ) : __( 'Install', 'charitable' ),
				'url'   => admin_url( 'plugin-install.php?s=wp+mail+smtp&tab=search&type=term' ),
				'cat'   => 'delivery',
				// The real WP Mail SMTP mark, already in the tree.
				// The real WP Mail SMTP mark, already in the tree.
				'icon_url' => charitable()->get_path( 'directory', false ) . 'assets/images/icons/icon-wpmailsmtp.svg',
			);
		}

		// ---- 5. Spam Blocker addon. ----
		$spam = $this->get_charitable_addon_data( 'charitable-spam-blocker' );

		if ( is_array( $spam ) && empty( $spam['active'] ) ) {
			$items[] = array(
				'key'   => 'spam',
				'title' => __( 'Filter Junk Donations With Spam Blocker', 'charitable' ),
				'cta'   => __( 'Get It', 'charitable' ),
				'url'   => admin_url( 'admin.php?page=charitable-addons' ),
				'cat'   => 'security',
				'icon'  => $icon( '<path d="M10 2.6 16 4.8v5c0 4-2.5 6.6-6 7.6-3.5-1-6-3.6-6-7.6v-5z"/><path d="M7.8 7.8l4.4 4.4M12.2 7.8l-4.4 4.4"/>' ),
			);
		}

		// ---- 6. HTTPS. ----
		if ( 'https' !== wp_parse_url( home_url(), PHP_URL_SCHEME ) ) {
			$items[] = array(
				'key'   => 'https',
				'title' => __( 'Serve Your Site Over HTTPS', 'charitable' ),
				'cta'   => __( 'How To Fix', 'charitable' ),
				'url'   => 'https://wpcharitable.com/documentation/',
				'cat'   => 'security',
				'icon'  => $icon( '<rect x="4.4" y="8.8" width="11.2" height="8" rx="1.4"/><path d="M7 8.8V6.6a3 3 0 0 1 6 0v2.2"/><path d="M10 11.8v2"/>' ),
			);
		}

		/*
		---- 7. Never measured. Genuinely last. ----

		READS THE SNAPSHOT DIRECTLY, where Pro calls get_site_analysis_dashboard_score(). That method
		belongs to the Site Analysis tab, which has not been ported yet, and all this rule needs is
		whether a score has ever been stored - so it asks the store rather than the formatter, and
		gains nothing from waiting.

		Both guards are Pro's, kept for Pro's reason: class_exists() alone once sailed past a
		half-present class straight into "Call to undefined method
		Charitable_Site_Analysis::get_last_known_score()", which killed the whole dashboard render.
		@since 1.8.15
		*/
		$charitable_sa_scored = class_exists( 'Charitable_Site_Analysis' )
			&& method_exists( 'Charitable_Site_Analysis', 'get_last_known_score' )
			&& is_array( Charitable_Site_Analysis::get_instance()->get_last_known_score() );

		if ( ! $charitable_sa_scored ) {
			$items[] = array(
				'key'   => 'analysis',
				'title' => __( 'Score Your Setup With Site Analysis', 'charitable' ),
				'cta'   => __( 'Run It', 'charitable' ),
				// Lite's Site Analysis is a REPORTS tab, not a dashboard tab - see
				// Charitable_Site_Analysis::register_tab(), which filters charitable_reports_tabs.
				'url'   => admin_url( 'admin.php?page=charitable-reports&tab=site-analysis' ),
				'cat'   => 'growth',
				'icon'  => $icon( '<path d="M3.2 15.4V9.6M8.4 15.4V4.6M13.6 15.4v-7M18 15.4V6.4"/>' ),
			);
		}

		/**
		 * Filter the dashboard recommendations.
		 *
		 * @since 1.8.15
		 *
		 * @param array $items Recommendation definitions, highest priority first.
		 */
		$memo = (array) apply_filters( 'charitable_dashboard_recommendations', $items );

		return $memo;
	}



	/**
	 * The rail health card: version on one line, security posture beneath it.
	 *
	 * Replaced three separate status tiles. The Site Score moved to the Site Analysis tab badge,
	 * and two tiles do not justify a row of their own - more importantly, version and security are
	 * both "is this install in good shape" questions, so they read better as one answer than as
	 * scattered numbers.
	 *
	 * Security is expressed as ON or OFF per protection rather than as a score, because each one is
	 * genuinely binary and an aggregate would hide which of them is the problem. Every value here
	 * is live: the version numbers drive the update state, and the three checks come from
	 * get_security_checks(), which reads real settings rather than placeholders.
	 *
	 * @since 1.8.15
	 *
	 * @return void
	 */
	public function render_rail_status_cards() {

		/*
		Was also computing $checks / $on / $total from get_security_checks(), none of which was
		referenced anywhere below - left behind by the security-card iteration this card replaced.
		Removed. The $blocked count that sat alongside them went the same way and then came BACK,
		inside get_site_status_rows(), which is where it belongs: it now feeds the Donation Guard
		note instead of being computed and discarded. @since 1.8.15
		*/
		$version_state = $this->get_dashboard_version_state();
		$behind        = (int) $version_state['updates_behind'];
		$status_rows   = $this->get_site_status_rows();
		?>
		<section class="charitable-health-card">

			<?php
			/*
			Tinted by state. Green while current, amber while behind - the tint is the same signal the
			check/link on the right already carries, just readable without reading. Modifier classes
			rather than an inline style so the two colours stay in the stylesheet with the rest of the
			band palette. @since 1.8.15
			*/
			?>
			<div class="charitable-health-version charitable-health-version--<?php echo $behind > 0 ? 'behind' : 'current'; ?>">
				<span class="charitable-health-version-name">
					<?php esc_html_e( 'Charitable', 'charitable' ); ?>
					<strong><?php echo esc_html( $version_state['version'] ); ?></strong>
				</span>
				<?php if ( $behind > 0 ) : ?>
					<a href="<?php echo esc_url( admin_url( 'plugins.php?s=charitable' ) ); ?>" class="charitable-health-version-action"><?php esc_html_e( 'Update now', 'charitable' ); ?></a>
				<?php else : ?>
					<span class="charitable-health-version-status">
						<svg viewBox="0 0 20 20" width="12" height="12" fill="none" aria-hidden="true"><path d="M16.7 5.3 7.5 14.6 3.3 10.4" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<?php esc_html_e( 'Up to date', 'charitable' ); ?>
					</span>
				<?php endif; ?>
			</div>


			<?php if ( ! empty( $status_rows ) ) : ?>
				<div class="charitable-health-status">
					<?php foreach ( $status_rows as $status_row ) : ?>
						<div class="charitable-health-stat">
							<div class="charitable-health-stat-top">
								<span class="charitable-health-stat-label">
									<?php if ( ! empty( $status_row['icon_url'] ) ) : ?>
										<img class="charitable-health-stat-icon" src="<?php echo esc_url( $status_row['icon_url'] ); ?>" alt="" width="15" height="15" />
									<?php elseif ( ! empty( $status_row['icon'] ) ) : ?>
										<span class="charitable-health-stat-icon"><?php echo $status_row['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php endif; ?>
									<span class="charitable-health-stat-name"><?php echo esc_html( $status_row['label'] ); ?></span>
								</span>
								<span class="charitable-health-stat-value charitable-health-stat-value--<?php echo esc_attr( $status_row['tone'] ); ?>">
									<?php echo esc_html( $status_row['value'] ); ?>
								</span>
							</div>
							<?php if ( ! empty( $status_row['note'] ) ) : ?>
								<div class="charitable-health-stat-note">
									<?php if ( ! empty( $status_row['note']['text'] ) ) : ?>
										<span class="charitable-health-stat-fact charitable-health-stat-fact--<?php echo esc_attr( ! empty( $status_row['note']['tone'] ) ? $status_row['note']['tone'] : 'plain' ); ?>"><?php echo esc_html( $status_row['note']['text'] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $status_row['note']['url'] ) && ! empty( $status_row['note']['label'] ) ) : ?>
										<a href="<?php echo esc_url( $status_row['note']['url'] ); ?>"><?php echo esc_html( $status_row['note']['label'] ); ?></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php
			/*
			RECOMMENDED IS SWITCHED OFF, NOT DELETED. Everything below this flag is intact and
			unmodified - get_dashboard_recommendations() with its seven rules, the nested
			collapsible, the "Show N more" toggle, the empty-state message and section 18 of the
			stylesheet. Flip this one boolean to bring the whole thing back.

			A flag rather than commenting the block out, for three reasons: the markup keeps being
			parsed so a syntax error cannot hide in it, phpcs and the .pot scan still see the
			strings, and there is exactly one obvious place to look for why the section is missing.

			The flag is checked BEFORE get_dashboard_recommendations() on purpose - that method
			reads the gateway helper, two plugin paths, the addon transient and the settings array,
			so gating after the call would keep paying for work nothing renders. @since 1.8.15
			*/
			$charitable_show_recs = false;

			if ( $charitable_show_recs ) :
				?>
			<?php
			$charitable_recs = $this->get_dashboard_recommendations();

			/*
			Dismissal was removed in favour of the collapse chevron: the section is hidden by
			collapsing the card, which is reversible in one click and needs no stored state. The
			dismissal option, its 30-day expiry and the under-attack override all went with it - a
			stale option with no UI to clear it would have hidden these permanently.
			*/
			$charitable_rest = array_slice( $charitable_recs, 3 );
			?>

			<?php if ( empty( $charitable_recs ) ) : ?>
				<div class="charitable-rec-clear">
					<svg viewBox="0 0 20 20" width="15" height="15" fill="none" aria-hidden="true"><path d="M16.7 5.3 7.5 14.6 3.3 10.4" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<span><?php esc_html_e( 'Everything we check is set up.', 'charitable' ); ?></span>
				</div>
			<?php else : ?>

				<?php
				/*
				Nested collapsible, wrapping ONLY the recommendations. The shipping toggle handler
				collapses .charitable-dashboard-v2-section-content inside the nearest
				.charitable-dashboard-v2-collapsible, so putting the collapsible on this inner wrapper
				rather than on the card keeps the version line visible when the list is closed. Marking
				the whole card collapsible would have taken the version line down with it. @since 1.8.15
				*/
				?>
				<div class="charitable-dashboard-v2-collapsible charitable-rec-collapsible">

				<div class="charitable-health-head">
					<span class="charitable-health-head-title"><?php esc_html_e( 'Recommended', 'charitable' ); ?></span>
					<?php $this->render_collapse_toggle(); ?>
				</div>

				<div class="charitable-dashboard-v2-section-content">

				<ul class="charitable-rec-list">
					<?php
					$charitable_i = 0;
					foreach ( $charitable_recs as $charitable_rec ) :
						$charitable_hidden = $charitable_i >= 3;
						++$charitable_i;
						?>
						<li class="charitable-rec<?php echo $charitable_hidden ? ' charitable-rec--extra' : ''; ?>" data-rec="<?php echo esc_attr( $charitable_rec['key'] ); ?>"<?php echo $charitable_hidden ? ' hidden' : ''; ?>>
							<span class="charitable-rec-icon">
								<?php if ( ! empty( $charitable_rec['icon_url'] ) ) : ?>
									<img src="<?php echo esc_url( $charitable_rec['icon_url'] ); ?>" alt="" width="18" height="18" />
								<?php else : ?>
									<?php echo $charitable_rec['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
							</span>
							<span class="charitable-rec-title"><?php echo esc_html( $charitable_rec['title'] ); ?></span>
							<a href="<?php echo esc_url( $charitable_rec['url'] ); ?>" class="charitable-rec-cta<?php echo 1 === $charitable_i ? ' charitable-rec-cta--primary' : ''; ?>"><?php echo esc_html( $charitable_rec['cta'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( ! empty( $charitable_rest ) ) : ?>
					<button type="button" class="charitable-rec-more" data-more="<?php echo esc_attr( count( $charitable_rest ) ); ?>">
						<?php
						printf(
							/* translators: %s: how many further recommendations are hidden. */
							esc_html__( 'Show %s more', 'charitable' ),
							esc_html( number_format_i18n( count( $charitable_rest ) ) )
						);
						?>
					</button>
				<?php endif; ?>
				</div><!-- /.charitable-dashboard-v2-section-content -->
				</div><!-- /.charitable-rec-collapsible -->

			<?php endif; ?>
			<?php endif; /* $charitable_show_recs */ ?>


		</section>
		<?php
		/*
		More/dismiss behaviour. Inline rather than in charitable-admin-dashboard.js because this is
		preview-only markup and that file is shipping code shared with three list-table screens.

		The dismiss is optimistic - the row goes immediately and the request is fire-and-forget. If it
		fails the row returns on the next load, which is the right failure mode: a dismissal that
		silently did not persist is better than a row that appears stuck.
		*/
		?>
		<script>
		( function () {
			var card = document.querySelector( '.charitable-health-card' );
			if ( ! card ) { return; }

			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var nonce   = <?php echo wp_json_encode( wp_create_nonce( 'charitable-dashboard' ) ); ?>;

			var more = card.querySelector( '.charitable-rec-more' );
			if ( more ) {
				more.addEventListener( 'click', function () {
					card.querySelectorAll( '.charitable-rec--extra' ).forEach( function ( row ) {
						row.hidden = false;
					} );
					more.remove();
				} );
			}

		} )();
		</script>
		<?php
	}

		/* --- Integrations and Raise More, ported from Charitable Pro 1.8.19.5. --- */



		/**
		 * The integration cards, with a real state and action resolved for each.
		 *
		 * Mirrors WPForms' integrations block: tier badge, logo, name, and one action whose wording
		 * changes with what the site can actually do about it.
		 *
		 * The ladder, in order:
		 *
		 *   plan not met        Upgrade    -> wpcharitable.com/lite-upgrade
		 *   plan met, absent    Install    -> the Addons screen
		 *   installed, off      Activate   -> the Addons screen
		 *   active              Installed  -> no link; it is a state, not an action
		 *
		 * PLANS ARE DECLARED HERE, and that is the one editorial thing in this method. Several of
		 * these are not Charitable addons at all - Webhooks, Elementor and WPVibe have no addon
		 * record to read a licence requirement from - so there is nothing to resolve them from yet.
		 * They need product sign-off rather than my guess; charitable_current_plan_meets_requirement()
		 * does the actual gating, so when a licence map exists the only change is where 'plan' comes
		 * from.
		 *
		 * @since 1.8.15
		 *
		 * @return array
		 */
		/**
		 * Decide what an integration card offers, given only the four facts that matter.
		 *
		 * Extracted from get_integrations_items() so the ladder can be table-tested. It used to be
		 * inline in that method, which reads the licence, the filesystem and the addon directory -
		 * so there was no way to assert the decision without booting WordPress and contriving real
		 * plugin state. Pure in, pure out: no globals, no I/O, no translation.
		 *
		 * Returns null to mean "drop this card entirely", which is what an already-active
		 * integration gets.
		 *
		 * The returned 'label' is a KEY, not display text - the caller translates. Keeping
		 * __() out of here is what lets a test compare exact strings without a text domain
		 * loaded.
		 *
		 * @since 1.8.15
		 *
		 * @param  bool $plan_met  Whether the licence tier satisfies the item's required plan.
		 * @param  bool $is_free   Whether the item is a free wp.org plugin (never licence-gated).
		 * @param  bool $installed Whether it is present on disk.
		 * @param  bool $active    Whether it is currently running.
		 * @param  bool $is_plugin True for a wp.org plugin, false for a Charitable addon.
		 * @return array|null { @type string $action. @type string $label. @type string $ajax.
		 *                     @type string $type. } Null to drop the card.
		 */
		public static function resolve_integration_action( $plan_met, $is_free, $installed, $active, $is_plugin ) {

			// Already running: nothing left to offer, so the card goes rather than showing a
			// greyed-out word.
			if ( $active ) {
				return null;
			}

			// A free wp.org plugin is never gated by a Charitable licence.
			if ( ! $is_free && ! $plan_met ) {
				return array(
					'action' => 'upgrade',
					'label'  => 'upgrade',
					'ajax'   => '',
					'type'   => '',
				);
			}

			if ( $is_plugin ) {
				return array(
					'action' => $installed ? 'activate' : 'install',
					'label'  => $installed ? 'activate' : 'install',
					'ajax'   => $installed ? 'activate' : 'install',
					'type'   => 'third_party',
				);
			}

			return array(
				'action' => $installed ? 'activate' : 'install',
				'label'  => $installed ? 'activate' : 'install',
				'ajax'   => $installed ? 'activate_addon' : 'install_addon',
				'type'   => 'charitable_addon',
			);
		}


		private function get_integrations_items() {

			$base = charitable()->get_path( 'directory', false ) . 'assets/images/integrations/logos/';

			/*
			Two icon sources, deliberately. assets/images/integrations/logos/ holds third-party brand
			marks copied into the tree, and assets/images/addons/ holds the 32 Charitable addon icons
			the plugin already ships - so a first-party item takes its real addon icon rather than a
			hand-drawn glyph. Both are local files, so no card breaks when a plugin is deactivated.
			@since 1.8.15
			*/
			$addon_base = charitable()->get_path( 'directory', false ) . 'assets/images/addons/';

			/*
			DonorLoyal came out: it is an unreleased third-party SaaS, so a card offering to upgrade
			into it would promise something nobody can buy. Brevo replaces it - a real integration,
			announced in this dashboard's own What's New feed, and its mark was already in the tree.

			'plan' values are the addon's REAL minimum plan, read off Charitable_Addons_Directory
			rather than guessed - the whole point of the badge is that it tells you what saying yes
			costs, and a wrong tier is worse than no tier. Verified 2026-09-24 against
			get_addons(): recurring-donations plus, custom-receipts agency|plus (so plus),
			google-analytics plus, newsletter-connect plus, automation-connect pro, square basic,
			easy-digital-downloads-connect plus.

			Webhooks became "Charitable Automation". It is our own addon, which is exactly why it
			had no brand mark and needed a glyph, and its badge said ELITE while
			charitable-automation-connect actually requires PRO - so the old card overstated the
			cost of the thing it was selling. It now carries the real addon icon and the real plan.
			NOTE it shares charitable-automation-connect with Zapier, so the two cards always show
			the same state and activating either satisfies both. @since 1.8.15
			*/
			$items = array(
				array(
					'name' => __( 'Mailchimp', 'charitable' ),
					'slug' => 'mailchimp',
					'plan' => 'plus',
					'logo' => $base . 'mailchimp.png',
					'addon' => 'charitable-newsletter-connect',
				),
				array(
					'name' => __( 'Recurring Donations', 'charitable' ),
					'slug' => 'recurring-donations',
					'plan' => 'plus',
					'logo' => $addon_base . 'addon-icon-recurring-donations.png',
					'addon' => 'charitable-recurring-donations',
				),
				/*
				The four first-party addons that follow Recurring, in the order asked for. Plans are
				the directory's real minimums, not guesses: multi-currency and donation-recovery are
				agency|pro so they resolve to PRO, fee-relief and annual-receipts are plus.

				Two of the four have a real icon in assets/images/addons/ and two do not, so those
				two take the glyph that get_raise_more_icon() already draws for the SAME addon -
				copied path for path rather than redrawn, because Raise More and this card can both
				be on screen at once and the same addon must not wear two different marks.
				@since 1.8.15
				*/
				array(
					'name' => __( 'Multi-Currency', 'charitable' ),
					'slug' => 'multi-currency',
					'plan' => 'pro',
					'addon' => 'charitable-multi-currency',
					/*
					The OFFICIAL addon icon, added to the tree 2026-09-24 from
					wpcharitable.com/wp-content/uploads/2024/07/charitable-multi-currency-icon-green.svg
					at David's request. It replaces the stroked glyph this card borrowed from
					get_raise_more_icon(), which was two partially-drawn circles and read as a
					single broken ring at 40px rather than as coins.

					Conceptually the same composition - two coins carrying a euro and a dollar -
					but this is the real artwork rather than an approximation of it.

					Left at its own #50A66A, NOT recoloured to canon #31714c: it is a supplied
					brand asset, and that is the same green addon-icon-tributes.png already uses.
					Note #50A66A is not catalogued in brand.color.brand-green.asBuilt, which is
					empty, so drift-check.py cannot see it.
					*/
					'logo' => $addon_base . 'addon-icon-multi-currency.svg',
				),
				array(
					'name' => __( 'Fee Relief', 'charitable' ),
					'slug' => 'fee-relief',
					'plan' => 'plus',
					'logo' => $addon_base . 'addon-icon-fee-relief.png',
					'addon' => 'charitable-fee-relief',
				),
				array(
					'name' => __( 'Annual Receipts', 'charitable' ),
					'slug' => 'annual-receipts',
					'plan' => 'plus',
					'logo' => $addon_base . 'addon-icon-annual-receipts.png',
					'addon' => 'charitable-annual-receipts',
				),
				array(
					'name' => __( 'Donation Recovery', 'charitable' ),
					'slug' => 'donation-recovery',
					'plan' => 'pro',
					'addon' => 'charitable-donation-recovery',
					// Arrow curving back, from get_raise_more_icon().
					'glyph' => '<svg viewBox="0 0 20 20" width="34" height="34" fill="none" stroke="rgba(25,29,45,.5)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M3.4 9.6a6.6 6.6 0 1 1 2.4 5.6"/><path d="M3.2 5.8v3.9h3.9"/><path d="M10 7.2v3.4l2.4 1.4"/></svg>',
				),
				array(
					'name' => __( 'Google Analytics', 'charitable' ),
					'slug' => 'google-analytics',
					'plan' => 'plus',
					'logo' => $base . 'google-analytics.png',
					'addon' => 'charitable-google-analytics',
				),
				array(
					'name' => __( 'Custom Receipts', 'charitable' ),
					'slug' => 'custom-receipts',
					'plan' => 'plus',
					'addon' => 'charitable-custom-receipts',
					/*
					The one item with no icon file: assets/images/addons/ ships 32 addon icons and
					custom-receipts is not among them. A receipt glyph is the honest answer rather
					than borrowing addon-icon-pdf-receipts.png, which would label this card with a
					different addon's mark. Same stroke weight and grey as the recommendation icons.
					Replace this with a real icon once one exists.
					*/
					'glyph' => '<svg viewBox="0 0 20 20" width="34" height="34" fill="none" stroke="rgba(25,29,45,.5)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M4.8 2.6h10.4v14.8l-2.6-1.6-2.6 1.6-2.6-1.6-2.6 1.6z"/><path d="M7.4 6.6h5.2M7.4 9.8h5.2M7.4 13h3"/></svg>',
				),
				array(
					'name' => __( 'Zapier', 'charitable' ),
					'slug' => 'zapier',
					'plan' => 'pro',
					'logo' => $base . 'zapier.png',
					'addon' => 'charitable-automation-connect',
				),
				array(
					'name' => __( 'Charitable Automation', 'charitable' ),
					'slug' => 'charitable-automation',
					'plan' => 'pro',
					'logo' => $addon_base . 'addon-icon-automation-connect.png',
					'addon' => 'charitable-automation-connect',
				),
				/*
				Brevo sits here rather than up with Mailchimp, by request. Both are
				charitable-newsletter-connect - the BRAND is what the reader recognises, so the name
				and the mark stay Brevo/Mailchimp and only the linking is the addon. The practical
				consequence is unchanged and worth restating: one addon behind two cards, so
				activating Newsletter Connect removes both at once. @since 1.8.15
				*/
				array(
					'name' => __( 'Brevo', 'charitable' ),
					'slug' => 'brevo',
					'plan' => 'plus',
					'logo' => $base . 'brevo.png',
					'addon' => 'charitable-newsletter-connect',
				),
				array(
					'name' => __( 'Elementor', 'charitable' ),
					'slug' => 'elementor',
					'plan' => 'lite',
					'logo' => $base . 'elementor.png',
					'plugin' => 'elementor/elementor.php',
				),
				array(
					'name' => __( 'WPVibe', 'charitable' ),
					'slug' => 'wpvibe',
					'plan' => 'lite',
					'logo' => $base . 'wpvibe.svg',
					/*
					vibe-ai, NOT wpvibe. The product is called WPVibe but it ships on wp.org under
					the slug `vibe-ai`, and its directory and main file are named for the slug -
					`vibe-ai/vibe-ai.php`. Confirmed against the wp.org API (a
					plugin_information request for `wpvibe` answers "Plugin not found") and against
					the installed copy on this dev site.

					One wrong value here caused three visible faults, which is worth spelling out
					because the same mistake is easy to repeat for any plugin whose brand name and
					wp.org slug differ:

					  1. file_exists() never matched, so WPVibe read as not installed even though
					     it was - and ACTIVE, meaning the card should have been dropped entirely.
					  2. It therefore offered "Install".
					  3. That install posted slug `wpvibe` to plugins_api(), which returned
					     "Plugin not found" - surfacing as the generic "Installation failed".

					So do NOT assume the wp.org slug equals the brand name. Derive both halves from
					the real basename, which is what the code below does. @since 1.8.15
					*/
					'plugin' => 'vibe-ai/vibe-ai.php',
				),
				/*
				Appended at the bottom by request, to fill the ragged last row once the actives
				drop off. Plan read off Charitable_Addons_Directory, not guessed:
				charitable-campaign-updates is licensed agency|pro, so PRO.

				Takes the megaphone from get_raise_more_icon() for the SAME addon rather than a
				new drawing - Raise More and this card can be on screen together, and one addon
				must not wear two different marks. There is no
				assets/images/addons/addon-icon-campaign-updates.png; the set has simple-updates
				but not this one. @since 1.8.15
				*/
				array(
					'name' => __( 'Campaign Updates', 'charitable' ),
					'slug' => 'campaign-updates',
					'plan' => 'pro',
					'addon' => 'charitable-campaign-updates',
					'glyph' => '<svg viewBox="0 0 20 20" width="34" height="34" fill="none" stroke="rgba(25,29,45,.5)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M3.6 8.6 14.8 4v12L3.6 11.4z"/><path d="M6.2 10.4v4.4a1.7 1.7 0 0 0 3.4 0v-3"/><path d="M17.2 8.2v3.6"/></svg>',
				),
				/*
				Charitable Events, launched 2026-09-24. Plan verified from
				Charitable_Addons_Directory like every other item here: license agency|pro, so
				PRO. Official ticket icon added to the tree from
				wpcharitable.com/wp-content/uploads/2026/09/events-icon-ticket.svg - canon ink
				#1e1515 with a red heart, left exactly as supplied.

				A NOTE ON REFRESHING THE ADDON CATALOG, because it cost a wrong conclusion here.
				The catalog is cached at TWO levels, and clearing the outer one does not refetch:

				  get_addons()
				    -> transient _charitable_addons             (processed list, DAY_IN_SECONDS)
				    -> get_addons_data_from_server()
				         -> transient _charitable_plugin_versions  (raw payload, DAY_IN_SECONDS)
				              -> wp_remote_post wpcharitable.com/edd-api/versions-v3/

				get_addons_data_from_server() returns early from _charitable_plugin_versions, so
				deleting only _charitable_addons re-PROCESSES stale data and never touches the
				network. Doing exactly that made Events look absent from the catalog - 38 slugs,
				no match - and produced a confident, wrong conclusion that its Install button
				could not work. Clearing BOTH returns 39 slugs including charitable-events with a
				live download_link. Delete both transients when checking for a new addon.
				@since 1.8.15
				*/
				array(
					'name' => __( 'Charitable Events', 'charitable' ),
					'slug' => 'events',
					'plan' => 'pro',
					'addon' => 'charitable-events',
					'logo' => $addon_base . 'addon-icon-events.svg',
				),
				/*
				Uncanny Automator sits last by request. Free plugin on wp.org, so its action is an
				install rather than an upgrade.
				*/
				array(
					'name' => __( 'Uncanny Automator', 'charitable' ),
					'slug' => 'uncanny-automator',
					'plan' => 'lite',
					'logo' => $base . 'uncanny-automator.png',
					'plugin' => 'uncanny-automator/uncanny-automator.php',
				),
			);

			/*
			TWO DESTINATIONS, chosen by whether this site already holds a licence.

			Pro sends every locked card to /lite-upgrade/. On Lite that is right for somebody who has
			bought nothing, and wrong for somebody who has: charitable_is_pro() is LICENCE-derived
			rather than edition-derived, so a Lite install can hold a perfectly valid licence, and
			these cards would then invite a paying customer to buy what they already own.

			For that reader the missing piece is the Pro plugin itself, not a purchase, so the card
			points at their account. Same reasoning and the same wording as the Donor Comments CTA and
			as Lite's existing links in views/donors/donors.php and the campaign builder, which already
			send licence-holders to /account/ with "Download Charitable Pro".

			Deliberately decided once here rather than per addon. Which addon a licence covers is a
			product question with no answer in the codebase - charitable_current_plan_meets_requirement()
			is the function that would answer it and Lite does not ship it - but "has a licence at all"
			is answerable, and it is the distinction that stops the worst version of the upsell.
			@since 1.8.15
			*/
			$charitable_has_licence = function_exists( 'charitable_is_pro' ) && charitable_is_pro();

			$upgrade_url   = $charitable_has_licence
				? 'https://wpcharitable.com/account/'
				: 'https://wpcharitable.com/lite-upgrade/';
			$upgrade_label = $charitable_has_licence
				? __( 'Download Charitable Pro', 'charitable' )
				: __( 'Upgrade', 'charitable' );

			if ( function_exists( 'charitable_utm_link' ) ) {
				$upgrade_url = charitable_utm_link( $upgrade_url, 'dashboard-integrations', 'upgrade' );
			}

			$addons_url = admin_url( 'admin.php?page=charitable-addons' );

			foreach ( $items as $i => $item ) {

				$plan_met = function_exists( 'charitable_current_plan_meets_requirement' )
					? charitable_current_plan_meets_requirement( $item['plan'] )
					: false;

				$installed = false;
				$active    = false;

				/*
				$is_free is resolved here rather than further down because the install detection below
				used to need it: a licence simulation suppressed the real install state for paid items
				while leaving free ones alone. That simulation is gone, but the flag is still read by the
				plan gate below, so it stays.
				*/
				$is_free = 'lite' === $item['plan'];

				if ( ! empty( $item['plugin'] ) ) {
					$installed = file_exists( WP_PLUGIN_DIR . '/' . $item['plugin'] );
					$active    = function_exists( 'is_plugin_active' ) && is_plugin_active( $item['plugin'] );
				} elseif ( ! empty( $item['addon'] ) ) {
					$addon     = $this->get_charitable_addon_data( $item['addon'] );
					$installed = is_array( $addon ) && ! empty( $addon['installed'] );
					$active    = is_array( $addon ) && ! empty( $addon['active'] );
				}

				/*
				A free plugin is never gated: Uncanny Automator, Elementor and WPVibe are on wp.org, so no
				Charitable licence can stand between a site and installing them. That is what 'lite' means
				on these items, and it is resolved above because the install detection needs it too.
				*/

				/*
				ALREADY RUNNING MEANS GONE, not "Installed".

				This card answers "does Charitable work with the tools I use", and for something
				already active the answer is settled - the card had nothing left to offer but a
				greyed-out word. Dropping it also frees the slot for a tool the reader could
				actually act on, which is why the list below it grew at the same time. Same rule
				render_raise_more_section() already applies to active addons.

				Consequence worth knowing: Mailchimp and Brevo are BOTH
				charitable-newsletter-connect, so activating that addon removes both cards even
				though neither provider is necessarily connected yet. The connection lives in the
				addon's own settings, which is where a half-configured provider belongs.
				@since 1.8.15
				*/
				/*
				The ladder itself lives in resolve_integration_action() so it can be table-tested
				without WordPress - see that method. It returns null to mean "drop this card",
				which is what an active integration gets, so the drop rule has ONE home rather
				than being asserted here and again in the tests.

				Everything below is turning its verdict into the attributes the markup and the
				shared AJAX handler need.

				A REAL action, not a link to a search screen. This was the card's worst bug: an
				installed-but-inactive plugin was labelled "Activate" and pointed at
				plugin-install.php?s=<name>, the install SEARCH screen - so the one thing the
				label promised was the one thing the click could not do. Elementor hit it exactly.

				The fix reuses the machinery the Enhance card has always had rather than adding an
				endpoint: charitable-dashboard-v2-enhance-grid-button is bound document-wide in
				charitable-admin-dashboard.js, and its handler dispatches on data-action.

				  third-party plugin  install  -> charitable_install_plugin,  data-slug = wp.org dir
				                      activate -> charitable_activate_plugin, data-basename
				  Charitable addon    install  -> install_addon,  data-slug = addon slug
				                      activate -> activate_addon, data-slug = addon slug
				*/
				$is_plugin = ! empty( $item['plugin'] );

				/*
				Preview override for the integrations grid, paired with ?charitable_preview_addon on
				Raise More and gated the same way.

				This ladder turns on different inputs - plan met, free or paid, on disk, active - so
				it is forced by overriding those rather than the resolved action, which keeps
				resolve_integration_action() the single source of the decision and means the preview
				cannot show a combination the real ladder would never produce:

				  ?charitable_preview_integration=upgrade    Upgrade / Download Charitable Pro
				  ?charitable_preview_integration=install    Install
				  ?charitable_preview_integration=activate   Activate
				  ?charitable_preview_integration=installed  card removed (active items drop out)

				Note 'upgrade' is already every paid card's state on Lite, since
				charitable_current_plan_meets_requirement() does not exist here - the override is
				what lets the other three be seen at all.

				THIS IS A QA SEAM AND MUST NOT SHIP. Strip with the Raise More one; both are single
				contiguous blocks with one $_GET read each.
				@since 1.8.15
				*/
				$charitable_forced_integration = isset( $_GET['charitable_preview_integration'] ) && current_user_can( 'manage_options' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					? sanitize_key( wp_unslash( $_GET['charitable_preview_integration'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					: '';

				if ( 'upgrade' === $charitable_forced_integration ) {
					$plan_met = false;
					$is_free  = false;
					$active   = false;
				} elseif ( 'install' === $charitable_forced_integration ) {
					$plan_met  = true;
					$installed = false;
					$active    = false;
				} elseif ( 'activate' === $charitable_forced_integration ) {
					$plan_met  = true;
					$installed = true;
					$active    = false;
				} elseif ( 'installed' === $charitable_forced_integration ) {
					$active = true;
				}

				$verdict   = self::resolve_integration_action( $plan_met, $is_free, $installed, $active, $is_plugin );

				if ( null === $verdict ) {
					unset( $items[ $i ] );
					continue;
				}

				/*
				The upgrade label is the one decided above rather than a fixed "Upgrade", so a site
				that already holds a licence is told to download the Pro plugin instead of being sold
				it again. Install and Activate are unchanged - those are the two free wp.org cards,
				where no licence question arises. @since 1.8.15
				*/
				$labels = array(
					'install'  => __( 'Install', 'charitable' ),
					'activate' => __( 'Activate', 'charitable' ),
					'upgrade'  => $upgrade_label,
				);

				$items[ $i ]['action'] = $verdict['action'];
				$items[ $i ]['label']  = $labels[ $verdict['label'] ];

				if ( 'upgrade' === $verdict['action'] ) {
					$items[ $i ]['url'] = $upgrade_url;
				} else {
					$items[ $i ]['url']       = '';
					$items[ $i ]['ajax']      = $verdict['ajax'];
					$items[ $i ]['type']      = $verdict['type'];
					/*
					dirname() rather than a second hand-kept key, because the wp.org slug is the
					directory half of the basename - the DIRECTORY name, which is not necessarily
					the brand name. WPVibe proved that: it ships as `vibe-ai/vibe-ai.php`, so this
					yields `vibe-ai` and the install works, where a key spelled from the product
					name would not. Deriving both the detection path and the install slug from one
					value is what keeps them from disagreeing.
					*/
					if ( $is_plugin ) {
						$items[ $i ]['data_slug'] = dirname( $item['plugin'] );
						$items[ $i ]['basename']  = $item['plugin'];
					} else {
						$items[ $i ]['data_slug'] = $item['addon'];
					}
				}

				// FREE rather than LITE on the badge: it describes the price, which is the question.
				$items[ $i ]['badge'] = $is_free
					? __( 'Free', 'charitable' )
					: $item['plan'];
			}

			/*
			Reindex. The active-item unset() above leaves gaps, and the renderer's foreach does not
			care - but array_values() keeps the returned list a plain 0..n array so anything that
			later counts or slices it behaves.
			*/
			return array_values( $items );
		}


		/**
		 * Render the integrations card. v3 only.
		 *
		 * A wall of logo cards rather than a feature list: the question this answers is "does Charitable
		 * work with the tools I already use", and a recognised mark answers that faster than any
		 * sentence. Card layout, tier badge and action link all follow WPForms' integrations block.
		 *
		 * The eight shown are a curated sample, not a complete list - hence the footer copy. The real
		 * supported set, confirmed from the installed addons rather than a marketing page, is roughly:
		 * 13 payment gateways (Stripe and its ACH/BECS/SEPA/Cash App variants, PayPal, Square,
		 * Authorize.Net, Braintree, GoCardless, Mollie, Payrexx, Paystack, PayFast, PayUMoney,
		 * Razorpay, Windcave), 6 email/CRM providers from charitable-newsletter-connect/providers
		 * (Mailchimp, ActiveCampaign, Campaign Monitor, MailerLite, MailPoet, Mailster), 3 automation
		 * routes (Zapier, Make.com, webhooks), 3 analytics, and 4 page builders.
		 *
		 * Logos live in assets/images/integrations/logos/ - copied in rather than referenced from
		 * other plugins, so the card does not break when Elementor is deactivated. Webhooks has no
		 * brand mark and takes a glyph, which is correct rather than a gap.
		 *
		 * @since 1.8.15
		 *
		 * @return void
		 */
		public function render_integrations_section() {

			$items = $this->get_integrations_items();

			if ( empty( $items ) ) {
				return;
			}

			$all_url = 'https://wpcharitable.com/integrations/';

			if ( function_exists( 'charitable_utm_link' ) ) {
				$all_url = charitable_utm_link( $all_url, 'dashboard-integrations', 'view-all' );
			}
			?>
			<section id="charitable-dashboard-v2-integrations" class="charitable-dashboard-v2-section charitable-dashboard-v2-collapsible">
				<header class="charitable-dashboard-v2-section-header">
					<h3><?php esc_html_e( 'Works With Your Tools', 'charitable' ); ?></h3>
					<?php $this->render_collapse_toggle(); ?>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<ul class="charitable-integrations-grid">
						<?php foreach ( $items as $item ) : ?>
							<li class="charitable-integration charitable-integration--<?php echo esc_attr( $item['action'] ); ?>">
								<span class="charitable-integration-tier charitable-integration-tier--<?php echo esc_attr( $item['plan'] ); ?>"><?php echo esc_html( $item['badge'] ); ?></span>
								<span class="charitable-integration-logo">
									<?php if ( ! empty( $item['logo'] ) ) : ?>
										<img src="<?php echo esc_url( $item['logo'] ); ?>" alt="" width="40" height="40" loading="lazy" />
									<?php else : ?>
										<?php echo $item['glyph']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php endif; ?>
								</span>
								<span class="charitable-integration-name"><?php echo esc_html( $item['name'] ); ?></span>
								<?php
								/*
								Two kinds of action, two elements.

								UPGRADE leaves the site, so it stays an anchor with a real href.
								data-charitable-upgrade marks it for the JS that shows the "thanks for
								your interest" modal when somebody comes back - see
								charitable-admin-dashboard.js. Only upgrades carry it: an install stays
								on this site, so there is no return to detect.

								INSTALL and ACTIVATE happen in place over AJAX, so they are <button>
								elements, not anchors. A button is the honest element for something that
								is not a destination, and - the practical reason - the shared handler
								calls .prop('disabled', true) to stop a double submit while the request
								is in flight, which does nothing on an <a>. §19 of the v3 stylesheet
								strips the button chrome so it still reads as the same blue link.

								There is no "Installed" branch any more: an active item never reaches
								the renderer. See get_integrations_items(). @since 1.8.15
								*/
								?>
								<?php if ( 'upgrade' === $item['action'] ) : ?>
									<a href="<?php echo esc_url( $item['url'] ); ?>" class="charitable-integration-action" target="_blank" rel="noopener noreferrer" data-charitable-upgrade="1"><?php echo esc_html( $item['label'] ); ?></a>
								<?php else : ?>
									<button type="button"
										class="charitable-integration-action charitable-integration-action--button charitable-dashboard-v2-enhance-grid-button"
										data-action="<?php echo esc_attr( $item['ajax'] ); ?>"
										data-slug="<?php echo esc_attr( $item['data_slug'] ); ?>"
										data-type="<?php echo esc_attr( $item['type'] ); ?>"
										<?php if ( ! empty( $item['basename'] ) ) : ?>
											data-basename="<?php echo esc_attr( $item['basename'] ); ?>"
										<?php endif; ?>
									><?php echo esc_html( $item['label'] ); ?></button>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<footer class="charitable-integrations-footer">
						<p class="charitable-integrations-note">
							<?php
							/*
							"Automation Connect Addon" rather than "Zapier": the Zapier
							connection is what our addon provides, so naming the third-party
							service sent the reader looking for a Zapier integration that does
							not exist under that name.

							Uncanny Automator and Make were named here too and have been dropped:
							one route out to thousands of apps is the point, and listing three
							third-party brands to carry our own capability was working against
							itself. Uncanny Automator still has a card in the grid above; Make
							never did, which is part of why naming it here read oddly.

							esc_html__ then printf is the pattern this codebase already uses for
							a sentence carrying a link (see views/reports/overview.php): the
							translated string holds no markup and is escaped, and the anchor is
							substituted in afterwards.
							*/
							printf(
								/* translators: %1$s: opening link tag. %2$s: closing link tag. */
								esc_html__( '30+ native integrations, plus thousands more via %1$sAutomation Connect Addon%2$s.', 'charitable' ),
								'<a href="' . esc_url( charitable_ga_url( 'https://www.wpcharitable.com/extensions/charitable-automation-connect/', 'dashboard-integrations', 'Automation Connect Addon' ) ) . '" target="_blank" rel="noopener noreferrer">',
								'</a>'
							);
							?>
						</p>
						<a href="<?php echo esc_url( $all_url ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-integrations-button"><?php esc_html_e( 'View All Integrations', 'charitable' ); ?></a>
					</footer>
				</div>
			</section>
			<?php
		}



		/**
		 * Signals the "Raise More With Charitable" rules are evaluated against.
		 *
		 * Gathered once per request. Every value here is something the dashboard can know for
		 * certain about this install - nothing is inferred from behaviour we cannot see.
		 *
		 * The country entry is the one that needs care. charitable_get_option( 'country' ) returns
		 * 'US' for a site that has NEVER opened the settings page, because the field declares
		 * 'default' => 'US' (see Charitable_General_Settings). Reading the defaulted getter would
		 * therefore make every untouched install look like a US nonprofit and hand it US-specific
		 * recommendations. So this reads the raw option and reports '' unless the key is actually
		 * present - country rules must treat "unknown" as "no country rule applies", never as US.
		 *
		 * @since 1.8.15
		 *
		 * @return array
		 */
		private function get_raise_more_signals() {

			static $memo = null;

			if ( null !== $memo ) {
				return $memo;
			}

			$raw     = get_option( 'charitable_settings', array() );
			$country = ( is_array( $raw ) && array_key_exists( 'country', $raw ) && ! empty( $raw['country'] ) )
				? strtoupper( (string) $raw['country'] )
				: '';

			$campaign_counts = wp_count_posts( 'campaign' );
			$donation_counts = wp_count_posts( 'donation' );

			$gateways = array();

			if ( function_exists( 'charitable_get_helper' ) ) {
				$helper = charitable_get_helper( 'gateways' );

				if ( is_object( $helper ) && method_exists( $helper, 'get_active_gateways' ) ) {
					$gateways = array_keys( (array) $helper->get_active_gateways() );
				}
			}

			$memo = array(
				'country'   => $country,
				'currency'  => function_exists( 'charitable_get_currency' ) ? charitable_get_currency() : 'USD',
				'campaigns' => isset( $campaign_counts->publish ) ? (int) $campaign_counts->publish : 0,
				'donations' => isset( $donation_counts->{'charitable-completed'} ) ? (int) $donation_counts->{'charitable-completed'} : 0,
				'gateways'  => $gateways,
				'recurring' => class_exists( 'Charitable_Recurring' ),
			);

			return $memo;
		}



		/**
		 * The recommendation rules, highest weight first.
		 *
		 * Each rule is an addon slug, a weight, a trigger closure evaluated against the signals,
		 * and an optional pitch that replaces the addon's generic catalogue description with
		 * something specific to why it is being shown. The pitch is where the value actually is:
		 * "Turn on recurring giving" is a feature, "monthly donors give more over time" is a
		 * reason, and a nonprofit is deciding between the two on fundraising grounds.
		 *
		 * Ordering intent: the top of this list is money left on the table (recurring giving,
		 * Gift Aid's 25%, a gateway donors in their country actually recognise), the middle is
		 * donor-experience and compliance (receipts, fee relief), and the tail is reach and
		 * polish. A rule only competes if its trigger fires, so a site with recurring already
		 * active never sees it and the next rule moves up.
		 *
		 * Slugs are the DIRECTORY slugs from the _charitable_addons transient - note
		 * charitable-recurring-donations, not charitable-recurring. get_charitable_addon_data()
		 * owns the mapping to the plugin folder name.
		 *
		 * @since 1.8.15
		 *
		 * @return array
		 */
		private function get_raise_more_rules() {

			// Country to regional gateway. Each of these is the payment method donors in that
			// country expect to see; offering only a card form measurably costs conversions.
			$gateway_by_country = array(
				'IN' => 'charitable-razorpay',
				'NG' => 'charitable-paystack',
				'GH' => 'charitable-paystack',
				'ZA' => 'charitable-payfast',
				'NL' => 'charitable-mollie',
				'BE' => 'charitable-mollie',
				'CH' => 'charitable-payrexx',
				'NZ' => 'charitable-windcave',
				'AU' => 'charitable-windcave',
				'GB' => 'charitable-gocardless',
				'IE' => 'charitable-gocardless',
			);

			return array(
				array(
					'slug'    => 'charitable-recurring-donations',
					'weight'  => 100,
					'pitch'   => __( 'A monthly donor is worth many times a one-off gift over the same year. Let supporters choose a schedule at the point they are already giving.', 'charitable' ),
					'trigger' => function ( $s ) {
						return empty( $s['recurring'] );
					},
				),
				array(
					'slug'    => 'charitable-gift-aid',
					'weight'  => 95,
					'pitch'   => __( 'Claim an extra 25% from HMRC on every eligible UK donation. Donors just tick a box, and you ask for nothing more.', 'charitable' ),
					'trigger' => function ( $s ) {
						return 'GB' === $s['country'];
					},
				),
				array(
					'slug'    => 'charitable-annual-receipts',
					'weight'  => 88,
					'pitch'   => __( 'Send every donor a single year-end tax receipt automatically, instead of exporting spreadsheets each January.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['donations'] >= 10;
					},
				),
				array(
					'slug'    => 'charitable-fee-relief',
					'weight'  => 80,
					'pitch'   => __( 'Ask donors to cover the processing fee. Many say yes, and your campaign keeps the full gift.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['donations'] >= 5;
					},
				),
				array(
					'slug'    => 'charitable-pdf-receipts',
					'weight'  => 76,
					'pitch'   => __( 'Attach a proper PDF receipt to every confirmation email, so donors have what they need for their records.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['donations'] >= 1;
					},
				),
				array(
					'slug'    => 'charitable-donation-recovery',
					'weight'  => 72,
					'pitch'   => __( 'Some supporters start a donation and never finish it. Follow up automatically and win a share of them back.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['donations'] >= 20;
					},
				),
				array(
					'slug'    => 'charitable-multi-currency',
					'weight'  => 68,
					'pitch'   => __( 'Show amounts in your supporters\' own currency, so an international donor never has to do the conversion themselves.', 'charitable' ),
					'trigger' => function ( $s ) {
						return 'USD' !== $s['currency'] || ( '' !== $s['country'] && 'US' !== $s['country'] );
					},
				),
				array(
					'slug'    => 'charitable-donortrust',
					'weight'  => 60,
					'pitch'   => __( 'Show real donations as they arrive. Seeing that other people have given is one of the strongest nudges there is.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['campaigns'] >= 1;
					},
				),
				array(
					'slug'    => 'charitable-tributes',
					'weight'  => 56,
					'pitch'   => __( 'Let donors give in memory or in honour of someone, with a dedicated page families can share.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['campaigns'] >= 1;
					},
				),
				array(
					'slug'    => 'charitable-ambassadors',
					'weight'  => 52,
					'pitch'   => __( 'Let your supporters fundraise on your behalf, each with their own page and their own network to ask.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['campaigns'] >= 3;
					},
				),
				array(
					'slug'    => 'charitable-newsletter-connect',
					'weight'  => 48,
					'pitch'   => __( 'Send new donors straight to your mailing list, with their consent, so the relationship does not end at the receipt.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['donations'] >= 5;
					},
				),
				array(
					'slug'    => 'charitable-campaign-updates',
					'weight'  => 44,
					'pitch'   => __( 'Post progress updates on a campaign so donors can see what their money actually did.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['campaigns'] >= 1;
					},
				),
				array(
					'slug'    => 'charitable-spam-blocker',
					'weight'  => 40,
					'pitch'   => __( 'Keep card-testing and junk submissions out of your donation records before they reach your reports.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['donations'] >= 1;
					},
				),
				array(
					'slug'    => 'charitable-videos',
					'weight'  => 34,
					'pitch'   => __( 'Put a video at the top of a campaign. Showing the work is more persuasive than describing it.', 'charitable' ),
					'trigger' => function ( $s ) {
						return $s['campaigns'] >= 1;
					},
				),
				array(
					'slug'    => 'charitable-anonymous-donations',
					'weight'  => 28,
					'pitch'   => __( 'Give supporters the option to keep their name off the public list, without hiding from you.', 'charitable' ),
					'trigger' => '__return_true',
				),
				// Regional gateway. Resolved from the country map, so exactly one of these can ever
				// fire and only when a country has actually been chosen in settings.
				array(
					'slug'    => $gateway_by_country,
					'weight'  => 92,
					'pitch'   => __( 'Accept donations the way supporters in your country already expect to pay, rather than card-only.', 'charitable' ),
					'trigger' => function ( $s ) use ( $gateway_by_country ) {
						return '' !== $s['country'] && isset( $gateway_by_country[ $s['country'] ] );
					},
				),
			);
		}



		/**
		 * Resolve the rules into renderable items: trigger, then state, then rank, then cap.
		 *
		 * The four states come straight from the existing get_button_state() / format_addon_item()
		 * pair, so the buttons carry the same data attributes as the Enhance grid and are driven
		 * by the same working install/activate AJAX - no new endpoints. The one behaviour added on
		 * top is that an ACTIVE addon is dropped entirely rather than rendered as a disabled
		 * "Installed" chip: this card is a list of things worth doing next, and something already
		 * running is not one of them.
		 *
		 * @since 1.8.15
		 *
		 * @param  int $limit Maximum items to return.
		 * @return array
		 */
		private function get_raise_more_items( $limit = 6 ) {

			$signals = $this->get_raise_more_signals();
			$items   = array();

			foreach ( $this->get_raise_more_rules() as $rule ) {

				$trigger = $rule['trigger'];

				if ( '__return_true' !== $trigger && ( ! is_callable( $trigger ) || ! $trigger( $signals ) ) ) {
					continue;
				}

				// The regional-gateway rule carries a country map rather than a single slug.
				$slug = is_array( $rule['slug'] )
					? ( isset( $rule['slug'][ $signals['country'] ] ) ? $rule['slug'][ $signals['country'] ] : '' )
					: $rule['slug'];

				if ( '' === $slug ) {
					continue;
				}

				/*
				Suppressed by design call rather than by signal: each of these does trigger on a site
				like this one, and is being held back from the card anyway. Kept as a list here instead
				of deleting the three rules, so their weights, triggers and pitch copy survive and
				putting one back is a one-line edit. @since 1.8.15
				*/
				$suppressed = array(
					'charitable-fee-relief',
					'charitable-pdf-receipts',
					'charitable-tributes',
				);

				if ( in_array( $slug, $suppressed, true ) ) {
					continue;
				}

				$addon_data = $this->get_charitable_addon_data( $slug );

				if ( ! $addon_data ) {
					continue;
				}

				/*
				Preview override, same shape and the same manage_options gate as
				?charitable_preview_empty above.

				The button a card shows is derived from three conditions that cannot be varied from
				the browser - whether <slug>/<slug>.php exists, whether that plugin is active, and
				whether the licence covers the addon - so seeing a state otherwise means creating
				it. This forces the resolved action instead:

				  ?charitable_preview_addon=upgrade     Upgrade
				  ?charitable_preview_addon=install     Install & Activate   (+ "Included In Your Plan")
				  ?charitable_preview_addon=activate    Activate             (+ "Included In Your Plan")
				  ?charitable_preview_addon=deactivate  Installed            (disabled)

				Applies to every card in the section at once, which is the point: it shows the state
				in situ, next to its neighbours, where a single forced card would not reveal
				alignment or wrapping problems.

				'deactivate' is the one state that cannot occur on load at all - the skip below
				removes an active addon from the recommendations entirely, and the real route to
				"Installed" is the AJAX response after a successful activate. Forcing the action
				without forcing 'active' renders that button for inspection while leaving the skip
				intact.

				THIS IS A QA SEAM AND MUST NOT SHIP. Strip it before release; it is deliberately
				written as one contiguous block with a single $_GET read so it comes out cleanly.
				@since 1.8.15
				*/
				$charitable_forced_state = isset( $_GET['charitable_preview_addon'] ) && current_user_can( 'manage_options' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					? sanitize_key( wp_unslash( $_GET['charitable_preview_addon'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					: '';

				if ( in_array( $charitable_forced_state, array( 'upgrade', 'install', 'activate', 'deactivate' ), true ) ) {
					$addon_data['action'] = $charitable_forced_state;
				}

				// Already running: nothing to recommend.
				if ( ! empty( $addon_data['active'] ) ) {
					continue;
				}

				$item = $this->format_addon_item( $addon_data );

				if ( ! empty( $rule['pitch'] ) ) {
					$item['description'] = $rule['pitch'];
				}

				// Name the tier instead of a bare "Upgrade", so the cost of saying yes is visible
				// before the click rather than after it.
				if ( 'upgrade' === $item['button_action'] && function_exists( 'charitable_get_addon_minimum_plan' ) ) {
					$plan = charitable_get_addon_minimum_plan( isset( $addon_data['license'] ) ? $addon_data['license'] : array() );

					if ( $plan && 'lite' !== $plan ) {
						$item['button_text'] = sprintf(
							/* translators: %s: plan name, e.g. "Plus" or "Pro" */
							__( 'Upgrade to %s', 'charitable' ),
							ucfirst( $plan )
						);
					}
				}

				/*
				Drop charitable-dashboard-v2-upgrade-button. An inline <style> block prints

				    .charitable-dashboard-v2-upgrade-button {
				        background-color: #e38632 !important; color: #fff !important; border-color: #e38632 !important;
				    }

				unscoped and with !important on every declaration, so anything wearing that class
				is painted orange wherever it appears. On this card the upgrade action is meant to
				be the QUIET one - orange is reserved for Activate, which is the action we want
				taken - and the only way to say so without starting an !important war is to not
				wear the class. The data-action attribute is untouched, so the click behaviour is
				identical; only the paint changes. @since 1.8.15
				*/
				$item['button_class'] = trim( str_replace( 'charitable-dashboard-v2-upgrade-button', '', $item['button_class'] ) );

				// The high-value state: paid for already, just not switched on.
				$item['included'] = ( 'install_addon' === $item['button_action'] || 'activate_addon' === $item['button_action'] );
				$item['weight']   = (int) $rule['weight'];

				// Uniform line icon in place of the catalogue PNG - see get_raise_more_icon().
				$item['icon']     = $this->get_raise_more_icon( $slug );

				$items[ $slug ] = $item;
			}

			// Stable sort on weight; array_multisort would lose the slug keys.
			uasort(
				$items,
				function ( $a, $b ) {
					return $b['weight'] <=> $a['weight'];
				}
			);

			return array_slice( $items, 0, $limit );
		}



		/**
		 * Get enhance items data.
		 *
		 * @since 1.8.15
		 * @return array
		 */
		/**
		 * Single-colour line icon for a Raise More card.
		 *
		 * The card previously used get_charitable_addon_icon(), which returns the addon catalogue's
		 * PNGs. Those are a mixed set - some are real product logos in full colour, some are flat
		 * marks, and the ones with no file at all fell back to a generic puzzle piece - so six cards
		 * side by side looked like six unrelated things. These are one stroke weight, one size, and
		 * currentColor, so the grid reads as a set and the icons can be recoloured by CSS.
		 *
		 * Each glyph depicts what the addon DOES rather than being an abstract mark: a repeat arrow
		 * for recurring giving, a receipt for receipts, a heart-hand for tributes, and so on. The
		 * default is a plug, used for the payment gateways, whose real identity is a third-party logo
		 * that has no business being redrawn as a line icon.
		 *
		 * @since 1.8.15
		 *
		 * @param  string $slug Addon slug.
		 * @return string Inline SVG.
		 */
		private function get_raise_more_icon( $slug ) {

			$p = array(

				// Circular arrows: giving that repeats.
				'charitable-recurring-donations' => '<path d="M3.2 10a6.8 6.8 0 0 1 11.6-4.8M16.8 10A6.8 6.8 0 0 1 5.2 14.8"/><path d="M14.8 2.4v3.2h-3.2M5.2 17.6v-3.2h3.2"/>',

				// Coin with an upward arrow: reclaiming a percentage.
				'charitable-gift-aid' => '<circle cx="10" cy="11.5" r="6.2"/><path d="M10 8.6v5.8M7.8 10.8 10 8.6l2.2 2.2"/><path d="M4.6 4.2h10.8"/>',

				// Receipt with a calendar notch: the yearly summary.
				'charitable-annual-receipts' => '<path d="M4.8 2.6h10.4v14.8l-2.6-1.6-2.6 1.6-2.6-1.6-2.6 1.6z"/><path d="M7.4 6.6h5.2M7.4 9.8h5.2M7.4 13h3"/>',

				// Receipt with a page fold: a document per donation.
				'charitable-pdf-receipts' => '<path d="M5.2 2.6h6l4 4v10.8H5.2z"/><path d="M11 2.6v4.2h4.2"/><path d="M7.6 11h4.8M7.6 14h3.2"/>',

				// Hand under a coin: the donor covering the cost.
				'charitable-fee-relief' => '<circle cx="10" cy="6.4" r="3.2"/><path d="M3.4 17.4c0-2.6 2.1-4.6 4.7-4.6h3.8c2.6 0 4.7 2 4.7 4.6"/><path d="M10 5.1v2.6"/>',

				// Arrow curving back: bringing something back.
				'charitable-donation-recovery' => '<path d="M3.4 9.6a6.6 6.6 0 1 1 2.4 5.6"/><path d="M3.2 5.8v3.9h3.9"/><path d="M10 7.2v3.4l2.4 1.4"/>',

				// Two overlapping coins: more than one currency.
				'charitable-multi-currency' => '<circle cx="7.6" cy="7.6" r="4.6"/><path d="M10.6 16.2a4.6 4.6 0 0 0 2.2-8.6"/><path d="M12.4 12.4a4.6 4.6 0 0 1-4.6 4.6"/>',

				// Shield with a tick: verified giving.
				'charitable-donortrust' => '<path d="M10 2.6 16 4.8v5c0 4-2.5 6.6-6 7.6-3.5-1-6-3.6-6-7.6v-5z"/><path d="M7.4 9.8 9.5 12l3.3-3.6"/>',

				// Hand holding a heart: giving in someone's name.
				'charitable-tributes' => '<path d="M10 16.4S4.2 13 4.2 8.8a3.1 3.1 0 0 1 5.8-1.6 3.1 3.1 0 0 1 5.8 1.6c0 4.2-5.8 7.6-5.8 7.6z"/>',

				// Network of people: supporters fundraising for you.
				'charitable-ambassadors' => '<circle cx="10" cy="4.6" r="2.4"/><circle cx="4.6" cy="14.4" r="2.4"/><circle cx="15.4" cy="14.4" r="2.4"/><path d="M8.4 6.5 6 12.1M11.6 6.5 14 12.1M7 14.4h6"/>',

				// Envelope with an arrow: donors moving to a list.
				'charitable-newsletter-connect' => '<path d="M3.2 5.8h13.6v8.4H3.2z"/><path d="m3.2 6.2 6.8 4.6 6.8-4.6"/>',

				// Megaphone: telling donors what happened.
				'charitable-campaign-updates' => '<path d="M3.6 8.6 14.8 4v12L3.6 11.4z"/><path d="M6.2 10.4v4.4a1.7 1.7 0 0 0 3.4 0v-3"/><path d="M17.2 8.2v3.6"/>',

				// Shield with a slash: keeping things out.
				'charitable-spam-blocker' => '<path d="M10 2.6 16 4.8v5c0 4-2.5 6.6-6 7.6-3.5-1-6-3.6-6-7.6v-5z"/><path d="M7.8 7.8l4.4 4.4M12.2 7.8l-4.4 4.4"/>',

				// Play button in a frame.
				'charitable-videos' => '<path d="M2.8 4.6h14.4v10.8H2.8z"/><path d="M8.4 7.6 12.8 10l-4.4 2.4z"/>',

				// Person with a dashed outline: present but unnamed.
				'charitable-anonymous-donations' => '<circle cx="10" cy="6.4" r="3.2" stroke-dasharray="2.4 1.8"/><path d="M3.8 17.2c0-2.7 2.8-4.8 6.2-4.8s6.2 2.1 6.2 4.8" stroke-dasharray="2.4 1.8"/>',
			);

			// Gateways and anything unmapped: a plug. Deliberately generic - a payment brand's mark is
			// its logo, and approximating one as a line icon would be both wrong and worse-looking.
			$path = isset( $p[ $slug ] ) ? $p[ $slug ] : '<path d="M6.6 3.2v4M13.4 3.2v4"/><path d="M4.4 7.2h11.2v3.2a5.6 5.6 0 0 1-11.2 0z"/><path d="M10 16v2.8"/>';

			return '<svg viewBox="0 0 20 20" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">' . $path . '</svg>';
		}



		/**
		 * Render the "Raise More With Charitable" card. v3 only.
		 *
		 * Charitable's own fundraising addons, ranked against this install. Its counterpart in the
		 * right rail (Enhance Your Campaigns) carries the Awesome Motive and partner plugins, so
		 * the two never show the same thing: site-level tools there, fundraising capability here.
		 *
		 * @since 1.8.15
		 *
		 * @return void
		 */
		public function render_raise_more_section() {

			/*
			One row of three, not two rows of six. The grid is 3-across, so the cap IS the row count -
			and three is enough to read as a set of options without the card competing with the tables
			above it for height. Raising this to 6 is the only change needed to get the second row back;
			the grid, the dividing rules and the last-row border logic all key off the item count.

			Works with the suppression list in get_raise_more_items(): with those three rules held back
			and a cap of three, the row is the next three the ranking actually returns rather than a
			backfill of whatever the suppression displaced. @since 1.8.15
			*/
			$items = $this->get_raise_more_items( 3 );

			if ( empty( $items ) ) {
				return;
			}

			$addons_url = admin_url( 'admin.php?page=charitable-addons' );
			?>
			<section id="charitable-dashboard-v2-raise-more" class="charitable-dashboard-v2-section charitable-dashboard-v2-collapsible">
				<header class="charitable-dashboard-v2-section-header">
					<h3><?php esc_html_e( 'Raise More With Charitable', 'charitable' ); ?></h3>
					<?php $this->render_collapse_toggle(); ?>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<div class="charitable-raise-more-grid">
						<?php foreach ( $items as $item ) : ?>
							<?php
							$attrs = 'data-action="' . esc_attr( $item['button_action'] ) . '"'
								. ' data-slug="' . esc_attr( $item['slug'] ) . '"'
								. ' data-type="' . esc_attr( $item['type'] ) . '"';

							if ( isset( $item['basename'] ) ) {
								$attrs .= ' data-basename="' . esc_attr( $item['basename'] ) . '"';
							}

							if ( isset( $item['upgrade_url'] ) ) {
								$attrs .= ' data-upgrade-url="' . esc_url( $item['upgrade_url'] ) . '"';
							}

							if ( ! empty( $item['button_disabled'] ) ) {
								$attrs .= ' disabled';
							}
							?>
							<div class="charitable-raise-more-item">
								<div class="charitable-raise-more-head">
									<span class="charitable-raise-more-icon"><?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<h4 class="charitable-raise-more-title"><?php echo esc_html( $item['title'] ); ?></h4>
								</div>
								<p class="charitable-raise-more-desc"><?php echo esc_html( $item['description'] ); ?></p>
								<div class="charitable-raise-more-foot">
									<button class="charitable-dashboard-v2-enhance-grid-button charitable-raise-more-button <?php echo esc_attr( $item['button_class'] ); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $item['button_text'] ); ?></button>
									<?php if ( ! empty( $item['included'] ) ) : ?>
										<span class="charitable-raise-more-included"><?php esc_html_e( 'Included In Your Plan', 'charitable' ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<?php
					/*
					The footer reuses Enhance Your Campaigns' OWN classes rather than a parallel set, so
					it inherits that card's rules - 70px tall, white, centred, 1px top border - instead of
					re-declaring them and drifting. The link markup was already identical; the wrapper was
					the only difference (18px/30px padding and a 54px height against Enhance's 70px).
					@since 1.8.15
					*/
					?>
					<footer class="charitable-dashboard-v2-enhance-campaign-footer">
						<div class="charitable-dashboard-v2-enhance-campaign-footer-content">
							<div class="charitable-dashboard-v2-enhance-campaign-footer-left">
								<a href="<?php echo esc_url( $addons_url ); ?>" class="charitable-dashboard-v2-addons-link">
									<span class="charitable-dashboard-v2-addons-text"><?php esc_html_e( 'View All Addons', 'charitable' ); ?></span>
									<svg class="charitable-dashboard-v2-arrow-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</div>
						</div>
					</footer>
				</div>
			</section>
			<?php
		}

		/* --- Site Analysis tab, tab registry, latest-updates v3 and top-donors v3,
		     ported from Charitable Pro 1.8.19.5. --- */



	/**
	 * Score to show on the dashboard card, or null to render the pre-run promo state.
	 *
	 * Reads Charitable_Site_Analysis::get_last_known_score(), the durable snapshot, and NOT the
	 * `charitable_analysis_cache` transient. The transient is the wrong store for a dashboard:
	 *
	 *   - it expires after Charitable_Site_Analysis::CACHE_TTL, 7 days;
	 *   - Charitable_Site_Analysis::flush_cache() deletes it on FIVE hooks, including
	 *     `save_post_campaign` and `update_option_charitable_settings`.
	 *
	 * Both are correct for a cache whose job is to avoid showing a stale *report*, and fatal here: on
	 * a site where someone is actually editing campaigns the score would appear and then vanish
	 * minutes later, with nothing to explain it. The snapshot survives both, so the card keeps showing
	 * the last real score, and the "Last analyzed N ago" line is what keeps a stale number honest.
	 *
	 * @since 1.8.15
	 *
	 * @return array|null
	 */
	private function get_site_analysis_dashboard_score() {
		/*
		TWO guards, and the second one has earned its keep.

		get_last_known_score() now exists - it and LAST_SCORE_OPTION were written in 1.8.19 - but the
		method_exists() check stays, because the class_exists() check alone once sailed past a
		half-present class straight into a fatal that killed the whole dashboard render: "Call to
		undefined method Charitable_Site_Analysis::get_last_known_score()". That happened when the
		class file shipped without the method, and the same shape recurs whenever the two halves can
		travel separately - a Lite build, a partial sync, a version skew between Pro and an addon.

		Returning null is the documented path, not a fallback bolted on to hide a gap. Every caller
		handles it: get_site_analysis_tab_score() returns null too, and the tab shows the "New"
		pill rather than a number, which is what a site that has never run an analysis should
		show. A missing sibling method should cost the score, not the page. @since 1.8.15
		*/
		if ( ! class_exists( 'Charitable_Site_Analysis' ) ) {
			return null;
		}

		if ( ! method_exists( 'Charitable_Site_Analysis', 'get_last_known_score' ) ) {
			return null;
		}

		// The durable snapshot, NOT the cache: the cache expires after 7 days and is deleted by
		// flush_cache() on five hooks, two of which fire when somebody saves a campaign or any
		// Charitable setting. See Charitable_Site_Analysis::LAST_SCORE_OPTION.
		$score = Charitable_Site_Analysis::get_instance()->get_last_known_score();

		if ( ! is_array( $score ) || empty( $score['dimensions'] ) ) {
			return null;
		}

		$labels = array(
			'activation'   => __( 'Activation', 'charitable' ),
			'health'       => __( 'Health', 'charitable' ),
			'growth'       => __( 'Growth', 'charitable' ),
			'optimization' => __( 'Optimization', 'charitable' ),
		);

		$fix_first   = isset( $score['fix_first'] ) ? (int) $score['fix_first'] : 0;
		$analyzed_at = isset( $score['time'] ) ? (int) $score['time'] : 0;

		/*
		The one line of text the scored card carries: how old the score is, and how much is still
		outstanding. Built as a single translatable sentence per case rather than two concatenated
		fragments, so a translator can reorder the parts - hence the numbered placeholders and the
		_n() on the whole line rather than on the count alone. The separator is the middot the tab's
		own JS already uses for "Estimated · 2 of 4 areas".
		*/
		$ago = '';

		if ( $analyzed_at > 0 ) {
			$ago = $fix_first > 0
				? sprintf(
					/* translators: 1: human-readable time difference, e.g. "3 days". 2: number of "fix first" recommendations, e.g. "2". */
					_n(
						'Last analyzed %1$s ago · %2$s thing to fix first',
						'Last analyzed %1$s ago · %2$s things to fix first',
						$fix_first,
						'charitable'
					),
					human_time_diff( $analyzed_at ),
					number_format_i18n( $fix_first )
				)
				: sprintf(
					/* translators: %s: human-readable time difference, e.g. "3 days". */
					__( 'Last analyzed %s ago · nothing to fix first', 'charitable' ),
					human_time_diff( $analyzed_at )
				);
		}

		$value = max( 0, min( 100, (int) $score['value'] ) );
		$band  = isset( $score['band'] ) ? (string) $score['band'] : '';

		$dimensions = array();

		foreach ( $labels as $key => $label ) {
			$dim                = isset( $score['dimensions'][ $key ] ) ? (array) $score['dimensions'][ $key ] : array();
			$dim_value          = max( 0, min( 100, isset( $dim['value'] ) ? (int) $dim['value'] : 0 ) );
			$dimensions[ $key ] = array(
				'label'    => $label,
				'value'    => $dim_value,
				'complete' => ! empty( $dim['complete'] ),
				'color'    => $this->site_analysis_scale_color( $dim_value ),
			);
		}

		return array(
			'value'      => $value,
			'band'       => $band,
			'band_label' => $this->site_analysis_band_label( $band ),
			'color'      => $this->site_analysis_band_color( $band ),
			'potential'  => max( $value, isset( $score['potential'] ) ? (int) $score['potential'] : 0 ),
			'dimensions' => $dimensions,
			'fix_first'  => (int) $fix_first,
			'ago'        => $ago,
		);
	}



	/**
	 * The score and its colour for the Site Analysis tab badge.
	 *
	 * Returns the COLOUR as well as the value, and that is the whole point of this method existing.
	 * There are two colour functions in play and they disagree:
	 *
	 *   site_analysis_band_color( $band )  - 'great' green, 'good' amber, else red. Used by the ring.
	 *   site_analysis_scale_color( $value ) - >=70 green, >=40 amber, else red. Used by the bars.
	 *
	 * At 72 they return DIFFERENT answers: the band is 'good' so the ring is amber, while 72 clears
	 * the scale's 70 threshold so a scale lookup is green. The badge was calling the scale function
	 * and rendered a green 72 next to an amber ring showing the same 72. Both now come from
	 * band_color, because both are reporting the overall score rather than one dimension.
	 *
	 * Returns null when the site has never been analysed, and the caller shows the "New" pill.
	 * This used to return the example fixture's 72 / 'good' pair, which put a fake score in the
	 * tab strip with nothing marking it as an example. @since 1.8.15
	 *
	 * @since 1.8.15
	 *
	 * @return array|null { @type int $value 0-100. @type string $color Hex. } or null.
	 */
	private function get_site_analysis_tab_score() {

		$score = $this->get_site_analysis_dashboard_score();

		if ( is_array( $score ) && isset( $score['value'] ) ) {
			return array(
				'value' => (int) $score['value'],
				// Already band_color() - set in get_site_analysis_dashboard_score().
				'color' => isset( $score['color'] ) ? $score['color'] : $this->site_analysis_band_color( '' ),
			);
		}

		return null;
	}



	/**
	 * Band colour for the score ring. Mirrors bandColor() in
	 * assets/js/admin/charitable-site-analysis.js so the card and the tab agree.
	 *
	 * @since 1.8.15
	 *
	 * @param string $band One of 'great', 'good', or anything else for needs-work.
	 * @return string
	 */
	public function site_analysis_band_color( $band ) {
		if ( 'great' === $band ) {
			return '#5AA152';
		}
		return 'good' === $band ? '#dba617' : '#d63638';
	}



	/**
	 * Band label for the score ring. Mirrors bandLabel() in the tab's JS.
	 *
	 * @since 1.8.15
	 *
	 * @param string $band One of 'great', 'good', or anything else for needs-work.
	 * @return string
	 */
	public function site_analysis_band_label( $band ) {
		if ( 'great' === $band ) {
			return __( 'Great', 'charitable' );
		}
		return 'good' === $band ? __( 'Good', 'charitable' ) : __( 'Needs work', 'charitable' );
	}



	/**
	 * Colour for a single dimension bar. Mirrors dimColor() in the tab's JS, thresholds included.
	 *
	 * @since 1.8.15
	 *
	 * @param int $value 0-100.
	 * @return string
	 */
	public function site_analysis_scale_color( $value ) {
		if ( $value >= 70 ) {
			return '#5AA152';
		}
		return $value >= 40 ? '#dba617' : '#d63638';
	}



	/**
	 * Render the Site Analysis tab content. v3 only.
	 *
	 * Uses the real score from get_site_analysis_dashboard_score() the moment one exists, and
	 * falls back to a fixture when it does not, so the tab is never an empty promo box. The
	 * fixture is LABELLED as example data rather than passed off as real - the Site Analysis tab
	 * itself does the same thing with the same numbers (see its "EXAMPLE DATA" overlay), so a
	 * score of 72 shown this way is already established as illustrative rather than measured.
	 *
	 * Colours come from site_analysis_band_color() / site_analysis_scale_color(), which mirror
	 * bandColor() and dimColor() in assets/js/admin/charitable-site-analysis.js. That palette is
	 * used deliberately in place of brand canon, as the rail card it descends from documented:
	 * canon has no warning or pending token, so there is no canonical way to say "good but not
	 * great" or "needs work" here.
	 *
	 * @since 1.8.15
	 *
	 * @return void
	 */
	private function render_site_analysis_tab() {

		$score      = $this->get_site_analysis_dashboard_score();
		$is_example = ! is_array( $score );
		$url        = admin_url( 'admin.php?page=charitable-reports&tab=site-analysis' );

		if ( $is_example ) {
			$dimension_fixture = array(
				'activation'   => array( 'label' => __( 'Activation', 'charitable' ), 'value' => 88 ),
				'health'       => array( 'label' => __( 'Health', 'charitable' ), 'value' => 76 ),
				'growth'       => array( 'label' => __( 'Growth', 'charitable' ), 'value' => 54 ),
				'optimization' => array( 'label' => __( 'Optimization', 'charitable' ), 'value' => 61 ),
			);

			$dimensions = array();

			foreach ( $dimension_fixture as $key => $dim ) {
				$dimensions[ $key ] = array(
					'label' => $dim['label'],
					'value' => $dim['value'],
					'color' => $this->site_analysis_scale_color( $dim['value'] ),
				);
			}

			$score = array(
				'value'      => 72,
				'band_label' => $this->site_analysis_band_label( 'good' ),
				'color'      => $this->site_analysis_band_color( 'good' ),
				'potential'  => 94,
				'fix_first'  => 3,
				'ago'        => '',
				'dimensions' => $dimensions,
			);
		}
		?>
		<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'site-analysis' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="site-analysis">
			<div class="charitable-sa-tab">

				<div class="charitable-sa-tab-score">
					<div class="charitable-sa-tab-ring" style="background: conic-gradient(<?php echo esc_attr( $score['color'] ); ?> <?php echo esc_attr( (int) $score['value'] * 3.6 ); ?>deg, #e9e9ea 0deg);">
						<b><?php echo esc_html( number_format_i18n( $score['value'] ) ); ?></b>
					</div>
					<div class="charitable-sa-tab-score-meta">
						<span class="charitable-sa-tab-band" style="color:<?php echo esc_attr( $score['color'] ); ?>;"><?php echo esc_html( $score['band_label'] ); ?></span>
						<span class="charitable-sa-tab-potential"><?php
							printf(
								/* translators: %s: the score achievable after acting on every recommendation */
								esc_html__( 'Up to %s with every recommendation applied', 'charitable' ),
								esc_html( number_format_i18n( $score['potential'] ) )
							);
						?></span>
						<?php if ( $is_example ) : ?>
							<span class="charitable-sa-tab-example"><?php esc_html_e( 'Example data', 'charitable' ); ?></span>
						<?php elseif ( ! empty( $score['ago'] ) ) : ?>
							<span class="charitable-sa-tab-potential"><?php echo esc_html( $score['ago'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>

				<div class="charitable-sa-tab-dims">
					<?php foreach ( $score['dimensions'] as $dim ) : ?>
						<div class="charitable-sa-tab-dim">
							<div class="charitable-sa-tab-dim-head">
								<span class="charitable-sa-tab-dim-label"><?php echo esc_html( $dim['label'] ); ?></span>
								<span class="charitable-sa-tab-dim-value"><?php echo esc_html( number_format_i18n( $dim['value'] ) ); ?></span>
							</div>
							<div class="charitable-sa-tab-dim-track">
								<span class="charitable-sa-tab-dim-fill" style="width:<?php echo esc_attr( (int) $dim['value'] ); ?>%; background:<?php echo esc_attr( $dim['color'] ); ?>;"></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="charitable-sa-tab-foot">
					<?php if ( $is_example ) : ?>
						<a href="<?php echo esc_url( $url ); ?>" class="button button-primary charitable-sa-dash-button charitable-sa-tab-cta">
							<?php esc_html_e( 'Run Your Free Analysis', 'charitable' ); ?>
						</a>
						<span class="charitable-sa-tab-foot-note"><?php esc_html_e( 'Free to run, based on your current Charitable configuration. No donor data is ever sent.', 'charitable' ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $url ); ?>" class="button button-primary charitable-sa-dash-button charitable-sa-tab-cta">
							<?php esc_html_e( 'View Full Analysis', 'charitable' ); ?>
						</a>
						<?php if ( ! empty( $score['fix_first'] ) ) : ?>
							<span class="charitable-sa-tab-foot-note"><?php
								printf(
									esc_html(
										/* translators: %s: number of "fix first" recommendations */
										_n( '%s thing to fix first', '%s things to fix first', (int) $score['fix_first'], 'charitable' )
									),
									esc_html( number_format_i18n( $score['fix_first'] ) )
								);
							?></span>
						<?php endif; ?>
					<?php endif; ?>
				</div>

			</div>
		</div>
		<?php
		/*
		Click beacon, same nonce action and tracking label as the rail card's. Re-emitted here for
		the same reason the tile version was: the shipping beacon in render_site_analysis_section()
		selects inside #charitable-dashboard-v2-site-analysis, which v3 does not render, and it
		bails silently on a null match rather than erroring - so without this the
		dashboard_site_analysis click metric would quietly read zero.
		*/
		$charitable_sa_tab_nonce = wp_create_nonce( 'charitable_site_analysis_click' );
		?>
		<script>
		( function () {
			var link = document.querySelector( '.charitable-sa-tab .charitable-sa-dash-button' );
			if ( ! link || ! navigator.sendBeacon ) { return; }
			link.addEventListener( 'click', function () {
				try {
					var fd = new FormData();
					fd.append( 'action', 'charitable_track_site_analysis_click' );
					fd.append( 'nonce', '<?php echo esc_js( $charitable_sa_tab_nonce ); ?>' );
					navigator.sendBeacon( '<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>', fd );
				} catch ( e ) {}
			} );
		} )();
		</script>
		<?php
	}



	/**
	 * Render the tabs section with campaigns, donations, donors, and comments.
	 *
	 * @since 1.8.15
	 */
	/**
	 * The dashboard tab registry: the single source of truth for which tabs exist.
	 *
	 * Replaces two hardcoded lists that had to be kept in step by hand - the nav markup in
	 * render_tabs_section() and the $valid_tabs array in get_default_tab(). Both now derive from
	 * here, so adding a tab is one entry plus a panel renderer.
	 *
	 * Each entry supports:
	 *   label    - the visible tab text.
	 *   render   - method on this class that prints the panel.
	 *   badge    - optional array( 'type' => 'new'|'count', 'label' => string ). 'new' is the
	 *              green feature flag; 'count' is the red bubble for things needing attention.
	 *
	 * @since 1.8.15
	 *
	 * @return array Keyed by tab slug, in display order.
	 */
	private function get_dashboard_tabs() {

		static $memo = null;

		if ( null !== $memo ) {
			return $memo;
		}

		$tabs = array(
			'top-campaigns'    => array(
				'label'  => __( 'Top Campaigns', 'charitable' ),
				'render' => 'render_top_campaigns_tab',
			),
			'latest-donations' => array(
				'label'  => __( 'Latest Donations', 'charitable' ),
				'render' => 'render_latest_donations_tab',
			),
			'top-donors'       => array(
				'label'  => __( 'Top Donors', 'charitable' ),
				'render' => 'render_top_donors_tab',
			),
			'comments'         => array(
				'label'  => __( 'Comments', 'charitable' ),
				'render' => 'render_comments_tab',
			),
		);

		if ( ( function_exists( 'charitable_reports_v3_enabled' ) && charitable_reports_v3_enabled() ) ) {

			/*
			Site Analysis, with the 1-100 score AS the badge rather than a "New" pill - the number is
			the thing worth glancing at, and a score that is visible without opening the tab is the
			only version of this that earns its place in the strip.

			The score comes from get_site_analysis_tab_score(), which returns the band colour alongside
			the value so the badge cannot disagree with the ring inside the panel - see that method for
			why using the other colour function made a green 72 sit beside an amber 72.

			A site that has never been analysed gets the "New" pill instead of a number. The badge
			used to fall back to the example fixture's 72, but the badge carries no "example" label
			the way the panel's ring does, so on its own it read as a real, passing score on every
			site that had never run an analysis. @since 1.8.15
			*/
			$charitable_sa = $this->get_site_analysis_tab_score();

			$tabs['site-analysis'] = array(
				'label'  => __( 'Site Analysis', 'charitable' ),
				'render' => 'render_site_analysis_tab',
				'badge'  => null === $charitable_sa
					? array(
						'type'  => 'new',
						'label' => __( 'New', 'charitable' ),
					)
					: array(
						'type'  => 'score',
						'label' => number_format_i18n( $charitable_sa['value'] ),
						// Band colour, so the badge always matches the ring inside the panel.
						'color' => $charitable_sa['color'],
					),
			);
		}

		/*
		Donation Recovery and Security were registered here and have been removed:
		four tabs now stretch to fill the strip instead. Their panel renderers are still present -
		render_donation_recovery_tab(), render_security_tab() and the get_security_checks()
		helper behind it - but NOTHING CALLS THEM while they are absent from this array.
		(Site Analysis is registered again, just above.)

		That is stated plainly because it is the same shape as render_dashboard_new(): a complete,
		correct-looking render method that is simply never reached, which costs somebody a debug cycle
		when they edit it and nothing changes. Re-registering an entry here is all it takes to bring
		one back; the renderers themselves need no changes. @since 1.8.15
		*/

		/**
		 * Filter the dashboard tabs.
		 *
		 * @since 1.8.15
		 *
		 * @param array $tabs Tab definitions keyed by slug.
		 */
		$memo = (array) apply_filters( 'charitable_dashboard_tabs', $tabs );

		return $memo;
	}



		private function render_latest_updates_items_v3( array $updates ) {

			$updates = array_slice( $updates, 0, 4 );

			if ( empty( $updates ) ) {
				return;
			}
			?>
			<div class="charitable-dashboard-v2-blog-posts charitable-whats-new-v3" id="charitable-blog-posts-container">
				<?php
				/*
				An accordion, not a featured item plus a list of links.

				The old shape gave the newest item the full treatment - icon, pills, description,
				Learn More - and demoted the other three to one-line rows, so three of the four were
				unreadable without leaving the dashboard. Every item now carries the same treatment
				and exactly one is open at a time, which keeps the card the same height as before
				while making all four reachable.

				The header is a real <button> with aria-expanded, so it is keyboard-operable and
				announces its state. That is also why the TITLE is not a link: a link inside a button
				is invalid and lands differently in every browser. The panel's Learn More is the way
				out to the post, which is one clear destination per item instead of two.

				Both the date and the pills render for every item, and CSS shows whichever the state
				calls for - the date on the right when closed, the pills under the title when open.
				Moving one node with JS instead would put layout decisions in two places.
				@since 1.8.15
				*/
				?>
				<ul class="charitable-wn-accordion">
					<?php
					foreach ( $updates as $charitable_wn_i => $update ) :

						$wn_url   = $this->pick_latest_update_url( $update );
						$wn_icon  = $this->get_latest_update_icon( $update );
						$wn_open  = 0 === $charitable_wn_i;
						$wn_date  = ! empty( $update['date'] ) ? $this->calculate_relative_time( $update['date'] ) : '';
						$wn_panel = 'charitable-wn-panel-' . (int) $charitable_wn_i;
						?>
						<li class="charitable-wn-item<?php echo $wn_open ? ' is-open' : ''; ?>">
							<button type="button" class="charitable-wn-head" aria-expanded="<?php echo $wn_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $wn_panel ); ?>">
								<?php
								/*
								Icon replaces the 2:1 thumbnail the feed supplies in `image`: that
								artwork is a wide banner with baked-in text, so a square crop renders
								as an illegible fragment of a sentence.
								*/
								?>
								<span class="charitable-wn-icon charitable-whats-new-v3-icon--<?php echo esc_attr( $wn_icon['badge'] ? $wn_icon['badge'] : 'default' ); ?>" aria-hidden="true">
									<?php if ( 'image' === $wn_icon['type'] ) : ?>
										<?php
										/*
										A 404 icon used to leave a broken image and nothing else.

										The three-step cascade in get_latest_update_icon() runs on the
										SERVER, so it can only see whether the feed supplied an icon
										string - never whether that URL resolves. A typo in
										charitable-latest-updates.json therefore reached every site as
										a broken-image glyph, and the local-addon and topic-glyph
										fallbacks below it could not help, having already been skipped.

										So the topic glyph is emitted alongside the image, hidden, and
										the image's own onerror reveals it. That keeps the decision
										client-side where the only real answer lives, costs one hidden
										SVG per item, and needs no enqueued script.

										The image is REMOVED, not hidden, and that distinction was
										earned: .charitable-wn-icon img sets display: block, which
										out-specifies the [hidden] attribute's own display: none, so
										hidden=true left the broken-image box sitting in the plate and
										shoving the fallback out of it. Verified on screen before and
										after. parentNode is captured first, since remove() nulls it.
										@since 1.8.15
										*/
										$wn_fallback = $this->get_latest_update_glyph( isset( $update['title'] ) ? (string) $update['title'] : '' );
										?>
										<img src="<?php echo esc_url( $wn_icon['url'] ); ?>" alt="" width="38" height="38" onerror="var p=this.parentNode;this.remove();var g=p.querySelector('[data-charitable-icon-fallback]');if(g){g.hidden=false;}" />
										<span data-charitable-icon-fallback hidden><?php echo $wn_fallback; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php else : ?>
										<?php echo $wn_icon['glyph']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php endif; ?>
								</span>

								<span class="charitable-wn-headtext">
									<span class="charitable-wn-title"><?php echo esc_html( $update['title'] ); ?></span>

									<span class="charitable-wn-meta">
										<?php if ( ! empty( $update['is_new'] ) ) : ?>
											<span class="charitable-dashboard-v2-blog-post-badge-new"><?php esc_html_e( 'New', 'charitable' ); ?></span>
										<?php endif; ?>
										<?php if ( 'pro' === $update['badge'] ) : ?>
											<span class="charitable-dashboard-v2-blog-post-badge-pro"><?php esc_html_e( 'Pro', 'charitable' ); ?></span>
										<?php elseif ( 'addon' === $update['badge'] ) : ?>
											<span class="charitable-dashboard-v2-blog-post-badge-addon"><?php esc_html_e( 'Addon', 'charitable' ); ?></span>
										<?php endif; ?>
										<?php
										/*
										The date used to render here as well, shown when open and hidden when closed while
										the right-hand copy did the reverse. It now lives in ONE place - the right-hand
										span below - and stays there in both states, so a row's date does not jump from
										beside the pills to the far edge as it opens.
										*/
										?>
									</span>
								</span>

								<?php if ( '' !== $wn_date ) : ?>
									<span class="charitable-wn-date"><?php echo esc_html( $wn_date ); ?></span>
								<?php endif; ?>
							</button>

							<div class="charitable-wn-panel" id="<?php echo esc_attr( $wn_panel ); ?>">
								<?php if ( ! empty( $update['description'] ) ) : ?>
									<p class="charitable-wn-desc"><?php echo esc_html( $update['description'] ); ?></p>
								<?php endif; ?>

								<a href="<?php echo esc_url( $wn_url ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-blog-post-learn-more"><?php esc_html_e( 'Learn More', 'charitable' ); ?> &rarr;</a>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}



		/**
		 * Render the v3 "What's New" items: one featured, the rest compact.
		 *
		 * The shipping card stacks four equally-weighted rows, which reads as a list to scan
		 * rather than news to absorb. This gives the newest item a real hierarchy - big
		 * thumbnail, full excerpt - and collapses the older ones to a title and a date, which is
		 * all they are actually consulted for. The badges stay on the featured item only; on a
		 * one-line row they were competing with the title for the eye.
		 *
		 * Caps at four items total, matching the shipping renderer.
		 *
		 * @since 1.8.15
		 *
		 * @param  array $updates Feed items, newest first.
		 * @return void
		 */
		/**
		 * Resolve a small square icon for a What's New item.
		 *
		 * Resolution order:
		 *   1. an `icon` URL supplied by the feed itself;
		 *   2. a local addon icon derived from the item id;
		 *   3. a tinted glyph, coloured by the item's badge.
		 *
		 * Step 1 exists because it is the only one that actually scales. The feed is editorial -
		 * integrations, blocks, builder features - and does NOT map onto the addon catalogue: of
		 * the 21 items currently in the feed, exactly ONE resolves a local addon icon, and only
		 * about five would even with generous slug normalisation. Third-party marks like Brevo's
		 * are not in the tree at all and should not be approximated. So a per-item logo needs
		 * either an asset added to the plugin or, better, an `icon` key in
		 * charitable-latest-updates.json - and this reads that key today so it starts working the
		 * moment the feed carries it, with no plugin release.
		 *
		 * Step 3 is therefore what most items get, and is treated as a real state rather than an
		 * error: the tint carries the badge meaning (addon vs pro), so the icon is informative
		 * instead of decorative.
		 *
		 * @since 1.8.15
		 *
		 * @param  array $update Feed item.
		 * @return array { @type string $type 'image'|'glyph'. @type string $url. @type string $badge. }
		 */
		/**
		 * A glyph that matches what the update is about.
		 *
		 * Every item without an icon file used to get the same megaphone, so three of four rows in the
		 * card were identical and the icon told the reader nothing. Keyword-matched on the title, which
		 * is the only field guaranteed to describe the thing - the feed's id is editorial and its
		 * `icon` field is optional.
		 *
		 * Drawn at 30px in a 24-unit viewBox so a stroked glyph reads at roughly the weight of the
		 * 38px addon PNGs beside it. Filling the full 38px made a 1.7px stroke look like a border.
		 *
		 * The megaphone stays as the fallback: an announcement is what this card is, so it is the right
		 * thing to show when nothing more specific is known.
		 *
		 * @since 1.8.15
		 *
		 * @param  string $title Update title.
		 * @return string Inline SVG.
		 */
		private function get_latest_update_glyph( $title ) {

			$title = strtolower( $title );

			// Ordered: the first match wins, so put the specific before the general.
			$map = array(
				// Brush - restyling something that already exists.
				'style'     => '<path d="M4 20h4l9.4-9.4a2.7 2.7 0 0 0-3.8-3.8L4 16.2V20Z"/><path d="M13.6 7.4l3.8 3.8"/>',
				// Square with a plus - dropping a block onto a page.
				'block'     => '<rect x="3.6" y="3.6" width="16.8" height="16.8" rx="2.6"/><path d="M12 8.4v7.2M8.4 12h7.2"/>',
				// Sliders - a settings or builder surface.
				'builder'   => '<path d="M5 6h14M5 12h14M5 18h14"/><circle cx="9" cy="6" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="8" cy="18" r="2"/>',
				// Envelope - anything about email.
				'email'     => '<rect x="3" y="5.4" width="18" height="13.2" rx="2"/><path d="M3.6 6.4 12 13l8.4-6.6"/>',
				// Card - payments and gateways.
				'payment'   => '<rect x="2.6" y="5.4" width="18.8" height="13.2" rx="2.2"/><path d="M2.6 10.2h18.8M6.4 14.8h3.6"/>',
				// Repeat arrows - recurring giving.
				'recurring' => '<path d="M4 12a8 8 0 0 1 13.6-5.6M20 12a8 8 0 0 1-13.6 5.6"/><path d="M17.6 3.2v3.6h-3.6M6.4 20.8v-3.6h3.6"/>',
				// Chart - reports and analytics.
				'report'    => '<path d="M4 20h16"/><path d="M7 20V11M12 20V5M17 20v-6"/>',
			);

			$paths = '<path d="M4 9.6 17.4 4.3c.6-.2 1.2.2 1.2.8v13.8c0 .6-.6 1-1.2.8L4 14.4V9.6Z"/><path d="M7 10.7v6.5a1.8 1.8 0 0 0 3.6 0V12"/><path d="M21 9.3v5"/>';

			foreach ( $map as $needle => $candidate ) {
				if ( false !== strpos( $title, $needle ) ) {
					$paths = $candidate;
					break;
				}
			}

			return '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">' . $paths . '</svg>';
		}


		private function get_latest_update_icon( array $update ) {

			// 1. Feed-supplied icon.
			if ( ! empty( $update['icon'] ) ) {
				return array(
					'type'  => 'image',
					'url'   => $update['icon'],
					'badge' => isset( $update['badge'] ) ? $update['badge'] : '',
				);
			}

			// 2. Local addon icon, if the item id happens to name one. Trailing editorial suffixes
			// are stripped so "recurring-donations-2" and "google-analytics-update" still match.
			$id = isset( $update['id'] ) ? (string) $update['id'] : '';

			if ( '' !== $id ) {
				$candidates = array(
					$id,
					preg_replace( '/^charitable-/', '', $id ),
					preg_replace( '/-(\d+|addon|update|integration)$/', '', $id ),
					/*
					A trailing VERSION, not just a trailing number.

					The rule above strips one token, so "braintree-1-3-0" became "braintree-1-3"
					and never reached addon-icon-braintree.png - which ships, and sat unused while
					that item rendered a generic card glyph. Feed ids carry release numbers as
					hyphenated segments, so the whole tail has to go, not its last piece.

					Anchored on all-digit segments only, which is what keeps it safe: it cannot
					eat a real word. "stripe-3d-secure" ends in "secure" and is untouched, and
					"annual-receipts-2" resolves identically through this rule or the one above.
					@since 1.8.15
					*/
					preg_replace( '/-\d+(?:-\d+)*$/', '', $id ),
					preg_replace( '/-\d+(?:-\d+)*$/', '', preg_replace( '/^charitable-/', '', $id ) ),
				);

				/*
				The title, slugified, as a further candidate. The feed's id is editorial and does not always
				carry the addon's slug - "Charitable Tributes" shipped with an id that matched no icon file,
				so it fell through to a glyph while addon-icon-tributes.png sat unused. Matching on the
				title as well picks up any item that names its addon, which most of them do.
				*/
				$title_slug = isset( $update['title'] ) ? sanitize_title( $update['title'] ) : '';

				if ( '' !== $title_slug ) {
					$candidates[] = $title_slug;
					$candidates[] = preg_replace( '/^charitable-/', '', $title_slug );
				}

				foreach ( array_unique( array_filter( $candidates ) ) as $candidate ) {
					$file = 'addon-icon-' . $candidate . '.png';

					if ( file_exists( charitable()->get_path( 'assets' ) . 'images/addons/' . $file ) ) {
						return array(
							'type'  => 'image',
							'url'   => charitable()->get_path( 'directory', false ) . 'assets/images/addons/' . $file,
							'badge' => isset( $update['badge'] ) ? $update['badge'] : '',
						);
					}
				}
			}

			// 3. Tinted glyph, chosen by topic - see get_latest_update_glyph().
			return array(
				'type'  => 'glyph',
				'url'   => '',
				'glyph' => $this->get_latest_update_glyph( isset( $update['title'] ) ? (string) $update['title'] : '' ),
				'badge' => isset( $update['badge'] ) ? $update['badge'] : '',
			);
		}


		/**
		 * Pick the right URL for a Latest Updates item based on license state.
		 *
		 * Order of preference:
		 *   Pro users  : url_pro → url_lite → url → https://www.wpcharitable.com/lite-upgrade/
		 *   Lite users : url_lite → url → https://www.wpcharitable.com/lite-upgrade/
		 *
		 * @since 1.8.15
		 *
		 * @param  array $update Feed item.
		 * @return string
		 */
		private function pick_latest_update_url( array $update ) {

			$fallback = 'https://www.wpcharitable.com/lite-upgrade/';
			$is_pro   = function_exists( 'charitable_is_pro' ) && charitable_is_pro();
			$url_pro  = ! empty( $update['url_pro'] ) ? $update['url_pro'] : '';
			$url_lite = ! empty( $update['url_lite'] ) ? $update['url_lite'] : '';
			$url      = ! empty( $update['url'] ) ? $update['url'] : '';

			if ( $is_pro ) {
				return $url_pro ?: ( $url_lite ?: ( $url ?: $fallback ) );
			}

			return $url_lite ?: ( $url ?: $fallback );
		}



	/**
	 * Render the top donors tab content.
	 *
	 * @since 1.8.15
	 */
	/**
	 * Render top donors as a two-column grid. v3 only.
	 *
	 * The shipping layout is a four-column table, which spends most of the widened left column on
	 * whitespace: a 40px avatar, a name, an amount and a badge do not need 1019px, so each donor
	 * got a very long, very empty row. Two donors per row halves the vertical space and puts the
	 * amount next to the name it belongs to instead of a screen-width away.
	 *
	 * Reuses the shipping .donor-avatar / .donor-details / .donor-name / .donor-email /
	 * .donor-amount / .status-badge class names, so the existing type and badge styling carries
	 * over untouched and only the layout is new. Same approach as the version pill: keep the
	 * classes, change the container.
	 *
	 * Collapses to one column at 782px, WordPress's own admin breakpoint - the same value the
	 * Raise More grid uses, so the two grids reflow together rather than at different points.
	 *
	 * @since 1.8.15
	 *
	 * @param  array $donors Donor rows from get_top_donors().
	 * @return void
	 */
	private function render_top_donors_grid_v3( array $donors ) {
		?>
		<div class="charitable-donors-grid-v3">
			<?php foreach ( $donors as $donor ) : ?>
				<div class="charitable-donor-card-v3">
					<div class="donor-avatar">
						<?php
						/*
						Guard on an empty avatar. get_top_donors() returns '' for donors it cannot
						resolve a gravatar for, and the shipping table renders that straight into
						<img src="">. An empty src is not a no-op: the browser resolves it against
						the current document, re-requests the entire admin page as if it were an
						image, and then draws a broken-image icon. Three of the eight donors on
						this site hit it. The shipping table has the same defect - it was simply
						never visible, because only the first few rows sat above the fold.
						@since 1.8.15
						*/
						if ( ! empty( $donor['avatar'] ) ) :
							?>
							<img src="<?php echo esc_url( $donor['avatar'] ); ?>" alt="<?php echo esc_attr( $donor['name'] ); ?>" width="40" height="40">
						<?php else : ?>
							<span class="charitable-donor-avatar-fallback" aria-hidden="true"><?php
								// First character of the display name, or a dash when even that is absent.
								$charitable_initial = trim( (string) $donor['name'] );
								echo esc_html( '' !== $charitable_initial ? mb_strtoupper( mb_substr( $charitable_initial, 0, 1 ) ) : '—' );
							?></span>
						<?php endif; ?>
					</div>
					<div class="donor-details">
						<div class="donor-name">
							<a href="<?php echo esc_url( $donor['url'] ); ?>" class="donor-name-link"><?php echo esc_html( $donor['name'] ); ?></a>
							<?php
							// Same crown as the shipping table above and as the Donors list table.
							// Escaped in the helper. @since 1.8.15
							echo $this->get_top_donor_crown_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
						<div class="donor-email">
							<?php if ( ! empty( $donor['email'] ) ) : ?>
								<a href="mailto:<?php echo esc_attr( $donor['email'] ); ?>" class="donor-email-link" title="<?php echo esc_attr( $donor['email'] ); ?>"><?php echo esc_html( $donor['email_display'] ); ?></a>
							<?php else : ?>
								<span class="no-email"><?php esc_html_e( 'No email', 'charitable' ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<div class="charitable-donor-card-v3-meta">
						<div class="donor-amount"><?php echo esc_html( $donor['total'] ); ?></div>
						<span class="status-badge status-<?php echo esc_attr( $donor['type'] ); ?>"><?php echo esc_html( $donor['type_label'] ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}


	/**
	 * The Top Donor crown badge, next to a donor's name on the Top Donors tab.
	 *
	 * This is the SAME badge the Donors list table renders in its Donor Type column
	 * (includes/admin/donors/class-charitable-donor-list-table.php, case 'donor_type'):
	 * the same .top-donor class name, the same title text, and a byte-for-byte copy of
	 * that SVG path, so a crown means one thing across both screens and the glyph cannot
	 * drift into two slightly different crowns.
	 *
	 * On the list table the badge is conditional - only donors present in
	 * Charitable_Donors::get_top_donor_list() (the site-wide top-10 list, all time,
	 * cached for a day in the charitable_top_donors transient) get one. Every row of the
	 * Top Donors tab IS that population by construction, so the badge is unconditional
	 * here rather than re-deriving a membership test that would always answer true.
	 *
	 * The charitable_enable_top_donors filter is still honoured, because a site that has
	 * switched top-donor marking off should not have it reappear on the dashboard.
	 *
	 * Presentation is inline rather than in a stylesheet, and that is deliberate. The
	 * badge has to render identically on both Top Donors paths: the v3 grid, styled by
	 * charitable-admin-dashboard-v3.css, and the shipping four-column table, whose
	 * stylesheet is charitable-admin-dashboard.css - a file with a hand-maintained
	 * .min.css sibling and three other consumers (the donors, donations and campaigns
	 * list tables). Putting the colour here keeps it in exactly one place and keeps that
	 * .css/.min.css pair untouched. The v3 stylesheet carries only the layout of the name
	 * row, which is genuinely v3-only.
	 *
	 * Colour: rgb(227, 134, 50) / #e38632, matched from the list table's own
	 * .charitable-donor-type .top-donor rule rather than converted to canon brand orange
	 * #fc9a2a, so the two screens agree. #e38632 is NOT in the brand token file's
	 * brand.color.brand-orange.asBuilt drift list - it is excluded there on purpose, as a
	 * value read as semantic rather than brand - so converging it is a separate decision
	 * about that component, not something to do as a side effect of adding a second
	 * consumer.
	 *
	 * @since 1.8.15
	 *
	 * @return string Crown badge HTML, or '' when top-donor marking is switched off.
	 */
	private function get_top_donor_crown_html() {

		if ( ! apply_filters( 'charitable_enable_top_donors', true ) ) {
			return '';
		}

		return '<span class="top-donor" title="' . esc_attr__( 'This donor is a Top Donor.', 'charitable' ) . '" style="display:inline-flex;align-items:center;flex:none;background-color:rgb(227, 134, 50);border-radius:3px;padding:2px 5px;margin-left:8px;vertical-align:middle;"><svg width="15" height="15" viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="display:block;width:15px;height:15px;"><path d="M11.55 9.625H2.45C2.2575 9.625 2.1 9.77969 2.1 9.96875V10.6562C2.1 10.8453 2.2575 11 2.45 11H11.55C11.7425 11 11.9 10.8453 11.9 10.6562V9.96875C11.9 9.77969 11.7425 9.625 11.55 9.625ZM12.95 2.75C12.3703 2.75 11.9 3.21191 11.9 3.78125C11.9 3.93379 11.935 4.07559 11.9963 4.20664L10.4125 5.13906C10.0756 5.33672 9.64031 5.225 9.44562 4.88984L7.66281 1.82617C7.89687 1.63711 8.05 1.35352 8.05 1.03125C8.05 0.461914 7.57969 0 7 0C6.42031 0 5.95 0.461914 5.95 1.03125C5.95 1.35352 6.10313 1.63711 6.33719 1.82617L4.55437 4.88984C4.35969 5.225 3.92219 5.33672 3.5875 5.13906L2.00594 4.20664C2.065 4.07773 2.10219 3.93379 2.10219 3.78125C2.10219 3.21191 1.63187 2.75 1.05219 2.75C0.4725 2.75 0 3.21191 0 3.78125C0 4.35059 0.470312 4.8125 1.05 4.8125C1.10687 4.8125 1.16375 4.80391 1.21844 4.79531L2.8 8.9375H11.2L12.7816 4.79531C12.8362 4.80391 12.8931 4.8125 12.95 4.8125C13.5297 4.8125 14 4.35059 14 3.78125C14 3.21191 13.5297 2.75 12.95 2.75Z" fill="#FFFFFF"/></svg></span>';
	}

	/**
	 * Calculate percentage changes for donations and average.
	 *
	 * @since 1.8.8
	 * @param string $time_period Current time period.
	 * @param float $current_total Current total donations.
	 * @param string $current_avg Current average donation.
	 * @return array
	 */
	private function calculate_percentage_changes( $time_period, $current_total, $current_avg ) {

		$debug_no_cache = ( defined( 'CHARITABLE_DEBUG' ) && true === constant( 'CHARITABLE_DEBUG' ) );

		// Get cache duration from filter (default 4 hours)
		$cache_duration = apply_filters( 'charitable_dashboard_stats_cache_duration', 4 * HOUR_IN_SECONDS );

		// Create cache key
		$cache_key = 'charitable_dashboard_stats_changes_' . $time_period . '_' . gmdate( 'Y-m-d' );

		// Try to get from cache first (unless debugging)
		if ( ! $debug_no_cache ) {
			$cached_changes = get_transient( $cache_key );
			if ( false !== $cached_changes ) {
				return $cached_changes;
			}
		}

		// Calculate previous period date range
		$previous_period_range = $this->get_previous_period_date_range( $time_period );
		if ( false === $previous_period_range ) {
			// If we can't calculate previous period, return no change
			$changes = array(
				'donations_change' => '',
				'avg_change' => ''
			);
			if ( ! $debug_no_cache ) {
				set_transient( $cache_key, $changes, $cache_duration );
			}
			return $changes;
		}

		// Get previous period data
		$previous_data = $this->get_previous_period_data( $previous_period_range );
		if ( false === $previous_data ) {
			// If we can't get previous data, return no change
			$changes = array(
				'donations_change' => '',
				'avg_change' => ''
			);
			if ( ! $debug_no_cache ) {
				set_transient( $cache_key, $changes, $cache_duration );
			}
			return $changes;
		}

		// Calculate percentage changes
		$donations_change = $this->calculate_percentage_change( $previous_data['total'], $current_total );
		$avg_change = $this->calculate_percentage_change( $previous_data['avg'], $this->parse_money_amount( $current_avg ) );

		$changes = array(
			'donations_change' => $donations_change,
			'avg_change' => $avg_change
		);

		// Cache the results unless debugging
		if ( ! $debug_no_cache ) {
			set_transient( $cache_key, $changes, $cache_duration );
		}

		return $changes;
	}

	/**
	 * Get previous period date range for comparison.
	 *
	 * @since 1.8.8
	 * @param string $time_period Current time period.
	 * @return array|false
	 */
	private function get_previous_period_date_range( $time_period ) {
		$current_range = $this->get_date_range_for_period( $time_period );
		$current_start = $current_range['start_date'];
		$current_days = $current_range['days'];

		// Calculate previous period dates
		$previous_end = gmdate( 'Y/m/d', strtotime( $current_start . ' -1 day' ) );
		$previous_start = gmdate( 'Y/m/d', strtotime( $previous_end . ' -' . ( $current_days - 1 ) . ' days' ) );

		return array(
			'start_date' => $previous_start,
			'end_date' => $previous_end,
			'days' => $current_days
		);
	}

	/**
	 * Get previous period donation data.
	 *
	 * @since 1.8.8
	 * @param array $date_range Previous period date range.
	 * @return array|false
	 */
	private function get_previous_period_data( $date_range ) {
		try {
			// Initialize Charitable Reports for previous period
			$charitable_reports = Charitable_Reports::get_instance();
			$args = array(
				'start_date' => $date_range['start_date'],
				'end_date'   => $date_range['end_date'],
				'days'       => $date_range['days'],
			);
			$charitable_reports->init_with_array( 'dashboard', $args );

			// Get donations data
			$donations = $charitable_reports->get_donations();
			$total_count_donations = count( $donations );
			$total_amount_donations = $charitable_reports->get_donations_total();

			// Calculate average
			$donation_average = ( $total_amount_donations > 0 && $total_count_donations > 0 ) ? ( $total_amount_donations / $total_count_donations ) : 0;

			return array(
				'total' => $total_amount_donations,
				'avg' => $donation_average
			);
		} catch ( Exception $e ) {
			// Log error and return false
			if ( charitable_is_debug() ) {
				error_log( 'Charitable Dashboard: Error getting previous period data - ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return false;
		}
	}

	/**
	 * Calculate percentage change between two values.
	 *
	 * @since 1.8.8
	 * @param float $previous Previous value.
	 * @param float $current Current value.
	 * @return string
	 */
	private function calculate_percentage_change( $previous, $current ) {
		// Handle edge cases: if either value is zero or negative, do not show a percentage change
		if ( $previous <= 0 || $current <= 0 ) {
			return '';
		}

		$percentage = ( ( $current - $previous ) / $previous ) * 100;

		// Round to 1 decimal place
		$percentage = round( $percentage, 1 );

		// Handle very large percentages
		if ( $percentage > 99.9 ) {
			return '+99.9%';
		}
		if ( $percentage < -99.9 ) {
			return '-99.9%';
		}

		// Format with + or - sign
		$sign = $percentage > 0 ? '+' : '';
		return $sign . $percentage . '%';
	}

	/**
	 * Parse money amount from formatted string.
	 *
	 * @since 1.8.8
	 * @param string $money_formatted Formatted money string.
	 * @return float
	 */
	private function parse_money_amount( $money_formatted ) {
		// Decode HTML entities first (e.g., &#36; becomes $)
		$money_formatted = html_entity_decode( $money_formatted, ENT_QUOTES, 'UTF-8' );

		// Remove currency symbols and formatting
		$amount = preg_replace( '/[^\d.,]/', '', $money_formatted );

		// Handle different decimal separators
		if ( strpos( $amount, ',' ) !== false && strpos( $amount, '.' ) !== false ) {
			// Both comma and dot present - assume comma is thousands separator
			$amount = str_replace( ',', '', $amount );
		} elseif ( strpos( $amount, ',' ) !== false ) {
			// Only comma - could be decimal separator
			$amount = str_replace( ',', '.', $amount );
		}

		return (float) $amount;
	}

	/**
	 * Clear dashboard stats cache.
	 *
	 * @since   1.8.8
	 * @version 1.8.8.4 - added blog posts cache clearing
	 */
	public function clear_dashboard_stats_cache() {
		// Delete all dashboard stats cache transients
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_charitable_dashboard_stats_changes_' ) . '%'
			)
		);

		// Also delete the transient timeout entries
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_charitable_dashboard_stats_changes_' ) . '%'
			)
		);

		// Clear blog posts cache and Latest Updates feed cache.
		delete_transient( 'charitable_blog_posts_cache' );
		delete_transient( 'charitable_latest_updates_cache' );
	}

	/**
	 * Get the default tab from URL parameter.
	 *
	 * @since 1.8.8
	 * @return string
	 */
	private function get_default_tab() {
		$valid_tabs = array( 'top-campaigns', 'latest-donations', 'top-donors', 'comments' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$default_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'top-campaigns';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Validate the tab parameter
		if ( ! in_array( $default_tab, $valid_tabs, true ) ) {
			$default_tab = 'top-campaigns';
		}

		return $default_tab;
	}

	/**
	 * Check if a tab should be active.
	 *
	 * @since 1.8.8
	 * @param string $tab_name The tab name to check.
	 * @return bool
	 */
	private function is_tab_active( $tab_name ) {
		return $this->get_default_tab() === $tab_name;
	}

	/**
	 * Render the tabs section with campaigns, donations, donors, and comments.
	 *
	 * @since 1.8.8
	 */
	public function render_tabs_section() {
		/*
		DRIVEN BY get_dashboard_tabs() AS OF 1.8.15, where this used to hardcode four tabs and four
		render calls.

		Two things were wrong with the hardcoded version, beyond it not being able to show the Site
		Analysis tab. The four labels were bare literals - "Top Campaigns" rather than
		__( 'Top Campaigns', 'charitable' ) - so the dashboard's own tab strip was the one part of
		the screen that could not be translated. And the list existed twice, in the nav and in the
		content block, so adding a tab meant editing both and a filter could not reach either.

		The registry fixes all three: labels are translated at their definition, the nav and the
		panels iterate the same array, and charitable_dashboard_tabs can add or remove an entry.
		@since 1.8.15
		*/
		$charitable_tabs = $this->get_dashboard_tabs();
		?>
		<section class="charitable-dashboard-v2-section charitable-dashboard-top-campaigns">
			<header class="charitable-dashboard-v2-section-header">
				<nav class="charitable-dashboard-v2-tab-nav">
					<?php foreach ( $charitable_tabs as $charitable_slug => $charitable_tab ) : ?>
						<a href="#" class="charitable-dashboard-v2-tab-nav-item<?php echo $this->is_tab_active( $charitable_slug ) ? ' charitable-dashboard-v2-tab-nav-active' : ''; ?>" data-tab="<?php echo esc_attr( $charitable_slug ); ?>">
							<?php echo esc_html( $charitable_tab['label'] ); ?>
							<?php if ( ! empty( $charitable_tab['badge'] ) ) : ?>
								<span class="charitable-tab-badge charitable-tab-badge--<?php echo esc_attr( $charitable_tab['badge']['type'] ); ?>"<?php echo ! empty( $charitable_tab['badge']['color'] ) ? ' style="background:' . esc_attr( $charitable_tab['badge']['color'] ) . ';"' : ''; ?>><?php echo esc_html( $charitable_tab['badge']['label'] ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</nav>
			</header>
			<div class="charitable-dashboard-v2-section-content">
				<?php
				/*
				method_exists() before each call, which is not defensive clutter: the tab array is
				filterable, so a third party can register a tab whose render callback does not
				exist, and an unguarded variable method call would fatal the whole dashboard rather
				than silently skip one panel.
				*/
				foreach ( $charitable_tabs as $charitable_tab ) {
					if ( ! empty( $charitable_tab['render'] ) && method_exists( $this, $charitable_tab['render'] ) ) {
						$this->{$charitable_tab['render']}();
					}
				}
				?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render the top campaigns tab content.
	 *
	 * @since 1.8.8
	 */
	private function render_top_campaigns_tab() {
		$campaigns = $this->get_top_campaigns();
		?>
		<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'top-campaigns' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="top-campaigns">
			<?php if ( ! empty( $campaigns ) ) : ?>
				<div class="charitable-dashboard-v2-campaigns-table">
					<table class="charitable-dashboard-v2-table">
						<thead>
							<tr>
								<th>Campaign Title</th>
								<th>Goal</th>
								<th>Raised</th>
								<th>Status</th>
								<th>Progress</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $campaigns as $campaign ) : ?>
								<tr>
									<td class="campaign-title">
										<a href="<?php echo esc_url( $campaign['url'] ); ?>">
											<?php echo esc_html( $campaign['title'] ); ?>
											<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M1.09091 11.0001C0.790908 11.0001 0.53409 10.8932 0.320454 10.6796C0.106818 10.466 0 10.2091 0 9.90915V2.27279C0 1.97279 0.106818 1.71598 0.320454 1.50234C0.53409 1.2887 0.790908 1.18188 1.09091 1.18188H4.36363V2.27279H1.09091V9.90915H8.72726V6.63642H9.81817V9.90915C9.81817 10.2091 9.71135 10.466 9.49772 10.6796C9.28408 10.8932 9.02726 11.0001 8.72726 11.0001H1.09091Z" fill="#191D2D" fill-opacity="0.6"/>
												<path d="M4 6.16363L4.76364 6.92726L9.83636 1.85454V3.81818H10.9273V0H7.10909V1.09091H9.07272L4 6.16363Z" fill="#191D2D" fill-opacity="0.6"/>
											</svg>
										</a>
									</td>
									<td><?php echo esc_html( $campaign['goal'] ); ?></td>
									<td><?php echo esc_html( $campaign['reach'] ); ?></td>
									<td><span class="status-badge status-<?php echo esc_attr( $campaign['status'] ); ?>"><?php echo esc_html( $campaign['status_label'] ); ?></span></td>
									<td>
										<div class="progress-circle" data-progress="<?php echo esc_attr( $campaign['progress'] ); ?>">
											<svg width="40" height="40" viewBox="0 0 40 40">
												<circle cx="20" cy="20" r="18" fill="none" stroke="rgba(222, 234, 255, 1)" stroke-width="3"/>
												<circle cx="20" cy="20" r="18" fill="none" stroke="rgba(76, 123, 207, 1)" stroke-width="3"/>
											</svg>
											<span class="progress-text"><?php echo esc_html( $campaign['progress'] ); ?>%</span>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="charitable-dashboard-v2-empty-state">
					<div class="charitable-dashboard-v2-empty-state-content">
						<h3><?php esc_html_e( 'No Campaigns Yet!', 'charitable' ); ?></h3>
						<p><?php esc_html_e( 'Create your first campaign to start receiving donations.', 'charitable' ); ?></p>
						<?php if ( charitable_disable_legacy_campaigns() ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Create Campaign', 'charitable' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Create Campaign', 'charitable' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get top campaigns data.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_top_campaigns() {
		// TEMPORARY TESTING: Return empty campaigns if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Get top campaigns using the same method as legacy dashboard
		$args = array(
			'posts_per_page' => apply_filters( 'charitable_dashboard_top_campaigns_amount', 5 )
		);
		$top_campaigns = Charitable_Campaigns::ordered_by_amount( $args );

			$campaigns = array();

			if ( ! empty( $top_campaigns->posts ) ) {
				foreach ( $top_campaigns->posts as $campaign ) {
					$_campaign = charitable_get_campaign( $campaign->ID );

					if ( ! $_campaign ) {
						continue;
					}

					// Get campaign data (same as legacy)
					$donated_amount = $_campaign->get_donated_amount();
					$donation_goal = $_campaign->get_goal();
					$status = $_campaign->get_status();
					$percent_donated = $_campaign->get_percent_donated_raw();

					// Filter out draft campaigns
					if ( 'draft' === $status ) {
						continue;
					}

					// Cap percentage at 100%
					if ( $percent_donated > 100 ) {
						$percent_donated = 100;
					}

					// Map status to display format (same as legacy)
					$status_labels = array(
						'active' => __( 'Active', 'charitable' ),
						'completed' => __( 'Finished', 'charitable' ),
						'failed' => __( 'Failed', 'charitable' )
					);
					$status_label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucwords( $status );

					// Map status for CSS class
					$status_class = $status;
					if ( 'completed' === $status ) {
						$status_class = 'finished';
					}

					// Build campaign URL
					$url = charitable_disable_legacy_campaigns()
						? admin_url( 'admin.php?page=charitable-campaign-builder&view=template&campaign_id=' . $campaign->ID )
						: admin_url( 'post.php?post=' . $campaign->ID . '&action=edit' );

					// Handle goal display for finished campaigns
					$goal_display = '-';
					if ( $donation_goal ) {
						$goal_display = charitable_format_money( $donation_goal );
					} elseif ( 'completed' === $status ) {
						// For finished campaigns without goals, show the total raised
						$goal_display = charitable_format_money( $donated_amount );
					}

					$campaigns[] = array(
						'title' => $campaign->post_title,
						'url' => $url,
						'goal' => $goal_display,
						'reach' => charitable_format_money( $donated_amount ),
						'status' => $status_class,
						'status_label' => $status_label,
						'progress' => round( $percent_donated )
					);
				}
			}

			// If no campaigns, return empty array
			return $campaigns;
		}

		/**
		 * Render the latest donations tab content.
		 *
		 * @since 1.8.8
		 */
		private function render_latest_donations_tab() {
			$donations = $this->get_latest_donations();
			?>
			<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'latest-donations' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="latest-donations">
				<?php if ( ! empty( $donations ) ) : ?>
					<div class="charitable-dashboard-v2-table-container">
						<table class="charitable-dashboard-v2-table charitable-dashboard-v2-table-donations">
							<thead>
								<tr>
									<th>Donation Details</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $donations as $donation ) : ?>
									<tr>
										<td>
											<div class="donation-details">
												<div class="donation-header">
													<span class="donation-name">
														<a href="<?php echo esc_url( $donation['url'] ); ?>">
															<span class="donation-id">#<?php echo esc_html( $donation['id'] ); ?></span>
															<span class="donor-name"><?php echo esc_html( $donation['donor_name'] ); ?></span>
															<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
																<path d="M1.09091 11.0001C0.790908 11.0001 0.53409 10.8932 0.320454 10.6796C0.106818 10.466 0 10.2091 0 9.90915V2.27279C0 1.97279 0.106818 1.71598 0.320454 1.50234C0.53409 1.2887 0.790908 1.18188 1.09091 1.18188H4.36363V2.27279H1.09091V9.90915H8.72726V6.63642H9.81817V9.90915C9.81817 10.2091 9.71135 10.466 9.49772 10.6796C9.28408 10.8932 9.02726 11.0001 8.72726 11.0001H1.09091Z" fill="#191D2D" fill-opacity="0.6"/>
																<path d="M4 6.16363L4.76364 6.92726L9.83636 1.85454V3.81818H10.9273V0H7.10909V1.09091H9.07272L4 6.16363Z" fill="#191D2D" fill-opacity="0.6"/>
															</svg>
														</a>
													</span>
													<span class="donation-timestamp"><?php echo esc_html( $donation['timestamp'] ); ?></span>
													<span class="donation-amount">
														<strong>Amount Donated:</strong> <?php echo esc_html( $donation['amount'] ); ?>
													</span>
													<span class="donation-method <?php echo esc_attr( $donation['method_class'] ); ?>">
														<strong>Method:</strong> <?php echo esc_html( $donation['method'] ); ?>
													</span>
												</div>
											</div>
										</td>
										<td><span class="status-badge status-<?php echo esc_attr( $donation['status'] ); ?>"><?php echo esc_html( $donation['status_label'] ); ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="charitable-dashboard-v2-empty-state">
						<div class="charitable-dashboard-v2-empty-state-content">
							<h3><?php esc_html_e( 'No Donations Yet!', 'charitable' ); ?></h3>
							<p><?php esc_html_e( 'Start receiving donations to see them here.', 'charitable' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=donation' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Add Donation', 'charitable' ); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Get latest donations data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_latest_donations() {
		// TEMPORARY TESTING: Return empty donations if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Check for test parameter to simulate empty state
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['charitable_test_no_donations'] ) && '1' === $_GET['charitable_test_no_donations'] ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return array();
		}

			// Get latest donations using Charitable_Reports
			$charitable_reports = Charitable_Reports::get_instance();
			/*
			 * Site-local, not UTC: post_date is stored in site-local time, so a
			 * bound built from bare UTC excludes part of today's donations on any
			 * site whose timezone is ahead of UTC.
			 *
			 * @since 1.8.13
			 */
			$args = array(
				'start_date' => gmdate( 'Y/m/d', current_time( 'timestamp', 0 ) - ( 30 * DAY_IN_SECONDS ) ),
				'end_date'   => gmdate( 'Y/m/d', current_time( 'timestamp', 0 ) ),
				'days'       => 30,
			);
			$charitable_reports->init_with_array( 'dashboard', $args );
			$donations = $charitable_reports->get_donations();

			$latest_donations = array();
			$limit = apply_filters( 'charitable_dashboard_latest_donations_amount', 3 );

			if ( ! empty( $donations ) ) {
				// Sort donations by date (newest first)
				usort( $donations, function( $a, $b ) {
					return strtotime( $b->post_date ) - strtotime( $a->post_date );
				});

				// Get valid donation statuses for mapping
				$statuses = charitable_get_valid_donation_statuses();

				$count = 0;
				foreach ( $donations as $donation ) {
					if ( $count >= $limit ) {
						break;
					}

					$donation_id = intval( $donation->donation_id );
					$donor_name = trim( esc_html( $donation->first_name ) . ' ' . esc_html( $donation->last_name ) );
					$payment_method = strtolower( $donation->payment_gateway );
					$donation_status = $donation->donation_status;
					$amount = charitable_format_money( $donation->amount );
					$timestamp = human_time_diff( strtotime( $donation->post_date ), current_time( 'timestamp' ) ) . ' ago';

					// Map status to display format
					$status_label = array_key_exists( $donation_status, $statuses ) ? $statuses[ $donation_status ] : ucwords( $donation_status );

					// Map status for CSS class
					$status_class = $donation_status;
					if ( 'charitable-completed' === $donation_status ) {
						$status_class = 'paid';
					} elseif ( strpos( $donation_status, 'charitable-' ) === 0 ) {
						$status_class = substr( $donation_status, 11 ); // Remove 'charitable-' prefix
					}

					// Determine payment method CSS class
					$method_class = '';
					if ( 'stripe' === $payment_method ) {
						$method_class = 'donation-method-stripe';
					} elseif ( 'paypal' === $payment_method ) {
						$method_class = 'donation-method-paypal';
					}

					// Build donation URL
					$url = admin_url( 'post.php?post=' . $donation_id . '&action=edit' );

					$latest_donations[] = array(
						'id' => $donation_id,
						'url' => $url,
						'donor_name' => $donor_name,
						'timestamp' => $timestamp,
						'amount' => $amount,
						'method' => ucwords( $payment_method ),
						'method_class' => $method_class,
						'status' => $status_class,
						'status_label' => $status_label
					);

					$count++;
				}
			}

			return $latest_donations;
		}

	/**
	 * Render the top donors tab content.
	 *
	 * @since 1.8.8
	 */
	private function render_top_donors_tab() {
		$donors = $this->get_top_donors();
		?>
		<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'top-donors' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="top-donors">
			<?php if ( ! empty( $donors ) ) : ?>
				<div class="charitable-dashboard-v2-table-container">
					<table class="charitable-dashboard-v2-table charitable-dashboard-v2-table-donors">
						<thead>
							<tr>
								<th style="width: 60px;"></th>
								<th style="width: 200px;">Name</th>
								<th style="width: 150px;">Total Donations</th>
								<th style="width: 120px;">Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $donors as $donor ) : ?>
								<tr>
									<td>
										<div class="donor-avatar">
											<img src="<?php echo esc_url( $donor['avatar'] ); ?>" alt="<?php echo esc_attr( $donor['name'] ); ?>" width="40" height="40">
										</div>
									</td>
									<td>
										<div class="donor-details">
											<div class="donor-name">
												<a href="<?php echo esc_url( $donor['url'] ); ?>" class="donor-name-link"><?php echo esc_html( $donor['name'] ); ?></a>
											</div>
											<div class="donor-email">
												<?php if ( ! empty( $donor['email'] ) ) : ?>
													<a href="mailto:<?php echo esc_attr( $donor['email'] ); ?>" class="donor-email-link" title="<?php echo esc_attr( $donor['email'] ); ?>"><?php echo esc_html( $donor['email_display'] ); ?></a>
												<?php else : ?>
													<span class="no-email"><?php esc_html_e( 'No email', 'charitable' ); ?></span>
												<?php endif; ?>
											</div>
										</div>
									</td>
									<td>
										<div class="donor-amount"><?php echo esc_html( $donor['total'] ); ?></div>
									</td>
									<td>
										<span class="status-badge status-<?php echo esc_attr( $donor['type'] ); ?>"><?php echo esc_html( $donor['type_label'] ); ?></span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<?php
					// Determine if there is at least one non-trashed campaign.
					$has_non_trashed_campaign = false;
					foreach ( array( 'campaign', 'charitable_campaign' ) as $campaign_cpt ) {
						$counts = wp_count_posts( $campaign_cpt );
						if ( $counts && is_object( $counts ) ) {
							foreach ( $counts as $status => $num_posts ) {
								if ( 'trash' === $status ) {
									continue;
								}
								if ( (int) $num_posts > 0 ) {
									$has_non_trashed_campaign = true;
									break 2;
								}
							}
						}
					}
				?>
				<div class="charitable-dashboard-v2-empty-state">
					<div class="charitable-dashboard-v2-empty-state-content">
						<h3><?php esc_html_e( 'No Donors Yet!', 'charitable' ); ?></h3>
						<p><?php esc_html_e( 'Start receiving donations to see your top donors here.', 'charitable' ); ?></p>
						<?php if ( $has_non_trashed_campaign ) : ?>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=donation' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Add Donation', 'charitable' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=charitable_campaign' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Create Campaign', 'charitable' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get top donors data.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_top_donors() {
		// TEMPORARY TESTING: Return empty donors if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Get configurable limit (default 10)
		$limit = apply_filters( 'charitable_dashboard_top_donors_limit', 10 );

		// Initialize Charitable_Reports for all-time data (no date filtering)
		$charitable_reports = Charitable_Reports::get_instance();
		$args = array(
			'start_date' => false,
			'end_date'   => false,
			'days'       => false,
		);
		$charitable_reports->init_with_array( 'dashboard', $args );

		// Get donations data first
		$charitable_reports->get_donations();

		// Get top donors data (get more than needed to account for filtering)
		$top_donors = $charitable_reports->get_top_donors_overview( $limit * 2 );

		if ( empty( $top_donors ) || ! is_array( $top_donors ) ) {
			return array();
		}

		$donors = array();
		$unique_donors = array();
		$count = 0;

		foreach ( $top_donors as $donor_data ) {
			// Skip if we've already processed this donor
			if ( in_array( $donor_data->donor_id, $unique_donors, true ) ) {
				continue;
			}

			// Stop if we've reached the limit
			if ( $count >= $limit ) {
				break;
			}

			$unique_donors[] = $donor_data->donor_id;
			$count++;

			// Get donor name
			$donor_name = ! empty( $donor_data->donor_name ) ? $donor_data->donor_name : __( 'Unknown', 'charitable' );

			// Get donor email and truncate if needed
			$donor_email = ! empty( $donor_data->donor_email ) ? $donor_data->donor_email : '';
			$donor_email_display = $donor_email;
			if ( strlen( $donor_email ) > 30 ) {
				$donor_email_display = substr( $donor_email, 0, 30 ) . '...';
			}

			// Get avatar (WordPress default)
			$donor_avatar = ! empty( $donor_data->donor_avatar_url ) ? $donor_data->donor_avatar_url : false;
			if ( false === $donor_avatar && function_exists( 'get_avatar_url' ) && ! empty( $donor_data->donor_email ) ) {
				$donor_avatar = get_avatar_url(
					$donor_data->donor_email,
					array(
						'size' => 40,
					)
				);
			}

			// Determine donor type and badge
			$donation_parent = false;
			if ( ! empty( $donor_data->donation_parent_id ) ) {
				$donation = charitable_get_donation( $donor_data->donation_parent_id );
				$donation_parent = ( $donation && is_object( $donation ) ) ? $donation->get( 'donation_type' ) : false;
			}
			$display_count = ! empty( $donor_data->total_donation_count ) ? intval( $donor_data->total_donation_count ) : 1;

			$badge_css = strtolower( $donation_parent ) === 'recurring' ? 'recurring' : 'one-time';
			$badge_label = strtolower( $donation_parent ) === 'recurring' ? esc_html__( 'Recurring', 'charitable' ) : esc_html__( 'One-Time', 'charitable' );

			// Override for multiple donations
			if ( $display_count > 1 ) {
				$badge_css = 'multiple';
				$badge_label = esc_html__( 'Multiple', 'charitable' );
			}

			// Format total amount
			$display_amount = ! empty( $donor_data->total_donation_amount ) ? charitable_format_money( $donor_data->total_donation_amount, false, true ) : charitable_format_money( $donor_data->amount, false, true );

			$donors[] = array(
				'name' => $donor_name,
				'email' => $donor_email,
				'email_display' => $donor_email_display,
				'avatar' => $donor_avatar,
				'total' => $display_amount,
				'type' => $badge_css,
				'type_label' => $badge_label,
				'url' => admin_url( 'admin.php?page=charitable-donors' )
			);
		}

		return $donors;
	}

		/**
		 * Render the comments tab content.
		 *
		 * @since 1.8.8
		 */
		private function render_comments_tab() {
			?>
			<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'comments' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="comments">
				<?php $this->render_comments_content(); ?>
			</div>
			<?php
		}

		/**
		 * Render comments content based on addon status.
		 *
		 * @since 1.8.8
		 */
		private function render_comments_content() {
			$addon_status = $this->get_donor_comments_addon_status();

			switch ( $addon_status ) {
				case 'active':
					$this->render_comments_table();
					break;
				case 'installed_inactive':
					/*
					Kept separate from the CTA below even though locked_cta() can also return an
					"Activate" state: this branch activates in place over AJAX, which is a better
					answer than sending somebody to plugins.php to find the row themselves.
					*/
					$this->render_addon_inactive_message();
					break;
				/*
				BOTH not-installed cases now take the same renderer. Splitting them by edition was
				the bug: charitable_is_pro() is licence-derived, so "not_installed_lite" included
				licensed sites and sent them to /pricing, while "not_installed_pro" sent paying
				customers to a 404. The CTA resolves the right action from licence and disk state
				instead of from the edition label. @since 1.8.15
				*/
				case 'not_installed_pro':
				case 'not_installed_lite':
				default:
					$this->render_donor_comments_cta();
					break;
			}
		}

		/**
		 * Get donor comments addon status.
		 *
		 * @since 1.8.8
		 * @return string Status: 'active', 'installed_inactive', 'not_installed_pro', 'not_installed_lite'
		 */
		private function get_donor_comments_addon_status() {
			// Check if addon is active
			if ( class_exists( 'Charitable_Donor_Comments' ) ) {
				return 'active';
			}

			// Check if addon is installed but not active
			$plugin_file = 'charitable-donor-comments/charitable-donor-comments.php';
			if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				return charitable_is_pro() ? 'installed_inactive' : 'not_installed_lite';
			}

			// Addon not installed
			return charitable_is_pro() ? 'not_installed_pro' : 'not_installed_lite';
		}

		/**
		 * Render comments table with actual data.
		 *
		 * @since 1.8.8
		 */
		private function render_comments_table() {
			$comments = $this->get_comments();
			?>
			<div class="charitable-dashboard-v2-table-container">
				<table class="charitable-dashboard-v2-table charitable-dashboard-v2-table-comments">
					<thead>
						<tr>
							<th>Comment Details</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $comments ) ) : ?>
							<?php foreach ( $comments as $comment ) : ?>
								<tr data-comment-id="<?php echo esc_attr( $comment['id'] ); ?>">
									<td>
										<div class="comment-details">
											<div class="comment-header">
												<span class="comment-name"><?php echo esc_html( $comment['name'] ); ?></span>
												<span class="comment-text">"<?php echo esc_html( $comment['text'] ); ?>"</span>
											</div>
										</div>
									</td>
									<td>
										<?php if ( 'approved' === $comment['status'] ) : ?>
											<span class="comment-status-approved">Approved</span>
										<?php else : ?>
											<div class="comment-actions">
												<a href="#" class="comment-action approve" data-comment-id="<?php echo esc_attr( $comment['id'] ); ?>">
													<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="#10B981"/>
													</svg>
													<span class="action-text">Approve</span>
													<span class="action-loading" style="display: none;">
														<svg class="charitable-spinner" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
																<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
																<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
															</circle>
														</svg>
														Processing...
													</span>
												</a>
												<span class="comment-action-separator">|</span>
												<a href="#" class="comment-action delete" data-comment-id="<?php echo esc_attr( $comment['id'] ); ?>">
													<img src="/wp-content/plugins/charitable/assets/images/icons/trash.svg" width="12" height="12" alt="Delete">
													<span class="action-text"><?php esc_html_e( 'Delete', 'charitable' ); ?></span>
													<span class="action-loading" style="display: none;">
														<svg class="charitable-spinner" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
																<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
																<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
															</circle>
														</svg>
														Processing...
													</span>
												</a>
											</div>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="2" class="charitable-dashboard-v2-no-comments">
									<p><strong><?php echo esc_html__( 'No donor comments yet.', 'charitable' ); ?></strong></p>
									<p><?php echo esc_html__( 'Donor comments will appear here once donors start leaving messages with their donations.', 'charitable' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Render the Donor Comments call to action, for every state short of the addon running.
		 *
		 * REPLACES render_addon_not_installed_lite_message() and
		 * render_addon_not_installed_pro_message(), both of which sent the reader somewhere wrong.
		 *
		 * The Pro-side one linked to https://wpcharitable.com/addons, which is a 404 - with or
		 * without a trailing slash - so the only action offered to somebody who had already paid was
		 * a dead link. Charitable Pro 1.8.19.5 has the identical bug in its own copy of that method;
		 * this fix is owed there too.
		 *
		 * The Lite-side one worked but asked the wrong question. It sent everyone to /pricing,
		 * including sites whose licence already covers Donor Comments - and because
		 * charitable_is_pro() is LICENCE-derived rather than edition-derived, a Lite install can
		 * hold a perfectly valid licence. Telling that reader to buy what they own is the worst
		 * version of an upsell.
		 *
		 * So the button now comes from charitable_reports_v3_locked_cta(), the four-state helper
		 * 1.8.14 shipped for exactly this, which resolves the question by what is actually missing:
		 *
		 *   licence valid, addon on disk      Activate Donor Comments  -> plugins.php
		 *   licence valid, addon absent       View Your Addons         -> the Addons screen
		 *   licence present but not valid     Renew Your License       -> account
		 *   no licence at all                 Upgrade to Charitable Pro
		 *
		 * Only the last of those is a sale. The first two stay inside wp-admin, which is the whole
		 * point: they are a click, not a purchase.
		 *
		 * Rendered as a CTA rather than a bare "not available" notice, because this tab is otherwise
		 * empty and the old copy led with the absence instead of the reason anyone would want the
		 * feature. The guard is for AJAX: this class is reachable where the reports-v3 file has not
		 * loaded, and a fatal there would take the whole panel down.
		 *
		 * @since 1.8.8
		 * @since 1.8.15 Replaced the two fixed messages with the state-aware CTA.
		 */
		private function render_donor_comments_cta() {

			$charitable_product_url = 'https://www.wpcharitable.com/extensions/charitable-donor-comments/';

			$charitable_cta = function_exists( 'charitable_reports_v3_locked_cta' )
				? charitable_reports_v3_locked_cta(
					array(
						'slug'        => 'charitable-donor-comments',
						'name'        => __( 'Donor Comments', 'charitable' ),
						'product_url' => $charitable_product_url,
						'utm'         => 'dashboard-donor-comments',
					)
				)
				: array(
					/*
					Fallback only for the no-gate-file case above. Deliberately the generic upgrade
					link rather than a guess at licence state, since without that helper there is
					nothing here that can tell the difference.
					*/
					'state'    => 'none',
					'label'    => __( 'Upgrade to Charitable Pro', 'charitable' ),
					'url'      => 'https://www.wpcharitable.com/lite-upgrade/',
					'external' => true,
					'note'     => '',
				);
			$charitable_assets = charitable()->get_path( 'assets', false );

			/*
			The three shipped screenshots, captioned by what each actually shows rather than by a
			guess: the settings screen, the comment field on the donation form, and the donor's own
			Comments tab. They already live in assets/images/education/donor-comments/ - Lite ships
			them for the Settings education page - so this is a second use of an existing asset, not
			a new one.

			WRAPPED IN .charitable-education-page BECAUSE THE STYLES REQUIRE IT. Every rule for these
			classes in charitable-admin.css is scoped to that ancestor, so without the wrapper the
			figures render as three full-size PNGs and blow the panel apart.

			The heading stays an h3 rather than becoming .charitable-education-page-heading h4, which
			carries an ::after that stamps a "Pro" badge on it. That badge is right for a sales page
			and wrong here in two of the three states - on a site whose licence already covers Donor
			Comments the feature is not a Pro teaser, it is something they own and have not installed.

			Both the stylesheet and lity are available on this screen:
			charitable_page_charitable-dashboard is in charitable_get_charitable_screens(), which is
			what gates the enqueue of charitable-admin and the lightbox, so the data-lity anchors
			open rather than navigating away to a .png.
			*/
			?>
			<div class="charitable-dashboard-v2-empty-state charitable-education-page">
				<div class="charitable-dashboard-v2-empty-state-content">
					<h3><?php esc_html_e( 'See What Your Donors Are Saying', 'charitable' ); ?></h3>
					<p><?php esc_html_e( 'Donor Comments lets supporters leave a message with their donation, and collects those messages here so you can read them, reply, and feature the best ones on your campaigns.', 'charitable' ); ?></p>

					<div class="charitable-education-page-media">
						<div class="charitable-education-page-images">
							<?php
							$charitable_shots = array(
								array(
									'file'    => 'education-1.png',
									'caption' => __( 'Comment Settings', 'charitable' ),
									'alt'     => __( 'Donor Comments settings, with the comment prompt donors will see', 'charitable' ),
								),
								array(
									'file'    => 'education-2.png',
									'caption' => __( 'On The Donation Form', 'charitable' ),
									'alt'     => __( 'The Leave a Comment field on a donation form', 'charitable' ),
								),
								array(
									'file'    => 'education-3.png',
									'caption' => __( 'In The Donor Dashboard', 'charitable' ),
									'alt'     => __( 'A donor viewing their own comments and their status', 'charitable' ),
								),
							);

							foreach ( $charitable_shots as $charitable_shot ) :
								$charitable_shot_url = $charitable_assets . 'images/education/donor-comments/' . $charitable_shot['file'];
								?>
								<figure>
									<div class="charitable-education-page-images-image">
										<img src="<?php echo esc_url( $charitable_shot_url ); ?>" alt="<?php echo esc_attr( $charitable_shot['alt'] ); ?>">
										<a href="<?php echo esc_url( $charitable_shot_url ); ?>" class="hover" data-lity="" data-lity-desc="<?php echo esc_attr( $charitable_shot['caption'] ); ?>"></a>
									</div>
									<figcaption><?php echo esc_html( $charitable_shot['caption'] ); ?></figcaption>
								</figure>
								<?php
							endforeach;
							?>
						</div>
					</div>

					<?php if ( ! empty( $charitable_cta['note'] ) ) : ?>
						<p class="charitable-dashboard-v2-empty-state-note"><?php echo esc_html( $charitable_cta['note'] ); ?></p>
					<?php endif; ?>
					<a href="<?php echo esc_url( $charitable_cta['url'] ); ?>"<?php echo ! empty( $charitable_cta['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
						<?php echo esc_html( $charitable_cta['label'] ); ?>
					</a>
					<?php
					/*
					Learn More is shown only when the primary button is a sale. Once the reader is
					entitled to the addon, a second link to the marketing page competes with the
					click that would actually give them the feature.
					*/
					if ( 'none' === $charitable_cta['state'] ) :
						?>
						<a href="<?php echo esc_url( function_exists( 'charitable_utm_link' ) ? charitable_utm_link( $charitable_product_url, 'dashboard-donor-comments', 'Learn More' ) : $charitable_product_url ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-button">
							<?php esc_html_e( 'Learn More', 'charitable' ); ?>
						</a>
						<?php
					endif;
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Render message for addon installed but not activated.
		 *
		 * @since 1.8.8
		 */
		private function render_addon_inactive_message() {
			?>
			<div class="charitable-dashboard-v2-empty-state">
				<div class="charitable-dashboard-v2-empty-state-content">
					<h3><?php esc_html_e( 'Donor Comments Addon Not Activated', 'charitable' ); ?></h3>
					<p><?php esc_html_e( 'The Donor Comments addon is installed but not activated. Click the button below to activate it.', 'charitable' ); ?></p>
					<button id="charitable-activate-donor-comments" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary" data-plugin="charitable-donor-comments/charitable-donor-comments.php">
						<span class="button-text"><?php esc_html_e( 'Activate Addon', 'charitable' ); ?></span>
						<span class="button-loading" style="display: none;">
							<svg class="charitable-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
									<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
									<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
								</circle>
							</svg>
							<?php esc_html_e( 'Activating...', 'charitable' ); ?>
						</span>
					</button>
				</div>
			</div>
			<?php
		}

		/**
		 * Get comments data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_comments() {
		// TEMPORARY TESTING: Return empty comments if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Check if donor comments addon is active
		if ( ! class_exists( 'Charitable_Donor_Comments' ) ) {
			return array();
		}

			// Get comments limit (configurable)
			$limit = apply_filters( 'charitable_dashboard_comments_limit', 10 );

			// Get charitable comments
			$comments = get_comments( array(
				'type'     => 'charitable_comment',
				'status'   => 'all', // Include pending and approved
				'number'   => $limit,
				'orderby'  => 'comment_date',
				'order'    => 'DESC',
				'post_type' => Charitable::CAMPAIGN_POST_TYPE,
			) );

			$formatted_comments = array();

			if ( ! empty( $comments ) ) {
				foreach ( $comments as $comment ) {
					// Get comment author (handle anonymous)
					$author = get_comment_author( $comment );
					if ( empty( $author ) ) {
						$author = __( 'Anonymous', 'charitable' );
					}

					// Get comment text and sanitize it
					$text = get_comment_text( $comment );
					if ( empty( $text ) ) {
						continue; // Skip comments without text
					}

					// Strip HTML tags except for bold and italic, then convert to proper formatting
					$allowed_tags = array(
						'strong' => array(),
						'b' => array(),
						'em' => array(),
						'i' => array(),
					);
					$text = wp_kses( $text, $allowed_tags );

					// Convert HTML tags to proper bold/italic formatting
					$text = str_replace( array( '<strong>', '</strong>', '<b>', '</b>' ), array( '<strong>', '</strong>', '<strong>', '</strong>' ), $text );
					$text = str_replace( array( '<em>', '</em>', '<i>', '</i>' ), array( '<em>', '</em>', '<em>', '</em>' ), $text );

					// Determine status
					$status = $comment->comment_approved ? 'approved' : 'pending';

					$formatted_comments[] = array(
						'id'     => $comment->comment_ID,
						'name'   => $author,
						'text'   => $text,
						'status' => $status,
						'date'   => $comment->comment_date,
					);
				}
			}

			return $formatted_comments;
		}

		/**
		 * AJAX handler for activating addons.
		 *
		 * @since 1.8.8
		 */
		public function ajax_activate_addon() {
			// Verify nonce
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
				wp_die( esc_html( __( 'Security check failed', 'charitable' ) ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to activate plugins.', 'charitable' ) ) );
			}

			$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $plugin ) ) {
				wp_send_json_error( array( 'message' => __( 'Plugin not specified.', 'charitable' ) ) );
			}

			// Check if plugin exists
			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
				wp_send_json_error( array( 'message' => __( 'Plugin file not found.', 'charitable' ) ) );
			}

			// Activate the plugin
			$result = activate_plugin( $plugin );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'Addon activated successfully!', 'charitable' ) ) );
		}


		/**
		 * AJAX handler for approving comments.
		 *
		 * @since 1.8.8
		 */
		public function ajax_approve_comment() {
			// Verify nonce
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
				wp_die( esc_html( __( 'Security check failed', 'charitable' ) ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'moderate_comments' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to moderate comments.', 'charitable' ) ) );
			}

			$comment_id = isset( $_POST['comment_id'] ) ? intval( wp_unslash( $_POST['comment_id'] ) ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $comment_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Comment ID not specified.', 'charitable' ) ) );
			}

			// Check if comment exists
			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				wp_send_json_error( array( 'message' => __( 'Comment not found.', 'charitable' ) ) );
			}

			// Check if it's a charitable comment
			if ( 'charitable_comment' !== $comment->comment_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid comment type.', 'charitable' ) ) );
			}

			// Approve the comment
			$result = wp_set_comment_status( $comment_id, 'approve' );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'Comment approved successfully!', 'charitable' ) ) );
		}

		/**
		 * AJAX handler for deleting comments.
		 *
		 * @since 1.8.8
		 */
		public function ajax_delete_comment() {
			// Verify nonce
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
				wp_die( esc_html( __( 'Security check failed', 'charitable' ) ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'moderate_comments' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to moderate comments.', 'charitable' ) ) );
			}

			$comment_id = isset( $_POST['comment_id'] ) ? intval( wp_unslash( $_POST['comment_id'] ) ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $comment_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Comment ID not specified.', 'charitable' ) ) );
			}

			// Check if comment exists
			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				wp_send_json_error( array( 'message' => __( 'Comment not found.', 'charitable' ) ) );
			}

			// Check if it's a charitable comment
			if ( 'charitable_comment' !== $comment->comment_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid comment type.', 'charitable' ) ) );
			}

			// Delete the comment (force delete)
			$result = wp_delete_comment( $comment_id, true );

			if ( ! $result ) {
				wp_send_json_error( array( 'message' => __( 'Failed to delete comment.', 'charitable' ) ) );
			}

			wp_send_json_success( array( 'message' => __( 'Comment deleted successfully!', 'charitable' ) ) );
		}

		/**
		 * Render the upgrade section.
		 *
		 * @since 1.8.8
		 */
		public function render_upgrade_section() {
			// Don't show upgrade section if user already has Pro license
			if ( charitable_is_pro() ) {
				return;
			}
			?>
			<section class="charitable-dashboard-v2-section">
				<h4 class="charitable-dashboard-v2-upgrade-title"><?php esc_html_e( 'Upgrade to Pro to Unlock Powerful Donation Features', 'charitable' ); ?></h4>
				<div class="charitable-dashboard-v2-upgrade-features">
					<div class="charitable-dashboard-v2-upgrade-column">
						<?php $this->render_upgrade_features_left(); ?>
					</div>
					<div class="charitable-dashboard-v2-upgrade-column">
						<?php $this->render_upgrade_features_right(); ?>
					</div>
				</div>
				<div class="charitable-dashboard-v2-upgrade-actions">
					<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/pricing', 'Dashboard Upgrade Section', 'Upgrade To Pro Button' ) ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-upgrade-button"><?php esc_html_e( 'Upgrade To Pro', 'charitable' ); ?></a>
					<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/pricing', 'Dashboard Upgrade Section', 'Learn More About Features Link' ) ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-learn-more-link"><?php esc_html_e( 'Learn more about all features →', 'charitable' ); ?></a>
				</div>
			</section>
			<?php
		}

		/**
		 * Render left column upgrade features.
		 *
		 * @since 1.8.8
		 */
		private function render_upgrade_features_left() {
			$features = array(
				__( 'Recurring Donations', 'charitable' ),
				__( 'Peer-to-Peer Fundraising', 'charitable' ),
				__( 'Donor Database', 'charitable' ),
				__( 'Donor Dashboard', 'charitable' ),
				__( 'PDF & Annual Receipts', 'charitable' )
			);

			foreach ( $features as $feature ) :
				?>
				<div class="charitable-dashboard-v2-upgrade-feature">
					<svg class="charitable-dashboard-v2-upgrade-checkmark" width="18" height="18" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M9.0688 1.3573C13.798 1.3573 17.6374 5.19676 17.6374 9.92591C17.6374 14.6551 13.798 18.4945 9.0688 18.4945C4.33966 18.4945 0.500198 14.6551 0.500198 9.92591C0.500198 5.19676 4.33966 1.3573 9.0688 1.3573ZM13.5778 7.82078C13.5778 7.67488 13.5197 7.52981 13.4146 7.42562L12.6234 6.63444C12.5192 6.52938 12.3733 6.47118 12.2283 6.47118C12.0824 6.47118 11.9373 6.52938 11.8322 6.63444L8.01537 10.4548L6.30536 8.7396C6.20026 8.6345 6.05523 8.57631 5.90933 8.57631C5.7643 8.57631 5.6184 8.6345 5.51421 8.7396L4.72303 9.53075C4.61793 9.63498 4.55978 9.78088 4.55978 9.92591C4.55978 10.0709 4.61793 10.2168 4.72303 10.3211L7.62021 13.2174C7.72444 13.3225 7.87034 13.3806 8.01537 13.3806C8.16127 13.3806 8.3063 13.3225 8.4114 13.2174L13.4146 8.2159C13.5197 8.11171 13.5778 7.96581 13.5778 7.82078Z" fill="#59A56D"/>
					</svg>
					<span class="charitable-dashboard-v2-upgrade-feature-text"><?php echo esc_html( $feature ); ?></span>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Render right column upgrade features.
		 *
		 * @since 1.8.8
		 */
		private function render_upgrade_features_right() {
			$features = array(
				__( 'Anonymous Giving', 'charitable' ),
				__( 'Fee Coverage Option', 'charitable' ),
				__( 'Email Marketing Integration', 'charitable' ),
				__( 'Built-In Analytics & Reports', 'charitable' ),
				__( 'Flexible Payment Methods', 'charitable' )
			);

			foreach ( $features as $feature ) :
				?>
				<div class="charitable-dashboard-v2-upgrade-feature">
					<svg class="charitable-dashboard-v2-upgrade-checkmark" width="18" height="18" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M9.0688 1.3573C13.798 1.3573 17.6374 5.19676 17.6374 9.92591C17.6374 14.6551 13.798 18.4945 9.0688 18.4945C4.33966 18.4945 0.500198 14.6551 0.500198 9.92591C0.500198 5.19676 4.33966 1.3573 9.0688 1.3573ZM13.5778 7.82078C13.5778 7.67488 13.5197 7.52981 13.4146 7.42562L12.6234 6.63444C12.5192 6.52938 12.3733 6.47118 12.2283 6.47118C12.0824 6.47118 11.9373 6.52938 11.8322 6.63444L8.01537 10.4548L6.30536 8.7396C6.20026 8.6345 6.05523 8.57631 5.90933 8.57631C5.7643 8.57631 5.6184 8.6345 5.51421 8.7396L4.72303 9.53075C4.61793 9.63498 4.55978 9.78088 4.55978 9.92591C4.55978 10.0709 4.61793 10.2168 4.72303 10.3211L7.62021 13.2174C7.72444 13.3225 7.87034 13.3806 8.01537 13.3806C8.16127 13.3806 8.3063 13.3225 8.4114 13.2174L13.4146 8.2159C13.5197 8.11171 13.5778 7.96581 13.5778 7.82078Z" fill="#59A56D"/>
					</svg>
					<span class="charitable-dashboard-v2-upgrade-feature-text"><?php echo esc_html( $feature ); ?></span>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Render the "Free Site Analysis" promo card in the dashboard right column. Links to the
		 * Reports > Site Analysis tab, where the user runs their free Fundraising Score analysis.
		 *
		 * @since 1.8.12
		 *
		 * @return void
		 */
		public function render_site_analysis_section() {
			$charitable_sa_url   = admin_url( 'admin.php?page=charitable-reports&tab=site-analysis' );
			$charitable_sa_nonce = wp_create_nonce( 'charitable_site_analysis_click' );
			?>
			<style>
			/* This card puts content directly in section-content, which has no horizontal padding by
			   default; restore the standard section padding for this card only. */
			#charitable-dashboard-v2-site-analysis .charitable-dashboard-v2-section-content { padding: 0 20px 20px; }
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-new {
				display: inline-block; margin-left: 8px; vertical-align: middle;
				background: #31714c; color: #fff; font-size: 10px; font-weight: 700;
				line-height: 1; letter-spacing: .04em; text-transform: uppercase;
				padding: 3px 7px; border-radius: 3px;
			}
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-body { display: flex; align-items: flex-start; gap: 14px; }
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-icon {
				flex: none; width: 40px; height: 40px; border-radius: 8px;
				display: flex; align-items: center; justify-content: center; background: #eaf4e8; color: #31714c;
			}
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-text { margin: 0 0 12px; color: #50575e; font-size: 13.5px; line-height: 1.5; }
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-button { background-color: #31714c !important; border-color: #31714c !important; color: #fff !important; text-shadow: none; }
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-button:hover,
			#charitable-dashboard-v2-site-analysis .charitable-sa-dash-button:focus { background-color: #2a6041 !important; border-color: #2a6041 !important; color: #fff !important; }
			</style>

			<section id="charitable-dashboard-v2-site-analysis" class="charitable-dashboard-v2-section">
				<header class="charitable-dashboard-v2-section-header">
					<h3>
						<?php esc_html_e( 'Free Site Analysis', 'charitable' ); ?>
						<span class="charitable-sa-dash-new"><?php esc_html_e( 'New', 'charitable' ); ?></span>
					</h3>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<div class="charitable-sa-dash-body">
						<span class="charitable-sa-dash-icon"><span class="dashicons dashicons-chart-area" aria-hidden="true"></span></span>
						<div>
							<p class="charitable-sa-dash-text">
								<?php esc_html_e( 'See how your fundraising setup measures up and get specific, prioritized steps to raise more. Free to run, based on your current Charitable configuration.', 'charitable' ); ?>
							</p>
							<a href="<?php echo esc_url( $charitable_sa_url ); ?>" class="button button-primary charitable-sa-dash-button">
								<?php esc_html_e( 'Run Your Free Analysis', 'charitable' ); ?>
							</a>
						</div>
					</div>
				</div>
			</section>
			<script>
			/* Record the click via Charitable's consent-gated usage tracking (sendBeacon so it fires
			   before the link navigates away). No effect if the user hasn't opted into usage tracking. */
			( function () {
				var link = document.querySelector( '#charitable-dashboard-v2-site-analysis .charitable-sa-dash-button' );
				if ( ! link || ! navigator.sendBeacon ) { return; }
				link.addEventListener( 'click', function () {
					try {
						var fd = new FormData();
						fd.append( 'action', 'charitable_track_site_analysis_click' );
						fd.append( 'nonce', '<?php echo esc_js( $charitable_sa_nonce ); ?>' );
						navigator.sendBeacon( '<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>', fd );
					} catch ( e ) {}
				} );
			} )();
			</script>
			<?php
		}

		/**
		 * AJAX: record a click on the dashboard "Run Your Free Analysis" button via the same
		 * consent-gated usage-tracking channel as the recommendation cards. Lands in the
		 * charitable_recommended_plugins_clicked option, which Charitable_Tracking reports to
		 * awesomemotive as recommended_plugins_clicked (filter by context 'dashboard_site_analysis').
		 *
		 * @since 1.8.12
		 *
		 * @return void
		 */
		public function ajax_track_site_analysis_click() {
			if ( ! check_ajax_referer( 'charitable_site_analysis_click', 'nonce', false ) || ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error();
			}
			$this->track_recommendation_click( 'clicked', 'site-analysis', 'feature', 'dashboard_site_analysis' );
			wp_send_json_success();
		}

		/**
		 * Render the enhance campaign section.
		 *
		 * @since 1.8.8
		 */
		public function render_enhance_campaign_section() {
			// Hide section if no items to show
			if ( ! $this->should_show_enhance_section() ) {
				return;
			}

			?>

			<style>
			.charitable-dashboard-v2-setup-button {
				background-color: #28a745 !important;
				color: white !important;
				border-color: #28a745 !important;
			}
			.charitable-dashboard-v2-setup-button:hover {
				background-color: #218838 !important;
				border-color: #1e7e34 !important;
			}
			.charitable-dashboard-v2-activate-button:disabled {
				background-color: #6c757d !important;
				color: #fff !important;
				border-color: #6c757d !important;
				opacity: 0.65 !important;
				cursor: not-allowed !important;
			}
			.charitable-dashboard-v2-upgrade-button {
				background-color: #E38632 !important;
				color: white !important;
				border-color: #E38632 !important;
			}
			.charitable-dashboard-v2-upgrade-button:hover {
				background-color: #E38632 !important;
				border-color: #E38632 !important;
			}
			.charitable-dashboard-v2-installed-button {
				background-color: #6c757d !important;
				color: #fff !important;
				border-color: #6c757d !important;
				cursor: not-allowed !important;
			}

			/* Hide all text in stats row initially */
			.charitable-dashboard-v2-stats-row .charitable-dashboard-v2-stat-title,
			.charitable-dashboard-v2-stats-row .charitable-dashboard-v2-stat-amount {
				visibility: hidden;
			}

			/* Show text when loading is complete */
			.charitable-dashboard-v2-stats-row.charitable-dashboard-stats-loaded .charitable-dashboard-v2-stat-title,
			.charitable-dashboard-v2-stats-row.charitable-dashboard-stats-loaded .charitable-dashboard-v2-stat-amount {
				visibility: visible;
			}
			</style>

			<section id="charitable-dashboard-v2-enhance-campaign" class="charitable-dashboard-v2-section">
				<header class="charitable-dashboard-v2-section-header">
					<h3><?php esc_html_e( 'Enhance Your Campaign', 'charitable' ); ?></h3>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<div class="charitable-dashboard-v2-enhance-grid">
						<?php $this->render_enhance_grid_items(); ?>
					</div>
					<footer class="charitable-dashboard-v2-enhance-campaign-footer">
						<div class="charitable-dashboard-v2-enhance-campaign-footer-content">
							<div class="charitable-dashboard-v2-enhance-campaign-footer-left">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-addons' ) ); ?>" class="charitable-dashboard-v2-addons-link">
									<span class="charitable-dashboard-v2-addons-text"><?php esc_html_e( 'View All Addons', 'charitable' ); ?></span>
									<svg class="charitable-dashboard-v2-arrow-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</div>
						</div>
					</footer>
				</div>
			</section>
			<?php
		}

		/**
		 * Render enhance grid items.
		 *
		 * @since 1.8.8
		 */
		private function render_enhance_grid_items() {
			$items = $this->get_enhance_items();

			foreach ( $items as $item ) :
			$button_attributes = '';
			if ( isset( $item['button_action'] ) ) {
				$button_attributes = 'data-action="' . esc_attr( $item['button_action'] ) . '"';
				$button_attributes .= ' data-slug="' . esc_attr( $item['slug'] ) . '"';
				$button_attributes .= ' data-type="' . esc_attr( $item['type'] ) . '"';

				// Add basename for third-party plugins and charitable addons
				if ( isset( $item['basename'] ) ) {
					$button_attributes .= ' data-basename="' . esc_attr( $item['basename'] ) . '"';
				}

				// Add setup URL for third-party plugins (always available for setup state)
				if ( $item['type'] === 'third_party' && isset( $item['setup_url'] ) ) {
					$button_attributes .= ' data-setup-url="' . esc_url( $item['setup_url'] ) . '"';
				}

				// Add upgrade URL for charitable addons
				if ( $item['type'] === 'charitable_addon' && isset( $item['upgrade_url'] ) ) {
					$button_attributes .= ' data-upgrade-url="' . esc_url( $item['upgrade_url'] ) . '"';
				}

				// Add disabled state
				if ( isset( $item['button_disabled'] ) && $item['button_disabled'] ) {
					$button_attributes .= ' disabled';
				}
			}
				?>
				<div class="charitable-dashboard-v2-enhance-grid-item">
					<div class="charitable-dashboard-v2-enhance-grid-content">
						<div class="charitable-dashboard-v2-enhance-grid-header">
							<div class="charitable-dashboard-v2-enhance-grid-icon">
								<?php
								// Allow SVG icons through as trusted raw output.
								if ( isset( $item['icon'] ) && is_string( $item['icon'] ) ) {
									echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is trusted as curated/provided internally.
								}
								?>
							</div>
							<h4 class="charitable-dashboard-v2-enhance-grid-title"><?php echo esc_html( $item['title'] ); ?></h4>
						</div>
						<p class="charitable-dashboard-v2-enhance-grid-description"><?php echo esc_html( $item['description'] ); ?></p>
						<button class="charitable-dashboard-v2-enhance-grid-button <?php echo esc_attr( $item['button_class'] ); ?>" <?php echo wp_kses_post( $button_attributes ); ?>><?php echo esc_html( $item['button_text'] ); ?></button>
					</div>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Get enhance items data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
		private function get_enhance_items() {
		// TODO: Future caching implementation
		// Consider caching plugin states to avoid repeated get_plugins() calls
		// Cache key: 'charitable_dashboard_plugin_states'
		// Cache duration: 1 hour or until plugin state changes
		// Invalidate cache when: plugins installed/activated/deactivated

		$items = array();
		$third_party_plugins = $this->get_curated_third_party_plugins();
		$charitable_addons = $this->get_curated_charitable_addons();

		// Get third-party plugin data
		$all_plugins = $this->get_third_party_plugin_data();

		// Get first 3 available third-party plugins
		$available_third_party = 0;
		foreach ( $third_party_plugins as $slug ) {
			if ( $available_third_party >= 3 ) {
				break;
			}

			if ( isset( $all_plugins[ $slug ] ) ) {
				$plugin_data = $all_plugins[ $slug ];
				if ( ! $plugin_data['active'] ) { // Show if not active (installed or not)
					$items[] = $this->format_plugin_item( $plugin_data, 'third_party' );
					$available_third_party++;
				}
			}
		}

		// Fill remaining slots with Charitable addons
		$remaining_slots = 4 - count( $items );
		$available_charitable = 0;

		foreach ( $charitable_addons as $slug ) {
			if ( $available_charitable >= $remaining_slots ) {
				break;
			}

			$addon_data = $this->get_charitable_addon_data( $slug );

			if ( $addon_data && ! ( $addon_data['installed'] && $addon_data['active'] ) ) {
				$items[] = $this->format_addon_item( $addon_data );
				$available_charitable++;
			}
		}

		return $items;
	}

	/**
	 * AJAX handler for installing Charitable addons.
	 *
	 * @since 1.8.8
	 */
	public function ajax_install_charitable_addon() {
		// Security check
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Permission check
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
			return;
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( empty( $slug ) ) {
			wp_send_json_error( 'Addon slug is required.' );
			return;
		}

		// Get addon data
		$addon_data = $this->get_charitable_addon_data( $slug );
		if ( ! $addon_data ) {
			wp_send_json_error( 'Addon not found.' );
			return;
		}

		// Check if already installed
		if ( $addon_data['installed'] ) {
			wp_send_json_error( 'Addon is already installed.' );
			return;
		}

		// Check if download URL is available
		if ( ! isset( $addon_data['download_url'] ) || empty( $addon_data['download_url'] ) ) {
			wp_send_json_error( 'Download URL not available for this addon.' );
			return;
		}

		// Download and install the addon
		$result = $this->install_charitable_addon_from_url( $addon_data['download_url'], $slug );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
			return;
		}

		wp_send_json_success( array(
			'message' => __( 'Addon installed successfully.', 'charitable' ),
			'button_text' => __( 'Activate', 'charitable' ),
			'button_class' => 'charitable-dashboard-v2-activate-button',
			'button_action' => 'activate_addon'
		) );
	}

	/**
	 * AJAX handler for activating Charitable addons.
	 *
	 * @since 1.8.8
	 */
	public function ajax_activate_charitable_addon() {
		// Security check
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Permission check
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
			return;
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( empty( $slug ) ) {
			wp_send_json_error( 'Addon slug is required.' );
			return;
		}

		// Get addon data
		$addon_data = $this->get_charitable_addon_data( $slug );
		if ( ! $addon_data ) {
			wp_send_json_error( 'Addon not found.' );
			return;
		}

		// Check if installed
		if ( ! $addon_data['installed'] ) {
			wp_send_json_error( 'Addon is not installed.' );
			return;
		}

		// Check if already active
		if ( $addon_data['active'] ) {
			wp_send_json_error( 'Addon is already active.' );
			return;
		}

		// Activate the plugin
		$result = activate_plugin( $addon_data['basename'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( 'Failed to activate addon: ' . $result->get_error_message() );
			return;
		}

		wp_send_json_success( array(
			'message' => __( 'Addon activated successfully.', 'charitable' ),
			'button_text' => __( 'Installed', 'charitable' ),
			'button_class' => 'charitable-dashboard-v2-installed-button',
			'button_action' => 'installed',
			'button_disabled' => true
		) );
	}

	/**
	 * Get curated third-party plugins list.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_curated_third_party_plugins() {
			return array(
			'monsterinsights',      // Analytics
			'wpforms-lite',         // Forms
			'aioseo',              // SEO
			'duplicator',          // Backup
			'pushengage',          // Notifications
			'uncanny-automator',   // Automation
			'envira-gallery',      // Gallery
			// Add more as you review
		);
	}

	/**
	 * Get custom SVG icons for plugins.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_custom_plugin_icons() {
		return array(
			'pushengage' => '<svg width="106" height="83" viewBox="0 0 106 83" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M102 40.4611C101.856 54.0439 97.8267 64.8812 89.768 74.1291C88.3289 75.863 86.6021 77.4525 84.8752 78.8975C83.2922 80.3425 80.9897 80.3425 79.6946 78.8975C78.3994 77.308 78.6872 75.1405 80.4141 73.6956C84.0117 70.6611 86.8899 67.1932 89.1924 63.1472C92.6461 57.2228 94.6608 50.8649 94.9486 43.929C95.5242 31.9357 91.7827 21.6763 83.8678 12.862C82.7166 11.706 81.4214 10.55 80.2702 9.39403C78.6872 7.94905 78.3994 5.63709 79.6946 4.19211C80.9897 2.60263 83.1483 2.60263 84.8752 4.19211C94.0852 12.1395 99.5536 22.2543 101.568 34.2477C101.856 36.7041 101.856 39.1606 102 40.4611Z" fill="#3B43FF"/><path d="M4 41.617C4.28781 26.5893 9.90015 14.018 21.2687 4.04761C22.8517 2.60263 25.1542 2.74713 26.3054 4.19211C27.6006 5.63709 27.3128 7.94905 25.7298 9.24953C17.9589 16.0409 13.0661 24.4218 11.4831 34.6811C9.32452 48.1194 12.9222 59.9683 21.9883 70.0831C23.1395 71.3836 24.5786 72.6841 25.8737 73.8401C27.4567 75.285 27.6006 77.4525 26.3054 78.8975C25.0103 80.3425 22.8517 80.3425 21.2687 79.042C11.1953 70.3721 5.43906 59.2458 4.28781 45.952C4.28781 45.2295 4.14391 44.507 4.14391 43.7845C4 43.062 4 42.3395 4 41.617Z" fill="#3B43FF"/><path d="M17.9589 41.4725C18.2467 30.7797 22.1322 21.8208 30.3348 14.7404C32.0617 13.2955 33.9325 13.44 35.3715 15.0294C36.6667 16.4744 36.5228 18.3529 34.7959 19.9424C30.9104 23.5548 27.7445 27.7453 26.1615 32.9472C23.1395 43.4955 25.2981 52.8879 32.7812 60.9798C33.5007 61.8467 34.3642 62.5692 35.0837 63.4362C36.3789 64.7367 36.5228 66.7597 35.3715 68.0602C34.2203 69.5051 31.9178 69.7941 30.6226 68.4936C28.464 66.4707 26.4493 64.4477 24.5786 62.1357C20.6931 56.9338 18.5345 51.0094 18.1028 44.3625C18.1028 43.929 17.9589 43.4955 17.9589 42.9175C17.9589 42.484 17.9589 42.0505 17.9589 41.4725Z" fill="#3B43FF"/><path d="M88.0411 41.617C87.7533 52.0209 83.8678 60.8353 76.0969 67.9157C74.9457 69.0716 73.5066 69.6496 71.9236 69.0716C69.6211 68.0602 69.0455 65.4592 70.7724 63.4362C72.7871 61.1243 75.0896 58.9568 76.8164 56.3558C83.7239 45.663 82.141 31.0687 73.0749 21.9653C72.3554 21.2429 71.6358 20.5204 70.9163 19.7979C69.6211 18.4974 69.4772 16.4744 70.6285 15.1739C71.9236 13.729 73.9383 13.44 75.5213 14.596C78.3994 16.9079 80.8458 19.6534 82.7166 22.8323C85.7386 27.7453 87.6094 33.0917 87.8972 38.8716C87.8972 39.7386 87.8972 40.6056 88.0411 41.617Z" fill="#3B43FF"/><path d="M73.7944 40.7501C73.7944 47.975 71.348 53.0324 66.743 57.3673C65.1601 58.9568 62.8576 58.9568 61.5624 57.3673C60.2673 55.9223 60.2673 53.7549 61.9941 52.3099C64.8722 49.5644 66.743 46.241 66.8869 42.195C67.0308 38.2936 65.8796 34.9701 63.2893 32.0802C62.8576 31.5022 62.2819 31.0687 61.8502 30.6352C60.4112 29.1902 60.2673 27.1673 61.5624 25.7223C62.8576 24.2773 65.0162 24.1328 66.5991 25.5778C69.0455 27.7453 70.9163 30.3462 72.2115 33.3807C73.3627 35.9816 73.9383 38.7271 73.7944 40.7501Z" fill="#3B43FF"/><path d="M41.2717 24.5663C43.2863 24.5663 44.2937 25.2888 45.0132 26.7338C45.7327 28.1787 45.301 29.6237 44.1498 30.6352C42.279 32.5137 40.696 34.3922 39.8326 36.9931C38.1057 42.484 39.257 47.2525 42.9985 51.4429C43.2863 51.8764 43.7181 52.1654 44.1498 52.5989C45.5888 54.1884 45.7327 56.2113 44.2937 57.6563C42.9985 58.9568 40.8399 59.1013 39.4009 57.8008C35.8032 54.6219 33.3568 50.8649 32.6373 46.0965C31.1982 38.2936 33.3568 31.5022 39.1131 26.1558C39.8326 25.1443 40.8399 24.8553 41.2717 24.5663Z" fill="#3B43FF"/><path d="M52.7841 48.4084C48.8987 48.4084 45.8767 45.374 45.8767 41.4725C45.8767 37.5711 49.0426 34.5367 52.928 34.5367C56.8135 34.5367 59.8355 37.5711 59.8355 41.4725C59.8355 45.5185 56.8135 48.4084 52.7841 48.4084Z" fill="#3B43FF"/></svg>',
			'all-in-one-seo-pack' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M10 20C15.524 20 20 15.524 20 10C20 4.47599 15.524 0 10 0C4.47599 0 0 4.47599 0 10C0 15.524 4.47599 20 10 20ZM8.40919 3.6618C8.27557 3.4572 8.02088 3.35699 7.79123 3.4405C7.52818 3.53236 7.27349 3.64092 7.02296 3.76618C6.80585 3.87474 6.69311 4.12526 6.74322 4.36743L6.91441 5.23591C6.96033 5.4572 6.86848 5.68685 6.69311 5.82881C6.41754 6.05846 6.16701 6.31315 5.94155 6.59708C5.79958 6.77244 5.57829 6.86848 5.36117 6.82255L4.50939 6.65136C4.2714 6.60125 4.02505 6.71399 3.92067 6.93946C3.86221 7.06472 3.80376 7.19415 3.74948 7.32777C3.6952 7.46138 3.64927 7.59081 3.60334 7.72443C3.52401 7.95825 3.62004 8.21712 3.82463 8.35491L4.54697 8.84342C4.73486 8.96869 4.82672 9.19415 4.80167 9.4238C4.76409 9.78706 4.76827 10.1545 4.80585 10.5094C4.8309 10.7349 4.73904 10.9603 4.55115 11.0898L3.82881 11.5825C3.62839 11.7203 3.53236 11.9749 3.61169 12.2129C3.70355 12.4802 3.80793 12.7432 3.93319 12.9979C4.04175 13.2192 4.28393 13.3319 4.52192 13.2818L5.37369 13.1065C5.59081 13.0605 5.81628 13.1524 5.95407 13.3319C6.17537 13.6117 6.43006 13.8706 6.70981 14.1002C6.88518 14.2422 6.97704 14.4718 6.93111 14.6931L6.76409 15.5616C6.71816 15.8038 6.82672 16.0543 7.04802 16.1628C7.17328 16.2255 7.29854 16.2839 7.42797 16.3382C7.55741 16.3925 7.68685 16.4426 7.81628 16.4885C8.19624 16.6221 8.73069 16.1461 9.12317 15.7912C9.31942 15.6159 9.43633 15.3695 9.4405 15.1065V15.1023V13.6493C9.4405 13.6326 9.4405 13.62 9.4405 13.6033C8.27557 13.3194 7.41127 12.2505 7.41127 10.977V9.43633C7.41127 9.31942 7.50313 9.22338 7.62004 9.22338H8.34238V7.70355C8.34238 7.49478 8.50939 7.32359 8.71399 7.32359C8.91858 7.32359 9.0856 7.49478 9.0856 7.70355V9.21921H11.0355V7.70355C11.0355 7.49478 11.2025 7.32359 11.4071 7.32359C11.6117 7.32359 11.7787 7.49478 11.7787 7.70355V9.21921H12.501C12.6138 9.21921 12.7098 9.31524 12.7098 9.43215V10.9729C12.7098 12.2881 11.7871 13.382 10.5679 13.6242C10.5679 13.6326 10.5679 13.6367 10.5679 13.6451V15.0898C10.5679 15.357 10.6931 15.6075 10.8935 15.7829C11.2944 16.1336 11.8372 16.6054 12.2171 16.4718C12.4802 16.38 12.7349 16.2714 12.9854 16.1461C13.2025 16.0376 13.3152 15.7871 13.2651 15.5449L13.0939 14.6764C13.048 14.4551 13.1399 14.2255 13.3152 14.0835C13.5866 13.8539 13.8413 13.5992 14.0668 13.3152C14.2088 13.1399 14.4301 13.0438 14.6472 13.0898L15.499 13.261C15.737 13.3111 15.9833 13.1983 16.0877 12.9729C16.1461 12.8476 16.2046 12.7182 16.2589 12.5846C16.3132 12.4509 16.3591 12.3215 16.405 12.1879C16.4843 11.9541 16.3883 11.6952 16.1837 11.5574L15.4614 11.0689C15.2735 10.9436 15.1816 10.7182 15.2067 10.4885C15.2443 10.1253 15.2401 9.75783 15.2025 9.40292C15.1775 9.17745 15.2693 8.95198 15.4572 8.82255L16.1795 8.32985C16.38 8.19207 16.476 7.93737 16.3967 7.69937C16.3048 7.43215 16.2004 7.1691 16.0752 6.91441C15.9666 6.69311 15.7244 6.58038 15.4864 6.63048L14.6347 6.80585C14.4175 6.85177 14.1921 6.75992 14.0543 6.58038C13.833 6.30063 13.5783 6.04175 13.2985 5.81211C13.1232 5.67015 13.0313 5.4405 13.0772 5.21921L13.2443 4.35073C13.2902 4.10856 13.1816 3.85804 12.9603 3.74948C12.8351 3.68685 12.7098 3.62839 12.5804 3.57411C12.4509 3.51983 12.3215 3.46973 12.1921 3.4238C11.9624 3.34447 11.7119 3.4405 11.5741 3.64927L11.0939 4.38831C10.9687 4.5762 10.7474 4.67223 10.5261 4.65136C10.167 4.61378 9.81211 4.61795 9.46138 4.65553C9.24008 4.68058 9.01879 4.58455 8.89353 4.39666L8.40919 3.6618Z" fill="#005AE0"/>
			</svg>',
			'duplicator' => '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M46.2041 20.0816H44.8571C44.7347 20.0816 44.6122 20.0408 44.449 20.0408C43.9184 19.9184 43.4286 19.7143 42.9388 19.4694C42.8163 19.3878 42.6939 19.3469 42.6122 19.2653C42.5714 19.2245 42.4898 19.1837 42.449 19.1429C42.3673 19.102 42.3265 19.0612 42.2449 18.9796C42.0408 18.8163 41.8775 18.6531 41.6735 18.449C41.6735 18.449 41.6735 18.449 41.6327 18.4082C41.5102 18.2857 41.4286 18.1633 41.3469 18.0408C41.2245 17.8776 41.1429 17.7143 41.0204 17.551C40.898 17.3061 40.7755 17.0612 40.6531 16.7755C40.6122 16.6122 40.5306 16.449 40.4898 16.2857C40.449 16.1633 40.449 16.0408 40.4082 15.9592C40.449 15.8367 40.4082 15.6735 40.4082 15.5102C40.4082 15.4694 40.4082 15.4286 40.4082 15.3878C40.4082 14.898 40.449 14.449 40.5714 14C40.7755 13.3061 41.102 12.6531 41.4694 12.0408C41.551 11.9592 41.6327 11.8776 41.6735 11.7551L42.4082 10.9796C43.0204 10.3673 43.0612 9.34694 42.4898 8.69388C41.7551 7.79592 40.9388 6.93878 40.0408 6.16327L15.3061 30.5714C14.898 30.9388 14.2449 30.6939 14.2449 30.1224C14.2857 25.8367 14.3673 18.5306 14.4082 14.8571C14.4082 14.2449 15.2245 14.0408 15.551 14.5714L18.1224 19.0612C18.3265 19.4286 18.8163 19.4694 19.102 19.1837L30.5714 7.71429C30.5714 7.71429 30.5306 7.71428 30.5306 7.67347C30.3673 7.59184 30.2449 7.5102 30.0816 7.42857C30 7.34694 29.8776 7.30612 29.7959 7.22449C29.6735 7.14286 29.551 7.02041 29.4286 6.93878C29.3878 6.89796 29.3878 6.89796 29.3469 6.85714C28.9796 6.4898 28.7347 6.12245 28.4898 5.71429C28.1224 5.02041 27.8776 4.28571 27.7551 3.55102C27.7143 3.42857 27.7143 3.26531 27.6735 3.14286C27.6735 3.06122 27.6735 2.97959 27.6735 2.89796V1.83673C27.6735 0.897959 26.9796 0.163265 26.0816 0.0816327C25.3878 0.0408163 24.6939 0 24 0C23.3469 0 22.6531 0.0408163 22 0.0816327C21.102 0.163265 20.4082 0.897959 20.4082 1.79592C20.4082 2.32653 20.3673 2.93878 20.2449 3.55102C20.1224 4.28571 19.8776 5.02041 19.5102 5.71429C19.3061 6.12245 19.0204 6.4898 18.6531 6.81633C18.6122 6.85714 18.6122 6.85714 18.5714 6.89796C18.449 7.02041 18.3265 7.10204 18.2041 7.18367C18.1224 7.26531 18 7.30612 17.9184 7.38775C17.7551 7.46939 17.6327 7.55102 17.4694 7.63265C17.2245 7.7551 16.9388 7.87755 16.6939 7.91837C16.4898 8 16.3265 8.04082 16.1224 8.04082C15.9592 8.08163 15.8367 8.08163 15.6735 8.08163H15.6327C15.3878 8.08163 15.102 8.08163 14.8571 8.08163C14.7755 8.08163 14.6939 8.08163 14.6122 8.04082C14.5306 8.04082 14.449 8 14.4082 8C14.2857 7.95918 14.1633 7.95918 14.0408 7.91837C13.5102 7.7551 13.0204 7.55102 12.5714 7.30612C12.3265 7.18367 12.1224 7.02041 11.9184 6.85714C11.8367 6.77551 11.7551 6.73469 11.6735 6.65306L10.8571 5.79592C10.2449 5.14286 9.18367 5.06122 8.4898 5.63265C7.38775 6.57143 6.36735 7.59184 5.46939 8.69388C4.89796 9.38775 4.97959 10.3673 5.55102 10.9796L6.28571 11.7551C6.36735 11.8367 6.44898 11.9184 6.4898 12.0408C6.85714 12.6122 7.22449 13.3061 7.38775 14C7.5102 14.449 7.55102 14.898 7.55102 15.3878C7.55102 15.4286 7.55102 15.4694 7.55102 15.5102C7.55102 15.6735 7.5102 15.7959 7.5102 15.9592C7.5102 16.0816 7.46939 16.2041 7.42857 16.2857C7.38775 16.449 7.34694 16.6122 7.26531 16.7755C7.18367 17.0612 7.06122 17.3061 6.89796 17.551C6.81633 17.7143 6.69388 17.8776 6.57143 18.0408C6.4898 18.1633 6.36735 18.2857 6.28571 18.4082C6.28571 18.4082 6.28571 18.4082 6.2449 18.449C6.08163 18.6531 5.87755 18.8163 5.67347 18.9796C5.59184 19.0204 5.55102 19.102 5.46939 19.1429C5.42857 19.1837 5.34694 19.2245 5.30612 19.2653C5.18367 19.3469 5.06122 19.3878 4.97959 19.4694C4.4898 19.7143 4 19.9184 3.46939 20.0408C3.34694 20.0408 3.22449 20.0816 3.06122 20.0816H1.71429C0.857143 20.0816 0.0816327 20.7347 0 21.6327C0.0408163 22.4082 0 23.2245 0 24C0 24.6531 0.0408163 25.3061 0.0816327 25.9592C0.163265 26.8571 0.857143 27.551 1.7551 27.551L2.97959 27.5918C3.10204 27.5918 3.22449 27.5918 3.34694 27.6327C3.83673 27.7551 4.28571 27.9184 4.73469 28.1224C5.14286 28.3265 5.5102 28.6122 5.87755 28.9388C5.91837 28.9796 5.91837 28.9796 5.95918 29.0204C6.08163 29.1429 6.16327 29.2653 6.2449 29.3469C6.32653 29.4286 6.40816 29.5102 6.44898 29.6327C6.53061 29.7959 6.65306 29.9184 6.73469 30.0816C6.85714 30.3265 6.97959 30.5714 7.06122 30.8571C7.14286 31.0612 7.18367 31.2245 7.22449 31.4286C7.26531 31.5918 7.26531 31.7143 7.30612 31.8775V31.9184C7.34694 32.1633 7.34694 32.449 7.30612 32.6939C7.30612 32.7755 7.30612 32.8571 7.30612 32.9388C7.30612 33.0204 7.30612 33.102 7.26531 33.1429C7.22449 33.2653 7.22449 33.3878 7.18367 33.5102C7.06122 34.0408 6.85714 34.5306 6.61225 34.9796C6.53061 35.1429 6.44898 35.3061 6.32653 35.4286C6.2449 35.5102 6.20408 35.6327 6.08163 35.7143L5.30612 36.5714C4.69388 37.1837 4.61225 38.1633 5.14286 38.8571C6 39.9592 6.97959 40.9796 8 41.8775L33.1429 17.1837C33.551 16.8163 34.2041 17.0612 34.2041 17.6327C34.1633 21.9184 34.0816 29.2245 34.0408 32.898C34.0408 33.5102 33.2245 33.7143 32.898 33.1837L30.3265 28.6939C30.1224 28.3265 29.6327 28.2857 29.3469 28.5714L17.3878 40.5306C17.3878 40.5306 17.4286 40.5306 17.4286 40.5714C17.551 40.6531 17.6735 40.7347 17.7959 40.8571C17.7959 40.8571 17.7959 40.8571 17.8367 40.898C18.0408 41.0612 18.2041 41.2245 18.3673 41.4286C18.4082 41.5102 18.4898 41.551 18.5306 41.6327C18.5714 41.6735 18.6122 41.7551 18.6531 41.7959C18.7347 41.9184 18.7755 42 18.8571 42.1224C19.1429 42.5714 19.3469 43.0612 19.4694 43.5918C19.7143 44.4898 19.7959 45.3878 19.7959 46.1633C19.7959 47.0612 20.4898 47.7959 21.3469 47.8776C22.2041 47.9592 23.102 48.0408 24 48.0408C24.898 48.0408 25.7551 48 26.6531 47.8776C27.5102 47.7959 28.2041 47.0612 28.2041 46.1633C28.2041 45.3878 28.2857 44.449 28.5306 43.5918C28.6531 43.102 28.8571 42.6122 29.1429 42.1224C29.2245 42 29.2653 41.9184 29.3469 41.7959C29.3878 41.7551 29.4286 41.6735 29.4694 41.6327C29.5102 41.551 29.5918 41.5102 29.6327 41.4286C29.7959 41.2245 29.9592 41.0612 30.1633 40.898C30.1633 40.898 30.1633 40.898 30.2041 40.8571C30.3265 40.7755 30.449 40.6531 30.5714 40.5714C30.7347 40.449 30.898 40.3673 31.102 40.2857C31.3469 40.1633 31.5918 40.0408 31.8775 39.9592C32.0408 39.9184 32.2041 39.8775 32.3673 39.8367C32.4898 39.7959 32.6122 39.7959 32.7347 39.7959C32.898 39.7959 33.0204 39.7551 33.1837 39.7551C33.2245 39.7551 33.2653 39.7551 33.3061 39.7551C33.7959 39.7551 34.2449 39.8367 34.6939 39.9592C35.3061 40.1633 35.8775 40.449 36.4082 40.8163C36.4898 40.898 36.6122 40.9796 36.6939 41.0612L37.551 41.9592C38.2041 42.6122 39.2245 42.6531 39.9184 42.0816C41.0204 41.102 42 40.0816 42.898 38.9388C43.4286 38.2449 43.3469 37.2653 42.7347 36.6531L41.9592 35.8776C41.8776 35.7959 41.7959 35.7143 41.7143 35.5918C41.6326 35.4286 41.5102 35.3061 41.4286 35.1429C41.1837 34.6939 40.9796 34.2041 40.8571 33.6735C40.8163 33.551 40.7755 33.4286 40.7755 33.3061C40.7755 33.2245 40.7347 33.1429 40.7347 33.102C40.7347 33.0204 40.7347 32.9388 40.7347 32.8571C40.7347 32.6122 40.7347 32.3673 40.7347 32.0816V32.0408C40.7347 31.8776 40.7755 31.7551 40.8163 31.5918C40.8571 31.3878 40.898 31.2245 40.9796 31.0204C41.0612 30.7755 41.1837 30.4898 41.3061 30.2449C41.3878 30.0816 41.4694 29.9592 41.5918 29.7959C41.6735 29.7143 41.7143 29.5918 41.7959 29.5102C41.8775 29.3878 42 29.2653 42.0816 29.1837C42.1224 29.1429 42.1224 29.1429 42.1633 29.102C42.4898 28.7755 42.898 28.4898 43.3061 28.2857C43.7551 28.0408 44.2041 27.8776 44.6939 27.7959C44.8163 27.7551 44.9388 27.7551 45.0612 27.7551L46.2857 27.7143C47.1837 27.6735 47.8775 26.9796 47.9592 26.1224C48 25.4694 48.0408 24.8163 48.0408 24.1633C48.0408 23.3469 48 22.5714 47.9184 21.7959C47.7959 20.7755 47.0612 20.1224 46.2041 20.0816Z" fill="#FE4715"/>
			</svg>',
			// Add more custom SVG icons as needed
		);
	}

	/**
	 * Get curated Charitable addons list.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_curated_charitable_addons() {
		return array(
			'charitable-recurring',
			'charitable-donortrust',
			'charitable-gift-aid',
			// Add more as you review
		);
	}

	/**
	 * Get custom title overrides for Charitable addons.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_charitable_addon_title_overrides() {
		return array(
			'charitable-recurring' => __( 'Recurring Donations', 'charitable' ),
			'charitable-donortrust' => __( 'DonorTrust', 'charitable' ),
			'charitable-gift-aid' => __( 'Gift Aid', 'charitable' ),
			'charitable-ambassadors' => __( 'Ambassadors', 'charitable' ),
			'charitable-anonymous-donations' => __( 'Anonymous Donations', 'charitable' ),
			'charitable-fee-relief' => __( 'Fee Relief', 'charitable' ),
			// Add more overrides as needed
		);
	}

	/**
	 * Get third-party plugin data.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_third_party_plugin_data() {
		$plugins_class = Charitable_Admin_Plugins_Third_Party::get_instance();
		return $plugins_class->get_plugins( false );
	}

	/**
	 * Get Charitable addon data.
	 *
	 * @since 1.8.8
	 * @param string $slug Addon slug.
	 * @return array|false
	 */
	private function get_charitable_addon_data( $slug ) {
		// Debug: Output current plan and license info
		$addons_directory = Charitable_Addons_Directory::get_instance();
		$current_plan     = $addons_directory->get_current_plan_slug();
		$licenses         = charitable_get_licenses();
		$is_pro           = charitable_is_pro();

		// First check if the addon is installed as a WordPress plugin
		$plugin_file = $slug . '/' . $slug . '.php';
		$is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
		$is_active = is_plugin_active( $plugin_file );

		// If not installed, try to get addon data from directory
		if ( ! $is_installed ) {
			$addons_directory = Charitable_Addons_Directory::get_instance();
			$addon = $addons_directory->get_addon( $slug );

			if ( ! $addon ) {
				// Check if user has access to this addon by checking if it's in the recommended addons
				$all_addons = $addons_directory->get_addons();
				$has_access = false;

				// Check if addon is in recommended, licensed, or unlicensed categories
				foreach ( $all_addons as $category => $addons ) {
					foreach ( $addons as $addon_data ) {
						if ( isset( $addon_data['slug'] ) && $addon_data['slug'] === $slug ) {
							$has_access = true;
							$addon = $addon_data;
							break 2;
						}
					}
				}

				if ( ! $has_access ) {
					return false;
				}

				// Process the raw addon data to add status, action, and plugin_allow fields
				// This mimics what get_addon() does but for raw data from get_addons()
				$addon = $this->process_raw_addon_data( $addon, $slug );
			}
		} else {
			// Create addon data for installed plugin
			$addon = array(
				'name' => ucwords( str_replace( '-', ' ', $slug ) ),
				'status' => $is_active ? 'active' : 'installed',
				'action' => $is_active ? 'deactivate' : 'activate',
				'path' => $plugin_file,
				'plugin_allow' => true,
				'upgrade_url' => '',
				// Installed plugin may not have directory metadata; default license to empty
				'license' => array(),
			);
		}

		// Get title with overrides
		$title_overrides = $this->get_charitable_addon_title_overrides();
		if ( isset( $title_overrides[ $slug ] ) ) {
			$title = $title_overrides[ $slug ];
		} else {
			$title = isset( $addon['name'] ) ? $addon['name'] : ucwords( str_replace( '-', ' ', $slug ) );
			$title = preg_replace( '/^Charitable\s+/i', '', $title );
		}

		// Get local icon
		$icon = $this->get_charitable_addon_icon( $slug );

		$final_data = array(
			'title' => $title,
			'description' => $this->get_charitable_addon_description( $slug ),
			'icon' => $icon,
			'installed' => $addon['status'] === 'installed' || $addon['status'] === 'active',
			'active' => $addon['status'] === 'active',
			'download_url' => isset( $addon['download_link'] ) ? $addon['download_link'] : '',
			'slug' => $slug,
			'type' => 'charitable_addon',
			'basename' => $addon['path'],
			'status' => $addon['status'],
			'action' => $addon['action'],
			'plugin_allow' => $addon['plugin_allow'],
			'upgrade_url' => $addon['upgrade_url'] ?? '',
			'license' => isset( $addon['license'] ) ? (array) $addon['license'] : array(),
		);

		return $final_data;
	}

	/**
	 * Get Charitable addon description.
	 *
	 * @since 1.8.8.4
	 * @param string $slug Addon slug.
	 * @return string Description.
	 */
	public function get_charitable_addon_description( $slug ) {

		$descriptions = array(
			// Charitable official addons
			'charitable-recurring' => __( 'Let donors give automatically on a daily, weekly, monthly, or yearly schedule.', 'charitable' ),
			'charitable-donortrust' => __( 'Build Trust and Boost Donations with Real-Time Social Proof.', 'charitable' ),
			'charitable-gift-aid' => __( 'Allow UK donors to add Gift Aid and boost eligible donations by 25%.', 'charitable' ),
			'charitable-anonymous-donations' => __( 'Give supporters the option to hide their name from public donations.', 'charitable' ),
			'charitable-fee-relief' => __( 'Let donors optionally cover processing fees.', 'charitable' ),

			// Add more addon descriptions here as they are surfaced in the dashboard
			'google-analytics-for-wordpress' => __( 'Get detailed insights into your donation traffic and campaign performance.', 'charitable' ),
			'wpforms-lite' => __( 'Create custom donation forms and surveys to engage with supporters.', 'charitable' ),
			'all-in-one-seo-pack' => __( 'Optimize your campaigns for search engines to reach more potential donors.', 'charitable' ),
			'duplicator' => __( 'Safely backup and migrate your donation data and campaign content.', 'charitable' ),
			'pushengage' => __( 'Send targeted push notifications to re-engage donors and drive contributions.', 'charitable' ),
			'uncanny-automator' => __( 'Automate your donation workflows and connect with other fundraising tools.', 'charitable' ),
			'envira-gallery' => __( 'Create beautiful photo galleries to showcase your charitable impact.', 'charitable' ),
		);

		if ( isset( $descriptions[ $slug ] ) ) {
			return $descriptions[ $slug ];
		}

		// Sensible default if an addon is missing a curated description
		return __( 'Enhance your campaign with powerful fundraising features.', 'charitable' );
	}

	/**
	 * Get Charitable addon icon from local assets.
	 *
	 * @since 1.8.8
	 * @param string $slug Addon slug.
	 * @return string Icon URL or generic icon.
	 */
	private function get_charitable_addon_icon( $slug ) {
		// Map addon slugs to their base filenames (without extension)
		$icon_mapping = array(
			'charitable-donortrust' => 'donortrust',
			'charitable-gift-aid' => 'gift-aid',
			'charitable-recurring' => 'recurring-donations',
			'charitable-anonymous-donations' => 'anonymous-donations',
			'charitable-fee-relief' => 'fee-relief',
		);

		// Check if we have a local icon for this addon
		if ( isset( $icon_mapping[ $slug ] ) ) {
			$base_filename = $icon_mapping[ $slug ];
			$base_path = charitable()->get_path( 'assets' ) . 'images/plugins/charitable/' . $base_filename;

			// First try SVG
			$svg_path = $base_path . '.svg';
			if ( file_exists( $svg_path ) ) {
				$svg_content = file_get_contents( $svg_path );
				return $svg_content ? $svg_content : $this->get_generic_plugin_icon();
			}

			// Fallback to PNG
			$png_path = $base_path . '.png';
			if ( file_exists( $png_path ) ) {
				$png_url = charitable()->get_path( 'directory', false ) . 'assets/images/plugins/charitable/' . $base_filename . '.png';
				return '<img src="' . esc_url( $png_url ) . '" alt="' . esc_attr( $base_filename ) . '" width="20" height="20" />';
			}
		}

		// Fall back to generic puzzle piece icon
		return $this->get_generic_plugin_icon();
	}

	/**
	 * Get generic plugin icon SVG.
	 *
	 * @since 1.8.8
	 * @return string Generic icon SVG.
	 */
	private function get_generic_plugin_icon() {
		return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 2L12 6L8 10L4 6L8 2Z" fill="#6B7280"/>
			<path d="M8 10L12 14L8 18L4 14L8 10Z" fill="#6B7280"/>
			<path d="M12 6L16 10L12 14L8 10L12 6Z" fill="#6B7280"/>
		</svg>';
	}

	/**
	 * Format plugin item for display.
	 *
	 * @since 1.8.8
	 * @param array  $plugin_data Plugin data.
	 * @param string $type Plugin type.
	 * @return array
	 */
	private function format_plugin_item( $plugin_data, $type ) {
		$button_state = $this->get_button_state( $plugin_data, $type );


		$item = array(
			'title' => $plugin_data['title'],
			'description' => $this->get_charitable_addon_description( $plugin_data['slug'] ),
			'button_text' => $button_state['text'],
			'button_class' => $button_state['class'],
			'button_action' => $button_state['action'],
			'icon' => $this->get_plugin_icon( $plugin_data ),
			'slug' => $plugin_data['slug'],
			'basename' => $plugin_data['basename'],
			'type' => $type
		);

		// Always add setup URL if available (for when button changes to setup state)
		if ( ! empty( $plugin_data['setup'] ) ) {
			$item['setup_url'] = $plugin_data['setup'];
		}

		return $item;
	}

	/**
	 * Format addon item for display.
	 *
	 * @since 1.8.8
	 * @param array $addon_data Addon data.
	 * @return array
	 */
	private function format_addon_item( $addon_data ) {
		$button_state = $this->get_button_state( $addon_data, 'charitable_addon' );

		$item = array(
			'title' => $addon_data['title'],
			'description' => isset( $addon_data['description'] ) ? $addon_data['description'] : $this->get_charitable_addon_description( $addon_data['slug'] ),
			'button_text' => $button_state['text'],
			'button_class' => $button_state['class'],
			'button_action' => $button_state['action'],
			'icon' => $addon_data['icon'],
			'slug' => $addon_data['slug'],
			'type' => 'charitable_addon'
		);

		// Add upgrade URL for upgrade buttons
		if ( $button_state['action'] === 'upgrade' && isset( $button_state['upgrade_url'] ) ) {
			$item['upgrade_url'] = $button_state['upgrade_url'];
		}

		// Add disabled state for installed buttons
		if ( isset( $button_state['disabled'] ) && $button_state['disabled'] ) {
			$item['button_disabled'] = true;
		}

		return $item;
	}

	/**
	 * Install a Charitable addon from download URL.
	 *
	 * @param string $download_url The download URL for the addon.
	 * @param string $slug         The addon slug.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function install_charitable_addon_from_url( $download_url, $slug ) {
		// Include WordPress filesystem API
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'unzip_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Download the addon zip file
		$temp_file = download_url( $download_url );

		if ( is_wp_error( $temp_file ) ) {
			return new WP_Error( 'download_failed', 'Failed to download addon: ' . $temp_file->get_error_message() );
		}

		// Get the WordPress filesystem
		$wp_filesystem = $this->get_wp_filesystem();
		if ( ! $wp_filesystem ) {
			unlink( $temp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			return new WP_Error( 'filesystem_error', 'Unable to access WordPress filesystem.' );
		}

		// Create the plugin directory
		$plugin_dir = WP_PLUGIN_DIR . '/' . $slug;
		if ( ! $wp_filesystem->is_dir( $plugin_dir ) ) {
			$wp_filesystem->mkdir( $plugin_dir );
		}

		// Unzip the file
		$result = unzip_file( $temp_file, WP_PLUGIN_DIR );

		// Clean up temp file
		unlink( $temp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'unzip_failed', 'Failed to extract addon: ' . $result->get_error_message() );
		}

		return true;
	}

	/**
	 * Get WordPress filesystem instance.
	 *
	 * @return WP_Filesystem_Base|false
	 */
	private function get_wp_filesystem() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * Process raw addon data to add status, action, and plugin_allow fields.
	 * This mimics what get_addon() does but for raw data from get_addons().
	 *
	 * @version 1.8.8.4
	 *
	 * @param array  $raw_addon The raw addon data from get_addons().
	 * @param string $slug      The addon slug.
	 * @return array The processed addon data.
	 */
	private function process_raw_addon_data( $raw_addon, $slug ) {
		// Check if plugin is installed
		$plugin_file = $slug . '/' . $slug . '.php';
		$is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
		$is_active = $is_installed ? is_plugin_active( $plugin_file ) : false;
		$is_pro = charitable_is_pro();
		$current_plan = Charitable_Addons_Directory::get_current_plan_slug();
		$required_plans = isset( $raw_addon['license'] ) ? (array) $raw_addon['license'] : array();
		$plan_allows = empty( $required_plans ) ? false : in_array( $current_plan, $required_plans, true );

		// Determine status and action based on installation state and license
		if ( $is_active ) {
			$status = 'active';
			$action = 'deactivate';
			$plugin_allow = true;
		} elseif ( $is_installed ) {
			$status = 'installed';
			$action = 'activate';
			$plugin_allow = true;
		} else {
			$status = 'uninstalled';
			// Check if user has a valid license and required plan before allowing install
			if ( $is_pro && $plan_allows ) {
				$action = 'install';
				$plugin_allow = true;
			} else {
				$action = 'upgrade';
				$plugin_allow = false;
				// Provide upgrade URL so the button can link to pricing
				$addons_directory = Charitable_Addons_Directory::get_instance();
				if ( method_exists( $addons_directory, 'charitable_get_upgrade_link' ) ) {
					$raw_addon['upgrade_url'] = $addons_directory->charitable_get_upgrade_link();
				}
			}
		}

		// Add the processed fields to the raw addon data
		$raw_addon['status'] = $status;
		$raw_addon['action'] = $action;
		$raw_addon['plugin_allow'] = $plugin_allow;
		$raw_addon['path'] = $plugin_file;

		return $raw_addon;
	}

	/**
	 * Get button state for plugin/addon.
	 *
	 * @since 1.8.8
	 * @param array  $item_data Item data.
	 * @param string $type Item type.
	 * @return array
	 */
	private function get_button_state( $item_data, $type = 'third_party' ) {
		if ( $type === 'third_party' ) {
			if ( ! $item_data['installed'] ) {
					return array(
						'text' => __( 'Install & Activate', 'charitable' ),
						'action' => 'install',
					'class' => '' // Same as hardcoded
				);
			} elseif ( ! $item_data['active'] ) {
				return array(
					'text' => __( 'Activate', 'charitable' ),
					'action' => 'activate',
					'class' => 'charitable-dashboard-v2-activate-button' // Same as hardcoded
				);
			} else {
				return array(
					'text' => __( 'Setup', 'charitable' ),
					'action' => 'setup',
					'class' => 'charitable-dashboard-v2-setup-button' // New class for setup
				);
			}
		} else { // Charitable addon
			// Use the action from addons directory
			switch ( $item_data['action'] ) {
				case 'upgrade':
				case 'license':
					return array(
						'text' => __( 'Upgrade', 'charitable' ),
						'action' => 'upgrade',
						'class' => 'charitable-dashboard-v2-upgrade-button',
						'upgrade_url' => $item_data['upgrade_url']
					);
				case 'install':
					return array(
						'text' => 'Install & Activate',
						'action' => 'install_addon',
						'class' => ''
					);
				case 'activate':
					return array(
						'text' => __( 'Activate', 'charitable' ),
						'action' => 'activate_addon',
						'class' => 'charitable-dashboard-v2-activate-button'
					);
				case 'deactivate':
					return array(
						'text' => __( 'Installed', 'charitable' ),
						'action' => 'installed',
						'class' => 'charitable-dashboard-v2-installed-button',
						'disabled' => true
					);
				default:
					return array(
						'text' => __( 'Upgrade', 'charitable' ),
						'action' => 'upgrade',
						'class' => 'charitable-dashboard-v2-upgrade-button',
						'upgrade_url' => $item_data['upgrade_url']
					);
			}
		}
	}

	/**
	 * Get plugin icon.
	 *
	 * @since 1.8.8
	 * @param array $plugin_data Plugin data.
	 * @return string
	 */
	private function get_plugin_icon( $plugin_data ) {
		// Step 1: Try PNG icon from plugin data (check if file exists)
		if ( ! empty( $plugin_data['icon'] ) ) {
			$icon_url = $plugin_data['icon'];
			if ( filter_var( $icon_url, FILTER_VALIDATE_URL ) ) {
				// Convert URL to server path for file existence check
				$icon_path = $this->url_to_server_path( $icon_url );
				if ( $icon_path && file_exists( $icon_path ) ) {
					return '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $plugin_data['title'] ) . '" width="20" height="20" />';
				}
			}
		}

		// Step 2: Try custom SVG icon
		$custom_icons = $this->get_custom_plugin_icons();
		if ( isset( $custom_icons[ $plugin_data['slug'] ] ) ) {
			return $custom_icons[ $plugin_data['slug'] ];
		}

		// Step 3: Try the 'icon' from plugin data even if file doesn't exist
		// (might be a different format or external URL)
		if ( ! empty( $plugin_data['icon'] ) ) {
			$icon_url = $plugin_data['icon'];
			if ( filter_var( $icon_url, FILTER_VALIDATE_URL ) ) {
				// Check if the URL actually exists
				$response = wp_remote_head( $icon_url, array( 'timeout' => 5 ) );
				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
					return '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $plugin_data['title'] ) . '" width="20" height="20" />';
				}
			}
		}

		// Step 4: Default puzzle piece icon
		return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 2a2 2 0 00-2 2v2H4a2 2 0 00-2 2v2a2 2 0 002 2h2v2a2 2 0 002 2h2a2 2 0 002-2v-2h2a2 2 0 002-2V8a2 2 0 00-2-2h-2V4a2 2 0 00-2-2H8z" fill="#9CA3AF"/>
			<path d="M10 6a1 1 0 100 2 1 1 0 000-2zM6 10a1 1 0 100 2 1 1 0 000-2zM14 10a1 1 0 100 2 1 1 0 000-2zM10 14a1 1 0 100 2 1 1 0 000-2z" fill="#6B7280"/>
		</svg>';
	}

	/**
	 * Convert URL to server file path.
	 *
	 * @since 1.8.8
	 * @param string $url The URL to convert.
	 * @return string|false The server path or false if conversion fails.
	 */
	private function url_to_server_path( $url ) {
		// Get the site URL and uploads directory
		$site_url = get_site_url();
		$upload_dir = wp_upload_dir();

		// Check if it's a local URL
		if ( strpos( $url, $site_url ) === 0 ) {
			// Remove site URL from the beginning
			$relative_path = str_replace( $site_url, '', $url );
			// Get the absolute path
			$absolute_path = ABSPATH . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		// Check if it's a plugin URL
		$plugin_url = plugins_url();
		if ( strpos( $url, $plugin_url ) === 0 ) {
			// Remove plugin URL from the beginning
			$relative_path = str_replace( $plugin_url, '', $url );
			// Get the absolute path
			$absolute_path = WP_PLUGIN_DIR . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		// Check if it's a theme URL
		$theme_url = get_template_directory_uri();
		if ( strpos( $url, $theme_url ) === 0 ) {
			// Remove theme URL from the beginning
			$relative_path = str_replace( $theme_url, '', $url );
			// Get the absolute path
			$absolute_path = get_template_directory() . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		// Check if it's a content URL
		$content_url = content_url();
		if ( strpos( $url, $content_url ) === 0 ) {
			// Remove content URL from the beginning
			$relative_path = str_replace( $content_url, '', $url );
			// Get the absolute path
			$absolute_path = WP_CONTENT_DIR . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		return false;
	}

	/**
	 * Track recommendation interaction.
	 *
	 * @since 1.8.8
	 * @param string $action Action performed.
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_type Plugin type.
	 * @param string $context Context of interaction.
	 * @return void
	 */
	private function track_recommendation_click( $action, $plugin_slug, $plugin_type, $context = 'dashboard_enhance' ) {
		if ( ! charitable_get_usage_tracking_setting() ) {
			return;
		}

		$tracked_data = get_option( 'charitable_recommended_plugins_' . $action, array() );
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$tracked_data[] = array(
			'plugin' => $plugin_slug,
			'type' => $plugin_type,
			'context' => $context,
			'timestamp' => time(),
			'user_agent' => $user_agent,
			'referrer' => wp_get_referer() ?: 'direct'
		);

		// Keep only last 100 entries
		if ( count( $tracked_data ) > 100 ) {
			$tracked_data = array_slice( $tracked_data, -100 );
		}

		update_option( 'charitable_recommended_plugins_' . $action, $tracked_data );
	}

	/**
	 * Check if enhance section should be shown.
	 *
	 * @since 1.8.8
	 * @return bool
	 */
	private function should_show_enhance_section() {
		$items = $this->get_enhance_items();
		return count( $items ) > 0; // Hide if no items to show
	}

		/**
		 * Render the latest updates section.
		 *
		 * @since 1.8.8
		 */
		public function render_latest_updates_section() {
			// Try v2 feed first, fall back to legacy blog posts.
			$updates = $this->get_latest_updates();
			$has_posts = ! empty( $updates );
			if ( ! $has_posts ) {
				$posts = $this->get_blog_posts();
				$has_posts = ! empty( $posts ) && ! $this->has_blog_posts_error( $posts );
			}
			$footer_link_text = __( 'See More Updates', 'charitable' );

			// Threshold resolves in order: feed meta.behind_threshold → WP filter → hardcoded default (3).
			$updates_behind = $this->get_updates_behind();
			$threshold      = 3;
			// isset (not ! empty) so a feed-supplied 0 correctly overrides to "always alert".
			if ( isset( $this->version_info['behind_threshold'] ) ) {
				$threshold = (int) $this->version_info['behind_threshold'];
			}
			/**
			 * Filter the "behind versions" threshold above which the big update alert renders.
			 *
			 * @since 1.8.12
			 *
			 * @param int $threshold Default 3, or value supplied by the feed.
			 */
			$threshold           = (int) apply_filters( 'charitable_latest_updates_behind_threshold', $threshold );
			$show_version_update = $updates_behind > $threshold;

			// Version-state pill resolution (mutually exclusive with the big alert). @since 1.8.12
			//   - on_latest       : remote latest is known AND user is 0 behind.
			//   - mildly_behind   : 1+ behind but not past the threshold (shows a compact amber nudge).
			$latest_known_version = $this->get_latest_version();
			$on_latest            = ! empty( $latest_known_version ) && 0 === $updates_behind;
			$mildly_behind        = ! empty( $latest_known_version ) && $updates_behind > 0 && ! $show_version_update;

			// If blog posts can't load and there's no version update to show, don't display the section at all
			if ( ! $has_posts && ! $show_version_update ) {
				return;
			}
	
			$section_class = 'charitable-dashboard-v2-section';
			if ( ! $show_version_update ) {
				$section_class .= ' no-update-alert';
			}
			?>
			<section id="charitable-dashboard-v2-latest-updates" class="<?php echo esc_attr( $section_class ); ?>">
				<header class="charitable-dashboard-v2-section-header">
					<h3>Latest Updates</h3>
				</header>
				<?php if ( $on_latest ) : ?>
					<div class="charitable-dashboard-v2-latest-updates-uptodate">
						<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="14" height="14" aria-hidden="true">
							<path d="M16.7 5.3L7.5 14.6L3.3 10.4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<span><?php
							echo wp_kses(
								sprintf(
									/* translators: %s: current installed version number */
									__( "You're running the latest version (<strong>%s</strong>).", 'charitable' ),
									esc_html( charitable()->get_version() )
								),
								array( 'strong' => array() )
							);
						?></span>
					</div>
				<?php elseif ( $mildly_behind ) : ?>
					<div class="charitable-dashboard-v2-latest-updates-behind">
						<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="14" height="14" aria-hidden="true">
							<circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2"/>
							<path d="M10 9v4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
							<circle cx="10" cy="6.3" r="1.1" fill="currentColor"/>
						</svg>
						<span><?php
							$versions_label = sprintf(
								/* translators: %d: number of versions behind */
								_n( '%d version', '%d versions', $updates_behind, 'charitable' ),
								$updates_behind
							);
							echo wp_kses(
								sprintf(
									/* translators: 1: versions-behind label (e.g. "2 versions"), 2: plugins page URL */
									__( 'You are behind <strong>%1$s</strong>. <a href="%2$s">Update now</a>', 'charitable' ),
									esc_html( $versions_label ),
									esc_url( admin_url( 'plugins.php?s=charitable' ) )
								),
								array( 'strong' => array(), 'a' => array( 'href' => array() ) )
							);
						?></span>
					</div>
				<?php endif; ?>
				<div class="charitable-dashboard-v2-section-content">
					<?php if ( $show_version_update ) : ?>
					<div class="charitable-dashboard-v2-latest-updates-background">
						<div class="charitable-dashboard-v2-latest-updates-background-content">
							<div class="charitable-dashboard-v2-latest-updates-flex">
								<div class="charitable-dashboard-v2-latest-updates-icon">
									<svg class="charitable-dashboard-v2-latest-updates-icon-placeholder" width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M13 2L24 22H2L13 2Z" stroke="#fff" stroke-width="2" stroke-linejoin="round"/>
										<path d="M13 10V16" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
										<circle cx="13" cy="19.5" r="1.25" fill="#fff"/>
									</svg>
								</div>
								<div class="charitable-dashboard-v2-latest-updates-content">
									<h4 class="charitable-dashboard-v2-latest-updates-headline"><?php
										$version_label = sprintf( '<strong>%s</strong>', esc_html( sprintf( _n( '%d version', '%d versions', $updates_behind, 'charitable' ), $updates_behind ) ) );
										$update_link   = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'plugins.php?s=charitable' ) ), esc_html__( 'Update now', 'charitable' ) );
										printf(
											wp_kses(
												/* translators: 1: versions-behind label (e.g. "4 versions"), 2: "Update now" link */
												__( 'You are currently %1$s behind. Please upgrade Charitable to take advantage of the features and addons below. %2$s', 'charitable' ),
												array( 'strong' => array(), 'a' => array( 'href' => array() ) )
											),
											$version_label,
											$update_link
										);
									?></h4>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<?php $this->render_latest_updates_items(); ?>
					<footer class="charitable-dashboard-v2-latest-updates-footer">
						<div class="charitable-dashboard-v2-latest-updates-footer-content">
							<div class="charitable-dashboard-v2-latest-updates-footer-left">
								<a href="https://www.wpcharitable.com/charitable-changelog/" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-blog-link">
									<span class="charitable-dashboard-v2-blog-text"><?php echo esc_html( $footer_link_text ); ?></span>
									<svg class="charitable-dashboard-v2-arrow-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</div>
						</div>
					</footer>
				</div>
			</section>
			<?php
		}
	

		/**
		 * Render the "Are you enjoying Charitable?" section on the dashboard.
		 * Mirrors the top admin notice (five-star review) but styled to match the dashboard.
		 * Only shows when the same eligibility conditions are met and it hasn't been dismissed.
		 *
		 * @since 1.8.12
		 */
		public function render_enjoying_section() {
			// ?preview_enjoy=1 bypasses all eligibility checks for testing (admins only).
			$preview = ! empty( $_GET['preview_enjoy'] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	
			if ( ! $preview ) {
				$activated_datetime = get_option( 'wpcharitable_activated_datetime', false );
				$days               = 0;
				if ( $activated_datetime ) {
					$diff = current_time( 'timestamp' ) - $activated_datetime;
					$days = (int) abs( round( $diff / 86400 ) );
				}
	
				$count_campaigns = wp_count_posts( 'campaign' );
				$total_campaigns = isset( $count_campaigns->publish ) ? (int) $count_campaigns->publish : 0;
				$count_donations = wp_count_posts( 'donation' );
				$total_donations = isset( $count_donations->{'charitable-completed'} ) ? (int) $count_donations->{'charitable-completed'} : 0;
	
				if ( $days < apply_filters( 'charitable_days_since_activated', 14 ) || $total_campaigns < 1 || $total_donations < 1 ) {
					return;
				}
	
				// Use the same transient as the top notice so dismissing either dismisses both.
				if ( get_transient( 'charitable_five-star-review_banner' ) ) {
					return;
				}
			}
	
			$feedback_url = 'https://wpcharitable.com/charitable-admin-review/';
			$rating_url   = 'https://wpcharitable.com/charitable-admin-review/';
			?>
			<section id="charitable-dashboard-enjoy-section" class="charitable-dashboard-v2-section charitable-dashboard-enjoy-section">
				<button type="button" class="charitable-enjoy-dismiss-btn" aria-label="<?php esc_attr_e( 'Dismiss', 'charitable' ); ?>">
					<svg width="10" height="10" viewBox="0 0 12 12" fill="none"><path d="M1 1L11 11M11 1L1 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
				</button>
	
				<div class="charitable-enjoy-step" data-step="1">
					<p class="charitable-enjoy-question"><?php esc_html_e( 'Are You Enjoying Charitable?', 'charitable' ); ?></p>
					<p class="charitable-enjoy-actions">
						<button type="button" class="charitable-enjoy-link charitable-enjoy-link--yes" data-navigate="3"><?php esc_html_e( 'Yes', 'charitable' ); ?></button>
						<button type="button" class="charitable-enjoy-link charitable-enjoy-link--no" data-navigate="2"><?php esc_html_e( 'Not Really', 'charitable' ); ?></button>
					</p>
				</div>
	
				<div class="charitable-enjoy-step" data-step="2" style="display:none;">
					<p class="charitable-enjoy-question charitable-enjoy-question--title"><?php esc_html_e( "Sorry To Hear That!", 'charitable' ); ?></p>
					<p class="charitable-enjoy-question"><?php esc_html_e( "We're sorry to hear you aren't enjoying Charitable. We would love a chance to improve. Could you take a minute and let us know what we can do better?", 'charitable' ); ?></p>
					<p class="charitable-enjoy-actions">
						<a href="<?php echo esc_url( $feedback_url ); ?>" class="charitable-enjoy-link charitable-enjoy-link--yes charitable-enjoy-dismiss-action" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Give Feedback', 'charitable' ); ?></a>
						<button type="button" class="charitable-enjoy-link charitable-enjoy-link--no charitable-enjoy-dismiss-action"><?php esc_html_e( 'No Thanks', 'charitable' ); ?></button>
					</p>
				</div>
	
				<div class="charitable-enjoy-step" data-step="3" style="display:none;">
					<p class="charitable-enjoy-question charitable-enjoy-question--title"><?php esc_html_e( "That's Awesome!", 'charitable' ); ?></p>
					<p class="charitable-enjoy-question"><?php esc_html_e( "Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our team motivation?", 'charitable' ); ?></p>
					<p class="charitable-enjoy-team"><strong><?php esc_html_e( '~ WP Charitable Team', 'charitable' ); ?></strong></p>
					<p class="charitable-enjoy-actions">
						<a href="<?php echo esc_url( $rating_url ); ?>" class="charitable-enjoy-link charitable-enjoy-link--yes charitable-enjoy-dismiss-action" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ok, You Deserve It', 'charitable' ); ?></a>
						<button type="button" class="charitable-enjoy-link charitable-enjoy-link--no charitable-enjoy-dismiss-action"><?php esc_html_e( 'I Already Did', 'charitable' ); ?></button>
					</p>
				</div>
			</section>
			<script>
			( function ( $ ) {
				var section = $( '#charitable-dashboard-enjoy-section' );
	
				// Navigate between steps.
				section.on( 'click', '[data-navigate]', function () {
					var target = $( this ).data( 'navigate' );
					section.find( '.charitable-enjoy-step' ).hide();
					section.find( '.charitable-enjoy-step[data-step="' + target + '"]' ).show();
				} );
	
				// Dismiss — fires AJAX then removes the section.
				function dismissSection() {
					$.post( ajaxurl, {
						action:    'charitable_dismiss_admin_five_star_rating',
						banner_id: 'five-star-review',
					} );
					section.fadeOut( 300, function () { $( this ).remove(); } );
				}
	
				section.on( 'click', '.charitable-enjoy-dismiss-btn', dismissSection );
				section.on( 'click', '.charitable-enjoy-dismiss-action', function () {
					setTimeout( dismissSection, 150 );
				} );
			}( jQuery ) );
			</script>
			<?php
		}
		/**
		 * Map a URL to a local filesystem path if it lives under WP_CONTENT_URL.
		 *
		 * @param string $url URL to check.
		 * @return string|false Absolute file path, or false if the URL is external.
		 */
		private function get_local_file_path_for_url( $url ) {
			$content_url = untrailingslashit( content_url() );
			// Normalize schemes so http/https mismatch doesn't break the comparison.
			$url_norm     = preg_replace( '#^https?://#', '//', $url );
			$content_norm = preg_replace( '#^https?://#', '//', $content_url );
			if ( strpos( $url_norm, $content_norm ) !== 0 ) {
				return false;
			}
			$relative = substr( $url_norm, strlen( $content_norm ) );
			$file     = WP_CONTENT_DIR . $relative;
			return file_exists( $file ) ? $file : false;
		}
		/**
		 * Return a local video URL for a given post title, if one exists.
		 * Used to override missing video_url values from the remote feed.
		 *
		 * @param  string $title Post title from the feed.
		 * @return string URL or empty string.
		 */
		private function get_local_video_url( $title ) {
			$map = array(
				'Mini Donation Widget' => site_url( '/_promo_videos/charitable_dashboard_updates_video_mini-widget.mp4' ),
			);

			foreach ( $map as $keyword => $url ) {
				if ( false !== stripos( $title, $keyword ) ) {
					return $url;
				}
			}

			return '';
		}
		/**
		 * Return a local thumbnail URL for a given post title, if one exists.
		 *
		 * @param  string $title Post title from the feed.
		 * @return string URL or empty string.
		 */
		private function get_local_thumbnail_url( $title ) {
			$base = charitable()->get_path( 'assets', false ) . 'images/dashboard/';
			$map  = array(
				'Mini Donation Widget' => site_url( '/_promo_videos/mini-widget-thumbnail.svg' ),
				'Donations Feed'       => site_url( '/_promo_videos/donations-feed-thumbnail.svg' ),
				'Campaign Showcase'    => $base . 'thumb-campaign-showcase.png',
				'Campaign Updates'     => $base . 'thumb-campaign-updates.svg',
			);

			foreach ( $map as $keyword => $url ) {
				if ( false !== stripos( $title, $keyword ) ) {
					return $url;
				}
			}

			return '';
		}
		/**
		 * Return a local badge type for a given post title, if one should be overridden.
		 * Values: 'pro', 'addon', or empty string.
		 *
		 * @param  string $title Post title from the feed.
		 * @return string Badge type or empty string.
		 */
		private function get_local_badge( $title ) {
			$pro_keywords = array( 'Mini Donation Widget', 'Donations Feed', 'Campaign Showcase' );
			foreach ( $pro_keywords as $keyword ) {
				if ( false !== stripos( $title, $keyword ) ) {
					return 'pro';
				}
			}

			$addon_keywords = array( 'Campaign Updates Addon', 'Campaigns Update Addon' );
			foreach ( $addon_keywords as $keyword ) {
				if ( false !== stripos( $title, $keyword ) ) {
					return 'addon';
				}
			}

			return '';
		}
		/**
		 * Fetch and return the v2 Latest Updates feed.
		 *
		 * @since 1.8.12
		 * @return array Array of update items, or empty array on failure.
		 */
		private function get_latest_updates() {
			$cached = get_transient( 'charitable_latest_updates_cache' );
			if ( false !== $cached ) {
				if ( isset( $cached['version_info'] ) ) {
					$this->version_info = $cached['version_info'];
				}
				return $cached['updates'] ?? [];
			}

			$response = wp_remote_get( self::LATEST_UPDATES_SOURCE_URL, [
				'timeout'   => 10,
				'sslverify' => false,
			] );

			if ( is_wp_error( $response ) ) {
				return [];
			}

			$body = wp_remote_retrieve_body( $response );
			if ( empty( $body ) ) {
				return [];
			}

			$decoded = json_decode( $body, true );
			if ( json_last_error() !== JSON_ERROR_NONE || empty( $decoded['updates'] ) ) {
				return [];
			}

			// Store version info from meta.
			if ( isset( $decoded['meta'] ) && is_array( $decoded['meta'] ) ) {
				$this->version_info = [
					'current_charitable_version_lite' => ! empty( $decoded['meta']['current_charitable_version_lite'] ) ? sanitize_text_field( $decoded['meta']['current_charitable_version_lite'] ) : '',
					'current_charitable_version_pro'  => ! empty( $decoded['meta']['current_charitable_version_pro'] ) ? sanitize_text_field( $decoded['meta']['current_charitable_version_pro'] ) : '',
				];
			}

			// Sanitize each update item.
			$updates = [];
			foreach ( $decoded['updates'] as $item ) {
				if ( empty( $item['title'] ) || empty( $item['url'] ) ) {
					continue;
				}
				$updates[] = [
					'id'          => sanitize_key( $item['id'] ?? '' ),
					'title'       => sanitize_text_field( $item['title'] ),
					'description' => sanitize_text_field( $item['description'] ?? '' ),
					'url'         => esc_url_raw( $item['url'] ),
					'date'        => sanitize_text_field( $item['date'] ?? '' ),
					'badge'       => sanitize_key( $item['badge'] ?? '' ),
					'is_new'      => ! empty( $item['is_new'] ),
					'image'       => ! empty( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
				];
			}

			// Cache for 24 hours.
			set_transient( 'charitable_latest_updates_cache', [
				'updates'      => $updates,
				'version_info' => $this->version_info,
			], DAY_IN_SECONDS );

			return $updates;
		}
		/**
		 * Render the v2 Latest Updates items.
		 *
		 * @since 1.8.12
		 */
		private function render_latest_updates_items() {
			$updates = $this->get_latest_updates();

			if ( empty( $updates ) ) {
				// Fall back to legacy blog posts.
				$this->render_blog_posts();
				return;
			}

			?>
			<div class="charitable-dashboard-v2-blog-posts" id="charitable-blog-posts-container">
				<?php $count = 0; foreach ( $updates as $update ) : if ( $count >= 4 ) break; $count++; ?>
					<div class="charitable-dashboard-v2-blog-post-row">
						<div class="charitable-dashboard-v2-blog-post-left">
							<div class="charitable-dashboard-v2-blog-post-timestamp-row">
								<?php if ( ! empty( $update['date'] ) ) : ?>
									<span class="charitable-dashboard-v2-blog-post-timestamp"><?php echo esc_html( $this->calculate_relative_time( $update['date'] ) ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $update['is_new'] ) ) : ?>
									<span class="charitable-dashboard-v2-blog-post-badge-new"><?php esc_html_e( 'New', 'charitable' ); ?></span>
								<?php endif; ?>
								<?php if ( 'pro' === $update['badge'] ) : ?>
									<span class="charitable-dashboard-v2-blog-post-badge-pro"><?php esc_html_e( 'Pro', 'charitable' ); ?></span>
								<?php elseif ( 'addon' === $update['badge'] ) : ?>
									<span class="charitable-dashboard-v2-blog-post-badge-addon"><?php esc_html_e( 'Addon', 'charitable' ); ?></span>
								<?php endif; ?>
							</div>
							<h5 class="charitable-dashboard-v2-blog-post-title">
								<a href="<?php echo esc_url( $update['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $update['title'] ); ?></a>
							</h5>
							<?php if ( ! empty( $update['description'] ) ) : ?>
								<p class="charitable-dashboard-v2-blog-post-desc"><?php echo esc_html( $update['description'] ); ?> <a href="<?php echo esc_url( $update['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-blog-post-learn-more"><?php esc_html_e( 'Learn More', 'charitable' ); ?> →</a></p>
							<?php endif; ?>
						</div>
						<div class="charitable-dashboard-v2-blog-post-right">
							<?php if ( ! empty( $update['image'] ) ) : ?>
								<a href="<?php echo esc_url( $update['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-blog-post-thumb charitable-dashboard-v2-blog-post-thumb--bg" style="background-image:url('<?php echo esc_url( $update['image'] ); ?>');">
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( $update['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-blog-post-thumb">
									<svg width="96" height="64" viewBox="0 0 96 64" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect width="96" height="64" rx="6" fill="#E5E7EB"/>
										<path d="M16 46L30 29L46 39L62 21L82 33" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										<circle cx="30" cy="29" r="2.5" fill="#9CA3AF"/>
										<circle cx="46" cy="39" r="2.5" fill="#9CA3AF"/>
										<circle cx="62" cy="21" r="2.5" fill="#9CA3AF"/>
									</svg>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		}

		/**
		 * Render update features.
		 *
		 * @since 1.8.8
		 */
		private function render_update_features() {
			$features = array(
				__( 'Enhancement Payments', 'charitable' ),
				__( 'Reply-To Donors', 'charitable' ),
				__( 'Square Gateway', 'charitable' ),
				__( 'DonorTrust', 'charitable' )
			);

			foreach ( $features as $feature ) :
				?>
				<span class="charitable-dashboard-v2-latest-updates-feature">
					<svg class="charitable-dashboard-v2-latest-updates-checkmark" width="9" height="7" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M9.03612 1.89488C9.03612 1.74898 8.97797 1.60391 8.87287 1.49973L8.08169 0.708539C7.9775 0.603482 7.8316 0.545288 7.68657 0.545288C7.54067 0.545288 7.39564 0.603482 7.29054 0.708539L3.47366 4.52891L1.76365 2.81371C1.65855 2.70861 1.51352 2.65041 1.36762 2.65041C1.22259 2.65041 1.07669 2.70861 0.972505 2.81371L0.181318 3.60485C0.0762192 3.70908 0.0180664 3.85498 0.0180664 4.00001C0.0180664 4.14504 0.0762192 4.29094 0.181318 4.39517L3.0785 7.29148C3.18273 7.39658 3.32863 7.45473 3.47366 7.45473C3.61956 7.45473 3.76459 7.39658 3.86969 7.29148L8.87287 2.29C8.97797 2.18581 9.03612 2.03991 9.03612 1.89488Z" fill="#59A56D"/>
					</svg>
					<?php echo esc_html( $feature ); ?>
				</span>
				<?php
			endforeach;
		}

		/**
		 * Render blog posts.
		 *
		 * @since 1.8.8
		 */
	private function render_blog_posts() {
		$posts = $this->get_blog_posts();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_simulating = isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) );
		$clear_cache = isset( $_GET['charitable_clear_cache'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_clear_cache'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended


			?>
			<div class="charitable-dashboard-v2-blog-posts" id="charitable-blog-posts-container">

				<?php if ( $is_simulating || $clear_cache || ! $posts ) : ?>
				<div class="charitable-dashboard-v2-blog-post-row error-display">
					<h5 class="charitable-dashboard-v2-blog-post-title">Unable to load blog posts. Please check back later.</h5>
				</div>
				<?php else : ?>
				<?php foreach ( $posts as $post ) : ?>
					<div class="charitable-dashboard-v2-blog-post-row<?php echo ! empty( $post['featured'] ) ? ' featured' : ''; ?><?php echo ! empty( $post['error'] ) ? ' error' : ''; ?>">
						<div class="charitable-dashboard-v2-blog-post-left">
							<?php if ( ! empty( $post['timestamp'] ) ) : ?>
								<div class="charitable-dashboard-v2-blog-post-timestamp"><?php echo esc_html( $post['timestamp'] ); ?></div>
							<?php endif; ?>
							<h5 class="charitable-dashboard-v2-blog-post-title">
								<?php if ( ! empty( $post['error'] ) ) : ?>
									<span class="charitable-dashboard-v2-blog-post-error"><?php echo esc_html( $post['title'] ); ?></span>
								<?php else : ?>
									<a href="<?php echo esc_url( $post['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $post['title'] ); ?></a>
								<?php endif; ?>
							</h5>
						</div>
						<div class="charitable-dashboard-v2-blog-post-right">
							<?php if ( ! empty( $post['error'] ) ) : ?>
								<div class="charitable-dashboard-v2-blog-post-error-icon">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#f56565"/>
									</svg>
								</div>
							<?php else : ?>
								<?php if ( ! empty( $post['image'] ) && is_array( $post['image'] ) ) : ?>
									<?php if ( ! empty( $post['image']['img'] ) ) : ?>
										<!-- Actual image -->
										<img src="<?php echo esc_url( $post['image']['img'] ); ?>" alt="<?php echo esc_attr( $post['title'] ); ?>" class="charitable-dashboard-v2-blog-post-image" width="106" height="56" style="object-fit: cover; border-radius: 4px;" />
									<?php elseif ( ! empty( $post['image']['svg'] ) ) : ?>
										<!-- Custom SVG placeholder -->
										<?php echo wp_kses_post( $post['image']['svg'] ); ?>
									<?php else : ?>
										<!-- Default SVG placeholder -->
										<svg class="charitable-dashboard-v2-blog-post-image-placeholder" width="106" height="56" viewBox="0 0 106 56" fill="none" xmlns="http://www.w3.org/2000/svg">
											<rect width="106" height="56" rx="4" fill="#E5E7EB"/>
											<path d="M20 40L35 25L50 35L65 20L80 30L95 15" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											<circle cx="35" cy="25" r="3" fill="#9CA3AF"/>
											<circle cx="50" cy="35" r="3" fill="#9CA3AF"/>
											<circle cx="65" cy="20" r="3" fill="#9CA3AF"/>
											<circle cx="80" cy="30" r="3" fill="#9CA3AF"/>
											<circle cx="95" cy="15" r="3" fill="#9CA3AF"/>
										</svg>
									<?php endif; ?>
								<?php else : ?>
									<!-- Fallback default SVG placeholder -->
									<svg class="charitable-dashboard-v2-blog-post-image-placeholder" width="106" height="56" viewBox="0 0 106 56" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect width="106" height="56" rx="4" fill="#E5E7EB"/>
										<path d="M20 40L35 25L50 35L65 20L80 30L95 15" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										<circle cx="35" cy="25" r="3" fill="#9CA3AF"/>
										<circle cx="50" cy="35" r="3" fill="#9CA3AF"/>
										<circle cx="65" cy="20" r="3" fill="#9CA3AF"/>
										<circle cx="80" cy="30" r="3" fill="#9CA3AF"/>
										<circle cx="95" cy="15" r="3" fill="#9CA3AF"/>
									</svg>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php


		}

		/**
		 * Fetch blog posts from feed.
		 *
		 * @since 1.8.8
		 *
		 * @return array|false Returns array on success, false on failure
		 */
	private function fetch_blog_posts_feed() {

		// Allow simulating API failure for testing
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) ) ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return false;
		}

			$args = [
				'timeout'    => 10,
				'sslverify'  => false,
				'user-agent' => charitable_get_default_user_agent(),
			];

			$response = wp_remote_get(
				self::BLOG_POSTS_SOURCE_URL,
				$args
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = wp_remote_retrieve_body( $response );

			if ( empty( $body ) ) {
				return false;
			}

			$decoded = json_decode( $body, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}


			return $this->verify_blog_posts( $decoded );
		}

		/**
		 * Calculate relative time from a date string.
		 *
		 * @since 1.8.8.4
		 *
		 * @param string $date_string Date string in format 'Y-m-d H:i:s'.
		 *
		 * @return string Relative time string (e.g., '2 days ago', '1 week ago').
		 */
		private function calculate_relative_time( $date_string ) {
			if ( empty( $date_string ) ) {
				return '';
			}

			$post_date = strtotime( $date_string );
			$current_time = current_time( 'timestamp' );
			$time_diff = $current_time - $post_date;

			// If the date is in the future, return empty string
			if ( $time_diff < 0 ) {
				return '';
			}

			// Calculate relative time
			$minutes = floor( $time_diff / 60 );
			$hours = floor( $time_diff / 3600 );
			$days = floor( $time_diff / 86400 );
			$weeks = floor( $time_diff / 604800 );
			$months = floor( $time_diff / 2592000 );
			$years = floor( $time_diff / 31536000 );

			if ( $years > 0 ) {
				/* translators: %s: number of years */
				return $years === 1 ? _x( '1 year ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s year ago', '%s years ago', $years, 'relative time', 'charitable' ), number_format_i18n( $years ) );
			} elseif ( $months > 0 ) {
				/* translators: %s: number of months */
				return $months === 1 ? _x( '1 month ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s month ago', '%s months ago', $months, 'relative time', 'charitable' ), number_format_i18n( $months ) );
			} elseif ( $weeks > 0 ) {
				/* translators: %s: number of weeks */
				return $weeks === 1 ? _x( '1 week ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s week ago', '%s weeks ago', $weeks, 'relative time', 'charitable' ), number_format_i18n( $weeks ) );
			} elseif ( $days > 0 ) {
				/* translators: %s: number of days */
				return $days === 1 ? _x( '1 day ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s day ago', '%s days ago', $days, 'relative time', 'charitable' ), number_format_i18n( $days ) );
			} elseif ( $hours > 0 ) {
				/* translators: %s: number of hours */
				return $hours === 1 ? _x( '1 hour ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s hour ago', '%s hours ago', $hours, 'relative time', 'charitable' ), number_format_i18n( $hours ) );
			} elseif ( $minutes > 0 ) {
				/* translators: %s: number of minutes */
				return $minutes === 1 ? _x( '1 minute ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s minute ago', '%s minutes ago', $minutes, 'relative time', 'charitable' ), number_format_i18n( $minutes ) );
			} else {
				return _x( 'Just now', 'relative time', 'charitable' );
			}
		}

		/**
		 * Verify blog posts data before it is saved.
		 *
		 * @since 1.8.8
		 *
		 * @param array $blog_posts Array of blog posts items to verify.
		 *
		 * @return array
		 */
		private function verify_blog_posts( $blog_posts ) {

			$data = [];

			if ( ! is_array( $blog_posts ) || empty( $blog_posts ) ) {
				return $data;
			}

			// Check if we have the expected structure
			if ( ! isset( $blog_posts['blog_posts'] ) || ! is_array( $blog_posts['blog_posts'] ) ) {
				return $data;
			}

			// Store version information if available
			if ( isset( $blog_posts['meta'] ) && is_array( $blog_posts['meta'] ) ) {
				$this->version_info = array(
					'current_charitable_version_lite' => ! empty( $blog_posts['meta']['current_charitable_version_lite'] ) ? sanitize_text_field( $blog_posts['meta']['current_charitable_version_lite'] ) : '',
					'current_charitable_version_pro' => ! empty( $blog_posts['meta']['current_charitable_version_pro'] ) ? sanitize_text_field( $blog_posts['meta']['current_charitable_version_pro'] ) : '',
				);
			}

			foreach ( $blog_posts['blog_posts'] as $post ) {

				// Verify required fields
				if ( empty( $post['title'] ) || empty( $post['url'] ) ) {
					continue;
				}

				// Handle image data
				$image_data = array(
					'placeholder' => true,
					'svg' => ''
				);

				if ( ! empty( $post['image'] ) && is_array( $post['image'] ) ) {
					// Handle placeholder as both boolean and string
					$placeholder_value = $post['image']['placeholder'] ?? true;
					if ( is_string( $placeholder_value ) ) {
						// Handle string values: 'false', '0', 'no' should be false
						$placeholder_value = in_array( strtolower( $placeholder_value ), ['false', '0', 'no', ''], true ) ? false : true;
					}
					$image_data['placeholder'] = (bool) $placeholder_value;

					if ( ! empty( $post['image']['img'] ) ) {
						$image_data['img'] = esc_url_raw( $post['image']['img'] );
					}

					if ( ! empty( $post['image']['svg'] ) ) {
						$image_data['svg'] = $post['image']['svg']; // SVG is already sanitized HTML
					}
				}

				// Calculate timestamp from date field if available, otherwise use provided timestamp
				$calculated_timestamp = '';
				if ( ! empty( $post['date'] ) ) {
					$calculated_timestamp = $this->calculate_relative_time( $post['date'] );
				} elseif ( ! empty( $post['timestamp'] ) ) {
					$calculated_timestamp = sanitize_text_field( $post['timestamp'] );
				}

				$data[] = array(
					'title'     => sanitize_text_field( $post['title'] ),
					'url'       => esc_url_raw( $post['url'] ),
					'timestamp' => $calculated_timestamp,
					'featured'  => ! empty( $post['featured'] ) ? (bool) $post['featured'] : false,
					'image'     => $image_data,
				);

			}

			return $data;
		}

		/**
		 * Get blog posts from transient cache.
		 *
		 * @since 1.8.8
		 *
		 * @return array
		 */
		private function get_blog_posts_from_cache() {

			$cached_data = get_transient( 'charitable_blog_posts_cache' );

			if ( false === $cached_data ) {
				return [];
			}

			return (array) $cached_data;
		}

		/**
		 * Cache blog posts data using transients.
		 *
		 * @since 1.8.8
		 *
		 * @param array $blog_posts Blog posts data to cache.
		 */
		private function cache_blog_posts( $blog_posts ) {

			$data = [
				'update'       => time(),
				'blog_posts'   => $blog_posts,
				'version_info' => $this->version_info,
			];

			// Cache for 24 hours
			set_transient( 'charitable_blog_posts_cache', $data, DAY_IN_SECONDS );
		}

		/**
		 * Get blog posts data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_blog_posts() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_simulating = isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) );
		$clear_cache = isset( $_GET['charitable_clear_cache'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_clear_cache'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

			// If simulating API failure, always return error
			if ( $is_simulating ) {
				return $this->get_blog_posts_error();
			}

			// Clear cache if requested
			if ( $clear_cache ) {
				$this->clear_blog_posts_cache();
			}

			// Try to get from cache first
			$cached_data = $this->get_blog_posts_from_cache();
			if ( ! empty( $cached_data ) && ! $clear_cache ) {
				// Restore version info from cache
				if ( isset( $cached_data['version_info'] ) ) {
					$this->version_info = $cached_data['version_info'];
				}
				return $cached_data['blog_posts'] ?? [];
			}

			// Fetch fresh data from API
			$blog_posts = $this->fetch_blog_posts_feed();

			// If API fetch was successful, cache and return the data
			if ( false !== $blog_posts && ! empty( $blog_posts ) ) {
				$this->cache_blog_posts( $blog_posts );
				return $blog_posts;
			} else {
				// API failed, show error
				return $this->get_blog_posts_error();
			}
		}

		/**
		 * Clear blog posts cache (for testing purposes).
		 *
		 * @since 1.8.8
		 */
		private function clear_blog_posts_cache() {
			delete_transient( 'charitable_blog_posts_cache' );
		}

		/**
		 * Get blog posts error data when API fails and no cache is available.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_blog_posts_error() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_simulating = isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$error_title = $is_simulating ? __( 'Unable to load blog posts (Simulated API Failure)', 'charitable' ) : __( 'Unable to load blog posts', 'charitable' );

			return array(
				array(
					'title' => $error_title,
					'url' => '#',
					'timestamp' => '',
					'featured' => false,
					'error' => true
				)
			);
		}

		/**
		 * Check if blog posts contain an error.
		 *
		 * @since 1.8.8
		 * @param array $posts Blog posts array.
		 * @return bool True if posts contain an error.
		 */
		private function has_blog_posts_error( $posts ) {
			if ( empty( $posts ) ) {
				return true;
			}

			foreach ( $posts as $post ) {
				if ( ! empty( $post['error'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Calculate how many updates behind the user is.
		 *
		 * @since 1.8.8
		 * @return int Number of updates behind, or 0 if up to date or unable to determine.
		 */
		private function get_updates_behind() {
			// Get current installed version
			$current_version = charitable()->get_version();

			// Get latest version from API
			$latest_version = $this->get_latest_version();

			if ( empty( $latest_version ) ) {
				return 0;
			}

			return $this->compare_versions( $current_version, $latest_version );
		}

		/**
		 * Get the latest version from the API data.
		 *
		 * @since 1.8.8
		 * @return string Latest version or empty string if not available.
		 */
		private function get_latest_version() {
			if ( null === $this->version_info ) {
				// Try to get version info from cached data
				$cached_data = get_transient( 'charitable_blog_posts_cache' );
				if ( false !== $cached_data && isset( $cached_data['version_info'] ) ) {
					$this->version_info = $cached_data['version_info'];
				}
			}

			if ( null === $this->version_info ) {
				return '';
			}

			// For now, we're working with Lite version
			return ! empty( $this->version_info['current_charitable_version_lite'] ) ? $this->version_info['current_charitable_version_lite'] : '';
		}

		/**
		 * Compare two version strings and return how many updates behind.
		 *
		 * @since 1.8.8
		 * @param string $current Current version (e.g., "1.8.4").
		 * @param string $latest Latest version (e.g., "1.8.8").
		 * @return int Number of updates behind.
		 */
		private function compare_versions( $current, $latest ) {
			// Normalize versions by removing any non-numeric characters except dots
			$current = preg_replace( '/[^0-9.]/', '', $current );
			$latest = preg_replace( '/[^0-9.]/', '', $latest );

			// Split into parts
			$current_parts = array_map( 'intval', explode( '.', $current ) );
			$latest_parts = array_map( 'intval', explode( '.', $latest ) );

			// Ensure both arrays have the same length by padding with zeros
			$max_length = max( count( $current_parts ), count( $latest_parts ) );
			$current_parts = array_pad( $current_parts, $max_length, 0 );
			$latest_parts = array_pad( $latest_parts, $max_length, 0 );

			// Compare each part
			for ( $i = 0; $i < $max_length; $i++ ) {
				if ( $latest_parts[ $i ] > $current_parts[ $i ] ) {
					// Calculate the difference
					$updates_behind = 0;
					for ( $j = $i; $j < $max_length; $j++ ) {
						$updates_behind += $latest_parts[ $j ] - $current_parts[ $j ];
					}
					return $updates_behind;
				} elseif ( $latest_parts[ $i ] < $current_parts[ $i ] ) {
					// Current version is newer than latest (shouldn't happen)
					return 0;
				}
			}

			// Versions are equal
			return 0;
		}

		/**
		 * Test version comparison logic (for debugging).
		 *
		 * @since 1.8.8
		 * @return void
		 */
		public function test_version_comparison() {
			// Test cases
			$test_cases = array(
				array( '1.8.4', '1.8.8', 4 ),
				array( '1.8.4.2', '1.8.8', 4 ),
				array( '1.8.7.5', '1.8.8', 1 ),
				array( '1.8.8', '1.8.8', 0 ),
				array( '1.8.9', '1.8.8', 0 ),
				array( '1.7.5', '1.8.2', 5 ),
			);

			foreach ( $test_cases as $test ) {
				$result = $this->compare_versions( $test[0], $test[1] );
				$status = ( $result === $test[2] ) ? 'PASS' : 'FAIL';
			}
		}

		/**
		 * Render the quick access section.
		 *
		 * @since 1.8.8
		 * @version 1.8.8.6
		 */
		public function render_quick_access_section() {
			?>
			<section id="charitable-dashboard-v2-quick-access" class="charitable-dashboard-v2-section charitable-dashboard-v2-collapsible">
				<header class="charitable-dashboard-v2-section-header">
					<h3>Quick Access</h3>
					<a href="#" class="charitable-dashboard-v2-toggle">
						<i class="fa fa-angle-down charitable-dashboard-v2-angle-down"></i>
					</a>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<div class="charitable-dashboard-v2-quick-access-content">
						<div class="charitable-dashboard-v2-quick-access-items">
							<?php $this->render_quick_access_items(); ?>
						</div>
					</div>
					<footer class="charitable-dashboard-v2-quick-access-footer">
						<div class="charitable-dashboard-v2-quick-access-footer-content">
							<div class="charitable-dashboard-v2-quick-access-footer-left">
								<a href="https://wpcharitable.com/charitable-admin-review/" class="charitable-dashboard-v2-rate-link" target="_blank" rel="noopener noreferrer">
									<span class="charitable-dashboard-v2-rate-text">Rate us 5 stars</span>
									<svg class="charitable-dashboard-v2-arrow-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</div>
							<div class="charitable-dashboard-v2-quick-access-footer-right">
								<div class="charitable-dashboard-v2-stars">
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
								</div>
							</div>
						</div>
					</footer>
				</div>
			</section>
			<?php
		}

		/**
		 * Render quick access items.
		 *
		 * @since 1.8.8
		 */
		private function render_quick_access_items() {
			$items = $this->get_quick_access_items();

			foreach ( $items as $item ) :
				?>
				<div class="charitable-dashboard-v2-quick-access-item">
					<div class="charitable-dashboard-v2-quick-access-icon">
						<?php
						// Only allow specific SVG-related tags and attributes for safety.
						echo wp_kses(
							$item['icon'],
							array(
								'svg'   => array(
									'xmlns'       => true,
									'width'       => true,
									'height'      => true,
									'viewbox'     => true,
									'fill'        => true,
									'class'       => true,
									'aria-hidden' => true,
									'role'        => true,
								),
								'path'  => array(
									'd'             => true,
									'fill'          => true,
									'stroke'        => true,
									'stroke-width'  => true,
									'stroke-linecap' => true,
									'stroke-linejoin'=> true,
								),
								'rect'  => array(
									'x'      => true,
									'y'      => true,
									'width'  => true,
									'height' => true,
									'fill'   => true,
								),
								'mask'  => array(
									'id'            => true,
									'style'         => true,
									'maskunits'     => true,
									'x'             => true,
									'y'             => true,
									'width'         => true,
									'height'        => true,
								),
								'g'     => array(
									'mask' => true,
								),
							)
						);
						?>
					</div>
					<div class="charitable-dashboard-v2-quick-access-title">
						<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank"><?php echo esc_html( $item['title'] ); ?></a>
					</div>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Get quick access items data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
		private function get_quick_access_items() {
			return array(
				array(
					'title' => __( 'Getting Started', 'charitable' ),
					'url' => 'https://wpcharitable.com/start-here/?referrer=chariable-dashboard',
					'icon' => '<svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M11 20C10.2 19.3667 9.33333 18.875 8.4 18.525C7.46667 18.175 6.5 18 5.5 18C4.8 18 4.11267 18.0917 3.438 18.275C2.76267 18.4583 2.11667 18.7167 1.5 19.05C1.15 19.2333 0.812667 19.225 0.488 19.025C0.162667 18.825 0 18.5333 0 18.15V6.1C0 5.91667 0.046 5.74167 0.138 5.575C0.229333 5.40833 0.366667 5.28333 0.55 5.2C1.31667 4.8 2.11667 4.5 2.95 4.3C3.78333 4.1 4.63333 4 5.5 4C6.46667 4 7.41267 4.125 8.338 4.375C9.26267 4.625 10.15 5 11 5.5V17.6C11.85 17.0667 12.7417 16.6667 13.675 16.4C14.6083 16.1333 15.55 16 16.5 16C17.1 16 17.6877 16.05 18.263 16.15C18.8377 16.25 19.4167 16.4 20 16.6V4.6C20.25 4.68333 20.496 4.77067 20.738 4.862C20.9793 4.954 21.2167 5.06667 21.45 5.2C21.6333 5.28333 21.771 5.40833 21.863 5.575C21.9543 5.74167 22 5.91667 22 6.1V18.15C22 18.5333 21.8377 18.825 21.513 19.025C21.1877 19.225 20.85 19.2333 20.5 19.05C19.8833 18.7167 19.2373 18.4583 18.562 18.275C17.8873 18.0917 17.2 18 16.5 18C15.5 18 14.5333 18.175 13.6 18.525C12.6667 18.875 11.8 19.3667 11 20ZM13 15V5.5L18 0.5V10.5L13 15ZM9 16.625V6.725C8.45 6.49167 7.87933 6.31267 7.288 6.188C6.696 6.06267 6.1 6 5.5 6C4.88333 6 4.28333 6.05833 3.7 6.175C3.11667 6.29167 2.55 6.46667 2 6.7V16.625C2.58333 16.4083 3.16267 16.25 3.738 16.15C4.31267 16.05 4.9 16 5.5 16C6.1 16 6.68733 16.05 7.262 16.15C7.83733 16.25 8.41667 16.4083 9 16.625Z" fill="#31714c"/>
					</svg>'
				),
				array(
					'title' => __( 'Support Ticket', 'charitable' ),
					'url' => 'https://wpcharitable.com/support?referrer=chariable-dashboard',
					'icon' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M6 9C6.28333 9 6.521 8.904 6.713 8.712C6.90433 8.52067 7 8.28333 7 8C7 7.71667 6.90433 7.479 6.713 7.287C6.521 7.09567 6.28333 7 6 7C5.71667 7 5.479 7.09567 5.287 7.287C5.09567 7.479 5 7.71667 5 8C5 8.28333 5.09567 8.52067 5.287 8.712C5.479 8.904 5.71667 9 6 9ZM10 9C10.2833 9 10.521 8.904 10.713 8.712C10.9043 8.52067 11 8.28333 11 8C11 7.71667 10.9043 7.479 10.713 7.287C10.521 7.09567 10.2833 7 10 7C9.71667 7 9.47933 7.09567 9.288 7.287C9.096 7.479 9 7.71667 9 8C9 8.28333 9.096 8.52067 9.288 8.712C9.47933 8.904 9.71667 9 10 9ZM14 9C14.2833 9 14.5207 8.904 14.712 8.712C14.904 8.52067 15 8.28333 15 8C15 7.71667 14.904 7.479 14.712 7.287C14.5207 7.09567 14.2833 7 14 7C13.7167 7 13.4793 7.09567 13.288 7.287C13.096 7.479 13 7.71667 13 8C13 8.28333 13.096 8.52067 13.288 8.712C13.4793 8.904 13.7167 9 14 9ZM0 20V2C0 1.45 0.196 0.979 0.588 0.587C0.979333 0.195667 1.45 0 2 0H18C18.55 0 19.021 0.195667 19.413 0.587C19.8043 0.979 20 1.45 20 2V14C20 14.55 19.8043 15.021 19.413 15.413C19.021 15.8043 18.55 16 18 16H4L0 20ZM2 15.175L3.175 14H18V2H2V15.175Z" fill="#31714c"/>
					</svg>'
				),
				array(
					'title' => __( 'Documentation', 'charitable' ),
					'url' => 'https://wpcharitable.com/documentation?referrer=chariable-dashboard',
					'icon' => '<svg width="22" height="16" viewBox="0 0 22 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M13 5.9V4.2C13.55 3.96667 14.1127 3.79167 14.688 3.675C15.2627 3.55833 15.8667 3.5 16.5 3.5C16.9333 3.5 17.3583 3.53333 17.775 3.6C18.1917 3.66667 18.6 3.75 19 3.85V5.45C18.6 5.3 18.196 5.18767 17.788 5.113C17.3793 5.03767 16.95 5 16.5 5C15.8667 5 15.2583 5.07933 14.675 5.238C14.0917 5.396 13.5333 5.61667 13 5.9ZM13 11.4V9.7C13.55 9.46667 14.1127 9.29167 14.688 9.175C15.2627 9.05833 15.8667 9 16.5 9C16.9333 9 17.3583 9.03333 17.775 9.1C18.1917 9.16667 18.6 9.25 19 9.35V10.95C18.6 10.8 18.196 10.6877 17.788 10.613C17.3793 10.5377 16.95 10.5 16.5 10.5C15.8667 10.5 15.2583 10.575 14.675 10.725C14.0917 10.875 13.5333 11.1 13 11.4ZM13 8.65V6.95C13.55 6.71667 14.1127 6.54167 14.688 6.425C15.2627 6.30833 15.8667 6.25 16.5 6.25C16.9333 6.25 17.3583 6.28333 17.775 6.35C18.1917 6.41667 18.6 6.5 19 6.6V8.2C18.6 8.05 18.196 7.93767 17.788 7.863C17.3793 7.78767 16.95 7.75 16.5 7.75C15.8667 7.75 15.2583 7.82933 14.675 7.988C14.0917 8.146 13.5333 8.36667 13 8.65ZM5.5 12C6.28333 12 7.046 12.0873 7.788 12.262C8.52933 12.4373 9.26667 12.7 10 13.05V3.2C9.31667 2.8 8.59167 2.5 7.825 2.3C7.05833 2.1 6.28333 2 5.5 2C4.9 2 4.30433 2.05833 3.713 2.175C3.121 2.29167 2.55 2.46667 2 2.7V12.6C2.58333 12.4 3.16267 12.25 3.738 12.15C4.31267 12.05 4.9 12 5.5 12ZM12 13.05C12.7333 12.7 13.471 12.4373 14.213 12.262C14.9543 12.0873 15.7167 12 16.5 12C17.1 12 17.6877 12.05 18.263 12.15C18.8377 12.25 19.4167 12.4 20 12.6V2.7C19.45 2.46667 18.8793 2.29167 18.288 2.175C17.696 2.05833 17.1 2 16.5 2C15.7167 2 14.9417 2.1 14.175 2.3C13.4083 2.5 12.6833 2.8 12 3.2V13.05ZM11 16C10.2 15.3667 9.33333 14.875 8.4 14.525C7.46667 14.175 6.5 14 5.5 14C4.8 14 4.11267 14.0917 3.438 14.275C2.76267 14.4583 2.11667 14.7167 1.5 15.05C1.15 15.2333 0.812667 15.225 0.488 15.025C0.162667 14.825 0 14.5333 0 14.15V2.1C0 1.91667 0.046 1.74167 0.138 1.575C0.229333 1.40833 0.366667 1.28333 0.55 1.2C1.31667 0.8 2.11667 0.5 2.95 0.3C3.78333 0.1 4.63333 0 5.5 0C6.46667 0 7.41267 0.125 8.338 0.375C9.26267 0.625 10.15 1 11 1.5C11.85 1 12.7377 0.625 13.663 0.375C14.5877 0.125 15.5333 0 16.5 0C17.3667 0 18.2167 0.1 19.05 0.3C19.8833 0.5 20.6833 0.8 21.45 1.2C21.6333 1.28333 21.771 1.40833 21.863 1.575C21.9543 1.74167 22 1.91667 22 2.1V14.15C22 14.5333 21.8377 14.825 21.513 15.025C21.1877 15.225 20.85 15.2333 20.5 15.05C19.8833 14.7167 19.2373 14.4583 18.562 14.275C17.8873 14.0917 17.2 14 16.5 14C15.5 14 14.5333 14.175 13.6 14.525C12.6667 14.875 11.8 15.3667 11 16Z" fill="#31714c"/>
					</svg>'
				),
				array(
					'title' => __( 'Upgrade To Pro', 'charitable' ),
					'url' => 'https://wpcharitable.com/lite-vs-pro?referrer=chariable-dashboard',
					'icon' => '<svg width="16" height="21" viewBox="0 0 16 21" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5.675 11.7L6.55 8.85L4.25 7H7.1L8 4.2L8.9 7H11.75L9.425 8.85L10.3 11.7L8 9.925L5.675 11.7ZM2 21V13.275C1.36667 12.575 0.875 11.775 0.525 10.875C0.175 9.975 0 9.01667 0 8C0 5.76667 0.775 3.875 2.325 2.325C3.875 0.775 5.76667 0 8 0C10.2333 0 12.125 0.775 13.675 2.325C15.225 3.875 16 5.76667 16 8C16 9.01667 15.825 9.975 15.475 10.875C15.125 11.775 14.6333 12.575 14 13.275V21L8 19L2 21ZM8 14C9.66667 14 11.0833 13.4167 12.25 12.25C13.4167 11.0833 14 9.66667 14 8C14 6.33333 13.4167 4.91667 12.25 3.75C11.0833 2.58333 9.66667 2 8 2C6.33333 2 4.91667 2.58333 3.75 2.325C2.58333 3.875 2 5.76667 2 8C2 9.66667 2.58333 11.0833 3.75 12.25C4.91667 13.4167 6.33333 14 8 14ZM4 18.025L8 17L12 18.025V14.925C11.4167 15.2583 10.7875 15.5208 10.1125 15.7125C9.4375 15.9042 8.73333 16 8 16C7.26667 16 6.5625 15.9042 5.8875 15.7125C5.2125 15.5208 4.58333 15.2583 4 14.925V18.025Z" fill="#31714c"/>
					</svg>'
				)
			);
		}

		/**
		 * Render dashboard scripts.
		 *
		 * @since 1.8.8
		 */
		public function render_dashboard_scripts() {
			$charitable_dashboard_legacy = Charitable_Dashboard_Legacy::get_instance();
			$charitable_dashboard_legacy->generate_dashboard_report_html();
			$donation_axis = $charitable_dashboard_legacy->get_donation_axis();
			$date_axis = $charitable_dashboard_legacy->get_date_axis();

			// Decode HTML entities in the donation axis data and extract numeric values
			if ( is_array( $donation_axis ) ) {
				$donation_axis = array_map( function( $value ) {
					$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					if ( preg_match( '/(\d+\.?\d*)/', $decoded, $matches ) ) {
						return floatval( $matches[1] );
					}
					return 0;
				}, $donation_axis );
			}
			?>
			<?php
			// Decode HTML entities in currency symbol before passing to JavaScript.
			// The currency symbol comes as HTML entities (e.g., &#36; for $), so we decode it
			// before using esc_js() to avoid double-encoding issues.
			$currency_helper = charitable_get_currency_helper();
			$currency_symbol = html_entity_decode( $currency_helper->get_currency_symbol(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$decimal_count = $currency_helper->get_decimals();
			?>
			<script id="charitable-dashboard-report-data-js">
				var charitable_dashboard_reporting = {
					version: '<?php echo esc_js( Charitable::VERSION ); ?>',
					user_id: '<?php echo esc_js( get_current_user_id() ); ?>',
					nonce: '<?php echo esc_js( wp_create_nonce( 'charitable-reporting' ) ); ?>',
					dashboard_nonce: '<?php echo esc_js( wp_create_nonce( 'charitable-dashboard' ) ); ?>',
					admin_nonce: '<?php echo esc_js( wp_create_nonce( 'charitable-admin' ) ); ?>',
					currency_symbol: '<?php echo esc_js( $currency_symbol ); ?>',
					decimal_count: <?php echo (int) $decimal_count; ?>,
					ajax_url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					date_select_day: 'DD',
					date_select_month: 'MM',
					strings: {
						activation_failed: '<?php echo esc_js( __( 'Activation failed. Please try again.', 'charitable' ) ); ?>',
						error_occurred: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'charitable' ) ); ?>',
						approved: '<?php echo esc_js( __( 'Approved', 'charitable' ) ); ?>',
						no_comments_title: '<?php echo esc_js( __( 'No donor comments yet.', 'charitable' ) ); ?>',
						no_comments_message: '<?php echo esc_js( __( 'Donor comments will appear here once donors start leaving messages with their donations.', 'charitable' ) ); ?>'
					},
					headline_chart_options: {
						donation_axis: <?php echo wp_json_encode( (array) $donation_axis ); ?>,
						date_axis: <?php echo wp_json_encode( (array) $date_axis ); ?>,
						enable_tooltips: <?php echo $charitable_dashboard_legacy->enable_tooltips() ? 'true' : 'false'; ?>
					},
					payment_methods_chart_options: {
						payment_percentages: [],
						payment_labels: []
					}
				};
			</script>

			<script id="charitable-dashboard-tabs-js">
				document.addEventListener('DOMContentLoaded', function() {
					// Tab switching functionality
					const tabNavItems = document.querySelectorAll('.charitable-dashboard-v2-tab-nav-item');
					const tabContents = document.querySelectorAll('.charitable-dashboard-v2-tab-content');

					// Function to switch tabs
					function switchTab(targetTab) {
						// Remove active classes from all tabs and contents
						tabNavItems.forEach(item => item.classList.remove('charitable-dashboard-v2-tab-nav-active'));
						tabContents.forEach(content => content.classList.remove('charitable-dashboard-v2-tab-content-active'));

						// Add active class to selected tab and content
						const activeTab = document.querySelector(`[data-tab="${targetTab}"]`);
						const activeContent = document.querySelector(`.charitable-dashboard-v2-tab-content[data-tab="${targetTab}"]`);

						if (activeTab) {
							activeTab.classList.add('charitable-dashboard-v2-tab-nav-active');
						}
						if (activeContent) {
							activeContent.classList.add('charitable-dashboard-v2-tab-content-active');
						}

						// Update URL without page reload
						const url = new URL(window.location);
						url.searchParams.set('tab', targetTab);
						window.history.pushState({}, '', url);
					}

					// Add click event listeners to tab navigation
					tabNavItems.forEach(item => {
						item.addEventListener('click', function(e) {
							e.preventDefault();
							const targetTab = this.getAttribute('data-tab');
							switchTab(targetTab);
						});
					});

					// Handle browser back/forward buttons
					window.addEventListener('popstate', function() {
						const urlParams = new URLSearchParams(window.location.search);
						const tab = urlParams.get('tab') || 'top-campaigns';
						switchTab(tab);
					});

					// Addon activation functionality
					const activateButton = document.getElementById('charitable-activate-donor-comments');
					if (activateButton) {
						activateButton.addEventListener('click', function(e) {
							e.preventDefault();

							const plugin = this.getAttribute('data-plugin');
							const buttonText = this.querySelector('.button-text');
							const buttonLoading = this.querySelector('.button-loading');

							// Show loading state
							buttonText.style.display = 'none';
							buttonLoading.style.display = 'inline-flex';
							this.disabled = true;

							// Make AJAX request
							const formData = new FormData();
							formData.append('action', 'charitable_activate_addon');
							formData.append('plugin', plugin);
							formData.append('nonce', charitable_dashboard_v2_reporting.admin_nonce);

							fetch(charitable_dashboard_v2_reporting.ajax_url, {
								method: 'POST',
								body: formData
							})
							.then(response => response.json())
							.then(data => {
								if (data.success) {
									// Show success message and reload page
									alert(data.data.message);
									window.location.reload();
								} else {
									// Show error message
									alert(data.data.message || charitable_dashboard_v2_reporting.strings.activation_failed);

									// Reset button state
									buttonText.style.display = 'inline';
									buttonLoading.style.display = 'none';
									this.disabled = false;
								}
							})
							.catch(error => {
								console.error('Error:', error);
								alert(charitable_dashboard_v2_reporting.strings.error_occurred);

								// Reset button state
								buttonText.style.display = 'inline';
								buttonLoading.style.display = 'none';
								this.disabled = false;
							});
						});
					}

					// Comment actions functionality
					let isProcessing = false;

					// Rollback function - defined outside to be accessible
					function rollbackUI(originalState, actionElement) {
						// Restore original state for the specific action
						const actionText = actionElement.querySelector('.action-text');
						const actionLoading = actionElement.querySelector('.action-loading');

						actionText.textContent = originalState.actionText;
						actionText.style.display = 'inline';
						actionLoading.style.display = originalState.actionLoading;

						// Restore the icon
						const icon = actionElement.querySelector('svg, img');
						if (icon) {
							icon.style.display = originalState.icon;
						}

						// Re-enable all comment actions
						const allCommentActions = document.querySelectorAll('.comment-action');
						allCommentActions.forEach(btn => {
							btn.style.pointerEvents = 'auto';
							btn.style.opacity = '1';
						});

						isProcessing = false;
					}

					// Re-enable all comment actions function
					function reEnableAllActions() {
						const allCommentActions = document.querySelectorAll('.comment-action');
						allCommentActions.forEach(btn => {
							btn.style.pointerEvents = 'auto';
							btn.style.opacity = '1';
						});
						isProcessing = false;
					}

					const commentActions = document.querySelectorAll('.comment-action');
					commentActions.forEach(action => {
						action.addEventListener('click', function(e) {
							e.preventDefault();

							// Prevent multiple simultaneous requests
							if (isProcessing) {
								return;
							}

							const commentId = this.getAttribute('data-comment-id');
							const actionType = this.classList.contains('approve') ? 'approve' : 'delete';
							const actionText = this.querySelector('.action-text');
							const actionLoading = this.querySelector('.action-loading');
							const row = this.closest('tr');

							// Store original state for rollback
							const originalState = {
								actionText: actionText.textContent,
								actionLoading: actionLoading.style.display,
								icon: this.querySelector('svg, img').style.display,
								pointerEvents: this.style.pointerEvents
							};

							// Disable ALL comment actions in the entire comments area
							isProcessing = true;
							const allCommentActions = document.querySelectorAll('.comment-action');
							allCommentActions.forEach(btn => {
								btn.style.pointerEvents = 'none';
								btn.style.opacity = '0.5';
							});

							// Show loading state for this specific action
							actionText.style.display = 'none';
							actionLoading.style.display = 'inline-flex';

							// Hide the icon during processing
							const icon = this.querySelector('svg, img');
							if (icon) {
								icon.style.display = 'none';
							}

							// Make AJAX request
							const formData = new FormData();
							formData.append('action', 'charitable_' + actionType + '_comment');
							formData.append('comment_id', commentId);
							formData.append('nonce', charitable_dashboard_v2_reporting.admin_nonce);

							fetch(charitable_dashboard_v2_reporting.ajax_url, {
								method: 'POST',
								body: formData
							})
							.then(response => {
								if (!response.ok) {
									throw new Error('HTTP ' + response.status + ': ' + response.statusText);
								}
								return response.json();
							})
							.then(data => {
								if (data.success) {
									// Success - update UI with slight delay
									setTimeout(() => {
										if (actionType === 'approve') {
											// Replace with approved status
											const statusCell = row.querySelector('td:last-child');
											statusCell.innerHTML = '<span class="comment-status-approved">' + charitable_dashboard_v2_reporting.strings.approved + '</span>';
											// Re-enable all other actions after successful approve
											reEnableAllActions();
										} else if (actionType === 'delete') {
											// Fade out and remove row
											row.style.transition = 'opacity 0.3s ease';
											row.style.opacity = '0';
											setTimeout(() => {
												row.remove();
												// Check if table is now empty
												checkEmptyState();
												// Re-enable all other actions after successful delete
												reEnableAllActions();
											}, 300);
										}
									}, 200); // Slight delay as requested
								} else {
									// Error - rollback UI
									console.error('Charitable: AJAX error:', data.data.message);
									rollbackUI(originalState, this);
								}
							})
							.catch(error => {
								console.error('Charitable: Network error:', error);
								rollbackUI(originalState, this);
							});
						});
					});

					// Check if comments table is empty and show empty state
					function checkEmptyState() {
						const tbody = document.querySelector('.charitable-dashboard-v2-table-comments tbody');
						const visibleRows = tbody.querySelectorAll('tr[data-comment-id]');

						if (visibleRows.length === 0) {
							// Show empty state
							tbody.innerHTML = `
								<tr>
									<td colspan="2" class="charitable-dashboard-v2-no-comments">
										<p><strong>` + charitable_dashboard_v2_reporting.strings.no_comments_title + `</strong></p>
										<p>` + charitable_dashboard_v2_reporting.strings.no_comments_message + `</p>
									</td>
								</tr>
							`;
						}
					}
				});
			</script>
			<?php
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Dashboard
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
