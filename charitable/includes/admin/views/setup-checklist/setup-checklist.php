<?php
/**
 * Display the setup checklist.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.12
 * @version   1.8.1.12
 */

// get first name of logged in user...
$first_name = get_user_meta( get_current_user_id(), 'first_name', true );
// .. and if that doesn't exist, use the username.
if ( empty( $first_name ) ) {
	$first_name = wp_get_current_user()->user_login;
}

$charitable_onboarding = Charitable_Onboarding::get_instance();
$step_first_campaign   = $charitable_onboarding->is_step_completed( 'first-campaign' );

ob_start();
?>

<div id="charitable-setup-checklist-wrap" class="wrap">

	<main id="charitable-setup-checklist" class="charitable-setup-checklist">

		<div class="charitable-setup-checklist-container">

			<header class="charitable-main-header">

				<h1><?php esc_html_e( 'Welcome Aboard, ', 'charitable' ); ?><?php echo esc_html( $first_name ); ?>!</h1>

				<p><?php esc_html_e( 'Follow these steps to start fundraising quickly.', 'charitable' ); ?></p>

			</header>

			<section class="charitable-step charitable-step-plugin-config" data-section-name="plugin-config">
				<header>
					<h2><span class="charitable-checklist-checkbox charitable-checklist-checked"></span><?php esc_html_e( 'Initial Plugin Configuration', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-two-col-content">
					<div>
						<p><?php esc_html_e( 'Your setup is almost done here! you need to complete few steps.', 'charitable' ); ?></p>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Configure Plugin', 'charitable' ); ?></a>
				</div>
			</section>

			<section class="charitable-step charitable-step-opt-in" data-section-name="opt-in">
				<header>
					<h2><span class="charitable-checklist-checkbox charitable-checklist-unchecked"></span><?php esc_html_e( 'Never Miss An Important Update', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-two-col-content charitable-equal-flex">
					<div>
						<p><?php esc_html_e( 'Optin to get email notifications for security &amp; feature updates, educational content, and occasional offers, and to share some basic WordPress environment info. This will help us make Charitable more compatible with your site and better at doing what you need it to do. Disable this at any time.', 'charitable' ); ?></p>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary alt"><?php esc_html_e( 'Agree And Continue', 'charitable' ); ?></a>
				</div>
			</section>

			<section class="charitable-step charitable-step-connect-payment" data-section-name="connect-payment">
				<header>
					<h2><span class="charitable-checklist-checkbox charitable-checklist-unchecked"></span><?php esc_html_e( 'Connect To Payment Gatway', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-one-col-content charitable-column">
					<div class="charitable-sub-container charitable-connect-stripe">
						<div>
							<div class="charitable-gateway-icon"><img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/onboarding/stripe-checklist.svg" alt="" /></div><div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Stripe', 'charitable' ); ?></h3>
								<p><?php esc_html_e( 'Connect Stripe with Charitable', 'charitable' ); ?></p>
							</div>
						</div>
						<div>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary alt"><?php esc_html_e( 'Connect To Stripe', 'charitable' ); ?></a>
						</div>
					</div>
					<div class="charitable-sub-container charitable-step-one-col-content charitable-column">
						<div class="charitable-sub-container-row ">
							<input type="checkbox" id="wpchar-no-stripe" name="no-stripe" value="no-stripe">
							<label for="wpchar-no-stripe"><?php esc_html_e( 'Stripe is not working in my country.', 'charitable' ); ?></label>
						</div>
						<div class="charitable-sub-container-row">
                            <p><?php esc_html_e( 'Charitable allows different payment gateways such as Authorize.net, PayPal, etc.', 'charitable' ); ?> <a href="#"><?php esc_html_e('Settings Payment Gateways', 'charitable'); ?></a><img class="wpchar-arrow" src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></p>
						</div>
					</div>
				</div>
			</section>

            <?php

            $css_class = $step_first_campaign ? 'charitable-checklist-checked' : 'charitable-checklist-unchecked';

            ?>

			<section class="charitable-step charitable-step-create-first-campaign" data-section-name="first-campaign">
				<header>
					<h2><span class="charitable-checklist-checkbox <?php echo $css_class; ?>"></span><?php esc_html_e( 'Create Your First Campaign', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-two-col-content">
					<div>
						<p><?php esc_html_e( 'Start your fundraising by creating your first campaign.', 'charitable' ); ?></p>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Create Campaign', 'charitable' ); ?></a>
				</div>
			</section>

			<section class="charitable-step charitable-step-fundraising-next-level" data-section-name="fundraising-next-level">
				<header>
					<h2><span class="charitable-checklist-checkbox charitable-checklist-unchecked"></span><?php esc_html_e( 'Take Fundraising To The Next Level', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-one-col-content charitable-column">
					<div class="charitable-sub-container">
						<div>
							<div class="charitable-next-level-icon"><img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/onboarding/checklist-fundraiser-1.svg" alt="" /></div>
								<div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Ambassadors', 'charitable' ); ?></h3>
								<p><?php esc_html_e( 'Feature transforms your website into a peer-to-peer fundraising platform.', 'charitable' ); ?></p>
							</div>
						</div>
						<div>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'More Information', 'charitable' ); ?></a>
						</div>
					</div>
					<div class="charitable-sub-container">
						<div>
							<div class="charitable-next-level-icon"><img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/onboarding/checklist-fundraiser-2.svg" alt="" /></div>
								<div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Recurring Donations', 'charitable' ); ?></h3>
								<p><?php esc_html_e( 'Grow your organisations revenue with recurring donations.', 'charitable' ); ?></p>
							</div>
						</div>
						<div>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'More Information', 'charitable' ); ?></a>
						</div>
					</div>
					<div class="charitable-sub-container">
						<div>
							<div class="charitable-next-level-icon"><img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/onboarding/checklist-fundraiser-3.svg" alt="" /></div>
								<div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Fee Relief', 'charitable' ); ?></h3>
								<p><?php esc_html_e( 'Give your donors the option to cover the processing fees on their donations.', 'charitable' ); ?></p>
							</div>
						</div>
						<div>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'More Information', 'charitable' ); ?></a>
						</div>
					</div>
				</div>
			</section>

			<?php // if ( ! charitable_is_pro() ) : ?>

				<section class="charitable-step charitable-pro">
					<div>
						<h4><?php esc_html_e( 'Upgrade to Pro to Unlock Powerful Donation Features', 'charitable' ); ?></h4>
						<p><?php esc_html_e( 'Get access to powerful features that will help you raise more money for your cause.', 'charitable' ); ?></p>
						<!-- flex box with two equal columns -->
						<div class="charitable-pro-features">
							<ul class="charitable-pro-features-list">
								<li><?php esc_html_e( 'Peer-to-Peer Fundraising', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Recurring Donations', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Fee Relief', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Advanced Reporting', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Custom Email Receipts', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Custom Donation Forms', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Custom User Registration Forms', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Custom Campaign Pages', 'charitable' ); ?></li>
							</ul>
						</div>
						<div class="charitable-upgrade-pro">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings' ) ); ?>" class="button button-primary alt"><?php esc_html_e( 'Upgrade to Pro', 'charitable' ); ?></a>
						</div>
						<div class="charitable-learn-more">
							<a href="<?php echo esc_url( 'https://www.wpcharitable.com/pricing/' ); ?>" target="_blank"><?php esc_html_e( 'Learn more about all features', 'charitable' ); ?></a>
						</div>
					</div>
				</section>

			<?php // endif; ?>


		</div>

	</main>



</div>
<?php
echo ob_get_clean();
