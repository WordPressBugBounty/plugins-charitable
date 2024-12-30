<?php
/**
 * Charitable Tracking Hooks.
 *
 * Action/filter hooks used for Charitable Tracking.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron stuff.
 *
 * @see     Charitable_Tracking::schedule_send()
 * @see     Charitable_Tracking::add_schedules()
 */
add_action( 'init', array( Charitable_Tracking::get_instance(), 'schedule_send' ) );
add_action( 'cron_schedules', array( Charitable_Tracking::get_instance(), 'add_schedules' ) );

/**
 * Register the check-in.
 *
 * @see     Charitable_Tracking::send_checkin()
 */
add_action( 'charitable_usage_tracking_cron', array( Charitable_Tracking::get_instance(), 'send_checkins' ) );

/**
 * Testing the check-in.
 *
 * @see     Charitable_Tracking::test_checkin()
 */
add_action( 'init', array( Charitable_Tracking::get_instance(), 'test_checkin' ) );