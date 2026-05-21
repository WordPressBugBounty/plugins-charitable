/**
 * Charitable Logger Admin JS.
 *
 * Handles the Logs tab UI interactions.
 *
 * @since 1.8.11
 */

/* global jQuery, charitableLogger */

( function( $ ) {
	'use strict';

	var Logger = {

		/**
		 * Initialize.
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			// Modal: click log title link.
			$( document ).on( 'click', '.js-charitable-log-link', this.openModal );

			// Modal: close.
			$( document ).on( 'click', '.charitable-log-modal-close, .charitable-log-modal-overlay', this.closeModal );
			$( document ).on( 'keydown', this.escCloseModal );

			// Settings: toggle logging.
			$( '#charitable-logs-enable' ).on( 'change', this.toggleLogging );

			// Settings: retention days.
			$( '#charitable-logs-retention' ).on( 'change', this.saveRetention );

			// Delete All.
			$( '#charitable-delete-all-logs' ).on( 'click', this.deleteAll );
		},

		/**
		 * Open the log detail modal via AJAX.
		 *
		 * @param {Event} e Click event.
		 */
		openModal: function( e ) {
			e.preventDefault();

			var recordId = $( this ).data( 'record-id' ),
				$modal   = $( '#charitable-log-modal' );

			// Show modal with loading state.
			$modal.show();
			$modal.find( '.charitable-log-modal-title' ).text( '...' );
			$modal.find( '.charitable-log-modal-message' ).html( '' );

			$.post( charitableLogger.ajaxUrl, {
				action:    'charitable_get_log_record',
				nonce:     charitableLogger.nonce,
				record_id: recordId
			}, function( response ) {
				if ( ! response.success ) {
					$modal.find( '.charitable-log-modal-title' ).text( charitableLogger.i18n.error );
					return;
				}

				var data = response.data;

				$modal.find( '.charitable-log-modal-title' ).text( data.title );
				$modal.find( '.charitable-log-modal-date' ).text( data.date );
				$modal.find( '.charitable-log-modal-level' ).html(
					'<span class="charitable-log-level charitable-log-level-' + data.level_key + '">' + data.level + '</span>'
				);
				$modal.find( '.charitable-log-modal-types' ).text( data.types );
				$modal.find( '.charitable-log-modal-source' ).text( data.source );
				$modal.find( '.charitable-log-modal-message' ).html( data.message );

				// Build IDs section.
				var ids = [],
					idFields = {
						campaign_id: 'Campaign',
						donation_id: 'Donation',
						donor_id:    'Donor',
						user_id:     'User',
						object_id:   'Object ID',
						object_type: 'Object Type'
					};

				$.each( idFields, function( key, label ) {
					var val = data[ key ];
					if ( val && val !== '0' && val !== 0 ) {
						var display = val,
							link    = '';

						if ( key === 'campaign_id' ) {
							link = '<a href="' + window.ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + val + '&action=edit' ) + '" target="_blank">' + val + '</a>';
						} else if ( key === 'donation_id' ) {
							link = '<a href="' + window.ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + val + '&action=edit' ) + '" target="_blank">' + val + '</a>';
						} else if ( key === 'donor_id' ) {
							link = '<a href="' + window.ajaxurl.replace( 'admin-ajax.php', 'admin.php?page=charitable-donors&donor_id=' + val ) + '" target="_blank">' + val + '</a>';
						}

						ids.push( '<div class="charitable-log-modal-id-item"><strong>' + label + ':</strong> ' + ( link || display ) + '</div>' );
					}
				} );

				if ( ids.length > 0 ) {
					$modal.find( '.charitable-log-modal-ids' ).show();
					$modal.find( '.charitable-log-modal-ids-grid' ).html( ids.join( '' ) );
				} else {
					$modal.find( '.charitable-log-modal-ids' ).hide();
				}
			} );
		},

		/**
		 * Close the modal.
		 */
		closeModal: function() {
			$( '#charitable-log-modal' ).hide();
		},

		/**
		 * Close modal on Escape key.
		 *
		 * @param {Event} e Keydown event.
		 */
		escCloseModal: function( e ) {
			if ( e.key === 'Escape' ) {
				Logger.closeModal();
			}
		},

		/**
		 * Toggle logging on/off.
		 */
		toggleLogging: function() {
			var enabled = $( this ).is( ':checked' ) ? 1 : 0,
				$status = $( '.charitable-logs-status' );

			$.post( charitableLogger.ajaxUrl, {
				action:  'charitable_toggle_logging',
				nonce:   charitableLogger.nonce,
				enabled: enabled
			}, function( response ) {
				if ( response.success ) {
					$status.text( enabled ? $status.data( 'enabled' ) || 'Enabled' : $status.data( 'disabled' ) || 'Disabled' );
				}
			} );
		},

		/**
		 * Save retention days.
		 */
		saveRetention: function() {
			var days = $( this ).val();

			$.post( charitableLogger.ajaxUrl, {
				action: 'charitable_save_log_retention',
				nonce:  charitableLogger.nonce,
				days:   days
			} );
		},

		/**
		 * Delete all logs.
		 */
		deleteAll: function() {
			if ( ! window.confirm( charitableLogger.i18n.deleteConfirm ) ) {
				return;
			}

			var $btn = $( this );
			$btn.prop( 'disabled', true ).text( charitableLogger.i18n.deleting );

			$.post( charitableLogger.ajaxUrl, {
				action: 'charitable_delete_all_logs',
				nonce:  charitableLogger.nonce
			}, function( response ) {
				if ( response.success ) {
					window.location.reload();
				} else {
					$btn.prop( 'disabled', false ).text( charitableLogger.i18n.error );
				}
			} );
		}
	};

	$( document ).ready( function() {
		Logger.init();
	} );

} )( jQuery );
