<?php
/**
 * PayPal Commerce Gateway Hooks
 *
 * Registers all actions and filters for the PayPal Commerce gateway.
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

/**
 * Get the current Charitable license key (charitable-v2 plan).
 *
 * Returns the active license key string, or an empty string for Lite installs
 * or if no license is stored. The middleware uses presence/absence of this
 * value to gate platform fee injection (Lite → fees apply, Pro → no fees).
 *
 * Defensive guard: middleware validates `nullable|string|max:128`. Charitable
 * EDD-style keys are typically 32 chars, but if for any reason the stored key
 * exceeds 128, return empty (treat as Lite) rather than send a request the
 * middleware will reject with a 422.
 *
 * @since  1.8.11
 *
 * @return string
 */
function charitable_paypal_get_license_key() {
	$settings = get_option( 'charitable_settings', array() );
	if ( empty( $settings['licenses']['charitable-v2']['license'] ) ) {
		return '';
	}
	$key = (string) $settings['licenses']['charitable-v2']['license'];
	if ( strlen( $key ) > 128 ) {
		if ( charitable_is_debug( 'paypal' ) ) {
			error_log( 'PayPal Commerce: license_key exceeds 128 chars (' . strlen( $key ) . '); treating as missing.' );
		}
		return '';
	}
	return $key;
}

/**
 * Sync Charitable license_key to PayPal middleware when the license changes.
 *
 * Why: middleware uses customer.license_key to gate platform fee injection.
 * If a user activates Pro after PayPal onboarding (the typical flow), the
 * middleware needs to learn about the new license. Conversely if they
 * deactivate, fees should resume.
 *
 * Fires on `update_option_charitable_settings`. Compares old vs new license
 * key for the charitable-v2 plan and pushes any change to the middleware.
 *
 * @since  1.8.11
 *
 * @param  mixed $old_value Previous option value.
 * @param  mixed $new_value New option value.
 * @return void
 */
function charitable_paypal_sync_license_to_middleware( $old_value, $new_value ) {
	$old_key = ( is_array( $old_value ) && ! empty( $old_value['licenses']['charitable-v2']['license'] ) )
		? (string) $old_value['licenses']['charitable-v2']['license']
		: '';
	$new_key = ( is_array( $new_value ) && ! empty( $new_value['licenses']['charitable-v2']['license'] ) )
		? (string) $new_value['licenses']['charitable-v2']['license']
		: '';

	/* Mirror the 128-char guard from charitable_paypal_get_license_key().
	 * Without this, an oversized stored value (corrupted option, third-party
	 * mutation) would be POSTed raw to the middleware which 422s on
	 * `nullable|string|max:128`, the failure writes a pending-retry transient
	 * carrying the oversized key, and `admin_init` then re-fires that bad
	 * request on every admin pageload for 24 hours. Treat oversized as Lite
	 * here to short-circuit that retry-storm. */
	if ( strlen( $old_key ) > 128 ) {
		$old_key = '';
	}
	if ( strlen( $new_key ) > 128 ) {
		$new_key = '';
	}

	if ( $old_key === $new_key ) {
		return;
	}

	if ( ! class_exists( 'Charitable_Gateway_Paypal_Commerce' ) || ! class_exists( 'Charitable_PayPal_Middleware_Client' ) ) {
		return;
	}

	$gateway = new Charitable_Gateway_Paypal_Commerce();
	if ( ! $gateway->is_middleware_mode() ) {
		return;
	}

	// Sync to BOTH connected modes (sandbox + live) so the freemium gate
	// stays aligned regardless of which mode the gateway is currently in.
	// A user might have onboarded in sandbox first, switched to live, then
	// activated Pro — the live middleware also needs the license_key. Without
	// this dual-write, switching modes would leave one middleware out of sync.
	// Self-healing race note: if a license is activated during the PayPal
	// onboarding flow (e.g., user switches browser tabs), the in-flight
	// exchange_credentials() call may carry the stale empty key. That's OK —
	// the onboarding handler saves merchant_id before exchange_credentials,
	// so by the time this hook fires, is_seller_connected() returns true and
	// we'll push the corrected license_key here. Don't reorder those steps.
	charitable_paypal_send_license_to_connected_modes( $new_key );
}

/**
 * Push a license_key to every connected PayPal Commerce mode (sandbox + live).
 *
 * Iterates over both modes; for each one with a seller_merchant_id+secret set,
 * makes a direct HTTP POST to that mode's middleware /customers/update endpoint
 * (bypassing the mode-bound middleware client singleton). This avoids
 * temporarily mutating `sandbox_mode` setting which would re-fire the
 * `update_option_charitable_settings` hook recursively.
 *
 * Auth flow per mode (mirrors middleware client):
 *   1. POST /oauth/access-token with Basic merchant_id:secret → bearer token
 *   2. POST /customers/update with Bearer token + {license_key}
 *
 * @since  1.8.11
 *
 * @param  string $license_key Empty string clears the field on the middleware.
 * @param  string $only_mode   Optional. When 'sandbox' or 'live', restrict the
 *                             push to that single mode (used by the retry path
 *                             so a pending sync for one mode never overwrites
 *                             a different pending key on the other mode). Pass
 *                             '' (default) to push to every connected mode.
 * @return void
 */
function charitable_paypal_send_license_to_connected_modes( $license_key, $only_mode = '' ) {
	$settings = get_option( 'charitable_settings', array() );
	$gw       = $settings['gateways_paypal_commerce'] ?? array();

	$mode_configs = array();
	foreach ( array( 'sandbox', 'live' ) as $mode ) {
		if ( '' !== $only_mode && $mode !== $only_mode ) {
			continue;
		}
		$merchant_id = $gw[ "{$mode}_seller_merchant_id" ] ?? '';
		$secret      = $gw[ "{$mode}_middleware_secret" ] ?? '';
		if ( empty( $merchant_id ) || empty( $secret ) ) {
			continue;
		}
		$mode_configs[ $mode ] = array(
			'merchant_id' => $merchant_id,
			'secret'      => $secret,
			'base_url'    => 'sandbox' === $mode
				? ( defined( 'CHARITABLE_PAYPAL_MIDDLEWARE_SANDBOX_URL' ) ? CHARITABLE_PAYPAL_MIDDLEWARE_SANDBOX_URL : 'https://paypal-sandbox.wpcharitable.com' )
				: ( defined( 'CHARITABLE_PAYPAL_MIDDLEWARE_LIVE_URL' ) ? CHARITABLE_PAYPAL_MIDDLEWARE_LIVE_URL : 'https://paypal.wpcharitable.com' ),
		);
	}

	if ( empty( $mode_configs ) ) {
		return;
	}

	foreach ( $mode_configs as $mode => $cfg ) {
		// Step 1: get bearer token. Re-use cached transient if fresh; else fetch.
		$token_cache = get_transient( 'charitable_middleware_token_' . $mode );
		$bearer      = is_array( $token_cache ) && ! empty( $token_cache['token'] ) && ( $token_cache['expires'] ?? 0 ) > time() + 60
			? $token_cache['token']
			: '';

		if ( empty( $bearer ) ) {
			$basic = base64_encode( $cfg['merchant_id'] . ':' . $cfg['secret'] );
			$tok   = wp_remote_post( $cfg['base_url'] . '/api/paypal/v1/oauth/access-token', array(
				'headers' => array(
					'Authorization' => 'Basic ' . $basic,
					'Content-Type'  => 'application/json',
				),
				'body'    => '{}',
				'timeout' => 20,
			) );
			if ( is_wp_error( $tok ) ) {
				if ( charitable_is_debug( 'paypal' ) ) {
					error_log( "PayPal Commerce [$mode]: license sync — token fetch failed: " . $tok->get_error_message() );
				}
				continue;
			}
			$tok_body = json_decode( wp_remote_retrieve_body( $tok ), true );
			// Middleware token endpoint returns `access_token` per OAuth conventions.
			// Older middleware builds returned `token`; accept either for forward/back
			// compat. Cache under `token` so the cache-hit branch above reads a stable
			// key. Without this, license-sync would silently no-op on a fresh-token
			// fetch (cache miss) — sync_license_to_middleware would discover the
			// "empty" $tok_body['token'] and `continue` past the actual update call,
			// leaving the customer row's license_key stale and the freemium fee gate
			// misfiring for that mode.
			$bearer = (string) ( $tok_body['access_token'] ?? $tok_body['token'] ?? '' );
			if ( '' === $bearer ) {
				if ( charitable_is_debug( 'paypal' ) ) {
					error_log( "PayPal Commerce [$mode]: license sync — empty token response" );
				}
				continue;
			}
			set_transient(
				'charitable_middleware_token_' . $mode,
				array(
					'token'   => $bearer,
					'expires' => time() + (int) ( $tok_body['expires_in'] ?? 3600 ),
				),
				(int) ( $tok_body['expires_in'] ?? 3600 )
			);
		}

		// Step 2: POST /customers/update with the new license_key.
		$resp = wp_remote_post( $cfg['base_url'] . '/api/paypal/v1/customers/update', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $bearer,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array( 'license_key' => $license_key ) ),
			'timeout' => 20,
		) );

		$failed = false;
		if ( is_wp_error( $resp ) ) {
			$failed = true;
			if ( charitable_is_debug( 'paypal' ) ) {
				error_log( "PayPal Commerce [$mode]: Failed to sync license_key — " . $resp->get_error_message() );
			}
		} else {
			$code = wp_remote_retrieve_response_code( $resp );
			if ( $code >= 400 ) {
				$failed = true;
			}
			if ( charitable_is_debug( 'paypal' ) ) {
				error_log( "PayPal Commerce [$mode]: Synced license_key (" . ( empty( $license_key ) ? 'cleared' : 'set' ) . ") — HTTP $code" );
			}
		}

		// On failure, mark this mode for retry on next admin pageload + via cron.
		if ( $failed ) {
			set_transient(
				'charitable_paypal_license_sync_pending_' . $mode,
				array(
					'license_key' => $license_key,
					'queued_at'   => time(),
				),
				DAY_IN_SECONDS
			);
		} else {
			// Clear any prior pending flag for this mode.
			delete_transient( 'charitable_paypal_license_sync_pending_' . $mode );
		}
	}
}

/**
 * Retry pending license_key syncs that failed on the original sync attempt.
 *
 * Fires on `admin_init` (lightweight pageload check) and via the daily
 * `charitable_paypal_license_sync_retry` cron. For each mode with a
 * pending sync transient, re-attempts the sync. Successful retry clears
 * the pending flag.
 *
 * Why: middleware can be transiently unreachable (network blip, deploy).
 * Without retry, a license activation that hits a momentary failure stays
 * out of sync indefinitely → middleware keeps charging fees on a Pro user.
 *
 * @since  1.8.11
 *
 * @return void
 */
function charitable_paypal_retry_pending_license_syncs() {
	foreach ( array( 'sandbox', 'live' ) as $mode ) {
		$pending = get_transient( 'charitable_paypal_license_sync_pending_' . $mode );
		if ( ! is_array( $pending ) || ! array_key_exists( 'license_key', $pending ) ) {
			continue;
		}
		/* Push only to the mode whose transient is firing. Passing the
		 * optional $only_mode keeps a sandbox-pending retry from clobbering
		 * a different live-pending key (the two modes can hold diverging
		 * pending keys if the user activated/deactivated twice with
		 * independent failures on each mode). The helper still clears or
		 * re-arms the pending transient for that specific mode. */
		charitable_paypal_send_license_to_connected_modes( (string) $pending['license_key'], $mode );
	}
}
add_action( 'admin_init', 'charitable_paypal_retry_pending_license_syncs' );

/**
 * Schedule a daily cron to retry pending license syncs even if the admin is
 * not actively logged in (e.g., a license activated via wp-cli in a flaky
 * network window).
 *
 * @since  1.8.11
 */
