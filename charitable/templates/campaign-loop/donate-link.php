<?php
/**
 * Displays the donate button to be displayed within campaign loops.
 *
 * Override this template by copying it to yourtheme/charitable/campaign-loop/donate-link.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.8.1.12
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* @var Charitable_Campaign */
$campaign = $view_args['campaign'];

if ( ! $campaign->can_receive_donations() ) :
	return;
endif;

$button_label = apply_filters( 'charitable_campaign_loop_donate_button_label', esc_html__( 'Donate', 'charitable' ), $campaign );

?>
<div class="<?php echo esc_attr( apply_filters( 'charitable_campaign_loop_donate_link_div_css', 'campaign-donation', $campaign ) ); ?>">
	<a class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"
		href="<?php echo charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $campaign->ID ) ); ?>"
		aria-label="<?php
		/* translators: %s: Campaign title */
		echo esc_attr( sprintf( _x( 'Make a donation to %s', 'make a donation to campaign', 'charitable' ), get_the_title( $campaign->ID ) ) );
		?>">
		<?php echo esc_html( $button_label ); ?>
	</a>
</div>
