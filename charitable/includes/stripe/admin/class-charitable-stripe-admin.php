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
				add_filter( 'plugin_action_links_' . plugin_basename( charitable()->get_path() ), array( $this, 'add_plugin_action_links' ) );
			}

			/**
			 * Add settings to the Privacy tab.
			 */
			add_filter( 'charitable_settings_tab_fields_privacy', array( $this, 'add_stripe_privacy_settings' ) );

			/**
			 * When saving Stripe settings, check for webhook if secret key has changed (when you aren't using Stripe Connect AM)
			 */
			add_filter( 'charitable_save_settings', array( $this, 'save_stripe_settings' ), 10, 3 );

			/**
			 * When connecting Stripe Connect, check for webhook if secret key has changed.
			 */
			add_action( 'wpcharitable_stripe_account_connected', array( $this, 'update_webhook_upon_connection' ), 10, 1 );

			/**
			 * Run webhook signing secret migration on admin_init.
			 */
			add_action( 'admin_init', array( $this, 'maybe_migrate_webhook_signing_secrets' ) );

			/**
			 * Add webhook security status to Stripe gateway settings.
			 */
			add_filter( 'charitable_settings_fields_gateways_gateway_stripe', array( $this, 'add_webhook_security_settings' ), 20 );

			/**
			 * Display admin notice if webhook signature verification failures are detected.
			 */
			add_action( 'admin_notices', array( $this, 'maybe_show_webhook_failure_notice' ) );

			/**
			 * AJAX handler for refreshing webhook signing secret.
			 */
			add_action( 'wp_ajax_charitable_refresh_webhook_signing_secret', array( $this, 'ajax_refresh_webhook_signing_secret' ) );

			/**
			 * Add "Sync Pending Donations" UI to Stripe gateway settings.
			 */
			add_filter( 'charitable_settings_fields_gateways_gateway_stripe', array( $this, 'add_sync_pending_donations_settings' ), 25 );

			/**
			 * AJAX handler for syncing pending Stripe donations.
			 */
			add_action( 'wp_ajax_charitable_sync_pending_stripe_donations', array( $this, 'ajax_sync_pending_stripe_donations' ) );
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
						array(
							'charitable_action' => 'enable_gateway',
							'gateway_id'        => 'stripe',
							'_nonce'            => wp_create_nonce( 'gateway' ),
						),
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

			if ( charitable_is_debug() ) {
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
			if ( charitable_is_debug() ) {
				error_log( 'save_stripe_settings midpoint'); // phpcs:ignore
			}

			// a reminder on the charitable_using_stripe_connect check:
			// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
			// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.
			if ( function_exists( 'charitable_stripe_should_setup_webhooks' ) && charitable_stripe_should_setup_webhooks() && ! charitable_using_stripe_connect() ) {
				if ( defined( 'CHARITABLE_FORCE_WEBHOOKS_WITHOUT_STRIPE_CONNECT' ) && CHARITABLE_FORCE_WEBHOOKS_WITHOUT_STRIPE_CONNECT ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable_stripe_should_setup_webhooks exists'); // phpcs:ignore
						error_log( print_r( $values, true ) ); // phpcs:ignore
					}
					$values = $this->setup_webhooks( $values, $new_values, $old_values );
					if ( charitable_is_debug() ) {
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
		 * @param  array $account_data The account data.
		 * @return void
		 */
		public function update_webhook_upon_connection( $account_data ) {

			if ( charitable_is_debug() ) {
				// phpcs:disable
				error_log( 'update_webhook_upon_connection 0' );
				error_log( print_r( charitable_stripe_should_setup_webhooks(), true ) );
				error_log( print_r( charitable_using_stripe_connect(), true ) );
				// phpcs:enable
			}

			// a reminder on the charitable_using_stripe_connect check:
			// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
			// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.
			if ( function_exists( 'charitable_stripe_should_setup_webhooks' ) && function_exists( 'charitable_using_stripe_connect' ) && charitable_stripe_should_setup_webhooks() && charitable_using_stripe_connect() ) {
				// going to "simulate" a save settings so we can use re-use the setup_webhooks function.
				$values = $new_values = $old_values = get_option( 'charitable_settings', array() ); // phpcs:ignore

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'update_webhook_upon_connection 3' );
					error_log( print_r( $account_data, true ) );
					error_log( print_r( $values, true ) );
					error_log( print_r( $new_values, true ) );
					error_log( print_r( $old_values, true ) );
					// phpcs:enable
				}
				$values = $this->setup_webhooks( $values, $new_values, $old_values );

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( print_r( $values, true ) );
					// phpcs:enable
				}

				// update the settings.
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

			$setting_pairs = array(
				'test_secret_key' => true,
				'live_secret_key' => false,
			);

			foreach ( $setting_pairs as $setting_key => $test_mode ) {

				$old = isset( $old_settings[ $setting_key ] ) ? trim( $old_settings[ $setting_key ] ) : false;
				$new = trim( $new_settings[ $setting_key ] );

				/* The secret key is unchanged and the upgrade is done, so no need to do anything. */
				if ( $old == $new && $upgrade_done ) {
					if ( charitable_is_debug() ) {
						// phpcs:disable
						error_log( 'key unchanged' );
						error_log( 'old:' );
						error_log( print_r( $old, true ) );
						error_log( 'new:' );
						error_log( print_r( $new, true ) );
						// phpcs:enable
					}
				}

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'old:' );
					error_log( print_r( $old, true ) );
					error_log( 'new:' );
					error_log( print_r( $new, true ) );
					// phpcs:enable
				}

				/* If the secret key has changed, deactivate the previously stored webhook. */
				if ( $old != $new ) {
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

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'webhook_api here' );
					error_log( print_r( $webhook_api, true ) );
					// phpcs:enable
				}

				/* First, check if we have a webhook. */
				$webhook = $webhook_api->get_webhook();

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'webhook here' );
					error_log( print_r( $webhook, true ) );
					// phpcs:enable
				}

				/* We don't have a webhook, so create one. */
				if ( ! $webhook ) {
					// phpcs:disable
					error_log( 'add webhook We do not have a webhook, so create one.' );
					$webhook_id = $webhook_api->add_webhook();
					error_log( 'add webhook' );
					// phpcs:enable
				} else {
					/* We have a webhook, but it needs to be updated. */
					if ( $webhook_api->webhook_needs_update( $webhook ) ) {
						$webhook_api->update_webhook();
					}
					$webhook_id = $webhook->id;
					if ( charitable_is_debug() ) {
						// phpcs:disable
						error_log( print_r( $webhook_id, true ) );
						// phpcs:enable
					}
				}

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'final testing data' );
					error_log( print_r( $webhook_api->setting_key, true ) );
					error_log( print_r( $webhook_id, true ) );
					// phpcs:enable
				}

				$values['gateways_stripe'][ $webhook_api->setting_key ] = $webhook_id;

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'values gateways_stripe updated' );
					error_log( print_r( $values, true ) );
					// phpcs:enable
				}
			}

			/* Mark the upgrade as done. */
			if ( ! $upgrade_done ) {
				if ( ! is_array( $upgrade_log ) ) {
					$upgrade_log = array();
				}

				$upgrade_log['stripe_update_hidden_settings'] = array(
					'time'    => time(),
					'version' => charitable_stripe()->get_version(),
				);

				update_option( 'charitable_stripe_upgrade_log', $upgrade_log );
			}

			return $values;
		}

		/**
		 * Migration: Fetch and store webhook signing secrets for existing webhooks.
		 *
		 * Runs once on upgrade to 1.8.9.8. Re-creates existing webhooks to obtain
		 * and store the signing secret for cryptographic signature verification.
		 *
		 * @since  1.8.9.8
		 *
		 * @return void
		 */
		public function maybe_migrate_webhook_signing_secrets() {
			// Never run during AJAX requests — this migration makes live Stripe API calls
			// and must only run during a real admin page load.
			if ( wp_doing_ajax() ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Stripe webhook classes are only loaded when Stripe is an active gateway.
			if ( ! class_exists( 'Charitable_Stripe_Webhook_API' ) ) {
				return;
			}

			// Charitable_Gateways must be available before calling is_active_gateway().
			if ( ! class_exists( 'Charitable_Gateways' ) ) {
				return;
			}

			$upgrade_log  = get_option( 'charitable_stripe_upgrade_log', array() );
			$upgrade_done = is_array( $upgrade_log ) && array_key_exists( 'stripe_webhook_signing_secrets', $upgrade_log );

			if ( $upgrade_done ) {
				// Retry if migration previously failed.
				$needs_retry = (bool) get_transient( 'charitable_stripe_signing_secret_migration_failed' );

				if ( $needs_retry ) {
					// Throttle retries — don't hammer the Stripe API on every page load.
					if ( get_transient( 'charitable_stripe_migration_retry_cooldown' ) ) {
						return;
					}
				}

				if ( ! $needs_retry ) {
					// Also retry if the current mode has API keys but no signing secret for the
					// direct webhook. Connect webhook failures are non-blocking.
					$test_mode = charitable_get_option( 'test_mode', false );
					$check     = new Charitable_Stripe_Webhook_API( $test_mode, null, false );
					if ( $check->has_api_key() && ! $check->has_signing_secret() ) {
						$needs_retry = true;
					}
				}

				if ( ! $needs_retry ) {
					return;
				}

				unset( $upgrade_log['stripe_webhook_signing_secrets'] );
				update_option( 'charitable_stripe_upgrade_log', $upgrade_log );
			}

			// Check if Stripe gateway is active.
			if ( ! Charitable_Gateways::get_instance()->is_active_gateway( 'stripe' ) ) {
				// Mark as done — no Stripe gateway active, nothing to migrate.
				$this->mark_signing_secret_migration_done( $upgrade_log );
				return;
			}

			// Attempt to refresh signing secrets for all webhook configurations.
			$configurations = array(
				array( 'test_mode' => true, 'connect' => false ),
				array( 'test_mode' => true, 'connect' => true ),
				array( 'test_mode' => false, 'connect' => false ),
				array( 'test_mode' => false, 'connect' => true ),
			);

			$any_refreshed      = false;
			$any_failed         = false;
			$any_connect_failed = false;

			foreach ( $configurations as $config ) {
				$webhook_api = new Charitable_Stripe_Webhook_API( $config['test_mode'], null, $config['connect'] );

				// Skip if we already have a signing secret for this configuration.
				if ( $webhook_api->has_signing_secret() ) {
					continue;
				}

				// Skip if no API keys are configured for this mode — nothing to connect to.
				if ( ! $webhook_api->has_api_key() ) {
					continue;
				}

				$result = $webhook_api->refresh_webhook_signing_secret();

				if ( $result ) {
					$any_refreshed = true;
				} elseif ( $config['connect'] ) {
					// Connect webhook failures are non-blocking — not all sites use Stripe Connect
					// as a platform. Don't count these as migration failures.
					$any_connect_failed = true;
				} else {
					$any_failed = true;
				}
			}

			// Only set failure transient for direct webhook failures, not connect webhook failures.
			if ( $any_failed ) {
				// Set a cooldown so failed migrations don't retry on every page load.
				set_transient( 'charitable_stripe_migration_retry_cooldown', true, HOUR_IN_SECONDS );
				// Store a flag so the admin warning can reference it.
				set_transient( 'charitable_stripe_signing_secret_migration_failed', true, DAY_IN_SECONDS * 30 );
			} else {
				delete_transient( 'charitable_stripe_migration_retry_cooldown' );
				// No direct failures — clear any stale failure transient so the notice goes away.
				delete_transient( 'charitable_stripe_signing_secret_migration_failed' );
			}

			$this->mark_signing_secret_migration_done( $upgrade_log );
		}

		/**
		 * Mark the signing secret migration as completed.
		 *
		 * @since  1.8.9.8
		 *
		 * @param  array $upgrade_log The current upgrade log.
		 * @return void
		 */
		private function mark_signing_secret_migration_done( $upgrade_log ) {
			if ( ! is_array( $upgrade_log ) ) {
				$upgrade_log = array();
			}

			$upgrade_log['stripe_webhook_signing_secrets'] = array(
				'time'    => time(),
				'version' => charitable()->get_version(),
			);

			update_option( 'charitable_stripe_upgrade_log', $upgrade_log );
		}

		/**
		 * Add webhook security status fields to the Stripe settings page.
		 *
		 * Shows an inline warning if no signing secret is stored, and a button
		 * to manually refresh the signing secret.
		 *
		 * @since  1.8.9.8
		 *
		 * @param  array $settings The current Stripe settings fields.
		 * @return array
		 */
		public function add_webhook_security_settings( $settings ) {
			$test_mode = charitable_get_option( 'test_mode', false );
			$has_secret = false;

			// Check if a signing secret exists for the current mode.
			$secret_keys = $test_mode
				? array( 'test_connect_webhook_signing_secret', 'test_webhook_signing_secret' )
				: array( 'live_connect_webhook_signing_secret', 'live_webhook_signing_secret' );

			foreach ( $secret_keys as $key ) {
				if ( ! empty( charitable_get_option( array( 'gateways_stripe', $key ) ) ) ) {
					$has_secret = true;
					break;
				}
			}

			// Only show if Stripe is connected.
			$gateway = new Charitable_Gateway_Stripe_AM();
			if ( ! $gateway->maybe_stripe_connected() ) {
				return $settings;
			}

			$nonce    = wp_create_nonce( 'charitable_refresh_webhook_signing_secret' );
			$ajax_url = admin_url( 'admin-ajax.php' );

			if ( ! $has_secret ) {
				$settings['webhook_security_warning'] = array(
					'type'        => 'content',
					'title'       => __( 'Webhook Security', 'charitable' ),
					'priority'    => 50,
					'content'     => '<div class="charitable-inline-notice warning">'
						. '<p><strong>' . esc_html__( 'Webhook signature verification is not configured.', 'charitable' ) . '</strong></p>'
						. '<p>' . esc_html__( 'Your Stripe webhooks are currently verified using a fallback method. For stronger security, click the button below to enable cryptographic signature verification.', 'charitable' ) . '</p>'
						. '<p><button type="button" class="button" id="charitable-refresh-webhook-secret" data-nonce="' . esc_attr( $nonce ) . '" data-ajax-url="' . esc_url( $ajax_url ) . '">'
						. esc_html__( 'Enable Webhook Signature Verification', 'charitable' )
						. '</button> <span class="spinner" style="float:none;"></span></p>'
						. '<p class="charitable-webhook-secret-result" style="display:none;"></p>'
						. '</div>',
				);
			} else {
				$settings['webhook_security_status'] = array(
					'type'     => 'content',
					'title'    => __( 'Webhook Security', 'charitable' ),
					'priority' => 50,
					'content'  => '<div class="charitable-inline-notice info">'
						. '<p>' . esc_html__( 'Webhook signature verification is active. Incoming Stripe webhooks are cryptographically verified.', 'charitable' ) . '</p>'
						. '<p><button type="button" class="button button-link" id="charitable-refresh-webhook-secret" data-nonce="' . esc_attr( $nonce ) . '" data-ajax-url="' . esc_url( $ajax_url ) . '">'
						. esc_html__( 'Refresh Signing Secret', 'charitable' )
						. '</button> <span class="spinner" style="float:none;"></span></p>'
						. '<p class="charitable-webhook-secret-result" style="display:none;"></p>'
						. '</div>',
				);
			}

			// Add inline JS for the refresh button.
			$settings['webhook_security_script'] = array(
				'type'     => 'content',
				'title'    => '',
				'priority' => 51,
				'content'  => '<script>
					jQuery(function($) {
						$("#charitable-refresh-webhook-secret").on("click", function(e) {
							e.preventDefault();
							var $btn = $(this),
								$spinner = $btn.next(".spinner"),
								$result = $btn.closest("div").find(".charitable-webhook-secret-result");

							$btn.prop("disabled", true);
							$spinner.addClass("is-active");
							$result.hide();

							$.post($btn.data("ajax-url"), {
								action: "charitable_refresh_webhook_signing_secret",
								_wpnonce: $btn.data("nonce")
							}, function(response) {
								$spinner.removeClass("is-active");
								$btn.prop("disabled", false);
								$result.show();
								if (response.success) {
									$result.empty().append($("<span>").text(response.data.message).css("color", "green"));
									if (response.data.reload) {
										setTimeout(function() { location.reload(); }, 1500);
									}
								} else {
									$result.empty().append($("<span>").text(response.data.message).css("color", "red"));
								}
							}).fail(function() {
								$spinner.removeClass("is-active");
								$btn.prop("disabled", false);
								$result.show().empty().append($("<span>").text(' . esc_js( __( 'Request failed. Please try again.', 'charitable' ) ) . ').css("color", "red"));
							});
						});
					});
				</script>',
			);

			return $settings;
		}

		/**
		 * Display admin notice when repeated webhook signature verification failures are detected.
		 *
		 * @since  1.8.9.8
		 *
		 * @return void
		 */
		public function maybe_show_webhook_failure_notice() {
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				return;
			}

			$failure_count = (int) get_transient( 'charitable_stripe_webhook_verification_failures' );

			if ( $failure_count >= 5 ) {
				$settings_url = admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' );
				?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'Charitable: Stripe Webhook Security Alert', 'charitable' ); ?></strong>
					</p>
					<p>
						<?php
						printf(
							/* translators: %1$d: number of failures, %2$s: opening link tag, %3$s: closing link tag. */
							esc_html__( '%1$d failed webhook signature verification attempts detected in the last 24 hours. This may indicate someone is attempting to send forged webhook events. Please check your %2$sStripe settings%3$s.', 'charitable' ),
							$failure_count,
							'<a href="' . esc_url( $settings_url ) . '">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}

			// Also show migration failure notice.
			if ( get_transient( 'charitable_stripe_signing_secret_migration_failed' ) ) {
				$settings_url = admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' );
				?>
				<div class="notice notice-warning is-dismissible">
					<p>
						<strong><?php esc_html_e( 'Charitable: Stripe Webhook Security Setup', 'charitable' ); ?></strong>
					</p>
					<p>
						<?php
						printf(
							/* translators: %1$s: opening link tag, %2$s: closing link tag. */
							esc_html__( 'Charitable was unable to automatically configure webhook signature verification for Stripe. Please visit your %1$sStripe settings%2$s and click "Enable Webhook Signature Verification" to secure your webhook endpoint.', 'charitable' ),
							'<a href="' . esc_url( $settings_url ) . '">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		}

		/**
		 * AJAX handler for refreshing the webhook signing secret.
		 *
		 * @since  1.8.9.8
		 *
		 * @return void
		 */
		public function ajax_refresh_webhook_signing_secret() {
			check_ajax_referer( 'charitable_refresh_webhook_signing_secret' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
			}

			$test_mode = charitable_get_option( 'test_mode', false );
			$use_connect = charitable_using_stripe_connect();

			$webhook_api = new Charitable_Stripe_Webhook_API( $test_mode, null, $use_connect );
			$result = $webhook_api->refresh_webhook_signing_secret();

			if ( $result ) {
				// Clear the migration failure transient if it exists.
				delete_transient( 'charitable_stripe_signing_secret_migration_failed' );

				wp_send_json_success(
					array(
						'message' => __( 'Webhook signature verification has been enabled successfully. The page will reload.', 'charitable' ),
						'reload'  => true,
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => __( 'Unable to configure webhook signature verification. Please ensure your Stripe API keys are valid and try again, or disconnect and reconnect your Stripe account.', 'charitable' ),
					)
				);
			}
		}
		/**
		 * Add "Sync Pending Donations" UI block to the Stripe gateway settings page.
		 *
		 * Renders a button that checks Stripe for the status of pending donations
		 * made in the last 30 days and marks them completed when Stripe shows payment succeeded.
		 *
		 * @since  1.8.10.2
		 *
		 * @param  array $settings The current Stripe settings fields.
		 * @return array
		 */
		public function add_sync_pending_donations_settings( $settings ) {
			// Only show if Stripe is connected.
			$gateway = new Charitable_Gateway_Stripe_AM();
			if ( ! $gateway->maybe_stripe_connected() ) {
				return $settings;
			}

			$nonce    = wp_create_nonce( 'charitable_sync_pending_stripe_donations' );
			$ajax_url = admin_url( 'admin-ajax.php' );

			$settings['sync_pending_donations'] = array(
				'type'     => 'content',
				'title'    => __( 'Sync Pending Donations', 'charitable' ),
				'priority' => 55,
				'content'  => '<div class="charitable-inline-notice info">'
					. '<p>' . esc_html__( 'If donations are stuck in Pending due to a webhook issue, use this tool to check Stripe and automatically complete any payments that Stripe has already confirmed as succeeded (last 30 days).', 'charitable' ) . '</p>'
					. '<p><button type="button" class="button" id="charitable-sync-pending-donations" data-nonce="' . esc_attr( $nonce ) . '" data-ajax-url="' . esc_url( $ajax_url ) . '">' 
					. esc_html__( 'Sync Pending Donations', 'charitable' )
					. '</button> <span class="charitable-sync-spinner spinner" style="float:none;"></span></p>'
					. '<p class="charitable-sync-result" style="display:none;"></p>'
					. '</div>',
			);

			$settings['sync_pending_donations_script'] = array(
				'type'     => 'content',
				'title'    => '',
				'priority' => 56,
				'content'  => '<script>
					jQuery(function($) {
						$("#charitable-sync-pending-donations").on("click", function(e) {
							e.preventDefault();
							var $btn     = $(this),
								$spinner = $btn.siblings(".charitable-sync-spinner"),
								$result  = $btn.closest("div").find(".charitable-sync-result");

							$btn.prop("disabled", true);
							$spinner.addClass("is-active");
							$result.hide();

							$.post($btn.data("ajax-url"), {
								action: "charitable_sync_pending_stripe_donations",
								_wpnonce: $btn.data("nonce")
							}, function(response) {
								$spinner.removeClass("is-active");
								$btn.prop("disabled", false);
								$result.show();
								if (response.success) {
									$result.empty().append($("<span>").text(response.data.message).css("color", "green"));
								} else {
									$result.empty().append($("<span>").text(response.data.message).css("color", "red"));
								}
							}).fail(function() {
								$spinner.removeClass("is-active");
								$btn.prop("disabled", false);
								$result.show().empty().append($("<span>").text("Request failed. Please try again.").css("color", "red"));
							});
						});
					});
				</script>',
			);

			return $settings;
		}

		/**
		 * AJAX handler: check recent pending Stripe donations against the Stripe API
		 * and mark as completed any where the PaymentIntent status is "succeeded".
		 *
		 * Checks donations created within the last 30 days that have a stored
		 * PaymentIntent ID. Respects test/live mode and Stripe Connect accounts.
		 *
		 * @since  1.8.10.2
		 *
		 * @return void
		 */
		public function ajax_sync_pending_stripe_donations() {
			check_ajax_referer( 'charitable_sync_pending_stripe_donations' );

			if ( charitable_is_debug() ) {
				error_log( '[charitable_sync_pending_stripe_donations] AJAX handler fired.' ); // phpcs:ignore
			}

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Permission check failed.' ); // phpcs:ignore
				}
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
				return;
			}

			// Set up the Stripe API with the current mode's secret key.
			$gateway = new Charitable_Gateway_Stripe_AM();
			if ( ! $gateway->setup_api() ) {
				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] setup_api() failed — no valid API key.' ); // phpcs:ignore
				}
				wp_send_json_error( array( 'message' => __( 'Unable to connect to Stripe. Please check that your API keys are configured correctly.', 'charitable' ) ) );
				return;
			}

			/**
			 * Filter the number of days back to look for pending Stripe donations.
			 *
			 * @since 1.8.10.2
			 *
			 * @param int $days Number of days. Default 30.
			 */
			$days = (int) apply_filters( 'charitable_sync_pending_stripe_donations_days', 30 );
			$days = max( 1, $days );

			if ( charitable_is_debug() ) {
				error_log( '[charitable_sync_pending_stripe_donations] Stripe API initialized. Querying pending donations (last ' . $days . ' days).' ); // phpcs:ignore
			}

			$limit = (int) apply_filters( 'charitable_sync_pending_stripe_donations_limit', 50 );
			$limit = max( 1, $limit );

			// Query pending donations from the last N days that have a Stripe PaymentIntent stored.
			$query = new WP_Query(
				array(
					'post_type'      => Charitable::DONATION_POST_TYPE,
					'post_status'    => 'charitable-pending',
					'posts_per_page' => $limit,
					'fields'         => 'ids',
					'date_query'     => array(
						array(
							'after'     => $days . ' days ago',
							'inclusive' => true,
						),
					),
					'meta_query'     => array(
						array(
							'key'     => '_stripe_payment_intent',
							'value'   => '',
							'compare' => '!=',
						),
					),
				)
			);

			$donation_ids = $query->posts;

			if ( charitable_is_debug() ) {
				error_log( '[charitable_sync_pending_stripe_donations] Query found ' . count( $donation_ids ) . ' pending donation(s): ' . implode( ', ', $donation_ids ) ); // phpcs:ignore
			}

			if ( empty( $donation_ids ) ) {
				wp_send_json_success( array(
					'message' => sprintf(
						/* translators: %d: number of days */
						__( 'No pending Stripe donations found in the last %d days.', 'charitable' ),
						$days
					),
				) );
				return;
			}

			$updated = 0;
			$skipped = 0;
			$errors  = 0;

			foreach ( $donation_ids as $donation_id ) {
				$intent_id = get_post_meta( $donation_id, '_stripe_payment_intent', true );

				if ( empty( $intent_id ) ) {
					if ( charitable_is_debug() ) {
						error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — no PaymentIntent meta, skipping.' ); // phpcs:ignore
					}
					$skipped++;
					continue;
				}

				// Build options array — pass Stripe Connect account if one is stored on the donation.
				$options    = array();
				$account_id = get_post_meta( $donation_id, '_stripe_account_id', true );
				if ( ! empty( $account_id ) ) {
					$options['stripe_account'] = $account_id;
				}

				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — retrieving PaymentIntent ' . $intent_id . ( ! empty( $account_id ) ? ' (Connect account: ' . $account_id . ')' : '' ) . '.' ); // phpcs:ignore
				}

				try {
					$intent = \Stripe\PaymentIntent::retrieve( $intent_id, $options );

					if ( charitable_is_debug() ) {
						error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — PaymentIntent status: ' . $intent->status . '.' ); // phpcs:ignore
					}

					if ( 'succeeded' === $intent->status ) {
						$donation = new Charitable_Donation( $donation_id );
						$donation->update_status( 'charitable-completed' );
						$updated++;
						if ( charitable_is_debug() ) {
							error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — marked completed.' ); // phpcs:ignore
						}
					} else {
						$skipped++;
						if ( charitable_is_debug() ) {
							error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — skipped (Stripe status: ' . $intent->status . ').' ); // phpcs:ignore
						}
					}
				} catch ( \Exception $e ) {
					$errors++;
					if ( charitable_is_debug() ) {
						error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — Stripe API error: ' . $e->getMessage() ); // phpcs:ignore
					}
				}
			}

			if ( charitable_is_debug() ) {
				error_log( '[charitable_sync_pending_stripe_donations] Done. Updated: ' . $updated . ', Skipped: ' . $skipped . ', Errors: ' . $errors . '.' ); // phpcs:ignore
			}

			$message = sprintf(
				/* translators: 1: updated count, 2: skipped count, 3: error count */
				__( '%1$d donation(s) marked completed. %2$d still pending in Stripe. %3$d error(s).', 'charitable' ),
				$updated,
				$skipped,
				$errors
			);

			wp_send_json_success( array( 'message' => $message ) );
		}
	}

endif;