function charitable_paypal_register_license_sync_cron() {
	if ( ! wp_next_scheduled( 'charitable_paypal_license_sync_retry' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'charitable_paypal_license_sync_retry' );
	}
}
add_action( 'init', 'charitable_paypal_register_license_sync_cron' );
add_action( 'charitable_paypal_license_sync_retry', 'charitable_paypal_retry_pending_license_syncs' );
add_action( 'update_option_charitable_settings', 'charitable_paypal_sync_license_to_middleware', 20, 2 );
/**
 * Register the PayPal Commerce gateway.
 *
 * @since  1.8.11
 *
 * @param  array $gateways Registered gateways.
 * @return array
 */
function charitable_register_paypal_commerce_gateway( $gateways ) {
	$gateways['paypal_commerce'] = 'Charitable_Gateway_Paypal_Commerce';
	return $gateways;
}
add_filter( 'charitable_payment_gateways', 'charitable_register_paypal_commerce_gateway' );

/**
 * Register `paypal_commerce` as a structured-logger source.
 *
 * Without this, rows with `source=paypal_commerce` are still queryable but the
 * Tools → Logs source filter dropdown won't expose them.
 *
 * @since  1.8.11
 *
 * @param  array $sources Existing source slug => label map.
 * @return array
 */
function charitable_register_paypal_commerce_log_source( $sources ) {
	$sources['paypal_commerce'] = __( 'PayPal Commerce', 'charitable' );
	return $sources;
}
add_filter( 'charitable_log_sources', 'charitable_register_paypal_commerce_log_source' );

/**
 * Surface an admin warning when CHARITABLE_PAYPAL_LOG_RAW disables PII redaction.
 *
 * Defined here (rather than auto-registering inside the helper class file) so
 * the hook chain stays explicit and discoverable.
 *
 * @since 1.8.11
 */
add_action( 'admin_notices', array( 'Charitable_PayPal_Logger', 'maybe_render_log_raw_warning' ) );

/**
 * Validate donation form submission.
 */
add_filter(
	'charitable_validate_donation_form_submission_gateway',
	array( 'Charitable_Gateway_Paypal_Commerce', 'validate_donation' ),
	10,
	3
);

/**
 * Process donation.
 */
add_filter(
	'charitable_process_donation_paypal_commerce',
	array( 'Charitable_Gateway_Paypal_Commerce', 'process_donation' ),
	10,
	3
);

/**
 * Process webhook/IPN.
 */
add_action(
	'charitable_process_ipn_paypal_commerce',
	array( 'Charitable_Gateway_Paypal_Commerce', 'process_webhook' )
);

/**
 * Process refund.
 */
add_action(
	'charitable_process_refund_paypal_commerce',
	array( 'Charitable_Gateway_Paypal_Commerce', 'process_refund' )
);

/**
 * Add gateway fields to donation form.
 */
add_filter(
	'charitable_donation_form_gateway_fields',
	array( 'Charitable_Gateway_Paypal_Commerce', 'get_gateway_fields' ),
	10,
	2
);

/**
 * Enqueue frontend scripts for PayPal Commerce.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_enqueue_scripts() {
	// Debug logging to track function execution.
	Charitable_PayPal_Logger::debug( 'paypal.diag.enqueue_started', 'enqueue_scripts function called' );

	// Only load on pages with donation forms.
	if ( ! charitable_is_page( 'donation_form' ) && ! charitable_is_page( 'campaign_donation_page' ) ) {
		// Check if we're on a page with a donation form.
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'charitable_donation_form' ) ) {
			Charitable_PayPal_Logger::debug(
				'paypal.diag.enqueue_skipped_no_form',
				'Skipping enqueue — not on donation form page',
				array(
					'post_id'                 => $post ? (int) $post->ID : 0,
					'is_donation_form'        => charitable_is_page( 'donation_form' ),
					'is_campaign_donation'    => charitable_is_page( 'campaign_donation_page' ),
					'has_donation_shortcode'  => $post && has_shortcode( $post->post_content, 'charitable_donation_form' ),
				)
			);
			return;
		}
	}

	Charitable_PayPal_Logger::debug( 'paypal.diag.enqueue_page_ok', 'Page condition passed' );

	$gateway = new Charitable_Gateway_Paypal_Commerce();

	// Check if gateway is active and configured.
	if ( ! charitable_get_helper( 'gateways' )->is_active_gateway( Charitable_Gateway_Paypal_Commerce::ID ) ) {
		Charitable_PayPal_Logger::debug( 'paypal.diag.enqueue_skipped_inactive', 'Gateway is not active' );
		return;
	}

	if ( empty( $gateway->get_client_id() ) ) {
		Charitable_PayPal_Logger::debug( 'paypal.diag.enqueue_skipped_no_client_id', 'Client ID is empty' );
		return;
	}

	Charitable_PayPal_Logger::debug(
		'paypal.diag.enqueue_client_id_present',
		'Client ID found',
		array(
			'client_id_prefix' => substr( $gateway->get_client_id(), 0, 20 ) . '...',
		)
	);

	// Check ACDC/Card Fields availability.
	$acdc_available      = $gateway->should_show_card_fields();
	$apple_pay_available = $gateway->should_show_apple_pay();
	$google_pay_available = $gateway->should_show_google_pay();

	// Check Fastlane availability.
	$fastlane_available = $gateway->is_fastlane_available();

	Charitable_PayPal_Logger::debug(
		'paypal.capability.enqueue_resolved',
		'Capability availability resolved at enqueue',
		array(
			'acdc_available'        => (bool) $acdc_available,
			'apple_pay_available'   => (bool) $apple_pay_available,
			'google_pay_available'  => (bool) $google_pay_available,
			'fastlane_available'    => (bool) $fastlane_available,
			'card_fields_setting'   => (bool) $gateway->get_value( 'enable_card_fields' ),
			'acdc_capability'       => (bool) $gateway->is_acdc_available(),
		)
	);

	// Build SDK components list.
	$components = array( 'buttons', 'messages' );
	if ( $acdc_available ) {
		$components[] = 'card-fields';
	}
	if ( $apple_pay_available ) {
		$components[] = 'applepay';
	}
	if ( $google_pay_available ) {
		$components[] = 'googlepay';
	}
	if ( $fastlane_available ) {
		$components[] = 'fastlane';
	}

	// Build SDK URL.
	$sdk_params = array(
		'client-id'   => $gateway->get_client_id(),
		'merchant-id' => $gateway->get_seller_merchant_id(),
		'currency'    => charitable_get_currency(),
		'intent'      => 'capture',
		'components'  => implode( ',', $components ),
	);

	// Force SDK v5 when Fastlane is available (v5 required for Fastlane).
	if ( $fastlane_available ) {
		$sdk_params['version'] = '5';
	}

	// Enable funding sources.
	$enable_funding = array();
	if ( $gateway->get_value( 'enable_venmo' ) ) {
		$enable_funding[] = 'venmo';
	}
	if ( $gateway->get_value( 'enable_paylater' ) ) {
		$enable_funding[] = 'paylater';
	}
	if ( ! empty( $enable_funding ) ) {
		$sdk_params['enable-funding'] = implode( ',', $enable_funding );
	}

	$sdk_url = add_query_arg( $sdk_params, $gateway->get_sdk_base_url() );

	Charitable_PayPal_Logger::debug(
		'paypal.diag.sdk_url_built',
		'PayPal SDK URL built',
		array(
			'sdk_url'    => $sdk_url,
			'components' => $components,
		)
	);

	// Enqueue PayPal SDK.
	wp_enqueue_script(
		'paypal-sdk',
		$sdk_url,
		array(),
		null,
		true
	);

	Charitable_PayPal_Logger::debug( 'paypal.diag.sdk_enqueued', 'PayPal SDK successfully enqueued' );

	// PayPal's googlepay component exposes paypal.Googlepay() but does NOT load
	// Google's pay.js runtime — without it, window.google.payments is undefined
	// and our renderGooglePay() bails out with "Google Pay API not available."
	// Loaded in head (not async) so window.google.payments is ready by the time
	// charitable-paypal-commerce.js runs renderGooglePay() during init.
	// See https://developer.paypal.com/docs/multiparty/checkout/advanced/google-pay/
	if ( $google_pay_available ) {
		wp_enqueue_script(
			'google-pay-sdk',
			'https://pay.google.com/gp/p/js/pay.js',
			array(),
			null,
			false
		);
	}

	// Enqueue our handler script.
	// Use filemtime() as the version when the source file is on disk. Without
	// it, sites behind aggressive CDN caching (Cloudflare, KeyCDN, etc.) and
	// browsers will serve stale JS for a full plugin version cycle, even after
	// we ship a fix. Same pattern already in use for paypal-commerce.css.
	// Note: get_path( 'plugin' ) returns the path to charitable.php, not the
	// plugin directory — use get_path( 'directory' ) for the filesystem dir.
	$js_path = charitable()->get_path( 'directory' ) . 'assets/js/paypal-commerce.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : charitable()->get_version();
	$ppc_deps = array( 'paypal-sdk', 'jquery' );
	if ( $google_pay_available ) {
		$ppc_deps[] = 'google-pay-sdk';
	}
	wp_enqueue_script(
		'charitable-paypal-commerce',
		charitable()->get_path( 'directory', false ) . 'assets/js/paypal-commerce.js',
		$ppc_deps,
		$js_ver,
		true
	);

	// Enqueue our styles.
	$css_path = charitable()->get_path( 'directory' ) . 'assets/css/paypal-commerce.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : charitable()->get_version();
	wp_enqueue_style(
		'charitable-paypal-commerce',
		charitable()->get_path( 'directory', false ) . 'assets/css/paypal-commerce.css',
		array(),
		$css_ver
	);

	// Localize script data.
	wp_localize_script(
		'charitable-paypal-commerce',
		'CHARITABLE_PAYPAL_COMMERCE',
		array(
			'gateway_id'        => Charitable_Gateway_Paypal_Commerce::ID,
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'charitable_paypal_commerce' ),
			'button_style'      => array(
				'layout' => 'vertical',
				'color'  => $gateway->get_value( 'button_style' ) ?: 'gold',
				'shape'  => $gateway->get_value( 'button_shape' ) ?: 'rect',
				'label'  => 'donate',
			),
			'currency'          => charitable_get_currency(),
			// Card Fields (ACDC) configuration.
			'card_fields'       => array(
				'enabled'       => $acdc_available,
				'style'         => array(
					'input' => array(
						'font-size'   => '16px',
						'font-family' => 'inherit',
						'color'       => '#333',
					),
					'.invalid' => array(
						'color' => '#c0392b',
					),
				),
			),
			// Apple Pay configuration.
			'apple_pay'         => array(
				'enabled' => $apple_pay_available,
			),
			// Google Pay configuration.
			'google_pay'        => array(
				'enabled' => $google_pay_available,
			),
			// Fastlane configuration.
			'fastlane'          => array(
				'enabled'  => $fastlane_available,
				'behavior' => $gateway->get_fastlane_behavior(),
			),
			'is_logged_in'      => is_user_logged_in(),
			'debug'             => charitable_is_debug( 'paypal' ),
			'strings'           => array(
				'payment_error'          => __( 'Failed to process payment.', 'charitable' ),
				'capture_error'          => __( 'Failed to capture payment.', 'charitable' ),
			),
			'i18n'              => array(
				'donate'           => __( 'Donate', 'charitable' ),
				'processing'       => __( 'Processing donation...', 'charitable' ),
				'error'            => __( 'There was an error processing your donation. Please try again.', 'charitable' ),
				'select_amount'    => __( 'Please select a donation amount.', 'charitable' ),
				'required_fields'  => __( 'Please fill in all required fields.', 'charitable' ),
				'card_error'       => __( 'Please check your card details and try again.', 'charitable' ),
				'card_number'      => __( 'Card Number', 'charitable' ),
				'expiry'           => __( 'Expiry Date', 'charitable' ),
				'cvv'              => __( 'CVV', 'charitable' ),
				'card_name'        => __( 'Name on Card', 'charitable' ),
				'pay_with_card'    => __( 'Pay with Card', 'charitable' ),
				'or'               => __( 'or', 'charitable' ),
				// Fastlane strings.
				'email_address'    => __( 'Email Address', 'charitable' ),
				'email_placeholder'=> __( 'Enter your email address', 'charitable' ),
				'continue'         => __( 'Continue', 'charitable' ),
				'loading'          => __( 'Loading...', 'charitable' ),
				'invalid_email'    => __( 'Please enter a valid email address.', 'charitable' ),
				'complete_payment' => __( 'Complete Payment', 'charitable' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'charitable_paypal_commerce_enqueue_scripts' );

/**
 * Temporary debug function to clear PayPal caches
 */
