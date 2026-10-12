<?php
/**
 * Addon catalogue hooks that MUST be registered in every request context.
 *
 * The weekly refresh runs in cron, and wp-cron.php has is_admin() false, so registering
 * the handler from includes/admin/charitable-admin-hooks.php attached it in wp-admin only
 * and the event fired with nothing listening. Caught in testing: the event ran, rescheduled
 * itself, and stored nothing.
 *
 * Same failure this file's sibling exists for - see includes/tracking/charitable-tracking-hooks.php.
 *
 * Deliberately narrow: this touches only Charitable_Addons_Catalogue, which is autoloaded.
 * It does not pull Charitable_Admin or the admin function files into front-end requests.
 *
 * @package   Charitable/Functions/Addons
 * @since     1.8.13.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run the weekly refresh when the event fires.
 *
 * @see Charitable_Addons_Catalogue::fetch_and_store()
 */
add_action( 'charitable_addons_catalogue_refresh', array( Charitable_Addons_Catalogue::get_instance(), 'fetch_and_store' ) );

/**
 * Book the weekly refresh if it is not already scheduled.
 *
 * On init rather than admin_init so an event lost to a failed reschedule comes back on the
 * next request instead of waiting for someone to load wp-admin. Guarded internally by
 * wp_next_scheduled(), so this is one in-memory option read per request.
 *
 * @see Charitable_Addons_Catalogue::maybe_schedule()
 */
add_action( 'init', array( Charitable_Addons_Catalogue::get_instance(), 'maybe_schedule' ) );
