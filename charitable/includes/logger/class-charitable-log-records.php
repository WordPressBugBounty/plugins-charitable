<?php
/**
 * Charitable Log Records collection class.
 *
 * @package   Charitable/Classes/Charitable_Log_Records
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Log_Records' ) ) :

	/**
	 * Charitable_Log_Records
	 *
	 * @since 1.8.11
	 */
	class Charitable_Log_Records implements Countable, Iterator {

		/**
		 * Array of Charitable_Log_Record objects.
		 *
		 * @since 1.8.11
		 *
		 * @var Charitable_Log_Record[]
		 */
		private $records = array();

		/**
		 * Iterator position.
		 *
		 * @since 1.8.11
		 *
		 * @var int
		 */
		private $position = 0;

		/**
		 * Return the count of records.
		 *
		 * @since 1.8.11
		 *
		 * @return int
		 */
		#[\ReturnTypeWillChange]
		public function count() {
			return count( $this->records );
		}

		/**
		 * Add a record to the collection.
		 *
		 * @since 1.8.11
		 *
		 * @param Charitable_Log_Record $record Record to add.
		 */
		public function push( Charitable_Log_Record $record ) {
			$this->records[] = $record;
		}

		/**
		 * Clear all records.
		 *
		 * @since 1.8.11
		 */
		public function clear() {
			$this->records  = array();
			$this->position = 0;
		}

		/**
		 * Return current record.
		 *
		 * @since 1.8.11
		 *
		 * @return Charitable_Log_Record
		 */
		#[\ReturnTypeWillChange]
		public function current() {
			return $this->records[ $this->position ];
		}

		/**
		 * Return current key.
		 *
		 * @since 1.8.11
		 *
		 * @return int
		 */
		#[\ReturnTypeWillChange]
		public function key() {
			return $this->position;
		}

		/**
		 * Advance to next position.
		 *
		 * @since 1.8.11
		 */
		#[\ReturnTypeWillChange]
		public function next() {
			++$this->position;
		}

		/**
		 * Rewind to beginning.
		 *
		 * @since 1.8.11
		 */
		#[\ReturnTypeWillChange]
		public function rewind() {
			$this->position = 0;
		}

		/**
		 * Check if current position is valid.
		 *
		 * @since 1.8.11
		 *
		 * @return bool
		 */
		#[\ReturnTypeWillChange]
		public function valid() {
			return isset( $this->records[ $this->position ] );
		}
	}

endif;
