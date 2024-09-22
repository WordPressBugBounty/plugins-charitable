<?php
/**
 * Charitable Onboarding Hooks.
 *
 * Action/filter hooks used for Charitable Onboarding.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.12
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable growth tool scripts.
 *
 * @see Charitable_Guide_Tools::enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Onboarding::get_instance(), 'enqueue_scripts' ) );
