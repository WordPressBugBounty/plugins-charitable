<?php
/**
 * The template used to display text form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.6.1 Added description output.
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form        = $view_args['form'];
$field       = $view_args['field'];
$classes     = esc_attr( $view_args['classes'] );
$field_type  = isset( $field['type'] ) ? $field['type'] : 'text';
$is_required = isset( $field['required'] ) ? $field['required'] : false;
$value       = isset( $field['value'] ) ? $field['value'] : '';

?>
<div id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>" class="<?php echo esc_attr( $classes ); ?>">
	<?php if ( isset( $field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_element">
			<?php echo wp_kses_post( $field['label'] ); ?>
			<?php if ( $is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<?php if ( isset( $field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $field['help']; // phpcs:ignore ?></p>
	<?php endif ?>
	<input type="<?php echo esc_attr( $field_type ); ?>" name="<?php echo esc_attr( $field['key'] ); ?>" id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_element" value="<?php echo esc_attr( stripslashes( $value ) ); ?>" <?php echo charitable_get_arbitrary_attributes( $field ); // phpcs:ignore ?>/>
	<?php

	// If there is a description, add it after the input.
	if ( isset( $field['description'] ) && ! empty( $field['description'] ) ) {
		echo '<p class="charitable-field-description">' . wp_kses_post( $field['description'] ) . '</p>';
	}

	?>
</div>
