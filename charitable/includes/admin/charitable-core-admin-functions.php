<?php
/**
 * Charitable Core Admin Functions
 *
 * General core functions available only within the admin area.
 *
 * @package     Charitable/Functions/Admin
 * @version     1.0.0
 * @author      David Bisset
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load a view from the admin/views folder.
 *
 * If the view is not found, an Exception will be thrown.
 *
 * Example usage: charitable_admin_view('metaboxes/campaign-title');
 *
 * @since  1.0.0
 *
 * @param  string $view      The view to display.
 * @param  array  $view_args Optional. Arguments to pass through to the view itself.
 * @param  bool   $return_html Optional. Whether to return the HTML or echo it. Default is false.
 *
 * @return boolean True if the view exists and was rendered. False otherwise.
 */
function charitable_admin_view( $view, $view_args = array(), $return_html = false ) {
	$base_path = array_key_exists( 'base_path', $view_args ) ? $view_args['base_path'] : charitable()->get_path( 'admin' ) . 'views/';

	/**
	 * Filter the path to the view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path      The default path.
	 * @param string $view      The view.
	 * @param array  $view_args View args.
	 */
	$filename = apply_filters( 'charitable_admin_view_path', $base_path . $view . '.php', $view, $view_args );

	if ( ! is_readable( $filename ) ) {
		charitable_get_deprecated()->doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: Filename of passed view */
				__( 'Passed view (%s) not found or is not readable.', 'charitable' ),
				$filename
			),
			'1.0.0'
		);

		return false;
	}

	ob_start();

	include $filename;

	if ( $return_html ) {
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	ob_end_flush();

	return true;
}

/**
 * Returns the Charitable_Settings helper.
 *
 * @since  1.0.0
 *
 * @return Charitable_Settings
 */
function charitable_get_admin_settings() {
	return Charitable_Settings::get_instance();
}

/**
 * Returns the Charitable_Reports helper.
 *
 * @since  1.8.1
 *
 * @return Charitable_Reports
 */
function charitable_get_admin_reports() {
	return Charitable_Reports::get_instance();
}

/**
 * Returns the Charitable_Dashboard helper.
 *
 * @since  1.8.1
 *
 * @return Charitable_Reports
 */
function charitable_get_admin_dashboard() {
	return Charitable_Dashboard::get_instance();
}

/**
 * Returns the Charitable_Tools helper.
 *
 * @since  1.8.1.6
 *
 * @return Charitable_Tools
 */
function charitable_get_admin_tools() {
	return Charitable_Tools::get_instance();
}

/**
 * Returns the Charitable_Intergrations_WPCode helper.
 *
 * @since  1.8.1.6
 *
 * @return Charitable_Intergrations_WPCode
 */
function charitable_get_intergration_wpcode() {
	return Charitable_Intergrations_WPCode::get_instance();
}

/**
 * Returns the Charitable_Tools_System_Info helper.
 *
 * @since  1.8.1.6
 *
 * @return Charitable_Tools_System_Info
 */
function charitable_get_system_info() {
	return Charitable_Tools_System_Info::get_instance();
}

/**
 * Returns the Charitable_Admin_Notices helper.
 *
 * @since  1.4.6
 *
 * @return Charitable_Admin_Notices
 */
function charitable_get_admin_notices() {
	return charitable()->registry()->get( 'admin_notices' );
}

/**
 * Returns whether we are currently viewing the Charitable settings area.
 *
 * @since  1.2.0
 *
 * @param  string $tab Optional. If passed, the function will also check that we are on the given tab.
 * @return boolean
 */
function charitable_is_settings_view( $tab = '' ) {
	if ( ! empty( $_POST ) ) { // phpcs:ignore
		$is_settings = array_key_exists( 'option_page', $_POST ) && 'charitable_settings' === $_POST['option_page']; // phpcs:ignore

		if ( ! $is_settings || empty( $tab ) ) {
			return $is_settings;
		}

		return array_key_exists( 'charitable_settings', $_POST ) && array_key_exists( $tab, $_POST['charitable_settings'] ); // phpcs:ignore
	}

	$is_settings = isset( $_GET['page'] ) && 'charitable-settings' == $_GET['page']; // phpcs:ignore

	if ( ! $is_settings || empty( $tab ) ) {
		return $is_settings;
	}

	/* The general tab can be loaded when tab is not set. */
	if ( 'general' == $tab ) {
		return ! isset( $_GET['tab'] ) || 'general' == $_GET['tab']; // phpcs:ignore
	}

	return isset( $_GET['tab'] ) && $tab == $_GET['tab']; // phpcs:ignore
}

/**
 * Print out the settings fields for a particular settings section.
 *
 * This is based on WordPress' do_settings_fields but allows the possibility
 * of leaving out a field lable/title, for fullwidth fields.
 *
 * @see    do_settings_fields
 *
 * @since  1.0.0
 *
 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
 *
 * @param  string $page       Slug title of the admin page who's settings fields you want to show.
 * @param  string $section    Slug title of the settings section who's fields you want to show.
 * @return string
 */
