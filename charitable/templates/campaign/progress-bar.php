<?php
/**
 * Displays the campaign progress bar.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/progress-bar.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$campaign = $view_args['campaign'];

if ( ! $campaign->has_goal() ) :
	return;
endif;

?>
<div class="campaign-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $campaign->get_percent_donated_raw(); // phpcs:ignore ?>"><span class="bar" style="width: <?php echo $campaign->get_percent_donated_raw(); // phpcs:ignore ?>%;"></span></div>
