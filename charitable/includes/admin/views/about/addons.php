<?php
/**
 * Display the Addons section of About tab.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/About
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 * @version   1.8.7.6
 */

if ( ! charitable_current_user_can( 'manage_charitable_settings' ) ) {
	return;
}

// Ensure functions are loaded.
if ( ! function_exists( 'charitable_get_am_plugins' ) || ! function_exists( 'charitable_get_plugin_data' ) ) {
	return;
}

$all_plugins          = get_plugins();
$am_plugins           = charitable_get_am_plugins();
$can_install_plugins  = charitable_can_install( 'plugin' );
$can_activate_plugins = charitable_can_activate( 'plugin' );

?>
<div id="charitable-admin-addons">
	<div class="addons-container">
		<?php
		foreach ( $am_plugins as $plugin => $details ) :

			$plugin_data              = charitable_get_plugin_data( $plugin, $details, $all_plugins );
			$plugin_ready_to_activate = $can_activate_plugins
				&& isset( $plugin_data['status_class'] )
				&& $plugin_data['status_class'] === 'status-installed';
			$plugin_not_activated     = ! isset( $plugin_data['status_class'] )
				|| $plugin_data['status_class'] !== 'status-active';

			?>
			<div class="addon-container">
				<div class="addon-item">
					<div class="details charitable-clear">
						<img src="<?php echo esc_url( $plugin_data['details']['icon'] ); ?>" alt="<?php echo esc_attr( $plugin_data['details']['name'] ); ?>">
						<h5 class="addon-name">
							<?php echo esc_html( $plugin_data['details']['name'] ); ?>
						</h5>
						<p class="addon-desc">
							<?php echo wp_kses_post( $plugin_data['details']['desc'] ); ?>
						</p>
					</div>
					<div class="actions charitable-clear">
						<div class="status">
							<strong>
								<?php
								printf( /* translators: %s - status label. */
									esc_html__( 'Status: %s', 'charitable' ),
									'<span class="status-label ' . esc_attr( $plugin_data['status_class'] ) . '">' . wp_kses_post( $plugin_data['status_text'] ) . '</span>'
								);
								?>
							</strong>
						</div>
						<div class="action-button">
							<?php if ( $can_install_plugins || $plugin_ready_to_activate || ! $details['wporg'] ) { ?>
								<button class="<?php echo esc_attr( $plugin_data['action_class'] ); ?>" data-plugin="<?php echo esc_attr( $plugin_data['plugin_src'] ); ?>" data-type="plugin">
									<?php echo wp_kses_post( $plugin_data['action_text'] ); ?>
								</button>
							<?php } elseif ( $plugin_not_activated ) { ?>
								<a href="<?php echo esc_url( $details['wporg'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'WordPress.org', 'charitable' ); ?>
									<span aria-hidden="true" class="dashicons dashicons-external"></span>
								</a>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
