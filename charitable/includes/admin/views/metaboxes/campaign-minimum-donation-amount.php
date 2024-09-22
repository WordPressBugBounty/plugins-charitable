<?php
/**
 * Renders the end date field for the Campaign post type.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin Views/Metaboxes
 * @since   1.7.0.3
 * @version 1.6.53
 */

global $post;

$title 			= isset( $view_args['title'] ) 		? $view_args['title'] 	: '';

$tooltip 		= isset( $view_args['tooltip'] )	? '<span class="tooltip"> '. $view_args['tooltip'] . '</span>'	: '';
$description	= isset( $view_args['description'] )? '<span class="charitable-helper">' . $view_args['description'] . '</span>' 	: '';
$goal 			= get_post_meta( $post->ID, '_campaign_minimum_donation_amount', true );
$goal 			= ! $goal ? '' : charitable_format_money( $goal );
?>
<div id="charitable-campaign-min-donation-metabox-wrap" class="charitable-metabox-wrap">
    <h4><?php echo $title; ?></h4>
	<label class="screen-reader-text" for="campaign_minimum_donation_amount"><?php echo $title ?></label>
	<input type="text" id="campaign_minimum_donation_amount" name="_campaign_minimum_donation_amount"  placeholder="&#8734;" value="<?php echo $goal ?>" />
	<?php echo $description ?>
</div>
