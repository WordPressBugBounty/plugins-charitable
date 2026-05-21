<?php
/**
 * Charitable Log Export class.
 *
 * Handles CSV export of log records.
 *
 * @package   Charitable/Classes/Charitable_Log_Export
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Log_Export' ) ) :

	/**
	 * Charitable_Log_Export
	 *
	 * @since 1.8.11
	 */
	class Charitable_Log_Export {

		/**
		 * Run the export.
		 *
		 * @since 1.8.11
		 */
		public function run() {
			$export_all = isset( $_GET['export_all'] ) && '1' === $_GET['export_all']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$args = array(
				'limit' => 999999,
			);

			// Apply filters unless exporting all.
			if ( ! $export_all ) {
				if ( ! empty( $_GET['log_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$args['type'] = sanitize_key( $_GET['log_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				if ( ! empty( $_GET['log_level'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$args['level'] = sanitize_key( $_GET['log_level'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				if ( ! empty( $_GET['log_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$args['source'] = sanitize_key( $_GET['log_source'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				if ( ! empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
			}

			$query  = Charitable_Log::get_instance()->get_query();
			$result = $query->get( $args );

			$filename = 'charitable-logs-' . gmdate( 'Y-m-d' ) . '.csv';

			// Discard any prior output so the CSV stream isn't corrupted by stray notices/echoes.
			while ( ob_get_level() ) {
				ob_end_clean();
			}

			// Headers.
			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $filename );

			$output = fopen( 'php://output', 'w' );

			// Column headers.
			fputcsv(
				$output,
				array(
					'ID',
					'Title',
					'Message',
					'Types',
					'Level',
					'Source',
					'Date',
					'Campaign ID',
					'Donation ID',
					'Donor ID',
					'User ID',
					'Object ID',
					'Object Type',
				),
				',',
				'"',
				'\\'
			);

			// Data rows.
			foreach ( $result['records'] as $record ) {
				fputcsv(
					$output,
					array_map(
						array( $this, 'csv_safe' ),
						array(
							$record->id,
							$record->title,
							wp_strip_all_tags( $record->message ),
							$record->types,
							$record->level,
							$record->source,
							$record->get_date( 'full' ),
							$record->campaign_id,
							$record->donation_id,
							$record->donor_id,
							$record->user_id,
							$record->object_id,
							$record->object_type,
						)
					),
					',',
					'"',
					'\\'
				);
			}

			fclose( $output );
			exit;
		}

		/**
		 * Neutralize CSV-formula-injection payloads before writing a cell.
		 *
		 * Donor-supplied strings (e.g. payment_method from the unauthenticated
		 * PayPal Commerce AJAX handlers) flow into log records and surface here
		 * unchanged. Excel/Numbers/Sheets interpret cells beginning with `=`,
		 * `+`, `-`, `@`, tab, or carriage return as formulas — a value like
		 * `=HYPERLINK("…", "Click me")` renders as a clickable phishing link
		 * in the opened spreadsheet, and `=WEBSERVICE(…)` can exfiltrate
		 * adjacent cells without user interaction in older Excel builds.
		 *
		 * The standard mitigation is to prefix any such cell with a single
		 * quote, which spreadsheet apps consume as a text marker and strip
		 * from the rendered value. Non-string values are returned untouched
		 * so numeric IDs aren't quoted into strings unnecessarily.
		 *
		 * @since 1.8.11
		 *
		 * @param  mixed $value Cell value.
		 * @return mixed
		 */
		protected function csv_safe( $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				return $value;
			}

			if ( false !== strpbrk( $value[0], "=+-@\t\r" ) ) {
				return "'" . $value;
			}

			return $value;
		}
	}

endif;
