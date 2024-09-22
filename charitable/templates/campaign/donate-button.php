<?php
/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/donate-button.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.3.0
 * @version 1.7.0.9
 */

$campaign          = $view_args['campaign'];
$button_style      = array_key_exists( 'button_colour', $view_args ) ? 'style="background-color:' . $view_args['button_colour'] . ';"' : '';
$button_text       = array_key_exists( 'button_text', $view_args ) ? $view_args['button_text'] : __( 'Donate', 'charitable' );
$show_amount_field = array_key_exists( 'show_amount_field', $view_args ) && $view_args['show_amount_field'];

$button_text       = esc_html( get_post_meta( $campaign->ID, '_campaign_donate_button_text', true ) );
$button_text       = false === $button_text || '' === trim( $button_text ) ? __( 'Donate', 'charitable' ) : $button_text;

?>
<form class="campaign-donation" method="post">
	<?php wp_nonce_field( 'charitable-donate', 'charitable-donate-now' ); ?>
	<input type="hidden" name="charitable_action" value="start_donation" />
	<input type="hidden" name="campaign_id" value="<?php echo $campaign->ID; ?>" />
	<button type="submit" name="charitable_submit" class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"><?php echo wp_strip_all_tags( $button_text ); ?></button>
</form>
