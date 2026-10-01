<?php
/**
 * Charitable AJAX Functions.
 *
 * Functions used with ajax hooks.
 *
 * @package   Charitable/Functions/AJAX
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.2.3
 * @version   1.8.12.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'charitable_ajax_get_donation_form' ) ) :

	/**
	 * Returns the donation form content for a particular campaign, through AJAX.
	 *
	 * @since   1.2.3
	 *
	 * @return  void
	 */
	function charitable_ajax_get_donation_form() { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification handled by caller
		if ( ! isset( $_POST['campaign_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error();
		}

		/* Load the template files. */
		require_once( charitable()->get_path( 'includes' ) . 'public/charitable-template-functions.php' );
		require_once( charitable()->get_path( 'includes' ) . 'public/charitable-template-hooks.php' );

		$campaign = new Charitable_Campaign( $_POST['campaign_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		ob_start();

		$campaign->get_donation_form()->render();

		/**
		 * Strip any shortcodes that haven't been rendered yet.
		 *
		 * @see https://github.com/Charitable/Charitable/issues/708
		 */
		$output = preg_replace( '~(?:\[/?)[^/\]]+/?\]~s', '', ob_get_clean() );

		wp_send_json_success( $output );

		die();
	}

endif;

if ( ! function_exists( 'charitable_plupload_image_upload' ) ) :

	/**
	 * Upload an image via plupload.
	 *
	 * @return void
	 */
	function charitable_plupload_image_upload() {
		// absint(), not FILTER_SANITIZE_NUMBER_INT: that filter strips non-digits rather than
		// rejecting them, so "1e5" resolves to post 15 and "a1b2" to post 12.
		$post_id  = absint( filter_input( INPUT_POST, 'post_id' ) );
		$field_id = (string) filter_input( INPUT_POST, 'field_id' );

		check_ajax_referer( 'charitable-upload-images-' . $field_id );

		/*
		 * Default-refuse anonymous callers.
		 *
		 * This handler is registered on wp_ajax_nopriv_, so it is reachable by logged-out
		 * visitors. The nonce is not an access control for them: wp_create_nonce() resolves
		 * against user 0 with an empty session token, so the token printed into any public
		 * picture field is identical for every anonymous visitor and valid for ~24h. One GET
		 * of a public form yields a token anyone can reuse to write files into the media
		 * library. charitable_picture_uploads_enabled() holds the opt-in, and the picture
		 * field template asks it the same question so the form cannot draw a working
		 * uploader for a visitor this endpoint will refuse.
		 *
		 * Errors are returned with a 200 on purpose. plupload's HTML5 runtime treats any
		 * status >= 400 as an HTTP error and fires Error instead of FileUploaded, and the
		 * Error binding in charitable-plupload-fields.js only logs to the console - so a
		 * status code here would hang the "Uploading..." row with nothing shown to the
		 * visitor. A 200 lands on the ! r.success branch, which renders the message.
		 */
		if ( ! charitable_picture_uploads_enabled( $field_id, $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Please log in or create an account to upload an image.', 'charitable' ) ) );
		}

		/*
		 * A request can pass the nonce and still carry no usable file - either nothing at all,
		 * or an array shape (async-upload[]) that makes core compare an array against an int
		 * and then use it as an array offset. Require the one thing every step below needs:
		 * a single non-empty tmp_name string.
		 */
		if ( ! isset( $_FILES['async-upload']['tmp_name'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			|| ! is_string( $_FILES['async-upload']['tmp_name'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			|| '' === $_FILES['async-upload']['tmp_name'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_send_json_error( array( 'message' => __( 'No file was uploaded.', 'charitable' ) ) );
		}

		$file = $_FILES['async-upload']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		/*
		 * This is a picture field, so restrict the server-side allowlist to images. Without a
		 * mimes array, wp_handle_upload() falls back to the full get_allowed_mime_types() set
		 * (PDF, ZIP, MP4, MP3, ...) for any caller; the client-side plupload extension filter
		 * is otherwise the only thing keeping non-images out, and a direct request bypasses it.
		 *
		 * Seeded from the same list the non-AJAX form path already uses in
		 * Charitable_Form::get_file_overrides(), plus webp, so the same picture field does not
		 * accept one set of types through the uploader and a different set on form submit.
		 */
		$charitable_default_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'tif|tiff'     => 'image/tiff',
			'ico'          => 'image/x-icon',
			'webp'         => 'image/webp',
		);

		$mimes = apply_filters( 'charitable_picture_upload_allowed_mimes', $charitable_default_mimes, $field_id );

		/*
		 * Never hand core an empty or non-array list. wp_check_filetype() reads an empty
		 * $mimes as "no restriction" and falls back to get_allowed_mime_types(), so
		 * normalising a bad filter return to array() would quietly widen this endpoint back
		 * to every type the site allows - the exact opposite of what it looks like it does.
		 */
		if ( ! is_array( $mimes ) || empty( $mimes ) ) {
			$mimes = $charitable_default_mimes;
		}

		$file_attr = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => $mimes,
			)
		);

		if ( isset( $file_attr['error'] ) ) {
			/*
			 * Core's upload errors quote server configuration back to the caller: the PHP
			 * size limit setting by name, or the uploads directory and whether its parent is
			 * writable. This endpoint answers logged-out visitors on public forms, so report
			 * our own wording instead.
			 */
			wp_send_json_error( array( 'message' => __( 'Sorry, that file could not be uploaded. Please choose an image and try again.', 'charitable' ) ) );
		}

		/*
		 * Re-assert against what actually landed on disk.
		 *
		 * The list above is not the last word on type. wp_handle_upload() resolves the type
		 * through wp_check_filetype_and_ext(), and any theme or plugin can rewrite that
		 * function's result through the filter of the same name - the usual way SVG support
		 * gets bolted onto a site, and it ignores the mimes array completely. It also falls
		 * back to the browser-supplied Content-Type for a caller holding unfiltered_upload.
		 * So resolve the type from the final on-disk filename with wp_check_filetype(), which
		 * is extension matching with no filter anywhere in its path, and require an image.
		 *
		 * SVG is refused here even on a site that permits it elsewhere: it is script-bearing
		 * markup served from this origin, which through a picture field is stored XSS.
		 */
		$charitable_final      = wp_check_filetype( $file_attr['file'] );
		$charitable_final_type = ! empty( $charitable_final['type'] ) ? (string) $charitable_final['type'] : '';

		if ( 0 !== strpos( $charitable_final_type, 'image/' ) || preg_match( '/svg|xml/i', $charitable_final_type ) ) {
			/*
			 * Assert the cleanup rather than assuming it. wp_delete_file() runs the path
			 * through its own filter, which backup and media-offload plugins do intercept and
			 * can blank - and a file left behind here is the very thing being refused, sitting
			 * at a public URL.
			 */
			if ( ! wp_delete_file( $file_attr['file'] ) && file_exists( $file_attr['file'] ) ) {
				// wp_delete_file() is the first attempt above; this is the backstop for when
				// its filter blanked the path, so the alternative-function sniff does not apply.
				@unlink( $file_attr['file'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink,WordPress.PHP.NoSilencedErrors.Discouraged

				if ( file_exists( $file_attr['file'] ) ) {
					charitable_log(
						'Picture upload refused but the file could not be removed',
						$file_attr['file'],
						array( 'level' => 'error' )
					);
				}
			}

			wp_send_json_error( array( 'message' => __( 'Sorry, you are not allowed to upload this file type.', 'charitable' ) ) );
		}

		/*
		 * Do not trust the caller-supplied post_id as the attachment parent. Honour it only
		 * when the current user can actually edit that post; otherwise parent to 0. For an
		 * anonymous opt-in upload there is no such user, so this always resolves to 0.
		 */
		$parent_id = ( $post_id && current_user_can( 'edit_post', $post_id ) ) ? $post_id : 0;

		$attachment = array(
			'guid'              => $file_attr['url'],
			'post_mime_type'    => $file_attr['type'],
			'post_title'        => preg_replace( '/\.[^.]+$/', '', basename( $file['name'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		/**
		 * Insert the file as an attachment.
		 */
		$attachment_id = wp_insert_attachment( $attachment, $file_attr['file'], $parent_id );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error();
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file_attr['file'] ) );

		$size = (string) filter_input( INPUT_POST, 'size' );
		$max_uploads = (int) filter_input( INPUT_POST, 'max_uploads', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $size ) {
			$size = 'thumbnail';
		}

		ob_start();

		charitable_template( 'form-fields/picture-preview.php', array(
			'image' => $attachment_id,
			'field' => array(
			'key' => $field_id,
			'size' => $size,
			'max_uploads' => $max_uploads,
			),
		) );

		wp_send_json_success( ob_get_clean() );
	}

endif;

/**
 * Get donor data, given a donor ID.
 *
 * @since  1.6.28
 *
 * @return void
 */
function charitable_ajax_get_donor_data() {
	$donor_id = (int) filter_input( INPUT_POST, 'donor_id', FILTER_SANITIZE_NUMBER_INT );

	if ( ! check_ajax_referer( 'donor-select', 'nonce' ) ) {
		wp_send_json_error( 'nonce check failed', '403' );
	}

	$fields = array_key_exists( 'fields', $_POST ) ? $_POST['fields'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$donor  = new Charitable_Donor( $donor_id );
	$data   = [];

	foreach ( $fields as $field ) {
		$data[ $field ] = $donor->get_donor_meta( $field );
	}

	wp_send_json_success( $data );
}

/**
 * Receives an AJAX request to load session content and returns
 * the output to be loaded.
 *
 * @since  1.5.0
 *
 * @return void
 */
	function charitable_ajax_get_session_content() { // phpcs:ignore WordPress.Security.NonceVerification.Missing
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification handled by caller
	if ( ! array_key_exists( 'templates', $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_send_json_error( __( 'Missing templates in request.', 'charitable' ) );
	}

	$output = array();

	foreach ( $_POST['templates'] as $i => $template_args ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $template_args ) || ! array_key_exists( 'template', $template_args ) ) {
			continue;
		}

		/**
		 * Get the output for the session content item.
		 *
		 * @since 1.5.0
		 *
		 * @param false|string $content The content to return, or a false in case of failure.
		 * @param array        $args    Mixed set of arguments.
		 */
		$output[ $i ] = apply_filters( 'charitable_session_content_' . $template_args['template'], false, $template_args );
	}

	wp_send_json_success( $output );
}

/**
 * Return the donation receipt.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @param  array        $args    Mixed array of args.
 * @return string|false
 */
function charitable_ajax_get_session_donation_receipt( $content, $args ) {
	if ( ! array_key_exists( 'donation_id', $args ) ) {
		return $content;
	}

	$donation = charitable_get_donation( $args['donation_id'] );

	if ( ! $donation ) {
		return $content;
	}

	return charitable_template_donation_receipt_output( '', $donation );
}

/**
 * Return the donation form's amount field.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @param  array        $args    Mixed array of args.
 * @return string|false
 */
function charitable_ajax_get_session_donation_form_amount_field( $content, $args ) {
	if ( ! array_key_exists( 'campaign_id', $args ) ) {
		return $content;
	}

	if ( ! array_key_exists( 'form_id', $args ) ) {
		return $content;
	}

	ob_start();

	charitable_template(
		'donation-form/donation-amount-list.php',
		array(
			'campaign' => charitable_get_campaign( $args['campaign_id'] ),
			'form_id'  => $args['form_id'],
		)
	);

	return ob_get_clean();
}

/**
 * Return the donation form's amount field.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @param  array        $args    Mixed array of args.
 * @return string|false
 */
function charitable_ajax_get_session_donation_form_current_amount_text( $content, $args ) {
	if ( ! array_key_exists( 'campaign_id', $args ) ) {
		return $content;
	}

	if ( ! array_key_exists( 'form_id', $args ) ) {
		return $content;
	}

	$amount = charitable_get_campaign( $args['campaign_id'] )->get_donation_amount_in_session();

	return charitable_template_donation_form_current_amount_text( $amount, $args['form_id'], $args['campaign_id'] );
}

/**
 * Return the error messages.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @return string|false
 */
function charitable_ajax_get_session_errors( $content ) {
	$errors = charitable_get_notices()->get_errors();

	if ( empty( $errors ) ) {
		return $content;
	}

	ob_start();

	charitable_template(
		'form-fields/errors.php',
		array(
			'errors' => $errors,
		)
	);

	return ob_get_clean();
}

/**
 * Return the notices
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @return string|false
 */
function charitable_ajax_get_session_notices( $content ) {
	$notices = charitable_get_notices()->get_notices();

	if ( empty( $notices ) ) {
		return $content;
	}

	ob_start();

	charitable_template(
		'form-fields/notices.php',
		array(
			'notices' => $notices,
		)
	);

	return ob_get_clean();
}
