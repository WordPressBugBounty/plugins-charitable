<?php
/**
 * Responsible for parsing and displaying the output of the [charitable_stat] shortcode.
 *
 * @package   Charitable_Stat/Classes/Charitable_Stat_Shortcode
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stat_Shortcode' ) ) :

	/**
	 * Charitable_Stat_Shortcode
	 *
	 * @since 1.6.0
	 */
	class Charitable_Stat_Shortcode {

		/**
		 * The type of query.
		 *
		 * @since 1.6.0
		 *
		 * @var   string
		 */
		private $type;

		/**
		 * Mixed set of arguments for the query.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		private $args;

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 *
		 * @param array $atts User-defined attributes.
		 */
		private function __construct( $atts ) {
			$this->args   = $this->parse_args( $atts );
			$this->type   = $this->args['display'];
			$this->report = $this->get_report();
		}

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 *
		 * @param  array $atts User-defined attributes.
		 * @return string
		 */
		public static function display( $atts ) {
			$object = new Charitable_Stat_Shortcode( $atts );

			return $object->get_query_result();
		}

		/**
		 * Return the query result.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		public function get_query_result() {
			switch ( $this->type ) {
				case 'progress':
					$total = $this->report->get_report( 'amount' );

					if ( ! $this->args['goal'] ) {
						return charitable_format_money( $total );
					}

					$goal    = charitable_sanitize_amount( $this->args['goal'], false );
					$total   = charitable_sanitize_amount( $total, true );
					$percent = ( $total / $goal ) * 100;

					return '<div class="campaign-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $percent . '"><span class="bar" style="width:' . $percent . '%;"></span></div>';

				case 'total':
					return charitable_format_money( $this->report->get_report( 'amount' ) );

				case 'donors':
				case 'donations':
					return (string) $this->report->get_report( $this->type );
			}
		}

		/**
		 * Parse shortcode attributes.
		 *
		 * @since   1.6.0
		 * @version 1.8.3.5 added 'campaign_categories' to the shortcode attributes.
		 *
		 * @param  array $atts User-defined attributes.
		 * @return array
		 */
		private function parse_args( $atts ) {
			$defaults = array(
				'display'             => 'total',
				'campaigns'           => '',
				'goal'                => false,
				'campaign_categories' => '',
			);

			$args = shortcode_atts( $defaults, $atts, 'charitable_stat' );

			if ( '' !== $args['campaign_categories'] ) {
				$campaign_categories = str_replace( ', ', ',', $args['campaign_categories'] );
				$campaign_categories = explode( ',', $args['campaign_categories'] );
				$cat_args            = [
					'post_type'      => 'campaign',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy' => 'campaign_category',
							'field'    => 'slug',
							'terms'    => $campaign_categories,
						],
					],
				];
				$cat_query           = new WP_Query( $cat_args );
				if ( ! empty( $cat_query->posts ) ) {
					$campaigns         = $cat_query->posts;
					$args['campaigns'] = implode( ',', $campaigns );
				}
			}

			$args['campaigns'] = strlen( $args['campaigns'] ) ? explode( ',', $args['campaigns'] ) : array();

			return $args;
		}

		/**
		 * Run the report for the shortcode.
		 *
		 * @since  1.6.0
		 *
		 * @return Charitable_Donation_Report
		 */
		private function get_report() {
			return new Charitable_Donation_Report( $this->get_report_args() );
		}

		/**
		 * Return the arguments used for generating the report.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		private function get_report_args() {
			$args                = array();
			$args['report_type'] = in_array( $this->type, array( 'progress', 'total' ), true ) ? 'amount' : $this->type;
			$args['campaigns']   = $this->args['campaigns'];

			return $args;
		}
	}

endif;
