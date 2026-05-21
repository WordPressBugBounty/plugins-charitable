<?php
/**
 * Charitable Log Record class.
 *
 * Value object for a single log entry.
 *
 * @package   Charitable/Classes/Charitable_Log_Record
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Log_Record' ) ) :

	/**
	 * Charitable_Log_Record
	 *
	 * @since 1.8.11
	 */
	class Charitable_Log_Record {

		/**
		 * Record data.
		 *
		 * @since 1.8.11
		 *
		 * @var object
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @since 1.8.11
		 *
		 * @param object $row Database row object from $wpdb->get_row().
		 */
		public function __construct( $row ) {
			$this->data = $row;
		}

		/**
		 * Create a new record from input data.
		 *
		 * @since 1.8.11
		 *
		 * @param string       $title   Log title.
		 * @param string|array $message Log message.
		 * @param array        $args    Additional arguments.
		 * @return array Record data ready for insertion.
		 */
		public static function create( $title, $message, $args = array() ) {
			$defaults = array(
				'types'       => 'log',
				'level'       => 'info',
				'source'      => 'core',
				'campaign_id' => 0,
				'donation_id' => 0,
				'donor_id'    => 0,
				'user_id'     => 0,
				'object_id'   => 0,
				'object_type' => '',
			);

			$args = wp_parse_args( $args, $defaults );

			// Sanitize title.
			$title = sanitize_text_field( $title );

			// Format message — arrays/objects become <pre> HTML.
			if ( is_array( $message ) || is_object( $message ) ) {
				$message = '<pre>' . esc_html( print_r( $message, true ) ) . '</pre>'; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			} else {
				$message = wp_kses(
					(string) $message,
					array(
						'pre'    => array(),
						'code'   => array(),
						'strong' => array(),
						'em'     => array(),
						'br'     => array(),
						'a'      => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
			}

			// Normalize types to comma-separated string.
			$types = $args['types'];
			if ( is_array( $types ) ) {
				$types = implode( ',', array_map( 'sanitize_key', $types ) );
			} else {
				$types = sanitize_key( $types );
			}

			return array(
				'title'       => $title,
				'message'     => $message,
				'types'       => $types,
				'level'       => sanitize_key( $args['level'] ),
				'source'      => sanitize_key( $args['source'] ),
				'create_at'   => gmdate( 'Y-m-d H:i:s' ),
				'campaign_id' => absint( $args['campaign_id'] ),
				'donation_id' => absint( $args['donation_id'] ),
				'donor_id'    => absint( $args['donor_id'] ),
				'user_id'     => absint( $args['user_id'] ),
				'object_id'   => absint( $args['object_id'] ),
				'object_type' => sanitize_key( $args['object_type'] ),
			);
		}

		/**
		 * Get a property from the record.
		 *
		 * @since 1.8.11
		 *
		 * @param string $key Property name.
		 * @return mixed
		 */
		public function __get( $key ) {
			return isset( $this->data->$key ) ? $this->data->$key : null;
		}

		/**
		 * Check if a property is set.
		 *
		 * @since 1.8.11
		 *
		 * @param string $key Property name.
		 * @return bool
		 */
		public function __isset( $key ) {
			return isset( $this->data->$key );
		}

		/**
		 * Get the record types.
		 *
		 * @since 1.8.11
		 *
		 * @param string $view 'key' for slug array, 'label' for translated labels.
		 * @return array
		 */
		public function get_types( $view = 'key' ) {
			$types = array_filter( explode( ',', $this->data->types ) );

			if ( 'label' === $view ) {
				$all_types = Charitable_Log::get_log_types();
				$labels    = array();
				foreach ( $types as $type ) {
					$labels[] = isset( $all_types[ $type ] ) ? $all_types[ $type ] : $type;
				}
				return $labels;
			}

			return $types;
		}

		/**
		 * Get the formatted date.
		 *
		 * @since 1.8.11
		 *
		 * @param string $format 'short', 'full', or 'sql'.
		 * @return string
		 */
		public function get_date( $format = 'short' ) {
			$timestamp = strtotime( $this->data->create_at );

			switch ( $format ) {
				case 'full':
					return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
				case 'sql':
					return $this->data->create_at;
				case 'short':
				default:
					return date_i18n( 'M j, Y g:i a', $timestamp );
			}
		}

		/**
		 * Convert to array for batch insert.
		 *
		 * @since 1.8.11
		 *
		 * @return array
		 */
		public function to_array() {
			return (array) $this->data;
		}
	}

endif;
