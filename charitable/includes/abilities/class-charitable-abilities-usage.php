<?php
/**
 * Counts how the Charitable abilities surface is actually being used.
 *
 * WHY THIS IS NOT DERIVED FROM THE LOG. Tools > Logs already records ability
 * activity, so a counter looks redundant until you ask it the one question the
 * feature needs answered — "is anyone using this?" — and find the log cannot
 * answer it:
 *
 * - **Reads are not logged, by design.** `log_read()` sits behind the
 *   `charitable_abilities_log_reads` filter, off by default, because two dozen
 *   read abilities inside an agentic loop would fill the table with rows nobody
 *   reads. A WPVibe session is mostly reads, so a healthy integration writes
 *   almost nothing to the log.
 * - **Logging can be switched off entirely** (`Charitable_Log::is_enabled()`),
 *   which takes a log-derived count to exactly zero.
 * - **Retention prunes rows** after 30 days, or 90 with Recurring.
 *
 * All three make "working perfectly" and "never connected" produce the same
 * log. This counter is therefore deliberately independent of it, and
 * Test_Charitable_Abilities_Usage_Counter pins that independence.
 *
 * WHAT IT DELIBERATELY DOES NOT STORE. The counter is the source for a field
 * that leaves the site in the usage check-in, so the privacy line is drawn
 * here rather than at the payload boundary: only the closed vocabulary of
 * registered ability slugs, four integers and two dates. Never ability input
 * or output — `wp_ability_invoked` hands us `$input`, which carries campaign
 * titles, donor names and amounts, and it is never read. Never log text,
 * never object ids, never a user id.
 *
 * ⚠️ THESE COUNTS ARE A FLOOR, NOT AN EXACT TALLY. flush() is a
 * read-modify-write of one serialised option, and the options API offers no
 * atomic increment, so two requests invoking abilities at the same moment can
 * each read the same stored value and one delta is lost. MCP clients do issue
 * tool calls in parallel, so this is reachable rather than theoretical.
 *
 * Accepted rather than fixed, because every fix is worse: a row per call
 * recreates the log-flooding problem the log already has, and a lock or a
 * separate table is a schema change to answer "is anyone using this". The
 * questions this data serves — is it used, by how many sites, for what, does it
 * succeed, do they stay — are all answered by magnitude and by the dates.
 * **Do not build anything that needs these to be exact,** and do not reconcile
 * them against the log: the two measure deliberately different populations
 * (the log cannot see reads; this cannot see anything before 1.8.13).
 *
 * WHAT IS PER-ABILITY AND WHAT IS NOT. `calls` is per-ability; the four
 * outcomes are site-wide totals. That split is deliberate, not an oversight:
 * per-ability outcomes would double the payload map to answer a question the
 * log already answers better, since log_failure() and log_denial() both record
 * the ability name, and System Info surfaces the most recent of each.
 *
 * @package   Charitable/Classes/Charitable_Abilities_Usage
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Abilities_Usage' ) ) :

	/**
	 * Charitable_Abilities_Usage
	 *
	 * @since 1.8.13
	 */
	class Charitable_Abilities_Usage {

		/**
		 * The option the counter persists to.
		 *
		 * Never autoloaded. It is read twice — once in Tools > System Info and
		 * once in the weekly check-in — and holds up to 29 keys, so autoloading
		 * it would add it to the alloptions blob of every request forever to
		 * serve two readers a week.
		 *
		 * @since 1.8.13
		 * @var   string
		 */
		const OPTION = 'charitable_abilities_usage';

		/**
		 * Stored shape version, so a future change can migrate rather than guess.
		 *
		 * @since 1.8.13
		 * @var   int
		 */
		const VERSION = 1;

		/**
		 * The ability-name prefix this class counts.
		 *
		 * @since 1.8.13
		 * @var   string
		 */
		const PREFIX = 'charitable/';

		/**
		 * Invocations this request, slug => count.
		 *
		 * @since 1.8.13
		 * @var   array<string,int>
		 */
		private static $calls = array();

		/**
		 * Successful executions this request.
		 *
		 * @since 1.8.13
		 * @var   int
		 */
		private static $ok = 0;

		/**
		 * Permission denials this request.
		 *
		 * @since 1.8.13
		 * @var   int
		 */
		private static $denied = 0;

		/**
		 * Writes refused by the AI write gate this request.
		 *
		 * @since 1.8.13
		 * @var   int
		 */
		private static $refused = 0;

		/**
		 * Whether anything happened worth a database write.
		 *
		 * Tracked separately rather than inferred from the counters above,
		 * because a request that recorded nothing must not write the option at
		 * all — this runs on `shutdown` for EVERY request on the site.
		 *
		 * @since 1.8.13
		 * @var   bool
		 */
		private static $touched = false;

		/**
		 * Record that an ability was invoked.
		 *
		 * Hooked from the `wp_ability_invoked` handler, which core fires once
		 * per `execute()` call before any processing — so this counts calls
		 * regardless of how they end.
		 *
		 * That seam matters. Core evaluates permission callbacks on its own to
		 * decide which abilities to even LIST, and the REST list route does it
		 * for every registered ability on every request. Counting from inside a
		 * permission callback would therefore count listings, not uses, and a
		 * single client connecting would report 29 calls.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability_name The ability being invoked.
		 * @return void
		 */
		public static function record_invocation( $ability_name ) {
			$slug = self::slug( $ability_name );

			if ( '' === $slug ) {
				return;
			}

			if ( ! isset( self::$calls[ $slug ] ) ) {
				self::$calls[ $slug ] = 0;
			}

			++self::$calls[ $slug ];
			self::$touched = true;
		}

		/**
		 * Record a successful execution.
		 *
		 * Hooked to `wp_after_execute_ability`, which core fires ONLY on
		 * success: `do_execute()` returning a WP_Error returns from `execute()`
		 * before the action runs, and so does a failed output validation. That
		 * asymmetry is what lets `failed` be derived in get() instead of needing
		 * a seam of its own — there is no "ability failed" action in core to
		 * hook even if we wanted one.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability_name The ability that executed.
		 * @return void
		 */
		public static function record_success( $ability_name ) {
			if ( '' === self::slug( $ability_name ) ) {
				return;
			}

			++self::$ok;
			self::$touched = true;
		}

		/**
		 * Record a permission denial.
		 *
		 * Kept apart from failures because support and product read them
		 * differently: a denial is about who is calling — credentials, roles,
		 * capabilities — while a failure is about a bug or bad input.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability_name The ability that was refused.
		 * @return void
		 */
		public static function record_denial( $ability_name ) {
			if ( '' === self::slug( $ability_name ) ) {
				return;
			}

			++self::$denied;
			self::$touched = true;
		}

		/**
		 * Record a write refused by the AI write gate.
		 *
		 * Distinct from a denial: the caller had every permission, the site
		 * simply has AI writes switched off. Fleet-wide that is a product
		 * signal — customers who have the feature and have not turned it on —
		 * rather than a fault, so it must not be mixed in with either denials
		 * or failures.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $ability_name The ability that was refused.
		 * @return void
		 */
		public static function record_refusal( $ability_name ) {
			if ( '' === self::slug( $ability_name ) ) {
				return;
			}

			++self::$refused;
			self::$touched = true;
		}

		/**
		 * Persist this request's counts, merging into whatever is stored.
		 *
		 * Called on `shutdown`. Counting happens in memory and lands in one
		 * option write per request, rather than a write per ability call: an
		 * MCP client drives one ability per HTTP request, but an in-process
		 * agentic loop can drive dozens, and a write per call is the kind of
		 * cost that resurfaces later as a ticket about admin slowness.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public static function flush() {
			if ( ! self::$touched ) {
				return;
			}

			$stored = self::stored();

			foreach ( self::$calls as $slug => $count ) {
				$previous                 = isset( $stored['calls'][ $slug ] ) ? (int) $stored['calls'][ $slug ] : 0;
				$stored['calls'][ $slug ] = $previous + (int) $count;
			}

			$stored['ok']      = (int) $stored['ok'] + self::$ok;
			$stored['denied']  = (int) $stored['denied'] + self::$denied;
			$stored['refused'] = (int) $stored['refused'] + self::$refused;

			/*
			 * Day granularity, not a timestamp. Every adoption and retention
			 * question this feeds is answered by a date, and a date cannot be
			 * correlated back to an individual administrator's working pattern
			 * the way a time-of-day can.
			 */
			$today = gmdate( 'Y-m-d' );

			if ( '' === (string) $stored['first_at'] ) {
				$stored['first_at'] = $today;
			}

			$stored['last_at'] = $today;
			$stored['v']       = self::VERSION;

			update_option( self::OPTION, $stored, false );

			self::$calls   = array();
			self::$ok      = 0;
			self::$denied  = 0;
			self::$refused = 0;
			self::$touched = false;
		}

		/**
		 * The counter, with failures and the invocation total derived.
		 *
		 * Counts are CUMULATIVE and reading never clears them. Deliberately not
		 * a window that resets when a check-in succeeds: the check-in pipeline's
		 * own history is the argument against that. record_checkin_failure()
		 * exists because sends fail, and the great majority of reporting sites
		 * have historically managed a single check-in ever. A window that clears
		 * on send loses its data every time a send fails, silently; a cumulative
		 * total lets a receiver diff successive snapshots and lose nothing.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public static function get() {
			$stored = self::stored();

			$calls = array();

			foreach ( (array) $stored['calls'] as $slug => $count ) {
				$calls[ (string) $slug ] = (int) $count;
			}

			$total   = array_sum( $calls );
			$ok      = (int) $stored['ok'];
			$denied  = (int) $stored['denied'];
			$refused = (int) $stored['refused'];

			/*
			 * Clamped at zero rather than allowed to go negative. The three
			 * counted outcomes and the invocation total are written by different
			 * seams, so a request that dies between them - a fatal inside an
			 * execute callback, a PHP timeout - can leave ok+denied+refused one
			 * ahead of total. A negative "failures" figure in a support paste
			 * would be read as a bug in Charitable rather than as the rounding
			 * artefact it is.
			 *
			 * A second, distinct reason for this same clamp, added in Lite 1.8.13:
			 * wp_after_execute_ability is core @since 6.9.0, but wp_ability_invoked
			 * is @since 7.1.0. On WordPress 6.9 and 7.0.x, record_success() (hooked
			 * to the former) runs while record_invocation() (hooked to the latter)
			 * never fires at all, so $total stays at 0 while $ok climbs — the exact
			 * same shape of negative that the clamp above already exists to catch,
			 * from a different cause. This is a live defect in shipped Pro too, not
			 * a Lite-only concern; see get_counts() for the companion field that
			 * lets a consumer tell "nothing happened" apart from "this core cannot
			 * tell us".
			 *
			 * @since 1.8.13
			 */
			$failed = max( 0, $total - $ok - $denied - $refused );

			return array(
				'v'        => (int) $stored['v'],
				'calls'    => $calls,
				'total'    => (int) $total,
				'ok'       => $ok,
				'denied'   => $denied,
				'refused'  => $refused,
				'failed'   => $failed,
				'first_at' => (string) $stored['first_at'],
				'last_at'  => (string) $stored['last_at'],
			);
		}

		/**
		 * The counter from get(), plus whether this core can see every invocation.
		 *
		 * Added in Lite 1.8.13 as the public-facing wrapper System Info's own
		 * usage_summary()/busiest_abilities() and the ported Pro test suite do NOT
		 * use — both call get() directly, verbatim, and continue to work unchanged.
		 * This method exists for consumers (the weekly usage check-in, and any new
		 * caller) who need the observation floor alongside the counts, without
		 * changing get()'s own return shape.
		 *
		 * @since 1.8.13
		 *
		 * @return array get()'s array, plus 'observation_complete'.
		 */
		public static function get_counts() {
			$counts = self::get();

			/*
			 * True when this core can see every invocation. Below WP 7.1 the
			 * wp_ability_invoked action does not exist, so `calls` reads 0 on a
			 * site an assistant is actively using. Reporting the floor is the
			 * difference between "nothing happened" and "we cannot tell".
			 *
			 * @since 1.8.13
			 */
			$counts['observation_complete'] = (int) did_action( 'wp_ability_invoked' ) > 0
				|| version_compare( get_bloginfo( 'version' ), '7.1', '>=' );

			return $counts;
		}

		/**
		 * Clear both halves of the counter. Test seam.
		 *
		 * The in-memory half has to be cleared explicitly: statics survive the
		 * per-test transaction rollback that resets options, so without this
		 * counts leak from one test into the next.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public static function reset() {
			self::$calls   = array();
			self::$ok      = 0;
			self::$denied  = 0;
			self::$refused = 0;
			self::$touched = false;

			delete_option( self::OPTION );
		}

		/**
		 * The stored counter, with every key guaranteed present.
		 *
		 * A non-array option degrades to empty rather than fatalling: this is
		 * read inside a cron check-in and inside a System Info render, and
		 * neither is a place to discover that somebody hand-edited an option.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function stored() {
			$stored = get_option( self::OPTION, array() );

			if ( ! is_array( $stored ) ) {
				$stored = array();
			}

			$stored = array_merge(
				array(
					'v'        => self::VERSION,
					'calls'    => array(),
					'ok'       => 0,
					'denied'   => 0,
					'refused'  => 0,
					'first_at' => '',
					'last_at'  => '',
				),
				$stored
			);

			if ( ! is_array( $stored['calls'] ) ) {
				$stored['calls'] = array();
			}

			return $stored;
		}

		/**
		 * The unprefixed slug of a REGISTERED Charitable ability, or '' otherwise.
		 *
		 * The prefix is dropped because every key in the stored map is a
		 * Charitable ability by construction, so carrying `charitable/` would
		 * repeat eleven bytes of nothing up to twenty-nine times in a payload
		 * field.
		 *
		 * THE REGISTRY CHECK IS NOT BELT-AND-BRACES. Without it this method
		 * accepts any `charitable/`-prefixed string, and `wp_ability_invoked` is
		 * a public action that anything on the site can fire — so one buggy
		 * do_action() in a loop could grow the stored option without bound, a
		 * key at a time. Renaming an ability between releases would also leave
		 * its old key in the map permanently.
		 *
		 * It is also what makes the vocabulary genuinely CLOSED rather than
		 * closed by convention, and two things downstream depend on that being
		 * true: the privacy contract (nothing but known slugs leaves the site)
		 * and the receiver's allowlist, which drops keys it does not recognise.
		 *
		 * Nothing real is lost by requiring registration. An unregistered
		 * ability cannot be executed through core in the first place —
		 * execute() is a method on a WP_Ability the registry handed out — so the
		 * only callers this rejects are ones faking the action.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $ability_name The ability name.
		 * @return string
		 */
		private static function slug( $ability_name ) {
			if ( ! is_string( $ability_name ) || 0 !== strpos( $ability_name, self::PREFIX ) ) {
				return '';
			}

			/*
			 * No registry means no Abilities API, which means no ability can
			 * have run. Counting nothing is the correct answer, not a fallback
			 * to the prefix check.
			 */
			if ( ! class_exists( 'WP_Abilities_Registry' ) ) {
				return '';
			}

			$registry = WP_Abilities_Registry::get_instance();

			/*
			 * is_registered() and NOT wp_get_ability(). The latter routes through
			 * the registry's get_registered(), which fires _doing_it_wrong for a
			 * name it does not hold ("Ability ... not found", @since 6.9.0). That
			 * would turn this guard into a notice emitter: every rejected name —
			 * exactly the anomalous case the guard exists for — would raise a
			 * Charitable-attributed notice on any site running WP_DEBUG, and
			 * `wp_ability_invoked` is a public action, so the offending call need
			 * not even be ours. is_registered() is a bare isset() and says
			 * nothing. Caught by the test suite's incorrect-usage detection.
			 */
			if ( null === $registry || ! $registry->is_registered( $ability_name ) ) {
				return '';
			}

			return substr( $ability_name, strlen( self::PREFIX ) );
		}
	}

endif;