function debug_clear_paypal_caches() {
	if ( isset( $_GET['clear_paypal_cache'] ) && charitable_is_debug( 'paypal' ) ) {
		delete_transient( 'charitable_paypal_seller_status_sandbox' );
		delete_transient( 'charitable_paypal_seller_status_live' );
		Charitable_PayPal_Logger::debug( 'paypal.diag.caches_cleared', 'Seller status caches cleared via debug parameter' );
	}
}
add_action( 'init', 'debug_clear_paypal_caches' );

/**
 * Add BN code (data-partner-attribution-id) to the PayPal SDK script tag.
 *
 * PayPal requires the BN code in two places:
 * 1. In API requests via the PayPal-Partner-Attribution-Id header (done in gateway class)
 * 2. In the JS SDK script tag as data-partner-attribution-id attribute (done here)
 *
 * @since 1.8.11
 *
 * @param  string $tag    The script tag HTML.
 * @param  string $handle The script handle.
 * @param  string $src    The script source URL.
 * @return string Modified script tag.
 */
function charitable_paypal_commerce_add_bn_code_to_sdk( $tag, $handle, $src ) {
	if ( 'paypal-sdk' !== $handle ) {
		return $tag;
	}

	$gateway = new Charitable_Gateway_Paypal_Commerce();
	$bn_code = $gateway->get_bn_code();

	// Add the data-partner-attribution-id attribute to the script tag.
	$tag = str_replace(
		' src=',
		' data-partner-attribution-id="' . esc_attr( $bn_code ) . '" src=',
		$tag
	);

	return $tag;
}
add_filter( 'script_loader_tag', 'charitable_paypal_commerce_add_bn_code_to_sdk', 10, 3 );

/**
 * AJAX handler: Create PayPal order.
 *
 * Creates a PayPal order directly from the form data. The Charitable donation
 * will be created when the order is captured (after user approves payment).
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_ajax_create_order() {
	// Whitelist-only POST fields — never log full $_POST (spec PII rule).
	Charitable_PayPal_Logger::debug(
		'paypal.diag.ajax_create_order_called',
		'AJAX create_order called',
		array(
			'amount'         => isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0,
			'campaign_id'    => isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0,
			'currency'       => isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : '',
			'payment_method' => isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '',
			'has_form_data'  => isset( $_POST['form_data'] ),
		)
	);

	check_ajax_referer( 'charitable_paypal_commerce', 'nonce' );

	$amount      = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
	$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
	$currency    = isset( $_POST['currency'] ) ? sanitize_text_field( $_POST['currency'] ) : charitable_get_currency();

	$form_data       = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : '';
	$payment_method  = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : 'paypal';

	if ( $amount <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid donation amount.', 'charitable' ) ) );
	}

	// Get campaign name for order description.
	$campaign_name = __( 'Donation', 'charitable' );
	if ( $campaign_id ) {
		$campaign = get_post( $campaign_id );
		if ( $campaign ) {
			$campaign_name = $campaign->post_title;
		}
	}

	// Generate a reference ID for this order.
	$reference_id = 'pending_' . time() . '_' . wp_rand( 1000, 9999 );

	$gateway = new Charitable_Gateway_Paypal_Commerce();

	// Pass payment_method through so the order builder can attach the
	// payment-source-specific experience_context (PAY_NOW, brand_name,
	// shipping_preference: NO_SHIPPING). Without this, PayPal renders a
	// shipping section on the hosted checkout and Continue-to-Review-Order
	// silently loops on shipping address validation.
	$order = $gateway->create_order_from_amount( $amount, $currency, $campaign_name, $reference_id, $payment_method );

	if ( is_wp_error( $order ) ) {
		$donor_response = charitable_paypal_commerce_format_error_for_donor( $order );
		charitable_paypal_commerce_log_donor_facing_error(
			'paypal.order.create_donor_facing_error',
			'Order creation failed; donor saw a PayPal error notice',
			$order,
			$donor_response,
			array(
				'amount'         => (float) $amount,
				'campaign_id'    => (int) $campaign_id,
				'currency'       => $currency,
				'payment_method' => $payment_method,
			)
		);
		wp_send_json_error( $donor_response );
	}

	// Store form data in a transient so we can create the donation when captured.
	// The transient key is based on the PayPal order ID.
	set_transient(
		'charitable_paypal_order_' . $order['id'],
		array(
			'form_data'      => $form_data,
			'amount'         => $amount,
			'campaign_id'    => $campaign_id,
			'currency'       => $currency,
			'payment_method' => $payment_method,
		),
		HOUR_IN_SECONDS // Expire after 1 hour.
	);

	Charitable_PayPal_Logger::info(
		'paypal.order.created',
		'Order created',
		array(
			'order_id'    => $order['id'],
			'amount'      => $amount,
			'currency'    => $currency,
			'campaign_id' => $campaign_id,
		),
		array(
			'campaign_id' => (int) $campaign_id,
		)
	);

	wp_send_json_success(
		array(
			'order_id' => $order['id'],
		)
	);
}
add_action( 'wp_ajax_charitable_paypal_commerce_create_order', 'charitable_paypal_commerce_ajax_create_order' );
add_action( 'wp_ajax_nopriv_charitable_paypal_commerce_create_order', 'charitable_paypal_commerce_ajax_create_order' );

/**
 * AJAX handler: Capture PayPal order.
 *
 * This captures the PayPal order and creates the Charitable donation
 * from the stored form data.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_ajax_capture_order() {
	// Whitelist-only POST fields — never log full $_POST (spec PII rule).
	Charitable_PayPal_Logger::debug(
		'paypal.diag.ajax_capture_order_called',
		'AJAX capture_order called',
		array(
			'order_id' => isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '',
		)
	);

	check_ajax_referer( 'charitable_paypal_commerce', 'nonce' );

	$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';

	Charitable_PayPal_Logger::debug(
		'paypal.order.capture_started',
		'Capturing order',
		array(
			'order_id' => $order_id,
		)
	);

	if ( ! $order_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'charitable' ) ) );
	}

	$gateway = new Charitable_Gateway_Paypal_Commerce();
	$capture = $gateway->capture_order( $order_id );

	if ( is_wp_error( $capture ) ) {
		$donor_response = charitable_paypal_commerce_format_error_for_donor( $capture );
		charitable_paypal_commerce_log_donor_facing_error(
			'paypal.order.capture_donor_facing_error',
			'Capture failed; donor saw a PayPal error notice',
			$capture,
			$donor_response,
			array(
				'order_id' => (string) $order_id,
			)
		);
		wp_send_json_error( $donor_response );
	}

	// Debug: Log the full capture response to see vault data structure.
	Charitable_PayPal_Logger::debug(
		'paypal.order.capture_response',
		'Capture API response',
		array(
			'order_id' => $order_id,
			'response' => $capture,
		)
	);

	// Extract capture details.
	$capture_data   = $capture['purchase_units'][0]['payments']['captures'][0] ?? array();
	$capture_status = $capture_data['status'] ?? $capture['status'] ?? '';
	$capture_id     = $capture_data['id'] ?? '';
	$payer_email    = $capture['payer']['email_address'] ?? '';
	$payer_name     = '';

	if ( ! empty( $capture['payer']['name'] ) ) {
		$payer_name = trim( ( $capture['payer']['name']['given_name'] ?? '' ) . ' ' . ( $capture['payer']['name']['surname'] ?? '' ) );
	}

	// Get stored form data from transient.
	$stored_data = get_transient( 'charitable_paypal_order_' . $order_id );

	if ( ! $stored_data ) {
		// No stored data - this might be a webhook or direct API call.
		// Check if donation already exists for this order.
		$donations = get_posts(
			array(
				'post_type'  => Charitable::DONATION_POST_TYPE,
				'meta_key'   => '_paypal_commerce_order_id',
				'meta_value' => $order_id,
				'fields'     => 'ids',
			)
		);

		if ( ! empty( $donations ) ) {
			$donation_id = $donations[0];
			$donation    = charitable_get_donation( $donation_id );

			// Update with capture details.
			if ( $capture_id ) {
				update_post_meta( $donation_id, '_paypal_commerce_capture_id', $capture_id );
				$donation->set_gateway_transaction_id( $capture_id );
			}

			if ( $payer_email ) {
				update_post_meta( $donation_id, '_paypal_commerce_payer_email', $payer_email );
			}

			// Update status.
			if ( 'COMPLETED' === $capture_status ) {
				$donation->update_status( 'charitable-completed' );
			} elseif ( 'PENDING' === $capture_status ) {
				$donation->update_status( 'charitable-pending' );
			} else {
				$donation->update_status( 'charitable-failed' );
			}

			wp_send_json_success(
				array(
					'status'      => $capture_status,
					'redirect'    => charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) ),
					'donation_id' => $donation_id,
				)
			);
		}

		// No donation found and no stored data - can't proceed.
		wp_send_json_error( array( 'message' => __( 'Session expired. Please try again.', 'charitable' ) ) );
	}

	// Parse the form data.
	parse_str( $stored_data['form_data'], $form_values );

	// Build donation data from form values.
	$campaign_id = $stored_data['campaign_id'];
	$amount      = $stored_data['amount'];

	// Extract donor information from form.
	$donor_data = array(
		'first_name' => isset( $form_values['first_name'] ) ? sanitize_text_field( $form_values['first_name'] ) : '',
		'last_name'  => isset( $form_values['last_name'] ) ? sanitize_text_field( $form_values['last_name'] ) : '',
		'email'      => isset( $form_values['email'] ) ? sanitize_email( $form_values['email'] ) : $payer_email,
		'address'    => isset( $form_values['address'] ) ? sanitize_text_field( $form_values['address'] ) : '',
		'address_2'  => isset( $form_values['address_2'] ) ? sanitize_text_field( $form_values['address_2'] ) : '',
		'city'       => isset( $form_values['city'] ) ? sanitize_text_field( $form_values['city'] ) : '',
		'state'      => isset( $form_values['state'] ) ? sanitize_text_field( $form_values['state'] ) : '',
		'postcode'   => isset( $form_values['postcode'] ) ? sanitize_text_field( $form_values['postcode'] ) : '',
		'country'    => isset( $form_values['country'] ) ? sanitize_text_field( $form_values['country'] ) : '',
		'phone'      => isset( $form_values['phone'] ) ? sanitize_text_field( $form_values['phone'] ) : '',
	);

	// Use PayPal payer info as fallback.
	if ( empty( $donor_data['email'] ) && $payer_email ) {
		$donor_data['email'] = $payer_email;
	}

	if ( empty( $donor_data['first_name'] ) && ! empty( $capture['payer']['name']['given_name'] ) ) {
		$donor_data['first_name'] = sanitize_text_field( $capture['payer']['name']['given_name'] );
	}

	if ( empty( $donor_data['last_name'] ) && ! empty( $capture['payer']['name']['surname'] ) ) {
		$donor_data['last_name'] = sanitize_text_field( $capture['payer']['name']['surname'] );
	}

	// Create the donation.
	$donation_args = array(
		'campaigns' => array(
			array(
				'campaign_id'     => $campaign_id,
				'campaign_name'   => get_the_title( $campaign_id ),
				'amount'          => $amount,
			),
		),
		'user'      => $donor_data,
		'gateway'   => Charitable_Gateway_Paypal_Commerce::ID,
		'status'    => 'COMPLETED' === $capture_status ? 'charitable-completed' : 'charitable-pending',
		'log'       => array(
			array(
				'time'    => time(),
				'message' => sprintf(
					/* translators: %s: PayPal order ID */
					__( 'PayPal Commerce order %s captured.', 'charitable' ),
					$order_id
				),
			),
		),
	);

	// Handle donor ID if user is logged in.
	if ( is_user_logged_in() ) {
		$donation_args['donor_id'] = get_current_user_id();
	}

	// Create the donation using Charitable's donation processor.
	$donation_id = charitable_create_donation( $donation_args );

	if ( ! $donation_id ) {
		wp_send_json_error( array( 'message' => __( 'Failed to create donation record.', 'charitable' ) ) );
	}

	$donation = charitable_get_donation( $donation_id );

	// Store PayPal transaction details.
	update_post_meta( $donation_id, '_paypal_commerce_order_id', $order_id );

	if ( $capture_id ) {
		update_post_meta( $donation_id, '_paypal_commerce_capture_id', $capture_id );
		$donation->set_gateway_transaction_id( $capture_id );
	}

	if ( $payer_email ) {
		update_post_meta( $donation_id, '_paypal_commerce_payer_email', $payer_email );
	}

	// Store payment method information for Fastlane tracking.
	$payment_method = isset( $stored_data['payment_method'] ) ? $stored_data['payment_method'] : 'paypal';
	update_post_meta( $donation_id, '_paypal_commerce_payment_method', $payment_method );

	if ( 'fastlane' === $payment_method ) {
		update_post_meta( $donation_id, '_paypal_commerce_fastlane_payment', true );

		Charitable_PayPal_Logger::info(
			'paypal.order.fastlane_processed',
			'Fastlane payment processed',
			array(
				'donation_id' => (int) $donation_id,
				'order_id'    => $order_id,
			),
			array(
				'donation_id' => (int) $donation_id,
			)
		);
	}

	// Add donation log entries for PayPal processing.
	$gateway = new Charitable_Gateway_Paypal_Commerce();
	$paypal_base_url = $gateway->is_sandbox() ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';

	$donation->log()->add(
		sprintf(
			/* translators: %1$s: PayPal order ID; %2$s: PayPal base URL; %3$s: PayPal order ID (repeated in URL path) */
			__( 'Payment completed via PayPal Commerce Platform. Order ID: %1$s <a href="%2$s/activities/payment/%3$s" target="_blank">[View in PayPal Dashboard]</a>', 'charitable' ),
			$order_id,
			$paypal_base_url,
			$order_id
		)
	);

	if ( $capture_id ) {
		$donation->log()->add(
			sprintf(
				/* translators: %1$s: PayPal capture ID; %2$s: PayPal base URL; %3$s: PayPal capture ID (repeated in URL path) */
				__( 'PayPal capture ID: %1$s <a href="%2$s/activities/payment/%3$s" target="_blank">[View Transaction in PayPal]</a>', 'charitable' ),
				$capture_id,
				$paypal_base_url,
				$capture_id
			)
		);
	}

	$donation->log()->add(
		sprintf(
			/* translators: %s: gateway transaction ID (PayPal capture or order ID) */
			__( 'Gateway transaction ID: %s', 'charitable' ),
			$capture_id ? $capture_id : $order_id
		)
	);

	if ( $capture_status ) {
		$donation->log()->add(
			sprintf(
				/* translators: %s: PayPal capture status string */
				__( 'PayPal capture status: %s', 'charitable' ),
				$capture_status
			)
		);
	}

	charitable_paypal_commerce_maybe_log_platform_fee( $donation, $capture );



	// Delete the transient.
	delete_transient( 'charitable_paypal_order_' . $order_id );

	// Trigger donation completed action.
	do_action( 'charitable_donation_complete', $donation_id );

	$capture_amount   = $capture_data['amount']['value'] ?? null;
	$capture_currency = $capture_data['amount']['currency_code'] ?? null;

	Charitable_PayPal_Logger::info(
		'paypal.order.captured',
		'Order captured',
		array(
			'order_id'        => $order_id,
			'capture_id'      => $capture_id,
			'capture_status'  => $capture_status,
			'amount'          => $capture_amount,
			'currency'        => $capture_currency,
			// Both modes attach the PayPal debug header to the capture response,
			// but under different keys: direct-API path (capture_order audit H3
			// fix) writes `paypal_debug_id`; middleware client writes `debug_id`.
			// Fall back so the structured log row always has the correlation
			// ID populated regardless of mode.
			'paypal_debug_id' => $capture['paypal_debug_id'] ?? ( $capture['debug_id'] ?? null ),
		),
		array(
			'donation_id' => (int) $donation_id,
		)
	);

	wp_send_json_success(
		array(
			'status'      => $capture_status,
			'redirect'    => charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) ),
			'donation_id' => $donation_id,
		)
	);
}
add_action( 'wp_ajax_charitable_paypal_commerce_capture_order', 'charitable_paypal_commerce_ajax_capture_order' );
add_action( 'wp_ajax_nopriv_charitable_paypal_commerce_capture_order', 'charitable_paypal_commerce_ajax_capture_order' );