function charitable_do_settings_fields( $page, $section ) {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {

		$class = '';

		if ( ! empty( $field['args']['class'] ) ) {
			$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
		}

        echo "<tr{$class}>"; // phpcs:ignore

		if ( ! empty( $field['args']['label_for'] ) ) {
            echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . charitable_santitize_setting_labels( $field['title'] ) . '</label></th>'; // phpcs:ignore
			echo '<td>';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
		} elseif ( ! empty( $field['title'] ) ) {
			if ( $field['args']['type'] === 'heading' && isset( $field['args']['help'] ) ) { // if this is a heading display a "help" as a subheading.
				echo '<th scope="row" colspan="2"><h4>' . esc_html( $field['title'] ) . '</h4>';
                echo '<p>' . sanitize_text_field( $field['args']['help'] ) . '</p>'; // phpcs:ignore
			} else {
				echo '<th scope="row"><h4>' . charitable_santitize_setting_labels( $field['title'] ) . '</h4>'; // phpcs:ignore
			}
			echo '</th>';
			if ( $field['args']['type'] !== 'heading' ) {
				echo '<td>';
				call_user_func( $field['callback'], $field['args'] );
				echo '</td>';
			}
		} else {
			echo '<td colspan="2" class="charitable-fullwidth">';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
		}

		echo '</tr>';
	}
}

/**
 * Add new tab to the Charitable settings area.
 *
 * @since  1.3.0
 *
 * @param  string[] $tabs  The existing tabs.
 * @param  string   $key   The key for the new tab.
 * @param  string   $name  The name of the new tab.
 * @param  mixed[]  $args  Additional arguments for the new tab.
 * @return string[]
 */
function charitable_add_settings_tab( $tabs, $key, $name, $args = array() ) {
	$defaults = array(
		'index' => 3,
	);

	$args   = wp_parse_args( $args, $defaults );
	$keys   = array_keys( $tabs );
	$values = array_values( $tabs );

	array_splice( $keys, $args['index'], 0, $key );
	array_splice( $values, $args['index'], 0, $name );

	return array_combine( $keys, $values );
}

/**
 * Returns whether we are currently viewing the Charitable tools area.
 *
 * @since  1.8.1.6
 *
 * @param  string $tab Optional. If passed, the function will also check that we are on the given tab.
 * @return boolean
 */
function charitable_is_tools_view( $tab = '' ) {
	if ( ! empty( $_POST ) ) { // phpcs:ignore
		$is_settings = array_key_exists( 'option_page', $_POST ) && 'charitable_tools' === $_POST['option_page']; // phpcs:ignore

		if ( ! $is_settings || empty( $tab ) ) {
			return $is_settings;
		}

		return array_key_exists( 'charitable_tools', $_POST ) && array_key_exists( $tab, $_POST['charitable_tools'] ); // phpcs:ignore
	}

	$is_settings = isset( $_GET['page'] ) && 'charitable-tools' === $_GET['page']; // phpcs:ignore

	if ( ! $is_settings || empty( $tab ) ) {
		return $is_settings;
	}

	/* The general tab can be loaded when tab is not set. */
	if ( 'general' === $tab ) {
		return ! isset( $_GET['tab'] ) || 'general' == $_GET['tab']; // phpcs:ignore
	}

	return isset( $_GET['tab'] ) && $tab == $_GET['tab']; // phpcs:ignore
}

/**
 * Print out the settings fields for a particular settings section.
 *
 * This is based on WordPress' do_settings_fields but allows the possibility
 * of leaving out a field lable/title, for fullwidth fields.
 *
 * @see    do_settings_fields
 *
 * @since  1.8.1.6
 *
 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
 *
 * @param  string $page       Slug title of the admin page who's settings fields you want to show.
 * @param  string $section    Slug title of the settings section who's fields you want to show.
 * @return string
 */
function charitable_do_tools_fields( $page, $section ) {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
		$class = '';

		if ( ! empty( $field['args']['class'] ) ) {
			$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
		}

		echo "<tr{$class}>"; // phpcs:ignore

		if ( ! empty( $field['args']['label_for'] ) ) {
			echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . charitable_santitize_setting_labels( $field['title'] ) . '</label></th>'; // phpcs:ignore
			echo '<td>';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
		} elseif ( ! empty( $field['title'] ) ) {
			if ( $field['args']['type'] === 'heading' && isset( $field['args']['help'] ) ) { // if this is a heading display a "help" as a subheading.
				echo '<th scope="row" colspan="2"><h4>' . esc_html( $field['title'] ) . '</h4>';
				echo '<p>' . sanitize_text_field( $field['args']['help'] ) . '</p>'; // phpcs:ignore
			} else {
				echo '<th scope="row"><h4>' . charitable_santitize_setting_labels( $field['title'] ) . '</h4>'; // phpcs:ignore
			}
			echo '</th>';
			if ( $field['args']['type'] !== 'heading' ) {
				echo '<td>';
				call_user_func( $field['callback'], $field['args'] );
				echo '</td>';
			}
		} else {
			echo '<td colspan="2" class="charitable-fullwidth">';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
		}

		echo '</tr>';
	}
}

/**
 * Return the donation actions class.
 *
 * @since  1.5.0
 *
 * @return Charitable_Donation_Admin_Actions
 */
function charitable_get_donation_actions() {
	return Charitable_Admin::get_instance()->get_donation_actions();
}

/**
 * Adds the "Upgrade to Pro" menu item to the very end of the submenu.
 *
 * @since 1.7.0
 */
function charitable_add_upgrade_item() {
	global $submenu;

	if ( charitable_is_pro() ) {
		return;
	}

	$submenu['charitable'][99] = array( // phpcs:ignore
		__( 'Upgrade to Pro', 'charitable' ),
		'manage_options',
		charitable_ga_url(
			'https://wpcharitable.com/lite-vs-pro/',
			urlencode( 'Admin Menu Link' ),
			urlencode( 'Upgrade to Pro' )
		),
	);
}
add_action( 'admin_menu', 'charitable_add_upgrade_item' );

/**
 * Determines if Charitable allows legacy campaigns to be created.
 *
 * @since 1.8.2
 *
 * @return bool
 */
function charitable_disable_legacy_campaigns() {

	$disable_legacy_campaign = charitable_get_option( 'disable_campaign_legacy_mode', false ) ? true : false;
	$disable_legacy_campaign = apply_filters( 'charitable_disable_legacy_campaign', $disable_legacy_campaign );

	return $disable_legacy_campaign;
}

/**
 * Outputs "please rate" text.
 *
 * @since 1.7.0
 *
 * @param string $footer_text Footer text.
 * @return string
 */
function charitable_add_footer_text( $footer_text ) {
	if ( ! charitable_is_admin_screen() ) {
		return $footer_text;
	}

	return sprintf(
		/* translators: %1$s Opening strong tag, do not translate. %2$s Closing strong tag, do not translate. %3$s Opening anchor tag, do not translate. %4$s Closing anchor tag, do not translate. */
		__( 'Please rate %1$sCharitable%2$s %3$s★★★★★%4$s on %3$sWordPress.org%4$s to help us spread the word. Thank you from the Charitable team!', 'charitable' ),
		'<strong>',
		'</strong>',
		'<a href="https://wordpress.org/support/plugin/charitable/reviews/?filter=5#new-post" rel="noopener noreferrer" target="_blank">',
		'</a>'
	);
}
add_filter( 'admin_footer_text', 'charitable_add_footer_text' );

/**
 * Check if a screen is a plugin admin view.
 * Returns the screen id if true, false (bool) if not.
 *
 * @since 1.7.0
 * @since 1.8.1.5 Added another check and 'donation' to first check.
 *
 * @return string|bool
 */
function charitable_is_admin_screen() {
	$screen = \get_current_screen();

	if (
		'charitable' === $screen->post_type ||
		'campaign' === $screen->post_type ||
		'donation' === $screen->post_type ||
		'charitable' === $screen->parent_file
	) {
		return 'charitable';
	}

	if ( isset( $_GET['page'] ) ) { // phpcs:ignore
		if ( 'charitable' === trim( $_GET['page'] ) ) { // phpcs:ignore
			return 'charitable';
		}
	}

	if ( isset( $_REQUEST['page'] ) && strpos( sanitize_text_field( $_REQUEST['page'] ), 'charitable' ) !== false ) { // phpcs:ignore
		return 'charitable';
	}

	return false;
}

/**
 * Appends UTM parameters to a given URL.
 *
 * @since 1.7.0
 *
 * @param string $base_url Base URL.
 * @param string $utm_medium utm_medium parameter.
 * @param string $utm_content Optional. utm_content parameter.
 * @return string $url Full Google Analytics campaign URL.
 */
function charitable_ga_url( $base_url, $utm_medium, $utm_content = false ) {
	/**
	 * Filters the UTM campaign for generated links.
	 *
	 * @since 1.7.0
	 *
	 * @param string $utm_campaign
	 */
	$utm_campaign = apply_filters( 'charitable_utm_campaign', 'WP+Charitable' );

	$args = array(
		'utm_source'   => 'WordPress',
		'utm_campaign' => $utm_campaign,
		'utm_medium'   => $utm_medium,
	);

	if ( ! empty( $utm_content ) ) {
		$args['utm_content'] = $utm_content;
	}

	return esc_url( add_query_arg( $args, $base_url ) );
}

/**
 * URL for upgrading to Pro (or another Pro licecnse).
 *
 * @since 1.7.0
 *
 * @param string $utm_medium utm_medium parameter.
 * @param string $utm_content Optional. utm_content parameter.
 * @return string
 */
function charitable_pro_upgrade_url( $utm_medium, $utm_content = '' ) {
	return apply_filters(
		'charitable_upgrade_link',
		charitable_ga_url(
			'https://wpcharitable.com/lite-vs-pro/',
			urlencode( $utm_medium ),
			urlencode( $utm_content )
		),
		$utm_medium,
		$utm_content
	);
}

/**
 * Get the current installation license type (always lowercase).
 *
 * @since 1.7.0.3
 *
 * @return string|false
 */
function charitable_get_license_type() {

	$type = charitable_setting( 'type', '', 'charitable_license' );

	if ( empty( $type ) || ! charitable()->is_pro() ) {
		return false;
	}

	return strtolower( $type );
}

/**
 * Get when WPCharitable was first installed.
 *
 * @since 1.6.0
 *
 * @param string $type Specific install type to check for.
 *
 * @return int|false Unix timestamp. False on failure.
 */
function charitable_get_activated_timestamp( $type = '' ) {

	$activated = (array) get_option( 'charitable_activated', [] );

	if ( empty( $activated ) ) {
		return false;
	}

	// When a passed install type is empty, then get it from a DB.
	// If it is installed/activated first, it is saved first.
	$type = empty( $type ) ? (string) array_keys( $activated )[0] : $type;

	if ( ! empty( $activated[ $type ] ) ) {
		return absint( $activated[ $type ] );
	}

	// Fallback.
	$types = array_diff( [ 'lite', 'pro' ], [ $type ] );

	foreach ( $types as $_type ) {
		if ( ! empty( $activated[ $_type ] ) ) {
			return absint( $activated[ $_type ] );
		}
	}

	return false;
}

/**
 * Get when WPCharitable was first installed.
 *
 * @since 8.1.0
 *
 * @param string $type Specific install type to check for.
 *
 * @return int|false Unix timestamp. False on failure.
 */
