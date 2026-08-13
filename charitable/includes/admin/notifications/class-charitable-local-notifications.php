<?php
/**
 * Charitable Local Notifications.
 *
 * Manages locally-generated notifications (milestones, lifecycle, health,
 * compliance, nudges) and injects them into the sidebar feed via filter.
 *
 * @package   Charitable/Classes/Charitable_Local_Notifications
 * @since     1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Charitable_Local_Notifications {

    private static $instance = null;

    /**
     * Priority order for local notification categories (lower = higher priority).
     */
    const CATEGORY_PRIORITY = [
        'health'     => 10,
        'legal'      => 20,
        'lifecycle'  => 30,
        'milestone'  => 40,
        'nudge'      => 50,
    ];

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        // Inject local notifications just after dynamic (license) notifications at priority 6.
        add_filter( 'charitable_notifications_get', [ $this, 'inject_local_notifications' ], 6 );

        // Handle dismiss AJAX to record dismissed_times for re-surface.
        add_action( 'wp_ajax_charitable_notification_dismiss', [ $this, 'record_dismiss_time' ], 5 );
        add_action( 'wp_ajax_charitable_notification_dismiss_multiple', [ $this, 'record_dismiss_time_multiple' ], 5 );
    }

    /**
     * Add or update a local notification.
     *
     * If the notification already exists, it is replaced (allows updating content).
     * Does NOT add if the category is currently disabled in settings.
     *
     * @param string $id       Unique notification ID, e.g. 'health_failed_donations'.
     * @param array  $data     Notification data. Same structure as feed notifications:
     *                         id, title, content, notification_type, dismissible,
     *                         allow_on_activation, btns, badge_label, badge_type, category.
     * @return void
     */
    public function add( $id, array $data ) {
        $notifications = Charitable_Notifications::get_instance();
        $option        = $notifications->get_option( false );

        $data['id']                  = $id;
        $data['allow_on_activation'] = true;

        $local          = $option['local'];
        $existing_start = '';

        // Replace existing entry with same ID, preserving original start time.
        foreach ( $local as $k => $item ) {
            if ( isset( $item['id'] ) && $item['id'] === $id ) {
                $existing_start = $item['start'] ?? '';
                unset( $local[ $k ] );
                break;
            }
        }

        if ( ! isset( $data['start'] ) ) {
            $data['start'] = $existing_start ?: current_time( 'mysql' );
        }

        $local[] = $data;

        $notifications->save_option_partial( [ 'local' => array_values( $local ) ] );
    }

    /**
     * Remove a local notification entirely (use when condition is resolved).
     *
     * @param string $id Notification ID.
     * @return void
     */
    public function remove( $id ) {
        $notifications = Charitable_Notifications::get_instance();
        $option        = $notifications->get_option( false );

        $local = array_filter( $option['local'], function( $item ) use ( $id ) {
            return ! ( isset( $item['id'] ) && $item['id'] === $id );
        } );

        $notifications->save_option_partial( [ 'local' => array_values( $local ) ] );
    }

    /**
     * Get all local notifications that should currently be displayed,
     * accounting for dismissal and re-surface intervals.
     *
     * @return array
     */
    public function get_displayable() {
        $notifications = Charitable_Notifications::get_instance();
        $option        = $notifications->get_option();
        $local         = $option['local'];
        $dismissed     = $option['dismissed'];
        $times         = $option['dismissed_times'];
        $settings      = Charitable_Notification_Settings::get_instance();
        $now           = time();
        $to_undismiss  = [];

        $result = [];

        foreach ( $local as $item ) {
            if ( empty( $item['id'] ) ) {
                continue;
            }

            $id       = (string) $item['id'];
            $category = isset( $item['category'] ) ? $item['category'] : 'nudge';

            // Skip if category disabled.
            if ( ! $settings->is_category_enabled( $category ) ) {
                continue;
            }

            // Check if dismissed.
            if ( in_array( $id, $dismissed, true ) ) {
                // Check re-surface interval.
                if ( isset( $times[ $id ] ) ) {
                    $interval = $this->get_resurface_interval( $category );
                    if ( $interval > 0 && ( $now - (int) $times[ $id ] ) >= $interval ) {
                        // Interval passed — un-dismiss so it shows again.
                        $to_undismiss[] = $id;
                    } else {
                        continue; // Still suppressed.
                    }
                } else {
                    continue; // Dismissed with no time recorded — permanent.
                }
            }

            $result[] = $item;
        }

        // Un-dismiss any notifications whose resurface interval has passed.
        if ( ! empty( $to_undismiss ) ) {
            $this->clear_dismissed( $to_undismiss );
        }

        return $result;
    }

    /**
     * Inject displayable local notifications into the main feed.
     *
     * Local notifications are sorted by category priority within the local group,
     * then prepended to the main feed (after dynamic license notifications which
     * are injected at priority 5).
     *
     * @param array $notifications Current notifications.
     * @return array
     */
    public function inject_local_notifications( array $notifications ) {
        $local = $this->get_displayable();

        if ( empty( $local ) ) {
            return $notifications;
        }

        // Sort local notifications by category priority.
        usort( $local, function( $a, $b ) {
            $pa = isset( self::CATEGORY_PRIORITY[ $a['category'] ] ) ? self::CATEGORY_PRIORITY[ $a['category'] ] : 99;
            $pb = isset( self::CATEGORY_PRIORITY[ $b['category'] ] ) ? self::CATEGORY_PRIORITY[ $b['category'] ] : 99;
            return $pa - $pb;
        } );

        // Insert after license notifications (which are at position 0-1) — find
        // first non-license entry and insert before it.
        $insert_at     = 0;
        $notifications = array_values( $notifications );
        foreach ( $notifications as $i => $n ) {
            $id = isset( $n['id'] ) ? $n['id'] : '';
            if ( 'license_expired' !== $id && 'license_expiring' !== $id ) {
                $insert_at = $i;
                break;
            }
            $insert_at = $i + 1;
        }

        array_splice( $notifications, $insert_at, 0, $local );

        return $notifications;
    }

    /**
     * Record the dismissal timestamp for a local notification (called before the
     * core dismiss handler at priority 5, so we can capture the ID).
     */
    public function record_dismiss_time() {
        if (
            ! check_ajax_referer( 'charitable-admin', 'nonce', false ) ||
            ! current_user_can( 'manage_options' )
        ) {
            return; // Let the core handler send the error response.
        }

        $id = isset( $_POST['notification_id'] ) ? sanitize_key( $_POST['notification_id'] ) : ''; // phpcs:ignore
        if ( empty( $id ) ) {
            return;
        }
        $this->store_dismiss_time( $id );
    }

    /**
     * Record dismissal timestamps for multiple notifications.
     */
    public function record_dismiss_time_multiple() {
        if (
            ! check_ajax_referer( 'charitable-admin', 'nonce', false ) ||
            ! current_user_can( 'manage_options' )
        ) {
            return; // Let the core handler send the error response.
        }

        $ids = isset( $_POST['notification_ids'] ) ? (array) $_POST['notification_ids'] : []; // phpcs:ignore
        $ids = array_slice( $ids, 0, 50 );
        foreach ( $ids as $id ) {
            $this->store_dismiss_time( sanitize_key( $id ) );
        }
    }

    /**
     * Write dismissal timestamp to dismissed_times in the option.
     *
     * @param string $id Notification ID.
     */
    private function store_dismiss_time( $id ) {
        $notifications = Charitable_Notifications::get_instance();
        $option        = $notifications->get_option( false );
        $times         = $option['dismissed_times'];
        $times[ $id ]  = time();
        $notifications->save_option_partial( [ 'dismissed_times' => $times ] );
    }

    /**
     * Remove IDs from dismissed and dismissed_times.
     *
     * @param array $ids
     */
    private function clear_dismissed( array $ids ) {
        $notifications = Charitable_Notifications::get_instance();
        $option        = $notifications->get_option( false );

        $dismissed = array_filter( $option['dismissed'], function( $id ) use ( $ids ) {
            return ! in_array( (string) $id, $ids, true );
        } );

        $times = $option['dismissed_times'];
        foreach ( $ids as $id ) {
            unset( $times[ $id ] );
        }

        $notifications->save_option_partial( [
            'dismissed'       => array_values( $dismissed ),
            'dismissed_times' => $times,
        ] );
    }

    /**
     * Get the re-surface interval in seconds for a given category.
     *
     * All intervals filterable. 0 = never re-surface.
     *
     * @param  string $category
     * @return int Seconds.
     */
    private function get_resurface_interval( $category ) {
        $defaults = [
            'health'    => apply_filters( 'charitable_notifications_health_resurface', DAY_IN_SECONDS ),
            'legal'     => apply_filters( 'charitable_notifications_compliance_resurface', WEEK_IN_SECONDS ),
            'nudge'     => apply_filters( 'charitable_notifications_nudges_resurface', WEEK_IN_SECONDS ),
            'lifecycle' => 0, // one-time per event.
            'milestone' => 0, // one-time per milestone.
        ];
        return isset( $defaults[ $category ] ) ? (int) $defaults[ $category ] : 0;
    }
}
