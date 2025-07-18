<?php
/**
 * Display radio field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.7
 */

$default = array_key_exists( 'default', $view_args ) ? $view_args['default'] : false;
$value   = charitable_get_option( $view_args['key'], $default );

?>
<ul class="charitable-radio-list <?php echo esc_attr( $view_args['classes'] ); ?>">
	<?php foreach ( $view_args['options'] as $option => $label ) : ?>
		<li><input type="radio"
				id="<?php printf( 'charitable_settings_%s_%s', esc_attr( implode( '_', $view_args['key'] ) ), esc_attr( $option ) ); ?>"
				name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>"
				value="<?php echo esc_attr( $option ); ?>"
				<?php checked( $value, $option ); ?>
				<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			/>
			<?php echo wp_kses_post( $label ); ?>
		</li>
	<?php endforeach ?>
</ul>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
	<?php
endif;
