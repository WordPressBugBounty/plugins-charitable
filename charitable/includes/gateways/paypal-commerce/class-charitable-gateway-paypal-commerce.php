<?php
/**
 * PayPal Commerce Gateway
 *
 * Modern PayPal Complete Payments (PPCP) integration for Charitable.
 *
 * @package   Charitable/Gateways/PayPal Commerce
 * @author    WP Charitable
 * @copyright Copyright (c) 2024-2025, Studio 164a
 * @license   GPL-2.0+
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateway_Paypal_Commerce' ) ) :

	/**
	 * PayPal Commerce Gateway Class
	 *
	 * @since 1.8.11
	 */
	class Charitable_Gateway_Paypal_Commerce extends Charitable_Gateway {

		/** Gateway ID */
		const ID = 'paypal_commerce';

		/**
		 * PayPal API base URLs.
		 */
		const SANDBOX_API_URL = 'https://api-m.sandbox.paypal.com';
		const LIVE_API_URL    = 'https://api-m.paypal.com';

		/**
		 * PayPal JS SDK URLs.
		 */
		const SANDBOX_SDK_URL = 'https://www.sandbox.paypal.com/sdk/js';
		const LIVE_SDK_URL    = 'https://www.paypal.com/sdk/js';

		/**
		 * Cached access token.
		 *
		 * @var string|null
		 */
		private static $access_token = null;

		/**
		 * Instantiate the gateway class, defining its key values.
		 *
		 * @since 1.8.11
		 */
		public function __construct() {
			/**
			 * Gateway name/label.
			 */
			$this->name = __( 'PayPal Commerce', 'charitable' );

			/**
			 * Default settings.
			 */
			$this->defaults = array(
				'label' => __( 'PayPal', 'charitable' ),
			);

			/**
			 * Supported features.
			 */
			$this->supports = array(
				'refunds',
				'1.3.0', // Minimum Charitable version
			);
		}

		/**
		 * Return the gateway ID.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public static function get_gateway_id() {
			return self::ID;
		}

		/**
		 * Return the gateway logo HTML.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_logo() {
			return '<img src="' . esc_url( charitable()->get_path( 'directory', false ) . 'assets/images/gateways/paypal-logo.png' ) . '" alt="PayPal" width="58" height="19" style="display:block;max-width:58px;height:auto" />';
		}


/**
		 * Return the gateway settings.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $settings Default gateway settings.
		 * @return array
		 */
		public function gateway_settings( $settings ) {
			$paypal_settings = array(
				'section_paypal_commerce' => array(
					'title'    => __( 'PayPal Commerce Settings', 'charitable' ),
					'type'     => 'heading',
					'priority' => 2,
				),
				'section_connection'      => array(
					'title'    => __( 'Account Connection', 'charitable' ),
					'type'     => 'heading',
					'priority' => 6,
				),
				'connection_status'       => array(
					'type'     => 'content',
					'title'    => __( 'Connection Status', 'charitable' ),
					'content'  => $this->get_connection_status_html(),
					'priority' => 7,
				),
				'section_webhooks'        => array(
					'title'    => __( 'Webhooks', 'charitable' ),
					'type'     => 'heading',
					'priority' => 28,
				),
				'webhook_url_display'     => array(
					'type'     => 'content',
					'title'    => __( 'Webhook URL', 'charitable' ),
					'content'  => $this->get_webhook_url_html(),
					'priority' => 29,
				),
				'section_options'         => array(
					'title'    => __( 'Payment Options', 'charitable' ),
					'type'     => 'heading',
					'priority' => 30,
				),
				'button_style'            => array(
					'type'     => 'select',
					'title'    => __( 'Button Color', 'charitable' ),
					'options'  => array(
						'gold'   => __( 'Gold (Recommended)', 'charitable' ),
						'blue'   => __( 'Blue', 'charitable' ),
						'silver' => __( 'Silver', 'charitable' ),
						'white'  => __( 'White', 'charitable' ),
						'black'  => __( 'Black', 'charitable' ),
					),
					'default'  => 'gold',
					'priority' => 32,
				),
				'button_shape'            => array(
					'type'     => 'select',
					'title'    => __( 'Button Shape', 'charitable' ),
					'options'  => array(
						'rect' => __( 'Rectangle', 'charitable' ),
						'pill' => __( 'Pill', 'charitable' ),
					),
					'default'  => 'rect',
					'priority' => 34,
				),
				'enable_venmo'            => array(
					'type'     => 'checkbox',
					'title'    => __( 'Enable Venmo', 'charitable' ),
					'help'     => __( 'Allow donors to pay with Venmo (US only).', 'charitable' ),
					'default'  => false,
					'priority' => 36,
				),
				'enable_paylater'         => array(
					'type'     => 'checkbox',
					'title'    => __( 'Enable Pay Later', 'charitable' ),
					'help'     => __( 'Allow donors to use PayPal Pay Later financing options.', 'charitable' ),
					'default'  => false,
					'priority' => 38,
				),
				'section_advanced_payments' => array(
					'title'    => __( 'Advanced Payment Methods', 'charitable' ),
					'type'     => 'heading',
					'priority' => 40,
					'help'     => __( 'These options require PPCP approval from PayPal. Check your connection status above.', 'charitable' ),
				),
				'enable_card_fields'      => array(
					'type'     => 'checkbox',
					'title'    => __( 'Enable Card Fields (ACDC)', 'charitable' ),
					'help'     => __( 'Show credit/debit card fields directly on your site. Requires Advanced Card Processing approval.', 'charitable' ),
					'default'  => true,
					'priority' => 42,
				),
				'enable_apple_pay'        => array(
					'type'     => 'checkbox',
					'title'    => __( 'Enable Apple Pay', 'charitable' ),
					'help'     => __( 'Only shown to donors using Safari on Apple devices with a card added to Apple Pay. Not visible in Chrome or Firefox. Requires Apple Pay approval from PayPal.', 'charitable' ),
					'default'  => false,
					'priority' => 44,
				),
				'enable_google_pay'       => array(
					'type'     => 'checkbox',
					'title'    => __( 'Enable Google Pay', 'charitable' ),
					'help'     => __( 'Only shown to donors using Chrome or Edge with Google Pay enabled. Not visible in Safari. Requires Google Pay approval from PayPal.', 'charitable' ),
					'default'  => false,
					'priority' => 46,
				),
				'enable_fastlane'         => array(
					'type'     => 'checkbox',
					'title'    => __( 'Enable Fastlane', 'charitable' ),
					'help'     => __( 'Enable accelerated checkout for guest users. Requires ADVANCED_VAULTING and vaulting capabilities.', 'charitable' ),
					'default'  => false,
					'priority' => 48,
				),
				'fastlane_behavior'       => array(
					'type'     => 'select',
					'title'    => __( 'Fastlane Behavior', 'charitable' ),
					'help'     => __( 'Choose when to show Fastlane checkout option.', 'charitable' ),
					'priority' => 50,
					'default'  => 'guest_only',
					'options'  => array(
						'guest_only' => __( 'Guest users only (recommended)', 'charitable' ),
						'all_users'  => __( 'All users', 'charitable' ),
					),
				),
			);

			return array_merge( $settings, $paypal_settings );
		}

		/**
		 * Get HTML for the connection status display in settings.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_connection_status_html() {
			$mode            = $this->is_sandbox() ? 'sandbox' : 'live';
			$is_connected    = $this->is_seller_connected();
			$seller_merchant_id = $this->get_seller_merchant_id();

			$html = '<div class="charitable-paypal-commerce-connection">';

			if ( $is_connected ) {
				// Read via the cached wrapper so the connection panel shares the
				// 1-hour transient with every other capability check. The wrapper
				// returns false on upstream error; fall through to the placeholder.
				$readiness = $this->get_seller_readiness();
				if ( false === $readiness ) {
					$readiness = array(
						'is_ready'                   => false,
						'payments_receivable'        => false,
						'primary_email_confirmed'    => false,
						'oauth_integrations'         => false,
						'granted_scopes'             => array(),
						'acdc_ready'                 => false,
						'acdc_vetting_status'        => '',
						'apple_pay_ready'            => false,
						'google_pay_ready'           => false,
						'vaulting_ready'             => false,
						'vaulting_capability_status' => '',
						'ppcp_custom_subscribed'     => false,
						'payment_methods_subscribed' => false,
						'issues'                     => array( __( 'Could not retrieve account status from PayPal. Click the "Refresh Status" button below to retry.', 'charitable' ) ),
					);
				}

				$html .= '<div class="charitable-paypal-status charitable-paypal-status--connected">';
				$html .= '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ';
				$html .= sprintf(
					/* translators: %s: mode (sandbox/live) */
					__( 'Connected to PayPal (%s)', 'charitable' ),
					$mode
				);
				$html .= '</div>';

				$html .= '<p class="description">';
				$html .= sprintf(
					/* translators: %s: merchant ID */
					__( 'Merchant ID: %s', 'charitable' ),
					'<code>' . esc_html( $seller_merchant_id ) . '</code>'
				);
				$html .= '</p>';

				// Show readiness status.
				if ( $readiness['is_ready'] ) {
					$html .= '<p style="color: #46b450;"><span class="dashicons dashicons-yes"></span> ' . __( 'Ready to accept payments', 'charitable' ) . '</p>';
				} else {
					$html .= '<div class="charitable-paypal-issues" style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0;">';
					$html .= '<strong>' . __( 'Action Required:', 'charitable' ) . '</strong><ul style="margin: 5px 0 0 20px;">';
					foreach ( $readiness['issues'] as $issue ) {
						$html .= '<li>' . esc_html( $issue ) . '</li>';
					}
					$html .= '</ul></div>';
				}

				// Show advanced capabilities.
				$html .= '<p class="description"><strong>' . __( 'Capabilities:', 'charitable' ) . '</strong></p>';
				$html .= '<ul class="charitable-paypal-capabilities" style="margin-left: 20px;">';
				$html .= '<li>' . ( $readiness['payments_receivable'] ? '<span style="color:#46b450;">&#10003;</span>' : '<span style="color:#dc3232;">&#10007;</span>' ) . ' ' . __( 'Payments Receivable', 'charitable' ) . '</li>';
				$html .= '<li>' . ( $readiness['primary_email_confirmed'] ? '<span style="color:#46b450;">&#10003;</span>' : '<span style="color:#dc3232;">&#10007;</span>' ) . ' ' . __( 'Email Confirmed', 'charitable' ) . '</li>';

				// ACDC status with vetting details.
				$acdc_status_text = __( 'Advanced Card Processing', 'charitable' );
				if ( ! empty( $readiness['acdc_vetting_status'] ) ) {
					$vetting_status = $readiness['acdc_vetting_status'];
					if ( 'SUBSCRIBED' === $vetting_status && $readiness['acdc_ready'] ) {
						$html .= '<li><span style="color:#46b450;">&#10003;</span> ' . $acdc_status_text . '</li>';
					} elseif ( 'DENIED' === $vetting_status ) {
						$html .= '<li><span style="color:#dc3232;">&#10007;</span> ' . $acdc_status_text . ' <span style="color:#999;">(' . __( 'Denied', 'charitable' ) . ')</span></li>';
					} elseif ( in_array( $vetting_status, array( 'IN_REVIEW', 'PENDING' ), true ) ) {
						$html .= '<li><span style="color:#f0ad4e;">&#9679;</span> ' . $acdc_status_text . ' <span style="color:#999;">(' . __( 'Pending Review', 'charitable' ) . ')</span></li>';
					} elseif ( 'NEEDS_MORE_DATA' === $vetting_status ) {
						$html .= '<li><span style="color:#e65c00;">&#9679;</span> ' . $acdc_status_text . ' <span style="color:#999;">(' . __( 'More Information Required', 'charitable' ) . ')</span></li>';
					} else {
						$html .= '<li><span style="color:#999;">&#10007;</span> ' . $acdc_status_text . ' <span style="color:#999;">(' . esc_html( $vetting_status ) . ')</span></li>';
					}
				} else {
					$html .= '<li><span style="color:#999;">&#10007;</span> ' . $acdc_status_text . ' <span style="color:#999;">(' . __( 'Not Requested', 'charitable' ) . ')</span></li>';
				}

				$html .= '<li>' . ( ! empty( $readiness['apple_pay_ready'] ) ? '<span style="color:#46b450;">&#10003;</span>' : '<span style="color:#999;">&#10007;</span>' ) . ' ' . __( 'Apple Pay', 'charitable' ) . '</li>';
				$html .= '<li>' . ( ! empty( $readiness['google_pay_ready'] ) ? '<span style="color:#46b450;">&#10003;</span>' : '<span style="color:#999;">&#10007;</span>' ) . ' ' . __( 'Google Pay', 'charitable' ) . '</li>';

				// Vaulting capability with granular status.
				$vaulting_label   = __( 'Payment Method Vaulting', 'charitable' );
				$vault_cap_status = $readiness['vaulting_capability_status'];
				if ( $readiness['vaulting_ready'] ) {
					$html .= '<li><span style="color:#46b450;">&#10003;</span> ' . $vaulting_label . '</li>';
				} elseif ( in_array( $vault_cap_status, array( 'PENDING_REVIEW', 'PENDING' ), true ) ) {
					$html .= '<li><span style="color:#f0ad4e;">&#9679;</span> ' . $vaulting_label . ' <span style="color:#999;">(' . __( 'Pending Review', 'charitable' ) . ')</span></li>';
				} elseif ( 'NEEDS_MORE_DATA' === $vault_cap_status ) {
					$html .= '<li><span style="color:#e65c00;">&#9679;</span> ' . $vaulting_label . ' <span style="color:#999;">(' . __( 'More Information Required', 'charitable' ) . ')</span></li>';
				} elseif ( 'DENIED' === $vault_cap_status ) {
					$html .= '<li><span style="color:#dc3232;">&#10007;</span> ' . $vaulting_label . ' <span style="color:#999;">(' . __( 'Denied', 'charitable' ) . ')</span></li>';
				} elseif ( 'SUSPENDED' === $vault_cap_status ) {
					$html .= '<li><span style="color:#dc3232;">&#10007;</span> ' . $vaulting_label . ' <span style="color:#999;">(' . __( 'Suspended', 'charitable' ) . ')</span></li>';
				} else {
					$html .= '<li><span style="color:#999;">&#10007;</span> ' . $vaulting_label . ' <span style="color:#999;">(' . __( 'Not Available', 'charitable' ) . ')</span></li>';
				}

				$html .= '</ul>';

				// Show note if ACDC is pending review or needs more data.
				if ( ! empty( $readiness['acdc_vetting_status'] ) ) {
					$acdc_vs = $readiness['acdc_vetting_status'];
					if ( in_array( $acdc_vs, array( 'IN_REVIEW', 'PENDING' ), true ) ) {
						$html .= '<div class="charitable-paypal-notice" style="background: #e7f3ff; padding: 10px; border-left: 4px solid #0073aa; margin: 10px 0;">';
						$html .= '<strong>' . __( 'Note:', 'charitable' ) . '</strong> ';
						$html .= __( 'Your account is being reviewed for Advanced Card Processing. PayPal buttons are available now. Card fields will be enabled once approved.', 'charitable' );
						$html .= '</div>';
					} elseif ( 'NEEDS_MORE_DATA' === $acdc_vs ) {
						$html .= '<div class="charitable-paypal-notice" style="background: #fff3cd; padding: 10px; border-left: 4px solid #e65c00; margin: 10px 0;">';
						$html .= '<strong>' . __( 'Action Required:', 'charitable' ) . '</strong> ';
						$html .= __( 'PayPal requires additional information to complete Advanced Card Processing vetting. Please log in to your PayPal account to provide the required details.', 'charitable' );
						$html .= '</div>';
					}
				}

				// Show note if vaulting is pending or needs more data.
				if ( ! empty( $readiness['vaulting_capability_status'] ) && ! $readiness['vaulting_ready'] ) {
					$vault_vs = $readiness['vaulting_capability_status'];
					if ( in_array( $vault_vs, array( 'PENDING_REVIEW', 'PENDING' ), true ) ) {
						$html .= '<div class="charitable-paypal-notice" style="background: #e7f3ff; padding: 10px; border-left: 4px solid #0073aa; margin: 10px 0;">';
						$html .= '<strong>' . __( 'Note:', 'charitable' ) . '</strong> ';
						$html .= __( 'Your vaulting application is being reviewed by PayPal. Saved payment methods will be enabled once approved.', 'charitable' );
						$html .= '</div>';
					} elseif ( 'NEEDS_MORE_DATA' === $vault_vs ) {
						$html .= '<div class="charitable-paypal-notice" style="background: #fff3cd; padding: 10px; border-left: 4px solid #e65c00; margin: 10px 0;">';
						$html .= '<strong>' . __( 'Action Required:', 'charitable' ) . '</strong> ';
						$html .= __( 'PayPal requires additional information to approve your vaulting application. Please log in to your PayPal account to provide the required details.', 'charitable' );
						$html .= '</div>';
					} elseif ( 'DENIED' === $vault_vs ) {
						$html .= '<div class="charitable-paypal-notice" style="background: #fce8e8; padding: 10px; border-left: 4px solid #dc3232; margin: 10px 0;">';
						$html .= '<strong>' . __( 'Note:', 'charitable' ) . '</strong> ';
						$html .= __( 'Your vaulting application was denied by PayPal. Saved payment methods are not available for your account. Please contact PayPal support for more information.', 'charitable' );
						$html .= '</div>';
					}
				}

				// Show granted OAuth scopes (IWT requirement: display scopes granted to partner).
				if ( ! empty( $readiness['granted_scopes'] ) ) {
					// Map raw scope URIs to human-readable labels.
					$scope_labels = array(
						'https://uri.paypal.com/services/payments/payment'                 => __( 'Payments', 'charitable' ),
						'https://uri.paypal.com/services/payments/refund'                  => __( 'Refunds', 'charitable' ),
						'https://uri.paypal.com/services/payments/realtimepayment'         => __( 'Real-time Payments', 'charitable' ),
						'https://uri.paypal.com/services/payments/payment/authcapture'     => __( 'Auth & Capture', 'charitable' ),
						'https://uri.paypal.com/services/billing-agreements'               => __( 'Billing Agreements', 'charitable' ),
						'https://uri.paypal.com/services/vault/payment-tokens/readwrite'   => __( 'Vault (read/write)', 'charitable' ),
						'https://uri.paypal.com/services/vault/payment-tokens/read'        => __( 'Vault (read)', 'charitable' ),
						'https://uri.paypal.com/services/reporting/search/read'            => __( 'Reporting', 'charitable' ),
						'https://uri.paypal.com/services/customer/merchant-integrations/all' => __( 'Merchant Integrations', 'charitable' ),
						'https://uri.paypal.com/payments/payouts'                          => __( 'Payouts', 'charitable' ),
						'openid'                                                            => __( 'OpenID (profile)', 'charitable' ),
					);

					$html .= '<p class="description" style="margin-top:12px;"><strong>' . __( 'Granted Scopes:', 'charitable' ) . '</strong></p>';
					$html .= '<ul class="charitable-paypal-scopes" style="margin-left:20px; font-size:11px;">';
					foreach ( $readiness['granted_scopes'] as $scope ) {
						$label = isset( $scope_labels[ $scope ] ) ? $scope_labels[ $scope ] . ' &mdash; ' : '';
						$html .= '<li>' . $label . '<code style="font-size:10px;">' . esc_html( $scope ) . '</code></li>';
					}
					$html .= '</ul>';
				}

				// Disconnect button.
				$disconnect_url = wp_nonce_url(
					add_query_arg(
						array(
							'charitable_paypal_commerce_disconnect' => '1',
							'mode' => $mode,
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce' )
					),
					'charitable_paypal_disconnect'
				);
				$html .= '<p style="margin-top: 15px;">';
				$html .= '<a href="' . esc_url( $disconnect_url ) . '" class="button" onclick="return confirm(\'' . esc_js( __( 'Are you sure you want to disconnect this PayPal account?', 'charitable' ) ) . '\');">';
				$html .= __( 'Disconnect Account', 'charitable' );
				$html .= '</a>';
				$html .= ' <a href="' . esc_url( admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce' ) ) . '" class="button">' . __( 'Refresh Status', 'charitable' ) . '</a>';
				$html .= '</p>';

			} else {
				// Not connected - show connect button.
				$html .= '<div class="charitable-paypal-status charitable-paypal-status--disconnected">';
				$html .= '<span class="dashicons dashicons-warning" style="color: #dc3232;"></span> ';
				$html .= sprintf(
					/* translators: %s: mode (sandbox/live) */
					__( 'Not connected to PayPal (%s)', 'charitable' ),
					$mode
				);
				$html .= '</div>';

				$html .= '<p class="description">' . __( 'Connect your PayPal account to start accepting donations.', 'charitable' ) . '</p>';

				// Platform credentials are hardcoded — connect button is always available.
				if ( true ) {
					// Show connect button.
					$connect_url = wp_nonce_url(
						add_query_arg(
							array(
								'charitable_paypal_commerce_connect' => '1',
								'mode' => $mode,
							),
							admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce' )
						),
						'charitable_paypal_connect'
					);

					$html .= '<p style="margin-top: 15px;">';
					$html .= '<a href="' . esc_url( $connect_url ) . '" class="button button-primary">';
					$html .= '<span class="dashicons dashicons-admin-links" style="margin-top: 4px;"></span> ';
					$html .= __( 'Connect with PayPal', 'charitable' );
					$html .= '</a>';
					$html .= '</p>';
				}
			}

			$html .= '</div>';

			return $html;
		}

		/**
		 * Check if the gateway is in sandbox mode.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_sandbox() {
			return (bool) charitable_get_option( 'test_mode', false );
		}

		/**
		 * Check if middleware mode is enabled.
		 *
		 * When enabled, all API calls are routed through the middleware server
		 * instead of directly to PayPal.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_middleware_mode() {
			return true;
		}

		/**
		 * Get the middleware server base URL for the current mode.
		 *
		 * @return string
		 */
		public function get_middleware_url() {
			if ( $this->is_sandbox() ) {
				return defined( 'CHARITABLE_PAYPAL_MIDDLEWARE_SANDBOX_URL' )
					? CHARITABLE_PAYPAL_MIDDLEWARE_SANDBOX_URL
					: 'https://paypal-sandbox.wpcharitable.com';
			}
			return defined( 'CHARITABLE_PAYPAL_MIDDLEWARE_LIVE_URL' )
				? CHARITABLE_PAYPAL_MIDDLEWARE_LIVE_URL
				: 'https://paypal.wpcharitable.com';
		}

		/**
		 * Get the middleware shared secret for the current mode.
		 *
		 * The secret is generated per-site during onboarding and stored in WP options.
		 * It is never hardcoded — if no secret is stored, an empty string is returned
		 * and the middleware will reject the request.
		 *
		 * @return string
		 */
		public function get_middleware_secret() {
			$key = $this->is_sandbox() ? 'sandbox_middleware_secret' : 'live_middleware_secret';
			return (string) $this->get_value( $key );
		}

		/**
		 * Return the middleware shared secret, generating + persisting one if absent.
		 *
		 * Why: the secret is what the plugin sends to the middleware as the
		 * partner-referral tracking_id during "Connect with PayPal". The middleware
		 * then stores it on `paypal_connected_customers.secret` and uses it later
		 * to verify webhook signatures and route subsequent calls back to this
		 * site. Without a secret on first connect, the middleware rejects the
		 * onboarding request with "The secret field is required."
		 *
		 * Read-only callers (webhook signature verification, etc.) should keep
		 * using `get_middleware_secret()` — they must not auto-create a secret,
		 * since that would mask a misconfiguration.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_or_create_middleware_secret() {
			$secret = $this->get_middleware_secret();
			if ( '' !== $secret ) {
				return $secret;
			}

			$new_secret = wp_generate_password( 32, false, false );

			$key      = $this->is_sandbox() ? 'sandbox_middleware_secret' : 'live_middleware_secret';
			$settings = get_option( 'charitable_settings', array() );
			if ( ! isset( $settings['gateways_paypal_commerce'] ) || ! is_array( $settings['gateways_paypal_commerce'] ) ) {
				$settings['gateways_paypal_commerce'] = array();
			}
			$settings['gateways_paypal_commerce'][ $key ] = $new_secret;
			update_option( 'charitable_settings', $settings );

			return $new_secret;
		}

		/**
		 * Get the PayPal API base URL.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_api_url() {
			return $this->is_sandbox() ? self::SANDBOX_API_URL : self::LIVE_API_URL;
		}

		/**
		 * Get the PayPal JS SDK URL.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_sdk_base_url() {
			return $this->is_sandbox() ? self::SANDBOX_SDK_URL : self::LIVE_SDK_URL;
		}

		/**
		 * Fetch and cache platform credentials from the middleware server.
		 *
		 * Returns an array with 'client_id' and 'partner_merchant_id' keys.
		 * Cached in a transient for 24 hours to avoid an API call on every page load.
		 * Returns an empty array on failure.
		 *
		 * @since  1.8.11
		 *
		 * @return array
		 */
		private function get_middleware_platform_credentials() {
			$mode          = $this->is_sandbox() ? 'sandbox' : 'live';
			$transient_key = 'charitable_paypal_platform_credentials_' . $mode;
			$cached        = get_transient( $transient_key );

			// Positive cache: credentials fetched successfully.
			if ( is_array( $cached ) && ! empty( $cached['client_id'] ) ) {
				return $cached;
			}

			// Negative cache: a recent fetch failed — don't hammer the middleware on every page load.
			if ( false !== $cached && empty( $cached ) ) {
				return array();
			}

			// Not connected yet — skip the API call entirely.
			if ( ! $this->is_seller_connected() ) {
				return array();
			}

			$result = Charitable_PayPal_Middleware_Client::get_instance()->get_credentials();
			if ( is_wp_error( $result ) || empty( $result['client_id'] ) ) {
				// Cache the failure for 5 minutes to avoid blocking outbound requests on every page load.
				set_transient( $transient_key, array(), 5 * MINUTE_IN_SECONDS );
				return array();
			}
			$credentials = array(
				'client_id'           => (string) $result['client_id'],
				'partner_merchant_id' => (string) ( $result['partner_merchant_id'] ?? '' ),
			);
			set_transient( $transient_key, $credentials, DAY_IN_SECONDS );
			return $credentials;
		}

		/**
		 * Get the platform Client ID for the current mode.
		 *
		 * In middleware mode the credential is fetched from the middleware server
		 * (never hardcoded). In direct mode it may be defined via a constant.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_client_id() {
			if ( $this->is_middleware_mode() ) {
				$creds = $this->get_middleware_platform_credentials();
				return $creds['client_id'] ?? '';
			}
			if ( $this->is_sandbox() ) {
				return defined( 'CHARITABLE_PAYPAL_SANDBOX_CLIENT_ID' ) ? CHARITABLE_PAYPAL_SANDBOX_CLIENT_ID : '';
			}
			return defined( 'CHARITABLE_PAYPAL_LIVE_CLIENT_ID' ) ? CHARITABLE_PAYPAL_LIVE_CLIENT_ID : '';
		}

		/**
		 * Get the platform Client Secret for the current mode.
		 *
		 * Not used in middleware mode — all API calls are proxied through the
		 * middleware server which holds the secret server-side.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_client_secret() {
			if ( $this->is_middleware_mode() ) {
				return '';
			}
			if ( $this->is_sandbox() ) {
				return defined( 'CHARITABLE_PAYPAL_SANDBOX_CLIENT_SECRET' ) ? CHARITABLE_PAYPAL_SANDBOX_CLIENT_SECRET : '';
			}
			return defined( 'CHARITABLE_PAYPAL_LIVE_CLIENT_SECRET' ) ? CHARITABLE_PAYPAL_LIVE_CLIENT_SECRET : '';
		}

		/**
		 * Get the platform partner Merchant ID for the current mode.
		 *
		 * In middleware mode fetched from the middleware server. In direct mode
		 * it may be defined via a constant.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_merchant_id() {
			if ( $this->is_middleware_mode() ) {
				$creds = $this->get_middleware_platform_credentials();
				return $creds['partner_merchant_id'] ?? '';
			}
			if ( $this->is_sandbox() ) {
				return defined( 'CHARITABLE_PAYPAL_SANDBOX_PARTNER_MERCHANT_ID' ) ? CHARITABLE_PAYPAL_SANDBOX_PARTNER_MERCHANT_ID : '';
			}
			return defined( 'CHARITABLE_PAYPAL_LIVE_PARTNER_MERCHANT_ID' ) ? CHARITABLE_PAYPAL_LIVE_PARTNER_MERCHANT_ID : '';
		}

		/**
		 * Get an OAuth access token from PayPal.
		 *
		 * @since  1.8.11
		 *
		 * @param  bool $force_refresh Force a new token.
		 * @return string|WP_Error
		 */
		public function get_access_token( $force_refresh = false ) {
			// Middleware mode: delegate to middleware client.
			if ( $this->is_middleware_mode() ) {
				return Charitable_PayPal_Middleware_Client::get_instance()->get_access_token( $force_refresh );
			}

			// Check cached token.
			if ( ! $force_refresh && self::$access_token ) {
				return self::$access_token;
			}

			// Check transient.
			$transient_key = 'charitable_paypal_commerce_token_' . ( $this->is_sandbox() ? 'sandbox' : 'live' );
			if ( ! $force_refresh ) {
				$cached = get_transient( $transient_key );
				if ( $cached ) {
					self::$access_token = $cached;
					return $cached;
				}
			}

			// Request new token.
			$response = wp_remote_post(
				$this->get_api_url() . '/v1/oauth2/token',
				array(
					'headers' => array(
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/x-www-form-urlencoded',
						'Authorization' => 'Basic ' . base64_encode( $this->get_client_id() . ':' . $this->get_client_secret() ),
					),
					'body'    => 'grant_type=client_credentials',
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $body['access_token'] ) ) {
				return new WP_Error(
					'paypal_commerce_auth_error',
					__( 'Failed to get PayPal access token.', 'charitable' )
				);
			}

			// Cache the token.
			$expires_in         = isset( $body['expires_in'] ) ? (int) $body['expires_in'] - 60 : 3600;
			self::$access_token = $body['access_token'];
			set_transient( $transient_key, $body['access_token'], $expires_in );

			return $body['access_token'];
		}

		/**
		 * Generate a client token for Fastlane initialization.
		 *
		 * Client tokens are different from access tokens - they're used for client-side
		 * SDK initialization and have shorter lifespans (1 hour).
		 *
		 * @since  1.8.11
		 *
		 * @param  bool $force_refresh Force generation of a new token.
		 * @return string|WP_Error Client token or error.
		 */
		public function generate_client_token( $force_refresh = false ) {
			// Middleware mode: use sdk-client-token endpoint which includes merchant
			// assertion and domain binding required for Fastlane initialization.
			if ( $this->is_middleware_mode() ) {
				$result = Charitable_PayPal_Middleware_Client::get_instance()->get_sdk_client_token();
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				return isset( $result['sdk_client_token'] ) ? $result['sdk_client_token'] : new WP_Error( 'no_sdk_client_token', 'Missing SDK client token.' );
			}

			try {
				$mode         = $this->is_sandbox() ? 'sandbox' : 'live';
				$transient_key = 'charitable_paypal_client_token_' . $mode;

				// Check cached token.
				if ( ! $force_refresh ) {
					$cached_token = get_transient( $transient_key );
					if ( $cached_token ) {
						return $cached_token;
					}
				}

				$access_token = $this->get_access_token();

				if ( is_wp_error( $access_token ) ) {
					Charitable_PayPal_Logger::error(
						'paypal.oauth.client_token_failed',
						'Client token generation failed (no access token)',
						array(
							'error' => $access_token->get_error_message(),
						)
					);
					return $access_token;
				}

				// Validate access token before using it.
				if ( empty( $access_token ) || ! is_string( $access_token ) ) {
					return new WP_Error(
						'paypal_commerce_client_token_error',
						__( 'Invalid access token for client token generation.', 'charitable' )
					);
				}

				// Generate client token using PayPal API.
				$response = wp_remote_post(
					$this->get_api_url() . '/v1/identity/generate-token',
					array(
						'headers' => array(
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json',
							'Authorization' => 'Bearer ' . $access_token,
						),
						'body'    => wp_json_encode( array() ),
						'timeout' => 30,
					)
				);

				if ( is_wp_error( $response ) ) {
					Charitable_PayPal_Logger::error(
						'paypal.oauth.client_token_failed',
						'Client token generation HTTP error',
						array(
							'error' => $response->get_error_message(),
						)
					);
					return $response;
				}

				$body      = json_decode( wp_remote_retrieve_body( $response ), true );
				$http_code = wp_remote_retrieve_response_code( $response );

				if ( $http_code >= 400 ) {
					Charitable_PayPal_Logger::error(
						'paypal.oauth.client_token_failed',
						'Client token generation failed',
						array(
							'http_code'       => $http_code,
							'paypal_error'    => $body['name'] ?? null,
							'paypal_debug_id' => wp_remote_retrieve_header( $response, 'paypal-debug-id' ),
						)
					);

					// Provide specific error messages for common error codes.
					$error_message = __( 'Failed to generate PayPal client token.', 'charitable' );
					if ( 401 === $http_code ) {
						$error_message = __( 'PayPal authentication failed. Please check your credentials.', 'charitable' );
					} elseif ( 403 === $http_code ) {
						$error_message = __( 'PayPal access denied. Your account may not have Fastlane permissions.', 'charitable' );
					}

					return new WP_Error(
						'paypal_commerce_client_token_error',
						$error_message,
						$body
					);
				}

				if ( empty( $body['client_token'] ) ) {
					Charitable_PayPal_Logger::error(
						'paypal.oauth.client_token_failed',
						'Client token response missing token',
						array(
							'http_code' => $http_code,
						)
					);
					return new WP_Error(
						'paypal_commerce_client_token_error',
						__( 'PayPal client token response missing token.', 'charitable' )
					);
				}

				// Cache the token for 1 hour (client tokens expire after 1 hour).
				$client_token = $body['client_token'];
				set_transient( $transient_key, $client_token, HOUR_IN_SECONDS );

				Charitable_PayPal_Logger::debug(
					'paypal.oauth.client_token_generated',
					'Client token generated',
					array(
						'http_code' => $http_code,
					)
				);

				return $client_token;

			} catch ( Exception $e ) {
				Charitable_PayPal_Logger::error(
					'paypal.oauth.client_token_failed',
					'Exception in generate_client_token',
					array(
						'error' => $e->getMessage(),
					)
				);

				return new WP_Error(
					'paypal_commerce_client_token_error',
					__( 'An unexpected error occurred while generating client token.', 'charitable' )
				);
			}
		}

		/**
		 * Get cached client token.
		 *
		 * @since  1.8.11
		 *
		 * @return string|false Client token or false if not cached.
		 */
		public function get_cached_client_token() {
			$mode         = $this->is_sandbox() ? 'sandbox' : 'live';
			$transient_key = 'charitable_paypal_client_token_' . $mode;

			return get_transient( $transient_key );
		}

		/**
		 * Generate a user ID token for card vaulting via JS SDK.
		 *
		 * This is different from the Fastlane client token. The user ID token
		 * is passed via data-user-id-token on the SDK script tag and enables
		 * the JS SDK to vault card payment methods during purchase.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $customer_id Optional PayPal customer ID for returning payers.
		 * @return string|WP_Error The id_token or error.
		 */
		public function generate_user_id_token( $customer_id = '' ) {
			$mode          = $this->is_sandbox() ? 'sandbox' : 'live';
			$customer_hash = ! empty( $customer_id ) ? '_' . md5( $customer_id ) : '';
			$transient_key = 'charitable_paypal_user_id_token_' . $mode . $customer_hash;

			// Check cached token (shorter TTL since these expire).
			// Cache is per-customer since target_customer_id changes the token.
			$cached_token = get_transient( $transient_key );
			if ( $cached_token ) {
				return $cached_token;
			}

			// In middleware mode, delegate to the middleware's /oauth/user-id-token endpoint.
			if ( $this->is_middleware_mode() ) {
				$result = Charitable_PayPal_Middleware_Client::get_instance()->get_user_id_token( $customer_id );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$id_token = isset( $result['id_token'] ) ? $result['id_token'] : '';
				if ( ! empty( $id_token ) ) {
					set_transient( $transient_key, $id_token, 30 * MINUTE_IN_SECONDS );
				}
				return $id_token;
			}

			$client_id     = $this->get_client_id();
			$client_secret = $this->get_client_secret();

			if ( empty( $client_id ) || empty( $client_secret ) ) {
				return new WP_Error(
					'paypal_commerce_user_id_token_error',
					__( 'PayPal credentials not configured.', 'charitable' )
				);
			}

			$body_params = array(
				'grant_type'    => 'client_credentials',
				'response_type' => 'id_token',
				'intent'        => 'sdk_init',
			);

			// For returning payers, include the customer ID.
			if ( ! empty( $customer_id ) ) {
				$body_params['target_customer_id'] = $customer_id;
			}

			$response = wp_remote_post(
				$this->get_api_url() . '/v1/oauth2/token',
				array(
					'headers' => array(
						'Content-Type'                  => 'application/x-www-form-urlencoded',
						'Authorization'                 => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
						'PayPal-Partner-Attribution-Id' => $this->get_bn_code(),
						'PayPal-Auth-Assertion'         => $this->build_auth_assertion(),
					),
					'body'    => http_build_query( $body_params ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				Charitable_PayPal_Logger::error(
					'paypal.oauth.user_id_token_failed',
					'User ID token generation failed',
					array(
						'http_code'       => $http_code,
						'paypal_error'    => isset( $body['error'] ) ? $body['error'] : null,
						'paypal_debug_id' => wp_remote_retrieve_header( $response, 'paypal-debug-id' ),
						'response'        => $body,
					)
				);

				return new WP_Error(
					'paypal_commerce_user_id_token_error',
					isset( $body['error_description'] ) ? $body['error_description'] : __( 'Failed to generate user ID token.', 'charitable' ),
					$body
				);
			}

			if ( empty( $body['id_token'] ) ) {
				return new WP_Error(
					'paypal_commerce_user_id_token_error',
					__( 'PayPal response missing id_token.', 'charitable' )
				);
			}

			$id_token = $body['id_token'];

			// Cache for 30 minutes (tokens have limited lifespan).
			set_transient( $transient_key, $id_token, 30 * MINUTE_IN_SECONDS );

			Charitable_PayPal_Logger::debug(
				'paypal.oauth.user_id_token_generated',
				'User ID token generated successfully'
			);

			return $id_token;
		}

		/**
		 * Get the platform fee payment_instruction array for a PayPal order.
		 *
		 * When the site does not have an active Pro license, a 3% platform fee
		 * is added to PayPal orders (mirroring the Stripe Connect fee model).
		 *
		 * @since  1.8.11
		 *
		 * @param  float  $amount   The donation amount.
		 * @param  string $currency The currency code.
		 * @return array Empty array if no fee, or payment_instruction array.
		 */
		public function get_platform_fee_instruction( $amount, $currency ) {
			// Middleware mode: fees are injected server-side (tamper-proof).
			if ( $this->is_middleware_mode() ) {
				return array();
			}

			if ( charitable_is_pro() ) {
				return array();
			}

			$amount = floatval( $amount );

			if ( $amount <= 0 ) {
				return array();
			}

			/**
			 * Filter the platform fee percentage for PayPal Commerce.
			 *
			 * @since  1.8.11
			 *
			 * @param  float $percentage The fee percentage (default 3).
			 * @param  float $amount     The donation amount.
			 * @return float
			 */
			$percentage = apply_filters( 'charitable_paypal_platform_fee_percentage', 3, $amount );

			// Determine decimal places based on currency (JPY, KRW, etc. use 0 decimals).
			$currency_helper = charitable_get_currency_helper();
			$decimals        = $currency_helper->is_zero_decimal_currency( $currency ) ? 0 : 2;

			$fee = round( $amount * ( $percentage / 100 ), $decimals );

			if ( $fee <= 0 ) {
				return array();
			}

			$fee_value = number_format( $fee, $decimals, '.', '' );

			Charitable_PayPal_Logger::debug(
				'paypal.platform_fee.applied',
				'Platform fee applied to PayPal order',
				array(
					'amount'     => number_format( $amount, $decimals, '.', '' ),
					'fee'        => $fee_value,
					'currency'   => $currency,
					'percentage' => $percentage,
				)
			);

			return array(
				'payment_instruction' => array(
					'platform_fees' => array(
						array(
							'amount' => array(
								'currency_code' => $currency,
								'value'         => $fee_value,
							),
						),
					),
				),
			);
		}

		/**
		 * Create a PayPal order from amount directly (for JS SDK flow).
		 *
		 * This is used when creating an order before the Charitable donation exists.
		 * The donation will be created when the order is captured.
		 *
		 * @since  1.8.11
		 *
		 * @param  float  $amount         Donation amount.
		 * @param  string $currency       Currency code.
		 * @param  string $campaign_name  Campaign name for description.
		 * @param  string $reference_id   Reference ID for the order.
		 * @param  string $payment_method Payment method (paypal, card, fastlane, etc.).
		 * @param  string $payer_email    Buyer email for prefill.
		 * @param  string $fastlane_token Single-use token from FastlanePaymentComponent.
		 * @return array|WP_Error
		 */
		public function create_order_from_amount( $amount, $currency, $campaign_name = '', $reference_id = '', $payment_method = '', $payer_email = '', $fastlane_token = '' ) {
			// Middleware mode: build order data and delegate.
			if ( $this->is_middleware_mode() ) {
				return $this->create_order_via_middleware( $amount, $currency, $campaign_name, $reference_id, $payment_method, $payer_email, $fastlane_token );
			}

			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			if ( empty( $campaign_name ) ) {
				$campaign_name = __( 'Donation', 'charitable' );
			}

			if ( empty( $reference_id ) ) {
				$reference_id = 'order_' . time() . '_' . wp_rand( 1000, 9999 );
			}

			// Get the merchant ID for the payee.
			$seller_merchant_id = $this->get_seller_merchant_id();

			Charitable_PayPal_Logger::debug(
				'paypal.order.create_started',
				'Creating order with payee merchant_id',
				array(
					'merchant_id'   => $seller_merchant_id,
					'amount'        => $amount,
					'currency'      => $currency,
					'reference_id'  => $reference_id,
					'payment_method' => $payment_method,
				)
			);

			$order_data = array(
				'intent'         => 'CAPTURE',
				'purchase_units' => array(
					array(
						'reference_id'    => $reference_id,
						'description'     => sprintf(
							/* translators: %s: campaign name */
							__( 'Donation to %s', 'charitable' ),
							$campaign_name
						),
						'soft_descriptor' => 'DONATION',
						'payee'           => array(
							'merchant_id' => $seller_merchant_id,
						),
						'amount'          => array(
							'currency_code' => $currency,
							'value'         => number_format( $amount, 2, '.', '' ),
							'breakdown'     => array(
								'item_total' => array(
									'currency_code' => $currency,
									'value'         => number_format( $amount, 2, '.', '' ),
								),
							),
						),
						'items'           => array(
							array(
								'name'        => sprintf(
									/* translators: %s: campaign name */
									__( 'Donation to %s', 'charitable' ),
									$campaign_name
								),
								'description' => __( 'Charitable donation', 'charitable' ),
								'quantity'    => '1',
								'unit_amount' => array(
									'currency_code' => $currency,
									'value'         => number_format( $amount, 2, '.', '' ),
								),
								// Apple Pay and Google Pay do not support 'DONATION' category.
								'category'    => in_array( $payment_method, array( 'applepay', 'googlepay' ), true ) ? 'PHYSICAL_GOODS' : 'DONATION',
							),
						),
					),
				),
			);

			// Add platform fee for unlicensed sites.
			$platform_fee = $this->get_platform_fee_instruction( $amount, $currency );
			if ( ! empty( $platform_fee ) ) {
				$order_data['purchase_units'][0] = array_merge( $order_data['purchase_units'][0], $platform_fee );
			}

			// Prefill payer email when available (reduces buyer data entry).
			if ( ! empty( $payer_email ) ) {
				$order_data['payer'] = array(
					'email_address' => $payer_email,
				);
			}

			// For PayPal wallet payments, add experience_context with Pay Now and brand name.
			if ( 'paypal' === $payment_method ) {
				$brand_name = ! empty( $campaign_name ) ? $campaign_name : get_bloginfo( 'name' );
				$order_data['payment_source'] = array(
					'paypal' => array(
						'experience_context' => array(
							'user_action'         => 'PAY_NOW',
							'brand_name'          => $brand_name,
							'return_url'          => home_url( '/' ),
							'cancel_url'          => home_url( '/' ),
							'shipping_preference' => 'NO_SHIPPING',
						),
					),
				);
			}

			// For Fastlane payments, use single_use_token from FastlanePaymentComponent.
			if ( 'fastlane' === $payment_method && ! empty( $fastlane_token ) ) {
				$order_data['payment_source'] = array(
					'card' => array(
						'single_use_token' => $fastlane_token,
					),
				);
			}

			// Deterministic PayPal-Request-Id keyed on reference_id so any retry
			// of the same logical create call returns PayPal's cached prior
			// response instead of producing a duplicate order.
			$response = wp_remote_post(
				$this->get_api_url() . '/v2/checkout/orders',
				array(
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token,
						'PayPal-Request-Id'             => 'charitable_' . $reference_id,
						'PayPal-Partner-Attribution-Id' => $this->get_bn_code(),
					),
					'body'    => wp_json_encode( $order_data ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				Charitable_PayPal_Logger::error(
					'paypal.order.create_failed',
					'Order create failed',
					array(
						'http_code'       => $http_code,
						'paypal_error'    => $body['name'] ?? null,
						'paypal_message'  => $body['message'] ?? null,
						'paypal_debug_id' => $body['debug_id'] ?? wp_remote_retrieve_header( $response, 'paypal-debug-id' ),
					)
				);

				return new WP_Error(
					'paypal_commerce_create_order_error',
					isset( $body['message'] ) ? $body['message'] : __( 'Failed to create PayPal order.', 'charitable' ),
					$body
				);
			}

			return $body;
		}

		/**
		 * Create a PayPal order for a donation.
		 *
		 * @since  1.8.11
		 *
		 * @param  Charitable_Donation $donation Donation object.
		 * @return array|WP_Error
		 */
		public function create_order( Charitable_Donation $donation ) {
			$campaign = $donation->get_campaigns()[0] ?? null;
			$campaign_name = $campaign ? $campaign->post_title : __( 'Donation', 'charitable' );
			$brand_name    = ! empty( $campaign_name ) ? $campaign_name : get_bloginfo( 'name' );

			$order_data = array(
				'intent'         => 'CAPTURE',
				'purchase_units' => array(
					array(
						'reference_id'    => 'donation_' . $donation->ID,
						'description'     => sprintf(
							/* translators: %s: campaign name */
							__( 'Donation to %s', 'charitable' ),
							$campaign_name
						),
						'soft_descriptor' => 'DONATION',
						'amount'          => array(
							'currency_code' => charitable_get_currency(),
							'value'         => number_format( $donation->get_total_donation_amount(), 2, '.', '' ),
							'breakdown'     => array(
								'item_total' => array(
									'currency_code' => charitable_get_currency(),
									'value'         => number_format( $donation->get_total_donation_amount(), 2, '.', '' ),
								),
							),
						),
						'items'           => array(
							array(
								'name'        => sprintf(
									/* translators: %s: campaign name */
									__( 'Donation to %s', 'charitable' ),
									$campaign_name
								),
								'description' => __( 'Charitable donation', 'charitable' ),
								'quantity'    => '1',
								'unit_amount' => array(
									'currency_code' => charitable_get_currency(),
									'value'         => number_format( $donation->get_total_donation_amount(), 2, '.', '' ),
								),
								'category'    => 'DONATION',
							),
						),
					),
				),
				// Donation orders never ship — set shipping_preference at the payment-source
				// level so PayPal hosted checkout renders without a shipping address section
				// and Continue-to-Review-Order does not loop on shipping validation.
				//
				// return_url is plain home_url() because PayPal appends ?token=<order_id>&PayerID=<...>
				// on success; the donation-return handler (init hook) looks up the pending
				// donation by _paypal_commerce_order_id matching the token and finalizes it.
				//
				// cancel_url carries ?paypal_cancel=1&donation_id=N so the cancel-return
				// handler can mark abandoned donations charitable-cancelled instead of
				// leaving them in charitable-pending forever.
				'payment_source' => array(
					'paypal' => array(
						'experience_context' => array(
							'user_action'         => 'PAY_NOW',
							'brand_name'          => $brand_name,
							'return_url'          => home_url( '/' ),
							'cancel_url'          => add_query_arg(
								array(
									'paypal_cancel' => '1',
									'donation_id'   => (int) $donation->ID,
								),
								home_url( '/' )
							),
							'shipping_preference' => 'NO_SHIPPING',
						),
					),
				),
			);

			// Middleware mode: delegate order creation to middleware (fees injected server-side).
			if ( $this->is_middleware_mode() ) {
				return Charitable_PayPal_Middleware_Client::get_instance()->create_order( $order_data );
			}

			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			// Add platform fee for unlicensed sites.
			$platform_fee = $this->get_platform_fee_instruction( $donation->get_total_donation_amount(), charitable_get_currency() );
			if ( ! empty( $platform_fee ) ) {
				$order_data['purchase_units'][0] = array_merge( $order_data['purchase_units'][0], $platform_fee );
			}

			$response = wp_remote_post(
				$this->get_api_url() . '/v2/checkout/orders',
				array(
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token,
						'PayPal-Request-Id'             => 'charitable_' . (int) $donation->ID,
						'PayPal-Partner-Attribution-Id' => $this->get_bn_code(),
					),
					'body'    => wp_json_encode( $order_data ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				return new WP_Error(
					'paypal_commerce_create_order_error',
					isset( $body['message'] ) ? $body['message'] : __( 'Failed to create PayPal order.', 'charitable' ),
					$body
				);
			}

			return $body;
		}

		/**
		 * Get PayPal order details.
		 *
		 * Retrieves the full order object including payment_source with vault data.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $order_id PayPal order ID.
		 * @return array|WP_Error
		 */
		public function get_order( $order_id ) {
			// Middleware mode: delegate to middleware client.
			if ( $this->is_middleware_mode() ) {
				return Charitable_PayPal_Middleware_Client::get_instance()->get_order( $order_id );
			}

			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$response = wp_remote_get(
				$this->get_api_url() . '/v2/checkout/orders/' . $order_id,
				array(
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token,
						'PayPal-Partner-Attribution-Id' => $this->get_bn_code(),
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				return new WP_Error(
					'paypal_commerce_get_order_error',
					isset( $body['message'] ) ? $body['message'] : __( 'Failed to get PayPal order details.', 'charitable' ),
					$body
				);
			}

			return $body;
		}

		/**
		 * Capture a PayPal order.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $order_id PayPal order ID.
		 * @return array|WP_Error
		 */
		public function capture_order( $order_id ) {
			// Middleware mode: delegate to middleware client.
			if ( $this->is_middleware_mode() ) {
				return Charitable_PayPal_Middleware_Client::get_instance()->capture_order( $order_id );
			}

			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$response = wp_remote_post(
				$this->get_api_url() . '/v2/checkout/orders/' . $order_id . '/capture',
				array(
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token,
						'PayPal-Request-Id'             => 'charitable_capture_' . $order_id,
						'PayPal-Partner-Attribution-Id' => $this->get_bn_code(),
					),
					'body'    => '{}',
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );
			$debug_id  = wp_remote_retrieve_header( $response, 'paypal-debug-id' );

			if ( $http_code >= 400 ) {
				// If the order was already captured (e.g. Fastlane auto-captures on creation),
				// fetch the order details and return them as if capture just succeeded.
				$issues = isset( $body['details'] ) ? wp_list_pluck( $body['details'], 'issue' ) : array();
				if ( in_array( 'ORDER_ALREADY_CAPTURED', $issues, true ) ) {
					return $this->get_order( $order_id );
				}

				return new WP_Error(
					'paypal_commerce_capture_error',
					isset( $body['message'] ) ? $body['message'] : __( 'Failed to capture PayPal payment.', 'charitable' ),
					$body
				);
			}

			// Surface the PayPal debug-id (HTTP response header, not a body field)
			// so callers — particularly the structured logger — can correlate
			// captures with PayPal's support records. Middleware mode already
			// attaches `debug_id` similarly; this aligns the direct-API path.
			if ( is_array( $body ) && ! empty( $debug_id ) && ! isset( $body['paypal_debug_id'] ) ) {
				$body['paypal_debug_id'] = $debug_id;
			}

			return $body;
		}

		/**
		 * Get the BN (Partner Attribution) code.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_bn_code() {
			/**
			 * Filter the PayPal BN code.
			 *
			 * @since 1.8.11
			 *
			 * @param string $bn_code BN code.
			 */
			return apply_filters( 'charitable_paypal_commerce_bn_code', 'WPCharitable_PPCP' );
		}

		/**
		 * Build the PayPal-Auth-Assertion header value.
		 *
		 * Structure: base64url({"alg":"none"}).base64url({"iss":"CLIENT_ID","payer_id":"MERCHANT_ID"}).
		 * Required by PayPal for partner API calls made on behalf of a seller.
		 *
		 * @since 1.8.11
		 *
		 * @return string
		 */
		public function build_auth_assertion() {
			$header  = base64_encode( wp_json_encode( array( 'alg' => 'none' ) ) );
			$payload = base64_encode( wp_json_encode( array(
				'iss'      => $this->get_client_id(),
				'payer_id' => $this->get_seller_merchant_id(),
			) ) );
			return $header . '.' . $payload . '.';
		}

		/**
		 * Create a partner referral for seller onboarding.
		 *
		 * This generates a signup link that redirects sellers to PayPal
		 * to connect their account. Requests PPCP product for ACDC support.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $tracking_id Unique ID to track this seller.
		 * @param  string $return_url  URL to redirect after onboarding.
		 * @return array|WP_Error
		 */
		public function create_partner_referral( $tracking_id, $return_url ) {
			// In middleware mode, delegate to middleware — the middleware uses the secret as tracking_id.
			// Use get_or_create so first-onboarding on a fresh site gets a freshly generated secret.
			if ( $this->is_middleware_mode() ) {
				$middleware = Charitable_PayPal_Middleware_Client::get_instance();
				return $middleware->create_partner_referral( $this->get_or_create_middleware_secret(), home_url( '/' ), $return_url );
			}

			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			// Build the referral data per PayPal's Partner Referrals API v2.
			$capabilities = array(
				'PAYPAL_WALLET_VAULTING_ADVANCED',  // Required for vaulting (per PayPal support 2026-02-17)
			);

			// Add Apple Pay capability when enabled in settings (IWT requirement).
			if ( $this->get_value( 'enable_apple_pay' ) ) {
				$capabilities[] = 'APPLE_PAY';
			}

			// Add Google Pay capability when enabled in settings (IWT requirement).
			if ( $this->get_value( 'enable_google_pay' ) ) {
				$capabilities[] = 'GOOGLE_PAY';
			}

			$referral_data = array(
				'tracking_id'             => $tracking_id,
				'partner_config_override' => array(
					'return_url' => $return_url,
				),
				'operations'              => array(
					array(
						'operation'                  => 'API_INTEGRATION',
						'api_integration_preference' => array(
							'rest_api_integration' => array(
								'integration_method'  => 'PAYPAL',
								'integration_type'    => 'THIRD_PARTY',
								'third_party_details' => array(
									'features' => array(
										'PAYMENT',
										'REFUND',
										'PARTNER_FEE',
										'ACCESS_MERCHANT_INFORMATION',
										'VAULT',             // Required for Fastlane
										'BILLING_AGREEMENT', // Required for Fastlane
									),
								),
							),
						),
					),
				),
				'products'                => array(
					'PPCP',
					'PPCP_CUSTOM',        // Required for Advanced Card Processing (ACDC); PayPal vets by country
					'ADVANCED_VAULTING',  // Required for Payment Method Vaulting (per PayPal support 2026-02-17)
					'PAYMENT_METHODS',    // Required for Apple Pay / Google Pay (IWT requirement)
				),
				'capabilities'            => $capabilities,
				'legal_consents'          => array(
					array(
						'type'    => 'SHARE_DATA_CONSENT',
						'granted' => true,
					),
				),
			);

			Charitable_PayPal_Logger::debug(
				'paypal.connect.referral_request',
				'Partner referral request payload built',
				array(
					'referral_data' => $referral_data,
				)
			);

			$response = wp_remote_post(
				$this->get_api_url() . '/v2/customer/partner-referrals',
				array(
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token,
						'PayPal-Partner-Attribution-Id' => $this->get_bn_code(),
					),
					'body'    => wp_json_encode( $referral_data ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				Charitable_PayPal_Logger::error(
					'paypal.connect.referral_failed',
					'Partner referral request failed (WP error)',
					array(
						'error' => $response->get_error_message(),
					)
				);
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			Charitable_PayPal_Logger::debug(
				'paypal.connect.referral_response',
				'Partner referral API response received',
				array(
					'http_code'       => $http_code,
					'paypal_debug_id' => wp_remote_retrieve_header( $response, 'paypal-debug-id' ),
					'response'        => $body,
				)
			);

			if ( $http_code >= 400 ) {
				// Build detailed error message.
				$error_message = isset( $body['message'] ) ? $body['message'] : __( 'Failed to create partner referral.', 'charitable' );

				// Include details if available.
				if ( ! empty( $body['details'] ) ) {
					$details = array();
					foreach ( $body['details'] as $detail ) {
						$field = isset( $detail['field'] ) ? $detail['field'] : '';
						$issue = isset( $detail['issue'] ) ? $detail['issue'] : '';
						$desc  = isset( $detail['description'] ) ? $detail['description'] : '';
						$details[] = trim( "$field: $issue - $desc" );
					}
					if ( ! empty( $details ) ) {
						$error_message .= ' Details: ' . implode( '; ', $details );
					}
				}

				return new WP_Error(
					'paypal_commerce_referral_error',
					$error_message,
					$body
				);
			}

			return $body;
		}

		/**
		 * Get the action URL from a partner referral response.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $referral Partner referral response.
		 * @return string|null
		 */
		public function get_referral_action_url( $referral ) {
			// Middleware response format: {url, referral_id, expires_in}.
			if ( ! empty( $referral['url'] ) && empty( $referral['links'] ) ) {
				return $referral['url'];
			}

			if ( empty( $referral['links'] ) ) {
				return null;
			}

			foreach ( $referral['links'] as $link ) {
				if ( 'action_url' === $link['rel'] ) {
					return $link['href'];
				}
			}

			return null;
		}

		/**
		 * Get seller onboarding status from PayPal.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $merchant_id The seller's PayPal merchant ID.
		 * @return array|WP_Error
		 */
		public function get_seller_status( $merchant_id ) {
			// Per-request cache. Several capability helpers funnel through here
			// during admin settings render; without dedup each one triggers a
			// fresh HTTP round-trip and can blow the PHP request timeout.
			static $request_cache = array();
			$cache_key = (string) $merchant_id;
			if ( array_key_exists( $cache_key, $request_cache ) ) {
				return $request_cache[ $cache_key ];
			}
			$request_cache[ $cache_key ] = $this->fetch_seller_status_remote( $merchant_id );
			return $request_cache[ $cache_key ];
		}

		/**
		 * Underlying network fetch for get_seller_status(). Always hits the
		 * wire (or the middleware) and bypasses the per-request cache.
		 *
		 * @internal
		 *
		 * @param  string $merchant_id The seller's PayPal merchant ID.
		 * @return array|WP_Error
		 */
		private function fetch_seller_status_remote( $merchant_id ) {
			// In middleware mode, use the middleware's merchant-info endpoint instead of
			// calling PayPal directly (which would fail since the access token is middleware-issued).
			if ( $this->is_middleware_mode() ) {
				return Charitable_PayPal_Middleware_Client::get_instance()->get_merchant_info();
			}

			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			// Get partner merchant ID (our platform's merchant ID).
			$partner_merchant_id = $this->get_merchant_id();

			if ( empty( $partner_merchant_id ) ) {
				return new WP_Error(
					'paypal_commerce_missing_partner_id',
					__( 'Partner merchant ID is not configured.', 'charitable' )
				);
			}

			$response = wp_remote_get(
				$this->get_api_url() . '/v1/customer/partners/' . $partner_merchant_id . '/merchant-integrations/' . $merchant_id,
				array(
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token,
						'PayPal-Partner-Attribution-Id' => $this->get_bn_code(),
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				Charitable_PayPal_Logger::error(
					'paypal.connect.seller_status_failed',
					'Seller status request failed (WP error)',
					array(
						'merchant_id' => $merchant_id,
						'error'       => $response->get_error_message(),
					)
				);
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			Charitable_PayPal_Logger::debug(
				'paypal.connect.seller_status_response',
				'Seller status API response received',
				array(
					'merchant_id'     => $merchant_id,
					'http_code'       => $http_code,
					'paypal_debug_id' => wp_remote_retrieve_header( $response, 'paypal-debug-id' ),
					'response'        => $body,
				)
			);

			if ( $http_code >= 400 ) {
				return new WP_Error(
					'paypal_commerce_status_error',
					isset( $body['message'] ) ? $body['message'] : __( 'Failed to get seller status.', 'charitable' ),
					$body
				);
			}

			return $body;
		}

		/**
		 * Check if a seller is fully onboarded and can receive payments.
		 *
		 * Per the Integration Guide (pages 7-8), checks:
		 * - PRIMARY_EMAIL_CONFIRMED flag
		 * - PAYMENTS_RECEIVABLE flag
		 * - OAUTH_INTEGRATIONS array
		 * - PPCP_CUSTOM product with VETTING_STATUS for ACDC
		 * - CUSTOM_CARD_PROCESSING capability for ACDC
		 * - PAYMENT_METHODS product + APPLE_PAY/GOOGLE_PAY capabilities
		 *
		 * @since  1.8.11
		 *
		 * @param  array $status Seller status response from get_seller_status().
		 * @return array Status details with boolean flags.
		 */
		public function check_seller_readiness( $status ) {
			$readiness = array(
				'is_ready'                   => false,
				'payments_receivable'        => false,
				'primary_email_confirmed'    => false,
				'oauth_integrations'         => false,
				'granted_scopes'             => array(),
				'acdc_ready'                 => false,
				'acdc_vetting_status'        => '',
				'apple_pay_ready'            => false,
				'google_pay_ready'           => false,
				'vaulting_ready'             => false,
				'vaulting_capability_status' => '',
				'ppcp_custom_subscribed'     => false,
				'payment_methods_subscribed' => false,
				'issues'                     => array(),
			);

			// Check payments_receivable flag.
			if ( ! empty( $status['payments_receivable'] ) ) {
				$readiness['payments_receivable'] = true;
			} else {
				$readiness['issues'][] = __( 'You currently cannot receive payments due to possible restriction on your PayPal account. Please reach out to PayPal Customer Support or connect to paypal.com for more information.', 'charitable' );
			}

			// Check primary_email_confirmed flag.
			if ( ! empty( $status['primary_email_confirmed'] ) ) {
				$readiness['primary_email_confirmed'] = true;
			} else {
				$readiness['issues'][] = __( 'Please confirm your email address on paypal.com in order to receive payments! You currently cannot receive payments. Once done, simply revisit this page to refresh the onboarding status.', 'charitable' );
			}

			// Check oauth_integrations array and extract granted scopes.
			if ( ! empty( $status['oauth_integrations'] ) ) {
				$readiness['oauth_integrations'] = true;

				// Extract scopes granted to the partner.
				foreach ( $status['oauth_integrations'] as $integration ) {
					if ( ! empty( $integration['oauth_third_party'] ) ) {
						foreach ( $integration['oauth_third_party'] as $third_party ) {
							if ( ! empty( $third_party['scopes'] ) ) {
								$readiness['granted_scopes'] = array_merge( $readiness['granted_scopes'], $third_party['scopes'] );
							}
						}
					}
				}
				$readiness['granted_scopes'] = array_unique( $readiness['granted_scopes'] );

			} elseif ( ! empty( $status['oauth_third_party'] ) ) {
				$readiness['oauth_integrations'] = true;
			} else {
				$readiness['issues'][] = __( 'There is an issue with your onboarding with PayPal. Please go through the onboarding flow again and grant third party permissions to WP Charitable.', 'charitable' );
			}

			// Check products array for PPCP_CUSTOM and PAYMENT_METHODS.
			$ppcp_custom_status     = '';
			$payment_methods_status = '';

			if ( ! empty( $status['products'] ) ) {
				foreach ( $status['products'] as $product ) {
					$product_name   = $product['name'] ?? '';
					$vetting_status = $product['vetting_status'] ?? '';

					// Check PPCP_CUSTOM product for ACDC eligibility.
					if ( 'PPCP_CUSTOM' === $product_name ) {
						$ppcp_custom_status                = $vetting_status;
						$readiness['acdc_vetting_status']  = $vetting_status;
						$readiness['ppcp_custom_subscribed'] = ( 'SUBSCRIBED' === $vetting_status );
					}

					// Check PAYMENT_METHODS product for Apple Pay / Google Pay.
					if ( 'PAYMENT_METHODS' === $product_name ) {
						$payment_methods_status                  = $vetting_status;
						$readiness['payment_methods_subscribed'] = ( 'SUBSCRIBED' === $vetting_status );
					}
				}
			}

			// Check ACDC readiness per Integration Guide page 8:
			// 1. PPCP_CUSTOM must have VETTING_STATUS = "SUBSCRIBED"
			// 2. CUSTOM_CARD_PROCESSING capability must be "ACTIVE" with no limits.
			if ( 'SUBSCRIBED' === $ppcp_custom_status && ! empty( $status['capabilities'] ) ) {
				foreach ( $status['capabilities'] as $capability ) {
					$cap_name   = $capability['name'] ?? '';
					$cap_status = $capability['status'] ?? '';

					if ( 'CUSTOM_CARD_PROCESSING' === $cap_name && 'ACTIVE' === $cap_status ) {
						// Check for limits - if limits exist, ACDC is restricted.
						if ( empty( $capability['limits'] ) ) {
							$readiness['acdc_ready'] = true;
						}
					}
				}
			}

			// If PPCP_CUSTOM vetting is DENIED, seller can use PayPal buttons but NOT ACDC.
			if ( 'DENIED' === $ppcp_custom_status ) {
				$readiness['acdc_ready'] = false;
				// Not an "issue" per se, but note it.
			}

			// Check Apple Pay / Google Pay readiness per Integration Guide page 8:
			// - PPCP_CUSTOM with SUBSCRIBED + PAYMENT_METHODS with SUBSCRIBED
			// - Respective capability (APPLE_PAY or GOOGLE_PAY) with status ACTIVE.
			if ( 'SUBSCRIBED' === $ppcp_custom_status && 'SUBSCRIBED' === $payment_methods_status && ! empty( $status['capabilities'] ) ) {
				foreach ( $status['capabilities'] as $capability ) {
					$cap_name   = $capability['name'] ?? '';
					$cap_status = $capability['status'] ?? '';

					if ( 'APPLE_PAY' === $cap_name && 'ACTIVE' === $cap_status ) {
						$readiness['apple_pay_ready'] = true;
					}

					if ( 'GOOGLE_PAY' === $cap_name && 'ACTIVE' === $cap_status ) {
						$readiness['google_pay_ready'] = true;
					}
				}
			}

			// Check vaulting readiness per Integration Guide:
			// - PAYPAL_WALLET_VAULTING_ADVANCED capability must be ACTIVE.
			if ( ! empty( $status['capabilities'] ) ) {
				foreach ( $status['capabilities'] as $capability ) {
					$cap_name   = $capability['name'] ?? '';
					$cap_status = $capability['status'] ?? '';

					if ( 'PAYPAL_WALLET_VAULTING_ADVANCED' === $cap_name ) {
						$readiness['vaulting_capability_status'] = $cap_status;
						if ( 'ACTIVE' === $cap_status ) {
							$readiness['vaulting_ready'] = true;
						}
					}
				}
			}

			// Overall readiness for basic PayPal payments.
			$readiness['is_ready'] = $readiness['payments_receivable']
				&& $readiness['primary_email_confirmed']
				&& $readiness['oauth_integrations'];

			return $readiness;
		}

		/**
		 * Check if a seller is connected (has stored merchant ID).
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_seller_connected() {
			$key = $this->is_sandbox() ? 'sandbox_seller_merchant_id' : 'live_seller_merchant_id';
			return ! empty( $this->get_value( $key ) );
		}

		/**
		 * Get the connected seller's merchant ID.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_seller_merchant_id() {
			$key = $this->is_sandbox() ? 'sandbox_seller_merchant_id' : 'live_seller_merchant_id';
			return $this->get_value( $key );
		}

		/**
		 * Check if ACDC (Advanced Card Processing / Card Fields) is available.
		 *
		 * This checks if the connected seller is approved for ACDC.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_acdc_available() {
			Charitable_PayPal_Logger::debug( 'paypal.capability.acdc_check_started', 'is_acdc_available() called' );

			if ( ! $this->is_seller_connected() ) {
				Charitable_PayPal_Logger::debug(
					'paypal.capability.acdc_resolved',
					'ACDC unavailable — seller not connected',
					array(
						'available' => false,
						'reason'    => 'seller_not_connected',
					)
				);
				return false;
			}

			// Check cached status first.
			$mode   = $this->is_sandbox() ? 'sandbox' : 'live';
			$cached = get_transient( 'charitable_paypal_seller_status_' . $mode );

			if ( is_array( $cached ) && isset( $cached['acdc_ready'] ) ) {
				Charitable_PayPal_Logger::debug(
					'paypal.capability.acdc_resolved',
					'ACDC resolved from cache',
					array(
						'available' => (bool) $cached['acdc_ready'],
						'source'    => 'cache',
					)
				);
				return (bool) $cached['acdc_ready'];
			}

			// Fetch fresh status.
			$merchant_id = $this->get_seller_merchant_id();

			Charitable_PayPal_Logger::debug(
				'paypal.capability.acdc_fetch_started',
				'Fetching fresh seller status for ACDC resolution',
				array(
					'merchant_id' => $merchant_id,
				)
			);

			$status      = $this->get_seller_status( $merchant_id );

			if ( is_wp_error( $status ) ) {
				Charitable_PayPal_Logger::error(
					'paypal.capability.acdc_fetch_failed',
					'Error fetching seller status during ACDC resolution',
					array(
						'merchant_id' => $merchant_id,
						'error'       => $status->get_error_message(),
					)
				);
				return false;
			}

			$readiness = $this->check_seller_readiness( $status );

			Charitable_PayPal_Logger::debug(
				'paypal.capability.acdc_resolved',
				'ACDC readiness computed',
				array(
					'available' => (bool) $readiness['acdc_ready'],
					'source'    => 'fresh',
					'readiness' => $readiness,
				)
			);

			// Cache the result.
			set_transient( 'charitable_paypal_seller_status_' . $mode, $readiness, HOUR_IN_SECONDS );

			return (bool) $readiness['acdc_ready'];
		}

		/**
		 * Check if Apple Pay is available for the connected seller.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_apple_pay_available() {
			if ( ! $this->is_seller_connected() ) {
				return false;
			}

			$mode   = $this->is_sandbox() ? 'sandbox' : 'live';
			$cached = get_transient( 'charitable_paypal_seller_status_' . $mode );

			if ( is_array( $cached ) && isset( $cached['apple_pay_ready'] ) ) {
				return (bool) $cached['apple_pay_ready'];
			}

			// Fetch fresh status.
			$merchant_id = $this->get_seller_merchant_id();
			$status      = $this->get_seller_status( $merchant_id );

			if ( is_wp_error( $status ) ) {
				return false;
			}

			$readiness = $this->check_seller_readiness( $status );
			set_transient( 'charitable_paypal_seller_status_' . $mode, $readiness, HOUR_IN_SECONDS );

			return (bool) $readiness['apple_pay_ready'];
		}

		/**
		 * Check if Google Pay is available for the connected seller.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_google_pay_available() {
			if ( ! $this->is_seller_connected() ) {
				return false;
			}

			$mode   = $this->is_sandbox() ? 'sandbox' : 'live';
			$cached = get_transient( 'charitable_paypal_seller_status_' . $mode );

			if ( is_array( $cached ) && isset( $cached['google_pay_ready'] ) ) {
				return (bool) $cached['google_pay_ready'];
			}

			// Fetch fresh status.
			$merchant_id = $this->get_seller_merchant_id();
			$status      = $this->get_seller_status( $merchant_id );

			if ( is_wp_error( $status ) ) {
				return false;
			}

			$readiness = $this->check_seller_readiness( $status );
			set_transient( 'charitable_paypal_seller_status_' . $mode, $readiness, HOUR_IN_SECONDS );

			return (bool) $readiness['google_pay_ready'];
		}

		/**
		 * Check if Fastlane is available for this seller.
		 *
		 * Fastlane requires:
		 * - ADVANCED_VAULTING product (vetting_status: "SUBSCRIBED")
		 * - PPCP_CUSTOM product (vetting_status: "SUBSCRIBED")
		 * - PAYPAL_WALLET_VAULTING_ADVANCED capability (status: "ACTIVE")
		 * - Fastlane setting enabled
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function is_fastlane_available() {
			try {
				// Check if Fastlane is enabled in settings.
				if ( ! $this->get_value( 'enable_fastlane' ) ) {
					Charitable_PayPal_Logger::debug(
						'paypal.capability.fastlane_resolved',
						'Fastlane unavailable — disabled in settings',
						array(
							'available' => false,
							'reason'    => 'disabled_in_settings',
						)
					);
					return false;
				}

				if ( ! $this->is_seller_connected() ) {
					Charitable_PayPal_Logger::debug(
						'paypal.capability.fastlane_resolved',
						'Fastlane unavailable — seller not connected',
						array(
							'available' => false,
							'reason'    => 'seller_not_connected',
						)
					);
					return false;
				}

				$mode   = $this->is_sandbox() ? 'sandbox' : 'live';
				// Use 1/0 instead of true/false so get_transient() returning false
				// (meaning "not cached") is distinguishable from a stored negative result.
				$cached = get_transient( 'charitable_paypal_fastlane_status_' . $mode );

				if ( false !== $cached ) {
					Charitable_PayPal_Logger::debug(
						'paypal.capability.fastlane_resolved',
						'Fastlane resolved from cache',
						array(
							'available' => (bool) $cached,
							'source'    => 'cache',
						)
					);
					return (bool) $cached;
				}

				// Fetch fresh status.
				$merchant_id = $this->get_seller_merchant_id();

				Charitable_PayPal_Logger::debug(
					'paypal.capability.fastlane_fetch_started',
					'Fetching fresh seller status for Fastlane resolution',
					array(
						'merchant_id' => $merchant_id,
					)
				);

				$status = $this->get_seller_status( $merchant_id );

				if ( is_wp_error( $status ) ) {
					Charitable_PayPal_Logger::error(
						'paypal.capability.fastlane_fetch_failed',
						'Error fetching seller status for Fastlane',
						array(
							'merchant_id' => $merchant_id,
							'error'       => $status->get_error_message(),
						)
					);
					// Cache negative result to prevent repeated API calls.
					set_transient( 'charitable_paypal_fastlane_status_' . $mode, 0, 5 * MINUTE_IN_SECONDS );
					return false;
				}

				$readiness = $this->check_fastlane_seller_readiness( $status );
				set_transient( 'charitable_paypal_fastlane_status_' . $mode, $readiness ? 1 : 0, HOUR_IN_SECONDS );

				Charitable_PayPal_Logger::debug(
					'paypal.capability.fastlane_resolved',
					'Fastlane readiness computed',
					array(
						'available'  => (bool) $readiness,
						'source'     => 'fresh',
						'merchant_id' => $merchant_id,
					)
				);

				return $readiness;

			} catch ( Exception $e ) {
				Charitable_PayPal_Logger::error(
					'paypal.capability.fastlane_exception',
					'Exception in is_fastlane_available',
					array(
						'error' => $e->getMessage(),
					)
				);

				// Cache negative result to prevent repeated failures.
				$mode = $this->is_sandbox() ? 'sandbox' : 'live';
				set_transient( 'charitable_paypal_fastlane_status_' . $mode, 0, 5 * MINUTE_IN_SECONDS );

				return false;
			}
		}

		/**
		 * Check if seller has the required capabilities for Fastlane.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $status Seller status response from get_seller_status().
		 * @return bool True if Fastlane is available.
		 */
		public function check_fastlane_seller_readiness( $status ) {
			if ( ! is_array( $status ) || empty( $status['merchant_id'] ) ) {
				return false;
			}

			$products     = isset( $status['products'] ) ? $status['products'] : array();
			$capabilities = isset( $status['capabilities'] ) ? $status['capabilities'] : array();

			// Check for ADVANCED_VAULTING product.
			$advanced_vaulting_ready = false;
			foreach ( $products as $product ) {
				if ( isset( $product['name'] ) && 'ADVANCED_VAULTING' === $product['name'] ) {
					$advanced_vaulting_ready = isset( $product['vetting_status'] ) && 'SUBSCRIBED' === $product['vetting_status'];
					break;
				}
			}

			// Check for PPCP_CUSTOM product.
			$ppcp_custom_ready = false;
			foreach ( $products as $product ) {
				if ( isset( $product['name'] ) && 'PPCP_CUSTOM' === $product['name'] ) {
					$ppcp_custom_ready = isset( $product['vetting_status'] ) && 'SUBSCRIBED' === $product['vetting_status'];
					break;
				}
			}

			// Check for PAYPAL_WALLET_VAULTING_ADVANCED capability.
			$vaulting_capability_ready = false;
			foreach ( $capabilities as $capability ) {
				if ( isset( $capability['name'] ) && 'PAYPAL_WALLET_VAULTING_ADVANCED' === $capability['name'] ) {
					$vaulting_capability_ready = isset( $capability['status'] ) && 'ACTIVE' === $capability['status'];
					break;
				}
			}

			$fastlane_ready = $advanced_vaulting_ready && $ppcp_custom_ready && $vaulting_capability_ready;

			Charitable_PayPal_Logger::debug(
				'paypal.capability.fastlane_capability_check',
				'Fastlane capability breakdown computed',
				array(
					'advanced_vaulting'            => (bool) $advanced_vaulting_ready,
					'ppcp_custom'                  => (bool) $ppcp_custom_ready,
					'paypal_wallet_vaulting_advanced' => (bool) $vaulting_capability_ready,
					'fastlane_ready'               => (bool) $fastlane_ready,
				)
			);

			return $fastlane_ready;
		}

		/**
		 * Get the seller's current readiness status (cached).
		 *
		 * @since  1.8.11
		 *
		 * @param  bool $force_refresh Force a fresh API call.
		 * @return array|false Readiness array or false if not connected.
		 */
		public function get_seller_readiness( $force_refresh = false ) {
			if ( ! $this->is_seller_connected() ) {
				return false;
			}

			$mode   = $this->is_sandbox() ? 'sandbox' : 'live';
			$cached = get_transient( 'charitable_paypal_seller_status_' . $mode );

			if ( ! $force_refresh && is_array( $cached ) ) {
				return $cached;
			}

			// Fetch fresh status.
			$merchant_id = $this->get_seller_merchant_id();
			$status      = $this->get_seller_status( $merchant_id );

			if ( is_wp_error( $status ) ) {
				return false;
			}

			$readiness = $this->check_seller_readiness( $status );
			set_transient( 'charitable_paypal_seller_status_' . $mode, $readiness, HOUR_IN_SECONDS );

			return $readiness;
		}

		/**
		 * Check if Card Fields should be enabled on the frontend.
		 *
		 * This checks both the admin setting AND PayPal ACDC availability.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function should_show_card_fields() {
			$enabled = $this->get_value( 'enable_card_fields' );
			return $enabled && $this->is_acdc_available();
		}

		/**
		 * Check if Apple Pay should be enabled on the frontend.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function should_show_apple_pay() {
			$enabled = $this->get_value( 'enable_apple_pay' );
			return $enabled && $this->is_apple_pay_available();
		}

		/**
		 * Check if Google Pay should be enabled on the frontend.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function should_show_google_pay() {
			$enabled = $this->get_value( 'enable_google_pay' );
			return $enabled && $this->is_google_pay_available();
		}

		/**
		 * Check if Fastlane should be enabled on the frontend.
		 *
		 * @since  1.8.11
		 *
		 * @return bool
		 */
		public function should_show_fastlane() {
			$enabled = $this->get_value( 'enable_fastlane' );
			return $enabled && $this->is_fastlane_available();
		}

		/**
		 * Get the Fastlane behavior setting.
		 *
		 * @since  1.8.11
		 *
		 * @return string 'guest_only' or 'all_users'
		 */
		public function get_fastlane_behavior() {
			return $this->get_value( 'fastlane_behavior' );
		}

		/**
		 * Check if Fastlane should be shown for the current user.
		 *
		 * @since  1.8.11
		 *
		 * @param  bool $is_logged_in Whether the user is logged into WordPress.
		 * @return bool
		 */
		public function should_show_fastlane_for_user( $is_logged_in = null ) {
			if ( ! $this->should_show_fastlane() ) {
				return false;
			}

			$behavior = $this->get_fastlane_behavior();

			// If behavior is 'all_users', show for everyone.
			if ( 'all_users' === $behavior ) {
				return true;
			}

			// Default behavior is 'guest_only' - show only for non-logged-in users.
			if ( null === $is_logged_in ) {
				$is_logged_in = is_user_logged_in();
			}

			return ! $is_logged_in;
		}

		/**
		 * Get the return URL for seller onboarding.
		 *
		 * PayPal limits return URLs to 127 characters, so we use short param names.
		 *
		 * When $csrf_token is non-empty, it is appended as `t=` so the return
		 * handler can verify the redirect originated from a connect flow this
		 * site started. PayPal preserves query-string params on its redirect
		 * back, so the token survives the round-trip. Pass an empty string
		 * (the default) for legacy callers that don't need verification.
		 *
		 * @since  1.8.11
		 * @since  1.8.11 Added $csrf_token parameter for return-handler CSRF defense.
		 *
		 * @param  string $csrf_token Optional short token to embed in the return URL.
		 * @return string
		 */
		public function get_onboarding_return_url( $csrf_token = '' ) {
			$args = array(
				'cppc_onboard' => '1',
				'm'            => $this->is_sandbox() ? 's' : 'l',
			);

			if ( '' !== $csrf_token ) {
				$args['t'] = $csrf_token;
			}

			// Use short parameter names to stay under PayPal's 127 char limit.
			return add_query_arg( $args, admin_url( 'admin.php?page=charitable-settings' ) );
		}

		/**
		 * Generate a unique tracking ID for seller onboarding.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function generate_tracking_id() {
			return 'charitable_' . get_current_blog_id() . '_' . time() . '_' . wp_rand( 1000, 9999 );
		}

		/**
		 * Get the webhook URL for this site.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_webhook_url() {
			return home_url( '/charitable-listener/paypal_commerce/' );
		}

		/**
		 * Get HTML for displaying the webhook URL in settings.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_webhook_url_html() {
			$webhook_url = $this->get_webhook_url();

			$html = '<code style="display: block; padding: 10px; background: #f0f0f0; margin-bottom: 10px;">' . esc_html( $webhook_url ) . '</code>';
			$html .= '<p class="description">' . __( 'Add this URL as a webhook in your PayPal Developer Dashboard. Subscribe to these events:', 'charitable' ) . '</p>';
			$html .= '<ul style="margin-left: 20px; list-style: disc;">';
			$html .= '<li><code>CHECKOUT.ORDER.APPROVED</code></li>';
			$html .= '<li><code>PAYMENT.CAPTURE.COMPLETED</code></li>';
			$html .= '<li><code>PAYMENT.CAPTURE.DENIED</code></li>';
			$html .= '<li><code>PAYMENT.CAPTURE.PENDING</code></li>';
			$html .= '<li><code>PAYMENT.CAPTURE.REFUNDED</code></li>';
			$html .= '<li><code>MERCHANT.ONBOARDING.COMPLETED</code></li>';
			$html .= '<li><code>MERCHANT.PARTNER-CONSENT.REVOKED</code></li>';
			$html .= '</ul>';

			// Show auto-create webhook button if the merchant is connected.
			if ( $this->is_seller_connected() ) {
				$mode        = $this->is_sandbox() ? 'sandbox' : 'live';
				$webhook_id  = $this->get_webhook_id();
				$create_url  = wp_nonce_url(
					add_query_arg(
						array(
							'charitable_paypal_create_webhook' => '1',
							'mode' => $mode,
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce' )
					),
					'charitable_paypal_create_webhook'
				);

				if ( empty( $webhook_id ) ) {
					$html .= '<p style="margin-top: 15px;">';
					$html .= '<a href="' . esc_url( $create_url ) . '" class="button">';
					$html .= __( 'Auto-Create Webhook', 'charitable' );
					$html .= '</a>';
					/* translators: %s: PayPal mode (sandbox or live). */
					$html .= ' <span class="description">' . sprintf( __( '(for %s mode)', 'charitable' ), $mode ) . '</span>';
					$html .= '</p>';
				} else {
					$recreate_url = wp_nonce_url(
						add_query_arg(
							array(
								'charitable_paypal_recreate_webhook' => '1',
								'mode' => $mode,
							),
							admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce' )
						),
						'charitable_paypal_recreate_webhook'
					);
					$html .= '<p style="margin-top: 10px; color: #46b450;"><span class="dashicons dashicons-yes"></span> ';
					/* translators: %s: PayPal mode (sandbox or live). */
					$html .= sprintf( __( 'Webhook configured for %s mode.', 'charitable' ), $mode );
					$html .= '</p>';
					$html .= '<p style="margin-top: 5px;">';
					$html .= '<a href="' . esc_url( $recreate_url ) . '" class="button button-small">';
					$html .= __( 'Re-Create Webhook', 'charitable' );
					$html .= '</a>';
					$html .= ' <span class="description">' . __( '(updates event types after plugin upgrade)', 'charitable' ) . '</span>';
					$html .= '</p>';
				}
			}

			return $html;
		}

		/**
		 * Create a webhook in PayPal.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error Webhook data or error.
		 */
		public function create_webhook() {
			$event_types = array(
				'CHECKOUT.ORDER.APPROVED',
				'PAYMENT.CAPTURE.COMPLETED',
				'PAYMENT.CAPTURE.DENIED',
				'PAYMENT.CAPTURE.PENDING',
				'PAYMENT.CAPTURE.REFUNDED',
				'PAYMENT.SALE.COMPLETED',
				'BILLING.SUBSCRIPTION.ACTIVATED',
				'BILLING.SUBSCRIPTION.CANCELLED',
				'BILLING.SUBSCRIPTION.SUSPENDED',
				'BILLING.SUBSCRIPTION.EXPIRED',
				'MERCHANT.ONBOARDING.COMPLETED',
				'MERCHANT.PARTNER-CONSENT.REVOKED',
				'CUSTOMER.MERCHANT-INTEGRATION.PRODUCT-SUBSCRIPTION-UPDATED',
				'VAULT.PAYMENT-TOKEN.CREATED',
			);

			// In middleware mode, delegate to middleware (platform credentials live server-side).
			if ( $this->is_middleware_mode() ) {
				$middleware = Charitable_PayPal_Middleware_Client::get_instance();
				return $middleware->create_webhook( $this->get_webhook_url(), $event_types );
			}

			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$webhook_data = array(
				'url'         => $this->get_webhook_url(),
				'event_types' => array_map( function( $name ) { return array( 'name' => $name ); }, $event_types ),
			);

			$response = wp_remote_post(
				$this->get_api_url() . '/v1/notifications/webhooks',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $access_token,
					),
					'body'    => wp_json_encode( $webhook_data ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				$error_message = isset( $body['message'] ) ? $body['message'] : __( 'Failed to create webhook.', 'charitable' );

				// Check for specific error - webhook URL already exists.
				if ( isset( $body['name'] ) && 'WEBHOOK_URL_ALREADY_EXISTS' === $body['name'] ) {
					$error_message = __( 'A webhook with this URL already exists. Please check your PayPal Developer Dashboard.', 'charitable' );
				}

				return new WP_Error( 'paypal_commerce_webhook_error', $error_message, $body );
			}

			return $body;
		}

		/**
		 * List webhooks from PayPal.
		 *
		 * @since  1.8.11
		 *
		 * @return array|WP_Error List of webhooks or error.
		 */
		public function list_webhooks() {
			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$response = wp_remote_get(
				$this->get_api_url() . '/v1/notifications/webhooks',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $access_token,
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body      = json_decode( wp_remote_retrieve_body( $response ), true );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				return new WP_Error(
					'paypal_commerce_webhook_error',
					isset( $body['message'] ) ? $body['message'] : __( 'Failed to list webhooks.', 'charitable' ),
					$body
				);
			}

			return $body;
		}

		/**
		 * Delete a webhook from PayPal.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $webhook_id Webhook ID to delete.
		 * @return bool|WP_Error True on success or error.
		 */
		public function delete_webhook( $webhook_id ) {
			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}

			$response = wp_remote_request(
				$this->get_api_url() . '/v1/notifications/webhooks/' . $webhook_id,
				array(
					'method'  => 'DELETE',
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $access_token,
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				return new WP_Error(
					'paypal_commerce_webhook_error',
					isset( $body['message'] ) ? $body['message'] : __( 'Failed to delete webhook.', 'charitable' ),
					$body
				);
			}

			return true;
		}

		/**
		 * Validate a donation submission for this gateway.
		 *
		 * @since  1.8.11
		 *
		 * @param  bool   $valid   Whether the submission is valid.
		 * @param  string $gateway Gateway ID.
		 * @param  array  $values  Submitted values.
		 * @return bool
		 */
		public static function validate_donation( $valid, $gateway, $values ) {
			if ( self::ID !== $gateway ) {
				return $valid;
			}

			$gateway_object = new self();

			// Check the merchant account is connected.
			if ( ! $gateway_object->is_seller_connected() ) {
				charitable_get_notices()->add_error(
					__( 'PayPal Commerce is not properly configured. Please contact the site administrator.', 'charitable' )
				);
				return false;
			}

			return $valid;
		}

		/**
		 * Process a donation.
		 *
		 * @since  1.8.11
		 *
		 * @param  mixed                         $return       Return value.
		 * @param  int                           $donation_id  Donation ID.
		 * @param  Charitable_Donation_Processor $processor    Processor object.
		 * @return array
		 */
		public static function process_donation( $return, $donation_id, $processor ) {
			$gateway  = new self();
			$donation = charitable_get_donation( $donation_id );

			// Create the PayPal order.
			$order = $gateway->create_order( $donation );

			if ( is_wp_error( $order ) ) {
				charitable_get_notices()->add_error(
					$order->get_error_message()
				);
				return array(
					'redirect' => charitable_get_permalink( 'donation_cancel_page' ),
					'safe'     => true,
				);
			}

			// Store the PayPal order ID.
			update_post_meta( $donation_id, '_paypal_commerce_order_id', $order['id'] );
			update_post_meta( $donation_id, '_paypal_commerce_order_status', $order['status'] );

			// Find the approval URL.
			$approval_url = '';
			foreach ( $order['links'] as $link ) {
				if ( 'payer-action' === $link['rel'] || 'approve' === $link['rel'] ) {
					$approval_url = $link['href'];
					break;
				}
			}

			if ( empty( $approval_url ) ) {
				charitable_get_notices()->add_error(
					__( 'Unable to get PayPal approval URL.', 'charitable' )
				);
				return array(
					'redirect' => charitable_get_permalink( 'donation_cancel_page' ),
					'safe'     => true,
				);
			}

			return array(
				'redirect' => $approval_url,
				'safe'     => false, // External redirect.
			);
		}

		/**
		 * Process a webhook/IPN from PayPal.
		 *
		 * @since  1.8.11
		 */
		public static function process_webhook() {
			$body    = file_get_contents( 'php://input' );
			$gateway = new self();

			// Verify webhook authenticity BEFORE parsing or logging any untrusted data.
			// This prevents log pollution and avoids acting on attacker-supplied input.
			if ( $gateway->is_middleware_mode() ) {
				// Middleware mode: verify HMAC signature added by the middleware server.
				// The middleware signs the forwarded payload with the per-site shared secret.
				$signature = isset( $_SERVER['HTTP_X_CHARITABLE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_CHARITABLE_SIGNATURE'] ) ) : '';
				if ( ! $gateway->verify_middleware_webhook_signature( $body, $signature ) ) {
					wp_die( 'Invalid signature', 'PayPal Commerce', array( 'response' => 401 ) );
				}
			} else {
				// Direct mode: verify PayPal's own signature. Reject if webhook ID is not configured.
				$webhook_id = $gateway->get_webhook_id();
				if ( empty( $webhook_id ) ) {
					wp_die( 'Not configured', 'PayPal Commerce', array( 'response' => 400 ) );
				}
				$headers  = $gateway->get_webhook_headers();
				$is_valid = $gateway->verify_webhook_signature( $headers, $body, $webhook_id );
				if ( ! $is_valid ) {
					wp_die( 'Invalid signature', 'PayPal Commerce', array( 'response' => 401 ) );
				}
			}

			// Signature verified — now safe to parse and process the payload.
			$event = json_decode( $body, true );

			$gateway->log_webhook( 'Incoming webhook', $event );

			if ( empty( $event['event_type'] ) ) {
				$gateway->log_webhook( 'Invalid webhook - no event_type', $event );
				wp_die( 'Invalid webhook', 'PayPal Commerce', array( 'response' => 400 ) );
			}

			// Replay protection. PayPal documents at-least-once webhook delivery and
			// retries on any non-2xx response or timeout. Without dedup, a retried
			// PAYMENT.SALE.COMPLETED produces phantom renewal donations; other state
			// mutations could double-fire. Transient TTL covers PayPal's full retry
			// window (~3 days) plus margin. Auto-expires; no cleanup needed.
			$event_id = isset( $event['id'] ) && is_string( $event['id'] ) ? sanitize_text_field( $event['id'] ) : '';
			if ( '' !== $event_id ) {
				$seen_key = 'charitable_paypal_wh_seen_' . md5( $event_id );
				if ( false !== get_transient( $seen_key ) ) {
					$gateway->log_webhook( 'Duplicate webhook event ignored', array(
						'event_id'   => $event_id,
						'event_type' => $event['event_type'],
					) );
					wp_die( 'Already processed', 'PayPal Commerce', array( 'response' => 200 ) );
				}
				set_transient( $seen_key, 1, 4 * DAY_IN_SECONDS );
			}

			$gateway->log_webhook( 'Processing event: ' . $event['event_type'], array( 'event_id' => $event_id ) );

			// Handle the event based on type.
			switch ( $event['event_type'] ) {
				// Payment events.
				case 'CHECKOUT.ORDER.APPROVED':
					$gateway->handle_order_approved( $event );
					break;

				case 'PAYMENT.CAPTURE.COMPLETED':
					$gateway->handle_capture_completed( $event );
					break;

				case 'PAYMENT.CAPTURE.DENIED':
					$gateway->handle_capture_denied( $event );
					break;

				case 'PAYMENT.CAPTURE.REFUNDED':
					$gateway->handle_capture_refunded( $event );
					break;

				case 'PAYMENT.CAPTURE.PENDING':
					$gateway->handle_capture_pending( $event );
					break;

				// Onboarding events.
				case 'MERCHANT.ONBOARDING.COMPLETED':
					$gateway->handle_merchant_onboarding_completed( $event );
					break;

				case 'MERCHANT.PARTNER-CONSENT.REVOKED':
					$gateway->handle_merchant_consent_revoked( $event );
					break;

				case 'CUSTOMER.MERCHANT-INTEGRATION.PRODUCT-SUBSCRIPTION-UPDATED':
					$gateway->handle_merchant_product_subscription_updated( $event );
					break;

				default:
					$gateway->log_webhook( 'Unhandled event type: ' . $event['event_type'] );
					break;
			}

			wp_die( 'OK', 'PayPal Commerce', array( 'response' => 200 ) );
		}

		/**
		 * Get the webhook ID for the current mode.
		 *
		 * @since  1.8.11
		 *
		 * @return string
		 */
		public function get_webhook_id() {
			$key = $this->is_sandbox() ? 'sandbox_webhook_id' : 'live_webhook_id';
			return $this->get_value( $key );
		}

		/**
		 * Get webhook headers from the current request.
		 *
		 * @since  1.8.11
		 *
		 * @return array
		 */
		protected function get_webhook_headers() {
			return array(
				'PAYPAL-AUTH-ALGO'         => isset( $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ) : '',
				'PAYPAL-CERT-URL'          => isset( $_SERVER['HTTP_PAYPAL_CERT_URL'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_CERT_URL'] ) : '',
				'PAYPAL-TRANSMISSION-ID'   => isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ) : '',
				'PAYPAL-TRANSMISSION-SIG'  => isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ) : '',
				'PAYPAL-TRANSMISSION-TIME' => isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ) : '',
			);
		}

		/**
		 * Verify a webhook forwarded by the Charitable middleware server.
		 *
		 * The middleware signs the JSON body with the per-site shared secret using
		 * HMAC-SHA256 and includes the signature as "sha256=<hex>" in the
		 * X-Charitable-Signature header. This prevents spoofed webhook events from
		 * anyone who knows the WP webhook URL.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $body      Raw JSON body from php://input.
		 * @param  string $signature Value of the X-Charitable-Signature header.
		 * @return bool
		 */
		public function verify_middleware_webhook_signature( $body, $signature ) {
			if ( empty( $signature ) || strpos( $signature, 'sha256=' ) !== 0 ) {
				return false;
			}
			$secret = $this->get_middleware_secret();
			if ( empty( $secret ) ) {
				return false;
			}
			$expected = 'sha256=' . hash_hmac( 'sha256', $body, $secret );
			return hash_equals( $expected, $signature );
		}

		/**
		 * Verify a webhook signature using PayPal's verification API.
		 *
		 * @since  1.8.11
		 *
		 * @param  array  $headers    Webhook headers.
		 * @param  string $body       Raw webhook body.
		 * @param  string $webhook_id The webhook ID.
		 * @return bool
		 */
		public function verify_webhook_signature( $headers, $body, $webhook_id ) {
			$access_token = $this->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				$this->log_webhook( 'Failed to get access token for signature verification' );
				return false;
			}

			$verification_data = array(
				'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'],
				'cert_url'          => $headers['PAYPAL-CERT-URL'],
				'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'],
				'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'],
				'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'],
				'webhook_id'        => $webhook_id,
				'webhook_event'     => json_decode( $body, true ),
			);

			$response = wp_remote_post(
				$this->get_api_url() . '/v1/notifications/verify-webhook-signature',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $access_token,
					),
					'body'    => wp_json_encode( $verification_data ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				$this->log_webhook( 'Signature verification request failed', array( 'error' => $response->get_error_message() ) );
				return false;
			}

			$result = json_decode( wp_remote_retrieve_body( $response ), true );

			return isset( $result['verification_status'] ) && 'SUCCESS' === $result['verification_status'];
		}

		/**
		 * Log a webhook event.
		 *
		 * @since  1.8.11
		 *
		 * @param  string $message Log message.
		 * @param  array  $data    Additional data.
		 */
		protected function log_webhook( $message, $data = array() ) {
			// Local diagnostic trace — distinct slug from `paypal.webhook.received`
			// (which is the structured row written below) so the dedup doesn't
			// drop one when both fire for the same request.
			Charitable_PayPal_Logger::debug(
				'paypal.webhook.local_trace',
				$message,
				is_array( $data ) ? $data : array( 'data' => $data )
			);

			// Also store in a transient for admin viewing (last 50 events).
			// NOTE: Kept in 1.8.15 because the existing admin webhook viewer reads from
			// this transient. Slated for removal in 1.8.16 once the structured-log
			// source filter is established as the primary surface. Until that
			// removal lands, this transient is LOAD-BEARING — the structured log
			// below does not replace it (yet); both writes are intentional.
			$log = get_transient( 'charitable_paypal_commerce_webhook_log' );
			if ( ! is_array( $log ) ) {
				$log = array();
			}

			array_unshift( $log, array(
				'time'    => current_time( 'mysql' ),
				'message' => $message,
				'data'    => $data,
			) );

			// Keep only last 50 entries.
			$log = array_slice( $log, 0, 50 );

			set_transient( 'charitable_paypal_commerce_webhook_log', $log, DAY_IN_SECONDS );

			// Additionally write to the structured logger so the row appears in the
			// Tools → Logs UI alongside other gateway events. `event_type` is
			// optional context; redact()/wp_json_encode() handle a missing key
			// fine, so we don't need to pre-fill it.
			$context = is_array( $data ) ? $data : array( 'data' => $data );

			Charitable_PayPal_Logger::info(
				'paypal.webhook.received',
				$message,
				$context
			);
		}

		/**
		 * Handle capture pending webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_capture_pending( $event ) {
			$capture_id = $event['resource']['id'] ?? '';

			$donations = get_posts(
				array(
					'post_type'  => Charitable::DONATION_POST_TYPE,
					'meta_key'   => '_paypal_commerce_capture_id',
					'meta_value' => $capture_id,
					'fields'     => 'ids',
				)
			);

			if ( empty( $donations ) ) {
				return;
			}

			$donation = charitable_get_donation( $donations[0] );
			$donation->update_status( 'charitable-pending' );

			$this->log_webhook( 'Donation marked as pending', array( 'donation_id' => $donations[0] ) );
		}

		/**
		 * Handle merchant onboarding completed webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_merchant_onboarding_completed( $event ) {
			$merchant_id = $event['resource']['merchant_id'] ?? '';
			$tracking_id = $event['resource']['tracking_id'] ?? '';

			$this->log_webhook( 'Merchant onboarding completed', array(
				'merchant_id' => $merchant_id,
				'tracking_id' => $tracking_id,
			) );

			// Scope to OUR connected merchant. Sister handlers (consent_revoked,
			// product_subscription_updated) already enforce this. The OAuth return
			// flow is what populates seller_merchant_id — the webhook is just async
			// confirmation. Skipping until then is safe; the next admin page load
			// refreshes seller status anyway.
			$connected_merchant = $this->get_seller_merchant_id();

			if ( empty( $merchant_id ) || empty( $connected_merchant ) || $merchant_id !== $connected_merchant ) {
				$this->log_webhook( 'Onboarding webhook ignored: merchant_id does not match connected merchant', array(
					'event_merchant_id'     => $merchant_id,
					'connected_merchant_id' => $connected_merchant,
				) );
				return;
			}

			// Refresh the seller status.
			$status = $this->get_seller_status( $merchant_id );
			if ( ! is_wp_error( $status ) ) {
				$readiness = $this->check_seller_readiness( $status );

				// Store the readiness status.
				$mode = $this->is_sandbox() ? 'sandbox' : 'live';
				set_transient( 'charitable_paypal_seller_status_' . $mode, $readiness, HOUR_IN_SECONDS );
			}
		}

		/**
		 * Handle merchant consent revoked webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_merchant_consent_revoked( $event ) {
			$merchant_id = $event['resource']['merchant_id'] ?? '';

			$this->log_webhook( 'Merchant consent revoked', array(
				'merchant_id' => $merchant_id,
			) );

			// Check if this is our connected merchant.
			$connected_merchant = $this->get_seller_merchant_id();

			if ( $merchant_id && $merchant_id === $connected_merchant ) {
				// Clear the stored merchant ID since consent was revoked.
				$mode         = $this->is_sandbox() ? 'sandbox' : 'live';
				$settings_key = $mode . '_seller_merchant_id';

				$gateway_settings = get_option( 'charitable_settings', array() );
				if ( isset( $gateway_settings['gateways_paypal_commerce'][ $settings_key ] ) ) {
					unset( $gateway_settings['gateways_paypal_commerce'][ $settings_key ] );
					update_option( 'charitable_settings', $gateway_settings );
				}

				// Store a notice for admin.
				set_transient(
					'charitable_paypal_commerce_consent_revoked',
					sprintf(
						/* translators: %s: merchant ID */
						__( 'PayPal merchant %s has revoked consent. Please reconnect your PayPal account.', 'charitable' ),
						$merchant_id
					),
					WEEK_IN_SECONDS
				);
			}
		}

		/**
		 * Handle merchant product subscription updated webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_merchant_product_subscription_updated( $event ) {
			$merchant_id = $event['resource']['merchant_id'] ?? '';

			$this->log_webhook( 'Merchant product subscription updated', array(
				'merchant_id' => $merchant_id,
				'resource'    => $event['resource'] ?? array(),
			) );

			// Refresh seller status if this is our merchant.
			$connected_merchant = $this->get_seller_merchant_id();

			if ( $merchant_id && $merchant_id === $connected_merchant ) {
				$status = $this->get_seller_status( $merchant_id );
				if ( ! is_wp_error( $status ) ) {
					$readiness = $this->check_seller_readiness( $status );
					$mode      = $this->is_sandbox() ? 'sandbox' : 'live';
					set_transient( 'charitable_paypal_seller_status_' . $mode, $readiness, HOUR_IN_SECONDS );
				}
			}
		}

		/**
		 * Handle order approved webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_order_approved( $event ) {
			$order_id = $event['resource']['id'] ?? '';

			if ( empty( $order_id ) ) {
				return;
			}

			// Find donation by PayPal order ID.
			$donations = get_posts(
				array(
					'post_type'  => Charitable::DONATION_POST_TYPE,
					'meta_key'   => '_paypal_commerce_order_id',
					'meta_value' => $order_id,
					'fields'     => 'ids',
				)
			);

			if ( empty( $donations ) ) {
				return;
			}

			$donation_id = $donations[0];

			// Auto-capture the order.
			$capture_result = $this->capture_order( $order_id );

			if ( is_wp_error( $capture_result ) ) {
				update_post_meta( $donation_id, '_paypal_commerce_error', $capture_result->get_error_message() );
				return;
			}

			// Store capture details.
			$capture = $capture_result['purchase_units'][0]['payments']['captures'][0] ?? array();

			if ( ! empty( $capture['id'] ) ) {
				update_post_meta( $donation_id, '_paypal_commerce_capture_id', $capture['id'] );
			}

			// Update donation status.
			$donation = charitable_get_donation( $donation_id );
			$donation->update_status( 'charitable-completed' );
		}

		/**
		 * Find a donation by its PayPal sale_id.
		 *
		 * Used for idempotency on PAYMENT.SALE.COMPLETED. Bypasses get_posts
		 * because Charitable's custom post statuses (charitable-completed)
		 * are not matched by post_status='any'.
		 *
		 * @since 1.8.11
		 *
		 * @param string $sale_id PayPal sale ID.
		 * @return int|false Donation post ID, or false if none.
		 */
		protected function find_donation_by_sale_id( $sale_id ) {
			global $wpdb;

			$post_type = Charitable::DONATION_POST_TYPE;

			$id = $wpdb->get_var( $wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_paypal_sale_id'
				 AND pm.meta_value = %s
				 AND p.post_type = %s
				 LIMIT 1",
				$sale_id,
				$post_type
			) );

			return $id ? (int) $id : false;
		}

		/**
		 * Handle capture completed webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_capture_completed( $event ) {
			$capture_id = $event['resource']['id'] ?? '';

			// Find donation by capture ID.
			$donations = get_posts(
				array(
					'post_type'  => Charitable::DONATION_POST_TYPE,
					'meta_key'   => '_paypal_commerce_capture_id',
					'meta_value' => $capture_id,
					'fields'     => 'ids',
				)
			);

			if ( empty( $donations ) ) {
				return;
			}

			$donation = charitable_get_donation( $donations[0] );
			$donation->update_status( 'charitable-completed' );

			// Store payer info.
			$payer_email = $event['resource']['payer']['email_address'] ?? '';
			if ( $payer_email ) {
				update_post_meta( $donations[0], '_paypal_commerce_payer_email', $payer_email );
			}
		}

		/**
		 * Handle capture denied webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_capture_denied( $event ) {
			$capture_id = $event['resource']['id'] ?? '';

			$donations = get_posts(
				array(
					'post_type'  => Charitable::DONATION_POST_TYPE,
					'meta_key'   => '_paypal_commerce_capture_id',
					'meta_value' => $capture_id,
					'fields'     => 'ids',
				)
			);

			if ( empty( $donations ) ) {
				return;
			}

			$donation = charitable_get_donation( $donations[0] );
			$donation->update_status( 'charitable-failed' );
		}

		/**
		 * Handle capture refunded webhook.
		 *
		 * @since  1.8.11
		 *
		 * @param  array $event Webhook event data.
		 */
		protected function handle_capture_refunded( $event ) {
			$capture_id = $event['resource']['id'] ?? '';

			$donations = get_posts(
				array(
					'post_type'  => Charitable::DONATION_POST_TYPE,
					'meta_key'   => '_paypal_commerce_capture_id',
					'meta_value' => $capture_id,
					'fields'     => 'ids',
				)
			);

			if ( empty( $donations ) ) {
				return;
			}

			$donation = charitable_get_donation( $donations[0] );
			$donation->update_status( 'charitable-refunded' );
		}

		/**
		 * Restore a donation's status when a PayPal refund attempt fails.
		 *
		 * Charitable core (class-charitable-donation-meta-boxes.php:change_donation_status)
		 * updates the donation status to 'charitable-refunded' BEFORE firing the
		 * gateway refund action. If the gateway-side refund fails (e.g., insufficient
		 * funds, network error), the donation is left in REFUNDED status while the
		 * PayPal capture is still COMPLETED — a state mismatch.
		 *
		 * This helper restores the donation to 'charitable-completed' so WP and
		 * PayPal stay in sync. The failure log entry separately added by the caller
		 * makes the failure visible to the admin.
		 *
		 * @since  1.8.11
		 *
		 * @param  int $donation_id Donation ID.
		 * @return void
		 */
		protected static function restore_status_on_refund_failure( $donation_id ) {
			$current = get_post_status( $donation_id );
			if ( 'charitable-refunded' !== $current ) {
				return;
			}

			// Unset the POST keys that drive change_donation_status. Without this,
			// the wp_update_post() below re-fires save_post → save_donation() →
			// charitable_get_donation_actions()->do_action( 'change_status_to_charitable-refunded' ),
			// which re-runs update_status() and process_refund() against PayPal in
			// an infinite loop until the request times out.
			unset( $_POST['charitable_donation_action'] );
			unset( $_POST['gateway_refund'] );

			wp_update_post(
				array(
					'ID'          => $donation_id,
					'post_status' => 'charitable-completed',
				)
			);
			$donation = charitable_get_donation( $donation_id );
			if ( $donation ) {
				$donation->log()->add(
					__( 'Donation status reverted to Completed because the gateway refund did not complete.', 'charitable' )
				);
			}
		}

		/**
		 * Process a refund.
		 *
		 * Dual logging is intentional throughout this method:
		 *
		 * - `$donation->log()->add( ... )` writes to the per-donation log that
		 *   renders in the donation admin meta box. Donors and admins look at
		 *   that surface to see what happened to a specific donation; we MUST
		 *   keep these calls intact even after the structured logger ships.
		 * - `Charitable_PayPal_Logger::info|error( ... )` writes structured
		 *   rows to Tools → Logs for support / triage workflows that span
		 *   donations (filter by event, by paypal-debug-id, etc.).
		 *
		 * The two surfaces are complementary, not redundant. Don't "clean up"
		 * one in favor of the other without coordinating with admin UI work.
		 *
		 * @since  1.8.11
		 *
		 * @param  int $donation_id Donation ID.
		 * @return bool
		 */
		public static function process_refund( $donation_id ) {
			// Re-entry guard. Belt-and-braces with the $_POST unset in
			// restore_status_on_refund_failure() — if anything else triggers a
			// second pass for the same donation in the same request, skip it.
			static $processing = array();
			if ( in_array( (int) $donation_id, $processing, true ) ) {
				return false;
			}
			$processing[] = (int) $donation_id;

			$gateway    = new self();
			$capture_id = get_post_meta( $donation_id, '_paypal_commerce_capture_id', true );

			$donation = charitable_get_donation( $donation_id );

			// Guard against a deleted/invalid donation. All subsequent code paths
			// assume $donation is a Charitable_Donation; without this check, a
			// stale donation_id reaching this hook fatals mid-refund and leaves
			// the donation post in 'charitable-refunded' with no real PayPal
			// refund — a state mismatch that requires manual cleanup.
			if ( ! $donation ) {
				Charitable_PayPal_Logger::error(
					'paypal.refund.invalid_donation',
					'Refund called with invalid donation ID; aborting',
					array( 'donation_id' => (int) $donation_id )
				);
				self::restore_status_on_refund_failure( $donation_id );
				return false;
			}

			/* Subscription-mode donations don't go through the Orders API
			 * capture flow, so they never get a `_paypal_commerce_capture_id`
			 * postmeta written at donation time. PayPal still produces a
			 * refundable transaction id when the first billing cycle is
			 * charged — recover it here on demand by listing the
			 * subscription's transactions. The lookup is cached as
			 * postmeta so subsequent refund actions on the same donation
			 * don't re-hit the API. */
			if ( empty( $capture_id ) ) {
				$capture_id = self::resolve_subscription_capture_id( $donation_id );
			}

			if ( empty( $capture_id ) ) {
				$donation->log()->add( __( 'PayPal refund failed: No capture ID found.', 'charitable' ) );
				self::restore_status_on_refund_failure( $donation_id );
				return false;
			}

			// Middleware mode: delegate refund to middleware.
			if ( $gateway->is_middleware_mode() ) {
				$amount   = $donation->get_total_donation_amount();
				$currency = charitable_get_currency();

				Charitable_PayPal_Logger::info(
					'paypal.refund.requested',
					'Refund requested via middleware',
					array(
						'capture_id' => $capture_id,
						'amount'     => $amount,
						'currency'   => $currency,
					),
					array(
						'donation_id' => (int) $donation_id,
					)
				);

				$result = Charitable_PayPal_Middleware_Client::get_instance()->refund( $capture_id, (float) $amount, $currency );

				if ( is_wp_error( $result ) ) {
					$donation->log()->add(
						sprintf(
							/* translators: %s: error message */
							__( 'PayPal refund failed: %s', 'charitable' ),
							$result->get_error_message()
						)
					);

					$err_data = $result->get_error_data();
					Charitable_PayPal_Logger::error(
						'paypal.refund.failed',
						$result->get_error_message(),
						array(
							'capture_id'      => $capture_id,
							'amount'          => $amount,
							'currency'        => $currency,
							'error_code'      => $result->get_error_code(),
							'http_code'       => is_array( $err_data ) && isset( $err_data['http_code'] ) ? (int) $err_data['http_code'] : null,
							'paypal_debug_id' => is_array( $err_data ) && isset( $err_data['debug_id'] ) ? $err_data['debug_id'] : null,
						),
						array(
							'donation_id' => (int) $donation_id,
						)
					);

					self::restore_status_on_refund_failure( $donation_id );
					return false;
				}

				$refund_id = isset( $result['refund_id'] ) ? $result['refund_id'] : ( isset( $result['id'] ) ? $result['id'] : '' );
				if ( ! empty( $refund_id ) ) {
					update_post_meta( $donation_id, '_paypal_commerce_refund_id', $refund_id );
					$donation->log()->add(
						sprintf(
							/* translators: %s: PayPal refund ID */
							__( 'Refund processed via PayPal Commerce Platform (middleware). Refund ID: %s', 'charitable' ),
							$refund_id
						)
					);

					Charitable_PayPal_Logger::info(
						'paypal.refund.succeeded',
						'Refund processed',
						array(
							'capture_id' => $capture_id,
							'refund_id'  => $refund_id,
							'amount'     => $amount,
							'currency'   => $currency,
						),
						array(
							'donation_id' => (int) $donation_id,
						)
					);
				}

				return true;
			}

			$access_token = $gateway->get_access_token();

			if ( is_wp_error( $access_token ) ) {
				$donation->log()->add( __( 'PayPal refund failed: Could not get access token.', 'charitable' ) );
				self::restore_status_on_refund_failure( $donation_id );
				return false;
			}

			// Deterministic PayPal-Request-Id so that an admin double-click within
			// PayPal's 24h idempotency window returns the cached prior response
			// instead of attempting a duplicate refund. A donation can only ever
			// be refunded once anyway, so coupling the key to donation_id is safe.
			$response = wp_remote_post(
				$gateway->get_api_url() . '/v2/payments/captures/' . $capture_id . '/refund',
				array(
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token,
						'PayPal-Request-Id'             => 'charitable_refund_' . (int) $donation_id,
						'PayPal-Partner-Attribution-Id' => $gateway->get_bn_code(),
					),
					'body'    => wp_json_encode(
						array(
							'amount' => array(
								'value'         => number_format( $donation->get_total_donation_amount(), 2, '.', '' ),
								'currency_code' => charitable_get_currency(),
							),
						)
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				$donation->log()->add(
					sprintf(
						/* translators: %s: error message */
						__( 'PayPal refund failed: %s', 'charitable' ),
						$response->get_error_message()
					)
				);

				Charitable_PayPal_Logger::error(
					'paypal.refund.failed',
					$response->get_error_message(),
					array(
						'capture_id' => $capture_id,
						'error_code' => $response->get_error_code(),
					),
					array(
						'donation_id' => (int) $donation_id,
					)
				);

				self::restore_status_on_refund_failure( $donation_id );
				return false;
			}

			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $http_code >= 400 ) {
				$error_body  = json_decode( wp_remote_retrieve_body( $response ), true );
				$error_name  = ! empty( $error_body['name'] ) ? $error_body['name'] : '';
				$error_msg   = ! empty( $error_body['message'] ) ? $error_body['message'] : "HTTP $http_code";

				// Provide a clear message when the seller has insufficient funds to cover the refund.
				if ( 'CANNOT_BE_REFUNDED' === $error_name || false !== strpos( $error_msg, 'INSUFFICIENT_FUNDS' ) ) {
					$error_msg = __( 'Refund failed: the seller account has insufficient balance to cover this refund. Please ensure funds are available in the PayPal account and try again.', 'charitable' );
				}

				$donation->log()->add(
					sprintf(
						/* translators: %s: error message */
						__( 'PayPal refund failed: %s', 'charitable' ),
						$error_msg
					)
				);

				Charitable_PayPal_Logger::error(
					'paypal.refund.failed',
					$error_msg,
					array(
						'capture_id'      => $capture_id,
						'http_code'       => $http_code,
						'error_name'      => $error_name,
						'paypal_debug_id' => wp_remote_retrieve_header( $response, 'paypal-debug-id' ),
					),
					array(
						'donation_id' => (int) $donation_id,
					)
				);

				self::restore_status_on_refund_failure( $donation_id );
				return false;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			// Store refund ID and log to donation.
			if ( ! empty( $body['id'] ) ) {
				update_post_meta( $donation_id, '_paypal_commerce_refund_id', $body['id'] );

				$donation->log()->add(
					sprintf(
						/* translators: %s: PayPal refund ID */
						__( 'Refund processed via PayPal Commerce Platform. Refund ID: %s', 'charitable' ),
						$body['id']
					)
				);

				Charitable_PayPal_Logger::info(
					'paypal.refund.succeeded',
					'Refund processed (direct API)',
					array(
						'capture_id'      => $capture_id,
						'refund_id'       => $body['id'],
						'paypal_debug_id' => wp_remote_retrieve_header( $response, 'paypal-debug-id' ),
					),
					array(
						'donation_id' => (int) $donation_id,
					)
				);
			}

			return true;
		}

		/**
		 * Get the gateway fields for the donation form.
		 *
		 * @since  1.8.11
		 *
		 * @param  array                $fields  Form fields.
		 * @param  Charitable_Gateway   $gateway Gateway object.
		 * @return array
		 */
		public static function get_gateway_fields( $fields, $gateway ) {
			if ( self::ID !== $gateway::get_gateway_id() ) {
				return $fields;
			}

			$gateway_instance = $gateway;

			// Add Fastlane container when Fastlane is available for guests.
			if ( method_exists( $gateway_instance, 'is_fastlane_available' ) && $gateway_instance->is_fastlane_available() ) {
				$behavior  = $gateway_instance->get_fastlane_behavior();
				$show      = ( 'all_users' === $behavior ) || ( ! is_user_logged_in() );
				if ( $show ) {
					$fields['fastlane_container'] = array(
						'type'     => 'content',
						'content'  => '<div id="charitable-fastlane-container"></div>',
						'priority' => 0.5,
					);
				}
			}

			return array_merge(
				$fields,
				array(
					'paypal_commerce_container' => array(
						'type'     => 'content',
						'content'  => '<div id="charitable-paypal-commerce-container"></div>',
						'priority' => 2,
					),
				)
			);
		}

		/**
		 * Check if the system should gracefully degrade from Fastlane.
		 *
		 * This method provides a central place to check if Fastlane should be
		 * disabled due to various error conditions or system states.
		 *
		 * @since  1.8.11
		 *
		 * @return bool True if should gracefully degrade from Fastlane.
		 */
		public function should_degrade_from_fastlane() {
			// If Fastlane is not enabled, always degrade.
			if ( ! $this->get_value( 'enable_fastlane' ) ) {
				return true;
			}

			// If seller is not connected, degrade.
			if ( ! $this->is_seller_connected() ) {
				return true;
			}

			// Check if there have been recent failures.
			$mode = $this->is_sandbox() ? 'sandbox' : 'live';
			$failure_count = get_transient( 'charitable_paypal_fastlane_failures_' . $mode );

			// If we've had 3 or more failures in the last hour, degrade.
			if ( $failure_count && $failure_count >= 3 ) {
				Charitable_PayPal_Logger::warn(
					'paypal.capability.fastlane_degraded',
					'Degrading from Fastlane due to repeated failures',
					array(
						'failure_count' => (int) $failure_count,
					)
				);
				return true;
			}

			return false;
		}

		/**
		 * Record a Fastlane failure for degradation tracking.
		 *
		 * @since  1.8.11
		 */
		public function record_fastlane_failure() {
			$mode = $this->is_sandbox() ? 'sandbox' : 'live';
			$failure_count = get_transient( 'charitable_paypal_fastlane_failures_' . $mode ) ?: 0;
			$failure_count++;

			set_transient( 'charitable_paypal_fastlane_failures_' . $mode, $failure_count, HOUR_IN_SECONDS );

			Charitable_PayPal_Logger::debug(
				'paypal.capability.fastlane_failure_recorded',
				'Fastlane failure recorded',
				array(
					'failure_count' => (int) $failure_count,
				)
			);
		}

		/**
		 * Clear Fastlane failure tracking.
		 *
		 * @since  1.8.11
		 */
		public function clear_fastlane_failures() {
			$mode = $this->is_sandbox() ? 'sandbox' : 'live';
			delete_transient( 'charitable_paypal_fastlane_failures_' . $mode );

			Charitable_PayPal_Logger::debug(
				'paypal.capability.fastlane_failure_cleared',
				'Fastlane failure tracking cleared'
			);
		}

		/* ──────────────────────────────────────────────
		 * Middleware Helper Methods
		 *
		 * These private methods build order payloads and delegate to the
		 * middleware client. Platform fees are injected server-side by the
		 * middleware, so get_platform_fee_instruction() is not called here.
		 * ────────────────────────────────────────────── */

		/**
		 * Create a standard order via middleware.
		 *
		 * @since  1.8.11
		 *
		 * @param  float  $amount         Donation amount.
		 * @param  string $currency       Currency code.
		 * @param  string $campaign_name  Campaign name.
		 * @param  string $reference_id   Reference ID.
		 * @param  string $payment_method Payment method.
		 * @param  string $payer_email    Buyer email for prefill.
		 * @param  string $fastlane_token Single-use token from FastlanePaymentComponent.
		 * @return array|WP_Error
		 */
		private function create_order_via_middleware( $amount, $currency, $campaign_name = '', $reference_id = '', $payment_method = '', $payer_email = '', $fastlane_token = '' ) {
			if ( empty( $campaign_name ) ) {
				$campaign_name = __( 'Donation', 'charitable' );
			}
			if ( empty( $reference_id ) ) {
				$reference_id = 'order_' . time() . '_' . wp_rand( 1000, 9999 );
			}

			$order_data = array(
				'intent'         => 'CAPTURE',
				'purchase_units' => array(
					array(
						'reference_id'    => $reference_id,
						/* translators: %s: campaign name */
						'description'     => sprintf( __( 'Donation to %s', 'charitable' ), $campaign_name ),
						'soft_descriptor' => 'DONATION',
						'payee'           => array(
							'merchant_id' => $this->get_seller_merchant_id(),
						),
						'amount'          => array(
							'currency_code' => $currency,
							'value'         => number_format( $amount, 2, '.', '' ),
							'breakdown'     => array(
								'item_total' => array(
									'currency_code' => $currency,
									'value'         => number_format( $amount, 2, '.', '' ),
								),
							),
						),
						'items'           => array(
							array(
								/* translators: %s: campaign name */
								'name'        => sprintf( __( 'Donation to %s', 'charitable' ), $campaign_name ),
								'description' => __( 'Charitable donation', 'charitable' ),
								'quantity'    => '1',
								'unit_amount' => array(
									'currency_code' => $currency,
									'value'         => number_format( $amount, 2, '.', '' ),
								),
								// Apple Pay and Google Pay do not support 'DONATION' category.
								'category'    => in_array( $payment_method, array( 'applepay', 'googlepay' ), true ) ? 'PHYSICAL_GOODS' : 'DONATION',
							),
						),
					),
				),
			);

			// Prefill payer email when available (reduces buyer data entry).
			if ( ! empty( $payer_email ) ) {
				$order_data['payer'] = array(
					'email_address' => $payer_email,
				);
			}

			// For PayPal wallet payments, add experience_context with Pay Now and brand name.
			if ( 'paypal' === $payment_method ) {
				$brand_name = ! empty( $campaign_name ) ? $campaign_name : get_bloginfo( 'name' );
				$order_data['payment_source'] = array(
					'paypal' => array(
						'experience_context' => array(
							'user_action'         => 'PAY_NOW',
							'brand_name'          => $brand_name,
							'return_url'          => home_url( '/' ),
							'cancel_url'          => home_url( '/' ),
							'shipping_preference' => 'NO_SHIPPING',
						),
					),
				);
			}

			// For Fastlane payments, use single_use_token from FastlanePaymentComponent.
			if ( 'fastlane' === $payment_method && ! empty( $fastlane_token ) ) {
				$order_data['payment_source'] = array(
					'card' => array(
						'single_use_token' => $fastlane_token,
					),
				);
			}

			return Charitable_PayPal_Middleware_Client::get_instance()->create_order( $order_data );
		}

	}

endif;
