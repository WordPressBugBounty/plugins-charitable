/**
 * Charitable Tools > AI MCP.
 *
 * Saves the AI write-access toggle over AJAX. The tab has no form and no Save
 * Changes button, so this is the only way the setting is written.
 *
 * One master switch, matching Charitable Pro. Still bound by CLASS rather than
 * the `#charitable-ai-mcp-write` id: the handler is delegated from document, and
 * a class keeps it working if the control is moved or rendered more than once.
 *
 * @since 1.8.13
 */
/* global jQuery, charitable_ai_mcp */
( function ( $ ) {
	'use strict';

	var CharitableAiMcp = {

		/**
		 * Bind up.
		 *
		 * @since 1.8.13
		 */
		init: function () {
			$( document ).on( 'change', '.charitable-ai-mcp-write', CharitableAiMcp.onToggle );
			$( document ).on( 'click', '.charitable-ai-button[data-action="install"], .charitable-ai-button[data-action="activate"]', CharitableAiMcp.onBridgeClick );
			$( document ).on( 'click', '.charitable-ai-video-link', CharitableAiMcp.onVideoClick );
			$( document ).on( 'click', '.charitable-ai-activity-toggle', CharitableAiMcp.onActivityToggle );
		},

		/**
		 * Expand or collapse the AI Activity panel.
		 *
		 * The panel starts collapsed, with `is-collapsed` already in the markup
		 * so nothing is painted and then hidden. This only flips that class and
		 * keeps the button's aria-expanded and its screen-reader label honest -
		 * the showing and hiding is CSS.
		 *
		 * Deliberately NOT persisted. The list is long enough to be worth
		 * collapsing by default on every load, and remembering "expanded" would
		 * put the tallest thing on the screen back at the top of the page for
		 * anyone who opened it once. If that turns out to be wrong it wants a
		 * user meta and a nonce, not a localStorage flag that disagrees with the
		 * server-rendered class.
		 *
		 * @since 1.8.13
		 *
		 * @param {Event} event The click event.
		 */
		onActivityToggle: function ( event ) {

			event.preventDefault();

			var $button    = $( event.currentTarget ),
				$panel     = $button.closest( '.charitable-ai-activity-collapsible' ),
				collapsing = ! $panel.hasClass( 'is-collapsed' );

			$panel.toggleClass( 'is-collapsed', collapsing );
			$button.attr( 'aria-expanded', collapsing ? 'false' : 'true' );

			// The chevron is a class swap rather than a CSS rotate(). A transform
			// was tried first: the rule matched, won on specificity and was in the
			// loaded stylesheet, but the computed value stayed at identity in
			// Chrome while the transition was live, so the arrow never turned.
			// Swapping the glyph has no such edge case. Right = closed, down = open.
			$button.find( '.charitable-ai-activity-angle' )
				.toggleClass( 'dashicons-arrow-right-alt2', collapsing )
				.toggleClass( 'dashicons-arrow-down-alt2', ! collapsing );

			// The visible label is an icon, so the accessible name is the only
			// thing that can say which way this control now goes.
			$button.find( '.screen-reader-text' ).text(
				collapsing ? charitable_ai_mcp.i18n.showActivity : charitable_ai_mcp.i18n.hideActivity
			);
		},

		/**
		 * Play the walkthrough in a modal instead of leaving the dashboard.
		 *
		 * Same shape as Charitable Lite's welcome-screen video modal
		 * (charitable-admin-2.0.js, initWelcome) so the two behave alike: a
		 * youtube-nocookie embed with autoplay, opened through jquery-confirm.
		 *
		 * TWO DELIBERATE DIFFERENCES from that one:
		 *
		 * - The options are passed per call rather than written into
		 *   `jconfirm.defaults`. Lite mutates the global, which then applies to
		 *   every other dialog on the page - including the What's New splash,
		 *   which is enqueued on this same screen.
		 * - If jquery-confirm is missing the click is left alone, so the anchor's
		 *   own href opens the video on YouTube. The link is never a dead end.
		 *
		 * @since 1.8.13
		 *
		 * @param {Event} event The click event.
		 */
		onVideoClick: function ( event ) {

			var id = $( event.currentTarget ).data( 'video-id' );

			if ( ! id || 'undefined' === typeof $.dialog ) {
				return;
			}

			event.preventDefault();

			$.dialog( {
				title: false,
				content: '<div class="charitable-ai-video-frame"><iframe src="https://www.youtube-nocookie.com/embed/' + encodeURIComponent( id ) + '?rel=0&amp;modestbranding=1&amp;iv_load_policy=3&amp;autoplay=1" title="' + charitable_ai_mcp.i18n.videoTitle + '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen frameborder="0"></iframe></div>',
				closeIcon: true,
				backgroundDismiss: true,
				escapeKey: true,
				useBootstrap: false,
				theme: 'modern,charitable-ai-video-modal',
				boxWidth: '900px',
				animateFromElement: false
			} );
		},

		/**
		 * Persist the new state of the write switch.
		 *
		 * The checkbox is disabled for the duration of the request, so a double
		 * click cannot race two saves against each other and leave the UI showing
		 * the opposite of what was stored.
		 *
		 * There is one switch, matching Charitable Pro. 1.8.13 briefly had three,
		 * which is why this used to post a `scope` alongside `enabled`; the
		 * handler now takes only the new state.
		 *
		 * @since 1.8.13
		 *
		 * @param {Event} event The change event.
		 */
		onToggle: function ( event ) {

			var $input   = $( event.target ),
				enabled  = $input.is( ':checked' ),
				$wrap    = $input.closest( '.charitable-ai-gate' ),
				$spinner = $wrap.find( '.charitable-ai-gate-spinner' );

			$input.prop( 'disabled', true );
			$spinner.addClass( 'is-active' );

			$.post(
				charitable_ai_mcp.ajax_url,
				{
					action: 'charitable_ai_mcp_toggle_write',
					nonce: charitable_ai_mcp.nonce,
					enabled: enabled ? '1' : '0'
				}
			).done( function ( response ) {

				if ( ! response || ! response.success ) {
					// Put the control back where it was: the stored value did not move.
					// ajax_toggle_write() guarantees that - its error path stores nothing,
					// and it verifies the save by re-reading the option. A failure returned
					// AFTER the value was written would revert this checkbox to OFF while an
					// assistant could still make changes.
					$input.prop( 'checked', ! enabled );
					window.alert( ( response && response.data && response.data.message ) || charitable_ai_mcp.i18n.error );
					return;
				}

				// Saved. If the handler ever has something to say about a save that
				// landed, say it before reloading or the reader never learns.
				if ( response.data && response.data.warning ) {
					window.alert( response.data.warning );
				}

				// Reload so the read-only banner, and anything else that depends on
				// the gate, reflects the new state rather than going stale.
				window.location.reload();

			} ).fail( function () {

				$input.prop( 'checked', ! enabled );
				window.alert( charitable_ai_mcp.i18n.error );

			} ).always( function () {

				$input.prop( 'disabled', false );
				$spinner.removeClass( 'is-active' );
			} );
		},

		/**
		 * Install and activate WPVibe without leaving the page.
		 *
		 * WPForms' equivalent tab does both in one click, so this chains the two
		 * endpoints rather than making the reader click twice. Charitable's shared
		 * installer JS is deliberately left alone: it drives the Addons, Dashboard
		 * and onboarding screens as a three-step install / activate / setup flow,
		 * and it finishes by retargeting the button to a new tab - which is the
		 * behaviour this button exists to avoid.
		 *
		 * @since 1.8.13
		 *
		 * @param {Event} event The click event.
		 */
		onBridgeClick: function ( event ) {

			event.preventDefault();

			var $button = $( event.currentTarget );

			// A second click while the first is in flight would install twice.
			if ( $button.hasClass( 'is-working' ) ) {
				return;
			}

			$button.addClass( 'is-working' ).data( 'label', $.trim( $button.text() ) );

			if ( 'activate' === $button.data( 'action' ) ) {
				CharitableAiMcp.activateBridge( $button, $button.data( 'basename' ) );
				return;
			}

			CharitableAiMcp.setLabel( $button, charitable_ai_mcp.i18n.installing );

			$.post(
				charitable_ai_mcp.ajax_url,
				{
					action: 'charitable_install_plugin',
					nonce: charitable_ai_mcp.plugin_nonce,
					slug: $button.data( 'slug' )
				}
			).done( function ( response ) {

				// install_plugin() answers {form:...} when it wants filesystem
				// credentials and {error:...} on failure, and a rejected nonce dies
				// with a bare -1. None of those carry a success flag, so anything
				// without one is a failure rather than something to chain from.
				if ( ! response || ! response.success ) {
					CharitableAiMcp.bridgeFailed( $button, response );
					return;
				}

				// install_plugin() resolves the basename through Charitable's own plugin
				// registry, which has no vibe-ai entry, so it answers '' here. The
				// basename rendered into the button is the reliable one.
				CharitableAiMcp.activateBridge( $button, ( response.data && response.data.basename ) || $button.data( 'basename' ) );

			} ).fail( function () {
				CharitableAiMcp.bridgeFailed( $button );
			} );
		},

		/**
		 * Activate the bridge plugin, then re-render the tab.
		 *
		 * A reload rather than a DOM swap: the note under the button, the
		 * read-only banner and all six connection checks are rendered from the
		 * bridge state in PHP, so re-rendering is the only thing that leaves the
		 * whole tab consistent instead of just this one button.
		 *
		 * @since 1.8.13
		 *
		 * @param {jQuery} $button  The button.
		 * @param {string} basename Plugin basename to activate.
		 */
		activateBridge: function ( $button, basename ) {

			CharitableAiMcp.setLabel( $button, charitable_ai_mcp.i18n.activating );

			$.post(
				charitable_ai_mcp.ajax_url,
				{
					action: 'charitable_activate_plugin',
					nonce: charitable_ai_mcp.plugin_nonce,
					basename: basename
				}
			).done( function ( response ) {

				// activate_plugin() only answers JSON when it can resolve a setup or
				// settings screen for the plugin, which it does from Charitable's own
				// registry - so for WPVibe a SUCCESSFUL activation falls through to a
				// bare wp_die() and returns an empty body. Treating that as a failure
				// is how this reported "could not be installed" for a plugin it had
				// just activated. So only an explicit error message counts as one,
				// and otherwise the reloaded tab is the source of truth: it renders
				// from the real plugin state, and shows "Activate" again if activation
				// genuinely did not happen.
				var message = CharitableAiMcp.errorFrom( response );

				if ( message ) {
					CharitableAiMcp.bridgeFailed( $button, response );
					return;
				}

				window.location.reload();

			} ).fail( function () {
				CharitableAiMcp.bridgeFailed( $button );
			} );
		},

		/**
		 * The error message in an AJAX payload, if it carries one.
		 *
		 * These endpoints report failure three different ways - {error}, a
		 * wp_send_json_error() wrapping {error}, and {message} - so all three are
		 * read rather than just the one.
		 *
		 * @since 1.8.13
		 *
		 * @param  {Object} response The AJAX payload.
		 * @return {string} The message, or '' when there is none.
		 */
		errorFrom: function ( response ) {

			if ( ! response ) {
				return '';
			}

			return response.error ||
				( response.data && ( response.data.error || response.data.message ) ) ||
				'';
		},

		/**
		 * Swap the label, keeping the trailing arrow.
		 *
		 * @since 1.8.13
		 *
		 * @param {jQuery} $button The button.
		 * @param {string} text    New label.
		 */
		setLabel: function ( $button, text ) {

			var $arrow = $button.find( '.charitable-ai-button-arrow' ).detach();

			$button.text( text ).append( $arrow );
		},

		/**
		 * Put the button back and say what went wrong.
		 *
		 * @since 1.8.13
		 *
		 * @param {jQuery} $button  The button.
		 * @param {Object} response The AJAX payload, when there was one.
		 */
		bridgeFailed: function ( $button, response ) {

			var message = CharitableAiMcp.errorFrom( response ) || charitable_ai_mcp.i18n.installError;

			$button.removeClass( 'is-working' );
			CharitableAiMcp.setLabel( $button, $button.data( 'label' ) );

			window.alert( message );
		}
	};

	$( CharitableAiMcp.init );

}( jQuery ) );
