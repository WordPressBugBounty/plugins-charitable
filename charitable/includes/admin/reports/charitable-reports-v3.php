<?php
/**
 * The gate, the body class and the stylesheet for the reworked Reports Overview.
 *
 * WHY THIS FILE EXISTS SEPARATELY FROM PRO'S.
 *
 * Pro ships the same screen gated on charitable_dashboard_v3_enabled(), because there the
 * Dashboard was reworked in the same release and the two share one flag. Lite has no reworked
 * Dashboard, so reusing that name here would imply a Dashboard change that does not exist and
 * would couple Lite's Reports page to a file Lite does not have.
 *
 * The gate is therefore Lite's own, with its own option and its own filter, and it is checked
 * only on the Reports screen. See charitable_reports_v3_is_reports_screen() for why that
 * narrowing matters rather than being tidiness.
 *
 * @package   Charitable/Functions/Reports
 * @author    David Bisset
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.14
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The option that can switch the reworked Reports Overview back off.
 *
 * @since 1.8.14
 */
if ( ! defined( 'CHARITABLE_REPORTS_V3_OPTION' ) ) {
	define( 'CHARITABLE_REPORTS_V3_OPTION', 'charitable_reports_v3' );
}

/**
 * Whether the reworked Reports Overview is in use.
 *
 * @since 1.8.14
 *
 * @return bool
 */
function charitable_reports_v3_enabled() {

	// Never let an admin layout gate leak into a front-end or AJAX render.
	if ( ! is_admin() ) {
		return false;
	}

	/*
	Default ON, and the test is "not explicitly disabled" rather than "explicitly enabled".

	That inversion is the whole mechanism. An upgrade and a fresh install both arrive with no
	option row at all, so defaulting the get_option() to '1' gives them the reworked screen
	without a migration step and without an upgrade routine that could half-run. Only a site
	that has deliberately written '0' sees the old screen.
	*/
	$enabled = '0' !== (string) get_option( CHARITABLE_REPORTS_V3_OPTION, '1' );

	/**
	 * Filter whether the reworked Reports Overview screen is used.
	 *
	 * The supported way to put a site back on the old screen, and the one to reach for in
	 * support: it needs no database write and no URL, so it can go in a snippet and come out
	 * again.
	 *
	 * @since 1.8.14
	 *
	 * @param bool $enabled Whether the reworked screen is used.
	 */
	return (bool) apply_filters( 'charitable_reports_v3_enabled', $enabled );
}

/**
 * Whether the current screen is the Reports page.
 *
 * NARROWER THAN PRO'S EQUIVALENT, deliberately.
 *
 * charitable-admin-reports-v3.css scopes 290 of its 308 rules to `body.charitable-dash-v3`, and
 * a number of those hang off `.charitable-cards .charitable-card` and `.charitable-report-card` -
 * class names Lite's DASHBOARD also uses. Pro can afford to put the body class on every
 * Charitable screen because its Dashboard was reworked to match in the same release. Lite's
 * Dashboard is untouched, so the same breadth would silently restyle it.
 *
 * So the gate is the Reports screen and nothing else. If the Dashboard is ever reworked to
 * match, this is the one function that widens.
 *
 * @since 1.8.14
 *
 * @return bool
 */
function charitable_reports_v3_is_reports_screen() {

	if ( ! function_exists( 'get_current_screen' ) ) {
		return false;
	}

	$screen = get_current_screen();

	if ( is_null( $screen ) ) {
		return false;
	}

	return 'charitable_page_charitable-reports' === $screen->base
		|| 'charitable_page_charitable-reports' === $screen->id;
}

/**
 * Add the body class every v3 style rule hangs off.
 *
 * A body class rather than a wrapper on the report div, because that is what the ported
 * stylesheet already expects and because it gives every v3 rule enough specificity to beat its
 * shipping counterpart without a single !important.
 *
 * @since 1.8.14
 *
 * @param  string $classes Existing admin body classes.
 * @return string
 */
function charitable_reports_v3_body_class( $classes ) {

	if ( ! charitable_reports_v3_enabled() || ! charitable_reports_v3_is_reports_screen() ) {
		return $classes;
	}

	return trim( $classes . ' charitable-dash-v3' );
}
add_filter( 'admin_body_class', 'charitable_reports_v3_body_class' );

/**
 * The local artwork for an addon, or '' when Lite does not ship it.
 *
 * Reads the same files the Addons screen does - assets/images/addons/addon-icon-{name}.svg, then
 * .png - so the rail and that screen cannot show different art for the same addon. Local files
 * only: no remote fetch, which matters on a Lite screen that must make no outbound request.
 *
 * RETURNS '' RATHER THAN A PLACEHOLDER, which is the one deliberate difference from the Addons
 * screen's resolver. That one falls back to addon-icon-stripe.png as a last resort, so an addon
 * with no local art renders as the Stripe logo - actively wrong rather than merely plain. Here an
 * empty return lets the caller draw a lettered tile, which cannot misidentify anything.
 *
 * THE ALIASES ARE NOT TIDINESS. An addon's plugin folder and its icon filename disagree in three
 * known cases, because the icon is named for the product and the folder for the plugin. Stripping
 * the prefix alone resolves 'charitable-recurring' to 'recurring', and the file is
 * addon-icon-recurring-donations.png - so the best-known addon in the catalogue would silently
 * fall back to a letter. Verified against the icons Lite actually ships.
 *
 * @since 1.8.14
 *
 * @param  string $slug Addon plugin folder name, e.g. 'charitable-recurring'.
 * @return string Icon URL, or '' when there is no local file.
 */
