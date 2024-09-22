<?php
/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/donate-link.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.6.29
 */

if ( ! array_key_exists( 'campaign', $view_args ) || ! is_a( $view_args['campaign'], 'Charitable_Campaign' ) ) :
	return;
endif;

$campaign = $view_args['campaign'];

if ( ! $campaign->can_receive_donations() ) :
	return;
endif;

$label = sprintf(
	/* translators: %s: campaign title */
	esc_attr_x( 'Make a donation to %s', 'make a donation to campaign', 'charitable' ),
	get_the_title( $campaign->ID )
);

?>
<div class="campaign-donation">
	<a href="#charitable-donation-form"
		class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"
		aria-label="<?php echo $label; ?>"
	>
	<?php _e( 'Donate', 'charitable' ); ?>
	</a>
</div>
