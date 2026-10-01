<?php
/**
 * The Abilities API section of Tools > System Info.
 *
 * Extracted from Charitable_Abilities in 1.8.18. That class's own docblock says
 * it "only wires the WordPress hooks, registers the category, and instantiates
 * the registrars"; it had also grown a 250-line diagnostics reporter, which is
 * this file.
 *
 * The goal of the section is to turn "the AI cannot see my campaigns" into a
 * one-paste diagnosis.
 *
 * THE RULE THIS FILE KEEPS RELEARNING: a line that can report a false problem
 * is worse than no line. Three separate measurements here refuse to run rather
 * than mislead, because each of them once reported a catastrophe on a
 * completely healthy site — see count_rest_visible(), which said "29 registered
 * but 0 visible" under WP-CLI, and application_passwords_status(), which said
 * "NOT available" on a site where WPVibe was authenticating perfectly. Anything
 * added here inherits that rule.
 *
 * @package   Charitable/Classes/Charitable_Abilities_System_Info
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Abilities_System_Info' ) ) :

	/**
	 * Charitable_Abilities_System_Info
	 *
	 * @since 1.8.13
	 */
	class Charitable_Abilities_System_Info {

		/**
		 * Single instance.
		 *
		 * @since 1.8.13
		 * @var   Charitable_Abilities_System_Info|null
		 */
		private static $instance = null;

		/**
		 * Width the labels are padded to, so values line up in a pasted report.
		 *
		 * @since 1.8.13
		 * @var   int
		 */
		const LABEL_WIDTH = 26;

		/**
		 * Returns and/or creates the single instance of this class.
		 *
		 * @since 1.8.13
		 *
		 * @return Charitable_Abilities_System_Info
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * One padded `label: value` line.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $label The label, without its colon.
		 * @param  string $value The value.
		 * @return string
		 */
		private function line( $label, $value ) {
			return str_pad( $label . ':', self::LABEL_WIDTH ) . $value . "\n";
		}

		/**
		 * Make sure is_plugin_active() exists before the detection helpers run.
		 *
		 * Without this the two plugin-detection lines report a FALSE CLEAN —
		 * "None detected" — rather than failing loudly, whenever this section is
		 * generated outside wp-admin, where `wp-admin/includes/plugin.php` is not
		 * loaded. A section that says no MCP client is installed on a site where
		 * WPVibe is active sends support down exactly the wrong path, and it is
		 * the one failure mode a `function_exists()` guard alone produces
		 * silently.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		private static function load_plugin_api() {
			if ( ! function_exists( 'is_plugin_active' ) && is_readable( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
		}

		/**
		 * Append the Abilities API section to Tools > System Info.
		 *
		 * The filter is a STRING filter: each contributor appends
		 * "-- Section Title\n\n" followed by "key: value" lines, matching the
		 * built-in sections.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $sections Existing add-on sections.
		 * @return string
		 */
		public function render( $sections ) {
			$available = function_exists( 'wp_register_ability' );
			$count     = 0;
			$names     = array();

			if ( $available ) {
				foreach ( wp_get_abilities() as $ability ) {
					if ( 0 === strpos( $ability->get_name(), 'charitable/' ) ) {
						++$count;
						$names[] = str_replace( 'charitable/', '', $ability->get_name() );
					}
				}
			}

			/*
			 * The LEADING newline is the section separator, and it is not
			 * optional. Every built-in section is emitted as
			 * "\n" . '-- Title' . "\n\n" (see Charitable_Tools_System_Info),
			 * so the blank line above a heading belongs to the heading rather
			 * than to the section before it. Without it this heading renders
			 * flush against the previous section's last value - which is what
			 * it did until 1.8.18 - and there is correspondingly NO trailing
			 * blank line below, because the next contributor supplies its own.
			 */
			$section = "\n-- Charitable Abilities API\n\n";

			/*
			 * "requires WordPress 6.9" was the whole of this message and it is
			 * not the whole truth: the standalone Abilities API feature plugin
			 * provides the same functions on an older core, so a site running
			 * 6.8 plus that plugin is fully supported. Telling its owner to
			 * update WordPress would be wrong advice in the one output support
			 * reads first.
			 */
			$section .= $this->line(
				'API available',
				$available
					? 'Yes'
					: 'No (needs the Abilities API - WordPress 6.9 or later, or the standalone feature plugin; this site runs ' . get_bloginfo( 'version' ) . ')'
			);

			/*
			 * WHERE the API comes from, which the line above cannot say. Core and
			 * the feature plugin are not interchangeable in support terms: the
			 * plugin version moves independently of WordPress, so "abilities
			 * behave differently on two sites running the same WordPress" is only
			 * explicable with this line present.
			 */
			$section .= $this->line( 'Abilities API source', $this->api_source() );

			$section .= $this->line(
				'Category registered',
				$available && function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( 'charitable' ) ? 'Yes' : 'No'
			);
			$section .= $this->line( 'Charitable abilities', (string) $count );

			/*
			 * The AI write gate. First thing to check on "the assistant says it
			 * cannot change anything", and it is off on a site that has never set
			 * it, so the common case is an owner who does not know it exists.
			 *
			 * TWO lines rather than one, because the stored setting and what the
			 * gate actually permits can legitimately disagree: a snippet plugin
			 * filtering `charitable_abilities_allow_write` shows up as a mismatch
			 * between them rather than being invisible.
			 *
			 * 1.8.13 briefly reported this per scope, when write access was split
			 * three ways. One switch, one stored value - so the per-scope block
			 * and the AND-semantics caveat about the legacy flag are both gone.
			 */
			if ( $available ) {
				$stored = ! empty( charitable_get_option( 'ai_mcp_write_enabled', '' ) );

				$section .= $this->line(
					'AI write access',
					$stored ? 'On' : 'Off (reads work; changes are refused)'
				);

				$section .= $this->line(
					'Writes allowed now',
					( Charitable_Abilities_Registrar::write_enabled() ? 'Yes' : 'No' )
						. ' (resolved through charitable_abilities_allow_write; a mismatch with the line above means a filter is overriding the stored setting)'
				);

				$section .= $this->line( 'Writable abilities', $this->count_write_abilities() . ' of ' . $count );
			}

			$section .= $this->line( 'REST route', rest_url( 'wp-abilities/v1/abilities' ) );
			$section .= $this->line(
				'Pretty permalinks',
				'' !== (string) get_option( 'permalink_structure' )
					? 'Yes'
					: 'No (plain permalinks; /wp-json/ will not resolve and clients must use ?rest_route=)'
			);
			$section .= $this->line( 'MCP clients active', implode( ', ', $this->mcp_client_labels() ) );

			/*
			 * Known REST and application-password blockers. Detected by plugin
			 * presence only, and worded as such: these plugins are all legitimate
			 * and most installs of them do not block anything. But when a client
			 * cannot authenticate and every Charitable-side check passes, this is
			 * the next place to look, and support cannot ask a customer to list
			 * their security plugins faster than reading it here.
			 */
			$section .= $this->line( 'REST access blockers', implode( ', ', $this->get_rest_blockers() ) );

			/*
			 * REST-visible count, next to the registered count above.
			 *
			 * These two diverging IS open item 51: a permission_callback that is
			 * not callable makes WordPress drop the ability from the REST list
			 * while leaving it registered, and nothing anywhere reported it. A
			 * partial deploy produced exactly that — 29 registered, 20 visible —
			 * and the only symptom was an assistant quietly not seeing half the
			 * plugin. Reported unconditionally so the comparison is always in
			 * front of whoever pastes System Info into a ticket.
			 */
			$section .= $this->line( 'REST-visible abilities', $this->count_rest_visible() );

			/*
			 * Every ability on the site, not just ours. Two reasons support needs
			 * it: it distinguishes "Charitable registered nothing" from "the
			 * Abilities API itself registered nothing", and it shows how much
			 * else is competing for the client's attention — an assistant working
			 * against a very large tool surface behaves differently from one with
			 * only Charitable loaded.
			 */
			$section .= $this->line( 'Abilities (all plugins)', $this->count_all_abilities() );

			/*
			 * Application passwords. WPVibe and every other remote MCP client
			 * authenticates with one, and availability is not a given: the
			 * feature is filterable and is unavailable over plain HTTP. When it
			 * is off, connecting fails with nothing else to see.
			 */
			$section .= $this->line( 'Application passwords', $this->application_passwords_status() );

			/*
			 * Whether the log is even on, which every log-derived line below
			 * depends on and none of them could previously state. `Ability log
			 * entries (7d): 0` was ambiguous between three completely different
			 * situations - never connected, connected and only reading, and
			 * logging switched off - and only one of them is a problem.
			 */
			$section .= $this->line( 'Logging', $this->logging_status() );
			$section .= $this->line( 'Ability log entries (7d)', $this->count_recent_log_entries() );

			/*
			 * Last invocation, because a count of 0 cannot distinguish "never
			 * connected" from "connected, and only reading" — reads are not
			 * logged by default (see below), so a working read-only integration
			 * looks identical to a broken one.
			 */
			$section .= $this->line( 'Last ability invoked', $this->last_log_entry() );

			/*
			 * The last refusal, separately, because it names the ability. The
			 * counter below says how many calls were denied; only this says which
			 * one, and "which one" is what turns a denial count into a fix.
			 */
			$section .= $this->line( 'Last denial or refusal', $this->last_denial() );

			/*
			 * Whether read logging is on. Without this line an empty log reads as
			 * a broken integration when it is simply the default: writes and
			 * denials always log, reads are opt-in, and a WPVibe session is
			 * mostly reads.
			 */
			$section .= $this->line(
				'Read logging',
				apply_filters( 'charitable_abilities_log_reads', false, '' )
					? 'On'
					: 'Off (default; writes and denials still log. Filter: charitable_abilities_log_reads)'
			);

			/*
			 * The counter, which is the only line here that sees READS - and so
			 * the only one that can answer "has this ever been used". Independent
			 * of the log entirely: unaffected by logging being switched off, by
			 * retention pruning, and by reads being opt-in. See
			 * Charitable_Abilities_Usage.
			 */
			$section .= $this->line( 'Ability calls (all time)', $this->usage_summary() );
			$section .= $this->line( 'Busiest abilities', $this->busiest_abilities() );

			if ( $count > 0 ) {
				$section .= $this->line( 'Registered', implode( ', ', $names ) );
			}

			/*
			 * A trailing blank line, added 2026-09-16 because the assumption in
			 * the comment above - that the next contributor supplies its own
			 * leading newline - is not true in practice. charitable-rewards
			 * builds `'-- Charitable Rewards' . "\n\n"` with NO leading newline
			 * (its own docblock says it matches the built-in format, so the two
			 * plugins simply read the convention differently), and its heading
			 * therefore rendered flush against this section's last value.
			 *
			 * Defending this section's bottom edge is the fix that does not
			 * require every addon to agree. The trade is one extra blank line
			 * before any section that DOES supply its own leading newline, which
			 * is cosmetic in a plain-text dump and strictly better than two
			 * headings running together.
			 */
			$section .= "\n";

			return $sections . $section;
		}

		/**
		 * Count the Charitable abilities that can change something.
		 *
		 * Derived from the `readonly` annotation, the same source the gate itself
		 * uses, so this number cannot drift from what is actually gated.
		 *
		 * @since 1.8.13
		 *
		 * @return int
		 */
		private function count_write_abilities() {

			if ( ! function_exists( 'wp_get_abilities' ) ) {
				return 0;
			}

			$writes = 0;

			foreach ( wp_get_abilities() as $ability ) {
				if ( ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ) {
					continue;
				}

				if ( 0 !== strpos( $ability->get_name(), 'charitable/' ) ) {
					continue;
				}

				$meta        = method_exists( $ability, 'get_meta' ) ? (array) $ability->get_meta() : array();
				$annotations = isset( $meta['annotations'] ) ? (array) $meta['annotations'] : array();

				if ( empty( $annotations['readonly'] ) ) {
					++$writes;
				}
			}

			return $writes;
		}

		/**
		 * Whether the Abilities API comes from core or from the feature plugin.
		 *
		 * Resolved by reflecting on where wp_register_ability() is actually
		 * defined, rather than by comparing version numbers. A version check
		 * would get this wrong in both directions: a 6.8 site with the feature
		 * plugin reads as unsupported, and a 6.9 site whose plugin copy loads
		 * first reads as core.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		private function api_source() {
			if ( ! function_exists( 'wp_register_ability' ) ) {
				return 'not available';
			}

			try {
				$file = ( new ReflectionFunction( 'wp_register_ability' ) )->getFileName();
			} catch ( ReflectionException $e ) {
				return 'unknown (could not be resolved)';
			}

			if ( ! is_string( $file ) || '' === $file ) {
				return 'unknown (could not be resolved)';
			}

			$file = wp_normalize_path( $file );

			/*
			 * realpath() both sides before comparing, not just wp_normalize_path().
			 * On a system where ABSPATH sits behind a symlink - e.g. macOS's
			 * /tmp -> /private/tmp, which is exactly where the WP core test
			 * install lives - ReflectionFunction::getFileName() returns the
			 * OS-resolved real path while ABSPATH is whatever unresolved string
			 * WordPress booted with. Comparing the two directly makes a file
			 * that IS inside wp-includes fail the prefix check purely because of
			 * the symlink, misreporting core as "a plugin, not core
			 * (wp-includes)" - confirmed: the mismatch disappears once both
			 * sides are resolved the same way. Falling back to the
			 * unresolved/normalized path when realpath() returns false (a
			 * stream-wrapped or otherwise unresolvable path) keeps this the
			 * exact same comparison the method always made in that case.
			 *
			 * @since 1.8.13
			 */
			$includes_dir = realpath( ABSPATH . WPINC );
			$includes_dir = wp_normalize_path( false !== $includes_dir ? $includes_dir : ( ABSPATH . WPINC ) );

			$real_file = realpath( $file );
			$real_file = wp_normalize_path( false !== $real_file ? $real_file : $file );

			if ( 0 === strpos( $real_file, $includes_dir ) ) {
				return 'WordPress core ' . get_bloginfo( 'version' );
			}

			return sprintf(
				'a plugin, not core (%s) - the standalone Abilities API, which versions independently of WordPress',
				basename( dirname( $file ) )
			);
		}

		/**
		 * Detect installed MCP clients that consume the Abilities API.
		 *
		 * Reported so a support reply can distinguish "Charitable registered
		 * nothing" from "nothing is asking". Detection is by plugin presence,
		 * not by capability: any MCP client reads whatever is registered, so the
		 * list is informational.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private function mcp_client_labels() {
			/*
			 * The basename map lives on Charitable_Abilities, not here, because
			 * the usage check-in needs the same detection and wants the slug
			 * half of it. Two copies is how System Info comes to report
			 * "WPVibe" on a site whose telemetry row says no client at all.
			 */
			$active = array();

			foreach ( Charitable_Abilities::get_active_mcp_clients() as $entry ) {
				$active[] = $entry['label'];
			}

			/**
			 * Filter the MCP clients reported in System Info.
			 *
			 * @since 1.8.13
			 *
			 * @param array $active Labels of detected MCP clients.
			 */
			$active = (array) apply_filters( 'charitable_abilities_mcp_clients', $active );

			return ! empty( $active ) ? $active : array( 'None detected' );
		}

		/**
		 * Detect plugins known to be capable of blocking REST or app passwords.
		 *
		 * Presence is NOT proof of blocking, and the wording says so. These are
		 * all legitimate security plugins and most installs block nothing; but
		 * filtering `rest_authentication_errors` or switching off application
		 * passwords is a documented option in every one of them, and it produces
		 * a failure where every Charitable-side check passes and the client
		 * simply cannot sign in.
		 *
		 * Deliberately NOT done by introspecting $wp_filter: core registers its
		 * own callbacks on those hooks, so a count cannot separate ours from
		 * theirs without hardcoding core's set, which then rots each release.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private function get_rest_blockers() {
			self::load_plugin_api();

			$known = array(
				'wordfence/wordfence.php'               => 'Wordfence',
				'better-wp-security/better-wp-security.php' => 'Solid Security',
				'sucuri-scanner/sucuri.php'             => 'Sucuri Security',
				'wp-cerber/wp-cerber.php'               => 'WP Cerber',
				'all-in-one-wp-security-and-firewall/wp-security.php' => 'All In One WP Security',
				'disable-json-api/disable-json-api.php' => 'Disable REST API',
				'disable-wp-rest-api/disable-wp-rest-api.php' => 'Disable WP REST API',
			);

			$found = array();

			foreach ( $known as $plugin => $label ) {
				if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin ) ) {
					$found[] = $label;
				}
			}

			/**
			 * Filter the REST-blocking plugins reported in System Info.
			 *
			 * @since 1.8.13
			 *
			 * @param array $found Labels of detected plugins.
			 */
			$found = (array) apply_filters( 'charitable_abilities_rest_blockers', $found );

			if ( empty( $found ) ) {
				return array( 'None detected' );
			}

			return array( implode( ', ', $found ) . ' - can each restrict REST or application passwords; none does by default, so check their settings before suspecting Charitable' );
		}

		/**
		 * How many Charitable abilities the REST list actually exposes.
		 *
		 * Delegates to the diagnostic registrar rather than repeating the
		 * rest_do_request() walk. That method is the one get-site-environment
		 * reports from, so the two surfaces cannot disagree — and disagreeing
		 * counts are precisely the failure this line exists to reveal.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		private function count_rest_visible() {
			if ( ! class_exists( 'Charitable_Diagnostic_Abilities' ) || ! function_exists( 'rest_do_request' ) ) {
				return 'unknown (diagnostic registrar not loaded)';
			}

			/*
			 * Refuse to measure from the command line rather than report a
			 * false mismatch.
			 *
			 * rest_do_request() under WP-CLI runs with no authenticated user, so
			 * the abilities list comes back empty and this reported
			 * "all registered but only 0 visible" on a completely healthy site
			 * (observed as "29 registered, 0 visible" while the registry still
			 * held 29; Lite ships 24 today, and the shape of the false alarm is
			 * the point rather than the number).
			 * That is a false alarm in the one output people paste into support
			 * tickets, pointing at open item 51 when nothing is wrong — worse
			 * than reporting nothing. Tools > System Info runs in a real admin
			 * request, where the measurement is valid: it reports the full
			 * registered count there, matching the "Charitable abilities" line.
			 */
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return 'not measurable from WP-CLI (no authenticated request); open Tools > System Info in the admin instead';
			}

			$visible    = (int) Charitable_Diagnostic_Abilities::count_rest_visible_charitable_abilities();
			$registered = 0;

			if ( function_exists( 'wp_get_abilities' ) ) {
				foreach ( wp_get_abilities() as $ability ) {
					if ( 0 === strpos( $ability->get_name(), 'charitable/' ) ) {
						++$registered;
					}
				}
			}

			if ( $visible === $registered ) {
				return (string) $visible;
			}

			/* translators: 1: REST-visible count, 2: registered count. */
			return sprintf(
				'%1$d — MISMATCH: %2$d registered but only %1$d visible over REST. An ability with a non-callable permission_callback is dropped from the list silently; check for a partial deploy.',
				$visible,
				$registered
			);
		}

		/**
		 * Every registered ability on the site, and how many are Charitable's.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		private function count_all_abilities() {
			if ( ! function_exists( 'wp_get_abilities' ) ) {
				return 'unknown (the Abilities API is not available)';
			}

			$all  = 0;
			$ours = 0;

			foreach ( wp_get_abilities() as $ability ) {
				++$all;

				if ( is_object( $ability ) && method_exists( $ability, 'get_name' ) && 0 === strpos( $ability->get_name(), 'charitable/' ) ) {
					++$ours;
				}
			}

			return sprintf( '%d registered on this site, %d of them Charitable', $all, $ours );
		}

		/**
		 * Whether a remote client could authenticate with an application password,
		 * and whether any actually exists.
		 *
		 * Guarded against WP-CLI for the same reason as count_rest_visible():
		 * wp_is_application_passwords_available() consults is_ssl(), which is
		 * false with no HTTP request, so this reported "NOT available" on a site
		 * where WPVibe was authenticating perfectly over HTTPS.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		private function application_passwords_status() {
			if ( ! function_exists( 'wp_is_application_passwords_available' ) ) {
				return 'unknown (this WordPress has no application passwords API)';
			}

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return 'not measurable from WP-CLI (is_ssl() is false with no request); open Tools > System Info in the admin instead';
			}

			if ( ! wp_is_application_passwords_available() ) {
				return 'NOT available — a remote client such as WPVibe cannot authenticate. Requires HTTPS, and can be switched off by the wp_is_application_passwords_available filter.';
			}

			/*
			 * Available is only half the answer. A site that has never created
			 * one has a client that cannot connect, and "Available" alone reads
			 * as though authentication is fine. The inventory below is counts
			 * and a date only - never the password NAMES, which are
			 * user-authored strings and belong to the same class of content as
			 * log messages.
			 */
			return 'Available. ' . $this->application_password_inventory();
		}

		/**
		 * How many application passwords exist, and when the newest was made.
		 *
		 * One indexed usermeta query rather than a walk over every user: a site
		 * with 40,000 subscribers is exactly the kind that also has a support
		 * ticket open, and get_users() there would make System Info time out.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @return string
		 */
		private function application_password_inventory() {
			global $wpdb;

			if ( ! class_exists( 'WP_Application_Passwords' ) ) {
				return '';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS
				)
			);

			$passwords = 0;
			$users     = 0;
			$newest    = 0;

			foreach ( (array) $rows as $row ) {
				$entries = maybe_unserialize( $row );

				if ( ! is_array( $entries ) || empty( $entries ) ) {
					continue;
				}

				++$users;
				$passwords += count( $entries );

				foreach ( $entries as $entry ) {
					if ( isset( $entry['created'] ) && (int) $entry['created'] > $newest ) {
						$newest = (int) $entry['created'];
					}
				}
			}

			if ( 0 === $passwords ) {
				return 'No application password has ever been created on this site, so no remote client can sign in yet.';
			}

			/*
			 * An entry with no `created` key would otherwise render as
			 * "newest created 1970-01-01", which reads as corrupt data rather
			 * than as a missing field.
			 */
			$newest_text = $newest > 0 ? 'newest created ' . gmdate( 'Y-m-d', $newest ) : 'creation date unknown';

			return sprintf(
				'%d password(s) across %d user(s)%s; %s.',
				$passwords,
				$users,
				is_multisite() ? ' network-wide, as usermeta is shared across a network' : '',
				$newest_text
			);
		}

		/**
		 * Whether logging is on, and how long rows survive.
		 *
		 * Every log-derived line in this section is meaningless without it. `0`
		 * entries was ambiguous between three unrelated states and only one of
		 * them is a fault.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		private function logging_status() {
			if ( ! class_exists( 'Charitable_Log' ) ) {
				return 'unknown (the logger is not loaded)';
			}

			if ( ! Charitable_Log::is_enabled() ) {
				return 'DISABLED under Tools > Logs - so every log-derived count below reads 0 no matter what an assistant is doing. The ability call counter further down is unaffected.';
			}

			return sprintf( 'Enabled, %d-day retention', (int) Charitable_Log::get_retention_days() );
		}

		/**
		 * When an ability was last invoked, or a plain statement that none has been.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @return string
		 */
		private function last_log_entry() {
			global $wpdb;

			$table = $wpdb->prefix . 'charitable_logs';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
				return 'never (no log table)';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$row = $wpdb->get_row(
				$wpdb->prepare(
					// $table is $wpdb->prefix . 'charitable_logs' and carries no user input; MySQL cannot bind a table identifier as a prepared parameter, so interpolation is the only option.
					"SELECT title, level, create_at FROM {$table} WHERE types LIKE %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'%abilities%'
				)
			);

			if ( empty( $row ) ) {
				return 'never';
			}

			return sprintf( '%s at %s [%s]', (string) $row->title, (string) $row->create_at, (string) $row->level );
		}

		/**
		 * The most recent denial or write-gate refusal, which names the ability.
		 *
		 * Both are logged at `warning` by log_denial() and log_write_refused(),
		 * and both are the cases where knowing WHICH ability was refused is the
		 * difference between a diagnosis and a number.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @return string
		 */
		private function last_denial() {
			global $wpdb;

			$table = $wpdb->prefix . 'charitable_logs';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
				return 'never (no log table)';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$row = $wpdb->get_row(
				$wpdb->prepare(
					// $table is $wpdb->prefix . 'charitable_logs' and carries no user input; MySQL cannot bind a table identifier as a prepared parameter, so interpolation is the only option.
					"SELECT title, message, create_at FROM {$table} WHERE types LIKE %s AND level = %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'%abilities%',
					'warning'
				)
			);

			if ( empty( $row ) ) {
				return 'none recorded';
			}

			return sprintf( '%s at %s - %s', (string) $row->title, (string) $row->create_at, (string) $row->message );
		}

		/**
		 * How many ability log entries were written in the last seven days.
		 *
		 * Reads the `types` and `create_at` columns. Those names matter: the
		 * table has no `log_type` or `log_date`, and querying the names you would
		 * expect returns 0 rather than an error, which reads as "the integration
		 * has never logged anything" on a site with 36 entries.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @return string
		 */
		private function count_recent_log_entries() {
			global $wpdb;

			$table = $wpdb->prefix . 'charitable_logs';

			/*
			 * The table is created lazily, on the first log write. Querying a
			 * table that does not exist yet would emit a database error into the
			 * System Info output — the one place a support diagnosis must not
			 * contain noise.
			 */
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
				return '0 (no log entries yet)';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$count = $wpdb->get_var(
				$wpdb->prepare(
					// $table is $wpdb->prefix . 'charitable_logs' and carries no user input; MySQL cannot bind a table identifier as a prepared parameter, so interpolation is the only option.
					"SELECT COUNT(*) FROM {$table} WHERE types LIKE %s AND create_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'%abilities%',
					gmdate( 'Y-m-d H:i:s', time() - ( 7 * DAY_IN_SECONDS ) )
				)
			);

			return (string) (int) $count;
		}

		/**
		 * The usage counter, rendered.
		 *
		 * The one line here that counts READS, and therefore the only one that
		 * can distinguish a working read-only integration from one that has
		 * never run. See Charitable_Abilities_Usage for why this cannot come
		 * from the log.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		private function usage_summary() {
			if ( ! class_exists( 'Charitable_Abilities_Usage' ) ) {
				return 'unknown (the usage counter is not loaded)';
			}

			$usage = Charitable_Abilities_Usage::get();

			if ( 0 === $usage['total'] ) {
				return 'none - no Charitable ability has ever been invoked on this site';
			}

			return sprintf(
				'%d (%d ok, %d denied, %d refused by the write gate, %d failed), first %s, last %s',
				$usage['total'],
				$usage['ok'],
				$usage['denied'],
				$usage['refused'],
				$usage['failed'],
				'' !== $usage['first_at'] ? $usage['first_at'] : 'unknown',
				'' !== $usage['last_at'] ? $usage['last_at'] : 'unknown'
			);
		}

		/**
		 * The five most-called abilities.
		 *
		 * Answers "what is the assistant actually doing", which the totals
		 * cannot. On a site reporting a problem with one area, this says
		 * immediately whether that area is being reached at all.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		private function busiest_abilities() {
			if ( ! class_exists( 'Charitable_Abilities_Usage' ) ) {
				return 'unknown (the usage counter is not loaded)';
			}

			$calls = Charitable_Abilities_Usage::get()['calls'];

			if ( empty( $calls ) ) {
				return 'none';
			}

			arsort( $calls );

			$parts = array();

			foreach ( array_slice( $calls, 0, 5, true ) as $slug => $count ) {
				$parts[] = $slug . ' ' . (int) $count;
			}

			return implode( ', ', $parts );
		}
	}

endif;
