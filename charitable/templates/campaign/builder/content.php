<?php
/**
 * Displays the campaign content created by the campaign builder, starting in 1.8.0.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/builder/content.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.8.0
 * @version 1.8.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Data related to the template (and therefore the defining layout) to be used.

$charitable_campaign = $view_args['campaign']; // Charitable_Campaign Instance of `Charitable_Campaign`.

/*
 * Whether to honour the ?charitable_campaign_preview argument.
 *
 * That argument is unauthenticated and names an arbitrary post ID, so it is only trusted
 * for a user who can edit that specific campaign. Without this check, appending
 * ?charitable_campaign_preview=<other campaign id> to ANY campaign URL replaced the
 * rendered campaign's whole builder payload with that other campaign's, which gave two
 * real problems:
 *
 *  - An unpublished campaign's headline, story and images leaked to any Contributor. The
 *    render gate further down tests edit_posts, a site-wide primitive every Contributor
 *    holds, not a per-campaign capability.
 *  - A published campaign's content rendered under a different campaign's URL for
 *    anonymous visitors, including its donation ask. That link is hand-outable and any
 *    full-page cache that ignores unknown query args will store it against the wrong URL.
 *
 * Both were reachable before 1.8.12.4 for the 24 hours a preview transient lives after
 * each builder save; the saved-revision fallback added below would otherwise have made
 * them permanent.
 *
 * edit_post (singular) is the meta capability. The campaign post type is registered with
 * capability_type 'campaign' and map_meta_cap true, so WordPress maps it onto
 * edit_campaign / edit_others_campaigns for the specific post. This mirrors the check
 * Campaign_Builder_Preview::is_preview_page() already makes.
 *
 * When the check fails the argument is ignored completely and the normal view_args path
 * renders the campaign the caller actually asked for.
 */
$charitable_preview_id  = empty( $_GET['charitable_campaign_preview'] ) ? 0 : absint( $_GET['charitable_campaign_preview'] ); //phpcs:ignore
$charitable_can_preview = $charitable_preview_id
	&& Charitable::CAMPAIGN_POST_TYPE === get_post_type( $charitable_preview_id )
	&& charitable_current_user_can( 'edit_post', $charitable_preview_id );

if ( $charitable_can_preview ) {
	// get the transient that is storing the temp settings information, as this is what we will use to display the preview.
	$charitable_campaign_data = get_transient( 'charitable_campaign_preview_' . $charitable_preview_id );

	/*
	 * The transient carries the builder's *unsaved* changes and is written only by the
	 * builder's save AJAX. Every other route to a preview arrives without it:
	 *
	 *  - the Preview action on a draft in the Campaigns list table,
	 *  - WordPress's own preview_post_link for a campaign,
	 *  - any preview more than DAY_IN_SECONDS after the last builder save,
	 *  - an object cache that has evicted the entry (HelpScout #11576).
	 *
	 * With no fallback the whole render proceeded on false, which produced an empty
	 * campaign body and logged a warning for every read of ['id']. Fall back to the
	 * saved revision, which is what the preview notice already tells the user it is
	 * showing.
	 */
	if ( empty( $charitable_campaign_data ) ) {
		$charitable_campaign_data = get_post_meta( $charitable_preview_id, 'campaign_settings_v2', true );
	}
} else {
	$charitable_campaign_data = empty( $view_args['campaign_data'] ) && ! empty( $view_args['id'] ) ? get_post_meta( intval( $view_args['id'] ), 'campaign_settings_v2', true ) : $view_args['campaign_data'];
}

// A brand-new campaign has neither transient nor saved meta, so either branch can still
// yield a non-array. Normalise before anything indexes it.
if ( ! is_array( $charitable_campaign_data ) ) {
	$charitable_campaign_data = array();
}

