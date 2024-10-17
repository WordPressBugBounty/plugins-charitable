<?php
/**
 * Charitable Settings Hooks.
 *
 * Action/filter hooks used for Charitable Settings API.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable tools.
 *
 * @see Charitable_Tools::register_settings()
 */
add_action( 'admin_init', array( Charitable_Tools::get_instance(), 'register_settings' ) );

/**
 * Add settings to the Tools tab.
 *
 * @since 1.8.1.6 and 1.8.2
 *
 * @see Charitable_Tools_Settings::add_import_fields()
 */
add_filter( 'charitable_tools_tab_fields_export', array( Charitable_Tools_Settings::get_instance(), 'add_tools_export_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_import', array( Charitable_Tools_Settings::get_instance(), 'add_tools_import_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_snippets', array( Charitable_Tools_Settings::get_instance(), 'add_tools_snippets_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_customize', array( Charitable_Tools_Settings::get_instance(), 'add_tools_customize_fields' ), 5 );

/**
 * Look for export/import attempts.
 *
 * @see Charitable_Export_Settings::add_export_fields()
 */
add_action( 'admin_init', array( Charitable_Export_Items::get_instance(), 'admin_accept_export_campaign_request' ) );
add_action( 'admin_init', array( Charitable_Export_Items::get_instance(), 'admin_accept_export_donations_request' ) );
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_campaign_request' ) );
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_donations_request' ) );


/**
 * Add the tools tab settings fields.
 *
 * @since   1.8.1.6
 *
 * @return  array<string,array>
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Intergrations_WPCode::get_instance(), 'enqueue_scripts' ) );
add_action( 'admin_enqueue_scripts', array( Charitable_Tools_System_Info::get_instance(), 'enqueue_scripts' ) );
