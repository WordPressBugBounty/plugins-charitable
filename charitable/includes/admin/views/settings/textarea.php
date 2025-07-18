<?php
/**
 * Display textarea field.
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

$rows = isset( $field['rows'] ) ? $field['rows'] : 4;
?>

<textarea
	id="<?php printf( 'charitable_settings_%s', implode( '_', ( $view_args['key'] ) ) ); // phpcs:ignore ?>"
	name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	rows="<?php echo absint( $rows ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>><?php echo esc_textarea( $value ); ?></textarea>

<?php if ( isset( $view_args['help'] ) ) : ?>

	<div class="charitable-help"><?php echo $view_args['help']; // phpcs:ignore ?></div>

	<?php
endif;
