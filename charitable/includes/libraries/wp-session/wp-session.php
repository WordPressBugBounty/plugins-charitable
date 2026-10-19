<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress session managment.
 *
 * Standardizes WordPress session data and uses either database transients or in-memory caching
 * for storing user session information.
 *
 * @package WordPress
 * @subpackage Session
 * @since 3.7.0
 */

/*
 * Charitable patch: every function below is wrapped in a function_exists() check as of
 * Lite 1.8.13.2. Ninja Forms ships its own copy of this library, and from Ninja Forms 3.15.6
 * it declares wp_session_cleanup() and wp_session_register_garbage_collection() on every
 * request without declaring the WP_Session class. Charitable_Session only checks for that
 * class before requiring this file, so the unguarded declarations caused a "Cannot
 * redeclare" fatal error on every page. Both libraries store sessions under the same
 * _wp_session_* option names, so whichever copy of the collector runs cleans up both.
 */

if ( ! function_exists( 'wp_session_cache_expire' ) ) {
	/**
	 * Return the current cache expire setting.
	 *
	 * @return int
	 */
	function wp_session_cache_expire() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();

		return $wp_session->cache_expiration();
	}
}

if ( ! function_exists( 'wp_session_commit' ) ) {
	/**
	 * Alias of wp_session_write_close()
	 */
	function wp_session_commit() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		wp_session_write_close();
	}
}

if ( ! function_exists( 'wp_session_decode' ) ) {
	/**
	 * Load a JSON-encoded string into the current session.
	 *
	 * @param string $data
	 */
	function wp_session_decode( $data ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();

		return $wp_session->json_in( $data );
	}
}

if ( ! function_exists( 'wp_session_encode' ) ) {
	/**
	 * Encode the current session's data as a JSON string.
	 *
	 * @return string
	 */
	function wp_session_encode() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();

		return $wp_session->json_out();
	}
}

if ( ! function_exists( 'wp_session_regenerate_id' ) ) {
	/**
	 * Regenerate the session ID.
	 *
	 * @param bool $delete_old_session
	 *
	 * @return bool
	 */
	function wp_session_regenerate_id( $delete_old_session = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();

		$wp_session->regenerate_id( $delete_old_session );

		return true;
	}
}

if ( ! function_exists( 'wp_session_start' ) ) {
	/**
	 * Start new or resume existing session.
	 *
	 * Resumes an existing session based on a value sent by the _wp_session cookie.
	 *
	 * @return bool
	 */
	function wp_session_start() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();
		do_action( 'wp_session_start' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.

		return $wp_session->session_started();
	}
}
if ( ! defined( 'WP_CLI' ) || false === WP_CLI ) {
	add_action( 'plugins_loaded', 'wp_session_start' );
}

if ( ! function_exists( 'wp_session_status' ) ) {
	/**
	 * Return the current session status.
	 *
	 * @return int
	 */
	function wp_session_status() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();

		if ( $wp_session->session_started() ) {
			return PHP_SESSION_ACTIVE;
		}

		return PHP_SESSION_NONE;
	}
}

if ( ! function_exists( 'wp_session_unset' ) ) {
	/**
	 * Unset all session variables.
	 */
	function wp_session_unset() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();

		$wp_session->reset();
	}
}

if ( ! function_exists( 'wp_session_write_close' ) ) {
	/**
	 * Write session data and end session
	 */
	function wp_session_write_close() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		$wp_session = WP_Session::get_instance();

		$wp_session->write_data();
		do_action( 'wp_session_commit' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.
	}
}
if ( ! defined( 'WP_CLI' ) || false === WP_CLI ) {
	add_action( 'shutdown', 'wp_session_write_close' );
}

if ( ! function_exists( 'wp_session_cleanup' ) ) {
	/**
	 * Clean up expired sessions by removing data and their expiration entries from
	 * the WordPress options table.
	 *
	 * This method should never be called directly and should instead be triggered as part
	 * of a scheduled task or cron job.
	 */
	function wp_session_cleanup() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return;
		}

		if ( ! defined( 'WP_INSTALLING' ) ) {
			/**
			 * Determine the size of each batch for deletion.
			 *
			 * @param int
			 */
			$batch_size = apply_filters( 'wp_session_delete_batch_size', 1000 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.

			// Delete a batch of old sessions
			WP_Session_Utils::delete_old_sessions( $batch_size );
		}

		// Allow other plugins to hook in to the garbage collection process.
		do_action( 'wp_session_cleanup' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.
	}
}
add_action( 'wp_session_garbage_collection', 'wp_session_cleanup' );

if ( ! function_exists( 'wp_session_register_garbage_collection' ) ) {
	/**
	 * Register the garbage collector as a twice daily event.
	 */
	function wp_session_register_garbage_collection() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a bundled library function. Changing it would break existing functionality.
		if ( ! wp_next_scheduled( 'wp_session_garbage_collection' ) ) {
			wp_schedule_event( time(), 'hourly', 'wp_session_garbage_collection' );
		}
	}
}
add_action( 'wp', 'wp_session_register_garbage_collection' );
