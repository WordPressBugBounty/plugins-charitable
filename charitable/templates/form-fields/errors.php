<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display error messages.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.5.0
 * @version 1.8.13.1
 * @package Charitable/Templates/Form Fields
 */

if ( ! array_key_exists( 'errors', $view_args ) || empty( $view_args['errors'] ) ) {
	return;
}

/*
 * WCAG 4.1.3 Status Messages / 3.3.1 Error Identification (1.8.13.1).
 *
 * role="alert" makes this an assertive live region, which is what announces the
 * errors on the AJAX path where charitable.js injects this markup into a form
 * that is already on screen.
 *
 * It is NOT sufficient on its own here: a live region only announces content
 * that CHANGES after the region exists, so a summary rendered server-side on a
 * failed submit is silent to a screen reader. tabindex="-1" is what closes that
 * gap -- it makes the summary programmatically focusable so charitable.js can
 * move focus to it on load, which both announces it and puts a sighted keyboard
 * user at the problem instead of back at the top of the page.
 *
 * data-charitable-error-summary marks this as a GENUINE error summary. The class
 * .charitable-form-errors is shared by page-level notices that merely borrow the
 * error styling, and focusing those on page load would steal focus on an ordinary
 * page view. See templates/form-fields/notices.php.
 */
?>
<div class="charitable-form-errors charitable-notice" role="alert" tabindex="-1" data-charitable-error-summary="1">
	<ul class="errors">
		<?php foreach ( $view_args['errors'] as $error ) : // phpcs:ignore ?>
			<li><?php echo $error; // phpcs:ignore ?></li>
		<?php endforeach ?>
	</ul>
</div>
