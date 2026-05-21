<?php
/**
 * Charitable PayPal Commerce Structured Logger.
 *
 * Wrapper around `charitable_log()` / `charitable_log_immediate()` that:
 * - tags entries with source `paypal_commerce` and an event taxonomy slug,
 * - injects request-level context (mode, merchant, gateway mode, request id),
 * - redacts PII / secrets before serialization,
 * - guards against writing log rows on legacy-only sites where the new
 *   PayPal Commerce gateway is not active.
 *
 * @package   Charitable/Gateways/PayPal Commerce
 * @author    WP Charitable
 * @copyright Copyright (c) 2024-2026, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.8.11
 *
 * TODO 1.8.16+ — bulk-sweep remaining `error_log()` calls in:
 *   - charitable-paypal-commerce-hooks.php (enqueue/diag noise)
 *   - class-charitable-gateway-paypal-commerce.php (capability detection,
 *     onboarding, additional webhook handlers, partner referral, oauth
 *     bootstrap, generate_client_token, generate_user_id_token)
 *   - class-charitable-recurring-paypal-commerce.php (vault renewal cron)
 *   - class-charitable-paypal-commerce-subscriptions.php (cancel/get)
 *   See PayPal-Logger-Spec.md, Section 4 + "Bonus migrations to defer".
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_PayPal_Logger' ) ) :

	/**
	 * Structured logger for the PayPal Commerce gateway.
	 *
	 * @since 1.8.11
	 */
	class Charitable_PayPal_Logger {

		/**
		 * Per-request unique ID. All entries from one HTTP request share it.
		 *
		 * @since 1.8.11
		 * @var   string|null
		 */
		private static $request_id = null;

		/**
		 * In-request dedup set, keyed on `event|hash(context_summary)`.
		 *
		 * @since 1.8.11
		 * @var   array<string,bool>
		 */
		private static $seen = array();

		/**
		 * Cache for should_log() result within a single request.
		 *
		 * @since 1.8.11
		 * @var   bool|null
		 */
		private static $should_log_cache = null;

		/**
		 * Cache for get_gateway_state() result within a single request.
		 *
		 * Avoids re-instantiating Charitable_Gateway_Paypal_Commerce and re-reading
		 * its options on every log call — important for cron loops that emit many
		 * events per execution (e.g. vault renewal cron processing 50 donations).
		 *
		 * @since 1.8.11
		 * @var   array|null
		 */
		private static $gateway_state_cache = null;

		/**
		 * Log a debug-level event (queued, dropped if logging disabled).
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event   Taxonomy slug, e.g. `paypal.order.captured`.
		 * @param  string $summary Short human-readable title.
		 * @param  array  $context Associative context (will be redacted + JSON-encoded).
		 * @param  array  $links   Optional FK overrides: donation_id, campaign_id, donor_id, user_id.
		 * @return void
		 */
		public static function debug( $event, $summary, array $context = array(), array $links = array() ) {
			self::write( 'debug', $event, $summary, $context, $links, false, false );
		}

		/**
		 * Log an info-level event (queued).
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event   Taxonomy slug.
		 * @param  string $summary Short human-readable title.
		 * @param  array  $context Associative context.
		 * @param  array  $links   Optional FK overrides.
		 * @return void
		 */
		public static function info( $event, $summary, array $context = array(), array $links = array() ) {
			self::write( 'info', $event, $summary, $context, $links, false, false );
		}

		/**
		 * Log a warning-level event (queued).
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event   Taxonomy slug.
		 * @param  string $summary Short human-readable title.
		 * @param  array  $context Associative context.
		 * @param  array  $links   Optional FK overrides.
		 * @return void
		 */
		public static function warn( $event, $summary, array $context = array(), array $links = array() ) {
			// `charitable_log()` accepts the level slug `warning`.
			self::write( 'warning', $event, $summary, $context, $links, false, false );
		}

		/**
		 * Log an error-level event (immediate write, bypasses the shutdown queue).
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event   Taxonomy slug.
		 * @param  string $summary Short human-readable title.
		 * @param  array  $context Associative context.
		 * @param  array  $links   Optional FK overrides.
		 * @return void
		 */
		public static function error( $event, $summary, array $context = array(), array $links = array() ) {
			self::write( 'error', $event, $summary, $context, $links, true, false );
		}

		/**
		 * Log a critical event (immediate, force=true even when logging is globally disabled).
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event   Taxonomy slug.
		 * @param  string $summary Short human-readable title.
		 * @param  array  $context Associative context.
		 * @param  array  $links   Optional FK overrides.
		 * @return void
		 */
		public static function critical( $event, $summary, array $context = array(), array $links = array() ) {
			self::write( 'error', $event, $summary, $context, $links, true, true );
		}

		/**
		 * Common write path.
		 *
		 * @since 1.8.11
		 *
		 * @param  string $level     One of: error, warning, info, debug.
		 * @param  string $event     Taxonomy slug.
		 * @param  string $summary   Title text.
		 * @param  array  $context   Context array.
		 * @param  array  $links     FK overrides.
		 * @param  bool   $immediate Whether to call `charitable_log_immediate()`.
		 * @param  bool   $force     Whether to set `force => true` (bypass disabled).
		 * @return void
		 */
		private static function write( $level, $event, $summary, array $context, array $links, $immediate, $force ) {
			// Legacy-isolation guard. See PayPal-Logger-Spec.md Section 8.
			if ( ! self::should_log() ) {
				return;
			}

			// Required: non-empty event slug.
			if ( ! is_string( $event ) || '' === $event ) {
				return;
			}

			// Required functions must exist (defensive — these load with the plugin).
			if ( ! function_exists( 'charitable_log' ) || ! function_exists( 'charitable_log_immediate' ) ) {
				return;
			}

			// In-request dedup. Key on event + small fingerprint of "key" context fields.
			$dedup_key = self::dedup_key( $event, $context );
			if ( isset( self::$seen[ $dedup_key ] ) ) {
				return;
			}

			// Defensive cap: in a normal request this set holds dozens of
			// entries at most. A pathological loop introduced by a future
			// regression could grow it without bound. Reset at 1000 to keep
			// memory predictable; the only consequence of a reset is one
			// possible duplicate row in the rare event that the cap is hit.
			if ( count( self::$seen ) >= 1000 ) {
				self::$seen = array();
			}
			self::$seen[ $dedup_key ] = true;

			// Auto-inject request context.
			$context = self::inject_request_context( $context );

			// Redact PII / secrets before serialization (unless bypassed).
			if ( ! self::is_redaction_bypassed() ) {
				$context = self::redact( $context, $level );
			}

			// Build title and message.
			$title   = self::format_title( $event, $summary );
			$message = self::format_message( $context );

			// Map to underlying logger args.
			$args = array(
				'type'        => self::map_type( $event ),
				'level'       => $level,
				'source'      => 'paypal_commerce',
				'object_type' => self::derive_object_type( $event ),
				'object_id'   => 0,
			);

			// Pass through typed FKs.
			foreach ( array( 'donation_id', 'campaign_id', 'donor_id', 'user_id' ) as $fk ) {
				if ( isset( $links[ $fk ] ) ) {
					$args[ $fk ] = (int) $links[ $fk ];
				}
			}

			if ( $force ) {
				$args['force'] = true;
			}

			if ( $immediate ) {
				charitable_log_immediate( $title, $message, $args );
			} else {
				charitable_log( $title, $message, $args );
			}
		}

		/**
		 * Decide whether the helper is allowed to write logs in the current request.
		 *
		 * Returns false on:
		 * - legacy-only sites (paypal_commerce gateway not active),
		 * - very-early entry points where Charitable_Gateways isn't loaded yet.
		 *
		 * Result is cached per request; pass the optional `$reset` argument
		 * (test-only) to flush the cache.
		 *
		 * @since 1.8.11
		 *
		 * @param  bool $reset Internal — reset the cache (used by tests only).
		 * @return bool
		 */
		public static function should_log( $reset = false ) {
			if ( $reset ) {
				self::$should_log_cache = null;
			}

			if ( null !== self::$should_log_cache ) {
				return self::$should_log_cache;
			}

			// Gateway registry not yet bootstrapped — return false but DON'T
			// cache that decision. The class can still load later in this
			// request (e.g., a cron callback that fires before plugins_loaded
			// completes the gateway bootstrap). Caching `false` here would
			// permanently silence the logger for the rest of the request.
			if ( ! class_exists( 'Charitable_Gateways' ) ) {
				return false;
			}

			try {
				$gateways = Charitable_Gateways::get_instance();

				if ( ! is_object( $gateways ) || ! method_exists( $gateways, 'is_active_gateway' ) ) {
					// Same reasoning as above — defer caching until the
					// registry has the expected shape.
					return false;
				}

				$active = (bool) $gateways->is_active_gateway( 'paypal_commerce' );
			} catch ( \Throwable $e ) {
				// If active_gateways option is corrupt or any other exception
				// bubbles up, fail-silent — never block donation processing
				// because the logger choked. Don't cache the failure either;
				// transient errors should be retried on the next call.
				return false;
			}

			self::$should_log_cache = $active;
			return $active;
		}

		/**
		 * Whether the redaction bypass constant is set (staging/debug only).
		 *
		 * WARNING: when this returns true, donor PII (emails, phone numbers,
		 * addresses) and full token prefixes are written unredacted to the
		 * Charitable logs table. Never define this constant on a production
		 * site. The maybe_render_log_raw_warning() admin notice surfaces the
		 * risk to anyone who lands in WP admin while the constant is active.
		 *
		 * @since 1.8.11
		 *
		 * @return bool
		 */
		private static function is_redaction_bypassed() {
			return defined( 'CHARITABLE_PAYPAL_LOG_RAW' ) && CHARITABLE_PAYPAL_LOG_RAW;
		}

		/**
		 * Render an admin notice when the redaction bypass constant is active.
		 *
		 * Hooked from `charitable-paypal-commerce-hooks.php` on `admin_notices`
		 * so site owners and operators see a prominent warning whenever
		 * CHARITABLE_PAYPAL_LOG_RAW is defined and truthy. Without this
		 * notice, a misconfigured production site could silently leak PII
		 * to the logs table for an extended period.
		 *
		 * @since 1.8.11
		 *
		 * @return void
		 */
		public static function maybe_render_log_raw_warning() {
			if ( ! self::is_redaction_bypassed() ) {
				return;
			}

			if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}

			echo '<div class="notice notice-error"><p><strong>'
				. esc_html__( 'PayPal Commerce: log redaction is disabled.', 'charitable' )
				. '</strong> '
				. esc_html__( 'The CHARITABLE_PAYPAL_LOG_RAW constant is defined as truthy in this site\'s configuration, so the PayPal Commerce logger is writing donor emails, phone numbers, addresses, and full token prefixes to the logs table without redaction. Remove the constant from wp-config.php as soon as possible. This is intended for short-lived staging debugging only.', 'charitable' )
				. '</p></div>';
		}

		/**
		 * Build the dedup key for an event + context combo.
		 *
		 * Uses a small set of "identifying" fields rather than the full context
		 * so that two info entries about the same order_id collapse to one row,
		 * but two entries about different orders do not.
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event   Event slug.
		 * @param  array  $context Context array.
		 * @return string
		 */
		private static function dedup_key( $event, array $context ) {
			$id_fields = array(
				'order_id',
				'capture_id',
				'refund_id',
				'subscription_id',
				'recurring_id',
				'donation_id',
				'event_id',
				'resource_id',
				'plan_id',
				'payment_token_id',
				'setup_token_id',
			);

			$parts = array();
			foreach ( $id_fields as $field ) {
				if ( isset( $context[ $field ] ) && '' !== $context[ $field ] ) {
					$parts[] = $field . '=' . (string) $context[ $field ];
				}
			}

			if ( empty( $parts ) ) {
				// No identifying field — dedup only on a per-message basis (rare).
				return $event . '|' . md5( wp_json_encode( $context ) );
			}

			return $event . '|' . implode( '&', $parts );
		}

		/**
		 * Merge per-request context into the user-provided context array.
		 *
		 * @since 1.8.11
		 *
		 * @param  array $context Caller-provided context.
		 * @return array
		 */
		private static function inject_request_context( array $context ) {
			$injected = array(
				'request_id' => self::get_request_id(),
			);

			$gateway_state = self::get_gateway_state();
			if ( ! empty( $gateway_state ) ) {
				$injected = array_merge( $injected, $gateway_state );
			}

			// Plugin version.
			if ( function_exists( 'charitable' ) ) {
				$charitable = charitable();
				if ( is_object( $charitable ) && method_exists( $charitable, 'get_version' ) ) {
					$injected['plugin_version'] = $charitable->get_version();
				}
			}

			// Caller fields win — request-level context fills the gaps.
			return array_merge( $injected, $context );
		}

		/**
		 * Get (or generate) the per-request unique ID.
		 *
		 * @since 1.8.11
		 *
		 * @return string
		 */
		private static function get_request_id() {
			if ( null === self::$request_id ) {
				self::$request_id = function_exists( 'wp_unique_id' )
					? wp_unique_id( 'ppc_' )
					: uniqid( 'ppc_', true );
			}
			return self::$request_id;
		}

		/**
		 * Read mode + merchant + middleware-mode from the gateway.
		 *
		 * Defensive — gateway class may not be loaded yet at very early hooks.
		 *
		 * @since 1.8.11
		 *
		 * @return array
		 */
		private static function get_gateway_state() {
			if ( null !== self::$gateway_state_cache ) {
				return self::$gateway_state_cache;
			}

			if ( ! class_exists( 'Charitable_Gateway_Paypal_Commerce' ) ) {
				// Don't cache "class not found" — it may load later in this request.
				return array();
			}

			try {
				$gateway      = new Charitable_Gateway_Paypal_Commerce();
				$is_sandbox   = method_exists( $gateway, 'is_sandbox' ) ? (bool) $gateway->is_sandbox() : false;
				$is_mw        = method_exists( $gateway, 'is_middleware_mode' ) ? (bool) $gateway->is_middleware_mode() : false;
				$merchant_id  = method_exists( $gateway, 'get_seller_merchant_id' ) ? $gateway->get_seller_merchant_id() : null;

				$state = array(
					'mode'         => $is_sandbox ? 'sandbox' : 'live',
					'gateway_mode' => $is_mw ? 'middleware' : 'direct',
				);

				if ( ! empty( $merchant_id ) ) {
					$state['merchant_id'] = (string) $merchant_id;
				}

				self::$gateway_state_cache = $state;
				return $state;
			} catch ( \Throwable $e ) {
				// Transient failure — don't cache; let next call retry.
				return array();
			}
		}

		/**
		 * Title formatting: `[paypal.order.captured] <summary>`.
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event   Event slug.
		 * @param  string $summary Free-form summary.
		 * @return string
		 */
		private static function format_title( $event, $summary ) {
			$summary = (string) $summary;
			$summary = trim( $summary );

			if ( '' === $summary ) {
				$summary = $event;
			}

			$title = '[' . $event . '] ' . $summary;

			// `wp_charitable_logs.title` is varchar(255). MySQL strict mode
			// rejects oversized rows; non-strict silently truncates. Webhook
			// summaries can exceed 255 with a long event slug. Cap at 250 to
			// leave room for the multibyte tail. Fall back to substr() if
			// mbstring isn't loaded (rare on hosts with PayPal Commerce active,
			// but safer than fataling).
			if ( function_exists( 'mb_substr' ) ) {
				return mb_substr( $title, 0, 250 );
			}
			return substr( $title, 0, 250 );
		}

		/**
		 * Body formatting: optional paypal-debug-id leading line, blank line, JSON-encoded context.
		 *
		 * @since 1.8.11
		 *
		 * @param  array $context Final (redacted) context array.
		 * @return string
		 */
		private static function format_message( array $context ) {
			$prefix = '';

			if ( ! empty( $context['paypal_debug_id'] ) ) {
				$prefix = 'paypal-debug-id: ' . (string) $context['paypal_debug_id'] . "\n\n";
			}

			$body = wp_json_encode( $context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

			if ( false === $body ) {
				$body = '{"_encode_error":"context could not be JSON-encoded"}';
			}

			return $prefix . $body;
		}

		/**
		 * Derive `object_type` from the first two segments of the event slug.
		 * `paypal.order.captured` → `paypal_order`.
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event Event slug.
		 * @return string
		 */
		private static function derive_object_type( $event ) {
			$parts = explode( '.', $event );

			if ( count( $parts ) >= 2 ) {
				return $parts[0] . '_' . $parts[1];
			}

			return 'paypal';
		}

		/**
		 * Map an event slug to a registered `types` value for the closed registry.
		 *
		 * @since 1.8.11
		 *
		 * @param  string $event Event slug.
		 * @return string
		 */
		private static function map_type( $event ) {
			// First two segments — same as object_type derivation.
			$key = '';
			$parts = explode( '.', $event );
			if ( isset( $parts[1] ) ) {
				$key = $parts[1];
			}

			switch ( $key ) {
				case 'webhook':
					return 'webhook';
				case 'subscription':
				case 'vault':
				case 'cron':
					return 'recurring';
				case 'refund':
				case 'order':
				case 'platform_fee':
					return 'payment';
				case 'connect':
				case 'oauth':
				case 'capability':
				case 'middleware':
					return 'gateway';
				default:
					return 'gateway';
			}
		}

		/**
		 * Recursive PII / secret redaction. See PayPal-Logger-Spec.md Section 6.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed  $value Value to scrub.
		 * @param  string $level Log level — affects email handling.
		 * @return mixed
		 */
		private static function redact( $value, $level = 'info' ) {
			// Non-serializable types short-circuit here so we never hand a
			// resource/closure to wp_json_encode() (which returns false on those
			// and discards the entire context).
			if ( is_resource( $value ) || $value instanceof Closure ) {
				return '[non-serializable]';
			}

			// Object handling: WP_Error gets a structured shape; other objects
			// are coerced to an array and recursed so any redactable keys
			// (e.g. ->email property) still get scrubbed.
			if ( is_object( $value ) ) {
				if ( $value instanceof WP_Error ) {
					return array(
						'wp_error_code'    => $value->get_error_code(),
						'wp_error_message' => $value->get_error_message(),
					);
				}
				return self::redact( (array) $value, $level );
			}

			if ( is_array( $value ) ) {
				$out = array();
				foreach ( $value as $k => $v ) {
					$key_lc = is_string( $k ) ? strtolower( $k ) : $k;

					// Strip-entirely keys.
					if ( in_array( $key_lc, array( 'authorization', 'cvv', 'cvv2', 'expiry', 'expiration_date' ), true ) ) {
						$out[ $k ] = '[redacted]';
						continue;
					}

					// Token-like keys: keep first 8 chars + ellipsis.
					if ( in_array( $key_lc, array( 'access_token', 'bearer', 'id_token', 'client_token', 'user_id_token', 'refresh_token' ), true ) ) {
						$out[ $k ] = self::truncate_token( $v );
						continue;
					}

					// Card number / PAN.
					if ( in_array( $key_lc, array( 'number', 'card_number', 'pan' ), true ) ) {
						$out[ $k ] = self::mask_pan( $v );
						continue;
					}

					if ( 'last_digits' === $key_lc || 'last4' === $key_lc ) {
						$out[ $k ] = self::mask_last4( $v );
						continue;
					}

					// Email handling — raw at debug, hashed at info+.
					if ( in_array( $key_lc, array( 'email', 'email_address' ), true ) ) {
						$out[ $k ] = ( 'debug' === $level ) ? $v : self::hash_email( $v );
						continue;
					}

					// Phone — keep last 4.
					if ( in_array( $key_lc, array( 'phone', 'phone_number' ), true ) ) {
						$out[ $k ] = self::mask_phone( $v );
						continue;
					}

					// Address — strip street, keep country + postal prefix when array.
					if ( in_array( $key_lc, array( 'address', 'billing_address', 'shipping_address', 'address_line_1', 'address_line_2' ), true ) ) {
						$out[ $k ] = self::scrub_address( $v );
						continue;
					}

					// Recurse.
					$out[ $k ] = self::redact( $v, $level );
				}
				return $out;
			}

			return $value;
		}

		/**
		 * Truncate a token to first 8 chars + ellipsis.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed $value Token candidate.
		 * @return string
		 */
		private static function truncate_token( $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				return '[redacted]';
			}
			return substr( $value, 0, 8 ) . '…';
		}

		/**
		 * Mask a card number to `****<last4>`.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed $value PAN candidate.
		 * @return string
		 */
		private static function mask_pan( $value ) {
			if ( ! is_string( $value ) ) {
				return '[redacted]';
			}
			$digits = preg_replace( '/\D+/', '', $value );
			if ( strlen( $digits ) < 4 ) {
				return '[redacted]';
			}
			return '****' . substr( $digits, -4 );
		}

		/**
		 * Sanitize a `last_digits` / `last4` field — keep last 4 digits only.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed $value Last-digits candidate.
		 * @return string
		 */
		private static function mask_last4( $value ) {
			if ( ! is_string( $value ) && ! is_int( $value ) ) {
				return '[redacted]';
			}
			$digits = preg_replace( '/\D+/', '', (string) $value );
			if ( '' === $digits ) {
				return '[redacted]';
			}
			return substr( $digits, -4 );
		}

		/**
		 * Hash email with HMAC-SHA256 keyed by the site's auth salt.
		 *
		 * Returns a short, deterministic-per-site hash that's non-trivial to
		 * dictionary-attack across sites (the salt is private and unique per
		 * install). Truncated to 12 hex chars (~48 bits) — collisions within
		 * a single site's log volume are negligibly rare; the goal is to make
		 * a row identifiable as "donor X" without exposing the email itself.
		 *
		 * Falls back to plain SHA-256 if `wp_salt()` is unavailable (very
		 * early hooks, or stripped-down test harnesses) so we never fatal.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed $value Email candidate.
		 * @return string
		 */
		private static function hash_email( $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				return '';
			}

			if ( function_exists( 'wp_salt' ) ) {
				$salt = wp_salt( 'auth' );
				return 'h:' . substr( hash_hmac( 'sha256', $value, $salt ), 0, 12 );
			}

			// Fallback — still preferable to plain sha1() in pre-WP contexts.
			return 'h:' . substr( hash( 'sha256', $value ), 0, 12 );
		}

		/**
		 * Mask phone to last 4 digits.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed $value Phone candidate.
		 * @return string
		 */
		private static function mask_phone( $value ) {
			if ( ! is_string( $value ) && ! is_int( $value ) ) {
				return '[redacted]';
			}
			$digits = preg_replace( '/\D+/', '', (string) $value );
			if ( strlen( $digits ) < 4 ) {
				return '[redacted]';
			}
			return '****' . substr( $digits, -4 );
		}

		/**
		 * Scrub address: keep country code + postal prefix only.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed $value Address scalar or array.
		 * @return mixed
		 */
		private static function scrub_address( $value ) {
			if ( is_array( $value ) ) {
				$kept = array();
				foreach ( array( 'country_code', 'country' ) as $k ) {
					if ( isset( $value[ $k ] ) ) {
						$kept[ $k ] = $value[ $k ];
					}
				}
				if ( isset( $value['postal_code'] ) && is_string( $value['postal_code'] ) ) {
					$kept['postal_code'] = substr( $value['postal_code'], 0, 3 ) . '…';
				}
				return $kept;
			}
			return '[redacted]';
		}

		/**
		 * Reset internal request-level state. Test-only helper.
		 *
		 * @since 1.8.11
		 *
		 * @return void
		 */
		public static function reset_request_state() {
			self::$request_id          = null;
			self::$seen                = array();
			self::$should_log_cache    = null;
			self::$gateway_state_cache = null;
		}
	}

endif;