function charitable_reports_v3_addon_icon( $slug ) {

	/*
	is_scalar() before the cast. `(string) $slug` on an array raises "Array to string
	conversion" and yields the literal "Array", which sanitize_key() then happily turns into a
	lookup for addon-icon-array.png - a warning in the log and a silent wrong answer. Caught by
	fuzzing this function with an array argument.

	No caller passes an array today. The guard is here because this is a public function in a
	loaded-everywhere file, and "no caller does that yet" is not a property a function can rely
	on.
	*/
	if ( ! is_scalar( $slug ) ) {
		return '';
	}

	$name = str_replace( array( 'charitable-', '-banner' ), '', sanitize_key( (string) $slug ) );

	if ( '' === $name ) {
		return '';
	}

	$aliases = array(
		'recurring' => 'recurring-donations',
		'anonymous' => 'anonymous-donations',
		'edd'       => 'easy-digital-downloads-connect',
	);

	if ( isset( $aliases[ $name ] ) ) {
		$name = $aliases[ $name ];
	}

	$dir = charitable()->get_path( 'assets', true ) . 'images/addons/';
	$url = charitable()->get_path( 'assets', false ) . 'images/addons/';

	// SVG first, so a crisper asset wins wherever one has shipped.
	foreach ( array( 'svg', 'png' ) as $extension ) {
		if ( file_exists( $dir . 'addon-icon-' . $name . '.' . $extension ) ) {
			return $url . 'addon-icon-' . $name . '.' . $extension;
		}
	}

	return '';
}

/**
 * The call to action for a locked report card, chosen by what is actually missing.
 *
 * WHY THIS IS ONE FUNCTION AND NOT COPY IN TWO VIEWS.
 *
 * Both the Recurring Revenue and Donation Recovery cards can be live on Lite - their addons gate
 * on Charitable core and on charitable_is_pro() respectively, and charitable_is_pro() is
 * LICENCE-derived, not edition-derived, so a Lite install carrying a valid licence runs both. The
 * cards are therefore only locked for one of three distinct reasons, and each wants different
 * words. Two copies of that branching would drift the first time one was edited.
 *
 * THE FOUR OUTCOMES, in the order they are tested:
 *
 * - LICENCE VALID, ADDON ON DISK: the addon is installed but not switched on, which is why the
 *   capability class the card gates on is missing. This is the common case on a site that bought a
 *   bundle, and the right CTA is a click, not a purchase. Telling somebody to buy what they
 *   already own is the worst version of an upsell.
 * - LICENCE VALID, ADDON ABSENT: they are entitled to it and simply have not installed it, so the
 *   CTA goes to their own Addons screen rather than to a sales page.
 * - LICENCE PRESENT BUT NOT VALID: expired, or no longer validating. Renewal, not purchase. The
 *   wording covers both because the stored row cannot always tell them apart, and "expired or no
 *   longer valid" is true in either case.
 * - NO LICENCE AT ALL: the genuine upsell.
 *
 * Note what is NOT tested here: whether the addon is active. By the time a card renders its locked
 * state, the capability class it needs is already absent - so an active addon is impossible by
 * construction, and asking is_plugin_active() would add a dependency on an admin-only include to
 * answer a question that is already settled.
 *
 * @since 1.8.14
 *
 * @param  array $args {
 *     @type string $slug        Addon plugin folder/file base, e.g. 'charitable-recurring'.
 *     @type string $name        Addon display name, for the CTA label.
 *     @type string $product_url The addon's page on wpcharitable.com, for Learn More.
 *     @type string $utm         utm_content tag for this card.
 * }
 * @return array {
 *     @type string $state    One of 'addon_inactive', 'addon_missing', 'expired', 'none'.
 *     @type string $label    The primary button's text.
 *     @type string $url      The primary button's href.
 *     @type bool   $external Whether the primary button leaves the admin.
 *     @type string $note     A sentence naming why the card is locked, or '' for the plain upsell.
 * }
 */
