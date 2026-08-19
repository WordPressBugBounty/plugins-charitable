<?php
/**
 * The template used to display the login form.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.8.12.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="login-prompt">
	<a href="#" data-charitable-toggle="charitable-donation-login-form"><?php esc_html_e( 'Registered before? Log in to use your saved details.', 'charitable' ); ?></a>
</p>
<div id="charitable-donation-login-form" class="charitable-login-form charitable-form">
	<p><?php esc_html_e( 'If you registered an account, please enter your details below to login. If this is your first time, proceed to the donation form.', 'charitable' ); ?></p>
	<?php
	wp_login_form(
		array(
			/**
			 * Filter whether usernames are used for donors.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $usernames Whether usernames are used.
			 */
			'label_username' => apply_filters( 'charitable_donor_usernames', false ) ? __( 'Username', 'charitable' ) : __( 'Email', 'charitable' ),
			/*
			 * Fingerprint this form with charitable=true so a failed login is caught by
			 * Charitable_User_Management::maybe_redirect_at_authenticate() and returned to
			 * the custom login page, instead of falling through to wp-login.php. The hidden
			 * charitable=1 field is added by the global login_form_bottom filter.
			 */
			'charitable'     => true,
		)
	);
	?>
</div>
