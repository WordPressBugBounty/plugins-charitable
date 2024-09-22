<?php
/**
 * Display the export button in the donation filters box.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Donations Page
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<?php /* <div class="alignleft actions charitable-export-actions charitable-donation-export-actions">
	<a href="#charitable-donations-export-modal" class="charitable-export-button buttonx dashicons-before dashicons-download trigger-modal hide-if-no-js" data-trigger-modal><?php _e( 'Export', 'charitable' ); ?></a>
</div> */ ?>


<div class="alignleft actions charitable-export-actions charitable-donation-export-actions">
	<a href="#charitable-donations-export-modal" title="<?php _e( 'Export', 'charitable' ); ?>" class="donation-export-with-icon trigger-modal hide-if-no-js" data-trigger-modal><img src="<?php echo charitable()->get_path( 'directory', false ) . 'assets/images/icons/export.svg'; ?>" alt="<?php _e( 'Export', 'charitable' ); ?>"  /><label><?php _e( 'Export', 'charitable' ); ?></label></a>
</div>