function charitable_get_updated_timestamp( $type = '' ) {

	$activated = (array) get_option( 'charitable_activated', [] );

	if ( empty( $activated ) ) {
		return false;
	}

	// When a passed install type is empty, then get it from a DB.
	// If it is installed/activated first, it is saved first.
	$type = empty( $type ) ? (string) array_keys( $activated )[0] : $type;

	if ( ! empty( $activated[ $type ] ) ) {
		return absint( $activated[ $type ] );
	}

	// Fallback.
	$types = array_diff( [ 'lite', 'pro' ], [ $type ] );

	foreach ( $types as $_type ) {
		if ( ! empty( $activated[ $_type ] ) ) {
			return absint( $activated[ $_type ] );
		}
	}

	return false;
}

/**
 * Determines whether the current request is a WP CLI request.
 *
 * @since 1.7.0.3
 *
 * @return bool
 */
function charitable_doing_wp_cli() {

	return defined( 'WP_CLI' ) && WP_CLI;
}

/**
 * Modify the default USer-Agent generated by wp_remote_*() to include additional information.
 *
 * @since 1.7.0.3
 *
 * @return string
 */
function charitable_get_default_user_agent() {

	$wpcharitable_type = function_exists( 'charitable_is_pro' ) && charitable_is_pro() ? 'Paid' : 'Lite';

	return 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) . '; WPCharitable/' . $wpcharitable_type;
}

/**
 * Get the template paths.
 *
 * @since 1.8.0
 *
 * @return array
 */
function charitable_theme_template_paths() {

	$template_dir = 'charitable';

	$file_paths = array(
		1   => trailingslashit( get_stylesheet_directory() ) . $template_dir,
		10  => trailingslashit( get_template_directory() ) . $template_dir,
		100 => trailingslashit( CHARITABLE_DIRECTORY_PATH ) . 'includes',
	);

	$file_paths = apply_filters( 'charitable_helpers_templates_get_theme_template_paths', $file_paths );

	// Sort the file paths based on priority.
	ksort( $file_paths, SORT_NUMERIC );

	return array_map( 'trailingslashit', $file_paths );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * @since 1.7.0.3
 *
 * @param string $template_name Template name.
 *
 * @return string
 */
function charitable_locate_template( $template_name ) {

	// Trim off any slashes from the template name.
	$template_name = ltrim( $template_name, '/' );

	if ( empty( $template_name ) ) {
		return apply_filters( 'charitable_helpers_templates_locate', '', $template_name );
	}

	$located = '';

	// Try locating this template file by looping through the template paths.
	foreach ( charitable_theme_template_paths() as $template_path ) {
		if ( file_exists( $template_path . $template_name ) ) {
			$located = $template_path . $template_name;
			break;
		}
	}

	return apply_filters( 'charitable_helpers_templates_locate', $located, $template_name );
}

/**
 * Include a template.
 * Use 'require' if $args are passed or 'load_template' if not.
 *
 * @since 1.7.0.3
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments.
 * @param bool   $extract       Extract arguments.
 *
 * @throws \RuntimeException If extract() tries to modify the scope.
 */
function charitable_admin_include_html( $template_name, $args = array(), $extract = false ) {

	$template_name .= '.php';

	// Allow 3rd party plugins to filter template file from their plugin.
	$located = apply_filters( 'charitable_helpers_templates_include_html_located', charitable_locate_template( $template_name ), $template_name, $args, $extract );
	$args    = apply_filters( 'charitable_helpers_templates_include_html_args', $args, $template_name, $extract );

	if ( empty( $located ) || ! \is_readable( $located ) ) {
		return;
	}

	// Load template WP way if no arguments were passed.
	if ( empty( $args ) ) {
		load_template( $located, false );
		return;
	}

	$extract = apply_filters( 'charitable_helpers_templates_include_html_extract_args', $extract, $template_name, $args );

	if ( $extract && is_array( $args ) ) {

		$created_vars_count = extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract

		// Protecting existing scope from modification.
		if ( count( $args ) !== $created_vars_count ) {
			throw new \RuntimeException( 'Extraction failed: variable names are clashing with the existing ones.' );
		}
	}

	require $located;
}

/**
 * Like self::include_html, but returns the HTML instead of including.
 *
 * @since 1.7.0.3
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments.
 * @param bool   $extract       Extract arguments.
 *
 * @return string
 */
function charitable_admin_get_html( $template_name, $args = array(), $extract = false ) {
	ob_start();
	charitable_admin_include_html( $template_name, $args, $extract );
	return ob_get_clean();
}

/**
 * Include a template - alias to \charitable\Helpers\Template::get_html.
 * Use 'require' if $args are passed or 'load_template' if not.
 *
 * @since 1.7.0.3
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments.
 * @param bool   $extract       Extract arguments.
 *
 * @throws \RuntimeException If extract() tries to modify the scope.
 *
 * @return string Compiled HTML.
 */
function charitable_render( $template_name, $args = array(), $extract = false ) {
	return charitable_admin_get_html( $template_name, $args, $extract );
}

/**
 * Determine if the plugin/addon installations are allowed.
 *
 * @since 1.6.2.3
 *
 * @param string $type Should be `plugin` or `addon`.
 *
 * @return bool
 */
function charitable_can_install( $type ) {

	return charitable_can_do( 'install', $type );
}

/**
 * Determine if the plugin/addon activations are allowed.
 *
 * @since 1.7.3
 *
 * @param string $type Should be `plugin` or `addon`.
 *
 * @return bool
 */
function charitable_can_activate( $type ) {

	return charitable_can_do( 'activate', $type );
}

/**
 * Determine if the plugin/addon installations/activations are allowed.
 *
 * @since 1.7.3
 * @since 1.8.4.3 // Removed 'Addons require additional license checks' comments.
 *
 * @internal Use charitable_can_activate() or charitable_can_install() instead.
 *
 * @param string $what Should be 'activate' or 'install'.
 * @param string $type Should be `plugin` or `addon`.
 *
 * @return bool
 */
function charitable_can_do( $what, $type ) {

	if ( ! in_array( $what, [ 'install', 'activate' ], true ) ) {
		return false;
	}

	if ( ! in_array( $type, [ 'plugin', 'addon' ], true ) ) {
		return false;
	}

	$capability = $what . '_plugins';

	if ( ! current_user_can( $capability ) ) {
		return false;
	}

	// Determine whether file modifications are allowed and it is activation permissions checking.
	if ( $what === 'install' && ! wp_is_file_mod_allowed( 'charitable_can_install' ) ) {
		return false;
	}

	// All plugin checks are done.
	if ( $type === 'plugin' ) {
		return true;
	}

	return true;
}

/**
 * Perform test connection to verify that the current web host can successfully
 * make outbound SSL connections.
 *
 * @since 1.4.5
 */
function charitable_verify_ssl() {

	// Run a security check.
	check_ajax_referer( 'charitable-admin', 'nonce' );

	// Check for permissions.
	if ( ! charitable_current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'msg' => esc_html__( 'You do not have permission to perform this operation.', 'charitable' ),
			)
		);
	}

	$response = wp_remote_post( 'https://wpcharitable.com/connection-test.php' );

	if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
		wp_send_json_success(
			array(
				'msg' => esc_html__( 'Success! Your server can make SSL connections.', 'charitable' ),
			)
		);
	}

	wp_send_json_error(
		array(
			'msg'   => esc_html__( 'There was an error and the connection failed. Please contact your web host with the technical details below.', 'charitable' ),
			'debug' => '<pre>' . print_r( map_deep( $response, 'wp_strip_all_tags' ), true ) . '</pre>', // phpcs:ignore
		)
	);
}
add_action( 'wp_ajax_charitable_verify_ssl', 'charitable_verify_ssl' );

