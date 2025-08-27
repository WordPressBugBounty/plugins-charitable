<?php
/**
 * Renders the campaign goal block in the settings metabox for the Campaign post type.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @package Charitable/Admin Views/Metaboxes
 */

global $post;

$title       = isset( $view_args['title'] ) ? $view_args['title'] : ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$tooltip     = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> ' . $view_args['tooltip'] . '</span>' : '';
$description = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . wp_kses_post( $view_args['description'] ) . '</span>' : '';
$goal        = get_post_meta( $post->ID, '_campaign_goal', true );
$goal        = ! $goal ? '' : charitable_format_money( $goal );
?>
<div id="charitable-campaign-goal-metabox-wrap" class="charitable-metabox-wrap">
	<label class="screen-reader-text" for="campaign_goal"><?php echo wp_kses_post( $title ); ?></label>
	<input type="text" id="campaign_goal" name="_campaign_goal"  placeholder="&#8734;" value="<?php echo esc_attr( $goal ); ?>" />
	<?php echo $description; // phpcs:ignore ?>
</div>
