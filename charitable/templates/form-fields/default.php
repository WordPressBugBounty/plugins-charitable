<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display text form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.6.1 Added description output.
 * @version 1.8.8.6
 * @version 1.8.13.1
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = esc_attr( $view_args['classes'] );
$charitable_field_type  = isset( $charitable_field['type'] ) ? $charitable_field['type'] : 'text';
$charitable_is_required = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_value       = isset( $charitable_field['value'] ) ? $charitable_field['value'] : '';

/*
 * Fill in the autocomplete token for known personal fields (WCAG 1.3.5) -- 1.8.13.1.
 *
 * Done here as well as in the field registry so every render path that reaches this
 * template is covered, including fields an addon registers without declaring a token.
 * Never overwrites an explicit value. The attribute itself is emitted by the existing
 * charitable_get_arbitrary_attributes() call on the input below.
 *
 * function_exists guarded because this template is theme-overridable --
 * Charitable_Template::locate_template() checks the theme and child theme before the
 * plugin's own copy. A site that took a copy into its theme and later ran it against a
 * Charitable without this helper (a rollback, or a theme moved between sites) would
 * otherwise hit Call to undefined function on every donation form. Unguarded, the field
 * simply renders without the token, exactly as it did before 1.8.13.1.
 */
if ( function_exists( 'charitable_maybe_add_field_autocomplete' ) ) {
	$charitable_field = charitable_maybe_add_field_autocomplete( $charitable_field );
}

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
	<?php if ( isset( $charitable_field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $charitable_field['help']; // phpcs:ignore ?></p>
	<?php endif ?>
	<input type="<?php echo esc_attr( $charitable_field_type ); ?>" name="<?php echo esc_attr( $charitable_field['key'] ); ?>" id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element" value="<?php echo esc_attr( stripslashes( $charitable_value ) ); ?>" <?php echo charitable_get_arbitrary_attributes( $charitable_field ); // phpcs:ignore ?>/>
	<?php

	// If there is a description, add it after the input.
	if ( isset( $charitable_field['description'] ) && ! empty( $charitable_field['description'] ) ) {
		echo '<p class="charitable-field-description">' . wp_kses_post( $charitable_field['description'] ) . '</p>';
	}

	?>
</div>
