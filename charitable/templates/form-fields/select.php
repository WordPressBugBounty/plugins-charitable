<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display select form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 * @version 1.8.13.1
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = $view_args['classes'];
$charitable_is_required = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_options     = isset( $charitable_field['options'] ) ? $charitable_field['options'] : array();
$charitable_value       = isset( $charitable_field['value'] ) ? $charitable_field['value'] : '';

if ( is_array( $charitable_value ) ) {
	$charitable_value = current( $charitable_value );
}

/*
 * Autocomplete token for known personal fields, e.g. Country (WCAG 1.3.5). 1.8.13.1
 *
 * function_exists guarded for the same reason as form-fields/default.php: this template is
 * theme-overridable, so a copy carried into a theme and run against an older Charitable would
 * otherwise fatal. Unguarded, the select renders without the token as it did before.
 */
if ( function_exists( 'charitable_maybe_add_field_autocomplete' ) ) {
	$charitable_field = charitable_maybe_add_field_autocomplete( $charitable_field );
}

if ( count( $charitable_options ) ) :

	?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php if ( isset( $charitable_field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element">
			<?php echo wp_kses_post( $charitable_field['label'] ); ?>
			<?php if ( $charitable_is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<?php
	/*
	 * Emit explicit attributes, as form-fields/default.php already does for text inputs
	 * (1.8.13.1). Without this a select could not carry an `autocomplete` token, which is
	 * what the Country field needs for WCAG 1.3.5 Identify Input Purpose.
	 *
	 * Deliberately passes ONLY the attrs array, rather than the whole field as default.php
	 * does. Handed the full field, charitable_get_arbitrary_attributes() also applies its
	 * back-compat pass, which would start emitting `required` on every required select --
	 * a real change in browser validation behaviour, and not something an accessibility
	 * fix should bring in as a side effect. Same call shape as
	 * class-charitable-public-form-view.php.
	 */
	$charitable_select_attrs = charitable_get_arbitrary_attributes(
		array( 'attrs' => isset( $charitable_field['attrs'] ) ? (array) $charitable_field['attrs'] : array() )
	);
	?>
	<select name="<?php echo esc_attr( $charitable_field['key'] ); ?>" id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element"<?php echo $charitable_select_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped in the helper. ?>>
		<?php
		foreach ( $charitable_options as $charitable_val => $charitable_label ) :

			if ( is_array( $charitable_label ) ) :
				?>
				<optgroup label="<?php echo esc_attr( $charitable_val ); ?>">
				<?php foreach ( $charitable_label as $charitable_val => $charitable_label ) : ?>
					<option value="<?php echo esc_attr( $charitable_val ); ?>" <?php selected( $charitable_val, $charitable_value ); ?>><?php echo esc_html( $charitable_label ); ?></option>
				<?php endforeach; ?>
				</optgroup>
			<?php else : ?>
				<option value="<?php echo esc_attr( $charitable_val ); ?>" <?php selected( $charitable_val, $charitable_value ); ?>><?php echo esc_html( $charitable_label ); ?></option>
				<?php

			endif;
		endforeach;

		?>
	</select>
	<?php if ( isset( $charitable_field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $charitable_field['help']; // phpcs:ignore ?></p>
	<?php endif ?>
</div>
	<?php

endif;
