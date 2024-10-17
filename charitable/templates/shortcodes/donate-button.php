<?php
/**
 * The template used to display a simple donation button/link.
 *
 * Override this template by copying it to yourtheme/charitable/shortcodes/donate-button.php
 *
 * @author  David Bisset
 * @package Charitable/Templates/Shortcodes
 * @since   1.8.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$target_for_link   = $view_args['new_tab'] ? ' target="_blank"' : '';
$target_for_button = $view_args['new_tab'] ? ' onclick="window.open(\'' . esc_url( $view_args['url'] ) . '\')" ' : ' onclick="location.href=\'' . esc_url( $view_args['url'] ) . '\'" ';

// if the type is a button, use a button element, otherwise use a link.
if ( 'link' === $view_args['type'] ) :
	?>

	<a <?php echo esc_html( $target_for_link ); ?> href="<?php echo esc_url( $view_args['url'] ); ?>" class="<?php echo esc_attr( $view_args['css'] ); ?>"><?php echo esc_html( $view_args['label'] ); ?></a>

<?php else : ?>

	<input <?php echo esc_html( $target_for_button ); ?> id="charitable-donate-button-<?php echo intval( $view_args['campaign'] ); ?>" class="<?php echo esc_attr( $view_args['css'] ); ?>" type="button"  value="<?php echo esc_html( $view_args['label'] ); ?>" />

<?php endif; ?>
