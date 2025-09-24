<?php
/**
 * Display the main privacy compliance page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.8
 * @version   1.8.8
 */

ob_start();

$plugin_name       = 'WPConsent';
$lite_plugin       = 'wpconsent-cookies-banner-privacy-suite/wpconsent.php';
$lite_wporg_url    = 'https://wordpress.org/plugins/wpconsent-cookies-banner-privacy-suite/';
$lite_download_url = 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip';
$pro_plugin        = 'wpconsent-premium/wpconsent-premium.php';

?>

<div id="charitable-privacy-compliance" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="charitable-intergrations-container intergration-privacy-compliance">

		<img class="charitable-privacy-compliance-logo" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/integrations/consent.png'; ?>" alt="<?php echo esc_html__( 'Built for transparency. Designed for ease.', 'charitable' ); ?>">

		<h1><?php echo esc_html__( 'Make Your Website Privacy Compliant in Minutes', 'charitable' ); ?></h1>

		<h2><?php echo esc_html__( 'Build trust with clear, compliant privacy practices. WPConsent adds clean, professional banners and handles the technical side for you. Built for transparency. Designed for ease.', 'charitable' ); ?></h2>

		<div class="bullets-thumbnail">

			<div>
				<div class="charitable-screenshot">
					<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/wpconsent-screenshot-thumbnail.png' ); ?>" alt="<?php echo esc_html__( 'Integrate WPConsent Tools Into Charitable For Enhanced Data Protection', 'charitable' ); ?>">
					<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/wpconsent-screenshot-full.png' ); ?>" class="hover" data-lity=""></a>
				</div>
			</div>
			<div>
				<div class="vertical-wrapper">
					<ul>
						<li><?php echo esc_html__( 'A professional banner that fits your site.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Tools like Google Analytics and Facebook Pixel paused until consent.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Peace of mind knowing you’re aligned with global laws.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Self-hosted. Your data remains on your site.', 'charitable' ); ?></li>
					</ul>
				</div>
			</div>

		</div>

		<div class="charitable-intergration-steps">

			<?php

			$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party(); // phpcs:ignore

			// determine if the Privacy Compliance plugin is installed and activated.
			$is_privacy_compliance_installed = $charitable_plugins_third_party->is_plugin_installed( 'wpconsent' );
			$is_privacy_compliance_active    = $charitable_plugins_third_party->is_plugin_activated( 'wpconsent' );

			if ( ! $is_privacy_compliance_installed ) {

				$install_button_html = $charitable_plugins_third_party->get_install_button_html( 'wpconsent', 'Install WPConsent' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1"  data-status="install">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Install and Activate ' . $plugin_name, 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Install the ' . $plugin_name . ' plugin by clicking this button', 'charitable' ); ?></p>
					<?php echo $install_button_html; // phpcs:ignore ?>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

				<?php
			} elseif ( ! $is_privacy_compliance_active ) {

				$basename = $charitable_plugins_third_party->get_basename_from_slug( 'wpconsent' );

				if ( $basename ) :

					$activate_button_html = $charitable_plugins_third_party->get_activation_button_html( 'wpconsent', 'Activate ' . $plugin_name );

					?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="activate">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Activate ' . $plugin_name, 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Activate ' . $plugin_name . ' by clicking this button:', 'charitable' ); ?></p>
					<?php echo $activate_button_html; // phpcs:ignore ?>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

					<?php

			endif;

			} else {

				$setup_url = $charitable_plugins_third_party->get_setup_screen_for_plugin( 'wpconsent' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="setup">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Setup ' . $plugin_name, 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Setup ' . $plugin_name . ' plugin by clicking this button:', 'charitable' ); ?></p>
					<a href="<?php echo esc_url( $setup_url ); ?>" target="_blank" class="charitable-button button-link charitable-button-setup"><?php echo esc_html__( 'Set Up WPConsent', 'charitable' ); ?></a>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

			<?php } ?>

			<?php if ( ! charitable_is_installed_mi_pro() ) : ?>

			<div class="charitable-intergration-step charitable-intergration-step-1">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Upgrade to ' . $plugin_name . ' Pro', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Unlock addition features by upgrading to ' . $plugin_name . ' Pro. Special offer: Get 50% off your plan!', 'charitable' ); ?></p>
					<a href="https://wpconsent.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=privacy-compliance-page" target="_blank" class="charitable-button button-link"><?php echo esc_html__( 'Upgrade To Pro', 'charitable' ); ?></a>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-2.png' ); ?>" alt="<?php echo esc_html__( 'Step 2', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

			<?php endif; ?>

		</div> <!-- charitable intergration steps -->

	</div> <!-- charitable integrations container -->


</div>

<?php
echo ob_get_clean(); // phpcs:ignore
