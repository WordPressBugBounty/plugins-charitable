<?php
/**
 * Display number field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

$value = charitable_get_option( $view_args['key'] );

if ( false === $value ) {
	$value = isset( $view_args['default'] ) ? $view_args['default'] : '';
}

$min = isset( $view_args['min'] ) ? 'min="' . $view_args['min'] . '"' : '';
$max = isset( $view_args['max'] ) ? 'max="' . $view_args['max'] . '"' : '';
?>
<input type="number"
	id="<?php printf( 'charitable_settings_%s', esc_attr( implode( '_', $view_args['key'] ) ) ); ?>"
	name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>"
	value="<?php echo esc_attr( $value ); ?>"
	<?php echo esc_attr( $min ); ?>
	<?php echo esc_attr( $max ); ?>
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	/>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
	<?php
endif;
