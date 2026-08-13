<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Beacon Single (1 Column) — admin color overrides.
 *
 * Color-specific CSS for the admin builder preview. Theme color CSS variables
 * are already declared in the main beacon-single.php; this file exists so that
 * the builder's per-color enqueue requests don't 404.
 *
 * @package Charitable
 * @author  WP Charitable LLC
 * @since   1.8.12
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary   = isset( $_GET['p'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['p'] ), 0, 6 ) : '#1d3a8a'; // phpcs:ignore
$secondary = isset( $_GET['s'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['s'] ), 0, 6 ) : '#0D0D0D'; // phpcs:ignore
$tertiary  = isset( $_GET['t'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['t'] ), 0, 6 ) : '#5F5F5F'; // phpcs:ignore
$button    = isset( $_GET['b'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['b'] ), 0, 6 ) : '#1d3a8a'; // phpcs:ignore

?>

.charitable-preview.charitable-builder-template-beacon-single #charitable-design-wrap .charitable-campaign-preview {
	--charitable-hero-accent: <?php echo $primary; // phpcs:ignore ?>;
}