/**
 * Deactivate addon.
 *
 * @since 1.0.0
 * @since 1.6.2.3 Updated the permissions checking.
 */
function charitable_deactivate_addon() {

	// Run a security check.
	check_ajax_referer( 'charitable-admin', 'nonce' );

	// Check for permissions.
	if ( ! current_user_can( 'deactivate_plugins' ) ) {
		wp_send_json_error( esc_html__( 'Plugin deactivation is disabled for you on this site.', 'charitable' ) );
	}

	$type = empty( $_POST['type'] ) ? 'addon' : sanitize_key( $_POST['type'] );

	if ( isset( $_POST['plugin'] ) ) {
		$plugin = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );

		if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
			error_log( 'charitable_deactivate_addon' ); // phpcs:ignore
			error_log( print_r( $plugin, true ) ); // phpcs:ignore
			error_log( print_r( $_POST, true ) ); // phpcs:ignore
			error_log( print_r( $type, true ) ); // phpcs:ignore
		}

		deactivate_plugins( $plugin );

		do_action( 'charitable_plugin_deactivated', $plugin );

		if ( $type === 'plugin' ) {
			wp_send_json_success( esc_html__( 'Plugin deactivated.', 'charitable' ) );
		} else {
			wp_send_json_success( esc_html__( 'Addon deactivated.', 'charitable' ) );
		}
	}

	wp_send_json_error( esc_html__( 'Could not deactivate the addon. Please deactivate from the Plugins page.', 'charitable' ) );
}
add_action( 'wp_ajax_charitable_deactivate_addon', 'charitable_deactivate_addon' );

/**
 * Activate addon.
 *
 * @since 1.0.0
 * @since 1.6.2.3 Updated the permissions checking.
 */
function charitable_ajax_activate_addon() {

	// Run a security check.
	check_ajax_referer( 'charitable-admin', 'nonce' );

	// Check for permissions.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( esc_html__( 'Plugin activation is disabled for you on this site.', 'charitable' ) );
	}

	$type = 'addon';

	if ( isset( $_POST['plugin'] ) ) {

		if ( ! empty( $_POST['type'] ) ) {
			$type = sanitize_key( $_POST['type'] );
		}

		$plugin = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );

		$activate = activate_plugins( $plugin );

		/**
		 * Fire after plugin activating via the Charitable installer.
		 *
		 * @since 1.6.3.1
		 *
		 * @param string $plugin Path to the plugin file relative to the plugins directory.
		 */
		do_action( 'charitable_plugin_activated', $plugin );

		if ( ! is_wp_error( $activate ) ) {
			if ( $type === 'plugin' ) {
				wp_send_json_success( esc_html__( 'Plugin activated.', 'charitable' ) );
			} else {
				wp_send_json_success( esc_html__( 'Addon activated.', 'charitable' ) );
			}
		}
	}

	if ( $type === 'plugin' ) {
		wp_send_json_error( esc_html__( 'Could not activate the plugin. Please activate it on the Plugins page.', 'charitable' ) );
	}

	wp_send_json_error( esc_html__( 'Could not activate the addon. Please activate it on the Plugins page.', 'charitable' ) );
}
add_action( 'wp_ajax_charitable_activate_addon', 'charitable_ajax_activate_addon' );


