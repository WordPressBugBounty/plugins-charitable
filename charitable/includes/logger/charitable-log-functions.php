<?php
/**
 * Charitable Log Functions.
 *
 * Global logging function with dual-write bridge.
 *
 * @package   Charitable/Functions/Logger
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log a message to the Charitable logging system.
 *
 * @since 1.8.11
 *
 * @param string       $title   Log title (required, non-empty).
 * @param string|array $message Log message or data.
 * @param array        $args {
 *     Optional. Additional arguments.
 *
 *     @type string|array $type        Log type(s). Default 'log'.
 *     @type string       $level       Log level: 'error', 'warning', 'info', 'debug'. Default 'info'.
 *     @type string       $source      Log source. Default 'core'.
 *     @type int          $campaign_id Campaign ID. Default 0.
 *     @type int          $donation_id Donation ID. Default 0.
 *     @type int          $donor_id    Donor ID. Default 0.
 *     @type int          $user_id     User ID. Default 0.
 *     @type int          $object_id   Object ID. Default 0.
 *     @type string       $object_type Object type. Default ''.
 *     @type bool         $force       Bypass disabled logging. Default false.
 * }
 */
function charitable_log( $title, $message = '', $args = array() ) {
	// Check if logging is enabled (unless forced).
	$force = ! empty( $args['force'] );
	if ( ! $force && ! Charitable_Log::is_enabled() ) {
		return;
	}

	// Require non-empty title.
	if ( empty( $title ) ) {
		return;
	}

	// Set defaults.
	$defaults = array(
		'type'        => 'log',
		'level'       => 'info',
		'source'      => 'core',
		'campaign_id' => 0,
		'donation_id' => 0,
		'donor_id'    => 0,
		'user_id'     => 0,
		'object_id'   => 0,
		'object_type' => '',
		'force'       => false,
	);

	$args = wp_parse_args( $args, $defaults );

	// Map 'type' key to 'types' for the record.
	$types = $args['type'];
	unset( $args['type'], $args['force'] );

	// Validate types against registered list.
	$valid_types = array_keys( Charitable_Log::get_log_types() );
	if ( is_array( $types ) ) {
		$types = array_filter(
			$types,
			function ( $t ) use ( $valid_types ) {
				return in_array( $t, $valid_types, true );
			}
		);
		if ( empty( $types ) ) {
			$types = array( 'log' );
		}
	} else {
		if ( ! in_array( $types, $valid_types, true ) ) {
			$types = 'log';
		}
	}

	$args['types'] = $types;

	// Validate level.
	$valid_levels = array_keys( Charitable_Log::get_log_levels() );
	if ( ! in_array( $args['level'], $valid_levels, true ) ) {
		$args['level'] = 'info';
	}

	// Sanitize source.
	$args['source'] = sanitize_key( $args['source'] );

	/**
	 * Filter the log message before saving.
	 *
	 * @since 1.8.11
	 *
	 * @param string|array $message The log message.
	 * @param string       $title   The log title.
	 * @param array        $args    Additional arguments.
	 */
	$message = apply_filters( 'charitable_log_message', $message, $title, $args );

	// Create the record data.
	$record = Charitable_Log_Record::create( $title, $message, $args );

	// Queue for batch insert.
	Charitable_Log::get_instance()->add( $record );

	// Dual-write bridge: also write to error_log if debug mode is on.
	if ( charitable_is_debug() ) {
		$level_upper = strtoupper( $args['level'] );
		$source      = $args['source'];

		// Format message for error_log.
		$log_message = $title;
		if ( ! empty( $message ) ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				$log_message .= ': ' . print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			} else {
				$log_message .= ': ' . wp_strip_all_tags( (string) $message );
			}
		}

		error_log( "[Charitable][{$level_upper}][{$source}] {$log_message}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * Log a message immediately (bypasses the queue, writes directly to DB).
 *
 * Identical API to charitable_log() but skips the shutdown-hook queue and
 * calls the DB insert directly. This ensures the record is persisted even
 * if PHP terminates abnormally before the shutdown hook fires (e.g. a fatal
 * error during IPN processing).
 *
 * @since 1.8.11
 *
 * @param string       $title   Log title (required, non-empty).
 * @param string|array $message Log message or data.
 * @param array        $args    Same arguments as charitable_log().
 */
function charitable_log_immediate( $title, $message = '', $args = array() ) {
	// Check if logging is enabled (unless forced).
	$force = ! empty( $args['force'] );
	if ( ! $force && ! Charitable_Log::is_enabled() ) {
		return;
	}

	// Require non-empty title.
	if ( empty( $title ) ) {
		return;
	}

	// Set defaults.
	$defaults = array(
		'type'        => 'log',
		'level'       => 'info',
		'source'      => 'core',
		'campaign_id' => 0,
		'donation_id' => 0,
		'donor_id'    => 0,
		'user_id'     => 0,
		'object_id'   => 0,
		'object_type' => '',
		'force'       => false,
	);

	$args = wp_parse_args( $args, $defaults );

	// Map 'type' key to 'types' for the record.
	$types = $args['type'];
	unset( $args['type'], $args['force'] );

	// Validate types against registered list.
	$valid_types = array_keys( Charitable_Log::get_log_types() );
	if ( is_array( $types ) ) {
		$types = array_filter(
			$types,
			function ( $t ) use ( $valid_types ) {
				return in_array( $t, $valid_types, true );
			}
		);
		if ( empty( $types ) ) {
			$types = array( 'log' );
		}
	} else {
		if ( ! in_array( $types, $valid_types, true ) ) {
			$types = 'log';
		}
	}

	$args['types'] = $types;

	// Validate level.
	$valid_levels = array_keys( Charitable_Log::get_log_levels() );
	if ( ! in_array( $args['level'], $valid_levels, true ) ) {
		$args['level'] = 'info';
	}

	// Sanitize source.
	$args['source'] = sanitize_key( $args['source'] );

	/** This filter is documented in charitable-log-functions.php */
	$message = apply_filters( 'charitable_log_message', $message, $title, $args );

	// Create the record data.
	$record = Charitable_Log_Record::create( $title, $message, $args );

	// Write directly to DB — bypass the queue.
	$log = Charitable_Log::get_instance();
	$log->ensure_table_exists();
	$log->get_db()->insert( $record );

	// Dual-write bridge: also write to error_log if debug mode is on.
	if ( charitable_is_debug() ) {
		$level_upper = strtoupper( $args['level'] );
		$source      = $args['source'];

		$log_message = $title;
		if ( ! empty( $message ) ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				$log_message .= ': ' . print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			} else {
				$log_message .= ': ' . wp_strip_all_tags( (string) $message );
			}
		}

		error_log( "[Charitable][{$level_upper}][{$source}] {$log_message}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
