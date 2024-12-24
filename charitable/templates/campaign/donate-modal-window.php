<?php
/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/donate-modal-window.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.6.57
 * @version 1.8.3.7
 */

$campaign = $view_args['campaign'];

if ( ! $campaign->can_receive_donations() ) :
	return;
endif;

$modal_class = apply_filters( 'charitable_modal_window_class', 'charitable-modal charitable-modal-donation' );

wp_enqueue_script( 'lean-modal' );
wp_enqueue_style( 'lean-modal-css' );

?>
<div id="charitable-donation-form-modal-<?php echo esc_attr( $campaign->ID ); ?>" style="display: none;" class="<?php echo esc_attr( $modal_class ); ?>">
	<a class="modal-close"></a>
	<?php $campaign->get_donation_form()->render(); ?>
</div>
