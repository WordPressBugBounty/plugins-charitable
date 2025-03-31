/* global charitable_admin */
/**
 * Connect functionality.
 *
 * @since 1.5.4
 */

'use strict';

var CharitableConnect = window.CharitableConnect || ( function( document, window, $ ) {

	/**
	 * Elements reference.
	 *
	 * @since 1.5.5
	 *
	 * @type {object}
	 */
	var el = {
		$connectBtn: $( '#charitable-settings-connect-btn' ),
		$connectKey: $( '#charitable-settings-upgrade-license-key' ),
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.5.5
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.5.5
		 */
		init: function() {

			$( app.ready );

	    },

		/**
		 * Document ready.
		 *
		 * @since 1.5.5
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.5.5
		 */
		events: function() {

			app.connectBtnClick();
		},

		/**
		 * Register connect button event.
		 *
		 * @since 1.5.5
		 */
		connectBtnClick: function() {

			// If the button contains the 'data-pro-connect' attribute and it's false, don't show the button.
			// If there is no data-pro-connect attribute, don't show the button.
			if ( ! el.$connectBtn.data( 'pro-connect' ) || el.$connectBtn.data( 'pro-connect' ) === false ) {
				return;
			}

			el.$connectBtn.on( 'click', function( event ) {
                // Stop the form from submitting.
				event.preventDefault();
				app.gotoUpgradeUrl();
			} );
		},

		/**
		 * Get the alert arguments in case of Pro already installed.
		 *
		 * @since 1.5.5
		 *
		 * @param {object} res Ajax query result object.
		 *
		 * @returns {object} Alert arguments.
		 */
		proAlreadyInstalled: function( res ) {

			var buttons = {
				confirm: {
					text: charitable_admin.plugin_activate_btn,
					btnClass: 'btn-confirm',
					keys: [ 'enter' ],
					action: function() {
						window.location.reload();
					},
				},
			};

			return {
				title: charitable_admin.almost_done,
				content: res.data.message,
				icon: 'fa fa-check-circle',
				type: 'green',
				buttons: buttons,
			};
		},

		/**
		 * Go to upgrade url.
		 *
		 * @since 1.5.5
		 */
		gotoUpgradeUrl: function( event ) {

			var data = {
				action: 'charitable_connect_url',
				key:  el.$connectKey.val(),
				nonce: charitable_admin.nonce,
			};

			// if there is key empty, then alert.
			if ( ! el.$connectKey.val() ) {
				$.alert( {
					title: charitable_admin.oops,
					icon: 'fa fa-exclamation-circle',
					type: 'orange',
					boxWidth: '800px',
					content: charitable_admin.please_enter_key,
					buttons: {
						confirm: {
							text: charitable_admin.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					},
				} );
				// prevent the form from submitting.
				event.preventDefault();
				return false;
			}

			$.post( charitable_admin.ajax_url, data )
				.done( function( res ) {

					if ( res.success ) {
						if ( res.data.reload ) {
							$.alert( app.proAlreadyInstalled( res ) );
							return;
						}
						window.location.href = res.data.url;
						return;
					}
					if ( res.data.show_manual_upgrade ) {
						$.alert( {
							title: charitable_admin.oops,
							content: res.data.message,
							icon: 'fa fa-exclamation-circle',
							type: 'orange',
							boxWidth: '800px',
							buttons: {
								confirm: {
									text: charitable_admin.ok,
									btnClass: 'btn-confirm',
									keys: [ 'enter' ],
								},
								url: {
									text: charitable_admin.manual_upgrade,
									btnClass: 'btn-confirm',
									action: function() {
										window.open( res.data.url, '_blank' );
									},
								},
							},
						} );
					} else {
						$.alert( {
							title: charitable_admin.oops,
							content: res.data.message,
							icon: 'fa fa-exclamation-circle',
							type: 'orange',
							boxWidth: '800px',
							buttons: {
								confirm: {
									text: charitable_admin.ok,
									btnClass: 'btn-confirm',
									keys: [ 'enter' ],
								},
							},
						} );
					}
				} )
				.fail( function( xhr ) {

					app.failAlert( xhr );
				} );
		},

		/**
		 * Alert in case of server error.
		 *
		 * @since 1.5.5
		 *
		 * @param {object} xhr XHR object.
		 */
		failAlert: function( xhr ) {

			$.alert( {
				title: charitable_admin.oops,
				content: charitable_admin.server_error + '<br>' + xhr.status + ' ' + xhr.statusText + ' ' + xhr.responseText,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: charitable_admin.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
CharitableConnect.init();
