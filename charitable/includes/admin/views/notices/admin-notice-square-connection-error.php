<?php
/**
 * Admin notice: Square connection error
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings_url = add_query_arg(
	array(
		'page'  => 'charitable-settings',
		'tab'   => 'gateways',
		'group' => 'gateways_square_core',
	),
	admin_url( 'admin.php' )
);

$message     = $view_args['message'];
$status_code = $view_args['status_code'];

if ( $status_code === '401' ) {
	$message = 'Charitable has been disconnected from your Square account. Please reconnect to continue accepting payments.';
}


?>

		<?php
		printf(
			wp_kses(
				/* translators: %1$s: error message, %2$s: opening link tag, %3$s: closing link tag */
				__( '<strong>Charitable:</strong> Important! %1$s %2$sReconnect to Square%3$s', 'charitable' ),
				array(
					'strong' => array(),
				)
			),
			esc_html( $message ),
			'<br><a href="' . esc_url( $settings_url ) . '" class="button button-primary charitable-admin-notice-square-connection-error" style="margin-top: 10px; background-color: #E89940; border-color: #E89940;">',
			'</a>'
		);
		?>
