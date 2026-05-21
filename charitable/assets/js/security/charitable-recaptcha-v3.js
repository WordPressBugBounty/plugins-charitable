/**
 * Google reCAPTCHA v3 Handler for Charitable
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WPCharitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9
 * @version   1.8.10.5
 */

( function( $ ) {
	'use strict';

	/**
	 * Initialize reCAPTCHA v3 when Google's script is ready.
	 */
	if ( typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready !== 'undefined' ) {
		grecaptcha.ready( function() {
			initializeRecaptchaV3();
		} );
	} else {
		// Fallback if grecaptcha.ready is not available.
		$( document ).ready( function() {
			if ( typeof grecaptcha !== 'undefined' ) {
				initializeRecaptchaV3();
			}
		} );
	}

	/**
	 * Initialize reCAPTCHA v3 for all forms.
	 */
	function initializeRecaptchaV3() {
		if ( typeof CHARITABLE_RECAPTCHA_V3 === 'undefined' ) {
			return;
		}

		var siteKey = CHARITABLE_RECAPTCHA_V3.site_key;
		var action  = CHARITABLE_RECAPTCHA_V3.action || 'charitable_donation';
		var errorMessage = CHARITABLE_RECAPTCHA_V3.error_message || 'Your form submission failed because the captcha failed to be validated.';

		/**
		 * Handle donation form submissions.
		 */
		$( 'body' ).on( 'charitable:form:validate', function( event, helper ) {
			if ( ! helper || helper.errors.length > 0 ) {
				return;
			}

			// Generate token with action.
			grecaptcha.execute( siteKey, { action: action } ).then( function( token ) {
				// Add token to form.
				var tokenInput = $( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'charitable_recaptcha_v3_token' )
					.val( token );

				// Remove existing token input if present.
				$( '[name="charitable_recaptcha_v3_token"]' ).remove();

				// Add token to form.
				$( helper.form ).append( tokenInput );

				// Remove pending process.
				helper.remove_pending_process_by_name( 'recaptcha_v3' );
			} ).catch( function( error ) {
				// Handle error.
				helper.add_error( errorMessage );
				helper.remove_pending_process_by_name( 'recaptcha_v3' );
			} );

			// Add pending process.
			helper.add_pending_process( 'recaptcha_v3' );
		} );

		// All Charitable forms carry `.charitable-form`. Donation form has its own flow above; server-side check is authoritative.
		$( 'form.charitable-form' )
			.not( '.charitable-donation-form' )
			.not( '[data-use-ajax="1"]' )
			.on( 'submit', function( e ) {
				var $form = $( this );

				e.preventDefault();

				grecaptcha.execute( siteKey, { action: action } ).then( function( token ) {
					$form.find( '[name="charitable_recaptcha_v3_token"]' ).remove();

					$( '<input>' )
						.attr( 'type', 'hidden' )
						.attr( 'name', 'charitable_recaptcha_v3_token' )
						.val( token )
						.appendTo( $form );

					$form.off( 'submit' ).submit();
				} ).catch( function() {
					alert( errorMessage );
				} );
			} );
	}

} )( jQuery );

