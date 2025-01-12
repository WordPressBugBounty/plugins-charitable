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
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_admin() ) {
	return;
}

// Data related to the template (and therefore the defining layout) to be used.
$template_data = $view_args['template'];

/* preview page check */
$content_preview = new Campaign_Builder_Preview();
$is_preview_page = $content_preview->is_preview_page();

/* tabs */
$enabled_tabs = isset( $campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $campaign_data['layout']['advanced']['enable_tabs'] ) ? false : true;


/* The Setup */

	/* Campaign Related */

	$campaign = $view_args['campaign']; // Charitable_Campaign Instance of `Charitable_Campaign`.


if ( ! empty( $_GET['charitable_campaign_preview'] ) ) { //phpcs:ignore
	// get the transient that is storing the temp settings information, as this is what we will use to display the preview.
	$campaign_data = get_transient( 'charitable_campaign_preview_' . intval( $_GET['charitable_campaign_preview'] ) ); //phpcs:ignore

} else {
	$campaign_data = empty( $view_args['campaign_data'] ) && ! empty( $view_args['id'] ) ? get_post_meta( intval( $view_args['id'] ), 'campaign_settings_v2', true ) : $view_args['campaign_data'];
}

	/* Template Related */

	$template_id     = isset( $campaign_data['template_id'] ) && ! empty( $campaign_data['template_id'] ) ? sanitize_key( $campaign_data['template_id'] ) : charitable_campaign_builder_default_template();
	$template_layout = $template_data['layout'];

	$template_parent_id = ! empty( $template_data['meta']['parent_theme'] ) ? esc_attr( $template_data['meta']['parent_theme'] ) : false;
	$template_wrap_css  = false !== $template_parent_id ? 'template-' . $template_parent_id : '';
	$template_wrap_css .= false !== $template_id ? ' template-' . $template_id : '';
	$template_wrap_css .= ! empty( $view_args['campaign_data']['settings']['general']['form_css_class'] ) ? ' ' . esc_attr( $view_args['campaign_data']['settings']['general']['form_css_class'] ) : false;
	$template_wrap_css .= 'draft' === get_post_status( $campaign_data['id'] ) ? ' is-charitable-preview' : false; // this to give a css class only when the campaign is previewed.

	/* Layout Related */

	$row_counter = 0;

	$rows = (array) isset( $campaign_data['layout'] ) && ! empty( $campaign_data['layout']['rows'] ) ? $campaign_data['layout']['rows'] : array();

	/* css: wrap and containers */

	$css_classes                   = array();
	$css_classes['container-wrap'] = 'charitable-campaign-wrap ' . trim( $template_wrap_css );
	$css_classes                   = apply_filters( 'charitable_builder_campaign_content_css_classes', $css_classes, $template_data, $campaign_data );
	$css_classes_output            = implode( ' ', $css_classes );

// Get the post status - if this is a draft, we will display a notice to the admin or author, and not show this to the public.
$post_status = get_post_status( $campaign_data['id'] );
$post_author = get_post_field( 'post_author', $campaign_data['id'] );

// Only display the message if the viewer if the view isn't viewing a preview from the campaign builder (maybe they are viewing this via shortcode on the frontend, etc.).
if ( empty( $_GET['charitable_campaign_preview'] ) && ( false === $post_status || 'draft' === $post_status ) ) : //phpcs:ignore

	// if the user is the author of the post OR if they have permissions to view drafts, show the notice.
	if ( $post_author === get_current_user_id() || current_user_can( 'edit_posts' ) ) {
		?>
		<div class="charitable-notice charitable-notice-info">
			<p style="margin: 0;"><?php esc_html_e( 'This campaign is currently in draft mode. Only you can see it, and some functionality (like donation forms, donation buttons, etc.) might be disabled.', 'charitable' ); ?></p>
		</div>
		<?php
	} else {
		// show a generic message to the public.
		?>
		<div class="charitable-notice charitable-notice-info">
			<p style="margin: 0;"><?php esc_html_e( 'This campaign is currently in draft mode.', 'charitable' ); ?></p>
		</div>
		<?php
	}

endif;

