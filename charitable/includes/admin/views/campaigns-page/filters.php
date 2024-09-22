<?php
/**
 * Display the date filters above the campaigns table.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Campaigns Page
 * @since   1.6.36
 * @version 1.8.0
 */

$filters = $_GET;

unset(
	$filters['post_type'],
	$filters['paged'],
	$filters['ids'],
	$filters['trashed'],
	$filters['post_status'],
	$filters['untrashed']
);

?>

<div class="alignleft actions charitable-export-actions charitable-campaign-filter-actions">
	<a href="#charitable-campaigns-filter-modal" title="<?php _e( 'Export', 'charitable' ); ?>" class="campaign-export-with-icon trigger-modal hide-if-no-js" data-trigger-modal><img src="<?php echo charitable()->get_path( 'directory', false ) . 'assets/images/icons/filter.svg'; ?>" alt="<?php _e( 'Filter', 'charitable' ); ?>"  /><label><?php _e( 'Filter', 'charitable' ); ?></label></a></li>
	<?php if ( count( $filters ) ) : ?>
		<a href="<?php echo esc_url_raw( add_query_arg( array( 'post_type' => Charitable::CAMPAIGN_POST_TYPE ), admin_url( 'edit.php' ) ) ); ?>" class="charitable-campaigns-clear button dashicons-before dashicons-clear"><?php _e( 'Clear Filters', 'charitable' ); ?></a>
	<?php endif ?>
</div>