function charitable_reports_v3_locked_cta( $args = array() ) {

	$args = wp_parse_args(
		$args,
		array(
			'slug'        => '',
			'name'        => '',
			'product_url' => 'https://www.wpcharitable.com/extensions/',
			'utm'         => 'reports-overview',
		)
	);

	$reports      = Charitable_Reports::get_instance();
	$on_disk      = method_exists( $reports, 'addon_is_installed' ) && $reports->addon_is_installed( $args['slug'] );
	$licence_ok   = function_exists( 'charitable_is_pro' ) && charitable_is_pro();
	$has_licence  = false;

	/*
	Read straight off the stored settings rather than through has_valid_license() or
	get_expired_license_package(), both of which take a per-PRODUCT argument and so cannot answer
	"is there a licence on this site at all". The shape here is the documented one: a
	'charitable-v2' row with 'valid' and 'expiration_date' keys.

	charitable_get_license_type() is deliberately NOT used - it calls charitable()->is_pro(), which
	does not exist as a method, so it fatals on any site that has a licence type stored.
	*/
	$charitable_settings = get_option( 'charitable_settings' );

	if ( is_array( $charitable_settings ) && ! empty( $charitable_settings['licenses'] ) ) {
		$has_licence = ! empty( array_filter( (array) $charitable_settings['licenses'] ) );
	}

	if ( $licence_ok && $on_disk ) {
		return array(
			'state'    => 'addon_inactive',
			/* translators: %s: the addon's name, e.g. "Recurring Donations". */
			'label'    => sprintf( __( 'Activate %s', 'charitable' ), $args['name'] ),
			'url'      => admin_url( 'plugins.php?s=' . rawurlencode( $args['name'] ) ),
			'external' => false,
			'note'     => __( 'Your licence covers this. The addon is installed but not active.', 'charitable' ),
		);
	}

	if ( $licence_ok ) {
		return array(
			'state'    => 'addon_missing',
			'label'    => __( 'View Your Addons', 'charitable' ),
			'url'      => admin_url( 'admin.php?page=charitable-addons' ),
			'external' => false,
			'note'     => __( 'Your licence covers this. Install the addon to switch it on.', 'charitable' ),
		);
	}

	/*
	Both URL builders are guarded. charitable_ga_url() lives in
	admin/charitable-core-admin-functions.php, which Charitable_Admin::load_dependencies() requires
	BEFORE this file - so in the admin request this helper is actually called from, it is always
	there. The guard is for every other caller: without it, invoking this function outside the
	admin bootstrap is an uncaught fatal rather than a slightly less tagged link, which is a poor
	trade for a URL that only differs by its utm parameters.
	*/
	if ( $has_licence ) {
		$charitable_renew_url = 'https://wpcharitable.com/account/licenses/';

		return array(
			'state'    => 'expired',
			'label'    => __( 'Renew Your License', 'charitable' ),
			'url'      => function_exists( 'charitable_ga_url' )
				? charitable_ga_url( $charitable_renew_url, $args['utm'], $args['name'] . ' renew' )
				: $charitable_renew_url,
			'external' => true,
			'note'     => __( 'Your license has expired or is no longer valid, so this report is paused.', 'charitable' ),
		);
	}

	return array(
		'state'    => 'none',
		'label'    => __( 'Upgrade to Charitable Pro', 'charitable' ),
		'url'      => function_exists( 'charitable_admin_upgrade_link' )
			? charitable_admin_upgrade_link( $args['utm'], $args['name'] )
			: 'https://wpcharitable.com/lite-vs-pro/',
		'external' => true,
		'note'     => '',
	);
}

/**
 * Enqueue the reworked Reports stylesheet.
 *
 * Priority 20 so it lands after the shipping dashboard stylesheet, whose base card rules it
 * builds on.
 *
 * $min follows the plugin-wide convention: charitable_get_min_suffix() returns '' under
 * SCRIPT_DEBUG, CHARITABLE_DEBUG, or with script minification switched off in settings, and
 * '.min' otherwise. Both files have a committed sibling - REGENERATE THE .min WHEN EDITING THE
 * SOURCE, because a stale .min is invisible in debug mode and only shows up in production.
 *
 * @since 1.8.14
 *
 * @return void
 */
function charitable_reports_v3_enqueue() {

	if ( ! charitable_reports_v3_enabled() || ! charitable_reports_v3_is_reports_screen() ) {
		return;
	}

	$min = charitable_get_min_suffix();

	/*
	charitable_get_style_version(), NOT charitable()->get_version().

	Lite ships that helper for exactly this and every other admin enqueue uses it: it returns
	time() when SCRIPT_DEBUG, CHARITABLE_DEBUG or the break-cache flag is on, and the plugin
	version otherwise. The plain version string is correct for a release but wrong during a
	development cycle - edit this stylesheet without bumping the plugin and the ?ver= does not
	move, so an already-cached browser keeps serving the old file and the change looks like it
	did not work. That cost me a wrong reading while testing the chevron alignment fix.

	Pro reaches the same end differently, with a filemtime filter on style_loader_src that Lite
	does not have. Using Lite's own helper keeps this enqueue consistent with the other dozen
	in the plugin rather than importing a second mechanism.
	*/
	wp_enqueue_style(
		'charitable-admin-reports-v3',
		charitable()->get_path( 'assets', false ) . 'css/admin/charitable-admin-reports-v3' . $min . '.css',
		array(),
		charitable_get_style_version()
	);
}
add_action( 'admin_enqueue_scripts', 'charitable_reports_v3_enqueue', 20 );