/**
 * AJAX handler: Get PayPal client token for Fastlane.
 *
 * Securely generates and returns a client token for Fastlane initialization.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_ajax_get_client_token() {
	// Verify nonce.
	if ( ! check_ajax_referer( 'charitable_paypal_commerce_nonce', 'nonce', false ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid security token.', 'charitable' ),
			)
		);
		return;
	}

	$gateway = new Charitable_Gateway_Paypal_Commerce();

	// Check if Fastlane is available.
	if ( ! $gateway->is_fastlane_available() ) {
		Charitable_PayPal_Logger::warn(
			'paypal.oauth.client_token_rejected_no_fastlane',
			'Client token request rejected — Fastlane not available'
		);

		wp_send_json_error(
			array(
				'message' => __( 'Fastlane is not available for this merchant.', 'charitable' ),
			)
		);
		return;
	}

	// Generate client token.
	$client_token = $gateway->generate_client_token();

	if ( is_wp_error( $client_token ) ) {
		Charitable_PayPal_Logger::error(
			'paypal.oauth.client_token_failed',
			'Client token generation failed in AJAX flow',
			array(
				'error' => $client_token->get_error_message(),
			)
		);

		wp_send_json_error(
			array(
				'message' => __( 'Failed to generate client token.', 'charitable' ),
				'error'   => $client_token->get_error_message(),
			)
		);
		return;
	}

	Charitable_PayPal_Logger::debug(
		'paypal.oauth.client_token_provided',
		'Client token successfully provided via AJAX'
	);

	wp_send_json_success(
		array(
			'client_token' => $client_token,
		)
	);
}
add_action( 'wp_ajax_charitable_paypal_commerce_get_client_token', 'charitable_paypal_commerce_ajax_get_client_token' );
add_action( 'wp_ajax_nopriv_charitable_paypal_commerce_get_client_token', 'charitable_paypal_commerce_ajax_get_client_token' );

/**
 * AJAX handler: Fastlane email lookup.
 *
 * This endpoint can be used to check if an email has an existing Fastlane profile
 * or for other Fastlane-related operations.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_ajax_fastlane_lookup() {
	// Verify nonce.
	if ( ! check_ajax_referer( 'charitable_paypal_commerce_nonce', 'nonce', false ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid security token.', 'charitable' ),
			)
		);
		return;
	}

	$gateway = new Charitable_Gateway_Paypal_Commerce();

	// Check if Fastlane is available.
	if ( ! $gateway->is_fastlane_available() ) {
		wp_send_json_error(
			array(
				'message' => __( 'Fastlane is not available for this merchant.', 'charitable' ),
			)
		);
		return;
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

	if ( empty( $email ) || ! is_email( $email ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Valid email address is required.', 'charitable' ),
			)
		);
		return;
	}

	Charitable_PayPal_Logger::debug(
		'paypal.capability.fastlane_lookup',
		'Fastlane lookup performed',
		array(
			'email' => $email,
		)
	);

	// For now, we'll return a success response.
	// In the future, this could be enhanced to perform actual lookups
	// or other Fastlane-specific operations.
	wp_send_json_success(
		array(
			'email'        => $email,
			'fastlane_eligible' => true,
		)
	);
}
add_action( 'wp_ajax_charitable_paypal_commerce_fastlane_lookup', 'charitable_paypal_commerce_ajax_fastlane_lookup' );
add_action( 'wp_ajax_nopriv_charitable_paypal_commerce_fastlane_lookup', 'charitable_paypal_commerce_ajax_fastlane_lookup' );

/**
 * Handle the return from PayPal (redirect flow).
 *
 * This handles the case where PayPal redirects the user back after approval
 * (as opposed to the JS SDK flow which uses AJAX).
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_handle_return() {
	if ( ! isset( $_GET['token'] ) || ! isset( $_GET['charitable_paypal_commerce'] ) ) {
		return;
	}

	$order_id = sanitize_text_field( $_GET['token'] );
	$gateway  = new Charitable_Gateway_Paypal_Commerce();

	// Capture the order.
	$capture = $gateway->capture_order( $order_id );

	if ( is_wp_error( $capture ) ) {
		charitable_get_notices()->add_error( $capture->get_error_message() );
		wp_safe_redirect( charitable_get_permalink( 'donation_cancel_page' ) );
		exit;
	}

	// Debug: Log the full capture response to see vault data structure.
	Charitable_PayPal_Logger::debug(
		'paypal.order.capture_response',
		'Capture API response (return flow)',
		array(
			'order_id' => $order_id,
			'response' => $capture,
		)
	);

	// Extract capture details.
	$capture_data   = $capture['purchase_units'][0]['payments']['captures'][0] ?? array();
	$capture_status = $capture_data['status'] ?? $capture['status'] ?? '';
	$capture_id     = $capture_data['id'] ?? '';
	$payer_email    = $capture['payer']['email_address'] ?? '';

	// Check if donation already exists for this order.
	$donations = get_posts(
		array(
			'post_type'  => Charitable::DONATION_POST_TYPE,
			'meta_key'   => '_paypal_commerce_order_id',
			'meta_value' => $order_id,
			'fields'     => 'ids',
		)
	);

	if ( ! empty( $donations ) ) {
		// Donation exists - just update it.
		$donation_id = $donations[0];
		$donation    = charitable_get_donation( $donation_id );

		if ( $capture_id ) {
			update_post_meta( $donation_id, '_paypal_commerce_capture_id', $capture_id );
			$donation->set_gateway_transaction_id( $capture_id );
		}

		if ( $payer_email ) {
			update_post_meta( $donation_id, '_paypal_commerce_payer_email', $payer_email );
		}

		if ( 'COMPLETED' === $capture_status ) {
			$donation->update_status( 'charitable-completed' );
		} elseif ( 'PENDING' === $capture_status ) {
			$donation->update_status( 'charitable-pending' );
		}

		wp_safe_redirect(
			charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) )
		);
		exit;
	}

	// No donation yet - check for stored form data.
	$stored_data = get_transient( 'charitable_paypal_order_' . $order_id );

	if ( ! $stored_data ) {
		// No stored data and no donation - session expired.
		charitable_get_notices()->add_error( __( 'Session expired. Please try again.', 'charitable' ) );
		wp_safe_redirect( charitable_get_permalink( 'donation_cancel_page' ) );
		exit;
	}

	// Parse the form data and create donation.
	parse_str( $stored_data['form_data'], $form_values );

	$campaign_id = $stored_data['campaign_id'];
	$amount      = $stored_data['amount'];

	// Extract donor information.
	$donor_data = array(
		'first_name' => isset( $form_values['first_name'] ) ? sanitize_text_field( $form_values['first_name'] ) : '',
		'last_name'  => isset( $form_values['last_name'] ) ? sanitize_text_field( $form_values['last_name'] ) : '',
		'email'      => isset( $form_values['email'] ) ? sanitize_email( $form_values['email'] ) : $payer_email,
		'address'    => isset( $form_values['address'] ) ? sanitize_text_field( $form_values['address'] ) : '',
		'address_2'  => isset( $form_values['address_2'] ) ? sanitize_text_field( $form_values['address_2'] ) : '',
		'city'       => isset( $form_values['city'] ) ? sanitize_text_field( $form_values['city'] ) : '',
		'state'      => isset( $form_values['state'] ) ? sanitize_text_field( $form_values['state'] ) : '',
		'postcode'   => isset( $form_values['postcode'] ) ? sanitize_text_field( $form_values['postcode'] ) : '',
		'country'    => isset( $form_values['country'] ) ? sanitize_text_field( $form_values['country'] ) : '',
		'phone'      => isset( $form_values['phone'] ) ? sanitize_text_field( $form_values['phone'] ) : '',
	);

	// Use PayPal payer info as fallback.
	if ( empty( $donor_data['email'] ) && $payer_email ) {
		$donor_data['email'] = $payer_email;
	}

	if ( empty( $donor_data['first_name'] ) && ! empty( $capture['payer']['name']['given_name'] ) ) {
		$donor_data['first_name'] = sanitize_text_field( $capture['payer']['name']['given_name'] );
	}

	if ( empty( $donor_data['last_name'] ) && ! empty( $capture['payer']['name']['surname'] ) ) {
		$donor_data['last_name'] = sanitize_text_field( $capture['payer']['name']['surname'] );
	}

	// Create the donation.
	$donation_args = array(
		'campaigns' => array(
			array(
				'campaign_id'   => $campaign_id,
				'campaign_name' => get_the_title( $campaign_id ),
				'amount'        => $amount,
			),
		),
		'user'      => $donor_data,
		'gateway'   => Charitable_Gateway_Paypal_Commerce::ID,
		'status'    => 'COMPLETED' === $capture_status ? 'charitable-completed' : 'charitable-pending',
		'log'       => array(
			array(
				'time'    => time(),
				'message' => sprintf(
					/* translators: %s: PayPal order ID */
					__( 'PayPal Commerce order %s captured.', 'charitable' ),
					$order_id
				),
			),
		),
	);

	if ( is_user_logged_in() ) {
		$donation_args['donor_id'] = get_current_user_id();
	}

	$donation_id = charitable_create_donation( $donation_args );

	if ( ! $donation_id ) {
		charitable_get_notices()->add_error( __( 'Failed to create donation record.', 'charitable' ) );
		wp_safe_redirect( charitable_get_permalink( 'donation_cancel_page' ) );
		exit;
	}

	$donation = charitable_get_donation( $donation_id );

	// Store PayPal transaction details.
	update_post_meta( $donation_id, '_paypal_commerce_order_id', $order_id );

	if ( $capture_id ) {
		update_post_meta( $donation_id, '_paypal_commerce_capture_id', $capture_id );
		$donation->set_gateway_transaction_id( $capture_id );
	}

	if ( $payer_email ) {
		update_post_meta( $donation_id, '_paypal_commerce_payer_email', $payer_email );
	}

	// Add donation log entries for PayPal processing.
	$gateway = new Charitable_Gateway_Paypal_Commerce();
	$paypal_base_url = $gateway->is_sandbox() ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';

	$donation->log()->add(
		sprintf(
			/* translators: %1$s: PayPal order ID; %2$s: PayPal base URL; %3$s: PayPal order ID (repeated in URL path) */
			__( 'Payment completed via PayPal Commerce Platform. Order ID: %1$s <a href="%2$s/activities/payment/%3$s" target="_blank">[View in PayPal Dashboard]</a>', 'charitable' ),
			$order_id,
			$paypal_base_url,
			$order_id
		)
	);

	if ( $capture_id ) {
		$donation->log()->add(
			sprintf(
				/* translators: %1$s: PayPal capture ID; %2$s: PayPal base URL; %3$s: PayPal capture ID (repeated in URL path) */
				__( 'PayPal capture ID: %1$s <a href="%2$s/activities/payment/%3$s" target="_blank">[View Transaction in PayPal]</a>', 'charitable' ),
				$capture_id,
				$paypal_base_url,
				$capture_id
			)
		);
	}

	$donation->log()->add(
		sprintf(
			/* translators: %s: gateway transaction ID (PayPal capture or order ID) */
			__( 'Gateway transaction ID: %s', 'charitable' ),
			$capture_id ? $capture_id : $order_id
		)
	);

	if ( $capture_status ) {
		$donation->log()->add(
			sprintf(
				/* translators: %s: PayPal capture status string */
				__( 'PayPal capture status: %s', 'charitable' ),
				$capture_status
			)
		);
	}

	charitable_paypal_commerce_maybe_log_platform_fee( $donation, $capture );

	// Delete the transient.
	delete_transient( 'charitable_paypal_order_' . $order_id );

	// Trigger donation completed action.
	do_action( 'charitable_donation_complete', $donation_id );

	// Redirect to receipt page.
	wp_safe_redirect(
		charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) )
	);
	exit;
}
add_action( 'template_redirect', 'charitable_paypal_commerce_handle_return' );