/**
 * Installs an Charitable addon.
 *
 * @since 1.0.0
 */
function charitable_ajax_install_addon() {

	// Run a security check first.
	check_admin_referer( 'charitable-admin', 'nonce' );

	// Permission check.
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( esc_html__( 'Plugin install is disabled for you on this site.', 'charitable' ) );
	}

	// Install the addon.
	if ( isset( $_POST['plugin'] ) ) {
		$download_url = esc_url_raw( wp_unslash( $_POST['plugin'] ) );
		global $hook_suffix;

		if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
			error_log( 'charitable_ajax_install_addon' ); // phpcs:ignore
			error_log( print_r( $_POST, true ) ); // phpcs:ignore
			error_log( print_r( $download_url, true ) ); // phpcs:ignore
		}

		// Set the current screen to avoid undefined notices.
		set_current_screen();

		// Prepare variables.
		$method = '';
		$url    = add_query_arg(
			array(
				'page' => 'charitable-settings',
			),
			admin_url( 'admin.php' )
		);
		$url    = esc_url( $url );

		// Start output bufferring to catch the filesystem form if credentials are needed.
		ob_start();
		$creds = request_filesystem_credentials( $url, $method, false, false, null );
		if ( false === $creds ) {
			$form = ob_get_clean();
			echo wp_json_encode( array( 'form' => $form ) );
			die;
		}

		if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
			error_log( 'charitable_ajax_install_addon creds' ); // phpcs:ignore
			error_log( print_r( $creds, true ) ); // phpcs:ignore
		}

		// If we are not authenticated, make it happen now.
		if ( ! WP_Filesystem( $creds ) ) {
			ob_start();
			request_filesystem_credentials( $url, $method, true, false, null );
			$form = ob_get_clean();
			echo wp_json_encode( array( 'form' => $form ) );
			die;
		}

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

		// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once plugin_dir_path( CHARITABLE_DIRECTORY_PATH ) . 'charitable/includes/utilities/Skin.php';

		// Create the plugin upgrader with our custom skin.
		$skin      = new Charitable_Skin();
		$installer = new Plugin_Upgrader( $skin );
		$installer->install( $download_url );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		if ( $installer->plugin_info() ) {
			$plugin_basename = $installer->plugin_info();
			$plugin          = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
			// attempt to activate the installed addon, save the user a step.
			$activate = activate_plugins( $plugin_basename );
			if ( ! is_wp_error( $activate ) ) {
				wp_send_json_success(
					array(
						'basename'     => $plugin_basename,
						'is_activated' => true,
						'msg'          => esc_html__( 'Addon installed and activated.', 'charitable' ),
					)
				);
			} else {
				wp_send_json_success(
					array(
						'basename' => $plugin_basename,
						'msg'      => esc_html__( 'Addon installed.', 'charitable' ),
					)
				);
			}

			die;
		}
	}

	// Send back a response.
	echo wp_json_encode( true );
	die;
}
add_action( 'wp_ajax_charitable_install_addon', 'charitable_ajax_install_addon' );

/**
 * Keep an option updated to know what/when a warning went out for a third party plugin, theme, etc.
 *
 * @since  1.7.0.8
 */
function charitable_update_third_party_warning_option() {

	$third_party_warnings = get_option( 'charitable_third_party_warnings' );
	$updated              = false;

	// If the option is empty or wiped (which is possible), initialize it with an empty array.
	$third_party_warnings = ( false === $third_party_warnings ) ? array( 'plugins' => array() ) : $third_party_warnings = json_decode( $third_party_warnings, ARRAY_A );

	if ( is_plugin_active( 'code-snippets/code-snippets.php' ) && ! array_key_exists( 'code-snippets/code-snippets.php', $third_party_warnings['plugins'] ) ) {
		$third_party_warnings['plugins']['code-snippets/code-snippets.php'] = 'noted';
		$updated = true;
	} elseif ( ! is_plugin_active( 'code-snippets/code-snippets.php' ) && array_key_exists( 'code-snippets/code-snippets.php', $third_party_warnings['plugins'] ) && $third_party_warnings['plugins']['code-snippets/code-snippets.php'] === 'noted' ) {
		unset( $third_party_warnings['plugins']['code-snippets/code-snippets.php'] );
		$updated = true;
	}

	$third_party_warnings = apply_filters( 'charitable_update_third_party_warning_option', $third_party_warnings );

	if ( $updated ) {
		$result = update_option( 'charitable_third_party_warnings', wp_json_encode( $third_party_warnings ) );
	}
}
add_action( 'admin_init', 'charitable_update_third_party_warning_option' );

/**
 * Get an option related to third party warning ( null, noted, dismissed )
 *
 * @since  1.7.0.8
 *
 * @param  string $plugin_path Plugin path.
 * @param  string $category    Category.
 */
function charitable_get_third_party_warning_option( $plugin_path = false, $category = 'plugins' ) {

	if ( false === $plugin_path ) {
		return false;
	}

	$third_party_warnings = get_option( 'charitable_third_party_warnings' );

	if ( false === $third_party_warnings ) {
		return false;
	}

	$third_party_warnings = json_decode( $third_party_warnings, ARRAY_A );

	if ( ! isset( $third_party_warnings[ $category ][ $plugin_path ] ) ) {
		return false;
	}

	return esc_html( $third_party_warnings[ $category ][ $plugin_path ] );
}

