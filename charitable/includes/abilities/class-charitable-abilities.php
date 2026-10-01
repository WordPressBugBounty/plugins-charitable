<?php
/**
 * Bootstraps Charitable abilities for the WordPress Abilities API (WP 6.9+).
 *
 * Each domain lives in its own registrar under registrars/. This class only
 * wires the WordPress hooks, registers the category, and instantiates the
 * registrars when the API initialises.
 *
 * @package   Charitable/Classes/Charitable_Abilities
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Abilities' ) ) :

	/**
	 * Charitable_Abilities
	 *
	 * @since 1.8.13
	 */
	class Charitable_Abilities {

		/**
		 * Single instance.
		 *
		 * @since 1.8.13
		 * @var   Charitable_Abilities|null
		 */
		private static $instance = null;

		/**
		 * MCP client plugins that consume the Abilities API, by plugin basename.
		 *
		 * ONE map with both halves, because two consumers want different halves
		 * of it: System Info renders the label, and the usage check-in sends the
		 * slug. Kept together so they cannot disagree — a second copy is how you
		 * end up with System Info reporting "WPVibe" on a site whose telemetry
		 * row says no client at all, which reads as a telemetry bug and is not
		 * one.
		 *
		 * The slugs are a closed vocabulary the receiver sanitises against, so
		 * changing one is a receiver change too.
		 *
		 * Note `vibe-ai`, not `wpvibe`: WPVibe is published under that slug.
		 *
		 * @since 1.8.13
		 * @var   array<string,array<string,string>>
		 */
		const MCP_CLIENTS = array(
			'vibe-ai/vibe-ai.php'             => array(
				'slug'  => 'wpvibe',
				'label' => 'WPVibe',
			),
			'wpvibe-ai-mcp/wpvibe-ai-mcp.php' => array(
				'slug'  => 'wpvibe-mcp',
				'label' => 'WPVibe MCP',
			),
			'mcp-adapter/mcp-adapter.php'     => array(
				'slug'  => 'mcp-adapter',
				'label' => 'WordPress MCP Adapter',
			),
			'wordpress-mcp/wordpress-mcp.php' => array(
				'slug'  => 'wordpress-mcp',
				'label' => 'WordPress MCP',
			),
		);

		/**
		 * The MCP clients active on this site.
		 *
		 * Loads the plugin API itself rather than guarding on
		 * `function_exists( 'is_plugin_active' )` alone. Both callers can run
		 * outside wp-admin — one in a cron check-in, one wherever the System
		 * Info filter is applied — and a bare guard there reports "no client
		 * installed" on a site running WPVibe. A false clean is the worst of the
		 * three possible answers, because nothing about it looks wrong.
		 *
		 * @since 1.8.13
		 *
		 * @return array<string,array<string,string>> Basename => entry, active only.
		 */
		public static function get_active_mcp_clients() {
			if ( ! function_exists( 'is_plugin_active' ) && is_readable( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( ! function_exists( 'is_plugin_active' ) ) {
				return array();
			}

			$active = array();

			foreach ( self::MCP_CLIENTS as $basename => $entry ) {
				if ( is_plugin_active( $basename ) ) {
					$active[ $basename ] = $entry;
				}
			}

			return $active;
		}

		/**
		 * Abilities Pro provides and Lite does not.
		 *
		 * These are NOT registered - an assistant's capability list holds only
		 * what it can use, and an ability that exists solely to fail reads as a
		 * broken plugin. This list exists for intercept_gated_ability() below,
		 * which serves a different client: one holding an ability list cached
		 * from a Pro site, or a human pasting a Pro tutorial's slug into an
		 * assistant running against this Lite site.
		 *
		 * @since 1.8.13
		 * @var   string[]
		 */
		const GATED_ABILITIES = array(
			'charitable/get-campaign-donation-form',
			'charitable/update-campaign-donation-form',
			'charitable/add-campaign-donation-form-field',
			'charitable/get-donor',
			'charitable/get-donor-donations',
			'charitable/list-donors',
		);

		/**
		 * Domain registrars.
		 *
		 * Held on the instance deliberately: the registered execute callbacks bind
		 * array( $registrar, 'execute_*' ), so the registrar objects must outlive
		 * register_abilities() or those callbacks point at collected objects.
		 *
		 * @since 1.8.13
		 * @var   Charitable_Abilities_Registrar[]
		 */
		private $registrars = array();

		/**
		 * Returns and/or creates the single instance of this class.
		 *
		 * @since 1.8.13
		 *
		 * @return Charitable_Abilities
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register the `charitable` ability category.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public function register_category() {
			if ( ! function_exists( 'wp_register_ability_category' ) ) {
				return;
			}

			/**
			 * Filters whether Charitable registers its abilities at all.
			 *
			 * A one-line mitigation for a site where another plugin's REST
			 * hardening interacts badly with wp-abilities/v1, so support never
			 * has to say "deactivate Charitable".
			 *
			 * @since 1.8.13
			 *
			 * @param bool $enabled Defaults to true.
			 */
			if ( ! apply_filters( 'charitable_abilities_enabled', true ) ) {
				return;
			}

			wp_register_ability_category(
				'charitable',
				array(
					'label'       => __( 'Charitable', 'charitable' ),

					/*
					 * Differs from Pro's on purpose. Core exposes category label
					 * and description over wp-abilities/v1, so a client
					 * enumerating categories - a call that costs nothing and
					 * needs no ability call - learns the edition boundary
					 * before ever trying a gated slug.
					 *
					 * @since 1.8.13
					 */
					'description' => __( 'Read and manage fundraising campaigns, donations and reports in Charitable. Donor management and donation form editing require Charitable Pro.', 'charitable' ),
				)
			);
		}

		/**
		 * Instantiate every registrar and register its abilities.
		 *
		 * `wp_abilities_api_init` fires exactly once in a normal WordPress
		 * request (WP_Abilities_Registry::get_instance() guards that with
		 * its own private static instance), so the check below never runs
		 * more than once there. It matters when the hook fires more than
		 * once in the SAME process, which the Charitable and WP core test
		 * suites both do deliberately — to test kill-switch behaviour, or to
		 * register an additional test-only ability on a hook that already
		 * fired. A naive second run would ask WP_Abilities_Registry to
		 * register every charitable/* ability a second time, and core
		 * refuses each one with an "already registered" _doing_it_wrong().
		 *
		 * Checking the registry's OWN current content, not a static "have I
		 * ever run" flag: a flag cannot tell "the hook fired again on top of
		 * an unreset registry" (skip — every charitable/* ability is already
		 * there) apart from "the hook fired again because something reset
		 * the registry itself, e.g. via reflection on
		 * WP_Abilities_Registry::$instance" (register — the registry
		 * genuinely has nothing in it now, including no Charitable
		 * abilities). A flag answers the same stale "yes" to both.
		 *
		 * ORDER MATTERS: this guard runs AFTER the charitable_abilities_enabled
		 * kill-switch check below, not before it. test-abilities-contract.php's
		 * test_the_kill_switch_prevents_registration() re-fires this hook with
		 * the kill switch forced off and asserts the ability count does not
		 * change. If this guard ran first, it would return before the kill
		 * switch filter is ever consulted — the test would still pass (nothing
		 * changes either way) but for the wrong reason, and it would no longer
		 * be able to catch a real regression in the kill-switch check itself.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public function register_abilities() {
			if ( ! function_exists( 'wp_register_ability' ) ) {
				return;
			}

			/**
			 * Filters whether Charitable registers its abilities at all.
			 *
			 * A one-line mitigation for a site where another plugin's REST
			 * hardening interacts badly with wp-abilities/v1, so support never
			 * has to say "deactivate Charitable".
			 *
			 * @since 1.8.13
			 *
			 * @param bool $enabled Defaults to true.
			 */
			if ( ! apply_filters( 'charitable_abilities_enabled', true ) ) {
				return;
			}

			foreach ( wp_get_abilities() as $registered_ability ) {
				if ( 0 === strpos( $registered_ability->get_name(), 'charitable/' ) ) {
					return;
				}
			}

			/*
			 * Four registrars, not Pro's five. Donor abilities are Pro-gated in
			 * Lite: Lite's Charitable_Donor lacks six of the eleven methods they
			 * call, and Lite's Donors admin page is an education page rather than
			 * donor management. See the 1.8.13 spec, section 3.2.
			 */
			$this->registrars = array();

			/*
			 * class_exists() guarded, unlike Pro. Pro ships all five registrars in
			 * one release, so this array can never reference a class that is not
			 * yet on disk. This port is landing across several commits instead
			 * (this class first, then the registrars themselves), so for the
			 * window between this commit and the last of those, the classes below
			 * genuinely do not exist yet — and `new` on a name with no class and
			 * no autoloader match is a fatal `Error`, not a WP_Error, thrown
			 * straight out of this wp_abilities_api_init callback with nothing
			 * upstream to catch it. That would fatal every front-end, REST and
			 * WP-CLI request on any WordPress 6.9+ site running this exact
			 * commit. The guard costs nothing once every registrar exists: four
			 * classes that are always loaded are always instantiated, in the same
			 * order, and this array ends up identical to an unguarded literal.
			 *
			 * @since 1.8.13
			 */
			foreach ( array(
				'Charitable_Diagnostic_Abilities',
				'Charitable_Campaign_Abilities',
				'Charitable_Donation_Abilities',
				'Charitable_Report_Abilities',
			) as $registrar_class ) {
				if ( class_exists( $registrar_class ) ) {
					$this->registrars[] = new $registrar_class();
				}
			}

			foreach ( $this->registrars as $registrar ) {
				$registrar->register();
			}
		}

		/**
		 * Add the `abilities` log type so ability activity is filterable in Tools > Logs.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $types Registered log types.
		 * @return array
		 */
		public function register_log_type( $types ) {
			$types['abilities'] = __( 'Abilities', 'charitable' );

			return $types;
		}

		/**
		 * Add the two abilities log sources so Tools > Logs can label and filter them.
		 *
		 * Charitable_Abilities_Registrar::log_write_refused() writes
		 * `source => 'write-gate'` and log_denial() writes
		 * `source => 'permission'` — the discriminator the AI Activity panel
		 * reads to tell a refusal from a denial. Charitable_Log::get_sources()
		 * is an allowlist and Charitable_Log_List_Table::column_source() falls
		 * back to the RAW KEY for anything missing from it, so without this
		 * registration the Source column reads the hyphenated machine slug
		 * `write-gate` while neighbouring abilities rows read "Core". Worse, the
		 * Source filter `<select>` is built from the same allowlist, so neither
		 * slug would be selectable and choosing "Core" would silently exclude
		 * every refusal and denial — on the exact screen the AI Activity panel
		 * links out to.
		 *
		 * Both labels are prefixed "AI" deliberately: this column is shared with
		 * the payment gateways, where a bare "Permission" would be ambiguous.
		 *
		 * Lite-only new code. Pro 1.8.18 logs both of those calls as
		 * `source => 'core'` and has no equivalent registration, so there is
		 * nothing to keep in sync until Pro adopts the two source values.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $sources Registered log sources, slug => label.
		 * @return array
		 */
		public function register_log_sources( $sources ) {
			$sources['write-gate'] = __( 'AI Write Gate', 'charitable' );
			$sources['permission'] = __( 'AI Permission', 'charitable' );

			return $sources;
		}

		/**
		 * Observe every ability invocation, to log denials and opted-in reads.
		 *
		 * Hooked to core's `wp_ability_invoked`, which fires once per execute()
		 * call before any processing. That is the only correct seam for this:
		 *
		 * - Logging a denial from inside a permission callback would fire on
		 *   LISTING, not on use. Core calls permission callbacks on their own to
		 *   decide which abilities a user may see, and the REST list route does
		 *   it for every registered ability on every request.
		 * - Core has no denial-specific action. execute() returns a WP_Error
		 *   from its permission branch before `wp_before_execute_ability` runs,
		 *   so there is nothing later to hook.
		 *
		 * Writes log themselves, from inside their own execute callbacks, where
		 * the campaign/donation/donor ids are known. This only adds the two
		 * cases they cannot see: a call that never reached them, and a read.
		 *
		 * @since 1.8.13
		 *
		 * @param  string     $ability_name The ability being invoked.
		 * @param  mixed      $input        The raw input.
		 * @param  WP_Ability $ability      The ability instance.
		 * @return void
		 */
		public function log_ability_invocation( $ability_name, $input, $ability ) {
			if ( 0 !== strpos( (string) $ability_name, 'charitable/' ) ) {
				return;
			}

			/*
			 * Counted before the permission check, and before the object guard
			 * below, because this is the only seam that sees EVERY call. The
			 * counter's whole purpose is to be able to say "nothing has ever
			 * called this", so a call that is about to be denied, or that
			 * arrives with an ability object we cannot inspect, still counts as
			 * a call.
			 */
			Charitable_Abilities_Usage::record_invocation( $ability_name );

			if ( ! is_object( $ability ) || ! method_exists( $ability, 'check_permissions' ) ) {
				return;
			}

			$permitted = $ability->check_permissions( $input );

			if ( is_wp_error( $permitted ) || ! $permitted ) {
				Charitable_Abilities_Usage::record_denial( $ability_name );
				Charitable_Abilities_Registrar::log_denial( $ability_name );

				return;
			}

			$meta = method_exists( $ability, 'get_meta' ) ? (array) $ability->get_meta() : array();

			if ( ! empty( $meta['annotations']['readonly'] ) ) {
				Charitable_Abilities_Registrar::log_read( $ability_name );
			}
		}

		/**
		 * Turn core's "ability not found" into an honest "needs Pro", for the six.
		 *
		 * Core's run controller (class-wp-rest-abilities-v1-run-controller.php)
		 * answers an unregistered ability name with `rest_ability_not_found` and
		 * `array( 'status' => 404 )` ALREADY SET. set_error_http_status() below
		 * then ignores it twice over: it returns early unless the code starts
		 * with `charitable_`, and separately returns any error that already
		 * carries a status. Since the six GATED_ABILITIES are never registered,
		 * no Charitable code ever runs for them - so without this interceptor,
		 * nothing would ever construct `charitable_capability_unavailable` and
		 * the 422 has no emitter.
		 *
		 * Runs at priority 9, before set_error_http_status() at 10 - see
		 * charitable-abilities-hooks.php. It must run first: by the time
		 * set_error_http_status() sees this error, the status is already set to
		 * 404 by core, and that method's own "never override a status something
		 * upstream set deliberately" guard would leave it there.
		 *
		 * Scoped to the six KNOWN slugs on purpose, not to every unknown
		 * ability name. A genuinely mistyped slug - `charitable/get-donr` -
		 * still deserves core's 404: an assistant that typo'd needs "no such
		 * ability", not "buy Pro".
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed           $response Result of the request handler.
		 * @param  array           $handler  Route handler used for the request.
		 * @param  WP_REST_Request $request  The request.
		 * @return mixed
		 */
		public function intercept_gated_ability( $response, $handler, $request ) {
			if ( ! is_wp_error( $response ) ) {
				return $response;
			}

			if ( 'rest_ability_not_found' !== $response->get_error_code() ) {
				return $response;
			}

			$route = (string) $request->get_route();

			if ( 0 !== strpos( $route, '/wp-abilities/' ) ) {
				return $response;
			}

			/*
			 * Read the parsed `name` param, never re-derive it from the route
			 * string. There is NO `~` or other namespace-separator encoding
			 * anywhere in core's REST API (verified: zero hits for `~` across
			 * wp-includes/rest-api/) - the run controller's own route regex,
			 * `(?P<name>[a-zA-Z0-9\-\/]+?)/run` (class-wp-rest-abilities-v1-run-
			 * controller.php), matches a LITERAL slash, so `charitable/get-donor`
			 * arrives on the wire exactly as written, slash and all.
			 *
			 * WP_REST_Server::match_request_to_handler() already calls
			 * `$request->set_url_params( $args )` with that regex capture before
			 * ANY `rest_request_after_callbacks` filter runs (class-wp-rest-
			 * server.php), so the full, untruncated slug is already sitting on
			 * the request under the `name` param by the time this method sees
			 * it. An earlier version of this method re-parsed the route string
			 * with `#/abilities/([^/]+)#`, which stops at the first slash and
			 * truncates every multi-segment slug to `"charitable"` - silently
			 * disabling the 422 for every real request, since `"charitable"` is
			 * never a member of GATED_ABILITIES. Do not reintroduce that regex.
			 */
			$requested = (string) $request->get_param( 'name' );

			if ( ! in_array( $requested, self::GATED_ABILITIES, true ) ) {
				return $response;
			}

			return new WP_Error(
				'charitable_capability_unavailable',
				__( 'That operation requires Charitable Pro and is not available on this site. Call Get Charitable Plugin Info for the list of capabilities this install does not provide.', 'charitable' ),
				array( 'status' => 422 )
			);
		}

		/**
		 * Give ability errors an accurate HTTP status over wp-abilities/v1.
		 *
		 * Without this EVERY error from EVERY Charitable ability comes back as
		 * HTTP 500. Verified live across the whole surface before this was
		 * written: campaign-not-found, donation-not-found, donor-not-found, an
		 * inverted date range and a rejected write all returned 500.
		 *
		 * The cause is the interaction between two correct things.
		 * rest_convert_error_to_response() (rest-api.php:3464) reads the status
		 * from the WP_Error's DATA, defaulting to 500 when none is present, and
		 * C7 requires the data payload to be dropped at the ability boundary
		 * because upstream error data can carry licence keys, admin email and
		 * gateway envelopes. Scrubbed errors therefore arrive status-less.
		 *
		 * A 500 is not a cosmetic difference to an AI client: it means "the
		 * server broke", so an agent retries it. 404 and 400 mean "your request
		 * was wrong", so an agent corrects itself or tells the user. Reporting
		 * a mistyped campaign id as a server fault is the difference between an
		 * assistant asking which campaign you meant and one insisting the site
		 * is down.
		 *
		 * Applied here, once, rather than at each of the ~60 places an ability
		 * builds a WP_Error: a per-site fix cannot be complete and the next
		 * ability added would miss it. Only the status is added — the dropped
		 * data payload stays dropped, so C7 still holds.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed           $response Result of the request handler.
		 * @param  array           $handler  Route handler used for the request.
		 * @param  WP_REST_Request $request  Request used to generate the response.
		 * @return mixed
		 */
		public function set_error_http_status( $response, $handler, $request ) {
			if ( ! is_wp_error( $response ) ) {
				return $response;
			}

			if ( 0 !== strpos( (string) $request->get_route(), '/wp-abilities/' ) ) {
				return $response;
			}

			$code = $response->get_error_code();

			if ( 0 !== strpos( (string) $code, 'charitable_' ) ) {
				return $response;
			}

			$data = $response->get_error_data();

			/* Never override a status something upstream set deliberately. */
			if ( is_array( $data ) && isset( $data['status'] ) ) {
				return $response;
			}

			$status = 400;

			if ( false !== strpos( (string) $code, '_not_found' ) ) {
				$status = 404;
			} elseif ( false !== strpos( (string) $code, 'permission' ) || false !== strpos( (string) $code, 'denied' ) ) {
				$status = 403;
			}

			$scrubbed = new WP_Error( $code, $response->get_error_message(), array( 'status' => $status ) );

			return $scrubbed;
		}
	}

endif;
