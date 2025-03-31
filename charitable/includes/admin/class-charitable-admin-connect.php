<?php
/**
 * Charitable Admin Connect.
 *
 * @package Charitable/Classes/Charitable_Admin_Connect
 * @since 1.8.5
 * @version 1.8.5
 * @category Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charitable_Admin_Getting_Started page class.
 *
 * This page is shown when the plugin is first activated.
 *
 * @since 1.8.5
 * @version 1.8.5
 */
class Charitable_Admin_Connect {

	/**
	 * Charitable Pro plugin basename.
	 *
	 * @since 1.8.5
	 *
	 * @var string
	 */
	const PRO_PLUGIN = 'charitable-pro/charitable.php';

	/**
	 * The single instance of this class.
	 *
	 * @var Charitable_Admin_Connect|null
	 */
	private static $instance = null;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.5
	 */
	public function __construct() {
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.5
	 */
	public function hooks() {
	}

	/**
	 * Settings page enqueues.
	 *
	 * @since 1.8.5
	 *
	 * @param string $min Minified suffix.
	 * @param string $version Charitable version.
	 * @param string $assets_dir Assets directory.
	 */
	public function settings_enqueues( $min, $version, $assets_dir ) { // phpcs:ignore

		wp_enqueue_script(
			'charitable-connect',
			charitable()->get_path( 'assets', false ) . "js/admin/charitable-admin-connect{$min}.js",
			array( 'jquery' ),
			$version,
			true
		);
	}

	/**
	 * Generate and return Charitable Connect URL.
	 *
	 * @since 1.8.5
	 */
	public function generate_url() {

		// Run a security check.
		check_ajax_referer( 'charitable-admin', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to install plugins.', 'charitable' ) ) );
		}

		$current_plugin = plugin_basename( charitable()->get_path() . 'charitable.php' );
		$is_pro         = charitable_is_pro();

		// Local development environment.
		if ( charitable_is_localhost() ) {
			wp_send_json_error(
				array(
					'show_manual_upgrade' => true,
					'url'                 => 'https://www.wpcharitable.com/documentation/installing-extensions/',
					'message'             => esc_html__( 'This upgrade is not available on localhost.', 'charitable' ),
				)
			);
		}

		$key = ! empty( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		// Empty license key.
		if ( empty( $key ) ) {
			wp_send_json_error(
				array(
					'show_manual_upgrade' => true,
					'url'                 => 'https://www.wpcharitable.com/documentation/installing-extensions/',
					'message'             => esc_html__( 'Please enter your license key to connect.', 'charitable' ),
				)
			);
		}

		// Whether it is the pro version.
		if ( $is_pro ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Only the Lite version can be upgraded.', 'charitable' ) ) );
		}

		// Verify pro version is not installed.
		$active = activate_plugin( self::PRO_PLUGIN, false, false, true );

		if ( ! is_wp_error( $active ) ) {

			// Deactivate Lite.
			deactivate_plugins( $current_plugin );

			 // phpcs:ignore Charitable.Comments.PHPDocHooks.RequiredHookDocumentation, Charitable.PHP.ValidateHooks.InvalidHookName
			do_action( 'charitable_plugin_deactivated', $current_plugin );

			wp_send_json_success(
				array(
					'message' => esc_html__( 'Charitable Pro is installed but not activated.', 'charitable' ),
					'reload'  => true,
				)
			);
		}

		// Generate URL.
		$oth        = hash( 'sha512', wp_rand() );
		$hashed_oth = hash_hmac( 'sha512', $oth, wp_salt() );

		update_option( 'charitable_connect_token', $oth );
		update_option( 'charitable_connect', $key );

		$version  = charitable()->get_version();
		$endpoint = admin_url( 'admin-ajax.php' );
		$redirect = admin_url( 'admin.php?page=charitable-settings' );
		$url      = add_query_arg(
			array(
				'key'      => $key,
				'oth'      => $hashed_oth,
				'endpoint' => $endpoint,
				'version'  => $version,
				'siteurl'  => admin_url(),
				'homeurl'  => site_url(),
				'redirect' => rawurldecode( base64_encode( $redirect ) ), // phpcs:ignore
				'v'        => 2,
			),
			'https://upgrade.wpcharitable.com'
		);

		wp_send_json_success(
			array(
				'url'      => $url,
				'back_url' => add_query_arg(
					array(
						'action' => 'charitable_connect',
						'oth'    => $hashed_oth,
					),
					$endpoint
				),
			)
		);
	}

	/**
	 * Process Charitable Connect.
	 *
	 * @since 1.8.5
	 */
	public function process() {

		$error = esc_html__( 'There was an error while installing an upgrade. Please download the plugin from charitable.com and install it manually.', 'charitable' );

		if ( charitable_is_debug() ) {
			error_log( 'Charitable Admin Connect process' ); // phpcs:ignore
			error_log( print_r( $_REQUEST, true ) ); // phpcs:ignore
		}

		// Verify params present (oth & download link).
		$post_oth     = ! empty( $_REQUEST['oth'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['oth'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$post_url     = ! empty( $_REQUEST['file'] ) ? esc_url_raw( wp_unslash( $_REQUEST['file'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$post_key     = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$post_item_id = ! empty( $_REQUEST['item_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['item_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $post_oth ) || empty( $post_url ) ) {
			wp_send_json_error( esc_html__( 'No oth or file found.', 'charitable' ) );
		}

		// Verify oth.
		$oth = get_option( 'charitable_connect_token' );

		if ( empty( $oth ) ) {
			wp_send_json_error( esc_html__( 'No oth found.', 'charitable' ) );
		}

		if ( hash_hmac( 'sha512', $oth, wp_salt() ) !== $post_oth ) {
			wp_send_json_error( esc_html__( 'Invalid oth.', 'charitable' ) );
		}

		// Delete so cannot replay.
		delete_option( 'charitable_connect_token' );

		// Set the current screen to avoid undefined notices.
		set_current_screen( 'charitable_page_charitable-settings' );

		// Prepare variables.
		$url = esc_url_raw(
			add_query_arg(
				array( 'page' => 'charitable-settings' ),
				admin_url( 'admin.php' )
			)
		);

		// Verify pro not activated.
		if ( charitable_is_pro() ) {
			wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'charitable' ) );
		}

		// Verify pro not installed.
		$active = activate_plugin( self::PRO_PLUGIN, $url, false, true );

		if ( ! is_wp_error( $active ) ) {
			$plugin = plugin_basename( charitable()->get_path() . 'charitable.php' );

			deactivate_plugins( $plugin );

			do_action( 'charitable_plugin_deactivated', $plugin );

			wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'charitable' ) );
		}

		$creds = request_filesystem_credentials( $url, '', false, false );

		// Check for file system permissions.
		if ( $creds === false || ! WP_Filesystem( $creds ) ) {
			wp_send_json_error(
				esc_html__( 'There was an error while installing an upgrade. Please check file system permissions and try again. Also, you can download the plugin from charitable.com and install it manually.', 'charitable' )
			);
		}

		/*
		 * We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		 */

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once plugin_dir_path( CHARITABLE_DIRECTORY_PATH ) . 'charitable/includes/utilities/Skin.php';

		// Create the plugin upgrader with our custom skin.
		$installer = new Plugin_Upgrader( new Charitable_Skin() );

		// Error check.
		if ( ! method_exists( $installer, 'install' ) ) {
			wp_send_json_error( esc_html__( 'No install method found.', 'charitable' ) );
		}

		// Check license key.
		$key = get_option( 'charitable_connect', false );

		if ( empty( $key ) ) {
			wp_send_json_error(
				new WP_Error(
					'403',
					esc_html__( 'No key provided.', 'charitable' )
				)
			);
		}

		$installer->install( $post_url ); // phpcs:ignore

		if ( charitable_is_debug() ) {
			error_log( 'Charitable Admin Connect process install' ); // phpcs:ignore
			error_log( $post_url ); // phpcs:ignore
			error_log( print_r( $installer->plugin_info(), true ) ); // phpcs:ignore
		}

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		$plugin_basename = $installer->plugin_info();

		if ( $plugin_basename ) {

			// Deactivate the lite version first.
			$plugin = plugin_basename( charitable()->get_path( 'plugin-directory' ) . '/charitable/charitable.php' );

			deactivate_plugins( $plugin );

			// phpcs:ignore Charitable.Comments.PHPDocHooks.RequiredHookDocumentation, Charitable.PHP.ValidateHooks.InvalidHookName
			do_action( 'charitable_plugin_deactivated', $plugin );

			// Activate the plugin silently.
			$activated = activate_plugin( $plugin_basename, '', false, true );

			if ( ! is_wp_error( $activated ) ) {

				// Add the pro_connect activation date to the activated array.
				$activated = (array) get_option( 'charitable_activated', array() );

				if ( empty( $activated['pro_connect'] ) ) {
					$activated['pro_connect'] = time();
					update_option( 'charitable_activated', $activated );
				}

				if ( charitable_is_debug() ) {
					error_log( 'Charitable Admin Connect process activate' ); // phpcs:ignore
					error_log( 'plugin_basename' ); // phpcs:ignore
					error_log( $plugin_basename ); // phpcs:ignore
					error_log( 'activated' ); // phpcs:ignore
					error_log( print_r( $activated, true ) ); // phpcs:ignore
				}

				if ( ! empty( $post_key ) && ! empty( $post_item_id ) ) {

					$data = array(
						'edd_action' => 'activate_license',
						'license'    => $key,
						'legacy'     => false,
						'item_id'    => $post_item_id,
						'url'        => site_url(),
					);

					if ( charitable_is_debug() ) {
						error_log( 'Charitable Admin Connect process activate license' ); // phpcs:ignore
						error_log( print_r( $data, true ) ); // phpcs:ignore
					}

					$response = wp_remote_post( 'https://wpcharitable.com/edd-api/versions-v2/', array( 'body' => $data ) );

					// Get the body of the response.
					$body = wp_remote_retrieve_body( $response );

					if ( charitable_is_debug() ) {
						error_log( 'Charitable Admin Connect process activate license body' ); // phpcs:ignore
						error_log( $body ); // phpcs:ignore
					}

					$license_data = json_decode( $body );

					if ( charitable_is_debug() ) {
						error_log( 'Charitable Admin Connect process activate license data' ); // phpcs:ignore
						error_log( print_r( $license_data, true ) ); // phpcs:ignore
					}

					// if $license_day is an object, convert it to an array.
					if ( is_object( $license_data ) ) {
						$license_data = (array) $license_data;
					}

					if ( empty( $license_data ) || is_wp_error( $response ) ) {
						if ( charitable_is_debug() ) {
							error_log( 'Charitable Admin Connect process activate license response iniitial failure' ); // phpcs:ignore
							error_log( print_r( $response, true ) ); // phpcs:ignore
							error_log( 'Charitable Admin Connect process activate license response iniitial failure license data' ); // phpcs:ignore
							error_log( print_r( $license_data, true ) ); // phpcs:ignore
						}
					} elseif ( ! empty( $license_data['success'] ) && 1 === intval( $license_data['success'] ) && ! empty( $license_data['license'] ) && 'valid' === $license_data['license'] && ! empty( $license_data['expires'] ) ) {

							$settings = get_option( 'charitable_settings' );

							$settings['licenses']['charitable-v2'] = array(
								'license'         => $key,
								'expiration_date' => isset( $license_data['expires'] ) ? $license_data['expires'] : false,
								'plan_id'         => 1, // isset( $license_data['price_id'] ) ? $license_data['price_id'] : false,
								'valid'           => true,
							);

							update_option( 'charitable_settings', $settings );

							if ( charitable_is_debug() ) {
								error_log( 'Charitable Admin Connect process activate license response good' ); // phpcs:ignore
								error_log( print_r( $response, true ) ); // phpcs:ignore
								error_log( 'Charitable Admin Connect process activate license response good license data' ); // phpcs:ignore
								error_log( print_r( $license_data, true ) ); // phpcs:ignore
							}

							set_site_transient( 'update_plugins', null );
							delete_site_option( 'wpc_plugin_versions' );
							update_option( 'charitable_connect_complete', true );
							update_option( 'charitable_connect_completed', true );

					} elseif ( charitable_is_debug() ) {

							error_log( 'Charitable Admin Connect process activate license response failed' ); // phpcs:ignore
							error_log( print_r( $response, true ) ); // phpcs:ignore
							error_log( 'Charitable Admin Connect process activate license response failed license data' ); // phpcs:ignore
							error_log( print_r( $license_data, true ) ); // phpcs:ignore

					}
				}

				wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'charitable' ) );
			} else {
				// Reactivate the lite plugin if pro activation failed.
				activate_plugin( plugin_basename( charitable()->get_path() . 'charitable.php' ), '', false, true );
				wp_send_json_error( esc_html__( 'Pro version installed but needs to be activated on the Plugins page inside your WordPress admin.', 'charitable' ) );
			}
		}

		wp_send_json_error( esc_html__( 'No plugin installed.', 'charitable' ) );
	}



	/**
	 * Returns and/or create the single instance of this class.
	 *
	 * @since  1.8.1.12
	 *
	 * @return Charitable_Admin_Connect
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
