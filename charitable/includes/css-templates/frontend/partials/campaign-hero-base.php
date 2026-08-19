<?php
/**
 * Template-agnostic base CSS for the Campaign Hero block.
 *
 * The Campaign Hero block can be dragged into a campaign built on *any* template, but
 * the bespoke hero choreography lives in `beacon-hero.php`, scoped to
 * `.charitable-campaign-wrap.template-beacon-*`. Without this partial, a hero on any
 * other template renders with an unstyled `__banner` — which is absolutely positioned
 * and therefore collapses to `height: 0`, making the background image invisible.
 *
 * This file supplies only the structural essentials needed for the hero to be legible
 * on its own: banner fills the section, content sits above it, and the block has height.
 *
 * Deliberately NOT included here are the Beacon-only overlap effects — the avatar's
 * `transform: translateY(...)` overhang and the widget shell's negative
 * `margin-bottom` — because both rely on the following Beacon body row carrying
 * compensating top padding. Applied on templates without that padding they would let
 * the hero overhang whatever follows it.
 *
 * Every selector here is one class less specific than its `beacon-hero.php`
 * counterpart (`.charitable-campaign-wrap` vs
 * `.charitable-campaign-wrap.template-beacon-split`), so on Beacon templates the
 * scoped rules continue to win regardless of source order.
 *
 * Expects (both optional):
 *
 *   $mobile_width — responsive breakpoint in px. Defaults to 800 to match the
 *                   templates' own default when `mw` is absent.
 *
 * @package Charitable
 * @since   1.8.12.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_hero_wrapper = '.charitable-campaign-wrap';
$charitable_hero_mobile  = isset( $mobile_width ) ? max( 320, min( 2000, intval( $mobile_width ) ) ) : 800;
?>

/* ── Campaign Hero — template-agnostic base ──────────────────────────── */

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero {
	position: relative;
	width: 100%;
	min-height: 420px;
	margin: 0;
	display: block;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__banner {
	position: absolute;
	inset: 0;
	background-size: cover;
	background-position: center;
	z-index: 1;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__overlay {
	position: relative;
	z-index: 2;
	display: grid;
	grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
	gap: 32px;
	padding: 32px;
	min-height: 420px;
	align-items: end;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__identity {
	display: flex;
	align-items: flex-end;
	gap: 20px;
	min-width: 0;
	align-self: end;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__avatar {
	width: clamp(105px, 12vw, 157px);
	height: clamp(105px, 12vw, 157px);
	flex-shrink: 0;
	background: #fff;
	border: 1px solid rgba(255, 255, 255, 0.7);
	border-radius: 4px;
	overflow: hidden;
	box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__avatar img {
	display: block;
	width: 100%;
	height: 100%;
	object-fit: cover;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__title {
	color: #fff;
	font-size: clamp(28px, 3.6vw, 44px);
	font-weight: 700;
	margin: 0 0 16px 0;
	text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
	flex: 1 1 auto;
	line-height: 1.15;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget-shell {
	background: #fff;
	border: 1px solid rgba(255, 255, 255, 0.7);
	border-radius: 0;
	overflow: hidden;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
	position: relative;
	align-self: end;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__raised {
	background: var(--charitable-hero-accent, #1d3a8a);
	color: #fff;
	padding: 10px 16px;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__raised-text {
	font-size: 13px;
	font-weight: 600;
	margin-bottom: 6px;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__progress {
	background: rgba(255, 255, 255, 0.25);
	height: 4px;
	border-radius: 2px;
	overflow: hidden;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__progress span {
	display: block;
	height: 100%;
	background: #fff;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget {
	padding: 14px 16px 16px;
}

<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget .charitable-mini-widget {
	max-width: none;
	width: 100%;
}

/* Donate Now CTA inside the hero follows the theme Button color (separate from the primary accent). */
<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__widget .charitable-mini-widget__cta {
	background-color: var(--charitable-hero-button, var(--charitable-hero-accent, #1d3a8a));
}

/* Mobile: stack the hero overlay grid so the widget drops below the identity block. */
@media ( max-width: <?php echo intval( $charitable_hero_mobile ); // phpcs:ignore ?>px ) {
	<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero__overlay {
		grid-template-columns: 1fr;
		padding: 24px 16px;
		min-height: 320px;
		gap: 16px;
	}
	<?php echo $charitable_hero_wrapper; // phpcs:ignore ?> .charitable-campaign-hero {
		min-height: auto;
	}
}
