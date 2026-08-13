<?php
/**
 * Charitable Notification Test Helper.
 *
 * Admin-only test harness for the notifications v2 system. Triggered by
 * URL query params on any Charitable admin page. Lets a developer / QA
 * seed and clear local notifications, fire the cron jobs, and exercise
 * the donation / campaign triggers without waiting for real events.
 *
 * Gated to manage_options. Intended for in-dev use; remove (or no-op
 * gate behind WP_DEBUG) before public release.
 *
 * Usage:
 *   ?charitable_notif_test=seed_all       — adds one notification per category
 *   ?charitable_notif_test=clear          — clears local + dismissed + new_feed
 *   ?charitable_notif_test=fire_cron      — runs all 4 cron jobs inline
 *   ?charitable_notif_test=fire_health    — adds a health alert (red dot)
 *   ?charitable_notif_test=fire_milestone — adds a milestone notification
 *   ?charitable_notif_test=fire_nudge     — adds a nudge notification
 *   ?charitable_notif_test=fire_lifecycle — adds a lifecycle notification
 *   ?charitable_notif_test=fire_legal     — adds a legal/compliance alert (red dot)
 *   ?charitable_notif_test=force_empty    — clears everything so empty state shows
 *
 * @package Charitable/Classes/Charitable_Notification_Test_Helper
 * @since   1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Charitable_Notification_Test_Helper {

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_run_test' ), 99 );
	}

	/**
	 * Intercept ?charitable_notif_test=<action> and dispatch.
	 */
	public function maybe_run_test() {
		if ( empty( $_GET['charitable_notif_test'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_GET['charitable_notif_test'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		switch ( $action ) {
			case 'seed_all':
				$this->seed_one_of_each();
				$message = 'Seeded one notification per category.';
				break;
			case 'clear':
				$this->clear_all();
				$message = 'Cleared all local + new_feed + dismissed notifications.';
				break;
			case 'force_empty':
				$this->force_empty();
				$message = 'Forced empty state (cleared everything + disabled legacy feed for this user).';
				break;
			case 'fire_cron':
				$this->fire_cron();
				$message = 'Ran all 4 cron jobs inline.';
				break;
			case 'fire_health':
				$this->seed( 'health_test', 'health' );
				$message = 'Added a HEALTH alert (red dot).';
				break;
			case 'fire_legal':
				$this->seed( 'legal_test', 'legal' );
				$message = 'Added a LEGAL alert (red dot).';
				break;
			case 'fire_milestone':
				$this->seed( 'milestone_test', 'milestone' );
				$message = 'Added a MILESTONE notification.';
				break;
			case 'fire_nudge':
				$this->seed( 'nudge_test', 'nudge' );
				$message = 'Added a NUDGE notification.';
				break;
			case 'fire_lifecycle':
				$this->seed( 'lifecycle_test', 'lifecycle' );
				$message = 'Added a LIFECYCLE notification.';
				break;
			default:
				return;
		}

		// Stash a notice to display after redirect (so the user sees confirmation).
		set_transient( 'charitable_notif_test_notice_' . get_current_user_id(), $message, 30 );

		// Redirect to drop the query param.
		$redirect_url = remove_query_arg( 'charitable_notif_test' );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Seed one notification for each category.
	 */
	private function seed_one_of_each() {
		$this->seed( 'health_test',    'health' );
		$this->seed( 'legal_test',     'legal' );
		$this->seed( 'lifecycle_test', 'lifecycle' );
		$this->seed( 'milestone_test', 'milestone' );
		$this->seed( 'nudge_test',     'nudge' );
	}

	/**
	 * Seed a single local notification for a category.
	 *
	 * @param string $id
	 * @param string $category
	 */
	private function seed( $id, $category ) {
		$copy = array(
			'health'    => array(
				'title'             => __( 'TEST: Failed donations detected', 'charitable' ),
				'content'           => '<p>' . __( 'This is a seeded HEALTH alert used to verify the red dot and red border.', 'charitable' ) . '</p>',
				'notification_type' => 'warning',
				'badge_label'       => __( 'Health Alert', 'charitable' ),
				'badge_type'        => 'critical',
			),
			'legal'     => array(
				'title'             => __( 'TEST: Compliance settings need attention', 'charitable' ),
				'content'           => '<p>' . __( 'This is a seeded LEGAL alert used to verify the red dot and red border.', 'charitable' ) . '</p>',
				'notification_type' => 'warning',
				'badge_label'       => __( 'Compliance', 'charitable' ),
				'badge_type'        => 'warning',
			),
			'lifecycle' => array(
				'title'             => __( 'TEST: Campaign is now live', 'charitable' ),
				'content'           => '<p>' . __( 'This is a seeded LIFECYCLE notification used to verify the blue flag icon.', 'charitable' ) . '</p>',
				'notification_type' => 'info',
				'badge_label'       => __( 'Campaign', 'charitable' ),
				'badge_type'        => 'info',
			),
			'milestone' => array(
				'title'             => __( 'TEST: 100 donors reached', 'charitable' ),
				'content'           => '<p>' . __( 'This is a seeded MILESTONE used to verify the star icon and green check.', 'charitable' ) . '</p>',
				'notification_type' => 'success',
				'badge_label'       => __( 'Milestone', 'charitable' ),
				'badge_type'        => 'success',
			),
			'nudge'     => array(
				'title'             => __( 'TEST: Create your first campaign', 'charitable' ),
				'content'           => '<p>' . __( 'This is a seeded NUDGE used to verify the lightbulb icon.', 'charitable' ) . '</p>',
				'notification_type' => 'info',
				'badge_label'       => '',
				'badge_type'        => 'nudge',
			),
		);

		if ( ! isset( $copy[ $category ] ) ) {
			return;
		}

		$data = array_merge(
			$copy[ $category ],
			array(
				'dismissible' => true,
				'category'    => $category,
				'btns'        => array(
					'main' => array(
						'url'  => admin_url( 'admin.php?page=charitable-dashboard' ),
						'text' => __( 'View Dashboard', 'charitable' ),
					),
				),
			)
		);

		Charitable_Local_Notifications::get_instance()->add( $id, $data );
	}

	/**
	 * Clear all local + new_feed + dismissed.
	 */
	private function clear_all() {
		Charitable_Notifications::get_instance()->save_option_partial( array(
			'local'           => array(),
			'new_feed'        => array(),
			'dismissed'       => array(),
			'dismissed_times' => array(),
		) );
	}

	/**
	 * Clear everything AND set legacy_feed to false so the empty state shows.
	 */
	private function force_empty() {
		$this->clear_all();

		// Also overwrite the cached legacy feed.
		$option = (array) get_option( 'charitable_notifications', array() );
		$option['feed']   = array();
		$option['events'] = array();
		update_option( 'charitable_notifications', $option );

		// Turn off the legacy_feed category so the legacy feed is suppressed even after re-fetch.
		Charitable_Notification_Settings::get_instance();
		$settings = (array) Charitable_Notifications::get_instance()->get_option()['settings'];
		$settings['legacy_feed'] = false;
		Charitable_Notifications::get_instance()->save_option_partial( array( 'settings' => $settings ) );
	}

	/**
	 * Fire all 4 cron jobs inline.
	 */
	private function fire_cron() {
		$cron = Charitable_Notification_Cron::get_instance();
		$cron->run_health_check();
		$cron->run_compliance_check();
		$cron->run_nudge_check();
		$cron->run_new_feed_fetch();
	}

	/**
	 * Display the success message after redirect.
	 */
	public function maybe_show_notice() {
		$key     = 'charitable_notif_test_notice_' . get_current_user_id();
		$message = get_transient( $key );
		if ( ! $message ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="notice notice-info is-dismissible"><p><strong>Notification test:</strong> %s</p></div>',
			esc_html( $message )
		);
	}
}
