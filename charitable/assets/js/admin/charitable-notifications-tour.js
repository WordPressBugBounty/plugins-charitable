/**
 * Notifications V2 — one-time onboarding tour.
 *
 * Shown once per user after upgrading to 1.8.14+. Highlights the
 * notifications badge in the admin header and the Latest Updates section
 * on the Charitable dashboard using Shepherd.js.
 *
 * @since 1.8.12
 */
( function ( $ ) {
	'use strict';

	$( function () {
		if ( typeof Shepherd === 'undefined' ) {
			return;
		}

		var dismissed = false;

		function dismissTour() {
			if ( dismissed ) {
				return;
			}
			dismissed = true;
			$.post( CHARITABLE_NOTIF_TOUR.ajaxurl, {
				action: 'charitable_dismiss_notifications_tour',
				nonce:  CHARITABLE_NOTIF_TOUR.nonce,
			} );
		}

		function startTour() {
			// Always observe — the sidebar auto-open JS may run after this script,
			// so we can't rely on the class being present at DOM-ready time.
			// If the sidebar is already closed (and stays closed), start after a
			// short settling delay so any pending open animation finishes first.
			var observer = new MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					if ( ! $( 'body' ).hasClass( 'charitable-show-notifications' ) ) {
						observer.disconnect();
						tour.start();
					}
				} );
			} );
			observer.observe( document.body, { attributes: true, attributeFilter: [ 'class' ] } );

			// Safety net: if the sidebar never opens at all, start after 800ms.
			setTimeout( function () {
				if ( ! $( 'body' ).hasClass( 'charitable-show-notifications' ) && ! tour.isActive() ) {
					observer.disconnect();
					tour.start();
				}
			}, 800 );
		}

		var tour = new Shepherd.Tour( {
			defaultStepOptions: {
				cancelIcon:  { enabled: true },
				exitOnEsc:   true,
				classPrefix: 'wpchar',
				classes:     'charitable-notif-tour-step',
				scrollTo:    { behavior: 'smooth', block: 'center' },
				modalOverlayOpeningPadding: 8,
				modalOverlayOpeningRadius:  6,
				tippyOptions: { theme: 'charitable-notif', maxWidth: 320 },
			},
			tourName:        'charitable-notifications-v2-intro',
			useModalOverlay: true,
		} );

		// Step 1 — notifications badge in the admin bar.
		tour.addStep( {
			id:    'notifications-badge',
			title: 'Updated Notifications Center',
			text:  'Health alerts, campaign milestones, and important updates - all in one place. Use the settings panel to control exactly which types you see.',
			attachTo: {
				element: '#charitable-admin-header .charitable-header-logos ul li a.charitable-notification-inbox',
				on:      'bottom',
			},
			tippyOptions: {
				offset: '-325,12', // negative skidding shifts tooltip LEFT away from right browser edge
			},
			buttons: [
				{
					text:    'Next',
					action:  tour.next,
					classes: 'charitable-tour-btn-primary',
				},
				{
					text:    'Skip',
					action:  tour.cancel,
					classes: 'charitable-tour-btn-skip',
				},
			],
		} );

		// Step 2 — Latest Updates dashboard section.
		tour.addStep( {
			id:      'latest-updates',
			classes: 'charitable-notif-tour-step charitable-notif-tour-step--white',
			title:   'Updated Latest Updates',
			text:  'This panel keeps you up to date on new plugin versions and updates from Charitable - learn about new features that can help you drive your campaigns.',
			attachTo: {
				element: '#charitable-dashboard-v2-latest-updates',
				on:      'left',
			},
			buttons: [
				{
					text:    'Got it',
					action:  tour.complete,
					classes: 'charitable-tour-btn-primary',
				},
			],
		} );

		// Force orange arrow after each step renders.
		// Sets only the placement-specific border side so it beats Shepherd's
		// injected border-bottom-color / border-top-color etc. rules.
		function colorArrow() {
			setTimeout( function () {
				document.querySelectorAll( '.tippy-popper.shepherd .tippy-arrow' ).forEach( function ( el ) {
					var popper    = el.closest( '.tippy-popper' );
					var placement = popper ? ( popper.getAttribute( 'x-placement' ) || 'bottom' ) : 'bottom';
					// White step uses left placement — arrow should be white to match header.
					var isWhite   = popper && popper.querySelector( '.charitable-notif-tour-step--white' );
					var color     = isWhite ? '#fff' : '#E38632';
					if ( placement.indexOf( 'bottom' ) === 0 ) {
						el.style.borderBottomColor = color;
					} else if ( placement.indexOf( 'top' ) === 0 ) {
						el.style.borderTopColor = color;
					} else if ( placement.indexOf( 'left' ) === 0 ) {
						el.style.borderLeftColor = color;
					} else {
						el.style.borderRightColor = color;
					}

					// Step 1 only: hardcode arrow position and shift the entire tooltip.
					if ( placement.indexOf( 'bottom' ) === 0 && ! isWhite ) {
						el.style.left = '292px';
						// Shift the entire tooltip box — tweak marginLeft to move left/right.
						popper.style.marginLeft = '13px';
					}

					} );
			}, 30 );
		}

		tour.on( 'show',     colorArrow );
		tour.on( 'complete', dismissTour );
		tour.on( 'cancel',   dismissTour );

		startTour();
	} );
}( jQuery ) );
