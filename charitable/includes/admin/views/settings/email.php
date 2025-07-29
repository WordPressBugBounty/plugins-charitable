<?php
/**
 * Display email field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

$value = charitable_get_option( $view_args['key'] );

if ( empty( $value ) ) :
	$value = isset( $view_args['default'] ) ? $view_args['default'] : '';
endif;

$escaped_key  = implode( '_', $view_args['key'] );
$escaped_name = $view_args['name'];

?>
<input type="email"
	id="charitable_settings_<?php echo esc_attr( $escaped_key ); ?>"
	name="charitable_settings[<?php echo esc_attr( $escaped_name ); ?>]"
	value="<?php echo esc_attr( $value ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
/>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
<?php endif; ?>
