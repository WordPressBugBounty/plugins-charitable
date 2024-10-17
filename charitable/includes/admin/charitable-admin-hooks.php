<?php
/**
 * Charitable Admin Hooks.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.3.0
 * @version   1.6.40
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Charitable's admin-area scripts & styles.
 *
 * @see Charitable_Admin::admin_enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Admin::get_instance(), 'admin_enqueue_scripts' ) );

/**
 * Set custom admin body classes.
 *
 * @see Charitable_Admin::set_body_class()
 */
add_filter( 'admin_body_class', array( Charitable_Admin::get_instance(), 'set_body_class' ) );

/**
 * Do an admin action.
 *
 * @see Charitable_Admin::maybe_do_admin_action()
 */
add_action( 'admin_init', array( Charitable_Admin::get_instance(), 'maybe_do_admin_action' ), 999 );

/**
 * Check if there are any notices to be displayed in the admin.
 *
 * @see Charitable_Admin::add_notices()
 */
add_action( 'admin_notices', array( Charitable_Admin::get_instance(), 'add_notices' ) );

/**
 * Dismiss a notice.
 *
 * @see Charitable_Admin::dismiss_notice()
 */
add_action( 'wp_ajax_charitable_dismiss_notice', array( Charitable_Admin::get_instance(), 'dismiss_notice' ) );

/**
 * Dismiss a banner.
 *
 * @see Charitable_Admin::dismiss_banner()
 */
add_action( 'wp_ajax_charitable_dismiss_admin_banner', array( Charitable_Admin::get_instance(), 'dismiss_banner' ) );

/**
 * Dismiss a list banner.
 *
 * @see Charitable_Admin::dismiss_banner()
 */
add_action( 'wp_ajax_charitable_dismiss_admin_list_banner', array( Charitable_Admin::get_instance(), 'dismiss_list_banner' ) );


/**
 * Dismiss a five star rating request.
 *
 * @see Charitable_Admin::dismiss_five_star_rating()
 */
add_action( 'wp_ajax_charitable_dismiss_admin_five_star_rating', array( Charitable_Admin::get_instance(), 'dismiss_five_star_rating' ) );

/**
 * Add a generic body class to donations page
 *
 * @see Charitable_Admin::add_admin_body_class()
 */
add_filter( 'admin_body_class', array( Charitable_Admin::get_instance(), 'add_admin_body_class' ) );

/**
 * Remove jQuery UI styles added by Ninja Forms.
 *
 * @see Charitable_Admin::remove_jquery_ui_styles_nf()
 */
// add_filter( 'media_buttons_context', array( Charitable_Admin::get_instance(), 'remove_jquery_ui_styles_nf' ), 20 );

/**
 * Add action links to the Charitable plugin block.
 *
 * @see Charitable_Admin::add_plugin_action_links()
 */
add_filter( 'plugin_action_links_' . plugin_basename( charitable()->get_path() ), array( Charitable_Admin::get_instance(), 'add_plugin_action_links' ) );

/**
 * Add a link to the settings page from the Charitable plugin block.
 *
 * @see Charitable_Admin::add_plugin_row_meta()
 */
add_filter( 'plugin_row_meta', array( Charitable_Admin::get_instance(), 'add_plugin_row_meta' ), 10, 2 );

/**
 * Export handlers.
 *
 * @see Charitable_Admin::export_donations()
 * @see Charitable_Admin::export_campaigns()
 */
add_action( 'charitable_export_donations', array( Charitable_Admin::get_instance(), 'export_donations' ) );
add_action( 'charitable_export_campaigns', array( Charitable_Admin::get_instance(), 'export_campaigns' ) );

/**
 * Add Charitable menu.
 *
 * @see Charitable_Admin_Pages::add_menu()
 */
add_action( 'admin_menu', array( Charitable_Admin_Pages::get_instance(), 'add_menu' ), 5 );
add_action( 'parent_file', array( Charitable_Admin_Pages::get_instance(), 'menu_highlight' ), 10 );

/**
 * Redirect to welcome page after install.
 *
 * @see Charitable_Admin_Pages::redirect_to_welcome()
 */
