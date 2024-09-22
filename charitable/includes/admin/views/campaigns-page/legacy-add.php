<?php
/**
 * Display the legacy add button in the campaign filters box.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Campaigns Page
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 * @version   1.8.1.10
 */

if ( ! empty( $_GET['post_status'] ) && 'trash' === $_GET['post_status'] ) {
	return;
}

// check the advanced setting for disabled legacy campaigns.
$disable_legacy_campaign = charitable_get_option( 'disable_campaign_legacy_mode', false ) ? true : false;
$disable_legacy_campaign = apply_filters( 'charitable_disable_legacy_campaign', $disable_legacy_campaign );

if ( $disable_legacy_campaign ) {
	return;
}
?>
<div class="alignleft actions charitable-legacy-actions charitable-campaign-legacy-actions">
	<a href="<?php echo admin_url( 'post-new.php?post_type=campaign' ); ?>" title="<?php _e( 'Create A New Legacy Campaign', 'charitable' ); ?>" class="campaign-export-with-icon trigger-modal hide-if-no-js" data-trigger-modal><img src="<?php echo charitable()->get_path( 'directory', false ) . 'assets/images/icons/add.svg'; ?>" alt="<?php _e( 'Create A New Legacy Campaign', 'charitable' ); ?>"  /><label><?php _e( 'Add Legacy', 'charitable' ); ?></label></a>
</div>