$charitable_template_data   = isset( $view_args['template'] ) && is_array( $view_args['template'] ) ? $view_args['template'] : array();
$charitable_template_id     = isset( $charitable_campaign_data['template_id'] ) && ! empty( $charitable_campaign_data['template_id'] ) ? sanitize_key( $charitable_campaign_data['template_id'] ) : charitable_campaign_builder_default_template();
$charitable_template_layout = isset( $charitable_template_data['layout'] ) ? $charitable_template_data['layout'] : array();

if ( is_admin() ) {

	// Check if Elementor is installed and activated.
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}

	// load the Campaign Builder Preview class.
	require_once charitable()->get_path( 'includes', true ) . 'admin/campaign-builder/class-campaign-builder-preview.php';

	$charitable_assets_dir = charitable()->get_path( 'assets', false );
	$charitable_min        = charitable_get_min_suffix();

	if ( charitable_is_debug( 'elementor' ) ) {
		echo 'Charitable Elementor template id is ' . esc_html( $charitable_template_id );
	}

	// Directly link and load the CSS files.
	$charitable_version        = charitable_is_break_cache() ? charitable()->get_version() . '.' . time() : charitable()->get_version();
	$charitable_css_theme_file = $charitable_assets_dir . 'css/campaign-builder/themes/frontend/' . $charitable_template_id . '.php';

	echo '<link rel="stylesheet" id="charitable-campaign-theme-' . esc_attr( $charitable_template_id ) . '-css" href="' . esc_url( $charitable_css_theme_file ) . '" media="all" />'; //phpcs:ignore
	echo '<link rel="stylesheet" id="charitable-campaign-theme-base-css" href="' . esc_url( $charitable_assets_dir . 'css/campaign-builder/themes/frontend/base' . $charitable_min . '.css' ) . '" media="all" />'; //phpcs:ignore

	// Add inline CSS to prevent clicks on '.charitable-campaign-wrapper'.
	echo '<style type="text/css">.charitable-campaign-wrapper { pointer-events: none; }</style>';
}

/* preview page check */
$charitable_content_preview = new Campaign_Builder_Preview();
$charitable_is_preview_page = $charitable_content_preview->is_preview_page();

/* tabs */
$charitable_enabled_tabs = isset( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) ? false : true;


/* The Setup */

/*
 * $charitable_campaign, $charitable_campaign_data, $charitable_template_data,
 * $charitable_template_id and $charitable_template_layout are all resolved at the top of
 * this file and are still current here. Nothing between there and this point reassigns
 * $view_args or $_GET, or writes the preview transient or the campaign's post meta. The
 * is_admin() branch only reads them and echoes stylesheet links, assigning nothing but four
 * locals of its own; Campaign_Builder_Preview's constructor does query the database via
 * is_preview_page() before registering its hooks, but it touches none of this scope.
 * Verified at runtime across the draft-preview, published-preview and front-end paths.
 *
 * This block previously recomputed four of the five from scratch - everything except
 * $charitable_template_data, which was and still is assigned exactly once above. That
 * duplicate is where the second copy of the preview-transient read lived, and why the
 * transient fix originally had to be written twice.
 */

/*
 * Resolve the campaign's post ID independently of the campaign data payload. The three post
 * lookups below need nothing but the ID, and sourcing it from inside the payload is what
 * made them warn whenever the payload was missing.
 *
 * $view_args['id'] comes first because it is the campaign the caller actually asked for.
 * The payload's own 'id' is the least trustworthy source: it is written once at save time
 * and copied verbatim thereafter, so a WXR import, a staging-to-production migration or a
 * duplicate-post plugin can leave it pointing at an unrelated post, and the status of THAT
 * post would then decide whether this campaign renders.
 *
 * $charitable_preview_id is used only when $charitable_can_preview passed, so the
 * unauthenticated query argument cannot steer this either.
 *
 * Stays 0 only when there is no global post either, since get_the_ID() would otherwise have
 * supplied it. The status gate below has an explicit branch for that case rather than
 * relying on get_post_status( 0 ), which returns false with no post to fall back to.
 */
$charitable_campaign_post_id = ! empty( $view_args['id'] ) ? intval( $view_args['id'] ) : 0;

