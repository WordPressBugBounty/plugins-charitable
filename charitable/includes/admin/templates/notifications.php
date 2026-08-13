<?php
/**
 * Admin Notifications template.
 *
 * @since   1.8.3
 * @version 1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_active_count        = intval( $args['notifications']['active_count'] );
$charitable_dismissed_count     = intval( $args['notifications']['dismissed_count'] );
$charitable_notifications_title = esc_html__( 'Notifications', 'charitable' );

$charitable_notif_settings      = class_exists( 'Charitable_Notification_Settings' ) ? Charitable_Notification_Settings::get_instance()->get_settings() : array();
$charitable_notif_settings_json = wp_json_encode( $charitable_notif_settings );
$charitable_notif_categories    = array(
	'license'     => __( 'License &amp; Account', 'charitable' ),
	'lifecycle'   => __( 'Campaign Lifecycle', 'charitable' ),
	'milestone'   => __( 'Milestones', 'charitable' ),
	'health'      => __( 'Health &amp; Data Alerts', 'charitable' ),
	'legal'       => __( 'Legal &amp; Compliance', 'charitable' ),
	'nudge'       => __( 'Engagement Nudges', 'charitable' ),
	'new_feed'    => __( 'Announcements', 'charitable' ),
	'legacy_feed' => __( 'News &amp; Updates', 'charitable' ),
);

?>

<div class="charitable-plugin-notifications" id="charitable-plugin-notifications">
	<div class="notification-menu">
		<div class="notification-header">
			<span class="new-notifications notifications-visible">
				<?php echo esc_html( $charitable_notifications_title ); ?>
				<span id="new-notifications-count" class="notif-count-pill"><?php echo intval( $charitable_active_count ); ?></span>
			</span>
			<span class="old-notifications">
				(<span id="dismissed-notifications-count"><strong><?php echo intval( $charitable_dismissed_count ); ?></strong></span>)
				<?php esc_attr_e( 'Dismissed Notifications', 'charitable' ); ?>
			</span>
			<div class="dismissed-notifications">
				<a href="#" data-status="dismissed"><?php esc_attr_e( 'Dismissed Notifications', 'charitable' ); ?></a>
			</div>
			<div class="notification-header-actions">
				<button class="charitable-notif-gear-btn" id="charitable-notif-gear-btn" title="<?php esc_attr_e( 'Notification Settings', 'charitable' ); ?>" aria-expanded="false">
					<svg viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/>
					</svg>
				</button>
				<button class="charitable-notif-close-btn" aria-label="<?php esc_attr_e( 'Close notifications', 'charitable' ); ?>">
					<svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-close" width="12" height="12">
						<path d="M11.8211 1.3415L10.6451 0.166504L5.98305 4.82484L1.32097 0.166504L0.14502 1.3415L4.80711 5.99984L0.14502 10.6582L1.32097 11.8332L5.98305 7.17484L10.6451 11.8332L11.8211 10.6582L7.159 5.99984L11.8211 1.3415Z" fill="currentColor"/>
				</svg>
				</button>
			</div>
		</div>

		<div class="charitable-notif-settings-panel" id="charitable-notif-settings-panel" style="display:none;" data-settings="<?php echo esc_attr( $charitable_notif_settings_json ); ?>">
			<p class="charitable-notif-settings-label"><?php esc_html_e( 'Show notification types:', 'charitable' ); ?></p>
			<div class="charitable-notif-toggle-grid">
				<?php foreach ( $charitable_notif_categories as $charitable_cat_key => $charitable_cat_label ) : ?>
					<label class="charitable-notif-toggle-row">
						<span class="charitable-notif-toggle">
							<input
								type="checkbox"
								name="notif_setting_<?php echo esc_attr( $charitable_cat_key ); ?>"
								data-category="<?php echo esc_attr( $charitable_cat_key ); ?>"
								<?php checked( isset( $charitable_notif_settings[ $charitable_cat_key ] ) ? $charitable_notif_settings[ $charitable_cat_key ] : true, true ); ?>
							>
							<span class="charitable-notif-toggle-slider"></span>
						</span>
						<span class="charitable-notif-toggle-label"><?php echo wp_kses_post( $charitable_cat_label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="charitable-notification-cards notification-cards notification-cards-active notification-cards-visible">
			<?php if ( empty( $args['notifications']['active_html'] ) ) : ?>
				<div aria-expanded="true" class="charitable-notification charitable-notification-intro current">
					<div class="charitable-notification-card-inner">
						<div class="notif-left">
							<div class="icon">
								<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-icon-intro" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
									<path d="M18 16v-5a6 6 0 1 0-12 0v5l-1.7 1.7a1 1 0 0 0 .7 1.7h14a1 1 0 0 0 .7-1.7L18 16z"/>
									<path d="M10 20a2 2 0 0 0 4 0"/>
								</svg>
							</div>
						</div>
						<div class="body">
							<h4><?php esc_html_e( 'Welcome to your notifications center', 'charitable' ); ?></h4>
							<div class="notification-content">
								<?php
								$charitable_inline_gear = '<svg class="charitable-notif-inline-gear" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" width="12" height="12" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/></svg>';
								printf(
									'<p>%s</p>',
									sprintf(
										/* translators: %s: inline gear icon */
										esc_html__( 'Product announcements, campaign milestones, license status, and action items will appear here as they happen. Use the %s gear icon above to choose which categories you want to see.', 'charitable' ),
										$charitable_inline_gear // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									)
								);
								?>
							</div>
						</div>
					</div>
				</div>
				<div aria-expanded="true" class="charitable-notification charitable-notification-empty current">
					<div class="charitable-notification-card-inner">
						<div class="notif-left">
							<div class="icon">
								<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-icon-empty" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
									<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
									<polyline points="22 4 12 14.01 9 11.01"/>
								</svg>
							</div>
						</div>
						<div class="body">
							<h4><?php esc_html_e( "You're all caught up!", 'charitable' ); ?></h4>
							<div class="notification-content">
								<p><?php esc_html_e( 'New notifications will appear here when they arrive.', 'charitable' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			<?php else : ?>
				<?php echo $args['notifications']['active_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<div class="charitable-notification-cards notification-cards notification-cards-dismissed">
			<?php if ( empty( $args['notifications']['dismissed_html'] ) ) : ?>
				<div class="notification-card">
					<div class="notification-card-content">
						<span class="notification-no-dismissed-title"><?php esc_attr_e( 'No dismissed notifications.', 'charitable' ); ?></span>
					</div>
				</div>
			<?php else : ?>
				<?php echo $args['notifications']['dismissed_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<div class="notification-footer">
			<?php if ( $charitable_active_count > 0 ) : ?>
				<div class="dismiss-all"><a href="#" class="dismiss"><?php esc_attr_e( 'Dismiss All', 'charitable' ); ?></a></div>
			<?php endif; ?>
		</div>
	</div>
	<div class="charitable-notifications-overlay"></div>
</div>
