<?php
/**
 * Displays the campaign donation form.
 *
 * Override this template by copying it to yourtheme/charitable/content-donation-form.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The donation form object used for donations to this campaign. By
 * default, this will be a Charitable_Donation_Form object, but
 * extensions are able to define their own donation form models to use
 * instead.
 *
 * @var Charitable_Donation_Form_Interface
 */
$form = charitable_get_current_donation_form();

if ( ! $form ) {
	return;
}

/**
 * Add something before the donation form.
 *
 * @hook    charitable_donation_form_before
 *
 * @param Charitable_Donation_Form_Interface $form The donation form object.
 */
do_action( 'charitable_donation_form_before', $form );

/**
 * Render the donation form.
 *
 * @param Charitable_Donation_Form_Interface $form The donation form object.
 */
$form->render();

/**
 * Add something after the donation form.
 *
 * @hook    charitable_donation_form_after
 *
 * @param Charitable_Donation_Form_Interface $form The donation form object.
 */
do_action( 'charitable_donation_form_after', $form );
