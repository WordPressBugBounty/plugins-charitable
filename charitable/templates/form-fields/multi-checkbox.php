<?php
/**
 * The template used to display form fields with multiple checkboxes.
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
$classes     = $view_args['classes'];
$is_required = isset( $field['required'] ) ? $field['required'] : false;
$options     = isset( $field['options'] ) ? $field['options'] : array();
$value       = isset( $field['value'] ) ? (array) $field['value'] : array();

if ( empty( $options ) ) {
	return;
}
?>
<div id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>" class="<?php echo esc_attr( $classes ); ?>">
	<fieldset class="charitable-fieldset-field-wrapper">
		<?php if ( isset( $field['label'] ) ) : ?>
			<div class="charitable-fieldset-field-header" id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_label">
				<?php echo wp_kses_post( $field['label'] ); ?>
				<?php if ( $is_required ) : ?>
					<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
				<?php endif ?>
			</div>
		<?php endif ?>
		<ul class="charitable-checkbox-list options">
		<?php foreach ( $options as $val => $label ) : ?>
			<li>
				<input type="checkbox"
					id="<?php echo esc_attr( $field['key'] . '-' . $val ); ?>"
					name="<?php echo esc_attr( $field['key'] ); ?>[]"
					value="<?php echo esc_attr( $val ); ?>"
					aria-describedby="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_label"
					<?php checked( in_array( $val, $value ) ); // phpcs:ignore ?>
					<?php echo charitable_get_arbitrary_attributes( $field ); // phpcs:ignore ?> />
				<label for="<?php echo esc_attr( $field['key'] . '-' . $val ); ?>"><?php echo esc_html( $label ); ?></label>
			</li>
		<?php endforeach ?>
		</ul>
		<?php

		// If there is a description, add it after the input.
		if ( isset( $field['description'] ) && ! empty( $field['description'] ) ) {
			echo '<p class="charitable-field-description">' . wp_kses_post( $field['description'] ) . '</p>';
		}

		?>
	</fieldset>
</div>
