<?php
/**
 * The template used to display checkbox form fields.
 *
 * Override this template by copying it to yourtheme/charitable/form-fields/checkbox.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.2.0
 * @version 1.8.6.1 Added description.
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form        = $view_args['form'];
$field       = $view_args['field'];
$classes     = $view_args['classes'];
$is_required = isset( $field['required'] ) ? $field['required'] : false;
$value       = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '1';

if ( isset( $field['checked'] ) ) {
	$checked = $field['checked'];
} else {
	$checked = isset( $field['default'] ) ? $field['default'] : 0;
}
?>
<div id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>" class="<?php echo esc_attr( $classes ); ?>">
	<input
		type="checkbox"
		name="<?php echo esc_attr( $field['key'] ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_element"
		<?php checked( $checked ); ?>
		<?php echo charitable_get_arbitrary_attributes( $field ); ?>
	/>
	<?php if ( isset( $field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_element">
		<?php echo wp_kses_post( $field['label'] ); ?>
			<?php if ( $is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<?php if ( isset( $field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $field['help']; ?></p>
	<?php endif ?>
	<?php

	// If there is a description, add it after the input.
	if ( isset( $field['description'] ) && ! empty( $field['description'] ) ) {
		echo '<p class="charitable-field-description">' . wp_kses_post( $field['description'] ) . '</p>';
	}

	?>
</div>