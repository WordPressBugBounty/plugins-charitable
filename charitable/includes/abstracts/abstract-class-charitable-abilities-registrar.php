<?php
/**
 * Base class for Charitable ability registrars.
 *
 * Supplies the shared permission callbacks, input sanitising, money helpers,
 * error scrubbing and logging so subclasses only describe schema and behaviour.
 *
 * @package   Charitable/Abstracts/Charitable_Abilities_Registrar
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Abilities_Registrar' ) ) :

	/**
	 * Charitable_Abilities_Registrar
	 *
	 * @since 1.8.13
	 */
	abstract class Charitable_Abilities_Registrar {

		/**
		 * The ability category all Charitable abilities belong to.
		 *
		 * @since 1.8.13
		 */
		const CATEGORY = 'charitable';

		/**
		 * Register every ability owned by this registrar.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		abstract public function register();

		/**
		 * Register an ability with Charitable's shared defaults applied.
		 *
		 * Defaults: category `charitable`, and **both** `meta.public` and
		 * `meta.show_in_rest` set to true. Setting both is not belt-and-braces;
		 * it is required, and setting only `public` shipped a bug.
		 *
		 * WordPress 7.1 resolves `show_in_rest` from most specific to least:
		 * an explicit `show_in_rest` wins, otherwise the high-level `public`
		 * flag seeds it (class-wp-ability.php:366-367). **WordPress 7.0.x does
		 * not do that second step at all** — it reads `meta['show_in_rest']`
		 * and defaults it to `self::DEFAULT_SHOW_IN_REST`, which is false.
		 * `public` is simply ignored there.
		 *
		 * So an ability registered with only `public => true` is invisible over
		 * `wp-abilities/v1` on 7.0.x. Verified on a live WordPress 7.0.2 site:
		 * all 28 Charitable abilities registered in PHP, and the REST list route
		 * returned 21 — ours absent, `show_in_rest` resolved to **false**.
		 * PushEngage's appeared because they set `show_in_rest` explicitly.
		 *
		 * That is precisely the Formidable failure mode this whole feature
		 * exists to avoid: abilities that register but are invisible to the
		 * install base. And the test suite could not catch it, because the test
		 * environment runs 7.1, where `public` alone works.
		 *
		 * Accepts a top-level `annotations` key for convenience and relocates it
		 * to `meta.annotations`, which is where WP_Ability expects it.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $slug Ability slug, e.g. "charitable/list-campaigns".
		 * @param  array  $args Ability config; see wp_register_ability().
		 * @return void
		 */
		protected function register_ability( $slug, array $args ) {
			$meta = self::default_meta();

			if ( isset( $args['annotations'] ) ) {
				$meta['annotations'] = $args['annotations'];
				unset( $args['annotations'] );
			}

			if ( isset( $args['meta'] ) && is_array( $args['meta'] ) ) {
				$meta = array_merge( $meta, $args['meta'] );
			}

			$args['meta']     = $meta;
			$args['category'] = self::CATEGORY;

			/**
			 * Filters a Charitable ability's meta array before registration.
			 *
			 * The Abilities API `meta` array is free-form, and different MCP
			 * clients read different keys out of it. Charitable sets `public`
			 * and `show_in_rest` — the WordPress-standard exposure signals that
			 * the mainstream bridges (WordPress MCP Adapter, wordpress-mcp,
			 * WPVibe) surface abilities from — plus any `annotations`. A
			 * third-party MCP layer may instead gate its discovery on its own
			 * key, e.g. `meta['mcp']['public']`. This filter lets a site add
			 * whatever such a client expects without editing the plugin, and
			 * without Charitable having to track each vendor's convention in
			 * core.
			 *
			 * The AI write gate is decided from the unfiltered annotations
			 * above, so a filter cannot loosen it: adding `annotations.readonly`
			 * here changes only the advertised meta, never whether a write
			 * actually executes.
			 *
			 * @since 1.8.13
			 *
			 * @param array  $meta The assembled meta: default_meta() plus this
			 *                      ability's annotations and any meta overrides.
			 * @param string $slug The ability slug, e.g. "charitable/list-campaigns".
			 * @param array  $args The full registration args, for context.
			 */
			$args['meta'] = (array) apply_filters( 'charitable_ability_meta', $args['meta'], $slug, $args );

			/*
			 * A callback that is not callable fails SILENTLY, and that is the whole
			 * reason for this guard. WordPress drops an ability whose
			 * permission_callback cannot be invoked from the wp-abilities/v1 list
			 * with no error at registration, none in the response, and none in the
			 * log — the ability is simply absent.
			 *
			 * Found by deploying one file of a two-file change: the campaign
			 * registrar shipped referencing permission_read_campaign() and
			 * permission_edit_campaign() while the abstract registrar defining them
			 * did not, and discover_abilities returned 20 Charitable abilities where
			 * 29 were registered. Same failure shape as the show_in_rest defect:
			 * registered in PHP, invisible over REST, healthy-looking count on the
			 * wrong channel.
			 *
			 * The ability is still registered rather than skipped, deliberately.
			 * Skipping would quietly drop the registered count to match the visible
			 * one and hide the discrepancy; registering leaves
			 * get-site-environment's registered_count and rest_visible_count
			 * disagreeing, which is the signal that survives into production —
			 * _doing_it_wrong() is silent without WP_DEBUG.
			 */
			foreach ( array( 'permission_callback', 'execute_callback' ) as $callback_key ) {
				if ( isset( $args[ $callback_key ] ) && ! is_callable( $args[ $callback_key ] ) ) {
					_doing_it_wrong(
						'Charitable_Abilities_Registrar::register_ability',
						sprintf(
							/* translators: 1: the ability slug, 2: the callback argument name. */
							esc_html__( 'Ability "%1$s" was registered with a %2$s that is not callable, so WordPress will hide it from the abilities REST route without reporting an error. Check that the method exists and that every file of the change was deployed.', 'charitable' ),
							esc_html( $slug ),
							esc_html( $callback_key )
						),
						'1.8.13'
					);
				}
			}

			/*
			 * The AI write gate. Wrapped HERE, deliberately AFTER the callable guard
			 * above: wrapping first would hide a non-callable original inside the
			 * closure, the guard would see a perfectly callable Closure, and the
			 * silent-drop defect that guard exists to catch would come straight back.
			 *
			 * Gated by the `readonly` annotation rather than a list of ability slugs,
			 * so an ability registered without `readonly => true` is gated the moment
			 * it is added. A hand-maintained list of the nine current writes would
			 * silently miss the tenth.
			 *
			 * The setting is read inside the closure, at EXECUTION time, so flipping
			 * the toggle takes effect on the next request without anything
			 * re-registering.
			 *
			 * @since 1.8.13
			 */
			if ( empty( $meta['annotations']['readonly'] ) && isset( $args['execute_callback'] ) ) {
				$args['execute_callback'] = self::gate_write_callback( $slug, $args['execute_callback'] );
			}

			if ( isset( $args['input_schema'] ) && is_array( $args['input_schema'] ) ) {
				$args['input_schema'] = self::allow_absent_input( $args['input_schema'] );
			}

			wp_register_ability( $slug, $args );
		}

		/**
		 * Let an ability that REQUIRES nothing be called with nothing.
		 *
		 * Widens `type: object` to `type: [object, null]` on any input schema
		 * that declares no required properties. One place decides this, applied
		 * to every ability at registration, so no individual schema has to
		 * remember - and a schema added later gets it for free.
		 *
		 * ── WHY ──────────────────────────────────────────────────────────────
		 *
		 * Core validates a missing input envelope as NULL, and `type: object`
		 * fails it with 400 `ability_invalid_input`, "input is not of type
		 * object". For a READONLY ability that is fatal rather than merely
		 * unfriendly: core answers POST with 405 `rest_ability_invalid_method`,
		 * so GET is the only method it accepts, a GET carries no body, and the
		 * only call it permits was the only call it refused. All six abilities
		 * registered with no_input_schema() were unreachable over REST -
		 * including get-plugin-info, the orientation call an assistant is told
		 * to make first.
		 *
		 * Reported from 1.8.13 testing as "a read-only ability that takes no
		 * settings shouldn't reject a plain GET with no input", and confirmed
		 * over rest_do_request() before this changed.
		 *
		 * ── WHY IT IS SCOPED TO "NO REQUIRED PROPERTIES" ─────────────────────
		 *
		 * An ability that genuinely needs an argument must still fail, and fail
		 * on the missing ARGUMENT rather than being handed null and improvising.
		 * get-campaign without a campaign_id is a client bug and should say so.
		 * So `required` being present and non-empty leaves the schema alone.
		 *
		 * `additionalProperties` is never touched, so this widens what counts as
		 * "nothing sent" and never what counts as valid input.
		 *
		 * ── THE CONSEQUENCE, WRITTEN DOWN ────────────────────────────────────
		 *
		 * This deliberately diverges from core's uniform behaviour, which
		 * test-abilities-rest-channel.php had recorded as a client contract:
		 * "always send an input envelope". After this, that guidance is only
		 * needed for abilities with required properties. Any client-facing
		 * documentation of the envelope rule needs to say so.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $schema An ability's input schema.
		 * @return array The schema, widened if it requires nothing.
		 */
		private static function allow_absent_input( array $schema ) {

			if ( ! isset( $schema['type'] ) || 'object' !== $schema['type'] ) {
				return $schema;
			}

			if ( ! empty( $schema['required'] ) ) {
				return $schema;
			}

			$schema['type'] = array( 'object', 'null' );

			return $schema;
		}

		/**
		 * Wrap a write ability's callback so it refuses when AI writes are off.
		 *
		 * Refuses BEFORE the callback runs, so nothing is read, validated or
		 * written on a gated request.
		 *
		 * Asks self::write_enabled(), the one master switch. 1.8.13 briefly
		 * asked a per-consequence scope instead; that was reverted before release
		 * in favour of matching Pro, and write_enabled()'s docblock records why.
		 *
		 * Note this is refuse-only: the ability stays visible in
		 * `/wp-abilities/v1/abilities` and still reports itself to a client. That
		 * is a deliberate choice over hiding it, so the operation count a site
		 * reports does not change with a setting — the changelog says twenty-four
		 * and the e2e floor asserts 24, and they stay true whichever way the
		 * switch is set. System Info and the Tools > AI MCP tab COMPUTE their
		 * counts at request time rather than stating a number, so they follow
		 * the registry automatically.
		 * The trade is that an assistant may offer a write and then be refused,
		 * which is why the message below has to explain itself properly.
		 *
		 * @since 1.8.13
		 *
		 * @param  string   $slug     The ability slug, for the refusal log entry.
		 * @param  callable $callback The ability's real execute callback.
		 * @return Closure
		 */
		private static function gate_write_callback( $slug, $callback ) {

			return function ( ...$params ) use ( $callback, $slug ) {

				if ( ! self::write_enabled() ) {
					Charitable_Abilities_Usage::record_refusal( $slug );

					/*
					 * The LOG row stays generic on purpose. log_write_refused()
					 * takes a slug and writes a fixed message, and the AI Activity
					 * panel classifies refusals against `source => 'write-gate'`
					 * rather than message text.
					 */
					self::log_write_refused( $slug );

					return new WP_Error(
						'charitable_write_access_disabled',
						__( 'AI write access is turned off for this site, so this cannot be changed. An administrator can turn it on under Charitable > Tools > AI MCP. Reading campaigns, donations and reports works either way.', 'charitable' ),
						array( 'status' => 403 )
					);
				}

				return call_user_func_array( $callback, $params );
			};
		}

		/**
		 * Whether AI write access is on for this site. THIS IS THE WRITE GATE.
		 *
		 * One master switch, the same model Charitable Pro uses and the same key
		 * both editions read: `ai_mcp_write_enabled` in the shared
		 * charitable_settings row. Reads are never gated - only abilities that
		 * carry no `readonly` annotation reach the gate at all.
		 *
		 * NOT the only check a write passes. Every ability still runs its own
		 * permission_callback against the WordPress capability it always
		 * required, so an assistant acting as a user who cannot edit campaigns
		 * still cannot, however this switch is set.
		 *
		 * 1.8.13 briefly split this three ways by consequence, behind a
		 * Charitable_Abilities_Scopes class, an `ai_mcp_write_scopes` option and
		 * a second `charitable_abilities_allow_write_scope` filter. Reverted
		 * before release in favour of matching Pro: three switches meant three
		 * ways to be half-on, a refusal that had to name which one applied, and a
		 * legacy boolean kept in AND agreement with the map so a partial Lite
		 * grant could not open all nine of Pro's writes on upgrade. Removed
		 * rather than deprecated, because no released version exposed them.
		 *
		 * What protects the irreversible operations is unaffected and predates
		 * the split: an assistant must confirm explicitly before trashing a
		 * campaign that has donations, or taking a live donation-receiving
		 * campaign offline.
		 *
		 * @since 1.8.13
		 *
		 * @return bool Whether an AI assistant may make changes on this site.
		 */
		public static function write_enabled() {

			$default = ! empty( charitable_get_option( 'ai_mcp_write_enabled', '' ) );

			/**
			 * Filters whether an AI assistant may make changes on this site.
			 *
			 * A real switch in both directions: `__return_false` refuses every
			 * write, `__return_true` allows them, subject as always to each
			 * ability's own WordPress capability check. Applied at exactly ONE
			 * call site, which is this one.
			 *
			 * Both of those were briefly untrue: 1.8.13's scoped permissions made
			 * this a veto only, and applied the same hook name at two call sites
			 * with different defaults. Reverting to one switch restores the
			 * behaviour Pro's changelog documents.
			 *
			 * @since 1.8.13
			 *
			 * @param bool $enabled Defaults to the `ai_mcp_write_enabled` setting,
			 *                      which is OFF on a site that has never set it.
			 */
			return (bool) apply_filters( 'charitable_abilities_allow_write', $default );
		}

		/**
		 * The REST-visibility meta every Charitable ability registers with.
		 *
		 * Factored out of register_ability() so it can be asserted directly.
		 * That is not tidiness — it is the only way to test this correctly.
		 * Reading a REGISTERED ability's `get_meta()` cannot tell you what we
		 * passed, because WordPress 7.1 seeds `show_in_rest` from `public`
		 * itself; on 7.1 the resolved meta says `show_in_rest => true` whether
		 * we set it or not. A test against the registry therefore passes even
		 * with the bug present, which is exactly how the bug shipped. Asserting
		 * this method's return value asserts OUR contribution.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		protected static function default_meta() {
			return array(

				/*
				 * `public` is the documented high-level flag on 7.1, and
				 * `show_in_rest` is what 7.0.x actually reads. BOTH are needed:
				 * 7.0.x ignores `public` and defaults `show_in_rest` to false,
				 * so `public` alone hides the ability from every MCP client on
				 * the current WordPress release. An explicit `show_in_rest`
				 * wins over `public` on 7.1, so setting both is consistent
				 * rather than contradictory.
				 */
				'public'       => true,
				'show_in_rest' => true,

				/*
				 * Some MCP layers gate their tool discovery on a nested
				 * `meta['mcp']['public']` flag rather than the top-level `public`
				 * above. Our sibling plugin WPForms sets this same key, and
				 * WPVibe reads it, so it is the de-facto convention across the
				 * AM/WPVibe MCP stack even though core does not define it (the
				 * meta array is free-form). Set it on every ability so
				 * Charitable's operations are discoverable out of the box by
				 * those clients rather than only by ones that read `public`.
				 *
				 * This affects DISCOVERY only, and deliberately does NOT copy
				 * WPForms' model of hiding writes from discovery when writes are
				 * off. Charitable keeps all abilities discoverable — the
				 * "every registered ability is also REST-visible" invariant
				 * that System Info, the Tools > AI MCP tab and the e2e floor
				 * all assert stays true (24 of 24 in Lite today) — and a write is
				 * still refused at execution time when AI write access is off,
				 * via gate_write_callback(). A site can still override this per
				 * ability through the `charitable_ability_meta` filter.
				 */
				'mcp'          => array(
					'public' => true,
				),
			);
		}

		/**
		 * Normalise ability input, and read one integer id out of it safely.
		 *
		 * Every `execute_*` method has to be **public**, because core needs it
		 * as a callback. That makes each one a reachable entry point for any
		 * internal caller holding a registrar instance — and this codebase
		 * already does exactly that:
		 * `Charitable_Donor_Abilities::execute_get_donor_donations()` calls
		 * `Charitable_Donation_Abilities::execute_list_donations()` directly, to
		 * keep the money-safe listing logic in one place rather than duplicating
		 * it. That call bypasses core's schema validation entirely.
		 *
		 * Fuzzed rather than assumed. 252 hostile calls through `execute()`
		 * produced ZERO PHP errors — core's validator catches everything. The
		 * same 252 made directly against the `execute_*` methods produced 83:
		 * `null`, a bare string, an int and a `stdClass` all reached
		 * `$input['campaign_id']` and raised "Trying to access array offset on
		 * null", "Undefined array key" or a TypeError instead of a WP_Error.
		 * Latent rather than live, but a public method that fatals on `null` is
		 * the same class of landmine as
		 * `Charitable_Campaign_Builder_Templates::get_instance()` returning the
		 * wrong class — catalogued in this codebase as exactly that.
		 *
		 * Returning 0 for a missing or unusable id is the correct behaviour, not
		 * a swallow: every `find_*()` guard rejects 0 with a "not found"
		 * WP_Error, which is what a caller passing nothing should get back.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed  $input Ability input. Anything at all.
		 * @param  string $key   The key to read.
		 * @return int
		 */
		protected static function input_id( $input, $key ) {
			if ( ! is_array( $input ) || ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
				return 0;
			}

			return (int) $input[ $key ];
		}

		/**
		 * Normalise ability input to an array.
		 *
		 * The companion to input_id() for methods that read several optional
		 * keys. See that method's docblock for why this is needed at all.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $input Ability input. Anything at all.
		 * @return array
		 */
		protected static function input_array( $input ) {
			return is_array( $input ) ? $input : array();
		}

		/**
		 * Read a bounded integer out of ability input.
		 *
		 * Replaces the `isset( $input[ $k ] ) ? max( 1, min( 100, (int) … ) ) : 20`
		 * idiom, which reads safely but casts unsafely: `isset()` is TRUE for an
		 * array or object value, so `(int) $input['per_page']` on an array still
		 * raises "Array to int conversion" and on an object throws. The
		 * is_scalar() check is the part that idiom was missing.
		 *
		 * An absent or unusable value returns $default; a supplied one is
		 * clamped. That distinction matters: `per_page => 0` should become the
		 * minimum of 1, not silently jump back to the default of 20.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed    $input   Ability input. Anything at all.
		 * @param  string   $key     The key to read.
		 * @param  int      $default Returned when the key is absent or unusable.
		 * @param  int|null $min     Optional lower bound.
		 * @param  int|null $max     Optional upper bound.
		 * @return int
		 */
		protected static function input_int( $input, $key, $default, $min = null, $max = null ) {
			if ( ! is_array( $input ) || ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
				return (int) $default;
			}

			$value = (int) $input[ $key ];

			if ( null !== $min ) {
				$value = max( (int) $min, $value );
			}

			if ( null !== $max ) {
				$value = min( (int) $max, $value );
			}

			return $value;
		}

		/**
		 * Read a string out of ability input.
		 *
		 * Same reasoning as input_int(): a non-scalar value returns the default
		 * rather than raising "Array to string conversion" or throwing on an
		 * object.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed  $input   Ability input. Anything at all.
		 * @param  string $key     The key to read.
		 * @param  string $default Returned when the key is absent or unusable.
		 * @return string
		 */
		protected static function input_string( $input, $key, $default = '' ) {
			if ( ! is_array( $input ) || ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
				return (string) $default;
			}

			return (string) $input[ $key ];
		}

		/**
		 * Permission callback: plugin administration and diagnostics.
		 *
		 * @since 1.8.13
		 *
		 * @return boolean
		 */
		public static function permission_manage() {
			return current_user_can( 'manage_charitable_settings' );
		}

		/**
		 * Permission callback: abilities that address NO PARTICULAR campaign.
		 *
		 * `edit_campaigns` is the plural, GENERAL capability: it carries no post
		 * id and no author check. Use it only where there is genuinely no object
		 * to check — listing the available builder templates, or a listing whose
		 * own query is author-scoped.
		 *
		 * For anything taking a `campaign_id`, use permission_read_campaign() or
		 * permission_edit_campaign() instead. The `campaign` post type registers
		 * `map_meta_cap => true` (Charitable_Post_Types::register_post_types()),
		 * so WordPress resolves the SINGULAR meta capabilities against the post's
		 * author — which is the check wp-admin makes and this one does not.
		 *
		 * Getting that wrong is not theoretical. Charitable Ambassadors grants its
		 * `campaign_creator` role exactly `read` + `edit_campaigns` and nothing
		 * else, automatically, to any user who starts a campaign through the
		 * front-end form. Gating a campaign_id-bearing ability on this callback
		 * therefore lets one ambassador read and rewrite another's fundraiser.
		 *
		 * @since 1.8.13
		 *
		 * @return boolean
		 */
		public static function permission_campaigns() {
			return current_user_can( 'edit_campaigns' );
		}

		/**
		 * Permission callback: reading ONE campaign.
		 *
		 * Requires the general `edit_campaigns` capability as a floor before any
		 * object-level check runs, so a caller holding no Charitable capability
		 * at all is refused regardless of the target campaign's status.
		 *
		 * Above that floor, `read_campaign` is the meta capability that NARROWS
		 * access, so core resolves it against the post: a published campaign is
		 * readable for its goal and amount raised, which are already public on
		 * its own page — `get-campaign-performance`, one of the four abilities
		 * gated on this callback, additionally exposes donor_count and
		 * average_gift to any `edit_campaigns` holder this way, an accepted
		 * boundary rather than an oversight (see U4 in the audit findings doc).
		 * A draft campaign falls through to `edit_others_campaigns`, and a
		 * private one to `read_private_campaigns`; Ambassadors'
		 * `campaign_creator` holds neither.
		 *
		 * @since 1.8.13
		 *
		 * @param  array|null $input Ability input.
		 * @return boolean
		 */
		public static function permission_read_campaign( $input = null ) {
			/*
			 * THE FLOOR, and it must come first.
			 *
			 * The object-level check below NARROWS access; it must never widen it.
			 * Without this line the callback REPLACED the general capability, and
			 * for a PUBLISHED campaign `read_campaign` resolves — via core's
			 * map_meta_cap() `read_post` branch, which short-circuits on any public
			 * post status — to `$post_type->cap->read`. The `campaign` post type
			 * registers `capability_type => 'campaign'` with no explicit
			 * `capabilities` array, so get_post_type_capabilities() leaves that as
			 * the plain core `read`, which every logged-in role holds. Four read
			 * abilities were therefore open to any Subscriber: verified over
			 * wp-abilities/v1, HTTP 200 carrying donor_count and average_gift.
			 *
			 * Caught by
			 * test_a_user_with_no_charitable_capability_cannot_read_a_published_campaign().
			 */
			if ( ! current_user_can( 'edit_campaigns' ) ) {
				return false;
			}

			$campaign_id = is_array( $input ) ? self::input_id( $input, 'campaign_id' ) : 0;

			if ( $campaign_id < 1 ) {
				return true;
			}

			$post = get_post( $campaign_id );

			/*
			 * Not a campaign: defer so the ability's own 404 "no campaign was
			 * found with that ID" is what the caller sees.
			 *
			 * This is load-bearing, not tidiness. current_user_can( 'read_campaign',
			 * <id that does not exist> ) is false, which would turn every mistyped
			 * id into a 403 — and the REST-channel suite asserts a missing record
			 * is a 404 precisely because a permission error tells an assistant the
			 * wrong thing: 404 makes it ask which campaign you meant, 403 makes it
			 * report an access problem that does not exist. Caught by
			 * test_a_missing_record_is_a_404_not_a_500().
			 */
			if ( ! $post || 'campaign' !== $post->post_type ) {
				return true;
			}

			/* The caller's own campaign, whatever its status. */
			if ( (int) $post->post_author === get_current_user_id() ) {
				return true;
			}

			/*
			 * Somebody else's campaign. `read_campaign` is the meta capability, so
			 * core resolves it against the post: a published campaign stays
			 * readable — its goal and amount raised are already public on its
			 * own page, and get-campaign-performance additionally exposes
			 * donor_count and average_gift to any edit_campaigns holder this
			 * way, an accepted boundary (U4 in the audit findings doc) rather
			 * than an oversight — while a draft or private one requires
			 * read_private_campaigns or edit_others_campaigns, which that role
			 * does not hold.
			 */
			return current_user_can( 'read_campaign', $campaign_id );
		}

		/**
		 * Permission callback: writing to ONE campaign.
		 *
		 * `edit_campaign` is the meta capability, which core resolves to
		 * `edit_others_campaigns` when the caller is not the post's author. That
		 * is precisely the check that stops a `campaign_creator` opening someone
		 * else's campaign in wp-admin, and gating on the plural `edit_campaigns`
		 * bypassed it.
		 *
		 * @since 1.8.13
		 *
		 * @param  array|null $input Ability input.
		 * @return boolean
		 */
		public static function permission_edit_campaign( $input = null ) {
			$campaign_id = is_array( $input ) ? self::input_id( $input, 'campaign_id' ) : 0;

			if ( $campaign_id < 1 ) {
				return current_user_can( 'edit_campaigns' );
			}

			$post = get_post( $campaign_id );

			/*
			 * Not a campaign: defer to the general capability so the ability's own
			 * "no campaign was found with that ID" error is what the caller sees,
			 * rather than a permission error that would confirm nothing either way.
			 */
			if ( ! $post || 'campaign' !== $post->post_type ) {
				return current_user_can( 'edit_campaigns' );
			}

			/*
			 * Somebody else's campaign: `edit_others_campaigns`, which is what
			 * core's map_meta_cap resolves `edit_campaign` to for a non-author,
			 * and therefore what wp-admin enforces.
			 */
			if ( (int) $post->post_author !== get_current_user_id() ) {
				return current_user_can( 'edit_others_campaigns' );
			}

			/*
			 * The caller's OWN campaign: the general capability is enough, and
			 * `edit_campaign` deliberately is NOT used here.
			 *
			 * That looks like the stricter, more obvious choice and it is wrong.
			 * For a PUBLISHED post core resolves `edit_campaign` to
			 * `edit_published_campaigns`, which Ambassadors' `campaign_creator`
			 * role does not hold — so using the meta capability would stop an
			 * ambassador editing their own live fundraiser through an ability,
			 * which is the entire purpose of that role. Caught by
			 * test_a_campaign_creator_can_still_write_to_its_own_campaign().
			 *
			 * Authorship is the axis that matters for the vulnerability this
			 * guards; the published/draft split is not. Same shape as
			 * permission_create_manual_donation(), which likewise falls back to an
			 * ownership test rather than a broader capability.
			 */
			return current_user_can( 'edit_campaigns' );
		}

		/**
		 * Permission callback: publishing or re-statusing ONE campaign.
		 *
		 * Charitable's own role model (Charitable_Roles::get_core_caps(),
		 * includes/users/class-charitable-roles.php) defines `publish_campaigns`
		 * as a capability DISTINCT from `edit_campaigns` — mirroring core
		 * WordPress's own `edit_posts`/`publish_posts` split. Both stock
		 * Charitable roles (`administrator`, `campaign_manager`) happen to be
		 * granted both capabilities as a bundle, which is exactly why gating a
		 * status-changing write on `permission_campaigns()` alone can look safe
		 * in testing and still be wrong: `wp_insert_post()`/`wp_update_post()`
		 * perform NO internal capability check of their own, so a role-editor
		 * plugin that splits "draft" from "publish" — a common nonprofit setup
		 * ("volunteers draft, staff approve and publish") — would let a
		 * draft-only user publish through an ability even though the equivalent
		 * admin-UI Publish button would be hidden from them.
		 *
		 * INPUT-AWARE as of the Audit 1 remediation. The capability alone is not
		 * enough: it carries no post id and no author check, so a role holding
		 * publish_campaigns without edit_others_campaigns could take ANOTHER
		 * author's live fundraiser offline through this ability — access wp-admin
		 * refuses, because core's map_meta_cap() resolves `edit_post` to
		 * `edit_others_campaigns` for a non-author. Confirmed by execution before
		 * the fix. Same axis as permission_edit_campaign(): authorship, not
		 * published-vs-draft.
		 *
		 * @since 1.8.13
		 *
		 * @param  array|null $input Ability input.
		 * @return boolean
		 */
		public static function permission_publish_campaigns( $input = null ) {
			if ( ! current_user_can( 'publish_campaigns' ) ) {
				return false;
			}

			$campaign_id = is_array( $input ) ? self::input_id( $input, 'campaign_id' ) : 0;

			if ( $campaign_id < 1 ) {
				return true;
			}

			$post = get_post( $campaign_id );

			/*
			 * Not a campaign: defer, so the ability's own 404 answers rather than a
			 * permission error that would confirm nothing either way. Same reasoning
			 * as permission_read_campaign() and permission_edit_campaign().
			 */
			if ( ! $post || 'campaign' !== $post->post_type ) {
				return true;
			}

			if ( (int) $post->post_author !== get_current_user_id() ) {
				return current_user_can( 'edit_others_campaigns' );
			}

			return true;
		}

		/**
		 * Permission callback: donation read and manual creation.
		 *
		 * The plural, GENERAL capability: it carries no donation id and no author
		 * check. It is the FLOOR for every donation ability, and it is enough on
		 * its own only where there is no single donation to check — a listing
		 * whose own query is author-scoped (execute_list_donations()), or a
		 * summary whose own query is (execute_get_donation_summary()).
		 *
		 * For anything taking a `donation_id`, use permission_read_donation()
		 * instead, which additionally asks whose donation it is.
		 *
		 * @since 1.8.13
		 *
		 * @return boolean
		 */
		public static function permission_donations() {
			return current_user_can( 'edit_donations' );
		}

		/**
		 * Permission callback: reading ONE donation.
		 *
		 * `edit_donations` is the floor and must come first, so a caller holding
		 * no Charitable capability at all is refused whoever authored the
		 * donation. Above that floor this asks the question the general
		 * capability cannot: whose donation is it?
		 *
		 * The rule is Charitable's own, taken from the wp-admin donation list
		 * table, which filters its query by author for anyone without
		 * `edit_others_donations`
		 * (includes/admin/donations/class-charitable-donation-list-table.php:783).
		 * A donation's `post_author` is the donor's WordPress user id —
		 * Charitable_Donation_Processor::parse_donation_data() sets
		 * `post_author => $this->get_donation_data_value( 'user_id',
		 * get_current_user_id() )` (line 1022) — so author IS the ownership axis
		 * here, exactly as it is for campaigns. A guest or manually recorded
		 * donation carries post_author 0 and is therefore NOT the caller's own,
		 * which is the same answer the list table gives: its `author` filter is
		 * the current user id, and 0 never matches it.
		 *
		 * The author comparison is written out rather than delegated to
		 * `current_user_can( 'read_donation', $id )`, and that is a choice, not an
		 * oversight. The meta capability would in fact work — `donation`
		 * registers `map_meta_cap => true`
		 * (Charitable_Post_Types::register_post_types():322), and since every
		 * `charitable-*` status is registered `public => false` with no `private`
		 * flag (register_post_statuses(), same file), core's `read_post` branch
		 * falls through to map_meta_cap( 'edit_post' ) and lands on
		 * `edit_others_donations` for a non-author. It is not used because that
		 * result is four hops of core derivation through non-public custom
		 * statuses: change any one of those `public` flags and this gate silently
		 * becomes a different check. Writing the rule out keeps it identical to
		 * the list table's, and identical to the two query-level sites
		 * (execute_list_donations(), execute_get_donation_summary()) which have no
		 * meta capability available to them at all.
		 *
		 * A missing id, or an id belonging to some other post type, DEFERS to the
		 * floor rather than refusing. That is load-bearing: the ability's own
		 * "no donation with that id exists" is a 404, and the REST-channel suite
		 * asserts it, because a 403 tells an assistant it has an access problem
		 * when what it really has is a wrong id. Same reasoning as
		 * permission_read_campaign(). It must also tolerate `array()` or `null`
		 * without a notice — the contract suite calls check_permissions( array() )
		 * on every registered ability.
		 *
		 * DIVERGENCE FROM PRO, deliberate and documented. Pro 1.8.18 (the pinned
		 * source these abilities were ported from) gates get-donation on
		 * permission_donations() alone and has the same defect. Pro should adopt
		 * this change so the shared schema does not drift silently.
		 *
		 * @since 1.8.13
		 *
		 * @param  array|null $input Ability input.
		 * @return boolean
		 */
		public static function permission_read_donation( $input = null ) {
			if ( ! current_user_can( 'edit_donations' ) ) {
				return false;
			}

			$donation_id = is_array( $input ) ? self::input_id( $input, 'donation_id' ) : 0;

			if ( $donation_id < 1 ) {
				return true;
			}

			$post = get_post( $donation_id );

			/* Not a donation: defer, so the ability's own 404 is what the caller sees. */
			if ( ! $post || 'donation' !== $post->post_type ) {
				return true;
			}

			/* The caller's own donation. */
			if ( (int) $post->post_author === get_current_user_id() ) {
				return true;
			}

			return current_user_can( 'edit_others_donations' );
		}

		/**
		 * Permission callback: donor personally identifiable information.
		 *
		 * @since 1.8.13
		 *
		 * @return boolean
		 */
		public static function permission_sensitive() {
			return current_user_can( 'view_charitable_sensitive_data' );
		}

		/**
		 * Permission callback: reporting.
		 *
		 * @since 1.8.13
		 *
		 * @return boolean
		 */
		public static function permission_reports() {
			return current_user_can( 'export_charitable_reports' );
		}

		/**
		 * Permission callback: a REPORT that also names donors.
		 *
		 * BOTH capabilities, not either. `export_charitable_reports` is what every
		 * other report ability requires — get-reports-overview,
		 * get-reports-activity, get-top-campaigns and get-reports-date-ranges all
		 * gate on permission_reports() — and `view_charitable_sensitive_data` is
		 * the PII gate on top, because the output carries donor names and email
		 * addresses.
		 *
		 * get-top-donors required the sensitive capability ALONE, so a role
		 * holding it without the reports capability was served a ranked list of
		 * donor names, emails and lifetime totals from an ability its four
		 * siblings would have refused. The sensitive capability is the narrower
		 * of the two and reads like the stricter choice, which is exactly why
		 * this was easy to miss: it is narrower on a different axis. It says
		 * "may see donor PII", not "may run reports".
		 *
		 * DIVERGENCE FROM PRO, deliberate and documented. Pro 1.8.18 (the pinned
		 * source these abilities were ported from) has the same defect. Pro
		 * should adopt this change so the shared schema does not drift silently.
		 *
		 * @since 1.8.13
		 *
		 * @return boolean
		 */
		public static function permission_sensitive_reports() {
			return current_user_can( 'export_charitable_reports' )
				&& current_user_can( 'view_charitable_sensitive_data' );
		}

		/**
		 * Return a monetary amount in both raw and formatted form.
		 *
		 * Abilities always return both so a client never has to parse a currency
		 * symbol or a thousands separator. The decimal count is resolved via
		 * get_decimals() and passed to get_monetary_amount() explicitly — its
		 * own `false` default falls back to
		 * `charitable_get_option( 'decimal_count', 2 )`, and
		 * charitable_get_option() returns that hardcoded 2 without ever calling
		 * apply_filters() when the `decimal_count` key is absent from the saved
		 * settings (true for any site whose admin has never saved General
		 * Settings). That silently skips the `charitable_option_decimal_count`
		 * filter Charitable_Currency::maybe_force_zero_decimals() hooks into, so
		 * a zero-decimal currency like JPY would render with 2 decimals.
		 * get_decimals() does not have this gap: it computes a currency-aware
		 * default (0 for zero-decimal currencies) before calling the option
		 * getter, so it is correct even when nothing has been saved. Do not
		 * revert the explicit `$helper->get_decimals()` argument below back to
		 * `false` — the filter does not cover this case.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $amount The amount. May be a locale-formatted string. Non-scalar
		 *                       input (e.g. an array) is treated as 0 rather than coerced.
		 * @return array {
		 *     @type float  $raw       Numeric amount.
		 *     @type string $formatted Localised, currency-symbolised amount.
		 * }
		 */
		protected static function money( $amount ) {
			if ( ! is_scalar( $amount ) ) {
				$amount = 0;
			}

			$helper    = charitable_get_currency_helper();
			$raw       = $helper->sanitize_monetary_amount( (string) $amount, true );
			$formatted = $helper->get_monetary_amount( $raw, $helper->get_decimals(), true );

			/*
			 * get_currency_symbol() (class-charitable-currency.php) returns HTML
			 * entities by design — '&#36;' for USD/AUD/CAD/etc, '&yen;' for
			 * JPY/CNY — which is correct wherever Charitable renders this into
			 * HTML. get_monetary_amount() above embeds that symbol, so
			 * $formatted inherits the entities (reproduced live: goal_formatted
			 * came back as '&#36;12,500.00'). This is a JSON surface read by AI
			 * clients, not HTML, so the entities must be decoded here rather
			 * than "fixed" upstream in the currency class — an assistant reading
			 * the raw entity aloud says "and pound 36, 12,500.00" instead of
			 * "$12,500.00". Do not remove this decode.
			 */
			$formatted = html_entity_decode( $formatted, ENT_QUOTES, 'UTF-8' );

			return array(
				'raw'       => (float) $raw,
				'formatted' => $formatted,
			);
		}

		/**
		 * Parse an inbound monetary amount, rejecting excess precision.
		 *
		 * On a zero-decimal currency site an amount carrying decimal places is
		 * refused rather than silently floored — quietly altering money is worse
		 * than failing the call.
		 *
		 * Non-scalar input (e.g. an array) is rejected with a WP_Error rather
		 * than coerced, which would otherwise emit an "Array to string
		 * conversion" notice.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $amount Inbound amount. JSON may deliver int, float or string.
		 * @return float|WP_Error
		 */
		protected static function parse_money( $amount ) {
			if ( ! is_scalar( $amount ) ) {
				return new WP_Error(
					'charitable_invalid_amount',
					__( 'The amount must be a number or numeric string.', 'charitable' )
				);
			}

			$helper   = charitable_get_currency_helper();
			$decimals = $helper->get_decimals();

			/* sanitize_monetary_amount() requires a string; a float triggers doing_it_wrong(). */
			$sanitized = $helper->sanitize_monetary_amount( (string) $amount, true );

			if ( 0 === (int) $decimals && (float) $sanitized !== floor( (float) $sanitized ) ) {
				return new WP_Error(
					'charitable_invalid_amount',
					sprintf(
						/* translators: %s: currency code. */
						__( 'The site currency (%s) does not use decimal places. Supply a whole number amount.', 'charitable' ),
						charitable_get_currency()
					)
				);
			}

			return (float) $sanitized;
		}

		/**
		 * Strip the data payload from a WP_Error before it leaves an ability.
		 *
		 * Error data can carry licence keys, admin email and gateway envelopes.
		 * An MCP bridge that serialises get_error_data() would log all of it, so
		 * the data payload is dropped at the boundary. The message is preserved.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $error Possibly a WP_Error.
		 * @return mixed Scrubbed WP_Error, or the original value untouched.
		 */
		protected static function scrub_error( $error ) {
			if ( ! is_wp_error( $error ) ) {
				return $error;
			}

			return new WP_Error( $error->get_error_code(), $error->get_error_message() );
		}

		/**
		 * Log a successful write performed by an ability.
		 *
		 * Reads are deliberately not logged — 26 abilities inside an agentic loop
		 * would flood the log table. Opt in with the
		 * `charitable_abilities_log_reads` filter.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability The ability slug.
		 * @param  string $message Human-readable message.
		 * @param  array  $context Optional. Correlation ids: campaign_id, donation_id, donor_id.
		 * @return void
		 */
		protected static function log_write( $ability, $message, array $context = array() ) {
			charitable_log(
				$ability,
				$message,
				array_merge(
					array(
						'type'        => 'abilities',
						'level'       => 'info',
						'source'      => 'core',
						'object_type' => 'ability',
						'user_id'     => get_current_user_id(),
					),
					$context
				)
			);
		}

		/**
		 * Log a read, but only if a site has opted in.
		 *
		 * Reads are NOT logged by default and this is a deliberate default, not
		 * an omission: an agentic loop calling a couple of dozen read abilities
		 * per turn would fill the log table with rows nobody will read, and
		 * Charitable has already had one debug line put 3,033 entries into a
		 * customer's log in a day.
		 *
		 * A site that is debugging what an assistant is actually doing opts in:
		 *
		 *     add_filter( 'charitable_abilities_log_reads', '__return_true' );
		 *
		 * Level is `debug`, so opting in does not mix read noise in with the
		 * writes and failures at `info` and above.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability The ability slug.
		 * @param  array  $context Optional. Correlation ids.
		 * @return void
		 */
		public static function log_read( $ability, array $context = array() ) {
			/**
			 * Filter whether read abilities write a log entry.
			 *
			 * Off by default. Turn it on to trace what an AI client is reading;
			 * expect a row per ability call.
			 *
			 * @since 1.8.13
			 *
			 * @param boolean $enabled Whether to log reads. Default false.
			 * @param string  $ability The ability slug about to be logged.
			 */
			if ( ! apply_filters( 'charitable_abilities_log_reads', false, $ability ) ) {
				return;
			}

			charitable_log(
				$ability,
				__( 'Read.', 'charitable' ),
				array_merge(
					array(
						'type'        => 'abilities',
						'level'       => 'debug',
						'source'      => 'core',
						'object_type' => 'ability',
						'user_id'     => get_current_user_id(),
					),
					$context
				)
			);
		}

		/**
		 * Log a write refused by the AI write gate.
		 *
		 * Public because the refusal is recorded from inside the gate closure
		 * rather than from a subclass, and because the AI Activity panel's tests
		 * drive it directly.
		 *
		 * `source` is `write-gate`, not the `core` default every other log_*()
		 * method here uses: it is the machine-readable discriminator
		 * `Charitable_Tools_AI_MCP::outcome_from_record()` switches on to tell
		 * this refusal apart from a permission denial, since the two share a
		 * log level and their message text is not safe to match on a
		 * translated site. Registered for Tools > Logs by
		 * `Charitable_Abilities::register_log_sources()`. Pro 1.8.18 still logs
		 * this call with `source => 'core'`; adopt `write-gate` there when Pro
		 * gains an equivalent panel.
		 *
		 * The MESSAGE is a FIXED string. It named which of three scopes was off
		 * while 1.8.13 had scoped permissions; with one switch there is only one
		 * thing it could name, and the sentence already says it.
		 *
		 * Fixed is also what the panel relies on: outcome_from_record()
		 * classifies a refusal on `source => 'write-gate'` and never on this
		 * text, which is what lets the row survive translation.
		 * `Test_Charitable_Abilities_Activity::test_refused_and_denied_survive_translation`
		 * keeps that true.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability The ability slug.
		 * @return void
		 */
		public static function log_write_refused( $ability ) {

			charitable_log(
				$ability,
				__( 'Refused: AI write access is turned off for this site.', 'charitable' ),
				array(
					'type'        => 'abilities',
					'level'       => 'warning',
					'source'      => 'write-gate',
					'object_type' => 'ability',
					'user_id'     => get_current_user_id(),
				)
			);
		}

		/**
		 * Log a permission denial.
		 *
		 * `source` is `permission`, not the `core` default every other
		 * log_*() method here uses: it is the machine-readable discriminator
		 * `Charitable_Tools_AI_MCP::outcome_from_record()` switches on to
		 * tell this denial apart from a write-gate refusal, since the two
		 * share a log level and their message text is not safe to match on a
		 * translated site. Registered for Tools > Logs by
		 * `Charitable_Abilities::register_log_sources()`. Pro 1.8.18 still logs
		 * this call with `source => 'core'`; adopt `permission` there when Pro
		 * gains an equivalent panel.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability The ability slug.
		 * @return void
		 */
		public static function log_denial( $ability ) {
			charitable_log(
				$ability,
				__( 'Permission denied.', 'charitable' ),
				array(
					'type'        => 'abilities',
					'level'       => 'warning',
					'source'      => 'permission',
					'object_type' => 'ability',
					'user_id'     => get_current_user_id(),
				)
			);
		}

		/**
		 * Log a validation failure or returned WP_Error.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability The ability slug.
		 * @param  mixed  $error   WP_Error or message string.
		 * @return void
		 */
		protected static function log_failure( $ability, $error ) {
			$message = is_wp_error( $error ) ? $error->get_error_message() : (string) $error;

			charitable_log(
				$ability,
				$message,
				array(
					'type'        => 'abilities',
					'level'       => 'error',
					'source'      => 'core',
					'object_type' => 'ability',
					'user_id'     => get_current_user_id(),
				)
			);
		}

		/*
		 * ---------------------------------------------------------------------
		 * Shared date handling.
		 *
		 * Lives on the base class because every ability taking a date range needs
		 * the same three things and must not get any of them wrong twice: a real
		 * calendar-date check (strtotime() accepts "2026-02-31"), an inverted-range
		 * refusal, and an INCLUSIVE date_query so an end date of today does not
		 * drop today.
		 * ---------------------------------------------------------------------
		 */

		/**
		 * Validate a YYYY-MM-DD string, rejecting impossible calendar dates.
		 *
		 * Note that strtotime() accepts "2026-02-31" and rolls it forward to March, so a
		 * checkdate() pass is required rather than a parse alone.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $value The candidate date.
		 * @return string|WP_Error
		 */
		protected static function validate_ymd( $value ) {
			/* Non-scalar in, clean rejection out — never an "Array to string" notice. */
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'charitable_invalid_date',
					__( 'Dates must be supplied as YYYY-MM-DD.', 'charitable' )
				);
			}

			$value = trim( (string) $value );

			if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches ) ) {
				return new WP_Error(
					'charitable_invalid_date',
					__( 'Dates must be supplied as YYYY-MM-DD.', 'charitable' )
				);
			}

			if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
				return new WP_Error(
					'charitable_invalid_date',
					__( 'That is not a real calendar date.', 'charitable' )
				);
			}

			return $value;
		}

		/**
		 * Parse an optional start_date/end_date pair out of ability input.
		 *
		 * @since 1.8.13
		 *
		 * @param  array  $input         Ability input.
		 * @param  string $default_start Fallback start date.
		 * @param  string $default_end   Fallback end date.
		 * @return array|WP_Error
		 */
		protected static function parse_date_range( $input, $default_start, $default_end ) {
			/*
			 * Read through input_string(), not isset() plus a cast. isset() is TRUE
			 * for an array or object value, so `trim( (string) $input['start_date'] )`
			 * raised "Array to string conversion" on an array — found by fuzzing the
			 * execute_* methods directly, which is a reachable path because they must
			 * be public. A non-scalar date now falls through to the default exactly
			 * as an absent one does.
			 */
			$supplied_start = self::input_string( $input, 'start_date' );

			$start = '' !== trim( $supplied_start )
				? self::validate_ymd( $supplied_start )
				: $default_start;

			if ( is_wp_error( $start ) ) {
				return $start;
			}

			$supplied_end = self::input_string( $input, 'end_date' );

			$end = '' !== trim( $supplied_end )
				? self::validate_ymd( $supplied_end )
				: $default_end;

			if ( is_wp_error( $end ) ) {
				return $end;
			}

			if ( '' !== $start && '' !== $end && $start > $end ) {
				return new WP_Error(
					'charitable_invalid_date_range',
					__( 'The start date must not be later than the end date.', 'charitable' )
				);
			}

			return array(
				'start_date' => $start,
				'end_date'   => $end,
			);
		}

		/**
		 * Build a WP_Query date_query clause from a parsed range.
		 *
		 * `inclusive => true` matters: without it an end_date of 2026-09-04
		 * excludes everything donated on 2026-09-04, so "up to today" silently
		 * drops today's donations.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $dates Parsed start_date/end_date.
		 * @return array
		 */
		protected static function date_query_clause( array $dates ) {
			$clause = array( 'inclusive' => true );

			if ( '' !== $dates['start_date'] ) {
				$clause['after'] = $dates['start_date'];
			}

			if ( '' !== $dates['end_date'] ) {
				$clause['before'] = $dates['end_date'];
			}

			return $clause;
		}

		/**
		 * A reusable empty-object input schema.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		protected static function no_input_schema() {
			return array(
				/*
				 * Plain `object` here. register_ability() widens it to
				 * [object, null] through allow_absent_input(), which does the
				 * same for every schema that requires nothing - see that method
				 * for why a bare call has to be valid.
				 */
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
			);
		}
	}

endif;
