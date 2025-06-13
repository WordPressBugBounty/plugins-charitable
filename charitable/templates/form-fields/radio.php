<?php
/**
 * The template used to display radio form fields.
 *
 * Override this template by copying it to yourtheme/charitable/form-fields/radio.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.6.1 Added description output.
 * @version 1.8.6.1 Added SVG support to label.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form        = $view_args['form'];
$field       = $view_args['field'];
$classes     = $view_args['classes'];
$is_required = isset( $field['required'] ) ? $field['required'] : false;
$options     = isset( $field['options'] ) ? $field['options'] : array();
$value       = isset( $field['value'] ) ? $field['value'] : '';

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
		<ul class="charitable-radio-list <?php echo esc_attr( $view_args['classes'] ); ?>">
			<?php foreach ( $options as $option => $label ) : ?>
				<li><input type="radio"
						id="<?php echo esc_attr( $field['key'] . '-' . $option ); ?>"
						name="<?php echo esc_attr( $field['key'] ); ?>"
						value="<?php echo esc_attr( $option ); ?>"
						aria-describedby="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_label"
						<?php checked( $value, $option ); ?>
						<?php echo charitable_get_arbitrary_attributes( $field ); // phpcs:ignore ?> />
					<?php
					$allowed_html = array_merge(
						wp_kses_allowed_html( 'post' ),
						array(
							'svg' => array(
								'class' => true,
								'aria-hidden' => true,
								'aria-labelledby' => true,
								'role' => true,
								'xmlns' => true,
								'width' => true,
								'height' => true,
								'viewbox' => true
							),
							'path' => array(
								'd' => true,
								'fill' => true
							)
						)
					);
					?>
					<label for="<?php echo esc_attr( $field['key'] . '-' . $option ); ?>"><?php echo wp_kses( $label, $allowed_html ); ?></label>
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
