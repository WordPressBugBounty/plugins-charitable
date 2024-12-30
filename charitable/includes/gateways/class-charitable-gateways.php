<?php
/**
 * Class that sets up the gateways.
 *
 * @package   Charitable/Classes/Charitable_Gateways
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.57
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateways' ) ) :

	/**
	 * Charitable_Gateways
	 *
	 * @since 1.0.0
	 */
	class Charitable_Gateways {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Gateways|null
		 */
		private static $instance = null;

		/**
		 * All available payment gateways.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $gateways;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the charitable_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since 1.0.0
		 */
		protected function __construct() {
			add_action( 'init', array( $this, 'check_settings' ), 10 );
			add_action( 'init', array( $this, 'register_gateways' ), 1 );
			add_action( 'init', array( $this, 'connect_gateways' ), 99 );
			add_action( 'init', array( $this, 'disconnect_gateways' ), 99 );
			add_action( 'charitable_make_default_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_action( 'charitable_enable_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_action( 'charitable_disable_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_filter( 'charitable_settings_fields_gateways_gateway', array( $this, 'register_gateway_settings' ), 10, 2 );

			add_action( 'charitable_disable_gateway', array( $this, 'handle_gateway_settings_request' ) );

			do_action( 'charitable_gateway_start', $this );

			add_action( 'wp_ajax_charitable-show-stripe-keys', array( $this, 'show_stripe_manual_keys_ajax' ), 10 );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Gateways
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register gateways.
		 *
		 * To register a new gateway, you need to hook into the `charitable_payment_gateways`
		 * hook and give Charitable the name of your gateway class.
		 *
		 * @since  1.2.0
		 *
		 * @return void
		 */
		public function register_gateways() {
			/**
			 * Filter the list of payment gateways.
			 *
			 * @since 1.2.0
			 *
			 * @param array $gateways The list of gateways in gateway ID => gateway class format.
			 */
			$this->gateways           = apply_filters(
				'charitable_payment_gateways',
				array(
					'stripe'  => 'Charitable_Gateway_Stripe_AM',
					'paypal'  => 'Charitable_Gateway_Paypal',
					'offline' => 'Charitable_Gateway_Offline',
				)
			);
			$this->gateways['stripe'] = 'Charitable_Gateway_Stripe_AM';
		}

		/**
		 * Receives a request to enable or disable a payment gateway and validates it before passing it off.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function handle_gateway_settings_request() {
			if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'gateway' ) ) { // phpcs:ignore
				wp_die( __( 'Cheatin\' eh?!', 'charitable' ) ); // phpcs:ignore
			}

			$gateway = isset( $_REQUEST['gateway_id'] ) ? $_REQUEST['gateway_id'] : false;

			/* Gateway must be set */
			if ( false === $gateway ) {
				wp_die( __( 'Missing gateway.', 'charitable' ) ); // phpcs:ignore
			}

			/* Validate gateway. */
			if ( ! isset( $this->gateways[ $gateway ] ) ) {
				wp_die( __( 'Invalid gateway.', 'charitable' ) ); // phpcs:ignore
			}

			switch ( $_REQUEST['charitable_action'] ) {
				case 'disable_gateway':
					$this->disable_gateway( $gateway );
					break;
				case 'enable_gateway':
					$this->enable_gateway( $gateway );
					$this->redirect_gateway_settings( $gateway );
					break;
				case 'make_default_gateway':
					$this->set_default_gateway( $gateway );
					break;
				default:
					/**
					 * Do something when a gateway settings request takes place.
					 *
					 * @since 1.0.0
					 *
					 * @param string $action     The action taking place.
					 * @param string $gateway_id The gateway ID.
					 */
					do_action( 'charitable_gateway_settings_request', $_REQUEST['charitable_action'], $gateway );
			}
		}

		/**
		 * Returns all available payment gateways.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_available_gateways() {
			if ( isset( $this->gateways['stripe'] ) ) {
				$this->gateways['stripe'] = charitable()->is_stripe_connect_addon() ? 'Charitable_Gateway_Stripe_AM' : $this->gateways['stripe'];
			}
			return $this->gateways;
		}

		/**
		 * Returns the current active gateways.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_active_gateways() {
			$active_gateways = charitable_get_option( 'active_gateways', array() );

			foreach ( $active_gateways as $gateway_id => $gateway_class ) {
				if ( ! class_exists( $gateway_class ) ) {
					unset( $active_gateways[ $gateway_id ] );
				}
			}

			uksort( $active_gateways, array( $this, 'sort_by_default' ) );

			// force the strip active gateway normally for stripe addon to point to the stripe built into core.
			if ( isset( $active_gateways['stripe'] ) ) {
				$active_gateways['stripe'] = 'Charitable_Gateway_Stripe_AM';
			}

			/**
			 * Filter the list of active gateways.
			 *
			 * @since 1.0.0
			 *
			 * @param array $gateways Active gateways.
			 */
			return apply_filters( 'charitable_active_gateways', $active_gateways );
		}

		/**
		 * Returns an array of the active gateways, in ID => name format.
		 *
		 * This is useful for select/radio input fields.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_gateway_choices() {
			$gateways = array();

			foreach ( $this->get_active_gateways() as $id => $class ) {
				$gateway         = new $class();
				$gateways[ $id ] = $gateway->get_label();
			}

			return $gateways;
		}

		/**
		 * Returns a text description of the active gateways.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		public function get_active_gateways_names() {
			$gateways = array();

			foreach ( $this->get_active_gateways() as $id => $class ) {
				$gateway    = new $class();
				$gateways[] = $gateway->get_name();
			}

			return $gateways;
		}

		/**
		 * Return the gateway class name for a given gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return string|false
		 */
		public function get_gateway( $gateway ) {
			return isset( $this->gateways[ $gateway ] ) ? $this->gateways[ $gateway ] : false;
		}

		/**
		 * Return the gateway object for a given gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return Charitable_Gateway|null
		 */
		public function get_gateway_object( $gateway ) {
			$class  = $this->get_gateway( $gateway );
			$object = $class ? new $class() : null;

			/**
			 * Filter the gateway object.
			 *
			 * @since 1.6.30
			 *
			 * @param Charitable_Gateway|null $object  The gateway object.
			 */
			return apply_filters( 'charitable_gateway_object_' . $gateway, $object, $gateway );
		}

		/**
		 * Returns whether the passed gateway is active.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway_id Gateway ID.
		 * @return boolean
		 */
		public function is_active_gateway( $gateway_id ) {
			return array_key_exists( $gateway_id, $this->get_active_gateways() );
		}

		/**
		 * Checks whether the submitted gateway is valid.
		 *
		 * @since  1.4.3
		 *
		 * @param  string $gateway Gateway ID.
		 * @return boolean
		 */
		public function is_valid_gateway( $gateway ) {
			/**
			 * Validate a particular gatewya.
			 *
			 * @since 1.4.3
			 *
			 * @param boolean $valid   Whether a gateway is valid.
			 * @param string  $gateway The gateway ID.
			 */

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'is_valid_gateway' );
				error_log( print_r( $gateway, true ) );
				error_log( print_r( $this->gateways, true ) );
			}

			return apply_filters( 'charitable_is_valid_gateway', array_key_exists( $gateway, $this->gateways ), $gateway );
		}

		/**
		 * Returns the default gateway.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_default_gateway() {
			return charitable_get_option( 'default_gateway', '' );
		}

		/**
		 * Provide default gateway settings fields.
		 *
		 * @since  1.0.0
		 *
		 * @param  array              $settings Gateway settings.
		 * @param  Charitable_Gateway $gateway  The gateway's helper object.
		 * @return array
		 */
		public function register_gateway_settings( $settings, Charitable_Gateway $gateway ) {
			add_filter( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), array( $gateway, 'default_gateway_settings' ), 5 );
			add_filter( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), array( $gateway, 'gateway_settings' ), 15 );

			/**
			 * Filter the settings to show for a particular gateway.
			 *
			 * @since 1.0.0
			 *
			 * @param array $settings Gateway settings.
			 */
			return apply_filters( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), $settings );
		}

		/**
		 * Returns true if test mode is enabled.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function in_test_mode() {
			$enabled = charitable_get_option( 'test_mode', false );

			/**
			 * Return whether Charitable is in test mode.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $enabled Whether test mode is on.
			 */
			return apply_filters( 'charitable_in_test_mode', $enabled );
		}

		/**
		 * Checks whether all of the active gateways support a feature.
		 *
		 * If ANY gateway doesn't support the feature, this returns false.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $feature Feature to search for.
		 * @return boolean
		 */
		public function all_gateways_support( $feature ) {
			foreach ( $this->get_active_gateways() as $gateway_id => $gateway_class ) {

				$gateway_object = new $gateway_class();

				if ( false === $gateway_object->supports( $feature ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Checks whether any of the active gateways support a feature.
		 *
		 * If any gateway supports the feature, this returns true. Otherwise false.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $feature Feature to check for.
		 * @return boolean
		 */
		public function any_gateway_supports( $feature ) {
			foreach ( $this->get_active_gateways() as $gateway_id => $gateway_class ) {

				$gateway_object = new $gateway_class();

				if ( true === $gateway_object->supports( $feature ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks whether all of the active gateways support AJAX.
		 *
		 * If ANY gateway doesn't support AJAX, this returns false.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function gateways_support_ajax() {
			return $this->all_gateways_support( '1.3.0' );
		}

		/**
		 * Return an array of recommended gateways for the current site.
		 *
		 * Note that this will only return gateways that are not already
		 * available on the site. i.e. If you have Stripe installed, it
		 * will not suggest that.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_recommended_gateways() {
			$available = $this->get_available_gateways();
			$gateways  = array(
				'payfast'       => __( 'Payfast', 'charitable' ),
				'paystack'      => __( 'Paystack', 'charitable' ),
				'stripe'        => __( 'Stripe', 'charitable' ),
				'authorize_net' => __( 'Authorize.Net', 'charitable' ),
				'windcave'      => __( 'Windcave', 'charitable' ),
				'braintree'     => __( 'Braintree', 'charitable' ),
				'mollie'        => __( 'Mollie', 'charitable' ),
				'gocardless'    => __( 'GoCardless', 'charitable' ),
				'payrexx'       => __( 'Payrexx', 'charitable' ),
			);

			/* If the user has already enabled one of these, leave them alone. :) */
			foreach ( $gateways as $gateway_id => $gateway ) {
				if ( array_key_exists( $gateway_id, $available ) ) {
					return array();
				}
			}

			$currency = charitable_get_default_currency();
			$locale   = get_locale();

			if ( 'en_ZA' == $locale || 'ZAR' == $currency ) {
				return charitable_array_subset( $gateways, array( 'payfast', 'paystack' ) );
			}

			if ( in_array( $currency, array( 'NGN', 'GHS' ) ) ) {
				return charitable_array_subset( $gateways, array( 'paystack' ) );
			}

			if ( in_array( $locale, array( 'en_NZ', 'en_AU', 'en_GB' ) ) || in_array( $currency, array( 'NZD', 'AUD', 'GBP' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'gocardless' ) );
			}

			if ( in_array( $locale, array( 'ms_MY', 'ja', 'zh_HK' ) ) || in_array( $currency, array( 'MYR', 'JPY', 'HKD' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'windcave' ) );
			}

			if ( in_array( $locale, array( 'th' ) ) || in_array( $currency, array( 'BND', 'FJD', 'KWD', 'PGK', 'SBD', 'THB', 'TOP', 'VUV', 'WST' ) ) ) {
				return charitable_array_subset( $gateways, array( 'windcave' ) );
			}

			if ( in_array( $currency, array( 'EUR' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'mollie' ) );
			}

			if ( in_array( $currency, array( 'CHF', 'DKK' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'payrexx' ) );
			}

			return charitable_array_subset( $gateways, array( 'stripe', 'braintree' ) );
		}

		/**
		 * Sets the default gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function set_default_gateway( $gateway ) {
			$settings = get_option( 'charitable_settings' );

			$settings['default_gateway'] = $gateway;

			update_option( 'charitable_settings', $settings );

			charitable_get_admin_notices()->add_success( __( 'Default Gateway Updated', 'charitable' ) );

			do_action( 'charitable_set_gateway_gateway', $gateway );
		}

		/**
		 * Enable a payment gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function enable_gateway( $gateway, $show_update_message = true ) {
			$settings = get_option( 'charitable_settings' );

			if ( false === $settings ) {
				$settings = array();
			}

			$active_gateways             = isset( $settings['active_gateways'] ) ? $settings['active_gateways'] : array();
			$active_gateways[ $gateway ] = $this->gateways[ $gateway ];

			if ( ! isset( $settings['active_gateways'] ) ) {
				$settings['active_gateways'] = array();
			}

			$settings['active_gateways'] = $active_gateways;

			/* If this is the only gateway, make it the default gateway */
			if ( 1 == count( $settings['active_gateways'] ) ) {
				$settings['default_gateway'] = $gateway;
			}

			update_option( 'charitable_settings', $settings );

			if ( $show_update_message ) {
				Charitable_Settings::get_instance()->add_update_message( __( 'Gateway enabled', 'charitable' ), 'success' );
			}

			/**
			 * Do something when a payment gateway is enabled.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gateway The payment gateway.
			 */
			do_action( 'charitable_gateway_enable', $gateway );
		}

		/**
		 * Disable a payment gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function disable_gateway( $gateway ) {
			$settings = get_option( 'charitable_settings' );

			if ( ! isset( $settings['active_gateways'][ $gateway ] ) ) {
				return;
			}

			unset( $settings['active_gateways'][ $gateway ] );

			/* Set a new default gateway */
			if ( $gateway == $this->get_default_gateway() ) {

				$settings['default_gateway'] = count( $settings['active_gateways'] ) ? key( $settings['active_gateways'] ) : '';

			}

			update_option( 'charitable_settings', $settings );

			Charitable_Settings::get_instance()->add_update_message( __( 'Gateway disabled', 'charitable' ), 'success' );

			/**
			 * Do something when a payment gateway is disabled.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gateway The payment gateway.
			 */
			do_action( 'charitable_gateway_disable', $gateway );
		}

		/**
		 * Connects to Stripe by saving account information passed back to the plugin.
		 *
		 * @since 4.2.2
		 *
		 * @return void
		 */
		public function connect_gateways() {
			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Do not need to handle this request, bail.
			if (
				! isset( $_GET['wpcharitable_gateway_connect_completion'] ) ||
				'stripe_connect' !== $_GET['wpcharitable_gateway_connect_completion'] ||
				! isset( $_GET['state'] )
			) {
				return;
			}

			// Unable to redirect, bail.
			if ( headers_sent() ) {
				return;
			}

			if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
				$current_url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			} else {
				$current_url = '';
			}

			$customer_site_url = remove_query_arg(
				array(
					'state',
					'wpcharitable_gateway_connect_completion',
				),
				$current_url
			);

			$wpcharitable_credentials_url = add_query_arg(
				array(
					'live_mode'         => (int) ! charitable_get_option( 'test_mode' ), // (int) ! $this->is_test_mode(),
					'state'             => sanitize_text_field( $_GET['state'] ),
					'customer_site_url' => urlencode( $customer_site_url ),
				),
				'https://wpcharitable.com/stripe-connect/?wpcharitable_gateway_connect_credentials=stripe_connect' // todo: filter market site url
			);

			$response = wp_remote_get( esc_url_raw( $wpcharitable_credentials_url ) ); // add add_filter('https_ssl_verify', '__return_false'); here if testing local

			if (
				is_wp_error( $response ) ||
				200 !== wp_remote_retrieve_response_code( $response )
			) {

				$stripe_account_settings_url = add_query_arg(
					array(
						'tab'   => 'gateways',
						'page'  => 'charitable-settings',
						'group' => 'gateways_stripe',
					),
					admin_url( 'admin.php' )
				);

				$message = wpautop(
					sprintf(
						/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
						__(
							'There was an error getting your Stripe credentials. Please %1$stry again%2$s. If you continue to have this problem, please contact support.',
							'charitable'
						),
						'<a href="' . esc_url( $stripe_account_settings_url ) . '">',
						'</a>'
					)
				);

				wp_die( $message );
			}

			$body = wp_remote_retrieve_body( $response );

			/** @var string $body */
			$body = json_decode( $body, true );

			/** @var array<array<string>> $body */
			$account_data = $body['data'];

			$this->save_account_information( $account_data );

			// update the option to track that we are using stripe connect.
			update_option( 'charitable_using_stripe_connect', time() );

			/**
			 * Allow further processing after connecting a Stripe account.
			 *
			 * @since 3.6.0
			 *
			 * @param array $data Stripe response data.
			 */
			do_action( 'wpcharitable_stripe_account_connected', $account_data );

			// If you configure Stripe and the offline gateway is the default gateway, after you have connected successfully with Stripe, it should automatically be set as the default gateway.
			if ( false === $this->get_default_gateway() || 'offline' === $this->get_default_gateway() ) {
				$this->set_default_gateway( 'stripe' );
			}

			wp_redirect( esc_url_raw( $customer_site_url ) );
			exit;
		}

		/**
		 * Saves the account information sent back from Stripe, alongside other, to identify the connected account.
		 *
		 * @since 4.4.2
		 * @version 1.8.1.12
		 *
		 * @param array<string> $data Stripe oAuth account data.
		 * @return void
		 */
		private function save_account_information( $data, $gateway_id = 'stripe' ) {

			$prefix = $this->in_test_mode()
				? 'test'
				: 'live';

			$settings = get_option( 'charitable_settings' );

			$settings[ 'gateways_' . $gateway_id ]['live_secret_key'] = '';
			$settings[ 'gateways_' . $gateway_id ]['live_public_key'] = '';
			$settings[ 'gateways_' . $gateway_id ]['test_secret_key'] = '';
			$settings[ 'gateways_' . $gateway_id ]['test_public_key'] = '';

			$settings[ 'gateways_' . $gateway_id ][ $prefix . '_secret_key' ] = isset( $data['secret_key'] ) ? sanitize_text_field( $data['secret_key'] ) : false;
			$settings[ 'gateways_' . $gateway_id ][ $prefix . '_public_key' ] = isset( $data['publishable_key'] ) ? sanitize_text_field( $data['publishable_key'] ) : false;

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'save_account_information' );
				error_log( print_r( $settings, true ) );
			}

			update_option( 'charitable_settings', $settings );

			// Don't show the success message if we are on the welcome / getting started page (otherwise it's fine).
			if ( ! isset( $_GET['page'] ) || 'charitable-getting-started' !== $_GET['page'] ) { // phpcs:ignore
				charitable_get_admin_notices()->add_success( __( 'Stripe Information Updated', 'charitable' ) );
			}

			do_action( 'charitable_save_account_information_gateway_' . $gateway_id, $settings, $data, $gateway_id );
		}

		/**
		 * Disconnects from Stripe by removing associated account information.
		 *
		 * This does not deauthorize the application within the Stripe account.
		 *
		 * @since 4.2.2
		 *
		 * @return void
		 */
		public function disconnect_gateways() {

			// Do not need to handle this request, bail.
			if (
				! ( isset( $_GET['page'] ) && 'charitable-settings' === $_GET['page'] ) ||
				! isset( $_GET['wpcharitable-stripe-disconnect'] )
			) {
				return;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! isset( $_GET['_wpnonce'] ) ) {
				return;
			}

			// Invalid nonce, bail.
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpcharitable-stripe-connect-disconnect' ) ) {
				return;
			}

			$settings = get_option( 'charitable_settings' );

			unset( $settings['gateways_stripe']['live_secret_key'] );
			unset( $settings['gateways_stripe']['live_public_key'] );
			unset( $settings['gateways_stripe']['test_secret_key'] );
			unset( $settings['gateways_stripe']['test_public_key'] );

			update_option( 'charitable_settings', $settings );

			// update the option to track that we are using stripe connect.
			delete_option( 'charitable_using_stripe_connect' );

			charitable_get_admin_notices()->add_success( __( 'Stripe Connected Removed', 'charitable' ) );

			do_action( 'charitable_remove_connection_gateway_stripe', $settings );

			$redirect_url = add_query_arg(
				array(
					'tab'   => 'gateways',
					'page'  => 'charitable-settings',
					'group' => 'gateways_stripe',
				),
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Redirects to the gateway settings page once a gateway has been activated.
		 *
		 * @since  1.7.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		public function redirect_gateway_settings( $gateway ) {

			$settings_url = add_query_arg(
				array(
					'group' => 'gateways_' . $gateway,
				),
				admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
			);

			wp_safe_redirect( $settings_url );
			exit;
		}

		/**
		 * Sort the active gateways, placing the default gateway first.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $a Gateway to compare.
		 * @param  string $b Gateway to compare against.
		 * @return int
		 */
		protected function sort_by_default( $a, $b ) {
			$default = $this->get_default_gateway();

			if ( $a == $default ) {
				return -1;
			}

			if ( $b == $default ) {
				return 1;
			}

			return 0;
		}

		/**
		 * Ensure there is a settings option when landing on settings pages, even if init blank. Mostly applicable to new installs.
		 *
		 * @since  1.4.0
		 * @since  1.8.1.11
		 *
		 * @return void
		 */
		public function check_settings() {

			if ( isset( $_GET['page'] ) && 'charitable-settings' !== $_GET['page'] ) { // phpcs:ignore
				return;
			}

			$settings = get_option( 'charitable_settings' );

			// attach this to CHARITABLE_DEBUG constant.
			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG && defined( 'CHARITABLE_DEBUG_SETTINGS' ) && CHARITABLE_DEBUG_SETTINGS ) {
				error_log( 'checking_settings' );
				error_log( print_r( $settings, true ) );
			}

			// If there is no settings, or the settings are empty, set the test mode to true and stripe is default gateway.
			if ( false === $settings || ( is_array( $settings ) && empty( $settings ) ) ) {
				$this->enable_gateway( 'stripe', false );
				$settings              = (array) get_option( 'charitable_settings' );
				$settings['test_mode'] = true;
				update_option( 'charitable_settings', $settings );
			}
		}

		/**
		 * Check the setup stored value of the payment methods.
		 *
		 * @since  1.8.4
		 *
		 * @return bool
		 */
		public function check_setup_payment_methods() {

			$payment_methods = get_option( 'charitable_setup_payment_methods', array() );

			if ( ! is_array( $payment_methods ) ) {
				return false;
			}

			$payment_methods = array_map( 'sanitize_text_field', $payment_methods );

			if ( empty( $payment_methods ) ) {
				return false;
			}

			$gateways_enbled = 0;

			if ( in_array( 'stripe', $payment_methods, true ) ) {
				// check if this gateway is already enabled.
				if ( ! $this->is_active_gateway( 'stripe' ) ) {
					$this->enable_gateway( 'stripe', false );
					++$gateways_enbled;
				}
			}

			if ( in_array( 'paypal', $payment_methods, true ) ) {
				// check if this gateway is already enabled.
				if ( ! $this->is_active_gateway( 'paypal' ) ) {
					$this->enable_gateway( 'paypal', false );
					++$gateways_enbled;
				}
			}

			if ( in_array( 'offline', $payment_methods, true ) ) {
				// check if this gateway is already enabled.
				if ( ! $this->is_active_gateway( 'offline' ) ) {
					$this->enable_gateway( 'offline', false );
				}
			}

			if ( $gateways_enbled > 0 && class_exists( 'Charitable_Checklist' ) ) {
				$checklist_class = Charitable_Checklist::get_instance();
				$checklist_class->mark_step_completed( 'connect-payment' );
			}

			return true;
		}

		/**
		 * Display the test and live fields that represent the manual keys for Stripe that users might be already using prior to 1.7.0.
		 *
		 * @since  1.7.0
		 */
		public function show_stripe_manual_keys_ajax() {

			if ( ! isset( $_POST ) || 'charitable-show-stripe-keys' !== $_POST['action'] ) { // phpcs:ignore
				return;
			}

			// Invalid nonce, bail.
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'charitable-show-stripe-keys' ) ) { // phpcs:ignore
				return;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings = get_option( 'charitable_settings' );

			if ( false === $settings ) {
				return;
			}

			$gateway_id = 'stripe';

			$live_secret_key = $settings[ 'gateways_' . $gateway_id ]['live_secret_key'];
			$live_public_key = $settings[ 'gateways_' . $gateway_id ]['live_public_key'];
			$test_secret_key = $settings[ 'gateways_' . $gateway_id ]['test_secret_key'];
			$test_public_key = $settings[ 'gateways_' . $gateway_id ]['test_public_key'];

			$html = '<tr><th scope="row">Live Settings</th><td><hr  />
			</td></tr><tr class="wide"><th scope="row">Live Secret Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_live_secret_key"
				name="charitable_settings[gateways_stripe][live_secret_key]"
				value="' . trim( $live_secret_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr><tr class="wide"><th scope="row">Live Publishable Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_live_public_key"
				name="charitable_settings[gateways_stripe][live_public_key]"
				value="' . trim( $live_public_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr><tr><th scope="row">Test Settings</th><td><hr  />
			</td></tr><tr class="wide"><th scope="row">Test Secret Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_test_secret_key"
				name="charitable_settings[gateways_stripe][test_secret_key]"
				value="' . trim( $test_secret_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr><tr class="wide"><th scope="row">Test Publishable Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_test_public_key"
				name="charitable_settings[gateways_stripe][test_public_key]"
				value="' . trim( $test_public_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr>';

			wp_send_json_success( $html );
		}
	}

endif;
