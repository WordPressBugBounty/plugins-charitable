<?php
/**
 * Displays the amount of time left in the campaign.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/summary-time-left.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.0.0
 */

$campaign = $view_args['campaign'];

?>
<div class="campaign-time-left campaign-summary-item">
	<?php echo wp_kses_post( $campaign->get_time_left() ); ?>
</div>
