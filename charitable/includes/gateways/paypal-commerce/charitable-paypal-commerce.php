<?php
/**
 * PayPal Commerce Gateway Loader
 *
 * Include this file to load the PayPal Commerce gateway.
 *
 * @package   Charitable/Gateways/PayPal Commerce
 * @author    WP Charitable
 * @copyright Copyright (c) 2024-2025, Studio 164a
 * @license   GPL-2.0+
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check minimum PHP version.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	return;
}

// Define gateway constants.
if ( ! defined( 'CHARITABLE_PAYPAL_COMMERCE_VERSION' ) ) {
	define( 'CHARITABLE_PAYPAL_COMMERCE_VERSION', '1.0.0' );
}

// Load structured logger helper (must load before gateway/hooks so they can call it).
require_once dirname( __FILE__ ) . '/class-charitable-paypal-logger.php';

// Load gateway class.
require_once dirname( __FILE__ ) . '/class-charitable-gateway-paypal-commerce.php';

// Load middleware client.
require_once dirname( __FILE__ ) . '/class-charitable-paypal-middleware-client.php';

// Load hooks.
require_once dirname( __FILE__ ) . '/charitable-paypal-commerce-hooks.php';

// Load WP-CLI commands (file self-skips when WP-CLI is not running).
require_once dirname( __FILE__ ) . '/class-charitable-paypal-cli.php';
