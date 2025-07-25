<?php
/**
 * The template used to display select form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form        = $view_args['form'];
$field       = $view_args['field'];
$classes     = $view_args['classes'];
$is_required = isset( $field['required'] ) ? $field['required'] : false;
$options     = isset( $field['options'] ) ? $field['options'] : array();
$value       = isset( $field['value'] ) ? $field['value'] : '';

if ( is_array( $value ) ) {
	$value = current( $value );
}

if ( count( $options ) ) :

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
	<select name="<?php echo esc_attr( $field['key'] ); ?>" id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_element">
		<?php
		foreach ( $options as $val => $label ) :

			if ( is_array( $label ) ) :
				?>
				<optgroup label="<?php echo esc_attr( $val ); ?>">
				<?php foreach ( $label as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
				</optgroup>
			<?php else : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php

			endif;
		endforeach;

		?>
	</select>
	<?php if ( isset( $field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $field['help']; // phpcs:ignore ?></p>
	<?php endif ?>
</div>
	<?php

endif;
