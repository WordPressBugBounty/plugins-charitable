<?php
/**
 * Charitable Notification Settings.
 *
 * Manages site-wide category toggle settings for the notifications sidebar.
 *
 * @package   Charitable/Classes/Charitable_Notification_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.12
 * @version   1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Charitable_Notification_Settings {

    private static $instance = null;

    /**
     * All known categories with their default enabled state.
     */
    const DEFAULTS = [
        'license'     => true,
        'lifecycle'   => true,
        'milestone'   => true,
        'health'      => true,
        'legal'       => true,
        'nudge'       => true,
        'new_feed'    => true,
        'legacy_feed' => true,
    ];

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'wp_ajax_charitable_save_notification_settings', [ $this, 'ajax_save' ] );
    }

    /**
     * Check whether a category is enabled site-wide.
     *
     * @param  string $category One of the keys in self::DEFAULTS.
     * @return bool
     */
    public function is_category_enabled( $category ) {
        $settings = $this->get_settings();
        if ( ! isset( $settings[ $category ] ) ) {
            return true; // Unknown category — show by default.
        }
        return (bool) $settings[ $category ];
    }

    /**
     * Return the current settings, merged with defaults.
     *
     * @return array
     */
    public function get_settings() {
        $notifications = Charitable_Notifications::get_instance();
        $option        = $notifications->get_option();
        return array_merge( self::DEFAULTS, (array) $option['settings'] );
    }

    /**
     * AJAX handler — saves category toggles.
     *
     * Expects POST: nonce, settings (JSON object of category => bool).
     */
    public function ajax_save() {
        check_ajax_referer( 'charitable_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_charitable_settings' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $raw      = isset( $_POST['settings'] ) ? $_POST['settings'] : '{}'; // phpcs:ignore
        $incoming = json_decode( wp_unslash( $raw ), true );

        if ( ! is_array( $incoming ) ) {
            wp_send_json_error( 'Invalid settings data' );
        }

        // Sanitize — only allow known keys, cast to bool.
        $clean = [];
        foreach ( self::DEFAULTS as $key => $default ) {
            $clean[ $key ] = isset( $incoming[ $key ] ) ? (bool) $incoming[ $key ] : $default;
        }

        Charitable_Notifications::get_instance()->save_option_partial( [ 'settings' => $clean ] );

        wp_send_json_success( $clean );
    }
}
