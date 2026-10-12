<?php
/**
 * Static addon catalogue for sites with no licence key.
 *
 * A licence-free site cannot act on the /edd-api/versions-v3/ response - `install` is a
 * licence-keyed download URL - so it no longer asks for one. This serves the same record
 * shape from a static file instead: no licence key and no home_url() on the wire, a weekly
 * cache, and a refresh on a schedule rather than during a page render.
 *
 * Falls back to the copy bundled in the plugin, so the catalogue is present before the
 * remote file exists, offline, and when the host is unreachable.
 *
 * @package   Charitable/Classes/Charitable_Addons_Catalogue
 * @since     1.8.13.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Addons_Catalogue' ) ) :

	/**
	 * Charitable_Addons_Catalogue
	 *
	 * @since 1.8.13.1
	 */
	class Charitable_Addons_Catalogue {

		/**
		 * Single instance.
		 *
		 * @var Charitable_Addons_Catalogue|null
		 */
		private static $instance = null;

		/**
		 * Remote catalogue, alongside the other static feeds already hosted there.
		 */
		const SOURCE_URL = 'https://plugin.wpcharitable.com/wp-content/charitable-addons.json';

		/**
		 * Option holding the fetched catalogue.
		 */
		const OPTION = 'charitable_addons_catalogue';

		/**
		 * Weekly refresh event.
		 */
		const CRON_HOOK = 'charitable_addons_catalogue_refresh';

		/**
		 * Returns and/or creates the single instance.
		 *
		 * @since 1.8.13.1
		 *
		 * @return Charitable_Addons_Catalogue
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Book the weekly refresh if it is not already scheduled.
		 *
		 * @since 1.8.13.1
		 *
		 * @return void
		 */
		public function maybe_schedule() {
			if ( wp_next_scheduled( self::CRON_HOOK ) ) {
				return;
			}

			// First run is jittered so every site does not fetch at once.
			wp_schedule_event( time() + wp_rand( HOUR_IN_SECONDS, DAY_IN_SECONDS ), 'weekly', self::CRON_HOOK );
		}

		/**
		 * The catalogue: stored copy if fresh, otherwise the bundled copy.
		 *
		 * Never fetches. A page render must not block on the network, which is the whole
		 * reason the licensed path was a problem; the cron handler does the fetching.
		 *
		 * @since 1.8.13.1
		 *
		 * @return array
		 */
		public function get() {
			$stored = get_option( self::OPTION );

			if ( is_array( $stored ) && ! empty( $stored['addons'] ) ) {
				return $stored['addons'];
			}

			return $this->get_bundled();
		}

		/**
		 * The copy shipped inside the plugin.
		 *
		 * @since 1.8.13.1
		 *
		 * @return array
		 */
		public function get_bundled() {
			$path = charitable()->get_path( 'includes', true ) . 'admin/addons-directory/data/charitable-addons.json';

			if ( ! file_exists( $path ) ) {
				return array();
			}

			$json = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local, bundled file.

			return $this->validate( json_decode( $json, true ) );
		}

		/**
		 * Fetch the remote catalogue and store it. Called by cron.
		 *
		 * @since 1.8.13.1
		 *
		 * @return bool Whether a usable catalogue was stored.
		 */
		public function fetch_and_store() {
			$response = wp_remote_get(
				apply_filters( 'charitable_addons_catalogue_source_url', self::SOURCE_URL ),
				array(
					'timeout'    => 10,
					// Admin-only helper, and this runs in cron. Core's default already carries the
					// same WordPress version and site URL, so falling back loses only the edition tag.
					'user-agent' => function_exists( 'charitable_get_default_user_agent' ) ? charitable_get_default_user_agent() : null,
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			$addons = $this->validate( json_decode( wp_remote_retrieve_body( $response ), true ) );

			// Keep the previous copy rather than replacing it with nothing.
			if ( empty( $addons ) ) {
				return false;
			}

			update_option(
				self::OPTION,
				array(
					'addons'  => $addons,
					'fetched' => time(),
				),
				false
			);

			return true;
		}

		/**
		 * Keep only well-formed records, and never trust a download URL from this source.
		 *
		 * The static catalogue is published with install/package/download_link blanked. If a
		 * value ever appears in one, something upstream regenerated the file wrongly, so they
		 * are blanked again here rather than handed to the installer.
		 *
		 * @since 1.8.13.1
		 *
		 * @param  mixed $addons Decoded catalogue.
		 * @return array
		 */
		private function validate( $addons ) {
			if ( ! is_array( $addons ) ) {
				return array();
			}

			$clean = array();

			foreach ( $addons as $addon ) {
				if ( ! is_array( $addon ) || empty( $addon['slug'] ) || ! is_string( $addon['slug'] ) ) {
					continue;
				}

				$addon['install']       = '';
				$addon['package']       = '';
				$addon['download_link'] = '';

				// Consumers read these without guarding, so guarantee they exist.
				foreach ( array( 'name', 'path', 'icon', 'homepage', 'new_version' ) as $key ) {
					$addon[ $key ] = isset( $addon[ $key ] ) && is_scalar( $addon[ $key ] ) ? (string) $addon[ $key ] : '';
				}

				foreach ( array( 'license', 'featured' ) as $key ) {
					$addon[ $key ] = isset( $addon[ $key ] ) && is_array( $addon[ $key ] ) ? array_values( $addon[ $key ] ) : array();
				}

				$addon['sections'] = isset( $addon['sections'] ) && is_array( $addon['sections'] ) ? $addon['sections'] : array();

				$clean[ $addon['slug'] ] = $addon;
			}

			return $clean;
		}
	}

endif;
