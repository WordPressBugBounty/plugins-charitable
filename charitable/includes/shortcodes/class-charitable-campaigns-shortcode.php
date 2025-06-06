<?php
/**
 * Campaigns shortcode class.
 *
 * @package   Charitable/Shortcodes/Campaigns
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.45
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaigns_Shortcode' ) ) :

	/**
	 * Charitable_Campaigns_Shortcode class.
	 *
	 * @since   1.0.0
	 */
	class Charitable_Campaigns_Shortcode {

		/**
		 * Display the shortcode output. This is the callback method for the campaigns shortcode.
		 *
		 * @since   1.0.0
		 * @version 1.8.2 added 'description_limit' to the shortcode_atts and view_args filter.
		 *
		 * @param  array $atts The user-defined shortcode attributes.
		 * @return string
		 */
		public static function display( $atts ) {
			$default = array(
				'id'                => '',
				'parent_campaigns'  => '', // only show child campaigns of the IDs provided.
				'orderby'           => 'post_date',
				'order'             => '',
				'number'            => get_option( 'posts_per_page' ),
				'category'          => '',
				'tag'               => '',
				'creator'           => '',
				'exclude'           => '',
				'include_inactive'  => false,
				'columns'           => 2,
				'button'            => 'donate',
				'responsive'        => 1,
				'masonry'           => 0,
				'description_limit' => 100,
				'shortcode'         => 'campaigns',
			);

			$args = shortcode_atts( $default, $atts, 'campaigns' );

			/**
			 * Modify the arguments passed to the campaigns shortcode.
			 *
			 * As an example, Ambassadors extension can add 'fundraiser_type' to the shortcode_atts which is used to filter campaigns by fundraiser type.
			 *
			 * @since 1.8.3
			 *
			 * @param array $args The arguments passed to the shortcode.
			 */
			$args = apply_filters( 'charitable_campaigns_shortcode_atts', $args );

			$args['campaigns'] = self::get_campaigns( $args );

			/**
			 * Replace the default template with your own.
			 *
			 * If you replace the template with your own, it needs to be an instance of Charitable_Template.
			 *
			 * @since 1.0.0
			 *
			 * @param false|Charitable_Template The template. If false (the default), we will use our own template.
			 * @param array $args               All the parsed arguments.
			 */
			$template = apply_filters( 'charitable_campaigns_shortcode_template', false, $args );

			/* Fall back to default Charitable_Template if no template returned or if template was not object of 'Charitable_Template' class. */
			if ( ! is_object( $template ) || ! is_a( $template, 'Charitable_Template' ) ) {
				$template = new Charitable_Template( 'campaign-loop.php', false );
			}

			if ( ! $template->template_file_exists() ) {
				return false;
			}

			/**
			 * Modify the view arguments that are passed to the campaigns shortcode template.
			 *
			 * @since 1.0.0
			 *
			 * @param array $view_args The arguments to pass.
			 * @param array $args      All the parsed arguments.
			 */
			$view_args = apply_filters( 'charitable_campaigns_shortcode_view_args', charitable_array_subset( $args, array( 'campaigns', 'columns', 'button', 'responsive', 'masonry', 'description_limit', 'shortcode' ) ), $args );

			$template->set_view_args( $view_args );

			ob_start();

			$template->render();

			/**
			 * Customize the output of the shortcode.
			 *
			 * @since  1.0.0
			 *
			 * @param  string $content The content to be displayed.
			 * @param  array  $args    All the parsed arguments.
			 * @return string
			 */
			return apply_filters( 'charitable_campaigns_shortcode', ob_get_clean(), $args );
		}

		/**
		 * Return campaigns to display in the campaigns shortcode.
		 *
		 * @since   1.0.0
		 * @since   1.8.4.7
		 *
		 * @param  array $args The query arguments to be used to retrieve campaigns.
		 * @return WP_Query
		 */
		public static function get_campaigns( $args ) {
			$query_args = array(
				'posts_per_page' => $args['number'],
			);

			/* Specific campaign IDs */
			if ( ! empty( $args['id'] ) ) {
				$query_args['post__in'] = explode( ',', $args['id'] );
			}

			/* Only show child campaigns of the IDs provided, added in 1.8.4.7 */
			if ( ! empty( $args['parent_campaigns'] ) ) {
				$query_args['post_parent__in'] = explode( ',', $args['parent_campaigns'] );
			}

			/* Parent IDs */

			/* Pagination */
			if ( ! empty( $args['paged'] ) ) {
				$query_args['paged'] = $args['paged'];
			}

			/* Set category constraint */
			if ( ! empty( $args['category'] ) ) {
				$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'campaign_category',
						'field'    => 'slug',
						'terms'    => explode( ',', $args['category'] ),
					),
				);
			}

			/* Set tag constraint */
			if ( ! empty( $args['tag'] ) ) {
				if ( ! array_key_exists( 'tax_query', $query_args ) ) {
					$query_args['tax_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				}

				$query_args['tax_query'][] = array(
					'taxonomy' => 'campaign_tag',
					'field'    => 'slug',
					'terms'    => explode( ',', $args['tag'] ),
				);
			}

			/* Set author constraint */
			if ( ! empty( $args['creator'] ) ) {
				$query_args['author'] = $args['creator'];
			}

			/* Only include active campaigns if flag is set */
			if ( ! $args['include_inactive'] ) {
				$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_campaign_end_date',
						'value'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						'compare' => '>=',
						'type'    => 'datetime',
					),
					array(
						'key'     => '_campaign_end_date',
						'value'   => 0,
						'compare' => '=',
					),
				);
			}

			if ( ! empty( $args['exclude'] ) ) {
				$query_args['post__not_in'] = explode( ',', $args['exclude'] );
			}

			if ( ! empty( $args['order'] ) && in_array( strtoupper( $args['order'] ), array( 'DESC', 'ASC' ), true ) ) {
				$query_args['order'] = $args['order'];
			}

			/* Return campaigns, ordered by date of creation. */
			if ( 'post_date' === $args['orderby'] ) {
				$query_args['orderby'] = 'date';

				if ( ! isset( $query_args['order'] ) ) {
					$query_args['order'] = 'DESC';
				}
			} elseif ( ! in_array( $args['orderby'], array( 'popular', 'ending' ), true ) ) {
				$query_args['orderby'] = $args['orderby'];
			}

			/**
			 * Filter the campaign query args.
			 *
			 * @since 1.6.28
			 *
			 * @param array $query_args The arguments to be passed to WP_Query.
			 * @param array $args       The shortcode args.
			 */
			$query_args = apply_filters( 'charitable_campaigns_shortcode_query_args', $query_args, $args );

			/**
			 * Finally, query based on the orderby argument.
			 */
			switch ( $args['orderby'] ) {
				case 'popular':
					return Charitable_Campaigns::ordered_by_amount( $query_args );

				case 'ending':
					return Charitable_Campaigns::ordered_by_ending_soon( $query_args );

				default:
					return Charitable_Campaigns::query( $query_args );
			}
		}
	}

endif;
