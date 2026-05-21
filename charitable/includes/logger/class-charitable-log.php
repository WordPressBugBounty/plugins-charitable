<?php
/**
 * Charitable Log orchestrator class.
 *
 * Main singleton that manages the logging system.
 *
 * @package   Charitable/Classes/Charitable_Log
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Log' ) ) :

	/**
	 * Charitable_Log
	 *
	 * @since 1.8.11
	 */
	class Charitable_Log {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.8.11
		 *
		 * @var Charitable_Log|null
		 */
		private static $instance = null;

		/**
		 * The database instance.
		 *
		 * @since 1.8.11
		 *
		 * @var Charitable_Log_DB
		 */
		private $db;

		/**
		 * The query instance.
		 *
		 * @since 1.8.11
		 *
		 * @var Charitable_Log_Query
		 */
		private $query;

		/**
		 * Queue of records to be saved on shutdown.
		 *
		 * @since 1.8.11
		 *
		 * @var array
		 */
		private $queue = array();

		/**
		 * Whether the shutdown hook has been registered.
		 *
		 * @since 1.8.11
		 *
		 * @var bool
		 */
		private $shutdown_registered = false;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.11
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since 1.8.11
		 *
		 * @return Charitable_Log
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get registered log types.
		 *
		 * @since 1.8.11
		 *
		 * @return array Associative array of type_slug => label.
		 */
		public static function get_log_types() {
			$types = array(
				'donation'      => __( 'Donation', 'charitable' ),
				'payment'       => __( 'Payment', 'charitable' ),
				'gateway'       => __( 'Gateway', 'charitable' ),
				'error'         => __( 'Error', 'charitable' ),
				'log'           => __( 'Log', 'charitable' ),
				'security'      => __( 'Security', 'charitable' ),
				'spam'          => __( 'Spam', 'charitable' ),
				'recurring'     => __( 'Recurring', 'charitable' ),
				'email'         => __( 'Email', 'charitable' ),
				'addon'         => __( 'Addon', 'charitable' ),
				'webhook'       => __( 'Webhook', 'charitable' ),
				'exchange_rate' => __( 'Exchange Rate', 'charitable' ),
				'form'          => __( 'Form', 'charitable' ),
				'migration'     => __( 'Migration', 'charitable' ),
				'notification'  => __( 'Notification', 'charitable' ),
				'newsletter'    => __( 'Newsletter', 'charitable' ),
			);

			/**
			 * Filter the registered log types.
			 *
			 * @since 1.8.11
			 *
			 * @param array $types Associative array of type_slug => label.
			 */
			return apply_filters( 'charitable_log_types', $types );
		}

		/**
		 * Get registered log levels.
		 *
		 * @since 1.8.11
		 *
		 * @return array Associative array of level_slug => label.
		 */
		public static function get_log_levels() {
			return array(
				'error'   => __( 'Error', 'charitable' ),
				'warning' => __( 'Warning', 'charitable' ),
				'info'    => __( 'Info', 'charitable' ),
				'debug'   => __( 'Debug', 'charitable' ),
			);
		}

		/**
		 * Get registered log sources.
		 *
		 * @since 1.8.11
		 *
		 * @return array Associative array of source_slug => label.
		 */
		public static function get_sources() {
			$sources = array(
				'core'       => __( 'Core', 'charitable' ),
				'stripe'     => __( 'Stripe', 'charitable' ),
				'paypal'     => __( 'PayPal', 'charitable' ),
				'square'     => __( 'Square', 'charitable' ),
				'braintree'  => __( 'Braintree', 'charitable' ),
				'mollie'     => __( 'Mollie', 'charitable' ),
				'authorize'  => __( 'Authorize.net', 'charitable' ),
				'windcave'   => __( 'Windcave', 'charitable' ),
				'recurring'  => __( 'Recurring', 'charitable' ),
				'email'      => __( 'Email', 'charitable' ),
				'spam'       => __( 'Spam Blocker', 'charitable' ),
				'form'       => __( 'Form', 'charitable' ),
			);

			/**
			 * Filter the registered log sources.
			 *
			 * @since 1.8.11
			 *
			 * @param array $sources Associative array of source_slug => label.
			 */
			return apply_filters( 'charitable_log_sources', $sources );
		}

		/**
		 * Get the database instance.
		 *
		 * @since 1.8.11
		 *
		 * @return Charitable_Log_DB
		 */
		public function get_db() {
			if ( ! isset( $this->db ) ) {
				$this->db = new Charitable_Log_DB();
			}

			return $this->db;
		}

		/**
		 * Get the query instance.
		 *
		 * @since 1.8.11
		 *
		 * @return Charitable_Log_Query
		 */
		public function get_query() {
			if ( ! isset( $this->query ) ) {
				$this->query = new Charitable_Log_Query( $this->get_db() );
			}

			return $this->query;
		}

		/**
		 * Add a record to the queue.
		 *
		 * @since 1.8.11
		 *
		 * @param array $record Record data array from Charitable_Log_Record::create().
		 */
		public function add( $record ) {
			$this->queue[] = $record;

			if ( ! $this->shutdown_registered ) {
				add_action( 'shutdown', array( $this, 'save_queued_records' ) );
				$this->shutdown_registered = true;
			}
		}

		/**
		 * Save all queued records to the database.
		 *
		 * @since 1.8.11
		 */
		public function save_queued_records() {
			if ( empty( $this->queue ) ) {
				return;
			}

			$this->ensure_table_exists();

			$db = $this->get_db();

			if ( count( $this->queue ) === 1 ) {
				$db->insert( $this->queue[0] );
			} else {
				$db->insert_batch( $this->queue );
			}

			$this->queue = array();
		}

		/**
		 * Ensure the logs table exists (with transient-cached check).
		 *
		 * @since 1.8.11
		 */
		public function ensure_table_exists() {
			$transient_key = 'charitable_logs_table_exists';

			if ( get_transient( $transient_key ) ) {
				return;
			}

			$db = $this->get_db();

			if ( ! $db->table_exists() ) {
				$db->create_table();
			}

			set_transient( $transient_key, true, DAY_IN_SECONDS );
		}

		/**
		 * Check if logging is enabled.
		 *
		 * @since 1.8.11
		 *
		 * @return bool
		 */
		public static function is_enabled() {
			$tools = get_option( 'charitable_tools', array() );

			// Default to enabled.
			if ( ! isset( $tools['logs_enable'] ) ) {
				return true;
			}

			return (bool) $tools['logs_enable'];
		}

		/**
		 * Get the retention days setting.
		 *
		 * @since 1.8.11
		 *
		 * @return int
		 */
		public static function get_retention_days() {
			$tools   = get_option( 'charitable_tools', array() );
			$default = 30;

			return isset( $tools['logs_retention_days'] ) ? absint( $tools['logs_retention_days'] ) : $default;
		}

		/**
		 * AJAX handler: Get a single log record for the modal.
		 *
		 * @since 1.8.11
		 */
		public static function ajax_get_record() {
			check_ajax_referer( 'charitable_logs_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'charitable' ) ) );
				return;
			}

			$id     = isset( $_POST['record_id'] ) ? absint( $_POST['record_id'] ) : 0;
			$record = self::get_instance()->get_query()->get_by_id( $id );

			if ( ! $record ) {
				wp_send_json_error( array( 'message' => __( 'Record not found.', 'charitable' ) ) );
				return;
			}

			$all_types  = self::get_log_types();
			$all_levels = self::get_log_levels();
			$all_sources = self::get_sources();

			$type_labels = array();
			foreach ( $record->get_types( 'key' ) as $type_key ) {
				$type_labels[] = isset( $all_types[ $type_key ] ) ? $all_types[ $type_key ] : $type_key;
			}

			wp_send_json_success( array(
				'id'          => $record->id,
				'title'       => $record->title,
				'message'     => $record->message,
				'types'       => implode( ', ', $type_labels ),
				'level'       => isset( $all_levels[ $record->level ] ) ? $all_levels[ $record->level ] : $record->level,
				'level_key'   => $record->level,
				'source'      => isset( $all_sources[ $record->source ] ) ? $all_sources[ $record->source ] : $record->source,
				'date'        => $record->get_date( 'full' ),
				'campaign_id' => $record->campaign_id,
				'donation_id' => $record->donation_id,
				'donor_id'    => $record->donor_id,
				'user_id'     => $record->user_id,
				'object_id'   => $record->object_id,
				'object_type' => $record->object_type,
			) );
		}

		/**
		 * AJAX handler: Delete all log records.
		 *
		 * @since 1.8.11
		 */
		public static function ajax_delete_all_logs() {
			check_ajax_referer( 'charitable_logs_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'charitable' ) ) );
				return;
			}

			$db = self::get_instance()->get_db();
			$db->truncate_table();

			wp_send_json_success( array( 'message' => __( 'All logs have been deleted.', 'charitable' ) ) );
		}

		/**
		 * AJAX handler: Export logs as CSV.
		 *
		 * @since 1.8.11
		 */
		public static function ajax_export_csv() {
			check_ajax_referer( 'charitable_logs_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'charitable' ) );
			}

			$export = new Charitable_Log_Export();
			$export->run();
		}

		/**
		 * AJAX handler: Toggle logging on/off.
		 *
		 * @since 1.8.11
		 */
		public static function ajax_toggle_logging() {
			check_ajax_referer( 'charitable_logs_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'charitable' ) ) );
				return;
			}

			$enabled = isset( $_POST['enabled'] ) ? absint( $_POST['enabled'] ) : 0;
			$tools   = get_option( 'charitable_tools', array() );

			if ( ! is_array( $tools ) ) {
				$tools = array();
			}

			$tools['logs_enable'] = $enabled;
			update_option( 'charitable_tools', $tools );

			wp_send_json_success( array(
				'message' => $enabled
					? __( 'Logging enabled.', 'charitable' )
					: __( 'Logging disabled.', 'charitable' ),
			) );
		}

		/**
		 * AJAX handler: Save log retention days.
		 *
		 * @since 1.8.11
		 */
		public static function ajax_save_retention() {
			check_ajax_referer( 'charitable_logs_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'charitable' ) ) );
				return;
			}

			$days  = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;
			$tools = get_option( 'charitable_tools', array() );

			if ( ! is_array( $tools ) ) {
				$tools = array();
			}

			$tools['logs_retention_days'] = $days;
			update_option( 'charitable_tools', $tools );

			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %d: number of days */
					__( 'Retention set to %d days.', 'charitable' ),
					$days
				),
			) );
		}

		/**
		 * Enqueue scripts for the logs admin page.
		 *
		 * @since 1.8.11
		 */
		public static function enqueue_scripts() {
			if ( ! charitable_is_tools_view() || ! isset( $_GET['tab'] ) || 'logs' !== $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$version = charitable()->get_version();
			$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script(
				'charitable-logger',
				charitable()->get_path( 'assets', false ) . 'js/admin/charitable-logger.js',
				array( 'jquery', 'wp-util' ),
				$version,
				true
			);

			wp_localize_script(
				'charitable-logger',
				'charitableLogger',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'charitable_logs_nonce' ),
					'i18n'    => array(
						'deleteConfirm' => __( 'Are you sure you want to delete ALL log records? This cannot be undone.', 'charitable' ),
						'deleting'      => __( 'Deleting...', 'charitable' ),
						'deleted'       => __( 'All logs deleted.', 'charitable' ),
						'saving'        => __( 'Saving...', 'charitable' ),
						'saved'         => __( 'Saved.', 'charitable' ),
						'error'         => __( 'An error occurred.', 'charitable' ),
						'close'         => __( 'Close', 'charitable' ),
					),
				)
			);

			wp_enqueue_style(
				'charitable-logger',
				charitable()->get_path( 'assets', false ) . 'css/admin/charitable-logger.css',
				array(),
				$version
			);
		}
	}

endif;
