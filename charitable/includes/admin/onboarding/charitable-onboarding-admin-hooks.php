<?php
/**
 * Charitable Onboarding Hooks.
 *
 * Action/filter hooks used for Charitable Onboarding.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.12
 * @version   1.8.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable onboarding scripts.
 *
 * @see Charitable_Onboarding::enqueue_scripts()
 */
// add_action( 'admin_enqueue_scripts', array( Charitable_Onboarding::get_instance(), 'enqueue_scripts' ) );

/**
 * Register Charitable checklist scripts.
 *
 * @see Charitable_Checklist::enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Checklist::get_instance(), 'enqueue_styles_and_scripts' ) );

/**
 * Add the checklist HTML to the footer.
 *
 * @see Charitable_Checklist::maybe_add_checklist_html()
 */
add_action( 'admin_footer', array( Charitable_Checklist::get_instance(), 'maybe_add_checklist_widget_html' ) );

/**
 * Show a notice related to the checklist.
 *
 * @see Charitable_Checklist::get_dashboard_notices()
 */
add_filter( 'charitable_admin_dashboard_init_end', array( Charitable_Checklist::get_instance(), 'get_dashboard_notices' ), 10 );