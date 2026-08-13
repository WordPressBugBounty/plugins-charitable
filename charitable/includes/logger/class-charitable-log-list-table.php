<?php
/**
 * Charitable Log List Table.
 *
 * @package   Charitable/Classes/Charitable_Log_List_Table
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'Charitable_Log_List_Table' ) ) :

	/**
	 * Charitable_Log_List_Table
	 *
	 * @since 1.8.11
	 */
	class Charitable_Log_List_Table extends WP_List_Table {

		/**
		 * Total number of records.
		 *
		 * @since 1.8.11
		 *
		 * @var int
		 */
		private $total_items = 0;

		/**
		 * Constructor.
		 *
		 * @since 1.8.11
		 */
		public function __construct() {
			parent::__construct(
				array(
					'plural'   => esc_html__( 'Logs', 'charitable' ),
					'singular' => esc_html__( 'Log', 'charitable' ),
					'ajax'     => false,
				)
			);
		}

		/**
		 * Get table columns.
		 *
		 * @since 1.8.11
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'title'   => __( 'Title', 'charitable' ),
				'message' => __( 'Message', 'charitable' ),
				'types'   => __( 'Types', 'charitable' ),
				'level'   => __( 'Level', 'charitable' ),
				'source'  => __( 'Source', 'charitable' ),
				'date'    => __( 'Date', 'charitable' ),
			);
		}

		/**
		 * Get sortable columns.
		 *
		 * @since 1.8.11
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			return array(
				'title'  => array( 'title', false ),
				'level'  => array( 'level', false ),
				'source' => array( 'source', false ),
				'date'   => array( 'create_at', true ),
			);
		}

		/**
		 * Prepare items for display.
		 *
		 * @since 1.8.11
		 */
		public function prepare_items() {
			$per_page = 20;
			$page     = $this->get_pagenum();
			$offset   = ( $page - 1 ) * $per_page;

			$args = array(
				'limit'   => $per_page,
				'offset'  => $offset,
				'search'  => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'type'    => isset( $_GET['log_type'] ) ? sanitize_key( $_GET['log_type'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'level'   => isset( $_GET['log_level'] ) ? sanitize_key( $_GET['log_level'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'source'  => isset( $_GET['log_source'] ) ? sanitize_key( $_GET['log_source'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'orderby' => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'id', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'order'   => isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);

			$query  = Charitable_Log::get_instance()->get_query();
			$result = $query->get( $args );

			$this->items       = iterator_to_array( $result['records'] );
			$this->total_items = $result['total'];

			// Explicitly set column headers — this list table renders on the
			// Tools page which doesn't have its own WP_Screen, so the
			// default manage_{screen}_columns filter returns nothing.
			$this->_column_headers = array(
				$this->get_columns(),
				array(),
				$this->get_sortable_columns(),
				$this->get_primary_column_name(),
			);

			$this->set_pagination_args(
				array(
					'total_items' => $this->total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil( $this->total_items / $per_page ),
				)
			);
		}

		/**
		 * Render the title column.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $item Current record.
		 * @return string
		 */
		public function column_title( $item ) {
			return sprintf(
				'<a href="#" class="js-charitable-log-link" data-record-id="%d">%s</a>',
				esc_attr( $item->id ),
				esc_html( $item->title )
			);
		}

		/**
		 * Return a clean label for the primary column's row header aria-label.
		 *
		 * WordPress 7.1 renders the primary column as the row header and reads this
		 * method for the accessible row name. See #32892.
		 *
		 * Declaring this on older WordPress is harmless — nothing calls it there.
		 *
		 * @since  1.8.12
		 *
		 * @param  Charitable_Log_Record $item Current record.
		 * @return string The log title, or an empty string to omit the attribute.
		 */
		protected function get_primary_column_aria_label( $item ) {
			return isset( $item->title ) ? wp_strip_all_tags( $item->title ) : '';
		}

		/**
		 * Render the message column (truncated).
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $item Current record.
		 * @return string
		 */
		public function column_message( $item ) {
			$message = wp_strip_all_tags( $item->message );

			if ( strlen( $message ) > 97 ) {
				$message = substr( $message, 0, 97 ) . '...';
			}

			return esc_html( $message );
		}

		/**
		 * Render the types column.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $item Current record.
		 * @return string
		 */
		public function column_types( $item ) {
			$labels = $item->get_types( 'label' );
			return esc_html( implode( ', ', $labels ) );
		}

		/**
		 * Render the level column with color badge.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $item Current record.
		 * @return string
		 */
		public function column_level( $item ) {
			$levels = Charitable_Log::get_log_levels();
			$label  = isset( $levels[ $item->level ] ) ? $levels[ $item->level ] : $item->level;

			return sprintf(
				'<span class="charitable-log-level charitable-log-level-%s">%s</span>',
				esc_attr( $item->level ),
				esc_html( $label )
			);
		}

		/**
		 * Render the source column.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $item Current record.
		 * @return string
		 */
		public function column_source( $item ) {
			$sources = Charitable_Log::get_sources();
			return esc_html( isset( $sources[ $item->source ] ) ? $sources[ $item->source ] : $item->source );
		}

		/**
		 * Render the date column.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $item Current record.
		 * @return string
		 */
		public function column_date( $item ) {
			return esc_html( $item->get_date( 'short' ) );
		}

		/**
		 * Default column handler.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $item        Current record.
		 * @param string                $column_name Column name.
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
		}

		/**
		 * Render extra controls above/below the table (filter dropdowns).
		 *
		 * @since 1.8.11
		 *
		 * @param string $which 'top' or 'bottom'.
		 */
		public function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			$current_type   = isset( $_GET['log_type'] ) ? sanitize_key( $_GET['log_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_level  = isset( $_GET['log_level'] ) ? sanitize_key( $_GET['log_level'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_source = isset( $_GET['log_source'] ) ? sanitize_key( $_GET['log_source'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="alignleft actions charitable-log-filters">
				<select name="log_type" id="charitable-log-type-filter">
					<option value=""><?php esc_html_e( 'All Types', 'charitable' ); ?></option>
					<?php foreach ( Charitable_Log::get_log_types() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<select name="log_level" id="charitable-log-level-filter">
					<option value=""><?php esc_html_e( 'All Levels', 'charitable' ); ?></option>
					<?php foreach ( Charitable_Log::get_log_levels() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_level, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<select name="log_source" id="charitable-log-source-filter">
					<option value=""><?php esc_html_e( 'All Sources', 'charitable' ); ?></option>
					<?php foreach ( Charitable_Log::get_sources() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_source, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<?php submit_button( __( 'Filter', 'charitable' ), '', 'filter_action', false ); ?>
			</div>
			<?php
		}

		/**
		 * Message to display when there are no items.
		 *
		 * @since 1.8.11
		 */
		public function no_items() {
			esc_html_e( 'No log entries found.', 'charitable' );
		}
	}

endif;
