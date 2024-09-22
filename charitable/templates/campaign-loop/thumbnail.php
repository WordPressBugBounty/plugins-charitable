<?php
/**
 * Displays the campaign thumbnail.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.0.0
 */

$campaign = $view_args['campaign'];

if ( has_post_thumbnail( $campaign->ID ) ) :
	echo get_the_post_thumbnail( $campaign->ID, apply_filters( 'charitable_campaign_loop_thumbnail_size', 'medium' ) );
endif;
