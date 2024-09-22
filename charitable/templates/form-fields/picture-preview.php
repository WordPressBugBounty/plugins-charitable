<?php
/**
 * The template used to display a preview of an uploaded photo.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.4.0
 * @version 1.6.43
 */

if ( ! isset( $view_args['image'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$image              = $view_args['image'];
$field              = $view_args['field'];
$size               = isset( $field['size'] ) ? $field['size'] : 'thumbnail';
$multiple           = isset( $field['max_uploads'] ) && $field['max_uploads'] > 1 ? '[]' : '';
$is_src             = strpos( $image, 'img' ) !== false;
$remove_button_text = isset( $field['remove_button_text' ] ) ? $field['remove_button_text' ] : __( 'Remove', 'charitable' );
$remove_button_show = isset( $field['remove_button_show' ] ) && $field['remove_button_show' ];

if ( is_numeric( $size ) ) {
	$size = array( $size, $size );
}

?>
<li <?php echo ! $is_src ? 'data-attachment-id="' . esc_attr( $image ) . '"' : ''; ?>>
	<a href="#" class="remove-image button" <?php if ( $remove_button_show ) echo 'style="display:block;"' ?>><?php echo $remove_button_text; ?></a>
	<?php
	if ( $is_src ) :
		echo $image;
	else :
	?>
		<input type="hidden"
			name="<?php echo esc_attr( $field['key'] . $multiple ); ?>"
			id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_element"
			value="<?php echo esc_attr( $image ); ?>"
		/>
		<?php echo wp_get_attachment_image( $image, $size ); ?>
	<?php endif ?>
</li>
