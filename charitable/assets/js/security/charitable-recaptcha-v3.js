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

			// Add pending process before calling out, so a failure can clear it rather
			// than leaving the form waiting on a process that will never resolve.
			helper.add_pending_process( 'recaptcha_v3' );

			/*
			 * grecaptcha.execute() throws synchronously, rather than rejecting, when the
			 * site key it was given was never registered with api.js. That happens whenever
			 * something else on the page loads reCAPTCHA without the render parameter, which
			 * other plugins loading their own reCAPTCHA routinely cause.
			 *
			 * A synchronous throw is not caught by .catch(). Uncaught, it escapes this
			 * handler and the form's own validate(), so Charitable's submit handler never
			 * returns false and the browser falls through to a native form POST: the gateway
			 * JS never runs, no payment method is created, and the donation dies server-side
			 * with nothing shown to the donor and nothing written to the log.
			 *
			 * Catching it keeps a misconfigured key to a failed captcha check instead of a
			 * broken donation form.
			 */
			try {
				grecaptcha.execute( siteKey, { action: action } ).then( function( token ) {
					// Add token to form.
					var tokenInput = $( '<input>' )
						.attr( 'type', 'hidden' )
						.attr( 'name', 'charitable_recaptcha_v3_token' )
						.val( token );

					/*
					 * Remove an existing token, SCOPED TO THIS FORM (1.8.13.1).
					 *
					 * This selector used to be unscoped, so it removed the token from every
					 * form in the document. On a page carrying two or more donation forms,
					 * generating a token for form A stripped the token already appended to
					 * form B, and because the server-side check is authoritative the result
					 * was a donation rejected for a missing captcha token -- a silent
					 * failure of exactly the kind 1.8.13.1 was released to fix.
					 *
					 * The generic `.charitable-form` path below always scoped its own
					 * removal to `$form`; the two paths simply disagreed. They now match.
					 */
					$( helper.form ).find( '[name="charitable_recaptcha_v3_token"]' ).remove();

					// Add token to form.
					$( helper.form ).append( tokenInput );

					// Remove pending process.
					helper.remove_pending_process_by_name( 'recaptcha_v3' );
				} ).catch( function( error ) {
					// Handle error.
					helper.add_error( errorMessage );
					helper.remove_pending_process_by_name( 'recaptcha_v3' );
				} );
			} catch ( error ) {
				helper.add_error( errorMessage );
				helper.remove_pending_process_by_name( 'recaptcha_v3' );
			}
		} );

		// All Charitable forms carry `.charitable-form`. Donation form has its own flow above; server-side check is authoritative.
		$( 'form.charitable-form' )
			.not( '.charitable-donation-form' )
			.not( '[data-use-ajax="1"]' )
			.on( 'submit', function( e ) {
				var $form = $( this );

				e.preventDefault();

				/* As above: a synchronous throw here would never reach .catch(). */
				try {
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
				} catch ( error ) {
					alert( errorMessage );
				}
			} );
	}

} )( jQuery );

