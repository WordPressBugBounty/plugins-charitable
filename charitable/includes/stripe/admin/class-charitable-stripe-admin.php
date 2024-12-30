<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.1.0
 * @version   1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Admin' ) ) :

	/**
	 * Charitable_Stripe_Admin
	 *
	 * @since 1.1.0
	 */
	class Charitable_Stripe_Admin {

		/**
		 * Single instance of this class.
		 *
		 * @since 1.1.0
		 *
		 * @var   Charitable_Stripe_Admin
		 */
		private static $instance = null;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.1.0
		 */
		public function __construct() {

			/**
			 * Add a direct link to the Extensions settings page from the plugin row.
			 */
			if ( class_exists( 'Charitable' ) ) {
				add_filter( 'plugin_action_links_' . plugin_basename( charitable()->get_path() ), [ $this, 'add_plugin_action_links' ] );
			}

			/**
			 * Add settings to the Privacy tab.
			 */
			add_filter( 'charitable_settings_tab_fields_privacy', [ $this, 'add_stripe_privacy_settings' ] );

			/**
			 * When saving Stripe settings, check for webhook if secret key has changed (when you aren't using Stripe Connect AM)
			 */
			add_filter( 'charitable_save_settings', [ $this, 'save_stripe_settings' ], 10, 3 );

			/**
			 * When connecting Stripe Connect, check for webhook if secret key has changed.
			 */
			add_action( 'wpcharitable_stripe_account_connected', [ $this, 'update_webhook_upon_connection' ], 10, 1 );

		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.1.0
		 *
		 * @return Charitable_Stripe_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Charitable_Stripe_Admin();
			}

			return self::$instance;
		}

		/**
		 * Add links to activate
		 *
		 * @since  1.1.0
		 *
		 * @param  string[] $links Plugin action links.
		 * @return string[]
		 */
		public function add_plugin_action_links( $links ) {
			if ( Charitable_Gateways::get_instance()->is_active_gateway( 'stripe' ) ) {
				// $links[] = '<a href="' . admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe&default_gateway=true' ) . '">' . __( 'Settings', 'charitable-stripe' ) . '</a>';
			} else {
				$activate_url = esc_url(
					add_query_arg(
						[
							'charitable_action' => 'enable_gateway',
							'gateway_id'        => 'stripe',
							'_nonce'            => wp_create_nonce( 'gateway' ),
						],
						admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
					)
				);

				$links[] = '<a href="' . $activate_url . '">' . __( 'Activate Stripe Gateway', 'charitable' ) . '</a>';
			}

			return $links;
		}

		/**
		 * Add extra settings to the Privacy tab.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $settings The privacy settings.
		 * @return array
		 */
		public function add_stripe_privacy_settings( $settings ) {
			if ( array_key_exists( 'data_retention_fields', $settings ) ) {
				$settings['data_retention_fields']['options']['stripe'] = __( 'Stripe Data', 'charitable' );
			}

			return $settings;
		}

		/**
		 * When Stripe settings are saved, maybe run background processes to set hidden settings.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $values     The submitted values.
		 * @param  array $new_values The new settings.
		 * @param  array $old_values The previous settings.
		 * @return array
		 */
		public function save_stripe_settings( $values, $new_values, $old_values ) {

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'save_stripe_settings'); // phpcs:ignore
				error_log( print_r( $values, true ) ); // phpcs:ignore
				error_log( print_r( $new_values, true ) ); // phpcs:ignore
				error_log( print_r( $old_values, true ) ); // phpcs:ignore
			}

			/* Bail early if this is not the Stripe settings page. */
			if ( ! array_key_exists( 'gateways_stripe', $values ) ) {
				return $values;
			}

			/* Bail early if Stripe is not an active gateway */
			if ( isset( $values['active_gateways'] ) && ! array_key_exists( 'gateways_stripe', $values['active_gateways'] ) ) {
				return $values;
			}

			/* Add webhooks unless we're on localhost. */
			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'save_stripe_settings midpoint'); // phpcs:ignore
			}

			// a reminder on the charitable_using_stripe_connect check:
			// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
			// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.
			if ( function_exists( 'charitable_stripe_should_setup_webhooks' ) && charitable_stripe_should_setup_webhooks() && ! charitable_using_stripe_connect() ) {
				if ( defined( 'CHARITABLE_FORCE_WEBHOOKS_WITHOUT_STRIPE_CONNECT' ) && CHARITABLE_FORCE_WEBHOOKS_WITHOUT_STRIPE_CONNECT ) {
					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( 'charitable_stripe_should_setup_webhooks exists'); // phpcs:ignore
						error_log( print_r( $values, true ) ); // phpcs:ignore
					}
					$values = $this->setup_webhooks( $values, $new_values, $old_values );
					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( print_r( $values, true ) ); // phpcs:ignore
						error_log( print_r( $new_values, true ) ); // phpcs:ignore
						error_log( print_r( $old_values, true ) ); // phpcs:ignore
					}
				}
			}

			return $values;
		}

		/**
		 * When Stripe settings are saved, maybe run background processes to set hidden settings.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $values     The submitted values.
		 * @param  array $new_values The new settings.
		 * @param  array $old_values The previous settings.
		 * @return array
		 */
		public function update_webhook_upon_connection( $account_data ) {

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'update_webhook_upon_connection 0');
				error_log( print_r( charitable_stripe_should_setup_webhooks(), true ) );
				error_log( print_r( charitable_using_stripe_connect(), true ) );
			}

			// a reminder on the charitable_using_stripe_connect check:
			// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
			// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.
			if ( function_exists( 'charitable_stripe_should_setup_webhooks' ) && function_exists( 'charitable_using_stripe_connect' ) && charitable_stripe_should_setup_webhooks() && charitable_using_stripe_connect() ) {
				// going to "simulate" a save settings so we can use re-use the setup_webhooks function.
				$values = $new_values = $old_values = get_option( 'charitable_settings', array() );

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'update_webhook_upon_connection 3');
					error_log( print_r( $account_data, true ) );
					error_log( print_r( $values, true ) );
					error_log( print_r( $new_values, true ) );
					error_log( print_r( $old_values, true ) );
				}
				$values = $this->setup_webhooks( $values, $new_values, $old_values );

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( print_r( $values, true ) );
				}

				// update the settings
				update_option( 'charitable_settings', $values );

			}
		}

		/**
		 * Set up webhooks after settings are saved.
		 *
		 * @since  1.4.0
		 *
		 * @param  array $values     The submitted values.
		 * @param  array $new_values The new settings.
		 * @param  array $old_values The previous settings.
		 * @return array
		 */
		private function setup_webhooks( $values, $new_values, $old_values ) {
			/* Check whether the stripe_update_hidden_settings upgrade has been completed. */
			$upgrade_log  = get_option( 'charitable_stripe_upgrade_log' );
			$upgrade_done = is_array( $upgrade_log ) && array_key_exists( 'stripe_update_hidden_settings', $upgrade_log );

			$old_settings = $old_values['gateways_stripe'];
			$new_settings = $values['gateways_stripe'];

			$setting_pairs = [
				'test_secret_key' => true,
				'live_secret_key' => false,
			];

			foreach ( $setting_pairs as $setting_key => $test_mode ) {

				$old = isset( $old_settings[ $setting_key ] ) ? trim( $old_settings[ $setting_key ] ) : false;
				$new = trim( $new_settings[ $setting_key ] );

				/* The secret key is unchanged and the upgrade is done, so no need to do anything. */
				if ( $old == $new && $upgrade_done ) {
					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( 'key unchanged' );
						error_log( 'old:' );
						error_log( print_r( $old, true ) );
						error_log( 'new:' );
						error_log( print_r( $new, true ) );
					}
				}

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'old:' );
					error_log( print_r( $old, true ) );
					error_log( 'new:' );
					error_log( print_r( $new, true ) );
				}

				/* If the secret key has changed, deactivate the previously stored webhook. */
				if ( $old != $new ) {
					error_log( 'old != new' );
					$webhook_api = new Charitable_Stripe_Webhook_API( $test_mode, $old );
					$webhook_api->deactivate_webhook();
				}

				/* If the new secret key is blank, set webhook_id to false. */
				if ( '' == $new && isset( $webhook_api->setting_key ) ) {
					$values['gateways_stripe'][ $webhook_api->setting_key ] = false;
					continue;
				}

				/* Finally, if we're still here, add a webhook using the new secret key. */
				$webhook_api = new Charitable_Stripe_Webhook_API( $test_mode, $new );

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'webhook_api here' );
					error_log( print_r( $webhook_api, true ) );
				}

				/* First, check if we have a webhook. */
				$webhook = $webhook_api->get_webhook();

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'webhook here' );
					error_log( print_r( $webhook, true ) );
				}

				/* We don't have a webhook, so create one. */
				if ( ! $webhook ) {
					error_log( 'add webhook We do not have a webhook, so create one.' );
					$webhook_id = $webhook_api->add_webhook();
					error_log( 'add webhook' );
				} else {
					/* We have a webhook, but it needs to be updated. */
					if ( $webhook_api->webhook_needs_update( $webhook ) ) {
						$webhook_api->update_webhook();
						error_log( 'update webhook' );
					}
					$webhook_id = $webhook->id;
					if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
						error_log( print_r( $webhook_id, true ) );
					}
				}

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'final testing data' );
					error_log( print_r( $webhook_api->setting_key, true ) );
					error_log( print_r( $webhook_id, true ) );
				}

				$values['gateways_stripe'][ $webhook_api->setting_key ] = $webhook_id;

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'values gateways_stripe updated' );
					error_log( print_r( $values, true ) );
				}
			}

			/* Mark the upgrade as done. */
			if ( ! $upgrade_done ) {
				if ( ! is_array( $upgrade_log ) ) {
					$upgrade_log = [];
				}

				$upgrade_log['stripe_update_hidden_settings'] = [
					'time'    => time(),
					'version' => charitable_stripe()->get_version(),
				];

				update_option( 'charitable_stripe_upgrade_log', $upgrade_log );
			}

			return $values;
		}
	}

endif;
