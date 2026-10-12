<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display notices.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.3.0
 * @version 1.8.8.6
 * @version 1.8.13.1
 */

if ( ! isset( $view_args['notices'] ) ) {
	return;
}

$charitable_notices = array_filter( $view_args['notices'] );

if ( empty( $charitable_notices ) ) {
	return;
}

/*
 * Whether this render is the result of a submission (1.8.13.1).
 *
 * This template is generic: the same "error" bucket carries both real validation
 * failures and page-level notices that merely use the error styling -- the
 * admin-only "Test mode is active" notice being the one that matters, since it is
 * present on an ordinary GET of every donation form.
 *
 * So role="alert" is set on every error group below (harmless either way: a live
 * region only announces content that changes AFTER it exists, so a notice already
 * in the page on load is not read out), but the marker that tells charitable.js it
 * may MOVE FOCUS here is set only on a POST. Without that distinction, auto-focusing
 * the summary would steal focus on every page load for any admin with test mode on.
 */
$charitable_is_submission = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );

foreach ( $charitable_notices as $type => $messages ) : // phpcs:ignore
	if ( 'error' == $type ) : // phpcs:ignore
		$type = 'errors'; // phpcs:ignore
	endif;

	$charitable_error_attr = '';

	if ( 'errors' === $type ) {
		$charitable_error_attr = ' role="alert" tabindex="-1"';

		if ( $charitable_is_submission ) {
			$charitable_error_attr .= ' data-charitable-error-summary="1"';
		}
	}
	?>
	<div class="charitable-notice charitable-form-<?php echo esc_attr( $type ); ?>"<?php echo $charitable_error_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string built above, no user input. ?>>
		<ul class="charitable-notice-<?php echo esc_attr( $type ); ?> <?php echo esc_attr( $type ); ?>">
			<?php foreach ( $messages as $charitable_message ) : ?>
				<li><?php echo $charitable_message; // phpcs:ignore ?></li>
			<?php endforeach ?>
		</ul><!-- charitable-notice-<?php esc_attr( $type ); ?> -->
	</div><!-- .charitable-notices -->
<?php endforeach ?>
