<?php
/**
 * Display a widget with donation stats.
 *
 * Override this template by copying it to yourtheme/charitable/widgets/donation-stats.php
 *
 * @package Charitable/Templates/Widgets
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.0.0
 */

$widget_title = apply_filters( 'widget_title', $view_args['title'] );

echo $view_args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! empty( $widget_title ) ) :
	echo $view_args['before_title'] . esc_html( $widget_title ) . $view_args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
endif;

charitable_template( 'donation-stats.php', $view_args );

echo $view_args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
