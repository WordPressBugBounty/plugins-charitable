<?php
/**
 * Display select field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.5.7 - Added show_button argument.
 */

$value = charitable_get_option( $view_args['key'] );

if ( false === $value ) {
	$value = isset( $view_args['default'] ) ? $view_args['default'] : '';
}

$form_action_url    = isset( $view_args['form_action_url'] ) ? $view_args['form_action_url'] : '';
$form_action_method = isset( $view_args['form_action_method'] ) ? $view_args['form_action_method'] : 'POST';
$show_button        = isset( $view_args['show_button'] ) ? $view_args['show_button'] : true;
$button_label       = isset( $view_args['button_label'] ) ? $view_args['button_label'] : 'Submit';
$nonce_action_name  = isset( $view_args['nonce_action_name'] ) ? $view_args['nonce_action_name'] : 'wpcharitable-action';
$nonce_field_name   = isset( $view_args['nonce_field_name'] ) ? $view_args['nonce_field_name'] : false;
$select_name        = isset( $view_args['select_name'] ) ? $view_args['select_name'] : $view_args['name'];
$accept             = isset( $view_args['accept'] ) ? $view_args['accept'] : '.jsn,.json';
$form_tag           = isset( $view_args['form_tag'] ) ? $view_args['form_tag'] : true;
$wrapper_class      = isset( $view_args['wrapper_class'] ) ? $view_args['wrapper_class'] : '';
$thumbnail_preview  = isset( $view_args['thumbnail_preview'] ) ? $view_args['thumbnail_preview'] : false; // This is the ID of the attachment.
$remove_button      = isset( $view_args['show_remove_button'] ) ? $view_args['show_remove_button'] : false;
$remove_button_text = isset( $view_args['remove_button_text'] ) ? $view_args['remove_button_text'] : __( 'Remove current logo', 'charitable' );
$error_message      = false;

// determine if the fields should be shown, based on what we are trying to do with them.
if ( isset( $view_args['nonce_action_name'] ) && 'import_donations' === $view_args['nonce_action_name'] && is_array( $view_args['options'] ) && 0 === count( $view_args['options'] ) ) {
	$error_message = '<strong>' . __( 'You have no campaigns to import donations into.', 'charitable' ) . '</strong>';
}
?>
<?php if ( $form_tag ) : ?>
	<form action="<?php echo esc_url( $form_action_url ); ?>" method="POST" enctype="multipart/form-data" class="form-contains-file charitable-import-campaign-donations-form">

	<?php wp_nonce_field( $nonce_action_name, $nonce_field_name ); ?>

	<input type="hidden" name="action" value="<?php echo esc_attr( $view_args['action'] ); ?>">

<?php endif; ?>

<?php if ( isset( $view_args['options'] ) && ! empty( $view_args['subtitle'] ) && ! $error_message ) : ?>
	<p class="top-label"><?php echo esc_html( $view_args['subtitle'] ); ?></p>
<?php endif; ?>

<?php if ( isset( $view_args['options'] ) ) : ?>
	<div class="charitable-setting-dropdown-container <?php echo esc_attr( $view_args['wrapper_class'] ); ?>">
		<?php if ( ! $error_message ) { ?>
			<select id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); // phpcs:ignore ?>"
				name="<?php printf( 'charitable_settings[%s]', esc_attr( $select_name ) ); ?>"
				class="<?php echo esc_attr( $view_args['classes'] ); ?>"
				<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>
				>
				<?php
				foreach ( $view_args['options'] as $key => $option ) :
					if ( is_array( $option ) ) :
						$label = isset( $option['label'] ) ? $option['label'] : '';
						?>
						<optgroup label="<?php echo esc_attr( $label ); ?>">
						<?php foreach ( $option['options'] as $k => $opt ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $value ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach ?>
						</optgroup>
					<?php else : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $value ); ?>><?php echo esc_html( $option ); ?></option>
						<?php
					endif;
				endforeach
				?>
			</select>
		<?php } else { ?>
			<p><?php echo $error_message; // phpcs:ignore ?></p>
		<?php } ?>
		<?php endif; ?>
	</div>
	<?php if ( ! $error_message ) { ?>
	<div class="charitable-setting-file-container">

		<input type="file" id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); // phpcs:ignore ?>"
			name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>"
			class="<?php echo esc_attr( $view_args['classes'] ); ?>"
			<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>
			accept="<?php echo esc_attr( $accept ); ?>"
			/>
		<?php if ( $show_button ) : ?>
			<input class="button button-primary" type="submit" value="<?php echo esc_attr( $button_label ); ?>">
		<?php endif; ?>

	</div>
	<?php } ?>
	<?php if ( $thumbnail_preview ) : ?>
		<div class="charitable-setting-file-thumbnail-preview" style="max-height: 100px; display: flex; align-items: center; justify-content: left;">
			<img src="<?php echo esc_url( wp_get_attachment_url( $thumbnail_preview ) ); ?>" alt="<?php echo esc_attr( $view_args['name'] ); ?>" style="max-height: 100px; width: auto; height: auto; object-fit: contain;">
		</div>
		<?php
		// Add checkbox for removing the logo - if there is no remove button id, don't show the checkbox.
		if ( ! empty( $view_args['remove_button_id'] ) ) {
			$remove_checkbox_name = 'charitable_settings[' . $view_args['remove_button_id'] . ']';
			$remove_checkbox_id   = 'charitable_settings_' . $view_args['remove_button_id'];
			?>
			<div class="charitable-setting-remove-logo" style="margin-top: 10px;">
				<label>
					<input type="checkbox"
						name="<?php echo esc_attr( $remove_checkbox_name ); ?>"
						id="<?php echo esc_attr( $remove_checkbox_id ); ?>"
						value="1"
						<?php checked( isset( $_POST['charitable_settings'][ $view_args['remove_button_id'] ] ) && $_POST['charitable_settings'][ $view_args['remove_button_id'] ] ); // phpcs:ignore ?>
					>
					<?php echo esc_html( $remove_button_text ); ?>
				</label>
			</div>
		<?php } ?>
	<?php endif; ?>
<?php if ( $form_tag ) : ?>
	</form>
<?php endif; ?>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; // phpcs:ignore ?></div>
	<?php
endif;