/**
 * Redirect back to the gateway settings page with an error notice.
 *
 * Used by admin button handlers (Connect, Disconnect, Create Webhook) when
 * nonce verification or capability check fails. Replaces the abrupt
 * `wp_die()` page with a friendly notice on the settings page the admin
 * came from. Matches the success-path pattern used elsewhere in this file
 * (set `charitable_paypal_commerce_error` transient + redirect to the
 * gateway settings page; the notice renderer at the top of the gateway
 * settings tab picks it up and displays it).
 *
 * @since 1.8.11
 *
 * @param string $message Translated message to show.
 * @return void Exits.
 */
function charitable_paypal_commerce_admin_error_redirect( $message ) {
	set_transient( 'charitable_paypal_commerce_error', $message, 60 );
	wp_safe_redirect(
		admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce' )
	);
	exit;
}

/**
 * Handle the "Connect with PayPal" button click.
 *
 * Generates a partner referral and redirects the user to PayPal.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_handle_connect() {
	if ( ! isset( $_GET['charitable_paypal_commerce_connect'] ) ) {
		return;
	}

	// Verify nonce and permissions. Friendly redirect rather than hard wp_die —
	// a nonce can quietly expire while the admin leaves the settings tab open,
	// and "Security check failed." on a blank page is a dead end.
	if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'charitable_paypal_connect' ) ) {
		charitable_paypal_commerce_admin_error_redirect(
			__( 'Your session expired before the connection could start. Please try again.', 'charitable' )
		);
	}

	if ( ! current_user_can( 'manage_charitable_settings' ) ) {
		charitable_paypal_commerce_admin_error_redirect(
			__( 'You do not have permission to perform this action.', 'charitable' )
		);
	}

	$gateway = new Charitable_Gateway_Paypal_Commerce();

	// Generate tracking ID and store it.
	$tracking_id = $gateway->generate_tracking_id();
	$mode        = $gateway->is_sandbox() ? 'sandbox' : 'live';

	set_transient( 'charitable_paypal_onboarding_tracking_' . $mode, $tracking_id, HOUR_IN_SECONDS );

	/* Short single-use token embedded in the return URL and matched in
	 * charitable_paypal_commerce_handle_onboarding_return(). Without this,
	 * an attacker who phishes an admin into clicking
	 * `?cppc_onboard=1&merchantIdInPayPal=ATTACKER_ID&permissionsGranted=true`
	 * can overwrite the saved seller_merchant_id and route donations to
	 * their own PayPal account (direct mode) or break new donations entirely
	 * (middleware mode). 12 alphanumeric chars keeps the URL under PayPal's
	 * 127-character limit while preserving ~71 bits of entropy. */
	$onboarding_csrf_token = wp_generate_password( 12, false, false );
	set_transient( 'charitable_paypal_onboarding_csrf_' . $mode, $onboarding_csrf_token, HOUR_IN_SECONDS );

	// Get return URL with embedded CSRF token.
	$return_url = $gateway->get_onboarding_return_url( $onboarding_csrf_token );

	// Create partner referral.
	$referral = $gateway->create_partner_referral( $tracking_id, $return_url );

	if ( is_wp_error( $referral ) ) {
		// Store error and redirect back to settings.
		set_transient( 'charitable_paypal_commerce_error', $referral->get_error_message(), 60 );
		wp_safe_redirect(
			admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_error=1' )
		);
		exit;
	}

	// Get the action URL.
	$action_url = $gateway->get_referral_action_url( $referral );

	if ( ! $action_url ) {
		set_transient( 'charitable_paypal_commerce_error', __( 'Failed to get PayPal signup URL.', 'charitable' ), 60 );
		wp_safe_redirect(
			admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_error=1' )
		);
		exit;
	}

	// Persist the middleware referral_id so the return handler can pass it to /oauth/credentials.
	// Without this, the middleware can't verify the partner referral or create a customer row,
	// and every subsequent /oauth/access-token call fails with "Customer not found".
	if ( $gateway->is_middleware_mode() && ! empty( $referral['referral_id'] ) ) {
		set_transient( 'charitable_paypal_onboarding_referral_id_' . $mode, $referral['referral_id'], HOUR_IN_SECONDS );
	}

	// Redirect to PayPal.
	wp_redirect( $action_url );
	exit;
}
add_action( 'admin_init', 'charitable_paypal_commerce_handle_connect' );