if ( 0 === $charitable_campaign_post_id && ! empty( $charitable_campaign_data['id'] ) ) {
	$charitable_campaign_post_id = intval( $charitable_campaign_data['id'] );
}

if ( 0 === $charitable_campaign_post_id && $charitable_can_preview ) {
	$charitable_campaign_post_id = $charitable_preview_id;
}

if ( 0 === $charitable_campaign_post_id ) {
	$charitable_campaign_post_id = intval( get_the_ID() );
}

/* Template Related */

$charitable_template_parent_id = ( isset( $charitable_template_data['meta']['parent_theme'] ) && ! empty( $charitable_template_data['meta']['parent_theme'] ) ) ? esc_attr( $charitable_template_data['meta']['parent_theme'] ) : false;
$charitable_template_wrap_css  = false !== $charitable_template_parent_id ? 'template-' . $charitable_template_parent_id : '';
$charitable_template_wrap_css .= false !== $charitable_template_id ? ' template-' . $charitable_template_id : '';
$charitable_template_wrap_css .= ! empty( $view_args['campaign_data']['settings']['general']['form_css_class'] ) ? ' ' . esc_attr( $view_args['campaign_data']['settings']['general']['form_css_class'] ) : false;
$charitable_template_wrap_css .= 'draft' === get_post_status( $charitable_campaign_post_id ) ? ' is-charitable-preview' : false; // this to give a css class only when the campaign is previewed.

/* Layout Related */

$charitable_row_counter = 0;

$charitable_rows = (array) isset( $charitable_campaign_data['layout'] ) && ! empty( $charitable_campaign_data['layout']['rows'] ) ? $charitable_campaign_data['layout']['rows'] : array();

/* css: wrap and containers */

$charitable_css_classes                    = array();
$charitable_css_classes['container-wrap']  = 'charitable-campaign-wrap ' . trim( $charitable_template_wrap_css );
$charitable_css_classes['container-wrap'] .= ! empty( $view_args['id'] ) ? ' charitable-campaign-wrap-id-' . intval( $view_args['id'] ) : '';
$charitable_css_classes                    = apply_filters( 'charitable_builder_campaign_content_css_classes', $charitable_css_classes, $charitable_template_data, $charitable_campaign_data );
$charitable_css_classes_output             = implode( ' ', $charitable_css_classes );

// Get the post status - if this is a draft, we will display a notice to the admin or author, and not show this to the public.
$charitable_post_status = get_post_status( $charitable_campaign_post_id );
$charitable_post_author = get_post_field( 'post_author', $charitable_campaign_post_id );

/*
 * Statuses that are not publicly published. These render only for the campaign's author or
 * a user who can edit that campaign, and carry the "not published yet" notice on the front
 * end.
 *
 * 'pending' and 'future' were missing before 1.8.12.4, so a campaign in either status
 * rendered nothing at all - the builder's preview page showed its notice bar above an empty
 * body. That became reachable once the Preview button stopped swallowing those statuses.
 * 'private' is deliberately not in this list: it is published but restricted, so it is
 * handled below with the read_post capability instead.
 */
$charitable_unpublished_statuses = array( 'draft', 'pending', 'future' );

/*
 * Whether the current user is the campaign's author.
 *
 * get_post_field() returns post_author as a *string*, while get_current_user_id() returns an
 * int, so the previous `$charitable_post_author === get_current_user_id()` could never match
 * and every check below fell through to the capability test alone. Comparing as integers is
 * plainly the intent (see the comments on each branch), and restores author access.
 *
 * The get_current_user_id() > 0 guard is load-bearing: a campaign whose post_author is 0 or
 * NULL would otherwise compare equal to a logged-out visitor's user ID of 0 and expose an
 * unpublished campaign publicly.
 */
$charitable_is_campaign_author = get_current_user_id() > 0 && (int) $charitable_post_author === get_current_user_id();