/**
 * Get an option related to third party warning ( null, noted, dismissed )
 *
 * @since  1.7.0.8
 *
 * @param  string $plugin_path Plugin path.
 * @param  string $value       Value.
 * @param  string $category    Category.
 */
function charitable_set_third_party_warning_option( $plugin_path = false, $value = false, $category = 'plugins' ) {

	if ( false === $plugin_path ) {
		return;
	}

	$third_party_warnings = get_option( 'charitable_third_party_warnings' );

	if ( false === $third_party_warnings ) {
		return;
	}

	$third_party_warnings = json_decode( $third_party_warnings, ARRAY_A );

	$third_party_warnings[ $category ][ $plugin_path ] = $value;

	$result = update_option( 'charitable_third_party_warnings', wp_json_encode( $third_party_warnings ) );

	return $result;
}

/**
 * Return the latest versions of Charitable plugins.
 *
 * @since  1.8.0
 *
 * @param  array  $licenses  Licenses.
 * @param  string $update_url Update URL.
 *
 * @return array
 */
function charitable_get_addons_data_from_server( $licenses = false, $update_url = 'https://wpcharitable.com' ) {

	$versions = get_transient( '_charitable_plugin_versions' );

	if ( false === $versions ) {

		if ( false === $licenses ) {

			$licenses = array();

			foreach ( charitable_get_licenses() as $license ) {
				if ( isset( $license['license'] ) ) {
					$licenses[] = $license['license'];
				}
			}
		}

		$response = wp_remote_post(
			$update_url . '/edd-api/versions-v3/',
			array(
				'sslverify' => false,
				'timeout'   => 15,
				'body'      => array(
					'licenses' => $licenses,
					'url'      => home_url(),
				),
			)
		);

		$response_body = wp_remote_retrieve_body( $response );
		$response_code = wp_remote_retrieve_response_code( $response );

		// Bail out early if there are any errors.
		if ( (int) $response_code !== 200 || is_wp_error( $response_body ) ) {
			return false;
		}

		$versions = json_decode( $response_body, true );

		set_transient( '_charitable_plugin_versions', $versions, DAY_IN_SECONDS );

	} // end if

	return $versions;
}

if ( ! function_exists( 'charitable_get_licenses' ) ) {
	/**
	 * Return the list of licenses.
	 *
	 * Note: The licenses are not necessarily valid. If a user enters an invalid
	 * license, the license will be stored but it will be flagged as invalid.
	 *
	 * @since  1.8.0
	 *
	 * @return array[]
	 */
	function charitable_get_licenses() {
		return charitable_get_option( 'licenses', array() );
	}
}

/**
 * Get license label from plan id.
 *
 * @since   1.8.0
 *
 * @param   boolean $plan_id Plan ID.
 * @return  string
 */
function charitable_get_license_label_from_plan_id( $plan_id = false ) {

	if ( ! $plan_id ) {
		$settings = get_option( 'charitable_settings' );
		$plan_id  = ! empty( $settings['licenses']['charitable-v2']['plan_id'] ) ? intval( $settings['licenses']['charitable-v2']['plan_id'] ) : false;
	}

	if ( ! $plan_id ) {
		$plan_name = 'Lite';
	}

	switch ( $plan_id ) {
		case 1:
			$plan_name = 'Basic';
			break;
		case 2:
			$plan_name = 'Plus';
			break;
		case 3:
			$plan_name = 'Pro';
			break;
		case 4:
			$plan_name = 'Elite';
			break;
		default:
			$plan_name = 'Lite';
			break;
	}

	return $plan_name;
}

/**
 * Get license label from plan id.
 *
 * @since   1.8.0
 *
 * @param   boolean $plan_id Plan ID.
 * @return  string
 */
function charitable_get_license_slug_from_plan_id( $plan_id = false ) {

	return sanitize_title( charitable_get_license_label_from_plan_id( $plan_id ) );
}


/**
 * Hide non-Charitable warnings.
 *
 * @since  1.8.1.5
 *
 * @return void
 */
function hide_non_charitable_warnings() {

	// Bail if we're not on a charitable screen (another type of check).
	if ( false === charitable_is_admin_screen() ) {
		return;
	}

	charitable_filter_admin_notices( 'user_admin_notices', 'charitable' );
	charitable_filter_admin_notices( 'admin_notices', 'charitable' );
	charitable_filter_admin_notices( 'all_admin_notices', 'charitable' );
}

/**
 * Filter notices.
 *
 * @since 1.8.1.5
 *
 * @param string $notice_type Notice type.
 * @param string $prefix      Prefix.
 *
 * @return void
 */
function charitable_filter_admin_notices( $notice_type, $prefix ) {
	global $wp_filter;
	if ( ! empty( $wp_filter[ $notice_type ]->callbacks ) && is_array( $wp_filter[ $notice_type ]->callbacks ) ) {
		foreach ( $wp_filter[ $notice_type ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if ( is_object( $arr['function'] ) && $arr['function'] instanceof Closure ) {
					unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
					continue;
				}
				if ( ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && false !== strpos( strtolower( get_class( $arr['function'][0] ) ), $prefix ) ) {
					continue;
				}
				if ( ! empty( $name ) && false === strpos( $name, $prefix ) ) {
					unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
				}
			}
		}
	}
}

add_action( 'admin_print_scripts', 'hide_non_charitable_warnings' );
add_action( 'admin_head', 'hide_non_charitable_warnings', PHP_INT_MAX );


/**
 * Remove a dashboard notification via AJAX.
 *
 * @since 1.8.2
 *
 * @return void
 */
