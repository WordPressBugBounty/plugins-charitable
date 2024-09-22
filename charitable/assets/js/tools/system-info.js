/* global wpchar */

/**
 * System Info script.
 *
 * @since 1.8.1.6
 */
const CharitableSystemInfo = window.CharitableSystemInfo || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.1.6
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.8.1.6
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.1.6
		 */
		ready() {
			app.events();
		},

		/**
		 * Events.
		 *
		 * @since 1.8.1.6
		 */
		events() {

			// Copy system information to clipboard.
			$( document ).on( 'click', '#charitable-system-information-copy', function( event ) {
				event.preventDefault();
				app.copySystemInformation();
			} );

			// Run SSL test.
			$( document ).on( 'click', '#charitable-ssl-verify', function( event ) {
				event.preventDefault();
				app.verifySSLConnection();
			} );
		},

		/**
		 * Copy system information to clipboard.
		 *
		 * @since 1.8.1.6
		 */
		copySystemInformation() {
            $( '#charitable-system-information' ).select();
            document.execCommand( 'copy' );
		},

		/**
		 * Verify SSL connection.
		 *
		 * @since 1.8.1.6
		 *
		 * @return {void}
		*/
		verifySSLConnection() {
			const $btn      = $( '#charitable-ssl-verify' );
			const btnLabel  = $btn.text();
			const btnWidth  = $btn.outerWidth();
			const $settings = $btn.parent();

			if ( typeof charitable_admin !== "undefined" && typeof charitable_admin.ajax_url !== "undefined" && typeof charitable_admin.nonce !== "undefined" && typeof charitable_admin.testing !== "undefined" ) {

				$btn.css( 'width', btnWidth ).prop( 'disabled', true ).text( charitable_admin.testing );

				const data = {
					action: 'charitable_verify_ssl',
					nonce:   charitable_admin.nonce
				};

				// Trigger AJAX to test connection
				$.post( charitable_admin.ajax_url, data, function( res ) {

					if ( typeof wpchar !== "undefined" ) {
						wpchar.debug( res );
					}

					// Remove any previous alerts.
					$settings.find( '.charitable-notice' ).remove();

					if ( res.success ) {
						$btn.before( '<div class="notice charitable-notice notice-success">' + res.data.msg + '</div>' );
					}

					if ( ! res.success && res.data.msg ) {
						$btn.before( '<div class="notice charitable-notice notice-error">' + res.data.msg + '</div>' );
					}

					if ( ! res.success && res.data.debug ) {
						$btn.before( '<div class="charitable-ssl-error pre-error">' + res.data.debug + '</div>' );
					}

					$btn.css( 'width', btnWidth ).prop( 'disabled', false ).text( btnLabel );
				} );

			}
		}
    };

	return app;
}( document, window, jQuery ) );

// Initialize.
CharitableSystemInfo.init();
