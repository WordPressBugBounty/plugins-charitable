<?php
/**
 * Charitable Log DB class.
 *
 * @package   Charitable/Classes/Charitable_Log_DB
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Log_DB' ) ) :

	/**
	 * Charitable_Log_DB
	 *
	 * @since 1.8.11
	 */
	class Charitable_Log_DB extends Charitable_DB {

		/**
		 * The version of our database table.
		 *
		 * @since 1.8.11
		 *
		 * @var string
		 */
		public $version = '1.8.11';

		/**
		 * The name of the primary column.
		 *
		 * @since 1.8.11
		 *
		 * @var string
		 */
		public $primary_key = 'id';

		/**
		 * Columns found in the database.
		 *
		 * @since 1.8.11
		 *
		 * @var array
		 */
		protected $db_columns;

		/**
		 * Set up the database table name.
		 *
		 * @since 1.8.11
		 *
		 * @global WPDB $wpdb
		 */
		public function __construct() {
			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_logs';
		}

		/**
		 * Create the table.
		 *
		 * @since 1.8.11
		 *
		 * @global WPDB $wpdb
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				title varchar(255) NOT NULL DEFAULT '',
				message longtext DEFAULT NULL,
				types varchar(255) NOT NULL DEFAULT 'log',
				level varchar(20) NOT NULL DEFAULT 'info',
				source varchar(100) NOT NULL DEFAULT 'core',
				create_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				campaign_id bigint(20) unsigned DEFAULT 0,
				donation_id bigint(20) unsigned DEFAULT 0,
				donor_id bigint(20) unsigned DEFAULT 0,
				user_id bigint(20) unsigned DEFAULT 0,
				object_id bigint(20) unsigned DEFAULT 0,
				object_type varchar(100) DEFAULT '',
				PRIMARY KEY  (id),
				KEY source (source),
				KEY level (level),
				KEY types (types),
				KEY object_id (object_id),
				KEY object_type (object_type)
			) $charset_collate;";

			$this->_create_table( $sql );
		}

		/**
		 * Return the list of columns that currently exist in the database.
		 *
		 * @since 1.8.11
		 *
		 * @global WPDB $wpdb
		 * @return array
		 */
		public function get_db_columns() {
			if ( ! isset( $this->db_columns ) ) {
				global $wpdb;

				$columns          = $wpdb->get_results( "SHOW COLUMNS FROM $this->table_name" ); // phpcs:ignore
				$this->db_columns = $columns ? wp_list_pluck( $columns, 'Field' ) : array();
			}

			return $this->db_columns;
		}

		/**
		 * Whitelist of columns.
		 *
		 * @since 1.8.11
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'id'          => '%d',
				'title'       => '%s',
				'message'     => '%s',
				'types'       => '%s',
				'level'       => '%s',
				'source'      => '%s',
				'create_at'   => '%s',
				'campaign_id' => '%d',
				'donation_id' => '%d',
				'donor_id'    => '%d',
				'user_id'     => '%d',
				'object_id'   => '%d',
				'object_type' => '%s',
			);
		}

		/**
		 * Default column values.
		 *
		 * @since 1.8.11
		 *
		 * @return array
		 */
		public function get_column_defaults() {
			return array(
				'title'       => '',
				'message'     => '',
				'types'       => 'log',
				'level'       => 'info',
				'source'      => 'core',
				'create_at'   => gmdate( 'Y-m-d H:i:s' ),
				'campaign_id' => 0,
				'donation_id' => 0,
				'donor_id'    => 0,
				'user_id'     => 0,
				'object_id'   => 0,
				'object_type' => '',
			);
		}

		/**
		 * Truncate the table.
		 *
		 * @since 1.8.11
		 *
		 * @global WPDB $wpdb
		 * @return bool
		 */
		public function truncate_table() {
			global $wpdb;

			return false !== $wpdb->query( "TRUNCATE TABLE {$this->table_name}" ); // phpcs:ignore
		}

		/**
		 * Delete records older than a given number of days.
		 *
		 * @since 1.8.11
		 *
		 * @param int $days Number of days to retain.
		 *
		 * @global WPDB $wpdb
		 * @return int Number of rows deleted.
		 */
		public function delete_older_than( $days ) {
			global $wpdb;

			$days   = absint( $days );
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

			return (int) $wpdb->query(
				$wpdb->prepare( "DELETE FROM {$this->table_name} WHERE create_at < %s", $cutoff ) // phpcs:ignore
			);
		}

		/**
		 * Check if the table exists.
		 *
		 * @since 1.8.11
		 *
		 * @global WPDB $wpdb
		 * @return bool
		 */
		public function table_exists() {
			global $wpdb;

			$table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name ) ); // phpcs:ignore

			return $table === $this->table_name;
		}

		/**
		 * Return the total count of log records.
		 *
		 * @since 1.8.11
		 *
		 * @global WPDB $wpdb
		 * @return int
		 */
		public function count() {
			global $wpdb;

			return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$this->table_name}" ); // phpcs:ignore
		}

		/**
		 * Batch insert multiple records.
		 *
		 * @since 1.8.11
		 *
		 * @param array $records Array of record arrays.
		 *
		 * @global WPDB $wpdb
		 * @return int|false Number of rows inserted or false on failure.
		 */
		public function insert_batch( $records ) {
			global $wpdb;

			if ( empty( $records ) ) {
				return 0;
			}

			$columns = array( 'title', 'message', 'types', 'level', 'source', 'create_at', 'campaign_id', 'donation_id', 'donor_id', 'user_id', 'object_id', 'object_type' );
			$placeholders = array();
			$values       = array();

			foreach ( $records as $record ) {
				$placeholders[] = '(%s, %s, %s, %s, %s, %s, %d, %d, %d, %d, %d, %s)';
				$values[]       = $record['title'];
				$values[]       = $record['message'];
				$values[]       = $record['types'];
				$values[]       = $record['level'];
				$values[]       = $record['source'];
				$values[]       = $record['create_at'];
				$values[]       = $record['campaign_id'];
				$values[]       = $record['donation_id'];
				$values[]       = $record['donor_id'];
				$values[]       = $record['user_id'];
				$values[]       = $record['object_id'];
				$values[]       = $record['object_type'];
			}

			$column_list     = implode( ', ', $columns );
			$placeholder_str = implode( ', ', $placeholders );

			return $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$this->table_name} ({$column_list}) VALUES {$placeholder_str}", // phpcs:ignore
					...$values
				)
			);
		}
	}

endif;
