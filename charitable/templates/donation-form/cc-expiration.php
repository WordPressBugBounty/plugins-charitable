<?php
/**
 * Displays the credit card expiration select boxes.
 *
 * Override this template by copying it to yourtheme/charitable/cc-expiration.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form         = $view_args['form'];
$field        = $view_args['field'];
$classes      = $view_args['classes'];
$is_required  = isset( $field['required'] ) ? $field['required'] : false;
$current_year = date( 'Y' ); // phpcs:ignore

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
		<select name="<?php echo esc_attr( $field['key'] ); ?>[month]" class="month" aria-describedby="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_label">
			<?php
			foreach ( range( 1, 12 ) as $month ) :
				$padded_month = sprintf( '%02d', $month );
				?>
				<option value="<?php echo esc_attr( $padded_month ); ?>"><?php echo esc_html( $padded_month ); ?></option>
			<?php endforeach ?>
		</select>
		<select name="<?php echo esc_attr( $field['key'] ); ?>[year]" class="year" aria-describedby="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_label">
			<?php
			for ( $i = 0; $i < 15; $i++ ) :
				$year = $current_year + $i; // phpcs:ignore
				?>
				<option value="<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $year ); ?></option>
			<?php endfor ?>
		</select>
	</fieldset><!-- .charitable-field-wrapper -->
</div><!-- #charitable_field_<?php echo esc_attr( $field['key'] ); ?> -->
