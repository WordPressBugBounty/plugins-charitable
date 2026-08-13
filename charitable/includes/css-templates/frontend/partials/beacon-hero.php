<?php
/**
 * Shared CSS partial for the Beacon family hero.
 *
 * Used by every Beacon variant (beacon-split, beacon-single, etc.) to keep
 * the overlapping hero + donation widget positioning in one place. Expects
 * two variables set by the including file:
 *
 *   $wrapper        — the CSS wrapper selector (e.g. `.charitable-campaign-wrap.template-beacon-split`)
 *   $hero_row_class — the CSS class on the locked hero row (e.g. `beacon-hero-row`)
 *
 * @package Charitable
 * @since   1.8.11.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $wrapper ) || ! isset( $hero_row_class ) ) {
	return;
}
?>

/* ── Beacon hero — shared positioning ────────────────────────────────── */

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $hero_row_class; // phpcs:ignore ?> {
	position: relative;
	overflow: visible;
	padding: 0;
	margin: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $hero_row_class; // phpcs:ignore ?> .charitable-campaign-column {
	padding: 0;
	border: 0;
	flex: 1 1 100%;
	max-width: 100%;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero {
	position: relative;
	width: 100%;
	min-height: 420px;
	margin: 0;
	display: block;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__banner {
	position: absolute;
	inset: 0;
	background-size: cover;
	background-position: center;
	z-index: 1;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__overlay {
	position: relative;
	z-index: 2;
	display: grid;
	grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
	gap: 32px;
	padding: 32px 32px 24px;
	min-height: 420px;
	align-items: end;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__identity {
	display: flex;
	align-items: flex-end;
	gap: 20px;
	min-width: 0;
	align-self: end;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__avatar {
	width: clamp(105px, 12vw, 157px);
	height: clamp(105px, 12vw, 157px);
	flex-shrink: 0;
	background: #fff;
	border: 1px solid rgba(255, 255, 255, 0.7);
	border-radius: 4px;
	overflow: hidden;
	box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
	transform: translateY(calc(67% - 30px));
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__avatar img {
	display: block;
	width: 100%;
	height: 100%;
	object-fit: cover;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__title {
	color: #fff;
	font-size: clamp(28px, 3.6vw, 44px);
	font-weight: 700;
	margin: 0 0 16px 0;
	text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
	flex: 1 1 auto;
	line-height: 1.15;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget-shell {
	background: #fff;
	border: 1px solid rgba(255, 255, 255, 0.7);
	border-radius: 0;
	overflow: hidden;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
	position: relative;
	align-self: end;
	margin-bottom: -80px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__raised {
	background: var(--charitable-hero-accent, #1d3a8a);
	color: #fff;
	padding: 10px 16px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__raised-text {
	font-size: 13px;
	font-weight: 600;
	margin-bottom: 6px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__progress {
	background: rgba(255, 255, 255, 0.25);
	height: 4px;
	border-radius: 2px;
	overflow: hidden;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__progress span {
	display: block;
	height: 100%;
	background: #fff;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget {
	padding: 14px 16px 16px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget .charitable-mini-widget {
	max-width: none;
	width: 100%;
}

/* Donate Now CTA inside the hero follows the theme Button color (separate from the primary accent). */
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget .charitable-mini-widget__cta {
	background-color: var(--charitable-hero-button, var(--charitable-hero-accent, #1d3a8a));
}

/* Mobile: stack the hero overlay grid, drop the widget below the banner. */
@media ( max-width: <?php echo intval( $mobile_width ); // phpcs:ignore ?>px ) {
	<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__overlay {
		grid-template-columns: 1fr;
		padding: 24px 16px;
		min-height: 320px;
		gap: 16px;
	}
	<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__avatar {
		transform: none;
	}
	<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget-shell {
		margin-bottom: 0;
	}
	<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero {
		min-height: auto;
	}
}
