<?php
/**
 * Display the Setup page.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Setup Page
 * @since   1.8.4
 */

$charitable_setup     = new Charitable_Setup();
$stripe_connect_url   = ! empty( $view_args['stripe_connect_url'] ) ? $view_args['stripe_connect_url'] : '';
$stripe_connected     = ! empty( $view_args['stripe_connected'] ) ? $view_args['stripe_connected'] : false;
$stripe_returned      = ! empty( $view_args['stripe_returned'] ) ? $view_args['stripe_returned'] : false;
$completed_url        = ! empty( $view_args['completed_url'] ) ? $view_args['completed_url'] : false;
$campaign_admin_url   = ! empty( $view_args['campaign_admin_url'] ) ? $view_args['campaign_admin_url'] : false;
$onboarding_completed = get_option( 'charitable_ss_complete', false ) ? true : false;
$campaign_created     = get_option( 'charitable_setup_campaign_created', false );

?>
<div class="charitable-user-onboarding-wrap">
	<div class="charitable-user-onboarding-logo">
		<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/onboarding/welcome/charitable-header-logo.png' ); ?>" alt="Charitable">
	</div>
	<div class="chartiable-user-onboarding-content">
		<h1></h1>
		<div class="charitable-user-onboarding-progress">
			<div class="charitable-user-onboarding-progress-bar">
				<div class="charitable-user-onboarding-progress-bar-fill" style="width: 0%;"></div>
			</div>
		</div>
		<p class="charitable-subheading"></p>
		<div class="charitable-user-onboarding-notices">
			<div id="charitable-user-onboarding-notice-featured-required" class="charitable-hidden">
				<?php

					printf(
						'<p>%1$s</p>',
						// translators: %s - getting started guide link.
						esc_html( sprintf( __( 'In order to install and activate some of the features you requested (%1$s) you need to activate your PRO license after setup is complete.', 'charitable' ), $charitable_setup->get_requested_feature_string() ) )
					);

					?>
			</div>
			<div id="charitable-user-onboarding-notice-license-failed" class="charitable-hidden">
				<p>
				<?php
					esc_html_e( 'There may have been a problem activating your license. Please confirm if your license is activated in the "General Tab" in settings. If you encounter any problems, please feel free to reach out to our support.', 'charitable' );
				?>
				</p>
			</div>
			<div id="charitable-user-onboarding-notice-featured-failed" class="charitable-hidden">
				<p>
				<?php
					esc_html_e( 'There may have been a problem installing or activating some the features you requested. Please check your installed plugins, and you can add Charitable addons via the addons screen after your license is activated.', 'charitable' );
				?>
				</p>
			</div>
			<div id="charitable-user-onboarding-notice-featured-not-required" class="charitable-hidden">
				<?php

					printf(
						'<p>%1$s</p>',
						// translators: %s - getting started guide link.
						esc_html( sprintf( __( 'Activating license and installing addons requested (%1$s)...', 'charitable' ), $charitable_setup->get_requested_feature_string() ) )
					);

					?>
			</div>
			<div id="charitable-user-onboarding-stripe-connect" class="charitable-hidden">
					<div class="charitable-sub-container charitable-connect-stripe">
						<?php if ( '' !== $stripe_connect_url ) : ?>
						<div>
							<div class="charitable-gateway-icon"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/onboarding/stripe-checklist.svg" alt="" /></div><div class="charitable-gateway-info-column">
								<h3><?php esc_html_e( 'Stripe', 'charitable' ); ?> <span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-green charitable-badge-rounded"><i class="fa fa-star" aria-hidden="true"></i><?php esc_html_e( 'Recommended', 'charitable' ); ?></span></h3>
								<?php if ( ! $stripe_connected ) : ?>
								<p><?php esc_html_e( 'You can create and connect to your Stripe account in just a few minutes.', 'charitable' ); ?></p>
								<?php else : ?>
								<p><?php printf( '%s <strong>%s %s</strong>.', esc_html__( 'Good news! You have connected to Stripe in', 'charitable' ), esc_html( $gateway_mode ), esc_html__( ' mode', 'charitable' ) ); ?></p>
								<?php endif; ?>
							</div>
						</div>
						<div>
							<?php if ( ! $stripe_connected ) : ?>
								<a href="<?php echo esc_url( $stripe_connect_url ); ?>"><div class="wpcharitable-stripe-connect"><span><?php esc_html_e( 'Connect With', 'charitable' ); ?></span>&nbsp;&nbsp;<svg width="49" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.4718 10.3338c0-3.41791-1.6696-6.11484-4.8607-6.11484-3.2045 0-5.1434 2.69693-5.1434 6.08814 0 4.0187 2.289 6.048 5.5743 6.048 1.6023 0 2.8141-.3604 3.7296-.8678v-2.6702c-.9155.4539-1.9658.7343-3.2987.7343-1.3061 0-2.464-.4539-2.6121-2.0294h6.5841c0-.1735.0269-.8678.0269-1.1882Zm-6.6514-1.26838c0-1.50868.929-2.13618 1.7773-2.13618.8213 0 1.6965.6275 1.6965 2.13618h-3.4738Zm-8.5499-4.84646c-1.3195 0-2.1678.61415-2.639 1.04139l-.1751-.82777h-2.9621V20l3.3661-.7076.0134-3.7784c.4847.3471 1.1984.8411 2.3832.8411 2.4102 0 4.6048-1.9225 4.6048-6.1548-.0134-3.87186-2.235-5.98134-4.5913-5.98134Zm-.8079 9.19894c-.7944 0-1.2656-.2804-1.5888-.6275l-.0134-4.95328c.35-.38719.8348-.65421 1.6022-.65421 1.2253 0 2.0735 1.36182 2.0735 3.11079 0 1.7891-.8347 3.1242-2.0735 3.1242Zm-9.6001-9.98666 3.3796-.72096V0l-3.3796.70761v2.72363Zm0 1.01469h3.3796V16.1282h-3.3796V4.44593Zm-3.6219.98798-.2154-.98798h-2.9083V16.1282h3.3661V8.21095c.7944-1.02804 2.1408-.84112 2.5582-.69426V4.44593c-.4309-.16022-2.0062-.45394-2.8006.98798Zm-6.7322-3.88518-3.2853.69426-.01346 10.69421c0 1.976 1.49456 3.4313 3.48726 3.4313 1.1041 0 1.912-.2003 2.3563-.4406v-2.7103c-.4309.1736-2.5583.7877-2.5583-1.1882V7.28972h2.5583V4.44593h-2.5583l.0135-2.8972ZM3.40649 7.83712c0-.5207.43086-.72096 1.14447-.72096 1.0233 0 2.31588.30707 3.33917.85447V4.83311c-1.11755-.44059-2.22162-.61415-3.33917-.61415C1.81769 4.21896 0 5.63418 0 7.99733c0 3.68487 5.11647 3.09747 5.11647 4.68627 0 .6141-.53858.8144-1.29258.8144-1.11755 0-2.54477-.4539-3.675782-1.0681v3.1776c1.252192.534 2.517842.761 3.675782.761 2.80059 0 4.72599-1.3752 4.72599-3.765-.01346-3.97867-5.14339-3.27106-5.14339-4.76638Z" fill="#fff"/></svg></div></a>
							<?php else : ?>
								<a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' ) ); ?>" class="charitable-button charitable-button-primary"><?php esc_html_e( 'Connected', 'charitable' ); ?></a>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>
			</div>
			<div id="charitable-user-onboarding-complete-buttons" class="
			<?php
			if ( ! $stripe_returned ) :
				?>
				charitable-hidden<?php endif; ?>">
				<!--<a href="<?php echo esc_url( $completed_url ); ?>" class="charitable-button-link"><?php esc_html_e( 'Finish Campaign', 'charitable' ); ?></a>-->
				<?php

				// if there is a campaign url, show the button to go to the campaign.... otherwise just 'Continue" and it goes to the checklist or dashboard.

				if ( $onboarding_completed ) {
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist' ) ); ?>" class="charitable-button-link charitable-button-primary"><?php esc_html_e( 'Continue to Checklist', 'charitable' ); ?></a>
					<?php
				} else {
					?>
					<a href="<?php echo esc_url( $completed_url ); ?>" class="charitable-button-link charitable-button-primary"><?php esc_html_e( 'Continue', 'charitable' ); ?></a>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php if ( ! $stripe_returned ) : ?>
		<div class="charitable-go-back"><?php esc_html_e( 'Please do not refresh or close the browser window until this process is complete.', 'charitable' ); ?></div>
	<?php endif; ?>

</div>
