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
add_filter( 'charitable_tools_tab_fields_snippets', array( Charitable_Tools_Settings::get_instance(), 'add_tools_snippets_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_customize', array( Charitable_Tools_Settings::get_instance(), 'add_tools_customize_fields' ), 5 );

/**
 * Import sub-tab fields.
 *
 * @since 1.8.10
 */
add_filter( 'charitable_tools_tab_fields_sub_import__charitable', array( Charitable_Tools_Settings::get_instance(), 'add_tools_import_charitable_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_sub_import__givewp', array( Charitable_Tools_Settings::get_instance(), 'add_tools_import_givewp_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_sub_import__givebutter', array( Charitable_Tools_Settings::get_instance(), 'add_tools_import_givebutter_fields' ), 5 );

/**
 * Add settings to the Misc tab.
 *
 * @since 1.8.9
 *
 * @see Charitable_Tools_Misc::add_misc_fields()
 */
add_filter( 'charitable_tools_tab_fields_misc', array( Charitable_Tools_Misc::get_instance(), 'add_misc_fields' ), 5 );

/**
 * Handle bulk removal of donations.
 *
 * @since 1.8.9
 *
 * @see Charitable_Tools_Misc::bulk_remove_donations()
 */
add_filter( 'charitable_save_tools', array( Charitable_Tools_Misc::get_instance(), 'bulk_remove_donations' ), 10, 3 );

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
 * Import sub-tab default redirect.
 *
 * @since 1.8.10
 */
add_action( 'admin_init', array( Charitable_Tools::get_instance(), 'tools_sub_tab_default' ), 5 );

/**
 * GiveWP and GiveButter CSV import handlers.
 *
 * @since 1.8.10
 */
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_donations_givewp_request' ) );
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_donations_givebutter_request' ) );

/**
 * GiveWP migration tool AJAX handlers.
 *
 * @since 1.8.10
 */
add_action( 'wp_ajax_charitable_givewp_import_start', array( Charitable_GiveWP_Importer::get_instance(), 'ajax_import_start' ) );
add_action( 'wp_ajax_charitable_givewp_import_batch', array( Charitable_GiveWP_Importer::get_instance(), 'ajax_import_batch' ) );


/**
 * Add the tools tab settings fields.
 *
 * @since   1.8.1.6
 *
 * @return  array<string,array>
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Intergrations_WPCode::get_instance(), 'enqueue_scripts' ) );
add_action( 'admin_enqueue_scripts', array( Charitable_Tools_System_Info::get_instance(), 'enqueue_scripts' ) );

/**
 * Register AJAX action for email diagnostics.
 *
 * @since 1.8.9.2
 */
add_action( 'wp_ajax_charitable_email_diagnostics', array( Charitable_Tools_System_Info::get_instance(), 'ajax_email_diagnostics' ) );

/**
 * Register AJAX action for test email.
 *
 * @since 1.8.9.2
 */
add_action( 'wp_ajax_charitable_send_test_email', array( Charitable_Tools_System_Info::get_instance(), 'ajax_send_test_email' ) );

/**
 * Clear error logs via AJAX.
 *
 * @since 1.8.9.2
 *
 * @see Charitable_Tools_System_Info::ajax_clear_error_logs()
 */
add_action( 'wp_ajax_charitable_clear_error_logs', array( Charitable_Tools_System_Info::get_instance(), 'ajax_clear_error_logs' ) );

/**
 * Export error logs via AJAX.
 *
 * @since 1.8.9.2
 *
 * @see Charitable_Tools_System_Info::ajax_export_error_logs()
 */
add_action( 'wp_ajax_charitable_export_error_logs', array( Charitable_Tools_System_Info::get_instance(), 'ajax_export_error_logs' ) );

/**
 * Register AJAX action for debug log scanner.
 *
 * @since 1.8.9.2
 *
 * @see Charitable_Tools_System_Info::ajax_debug_log_scan()
 */
add_action( 'wp_ajax_charitable_debug_log_scan', array( Charitable_Tools_System_Info::get_instance(), 'ajax_debug_log_scan' ) );

/**
 * Capture failed plugin installs/updates into a small ring buffer so the
 * "Recent Plugin Install/Update Errors" section of System Info has data
 * to show. Pure pass-through on success.
 *
 * @since 1.8.10.5
 */
add_filter( 'upgrader_install_package_result', array( 'Charitable_Tools_System_Info', 'log_install_result' ), 10, 2 );

add_action( 'admin_enqueue_scripts', array( Charitable_Tools_Misc::get_instance(), 'enqueue_scripts' ) );

/**
 * Enqueue GiveWP migration tool scripts.
 *
 * @since 1.8.10
 */
add_action( 'admin_enqueue_scripts', array( Charitable_GiveWP_Importer::get_instance(), 'enqueue_scripts' ) );

/**
 * Logger AJAX handlers and script enqueue.
 *
 * @since 1.8.11
 */
add_action( 'wp_ajax_charitable_get_log_record', array( 'Charitable_Log', 'ajax_get_record' ) );
add_action( 'wp_ajax_charitable_delete_all_logs', array( 'Charitable_Log', 'ajax_delete_all_logs' ) );
add_action( 'wp_ajax_charitable_export_logs_csv', array( 'Charitable_Log', 'ajax_export_csv' ) );
add_action( 'wp_ajax_charitable_toggle_logging', array( 'Charitable_Log', 'ajax_toggle_logging' ) );
add_action( 'wp_ajax_charitable_save_log_retention', array( 'Charitable_Log', 'ajax_save_retention' ) );
add_action( 'admin_enqueue_scripts', array( 'Charitable_Log', 'enqueue_scripts' ) );

/**
 * Tools > AI MCP.
 *
 * Surfaces the Abilities API, which ships with no UI of its own, and carries the
 * AI write-access toggle. Charitable_Tools_AI_MCP is autoloaded via the classmap.
 *
 * Ported from Charitable Pro (pinned commit 4f84275197) for Lite 1.8.13. The
 * `ai-mcp` tab itself is registered directly in
 * Charitable_Tools::get_sections(), and the "no form / no Save Changes button"
 * behaviour Pro gets from its `charitable_tools_tabs_without_form` filter is
 * given here by adding 'ai-mcp' to the `$charitable_tab_no_form_tag` array in
 * includes/admin/views/tools/tools.php - the hardcoded-array convention that
 * file already uses for 'import', 'export', 'system-info' and the rest, rather
 * than porting a filter Lite's view template does not read.
 *
 * @since 1.8.13
 */
add_filter(
	'charitable_tools_tab_fields_ai-mcp',
	array( Charitable_Tools_AI_MCP::get_instance(), 'add_fields' ),
	5
);

/**
 * Enqueue the AI MCP panel assets, on that one tab only.
 *
 * @since 1.8.13
 *
 * @return void
 */
add_action(
	'admin_enqueue_scripts',
	function () {

		if ( ! function_exists( 'charitable_is_tools_view' ) || ! charitable_is_tools_view() ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'ai-mcp' !== $tab ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'charitable-ai-mcp',
			charitable()->get_path( 'assets', false ) . 'css/admin/charitable-ai-mcp.css',
			array(),
			charitable()->get_version()
		);

		if ( ! current_user_can( 'manage_charitable_settings' ) ) {
			return;
		}

		/*
		 * jquery-confirm powers the "Watch video" modal. Guarded the same way
		 * Charitable_Admin_Splash guards it, because the splash is enqueued on
		 * this same screen and whichever runs first should win. Do NOT rely on
		 * the splash having done it: the link has to work on a site where the
		 * splash is suppressed (hide_announcements), and without the library the
		 * handler falls through to the anchor's YouTube href.
		 */
		if ( ! wp_style_is( 'jquery-confirm', 'enqueued' ) ) {
			wp_enqueue_style(
				'jquery-confirm',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.css',
				null,
				'3.3.4'
			);
		}

		if ( ! wp_script_is( 'jquery-confirm', 'enqueued' ) ) {
			wp_enqueue_script(
				'jquery-confirm',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.js',
				array( 'jquery' ),
				'3.3.4',
				false
			);
		}

		wp_enqueue_script(
			'charitable-ai-mcp',
			charitable()->get_path( 'assets', false ) . 'js/admin/charitable-ai-mcp.js',
			array( 'jquery', 'jquery-confirm' ),
			charitable()->get_version(),
			true
		);

		wp_localize_script(
			'charitable-ai-mcp',
			'charitable_ai_mcp',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'charitable_ai_mcp_toggle' ),
				// Charitable_Admin_Plugins_Third_Party guards both the install and
				// the activate call with the shared 'charitable-admin' nonce, so the
				// in-place bridge install needs that one rather than the toggle nonce.
				'plugin_nonce' => wp_create_nonce( 'charitable-admin' ),
				'i18n'         => array(
					'error'        => __( 'That could not be saved. Please try again.', 'charitable' ),
					'installing'   => __( 'Installing WPVibe…', 'charitable' ),
					'activating'   => __( 'Activating WPVibe…', 'charitable' ),
					'installError' => __( 'WPVibe could not be installed. Install it from the Plugins screen instead.', 'charitable' ),
					/* The iframe's title attribute, read by screen readers in the video modal. */
					'videoTitle'   => esc_attr__( 'Using Charitable with AI', 'charitable' ),
					/*
					 * The AI Activity toggle's accessible name, which is the only
					 * thing that can say which way the control goes - its visible
					 * label is a chevron. The collapsed wording matches the
					 * `screen-reader-text` rendered in views/ai-mcp-activity.php,
					 * since that is the state the panel loads in.
					 */
					'showActivity' => __( 'Show AI activity', 'charitable' ),
					'hideActivity' => __( 'Hide AI activity', 'charitable' ),
				),
			)
		);
	}
);

/**
 * AJAX: flip the AI write gate.
 *
 * No _nopriv counterpart, deliberately - this changes a security setting.
 *
 * @since 1.8.13
 */
add_action(
	'wp_ajax_charitable_ai_mcp_toggle_write',
	array( Charitable_Tools_AI_MCP::get_instance(), 'ajax_toggle_write' )
);

/**
 * Remember when a user has opened WPVibe's own admin page.
 *
 * On `current_screen`, and NOT gated to the Tools tab, because WPVibe's page is
 * a different screen entirely and the visit has to be caught wherever it
 * happens. Flips the AI MCP tab's primary button from "Open WPVibe Setup" to
 * "Go to WPVibe" so a user is not told to set up something they have already
 * opened. The write is a one-time latch (see maybe_mark_bridge_visited()), so
 * this costs a single user-meta read on other admin screens.
 *
 * @since 1.8.13
 */
add_action(
	'current_screen',
	array( Charitable_Tools_AI_MCP::get_instance(), 'maybe_mark_bridge_visited' )
);
