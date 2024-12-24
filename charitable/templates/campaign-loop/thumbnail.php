<?php
/**
 * Displays the campaign thumbnail.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.8.2
 * @version 1.8.3.7 - added force_featured_thumbnail parameter/filter.
 */

$campaign                 = $view_args['campaign'];
$force_featured_thumbnail = apply_filters( 'charitable_campaign_loop_featured_thumbnail', empty( $view_args['force_featured_thumbnail'] ) ? false : true );

// determine if this is a legacy campaign, or a new campaign built with the visual editor.
if ( ! function_exists( 'charitable_is_campaign_legacy' ) ) :
	if ( has_post_thumbnail( $campaign->ID ) ) :
		echo get_the_post_thumbnail( $campaign->ID, apply_filters( 'charitable_campaign_loop_thumbnail_size', 'medium' ) );
	endif;
elseif ( charitable_is_campaign_legacy( $campaign ) || $force_featured_thumbnail ) :
		$thumbnail_id = get_post_thumbnail_id( $campaign->ID );
	if ( $thumbnail_id ) :
		echo wp_get_attachment_image( $thumbnail_id, apply_filters( 'charitable_campaign_loop_thumbnail_size', 'medium' ) );
		endif;
	return;
elseif ( function_exists( 'charitable_find_photo_in_campaign_settings' ) ) :
		$campaign_settings = get_post_meta( $campaign->ID, 'campaign_settings_v2', true );
		$media_info        = charitable_find_photo_in_campaign_settings( $campaign_settings );
	if ( ! empty( $media_info['media_id'] ) ) {
		$image_attributes = wp_get_attachment_image_src( $media_info['media_id'], apply_filters( 'charitable_campaign_loop_thumbnail_size', 'medium' ) );
		if ( ! empty( $image_attributes ) ) : ?>
				<img src="<?php echo esc_url( $image_attributes[0] ); ?>" width="<?php echo esc_html( $image_attributes[1] ); ?>" height="<?php echo esc_html( $image_attributes[2] ); ?>" />
				<?php
			endif;
	}
endif;