// if the campaign/post is published OR if the user is the author of the post OR if they have permissions to edit posts, show the (slightly disabled) campaign.
if ( 'publish' === $post_status || ( ( false === $post_status || 'draft' === $post_status ) && ( $post_author === get_current_user_id() || current_user_can( 'edit_posts' ) ) ) ) :

	/**
	 * Add something before the campaign builder content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_builder_campaign_content_before', $campaign_data );

	/**
	 * Add something before the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_campaign_content_before', $campaign );

	?>

	<div class="<?php echo esc_attr( $css_classes_output ); ?>">

		<div class="charitable-campaign-container">

		<?php

		foreach ( $rows as $row_id => $row ) :

			$row_type = ! empty( $row['type'] ) ? esc_attr( $row['type'] ) : false;

			$row_css        = array(
				'charitable-campaign-row',
				'charitable-campaign-row-type-' . $row_type,
			);
			$additional_css = ! empty( $row['css_class'] ) ? esc_attr( $row['css_class'] ) : '';
			if ( '' !== $additional_css ) {
				$row_css[] = $additional_css;
			}

			$charitable_row_css_classes = apply_filters(
				'charitable_campaign_row_css',
				$row_css,
				$row,
				$template_id,
				$campaign_data
			);

			if ( 'row' === $row_type || 'header' === $row_type ) :
				?>

					<?php echo '<!-- row START -->'; ?>

					<div id="charitable-template-row-<?php echo intval( $row_id ); ?>" data-row-id="<?php echo intval( $row_id ); ?>" data-row-type="<?php echo esc_attr( $row_type ); ?>" class="<?php echo implode( ' ', array_map( 'esc_attr', $charitable_row_css_classes ) ); ?>">

					<?php

					if ( ! empty( $row['columns'] ) ) :

						$column_counter = 0;

						foreach ( $row['columns'] as $column_id => $column ) :

								$charitable_column_css_classes = apply_filters(
									'charitable_campaign_column_css',
									array(
										'charitable-campaign-column',
										'charitable-campaign-column-' . $column_id,
									),
									$column,
									$template_id,
									$campaign_data
								);


							echo '<!-- column START -->';

							echo '<div data-column-id="' . intval( $column_id ) . '" class="' . implode( ' ', array_map( 'esc_attr', $charitable_column_css_classes ) ) . '">';

							$section_counter = 0;

							foreach ( $column['sections'] as $section ) :

								echo '<!-- section START -->';

								echo '<div data-section-id="' . intval( $section_counter ) . '" data-section-type="' . esc_attr( $section['type'] ) . '" class="section charitable-field-section">';

								$section_type = ! empty( $section['type'] ) ? esc_attr( $section['type'] ) : 'fields';

								if ( 'tabs' === $section_type ) {

									// Did the user disable tabs entirely?
									$enable_tabs = ( ! empty( $campaign_data['layout']['advanced']['enable_tabs'] ) && $campaign_data['layout']['advanced']['enable_tabs'] === 'disabled' ) ? false : true;

									if ( false !== $enable_tabs ) {


										// If there is campaign data, make a list of fields already in use which might determine if we show any tabs or not.
										$fields_in_tabs = array();

										if ( ! empty( $section['tabs'] ) ) {
											foreach ( $section['tabs'] as $section_tab ) {
												if ( ! empty( $section_tab['fields'] ) ) {
													$fields_in_tabs = array_merge( $fields_in_tabs, $section_tab['fields'] );
												}
											}
										}

										// If there are no fields in any tabs, don't show the tabs.
										if ( empty( $fields_in_tabs ) ) {
											continue;
										}

										$tab_tabs  = (array) isset( $section['tabs'] ) && ! empty( $section['tabs'] ) ? $section['tabs'] : array();
										$tab_order = isset( $campaign_data['tab_order'] ) && ! empty( $campaign_data['tab_order'] ) ? $campaign_data['tab_order'] : array();
										$tab_style = isset( $campaign_data['layout']['advanced']['tab_style'] ) && '' !== trim( $campaign_data['layout']['advanced']['tab_style'] ) ? $campaign_data['layout']['advanced']['tab_style'] : 'medium';
										$tab_size  = isset( $campaign_data['layout']['advanced']['tab_size'] ) && '' !== trim( $campaign_data['layout']['advanced']['tab_size'] ) ? $campaign_data['layout']['advanced']['tab_size'] : 'medium';
										$css_class = isset( $campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : false;

										// sort a multidimensional array matching the same order of keys as another multidimensional array.
										if ( ! empty( $tab_order ) ) {
											$_temp_tab_tabs = array();
											foreach ( $tab_order as $order_id => $tab_id ) {
												if ( isset( $tab_tabs[ $tab_id ] ) ) {
													$_temp_tab_tabs[ $tab_id ] = $tab_tabs[ $tab_id ];
												}
											}
											$tab_tabs = $_temp_tab_tabs;
										}

										?>
									<article>
										<nav class="charitable-campaign-nav charitable-tab-style-<?php echo esc_attr( $tab_style ); ?> charitable-tab-size-<?php echo esc_attr( $tab_size ); ?>">
											<ul>
											<?php if ( ! empty( $tab_tabs ) ) : ?>

													<?php

														$counter = 1;

													foreach ( $tab_tabs as $tab_id => $tab_fields ) :

														$tab_info = $campaign_data['tabs'][ $tab_id ];

														$tab_type  = isset( $tab_info['type'] ) && ! empty( $tab_info['type'] ) ? ( $tab_info['type'] ) : false;
														$tab_title = isset( $tab_info['title'] ) && ! empty( $tab_info['title'] ) ? ( $tab_info['title'] ) : false;
														$tab_desc  = isset( $tab_info['desc'] ) && ! empty( $tab_info['desc'] ) ? ( $tab_info['desc'] ) : false;
														$css_class = 'tab_type_' . $tab_type . ' ';
														$css_class = ( $counter === 1 ) ? 'active' : false;


														?><li id="tab_<?php echo intval( $tab_id ); ?>_title" data-tab-id="<?php echo intval( $tab_id ); ?>" data-tab-type="<?php echo esc_attr( $tab_type ); ?>" class="tab_title <?php echo esc_attr( $css_class ); ?>"><a href="#"><?php echo esc_html( $tab_title ); ?></a></li><?php //phpcs:ignore

															++$counter;

															endforeach;

													?>

												<?php endif; ?>

											</ul>
										</nav>
										<div class="tab-content">

											<ul class="charitable-tabs">

											<?php if ( ! empty( $tab_tabs ) ) : ?>

												<?php

														$counter = 1;

												foreach ( $tab_tabs as $tab_id => $tab_fields ) :

													$tab_info = $campaign_data['tabs'][ $tab_id ];

													$tab_type   = isset( $tab_info['type'] ) && ! empty( $tab_info['type'] ) ? ( $tab_info['type'] ) : false;
													$tab_title  = isset( $tab_info['title'] ) && ! empty( $tab_info['title'] ) ? ( $tab_info['title'] ) : false;
													$tab_desc   = isset( $tab_info['desc'] ) && ! empty( $tab_info['desc'] ) ? ( $tab_info['desc'] ) : false;
													$css_class  = 'tab_type_' . $tab_type . ' ';
													$css_class .= ( $counter === 1 ) ? 'active' : false;


													?>
													<li id="tab_<?php echo intval( $tab_id ); ?>_content" class="tab_content_item <?php echo esc_attr( $css_class ); ?>" data-tab-type="<?php echo esc_attR( $tab_type ); ?>" data-tab-id="<?php echo esc_attr( $tab_id ); ?>">

															<div class="charitable-tab-wrap">

													<?php

														$tab_tabs = (array) isset( $section['tabs'] ) && ! empty( $section['tabs'][ $tab_id ] ) ? $section['tabs'][ $tab_id ] : array();

													if ( ! empty( $tab_tabs['fields'] ) ) :

															$tab_fields_types = isset( $row['fields'] ) ? $row['fields'] : array();

														foreach ( $tab_tabs['fields'] as $tab_field_id => $tab_field_type_id ) :

															$tab_field_data = ! empty( $campaign_data['fields'][ $tab_field_type_id ] ) ? $campaign_data['fields'][ $tab_field_type_id ] : false;
															$tab_field_type = ! empty( $row['fields'][ $tab_field_type_id ] ) ? $row['fields'][ $tab_field_type_id ] : false;

															$field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $tab_field_type ) ) ) );

															if ( class_exists( $field_class ) ) :

																	$class = new $field_class();
																	$class->field_display( $tab_field_type, $tab_field_data, $campaign_data, $is_preview_page, $tab_field_id );

																endif;


																endforeach;

														endif;

													?>
															</div>

														</li>

													<?php

													++$counter;

														endforeach;
												?>

												<?php endif; ?>
											</ul>
										</div>
									</article>

									<?php } // end if tabs ?>

									<?php


								} elseif ( 'fields' === $section_type ) {

									$field_types_data = $row['fields'];

									foreach ( $section['fields'] as $key => $field_id ) :

											$field_data  = ! empty( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;
											$field_type  = false !== $field_data && isset( $field_types_data[ $field_id ] ) ? sanitize_key( $field_types_data[ $field_id ] ) : false;
											$field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $field_type ) ) ) );

										if ( class_exists( $field_class ) ) :

											$class = new $field_class();
											$class->field_display( $field_type, $field_data, $campaign_data, $is_preview_page, $field_id, $template_data );

											endif;

										endforeach;

								}

								++$section_counter;

								echo '</div>';

								echo '<!-- section END -->';

										endforeach;

										++$column_counter;

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

			elseif ( $enabled_tabs && 'tabs' === $row_type ) :

				// Did the user disable tabs entirely?
				$enable_tabs = ( ! empty( $campaign_data['layout']['advanced']['enable_tabs'] ) && $campaign_data['layout']['advanced']['enable_tabs'] === 'disabled' ) ? false : true;

				if ( false === $enable_tabs ) {
					continue;
				}

				$row_tabs  = isset( $row['tabs'] ) ? $row['tabs'] : false;
				$tab_style = isset( $campaign_data['layout']['advanced']['tab_style'] ) && '' !== trim( $campaign_data['layout']['advanced']['tab_style'] ) ? $campaign_data['layout']['advanced']['tab_style'] : 'medium';
				$tab_size  = isset( $campaign_data['layout']['advanced']['tab_size'] ) && '' !== trim( $campaign_data['layout']['advanced']['tab_size'] ) ? $campaign_data['layout']['advanced']['tab_style'] : 'medium';
				$css_class = isset( $campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : false;

				?>

				<article>
					<nav class="charitable-campaign-nav charitable-tab-style-<?php echo esc_attr( $tab_style ); ?> charitable-tab-size-<?php echo esc_attr( $tab_size ); ?>">
						<ul>
							<?php if ( $row_tabs ) : ?>
								<?php

									$counter = 1;

								foreach ( $row_tabs as $tab_id => $tab_fields ) :

									$tab_info = $campaign_data['tabs'][ $tab_id ];

									$tab_type      = isset( $tab_info['type'] ) && ! empty( $tab_info['type'] ) ? ( $tab_info['type'] ) : false;
									$tab_title     = isset( $tab_info['title'] ) && ! empty( $tab_info['title'] ) ? ( $tab_info['title'] ) : false;
									$tab_desc      = isset( $tab_info['desc'] ) && ! empty( $tab_info['desc'] ) ? ( $tab_info['desc'] ) : false;
										$css_class = 'tab_type_' . $tab_type . ' ';
										$css_class = ( $counter === 1 ) ? 'active' : false;


									?>
										<li id="tab_<?php echo intval( $tab_id ); ?>_title" data-tab-id="<?php echo intval( $tab_id ); ?>" data-tab-type="<?php echo esc_attr( $tab_type ); ?>" class="tab_title <?php echo esc_attr( $css_class ); ?>"><a href="#"><?php echo esc_html( $tab_title ); ?></a></li>
										<?php

										++$counter;

										endforeach;
								?>

								<?php endif; ?>
						</ul>
					</nav>
					<div class="tab-content">
							<ul>
							<?php

							if ( $row_tabs ) :

								$counter = 1;

								foreach ( $row_tabs as $tab_id => $tab_fields ) :

									$tab_info = $campaign_data['tabs'][ $tab_id ];

									$tab_type   = isset( $tab_info['type'] ) && ! empty( $tab_info['type'] ) ? ( $tab_info['type'] ) : false;
									$tab_title  = isset( $tab_info['title'] ) && ! empty( $tab_info['title'] ) ? ( $tab_info['title'] ) : false;
									$tab_desc   = isset( $tab_info['desc'] ) && ! empty( $tab_info['desc'] ) ? ( $tab_info['desc'] ) : false;
									$css_class  = 'tab_type_' . $tab_type . ' ';
									$css_class .= ( $counter === 1 ) ? 'active' : false;


									?>
										<li id="tab_<?php echo intval( $tab_id ); ?>_content" class="tab_content_item <?php echo esc_attr( $css_class ); ?>" data-tab-type="<?php echo esc_attr( $tab_type ); ?>" data-tab-id="<?php echo intval( $tab_id ); ?>">

											<div class="charitable-tab-wrap">

									<?php

										$tab_field_info = isset( $row['fields'] ) ? $row['fields'] : false;

									if ( false !== $tab_field_info ) :

											$tab_fields_types = isset( $row['fields'] ) ? $row['fields'] : array();

										foreach ( $tab_fields as $tab_field_id => $tab_field_type_id ) :

											$tab_field_data = ! empty( $campaign_data['fields'][ $tab_field_type_id ] ) ? $campaign_data['fields'][ $tab_field_type_id ] : false;
											$tab_field_type = ! empty( $row['fields'][ $tab_field_type_id ] ) ? $row['fields'][ $tab_field_type_id ] : false;

											$field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $tab_field_type ) ) ) );

											if ( class_exists( $field_class ) ) :

													$class = new $field_class();
													$class->field_display( $tab_field_type, $tab_field_data, $campaign_data, $is_preview_page, $tab_field_id );

												endif;

											endforeach;

										endif;

									?>
											</div>

										</li>

									<?php

									++$counter;

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
	do_action( 'charitable_builder_campaign_content_after', $campaign_data );

	/**
	 * Add something before the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_campaign_content_after', $campaign );

endif;
