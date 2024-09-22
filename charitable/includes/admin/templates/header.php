<?php
/**
 * Outputs the Charitable admin header.
 *
 * @package Charitable
 * @since   1.8.0
 * @since   1.8.0.3
 */

?>

<?php do_action( 'charitable_admin_before_header' ); ?>

<div id="charitable-admin-header" class="charitable-admin-header charitable-campaign-header">
	<div class="charitable-admin-header-interior">

		<h1 class="charitable-logo" id="charitable-logo">
			<a href="<?php echo admin_url( 'admin.php?page=charitable' ); ?>"><img src="<?php echo charitable()->get_path( 'directory', false ) . 'assets/images/charitable-header-logo.png'; ?>" alt="Charitable" width="200" height="38" /></a>
		</h1>
		<div class="charitable-header-logos">
			<ul>
				<?php /* <li><a href="#" title="<?php _e( 'Notifications', 'charitable' ); ?>" class="charitable-header-logo charitable-notification-inbox"><img src="<?php echo charitable()->get_path( 'directory', false ) . 'assets/images/icons/inbox.png'; ?>" alt="<?php _e( 'Notifications', 'charitable' ); ?>"" width="25" height="25" /></a></li> */ ?>
				<li><a href="<?php echo esc_url( charitable_help_link() ); ?>" target="_blank" title="<?php esc_html_e( 'Help', 'charitable' ); ?>" class="charitable-header-logo charitable-notification-help"><img src="<?php echo charitable()->get_path( 'directory', false ) . 'assets/images/icons/help.png'; ?>" alt="<?php esc_html_e( 'Help', 'charitable' ); ?>" width="25" height="25" /></a></li>
			</ul>
		</div>

	<?php do_action( 'charitable_admin_in_header' ); ?>

	</div>
</div>

<?php do_action( 'charitable_admin_after_header' ); ?>