add_action( 'charitable_install', array( Charitable_Admin_Pages::get_instance(), 'setup_welcome_redirect' ), 100 );

/**
 * Stash any notices that haven't been displayed.
 *
 * @see Charitable_Admin_Notices::shutdown()
 */
add_action( 'shutdown', array( charitable_get_admin_notices(), 'shutdown' ) );

/**
 * Stash any notices that haven't been displayed.
 *
 * @see Charitable_Admin_Support::maybe_do_charitable_support_query()
 */
add_action( 'admin_init', array( Charitable_Admin_Support::get_instance(), 'maybe_do_charitable_support_query' ), 100 );

/**
 * Enqueue Charitable's campaign builder related scripts & styles.
 *
 * @see Charitable_Campaign_Builder::add_page()
 */
add_action( 'admin_menu', array( Charitable_Campaign_Builder::get_instance(), 'add_page' ), 5 );

/**
 * Enqueue Charitable's campaign builder related scripts & styles.
 *
 * @see Charitable_Campaign_Builder::init()
 */
add_action( 'admin_init', array( Charitable_Campaign_Builder::get_instance(), 'init' ), 100 );

/**
 * Enqueue Charitable's campaign builder embed related scripts & styles.
 *
 * @see Charitable_Campaign_Embed_Wizard::init()
 */
add_action( 'admin_init', array( Charitable_Campaign_Embed_Wizard::get_instance(), 'init' ), 100 );

/**
 * Enqueue Charitable's block releated scripts & styles.
 *
 * @see Charitable_Admin_Blocks::admin_enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Admin_Blocks::get_instance(), 'admin_enqueue_scripts' ) );

/**
 * Enqueue Charitable's campaign builder congrats wizard related scripts & styles.
 *
 * @see Charitable_Campaign_Congrats_Wizard::init()
 */
add_action( 'admin_init', array( Charitable_Campaign_Congrats_Wizard::get_instance(), 'init' ), 120 );

/**
 * Dismiss dashboard notices.
 *
 * @see Charitable_Admin::dismiss_dashboard_notice()
 */
add_action( 'wp_ajax_charitable_dismiss_dashboard_notice', array( Charitable_Admin::get_instance(), 'dismiss_dashboard_notices' ) );

/**
 * Dismiss guide tool related notices.
 *
 * @see Charitable_Admin::dismiss_growth_tools_notices()
 */
add_action( 'wp_ajax_charitable_dismiss_growth_tools_dashboard_notice', array( Charitable_Admin::get_instance(), 'dismiss_growth_tools_notices' ) );

/**
 * Do an admin action.
 *
 * @see Charitable_Admin::maybe_do_admin_action()
 */
add_action( 'plugins_loaded', array( Charitable_Admin_Getting_Started::get_instance(), 'init' ), 999 );

/**
 * Insert HTML into the footer that can be used later by onboarding.
 *
 * @see Charitable_Campaign_Builder::render_onboarding_html()
 */
add_action( 'admin_footer', array( Charitable_Campaign_Builder::get_instance(), 'render_onboarding_html' ), 5 );

/**
 * Save an onboarding options via AJAX.
 *
 * @see Charitable_Campaign_Builder::save_onboarding_option_ajax()
 */
add_action( 'wp_ajax_charitable_onboarding_save_option', array( Charitable_Campaign_Builder::get_instance(), 'save_onboarding_option_ajax' ), 5 );
add_action( 'wp_ajax_charitable_onboarding_tour_save_option', array( Charitable_Campaign_Builder::get_instance(), 'save_tour_option_ajax' ), 5 );
add_action( 'wp_ajax_charitable_onboarding_checklist_save_option', array( Charitable_Checklist::get_instance(), 'save_checklist_option_ajax' ), 5 );


/**
 * Checklist: check to mark step as completed.
 *
 * @see Charitable_Checklist::check_step_first_donation()
 */
// add_action( 'admin_init', array( Charitable_Onboarding::get_instance(), 'check_step_first_donation' ), 5 );
add_action( 'admin_init', array( Charitable_Checklist::get_instance(), 'maybe_redirect_to_next_step' ), 999 );
