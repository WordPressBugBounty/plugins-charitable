<?php
/**
 * The template used to display the suggested amounts field.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.0.0
 * @package Charitable/Templates/Form Fields
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form    = $view_args['form'];
$field   = $view_args['field'];
$classes = $view_args['classes'];

if ( ! isset( $field['content'] ) ) {
	return;
}
?>
<p class="<?php echo esc_attr( $classes ); ?>">
	<?php echo $field['content']; // phpcs:ignore ?>
</p>
