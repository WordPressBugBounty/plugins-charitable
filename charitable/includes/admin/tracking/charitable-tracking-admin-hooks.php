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
/**
 * MOVED in 1.8.12 to includes/tracking/charitable-tracking-hooks.php.
 *
 * These were admin-only, so the weekly check-in never ran in a wp-cron.php
 * request and the recurring event was destroyed on its first firing. Do NOT
 * re-add them here.
 */

/**
 * Register the check-in.
 *
 * @see     Charitable_Tracking::send_checkin()
 */
/* The charitable_usage_tracking_cron handler also moved; see above. */

/**
 * Testing the check-in.
 *
 * @see     Charitable_Tracking::test_checkin()
 */
add_action( 'init', array( Charitable_Tracking::get_instance(), 'test_checkin' ) );

/**
 * Save the time to first campaign.
 *
 * @see     Charitable_Tracking::insert_time_to_first_campaign()
 */
/* The first-campaign writer moved; see above. */

/**
 * Save the time to first donation.
 *
 * @see     Charitable_Tracking::insert_time_to_first_donation()
 */
/*
 * The first-donation writer moved too. charitable_after_save_donation fires
 * during front-end checkout, so registering it here meant it never fired for a
 * real donation. Present since 1.8.4.5.
 */
