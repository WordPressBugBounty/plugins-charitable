<?php
/**
 * Charitable Log Query class.
 *
 * SQL query builder with parameterized filters.
 *
 * @package   Charitable/Classes/Charitable_Log_Query
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Log_Query' ) ) :

	/**
	 * Charitable_Log_Query
	 *
	 * @since 1.8.11
	 */
	class Charitable_Log_Query {

		/**
		 * The database instance.
		 *
		 * @since 1.8.11
		 *
		 * @var Charitable_Log_DB
		 */
		private $db;

		/**
		 * Constructor.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_DB $db Database instance.
		 */
		public function __construct( Charitable_Log_DB $db ) {
			$this->db = $db;
		}

		/**
		 * Get log records with filtering and pagination.
		 *
		 * @since 1.8.11
		 *
		 * @param array $args {
		 *     Query arguments.
		 *
		 *     @type int    $limit       Number of records per page. Default 20.
		 *     @type int    $offset      Offset for pagination. Default 0.
		 *     @type string $search      Search term for title and message. Default ''.
		 *     @type string $type        Log type to filter by (REGEXP on comma-separated). Default ''.
		 *     @type string $level       Log level to filter by (exact match). Default ''.
		 *     @type string $source      Log source to filter by (exact match). Default ''.
		 *     @type int    $campaign_id Campaign ID filter. Default 0.
		 *     @type int    $donation_id Donation ID filter. Default 0.
		 *     @type int    $donor_id    Donor ID filter. Default 0.
		 *     @type string $date_from   Start date (Y-m-d format). Default ''.
		 *     @type string $date_to     End date (Y-m-d format). Default ''.
		 *     @type string $orderby     Column to sort by. Default 'id'.
		 *     @type string $order       Sort direction (ASC/DESC). Default 'DESC'.
		 * }
		 * @return array {
		 *     @type Charitable_Log_Records $records Collection of log records.
		 *     @type int                    $total   Total count of matching records.
		 * }
		 */
		public function get( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'limit'       => 20,
				'offset'      => 0,
				'search'      => '',
				'type'        => '',
				'level'       => '',
				'source'      => '',
				'campaign_id' => 0,
				'donation_id' => 0,
				'donor_id'    => 0,
				'date_from'   => '',
				'date_to'     => '',
				'orderby'     => 'id',
				'order'       => 'DESC',
			);

			$args = wp_parse_args( $args, $defaults );

			$table  = $this->db->table_name;
			$where  = array();
			$values = array();

			// Search filter.
			if ( ! empty( $args['search'] ) ) {
				$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
				$where[]  = '(title LIKE %s OR message LIKE %s)';
				$values[] = $like;
				$values[] = $like;
			}

			// Type filter (REGEXP on comma-separated field).
			if ( ! empty( $args['type'] ) ) {
				$type    = sanitize_key( $args['type'] );
				$where[] = 'types REGEXP %s';
				// Match the type as a standalone value in comma-separated list.
				$values[] = '(^|,)' . preg_quote( $type, '/' ) . '(,|$)';
			}

			// Level filter.
			if ( ! empty( $args['level'] ) ) {
				$where[]  = 'level = %s';
				$values[] = sanitize_key( $args['level'] );
			}

			// Source filter.
			if ( ! empty( $args['source'] ) ) {
				$where[]  = 'source = %s';
				$values[] = sanitize_key( $args['source'] );
			}

			// Campaign ID filter.
			if ( ! empty( $args['campaign_id'] ) ) {
				$where[]  = 'campaign_id = %d';
				$values[] = absint( $args['campaign_id'] );
			}

			// Donation ID filter.
			if ( ! empty( $args['donation_id'] ) ) {
				$where[]  = 'donation_id = %d';
				$values[] = absint( $args['donation_id'] );
			}

			// Donor ID filter.
			if ( ! empty( $args['donor_id'] ) ) {
				$where[]  = 'donor_id = %d';
				$values[] = absint( $args['donor_id'] );
			}

			// Date range filters.
			if ( ! empty( $args['date_from'] ) ) {
				$where[]  = 'create_at >= %s';
				$values[] = sanitize_text_field( $args['date_from'] ) . ' 00:00:00';
			}

			if ( ! empty( $args['date_to'] ) ) {
				$where[]  = 'create_at <= %s';
				$values[] = sanitize_text_field( $args['date_to'] ) . ' 23:59:59';
			}

			// Build WHERE clause.
			$where_clause = '';
			if ( ! empty( $where ) ) {
				$where_clause = 'WHERE ' . implode( ' AND ', $where );
			}

			// Validate orderby.
			$allowed_orderby = array( 'id', 'title', 'types', 'level', 'source', 'create_at' );
			$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
			$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			// Build the query.
			$limit    = absint( $args['limit'] );
			$offset   = absint( $args['offset'] );

			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$table} {$where_clause} ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";

			if ( ! empty( $values ) ) {
				$sql = $wpdb->prepare( $sql, ...$values ); // phpcs:ignore
			}

			$rows  = $wpdb->get_results( $sql ); // phpcs:ignore
			$total = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore

			// Build records collection.
			$records = new Charitable_Log_Records();

			if ( $rows ) {
				foreach ( $rows as $row ) {
					$records->push( new Charitable_Log_Record( $row ) );
				}
			}

			return array(
				'records' => $records,
				'total'   => $total,
			);
		}

		/**
		 * Get a single record by ID.
		 *
		 * @since 1.8.11
		 *
		 * @param int $id Record ID.
		 * @return Charitable_Log_Record|null
		 */
		public function get_by_id( $id ) {
			global $wpdb;

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->db->table_name} WHERE id = %d",
					absint( $id )
				)
			);

			return $row ? new Charitable_Log_Record( $row ) : null;
		}
	}

endif;
