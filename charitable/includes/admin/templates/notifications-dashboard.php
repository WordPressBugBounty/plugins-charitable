<?php
/**
 * Admin Notifications template.
 *
 * @since 1.8.3
 *
 * @var array $notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_count           = intval( $args['notifications']['active_count'] );
$remaining_active_count = $active_count > 4 ? $active_count - 3 : 0;
$dismissed_count        = intval( $args['notifications']['dismissed_count'] );
$no_items_css           = ( $active_count > 0 ) ? 'charitable-hidden' : '';
$yes_items_css          = ( $active_count === 0 ) ? 'charitable-hidden' : '';
$notifications_title    = $active_count > 1 ? esc_html__( 'New Notifications', 'charitable' ) : esc_html__( 'New Notification', 'charitable' );

?>


<div class="charitable-container charitable-report-card charitable-dashboard-notifications">
	<div class="header">
		<?php if ( $active_count ) : ?>
			<h4>(<span id="new-notifications-count-dashboard"><?php echo esc_html( $active_count ); ?></span>) <?php echo esc_html( $notifications_title ); ?></h4>
		<?php else : ?>
			<h4><?php echo esc_html__( 'Notifications', 'charitable' ); ?></h4>
		<?php endif; ?>

		<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
	</div>
	<div class="charitable-toggle-container charitable-report-ui">
		<div class="no-items <?php echo esc_attr( $no_items_css ); ?>">
			<p><strong><?php echo esc_html__( 'There are currently no active notifications.', 'charitable' ); ?></strong></p>
			<p class="link charitable-view-notifications"><a href="#"><?php echo esc_html__( 'View Notifications', 'charitable' ); ?><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a></p>
		</div>
		<div class="the-list <?php echo esc_attr( $yes_items_css ); ?>">
			<?php echo $args['notifications']['active_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<div class="more">
		<?php if ( $remaining_active_count ) : ?>
			<a href="#"><?php wp_sprintf( 'You have %d more notifications', $remaining_active_count ); ?><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a>
		<?php elseif ( $active_count > 0 || $dismissed_count > 0 ) : ?>
			<a href="#"><?php esc_html_e( 'View Notifications', 'charitable' ); ?><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a>
		<?php endif; ?>
	</div>

</div>