<?php
/**
 * Get, update and deactivate webhooks.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Webhook_API
 * @author    Eric Daams
 * @copyright Copyright (c) 2021-2022, WPCharitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.3.0
 * @version   1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Webhook_API' ) ) :

	/**
	 * Charitable_Stripe_Webhook_API
	 *
	 * @since 1.3.0
	 */
	class Charitable_Stripe_Webhook_API {

		/**
		 * Secret key.
		 *
		 * @since 1.3.0
		 *
		 * @var   string
		 */
		private $secret_key;

		/**
		 * The webhook setting key, based on whether we're in test mode
		 * and whether this is for the Connect webhook.
		 *
		 * @since 1.3.0
		 *
		 * @var   string
		 */
		private $setting_key;

		/**
		 * Whether this is for the Connect webhook.
		 *
		 * @since 1.3.0
		 *
		 * @var   boolean
		 */
		private $connect_application;

		/**
		 * Gateway helper.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Gateway_Stripe_AM
		 */
		private $gateway;

		/**
		 * Create class object.
		 *
		 * @since 1.3.0
		 *
		 * @param boolean|null $test_mode           Whether to use test mode or not. If left as
		 *                                          null, the site test mode setting will be used.
		 * @param boolean|null $secret_key          The api login id to use. If left as null, the
		 *                                          stored setting will be used.
		 * @param boolean      $connect_application Whether we want the Connect webhook.
		 */
		public function __construct( $test_mode = null, $secret_key = null, $connect_application = false ) {
			$this->test_mode           = is_null( $test_mode ) ? charitable_get_option( 'test_mode', false ) : $test_mode;
			$this->connect_application = $connect_application;
			$this->secret_key          = is_null( $secret_key ) ? $this->parse_secret_key() : $secret_key;
			$this->setting_key         = $this->parse_setting_key();
			$this->gateway             = new Charitable_Gateway_Stripe_AM();
		}

		/**
		 * Returns class properties.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $prop The property to return.
		 * @return mixed
		 */
		public function __get( $prop ) {
			return isset( $this->$prop ) ? $this->$prop : null;
		}

		/**
		 * Return the set of webhook event types we need to subscribe to.
		 *
		 * @since  1.3.0
		 *
		 * @return array
		 */
		public function get_webhook_events() {
			/**
			 * Filter the events that the webhook will be notified about.
			 *
			 * @since 1.3.0
			 *
			 * @param array $events The events that the webhook will be notified about.
			 */
			return apply_filters(
				'charitable_stripe_webhook_events',
				[
					'charge.refunded',
					'invoice.created',
					'invoice.payment_failed',
					'invoice.payment_succeeded',
					'invoice.payment_action_required',
					'customer.subscription.updated',
					'customer.subscription.deleted',
					'payment_intent.payment_failed',
					'payment_intent.succeeded',
					'checkout.session.completed',
				],
				$this->connect_application
			);
		}

		/**
		 * Return the webhook listener endpoint.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		public function get_webhook_listener() {
			return charitable_get_ipn_url( Charitable_Gateway_Stripe_AM::ID );
		}

		/**
		 * Add a new webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return string The webhook id.
		 */
		public function add_webhook() {
			/**
			 * First check whether the webhook has already been added.
			 */
			$webhook = $this->get_webhook();

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'add_webook' );
				error_log( print_r( $webhook, true ) );
			}

			if ( false === $webhook ) {
				try {

					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( 'add_webook false' );
						error_log( print_r( $this->secret_key, true ) );
					}

					$this->gateway->setup_api( $this->secret_key );

					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( 'add_webook after setup_api' );
					}

					$webhook = \Stripe\WebhookEndpoint::create(
						[
							'url'            => $this->get_webhook_listener(),
							'enabled_events' => $this->get_webhook_events(),
							'api_version'    => Charitable_Gateway_Stripe_AM::STRIPE_API_VERSION,
							'connect'        => $this->connect_application,
						]
					);

					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( 'add_webook after WebhookEndpoint' );
						error_log( print_r( $webhook, true ) );
					}
					// Override the signing secret, for teseting purposes (perhaps with the Stripe API CLI)
					if ( defined( 'CHARITABLE_WEBHOOK_SIGNING_SECRET' ) && CHARITABLE_WEBHOOK_SIGNING_SECRET ) {
						$webhook->secret = CHARITABLE_WEBHOOK_SIGNING_SECRET;
					}
					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( 'add_webook after WebhookEndpoint UPDATED FOR CLI' );
						error_log( print_r( $webhook, true ) );
					}

				} catch ( Exception $e ) {
					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log(
							sprintf(
								// translators: %s is the error message.
								__( 'Error creating Stripe webhook: %s', 'charitable' ),
								$e->getMessage()
							)
						);
					}

					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( 'add_webhook ERROR' );
						error_log( print_r( $e->getMessage(), true ) );
					}

					return 'invalid_request';
				}
			}

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'add_webook returning $webhook->id' );
				error_log( print_r( $webhook->id, true ) );
			}

			return $webhook->id;
		}

		/**
		 * Returns the WebhookEndpoint object, or false if one doesn't exist yet.
		 *
		 * @since  1.3.0
		 *
		 * @return false|\Stripe\WebhookEndpoint
		 */
		public function get_webhook() {
			$webhook_id = charitable_get_option( [ 'gateways_stripe', $this->setting_key ], '' );

			if ( ! $webhook_id ) {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'get_webhook ! $webhook_id' );
				}
				return $this->has_webhook();
			}

			try {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'get_webhook try setup_api using secret key ' . $this->secret_key );
				}
				$this->gateway->setup_api( $this->secret_key );

				return \Stripe\WebhookEndpoint::retrieve( $webhook_id );
			} catch ( Exception $e ) {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'get_webhook try setup_api catch' );
				}
				return false;
			}
		}

		/**
		 * Checks whether a matching webhook already exists within Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @return false|\Stripe\WebhookEndpoint If a webhook exists, returns it.
		 *                                       Otherwise, returns false.
		 */
		public function has_webhook() {
			try {
				$this->gateway->setup_api( $this->secret_key );

				$endpoints = \Stripe\WebhookEndpoint::all( [ 'limit' => 100 ] );

				$endpoint_urls = $this->get_possible_endpoint_urls();

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'has_webhook endpoint_urls' );
					error_log( print_r( $endpoint_urls, true ) );
					error_log( print_r( $endpoints, true ) );
				}

				foreach ( $endpoints->data as $webhook ) {
					if ( ! in_array( $webhook->url, $endpoint_urls ) ) {
						continue;
					}

					/**
					 * If we're looking for a Connect application webhook, check that the application
					 * property is not null. Otherwise, make sure it is null.
					 */
					if ( $this->connect_application ? is_null( $webhook->application ) : ! is_null( $webhook->application ) ) {
						continue;
					}

					return $webhook;
				}
			} catch ( Exception $e ) {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( var_export( $e, true ) );
				}

				return false;
			}

			return false;
		}

		/**
		 * Checks if a webhook needs an update.
		 *
		 * @since  1.3.0
		 *
		 * @param  \Stripe\WebhookEndpoint $webhook The webhook endpoint object.
		 * @return boolean
		 */
		public function webhook_needs_update( $webhook ) {
			/* The webhook is not enabled, so it needs an update. */
			if ( 'enabled' != $webhook->status ) {
				return true;
			}

			/* The webhook is not sending some events we need, so we need to update it. */
			if ( ! in_array( '*', $webhook->enabled_events ) && ! empty( array_diff( $this->get_webhook_events(), $webhook->enabled_events ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Update the webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function update_webhook() {
			$webhook = $this->get_webhook();

			if ( ! $webhook ) {
				return false;
			}

			try {
				$this->gateway->setup_api( $this->secret_key );

				$webhook                 = \Stripe\WebhookEndpoint::retrieve( $webhook->id );
				$webhook->disabled       = false;
				$webhook->enabled_events = $this->get_webhook_events();
				$webhook->save();

				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Deactivate the webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function deactivate_webhook() {
			$webhook = $this->get_webhook();

			if ( ! $webhook ) {
				return false;
			}

			try {
				$this->gateway->setup_api( $this->secret_key );

				$webhook           = \Stripe\WebhookEndpoint::retrieve( $webhook->id );
				$webhook->disabled = true;
				$webhook->save();
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Returns the setting key to use based on whether it's test mode and whether it's for the Connect webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		private function parse_setting_key() {
			if ( $this->test_mode ) {
				return $this->connect_application ? 'test_connect_webhook_id' : 'test_webhook_id';
			}

			return $this->connect_application ? 'live_connect_webhook_id' : 'live_webhook_id';
		}

		/**
		 * Return the secret key to use based on whether it's test mode.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		private function parse_secret_key() {
			$setting = $this->test_mode ? 'test_secret_key' : 'live_secret_key';

			return charitable_get_option( [ 'gateways_stripe', $setting ] );
		}

		/**
		 * Return all possible webhook URLs.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		private function get_possible_endpoint_urls() {
			$home_url = home_url();

			return [
				sprintf( '%s/charitable-listener/%s', untrailingslashit( $home_url ), Charitable_Gateway_Stripe_AM::ID ),
				esc_url_raw( add_query_arg( [ 'charitable-listener' => Charitable_Gateway_Stripe_AM::ID ], trailingslashit( $home_url ) ) ),
				esc_url_raw( add_query_arg( [ 'charitable-listener' => Charitable_Gateway_Stripe_AM::ID ], untrailingslashit( $home_url ) ) ),
			];
		}
	}

endif;
