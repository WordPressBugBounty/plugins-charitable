<?php
/**
 * Charitable Tools > AI MCP.
 *
 * Surfaces the WordPress Abilities API, which has no UI of its own. Charitable
 * registers its abilities into core and any MCP client reads them, so without a
 * screen like this a site owner has no way to discover the feature exists, let
 * alone what it can do.
 *
 * Modelled on the shape of WPForms' Tools > AI MCP tab (WPForms Lite 2.0.1.1,
 * `src/Admin/Tools/Views/AiMcp.php` + `templates/admin/tools/ai-mcp.php`) so the
 * two products read as siblings, with Charitable's own branding and content.
 *
 * The write-access toggle rendered below is Charitable's equivalent of WPForms'
 * single "Enable MCP write access". It is saved over AJAX by
 * charitable-ai-mcp.js and stored as `ai_mcp_write_enabled` in the shared
 * charitable_settings option - the same key Charitable Pro reads, so the two
 * editions agree on one value.
 *
 * 1.8.13 briefly split this into three toggles by consequence, behind a
 * Charitable_Abilities_Scopes class and an `ai_mcp_write_scopes` map. Reverted
 * before release in favour of matching Pro; see
 * Charitable_Abilities_Registrar::write_enabled() for the reasoning and for
 * what protects the irreversible operations now.
 *
 * DELIBERATE DIFFERENCE: WPForms gates its write abilities TWO ways - a 403 from
 * `check_write_gate()`, and `'mcp' => [ 'public' => $this->write_enabled() ]`,
 * which hides the write abilities from MCP discovery altogether. Charitable does
 * only the first. `Charitable_Abilities_Registrar::gate_write_callback()` wraps
 * every ability that is not annotated `readonly` and refuses it at execution
 * time, but the ability stays visible in `/wp-abilities/v1/abilities`.
 *
 * That is on purpose: the operation count a site reports must not change with a
 * setting. The changelog, System Info and this screen all say 24, and they stay
 * true whichever way the toggle is set. The trade is that an assistant can offer
 * a write and then be refused, which is why the refusal message names the screen
 * to come and change.
 *
 * Ported from Charitable Pro (pinned commit 4f84275197) for Lite 1.8.13. Lite
 * registers 24 abilities (3 diagnostic + 12 campaign + 4 donation + 5 report)
 * rather than Pro's full set, and get_ability_count() below counts them live
 * against `wp_get_abilities()` rather than asserting a fixed number, so this
 * screen cannot drift from what the site actually exposes. The capability
 * cards flagged `requires_pro` in get_cards() are Lite-only additions: they
 * name the areas GATED_ABILITIES (Charitable_Abilities) leaves unregistered
 * on this edition, reading their text LIVE from
 * `Charitable_Diagnostic_Abilities::unavailable_capabilities()` - the same
 * data `charitable/get-plugin-info` returns for the same boundary - rather
 * than keeping a second copy of it. See the comment above that block, below.
 *
 * @package   Charitable/Classes/Charitable_Tools_AI_MCP
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tools_AI_MCP' ) ) :

	/**
	 * Charitable_Tools_AI_MCP
	 *
	 * @since 1.8.13
	 */
	class Charitable_Tools_AI_MCP {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.8.13
		 *
		 * @var Charitable_Tools_AI_MCP|null
		 */
		private static $instance = null;

		/**
		 * The WPVibe plugin's wordpress.org slug.
		 *
		 * NOT "wpvibe" - the plugin is published as `vibe-ai`. Verified against
		 * WPForms Lite 2.0.1.1, which points at the same slug.
		 *
		 * @since 1.8.13
		 *
		 * @var string
		 */
		const BRIDGE_SLUG = 'vibe-ai';

		/**
		 * The WPVibe plugin's basename, for install/active detection.
		 *
		 * @since 1.8.13
		 *
		 * @var string
		 */
		const BRIDGE_BASENAME = 'vibe-ai/vibe-ai.php';

		/**
		 * The admin screen id of WPVibe's own top-level page.
		 *
		 * WPVibe registers its menu with the slug in BRIDGE_SLUG, so WordPress
		 * names the screen `toplevel_page_<slug>`. Derived from BRIDGE_SLUG rather
		 * than written out again so the two cannot drift, and held as a constant
		 * because both the visit tracker and its test compare against it - a typo
		 * in either would fail silently by simply never matching. WPForms watches
		 * the same screen id for the same purpose.
		 *
		 * @since 1.8.13
		 *
		 * @var string
		 */
		const BRIDGE_PAGE_SCREEN_ID = 'toplevel_page_' . self::BRIDGE_SLUG;

		/**
		 * User-meta key: this user has opened WPVibe's own admin page at least once.
		 *
		 * Per-user, not per-site: "have YOU been to set this up yet" is the
		 * question the CTA answers, and two admins on one site can be at different
		 * points. Carries the charitable_ prefix deliberately - unlike the
		 * cross-brand attribution options, this flag is Charitable's alone. Mirrors
		 * WPForms' `wpforms_ai_mcp_visited_wpvibe`; the Lite port reuses this exact
		 * key so a site running both editions never asks twice.
		 *
		 * @since 1.8.13
		 *
		 * @var string
		 */
		const USER_META_VISITED_BRIDGE = 'charitable_ai_mcp_visited_wpvibe';

		/**
		 * The YouTube id of the walkthrough shown in the "Watch video" modal.
		 *
		 * Held as the bare id rather than a URL because three things are built
		 * from it: the watch link the browser follows with JS off, the
		 * youtube-nocookie embed the modal loads, and the JS data attribute.
		 *
		 * @since 1.8.13
		 *
		 * @var string
		 */
		const VIDEO_ID = 'zcRODJj9pAc';

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since 1.8.13
		 *
		 * @return Charitable_Tools_AI_MCP
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new Charitable_Tools_AI_MCP();
			}

			return self::$instance;
		}

		/**
		 * Register the tab's single content field.
		 *
		 * The whole panel is one `content` field rather than a set of settings
		 * fields, because nothing on this screen is a setting - it is a
		 * discovery surface. `content` echoes raw HTML, so everything is escaped
		 * at the point it is built below.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $fields Existing fields.
		 * @return array
		 */
		public function add_fields( $fields = array() ) {

			$fields['ai_mcp_panel'] = array(
				'type'     => 'content',
				'content'  => $this->render(),
				'priority' => 10,
			);

			return $fields;
		}

		/**
		 * Whether the Abilities API is present.
		 *
		 * Charitable registers nothing below WordPress 6.9, so on an older site
		 * this screen has to say so rather than advertise a feature that cannot
		 * work.
		 *
		 * @since 1.8.13
		 *
		 * @return bool
		 */
		public function has_abilities_api() {

			return function_exists( 'wp_register_ability' ) && function_exists( 'wp_get_abilities' );
		}

		/**
		 * Count the Charitable abilities currently registered.
		 *
		 * Counted live rather than hardcoded, so the number on screen cannot
		 * drift from the number the site actually exposes - which is exactly the
		 * bug this screen exists to make visible. On Lite this reads 24 (3
		 * diagnostic + 12 campaign + 4 donation + 5 report); the six abilities in
		 * Charitable_Abilities::GATED_ABILITIES are never registered on this
		 * edition, so they are never counted here either.
		 *
		 * @since 1.8.13
		 *
		 * @return int
		 */
		public function get_ability_count() {

			if ( ! $this->has_abilities_api() ) {
				return 0;
			}

			$count = 0;

			foreach ( (array) wp_get_abilities() as $key => $ability ) {
				$name = is_object( $ability ) && method_exists( $ability, 'get_name' )
					? $ability->get_name()
					: (string) $key;

				if ( 0 === strpos( $name, 'charitable/' ) ) {
					++$count;
				}
			}

			return $count;
		}

		/**
		 * Resolve the bridge plugin's state.
		 *
		 * @since 1.8.13
		 *
		 * @return string One of 'not_installed', 'installed_inactive', 'active'.
		 */
		public function get_bridge_state() {

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = get_plugins();

			if ( ! array_key_exists( self::BRIDGE_BASENAME, $plugins ) ) {
				return 'not_installed';
			}

			if ( ! is_plugin_active( self::BRIDGE_BASENAME ) ) {
				return 'installed_inactive';
			}

			return 'active';
		}

		/**
		 * Whether this user can install a plugin from the dashboard.
		 *
		 * Some hosts set DISALLOW_FILE_MODS, and the primary button has to say so
		 * instead of leading somewhere that will refuse.
		 *
		 * @since 1.8.13
		 *
		 * @return bool
		 */
		public function can_install_plugins() {

			return current_user_can( 'install_plugins' ) && ! ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS );
		}

		/**
		 * Record that the current user has opened WPVibe's admin page.
		 *
		 * Hooked on `current_screen` rather than on a link click, so EVERY route to
		 * the page counts - the sidebar menu WPVibe adds, our own setup button, or
		 * a bookmarked URL - matching how WPForms tracks the same visit. The meta
		 * is a write-once latch: once set, the get_user_meta guard below returns
		 * early, so the cost on every later admin screen is a single lookup.
		 *
		 * Public because it is a hook callback; core hands it the WP_Screen. The
		 * is_object()/isset() guard is not defensive theatre - a plugin can fire
		 * `current_screen` with null on an unusual request, and reading `->id` off
		 * a non-object is a fatal on PHP 8 rather than a notice on 7.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $screen The current screen, as passed by the current_screen action.
		 * @return void
		 */
		public function maybe_mark_bridge_visited( $screen ) {

			if ( ! is_object( $screen ) || ! isset( $screen->id ) || self::BRIDGE_PAGE_SCREEN_ID !== $screen->id ) {
				return;
			}

			$user_id = get_current_user_id();

			if ( ! $user_id || get_user_meta( $user_id, self::USER_META_VISITED_BRIDGE, true ) ) {
				return;
			}

			update_user_meta( $user_id, self::USER_META_VISITED_BRIDGE, '1' );
		}

		/**
		 * Whether the current user has already opened WPVibe's admin page.
		 *
		 * Drives the one CTA copy swap in get_primary_action() below. Returns false
		 * for a logged-out request - get_current_user_id() is 0 there, and reading
		 * meta for user 0 would be a shared, meaningless row.
		 *
		 * @since 1.8.13
		 *
		 * @return bool
		 */
		public function has_visited_bridge() {

			$user_id = get_current_user_id();

			return $user_id && (bool) get_user_meta( $user_id, self::USER_META_VISITED_BRIDGE, true );
		}


		/**
		 * The AI clients shown beside the hero copy.
		 *
		 * The marks are the vendors' own, shipped to indicate compatibility, and
		 * are the same three assets WPForms ships for the same purpose in
		 * assets/images/admin/tools/ai-mcp/. They are third-party trademarks -
		 * worth a Legal nod before release even though there is internal
		 * precedent.
		 *
		 * Each SVG carries its own colours via var(--fill-0, <default>). Because
		 * they render through <img>, page-level custom properties do NOT reach
		 * inside them, so the defaults are what you get - and one of them is
		 * black. That is why the chips are light rather than dark.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public function get_clients() {

			return array(
				array(
					'asset' => 'ai-claude.svg',
					'label' => __( 'Claude', 'charitable' ),
				),
				array(
					'asset' => 'ai-chatgpt.svg',
					'label' => __( 'ChatGPT', 'charitable' ),
				),
				array(
					'asset' => 'ai-cursor.svg',
					'label' => __( 'Cursor', 'charitable' ),
				),
			);
		}

		/**
		 * The capability cards.
		 *
		 * Charitable's abilities fall into five groups (diagnostics, campaign,
		 * donation, donor, report). These are deliberately collapsed to three
		 * "can do" cards: the layout carries three well, and the diagnostics group
		 * is left out because "report my gateway status" is a support tool rather
		 * than a reason to connect an assistant.
		 *
		 * LITE-ONLY ADDITION (fix round 1, Task 10): further cards, flagged
		 * `requires_pro`, name the areas this edition does not register at all -
		 * donor management and donation-form editing
		 * (Charitable_Abilities::GATED_ABILITIES). Their `note` text is read
		 * LIVE from Charitable_Diagnostic_Abilities::unavailable_capabilities()
		 * - the exact array `charitable/get-plugin-info` returns to an
		 * assistant for the same boundary - rather than duplicated here. That
		 * method was widened from private to public specifically so this could
		 * be a single source of truth instead of two copies behind a sync
		 * comment: a comment cannot enforce that two strings stay equal, and a
		 * duplicate very nearly drifted apart during this port. Only `icon` and
		 * `title` (presentation, not carried by the ability's schema) stay
		 * local, keyed on the same `name` the ability uses.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public function get_cards() {

			$cards = array(
				array(
					'slug'    => 'campaigns',
					'icon'    => 'megaphone',
					'title'   => __( 'Campaigns', 'charitable' ),
					'bullets' => array(
						__( 'Build a whole fundraiser from a plain-English brief', 'charitable' ),
						__( 'Add a question to a donation form, or change its colors', 'charitable' ),
						__( 'Publish, unpublish, trash and restore a campaign', 'charitable' ),
					),
				),
				array(
					'slug'    => 'donations',
					'icon'    => 'heart',
					'title'   => __( 'Donations & Donors', 'charitable' ),
					'bullets' => array(
						__( 'Record a gift that arrived by check, cash or transfer', 'charitable' ),
						__( 'Look up a donation, or everything one donor has given', 'charitable' ),
						__( 'Filter donations by campaign, status or date range', 'charitable' ),
					),
				),
				array(
					'slug'    => 'reports',
					'icon'    => 'chart-bar',
					'title'   => __( 'Reports', 'charitable' ),
					'bullets' => array(
						__( 'Ask what you raised over any period, converted to one currency', 'charitable' ),
						__( 'Get a thank-you list of your biggest supporters', 'charitable' ),
						__( 'See which campaigns are performing best, day by day', 'charitable' ),
					),
				),
			);

			/*
			 * Presentation-only metadata for the gated areas, keyed on the same
			 * `name` unavailable_capabilities() uses. An area with no entry here
			 * is skipped rather than guessed at - see the isset() guard below.
			 */
			$gated_presentation = array(
				'donor-management'      => array(
					'icon'  => 'admin-users',
					'title' => __( 'Donor Management', 'charitable' ),
				),
				'donation-form-editing' => array(
					'icon'  => 'edit',
					'title' => __( 'Donation Form Editing', 'charitable' ),
				),
				/*
				 * Added with the matching entries in
				 * unavailable_capabilities(). These two areas have no gated
				 * ability slug - they are analyses Pro reports on and Lite does
				 * not compute - but they still need an entry here, because
				 * Test_Charitable_Abilities_Pro_Gate asserts the screen renders
				 * a card for EVERY named area carrying the same note string.
				 * The screen and the assistant are meant to describe the
				 * boundary identically, so a named area with no card is a
				 * failure rather than a quiet subset.
				 */
				'donor-retention'       => array(
					'icon'  => 'chart-line',
					'title' => __( 'Donor Retention', 'charitable' ),
				),
				'recurring-donations'   => array(
					'icon'  => 'update',
					'title' => __( 'Recurring Donations', 'charitable' ),
				),
			);

			$unavailable = class_exists( 'Charitable_Diagnostic_Abilities' )
				? ( new Charitable_Diagnostic_Abilities() )->unavailable_capabilities()
				: array();

			foreach ( $unavailable as $area ) {
				if ( ! isset( $gated_presentation[ $area['name'] ] ) ) {
					continue;
				}

				$cards[] = array(
					'slug'         => $area['name'],
					'icon'         => $gated_presentation[ $area['name'] ]['icon'],
					'title'        => $gated_presentation[ $area['name'] ]['title'],
					'requires_pro' => true,
					'note'         => $area['note'],
				);
			}

			return $cards;
		}

		/**
		 * The primary button's href, text, state and what clicking it does.
		 *
		 * WPForms' equivalent tab installs and activates its bridge plugin in
		 * place, so this returns an `action` for the tab's JS to act on rather
		 * than only a URL. `install` and `activate` are served over AJAX by
		 * Charitable_Admin_Plugins_Third_Party, which resolves the download from
		 * wordpress.org via plugins_api() and so needs no registry entry for the
		 * slug. `external` is the fallback for a site that forbids dashboard
		 * installs: there is nothing to do in place, so it stays a plain link.
		 *
		 * Both AJAX endpoints check `install_plugins`, so the in-place branches
		 * are gated on the same capability rather than on `activate_plugins` -
		 * otherwise the button would render for someone the endpoint refuses.
		 *
		 * @since 1.8.13
		 *
		 * @return array {
		 *     @type string $url      Where the button points, or '#' when it acts in place.
		 *     @type string $text     Button label.
		 *     @type string $target   Link target.
		 *     @type string $note     Explanatory line beneath, or ''.
		 *     @type string $action   One of install|activate|setup|external.
		 *     @type string $slug     Plugin slug, for the install call.
		 *     @type string $basename Plugin basename, for the activate call.
		 * }
		 */
		public function get_primary_action() {

			$state = $this->get_bridge_state();

			if ( 'active' === $state ) {

				/*
				 * Once the user has actually opened WPVibe's page, "Open WPVibe
				 * Setup" reads as a chore they have already done. Swap it for "Go
				 * to WPVibe" and drop the "to finish" tail of the note, which no
				 * longer describes where they are. Tracked per user by
				 * maybe_mark_bridge_visited(); WPForms makes the same swap for the
				 * same reason.
				 */
				$visited = $this->has_visited_bridge();

				return array(
					'url'      => admin_url( 'admin.php?page=' . self::BRIDGE_SLUG ),
					'text'     => $visited
						? __( 'Go to WPVibe', 'charitable' )
						: __( 'Open WPVibe Setup', 'charitable' ),
					'target'   => '_self',
					'note'     => $visited
						? __( 'WPVibe is active on this site.', 'charitable' )
						: __( 'WPVibe is active on this site. Connect it to your assistant to finish.', 'charitable' ),
					'action'   => 'setup',
					'slug'     => self::BRIDGE_SLUG,
					'basename' => self::BRIDGE_BASENAME,
				);
			}

			if ( 'installed_inactive' === $state ) {

				if ( ! $this->can_install_plugins() ) {
					return array(
						'url'      => admin_url( 'plugins.php' ),
						'text'     => __( 'Activate WPVibe', 'charitable' ),
						'target'   => '_self',
						'note'     => __( 'WPVibe is installed but not active.', 'charitable' ),
						'action'   => 'external',
						'slug'     => self::BRIDGE_SLUG,
						'basename' => self::BRIDGE_BASENAME,
					);
				}

				return array(
					'url'      => '#',
					'text'     => __( 'Activate WPVibe', 'charitable' ),
					'target'   => '_self',
					'note'     => __( 'WPVibe is installed but not active.', 'charitable' ),
					'action'   => 'activate',
					'slug'     => self::BRIDGE_SLUG,
					'basename' => self::BRIDGE_BASENAME,
				);
			}

			if ( ! $this->can_install_plugins() ) {
				return array(
					'url'      => 'https://wordpress.org/plugins/' . self::BRIDGE_SLUG . '/',
					'text'     => __( 'View on WordPress.org', 'charitable' ),
					'target'   => '_blank',
					'note'     => __( 'Your site is configured to disallow plugin installation from the dashboard.', 'charitable' ),
					'action'   => 'external',
					'slug'     => self::BRIDGE_SLUG,
					'basename' => self::BRIDGE_BASENAME,
				);
			}

			return array(
				'url'      => '#',
				'text'     => __( 'Install & Activate WPVibe', 'charitable' ),
				'target'   => '_self',
				'note'     => '',
				'action'   => 'install',
				'slug'     => self::BRIDGE_SLUG,
				'basename' => self::BRIDGE_BASENAME,
			);
		}

		/**
		 * The handful of things that actually stop a client connecting.
		 *
		 * On the tab rather than behind a link to System Info, because that page is
		 * an undifferentiated text dump with no anchor to land on - a link labelled
		 * "diagnose a client that cannot connect" that opens it is a promise the
		 * destination does not keep. System Info remains the right tool for pasting
		 * into a ticket; this is the right thing for answering the question here.
		 *
		 * Every value is measured now, not cached.
		 *
		 * @since 1.8.13
		 *
		 * @return array<int,array{label:string,value:string,ok:bool}>
		 */
		public function get_diagnostics() {

			$rows = array();

			$rows[] = array(
				'label' => __( 'WordPress Abilities API', 'charitable' ),
				'value' => $this->has_abilities_api()
					? __( 'Available', 'charitable' )
					/* translators: %s: the WordPress version running on this site. */
					: sprintf( __( 'Not available - needs WordPress 6.9 or later, this site runs %s', 'charitable' ), get_bloginfo( 'version' ) ),
				'ok'    => $this->has_abilities_api(),
			);

			$count = $this->get_ability_count();

			$rows[] = array(
				'label' => __( 'Charitable operations registered', 'charitable' ),
				/* translators: %d: number of registered Charitable operations. */
				'value' => sprintf( _n( '%d operation', '%d operations', $count, 'charitable' ), $count ),
				'ok'    => $count > 0,
			);

			/*
			 * Pretty permalinks. With plain permalinks /wp-json/ does not resolve and
			 * a client has to use ?rest_route= instead, which most do not - this was
			 * the actual blocker when the testing site was first connected.
			 */
			$pretty = '' !== (string) get_option( 'permalink_structure' );

			$rows[] = array(
				'label' => __( 'Pretty permalinks', 'charitable' ),
				'value' => $pretty
					? __( 'On, so /wp-json/ resolves', 'charitable' )
					: __( 'Off - /wp-json/ will not resolve and most clients cannot connect', 'charitable' ),
				'ok'    => $pretty,
			);

			/*
			 * Application passwords. Every remote MCP client authenticates with one,
			 * and the feature is unavailable over plain HTTP and is filterable.
			 */
			$app_passwords = function_exists( 'wp_is_application_passwords_available' ) && wp_is_application_passwords_available();

			$rows[] = array(
				'label' => __( 'Application passwords', 'charitable' ),
				'value' => $app_passwords
					? __( 'Available', 'charitable' )
					: __( 'Unavailable - they need HTTPS, and a client cannot sign in without one', 'charitable' ),
				'ok'    => $app_passwords,
			);

			if ( $this->has_abilities_api() ) {

				$rows[] = array(
					'label' => __( 'AI write access', 'charitable' ),
					'value' => Charitable_Abilities_Registrar::write_enabled()
						? __( 'On - an assistant can make changes', 'charitable' )
						: __( 'Off - an assistant can read, but changes are refused', 'charitable' ),
					/* Off is a valid, deliberate state, so this is never a failure. */
					'ok'    => true,
				);
			}

			/*
			 * Whether anything has ever actually called an ability, which is the
			 * question this panel could not answer. Every other row here reports
			 * whether the feature COULD work; all of them can pass while no
			 * client has ever connected.
			 *
			 * From the usage counter rather than the log, because reads are not
			 * logged by default and a session with an assistant is mostly reads -
			 * so the log shows an empty integration on a site where it is working.
			 */
			if ( $this->has_abilities_api() && class_exists( 'Charitable_Abilities_Usage' ) ) {
				$usage = Charitable_Abilities_Usage::get();

				$rows[] = array(
					'label' => __( 'Activity', 'charitable' ),
					'value' => $usage['total'] > 0
						? sprintf(
							/* translators: 1: total ability calls, 2: date of the most recent call. */
							__( '%1$d operation(s) run, most recently %2$s', 'charitable' ),
							(int) $usage['total'],
							$this->format_usage_date( $usage['last_at'] )
						)
						: __( 'Nothing has used this yet - connect a client to get started', 'charitable' ),
					/* A site that has not connected anything yet is not broken. */
					'ok'    => true,
				);
			}

			return $rows;
		}

		/**
		 * Render a usage date in the site's own date format.
		 *
		 * `Charitable_Abilities_Usage` stores day granularity only - a bare
		 * `Y-m-d` written with `gmdate()`, deliberately, so the counter cannot be
		 * correlated back to an individual administrator's working pattern. There
		 * is therefore no time of day to show, and appending the site's
		 * `time_format` would print a midnight that never happened.
		 *
		 * The stored value is a calendar date rather than an instant, so it is
		 * formatted IN UTC. Handing the bare string to `date_i18n()` instead would
		 * parse it as UTC midnight and then apply the site's offset, moving the
		 * date back a day on every negative-offset site - the whole of the
		 * Americas would see the day before the one that was recorded.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $date A `Y-m-d` date, or '' when nothing is recorded.
		 * @return string The formatted date, or the input unchanged if unparseable.
		 */
		private function format_usage_date( $date ) {

			$date = (string) $date;

			if ( '' === $date ) {
				return $date;
			}

			$timestamp = strtotime( $date . ' 00:00:00+00:00' );

			if ( false === $timestamp ) {
				return $date;
			}

			return wp_date( (string) get_option( 'date_format' ), $timestamp, new DateTimeZone( 'UTC' ) );
		}

		/*
		 * ---------------------------------------------------------------------
		 * AI Activity panel.
		 *
		 * A view over the `abilities` log type Charitable_Abilities_Registrar
		 * already writes - log_write(), log_write_refused() and log_denial().
		 * NO new storage is added here: every method below reads through
		 * Charitable_Log's own query class, exactly as Tools > Logs does.
		 * ---------------------------------------------------------------------
		 */

		/**
		 * Ability-slug prefix, used to tell a real ability call apart from the
		 * one other thing that logs under the `abilities` type.
		 *
		 * ajax_toggle_write() above logs the write-gate TOGGLE itself under
		 * `type => 'abilities'` with the plain-English title "AI write access
		 * changed" rather than an ability slug, so it does not carry an outcome
		 * of applied/refused/denied and must not appear as a phantom "applied"
		 * row. Every real ability call is logged with the slug as the record's
		 * title (see Charitable_Abilities_Registrar::log_write() and its
		 * siblings), and every Charitable ability slug is namespaced under this
		 * exact prefix (Charitable_Abilities_Registrar::CATEGORY), so filtering
		 * on it keeps the panel's "ability" column an actual ability, always.
		 *
		 * @since 1.8.13
		 *
		 * @var string
		 */
		const ABILITY_LOG_PREFIX = 'charitable/';

		/**
		 * The most recent ability activity: what an AI assistant actually did.
		 *
		 * Reads the log records the abstract registrar already writes. NO new
		 * storage, and NO per-row MCP client column - that is a real constraint,
		 * not a missing feature. log_write()'s context carries type, level,
		 * source, object_type, user_id and object ids, but no client identity,
		 * and there is no reliable way to attribute a single REST call to a
		 * specific MCP plugin. The acting WordPress user IS in the record and is
		 * more meaningful anyway: it says who is accountable, not which client
		 * happened to be in front of them. The site-level client (which IS known)
		 * belongs in the panel header instead - see render_activity_panel().
		 *
		 * Twenty-five rows covers a realistic assistant session without turning
		 * this into a second, paginated Tools > Logs; the full history is
		 * already one click away there.
		 *
		 * @since 1.8.13
		 *
		 * @param  int $limit Maximum rows to return. Default 25.
		 * @return array<int,array{date:string,ability:string,outcome:string,user:string,message:string}>
		 */
		public function get_activity_rows( $limit = 25 ) {

			$limit = (int) $limit;

			if ( $limit < 1 ) {
				$limit = 25;
			}

			if ( ! class_exists( 'Charitable_Log' ) || ! class_exists( 'Charitable_Log_Query' ) ) {
				return array();
			}

			$log = Charitable_Log::get_instance();

			/*
			 * A write from earlier in THIS SAME request can still be sitting in
			 * the in-memory queue - charitable_log() only flushes to the DB on
			 * `shutdown` - so without this, an assistant's own most recent action
			 * would be invisible on the very screen built to show it. This is the
			 * exact seam test-abilities-system-info.php already flushes through
			 * for the same reason; it is not new storage, just reading the
			 * existing queue before the existing table.
			 */
			$log->save_queued_records();

			if ( ! $log->get_db()->table_exists() ) {
				return array();
			}

			/*
			 * One PAGED query per level, not one fixed-size fetch per level.
			 *
			 * Two things share the `abilities` log type and are not outcomes this
			 * panel can show, and both are excluded in SQL by asking for the two
			 * levels explicitly:
			 *
			 * - level `debug`, from Charitable_Abilities_Registrar::log_read(),
			 *   on any site that turns on the documented, supported
			 *   `charitable_abilities_log_reads` filter - whose own docblock
			 *   promises "a row per ability call", and an agentic loop reads an
			 *   order of magnitude more often than it writes.
			 * - level `error`, from log_failure() (a rejected argument or a
			 *   returned WP_Error).
			 *
			 * Neither can crowd a write out of the result set, because neither is
			 * ever IN it.
			 *
			 * One more row cannot be excluded in SQL: ajax_toggle_write() logs
			 * the write-gate toggle itself under this same type at the same
			 * default `info` level, with a plain-English title instead of an
			 * ability slug, so it is dropped by title below - AFTER being
			 * fetched. An earlier version of this code fetched one fixed-size
			 * window per level and argued that row was too rare to fill it. That
			 * argument was wrong: 50 consecutive toggles with no intervening
			 * ability write push an older applied write clean out of a 50-row
			 * window, which is the reads failure again through a different door.
			 * Worse, it is an argument that has to be re-made from scratch every
			 * time anything new logs at `info` under this type, and nothing
			 * fails if it is not.
			 *
			 * So the loop pages until it hits a real condition instead of
			 * trusting a margin. It stops when this level has yielded $limit
			 * SURVIVING candidates - enough that the merge below, which
			 * truncates to $limit overall, cannot need more from this level - or
			 * when a page comes back short of what was asked for, which means
			 * that level's result set is exhausted. The only way a write goes
			 * unlisted now is that no further rows exist at its level to find.
			 *
			 * Each level must be able to supply $limit on its own, since rows
			 * from either level alone could legitimately fill the whole panel;
			 * worst case is 2 * $limit candidates, truncated to $limit.
			 *
			 * The 20-page cap is a runaway guard, NOT a correctness condition: it
			 * bounds the work on a pathological log table, it does not define
			 * when the answer is complete.
			 *
			 * Charitable_Log_Query's `level` is an exact `level = %s` match with
			 * no support for a list or a negation, and its `limit`/`offset` map
			 * straight onto SQL LIMIT/OFFSET (see
			 * includes/logger/class-charitable-log-query.php) - hence one paged
			 * loop per level rather than one query for both.
			 */
			$page_size  = max( $limit * 2, 50 );
			$max_pages  = 20;
			$candidates = array();

			foreach ( array( 'info', 'warning' ) as $level ) {

				$offset = 0;
				$kept   = 0;

				for ( $page = 0; $page < $max_pages; $page++ ) {

					$result = $log->get_query()->get(
						array(
							'type'    => 'abilities',
							'level'   => $level,
							'limit'   => $page_size,
							'offset'  => $offset,
							'orderby' => 'id',
							'order'   => 'DESC',
						)
					);

					$fetched = count( $result['records'] );

					foreach ( $result['records'] as $record ) {

						if ( 0 !== strpos( (string) $record->title, self::ABILITY_LOG_PREFIX ) ) {
							continue; // Not an ability call - e.g. the write-gate toggle's own log entry.
						}

						$candidates[] = $record;
						++$kept;
					}

					if ( $kept >= $limit || $fetched < $page_size ) {
						break;
					}

					$offset += $page_size;
				}
			}

			/*
			 * Two ordered result sets interleave, so re-sort. `id` is the log
			 * table's auto-increment primary key, so it is both unique and in
			 * insert order - a total ordering, which makes usort()'s lack of
			 * stability on PHP 7.4 irrelevant here.
			 */
			usort(
				$candidates,
				static function ( $a, $b ) {
					return (int) $b->id <=> (int) $a->id;
				}
			);

			$accepted = array();
			$user_ids = array();

			foreach ( $candidates as $record ) {

				$outcome = $this->outcome_from_record( $record );

				if ( '' === $outcome ) {
					continue; // A warning from an unrecognised source - not one of applied/refused/denied.
				}

				$accepted[] = array(
					'record'  => $record,
					'outcome' => $outcome,
				);

				$user_id = (int) $record->user_id;

				if ( $user_id > 0 ) {
					$user_ids[ $user_id ] = $user_id;
				}

				if ( count( $accepted ) >= $limit ) {
					break;
				}
			}

			/*
			 * Prime the user cache once for the whole page rather than letting
			 * user_label()'s get_userdata() issue a query per row: up to $limit
			 * uncached queries on first render otherwise.
			 */
			if ( ! empty( $user_ids ) ) {
				cache_users( array_values( $user_ids ) );
			}

			$rows = array();

			foreach ( $accepted as $entry ) {

				$record = $entry['record'];

				$rows[] = array(
					'date'    => $record->get_date( 'full' ),
					'ability' => (string) $record->title,
					'outcome' => $entry['outcome'],
					'user'    => $this->user_label( (int) $record->user_id ),
					'message' => $this->row_message( $record ),
				);
			}

			return $rows;
		}

		/**
		 * Map a log record to applied / refused / denied.
		 *
		 * `log_write()` records at level "info" and only ever runs after a write
		 * has actually happened, so "info" always means applied.
		 *
		 * Both `log_write_refused()` and `log_denial()` record at level
		 * "warning" - the log system has no third level for "blocked" - so
		 * level cannot tell them apart. `source` is the discriminator instead:
		 * `log_write_refused()` writes `source => 'write-gate'` and
		 * `log_denial()` writes `source => 'permission'`.
		 *
		 * `level` could not carry this distinction, because `charitable_log()`
		 * validates it against `Charitable_Log::get_log_levels()` and silently
		 * coerces anything outside that allowlist back to `'info'` - a fourth
		 * level would never survive to be read back. `source` has no such
		 * allowlist; `sanitize_key()` is the only transform applied to it, and
		 * both values above pass through it unchanged.
		 *
		 * An earlier version of this method matched on whether `$record->message`
		 * started with "Refused". That is wrong on any translated site: the
		 * stored message is the OUTPUT of `__()`, so a non-English locale does
		 * not start with the English word "Refused", and WordPress's admin
		 * locale is per-user, so a record written under one locale can be read
		 * under another. Every refusal silently rendered as a denial.
		 *
		 * Everything else - "error" from log_failure(), "debug" from an opted-in
		 * log_read() - is not one of the three outcomes this panel promises and
		 * returns ''. get_activity_rows() no longer FETCHES either of those
		 * levels (it queries `info` and `warning` explicitly, so read logging
		 * cannot crowd writes out of the window), so the '' that matters in
		 * practice is the one below: a warning-level record whose `source` is
		 * neither of the two recognised values. That returns '' rather than
		 * guessing 'denied', which would be exactly the quiet mislabelling this
		 * method exists to avoid.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Log_Record $record The record.
		 * @return string One of 'applied', 'refused', 'denied', or '' if none apply.
		 */
		private function outcome_from_record( $record ) {

			$level = (string) $record->level;

			if ( 'info' === $level ) {
				return 'applied';
			}

			if ( 'warning' !== $level ) {
				return '';
			}

			switch ( (string) $record->source ) {
				case 'write-gate':
					return 'refused';

				case 'permission':
					return 'denied';

				default:
					// Unrecognised warning-level source: '' (dropped by
					// get_activity_rows()), not a guessed 'denied'.
					return '';
			}
		}

		/**
		 * A display name for the acting WordPress user.
		 *
		 * User id 0 is not a hypothetical: a request that reaches an ability
		 * unauthenticated, or from an account since deleted, logs user_id 0, and
		 * get_userdata( 0 ) returns false rather than a user with an empty name.
		 *
		 * @since 1.8.13
		 *
		 * @param  int $user_id The record's user_id column.
		 * @return string
		 */
		private function user_label( $user_id ) {

			$user_id = (int) $user_id;

			if ( $user_id < 1 ) {
				return __( 'Unknown', 'charitable' );
			}

			$user = get_userdata( $user_id );

			return $user ? $user->display_name : __( 'Unknown', 'charitable' );
		}

		/**
		 * The row's human-readable message, with the affected object named when known.
		 *
		 * log_write()'s free-text message rarely names WHICH campaign or
		 * donation changed ("Campaign trashed via ability.") - the id lives in
		 * the record's own campaign_id/donation_id/donor_id columns instead,
		 * alongside the message rather than inside it. This stitches the two
		 * back together so the row actually says what changed. A refusal or
		 * denial carries no such context - the gate refuses before anything
		 * about the target is known - so those render with no object suffix,
		 * which is correct: "where known" excludes them on purpose.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Log_Record $record The record.
		 * @return string
		 */
		private function row_message( $record ) {

			$message = (string) $record->message;
			$object  = $this->object_label( $record );

			if ( '' === $object ) {
				return $message;
			}

			/* translators: 1: the log message, 2: the affected object, e.g. "Campaign #12". */
			return sprintf( __( '%1$s (%2$s)', 'charitable' ), $message, $object );
		}

		/**
		 * Name the affected object from the record's own correlation columns.
		 *
		 * Priority is most-specific first. create-manual-donation's context
		 * carries donation_id, campaign_id AND donor_id together, and the
		 * object actually created is the donation, not the campaign it landed
		 * on or the donor it came from - so donation_id is checked first.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Log_Record $record The record.
		 * @return string The label, or '' when nothing is known.
		 */
		private function object_label( $record ) {

			$donation_id = isset( $record->donation_id ) ? (int) $record->donation_id : 0;

			if ( $donation_id > 0 ) {
				/* translators: %d: donation ID. */
				return sprintf( __( 'Donation #%d', 'charitable' ), $donation_id );
			}

			$campaign_id = isset( $record->campaign_id ) ? (int) $record->campaign_id : 0;

			if ( $campaign_id > 0 ) {
				/* translators: %d: campaign ID. */
				return sprintf( __( 'Campaign #%d', 'charitable' ), $campaign_id );
			}

			$donor_id = isset( $record->donor_id ) ? (int) $record->donor_id : 0;

			if ( $donor_id > 0 ) {
				/* translators: %d: donor ID. */
				return sprintf( __( 'Donor #%d', 'charitable' ), $donor_id );
			}

			return '';
		}

		/**
		 * Render the AI Activity panel.
		 *
		 * A separate view file (includes/admin/tools/views/ai-mcp-activity.php)
		 * rather than more inline HTML in render() below, so the panel's markup
		 * can be read, and dumped for review, apart from the much larger method
		 * that builds the rest of this screen.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		public function render_activity_panel() {

			$rows                 = $this->get_activity_rows();
			$observation_complete = true;

			/*
			 * Denial logging depends on `wp_ability_invoked`, core @since 7.1.0.
			 * Below that, writes and refusals still log - they are recorded from
			 * inside the callbacks that run them - but a denial never fires at
			 * all, and this floor is the only way to tell "nothing was denied"
			 * apart from "this core cannot see denials". Charitable_Abilities_Usage
			 * already computes it for exactly this reason (get_counts()).
			 */
			if ( class_exists( 'Charitable_Abilities_Usage' ) ) {
				$usage                = Charitable_Abilities_Usage::get_counts();
				$observation_complete = ! empty( $usage['observation_complete'] );
			}

			/*
			 * The site-level client, not a per-row one - see ABILITY_LOG_PREFIX's
			 * docblock and get_activity_rows() above for why a per-row client is
			 * not something this data can honestly show.
			 */
			$clients = class_exists( 'Charitable_Abilities' ) ? Charitable_Abilities::get_active_mcp_clients() : array();

			$logs_url = admin_url( 'admin.php?page=charitable-tools&tab=logs&log_type=abilities' );

			$view = charitable()->get_path( 'admin' ) . 'tools/views/ai-mcp-activity.php';

			if ( ! is_readable( $view ) ) {
				return '';
			}

			ob_start();
			include $view;
			return ob_get_clean();
		}

		/**
		 * The settings key the write gate reads.
		 *
		 * Kept here beside the UI that writes it, while
		 * Charitable_Abilities_Registrar::write_enabled() is what READS it - the
		 * gate must not depend on an admin-only class being loaded.
		 *
		 * @since 1.8.13
		 *
		 * @var string
		 */
		const WRITE_SETTING_KEY = 'ai_mcp_write_enabled';

		/**
		 * Turn AI write access on or off, and verify it actually stored.
		 *
		 * Returns whether the setting now reads what was asked for, RE-READ from
		 * the option rather than taken from update_option()'s return value -
		 * which is false both when the write failed and when the value was
		 * already what we asked for.
		 *
		 * Verifying matters because charitable_settings is a shared row that
		 * WP-CLI, another plugin or a filter can rewrite underneath this, and
		 * because charitable-ai-mcp.js reverts the checkbox on a failure: a
		 * success reported for a write that did not land shows the reader OFF
		 * while an assistant can still make changes, and a failure reported for
		 * one that DID land shows the reverse. Both were real defects on this
		 * screen while it had three toggles and a five-way status enum.
		 *
		 * 1.8.13 briefly had set_write_scope() here plus scope_persisted(),
		 * write_scope_response(), reassert_legacy_boolean() and three SCOPE_SAVE_*
		 * statuses, because write access was split three ways and a legacy boolean
		 * had to be kept in AND agreement with the scope map. One switch needs
		 * none of that: one value, it is the value the gate reads, and it either
		 * stored or it did not.
		 *
		 * @since 1.8.13
		 *
		 * @param  bool $enabled Whether AI write access should be on.
		 * @return bool Whether the stored setting now matches $enabled.
		 */
		public function set_write_enabled( $enabled ) {

			$settings = get_option( 'charitable_settings' );
			$settings = is_array( $settings ) ? $settings : array();

			$settings[ self::WRITE_SETTING_KEY ] = $enabled ? 1 : 0;

			update_option( 'charitable_settings', $settings );

			/*
			 * The RAW option, not charitable_get_option(): the generic
			 * `charitable_option_ai_mcp_write_enabled` filter could make a value
			 * that never stored look present and correct, and a verification that
			 * consults a filter is not a verification.
			 */
			$stored = get_option( 'charitable_settings' );
			$stored = is_array( $stored ) ? $stored : array();

			return ! empty( $stored[ self::WRITE_SETTING_KEY ] ) === (bool) $enabled;
		}

		/**
		 * Flip the AI write gate over AJAX.
		 *
		 * ⚠️ The error path must have stored NOTHING. charitable-ai-mcp.js reverts
		 * the checkbox on a failure, so an error returned after the setting was
		 * written shows the reader OFF while an assistant can still make changes.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public function ajax_toggle_write() {

			check_ajax_referer( 'charitable_ai_mcp_toggle', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to change this.', 'charitable' ) ) );
			}

			$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_key( wp_unslash( $_POST['enabled'] ) );

			if ( ! $this->set_write_enabled( $enabled ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Nothing has been changed for an AI assistant. That setting could not be saved - something else on this site may be writing Charitable settings. Please try again.', 'charitable' ),
					)
				);
			}

			/*
			 * charitable_log()'s first argument is the TITLE, and
			 * get_activity_rows() matches this exact string to exclude the
			 * toggle's own entry from the AI Activity panel's ability rows - so it
			 * must not change.
			 */
			if ( function_exists( 'charitable_log' ) ) {
				charitable_log(
					__( 'AI write access changed', 'charitable' ),
					/* Not translated: a log body read by us, not UI. */
					$enabled
						? 'AI write access was turned ON from Tools > AI MCP.'
						: 'AI write access was turned OFF from Tools > AI MCP.',
					array(
						'type'    => 'abilities',
						'user_id' => get_current_user_id(),
					)
				);
			}

			wp_send_json_success(
				array(
					'message' => $enabled
						? __( 'An AI assistant can now make changes on this site.', 'charitable' )
						: __( 'An AI assistant can no longer make changes on this site.', 'charitable' ),
				)
			);
		}

		/**
		 * Build the panel markup.
		 *
		 * @since 1.8.13
		 *
		 * @return string
		 */
		public function render() {

			$action = $this->get_primary_action();

			/*
			 * One master switch, read through the same method the gate uses, so
			 * the toggle and the banner below can never disagree with what an
			 * assistant is actually allowed to do.
			 */
			$write_enabled = Charitable_Abilities_Registrar::write_enabled();

			/*
			 * Per David 2026-09-16: the hub page rather than the deep reference.
			 * /ai is a live redirect to /ai-info/ (verified 200), unlike the
			 * ai-assistants-learn-more page this used to point at, which is still
			 * an unpublished draft and would have 404'd for every customer.
			 */
			$docs_url  = 'https://www.wpcharitable.com/ai/';
			$video_url = 'https://youtu.be/' . self::VIDEO_ID;

			if ( function_exists( 'charitable_utm_link' ) ) {
				$docs_url = charitable_utm_link( $docs_url, 'tools-ai-assistants', 'Charitable + AI Documentation' );
			}

			ob_start();
			?>
			<div class="charitable-ai-mcp">

				<?php if ( ! $this->has_abilities_api() ) : ?>
					<div class="charitable-ai-notice">
						<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
						<p>
							<?php
							printf(
								/* translators: %s: the WordPress version currently running. */
								esc_html__( 'This feature needs the WordPress Abilities API, which arrived in WordPress 6.9. This site is running %s, so nothing is registered and nothing about your site has changed. Update WordPress to use Charitable with an AI assistant.', 'charitable' ),
								esc_html( get_bloginfo( 'version' ) )
							);
							?>
						</p>
					</div>
				<?php endif; ?>

				<section class="charitable-ai-hero">
					<div class="charitable-ai-hero-body">
						<p class="charitable-ai-eyebrow"><?php esc_html_e( 'WordPress Abilities API + Charitable', 'charitable' ); ?></p>
						<h2 class="charitable-ai-title"><?php esc_html_e( 'Use Charitable With Your Favorite AI', 'charitable' ); ?></h2>
						<p class="charitable-ai-desc">
							<?php
							printf(
								/* translators: %s: linked "WPVibe.ai" text. */
								esc_html__( 'Connect your site to an assistant like Claude or ChatGPT, then just say what you need. Launch a fundraiser, add a question to a donation form, log a check that came in the post, or ask who your biggest supporters are. No copy-pasting, no exports. Connect them with the free %s plugin.', 'charitable' ),
								'<a href="https://wpvibe.ai/" target="_blank" rel="noopener noreferrer"><strong>WPVibe.ai</strong></a>'
							);
							?>
						</p>

						<div class="charitable-ai-actions">
							<a
								class="charitable-ai-button"
								href="<?php echo esc_url( $action['url'] ); ?>"
								data-action="<?php echo esc_attr( $action['action'] ); ?>"
								data-slug="<?php echo esc_attr( $action['slug'] ); ?>"
								data-basename="<?php echo esc_attr( $action['basename'] ); ?>"
								<?php if ( '_blank' === $action['target'] ) : ?>
									target="_blank" rel="noopener noreferrer"
								<?php endif; ?>
							>
								<?php echo esc_html( $action['text'] ); ?>
								<span class="charitable-ai-button-arrow" aria-hidden="true">&rarr;</span>
							</a>

							<?php
							/*
							 * ONE toggle, sitting INLINE with the install button,
							 * exactly as Charitable Pro renders it.
							 *
							 * The `.charitable-ai-gates` wrapper this used to sit in
							 * is gone, and that wrapper was the only reason the
							 * toggle dropped to its own line. It carried
							 * `width: 100%`, which made it a full-width flex item
							 * inside `.charitable-ai-actions` and so forced a wrap.
							 * That was correct when there were THREE toggles - the
							 * stylesheet note recorded that inline, the first label
							 * rode alongside the button while the other two wrapped
							 * to a second misaligned line - but with one toggle it
							 * just separated a single control from the button it
							 * belongs beside. Pro has no such wrapper and never did.
							 *
							 * `.charitable-ai-actions` is byte-identical in both
							 * editions (display:flex, align-items:center, gap:16px,
							 * flex-wrap:wrap), so removing the wrapper is all that
							 * was needed to match Pro's layout - no new CSS.
							 *
							 * The `charitable-ai-mcp-write` CLASS stays on the input.
							 * Pro gates on the id alone, but Lite's JS delegates from
							 * document on `.charitable-ai-mcp-write`, so dropping it
							 * to match Pro exactly would silently unbind the toggle.
							 */
							?>
							<?php if ( $this->has_abilities_api() && current_user_can( 'manage_charitable_settings' ) ) : ?>
								<div class="charitable-ai-gate">
									<label class="charitable-toggle">
										<input
											type="checkbox"
											class="charitable-ai-mcp-write"
											id="charitable-ai-mcp-write"
											value="1"
											<?php checked( $write_enabled ); ?>
										/>
										<span class="charitable-toggle-slider"></span>
									</label>
									<label class="charitable-ai-gate-label" for="charitable-ai-mcp-write">
										<?php esc_html_e( 'Enable AI write access', 'charitable' ); ?>
									</label>
									<span class="charitable-ai-gate-spinner" aria-hidden="true"></span>
								</div>
							<?php endif; ?>
						</div>

						<?php if ( '' !== $action['note'] ) : ?>
							<p class="charitable-ai-note"><?php echo esc_html( $action['note'] ); ?></p>
						<?php endif; ?>
					</div>

					<aside class="charitable-ai-clients" aria-label="<?php esc_attr_e( 'Supported AI clients', 'charitable' ); ?>">
						<?php foreach ( $this->get_clients() as $client ) : ?>
							<div class="charitable-ai-client">
								<span class="charitable-ai-client-icon">
									<img
										src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/admin/tools/ai-mcp/' . $client['asset'] ); ?>"
										alt=""
										role="presentation"
										width="16"
										height="16"
									>
								</span>
								<span class="charitable-ai-client-label"><?php echo esc_html( $client['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
						<p class="charitable-ai-client-any"><?php esc_html_e( '+ Any MCP Client', 'charitable' ); ?></p>
					</aside>
				</section>

				<section class="charitable-ai-panel">
					<?php if ( $this->has_abilities_api() && ! $write_enabled ) : ?>
						<p class="charitable-ai-readonly-banner">
							<span class="dashicons dashicons-lock" aria-hidden="true"></span>
							<?php esc_html_e( 'Write access is off. An assistant can read everything below, but cannot change anything.', 'charitable' ); ?>
						</p>
					<?php endif; ?>

					<header class="charitable-ai-panel-head">
						<h2 class="charitable-ai-panel-title">
							<?php
							/*
							 * The brand word is a placeholder rather than part of the
							 * sentence so it can be painted brand orange without
							 * handing translators a span to preserve. esc_html() on
							 * the format string leaves %1$s intact, and $brand is our
							 * own markup with its text already escaped.
							 */
							$brand = '<span class="charitable-ai-brand">' . esc_html__( 'Charitable', 'charitable' ) . '</span>';

							/*
							 * NO OPERATION COUNT in this heading, deliberately.
							 *
							 * It used to read "(24 operations)" from a _n() pair. The
							 * number is still reported where it is diagnostically
							 * useful - the "Charitable abilities" row under *If a
							 * client cannot connect* - and that is the copy people
							 * paste into a support ticket. In the heading it was
							 * decoration: it told a fundraiser nothing they could act
							 * on, and it dated the sentence, because the count moves
							 * whenever an ability is added.
							 *
							 * Removing it also removed the only caller of
							 * get_ability_count() in render(), which walked the whole
							 * registry on every page load of this tab.
							 *
							 * DIVERGENCE FROM PRO, and intentional: Pro 1.8.19 still
							 * prints the count here from the same _n() pair. Per David.
							 */
							printf(
								/* translators: %s: the product name, styled. */
								esc_html__( 'Everything %s Can Do With AI', 'charitable' ),
								$brand // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above with esc_html__().
							);
							?>
						</h2>
						<p class="charitable-ai-panel-links">
							<?php
							/*
							 * A real YouTube link, not a bare button: with JS off, or
							 * before charitable-ai-mcp.js runs, the click still
							 * reaches the video. The handler intercepts it and opens
							 * the same id in a modal instead.
							 */
							?>
							<a class="charitable-ai-video-link" href="<?php echo esc_url( $video_url ); ?>" data-video-id="<?php echo esc_attr( self::VIDEO_ID ); ?>" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-video-alt3" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Watch video', 'charitable' ); ?></span>
							</a>
							<span class="charitable-ai-panel-links-sep" aria-hidden="true">&middot;</span>
							<a class="charitable-ai-docs-link" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer">
								<span><?php esc_html_e( 'View Charitable + AI Documentation', 'charitable' ); ?></span>
								<span class="charitable-ai-docs-arrow" aria-hidden="true">&rarr;</span>
							</a>
						</p>
					</header>

					<div class="charitable-ai-cards">
						<?php foreach ( $this->get_cards() as $card ) : ?>
							<article class="charitable-ai-card charitable-ai-card-<?php echo esc_attr( $card['slug'] ); ?>">
								<header class="charitable-ai-card-head">
									<span class="charitable-ai-card-icon dashicons dashicons-<?php echo esc_attr( $card['icon'] ); ?>" aria-hidden="true"></span>
									<h3 class="charitable-ai-card-title">
										<?php echo esc_html( $card['title'] ); ?>
										<?php if ( ! empty( $card['requires_pro'] ) ) : ?>
											<span class="charitable-pro-badge"><?php esc_html_e( 'Pro', 'charitable' ); ?></span>
										<?php endif; ?>
									</h3>
								</header>
								<?php if ( ! empty( $card['requires_pro'] ) ) : ?>
									<p class="charitable-ai-card-note"><?php echo esc_html( $card['note'] ); ?></p>
									<p class="charitable-ai-card-upgrade">
										<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-upgrade/', 'tools-ai-mcp-' . $card['slug'], __( 'Upgrade to Charitable Pro', 'charitable' ) ) ); ?>" target="_blank" rel="noopener noreferrer">
											<?php esc_html_e( 'Upgrade to Charitable Pro', 'charitable' ); ?>
											<span aria-hidden="true">&rarr;</span>
										</a>
									</p>
								<?php else : ?>
									<ul class="charitable-ai-card-list">
										<?php foreach ( $card['bullets'] as $bullet ) : ?>
											<li><?php echo esc_html( $bullet ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>

					<?php if ( $this->has_abilities_api() ) : ?>
						<?php
						/*
						 * Below the capability cards, and gated on the same check the
						 * WP<6.9 notice above uses: there can be no ability activity
						 * without the Abilities API, so this panel simply does not
						 * appear in that state rather than advertising empty data.
						 * render_activity_panel() escapes everything itself.
						 */
						echo $this->render_activity_panel(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the included view.
						?>
					<?php endif; ?>

					<footer class="charitable-ai-panel-foot">
						<p>
							<?php
							esc_html_e( 'Nothing is exposed to anyone who could not already do it in this dashboard. Every operation checks a Charitable permission before it runs, donor contact details need the sensitive-data permission, and your payment gateway keys are never readable.', 'charitable' );
							?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=logs&log_type=abilities' ) ); ?>">
								<?php esc_html_e( 'View logs to see what an assistant changed.', 'charitable' ); ?>
							</a>
						</p>

						<details class="charitable-ai-diag-wrap" id="charitable-ai-diagnostics">
							<summary class="charitable-ai-diag-summary"><?php esc_html_e( 'If a client cannot connect', 'charitable' ); ?></summary>

						<div class="charitable-ai-diag-box">
						<ul class="charitable-ai-diag">
							<?php foreach ( $this->get_diagnostics() as $row ) : ?>
								<li class="charitable-ai-diag-row <?php echo $row['ok'] ? 'is-ok' : 'is-problem'; ?>">
									<span class="charitable-ai-diag-mark dashicons dashicons-<?php echo $row['ok'] ? 'yes-alt' : 'warning'; ?>" aria-hidden="true"></span>
									<span class="charitable-ai-diag-label"><?php echo esc_html( $row['label'] ); ?></span>
									<span class="charitable-ai-diag-value"><?php echo esc_html( $row['value'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>

						<p class="charitable-ai-diag-more">
							<?php
							printf(
								/* translators: %s: link to the System Info tab, text "System Info". */
								esc_html__( 'Everything above, plus the REST route, the registered operation names and how many are visible to a client, is in %s.', 'charitable' ),
								'<a href="' . esc_url( admin_url( 'admin.php?page=charitable-tools&tab=system-info' ) ) . '">' . esc_html__( 'System Info', 'charitable' ) . '</a>'
							);
							?>
						</p>
						</div>
						</details>
					</footer>
				</section>

			</div>
			<?php
			return ob_get_clean();
		}
	}

endif;
