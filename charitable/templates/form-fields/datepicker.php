<?php
/**
 * The template used to display datepicker form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.6.40
 * @version 1.8.6.1 Added description output.
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

global $wp_locale;

$form        = $view_args['form'];
$field       = $view_args['field'];
$classes     = esc_attr( $view_args['classes'] );
$is_required = isset( $field['required'] ) ? $field['required'] : false;
$value       = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
$min_date    = isset( $field['min_date'] ) ? esc_attr( $field['min_date'] ) : '';
$max_date    = isset( $field['max_date'] ) ? esc_attr( $field['max_date'] ) : '';
$year_range  = array_key_exists( 'year_range', $field ) ? $field['year_range'] : 'c-100:c';
$date_format = array_key_exists( 'date_format', $field ) ? $field['date_format'] : charitable()->registry()->get( 'i18n' )->get_js_datepicker_format( 'MM d, yy' );
$json_args   = array(
	'changeMonth'     => true,
	'changeYear'      => true,
	'dateFormat'      => $date_format,
	'yearRange'       => $year_range,
	'monthNames'      => array_values( $wp_locale->month ),
	'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
);

if ( array_key_exists( 'min_date', $field ) ) {
	$json_args['minDate'] = '+' . $field['min_date'];
}

if ( array_key_exists( 'max_date', $field ) ) {
	$json_args['maxDate'] = '+' . $field['max_date'];
}

/* Enqueue the datepicker */
if ( ! wp_script_is( 'jquery-ui-datepicker' ) ) {
	wp_enqueue_script( 'jquery-ui-datepicker' );
}

$datepicker_json_args = wp_json_encode( $json_args );

wp_add_inline_script(
	'jquery-ui-datepicker',
	"jQuery(document).ready( function(){ jQuery( '.datepicker' ).datepicker( {$datepicker_json_args} ); });"
);

wp_enqueue_style( 'charitable-datepicker' );

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
	<input
		type="text"
		class="datepicker"
		name="<?php echo esc_attr( $field['key'] ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_element"
		<?php echo charitable_get_arbitrary_attributes( $field ); // phpcs:ignore ?>
	/>
	<?php

	// If there is a description, add it after the input.
	if ( isset( $field['description'] ) && ! empty( $field['description'] ) ) {
		echo '<p class="charitable-field-description">' . wp_kses_post( $field['description'] ) . '</p>';
	}

	?>
</div>