<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Basic theme for the Campaign Builder.
 *
 * @package Charitable/Campaign Builder/Themes
 * @author  WP Charitable
 * @since   1.0.0
 * @version 1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary   = isset( $_GET['p'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['p'] ), 0, 6 ) : '#3418d2'; // phpcs:ignore
$secondary = isset( $_GET['s'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['s'] ), 0, 6 ) : '#005eff'; // phpcs:ignore
$tertiary  = isset( $_GET['t'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['t'] ), 0, 6 ) : '#00a1ff'; // phpcs:ignore
$button    = isset( $_GET['b'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['b'] ), 0, 6 ) : '#ec5f25'; // phpcs:ignore

$wrapper = '.charitable-preview #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore
