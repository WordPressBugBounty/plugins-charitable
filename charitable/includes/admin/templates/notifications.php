<?php
/**
 * Admin Notifications template.
 *
 * @since 1.7.0.3
 *
 * @var array $notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="charitable-notifications">
	<div class="charitable-notifications-header">
		<div class="charitable-notifications-bell">
			<svg xmlns="http://www.w3.org/2000/svg" width="15" height="17" fill="none">
				<path fill="#777" d="M7.68 16.56c1.14 0 2.04-.95 2.04-2.17h-4.1c0 1.22.9 2.17 2.06 2.17Zm6.96-5.06c-.62-.71-1.81-1.76-1.81-5.26A5.32 5.32 0 0 0 8.69.97H6.65A5.32 5.32 0 0 0 2.5 6.24c0 3.5-1.2 4.55-1.81 5.26a.9.9 0 0 0-.26.72c0 .57.39 1.08 1.04 1.08h12.38c.65 0 1.04-.5 1.07-1.08 0-.24-.1-.51-.3-.72Z"/>
			</svg>
			<span class="wp-ui-notification charitable-notifications-circle"></span>
		</div>
		<div class="charitable-notifications-title"><?php esc_html_e( 'Notifications', 'charitable' ); ?></div>
	</div>

	<div class="charitable-notifications-body">

		<button type="button" class="notice-dismiss dismiss">
			<span class="screen-reader-text">
				<?php esc_attr_e( 'Dismiss this notice', 'charitable' ); ?>
			</span>
		</button>

		<?php if ( (int) $notifications['count'] > 1 ) : ?>
			<div class="navigation">
				<a class="prev">
					<span class="screen-reader-text"><?php esc_attr_e( 'Previous message', 'charitable' ); ?></span>
					<span aria-hidden="true">&lsaquo;</span>
				</a>
				<a class="next">
					<span class="screen-reader-text"><?php esc_attr_e( 'Next message', 'charitable' ); ?></span>
					<span aria-hidden="true">&rsaquo;</span>
				</a>
			</div>
		<?php endif; ?>

		<div class="charitable-notifications-messages">
			<?php echo $notifications['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</div>
