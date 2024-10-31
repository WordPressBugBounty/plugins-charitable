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
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prior the description output as $view_args['campaign']->description but since 1.7.0.4 as visual editor can add html.
$campaign          = isset( $view_args['campaign'] ) ? $view_args['campaign'] : false;
$description       = isset( $view_args['description'] ) ? $view_args['description'] : false;
$description_limit = isset( $view_args['view_args']['description_limit'] ) ? $view_args['view_args']['description_limit'] : 100;

if ( $description === false ) {
	$description = isset( $view_args['campaign'] ) && ! empty( $view_args['campaign']->description ) ? $view_args['campaign']->description : $description;
	// If there still is no description, try to get it from the meta data (legacy).
	if ( ( false === $description || '' === $description ) && false !== $campaign && is_object( $campaign ) && is_a( $campaign, 'Charitable_Campaign' ) ) {
		$description = get_post_meta( $campaign->get_campaign_id(), '_campaign_description', true );
		if ( ( false === $description || '' === $description ) && isset( $campaign->post->post_content ) && ! empty( $campaign->post->post_content ) ) {
			$description = $campaign->post->post_content;
		}
	}
	$description = $description_limit > 0 ? wp_trim_words( $description, $description_limit, '...' ) : '';
}

?>
<div class="campaign-description">
	<?php echo apply_filters( 'charitable_campaign_description_template_content', $description, $campaign ); ?>
</div>
