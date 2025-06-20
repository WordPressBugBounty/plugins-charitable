<?php
/**
 * Display basic CSS.
 *
 * @package Charitable
 * @author  WP Charitable LLC
 * @since   1.8.0
 */

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Unslash a value.
	 *
	 * @param mixed $value The value to unslash.
	 * @return mixed The unslashed value.
	 */
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Sanitize a text field.
	 *
	 * @param string $str The string to sanitize.
	 * @return string The sanitized string.
	 */
	function sanitize_text_field( $str ) {
		$filtered = preg_replace( '/[\r\n\t\0\x0B]/', '', $str );
		$filtered = filter_var( $filtered, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
		return trim( $filtered );
	}
}

header( 'Content-type: text/css; charset: UTF-8' );

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', sanitize_text_field( wp_unslash( $_GET['p'] ) ) ) : '#3418d2'; // phpcs:ignore
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '#005eff'; // phpcs:ignore
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', sanitize_text_field( wp_unslash( $_GET['t'] ) ) ) : '#00a1ff'; // phpcs:ignore
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', sanitize_text_field( wp_unslash( $_GET['b'] ) ) ) : '#ec5f25'; // phpcs:ignore

$wrapper = '.charitable-preview #charitable-design-wrap .charitable-campaign-preview';
