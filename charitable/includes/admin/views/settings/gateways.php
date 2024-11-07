<?php
/**
 * Display the table of payment gateways.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.38
 * @version   1.8.0
 * @version   1.8.1.10 - Cleanup
 * @version   1.8.2 - Add Stripe Warning
 */

$helper   = charitable_get_helper( 'gateways' );
$gateways = $helper->get_available_gateways();
$default  = $helper->get_default_gateway();
$upgrades = $helper->get_recommended_gateways();

// Add a warning message here if Stripe is an enabled gateway, but no keys are found.

if ( class_exists( 'Charitable_Gateway_Stripe_AM' ) ) {

	if ( ( ! defined( 'CHARITABLE_DISABLE_STRIPE_KEY_CHECK' ) || ! CHARITABLE_DISABLE_STRIPE_KEY_CHECK ) && is_array( $gateways ) && ! empty( $gateways ) && in_array( 'Charitable_Gateway_Stripe_AM', $gateways, true ) && $helper->is_active_gateway( 'Charitable_Gateway_Stripe_AM' ) ) {
		$stripe_gateway = new Charitable_Gateway_Stripe_AM();
		$stripe_keys    = $stripe_gateway->get_keys();

		if ( ! isset( $stripe_keys['public_key'] ) || empty( $stripe_keys['public_key'] ) ) {
			// Display the warning message HTML.
			/* translators: %s: URL to the Stripe settings page */
			echo '<div class="charitable-settings-notice charitable-stripe-key-notice"> ' . ( sprintf( __( '<strong>Note:</strong> Stripe is enabled but it does not appear to be connected or API keys are missing. <a href="%s">Confirm Stripe settings to keep using this gateway</a>.', 'charitable' ), admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' ) ) ) . '</div>';
		}

	}
}

foreach ( $gateways as $gateway ) :

	$gateway   = new $gateway();
	$is_active = $helper->is_active_gateway( $gateway->get_gateway_id() );

	if ( $is_active ) {
		$action_url  = esc_url(
			add_query_arg(
				array(
					'charitable_action' => 'disable_gateway',
					'gateway_id'        => $gateway->get_gateway_id(),
					'_nonce'            => wp_create_nonce( 'gateway' ),
				),
				admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
			)
		);
		$action_text = __( 'Disable Gateway', 'charitable' );
	} else {
		$action_url  = esc_url(
			add_query_arg(
				array(
					'charitable_action' => 'enable_gateway',
					'gateway_id'        => $gateway->get_gateway_id(),
					'_nonce'            => wp_create_nonce( 'gateway' ),
				),
				admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
			)
		);
		$action_text = __( 'Enable Gateway', 'charitable' );
	}

	$action_url = esc_url(
		add_query_arg(
			array(
				'charitable_action' => $is_active ? 'disable_gateway' : 'enable_gateway',
				'gateway_id'        => $gateway->get_gateway_id(),
				'_nonce'            => wp_create_nonce( 'gateway' ),
			),
			admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
		)
	);

	$make_default_url = esc_url(
		add_query_arg(
			array(
				'charitable_action' => 'make_default_gateway',
				'gateway_id'        => $gateway->get_gateway_id(),
				'_nonce'            => wp_create_nonce( 'gateway' ),
			),
			admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
		)
	);

	$gateway_name = null !== $gateway->get_name() ? $gateway->get_name() : '';

	?>
	<div class="charitable-settings-object charitable-gateway cf <?php echo esc_attr( strtolower( $gateway_name ) ); ?>" style="position: relative;">
		<?php if ( $gateway->get_badge() ) : ?>
		<span class="gateway-logo"><svg width="49" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.4718 10.3338c0-3.41791-1.6696-6.11484-4.8607-6.11484-3.2045 0-5.1434 2.69693-5.1434 6.08814 0 4.0187 2.289 6.048 5.5743 6.048 1.6023 0 2.8141-.3604 3.7296-.8678v-2.6702c-.9155.4539-1.9658.7343-3.2987.7343-1.3061 0-2.464-.4539-2.6121-2.0294h6.5841c0-.1735.0269-.8678.0269-1.1882Zm-6.6514-1.26838c0-1.50868.929-2.13618 1.7773-2.13618.8213 0 1.6965.6275 1.6965 2.13618h-3.4738Zm-8.5499-4.84646c-1.3195 0-2.1678.61415-2.639 1.04139l-.1751-.82777h-2.9621V20l3.3661-.7076.0134-3.7784c.4847.3471 1.1984.8411 2.3832.8411 2.4102 0 4.6048-1.9225 4.6048-6.1548-.0134-3.87186-2.235-5.98134-4.5913-5.98134Zm-.8079 9.19894c-.7944 0-1.2656-.2804-1.5888-.6275l-.0134-4.95328c.35-.38719.8348-.65421 1.6022-.65421 1.2253 0 2.0735 1.36182 2.0735 3.11079 0 1.7891-.8347 3.1242-2.0735 3.1242Zm-9.6001-9.98666 3.3796-.72096V0l-3.3796.70761v2.72363Zm0 1.01469h3.3796V16.1282h-3.3796V4.44593Zm-3.6219.98798-.2154-.98798h-2.9083V16.1282h3.3661V8.21095c.7944-1.02804 2.1408-.84112 2.5582-.69426V4.44593c-.4309-.16022-2.0062-.45394-2.8006.98798Zm-6.7322-3.88518-3.2853.69426-.01346 10.69421c0 1.976 1.49456 3.4313 3.48726 3.4313 1.1041 0 1.912-.2003 2.3563-.4406v-2.7103c-.4309.1736-2.5583.7877-2.5583-1.1882V7.28972h2.5583V4.44593h-2.5583l.0135-2.8972ZM3.40649 7.83712c0-.5207.43086-.72096 1.14447-.72096 1.0233 0 2.31588.30707 3.33917.85447V4.83311c-1.11755-.44059-2.22162-.61415-3.33917-.61415C1.81769 4.21896 0 5.63418 0 7.99733c0 3.68487 5.11647 3.09747 5.11647 4.68627 0 .6141-.53858.8144-1.29258.8144-1.11755 0-2.54477-.4539-3.675782-1.0681v3.1776c1.252192.534 2.517842.761 3.675782.761 2.80059 0 4.72599-1.3752 4.72599-3.765-.01346-3.97867-5.14339-3.27106-5.14339-4.76638Z" fill="#635BFF"/></svg></span>
		<?php else : ?>
		<h4 style="padding-left: 20px;"><?php echo esc_html( $gateway->get_name() ); ?></h4>
		<?php endif; ?>

		<?php if ( $gateway->get_recommended() ) : ?>
			<span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-green charitable-badge-rounded"><i class="fa fa-star" aria-hidden="true"></i><?php esc_html_e( 'Recommended', 'charitable' ); ?></span>
		<?php endif ?>

		<?php if ( (string) $gateway->get_gateway_id() === (string) $default ) : ?>

			<span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-orange charitable-badge-rounded"><i class="fa fa-check" aria-hidden="true"></i><?php esc_html_e( 'Default Gateway', 'charitable' ); ?></span>

		<?php elseif ( $is_active ) : ?>

			<a href="<?php echo esc_url( $make_default_url ); ?>" class="make-default-gateway"><i class="fa fa-check" aria-hidden="true"></i><?php esc_html_e( 'Make default', 'charitable' ); ?></a>

		<?php endif ?>
		<span class="actions">
			<?php
			if ( $is_active ) :
				$settings_url = esc_url(
					add_query_arg(
						array(
							'group' => 'gateways_' . $gateway->get_gateway_id(),
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
					)
				);
				?>

				<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary"><?php esc_html_e( 'Gateway Settings', 'charitable' ); ?></a>
			<?php endif ?>
			<a href="<?php echo $action_url; ?>" class="button"><?php echo $action_text; ?></a>
		</span>
	</div>
<?php endforeach ?>
<?php
if ( ! empty( $upgrades ) ) :
	if ( 1 === count( $upgrades ) ) {
		$currencies = charitable_get_currency_helper()->get_all_currencies();
		$gateway    = key( $upgrades );
		$message    = sprintf(
			/* translators: %1$s: currency; %2$s: hyperlink %3$s: payment gateway name */
			__( '<strong>Tip</strong>: Accept donations in %1$s with <a href="%2$s" target="_blank">%3$s</a>.', 'charitable' ),
			$currencies[ charitable_get_currency() ],
			'https://www.wpcharitable.com/extensions/charitable-' . $gateway . '/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Upgrade+Notice&utm_content=Accept+Donations',
			current( $upgrades )
		);
	} else {
		$message = sprintf(
			/* translators: %1$s: hyperlink; %2$s: single extension name; %3$s: comma-separated list of extension names */
			__( '<strong>Need more options?</strong> <a href="%1$s" target="_blank">Click here to browse our payment gateway extensions</a>, including %3$s and %2$s.', 'charitable' ),
			'https://www.wpcharitable.com/extensions/category/payment-gateways/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Admin+Notice&utm_content=Need+More+Options+Browse+Gateway+Extensions',
			array_pop( $upgrades ),
			implode( ', ', $upgrades )
		);
	}
	?>
	<p class="charitable-gateway-prompt charitable-settings-notice"><?php echo esc_html( $message ); ?></p>
<?php endif ?>