/**
 * Handle the return from PayPal seller onboarding.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_handle_onboarding_return() {
	// Check for short param name (cppc_onboard) used due to PayPal's 127 char URL limit.
	if ( ! isset( $_GET['cppc_onboard'] ) ) {
		return;
	}

	// This should be an admin page.
	if ( ! current_user_can( 'manage_charitable_settings' ) ) {
		return;
	}

	// 'm' param: 's' = sandbox, 'l' = live.
	$mode_param = isset( $_GET['m'] ) ? sanitize_text_field( $_GET['m'] ) : 's';
	$mode       = 's' === $mode_param ? 'sandbox' : 'live';

	/* CSRF defense. The connect handler stored a short single-use token in
	 * a transient and embedded a copy in the return URL. PayPal preserves
	 * query string params on its redirect, so a legitimate return arrives
	 * with a token that matches what we stored. Verification gates the
	 * merchant_id write below — without it, an attacker who phishes an
	 * admin click on a forged URL could overwrite `live_seller_merchant_id`
	 * with their own PayPal merchant ID (direct mode: donations routed to
	 * attacker; middleware mode: donations break until re-onboard). Token
	 * is consumed on first verification so a replay can't re-enter even
	 * within the transient TTL window. */
	$supplied_token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';
	$expected_token = (string) get_transient( 'charitable_paypal_onboarding_csrf_' . $mode );

	if ( '' === $expected_token || '' === $supplied_token || ! hash_equals( $expected_token, $supplied_token ) ) {
		set_transient(
			'charitable_paypal_commerce_error',
			__( 'Could not verify the PayPal onboarding response. Please start the connection again.', 'charitable' ),
			60
		);
		wp_safe_redirect(
			admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_error=1' )
		);
		exit;
	}

	delete_transient( 'charitable_paypal_onboarding_csrf_' . $mode );

	// Get the returned parameters.
	$merchant_id_in_paypal = isset( $_GET['merchantIdInPayPal'] ) ? sanitize_text_field( $_GET['merchantIdInPayPal'] ) : '';
	$permissions_granted   = isset( $_GET['permissionsGranted'] ) && 'true' === $_GET['permissionsGranted'];
	$consent_status        = isset( $_GET['consentStatus'] ) && 'true' === $_GET['consentStatus'];
	$is_email_confirmed    = isset( $_GET['isEmailConfirmed'] ) && 'true' === $_GET['isEmailConfirmed'];
	$account_status        = isset( $_GET['accountStatus'] ) ? sanitize_text_field( $_GET['accountStatus'] ) : '';

	// Validate we got a merchant ID.
	if ( empty( $merchant_id_in_paypal ) ) {
		set_transient(
			'charitable_paypal_commerce_error',
			__( 'PayPal did not return a merchant ID. Please try again.', 'charitable' ),
			60
		);
		wp_safe_redirect(
			admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_error=1' )
		);
		exit;
	}

	// Check if permissions were granted.
	if ( ! $permissions_granted ) {
		set_transient(
			'charitable_paypal_commerce_error',
			__( 'You must grant permissions to WP Charitable to process payments on your behalf.', 'charitable' ),
			60
		);
		wp_safe_redirect(
			admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_error=1' )
		);
		exit;
	}

	// Save the seller merchant ID.
	$settings_key = 'sandbox' === $mode ? 'sandbox_seller_merchant_id' : 'live_seller_merchant_id';

	// Get current gateway settings.
	$gateway_settings = get_option( 'charitable_settings', array() );

	if ( ! isset( $gateway_settings['gateways_paypal_commerce'] ) ) {
		$gateway_settings['gateways_paypal_commerce'] = array();
	}

	$gateway_settings['gateways_paypal_commerce'][ $settings_key ] = $merchant_id_in_paypal;

	update_option( 'charitable_settings', $gateway_settings );

	// Reload the gateway with the freshly stored merchant_id so subsequent calls below
	// see the new value rather than the in-memory copy from before update_option().
	$gateway = new Charitable_Gateway_Paypal_Commerce();

	// Register this site with the middleware (creates the paypal_connected_customers row).
	// Without this, the middleware has no record of the (merchant_id, secret, site_url) tuple
	// and every authenticated API call (access-token, seller status, orders) returns 401.
	if ( $gateway->is_middleware_mode() ) {
		$referral_id = get_transient( 'charitable_paypal_onboarding_referral_id_' . $mode );
		if ( ! empty( $referral_id ) ) {
			// Read license_key via the canonical helper. Earlier code read
			// $root_settings['license_key'] (a UI field key, not the storage
			// key) which seeded the broker with an empty key on onboarding.
			$license_key = function_exists( 'charitable_paypal_get_license_key' ) ? charitable_paypal_get_license_key() : '';

			$middleware = Charitable_PayPal_Middleware_Client::get_instance();
			$exchange   = $middleware->exchange_credentials( array(
				'secret'         => $gateway->get_middleware_secret(),
				'referral_token' => $referral_id,
				'merchant_id'    => $merchant_id_in_paypal,
				'site_url'       => home_url( '/' ),
				'license_key'    => $license_key,
				'webhooks_url'   => $gateway->get_webhook_url(),
			) );

			if ( is_wp_error( $exchange ) ) {
				/* Leave the referral_id transient in place on failure. The
				 * merchant_id was saved to options before this call, so the
				 * site appears connected, but the middleware has no record.
				 * If we deleted the transient here, a subsequent return-URL
				 * replay would fall through to the success message at the
				 * bottom of this function without ever calling
				 * exchange_credentials again — leaving the merchant
				 * permanently half-onboarded. Retaining the transient lets
				 * the admin retry the connection without first disconnecting. */
				Charitable_PayPal_Logger::error(
					'paypal.oauth.credentials_exchange_failed',
					'Failed to register site with middleware after PayPal redirect',
					array(
						'merchant_id' => $merchant_id_in_paypal,
						'error'       => $exchange->get_error_message(),
					)
				);
				set_transient(
					'charitable_paypal_commerce_error',
					/* translators: %s: error message */
					sprintf( __( 'PayPal account connected, but middleware registration failed: %s', 'charitable' ), $exchange->get_error_message() ),
					60
				);
				wp_safe_redirect(
					admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_error=1' )
				);
				exit;
			}

			delete_transient( 'charitable_paypal_onboarding_referral_id_' . $mode );
		}
	}

	// Clear the tracking ID transient.
	delete_transient( 'charitable_paypal_onboarding_tracking_' . $mode );

	// Set success message.
	$message = __( 'Successfully connected to PayPal!', 'charitable' );

	if ( ! $is_email_confirmed ) {
		$message .= ' ' . __( 'Please confirm your email address on paypal.com to receive payments.', 'charitable' );
	}

	set_transient( 'charitable_paypal_commerce_success', $message, 60 );

	// Redirect to settings page.
	wp_safe_redirect(
		admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_connected=1' )
	);
	exit;
}
add_action( 'admin_init', 'charitable_paypal_commerce_handle_onboarding_return' );

/**
 * Handle the "Disconnect" button click.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_handle_disconnect() {
	if ( ! isset( $_GET['charitable_paypal_commerce_disconnect'] ) ) {
		return;
	}

	// Verify nonce and permissions. Friendly redirect rather than hard wp_die —
	// a nonce can quietly expire while the admin leaves the settings tab open.
	if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'charitable_paypal_disconnect' ) ) {
		charitable_paypal_commerce_admin_error_redirect(
			__( 'Your session expired before the disconnect could complete. Please try again.', 'charitable' )
		);
	}

	if ( ! current_user_can( 'manage_charitable_settings' ) ) {
		charitable_paypal_commerce_admin_error_redirect(
			__( 'You do not have permission to perform this action.', 'charitable' )
		);
	}

	$mode = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : 'sandbox';

	/* Clear the merchant ID and the per-mode middleware secret. The secret
	 * is the shared symmetric credential used to authenticate this site to
	 * the middleware. Disconnecting should rotate it so a subsequent
	 * reconnect provisions a fresh credential rather than reusing the
	 * stale one — important if the disconnect was triggered to invalidate
	 * a leaked secret, and matches the "fresh start" semantic admins
	 * expect after disconnecting. The next onboarding pass auto-generates
	 * a new secret via Charitable_Gateway_Paypal_Commerce::get_or_create_middleware_secret(). */
	$merchant_key = 'sandbox' === $mode ? 'sandbox_seller_merchant_id' : 'live_seller_merchant_id';
	$secret_key   = 'sandbox' === $mode ? 'sandbox_middleware_secret'   : 'live_middleware_secret';

	$gateway_settings = get_option( 'charitable_settings', array() );
	$dirty            = false;

	if ( isset( $gateway_settings['gateways_paypal_commerce'][ $merchant_key ] ) ) {
		unset( $gateway_settings['gateways_paypal_commerce'][ $merchant_key ] );
		$dirty = true;
	}
	if ( isset( $gateway_settings['gateways_paypal_commerce'][ $secret_key ] ) ) {
		unset( $gateway_settings['gateways_paypal_commerce'][ $secret_key ] );
		$dirty = true;
	}

	if ( $dirty ) {
		update_option( 'charitable_settings', $gateway_settings );
	}

	/* Drop the cached middleware bearer token for this mode. Without this,
	 * get_access_token() returns a token tied to the previous secret on
	 * the next request, and every middleware call 401s until the transient
	 * expires. */
	delete_transient( 'charitable_middleware_token_' . $mode );

	set_transient(
		'charitable_paypal_commerce_success',
		__( 'PayPal account disconnected.', 'charitable' ),
		60
	);

	wp_safe_redirect(
		admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&paypal_disconnected=1' )
	);
	exit;
}
add_action( 'admin_init', 'charitable_paypal_commerce_handle_disconnect' );

/**
 * Finalize a donation after the buyer returns from PayPal hosted checkout.
 *
 * Form-submit flow only — the SDK-button flow finalizes via JS onApprove ->
 * AJAX `charitable_paypal_commerce_capture_order`. After the buyer approves
 * the order on PayPal, PayPal redirects to experience_context.return_url with
 * `?token=<order_id>&PayerID=<payer_id>` appended. We need to:
 *
 *   1. Find the pending donation by `_paypal_commerce_order_id` matching the token.
 *   2. Capture the order via PayPal Orders API.
 *   3. Update donation status (charitable-pending -> charitable-completed).
 *   4. Persist capture_id + payer_email + structured log row.
 *   5. Redirect the buyer to the donation receipt.
 *
 * Without this handler, donations created via the form-submit flow stay in
 * charitable-pending forever even after the buyer authorizes payment.
 *
 * @since 1.8.11
 *
 * @return void
 */
function charitable_paypal_commerce_handle_donation_return() {
	// Only act when both PayPal redirect-back params are present. Stay quiet otherwise.
	if ( ! isset( $_GET['token'] ) || ! isset( $_GET['PayerID'] ) ) {
		return;
	}

	// Cancel return is handled by a separate hook below — leave it alone here.
	if ( isset( $_GET['paypal_cancel'] ) ) {
		return;
	}

	$order_id = sanitize_text_field( wp_unslash( $_GET['token'] ) );
	if ( '' === $order_id ) {
		return;
	}

	// Find the pending donation by stored _paypal_commerce_order_id meta.
	// Direct SQL: WP get_posts(post_status => 'any') excludes Charitable's custom
	// statuses (they have exclude_from_search => true), so a get_posts lookup
	// would silently return empty. See MEMORY note "WordPress get_posts + Charitable Custom Statuses".
	global $wpdb;
	$donation_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key = %s AND meta_value = %s
			 LIMIT 1",
			'_paypal_commerce_order_id',
			$order_id
		)
	);

	if ( $donation_id <= 0 ) {
		return; // Token isn't ours — leave the request alone for someone else to handle.
	}
	$donation    = charitable_get_donation( $donation_id );
	if ( ! $donation ) {
		return;
	}

	/* Idempotent guard. Already-completed donations redirect straight to the
	 * receipt. Already-failed donations also short-circuit so a browser
	 * replay of the return URL after an earlier capture failure doesn't
	 * trigger a duplicate capture attempt (PayPal would reject it, but the
	 * status-write side effects would still run). */
	$existing_status = $donation->get_status();
	if ( 'charitable-completed' === $existing_status || 'charitable-failed' === $existing_status ) {
		wp_safe_redirect( charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) ) );
		exit;
	}

	/* Cross-request lock around the capture+status work. PayPal capture itself
	 * is idempotent, but `update_status` fires donation_complete + email hooks
	 * on the FIRST transition. Two concurrent return-URL hits (browser refresh,
	 * double redirect, server retry) racing past the idempotent guard above
	 * could both flip status before either commit — duplicate confirmation
	 * emails. TTL covers a typical capture round-trip (~30s) plus margin. */
	$return_lock_key = 'charitable_paypal_return_lock_' . $donation_id;
	if ( false !== get_transient( $return_lock_key ) ) {
		// Another request is already capturing this donation — let it finish
		// and bounce the donor to the receipt page (which will reflect the
		// status as soon as the in-flight request commits).
		wp_safe_redirect( charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) ) );
		exit;
	}
	set_transient( $return_lock_key, time(), 2 * MINUTE_IN_SECONDS );

	Charitable_PayPal_Logger::info(
		'paypal.return.donation_received',
		'Buyer returned from PayPal hosted checkout',
		array(
			'order_id'    => $order_id,
			'donation_id' => $donation_id,
		),
		array(
			'donation_id' => $donation_id,
		)
	);

	$gateway = new Charitable_Gateway_Paypal_Commerce();
	$capture = $gateway->capture_order( $order_id );

	if ( is_wp_error( $capture ) ) {
		Charitable_PayPal_Logger::error(
			'paypal.return.capture_failed',
			'Order capture failed on return',
			array(
				'order_id'    => $order_id,
				'donation_id' => $donation_id,
				'error'       => $capture->get_error_message(),
			),
			array(
				'donation_id' => $donation_id,
			)
		);
		$donation->update_status( 'charitable-failed' );
		$donation->log()->add( sprintf(
			/* translators: %s: error message */
			__( 'PayPal capture failed on return: %s', 'charitable' ),
			$capture->get_error_message()
		) );
		delete_transient( $return_lock_key );
		wp_safe_redirect( charitable_get_permalink( 'donation_cancel_page' ) );
		exit;
	}

	$capture_data   = $capture['purchase_units'][0]['payments']['captures'][0] ?? array();
	$capture_status = $capture_data['status'] ?? ( $capture['status'] ?? '' );
	$capture_id     = $capture_data['id'] ?? '';
	$payer_email    = $capture['payer']['email_address'] ?? '';

	if ( $capture_id ) {
		update_post_meta( $donation_id, '_paypal_commerce_capture_id', $capture_id );
		$donation->set_gateway_transaction_id( $capture_id );
	}
	if ( $payer_email ) {
		update_post_meta( $donation_id, '_paypal_commerce_payer_email', $payer_email );
	}

	if ( 'COMPLETED' === $capture_status ) {
		$donation->update_status( 'charitable-completed' );
	} elseif ( 'PENDING' === $capture_status ) {
		// PayPal flagged for review — keep as pending; webhook will finalize.
		$donation->update_status( 'charitable-pending' );
	} else {
		$donation->update_status( 'charitable-failed' );
	}

	Charitable_PayPal_Logger::info(
		'paypal.order.captured',
		'Order captured',
		array(
			'order_id'        => $order_id,
			'capture_id'      => $capture_id,
			'capture_status'  => $capture_status,
			'amount'          => $capture_data['amount']['value'] ?? '',
			'currency'        => $capture_data['amount']['currency_code'] ?? '',
			'paypal_debug_id' => $capture['paypal_debug_id'] ?? ( $capture['debug_id'] ?? null ),
			'donation_id'     => $donation_id,
		),
		array(
			'donation_id' => $donation_id,
		)
	);

	delete_transient( $return_lock_key );
	wp_safe_redirect( charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) ) );
	exit;
}
add_action( 'init', 'charitable_paypal_commerce_handle_donation_return' );

