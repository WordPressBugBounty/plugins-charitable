<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.35
 * @version   1.8.8.6
 * @version   1.8.15
 */

global $post;

$charitable_logs             = charitable_get_donation( $post->ID )->log()->get_log();
$charitable_date_time_format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );

/**
 * Log messages are written by gateways and webhooks and can contain values that
 * originated in a donor-submitted request, so they must never be echoed raw.
 *
 * Some legitimate log entries intentionally carry markup -- the email logs append
 * a "Resend it now" link, and the Stripe and PayPal Commerce handlers append links
 * to the error documentation and the gateway dashboard -- so the messages are run
 * through a narrow allowlist rather than escaped outright. This also neutralises
 * any payload that was stored by an earlier version of the plugin.
 *
 * @since 1.8.15
 */
$charitable_log_allowed_html = array(
	'a'      => array(
		'href'   => true,
		'title'  => true,
		'target' => true,
		/*
		 * `rel` is deliberately NOT allowed. The gateway log messages that open a
		 * dashboard link set only `target`, and permitting an attacker-chosen `rel`
		 * would let a planted link carry `rel="opener"`, which opts the new tab back
		 * in to `window.opener` and defeats the browser's implicit `noopener`.
		 */
	),
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
	'code'   => array(),
	'br'     => array(),
	'span'   => array(),
);
?>
<div id="charitable-donation-log-metabox" class="charitable-metabox">
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date &amp; Time', 'charitable' ); ?></th>
				<th><?php esc_html_e( 'Log', 'charitable' ); ?></th>
			</th>
		</thead>
		<?php foreach ( $charitable_logs as $charitable_log ) : ?>
			<?php
			/*
			 * Entries are not guaranteed to be well formed. They can arrive from the
			 * donation importer, which unserializes `_donation_log` straight out of an
			 * uploaded file, or from the `charitable_donation_logs` filter. A non-string
			 * message makes wp_kses() throw a TypeError, and a non-numeric time makes
			 * gmdate() throw, either of which would take down the whole edit screen.
			 *
			 * @since 1.8.15
			 */
			$charitable_log_message = isset( $charitable_log['message'] ) && is_string( $charitable_log['message'] ) ? $charitable_log['message'] : '';
			$charitable_log_time    = isset( $charitable_log['time'] ) && is_numeric( $charitable_log['time'] ) ? (int) $charitable_log['time'] : 0;
			?>
		<tr>
			<td><?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $charitable_log_time ), $charitable_date_time_format ) ); ?></td>
			<td><?php echo wp_kses( $charitable_log_message, $charitable_log_allowed_html ); ?></td>
		</tr>
		<?php endforeach ?>
	</table>
</div>