/*
 * Only display the message if the viewer isn't viewing a preview from the campaign builder
 * (maybe they are viewing this via shortcode on the frontend, etc.).
 *
 * Keyed off $charitable_can_preview rather than the raw query argument: a request carrying
 * ?charitable_campaign_preview for a campaign the user cannot edit is an ordinary page view,
 * not a preview, so it should still get the notice.
 */
if ( ! $charitable_can_preview && ( false === $charitable_post_status || in_array( $charitable_post_status, $charitable_unpublished_statuses, true ) ) ) :

	// if the user is the author of the post OR if they have permissions to view drafts, show the notice.
	if ( $charitable_is_campaign_author || current_user_can( 'edit_posts' ) ) {
		?>
		<div class="charitable-notice charitable-notice-info">
			<?php
			/*
			 * Status-neutral wording. This branch now covers pending and future as well as
			 * draft, so the previous "currently in draft mode" was inaccurate for a campaign
			 * awaiting review or scheduled. The draft-specific strings it replaces are no
			 * longer referenced.
			 */
			?>
			<p style="margin: 0;"><?php esc_html_e( 'This campaign has not been published yet. Only you can see it, and some functionality (like donation forms, donation buttons, etc.) might be disabled.', 'charitable' ); ?></p>
		</div>
		<?php
	} else {
		// show a generic message to the public.
		?>
		<div class="charitable-notice charitable-notice-info">
			<p style="margin: 0;"><?php esc_html_e( 'This campaign has not been published yet.', 'charitable' ); ?></p>
		</div>
		<?php
	}

endif;

/*
 * Whether to render the campaign at all, and to whom.
 *
 * - 'publish' is public.
 * - The unpublished states render for the author, or for a user who can edit THAT campaign.
 * - 'private' is published but access-restricted, so it asks whether the user can read that
 *   specific post rather than whether they can edit campaigns generally.
 * - No resolvable post means there is nothing to disclose, so this keeps the pre-1.8.12.4
 *   site-wide edit_posts test rather than inventing a per-post check with no post.
 * - Everything else - trash, auto-draft, inherit, and any status a third party registers -
 *   renders nothing, as before.
 *
 * These use the singular edit_post / read_post META capabilities against
 * $charitable_campaign_post_id, not the plural edit_posts / read_private_posts primitives.
 * The campaign post type registers capability_type 'campaign' with map_meta_cap true, so
 * WordPress maps them onto edit_campaign / edit_others_campaigns / read_private_campaigns
 * for that one post. The plural forms were wrong twice over: edit_posts is held by every
 * Contributor for every campaign on the site, and read_private_posts is a *posts* primitive
 * that says nothing about campaigns, so a core Editor would have passed it while WordPress's
 * own read_post check on the campaign would have denied them.
 */
if ( 'publish' === $charitable_post_status ) {
	$charitable_can_view_campaign = true;
} elseif ( false === $charitable_post_status || 0 === $charitable_campaign_post_id ) {
	$charitable_can_view_campaign = $charitable_is_campaign_author || charitable_current_user_can( 'edit_posts' );
} elseif ( 'private' === $charitable_post_status ) {
	$charitable_can_view_campaign = $charitable_is_campaign_author || charitable_current_user_can( 'read_post', $charitable_campaign_post_id );
} elseif ( in_array( $charitable_post_status, $charitable_unpublished_statuses, true ) ) {
	$charitable_can_view_campaign = $charitable_is_campaign_author || charitable_current_user_can( 'edit_post', $charitable_campaign_post_id );
} else {
	$charitable_can_view_campaign = false;
}