/**
 * Handle the cancel return URL when buyer abandons PayPal hosted checkout.
 *
 * The form-submit flow's order creation sets cancel_url to
 * `home_url('/?paypal_cancel=1&donation_id=N')`. When the donor cancels at
 * PayPal's hosted checkout, PayPal redirects there. We mark the donation as
 * charitable-cancelled (rather than leaving it stranded in pending) and
 * redirect the donor to the campaign's cancel page.
 *
 * Already-completed donations are left alone (idempotent).
 *
 * @since 1.8.11
 *
 * @return void
 */
function charitable_paypal_commerce_handle_donation_cancel() {
	if ( ! isset( $_GET['paypal_cancel'] ) || '1' !== (string) $_GET['paypal_cancel'] ) {
		return;
	}
	if ( ! isset( $_GET['donation_id'] ) ) {
		return;
	}

	$donation_id = (int) $_GET['donation_id'];
	if ( $donation_id <= 0 ) {
		return;
	}

	$donation = charitable_get_donation( $donation_id );
	if ( ! $donation ) {
		return;
	}

	/* Refuse to act on donations that don't belong to PayPal Commerce.
	 * `charitable-pending` is shared across gateways (Stripe, Square, offline),
	 * and donation_id is a sequential post ID — without this guard an
	 * unauthenticated visitor could enumerate `?paypal_cancel=1&donation_id=N`
	 * and cancel pending donations on any gateway. */
	if ( Charitable_Gateway_Paypal_Commerce::ID !== $donation->get_gateway() ) {
		return;
	}

	/* PayPal appends `?token={ORDER_ID}` to the cancel_url when redirecting.
	 * Verify the supplied token matches the PayPal order ID we stored at
	 * order-creation time. This blocks enumeration even within the
	 * paypal_commerce gateway: an attacker who guesses a valid donation_id
	 * still can't guess the matching PayPal order ID. */
	$expected_order_id = get_post_meta( $donation_id, '_paypal_commerce_order_id', true );
	$supplied_token    = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
	if ( empty( $expected_order_id ) || ! hash_equals( (string) $expected_order_id, $supplied_token ) ) {
		return;
	}

	if ( 'charitable-pending' === $donation->get_status() ) {
		$donation->update_status( 'charitable-cancelled' );
		$donation->log()->add( __( 'Donor cancelled the PayPal checkout.', 'charitable' ) );
		Charitable_PayPal_Logger::info(
			'paypal.return.donation_cancelled',
			'Buyer cancelled at PayPal hosted checkout',
			array(
				'donation_id' => $donation_id,
			),
			array(
				'donation_id' => $donation_id,
			)
		);
	}

	// Redirect the donor back to the campaign donation page they came from.
	// Falling back through Charitable's `donation_cancel_page` endpoint produces
	// inconsistent results across themes/configurations; sending the donor to
	// the campaign donation form is the most familiar UX and lets them retry.
	//
	// Note: $donation->get_campaigns() returns an array of campaign *names*
	// (strings) keyed by campaign_donation_id, NOT campaign objects with IDs.
	// To get the actual campaign ID we have to walk get_campaign_donations()
	// which returns stdClass records carrying campaign_id.
	$redirect_url        = '';
	$campaign_donations  = $donation->get_campaign_donations();
	$campaign_donation   = is_array( $campaign_donations ) ? current( $campaign_donations ) : null;
	$campaign_id         = is_object( $campaign_donation ) ? (int) ( $campaign_donation->campaign_id ?? 0 ) : 0;
	if ( $campaign_id > 0 ) {
		$campaign_url = charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $campaign_id ) );
		if ( empty( $campaign_url ) ) {
			$campaign_url = get_permalink( $campaign_id );
		}
		if ( ! empty( $campaign_url ) ) {
			$redirect_url = add_query_arg(
				array(
					'donation_cancelled' => 1,
					'donation_id'        => $donation_id,
				),
				$campaign_url
			);
		}
	}
	if ( empty( $redirect_url ) ) {
		$redirect_url = home_url( '/' );
	}

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'init', 'charitable_paypal_commerce_handle_donation_cancel' );

/**
 * Display admin notices for PayPal Commerce onboarding.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_admin_notices() {
	// Only show on Charitable admin pages.
	$screen = get_current_screen();
	if ( ! $screen || false === strpos( $screen->id, 'charitable' ) ) {
		return;
	}

	// Check for error message.
	$error = get_transient( 'charitable_paypal_commerce_error' );
	if ( $error ) {
		delete_transient( 'charitable_paypal_commerce_error' );
		echo '<div class="notice notice-error is-dismissible"><p><strong>PayPal Commerce:</strong> ' . esc_html( $error ) . '</p></div>';
	}

	// Check for success message.
	$success = get_transient( 'charitable_paypal_commerce_success' );
	if ( $success ) {
		delete_transient( 'charitable_paypal_commerce_success' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>PayPal Commerce:</strong> ' . esc_html( $success ) . '</p></div>';
	}

	// Check for consent revoked warning.
	$consent_revoked = get_transient( 'charitable_paypal_commerce_consent_revoked' );
	if ( $consent_revoked ) {
		echo '<div class="notice notice-warning"><p><strong>PayPal Commerce:</strong> ' . esc_html( $consent_revoked ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'charitable_paypal_commerce_admin_notices' );

/**
 * Add a body class on the PayPal Commerce settings page so the dedicated
 * CSS rules in assets/scss/admin/charitable-admin-legacy.scss can scope to
 * just this gateway's settings page.
 *
 * @since 1.8.11.1
 *
 * @param string $classes Existing body classes.
 * @return string
 */
