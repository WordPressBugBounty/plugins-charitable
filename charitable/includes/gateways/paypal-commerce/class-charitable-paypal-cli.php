<?php
/**
 * Charitable PayPal Commerce — WP-CLI commands.
 *
 * Ships the escape-valve command for the PayPal Hard Fork (1.8.15):
 *
 *   wp charitable paypal force-commerce-tier [--confirm-recurring-impact] [--dry-run]
 *
 * See PayPal-Gateway-Exclusivity-PrePlan.md, Section 5.4 (Surface 1) for the
 * full behavior contract.
 *
 * @package   Charitable/Gateways/PayPal Commerce
 * @author    WP Charitable
 * @copyright Copyright (c) 2024-2026, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only register when WP-CLI is loaded.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! class_exists( 'Charitable_PayPal_CLI' ) ) :

	/**
	 * Charitable PayPal Commerce CLI commands.
	 *
	 * @since 1.8.11
	 */
	class Charitable_PayPal_CLI {

		/**
		 * Force the locked PayPal user tier from `legacy` to `commerce`.
		 *
		 * Disables the legacy `paypal` gateway in `active_gateways`, flips the
		 * `charitable_paypal_user_tier` option to `commerce`, and writes a
		 * structured log entry. PayPal Commerce becomes visible in the gateway
		 * settings list immediately, but is NOT auto-enabled — the admin must
		 * complete the Connect with PayPal flow manually after the switch.
		 *
		 * Active legacy recurring donations are a hazard: PayPal will continue
		 * billing donors via IPN, but Charitable will not record those renewals
		 * after the legacy gateway is disabled. The command refuses to run when
		 * any are present unless `--confirm-recurring-impact` is passed.
		 *
		 * ## OPTIONS
		 *
		 * [--confirm-recurring-impact]
		 * : Acknowledge that PayPal will continue billing legacy recurring
		 *   donors but Charitable will stop recording renewals.
		 *
		 * [--dry-run]
		 * : Print what would change without writing anything.
		 *
		 * ## EXAMPLES
		 *
		 *     # Inspect impact without making changes.
		 *     wp charitable paypal force-commerce-tier --dry-run
		 *
		 *     # Switch a legacy site to PayPal Commerce, accepting recurring trade-off.
		 *     wp charitable paypal force-commerce-tier --confirm-recurring-impact
		 *
		 * @subcommand force-commerce-tier
		 *
		 * @since 1.8.11
		 *
		 * @param array $args       Positional arguments (unused).
		 * @param array $assoc_args Associative arguments.
		 * @return void
		 */
		public function force_commerce_tier( $args, $assoc_args ) {
			$tier = get_option( 'charitable_paypal_user_tier', 'commerce' );

			if ( 'legacy' !== $tier ) {
				WP_CLI::log(
					sprintf(
						/* translators: %s: current tier value */
						__( 'No-op: the current PayPal tier is "%s". Nothing to switch.', 'charitable' ),
						$tier
					)
				);
				return;
			}

			$dry_run            = ! empty( $assoc_args['dry-run'] );
			$confirm_recurring  = ! empty( $assoc_args['confirm-recurring-impact'] );
			$active_recurring   = $this->count_active_legacy_recurring();

			if ( $active_recurring > 0 && ! $confirm_recurring ) {
				WP_CLI::error(
					sprintf(
						/* translators: %d: number of active recurring donations */
						__( 'You have %d active legacy PayPal recurring donations. PayPal will continue billing donors, but Charitable will not record those renewals as donations after this switch. Cancel them in PayPal first OR pass --confirm-recurring-impact to accept this trade-off.', 'charitable' ),
						$active_recurring
					)
				);
				return; // WP_CLI::error exits, but keep return for clarity in unit/test contexts.
			}

			if ( $dry_run ) {
				WP_CLI::log( __( 'DRY RUN — nothing will be changed.', 'charitable' ) );
				WP_CLI::log( sprintf( '  current tier: legacy' ) );
				WP_CLI::log( sprintf( '  active legacy recurring donations: %d', $active_recurring ) );
				WP_CLI::log( '  would: disable legacy paypal gateway' );
				WP_CLI::log( '  would: set charitable_paypal_user_tier = commerce' );
				WP_CLI::log( '  would: write paypal.tier.switched log entry' );
				return;
			}

			// Disable the legacy `paypal` gateway in active_gateways. We replicate
			// the body of the protected Charitable_Gateways::disable_gateway() rather
			// than promoting it to public — option manipulation only, no side effects
			// beyond the standard `charitable_gateway_disable` action.
			$settings = get_option( 'charitable_settings', array() );
			if ( isset( $settings['active_gateways']['paypal'] ) ) {
				unset( $settings['active_gateways']['paypal'] );

				if ( isset( $settings['default_gateway'] ) && 'paypal' === $settings['default_gateway'] ) {
					$settings['default_gateway'] = ! empty( $settings['active_gateways'] )
						? key( $settings['active_gateways'] )
						: '';
				}

				update_option( 'charitable_settings', $settings );

				/** This action is documented in includes/gateways/class-charitable-gateways.php */
				do_action( 'charitable_gateway_disable', 'paypal' );
			}

			update_option( 'charitable_paypal_user_tier', 'commerce' );

			// Structured log entry (Q2 helper). The helper guards against writes on
			// legacy-only sites where `paypal_commerce` is not yet active — but at
			// this exact moment that's typically TRUE (we just disabled legacy and
			// the admin hasn't run onboarding yet). Fall back to a direct write so
			// the audit entry for tier.switched always lands.
			$context = array(
				'old_tier'                      => 'legacy',
				'new_tier'                      => 'commerce',
				'active_legacy_recurring_count' => $active_recurring,
				'method'                        => 'wp_cli',
			);

			if ( class_exists( 'Charitable_PayPal_Logger' ) && Charitable_PayPal_Logger::should_log() ) {
				Charitable_PayPal_Logger::info(
					'paypal.tier.switched',
					'PayPal user tier switched from legacy to commerce',
					$context
				);
			} elseif ( function_exists( 'charitable_log_immediate' ) ) {
				charitable_log_immediate(
					'[paypal.tier.switched] PayPal user tier switched from legacy to commerce',
					wp_json_encode( $context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
					array(
						'type'        => 'gateway',
						'level'       => 'info',
						'source'      => 'paypal_commerce',
						'object_type' => 'paypal_tier',
						'force'       => true,
					)
				);
			}

			WP_CLI::success(
				sprintf(
					/* translators: %s: gateway settings URL */
					__( 'Tier switched to commerce. Connect PayPal Commerce: %s', 'charitable' ),
					admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal_commerce' )
				)
			);
		}

		/**
		 * Count active legacy `paypal` recurring donations.
		 *
		 * Uses $wpdb directly because WP_Query / get_posts with `post_status => 'any'`
		 * silently excludes Charitable's custom `charitable-active` status.
		 *
		 * @since  1.8.11
		 *
		 * @return int
		 */
		private function count_active_legacy_recurring() {
			global $wpdb;

			$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'recurring_donation'
				  AND pm.meta_key = 'donation_gateway'
				  AND pm.meta_value = 'paypal'
				  AND p.post_status IN ( 'charitable-active', 'charitable-pending' )"
			);

			return (int) $count;
		}
	}

	WP_CLI::add_command( 'charitable paypal', 'Charitable_PayPal_CLI' );

endif;