if ( $charitable_can_view_campaign ) :

	/**
	 * Add something before the campaign builder content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_builder_campaign_content_before', $charitable_campaign_data );

	/**
	 * Add something before the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_campaign_content_before', $charitable_campaign );

	?>

	<div class="<?php echo esc_attr( $charitable_css_classes_output ); ?>">

		<div class="charitable-campaign-container">

		<?php

		foreach ( $charitable_rows as $charitable_row_id => $charitable_row ) :

			$charitable_row_type = ! empty( $charitable_row['type'] ) ? esc_attr( $charitable_row['type'] ) : false;

			$charitable_row_css        = array(
				'charitable-campaign-row',
				'charitable-campaign-row-type-' . $charitable_row_type,
			);
			$charitable_additional_css = ! empty( $charitable_row['css_class'] ) ? esc_attr( $charitable_row['css_class'] ) : '';
			if ( '' !== $charitable_additional_css ) {
				$charitable_row_css[] = $charitable_additional_css;
			}

			$charitable_row_bg     = ! empty( $charitable_row['background_image'] ) ? esc_url( $charitable_row['background_image'] ) : '';
			$charitable_row_style  = '' !== $charitable_row_bg ? ' style="background-image:url(\'' . $charitable_row_bg . '\');"' : '';
			$charitable_row_locked = ! empty( $charitable_row['locked'] ) ? ' data-row-locked="1"' : '';

			if ( '' !== $charitable_row_bg ) {
				$charitable_row_css[] = 'has-background-image';
			}
			if ( ! empty( $charitable_row['locked'] ) ) {
				$charitable_row_css[] = 'is-locked';
			}

			$charitable_row_css_classes = apply_filters(
				'charitable_campaign_row_css',
				$charitable_row_css,
				$charitable_row,
				$charitable_template_id,
				$charitable_campaign_data
			);

			if ( 'row' === $charitable_row_type || 'header' === $charitable_row_type ) :
				?>

					<?php echo '<!-- row START -->'; ?>

					<div id="charitable-template-row-<?php echo intval( $charitable_row_id ); ?>" data-row-id="<?php echo intval( $charitable_row_id ); ?>" data-row-type="<?php echo esc_attr( $charitable_row_type ); ?>"<?php echo $charitable_row_locked; // phpcs:ignore ?> class="<?php echo implode( ' ', array_map( 'esc_attr', $charitable_row_css_classes ) ); ?>"<?php echo $charitable_row_style; // phpcs:ignore ?>>

					<?php

					if ( ! empty( $charitable_row['columns'] ) ) :

						$charitable_column_counter = 0;

						foreach ( $charitable_row['columns'] as $charitable_column_id => $charitable_column ) :

								$charitable_column_css_classes = apply_filters(
									'charitable_campaign_column_css',
									array(
										'charitable-campaign-column',
										'charitable-campaign-column-' . $charitable_column_id,
									),
									$charitable_column,
									$charitable_template_id,
									$charitable_campaign_data
								);


							echo '<!-- column START -->';

							echo '<div data-column-id="' . intval( $charitable_column_id ) . '" class="' . implode( ' ', array_map( 'esc_attr', $charitable_column_css_classes ) ) . '">';

							$charitable_section_counter = 0;

							foreach ( $charitable_column['sections'] as $charitable_section ) :

								echo '<!-- section START -->';

								echo '<div data-section-id="' . intval( $charitable_section_counter ) . '" data-section-type="' . esc_attr( $charitable_section['type'] ) . '" class="section charitable-field-section">';

								$charitable_section_type = ! empty( $charitable_section['type'] ) ? esc_attr( $charitable_section['type'] ) : 'fields';

								if ( 'tabs' === $charitable_section_type ) {

									// Did the user disable tabs entirely?
									$charitable_enable_tabs = ( ! empty( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && $charitable_campaign_data['layout']['advanced']['enable_tabs'] === 'disabled' ) ? false : true;

									if ( false !== $charitable_enable_tabs ) {


										// If there is campaign data, make a list of fields already in use which might determine if we show any tabs or not.
										$charitable_fields_in_tabs = array();

										if ( ! empty( $charitable_section['tabs'] ) ) {
											foreach ( $charitable_section['tabs'] as $charitable_section_tab ) {
												if ( ! empty( $charitable_section_tab['fields'] ) ) {
													$charitable_fields_in_tabs = array_merge( $charitable_fields_in_tabs, $charitable_section_tab['fields'] );
												}
											}
										}

										// If there are no fields in any tabs, don't show the tabs.
										if ( empty( $charitable_fields_in_tabs ) ) {
											continue;
										}

										$charitable_tab_tabs  = (array) isset( $charitable_section['tabs'] ) && ! empty( $charitable_section['tabs'] ) ? $charitable_section['tabs'] : array();
										$charitable_tab_order = isset( $charitable_campaign_data['tab_order'] ) && ! empty( $charitable_campaign_data['tab_order'] ) ? $charitable_campaign_data['tab_order'] : array();
										$charitable_tab_style = isset( $charitable_campaign_data['layout']['advanced']['tab_style'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_style'] ) ? $charitable_campaign_data['layout']['advanced']['tab_style'] : 'medium';
										$charitable_tab_size  = isset( $charitable_campaign_data['layout']['advanced']['tab_size'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_size'] ) ? $charitable_campaign_data['layout']['advanced']['tab_size'] : 'medium';
										$charitable_css_class = isset( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : false;

										// sort a multidimensional array matching the same order of keys as another multidimensional array.
										if ( ! empty( $charitable_tab_order ) ) {
											$charitable_temp_tab_tabs = array();
											foreach ( $charitable_tab_order as $charitable_order_id => $charitable_tab_id ) {
												if ( isset( $charitable_tab_tabs[ $charitable_tab_id ] ) ) {
													$charitable_temp_tab_tabs[ $charitable_tab_id ] = $charitable_tab_tabs[ $charitable_tab_id ];
												}
											}
											$charitable_tab_tabs = $charitable_temp_tab_tabs;
										}

										?>
									<article>
										<?php
										// Check if all tabs should be hidden (1.8.8.2)
										$charitable_all_tabs_hidden = true;
										$charitable_visible_tabs_count = 0;
										foreach ( $charitable_tab_tabs as $charitable_tab_id => $charitable_tab_fields ) {
											$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];
											if ( empty( $charitable_tab_info['visible_nav'] ) || $charitable_tab_info['visible_nav'] !== 'invisible' ) {
												$charitable_all_tabs_hidden = false;
												$charitable_visible_tabs_count++;
											}
										}

										// Only show navigation if there are multiple visible tabs (hide for single tab)
										if ( ! $charitable_all_tabs_hidden && $charitable_visible_tabs_count > 1 ) :
										?>
										<nav class="charitable-campaign-nav charitable-tab-style-<?php echo esc_attr( $charitable_tab_style ); ?> charitable-tab-size-<?php echo esc_attr( $charitable_tab_size ); ?>">
											<ul>
											<?php if ( ! empty( $charitable_tab_tabs ) ) : ?>

													<?php

														$charitable_counter = 1;

													foreach ( $charitable_tab_tabs as $charitable_tab_id => $charitable_tab_fields ) :

														$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

														$charitable_tab_type  = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
														$charitable_tab_title = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
														$charitable_tab_desc  = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
														$charitable_css_class = 'tab_type_' . $charitable_tab_type . ' ';
														$charitable_css_class = ( $charitable_counter === 1 ) ? 'active' : false;


														?><li id="tab_<?php echo intval( $charitable_tab_id ); ?>_title" data-tab-id="<?php echo intval( $charitable_tab_id ); ?>" data-tab-type="<?php echo esc_attr( $charitable_tab_type ); ?>" class="tab_title <?php echo esc_attr( $charitable_css_class ); ?>"><a href="#"><?php echo esc_html( $charitable_tab_title ); ?></a></li><?php //phpcs:ignore

															++$charitable_counter;

															endforeach;

													?>

												<?php endif; ?>

											</ul>
										</nav>
										<?php endif; // end if not all tabs hidden ?>
										<div class="tab-content">

											<ul class="charitable-tabs">

											<?php if ( ! empty( $charitable_tab_tabs ) ) : ?>

												<?php

														$charitable_counter = 1;

												foreach ( $charitable_tab_tabs as $charitable_tab_id => $charitable_tab_fields ) :

													$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

													$charitable_tab_type   = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
													$charitable_tab_title  = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
													$charitable_tab_desc   = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
													$charitable_css_class  = 'tab_type_' . $charitable_tab_type . ' ';
													$charitable_css_class .= ( $charitable_counter === 1 ) ? 'active' : false;


													?>
													<li id="tab_<?php echo intval( $charitable_tab_id ); ?>_content" class="tab_content_item <?php echo esc_attr( $charitable_css_class ); ?>" data-tab-type="<?php echo esc_attR( $charitable_tab_type ); ?>" data-tab-id="<?php echo esc_attr( $charitable_tab_id ); ?>">

															<div class="charitable-tab-wrap">

													<?php

														$charitable_tab_tabs = (array) isset( $charitable_section['tabs'] ) && ! empty( $charitable_section['tabs'][ $charitable_tab_id ] ) ? $charitable_section['tabs'][ $charitable_tab_id ] : array();

													if ( ! empty( $charitable_tab_tabs['fields'] ) ) :

															$charitable_tab_fields_types = isset( $charitable_row['fields'] ) ? $charitable_row['fields'] : array();

														foreach ( $charitable_tab_tabs['fields'] as $charitable_tab_field_id => $charitable_tab_field_type_id ) :

															$charitable_tab_field_data = ! empty( $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] : false;
															$charitable_tab_field_type = ! empty( $charitable_row['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_row['fields'][ $charitable_tab_field_type_id ] : false;

															$charitable_field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $charitable_tab_field_type ) ) ) );

															if ( class_exists( $charitable_field_class ) ) :

																	$charitable_class = new $charitable_field_class();
																	$charitable_class->field_display( $charitable_tab_field_type, $charitable_tab_field_data, $charitable_campaign_data, $charitable_is_preview_page, $charitable_tab_field_id );

																endif;


																endforeach;

														endif;

													?>
															</div>

														</li>

													<?php

													++$charitable_counter;

														endforeach;
												?>

												<?php endif; ?>
											</ul>
										</div>
									</article>

									<?php } // end if tabs ?>

									<?php


								} elseif ( 'fields' === $charitable_section_type ) {

									$charitable_field_types_data = $charitable_row['fields'];

									foreach ( $charitable_section['fields'] as $charitable_key => $charitable_field_id ) :

											$charitable_field_data  = ! empty( $charitable_campaign_data['fields'][ $charitable_field_id ] ) ? $charitable_campaign_data['fields'][ $charitable_field_id ] : false;
											$charitable_field_type  = false !== $charitable_field_data && isset( $charitable_field_types_data[ $charitable_field_id ] ) ? sanitize_key( $charitable_field_types_data[ $charitable_field_id ] ) : false;
											$charitable_field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $charitable_field_type ) ) ) );

										if ( class_exists( $charitable_field_class ) ) :

											$charitable_class = new $charitable_field_class();
											$charitable_class->field_display( $charitable_field_type, $charitable_field_data, $charitable_campaign_data, $charitable_is_preview_page, $charitable_field_id, $charitable_template_data );

											endif;

										endforeach;

								}

								++$charitable_section_counter;

								echo '</div>';

								echo '<!-- section END -->';

										endforeach;

										++$charitable_column_counter;

							?>

								</div>

							<?php

								echo '<!-- column END -->';

						endforeach;

					endif;

					?>

					</div>

					<?php echo '<!-- row END -->'; ?>

				<?php

			elseif ( $charitable_enabled_tabs && 'tabs' === $charitable_row_type ) :

				// Did the user disable tabs entirely?
				$charitable_enable_tabs = ( ! empty( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && $charitable_campaign_data['layout']['advanced']['enable_tabs'] === 'disabled' ) ? false : true;

				if ( false === $charitable_enable_tabs ) {
					continue;
				}

				$charitable_row_tabs  = isset( $charitable_row['tabs'] ) ? $charitable_row['tabs'] : false;
				$charitable_tab_style = isset( $charitable_campaign_data['layout']['advanced']['tab_style'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_style'] ) ? $charitable_campaign_data['layout']['advanced']['tab_style'] : 'medium';
				$charitable_tab_size  = isset( $charitable_campaign_data['layout']['advanced']['tab_size'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_size'] ) ? $charitable_campaign_data['layout']['advanced']['tab_style'] : 'medium';
				$charitable_css_class = isset( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : false;

				?>

				<article>
					<nav class="charitable-campaign-nav charitable-tab-style-<?php echo esc_attr( $charitable_tab_style ); ?> charitable-tab-size-<?php echo esc_attr( $charitable_tab_size ); ?>">
						<ul>
							<?php if ( $charitable_row_tabs ) : ?>
								<?php

									$charitable_counter = 1;

								foreach ( $charitable_row_tabs as $charitable_tab_id => $charitable_tab_fields ) :

									$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

									$charitable_tab_type      = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
									$charitable_tab_title     = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
									$charitable_tab_desc      = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
										$charitable_css_class = 'tab_type_' . $charitable_tab_type . ' ';
										$charitable_css_class = ( $charitable_counter === 1 ) ? 'active' : false;


									?>
										<li id="tab_<?php echo intval( $charitable_tab_id ); ?>_title" data-tab-id="<?php echo intval( $charitable_tab_id ); ?>" data-tab-type="<?php echo esc_attr( $charitable_tab_type ); ?>" class="tab_title <?php echo esc_attr( $charitable_css_class ); ?>"><a href="#"><?php echo esc_html( $charitable_tab_title ); ?></a></li>
										<?php

										++$charitable_counter;

										endforeach;
								?>

								<?php endif; ?>
						</ul>
					</nav>
					<div class="tab-content">
							<ul>
							<?php

							if ( $charitable_row_tabs ) :

								$charitable_counter = 1;

								foreach ( $charitable_row_tabs as $charitable_tab_id => $charitable_tab_fields ) :

									$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

									$charitable_tab_type   = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
									$charitable_tab_title  = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
									$charitable_tab_desc   = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
									$charitable_css_class  = 'tab_type_' . $charitable_tab_type . ' ';
									$charitable_css_class .= ( $charitable_counter === 1 ) ? 'active' : false;


									?>
										<li id="tab_<?php echo intval( $charitable_tab_id ); ?>_content" class="tab_content_item <?php echo esc_attr( $charitable_css_class ); ?>" data-tab-type="<?php echo esc_attr( $charitable_tab_type ); ?>" data-tab-id="<?php echo intval( $charitable_tab_id ); ?>">

											<div class="charitable-tab-wrap">

									<?php

										$charitable_tab_field_info = isset( $charitable_row['fields'] ) ? $charitable_row['fields'] : false;

									if ( false !== $charitable_tab_field_info ) :

											$charitable_tab_fields_types = isset( $charitable_row['fields'] ) ? $charitable_row['fields'] : array();

										foreach ( $charitable_tab_fields as $charitable_tab_field_id => $charitable_tab_field_type_id ) :

											$charitable_tab_field_data = ! empty( $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] : false;
											$charitable_tab_field_type = ! empty( $charitable_row['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_row['fields'][ $charitable_tab_field_type_id ] : false;

											$charitable_field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $charitable_tab_field_type ) ) ) );

											if ( class_exists( $charitable_field_class ) ) :

													$charitable_class = new $charitable_field_class();
													$charitable_class->field_display( $charitable_tab_field_type, $charitable_tab_field_data, $charitable_campaign_data, $charitable_is_preview_page, $charitable_tab_field_id );

												endif;

											endforeach;

										endif;

									?>
											</div>

										</li>

									<?php

									++$charitable_counter;

									endforeach;
								?>

								<?php endif; ?>
							</ul>
						</div>
				</article>

					<?php

				endif;

			endforeach;

		?>

			</div>
		</div>

	<?php

	/**
	 * Add something after the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_builder_campaign_content_after', $charitable_campaign_data );

	/**
	 * Add something before the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_campaign_content_after', $charitable_campaign );

endif;
