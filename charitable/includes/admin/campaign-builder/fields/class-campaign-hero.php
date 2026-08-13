<?php
/**
 * Class to add the Campaign Hero block to a campaign in the builder.
 *
 * A composite block that renders a hero banner with background image, optional
 * avatar/logo, campaign title (or override), an inline raised band (progress),
 * and the mini donation widget. Designed to be the locked structural element
 * of the Beacon family of templates, but reusable in any template.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Field_Campaign_Hero' ) ) :

	/**
	 * Charitable_Field_Campaign_Hero class.
	 *
	 * @since 1.8.11.1
	 */
	class Charitable_Field_Campaign_Hero extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.11.1
		 */
		public function init() {

			$this->name  = esc_html__( 'Campaign Hero', 'charitable' );
			$this->type  = 'campaign-hero';
			$this->icon  = 'fa-star';
			$this->order = 5;
			$this->group = 'standard';

			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = false;
			$this->edit_label        = esc_html__( 'Edit Campaign Hero', 'charitable' );
			$this->edit_type         = 'campaign-hero';
			$this->edit_section      = 'standard';

			$this->tooltip = '';
		}

		/**
		 * Field options panel inside the builder.
		 *
		 * @since 1.8.11.1
		 *
		 * @param array $field Field settings.
		 */
		public function field_options( $field ) {
			// Intentionally empty; all options surface through settings_display().
		}

		/**
		 * Expand a bare filename (e.g. "hero.jpg") to a full URL pointing into the
		 * template's assets folder. Full URLs pass through unchanged.
		 *
		 * @since 1.8.11.1
		 *
		 * @param string $image       The stored image value (URL or filename).
		 * @param string $template_id The campaign's template slug.
		 * @return string The resolved URL, or empty string if no image.
		 */
		protected function resolve_template_image( $image, $template_id ) {
			if ( empty( $image ) ) {
				return '';
			}
			$basename = basename( $image );
			$template_id = preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) $template_id ) );
			if ( $basename === $image && ! empty( $template_id ) ) {
				return charitable()->get_path( 'assets', false ) . 'images/campaign-builder/templates/' . $template_id . '/' . $basename;
			}
			return $image;
		}

		/**
		 * Render the field in the builder preview as a styled placeholder.
		 *
		 * @since 1.8.11.1
		 *
		 * @param array   $field_data    Field data.
		 * @param array   $campaign_data Campaign data and settings.
		 * @param integer $field_id      Field ID.
		 * @param string  $mode          'preview' or 'template'.
		 * @param array   $template_data Template data.
		 * @return string
		 */
		public function render( $field_data = false, $campaign_data = false, $field_id = false, $mode = 'template', $template_data = false ) {

			$html = '';

			if ( 'preview' !== $mode ) {
				return $html;
			}

			$template_id  = is_array( $campaign_data ) && ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id'] : '';
			$bg_raw       = ! empty( $field_data['background_image'] ) ? $field_data['background_image'] : '';
			$avatar_raw   = ! empty( $field_data['avatar_image'] ) ? $field_data['avatar_image'] : '';
			$bg_url       = esc_url( $this->resolve_template_image( $bg_raw, $template_id ) );
			$avatar_url   = esc_url( $this->resolve_template_image( $avatar_raw, $template_id ) );
			$title_text   = ! empty( $field_data['title_override'] ) ? esc_html( $field_data['title_override'] ) : esc_html__( 'Campaign Title', 'charitable' );
			$cta_label    = ! empty( $field_data['cta_label'] ) ? esc_html( $field_data['cta_label'] ) : esc_html__( 'Donate Now', 'charitable' );

			// Read theme colors: primary drives the raised band + selected amount; button drives the CTA.
			$theme_primary = is_array( $campaign_data ) && ! empty( $campaign_data['layout']['advanced']['theme_color_primary'] )
				? $campaign_data['layout']['advanced']['theme_color_primary']
				: ( is_array( $campaign_data ) && ! empty( $campaign_data['color_base_primary'] ) ? $campaign_data['color_base_primary'] : '#1d3a8a' );
			if ( false === strpos( $theme_primary, '#' ) ) {
				$theme_primary = '#' . ltrim( $theme_primary, '#' );
			}
			$theme_button = is_array( $campaign_data ) && ! empty( $campaign_data['layout']['advanced']['theme_color_button'] )
				? $campaign_data['layout']['advanced']['theme_color_button']
				: ( is_array( $campaign_data ) && ! empty( $campaign_data['color_base_button'] ) ? $campaign_data['color_base_button'] : $theme_primary );
			if ( false === strpos( $theme_button, '#' ) ) {
				$theme_button = '#' . ltrim( $theme_button, '#' );
			}
			$accent       = sanitize_hex_color( $theme_primary );
			$accent       = $accent ? $accent : '#1d3a8a';
			$button_color = sanitize_hex_color( $theme_button );
			$button_color = $button_color ? $button_color : $accent;
			$show_raised  = ! isset( $field_data['show_raised'] ) || ! empty( $field_data['show_raised'] );
			$amounts_csv  = ! empty( $field_data['onetime_amounts'] ) ? $field_data['onetime_amounts'] : '50,150,500,1000';
			$amounts      = array_filter( array_map( 'trim', explode( ',', $amounts_csv ) ) );
			$show_other   = ! isset( $field_data['onetime_show_other'] ) || ! empty( $field_data['onetime_show_other'] );

			// Pull the actual goal + donated amount for the raised band. Falls back to 0 for new campaigns.
			$preview_goal = isset( $campaign_data['settings']['general']['goal'] ) ? floatval( $campaign_data['settings']['general']['goal'] ) : 0;
			$preview_donated = 0;
			if ( ! empty( $campaign_data['id'] ) && function_exists( 'charitable_get_campaign' ) ) {
				$preview_campaign = charitable_get_campaign( intval( $campaign_data['id'] ) );
				if ( $preview_campaign ) {
					$preview_donated = (float) $preview_campaign->get_donated_amount();
					// If the goal wasn't in settings (e.g., reading saved meta directly), fall back to the object's goal.
					if ( 0.0 === $preview_goal ) {
						$preview_goal = (float) $preview_campaign->get_goal();
					}
				}
			}
			$preview_donated_fmt = function_exists( 'charitable_format_money' ) ? charitable_format_money( $preview_donated ) : '$' . number_format( $preview_donated, 2 );
			$preview_goal_fmt    = function_exists( 'charitable_format_money' ) ? charitable_format_money( $preview_goal ) : '$' . number_format( $preview_goal, 2 );
			$preview_percent     = $preview_goal > 0 ? min( 100, ( $preview_donated / $preview_goal ) * 100 ) : 0;
			$show_raised_resolved = $show_raised && $preview_goal > 0;

			$base_style_props = '--charitable-hero-accent:' . $accent . ';--charitable-hero-button:' . $button_color . ';';
			$bg_style = $bg_url
				? ' style="' . $base_style_props . 'background-image:linear-gradient(rgba(0,0,0,.2),rgba(0,0,0,.5)),url(\'' . $bg_url . '\');"'
				: ' style="' . $base_style_props . 'background:#2a2a2a;"';

			ob_start();
			?>
			<div class="charitable-hero-preview"<?php echo $bg_style; // phpcs:ignore ?>>
				<div class="charitable-hero-preview__overlay">
					<div class="charitable-hero-preview__identity">
						<?php if ( $avatar_url ) : ?>
							<div class="charitable-hero-preview__avatar">
								<img src="<?php echo esc_url( $avatar_url ); ?>" alt="" />
							</div>
						<?php endif; ?>
						<h1 class="charitable-hero-preview__title<?php echo $avatar_url ? '' : ' charitable-hero-preview__title--no-logo'; ?>"><?php echo esc_html( $title_text ); ?></h1>
					</div>
					<div class="charitable-hero-preview__widget">
						<?php if ( $show_raised_resolved ) : ?>
							<div class="charitable-hero-preview__raised">
								<span>
									<?php
									printf(
										/* translators: 1: amount raised, 2: goal amount */
										esc_html__( '%1$s raised toward our %2$s goal.', 'charitable' ),
										esc_html( $preview_donated_fmt ),
										esc_html( $preview_goal_fmt )
									);
									?>
								</span>
								<div class="charitable-hero-preview__bar"><span style="width:<?php echo esc_attr( $preview_percent ); ?>%;background:#fff;"></span></div>
							</div>
						<?php endif; ?>
						<div class="charitable-hero-preview__amounts" aria-hidden="true">
							<?php foreach ( $amounts as $i => $amount ) : ?>
								<button type="button" tabindex="-1" class="charitable-hero-preview__amount<?php echo 0 === $i ? ' is-selected' : ''; ?>"<?php echo 0 === $i ? ' style="background:' . esc_attr( $accent ) . ';color:#fff;border-color:' . esc_attr( $accent ) . '"' : ''; ?>>$<?php echo esc_html( $amount ); ?></button>
							<?php endforeach; ?>
							<?php if ( $show_other ) : ?>
								<button type="button" tabindex="-1" class="charitable-hero-preview__amount charitable-hero-preview__amount--other"><?php esc_html_e( 'Other', 'charitable' ); ?></button>
							<?php endif; ?>
						</div>
						<button type="button" tabindex="-1" class="charitable-hero-preview__cta" aria-hidden="true"><?php echo esc_html( $cta_label ); ?></button>
					</div>
				</div>
			</div>
			<?php
			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Builder preview wrapper.
		 *
		 * @since 1.8.11.1
		 *
		 * @param array   $field_data    Field data.
		 * @param array   $campaign_data Campaign data and settings.
		 * @param integer $field_id      Field ID.
		 * @param string  $theme         Template data.
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			$html  = $this->field_title( $this->name );
			$html .= $this->field_wrapper( $this->render( $field_data, $campaign_data, $field_id, 'preview' ), $field_data );

			echo $html; // phpcs:ignore
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.11.1
		 *
		 * @param string $field         The field type.
		 * @param array  $field_data    Field data.
		 * @param array  $campaign_data Campaign data and settings.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false ) {

			$campaign_id = isset( $campaign_data['id'] ) ? intval( $campaign_data['id'] ) : 0;
			if ( ! $campaign_id ) {
				return;
			}

			$campaign = charitable_get_campaign( $campaign_id );
			if ( ! $campaign ) {
				return;
			}

			$template_id = ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id'] : '';
			$bg_raw      = ! empty( $field_data['background_image'] ) ? $field_data['background_image'] : '';
			$avatar_raw  = ! empty( $field_data['avatar_image'] ) ? $field_data['avatar_image'] : '';
			$bg_url      = esc_url( $this->resolve_template_image( $bg_raw, $template_id ) );
			$avatar_url  = esc_url( $this->resolve_template_image( $avatar_raw, $template_id ) );
			$title_text  = ! empty( $field_data['title_override'] )
				? $field_data['title_override']
				: get_the_title( $campaign_id );

			// Accent for the hero block IS the template's primary theme color. The CTA uses a
			// separate Button color (also from layout advanced settings). Both fall back through
			// layout → campaign base → Beacon defaults.
			$theme_primary = '';
			if ( ! empty( $campaign_data['layout']['advanced']['theme_color_primary'] ) ) {
				$theme_primary = $campaign_data['layout']['advanced']['theme_color_primary'];
			} elseif ( ! empty( $campaign_data['color_base_primary'] ) ) {
				$theme_primary = $campaign_data['color_base_primary'];
			}
			if ( $theme_primary && false === strpos( $theme_primary, '#' ) ) {
				$theme_primary = '#' . ltrim( $theme_primary, '#' );
			}
			$theme_button = '';
			if ( ! empty( $campaign_data['layout']['advanced']['theme_color_button'] ) ) {
				$theme_button = $campaign_data['layout']['advanced']['theme_color_button'];
			} elseif ( ! empty( $campaign_data['color_base_button'] ) ) {
				$theme_button = $campaign_data['color_base_button'];
			}
			if ( $theme_button && false === strpos( $theme_button, '#' ) ) {
				$theme_button = '#' . ltrim( $theme_button, '#' );
			}
			$accent       = $theme_primary ? sanitize_hex_color( $theme_primary ) : '';
			$button_color = $theme_button ? sanitize_hex_color( $theme_button ) : '';
			$show_raised  = ! isset( $field_data['show_raised'] ) || ! empty( $field_data['show_raised'] );
			$enable_recur = ! empty( $field_data['enable_recurring'] );

			// Raised band data.
			$raised     = (float) $campaign->get_donated_amount();
			$goal       = (float) $campaign->get_goal();
			$raised_fmt = charitable_format_money( $raised );
			$goal_fmt   = $goal > 0 ? charitable_format_money( $goal ) : '';
			$percent    = $goal > 0 ? min( 100, ( $raised / $goal ) * 100 ) : 0;

			// Build the mini-widget args.
			$widget_args = array(
				'campaign_id'        => $campaign_id,
				'cta_mode'           => 'redirect',
				'cta_label'          => ! empty( $field_data['cta_label'] ) ? $field_data['cta_label'] : __( 'Donate Now', 'charitable' ),
				'recurring'          => $enable_recur ? 'true' : 'false',
				'accent_color'       => $accent,
				'onetime_amounts'    => ! empty( $field_data['onetime_amounts'] ) ? $field_data['onetime_amounts'] : '50,150,500,1000',
				'onetime_default'    => ! empty( $field_data['onetime_default'] ) ? $field_data['onetime_default'] : '',
				'onetime_show_other' => ( ! isset( $field_data['onetime_show_other'] ) || ! empty( $field_data['onetime_show_other'] ) ) ? 'true' : 'false',
				'monthly_amounts'    => ! empty( $field_data['monthly_amounts'] ) ? $field_data['monthly_amounts'] : '',
				'monthly_default'    => ! empty( $field_data['monthly_default'] ) ? $field_data['monthly_default'] : '',
				'monthly_show_other' => ! empty( $field_data['monthly_show_other'] ) ? 'true' : 'false',
				'min_amount'         => ! empty( $field_data['min_amount'] ) ? $field_data['min_amount'] : '',
				'max_amount'         => ! empty( $field_data['max_amount'] ) ? $field_data['max_amount'] : '',
				'widget_align'       => 'left',
				'widget_width'       => '100%',
			);

			$accent_or_default = $accent ? $accent : '#1d3a8a';
			$button_or_default = $button_color ? $button_color : $accent_or_default;

			$bg_style = $bg_url
				? ' style="background-image:linear-gradient(rgba(0,0,0,.2),rgba(0,0,0,.5)),url(\'' . $bg_url . '\');"'
				: ' style="background-color:' . esc_attr( $accent_or_default ) . ';"';

			$accent_style = ' style="--charitable-hero-accent:' . esc_attr( $accent_or_default ) . ';--charitable-hero-button:' . esc_attr( $button_or_default ) . ';"';

			$title_extra_class = $avatar_url ? '' : ' charitable-campaign-hero__title--no-logo';

			ob_start();
			?>
			<section class="charitable-campaign-hero"<?php echo $accent_style; // phpcs:ignore ?>>
				<div class="charitable-campaign-hero__banner"<?php echo $bg_style; // phpcs:ignore ?>></div>
				<div class="charitable-campaign-hero__overlay">
					<div class="charitable-campaign-hero__identity">
						<?php if ( $avatar_url ) : ?>
							<div class="charitable-campaign-hero__avatar">
								<img src="<?php echo esc_url( $avatar_url ); ?>" alt="" />
							</div>
						<?php endif; ?>
						<h1 class="charitable-campaign-hero__title<?php echo esc_attr( $title_extra_class ); ?>"><?php echo esc_html( $title_text ); ?></h1>
					</div>
					<div class="charitable-campaign-hero__widget-shell">
						<?php if ( $show_raised && $goal > 0 ) : ?>
							<div class="charitable-campaign-hero__raised">
								<div class="charitable-campaign-hero__raised-text">
									<?php
									printf(
										/* translators: 1: amount raised, 2: goal amount */
										esc_html__( '%1$s raised toward our %2$s goal.', 'charitable' ),
										esc_html( $raised_fmt ),
										esc_html( $goal_fmt )
									);
									?>
								</div>
								<div class="charitable-campaign-hero__progress">
									<span style="width:<?php echo esc_attr( $percent ); ?>%;"></span>
								</div>
							</div>
						<?php endif; ?>
						<div class="charitable-campaign-hero__widget">
							<?php echo Charitable_Mini_Widget::render( $widget_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
				</div>
			</section>
			<?php
			$html = ob_get_clean();

			$html = $this->field_display_wrapper( $html, $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Settings panel rendered inside the builder's right sidebar.
		 *
		 * @since 1.8.11.1
		 *
		 * @param integer $field_id      Field ID.
		 * @param array   $campaign_data Campaign data.
		 * @return string
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			$ff       = new Charitable_Builder_Form_Fields();
			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : array();

			$has_recurring_addon = class_exists( 'Charitable_Recurring' );

			// Resolve bare filenames so the uploader UI shows the actual asset URL.
			$template_id = ! empty( $campaign_data['template_id'] ) ? $campaign_data['template_id'] : '';
			if ( ! empty( $settings['background_image'] ) ) {
				$settings['background_image'] = $this->resolve_template_image( $settings['background_image'], $template_id );
			}
			if ( ! empty( $settings['avatar_image'] ) ) {
				$settings['avatar_image'] = $this->resolve_template_image( $settings['avatar_image'], $template_id );
			}

			// Look up the template's original default values for the hero block (used by Reset buttons).
			$template_default_bg     = '';
			$template_default_avatar = '';
			if ( $template_id && class_exists( 'Charitable_Campaign_Builder_Templates' ) ) {
				$template_data = ( new Charitable_Campaign_Builder_Templates() )->get_template_data( $template_id );
				if ( ! empty( $template_data['layout'] ) && is_array( $template_data['layout'] ) ) {
					foreach ( $template_data['layout'] as $row ) {
						if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
							continue;
						}
						foreach ( $row['columns'] as $column ) {
							if ( ! is_array( $column ) ) {
								continue;
							}
							foreach ( $column as $section ) {
								if ( empty( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
									continue;
								}
								foreach ( $section['fields'] as $field_cfg ) {
									if ( is_array( $field_cfg ) && isset( $field_cfg['type'] ) && 'campaign-hero' === $field_cfg['type'] ) {
										$template_default_bg     = ! empty( $field_cfg['background_image'] ) ? $field_cfg['background_image'] : '';
										$template_default_avatar = ! empty( $field_cfg['avatar_image'] ) ? $field_cfg['avatar_image'] : '';
										break 3;
									}
								}
							}
						}
					}
				}
			}
			$template_default_bg_url     = $this->resolve_template_image( $template_default_bg, $template_id );
			$template_default_avatar_url = $this->resolve_template_image( $template_default_avatar, $template_id );

			ob_start();
			?>
			<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>
			<?php

			// Background image.
			echo $ff->generate_uploader( // phpcs:ignore
				isset( $settings['background_image'] ) ? $settings['background_image'] : '',
				esc_html__( 'Background Image', 'charitable' ),
				array(
					'id'           => 'field_' . esc_attr( $this->type ) . '_background_image_' . intval( $field_id ),
					'name'         => array( '_fields', intval( $field_id ), 'background_image' ),
					'field_id'     => esc_attr( $field_id ),
					'button_label' => esc_html__( 'Upload', 'charitable' ),
					'placeholder'  => 'https://',
					'tooltip'      => esc_html__( 'The hero banner image. Renders edge-to-edge behind the title and donation widget. Clear to show a solid accent color; Reset to restore the template default.', 'charitable' ),
				)
			);

			if ( ! empty( $template_default_bg_url ) ) {
				?>
				<div class="charitable-campaign-hero-reset-row">
					<button type="button" class="charitable-campaign-hero-reset-image charitable-campaign-builder-button-secondary" data-default="<?php echo esc_url( $template_default_bg_url ); ?>" data-target-label="background_image"><?php esc_html_e( 'Reset', 'charitable' ); ?></button>
				</div>
				<?php
			}

			// Logo image.
			echo $ff->generate_uploader( // phpcs:ignore
				isset( $settings['avatar_image'] ) ? $settings['avatar_image'] : '',
				esc_html__( 'Logo Image', 'charitable' ),
				array(
					'id'           => 'field_' . esc_attr( $this->type ) . '_avatar_image_' . intval( $field_id ),
					'name'         => array( '_fields', intval( $field_id ), 'avatar_image' ),
					'field_id'     => esc_attr( $field_id ),
					'button_label' => esc_html__( 'Upload', 'charitable' ),
					'placeholder'  => 'https://',
					'tooltip'      => esc_html__( 'Optional small logo overlapping the bottom-left of the hero. Clear to remove the logo entirely (the title shifts left); Reset to restore the template default.', 'charitable' ),
				)
			);

			if ( ! empty( $template_default_avatar_url ) ) {
				?>
				<div class="charitable-campaign-hero-reset-row">
					<button type="button" class="charitable-campaign-hero-reset-image charitable-campaign-builder-button-secondary" data-default="<?php echo esc_url( $template_default_avatar_url ); ?>" data-target-label="avatar_image"><?php esc_html_e( 'Reset', 'charitable' ); ?></button>
				</div>
				<?php
			}

			// Title override.
			echo $ff->generate_text( // phpcs:ignore
				isset( $settings['title_override'] ) ? $settings['title_override'] : '',
				esc_html__( 'Title Override', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_title_override_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'title_override' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Optional. Leave blank to use the campaign post title.', 'charitable' ),
				)
			);

			// Show raised amount + progress bar.
			// Hidden fallback ensures an unchecked toggle still submits a value (0). Without it the
			// key is absent from the POST and the read-side `! isset()` fallback re-defaults to ON,
			// making "off" silently revert on save.
			echo '<input type="hidden" name="_fields[' . intval( $field_id ) . '][show_raised]" value="0" />';
			echo $ff->generate_toggle( // phpcs:ignore
				( ! isset( $settings['show_raised'] ) || ! empty( $settings['show_raised'] ) ) ? '1' : false,
				esc_html__( 'Show Raised Amount + Progress Bar', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_show_raised_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'show_raised' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Displays the "$X raised toward our $Y goal" headline and progress bar above the donation widget. Only appears when a campaign goal is set (Settings → General Settings → Goal).', 'charitable' ),
				)
			);

			// Enable recurring — disabled when the Recurring Donations addon isn't active.
			echo '<input type="hidden" name="_fields[' . intval( $field_id ) . '][enable_recurring]" value="0" />';
			echo $ff->generate_toggle( // phpcs:ignore
				( $has_recurring_addon && ! empty( $settings['enable_recurring'] ) ) ? '1' : false,
				esc_html__( 'Enable Recurring Donations', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_enable_recurring_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'enable_recurring' ),
					'field_id' => intval( $field_id ),
					'disabled' => ! $has_recurring_addon,
					'tooltip'  => $has_recurring_addon
						? esc_html__( 'Show monthly/one-time tabs in the donation widget.', 'charitable' )
						: esc_html__( 'Requires the Charitable Recurring Donations addon. Install and activate the addon to enable this option.', 'charitable' ),
				)
			);

			// One-time amounts.
			echo $ff->generate_text( // phpcs:ignore
				isset( $settings['onetime_amounts'] ) ? $settings['onetime_amounts'] : '50,150,500,1000',
				esc_html__( 'One-Time Amounts', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_onetime_amounts_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'onetime_amounts' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Comma-separated list of suggested amounts, e.g. 50,150,500,1000.', 'charitable' ),
				)
			);

			// One-time default.
			echo $ff->generate_text( // phpcs:ignore
				isset( $settings['onetime_default'] ) ? $settings['onetime_default'] : '',
				esc_html__( 'One-Time Default', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_onetime_default_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'onetime_default' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Pre-selected amount on page load. Must match one of the amounts above. Leave blank to use the first amount.', 'charitable' ),
				)
			);

			// Show "Other" toggle.
			echo '<input type="hidden" name="_fields[' . intval( $field_id ) . '][onetime_show_other]" value="0" />';
			echo $ff->generate_toggle( // phpcs:ignore
				( isset( $settings['onetime_show_other'] ) ? ! empty( $settings['onetime_show_other'] ) : true ) ? '1' : false,
				esc_html__( 'Show "Other" Amount Option', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_onetime_show_other_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'onetime_show_other' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Show a custom amount input alongside the suggested amounts.', 'charitable' ),
				)
			);

			// Donate button text.
			echo $ff->generate_text( // phpcs:ignore
				isset( $settings['cta_label'] ) ? $settings['cta_label'] : '',
				esc_html__( 'Donate Button Text', 'charitable' ),
				array(
					'id'          => 'field_' . esc_attr( $this->type ) . '_cta_label_' . intval( $field_id ),
					'name'        => array( '_fields', intval( $field_id ), 'cta_label' ),
					'field_id'    => intval( $field_id ),
					'placeholder' => esc_attr__( 'Donate Now', 'charitable' ),
					'tooltip'     => esc_html__( 'Label shown on the donation button. Leave blank to use "Donate Now". The button color follows the campaign theme\'s Button color (Layout Options → Theme Colors → Button).', 'charitable' ),
				)
			);

			// CSS class.
			echo $ff->generate_text( // phpcs:ignore
				isset( $settings['css_class'] ) ? $settings['css_class'] : '',
				esc_html__( 'CSS Class', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_css_class_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'css_class' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add CSS classes (space-separated) to customize this hero in your theme.', 'charitable' ),
				)
			);

			return ob_get_clean();
		}

		/**
		 * Display settings for AJAX requests.
		 *
		 * @since 1.8.11.1
		 * @return void
		 */
		public function settings_display_ajax() {

			check_ajax_referer( 'charitable-builder', 'nonce' );

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error();
			}

			$field_id    = isset( $_POST['field_id'] ) ? intval( wp_unslash( $_POST['field_id'] ) ) : 0;
			$campaign_id = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0;

			$campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

			$html = $this->settings_display( $field_id, $campaign_data );

			wp_send_json_success( array( 'html' => $html ) );
			exit;
		}

		/**
		 * Format and sanitize submitted field data.
		 *
		 * @since 1.8.11.1
		 *
		 * @param int   $field_id      Field ID.
		 * @param mixed $field_submit  Submitted value.
		 * @param array $campaign_data Campaign data.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.11.1
		 *
		 * @param int   $field_id     Field ID.
		 * @param mixed $field_submit Submitted value.
		 * @param array $campaign_data Campaign data.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}
	}

	new Charitable_Field_Campaign_Hero();

endif;
