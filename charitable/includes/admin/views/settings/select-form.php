<?php
/**
 * Display select field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.25
 */

$value = charitable_get_option( $view_args['key'] );

if ( false === $value ) {
	$value = isset( $view_args['default'] ) ? $view_args['default'] : '';
}

$form_action_url    = isset( $view_args['form_action_url'] ) ? $view_args['form_action_url'] : '';
$form_action_method = isset( $view_args['form_action_method'] ) ? $view_args['form_action_method'] : 'POST';
$button_label       = isset( $view_args['button_label'] ) ? $view_args['button_label'] : 'Submit';
$nonce_action_name  = isset( $view_args['nonce_action_name'] ) ? $view_args['nonce_action_name'] : 'wpcharitable-action';
$nonce_field_name   = isset( $view_args['nonce_field_name'] ) ? $view_args['nonce_field_name'] : false;
$error_message      = false;

// determine if the fields should be shown, based on what we are trying to do with them.
if ( isset( $view_args['nonce_action_name'] ) && 'export_campaign' === $view_args['nonce_action_name'] && is_array( $view_args['options'] ) && 0 === count( $view_args['options'] ) ) {
    $error_message = '<strong>' . __('You have no campaigns to export.', 'charitable' ) . '</strong>';
} else if ( isset( $view_args['nonce_action_name'] ) && 'export_donations_from_campaign' === $view_args['nonce_action_name'] && is_array( $view_args['options'] ) && 0 === count( $view_args['options'] ) ) {
	$error_message = '<strong>' . __('You have no donations to export.', 'charitable' ) . '</strong>';
}
?>
<form action="<?php echo $form_action_url; ?>" method="<?php echo $form_action_method; ?>">

	<?php wp_nonce_field( $nonce_action_name, $nonce_field_name ); ?>

	<?php if ( ! $error_message ) { ?>
		<select id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); ?>"
			name="<?php printf( 'charitable_settings[%s]', $view_args['name'] ); ?>"
			class="<?php echo esc_attr( $view_args['classes'] ); ?>"
			<?php echo charitable_get_arbitrary_attributes( $view_args ); ?>
			>
			<?php
			foreach ( $view_args['options'] as $key => $option ) :
				if ( is_array( $option ) ) :
					$label = isset( $option['label'] ) ? $option['label'] : '';
					?>
					<optgroup label="<?php echo $label; ?>">
					<?php foreach ( $option['options'] as $k => $opt ) : ?>
						<option value="<?php echo $k; ?>" <?php selected( $k, $value ); ?>><?php echo $opt; ?></option>
					<?php endforeach ?>
					</optgroup>
				<?php else : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $value ); ?>><?php echo $option; ?></option>
					<?php
				endif;
			endforeach
			?>
		</select>
		<input class="button button-primary" type="submit" value="<?php echo $button_label; ?>">
	<?php } else { ?>
        <p><?php echo $error_message; ?></p>
    <?php } ?>
</form>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; ?></div>
	<?php
endif;
