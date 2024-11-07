<?php
/**
 * Admin Dashboard template.
 *
 * @since 1.8.2
 *
 * @var array $notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $notifications ) || ! is_array( $notifications ) ) {
	return;
}

$notifications_count = 1;
$notifications_total = count( $notifications );


?>

<div class="charitable-container charitable-dashboard-notifications">

	<div class="charitable-dashboard-notification-bar">

			<?php if ( (int) $notifications_total > 1 ) : ?>
				<div class="charitable-dashboard-notification-navigation">
					<a class="prev">
						<span class="screen-reader-text"><?php esc_attr_e( 'Previous message', 'charitable' ); ?></span>
						<span aria-hidden="true">&lsaquo;</span>
					</a>
					<a class="next">
						<span class="screen-reader-text"><?php esc_attr_e( 'Next message', 'charitable' ); ?></span>
						<span aria-hidden="true">&rsaquo;</span>
					</a>
				</div>
			<?php else : ?>
				<div class="charitable-dashboard-notification-navigation"></div>
			<?php endif; ?>

			<a href="#" class="charitable-remove-dashboard-notification"></a>

		</div>

	<?php

	foreach ( $notifications as $notification_slug => $notification ) :

		$css_class     = ! empty( $notification['custom_css'] ) ? $notification['custom_css'] : '';
		$css_class    .= $notifications_count === 1 ? '' : ' charitable-hidden';
		$message_title = ! empty( $notification['title'] ) ? sanitize_text_field( $notification['title'] ) : esc_html__( 'Important', 'charitable' );
		$message       = ! empty( $notification['message'] ) ? $notification['message'] : '';
		$message       = wp_kses(
			$message,
			array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
				),
				'strong' => array(),
				'p'      => array(),
				'ol'     => array(),
				'ul'     => array(),
				'li'     => array(),
				'br'     => array(),
				'h1'     => array(),
				'h2'     => array(),
				'h3'     => array(),
				'h4'     => array(),
				'h5'     => array(),
			)
		);

		?>

			<div class="charitable-dashboard-notification <?php echo esc_attr( $css_class ); ?>" data-notification-number="<?php echo (int) $notifications_count; ?>" data-notification-id="<?php echo esc_attr( $notification_slug ); ?>" data-notification-type="<?php echo esc_attr( $notification['type'] ); ?>">

				<div class="charitable-dashboard-notification-message">
				<h4 class="charitable-dashboard-notification-headline"><?php echo esc_html( $message_title ); ?></h4>
					<?php echo $message; // phpcs:ignore ?>
				</div>

			</div>

		<?php

		++$notifications_count;

	endforeach;
	?>

</div>
