<?php
/**
 * Charitable Log Muter
 *
 * This class provides a simple way to mute specific error logs that clutter the error log.
 * To enable/disable muting, simply change the MUTE_LOGS constant to true/false.
 *
 * Muted logs:
 * - CHARITABLE: NEW VENDOR CALL (THROWN/RECEIVED)
 * - Charitable Progress Bar Debug
 * - Charitable Admin Splash
 * - [Charitable Square] logs
 * - Square Legacy Mode logs
 * - NPS Survey logs
 * - Admin notices saving logs
 *
 * @package Charitable
 * @since 1.8.8
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Charitable_Log_Muter {

	/**
	 * Whether to mute the specified logs
	 *
	 * Set to true to mute logs, false to show them
	 *
	 * @var boolean
	 */
	const MUTE_LOGS = true;

	/**
	 * Patterns to mute (case-insensitive)
	 *
	 * @var array
	 */
	private static $mute_patterns = array(
		'CHARITABLE: NEW VENDOR',
		'Charitable Progress Bar Debug',
		'Charitable Admin Splash',
		'[Charitable Square]',
		'Square Legacy Mode',
		'Charitable: Loading NPS Survey',
		'Charitable NPS Survey:',
		'Saving admin notices to transient',
		'Notices to save:'
	);

	/**
	 * Initialize the log muter
	 */
	public static function init() {
		// The log muter is ready to use
		// Note: Direct error_log() calls in vendor files have been disabled
		// by commenting them out in the vendor files themselves
	}

	/**
	 * Check if a message should be muted
	 *
	 * @param string $message The error message
	 * @return boolean True if message should be muted, false otherwise
	 */
	public static function should_mute( $message ) {
		if ( ! self::MUTE_LOGS ) {
			return false;
		}

		foreach ( self::$mute_patterns as $pattern ) {
			if ( stripos( $message, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Conditional error logging function
	 *
	 * @param string $message The message to log
	 * @param int    $message_type The message type
	 * @param string $destination The destination
	 * @param string $extra_headers Extra headers
	 * @return boolean True if logged, false if muted
	 */
	public static function log( $message, $message_type = 0, $destination = null, $extra_headers = null ) {
		if ( self::should_mute( $message ) ) {
			return true; // Return true to indicate "success" but don't actually log
		}

		return error_log( $message, $message_type, $destination, $extra_headers );
	}
}

// Initialize the log muter
Charitable_Log_Muter::init();
