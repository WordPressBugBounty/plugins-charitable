<?php
/**
 * Telemetry hooks that MUST be registered in every request context.
 *
 * Action/filter hooks for Charitable_Tracking that cannot live in
 * includes/admin/tracking/charitable-tracking-admin-hooks.php, because that file
 * is required by Charitable_Admin::load_dependencies(), which only runs when
 * is_admin() is true (charitable.php, maybe_start_admin(), returns early
 * otherwise).
 *
 * WHY THIS FILE EXISTS. Three things were registered admin-only and silently
 * never fired:
 *
 *   1. charitable_usage_tracking_cron -> send_checkins(). A wp-cron.php request
 *      has is_admin() false, so the weekly check-in handler was never attached
 *      and the weekly telemetry send has never run.
 *
 *   2. cron_schedules -> add_schedules(), which DEFINES the 'weekly' schedule.
 *      Without it in cron context, WordPress cannot look up the interval to
 *      reschedule a due recurring event, so the event is unscheduled entirely.
 *      This is the compounding half: the event was booked in wp-admin, fired
 *      once in cron with no handler attached, failed to reschedule, and
 *      vanished. Measured against production telemetry 2026-08-03: 3,003 of
 *      3,434 reporting sites had exactly one check-in, with created_at
 *      identical to updated_at.
 *
 *   3. charitable_after_save_donation -> insert_time_to_first_donation().
 *      Donations are processed on the FRONT END (donation hooks are required
 *      from charitable.php outside the admin gate), so this writer never fired
 *      for a real donation. Lite has carried this since 1.8.4.5, which means
 *      charitable_first_donation has only ever been stamped by donations created
 *      inside wp-admin.
 *
 * DELIBERATELY NARROW. This file only touches Charitable_Tracking, which is
 * autoloaded. It does NOT pull Charitable_Admin or the admin functions files
 * into front-end requests; doing so would trade a telemetry bug for a sitewide
 * performance regression. Charitable_Tracking's consent gates read consent
 * through Charitable_Tracking::get_usage_tracking_setting(), which falls back to
 * the raw option when the admin-only helper is absent, so nothing here depends
 * on admin code being loaded.
 *
 * LITE HAS NO NUDGE. Pro 1.8.17.4 adds a one-off upgrade check-in so existing
 * sites deliver new payload fields immediately. Lite deliberately does not:
 * usage tracking is opt-in here under WP.org guidelines, so there is no
 * auto-enable routine to hang a nudge from, and the existing opt-in paths (setup
 * wizard, Settings -> Advanced) already send immediately on consent.
 *
 * test_checkin() intentionally stays in the admin hooks file: it is a debug
 * entry point gated on is_admin() and manage_options anyway.
 *
 * @package   Charitable/Functions/Tracking
 * @author    David Bisset
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.12
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the 'weekly' cron schedule.
 *
 * MUST be registered outside admin. WordPress reschedules a due recurring event
 * by looking its interval up in wp_get_schedules(); if 'weekly' is unknown in
 * the request that runs cron, the event is dropped instead of rescheduled.
 *
 * @see Charitable_Tracking::add_schedules()
 */
add_action( 'cron_schedules', array( Charitable_Tracking::get_instance(), 'add_schedules' ) );

/**
 * Book the weekly check-in if it is not already scheduled.
 *
 * Registered on init in every context, not just admin, so that a site whose
 * event was destroyed by the reschedule failure above re-books it on the next
 * request rather than waiting for someone to load wp-admin. Guarded internally
 * by wp_next_scheduled(), so this is a single in-memory option read per request.
 *
 * @see Charitable_Tracking::schedule_send()
 */
add_action( 'init', array( Charitable_Tracking::get_instance(), 'schedule_send' ) );

/**
 * Run the check-in when the cron event fires.
 *
 * send_checkins() re-checks consent internally, so registering the handler
 * everywhere does not send anything from a site that has not opted in. That
 * matters more in Lite than in Pro, because Lite is opt-in only.
 *
 * @see Charitable_Tracking::send_checkins()
 */
add_action( 'charitable_usage_tracking_cron', array( Charitable_Tracking::get_instance(), 'send_checkins' ) );

/**
 * Record the time of the first campaign and the first donation.
 *
 * The donation hook fires during front-end checkout, which is why these cannot
 * be admin-only. They stamp charitable_first_campaign and
 * charitable_first_donation, which the opt-in payload reports.
 *
 * @see Charitable_Tracking::insert_time_to_first_campaign()
 * @see Charitable_Tracking::insert_time_to_first_donation()
 */
add_action( 'charitable_campaign_processor_save_core', array( Charitable_Tracking::get_instance(), 'insert_time_to_first_campaign' ), 10, 4 );
add_action( 'charitable_after_save_donation', array( Charitable_Tracking::get_instance(), 'insert_time_to_first_donation' ), 10, 2 );
