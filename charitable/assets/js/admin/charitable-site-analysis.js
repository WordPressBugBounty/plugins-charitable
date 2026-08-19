( function ( $ ) {
	'use strict';

	var TIER_LABEL = { foundational: 'Fix first', 'config-health': 'Quick wins', opportunity: 'Opportunities' };
	var TIER_ORDER = [ 'foundational', 'config-health', 'opportunity' ];
	var OPP_VISIBLE = 5; // collapse opportunities beyond this many.
	var OPP_PIN = 'newsletter'; // always keep this opportunity in the visible set (promote if below the fold).
	// PLACEHOLDER personalization hints (until the backend personalizes detail copy per featureId).
	var AFTER_OPTIN = { recurring: 'quantified after opt-in', 'fee-relief': 'personalized after opt-in' };

	// Allow only http(s) (or scheme-less) URLs into an href. cta.url and recommendedPlan.url come from
	// the remote recommendations feed, so a `javascript:` or `data:` URL from a compromised or spoofed
	// endpoint would otherwise execute inside wp-admin with the logged-in admin's privileges.
	//
	// Deliberately NOT implemented with the URL constructor and a catch that returns '#': where URL is
	// unavailable that silently kills every CTA on the page.
	function safeHref( url ) {
		if ( ! url ) { return '#'; }
		// Strip whitespace and control characters anywhere in the string before testing the scheme:
		// browsers ignore them when resolving, so "java\tscript:" and " javascript:" both execute.
		var candidate = String( url ).replace( /[\s\x00-\x1f\x7f]/g, '' );
		// A scheme is present only if a colon precedes any /, ? or #. Scheme-relative ("//host/x") and
		// path-relative URLs carry no scheme of their own and inherit the admin origin, so they're fine.
		if ( /^[a-z][a-z0-9+.\-]*:/i.test( candidate ) ) {
			return /^https?:/i.test( candidate ) ? url : '#';
		}
		return url;
	}

	// Upgrade recs point at the plugin's tagged upgrade URL; config-fix recs open the relevant in-plugin
	// screen (cfg.internal); everything else falls back to the rec's own (external) CTA link.
	function ctaHref( rec, cfg ) {
		if ( 'upgrade' === rec.action && cfg.upgrade_url ) {
			return cfg.upgrade_url + ( cfg.upgrade_url.indexOf( '?' ) > -1 ? '&' : '?' ) + 'utm_content=' + encodeURIComponent( rec.featureId || '' );
		}
		if ( ctaIsInternal( rec, cfg ) ) {
			return cfg.internal[ rec.name ];
		}
		// Growth/how-to recs have no in-plugin toggle; point them at a specific doc article
		// (cfg.docs) instead of the engine's generic /documentation/ landing page.
		if ( cfg.docs && rec.name && cfg.docs[ rec.name ] ) {
			return cfg.docs[ rec.name ];
		}
		return rec.cta && rec.cta.url ? rec.cta.url : '#';
	}

	// True when a rec maps to an in-plugin admin screen (opens in the same tab, not a new one).
	function ctaIsInternal( rec, cfg ) {
		return 'upgrade' !== rec.action && !! ( cfg.internal && rec.name && cfg.internal[ rec.name ] );
	}

	// Score gain badge. Uses the backend's rec.scoreGain when present; until then, a PLACEHOLDER
	// derived from priority so only the fixable tiers (foundational / config-health) show points.
	function scoreGain( rec ) {
		if ( rec.scoreGain != null ) { return rec.scoreGain; }
		if ( 'foundational' === rec.tier ) { return Math.max( 10, Math.min( 20, Math.round( ( rec.priority || 0 ) / 55 ) ) ); }
		if ( 'config-health' === rec.tier ) { return Math.max( 4, Math.min( 12, Math.round( ( rec.priority || 0 ) / 70 ) ) ); }
		return 0;
	}

	// Fallback Font Awesome icon per recommendation category, used when a rec has no bundled addon icon.
	var CAT_ICON = {
		payments: 'fa-credit-card',
		donations: 'fa-bullhorn',
		'donor-experience': 'fa-heart',
		growth: 'fa-line-chart',
		compliance: 'fa-shield'
	};
	// More specific Font Awesome icons, keyed by rec featureId (opportunities without a bundled icon)
	// or by config-fix check name (those recs have an empty featureId; their name is the check id).
	var FEATURE_ICON = {
		'seo-aioseo': 'fa-search',
		offline: 'fa-money',
		campaigns: 'fa-bullhorn',
		gateways: 'fa-credit-card',
		'test-mode-on': 'fa-flask',
		'no-gateway': 'fa-credit-card',
		'single-gateway': 'fa-credit-card',
		'offline-off': 'fa-money',
		'receipt-email-off': 'fa-envelope',
		'recurring-no-capable-gateway': 'fa-refresh',
		'no-published-campaign': 'fa-bullhorn',
		'no-donation-yet': 'fa-heart',
		'campaigns-no-goal': 'fa-bullseye',
		'spam-off-with-donations': 'fa-shield',
		'low-avg-gift': 'fa-line-chart',
		'stalled-momentum': 'fa-clock-o'
	};

	// Build the left-hand icon for a recommendation: the bundled addon icon when we have one, otherwise
	// a category/feature Font Awesome icon. Config-fix recs have an empty featureId, so fall back to name.
	function recIcon( r, cfg ) {
		var $icon = $( '<span class="charitable-sa-rec-icon" aria-hidden="true"></span>' );
		var url = cfg.rec_icons && r.featureId && cfg.rec_icons[ r.featureId ];
		if ( url ) {
			$icon.append( $( '<img alt="" />' ).attr( 'src', url ) );
		} else {
			var key = r.featureId || r.name;
			var fa = FEATURE_ICON[ key ] || CAT_ICON[ r.category ] || 'fa-lightbulb-o';
			$icon.addClass( 'charitable-sa-rec-icon--fa' ).append( $( '<i class="fa"></i>' ).addClass( fa ) );
		}
		return $icon;
	}

	function renderRec( r, tier, cfg, complete ) {
		var $c = $( '<div class="charitable-sa-rec tier-' + tier + '"></div>' );
		$c.append( recIcon( r, cfg ) );
		var $main = $( '<div class="charitable-sa-rec-main"></div>' );
		var $h = $( '<strong></strong>' ).text( r.headline );
		var gain = scoreGain( r );
		if ( gain > 0 ) { $h.append( $( '<span class="charitable-sa-gain"></span>' ).text( '+' + gain + ' pts' ) ); }
		$main.append( $h );
		var $d = $( '<div class="charitable-sa-detail"></div>' ).text( r.detail );
		// The "quantified/personalized after opt-in" note only makes sense before opt-in; once the
		// score is complete the detail is already personalized, so suppress it.
		if ( ! complete && AFTER_OPTIN[ r.featureId ] ) { $d.append( $( '<em class="charitable-sa-after"></em>' ).text( ' (' + AFTER_OPTIN[ r.featureId ] + ')' ) ); }
		$main.append( $d );
		$c.append( $main );
		if ( r.cta && r.cta.label ) {
			var $cta = $( '<a class="button"></a>' ).attr( 'href', safeHref( ctaHref( r, cfg ) ) ).text( r.cta.label );
			// Internal (in-plugin) links open in the same tab; external links open in a new tab.
			if ( ! ctaIsInternal( r, cfg ) ) { $cta.attr( 'target', '_blank' ).attr( 'rel', 'noopener noreferrer' ); }
			$c.append( $cta );
		}
		return $c;
	}

	function bandColor( band ) { return band === 'great' ? '#5AA152' : band === 'good' ? '#dba617' : '#d63638'; }
	function bandLabel( band ) { return band === 'great' ? 'Great' : band === 'good' ? 'Good' : 'Needs work'; }
	function dimColor( v ) { return v >= 70 ? '#5AA152' : v >= 40 ? '#dba617' : '#d63638'; }
	var DIM_LABEL = { activation: 'Activation', health: 'Health', growth: 'Growth', optimization: 'Optimization' };
	// Plain-language explanation of each scored area, shown in a tooltip on the "?" icon beside the label.
	var DIM_TIP = {
		activation: 'Getting live and bringing in your first donations.',
		health: 'Core setup best practices: receipts, campaign goals, and more than one way to give.',
		growth: 'Recurring giving, list building, and steady donation momentum.',
		optimization: 'Getting more from every gift: larger average gifts, covered fees, and repeat donors.'
	};

	function renderScore( score, $out ) {
		if ( ! score ) { return; }
		var pct = Math.max( 0, Math.min( 100, score.value || 0 ) );
		var col = bandColor( score.band );
		var $hero = $( '<div class="charitable-sa-score charitable-sa-score--live"></div>' );
		// Start the ring empty and the number at 0; animateScore() fills them on load (see below).
		var $ring = $( '<div class="charitable-sa-ring"></div>' )
			.attr( 'style', 'background: conic-gradient(' + col + ' 0 0%, #edeef0 0% 100%);' );
		var $ringNum = $( '<b></b>' ).text( '0' );
		$ring.append( $( '<span class="charitable-sa-ring-v"></span>' ).append( $ringNum ).append( $( '<s></s>' ).text( '/ 100' ) ) );
		$hero.append( $ring );
		var bars = []; // { $el, target } - filled while building the dimension bars, animated on load.

		var $meta = $( '<div class="charitable-sa-score-meta"></div>' );
		var $title = $( '<h3 class="charitable-sa-score-title"></h3>' ).text( 'Fundraising Score ' );
		if ( score.complete ) {
			$title.append( $( '<span class="charitable-sa-band"></span>' ).text( bandLabel( score.band ) ).attr( 'style', 'background:' + col + ';' ) );
		} else {
			var done = [ 'activation', 'health', 'growth', 'optimization' ].filter( function ( k ) {
				return score.dimensions && score.dimensions[ k ] && score.dimensions[ k ].complete;
			} ).length;
			$title.append( $( '<span class="charitable-sa-estpill"></span>' ).text( 'Estimated · ' + done + ' of 4 areas' ) );
		}
		$meta.append( $title );

		if ( ( score.potential || 0 ) > ( score.value || 0 ) ) {
			$meta.append( $( '<div class="charitable-sa-score-desc"></div>' ).text( 'Reaches ' + score.potential + ' if you complete the items below.' ) );
		} else if ( ! score.complete ) {
			$meta.append( $( '<div class="charitable-sa-score-desc"></div>' ).text( 'Share your fundraising stats below to complete your Growth & Optimization scores.' ) );
		}

		var $dims = $( '<div class="charitable-sa-dims"></div>' );
		[ 'activation', 'health', 'growth', 'optimization' ].forEach( function ( k ) {
			var d = ( score.dimensions && score.dimensions[ k ] ) || {};
			var $dim = $( '<div class="charitable-sa-dim"></div>' );
			var $dimN = $( '<span class="charitable-sa-dim-n"></span>' ).text( DIM_LABEL[ k ] );
			$dimN.append(
				$( '<span class="charitable-sa-tip" tabindex="0" role="img"></span>' )
					.attr( 'aria-label', DIM_LABEL[ k ] + ': ' + DIM_TIP[ k ] )
					.attr( 'data-tip', DIM_TIP[ k ] )
					.text( '?' )
			);
			$dim.append( $dimN );
			var $bar = $( '<span class="charitable-sa-dim-bar"></span>' );
			if ( d.complete ) {
				var barVal = Math.max( 0, Math.min( 100, d.value || 0 ) );
				var $barFill = $( '<i></i>' ).attr( 'style', 'width:0%; background:' + dimColor( d.value || 0 ) + ';' );
				bars.push( { $el: $barFill, target: barVal } );
				$bar.append( $barFill );
			} else {
				$dim.addClass( 'charitable-sa-dim--locked' );
			}
			$dim.append( $bar );
			if ( ! d.complete ) { $dim.append( $( '<span class="charitable-sa-dim-hint"></span>' ).text( 'Share stats to score' ) ); }
			$dims.append( $dim );
		} );
		$meta.append( $dims );
		$hero.append( $meta );
		$out.append( $hero );
		animateScore( $ring, $ringNum, col, pct, bars );
	}

	// Animate the score ring (conic fill), its number (count up), and the dimension bars from zero to
	// their values when the report renders. Honors prefers-reduced-motion, defers until the tab is
	// visible (rAF is paused while hidden), and always guarantees a correct final state.
	function animateScore( $ring, $ringNum, col, pct, bars ) {
		var done = false;
		function setRing( v ) { $ring.attr( 'style', 'background: conic-gradient(' + col + ' 0 ' + v + '%, #edeef0 ' + v + '% 100%);' ); }
		function finalState() {
			if ( done ) { return; }
			done = true;
			setRing( pct ); $ringNum.text( pct );
			bars.forEach( function ( b ) { b.$el.css( 'width', b.target + '%' ); } );
		}
		var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( reduce || ! window.requestAnimationFrame ) { finalState(); return; }

		function run() {
			if ( done ) { return; }
			var DURATION = 900, start = null;
			function frame( ts ) {
				if ( done ) { return; }
				if ( start === null ) { start = ts; }
				var t = Math.min( 1, ( ts - start ) / DURATION );
				var e = 1 - Math.pow( 1 - t, 3 ); // easeOutCubic
				setRing( pct * e );
				$ringNum.text( Math.round( pct * e ) );
				bars.forEach( function ( b ) { b.$el.css( 'width', ( b.target * e ) + '%' ); } );
				if ( t < 1 ) { window.requestAnimationFrame( frame ); } else { finalState(); }
			}
			window.requestAnimationFrame( frame );
			window.setTimeout( finalState, DURATION + 600 ); // safety net if rAF gets throttled mid-run
		}

		if ( document.hidden ) {
			// rAF is paused while the tab is hidden; animate the first time it becomes visible, but still
			// guarantee a correct final state in case the tab is never viewed.
			var onShow = function () {
				if ( ! document.hidden ) { document.removeEventListener( 'visibilitychange', onShow ); run(); }
			};
			document.addEventListener( 'visibilitychange', onShow );
			window.setTimeout( finalState, 5000 );
		} else {
			run();
		}
	}

	function renderReport( report, $out, cfg ) {
		$out.empty();
		var recs = ( report && report.recommendations ) || [];
		var complete = !! ( report && report.score && report.score.complete );
		var byTier = {};
		recs.forEach( function ( r ) { ( byTier[ r.tier ] = byTier[ r.tier ] || [] ).push( r ); } );

		// Score hero first - top of the report.
		renderScore( report && report.score, $out );

		// Recommended plan second.
		if ( report && report.recommendedPlan ) {
			var p = report.recommendedPlan;
			var $plan = $( '<div class="charitable-sa-plan"></div>' );
			$plan.append( $( '<strong></strong>' ).text( 'Recommended plan: ' + p.label ) );
			var unlocks = ( p.unlocks && p.unlocks.length ) ? 'Unlocks ' + p.unlocks.join( ', ' ) + '. ' : '';
			var tail = ( report.score && report.score.potential > report.score.value )
				? ' Acting on these would lift your score toward ' + report.score.potential + '.'
				: '';
			$plan.append( $( '<div></div>' ).text( unlocks + tail ) );
			$plan.append( $( '<a class="button button-primary" style="margin-top:8px;"></a>' ).attr( 'href', safeHref( p.url ) ).attr( 'target', '_blank' ).attr( 'rel', 'noopener noreferrer' ).text( 'See ' + p.label ) );
			$out.append( $plan );
		}

		TIER_ORDER.forEach( function ( tier ) {
			if ( ! byTier[ tier ] ) { return; }
			var list = byTier[ tier ];
			// Promote the pinned opportunity into the last visible slot if it sorted below the fold.
			if ( 'opportunity' === tier && OPP_PIN ) {
				var pinIdx = -1;
				for ( var j = 0; j < list.length; j++ ) { if ( OPP_PIN === list[ j ].featureId ) { pinIdx = j; break; } }
				if ( pinIdx >= OPP_VISIBLE ) { list.splice( OPP_VISIBLE - 1, 0, list.splice( pinIdx, 1 )[ 0 ] ); }
			}
			var $g = $( '<div class="charitable-sa-group"></div>' );
			$g.append( $( '<h3></h3>' ).text( TIER_LABEL[ tier ] || tier ) );

			var visible = ( 'opportunity' === tier ) ? Math.min( OPP_VISIBLE, list.length ) : list.length;
			for ( var i = 0; i < visible; i++ ) { $g.append( renderRec( list[ i ], tier, cfg, complete ) ); }

			if ( 'opportunity' === tier && list.length > visible ) {
				var hidden = list.slice( visible );
				var $more = $( '<p class="charitable-sa-more"></p>' ).text( '+ ' + hidden.length + ' more opportunities' );
				$more.on( 'click', function () {
					hidden.forEach( function ( r ) { $more.before( renderRec( r, tier, cfg, complete ) ); } );
					$more.remove();
				} );
				$g.append( $more );
			}
			$out.append( $g );
		} );

		if ( ! recs.length && ! ( report && report.recommendedPlan ) ) {
			$out.append( $( '<p></p>' ).text( 'Your setup looks great - no recommendations right now.' ) );
		}
	}

	function appendRefresh( $out, cfg, agoText, refreshOk, waitText ) {
		if ( $out.find( '.charitable-sa-refresh-row' ).length ) { return; }
		var $row = $( '<p class="charitable-sa-refresh-row"></p>' );
		if ( agoText ) { $row.append( $( '<span class="charitable-sa-analyzed"></span>' ).text( agoText + '. ' ) ); }
		if ( refreshOk ) {
			var label = ( cfg.i18n && cfg.i18n.refresh ) || 'Refresh';
			var $link = $( '<button type="button" class="button-link charitable-sa-refresh"></button>' ).text( label );
			$link.on( 'click', function () {
				$( '#charitable-site-analysis-run' ).data( 'refresh', 1 ).trigger( 'click' );
			} );
			$row.append( $link );
		} else {
			// Within the once-a-day refresh cooldown: show when it's available instead of an active link.
			var msg = waitText
				? ( ( cfg.i18n && cfg.i18n.refresh_wait ) || 'You can refresh again in %s' ).replace( '%s', waitText )
				: ( ( cfg.i18n && cfg.i18n.refresh_soon ) || 'You can refresh again tomorrow' );
			$row.append( $( '<span class="charitable-sa-refresh-wait"></span>' ).text( msg ) );
		}
		$out.append( $row );
	}

	$( function () {
		var cfg = window.charitable_site_analysis || {};
		$( '#charitable-site-analysis-run' ).on( 'click', function () {
			var $btn = $( this ),
				$spin = $( '#charitable-site-analysis-spinner' ),
				$err = $( '#charitable-site-analysis-error' ),
				$out = $( '#charitable-site-analysis-results' );
			$err.hide();
			var refresh = $btn.data( 'refresh' ) ? 1 : 0;
			$btn.removeData( 'refresh' );
			$out.empty();
			// On refresh the pre-run block (and its spinner) is hidden, so show a loading state inside the
			// results area instead of leaving it blank while the API responds.
			if ( refresh ) {
				$out.append( $( '<div class="charitable-sa-loading"></div>' )
					.append( $( '<span class="spinner is-active"></span>' ) )
					.append( $( '<span></span>' ).text( ( cfg.i18n && cfg.i18n.running ) || 'Analyzing your site…' ) ) );
			}
			$btn.prop( 'disabled', true );
			$spin.addClass( 'is-active' );
			// The usage toggle is absent when site-wide usage tracking is already on -> treat as consented.
			var $consent = $( '#charitable-site-analysis-consent' );
			var consent = ( 0 === $consent.length || $consent.is( ':checked' ) ) ? 1 : 0;
			$.post( cfg.ajax_url, {
				action: 'charitable_site_analysis',
				nonce: cfg.nonce,
				consent: consent,
				refresh: refresh
			} ).done( function ( res ) {
				if ( res && res.success ) {
					$( '.charitable-site-analysis' ).addClass( 'charitable-sa-has-results' );
					renderReport( res.data, $out, cfg );
					// A fresh analysis just ran, so the refresh cooldown restarts - disable it until reload.
					appendRefresh( $out, cfg, '', false, '' );
				} else {
					var msg = ( res && res.data && res.data.message ) || ( cfg.i18n && cfg.i18n.error ) || 'Error.';
					$err.show().find( 'p' ).text( msg );
					if ( res && res.data && res.data.stale ) {
						$( '.charitable-site-analysis' ).addClass( 'charitable-sa-has-results' );
						renderReport( res.data.stale, $out, cfg );
						// The fetch failed (stale shown), so let the user retry; the server still rate-limits.
						appendRefresh( $out, cfg, '', true, '' );
					}
				}
			} ).fail( function () {
				$err.show().find( 'p' ).text( ( cfg.i18n && cfg.i18n.error ) || 'Error.' );
			} ).always( function () {
				$out.find( '.charitable-sa-loading' ).remove();
				$btn.prop( 'disabled', false );
				$spin.removeClass( 'is-active' );
			} );
		} );

		// On load, render the last cached report (if any) so returning to the tab shows the last
		// analysis instead of the pre-run state. Refresh re-runs against the live API.
		if ( cfg.cached ) {
			var $cachedOut = $( '#charitable-site-analysis-results' );
			$( '.charitable-site-analysis' ).addClass( 'charitable-sa-has-results' );
			renderReport( cfg.cached, $cachedOut, cfg );
			appendRefresh( $cachedOut, cfg, cfg.cached_ago, cfg.refresh_ok, cfg.refresh_wait );
		}
	} );
} )( jQuery );
