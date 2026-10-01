<?php
/**
 * The template used to display a simple donation button/link.
 *
 * Override this template by copying it to yourtheme/charitable/shortcodes/donate-button.php
 *
 * @author  David Bisset
 * @package Charitable/Templates/Shortcodes
 * @since   1.8.2
 * @version 1.8.13
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * The destination URL is carried in a data attribute and read back by the click
 * handler rather than being interpolated into the inline JavaScript.
 *
 * esc_url() is a URL escaper, not a JavaScript string escaper. It encodes a literal
 * apostrophe as `&#039;` and leaves named entities such as `&apos;` intact -- correct
 * inside href="", but both are decoded back into a real apostrophe by the HTML parser
 * when it reads an attribute value, which would close a single-quoted JS string and
 * let the remainder of the URL run as script. Keeping the handler free of any
 * user-controlled data removes that context altogether.
 */
$charitable_button_onclick = $view_args['new_tab']
	? "window.open(this.getAttribute('data-charitable-url'));"
	: "location.href=this.getAttribute('data-charitable-url');";

// if the type is a button, use a button element, otherwise use a link.
if ( 'link' === $view_args['type'] ) :
	?>

	<a<?php echo $view_args['new_tab'] ? ' target="_blank"' : ''; ?> href="<?php echo esc_url( $view_args['url'] ); ?>" class="<?php echo esc_attr( $view_args['css'] ); ?>"><?php echo esc_html( $view_args['label'] ); ?></a>

<?php else : ?>

	<input onclick="<?php echo esc_attr( $charitable_button_onclick ); ?>" data-charitable-url="<?php echo esc_url( $view_args['url'] ); ?>" id="charitable-donate-button-<?php echo intval( $view_args['campaign'] ); ?>" class="<?php echo esc_attr( $view_args['css'] ); ?>" type="button"  value="<?php echo esc_attr( $view_args['label'] ); ?>" />

<?php endif; ?>
