<?php
/**
 * Charitable abilities hooks.
 *
 * The wp_abilities_api_* hooks exist only in WordPress 6.9+, so on older cores
 * these callbacks never fire and nothing registers. That is the entire
 * version-guard strategy — no polyfill, no vendored dependency.
 *
 * Note the version guard covers REGISTRATION only. `wp_after_execute_ability`
 * is core 6.9.0 but `wp_ability_invoked` is 7.1.0, so on 6.9 and 7.0.x
 * abilities work while denial logging and invocation counting do not. See
 * Charitable_Abilities_Usage for the clamp that stops that reporting a
 * negative failure count.
 *
 * @package   Charitable/Functions/Abilities
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_abilities_api_categories_init', array( Charitable_Abilities::get_instance(), 'register_category' ) );
add_action( 'wp_abilities_api_init', array( Charitable_Abilities::get_instance(), 'register_abilities' ) );
add_filter( 'charitable_log_types', array( Charitable_Abilities::get_instance(), 'register_log_type' ) );
add_filter( 'charitable_log_sources', array( Charitable_Abilities::get_instance(), 'register_log_sources' ) );
add_filter( 'charitable_system_info_sections', array( Charitable_Abilities_System_Info::get_instance(), 'render' ) );
add_action( 'wp_ability_invoked', array( Charitable_Abilities::get_instance(), 'log_ability_invocation' ), 10, 3 );

/*
 * Priority 9, strictly before set_error_http_status() at 10. That method's own
 * "never override a status something upstream set deliberately" guard would
 * otherwise leave core's 404 in place on every one of the six gated slugs, and
 * charitable_capability_unavailable would never be constructed. See
 * Charitable_Abilities::intercept_gated_ability() for the full reasoning.
 *
 * That method reads the ability name from `$request->get_param( 'name' )`,
 * which core has already parsed off the route by this point - the slug
 * arrives as a literal slash (`charitable/get-donor`), never a `~` or any
 * other encoded separator. There is no tilde anywhere in core's REST API.
 */
add_filter( 'rest_request_after_callbacks', array( Charitable_Abilities::get_instance(), 'intercept_gated_ability' ), 9, 3 );
add_filter( 'rest_request_after_callbacks', array( Charitable_Abilities::get_instance(), 'set_error_http_status' ), 10, 3 );

/*
 * Usage counting. Three seams, because core gives us three different facts and
 * no single hook carries all of them:
 *
 * - `wp_ability_invoked` fires for every call, and the handler above records
 *   both the invocation and a permission denial from it.
 * - `wp_after_execute_ability` fires ONLY on success — a WP_Error out of
 *   do_execute(), or a failed output validation, returns from execute() before
 *   it. There is no "ability failed" action in core, which is why `failed` is
 *   derived rather than counted.
 * - The write gate records its own refusals from inside
 *   Charitable_Abilities_Registrar::gate_write_callback(), where it already
 *   knows it is refusing.
 *
 * Counts accumulate in memory and land in one option write per request on
 * `shutdown`.
 */
add_action( 'wp_after_execute_ability', array( 'Charitable_Abilities_Usage', 'record_success' ), 10, 1 );
add_action( 'shutdown', array( 'Charitable_Abilities_Usage', 'flush' ) );