function charitable_paypal_commerce_admin_body_class( $classes ) {
	if ( ! isset( $_GET['page'], $_GET['tab'], $_GET['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $classes;
	}
	if ( 'charitable-settings' !== $_GET['page'] || 'gateways' !== $_GET['tab'] || 'gateways_paypal_commerce' !== $_GET['group'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $classes;
	}
	return $classes . ' charitable-paypal-commerce-settings';
}
add_filter( 'admin_body_class', 'charitable_paypal_commerce_admin_body_class' );

/**
 * Handle the "Auto-Create Webhook" button click.
 *
 * @since 1.8.11
 */
function charitable_paypal_commerce_handle_create_webhook() {
	if ( ! isset( $_GET['charitable_paypal_create_webhook'] ) ) {
		return;
	}

	// Verify nonce and permissions. Friendly redirect rather than hard wp_die —
	// a nonce can quietly expire while the admin leaves the settings tab open.
	if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'charitable_paypal_create_webhook' ) ) {
		charitable_paypal_commerce_admin_error_redirect(
			__( 'Your session expired before the webhook could be created. Please try again.', 'charitable' )
		);
	}

	if ( ! current_user_can( 'manage_charitable_settings' ) ) {
		charitable_paypal_commerce_admin_error_redirect(
			__( 'You do not have permission to perform this action.', 'charitable' )
		);
	}

	$mode    = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : 'sandbox';
	$gateway = new Charitable_Gateway_Paypal_Commerce();

	// Create the webhook.
	$result = $gateway->create_webhook();

	if ( is_wp_error( $result ) ) {
		set_transient( 'charitable_paypal_commerce_error', $result->get_error_message(), 60 );
		wp_safe_redirect(
			admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&webhook_error=1' )
		);
		exit;
	}

	// Save the webhook ID.
	$webhook_id   = $result['id'] ?? '';
	$settings_key = 'sandbox' === $mode ? 'sandbox_webhook_id' : 'live_webhook_id';

	$gateway_settings = get_option( 'charitable_settings', array() );

	if ( ! isset( $gateway_settings['gateways_paypal_commerce'] ) ) {
		$gateway_settings['gateways_paypal_commerce'] = array();
	}

	$gateway_settings['gateways_paypal_commerce'][ $settings_key ] = $webhook_id;
	update_option( 'charitable_settings', $gateway_settings );

	set_transient(
		'charitable_paypal_commerce_success',
		sprintf(
			/* translators: %s: webhook ID */
			__( 'Webhook created successfully! Webhook ID: %s', 'charitable' ),
			$webhook_id
		),
		60
	);

	wp_safe_redirect(
		admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce&webhook_created=1' )
	);
	exit;
}
add_action( 'admin_init', 'charitable_paypal_commerce_handle_create_webhook' );

// No nopriv: changing the default requires a logged-in donor.

/* ===== LOAD RECURRING INTEGRATION ===== */

/**
 * Map a raw PayPal API error message / issue code to a friendly donor message.
 *
 * PayPal's API surfaces technical strings like "The merchant account is
 * restricted." or "INSTRUMENT_DECLINED". Donors don't know what those mean
 * and there's no useful action behind them. This helper translates the
 * common ones into plain language with a recovery hint where one exists.
 *
 * @since 1.8.11
 *
 * @param  string $raw_message Raw error message from WP_Error.
 * @param  mixed  $error_data  WP_Error data — may be array carrying PayPal's
 *                             upstream response details.
 * @return string Friendly donor-facing message.
 */
function charitable_paypal_commerce_friendly_payment_error( $raw_message, $error_data = null ) {
	$issue = '';
	if ( is_array( $error_data ) ) {
		// Middleware-wrapped errors come back one level deeper (body.body.*)
		// than direct PayPal calls (body.*). Check both depths so the issue
		// code is extracted regardless of mode.
		$candidates = array(
			$error_data['details'][0]['issue'] ?? null,
			$error_data['body']['details'][0]['issue'] ?? null,
			$error_data['body']['body']['details'][0]['issue'] ?? null,
			$error_data['name'] ?? null,
			$error_data['body']['name'] ?? null,
			$error_data['body']['body']['name'] ?? null,
		);
		foreach ( $candidates as $c ) {
			if ( is_string( $c ) && '' !== $c ) {
				$issue = $c;
				break;
			}
		}
	}

	$by_issue = array(
		'PAYEE_ACCOUNT_RESTRICTED'    => __( 'Online donations via PayPal are temporarily unavailable. Please try a different payment method, or contact us to complete your donation.', 'charitable' ),
		'PAYEE_ACCOUNT_NOT_VERIFIED'  => __( 'Online donations via PayPal are temporarily unavailable. Please try a different payment method, or contact us to complete your donation.', 'charitable' ),
		'PAYEE_ACCOUNT_LOCKED_OR_CLOSED' => __( 'Online donations via PayPal are temporarily unavailable. Please try a different payment method, or contact us to complete your donation.', 'charitable' ),
		'INSTRUMENT_DECLINED'         => __( 'Your payment method was declined. Please try a different card or use PayPal balance.', 'charitable' ),
		'PAYMENT_DENIED'              => __( 'Your payment was declined by PayPal. Please try a different card or payment method.', 'charitable' ),
		'PAYER_ACCOUNT_RESTRICTED'    => __( 'Your PayPal account cannot be used for this donation right now. Please try a different payment method.', 'charitable' ),
		'PAYER_ACCOUNT_LOCKED_OR_CLOSED' => __( 'Your PayPal account cannot be used for this donation right now. Please try a different payment method.', 'charitable' ),
		'PAYER_ACTION_REQUIRED'       => __( 'PayPal needs additional verification. Please complete the steps in the PayPal window and try again.', 'charitable' ),
		'TRANSACTION_REFUSED'         => __( 'PayPal could not process this donation. Please try a different payment method.', 'charitable' ),
		'COUNTRY_NOT_SUPPORTED'       => __( 'Donations via PayPal are not supported from your country. Please try a different payment method.', 'charitable' ),
		'CURRENCY_NOT_SUPPORTED_FOR_COUNTRY' => __( 'PayPal does not support the campaign currency for your account. Please try a different payment method.', 'charitable' ),
		'INVALID_CURRENCY_CODE'       => __( 'PayPal does not support the campaign currency for your account. Please try a different payment method.', 'charitable' ),
		'AUTHENTICATION_FAILURE'      => __( 'Your bank declined this donation. Please try a different card or payment method.', 'charitable' ),
		'INSUFFICIENT_FUNDS'          => __( 'The payment was declined for insufficient funds. Please try a different card or payment method.', 'charitable' ),
	);

	if ( '' !== $issue && isset( $by_issue[ $issue ] ) ) {
		return $by_issue[ $issue ];
	}

	/* Pattern fallbacks for cases where the issue code wasn't extracted —
	 * middleware-mode wrapping sometimes flattens the structure. */
	$patterns = array(
		'/merchant account.*restricted|payee.*restricted/i'         => $by_issue['PAYEE_ACCOUNT_RESTRICTED'],
		'/(card|payment).*declined|instrument.*declined/i'          => $by_issue['INSTRUMENT_DECLINED'],
		'/insufficient funds/i'                                     => $by_issue['INSUFFICIENT_FUNDS'],
		'/payer.*action.*required|verification/i'                   => $by_issue['PAYER_ACTION_REQUIRED'],
	);
	foreach ( $patterns as $pattern => $msg ) {
		if ( preg_match( $pattern, $raw_message ) ) {
			return $msg;
		}
	}

	return __( 'We could not process this donation via PayPal right now. Please try a different payment method or try again in a few minutes.', 'charitable' );
}

/**
 * Write a single ERROR-level log entry summarizing what the donor saw.
 *
 * The middleware client already emits debug-level request/response rows
 * for the underlying PayPal call, but those are noisy and the actual
 * rejection detail is buried inside the response JSON. Support staff
 * scanning Charitable → Tools → Logs need a one-row, scannable record
 * showing: the friendly message the donor was actually shown, the raw
 * PayPal text behind it, the issue code (when extractable), the
 * paypal-debug-id (when PayPal returned one), and basic donation
 * context (amount, campaign, payment method).
 *
 * @since 1.8.11
 *
 * @param  string   $event          Structured event slug (e.g. paypal.order.create_donor_facing_error).
 * @param  string   $title          Human-readable one-line summary for the log row.
 * @param  WP_Error $error          The WP_Error returned by the gateway/middleware.
 * @param  array    $donor_response The shape returned by format_error_for_donor.
 * @param  array    $context        Extra donation context (amount, campaign_id, etc).
 * @return void
 */
function charitable_paypal_commerce_log_donor_facing_error( $event, $title, $error, $donor_response, $context = array() ) {
	$error_data = $error->get_error_data();

	$issue = '';
	$debug_id = '';
	if ( is_array( $error_data ) ) {
		// Middleware-wrapped errors come back one level deeper (body.body.*)
		// than direct PayPal calls (body.*). Check both depths for both the
		// issue code and the paypal-debug-id so support staff can grep a
		// real debug_id from the structured log row regardless of mode.
		$issue_candidates = array(
			$error_data['details'][0]['issue'] ?? null,
			$error_data['body']['details'][0]['issue'] ?? null,
			$error_data['body']['body']['details'][0]['issue'] ?? null,
			$error_data['name'] ?? null,
			$error_data['body']['name'] ?? null,
			$error_data['body']['body']['name'] ?? null,
		);
		foreach ( $issue_candidates as $c ) {
			if ( is_string( $c ) && '' !== $c ) { $issue = $c; break; }
		}
		$debug_candidates = array(
			$error_data['debug_id'] ?? null,
			$error_data['body']['debug_id'] ?? null,
			$error_data['body']['body']['debug_id'] ?? null,
			$error_data['paypal_debug_id'] ?? null,
		);
		foreach ( $debug_candidates as $c ) {
			if ( is_string( $c ) && '' !== $c ) { $debug_id = $c; break; }
		}
	}

	$payload = array_merge(
		array(
			'donor_message_shown' => isset( $donor_response['message'] ) ? (string) $donor_response['message'] : '',
			'paypal_raw_message'  => $error->get_error_message(),
			'paypal_issue_code'   => $issue,
			'paypal_debug_id'     => $debug_id,
			'wp_error_code'       => $error->get_error_code(),
		),
		$context
	);

	Charitable_PayPal_Logger::error( $event, $title, $payload );
}

/**
 * Build the AJAX error payload for PayPal failures, with admin-only detail.
 *
 * Donors get a friendly message; site admins (anyone with
 * `manage_charitable_settings`) additionally get the raw PayPal message
 * and any PayPal debug ID — enough to triage without checking the logs.
 *
 * @since 1.8.11
 *
 * @param  WP_Error $error WP_Error from the gateway / middleware client.
 * @return array Shape: { message, detail?, debug_id?, is_admin? }.
 */
function charitable_paypal_commerce_format_error_for_donor( $error ) {
	$raw_message = $error->get_error_message();
	$error_data  = $error->get_error_data();

	$response = array(
		'message' => charitable_paypal_commerce_friendly_payment_error( $raw_message, $error_data ),
	);

	if ( current_user_can( 'manage_charitable_settings' ) || current_user_can( 'manage_options' ) ) {
		$response['is_admin'] = true;
		$response['detail']   = $raw_message;

		$debug_id = '';
		if ( is_array( $error_data ) ) {
			// Middleware-wrapped errors carry debug_id one level deeper.
			$candidates = array(
				$error_data['debug_id'] ?? null,
				$error_data['body']['debug_id'] ?? null,
				$error_data['body']['body']['debug_id'] ?? null,
				$error_data['paypal_debug_id'] ?? null,
			);
			foreach ( $candidates as $c ) {
				if ( is_string( $c ) && '' !== $c ) {
					$debug_id = $c;
					break;
				}
			}
		}
		if ( '' !== $debug_id ) {
			$response['debug_id'] = $debug_id;
		}
	}

	return $response;
}

/**
 * Optionally append a Lite-tier platform fee line to the donation log.
 *
 * Off by default. Sites can opt in via:
 *   add_filter( 'charitable_paypal_log_platform_fee_on_donation', '__return_true' );
 *
 * Only writes a log entry when the capture response actually carries a
 * platform fee — Pro sites without a fee never see this line even with
 * the filter on. The fee data is read from PayPal's capture response
 * (`purchase_units[0].payments.captures[0].seller_receivable_breakdown.platform_fees`)
 * which is the authoritative number that was deducted by PayPal.
 *
 * Note: Charitable's donation log is append-only at event time. Flipping
 * the filter on later does NOT backfill old donations. The structured
 * PayPal logger (`Charitable_PayPal_Logger`) archives the full capture
 * response for every donation regardless of this filter, so retroactive
 * fee inspection is always possible by querying `wp_charitable_logs`.
 *
 * @since 1.8.11
 *
 * @param Charitable_Donation $donation Donation object.
 * @param array               $capture  Decoded PayPal capture response.
 * @return void
 */
function charitable_paypal_commerce_maybe_log_platform_fee( $donation, $capture ) {
	if ( ! apply_filters( 'charitable_paypal_log_platform_fee_on_donation', false, $donation, $capture ) ) {
		return;
	}

	$platform_fees = $capture['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['platform_fees'] ?? array();
	if ( empty( $platform_fees ) || ! is_array( $platform_fees ) ) {
		return;
	}

	$fee = $platform_fees[0]['amount'] ?? array();
	$fee_value    = isset( $fee['value'] ) ? (string) $fee['value'] : '';
	$fee_currency = isset( $fee['currency_code'] ) ? (string) $fee['currency_code'] : '';
	if ( '' === $fee_value || '' === $fee_currency ) {
		return;
	}

	/* charitable_format_money() prepends the locale's currency symbol
	 * ($, €, £, etc.) and respects the site's decimal/thousands settings.
	 * The currency code is appended explicitly so symbol-overlap currencies
	 * (USD/CAD/AUD all use "$") remain unambiguous in the log. */
	$formatted_amount = function_exists( 'charitable_format_money' )
		? charitable_format_money( $fee_value, false, false, $fee_currency )
		: $fee_value;

	$donation->log()->add(
		sprintf(
			/* translators: 1: formatted fee amount with currency symbol (e.g. "$0.09"), 2: currency code (e.g. "USD") */
			__( '%1$s %2$s Deducted By PayPal', 'charitable' ),
			$formatted_amount,
			$fee_currency
		)
	);
}

/**
 * Force-disable page cache when a PayPal Commerce donor-return URL is being requested.
 *
 * The four PayPal donor-return URLs land on `home_url('/')` with a query string,
 * NOT on a registered Charitable endpoint:
 *
 *   - ?paypal_cancel=1&donation_id=N&token=...
 *   - ?paypal_subscription_cancel=1&subscription_id=I-...
 *   - ?paypal_subscription_return=1&subscription_id=I-...
 *   - ?token=...&PayerID=...   (form-submit donation approval return)
 *
 * Without an active cache-skip signal, full-page caches (WP Rocket, W3TC,
 * WP Super Cache, LiteSpeed) can serve the cached homepage HTML and the
 * PHP return-handler never runs, leaving the donation stuck at
 * `charitable-pending`. Fan out to all named cache plugins via
 * `charitable_do_not_cache` + `DONOTCACHEPAGE` + `nocache_headers()` for
 * belt-and-braces protection against reverse proxies.
 *
 * Hooks at init priority 0 so it fires before any handler that reads these
 * query strings (handle_donation_return / handle_donation_cancel are on init,
 * the *_subscription_* handlers are on template_redirect — all later than
 * init:0).
 *
 * @since 1.8.11
 *
 * @return void
 */
function charitable_paypal_commerce_disable_cache_on_return() {
	// Read-only inspection of $_GET to decide cache behavior. The actual return
	// handlers below verify nonces / tokens before doing anything stateful.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$is_paypal_return = ! empty( $_GET['paypal_cancel'] )
		|| ! empty( $_GET['paypal_subscription_cancel'] )
		|| ! empty( $_GET['paypal_subscription_return'] )
		|| ( ! empty( $_GET['token'] ) && ! empty( $_GET['PayerID'] ) );
	// phpcs:enable

	if ( ! $is_paypal_return ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();

	do_action( 'charitable_do_not_cache' );
}
add_action( 'init', 'charitable_paypal_commerce_disable_cache_on_return', 0 );

/**
 * Send no-cache headers on every PayPal Commerce AJAX request.
 *
 * WordPress core's `wp_send_json_*` does not call `nocache_headers()`. Every
 * named cache plugin (and Cloudflare's default rules) excludes admin-ajax.php
 * by URL — so real-world impact is low — but a custom reverse proxy with
 * permissive rules could cache an authenticated AJAX response and leak per-
 * request tokens (client tokens, vault setup tokens, order IDs) across donors.
 *
 * Hook on `admin_init` (fires before any `wp_ajax_*` action callback) and
 * scope by action prefix. All PayPal Commerce handlers register actions
 * starting with `charitable_paypal_commerce_`.
 *
 * @since 1.8.11
 *
 * @return void
 */
function charitable_paypal_commerce_ajax_no_cache_headers() {
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only inspection of the action name to decide whether to send headers; individual handlers verify their own nonces.
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	// phpcs:enable

	if ( '' === $action || 0 !== strpos( $action, 'charitable_paypal_commerce_' ) ) {
		return;
	}

	nocache_headers();
}
add_action( 'admin_init', 'charitable_paypal_commerce_ajax_no_cache_headers' );
