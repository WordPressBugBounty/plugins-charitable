<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for Beacon Split campaign template (frontend).
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.8.11.1
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['p'] ), 0, 6 ) : '#1d3a8a'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['s'] ), 0, 6 ) : '#0D0D0D'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['t'] ), 0, 6 ) : '#5F5F5F'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['b'] ), 0, 6 ) : '#1d3a8a'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? max( 320, min( 2000, intval( $_GET['mw'] ) ) ) : 800; // phpcs:ignore

$charitable_slug    = 'beacon-split';
$wrapper            = '.charitable-campaign-wrap.template-' . $charitable_slug;
$hero_row_class     = 'beacon-hero-row';
$body_row_class     = 'beacon-body-row';

require_once '../../admin/campaign-builder/templates/functions-campaign-templates.php';

?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; // phpcs:ignore ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; // phpcs:ignore ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; // phpcs:ignore ?>;
	--charitable_campaign_theme_button: <?php echo $button; // phpcs:ignore ?>;
}

<?php echo $wrapper; // phpcs:ignore ?> {
	--charitable-hero-accent: <?php echo $primary; // phpcs:ignore ?>;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

<?php
// Include the shared hero positioning partial.
include __DIR__ . '/partials/beacon-hero.php';
?>

/* ── Body row — two columns: story + media ──────────────────────────── */

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> {
	padding: 80px 32px 48px;
}

/* When the hero has no logo, the avatar overhang isn't there — pull the story column up. */
<?php echo $wrapper; // phpcs:ignore ?>:not(:has(.charitable-campaign-hero__avatar)) .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-column:first-child {
	margin-top: -60px;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-container,
<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> {
	display: block;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> {
	display: grid;
	grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
	gap: 32px;
	align-items: start;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-column {
	padding: 0;
	border: 0;
	max-width: none;
	flex: none;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-column:first-child .charitable-field {
	margin-bottom: 24px;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-column:last-child .charitable-field {
	margin-bottom: 16px;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> h5.charitable-field-template-headline {
	font-size: 24px;
	font-weight: 700;
	margin: 0 0 24px;
	line-height: 1.3;
	text-wrap: balance;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-hero__title,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-hero__title {
	text-wrap: balance;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-field-content p {
	font-size: 15px;
	line-height: 1.6;
	color: #333;
	margin-bottom: 16px;
}

/* Campaign description — frame the description body (matches preview). */
<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text {
	border: 1px solid <?php echo $secondary; // phpcs:ignore ?>;
	border-radius: 4px;
	padding: 12px 16px;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text p:first-child {
	margin: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text p:last-child {
	margin-bottom: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-field-content > *:first-child {
	margin-top: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-field-photo img {
	display: block;
	width: 100%;
	height: auto;
	border-radius: 0;
}

/* Social sharing — compact horizontal row */

<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-field-social-sharing {
	margin-bottom: 24px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 12px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-template-social-sharing-headline-container {
	margin: 0;
	padding: 0;
	display: flex;
	align-items: center;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {
	font-size: 14px !important;
	font-weight: 600 !important;
	margin: 0 8px 0 0 !important;
	padding: 0 !important;
	line-height: 1 !important;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 8px;
	margin: 0;
	padding: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-social-field-column {
	margin: 0;
	padding: 0;
	display: flex;
	align-items: center;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-campaign-social-link {
	margin: 0 !important;
	padding: 0 !important;
	display: flex;
	align-items: center;
	min-height: 0 !important;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-campaign-social-link a {
	display: flex;
	align-items: center;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-campaign-social-link img,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-campaign-social-link a {
	width: 24px;
	height: 24px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-campaign-social-link p {
	display: none;
}

/* ── Donor wall — Beacon-scoped restyle ───────────────────────────────── */

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal {
	list-style: none;
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
	gap: 16px;
	margin: 0;
	padding: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor {
	display: flex;
	align-items: center;
	gap: 14px;
	padding: 14px 16px;
	background: #fff;
	border: 1px solid #e6e8eb;
	border-radius: 10px;
	transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
	min-width: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor:hover {
	border-color: var(--charitable-hero-accent, #1d3a8a);
	transform: translateY(-1px);
	box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor .avatar {
	width: 44px !important;
	height: 44px !important;
	border-radius: 50%;
	object-fit: cover;
	flex-shrink: 0;
	background: #f1f3f5;
	border: 1px solid #e6e8eb;
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor .donor-name {
	font-size: 14px;
	font-weight: 600;
	line-height: 1.25;
	color: #111827;
	margin: 0;
	padding: 0;
	min-width: 0;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	flex: 1 1 auto;
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor .donor-donation-amount {
	font-size: 13px;
	font-weight: 600;
	line-height: 1.25;
	color: var(--charitable-hero-accent, #1d3a8a);
	margin-top: 4px;
	letter-spacing: 0.01em;
}

/* Reflow name + amount into a single column so they stack inside the card. */
<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor::after {
	content: none;
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor {
	flex-wrap: wrap;
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor .donor-name {
	flex: 1 1 calc(100% - 60px);
	white-space: normal;
}

<?php echo $wrapper; // phpcs:ignore ?> .donors-list.donors-list-horizontal .donor .donor-donation-amount {
	flex: 1 1 calc(100% - 60px);
	margin-left: 58px;
	margin-top: -2px;
}

/* Empty-state message — match the body typography rather than the theme default. */
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field_donation-wall p,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-donation-wall .charitable-campaign-field-inner > .charitable-campaign-field_donation-wall p {
	font-family: inherit;
	font-size: 15px;
	font-weight: 400;
	font-style: normal;
	line-height: 1.6;
	color: #6b7280;
	margin: 8px 0 0;
	padding: 0 !important;
	text-align: left;
}

/* ── Mobile reflow ────────────────────────────────────────────────────── */

@media ( max-width: <?php echo intval( $mobile_width ); // phpcs:ignore ?>px ) {
	<?php echo $wrapper; // phpcs:ignore ?> .<?php echo $body_row_class; // phpcs:ignore ?> {
		grid-template-columns: 1fr;
		padding: 32px 16px;
		gap: 24px;
	}
	<?php echo $wrapper; // phpcs:ignore ?>:not(:has(.charitable-campaign-hero__avatar)) .<?php echo $body_row_class; // phpcs:ignore ?> .charitable-campaign-column:first-child {
		margin-top: 0;
	}
}
