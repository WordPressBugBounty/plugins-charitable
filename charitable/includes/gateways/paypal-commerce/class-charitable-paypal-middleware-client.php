<?php
/**
 * PayPal Middleware Client
 *
 * Routes PayPal API calls through the middleware server instead of directly
 * to PayPal. The middleware handles OAuth token management, platform fee injection,
 * and BN code attribution server-side.
 *
 * Modeled after wp-payment-pal's BaseClient/OAuthClient/OrderClient pattern,
 * adapted for WordPress HTTP API and Charitable's WP_Error conventions.
 *
 * @package   Charitable/Gateways/PayPal Commerce
 * @author    WP Charitable
 * @copyright Copyright (c) 2024-2026, Studio 164a
 * @license   GPL-2.0+
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_PayPal_Middleware_Client' ) ) :

	/**
	 * PayPal Middleware Client
	 *
	 * Singleton class that proxies all PayPal API calls through the middleware server.
	 *
	 * @since 1.8.11
	 */
	class Charitable_PayPal_Middleware_Client {

		/**
		 * API path on the middleware server.
		 *
		 * @var string
		 */
		const API_PATH = 'api/paypal/v1';

		/**
		 * Singleton instance.
		 *
		 * @var Charitable_PayPal_Middleware_Client|null
		 */
		private static $instance = null;

		/**
		 * Cached access token.
		 *
		 * @var string|null
		 */
		private $access_token = null;

		/**
		 * Access token expiry timestamp.
		 *
		 * @var int
		 */
		private $access_token_expires = 0;

		/**
		 * Get the singleton instance.
		 *
		 * @since  1.8.11
		 *
		 * @return Charitable_PayPal_Middleware_Client
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Private constructor for singleton.
		 *
		 * @since 1.8.11
		 */
		private function __construct() {
			// Load cached token from transient.
			$gateway  = new Charitable_Gateway_Paypal_Commerce();
			$mode     = $gateway->is_sandbox() ? 'sandbox' : 'live';
			$cached   = get_transient( 'charitable_middleware_token_' . $mode );

			if ( is_array( $cached ) && ! empty( $cached['token'] ) ) {
				$this->access_token         = $cached['token'];
				$this->access_token_expires = isset( $cached['expires'] ) ? (int) $cached['expires'] : 0;
			}
		}

		/**
		 * Check if middleware mode is enabled.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_enabled() {
			$gateway = new Charitable_Gateway_Paypal_Commerce();
			return $gateway->is_middleware_mode();
		}

		/* ──────────────────────────────────────────────
		 * Core HTTP
		 * ────────────────────────────────────────────── */

		/**
		 * Make a request to the middleware server.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $endpoint  API endpoint path (e.g. '/orders/create').
		 * @param  string $method    HTTP method: 'POST' or 'GET'.
		 * @param  array  $body      Request body (for POST) or query params (for GET).
		 * @param  string $auth_type Auth type: 'none', 'basic', or 'bearer'.
		 * @return array|WP_Error Response data array or WP_Error.
		 */
		public function request( $endpoint, $method = 'POST', $body = array(), $auth_type = 'bearer' ) {
			$url = $this->build_url( $endpoint );

			$headers = array(
				'Accept'               => 'application/json',
				'Content-Type'         => 'application/json',
				'X-Charitable-Edition' => 'lite',
				'X-Charitable-Version' => class_exists( 'Charitable' ) ? Charitable::VERSION : 'unknown',
			);

			// Add authorization header.
			if ( 'basic' === $auth_type ) {
				$credentials = $this->get_basic_auth_credentials();
				if ( is_wp_error( $credentials ) ) {
					return $credentials;
				}
				$headers['Authorization'] = 'Basic ' . $credentials;
			} elseif ( 'bearer' === $auth_type ) {
				$token = $this->get_access_token();
				if ( is_wp_error( $token ) ) {
					return $token;
				}
				$headers['Authorization'] = 'Bearer ' . $token;
			}

			Charitable_PayPal_Logger::debug(
				'paypal.middleware.request',
				$method . ' ' . $endpoint,
				array(
					'method'    => $method,
					'endpoint'  => $endpoint,
					'auth_type' => $auth_type,
				)
			);

			// Build request args.
			if ( 'GET' === strtoupper( $method ) ) {
				if ( ! empty( $body ) ) {
					$url = add_query_arg( $body, $url );
				}
				$response = wp_remote_get( $url, array(
					'headers' => $headers,
					'timeout' => 20,
				) );
			} else {
				$response = wp_remote_post( $url, array(
					'headers' => $headers,
					'body'    => wp_json_encode( $body ),
					'timeout' => 20,
				) );
			}

			if ( is_wp_error( $response ) ) {
				Charitable_PayPal_Logger::error(
					'paypal.middleware.http_error',
					$response->get_error_message(),
					array(
						'method'   => $method,
						'endpoint' => $endpoint,
					)
				);
				return $response;
			}

			$http_code = wp_remote_retrieve_response_code( $response );
			$raw_body  = wp_remote_retrieve_body( $response );
			$debug_id  = wp_remote_retrieve_header( $response, 'paypal-debug-id' );
			$data      = json_decode( $raw_body, true );

			if ( ! is_array( $data ) ) {
				$data = array();
			}

			Charitable_PayPal_Logger::debug(
				'paypal.middleware.response',
				'HTTP ' . $http_code,
				array(
					'method'          => $method,
					'endpoint'        => $endpoint,
					'http_code'       => $http_code,
					'paypal_debug_id' => $debug_id,
					'body'            => $data, // Helper redact() will handle access_token / id_token / etc.
				)
			);

			// Check for errors.
			if ( $http_code >= 400 ) {
				$error_message = $this->extract_error_message( $data );
				return new WP_Error(
					'middleware_error',
					$error_message,
					array(
						'http_code' => $http_code,
						'body'      => $data,
						'debug_id'  => $debug_id,
					)
				);
			}

			// Attach debug_id to the response data.
			if ( $debug_id ) {
				$data['debug_id'] = $debug_id;
			}

			return $data;
		}

		/**
		 * Build a full URL from an endpoint path.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $path Endpoint path (e.g. '/orders/create').
		 * @return string Full URL.
		 */
		private function build_url( $path ) {
			$gateway  = new Charitable_Gateway_Paypal_Commerce();
			$base_url = rtrim( $gateway->get_middleware_url(), '/' );
			return $base_url . '/' . self::API_PATH . '/' . ltrim( $path, '/' );
		}

		/**
		 * Get Basic auth credentials (base64 of merchant_id:secret).
		 *
		 * @since  1.8.11
		 *
		 * @return string|WP_Error Base64-encoded credentials or WP_Error.
		 */
		private function get_basic_auth_credentials() {
			$gateway     = new Charitable_Gateway_Paypal_Commerce();
			$merchant_id = $gateway->get_seller_merchant_id();
			$secret      = $gateway->get_middleware_secret();

			if ( empty( $merchant_id ) || empty( $secret ) ) {
				return new WP_Error(
					'middleware_auth_error',
					__( 'Missing merchant ID or middleware secret for authentication.', 'charitable' )
				);
			}

			return base64_encode( $merchant_id . ':' . $secret );
		}

		/**
		 * Get the middleware secret.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		private function get_middleware_secret() {
			$gateway = new Charitable_Gateway_Paypal_Commerce();
			return $gateway->get_middleware_secret();
		}

		/**
		 * Get the seller merchant ID.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		private function get_merchant_id() {
			$gateway = new Charitable_Gateway_Paypal_Commerce();
			return $gateway->get_seller_merchant_id();
		}

		/**
		 * Extract a human-readable error message from a middleware response.
		 *
		 * Follows the wp-payment-pal extractUpstreamErrorDetail pattern:
		 * body.details[0].description -> details[0].issue -> body.message -> error
		 *
		 * @since  1.8.11
		 *
		 * @param  array $data Response data.
		 * @return string Error message.
		 */
		private function extract_error_message( $data ) {
			// Check for upstream PayPal error body.
			if ( ! empty( $data['body'] ) && is_array( $data['body'] ) ) {
				$body = $data['body'];

				// Use the first detail description if available (most specific).
				if ( ! empty( $body['details'] ) && is_array( $body['details'] ) ) {
					$first_detail = $body['details'][0];
					if ( ! empty( $first_detail['description'] ) ) {
						return (string) $first_detail['description'];
					}
					if ( ! empty( $first_detail['issue'] ) ) {
						return (string) $first_detail['issue'];
					}
				}

				// Fall back to PayPal's top-level message.
				if ( ! empty( $body['message'] ) ) {
					return (string) $body['message'];
				}
			}

			// Direct error field.
			if ( ! empty( $data['error'] ) ) {
				return (string) $data['error'];
			}

			// Top-level message.
			if ( ! empty( $data['message'] ) ) {
				return (string) $data['message'];
			}

			return __( 'An unexpected error occurred communicating with the payment service.', 'charitable' );
		}

		/* ──────────────────────────────────────────────
		 * Auth (mirrors OAuthClient)
		 * ────────────────────────────────────────────── */

		/**
		 * Get an access token from the middleware.
		 *
		 * Uses Basic auth (merchant_id:secret) -> /oauth/access-token.
		 * Tokens last 7 days; cached in transient for 6 days.
		 *
		 * @since  1.8.11
		 *
		 * @param  bool $force_refresh Force a new token.
		 * @return string|WP_Error Access token or WP_Error.
		 */
		public function get_access_token( $force_refresh = false ) {
			// Check in-memory cache with 5-minute buffer.
			if ( ! $force_refresh && $this->access_token && $this->access_token_expires > ( time() + 300 ) ) {
				return $this->access_token;
			}

			// Check transient.
			$gateway = new Charitable_Gateway_Paypal_Commerce();
			$mode    = $gateway->is_sandbox() ? 'sandbox' : 'live';
			$transient_key = 'charitable_middleware_token_' . $mode;

			if ( ! $force_refresh ) {
				$cached = get_transient( $transient_key );
				if ( is_array( $cached ) && ! empty( $cached['token'] ) && $cached['expires'] > ( time() + 300 ) ) {
					$this->access_token         = $cached['token'];
					$this->access_token_expires = $cached['expires'];
					return $this->access_token;
				}
			}

			// Request new token via Basic auth.
			$result = $this->request( '/oauth/access-token', 'POST', array(), 'basic' );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( empty( $result['access_token'] ) ) {
				return new WP_Error(
					'middleware_auth_error',
					__( 'Missing access token in middleware response.', 'charitable' )
				);
			}

			$this->access_token = $result['access_token'];
			$expires_in         = isset( $result['expires_in'] ) ? (int) $result['expires_in'] : 604800; // 7 days default.
			$this->access_token_expires = time() + $expires_in;

			// Cache for 6 days (token lasts 7).
			$cache_duration = min( $expires_in - 86400, $expires_in );
			set_transient( $transient_key, array(
				'token'   => $this->access_token,
				'expires' => $this->access_token_expires,
			), $cache_duration );

			return $this->access_token;
		}

		/**
		 * Get a client token for PayPal JS SDK initialization.
		 *
		 * Bearer auth -> /oauth/client-token with {secret, merchant_id}.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error Array with client_token and expires_in, or WP_Error.
		 */
		public function get_client_token() {
			$result = $this->request( '/oauth/client-token', 'POST', array(
				'secret'      => $this->get_middleware_secret(),
				'merchant_id' => $this->get_merchant_id(),
			) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( empty( $result['client_token'] ) ) {
				return new WP_Error(
					'middleware_client_token_error',
					__( 'Missing client token in middleware response.', 'charitable' )
				);
			}

			return array(
				'client_token' => $result['client_token'],
				'expires_in'   => isset( $result['expires_in'] ) ? (int) $result['expires_in'] : 3600,
			);
		}

		/**
		 * Get a merchant-specific SDK client token for Fastlane initialization.
		 *
		 * Calls /oauth/sdk-client-token which generates a token via
		 * POST /v1/oauth2/token with response_type=client_token&intent=sdk_init
		 * and the merchant's PayPal-Auth-Assertion. This token is required by
		 * paypal.Fastlane({ clientToken }) and is domain-bound.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error Array with sdk_client_token and expires_in, or WP_Error.
		 */
		public function get_sdk_client_token() {
			$result = $this->request( '/oauth/sdk-client-token', 'POST', array() );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( empty( $result['sdk_client_token'] ) ) {
				return new WP_Error(
					'middleware_sdk_client_token_error',
					__( 'Missing sdk_client_token in middleware response.', 'charitable' )
				);
			}

			return array(
				'sdk_client_token' => $result['sdk_client_token'],
				'expires_in'       => isset( $result['expires_in'] ) ? (int) $result['expires_in'] : 3600,
			);
		}

		/**
		 * Create a partner referral for seller onboarding.
		 *
		 * No auth -> /oauth/partner-referral with {secret, site_url, return_url}.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $secret     32-character secret.
		 * @param  string $site_url   Merchant site URL (for registration).
		 * @param  string $return_url Optional. URL PayPal redirects to after onboarding. Defaults to site_url.
		 * @return array|WP_Error Array with url, expires_in, and referral_id, or WP_Error.
		 */
		public function create_partner_referral( $secret, $site_url, $return_url = '' ) {
			$body = array(
				'secret'   => $secret,
				'site_url' => $site_url,
			);
			if ( ! empty( $return_url ) ) {
				$body['return_url'] = $return_url;
			}
			$result = $this->request( '/oauth/partner-referral', 'POST', $body, 'none' );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( empty( $result['url'] ) ) {
				return new WP_Error(
					'middleware_referral_error',
					__( 'Missing referral URL in middleware response.', 'charitable' )
				);
			}

			return $result;
		}

		/**
		 * Exchange credentials after onboarding.
		 *
		 * No auth -> /oauth/credentials with {secret, referral_token, merchant_id, site_url, webhooks_url}.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $params Credential exchange parameters.
		 * @return array|WP_Error Response data or WP_Error.
		 */
		public function exchange_credentials( $params ) {
			return $this->request( '/oauth/credentials', 'POST', $params, 'none' );
		}

		/**
		 * Create a PayPal webhook via the middleware.
		 *
		 * Bearer auth -> /webhooks/create with {url, event_types}.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $url         Webhook URL to register.
		 * @param  array  $event_types Array of event type names.
		 * @return array|WP_Error Response data or WP_Error.
		 */
		public function create_webhook( $url, $event_types ) {
			return $this->request( '/webhooks/create', 'POST', array(
				'url'          => $url,
				'event_types'  => $event_types,
			) );
		}

		/* ──────────────────────────────────────────────
		 * Orders (mirrors OrderClient)
		 * ────────────────────────────────────────────── */

		/**
		 * Create a PayPal order via the middleware.
		 *
		 * Bearer auth -> /orders/create with {data: $order_data}.
		 * The middleware injects platform fees server-side.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $order_data The order payload (intent, purchase_units, payment_source, etc.).
		 * @return array|WP_Error Response data with id, status, etc., or WP_Error.
		 */
		public function create_order( $order_data ) {
			$result = $this->request( '/orders/create', 'POST', array(
				'data' => $order_data,
			) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Normalize: middleware may return order_id or id.
			if ( ! empty( $result['order_id'] ) && empty( $result['id'] ) ) {
				$result['id'] = $result['order_id'];
			}

			return $result;
		}

		/**
		 * Capture a PayPal order via the middleware.
		 *
		 * Bearer auth -> /orders/capture with {id: $order_id}.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $order_id PayPal order ID.
		 * @return array|WP_Error Capture response data or WP_Error.
		 */
		public function capture_order( $order_id ) {
			$result = $this->request( '/orders/capture', 'POST', array(
				'id' => $order_id,
			) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Normalize the response to match the direct PayPal format that existing
			// code expects (body with purchase_units, status, etc.).
			// The middleware returns {order_id, capture_id, transaction_id, status, payer, data}.
			// If 'data' key exists with the full PayPal response, return that.
			if ( ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
				return $result['data'];
			}

			return $result;
		}

		/**
		 * Get order details via the middleware.
		 *
		 * Bearer auth -> /orders/get?id=$order_id.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $order_id PayPal order ID.
		 * @return array|WP_Error Order data or WP_Error.
		 */
		public function get_order( $order_id ) {
			$result = $this->request( '/orders/get', 'GET', array(
				'id' => $order_id,
			) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Return the nested data if present.
			if ( ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
				return $result['data'];
			}

			return $result;
		}

		/**
		 * Refund a captured payment via the middleware.
		 *
		 * Bearer auth -> /orders/refund with {id, amount?, currency?, note_to_payer?}.
		 *
		 * @since  1.8.11
		 *
		 * @param  string      $capture_id   PayPal capture ID.
		 * @param  float|null  $amount       Refund amount (null for full refund).
		 * @param  string      $currency     Currency code.
		 * @param  string      $note         Note to payer.
		 * @return array|WP_Error Refund response or WP_Error.
		 */
		public function refund( $capture_id, $amount = null, $currency = 'USD', $note = '' ) {
			$payload = array(
				'id' => $capture_id,
			);

			if ( null !== $amount && $amount > 0 ) {
				$payload['amount']   = number_format( (float) $amount, 2, '.', '' );
				$payload['currency'] = strtoupper( $currency );
			}

			if ( ! empty( $note ) ) {
				$payload['note_to_payer'] = substr( $note, 0, 255 );
			}

			return $this->request( '/orders/refund', 'POST', $payload );
		}

		/* ──────────────────────────────────────────────
		 * Subscriptions (mirrors SubscriptionClient — processor endpoints)
		 * ────────────────────────────────────────────── */

		/**
		 * Create a subscription via the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $data Subscription data.
		 * @return array|WP_Error
		 */
		public function create_subscription( $data ) {
			return $this->request( '/subscriptions/processor/create', 'POST', array(
				'data' => $data,
			) );
		}

		/**
		 * Capture a subscription via the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $id Subscription ID.
		 * @return array|WP_Error
		 */
		public function capture_subscription( $id ) {
			return $this->request( '/subscriptions/processor/capture', 'POST', array(
				'id' => $id,
			) );
		}

		/**
		 * Cancel a subscription via the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $id Subscription ID.
		 * @return array|WP_Error
		 */
		public function cancel_subscription( $id ) {
			return $this->request( '/subscriptions/processor/cancel', 'POST', array(
				'id' => $id,
			) );
		}

		/**
		 * Get subscription details via the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $id Subscription ID.
		 * @return array|WP_Error
		 */
		public function get_subscription( $id ) {
			return $this->request( '/subscriptions/get', 'GET', array(
				'id' => $id,
			) );
		}

		/**
		 * List the transactions on a PayPal subscription via the middleware.
		 *
		 * PayPal's /v1/billing/subscriptions/{id}/transactions endpoint
		 * requires start_time and end_time query params and returns the
		 * underlying capture/sale transactions for the subscription's
		 * billing cycles. The transaction's `id` is what `/v2/payments/
		 * captures/{id}/refund` accepts, so this is how we recover a
		 * refundable capture_id for subscription-mode donations (which
		 * never get one through the Orders API capture flow).
		 *
		 * @since  1.8.11
		 *
		 * @param  string $id         Subscription ID.
		 * @param  string $start_time ISO-8601 timestamp.
		 * @param  string $end_time   ISO-8601 timestamp.
		 * @return array|WP_Error
		 */
		public function get_subscription_transactions( $id, $start_time, $end_time ) {
			return $this->request( '/subscriptions/transactions', 'GET', array(
				'id'     => $id,
				'params' => array(
					'start_time' => $start_time,
					'end_time'   => $end_time,
				),
			) );
		}

		/* ──────────────────────────────────────────────
		 * Billing Plans & Native Subscriptions
		 * ────────────────────────────────────────────── */

		/**
		 * Create a billing plan via the middleware.
		 *
		 * Proxies to PayPal /v1/billing/plans with the merchant's auth assertion.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $plan_data Billing plan data (same structure as direct PayPal API).
		 * @return array|WP_Error Plan response data or WP_Error.
		 */
		public function create_billing_plan( $plan_data ) {
			return $this->request( '/plans/create', 'POST', array(
				'data' => $plan_data,
			) );
		}

		/**
		 * Create a native PayPal subscription via the middleware.
		 *
		 * Proxies to PayPal /v1/billing/subscriptions with the merchant's auth assertion.
		 * Note: native subscriptions do not support platform fees; use the vault/Orders
		 * API flow for fee injection.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $subscription_data Subscription data (same structure as direct PayPal API).
		 * @return array|WP_Error Subscription response data or WP_Error.
		 */
		public function create_native_subscription( $subscription_data ) {
			return $this->request( '/subscriptions/create', 'POST', array(
				'data' => $subscription_data,
			) );
		}

		/**
		 * Create a product via the middleware.
		 *
		 * Proxies to PayPal /v1/catalogs/products with the merchant's auth assertion.
		 * Used to create the product that billing plans are associated with.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $product_data Product data.
		 * @return array|WP_Error Product response data or WP_Error.
		 */
		public function create_product( $product_data ) {
			return $this->request( '/products/create', 'POST', array(
				'data' => $product_data,
			) );
		}

		/* ──────────────────────────────────────────────
		 * Customer (mirrors CustomerClient)
		 * ────────────────────────────────────────────── */

		/**
		 * Create a vault setup token via the middleware.
		 *
		 * Bearer auth -> /vault/setup-tokens/create with {data:{...setup payload...}}.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $setup_data Body matching PayPal's POST /v3/vault/setup-tokens schema.
		 * @return array|WP_Error
		 */
		public function create_setup_token( $setup_data ) {
			return $this->request( '/vault/setup-tokens/create', 'POST', array( 'data' => $setup_data ) );
		}

		/**
		 * Create a vault payment token from a confirmed setup token via the middleware.
		 *
		 * Bearer auth -> /vault/payment-tokens/create with {data:{...payment_token payload...}}.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $token_data Body matching PayPal's POST /v3/vault/payment-tokens schema.
		 * @return array|WP_Error
		 */
		public function create_payment_token( $token_data ) {
			return $this->request( '/vault/payment-tokens/create', 'POST', array( 'data' => $token_data ) );
		}

		/**
		 * Update customer data on the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $data Customer update data (webhooks_url, license_key, etc.).
		 * @return array|WP_Error
		 */
		public function update_customer( $data ) {
			return $this->request( '/customers/update', 'POST', $data );
		}

		/**
		 * Disconnect from the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error
		 */
		public function disconnect() {
			return $this->request( '/customers/disconnect', 'POST' );
		}

		/**
		 * Get merchant info from the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error
		 */
		public function get_merchant_info() {
			return $this->request( '/customers/merchant-info', 'GET' );
		}

		/**
		 * Get credentials from the middleware.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error
		 */
		public function get_credentials() {
			return $this->request( '/customers/credentials', 'GET' );
		}

		/**
		 * Generate a PayPal user identity token (id_token) for card field vaulting.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $customer_id Optional PayPal customer ID for returning payers.
		 * @return array|WP_Error Array with id_token and expires_in, or WP_Error.
		 */
		public function get_user_id_token( $customer_id = '' ) {
			$body = array();
			if ( ! empty( $customer_id ) ) {
				$body['customer_id'] = $customer_id;
			}

			$result = $this->request( '/oauth/user-id-token', 'POST', $body );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( empty( $result['id_token'] ) ) {
				return new WP_Error(
					'middleware_user_id_token_error',
					__( 'Missing id_token in middleware response.', 'charitable' )
				);
			}

			return array(
				'id_token'   => $result['id_token'],
				'expires_in' => isset( $result['expires_in'] ) ? (int) $result['expires_in'] : 3600,
			);
		}

		/**
		 * Test webhook connectivity.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error
		 */
		public function test_webhook() {
			return $this->request( '/webhooks/test', 'POST' );
		}
	}

endif;
