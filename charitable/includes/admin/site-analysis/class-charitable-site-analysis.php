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

		/** Delete the cached report so the next tab view recomputes (config changed or cache cleared). */
		public function flush_cache() {
			delete_transient( self::CACHE_TRANSIENT );
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
			$cache_time    = ( is_array( $cache ) && ! empty( $cache['time'] ) ) ? (int) $cache['time'] : 0;
			$refresh_ok    = ( 0 === $cache_time ) || ( time() - $cache_time ) >= self::REFRESH_COOLDOWN;
			$refresh_wait  = $refresh_ok ? '' : human_time_diff( time(), $cache_time + self::REFRESH_COOLDOWN );

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

			// Rate-limit manual refresh: only honor a forced re-fetch once the cooldown has elapsed since
			// the last analysis. Within the window, ignore the force and serve the cache (no network/quota).
			// This is the authoritative check - the button is also disabled client-side, but that's only UX.
			if ( $force && is_array( $cache ) && ! empty( $cache['time'] )
				&& ( time() - (int) $cache['time'] ) < self::REFRESH_COOLDOWN ) {
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
				if ( '' === $token ) {
					$this->fail_with_cache( __( 'The analysis service is unavailable right now. Please try again.', 'charitable' ), $cache );
				}
			}

			// 3) Call recommendations; on 401 (revoked/unknown token) re-register once and retry.
			$report = $this->request_recommendations( $payload, $token );
			if ( 'reauth' === $report ) {
				$token  = $this->register();
				$report = ( '' === $token ) ? 'error' : $this->request_recommendations( $payload, $token );
			}

			if ( 'quota' === $report ) {
				$this->fail_with_cache( __( "You've reached the analysis limit for now. Please try again later.", 'charitable' ), $cache );
			}
			if ( ! is_array( $report ) ) {
				$this->fail_with_cache( __( 'The analysis service is unavailable right now. Please try again.', 'charitable' ), $cache );
			}

			// 4) Success: cache, consent-gated usage check-in, return.
			set_transient( self::CACHE_TRANSIENT, array( 'payload_hash' => $hash, 'result' => $report, 'time' => time() ), self::CACHE_TTL );
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
			$recurring  = method_exists( $t, 'get_recurring_donation_data' ) ? (array) $t->get_recurring_donation_data() : array();
			$total      = isset( $donation['total_donations'] ) ? (float) $donation['total_donations'] : 0.0;
			$rec_total  = isset( $recurring['total_recurring_amount'] ) ? (float) $recurring['total_recurring_amount'] : 0.0;
			return array(
				'total_raised'    => $total,
				'avg_gift'        => isset( $charitable['average'] ) ? (float) $charitable['average'] : 0.0,
				'recurring_share' => $total > 0 ? min( 1, round( $rec_total / $total, 4 ) ) : 0.0,
				'donations_30d'   => isset( $donation['donations_30_days'] ) ? (float) $donation['donations_30_days'] : 0.0,
				'donor_count'     => isset( $charitable['donor_count'] ) ? (int) $charitable['donor_count'] : 0,
				'country'         => (string) charitable_get_option( 'country', '' ),
			);
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
		 * Returns the token on success, '' on failure (the caller surfaces a graceful error).
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

			if ( is_wp_error( $response ) || 201 !== (int) wp_remote_retrieve_response_code( $response ) ) {
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
		 * @param bool $tracking_enabled Whether the Advanced "Usage Tracking" toggle is ON.
		 * @param bool $consent          Whether the user ticked the in-tool consent checkbox.
		 * @return string 'none' | 'financial' | 'both'
		 */
		public static function decide_tracking_action( $tracking_enabled, $consent ) {
			if ( $tracking_enabled ) {
				// Fire an immediate full check-in on submit. The sender's once-per-week
				// throttle prevents a double-send and stamps charitable_usage_tracking_last_checkin,
				// so the weekly cron simply resumes from here.
				return 'both';
			}
			return $consent ? 'both' : 'financial';
		}

		/** Fire the appropriate one-time check-in, best-effort. Never flips the Advanced setting. */
		private function fire_usage_tracking( $consent ) {
			// Resolve "is tracking on?" the same way core does, so a filter-based force-enable
			// can't cause a double-send with the weekly cron.
			$tracking_enabled = (bool) apply_filters( 'charitable_usage_tracking', charitable_get_usage_tracking_setting() );
			$action           = self::decide_tracking_action( $tracking_enabled, (bool) $consent );

			if ( 'none' === $action || ! class_exists( 'Charitable_Tracking' ) ) {
				return;
			}

			try {
				$tracker = Charitable_Tracking::get_instance();
				if ( 'both' === $action ) {
					$tracker->send_checkins( true, false );          // anonymous financial + usage (admin email + license)
				} else {
					$tracker->send_tracking_checkin( true, false );  // anonymous financial only
				}
			} catch ( \Throwable $e ) {
				// Best-effort: a tracking failure must never affect the analysis.
			}
		}
	}

endif;
