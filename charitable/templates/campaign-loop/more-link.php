<?php
/**
 * Displays the donate button to be displayed within campaign loops.
 *
 * Override this template by copying it to yourtheme/charitable/campaign-loop/more-link.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.2.3
 * @version 1.6.29
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* @var Charitable_Campaign */
$campaign = $view_args['campaign'];

?>
<p>
	<a class="<?php echo esc_attr( charitable_get_button_class( 'read-more' ) ); ?>"
		href="<?php echo get_permalink( $campaign->ID ); ?>"
		aria-label="<?php echo esc_attr( sprintf( _x( 'Continue reading about %s', 'Continue reading about campaign', 'charitable' ), get_the_title( $campaign->ID ) ) ); ?>"
	>
		<?php _e( 'Read More', 'charitable' ); ?>
	</a>
</p>
