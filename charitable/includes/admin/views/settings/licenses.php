<?php
/**
 * Display the table of products requiring licenses.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

$helper   = charitable_get_helper( 'licenses' );
$products = $helper->get_products();

if ( empty( $products ) ) :
	return;
endif;

$slug      = Charitable_Addons_Directory::get_current_plan_slug();
$is_legacy = Charitable_Addons_Directory::is_current_plan_legacy();

if ( false !== $slug && strtolower( $slug ) !== 'lite' && ! $is_legacy ) {

	// there is a valid legacy license present.
	$new_tab_notification =
	'<p>' .
	sprintf(
		wp_kses(
		/* translators: %s - charitable.com upgrade URL. */
			__( 'You already have a non-legacy license activated on this install, which you can deactivate <a href="%s">in the "General" tab</a>.', 'charitable' ),
			array(
				'a'      => array(
					'href'   => array(),
					'class'  => array(),
					'target' => array(),
					'rel'    => array(),
				),
				'br'     => array(),
				'strong' => array(),
			)
		),
		esc_url( admin_url( 'admin.php?page=charitable-settings&tab=general' ) )
	) .
	'</p>';

	?>

<div class="charitable-settings-notice license-notice" style="margin-bottom: 20px;">
<p><?php esc_html_e( 'This area is reserved for older (legacy) license keys.', 'charitable' ); ?></p>
<p><?php echo $new_tab_notification; // phpcs:ignore ?></p>
</div>

	<?php

} else {

	$new_tab_notification =
	'<p>' .
	sprintf(
		wp_kses(
		/* translators: %s - charitable.com upgrade URL. */
			__( 'If you have purchased your license key for <strong>Basic</strong>, <strong>Plus</strong>, <strong>Pro</strong>, or <strong>Agency / Elite</strong> recently, please enter your charitable license key <a href="%s">in the "General" tab</a>.', 'charitable' ),
			array(
				'a'      => array(
					'href'   => array(),
					'class'  => array(),
					'target' => array(),
					'rel'    => array(),
				),
				'br'     => array(),
				'strong' => array(),
			)
		),
		esc_url( admin_url( 'admin.php?page=charitable-settings&tab=general' ) )
	) .
	'</p>';

	?>
<div class="charitable-settings-notice license-notice" style="margin-bottom: 20px;">
	<p><?php esc_html_e( 'This area is reserved for older (legacy) license keys.', 'charitable' ); ?></p>
	<p><?php echo $new_tab_notification; // phpcs:ignore ?></p>
	<p><?php esc_html_e( 'By adding your license keys, you agree for your website to send requests to wpcharitable.com to check license details and provide automatic plugin updates. Your license(s) can be disconnected at any time.', 'charitable' ); ?></p>
</div>
	<?php

	$_charitable_legacy_license_info = get_transient( '_charitable_legacy_license_info' );

	foreach ( $products as $key => $product ) :

		$license = $helper->get_license_details( $key );

		// set a default invalid message.
		$invalid_message = __( 'This is an invalid license.', 'charitable' );

		if ( is_array( $license ) ) {
			if ( isset( $license['expiration_date'] ) && false !== $license['expiration_date'] && isset( $license['valid'] ) && false !== $license['valid'] ) {
				$is_active   = $license['valid'];
				$license_key = $license['license'];
			} else {
				$is_active   = false;
				$license_key = false;
				// this is different because we try to avoid just a BAD license vs. an invalid-but-could-be-expired license.
				$referer = wp_get_referer();
				if ( admin_url( 'admin.php?page=charitable-settings&tab=advanced' ) === $referer ) {
					if ( false !== $_charitable_legacy_license_info && is_array( $_charitable_legacy_license_info ) && array_key_exists( $key, $_charitable_legacy_license_info ) && '' !== trim( $_charitable_legacy_license_info[ $key ] ) ) {
						$invalid_message = __( 'The license was not valid.', 'charitable' );
					} else {
						$invalid_message = false;
					}
				} else {
					$invalid_message = false;
				}
			}
		} else {
			$is_active   = false;
			$license_key = $license;
		}

		?>
	<div class="charitable-settings-object charitable-licensed-product">
		<h4><?php echo esc_html( $product['name'] ); ?></h4>
		<input type="text" name="charitable_settings[legacy_licenses][<?php echo esc_attr( $key ); ?>]" id="charitable_settings_licenses_<?php echo esc_attr( $key ); ?>" class="charitable-settings-field" placeholder="<?php esc_attr_e( 'Add your license key', 'charitable' ); ?>" value="<?php echo esc_attr( $license_key ); ?>" />
		<?php if ( $license ) : ?>
			<div class="license-meta">
				<?php if ( $is_active ) : ?>
					<a href="<?php echo esc_url( $helper->get_license_deactivation_url( $key ) ); ?>" class="button-secondary license-deactivation"><?php esc_html_e( 'Deactivate License', 'charitable' ); ?></a>
					<?php if ( 'lifetime' === $license['expiration_date'] ) : ?>
						<span class="license-expiration-date"><?php esc_html_e( 'Lifetime license', 'charitable' ); ?></span>
					<?php else : ?>
						<span class="license-expiration-date"><?php printf( '%s %s.', esc_html__( 'Expiring in', 'charitable' ), human_time_diff( strtotime( $license['expiration_date'] ), time() ) ); // phpcs:ignore ?></span>
					<?php endif ?>
				<?php elseif ( is_array( $license ) ) : ?>
					<span class="license-invalid"><?php echo $invalid_message; // phpcs:ignore ?></span>
				<?php else : ?>
					<span class="license-invalid"><?php esc_html_e( 'We could not validate this license.', 'charitable' ); ?></span>
				<?php endif ?>
			</div>
		<?php endif ?>
	</div>

		<?php
endforeach;



	delete_transient( '_charitable_legacy_license_info' );

}
