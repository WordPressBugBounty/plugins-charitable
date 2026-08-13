<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin builder preview CSS for the Beacon Single (1 Column) campaign template.
 *
 * @package Charitable
 * @author  WP Charitable LLC
 * @since   1.8.12
 */

header( 'Content-type: text/css; charset: UTF-8' );

if ( ! function_exists( 'charitable_sanitize_hex_color' ) ) {
	function charitable_sanitize_hex_color( $color ) {
		$color = trim( $color );
		if ( preg_match( '/^#[a-fA-F0-9]{6}$/', $color ) ) {
			return $color;
		}
		return null;
	}
}

if ( ! function_exists( 'charitable_esc_attr_php' ) ) {
	function charitable_esc_attr_php( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

$primary      = isset( $_GET['p'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['p'] ), 0, 6 ) : '#1d3a8a'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['s'] ), 0, 6 ) : '#0D0D0D'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['t'] ), 0, 6 ) : '#5F5F5F'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . substr( preg_replace( '/[^A-Fa-f0-9]/', '', $_GET['b'] ), 0, 6 ) : '#1d3a8a'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$slug    = 'beacon-single';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; // phpcs:ignore ?> {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> {
	--charitable-hero-accent: <?php echo $primary; // phpcs:ignore ?>;
}

/* Hero row in admin preview — keep tight, hide default field padding/borders. */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-header.beacon-hero-row,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-row.beacon-hero-row {
	padding: 0;
	margin: 0;
	border: 0;
	background: transparent;
	overflow: visible;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .row,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .column,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .charitable-field-wrap,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .charitable-field {
	padding: 0;
	margin: 0;
	border: 0;
	background: transparent;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .charitable-field-campaign-hero {
	width: 100%;
}

/* Hide the field title on the hero block in preview — the hero IS the visual. */
<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .charitable-field-campaign-hero > h4.label-title {
	display: none;
}

/* Match frontend hero proportions so authors can preview accurate layout. */
<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .charitable-campaign-hero {
	min-height: 420px;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-hero-row .charitable-campaign-hero__overlay {
	min-height: 420px;
}

/* Body row — single-column stack in the admin preview. */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-row.beacon-body-row {
	padding: 32px 24px;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-body-row > .row {
	display: block;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-body-row .column {
	padding: 0;
	border: 0;
	width: 100%;
	max-width: none;
}

/* Campaign description block — frame the description body (non-headline) with the theme secondary color. */
<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-body-row .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	border: 1px solid <?php echo charitable_esc_attr_php( $secondary ); ?>;
	border-radius: 4px;
	padding: 12px 16px;
}

/* Donor wall — align the headline + empty-state text with the other body field headlines. */
<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-body-row .charitable-field-donation-wall .charitable-field-donation-wall-placeholder {
	display: block;
	padding-left: 5px;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-body-row h5.charitable-field-template-headline {
	font-size: 22px;
	font-weight: 700;
	margin-bottom: 12px;
	line-height: 1.3;
}

@media ( max-width: <?php echo intval( $mobile_width ); // phpcs:ignore ?>px ) {
	<?php echo charitable_esc_attr_php( $wrapper ); ?> .beacon-body-row > .row {
		display: block;
	}
}

/* Social sharing — make the placeholder icons visible in the admin preview. */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing {
	display: flex !important;
	align-items: center;
	flex-wrap: wrap;
	gap: 12px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-preview-social-sharing-headline-container {
	display: flex;
	align-items: center;
	float: none;
	padding: 0;
	margin: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-row {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 8px;
	float: none;
	width: auto;
	margin: 0;
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {
	font-size: 14px !important;
	line-height: 1 !important;
	font-weight: 600 !important;
	text-transform: uppercase !important;
	letter-spacing: 0.02em !important;
	margin: 0 8px 0 0 !important;
	padding: 0 !important;
	color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-field-column {
	display: flex;
	align-items: center;
	margin: 0;
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-placeholder {
	width: 24px;
	height: 24px;
	padding: 0;
	display: inline-block;
	line-height: 0;
	box-sizing: border-box;
}
