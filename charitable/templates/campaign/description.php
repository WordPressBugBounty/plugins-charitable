<?php
/**
 * Displays the campaign description.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/description.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.8.1.14
 * @version 1.8.3
 * @version 1.8.8.6
 * @version 1.8.12.1 Add charitable_campaign_description_limit filter.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prior the description output as $view_args['campaign']->description but since 1.7.0.4 as visual editor can add html.
$charitable_campaign          = isset( $view_args['campaign'] ) ? $view_args['campaign'] : false;
$charitable_description       = isset( $view_args['description'] ) ? $view_args['description'] : false;
$charitable_description_limit = isset( $view_args['view_args']['description_limit'] ) ? $view_args['view_args']['description_limit'] : 100;

/**
 * Filter the number of words the campaign description is trimmed to on the campaign page.
 *
 * @since 1.8.12.1
 *
 * @param int                       $charitable_description_limit Number of words to keep.
 * @param Charitable_Campaign|false $charitable_campaign          The campaign object (may be false).
 * @param array                     $view_args                    The template view arguments.
 */
$charitable_description_limit = (int) apply_filters( 'charitable_campaign_description_limit', $charitable_description_limit, $charitable_campaign, $view_args );

if ( $charitable_description === false ) {
	$charitable_description = isset( $view_args['campaign'] ) && ! empty( $view_args['campaign']->description ) ? $view_args['campaign']->description : $charitable_description;
	// If there still is no description, try to get it from the meta data (legacy).
	if ( ( false === $charitable_description || '' === $charitable_description ) && false !== $charitable_campaign && is_object( $charitable_campaign ) && is_a( $charitable_campaign, 'Charitable_Campaign' ) ) {
		$charitable_description = get_post_meta( $charitable_campaign->get_campaign_id(), '_campaign_description', true );
		if ( ( false === $charitable_description || '' === $charitable_description ) && isset( $charitable_campaign->post->post_content ) && ! empty( $charitable_campaign->post->post_content ) ) {
			$charitable_description = $charitable_campaign->post->post_content;
		}
	}
	$charitable_description = $charitable_description_limit > 0 ? wp_trim_words( $charitable_description, $charitable_description_limit, '...' ) : '';
}

?>
<div class="campaign-description">
	<?php echo apply_filters( 'charitable_campaign_description_template_content', $charitable_description, $charitable_campaign ); // phpcs:ignore ?>
</div>
