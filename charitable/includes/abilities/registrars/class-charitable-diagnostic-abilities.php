<?php
/**
 * Diagnostic abilities: plugin info, site environment, and gateway status.
 *
 * These three abilities have no write path and no complex input schema, so
 * they prove the whole registration pipeline end-to-end with the least
 * surface area. get-plugin-info also establishes the currency shape every
 * later money ability depends on.
 *
 * @package   Charitable/Classes/Charitable_Diagnostic_Abilities
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Diagnostic_Abilities' ) ) :

	/**
	 * Charitable_Diagnostic_Abilities
	 *
	 * @since 1.8.13
	 */
	class Charitable_Diagnostic_Abilities extends Charitable_Abilities_Registrar {

		/**
		 * Register the diagnostic abilities.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public function register() {
			$this->register_ability(
				'charitable/get-plugin-info',
				array(
					'label'               => __( 'Get Charitable Plugin Info', 'charitable' ),
					'description'         => __( "Returns the Charitable version, whether this is Pro, active addons, the site's currency and decimal configuration, and whether AI write access is currently enabled. Use this first to orient before reading or writing any monetary amount: if write_enabled is false, changes are refused until an administrator turns writing on under Charitable > Tools > AI MCP. For gateway connection state use Get Gateway Status instead.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_plugin_info' ),
					'permission_callback' => array( __CLASS__, 'permission_manage' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::no_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'version'         => array( 'type' => 'string' ),
							'is_pro'          => array( 'type' => 'boolean' ),
							'currency'        => array( 'type' => 'string' ),
							'currency_symbol' => array( 'type' => 'string' ),
							'decimal_count'   => array( 'type' => 'integer' ),
							'is_zero_decimal' => array( 'type' => 'boolean' ),
							'test_mode'       => array( 'type' => 'boolean' ),
							'active_addons'   => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'campaign_count'  => array( 'type' => 'integer' ),
							'donation_count'  => array( 'type' => 'integer' ),
							'write_enabled'   => array(
								'type'        => 'boolean',
								'description' => __( 'Whether an AI assistant may make changes on this site. When false, every write is refused and you should tell the user to turn on AI write access under Charitable > Tools > AI MCP rather than retrying. Reading campaigns, donations and reports works either way. Each write is also still subject to the WordPress permissions of the user you are acting as.', 'charitable' ),
							),

							/*
							 * ADDITIVE, and deliberately so. `write_enabled` above is
							 * left exactly as it was - same key, same type, same
							 * resolution through the master filter - because it is a
							 * shared cross-edition field with existing consumers and
							 * pinned assertions. What changed in Lite 1.8.13 is that
							 * it is no longer the whole answer: AND semantics mean it
							 * reads false for every PARTIAL grant, so an assistant
							 * that trusted it alone would conclude it cannot write at
							 * all on a site where campaign editing works.
							 *
							 * ⚠️ CANDIDATE FOR PRO TO ADOPT. Pro 1.8.18 has one master
							 * switch and does not report this field. If Pro gains
							 * scopes, it should report them under this same key and
							 * shape so the shared ability schema does not diverge -
							 * an assistant should not have to ask which edition it is
							 * talking to before reading a permission.
							 *
							 * @since 1.8.13
							 */

							/*
							 * The edition contract. See execute_get_plugin_info() for why
							 * this belongs here.
							 *
							 * @since 1.8.13
							 */
							'edition'                  => array(
								'type'        => 'string',
								'enum'        => array( 'lite', 'pro' ),
								'description' => __( 'Which Charitable edition is running. Determines what unavailable_capabilities (below) contains.', 'charitable' ),
							),
							'unavailable_capabilities' => array(
								'type'        => 'array',
								'description' => __( 'Capability areas this edition does not provide. Empty on Charitable Pro. Each entry names an AREA, not an individual ability, and its note is written to be relayed to a human rather than acted on.', 'charitable' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'name'     => array( 'type' => 'string' ),
										'requires' => array( 'type' => 'string' ),
										'note'     => array( 'type' => 'string' ),
									),
								),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-site-environment',
				array(
					'label'               => __( 'Get Site Environment', 'charitable' ),
					'description'         => __( 'Returns WordPress, PHP and Charitable page configuration for diagnostics — donation forms are per-campaign and have no sitewide page. Use when troubleshooting why Charitable pages, forms or endpoints are not behaving as expected.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_site_environment' ),
					'permission_callback' => array( __CLASS__, 'permission_manage' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::no_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'wordpress'        => array(
								'type'       => 'object',
								'properties' => array(
									'version'             => array( 'type' => 'string' ),
									'locale'              => array( 'type' => 'string' ),
									'is_multisite'        => array( 'type' => 'boolean' ),
									'home_url'            => array( 'type' => 'string' ),
									'permalink_structure' => array( 'type' => 'string' ),
								),
							),
							'php'              => array(
								'type'       => 'object',
								'properties' => array(
									'version' => array( 'type' => 'string' ),
								),
							),
							'charitable_pages' => array(
								'type'       => 'object',
								'properties' => array(
									'donation_receipt' => self::page_schema(),
									'donor_dashboard'  => self::page_schema(),
									'login'            => self::page_schema(),
									'registration'     => self::page_schema(),
									'profile'          => self::page_schema(),
								),
							),
							'abilities'        => array(
								'type'       => 'object',
								'properties' => array(
									'api_available'      => array( 'type' => 'boolean' ),
									'registered_count'   => array(
										'type'        => 'integer',
										'description' => __( 'Charitable abilities registered in PHP.', 'charitable' ),
									),
									'rest_visible_count' => array(
										'type'        => 'integer',
										'description' => __( 'Charitable abilities a REST client can actually see. If this is LOWER than registered_count, an ability registered but is invisible to every client — usually a missing show_in_rest or a permission callback that is not callable, both of which fail silently. That gap is the first thing to investigate when an assistant cannot see part of the surface.', 'charitable' ),
									),
								),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-gateway-status',
				array(
					'label'               => __( 'Get Gateway Status', 'charitable' ),
					'description'         => __( "Lists the payment gateways that are currently ENABLED, each with its live-or-test mode and whether its credentials are connected. Never returns keys or secrets. Use to answer whether the site can currently accept donations, or why donations are failing. A gateway that is installed but switched off does not appear here at all, so this cannot show that a gateway the user believes is on is actually off — if an expected gateway is missing from this list, say that it is not enabled rather than that it does not exist.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_gateway_status' ),
					'permission_callback' => array( __CLASS__, 'permission_manage' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::no_input_schema(),
					'output_schema'       => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'gateway'    => array( 'type' => 'string' ),
								'label'      => array( 'type' => 'string' ),
								'enabled'    => array( 'type' => 'boolean' ),
								'is_default' => array( 'type' => 'boolean' ),
								'mode'       => array(
									'type' => 'string',
									'enum' => array( 'live', 'test' ),
								),
								'connected'  => array( 'type' => 'boolean' ),
							),
						),
					),
				)
			);
		}

		/**
		 * Execute charitable/get-plugin-info.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public function execute_get_plugin_info() {
			$helper    = charitable_get_currency_helper();
			$donations = (array) wp_count_posts( 'donation' );

			return array(
				'version'         => charitable()->get_version(),
				'is_pro'          => function_exists( 'charitable_is_pro' ) ? (bool) charitable_is_pro() : false,
				'currency'        => charitable_get_currency(),

				/*
				 * get_currency_symbol() returns HTML entities by design
				 * ('&#36;' for USD, '&yen;' for JPY, etc) — correct for
				 * Charitable's HTML output, wrong for this JSON surface, where
				 * an AI client would read the raw entity aloud instead of the
				 * symbol. Decode rather than "fixing" get_currency_symbol()
				 * itself, which every HTML call site still needs entity-encoded.
				 */
				'currency_symbol' => html_entity_decode( $helper->get_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'decimal_count'   => (int) $helper->get_decimals(),
				'is_zero_decimal' => (bool) $helper->is_zero_decimal_currency(),
				'test_mode'       => (bool) charitable_get_option( 'test_mode', 0 ),
				'active_addons'   => $this->get_active_addon_names(),
				'campaign_count'  => (int) wp_count_posts( 'campaign' )->publish,
				'donation_count'  => (int) array_sum( array_map( 'intval', $donations ) ),

				/*
				 * Whether an AI assistant may make changes on this site - the one
				 * master write switch, resolved through
				 * `charitable_abilities_allow_write`.
				 *
				 * 1.8.13 briefly shipped a `write_scopes` map alongside this, and
				 * this flag was then false on any partial grant while individual
				 * writes worked. Both halves of that confusion are gone: one
				 * switch, one field, and this IS the value the gate enforces.
				 */
				'write_enabled'   => (bool) self::write_enabled(),

				/*
				 * The edition contract. This is the orientation ability - its own
				 * description tells an assistant to call it first - so it is where
				 * the boundary belongs. Without it an assistant discovers what Lite
				 * cannot do by trying and failing.
				 *
				 * One entry per gated AREA, not per ability: an assistant needs to
				 * know "donor management needs Pro", not three slugs.
				 *
				 * Derived from what is REGISTERED, not from the license - see
				 * pro_capabilities_available(). `is_pro` above deliberately stays
				 * on license semantics: it is a shared cross-edition field with
				 * existing consumers. These two fields describe the running code.
				 *
				 * @since 1.8.13
				 */
				'edition'                  => $this->pro_capabilities_available() ? 'pro' : 'lite',
				'unavailable_capabilities' => $this->unavailable_capabilities(),
			);
		}

		/**
		 * Capability areas this install cannot provide.
		 *
		 * One entry per gated AREA, not per one of the six gated ability slugs
		 * (see Charitable_Abilities::GATED_ABILITIES) - an assistant needs to
		 * know "donor management needs Pro", not three slugs it has never seen
		 * and never will, because they are never registered here.
		 *
		 * WIDENED in 1.8.13 beyond the GATED_ABILITIES areas: this is "what this
		 * install cannot provide", which is broader than "which ability slugs are
		 * missing". donor-retention and recurring-donations have no ability slug
		 * in EITHER edition - they are analyses Pro reports on and Lite does not
		 * compute at all - and leaving them unnamed here is what let an assistant
		 * build them itself. 1.8.13 pre-release testing asked a Lite site to
		 * "outline the user retention" and got a full donor-retention report,
		 * assembled by querying the donation tables over WP-CLI. No ability was
		 * called, so the write gate never saw it and could not have refused it;
		 * the only lever Charitable has is telling the assistant up front that
		 * this is not a thing this site computes. The existing donor-management
		 * note made that worse by ending "Donation records and the top-donor
		 * report are available on this site", which truthfully points at exactly
		 * the raw material a retention figure is built from.
		 *
		 * Deliberately NOT listed here: data export. Lite ships Charitable >
		 * Tools > Export, so calling it Pro-only would be false and would make an
		 * assistant refuse something the site can do. There is no export ABILITY
		 * among the 24, which is a different statement and does not belong in an
		 * array about edition boundaries.
		 *
		 * Anything added here MUST also get an icon and title in
		 * Charitable_Tools_AI_MCP::get_cards()'s `$gated_presentation`. That
		 * method skips an area it has no presentation entry for, and
		 * Test_Charitable_Abilities_Pro_Gate::test_ai_mcp_screen_pro_gated_cards_match_unavailable_capabilities_exactly
		 * asserts the screen renders one card per named area carrying the SAME
		 * note string - so a named area with no card fails the suite rather than
		 * quietly showing a subset. Tools > AI MCP therefore now carries four
		 * Pro-gated cards, not two. That coupling is deliberate: the screen and
		 * the assistant are supposed to describe this boundary identically, and
		 * the only reason unavailable_capabilities() is public at all is so
		 * there is one copy of these strings instead of two.
		 *
		 * PUBLIC since 1.8.13 (fix round 1, Task 10): Charitable_Tools_AI_MCP's
		 * Pro-gated capability cards read their `note` text from here too, so
		 * the Tools > AI MCP screen and what `charitable/get-plugin-info`
		 * actually tells an assistant cannot say something different about the
		 * same boundary. Widened from private rather than duplicating this
		 * array a second time behind a sync comment, which cannot actually
		 * enforce that the two stay equal.
		 *
		 * @since 1.8.13
		 *
		 * @return array<int,array<string,string>>
		 */
		public function unavailable_capabilities() {
			if ( $this->pro_capabilities_available() ) {
				return array();
			}

			return array(
				array(
					'name'     => 'donor-management',
					'requires' => 'pro',
					'note'     => __( 'Reading individual donor records and donor histories requires Charitable Pro. Donation records and the top-donor report are available on this site.', 'charitable' ),
				),
				array(
					'name'     => 'donation-form-editing',
					'requires' => 'pro',
					'note'     => __( 'Reading or changing a campaign\'s donation form, including adding fields to it, requires Charitable Pro. Campaign content and colours can be changed on this site.', 'charitable' ),
				),
				array(
					'name'     => 'donor-retention',
					'requires' => 'pro',
					'note'     => __( 'This site does not calculate donor retention, repeat-giving, churn or lifetime-value figures; Charitable Pro is what reports on them. Do not assemble them yourself from donation records or by querying the database - say they require Charitable Pro. The reports on this site give totals over a period, day-by-day activity, best-performing campaigns and a top-donor list, and those are the numbers this site can stand behind.', 'charitable' ),
				),
				array(
					'name'     => 'recurring-donations',
					'requires' => 'pro',
					'note'     => __( 'This site does not record or report recurring donations, and every donation on it is one-time. Recurring giving requires Charitable Pro. Do not infer a subscription or a schedule from one donor having given more than once.', 'charitable' ),
				),
			);
		}

		/**
		 * Whether the Pro-gated capability areas are actually available here.
		 *
		 * Derived from what is REGISTERED, not from charitable_is_pro(), which is
		 * a LICENSE check (Charitable_Licenses::is_pro()). The two disagree on a
		 * site running Lite with a valid Pro license in the database - bought Pro
		 * and not installed it yet, or deactivated Pro to troubleshoot - and there
		 * the license answer made get-plugin-info report `edition: pro` with NO
		 * unavailable capabilities while Lite was the only thing running. That
		 * silently disabled the guardrail unavailable_capabilities() exists to
		 * provide: nothing warned the assistant off donor retention, so it was
		 * free to assemble one from the donation tables, and because no ability is
		 * called on that path the write gate could never have refused it.
		 *
		 * A stored license does not load Pro's code. Registration is the same fact
		 * that decides whether these abilities actually run, so deriving the
		 * self-description from it means the two can no longer disagree.
		 *
		 * is_registered() and NOT wp_get_ability(): the latter routes through the
		 * registry's get_registered(), which fires _doing_it_wrong for a name it
		 * does not hold - and on Lite it never holds these six, which is exactly
		 * the case being asked about. See the same reasoning spelled out in
		 * Charitable_Abilities_Usage.
		 *
		 * Fails closed to Lite, which is the right answer for every install that
		 * loads this file: Pro ships its own copy of this registrar and does not
		 * emit the edition contract at all.
		 *
		 * @since 1.8.13
		 *
		 * @return boolean
		 */
		private function pro_capabilities_available() {
			/*
			 * DO NOT add a did_action( 'wp_abilities_api_init' ) guard here. It
			 * looks like free hardening and it is actually a bug - this was tried
			 * and reverted.
			 *
			 * The motivation is real: core primes the registry lazily (nothing
			 * hooks it; the wp_*_ability*() functions each prime on first use),
			 * and priming fires wp_abilities_api_init, registering every ability
			 * on the site. Since this method also runs from Tools > AI MCP's
			 * get_cards(), which renders before that screen's get_diagnostics()
			 * reaches wp_get_abilities(), consulting the registry here moves every
			 * plugin's registration slightly earlier in that one request.
			 *
			 * That is benign - the screen primes the registry later in the very
			 * same render either way - and the guard's cure is worse: "the action
			 * has fired" and "the registry is primed" are NOT the same fact. The
			 * registry is a singleton that outlives a request-scoped action count,
			 * so did_action() can read 0 while the registry is fully primed, and
			 * the guard then reports lite no matter what is registered.
			 * Test_Charitable_Abilities_Pro_Gate caught exactly that: the guarded
			 * version passed in isolation and failed inside its own class, because
			 * WP_UnitTestCase resets $wp_actions between tests while the registry
			 * persists.
			 */
			if ( ! class_exists( 'WP_Abilities_Registry' ) || ! class_exists( 'Charitable_Abilities' ) ) {
				return false;
			}

			$registry = WP_Abilities_Registry::get_instance();

			if ( null === $registry ) {
				return false;
			}

			foreach ( Charitable_Abilities::GATED_ABILITIES as $ability_name ) {
				if ( $registry->is_registered( $ability_name ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Execute charitable/get-site-environment.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public function execute_get_site_environment() {
			return array(
				'wordpress'        => array(
					'version'             => get_bloginfo( 'version' ),
					'locale'              => get_locale(),
					'is_multisite'        => (bool) is_multisite(),
					'home_url'            => home_url(),
					'permalink_structure' => (string) get_option( 'permalink_structure' ),
				),
				'php'              => array(
					'version' => PHP_VERSION,
				),

				/*
				 * Only pages that actually exist in Charitable's settings are reported
				 * here. There is no sitewide "donation page" — donating happens on each
				 * campaign's own permalink via the `campaign_donation` endpoint, which
				 * requires a campaign_id — so that key is deliberately absent rather
				 * than reported as a permanently empty placeholder.
				 */
				'charitable_pages' => array(
					'donation_receipt' => $this->page_info( 'donation_receipt_page', 'auto' ),
					'donor_dashboard'  => $this->page_info( 'donor_dashboard_page', 'auto' ),
					'login'            => $this->page_info( 'login_page', 'wp' ),
					'registration'     => $this->page_info( 'registration_page', 'wp' ),
					'profile'          => $this->page_info( 'profile_page', false ),
				),
				'abilities'        => array(
					'api_available'      => function_exists( 'wp_register_ability' ),
					'registered_count'   => self::count_charitable_abilities(),
					'rest_visible_count' => self::count_rest_visible_charitable_abilities(),
				),
			);
		}

		/**
		 * Count the Charitable abilities a REST client can actually see.
		 *
		 * THE NUMBER THAT MATTERS, and the reason it exists as a separate one.
		 * registered_count is the PHP-side registry; this is the wp-abilities/v1
		 * list. They can disagree, and when they do every client sees the smaller
		 * number while the plugin believes the larger.
		 *
		 * Two ways that has already happened. A missing `show_in_rest` (fixed in
		 * beta5) left 28 abilities registered and 0 visible on WordPress 7.0.x. And
		 * a permission_callback naming a method that does not exist — a partial
		 * deploy of a multi-file change — left 29 registered and 20 visible, with
		 * no error at registration, in the response, or in the log.
		 *
		 * Dispatched through rest_do_request() rather than by reimplementing core's
		 * filtering, because the filtering IS the thing being measured: core checks
		 * show_in_rest AND invokes the permission callback, and a reimplementation
		 * that drifted from it would report a number that is reassuring and wrong.
		 * This is a diagnostic, called rarely, so one internal request is cheap; it
		 * targets the LIST route, never a run route, so there is no recursion.
		 *
		 * @since 1.8.13
		 *
		 * @return integer
		 */
		public static function count_rest_visible_charitable_abilities() {
			if ( ! function_exists( 'rest_do_request' ) ) {
				return 0;
			}

			$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' );
			$request->set_param( 'per_page', 100 );

			$response = rest_do_request( $request );

			if ( $response->is_error() ) {
				return 0;
			}

			$count = 0;

			foreach ( (array) $response->get_data() as $ability ) {
				$name = is_array( $ability ) && isset( $ability['name'] ) ? (string) $ability['name'] : '';

				if ( 0 === strpos( $name, self::CATEGORY . '/' ) ) {
					++$count;
				}
			}

			return $count;
		}

		/**
		 * Count the registered Charitable abilities.
		 *
		 * Counted by NAME PREFIX, never by passing a category argument to
		 * wp_get_abilities(). That argument is a 7.1.0 addition:
		 * `wp_get_abilities()` on WordPress 7.0.x declares ZERO parameters, so
		 * an argument is silently ignored and the call returns EVERY registered
		 * ability rather than Charitable's.
		 *
		 * Measured on a live WordPress 7.0.2 site: this reported 50 when
		 * Charitable had registered 28, counting PushEngage's and core's as
		 * ours. Worse than a wrong number, given what this ability is for -- it
		 * is the diagnostic that answers "why can't the AI see my campaigns",
		 * and it would have said "50 registered" at a moment when the true
		 * answer was "28 registered, 0 visible".
		 *
		 * Charitable_Abilities_System_Info::render() already counted by prefix
		 * and was correct; this brings the two into line.
		 *
		 * @since 1.8.13
		 *
		 * @return int
		 */
		private static function count_charitable_abilities() {
			if ( ! function_exists( 'wp_get_abilities' ) ) {
				return 0;
			}

			$count = 0;

			foreach ( wp_get_abilities() as $ability ) {
				if ( 0 === strpos( $ability->get_name(), self::CATEGORY . '/' ) ) {
					++$count;
				}
			}

			return $count;
		}

		/**
		 * Execute charitable/get-gateway-status.
		 *
		 * Never returns keys, secrets, tokens, client ids or webhook secrets —
		 * only booleans and mode strings.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public function execute_get_gateway_status() {
			$gateways_helper = charitable_get_helper( 'gateways' );

			if ( ! $gateways_helper instanceof Charitable_Gateways ) {
				return array();
			}

			$active_gateways = $gateways_helper->get_active_gateways();
			$default_gateway = $gateways_helper->get_default_gateway();
			$mode            = charitable_get_option( 'test_mode', 0 ) ? 'test' : 'live';

			$statuses = array();

			foreach ( $active_gateways as $gateway_id => $gateway_class ) {
				if ( ! class_exists( $gateway_class ) ) {
					continue;
				}

				$gateway = new $gateway_class();

				$statuses[] = array(
					'gateway'    => (string) $gateway_id,
					'label'      => (string) $gateway->get_label(),
					'enabled'    => true,
					'is_default' => ( (string) $gateway_id === (string) $default_gateway ),
					'mode'       => $mode,
					'connected'  => $this->is_gateway_connected( (string) $gateway_id, $gateway, $mode ),
				);
			}

			return $statuses;
		}

		/**
		 * Build the `{id, url}` pair for one of Charitable's five real page
		 * settings (`donation_receipt_page`, `donor_dashboard_page`, `login_page`,
		 * `registration_page`, `profile_page`).
		 *
		 * Each of these settings stores either a placeholder ("auto", "wp", or
		 * `false`, depending on the setting — see the `$auto_value` argument)
		 * meaning "no static page, Charitable/WordPress handles this
		 * automatically", or a WP page ID. `charitable_get_permalink()` already
		 * resolves the URL correctly for either case for all five endpoints (e.g.
		 * `wp_login_url()` when `login_page` is "wp", the built-in receipt URL
		 * when `donation_receipt_page` is "auto"), so only the id needs separate
		 * resolution here.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $option_key The Charitable setting key, e.g. "login_page".
		 * @param  mixed  $auto_value The setting's own "automatic" default value
		 *                            ("auto", "wp", or false).
		 * @return array { @type integer id, @type string url }
		 */
		private function page_info( $option_key, $auto_value ) {
			$setting = charitable_get_option( $option_key, $auto_value );

			return array(
				'id'  => is_numeric( $setting ) ? (int) $setting : 0,
				'url' => (string) charitable_get_permalink( $option_key ),
			);
		}

		/**
		 * Determine whether a gateway's own credentials are present, without ever
		 * returning the credentials themselves.
		 *
		 * Charitable's gateways don't share one credential shape or a common
		 * "connected" method:
		 * - Stripe exposes `check_keys_exist( $mode )`, which reads through the
		 *   defensive `get_value()` helper.
		 * - Square Core's own `check_keys_exist()` indexes the raw settings array
		 *   without isset() guards and warns on a fresh/unconfigured install, so
		 *   its safer `get_keys( $mode )` (used generically below) is used instead.
		 * - PayPal Commerce tracks a stored merchant id via `is_seller_connected()`.
		 * - PayPal (legacy) and Square (legacy) predate both patterns and are
		 *   checked directly against their own known storage.
		 * - Offline never needs external credentials.
		 *
		 * Unknown/future gateway ids (e.g. a third party added via the
		 * `charitable_payment_gateways` filter) fall through to the generic
		 * `get_keys()` check, or `false` when that is unavailable — never assumed
		 * connected.
		 *
		 * @since 1.8.13
		 *
		 * @param  string             $gateway_id Gateway slug, e.g. "stripe".
		 * @param  Charitable_Gateway $gateway    Instantiated gateway object.
		 * @param  string             $mode       "test" or "live".
		 * @return boolean
		 */
		private function is_gateway_connected( $gateway_id, $gateway, $mode ) {
			switch ( $gateway_id ) {
				case 'offline':
					return true;

				case 'stripe':
					return method_exists( $gateway, 'check_keys_exist' ) && (bool) $gateway->check_keys_exist( $mode );

				case 'square':
					return function_exists( 'charitable_square_is_connected' ) && (bool) charitable_square_is_connected( $mode );

				case 'paypal':
					return class_exists( 'Charitable_Gateway_Paypal' )
						&& '' !== trim( (string) Charitable_Gateway_Paypal::get_paypal_email() );

				case 'paypal_commerce':
					return method_exists( $gateway, 'is_seller_connected' ) && (bool) $gateway->is_seller_connected();
			}

			return $this->gateway_keys_all_present( $gateway, $mode );
		}

		/**
		 * Generic fallback credential check: true only when every value returned
		 * by the gateway's own `get_keys()` is non-empty.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Gateway $gateway Instantiated gateway object.
		 * @param  string             $mode    "test" or "live".
		 * @return boolean
		 */
		private function gateway_keys_all_present( $gateway, $mode ) {
			if ( ! method_exists( $gateway, 'get_keys' ) ) {
				return false;
			}

			$keys = (array) $gateway->get_keys( $mode );

			if ( empty( $keys ) ) {
				return false;
			}

			foreach ( $keys as $value ) {
				if ( '' === trim( (string) $value ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Return the slugs of every active `charitable-*` addon plugin, excluding
		 * Charitable itself (Lite: `charitable`, Pro: `charitable-pro`) since
		 * `active_addons` is meant to answer "what is installed on top of
		 * Charitable", not restate that Charitable is running.
		 *
		 * @since 1.8.13
		 *
		 * @return string[]
		 */
		private function get_active_addon_names() {
			$exclude = array( 'charitable', 'charitable-pro' );

			/*
			 * `active_plugins` is WordPress core's own filter, not a Charitable
			 * hook, so the PrefixAllGlobals sniff is wrong here - prefixing it
			 * would just invent a hook nobody fires. It is applied rather than
			 * reading the raw option so that anything filtering the active list
			 * (multisite, mu-plugins, a must-use loader) is reflected in what we
			 * report, instead of reporting plugins that are not really running.
			 *
			 * phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			 */
			$active = (array) apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$addons = array();

			foreach ( $active as $plugin_path ) {
				$slug = explode( '/', (string) $plugin_path )[0];

				if ( 0 !== strpos( $slug, 'charitable-' ) ) {
					continue;
				}

				if ( in_array( $slug, $exclude, true ) ) {
					continue;
				}

				$addons[] = $slug;
			}

			return $addons;
		}

		/**
		 * Reusable {id, url} schema for a Charitable page.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function page_schema() {
			return array(
				'type'       => 'object',
				'properties' => array(
					'id'  => array( 'type' => 'integer' ),
					'url' => array( 'type' => 'string' ),
				),
			);
		}
	}

endif;