function charitable_disable_dashboard_notification_ajax() {

	// Run a security check.
	check_ajax_referer( 'charitable-admin', 'nonce' );

	$notification_id = isset( $_POST['notification_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notification_id'] ) ) : false;

	if ( ! $notification_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid notification ID.', 'charitable' ) ) );
	}

	$notifications = (array) get_option( 'charitable_dashboard_notifications', array() );

	if ( empty( $notifications ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'No notifications found.', 'charitable' ) ) );
	}

	if ( ! empty( $notifications[ $notification_id ] ) ) {
		// add a 'dismissed' key to the notification with the current time.
		$notifications[ $notification_id ]['dismissed'] = time();
		update_option( 'charitable_dashboard_notifications', $notifications );
		wp_send_json_success( array( 'message' => esc_html__( 'Notification removed.', 'charitable' ) ) );
	} else {
		wp_send_json_error( array( 'message' => esc_html__( 'Notification not found.', 'charitable' ) ) );
	}
}

add_action( 'wp_ajax_charitable_disable_dashboard_notification', 'charitable_disable_dashboard_notification_ajax' );

/**
 * Get a checkbox wrapped with markup to be displayed as a toggle.
 *
 * @since 1.8.2
 *
 * @param bool       $checked Is it checked or not.
 * @param string     $name The name for the input.
 * @param string     $description Field description (optional).
 * @param string|int $value Field value (optional).
 * @param string     $label Field label (optional).
 *
 * @return string
 */
function charitable_get_checkbox_toggle( $checked, $name, $description = '', $value = '', $label = '' ) {
	$markup = '<label class="wpcode-checkbox-toggle">';

	$markup .= '<input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
	$markup .= '<span class="wpcode-checkbox-toggle-slider"></span>';
	$markup .= '</label>';
	if ( ! empty( $label ) ) {
		$markup .= '<label class="wpcode-checkbox-toggle-label" for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
	}

	if ( ! empty( $description ) ) {
		$markup .= '<p class="description">' . wp_kses_post( $description ) . '</p>';
	}

	return $markup;
}

/**
 * Gets the unique site ID or token.
 * This is generated from the home URL and two random pieces of data
 * to create a hashed site ID that can anonymize data.
 *
 * @since 1.8.4
 *
 * @return string
 */
function charitable_get_site_token() {
	$wpchar_site_token = get_option( 'wpchar_telemetry_uuid' );
	if ( empty( $wpchar_site_token ) ) {
		$home_url          = get_home_url();
		$uuid              = wp_generate_uuid4();
		$today             = gmdate( 'now' );
		$wpchar_site_token = md5( $home_url . $uuid . $today );
		update_option( 'wpchar_telemetry_uuid', $wpchar_site_token, false );
	}
	return $wpchar_site_token;
}

if ( ! function_exists( 'charitable_get_onboarding_url' ) ) :

	/**
	 * Get the URL for the first screen of the onboarding process.
	 *
	 * @since 1.8.4
	 *
	 * @return string
	 */
	function charitable_get_onboarding_url() {

		$current_site_url = get_site_url();

		return add_query_arg(
			array(
				'token'             => charitable_get_site_token(),
				'version'           => charitable()->get_version(),
				'utm_campaign'      => 'onboarding_charitable_lite',
				'email'             => get_option( 'admin_email' ),
				// generate a random session id to prevent caching.
				'sessionid'         => wp_rand( 10000000, 99999999 ),
				'return'            => rawurlencode(
					base64_encode( get_admin_url( null, 'admin.php') ) // phpcs:ignore
				),
				'update_to_pro_url' => 'https://app.charitable.com/upgrade-free-to-pro?api_token=REPLACE_API_TOKEN&license_key=REPLACE_LICENSE_KEY&oth=11ecbadab9561202d33b5ffb8405f9cb9b783af17b52c4b16e16bd8fbbd6cdccbd2a5445c2cb456cb11cdd555471c19e5e2ad446450df2f4e0fc70e410a814d4&endpoint=&siteurl=' . $current_site_url . '/wp-admin/',
			),
			'https://app.wpcharitable.com/setup-wizard-charitable_lite'
		);
	}

endif;

if ( ! function_exists( 'charitable_get_usage_tracking_setting' ) ) :

	/**
	 * Get the usage tracking setting.
	 *
	 * @since 1.8.4
	 *
	 * @return string
	 */
	function charitable_get_usage_tracking_setting() {
		return (int) get_option( 'charitable_usage_tracking', false );
	}

endif;

if ( ! function_exists( 'charitable_update_usage_tracking_setting' ) ) :

	/**
	 * Update the usage tracking setting.
	 *
	 * @since 1.8.4
	 *
	 * @param int $value The new value (0 or 1).
	 */
	function charitable_update_usage_tracking_setting( $value ) {

		// update options.
		update_option( 'charitable_usage_tracking', $value );

		// update Charitable settings to match.
		$settings                              = get_option( 'charitable_settings' );
		$settings['charitable_usage_tracking'] = $value ? true : false;
		update_option( 'charitable_settings', $settings );
	}

endif;

/**
 * Santitize setting labels to allow only <div> and <span> tags and their respective attributes.
 *
 * @since 1.8.4
 *
 * @param mixed $content The content to sanitize.
 *
 * @return string
 */
function charitable_santitize_setting_labels( $content ) {
	$allowed_tags = array(
		'div'  => array(
			'class' => array(),
			'id'    => array(),
		),
		'span' => array(
			'class' => array(),
			'id'    => array(),
		),
	);

	return wp_kses( $content, $allowed_tags );
}
