<?php
/**
 * Helper functions for campaign templates (visual builder).
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.4.2
 * @version   1.8.4.2
 */

if ( ! function_exists( 'charitable_hex_to_rgb' ) ) {
	/**
	 * Convert a hex color to an RGB array.
	 *
	 * @since  1.5.0
	 *
	 * @param  string $hex The hex color.
	 * @return array
	 */
	function charitable_hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );

		if ( strlen( $hex ) == 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$int = hexdec( $hex );

		return [
			'r' => ( $int >> 16 ) & 255,
			'g' => ( $int >> 8 ) & 255,
			'b' => $int & 255,
		];
	}
}

if ( ! function_exists( 'charitable_get_brightness' ) ) {
	/**
	 * Get the brightness of an RGB color.
	 *
	 * @since  1.5.0
	 *
	 * @param  array $rgb The RGB color.
	 * @return float
	 */
	function charitable_get_brightness( $rgb ) {
		return ( $rgb['r'] * 299 + $rgb['g'] * 587 + $rgb['b'] * 114 ) / 1000;
	}
}

if ( ! function_exists( 'charitable_get_constracting_text_color' ) ) {

	/**
	 * Get the contrasting text color for a given background color.
	 *
	 * @since  1.5.0
	 *
	 * @param  string $hexColor The background color.
	 * @return string
	 */
	function charitable_get_constracting_text_color( $hexColor ) {
		$rgb        = charitable_hex_to_rgb( $hexColor );
		$brightness = charitable_get_brightness( $rgb );

		// If brightness is below 175, use white text, otherwise use black.
		return $brightness < 175 ? '#FFFFFF' : '#000000';
	}
}
