<?php
/**
 * Renders the Campaign Page Settings metabox.
 *
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @package   Charitable/Admin Views/Metaboxes
 * @since     1.2.0
 * @version   1.7.0.9
 */

global $post, $wpdb;

$description = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . $view_args['description'] . '</span>' : '';

$_campaign_donate_button_text = wp_strip_all_tags( get_post_meta( $post->ID, '_campaign_donate_button_text', true ) );
$_campaign_donate_button_text = trim( $_campaign_donate_button_text ) === '' ? false : $_campaign_donate_button_text;

?>
<div id="charitable-campaign-page-settings-metabox-wrap" class="charitable-metaboxx-wrap">
	<h4 style="margin-top: 0;"><?php _e( 'Hide Information', 'charitable' ); ?></h4>
	<div class="charitable-metabox" style="width: 100%">
		<?php

		$fields = array( 'Amount Donated', 'Number of Donors', 'Percent Raised', 'Time Remaining' );
		foreach ( $fields as $field ) :

			$santitized_field = strtolower( str_replace( ' ', '_', $field ) );
			$meta_field       = ( get_post_meta( $post->ID, '_campaign_hide_' . $santitized_field, true ) );

			?>
		<div style="display: inline-block; width: 24%;">
		<ul style="padding: 0 10px;">
				<?php
				$checked = ( false !== $meta_field && is_countable( $meta_field ) && count( $meta_field ) > 0 && in_array( 'hide_' . $santitized_field, $meta_field, true ) ) ? 'checked="checked"' : false;
				?>
				<li><input type="checkbox" <?php echo $checked; ?> name="_campaign_hide_<?php echo $santitized_field; ?>[]" value="hide_<?php echo $santitized_field; ?>" id="_campaign_hide_<?php echo $santitized_field; ?>_<?php echo $santitized_field; ?>" /><label for="_campaign_hide_<?php echo $santitized_field; ?>_<?php echo $santitized_field; ?>"><?php _e( $field ); ?></label></li>
		</ul>
		</div>
		<?php endforeach; ?>

	</div>
</div>

<div id="charitable-campaign-min-donation-metabox-wrap" class="charitable-metabox-wrap">
	<h4><?php _e( 'Donation Button Text:', 'charitable' ); ?></h4>
	<label class="screen-reader-text" for="campaign_minimum_donation_amount"><?php _e( 'Donate', 'charitable' ); ?></label>
	<input type="text" id="campaign_donate_button_text" name="_campaign_donate_button_text"  placeholder="<?php _e( 'Donate' ); ?>" value="<?php echo esc_html( $_campaign_donate_button_text ); ?>" />
	<?php echo $description; ?>
</div>

