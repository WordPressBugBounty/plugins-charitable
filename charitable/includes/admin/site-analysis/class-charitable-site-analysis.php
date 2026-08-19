<?php
/**
 * Charitable Site Analysis - a "Site Analysis" subtab on the Reports page that calls the
 * NonprofitScore config-fidelity endpoint and renders recommendations. Self-contained + portable.
 *
 * @package Charitable/Classes/Charitable_Site_Analysis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Site_Analysis' ) ) :

	class Charitable_Site_Analysis {

		/** @var Charitable_Site_Analysis|null */
		private static $instance = null;

		/** Option holding the runtime-minted per-site token (autoload off). */
		const TOKEN_OPTION = 'charitable_analysis_token';

		/** Transient holding the last result keyed by payload hash. */
		const CACHE_TRANSIENT = 'charitable_analysis_cache';

		/**
		 * Timestamp of the last successful API call, for the refresh rate limit (autoload off).
		 *
		 * NEW IN 1.8.12.1. This used to be read out of CACHE_TRANSIENT's own 'time' key - i.e. the rate
		 * limit lived inside the very cache that flush_cache() deletes. Every one of the five
		 * invalidation hooks therefore reset the 24h limit, and "Settings > Advanced > Clear cache" was
		 * a deliberate, repeatable, one-click reset. The cache is meant to be volatile; a rate limit
		 * must not be.
		 *
		 * Kept separate from the cache's 'time' on purpose: that still dates the *report* ("Last
		 * analyzed 2 hours ago"), which correctly disappears when the report does. This dates the
		 * last *network call*, which must not.
		 */
		const LAST_RUN_OPTION = 'charitable_analysis_last_run';

		/** Plugin-side cache lifetime. */
		const CACHE_TTL = 7 * DAY_IN_SECONDS;

		/** Minimum time between manual "Refresh" pulls (which bypass the cache), to prevent API abuse. */
		const REFRESH_COOLDOWN = DAY_IN_SECONDS;

		/** Addon folder slugs the engine's configSignal recognises (Phase-1 catalog). */
		const ADDON_SLUGS = array(
			'charitable-recurring',
			'charitable-fee-relief',
			'charitable-ambassadors',
			'charitable-newsletter-connect',
			'charitable-pdf-receipts',
			'charitable-annual-receipts',
			'charitable-anonymous',
			'charitable-gift-aid',
			'charitable-geolocation',
			'charitable-videos',
		);

		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/** Singletons are not cloneable. */
		private function __clone() {}

		/** Singletons are not unserializable. */
		public function __wakeup() {}

		private function __construct() {
			add_filter( 'charitable_reports_tabs', array( $this, 'register_tab' ) );
			add_action( 'charitable_reports_tab_site-analysis', array( $this, 'render_tab' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_charitable_site_analysis', array( $this, 'ajax_run' ) );

			// Invalidate the cached report when the analyzed configuration changes, or when the user
			// clears Charitable's cache from Settings > Advanced, so the next visit recomputes fresh.
			add_action( 'update_option_charitable_settings', array( $this, 'flush_cache' ) );   // test mode, gateways, receipts, spam, currency, plan.
			add_action( 'activated_plugin', array( $this, 'flush_cache' ) );                     // an addon was activated (recurring, fee relief, etc.).
			add_action( 'deactivated_plugin', array( $this, 'flush_cache' ) );                   // an addon was deactivated.
			add_action( 'save_post_campaign', array( $this, 'flush_cache' ) );                   // a campaign changed (e.g. a goal added).
			add_action( 'charitable_after_clear_expired_options', array( $this, 'flush_cache' ) ); // user clicked "Clear cache" in Settings > Advanced.
		}

		/** Add the Site Analysis tab to the Reports nav. */
		public function register_tab( $tabs ) {
			$tabs['site-analysis'] = __( 'Site Analysis', 'charitable' );
			return $tabs;
		}

		/** Render the tab body. */
		public function render_tab() {
			charitable_admin_view( 'reports/site-analysis' );
		}

		/**
		 * Delete the cached report so the next tab view recomputes (config changed or cache cleared).
		 *
		 * Deliberately does NOT touch LAST_RUN_OPTION. Invalidating the report is the point here;
		 * resetting the refresh rate limit is not, and used to be a side effect.
		 */
		public function flush_cache() {
			delete_transient( self::CACHE_TRANSIENT );
		}

		/** Timestamp of the last successful API call, or 0 if it has never run. */
		private function last_run_time() {
			return (int) get_option( self::LAST_RUN_OPTION, 0 );
		}

		/** Enqueue the tool's assets on the Reports > Site Analysis tab. */
		public function enqueue_assets( $hook ) {
			$screen = get_current_screen();
			if ( is_null( $screen ) || 'charitable_page_charitable-reports' !== $screen->id ) {
				return;
			}

			// Green "New" badge on the Site Analysis nav tab, shown on every Reports tab (mirrors the Pro badges).
			wp_register_style( 'charitable-site-analysis-nav', false );
			wp_enqueue_style( 'charitable-site-analysis-nav' );
			wp_add_inline_style(
				'charitable-site-analysis-nav',
				'body.charitable_page_charitable-reports .nav-tab.site-analysis::after{content:"New";background-color:#5AA152;padding:3px 7px;font-size:11px;line-height:11px;text-transform:uppercase;color:#fff;font-weight:600;margin-left:5px;margin-right:5px;margin-top:0;position:relative;top:-7px;}'
			);

			$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'site-analysis' !== $tab ) {
				return;
			}
			$assets = charitable()->get_path( 'assets', false );
			$ver    = charitable()->get_version();
			// Font Awesome (bundled) powers the category icons the JS renders for config-fix recommendations.
			wp_enqueue_style( 'charitable-font-awesome', charitable()->get_path( 'directory', false ) . 'assets/lib/font-awesome/font-awesome.min.css', array(), '4.7.0' );
			wp_enqueue_style( 'charitable-site-analysis', $assets . 'css/admin/charitable-site-analysis.css', array( 'charitable-font-awesome' ), $ver );
			wp_enqueue_script( 'charitable-site-analysis', $assets . 'js/admin/charitable-site-analysis.js', array( 'jquery' ), $ver, true );

			// Surface the last cached report so the JS can render it immediately on load (no re-run needed).
			$cache          = get_transient( self::CACHE_TRANSIENT );
			$cached_result  = ( is_array( $cache ) && isset( $cache['result'] ) ) ? $cache['result'] : null;
			$cached_ago     = ( is_array( $cache ) && ! empty( $cache['time'] ) )
				/* translators: %s: human-readable time difference, e.g. "2 hours". */
				? sprintf( __( 'Last analyzed %s ago', 'charitable' ), human_time_diff( (int) $cache['time'] ) )
				: '';

			// Manual refresh is limited to once per cooldown window; tell the JS whether it's allowed yet
			// (and, if not, how long until it is) so it can disable the link. The server enforces this too.
			// Measured from LAST_RUN_OPTION rather than the cache's own timestamp: the cache is deleted on
			// every config change and by "Clear cache", which used to silently reset this limit and leave
			// the UI offering a live Refresh link seconds after an analysis ran.
			$last_run     = $this->last_run_time();
			$refresh_ok   = ( 0 === $last_run ) || ( time() - $last_run ) >= self::REFRESH_COOLDOWN;
			$refresh_wait = $refresh_ok ? '' : human_time_diff( time(), $last_run + self::REFRESH_COOLDOWN );

			wp_localize_script(
				'charitable-site-analysis',
				'charitable_site_analysis',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'charitable_site_analysis' ),
					'upgrade_url' => function_exists( 'charitable_pro_upgrade_url' ) ? charitable_pro_upgrade_url( 'site-analysis', '' ) : 'https://wpcharitable.com/lite-upgrade/',
					// Config-fix recommendations open the relevant in-plugin screen instead of external docs.
					'internal'    => array(
						'test-mode-on'                 => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
						'no-gateway'                   => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
						'single-gateway'               => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
						'recurring-no-capable-gateway' => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
						'offline-off'                  => admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
						'receipt-email-off'            => admin_url( 'admin.php?page=charitable-settings&tab=emails' ),
						'campaigns-no-goal'            => admin_url( 'edit.php?post_type=campaign' ),
						'no-published-campaign'        => admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ),
						'spam-off-with-donations'      => admin_url( 'admin.php?page=charitable-settings&tab=security' ),
					),
					// Growth/how-to recs have no in-plugin toggle. Override the engine's generic
					// /documentation/ CTA with a specific article (keyed by the engine's check id).
					// Recs left out here keep the engine's own CTA URL.
					'docs'        => array(
						'no-donation-yet'   => 'https://www.wpcharitable.com/documentation/promote-your-campaign/',
						'low-avg-gift'      => 'https://www.wpcharitable.com/documentation/set-suggested-donation-amounts/',
						'stalled-momentum'  => 'https://www.wpcharitable.com/documentation/re-engage-your-donors/',
						'Offline Donations' => 'https://www.wpcharitable.com/documentation/setting-up-offline-donations/',
					),
					// Addon recommendations show their official extension icon (bundled locally); other recs
					// fall back to a category icon in the JS. Keyed by the engine's rec featureId.
					'rec_icons'   => array(
						'recurring'       => esc_url_raw( $assets . 'images/addons/addon-icon-recurring-donations.png' ),
						'ambassadors'     => esc_url_raw( $assets . 'images/addons/addon-icon-ambassadors.png' ),
						'fee-relief'      => esc_url_raw( $assets . 'images/addons/addon-icon-fee-relief.png' ),
						'stripe'          => esc_url_raw( $assets . 'images/addons/addon-icon-stripe.png' ),
						'newsletter'      => esc_url_raw( $assets . 'images/addons/addon-icon-newsletter-connect.png' ),
						'pdf-receipts'    => esc_url_raw( $assets . 'images/addons/addon-icon-pdf-receipts.png' ),
						'annual-receipts' => esc_url_raw( $assets . 'images/addons/addon-icon-annual-receipts.png' ),
						'anonymous'       => esc_url_raw( $assets . 'images/addons/addon-icon-anonymous-donations.png' ),
						'gift-aid'        => esc_url_raw( $assets . 'images/addons/addon-icon-gift-aid.png' ),
						'donor-comments'  => esc_url_raw( $assets . 'images/addons/addon-icon-donor-comments.png' ),
						'geolocation'     => esc_url_raw( $assets . 'images/addons/addon-icon-geolocation.png' ),
						'videos'          => esc_url_raw( $assets . 'images/addons/addon-icon-videos.png' ),
					),
					'cached'       => $cached_result,
					'cached_ago'   => $cached_ago,
					'refresh_ok'   => $refresh_ok,
					'refresh_wait' => $refresh_wait,
					'i18n'        => array(
						'running'      => __( 'Analyzing your site...', 'charitable' ),
						'error'        => __( 'Sorry, the analysis could not be completed. Please try again.', 'charitable' ),
						'refresh'      => __( 'Refresh', 'charitable' ),
						/* translators: %s: human-readable time until refresh is available, e.g. "20 hours". */
						'refresh_wait' => __( 'You can refresh again in %s', 'charitable' ),
						'refresh_soon' => __( 'You can refresh again tomorrow', 'charitable' ),
					),
				)
			);
		}

		/** AJAX: serve cached or call the recommendations API with the per-site token. */
		public function ajax_run() {
			if ( ! check_ajax_referer( 'charitable_site_analysis', 'nonce', false ) || ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'charitable' ) ) );
			}

			$consent = isset( $_POST['consent'] ) && '1' === (string) $_POST['consent']; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$payload = $this->gather_config( $consent );
			$hash    = $this->payload_hash( $payload );
			$force   = ! empty( $_POST['refresh'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$cache   = get_transient( self::CACHE_TRANSIENT );

			/*
			 * Rate-limit manual refresh: only honor a forced re-fetch once the cooldown has elapsed since
			 * the last successful API call. Within the window, ignore the force and serve the cache
			 * (no network, no quota). This is the authoritative check for the *forced refresh* path - the
			 * button is also disabled client-side, but that is only UX.
			 *
			 * Read from LAST_RUN_OPTION, not from $cache['time']: the cooldown must outlive the cache it
			 * limits. See LAST_RUN_OPTION.
			 *
			 * Note this gates the forced path only. A changed payload hash, or an absent cache, still
			 * reaches the network on the next run - deliberately, because a config change must be
			 * reflected in the score. The floor on that path is the server's own per-token daily quota,
			 * which returns 429 and is surfaced as the "analysis limit" message below.
			 */
			$last_run = $this->last_run_time();

			if ( $force && $last_run > 0 && ( time() - $last_run ) < self::REFRESH_COOLDOWN ) {
				$force = false;
			}

			// 1) Plugin-side cache: unchanged config + fresh + not a forced refresh -> no network, no quota.
			if ( ! $force && is_array( $cache ) && isset( $cache['payload_hash'], $cache['result'] ) && $cache['payload_hash'] === $hash ) {
				wp_send_json_success( $cache['result'] );
			}

			// 2) Ensure a per-site token (lazy register on first run).
			$token = $this->get_token();
			if ( '' === $token ) {
				$token = $this->register();
				// A rate-limited registration is not an outage; say so, so the user waits instead of
				// retrying into the same limit. See register().
				if ( 'quota' === $token ) {
					$this->fail_with_cache( __( "You've reached the analysis limit for now. Please try again later.", 'charitable' ), $cache );
				}
				if ( '' === $token ) {
					$this->fail_with_cache( __( 'The analysis service is unavailable right now. Please try again.', 'charitable' ), $cache );
				}
			}

			// 3) Call recommendations; on 401 (revoked/unknown token) re-register once and retry.
			$report = $this->request_recommendations( $payload, $token );
			if ( 'reauth' === $report ) {
				$token = $this->register();
				// Handle the rate limit here too: 'quota' is a status, not a token, and passing it
				// through as one would produce a second 401 and report a misleading generic error.
				if ( 'quota' === $token ) {
					$this->fail_with_cache( __( "You've reached the analysis limit for now. Please try again later.", 'charitable' ), $cache );
				}
				$report = ( '' === $token ) ? 'error' : $this->request_recommendations( $payload, $token );
			}

			if ( 'quota' === $report ) {
				$this->fail_with_cache( __( "You've reached the analysis limit for now. Please try again later.", 'charitable' ), $cache );
			}
			if ( ! is_array( $report ) ) {
				$this->fail_with_cache( __( 'The analysis service is unavailable right now. Please try again.', 'charitable' ), $cache );
			}

			// 4) Success: cache, stamp the rate limit, consent-gated usage check-in, return.
			set_transient( self::CACHE_TRANSIENT, array( 'payload_hash' => $hash, 'result' => $report, 'time' => time() ), self::CACHE_TTL );
			// Stamped outside the transient so flush_cache() cannot reset the cooldown. Only successes
			// count, so a failed call stays retryable - which is what fail_with_cache()'s stale-report
			// path and the JS retry link both assume.
			update_option( self::LAST_RUN_OPTION, time(), false ); // autoload = no
			$this->fire_usage_tracking( $consent );
			wp_send_json_success( $report );
		}

		/** Build the no-PII config payload (see SPEC §4.1). */
		public function gather_config( $consent = false ) {
			$gateways  = charitable_get_helper( 'gateways' );
			$emails    = charitable_get_helper( 'emails' );
			$active    = array_values( array_keys( (array) $gateways->get_active_gateways() ) );
			$published = (int) wp_count_posts( 'campaign' )->publish;
			$plan_id   = $this->get_plan_id();

			$payload = array(
				'schema_version' => 1,
				'site_id'        => hash( 'sha256', home_url() . $this->analysis_salt() ),
				'product'        => array(
					'is_pro'       => (bool) charitable_is_pro(),
					'plan_id'      => $plan_id,
					'plan_label'   => function_exists( 'charitable_get_license_label_from_plan_id' ) ? (string) charitable_get_license_label_from_plan_id( $plan_id ) : 'Lite',
					'lite_version' => (string) charitable()->get_version(),
					'pro_version'  => charitable_is_pro() ? (string) charitable()->get_version() : null,
				),
				'config'         => array(
					'active_gateways'               => $active,
					'default_gateway'               => $gateways->get_default_gateway() ? (string) $gateways->get_default_gateway() : null,
					'active_addons'                 => $this->active_addons(),
					'features'                      => $this->feature_flags(),
					'in_test_mode'                  => (bool) $gateways->in_test_mode(),
					'donation_receipt_enabled'      => (bool) $emails->is_enabled_email( 'donation_receipt' ),
					'offline_receipt_enabled'       => (bool) $emails->is_enabled_email( 'offline_donation_receipt' ),
					'has_recurring_capable_gateway' => $this->has_recurring_capable_gateway( $active ),
					'all_campaigns_have_goals'      => $this->all_campaigns_have_goals(),
					'currency'                      => (string) charitable_get_option( 'currency', 'USD' ),
					'has_seo_plugin'                => $this->has_seo_plugin(),
				),
				'counts'         => array(
					'campaigns_published' => $published,
					'donations_completed' => $this->completed_donation_count(),
				),
				'activation'     => array(
					'has_published_campaign' => $published > 0,
					'has_received_donation'  => $this->has_real_donation(),
				),
			);

			if ( $consent ) {
				$fin = $this->financials();
				if ( is_array( $fin ) ) {
					$payload['financials'] = $fin;
				}
			}

			return $payload;
		}

		/** No-PII aggregate financials for the score (only sent on consent). Mirrors Charitable_Tracking aggregates. */
		private function financials() {
			if ( ! class_exists( 'Charitable_Tracking' ) ) {
				return null;
			}
			$t          = Charitable_Tracking::get_instance();
			$donation   = method_exists( $t, 'get_donation_data' ) ? (array) $t->get_donation_data() : array();
			$charitable = method_exists( $t, 'get_charitable_data' ) ? (array) $t->get_charitable_data() : array();
			$total      = isset( $donation['total_donations'] ) ? (float) $donation['total_donations'] : 0.0;
			return array(
				'total_raised'    => $total,
				'avg_gift'        => isset( $charitable['average'] ) ? (float) $charitable['average'] : 0.0,
				'recurring_share' => $this->recurring_revenue_share(),
				'donations_30d'   => isset( $donation['donations_30_days'] ) ? (float) $donation['donations_30_days'] : 0.0,
				'donor_count'     => isset( $charitable['donor_count'] ) ? (int) $charitable['donor_count'] : 0,
				'country'         => (string) charitable_get_option( 'country', '' ),
			);
		}

		/**
		 * Share of completed donation revenue that came from a recurring donation, 0..1.
		 *
		 * REPLACES A BROKEN DERIVATION. Until 1.8.12.1 this came from Charitable_Tracking's
		 * total_recurring_amount divided by total_donations and clamped with min( 1, ... ). That was
		 * wrong twice over: total_recurring_amount summed the _first_donation post meta, which is a
		 * donation POST ID and not an amount (the Recurring addon writes it as
		 * update_post_meta( $recurring_id, '_first_donation', $donation_id )), and even correctly
		 * summed it would count active pledges rather than revenue received, so dividing it by
		 * lifetime revenue was a category error. The clamp hid it: the numerator was large enough
		 * that the ratio pinned to exactly 1, so almost every site reported 100% recurring. Measured
		 * on a real site: 1.2% actual vs 100% reported. A value of exactly 1 is that bug's signature.
		 *
		 * Both halves below come from the same rows of the same table, so this is a true ratio and
		 * needs no clamp. Recurring child donations are themselves completed `donation` posts, so
		 * they are already inside the denominator. See
		 * _claude/addons/charitable-pro/1.8.18/2026-08-18-recurring-share-and-total-recurring-amount-bugs.md
		 *
		 * @since 1.8.12.1
		 *
		 * @return float
		 */
		private function recurring_revenue_share() {
			global $wpdb;

			// Match on parent.post_type rather than post_parent > 0: post_parent is a generic column,
			// and this stays correct if anything else ever parents a donation.
			//
			// A direct query is deliberate: this is a two-column aggregate over the whole donation
			// table, computed once per analysis run, and there is no Charitable API that returns both
			// halves from the same rows - which is the entire point (see the docblock). It is not
			// cached because a stale ratio would silently misreport the score.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$row = $wpdb->get_row(
				"SELECT SUM(cd.amount) AS total,
					SUM( CASE WHEN parent.ID IS NOT NULL THEN cd.amount ELSE 0 END ) AS recurring
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}charitable_campaign_donations cd
					ON p.ID = cd.donation_id
				LEFT JOIN {$wpdb->posts} parent
					ON p.post_parent = parent.ID
					AND parent.post_type = 'recurring_donation'
				WHERE p.post_type = 'donation'
					AND p.post_status = 'charitable-completed'"
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

			$total = ( $row && $row->total ) ? (float) $row->total : 0.0;

			if ( $total <= 0 ) {
				return 0.0;
			}

			return round( (float) $row->recurring / $total, 4 );
		}

		/**
		 * Resolve the site_id hashing salt lazily. wp_salt() lives in pluggable.php and is NOT available
		 * at plugin-construct time, so this is computed here (admin-ajax time) instead of as a load-time
		 * constant. A host may still pre-define CHARITABLE_ANALYSIS_SALT (e.g. in wp-config.php).
		 */
		private function analysis_salt() {
			if ( defined( 'CHARITABLE_ANALYSIS_SALT' ) ) {
				return CHARITABLE_ANALYSIS_SALT;
			}
			return (string) apply_filters( 'charitable_analysis_salt', wp_salt( 'nonce' ) );
		}

		/** The stored per-site token, or '' if none. */
		private function get_token() {
			return (string) get_option( self::TOKEN_OPTION, '' );
		}

		/**
		 * Register this site with NonprofitScore and persist the minted per-site token.
		 * Returns the token on success, 'quota' on 429, or '' on failure (the caller surfaces a
		 * graceful error).
		 *
		 * CHANGED IN 1.8.12.1. This used to fold every non-201 into '', so the caller reported "The
		 * analysis service is unavailable right now" for a 429 as well as for a real outage. The
		 * register endpoint is rate-limited **by IP** with a window observed at over 15 minutes, which
		 * means a shared host, a multisite, or an agency running several client sites can trip it
		 * through no fault of the site in front of the user - and the message then invites exactly the
		 * pointless retry that keeps it tripped.
		 *
		 * Mirrors request_recommendations()'s existing convention of returning a status string, so both
		 * network paths report a quota the same way. A minted token can never collide with 'quota';
		 * they are prefixed 'nst_'.
		 *
		 * @since 1.8.12
		 * @version 1.8.12.1
		 *
		 * @return string
		 */
		private function register() {
			$response = wp_remote_post(
				CHARITABLE_ANALYSIS_REGISTER_URL,
				array(
					'timeout' => 15,
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode(
						array(
							'site_id'        => hash( 'sha256', home_url() . $this->analysis_salt() ),
							'home_url'       => home_url(),
							'plugin_version' => (string) charitable()->get_version(),
						)
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return '';
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			if ( 429 === $code ) {
				return 'quota';
			}

			if ( 201 !== $code ) {
				return '';
			}

			$data  = json_decode( wp_remote_retrieve_body( $response ), true );
			$token = ( is_array( $data ) && ! empty( $data['token'] ) ) ? (string) $data['token'] : '';
			if ( '' !== $token ) {
				update_option( self::TOKEN_OPTION, $token, false ); // autoload = no
			}
			return $token;
		}

		/** Stable hash of the no-PII payload, for plugin-side caching. */
		private function payload_hash( array $payload ) {
			return hash( 'sha256', wp_json_encode( $payload ) );
		}

		/**
		 * POST the payload to the recommendations endpoint with the per-site token.
		 * Returns the decoded report array on success, or a status string:
		 *   'reauth' (401 - token revoked/unknown) | 'quota' (429) | 'error' (anything else).
		 */
		private function request_recommendations( array $payload, $token ) {
			$response = wp_remote_post(
				CHARITABLE_ANALYSIS_API_URL,
				array(
					'timeout' => 15,
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode( $payload ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return 'error';
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 401 === $code ) {
				return 'reauth';
			}
			if ( 429 === $code ) {
				return 'quota';
			}
			if ( 200 !== $code ) {
				return 'error';
			}
			$report = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $report ) || ( empty( $report['recommendations'] ) && empty( $report['recommendedPlan'] ) ) ) {
				return 'error';
			}
			return $report;
		}

		/** Send a JSON error; attach a stale cached result (if any) so the UI can still show something. */
		private function fail_with_cache( $message, $cache ) {
			$payload = array( 'message' => $message );
			if ( is_array( $cache ) && isset( $cache['result'] ) ) {
				$payload['stale'] = $cache['result'];
			}
			wp_send_json_error( $payload );
		}

		/** License plan_id (0=Lite); Lite has no direct accessor. */
		private function get_plan_id() {
			$settings = get_option( 'charitable_settings' );
			return ( ! empty( $settings['licenses']['charitable-v2']['plan_id'] ) ) ? (int) $settings['licenses']['charitable-v2']['plan_id'] : 0;
		}

		/** Active Charitable addons among the known slugs. */
		private function active_addons() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$active = array();
			foreach ( self::ADDON_SLUGS as $slug ) {
				if ( is_plugin_active( $slug . '/' . $slug . '.php' ) ) {
					$active[] = $slug;
				}
			}
			return $active;
		}

		/**
		 * Whether any well-known SEO plugin is active. The engine uses this to offer a score-neutral
		 * SEO cross-sell only when no SEO plugin is present, so a site already running one is never
		 * nudged. We report a boolean, never the plugin list, to keep the payload PII-free.
		 *
		 * @return bool
		 */
		private function has_seo_plugin() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$seo_plugins = array(
				'all-in-one-seo-pack/all_in_one_seo_pack.php',      // AIOSEO (free).
				'all-in-one-seo-pack-pro/all_in_one_seo_pack.php',  // AIOSEO (pro).
				'wordpress-seo/wp-seo.php',                         // Yoast SEO (free).
				'wordpress-seo-premium/wp-seo-premium.php',         // Yoast SEO Premium.
				'seo-by-rank-math/rank-math.php',                   // Rank Math (free).
				'seo-by-rank-math-pro/rank-math-pro.php',           // Rank Math Pro.
				'wp-seopress/seopress.php',                         // SEOPress (free).
				'wp-seopress-pro/seopress-pro.php',                 // SEOPress Pro.
				'autodescription/autodescription.php',              // The SEO Framework.
			);
			foreach ( $seo_plugins as $plugin ) {
				if ( is_plugin_active( $plugin ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Feature on/off flags the engine reads to tell "installed-but-off" (enable) from "active"
		 * (suppress). The Pro-only addons (recurring, fee relief, donor comments) are absent on Lite,
		 * so they stay false and the Pro port computes their real state. Spam protection is NOT Pro-only:
		 * Lite ships CAPTCHA-based protection (Settings > Security), so it reflects the real toggle here.
		 */
		private function feature_flags() {
			return array(
				'recurring_enabled'       => false,
				'fee_relief_enabled'      => false,
				// "On" when a CAPTCHA provider is selected; mirrors Charitable_Captcha::is_active().
				'spam_protection_enabled' => ( 'disabled' !== charitable_get_option( 'captcha_provider', 'disabled' ) ),
				'donor_comments_enabled'  => false,
			);
		}

		/** True if any active gateway supports recurring. */
		private function has_recurring_capable_gateway( array $active_ids ) {
			$gateways = charitable_get_helper( 'gateways' );
			foreach ( $active_ids as $id ) {
				$class = $gateways->get_gateway( $id );
				if ( is_string( $class ) && class_exists( $class ) ) {
					$obj = new $class();
					if ( method_exists( $obj, 'supports' ) && $obj->supports( 'recurring' ) ) {
						return true;
					}
				}
			}
			return false;
		}

		/** True if every published campaign has a goal (true when there are none). */
		private function all_campaigns_have_goals() {
			$ids = get_posts(
				array(
					'post_type'      => 'campaign',
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => 200,
					'no_found_rows'  => true,
				)
			);
			foreach ( $ids as $id ) {
				$campaign = charitable_get_campaign( $id );
				if ( $campaign && method_exists( $campaign, 'has_goal' ) && ! $campaign->has_goal() ) {
					return false;
				}
			}
			return true;
		}

		/** Count completed donations. */
		private function completed_donation_count() {
			$counts = wp_count_posts( 'donation' );
			return isset( $counts->{'charitable-completed'} ) ? (int) $counts->{'charitable-completed'} : 0;
		}

		/** True if there's at least one real (non-test, > 1) completed donation. Computed locally. */
		private function has_real_donation() {
			$ids = get_posts(
				array(
					'post_type'      => 'donation',
					'post_status'    => 'charitable-completed',
					'fields'         => 'ids',
					'posts_per_page' => 25,
					'no_found_rows'  => true,
				)
			);
			foreach ( $ids as $id ) {
				$donation = charitable_get_donation( $id );
				if ( ! $donation ) {
					continue;
				}
				$is_test = method_exists( $donation, 'get_test_mode' ) ? (int) $donation->get_test_mode( false ) : (int) get_post_meta( $id, 'test_mode', true );
				$amount  = method_exists( $donation, 'get_total_donation_amount' ) ? (float) $donation->get_total_donation_amount() : 0.0;
				if ( ! $is_test && $amount > 1 ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Decide which usage-tracking check-in to fire (pure; unit-testable).
		 *
		 * CHANGED IN 1.8.12.1. This used to return 'financial' when the user DECLINED the in-tool
		 * toggle and usage tracking was off, and the caller then invoked
		 * send_tracking_checkin( true, false ) - where the first argument is $override, which skips
		 * the consent gate outright:
		 *
		 *     if ( ! $this->tracking_allowed() && ! $override ) {
		 *         return false;
		 *     }
		 *
		 * So declining still transmitted: a persistent sha256 site AID from
		 * charitable_site_tracking_aid, campaign counts, campaign data, donation data and recurring
		 * data. On Lite the licence key resolves to '', so unlike Pro the payload is not directly
		 * identifying - but the AID is stable, so it still links a site's history, and it was a
		 * decline being ignored either way.
		 *
		 * This was Lite's behaviour from its first commit (334730a553) rather than a regression -
		 * dd0c57264f only changed the tracking-ON case from 'none' to 'both' - so the intent was
		 * presumably "anonymous aggregates are fine". The premise does not hold: the user declined.
		 *
		 * Declining now sends nothing. The report itself is unaffected: this runs after the report is
		 * built, and the score comes from the separately consent-gated NonprofitScore payload, so with
		 * the toggle off the analysis and score still work in full.
		 *
		 * 'financial' is gone rather than left unreachable, so no dead branch remains below.
		 *
		 * The Advanced > Misc "Usage Tracking" permission is now the ONLY basis for transmitting
		 * anything, in Lite and Pro alike. In-tool consent is not an independent basis: ticking the
		 * toggle turns that permission ON (see enable_usage_tracking()), and the caller applies it
		 * before this runs, so by the time we get here $tracking_enabled already reflects it. That is
		 * what makes "nothing is submitted while usage tracking is off" true rather than aspirational,
		 * and it means a filter-based force-disable still wins over a ticked toggle.
		 *
		 * @since 1.8.12
		 * @version 1.8.12.1
		 *
		 * @param bool $tracking_enabled Whether usage-tracking permission is granted, read through
		 *                               the charitable_usage_tracking filter.
		 * @param bool $consent          Whether the user ticked the in-tool toggle. Retained because
		 *                               this is a public, unit-tested contract; not decisive, since
		 *                               the caller has already converted it into the permission.
		 * @return string 'none' | 'both'
		 */
		public static function decide_tracking_action( $tracking_enabled, $consent ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $consent stays in the signature because this is a public, unit-tested contract; it is no longer decisive. See above.
			// Fire an immediate full check-in on submit. The sender's once-per-week throttle prevents a
			// double-send and stamps charitable_usage_tracking_last_checkin, so the weekly cron simply
			// resumes from here.
			return $tracking_enabled ? 'both' : 'none';
		}

		/**
		 * Grant usage-tracking permission persistently, because the user ticked the in-tool toggle.
		 *
		 * Ticking the Site Analysis toggle turns the Advanced > Misc "Usage Tracking" setting ON, in
		 * both Lite and Pro. Lite has no other mechanism for enabling it outside the settings screen,
		 * so routing consent through the setting is what lets one rule cover both products.
		 *
		 * ONE-WAY BY DESIGN. This never writes false: unticking the toggle must not turn off tracking
		 * that is already on. In practice the toggle is not even rendered once tracking is on (see
		 * views/reports/site-analysis.php), so there is nothing to untick - but the guarantee is
		 * enforced here rather than left to the view.
		 *
		 * @since 1.8.12.1
		 *
		 * @return void
		 */
		private function enable_usage_tracking() {
			if ( function_exists( 'charitable_update_usage_tracking_setting' ) ) {
				charitable_update_usage_tracking_setting( true );
				return;
			}

			// Same inline write Charitable_Upgrade uses, for contexts where that admin-only helper is
			// not loaded. Keeps the charitable_settings mirror in sync.
			update_option( 'charitable_usage_tracking', 1 );
			$settings                              = (array) get_option( 'charitable_settings', array() );
			$settings['charitable_usage_tracking'] = true;
			update_option( 'charitable_settings', $settings );
		}

		/**
		 * Fire the appropriate one-time check-in, best-effort.
		 *
		 * As of 1.8.12.1 this DOES flip the Advanced setting - but only ever on, and only when the
		 * user ticked the toggle. See enable_usage_tracking().
		 */
		private function fire_usage_tracking( $consent ) {
			// Ticking the toggle grants permission persistently, so record it before deciding anything.
			// Reads the raw setting, not the filtered value: a filter that force-enables is somebody
			// else's runtime opinion and should not be persisted as the user's choice.
			if ( $consent && ! charitable_get_usage_tracking_setting() ) {
				$this->enable_usage_tracking();
			}

			// Resolve "may we transmit?" the same way core does, so a filter-based force-disable still
			// wins over a ticked toggle, and a force-enable can't cause a double-send with the weekly cron.
			$tracking_enabled = (bool) apply_filters( 'charitable_usage_tracking', charitable_get_usage_tracking_setting() );
			$action           = self::decide_tracking_action( $tracking_enabled, (bool) $consent );

			// 'none' means the user declined and nothing may be transmitted. This guard existed before
			// 1.8.12.1 but was unreachable, because decide_tracking_action() never returned 'none'.
			if ( 'none' === $action || ! class_exists( 'Charitable_Tracking' ) ) {
				return;
			}

			try {
				// Consented (either by the Advanced setting or the in-tool toggle), so send both
				// endpoints: the financial capture and the opt-in usage check-in.
				Charitable_Tracking::get_instance()->send_checkins( true, false );
			} catch ( \Throwable $e ) {
				// Best-effort: a tracking failure must never affect the analysis.
			}
		}
	}

endif;
