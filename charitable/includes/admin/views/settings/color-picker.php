<?php
/**
 * Display color picker field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0.8
 * @version   1.7.0.8
 */

$value         = charitable_get_option( $view_args['key'] );
$default_color = apply_filters( 'charitable_default_highlight_colour', '#f89d35' );

if ( empty( $value ) ) :
	$value = isset( $view_args['default'] ) ? $view_args['default'] : '';
endif;

?>
<input type="text"
	id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); ?>"
	name="<?php printf( 'charitable_settings[%s]', $view_args['name'] ); ?>"
	value="<?php echo esc_attr( $value ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
    data-default-color="<?php echo $default_color; ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); ?> />
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; ?></div>
	<?php
endif;
