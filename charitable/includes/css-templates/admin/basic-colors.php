<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display basic CSS.
 *
 * @package Charitable
 * @author  WP Charitable LLC
 * @since   1.8.0
 * @version 1.8.8.6
 */

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Unslash a value.
	 *
	 * @param mixed $value The value to unslash.
	 * @return mixed The unslashed value.
	 */
	function wp_unslash( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a WordPress core function fallback for older WordPress versions.
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
	function sanitize_text_field( $str ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a WordPress core function fallback for older WordPress versions.
		$filtered = preg_replace( '/[\r\n\t\0\x0B]/', '', $str );
		$filtered = filter_var( $filtered, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
		return trim( $filtered );
	}
}

header( 'Content-type: text/css; charset: UTF-8' );

$primary   = isset( $_GET['p'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', sanitize_text_field( wp_unslash( $_GET['p'] ) ) ), 0, 6 ) : '#3418d2'; // phpcs:ignore
$secondary = isset( $_GET['s'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', sanitize_text_field( wp_unslash( $_GET['s'] ) ) ), 0, 6 ) : '#005eff'; // phpcs:ignore
$tertiary  = isset( $_GET['t'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', sanitize_text_field( wp_unslash( $_GET['t'] ) ) ), 0, 6 ) : '#00a1ff'; // phpcs:ignore
$button    = isset( $_GET['b'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', sanitize_text_field( wp_unslash( $_GET['b'] ) ) ), 0, 6 ) : '#ec5f25'; // phpcs:ignore

$wrapper = '.charitable-preview #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is defined but not used in this file.
