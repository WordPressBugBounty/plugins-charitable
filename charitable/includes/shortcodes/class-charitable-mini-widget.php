<?php
/**
 * Mini Donation Widget renderer.
 *
 * Ported from Charitable Pro 1.8.16 (class-charitable-mini-widget-shortcode.php)
 * for use by the campaign-hero block. Not registered as a public shortcode in Lite;
 * exposed via Charitable_Mini_Widget::render( $args ) so it can be consumed by any
 * future shortcode handler or Gutenberg block.
 *
 * @package   Charitable/Shortcodes/Mini_Widget
 * @author    David Bisset
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Mini_Widget' ) ) :

	/**
	 * Charitable_Mini_Widget class.
	 *
	 * @since 1.8.11.1
	 */
	class Charitable_Mini_Widget {

		/**
		 * Whether the widget's CSS/JS assets have already been enqueued for this request.
		 *
		 * @since 1.8.11.1
		 * @var   bool
		 */
		private static $assets_enqueued = false;

		/**
		 * Enqueue the widget's CSS and JS. Idempotent — safe to call multiple times.
		 *
		 * @since 1.8.11.1
		 *
		 * @return void
		 */
		public static function enqueue_assets() {
			if ( self::$assets_enqueued ) {
				return;
			}

			$assets_dir = charitable()->get_path( 'assets', false );
			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();

			wp_enqueue_style(
				'charitable-mini-widget',
				$assets_dir . 'css/charitable-mini-widget' . $min . '.css',
				array(),
				$version
			);

			wp_enqueue_script(
				'charitable-mini-widget',
				$assets_dir . 'js/frontend/charitable-mini-widget' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			wp_localize_script(
				'charitable-mini-widget',
				'wpCharitableMiniWidget',
				array(
					'i18n' => array(
						'other'              => __( 'Other', 'charitable' ),
						'enterAmount'        => __( 'Enter amount', 'charitable' ),
						'enterAmountMonthly' => __( 'Enter monthly amount', 'charitable' ),
						'minError'           => __( 'Minimum amount is %s', 'charitable' ),
						'maxError'           => __( 'Maximum amount is %s', 'charitable' ),
					),
				)
			);

			self::$assets_enqueued = true;
		}

		/**
		 * Render the mini donation widget.
		 *
		 * Accepts the same argument keys as the Pro [charitable_mini_widget] shortcode
		 * (campaign_id, cta_mode, cta_label, recurring, accent_color, etc.) plus dynamic
		 * monthly_impact_X / onetime_impact_X impact keys.
		 *
		 * @since  1.8.11.1
		 *
		 * @param  array $args Render arguments. See $defaults below for accepted keys.
		 * @return string The rendered HTML, or an empty string on failure.
		 */
		public static function render( $args = array() ) {
			// Extract dynamic impact keys before the defaults merge strips them.
			$impact_map = array(
				'monthly' => array(),
				'onetime' => array(),
			);

			if ( is_array( $args ) ) {
				foreach ( $args as $key => $value ) {
					if ( preg_match( '/^(monthly|onetime)_impact_(\d+(?:\.\d+)?)$/', $key, $matches ) ) {
						$tab    = $matches[1];
						$amount = $matches[2];
						$impact_map[ $tab ][ $amount ] = wp_kses( $value, array() );
					}
				}
			}

			// Parse static attributes.
			$defaults = array(
				'campaign_id'        => '',
				'cta_mode'           => 'redirect',
				'cta_label'          => __( 'Donate Now', 'charitable' ),
				'recurring'          => 'false',
				'show_currency'      => 'false',
				'min_amount'         => '',
				'max_amount'         => '',
				'accent_color'       => '',
				'class'              => '',
				'monthly_amounts'    => '',
				'monthly_default'    => '',
				'monthly_show_other' => 'false',
				'onetime_amounts'    => '',
				'onetime_default'    => '',
				'onetime_show_other' => 'false',
				'widget_align'       => 'center',
				'widget_width'       => '',
				'impact_align'       => '',
				'monthly_label'      => '',
				'give_once_label'    => '',
				'widget_title'       => '',
			);

			$args = wp_parse_args( $args, $defaults );

			// Validate campaign_id.
			$campaign_id = absint( $args['campaign_id'] );
			if ( ! $campaign_id ) {
				if ( current_user_can( 'edit_posts' ) ) {
					return '<p><em>' . esc_html__( 'Mini Donation Widget: campaign_id is required.', 'charitable' ) . '</em></p>';
				}
				return '';
			}

			$campaign = get_post( $campaign_id );
			if ( ! $campaign || 'campaign' !== $campaign->post_type ) {
				if ( current_user_can( 'edit_posts' ) ) {
					return '<p><em>' . esc_html__( 'Mini Donation Widget: campaign not found.', 'charitable' ) . '</em></p>';
				}
				return '';
			}

			// Page-builder preview detection (WPBakery / Divi).
			$is_wpbakery = defined( 'VCV_VERSION' ) ||
				( isset( $_GET['vc_action'] ) && 'vc_inline' === $_GET['vc_action'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				( function_exists( 'vc_is_inline' ) && vc_is_inline() );
			$is_divi    = defined( 'DOING_AJAX' ) && DOING_AJAX && defined( 'ET_BUILDER_VERSION' );
			$is_preview = $is_wpbakery || $is_divi;

			if ( $is_preview ) {
				return '<div class="charitable-mini-widget charitable-mini-widget--preview" style="pointer-events:none;opacity:.7;border:2px dashed #ccc;padding:20px;text-align:center;">' .
					'<p style="margin:0;">' . esc_html__( 'Mini Donation Widget — preview only', 'charitable' ) . '</p>' .
					'</div>';
			}

			// Auto-enqueue assets on first render.
			self::enqueue_assets();

			// Normalize booleans.
			$recurring     = self::parse_bool( $args['recurring'] );
			$show_currency = self::parse_bool( $args['show_currency'] );
			$show_other_m  = self::parse_bool( $args['monthly_show_other'] );
			$show_other_o  = self::parse_bool( $args['onetime_show_other'] );

			// Recurring donations addon detection.
			$recurring_active = $recurring && class_exists( 'Charitable_Recurring' );

			// Verify the selected campaign has recurring enabled at the campaign level.
			if ( $recurring_active ) {
				$campaign_recurring_mode = get_post_meta( $campaign_id, '_campaign_recurring_donation_mode', true );
				if ( empty( $campaign_recurring_mode ) || 'disabled' === $campaign_recurring_mode ) {
					$recurring_active = false;
				}
			}

			// Parse amounts.
			$monthly_amounts = self::parse_amounts( $args['monthly_amounts'] );
			$onetime_amounts = self::parse_amounts( $args['onetime_amounts'] );

			// Fallback: if no onetime amounts but monthly amounts exist, share them.
			if ( empty( $onetime_amounts ) && ! empty( $monthly_amounts ) ) {
				$onetime_amounts = $monthly_amounts;
			}

			$monthly_default = self::resolve_default( $args['monthly_default'], $monthly_amounts );
			$onetime_default = self::resolve_default( $args['onetime_default'], $onetime_amounts );

			$min_amount = '' !== $args['min_amount'] ? floatval( $args['min_amount'] ) : null;
			$max_amount = '' !== $args['max_amount'] ? floatval( $args['max_amount'] ) : null;

			$accent_color = '' !== $args['accent_color'] ? sanitize_hex_color( $args['accent_color'] ) : '';

			$cta_mode = in_array( $args['cta_mode'], array( 'redirect', 'modal' ), true ) ? $args['cta_mode'] : 'redirect';

			// In modal mode, ensure Stripe assets get enqueued if they're registered elsewhere.
			if ( 'modal' === $cta_mode ) {
				if ( wp_script_is( 'charitable-stripe', 'registered' ) ) {
					wp_enqueue_script( 'charitable-stripe' );
				}
				if ( wp_script_is( 'stripe-v3', 'registered' ) ) {
					wp_enqueue_script( 'stripe-v3' );
				}
			}

			$campaign_url = ( 'redirect' === $cta_mode )
				? charitable_get_permalink( 'campaign_donation', array( 'campaign_id' => $campaign_id ) )
				: '';

			$currency_code   = charitable_get_currency();
			$currency_symbol = charitable_get_currency_helper()->get_currency_symbol();
			$currency_pos    = charitable_get_option( 'currency_format', 'left' );

			$widget_align = in_array( $args['widget_align'], array( 'left', 'center', 'right' ), true )
				? $args['widget_align'] : 'left';
			$widget_width = sanitize_text_field( $args['widget_width'] );

			$view_args = array(
				'campaign_id'        => $campaign_id,
				'cta_mode'           => $cta_mode,
				'cta_label'          => sanitize_text_field( $args['cta_label'] ),
				'recurring_active'   => $recurring_active,
				'show_currency'      => $show_currency,
				'min_amount'         => $min_amount,
				'max_amount'         => $max_amount,
				'accent_color'       => $accent_color,
				'extra_class'        => implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) ),
				'monthly_amounts'    => $monthly_amounts,
				'monthly_default'    => $monthly_default,
				'monthly_show_other' => $show_other_m,
				'monthly_impact'     => $impact_map['monthly'],
				'onetime_amounts'    => $onetime_amounts,
				'onetime_default'    => $onetime_default,
				'onetime_show_other' => $show_other_o,
				'onetime_impact'     => $impact_map['onetime'],
				'campaign_url'       => $campaign_url,
				'currency_code'      => $currency_code,
				'currency_symbol'    => $currency_symbol,
				'currency_pos'       => $currency_pos,
				'widget_align'       => $widget_align,
				'widget_width'       => $widget_width,
				'impact_align'       => in_array( $args['impact_align'], array( 'left', 'center', 'right' ), true ) ? $args['impact_align'] : '',
				'monthly_label'      => sanitize_text_field( $args['monthly_label'] ),
				'give_once_label'    => sanitize_text_field( $args['give_once_label'] ),
				'widget_title'       => sanitize_text_field( $args['widget_title'] ),
			);

			ob_start();

			charitable_template( 'shortcodes/mini-widget.php', $view_args );

			/**
			 * Filter the output of the mini donation widget.
			 *
			 * @since 1.8.11.1
			 *
			 * @param string $output The rendered HTML.
			 * @param array  $args   The render arguments.
			 */
			return apply_filters( 'charitable_mini_widget_output', ob_get_clean(), $args );
		}

		/**
		 * Parse a comma-delimited amounts string into an array of floats.
		 *
		 * @since  1.8.11.1
		 *
		 * @param  string $amounts_string Comma-delimited amounts, e.g. "10,20,40,100".
		 * @return float[]
		 */
		public static function parse_amounts( $amounts_string ) {
			if ( empty( $amounts_string ) ) {
				return array();
			}

			$parts = explode( ',', $amounts_string );
			$out   = array();

			foreach ( $parts as $part ) {
				$val = floatval( trim( $part ) );
				if ( $val > 0 ) {
					$out[] = $val;
				}
			}

			return $out;
		}

		/**
		 * Resolve a default amount against an amounts list. Falls back to the first amount.
		 *
		 * @since  1.8.11.1
		 *
		 * @param  mixed   $default Default amount (0 = no explicit default).
		 * @param  float[] $amounts The amounts list.
		 * @return float 0 if list is empty, otherwise a valid amount from the list.
		 */
		public static function resolve_default( $default, $amounts ) {
			if ( empty( $amounts ) ) {
				return 0;
			}

			$default = floatval( $default );

			if ( $default > 0 && in_array( $default, $amounts, true ) ) {
				return $default;
			}

			return $amounts[0];
		}

		/**
		 * Parse a boolean-ish string or value.
		 *
		 * @since  1.8.11.1
		 *
		 * @param  mixed $value The value to parse.
		 * @return bool
		 */
		public static function parse_bool( $value ) {
			if ( is_bool( $value ) ) {
				return $value;
			}

			return in_array( strtolower( (string) $value ), array( 'true', '1', 'yes' ), true );
		}
	}

endif;
