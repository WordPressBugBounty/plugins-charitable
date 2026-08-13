<?php
/**
 * Charitable Notification Onboarding Tour.
 *
 * Shows a one-time Shepherd.js tour on the Charitable dashboard after the site
 * upgrades to 1.8.14+, highlighting the notifications badge and the Latest
 * Updates dashboard section. Fires once per user; dismissed state stored in
 * user meta.
 *
 * @package   Charitable/Classes/Charitable_Notification_Onboarding
 * @since     1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Charitable_Notification_Onboarding {

	/** User meta key used to record dismissal. */
	const TOUR_META_KEY = 'charitable_notifications_v2_tour_dismissed';

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_tour' ) );
		add_action( 'wp_ajax_charitable_dismiss_notifications_tour', array( $this, 'ajax_dismiss' ) );
	}

	/**
	 * Enqueue the tour assets only when:
	 *  - We're on the Charitable dashboard page.
	 *  - The current user has not already dismissed the tour.
	 */
	public function maybe_enqueue_tour() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'charitable-dashboard' !== $page ) {
			return;
		}

		// ?preview_notif_tour=1 clears the dismissal so the tour fires again.
		if ( ! empty( $_GET['preview_notif_tour'] ) && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			delete_user_meta( get_current_user_id(), self::TOUR_META_KEY );
		}

		if ( get_user_meta( get_current_user_id(), self::TOUR_META_KEY, true ) ) {
			return;
		}

		$min     = charitable_get_min_suffix();
		$version = charitable()->get_version();
		$dir     = charitable()->get_path( 'directory', false );
		$assets  = charitable()->get_path( 'assets', false );

		// Floating UI (Shepherd dependency).
		wp_enqueue_script(
			'charitable-float-ui-core',
			$dir . 'assets/js/libraries/floating-ui-core.min.js',
			array( 'jquery' ),
			$version
		);
		wp_enqueue_script(
			'charitable-float-ui-dom',
			$dir . 'assets/js/libraries/floating-ui-dom.min.js',
			array( 'charitable-float-ui-core' ),
			$version
		);

		// Shepherd.js.
		wp_enqueue_script(
			'charitable-shepherd',
			$dir . 'assets/js/libraries/shepherd.js',
			array( 'jquery', 'charitable-float-ui-core', 'charitable-float-ui-dom' ),
			$version
		);
		wp_enqueue_style(
			'charitable-shepherd',
			$dir . "assets/css/libraries/shepherd{$min}.css",
			array(),
			$version
		);

		// Tour script.
		wp_enqueue_script(
			'charitable-notifications-tour',
			$dir . "assets/js/admin/charitable-notifications-tour{$min}.js",
			array( 'jquery', 'charitable-shepherd' ),
			$version . '.' . filemtime( charitable()->get_path( 'directory' ) . "assets/js/admin/charitable-notifications-tour{$min}.js" ),
			true
		);

		wp_localize_script(
			'charitable-notifications-tour',
			'CHARITABLE_NOTIF_TOUR',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'charitable_admin_nonce' ),
			)
		);
	}

	/**
	 * AJAX — mark the tour dismissed for the current user.
	 */
	public function ajax_dismiss() {
		check_ajax_referer( 'charitable_admin_nonce', 'nonce' );
		update_user_meta( get_current_user_id(), self::TOUR_META_KEY, true );
		wp_send_json_success();
	}
}
