<?php
/**
 * Renders the campaign description field for the Campaign post type.
 *
 * @author 	WP Charitable LLC
 * @since   1.0.0
 * @package Charitable/Admin Views/Metaboxes
 */

global $post;

$title 					= isset( $view_args['title'] ) 		? $view_args['title'] 	: '';
$tooltip 				= isset( $view_args['tooltip'] )	? '<span class="tooltip"> '. $view_args['tooltip'] . '</span>'	: '';
$campaign_description	= get_post_meta( $post->ID, '_campaign_description', true ); // esc_textarea was removed

$textarea_name      = 'content';
$textarea_rows      = apply_filters( 'charitable_campaign_description_rows', 15 );
$textarea_tab_index = isset( $view_args['tab_index'] ) ? $view_args['tab_index'] : 0;

wp_editor( $campaign_description, '_charitable_campaign_description', array(
	'textarea_name' => '_campaign_description',
	'textarea_rows' => $textarea_rows,
	'tabindex'      => $textarea_tab_index,
) );

// $textarea_name      = 'content';
// $textarea_rows      = apply_filters( 'charitable_extended_description_rows', 40 );
// $textarea_tab_index = isset( $view_args['tab_index'] ) ? $view_args['tab_index'] : 0;

// wp_editor( $post->post_content, 'charitable-extended-description', array(
// 	'textarea_name' => 'post_content',
// 	'textarea_rows' => $textarea_rows,
// 	'tabindex'      => $textarea_tab_index,
// ) );

/*
<div id="charitable-campaign-description-metabox-wrap" class="charitable-metabox-wrap">
	<label class="screen-reader-text" for="campaign_description"><?php echo $campaign_description ?></label>
	<textarea name="_campaign_description" id="campaign_description" tabindex="" rows="10" placeholder="<?php _e( 'Enter a short description of your campaign', 'charitable' ); ?>"><?php echo $campaign_description ?></textarea>
</div>
*/

?>