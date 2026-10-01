<?php
/**
 * Charitable Uninstall class.
 *
 * The responsibility of this class is to manage the events that need to happen
 * when the plugin is deactivated.
 *
 * @package   Charitable/Charitable_Uninstall
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.42
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Uninstall' ) ) :

	/**
	 * Charitable_Uninstall
	 *
	 * @since 1.0.0
	 */
	class Charitable_Uninstall {

		/**
		 * Uninstall the plugin.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( charitable()->is_deactivation() && charitable_get_option( 'delete_data_on_uninstall' ) ) {

				$this->remove_caps();
				$this->remove_post_data();
				$this->remove_tables();
				$this->remove_settings();
				$this->remove_abilities_data();

				do_action( 'charitable_uninstall' );
			}
		}

		/**
		 * Remove plugin-specific roles.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function remove_caps() {
			$roles = new Charitable_Roles();
			$roles->remove_caps();
		}

		/**
		 * Remove post objects created by Charitable.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb The WordPress database object.
		 * @return void
		 */
		private function remove_post_data() {
			global $wpdb;

			$posts = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type IN ( 'donation', 'campaign' );" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		/**
		 * Remove the custom tables added by Charitable.
		 *
		 * @since  1.0.0
		 * @version 1.8.1
		 *
		 * @global WPDB $wpdb The WordPress database object.
		 * @return void
		 */
		private function remove_tables() {
			global $wpdb;

			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_campaign_donations' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_donors' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_donormeta' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_benefactors' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_donation_activities' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_campaign_activities' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

			delete_option( $wpdb->prefix . 'charitable_campaign_donations_db_version' );
			delete_option( $wpdb->prefix . 'charitable_donors_db_version' );
			delete_option( $wpdb->prefix . 'charitable_donormeta_db_version' );
			delete_option( $wpdb->prefix . 'charitable_benefactors_db_version' );
			delete_option( $wpdb->prefix . 'charitable_donation_activities_db_version' );
			delete_option( $wpdb->prefix . 'charitable_campaign_activities_db_version' );
		}

		/**
		 * Remove any other options added by Charitable.
		 *
		 * @since  1.6.42
		 *
		 * @return void
		 */
		private function remove_settings() {
			delete_option( 'charitable_settings' );
			delete_option( 'charitable_version' );
			delete_option( 'charitable_upgrade_log' );
			delete_option( 'charitable_skipped_donations_with_empty_donor_id' );
			delete_option( 'charitable_analysis_token' );

			delete_transient( 'charitable_notices' );
			delete_transient( 'charitable_user_dashboard_objects' );
			delete_transient( 'charitable_custom_styles' );
			delete_transient( 'charitable_analysis_cache' );

			/* Stop Charitable from re-adding the notices transient. */
			if ( function_exists( 'charitable_get_admin_notices' ) ) {
				remove_action( 'shutdown', array( charitable_get_admin_notices(), 'shutdown' ) );
			}

			/*
			 * The `ai_mcp_write_enabled` setting (Charitable_Tools_Ai_Mcp::WRITE_SETTING_KEY)
			 * needs NO separate delete_option() call. It is not its own option -
			 * it is a key nested inside the `charitable_settings` array, written by
			 * ajax_toggle_write() as $settings['ai_mcp_write_enabled'], and the
			 * blanket delete_option( 'charitable_settings' ) above already removes
			 * it. Any future AI setting nested the same way needs no change here
			 * either. (1.8.13 briefly also stored `ai_mcp_write_scopes` in that
			 * array; it was removed before release, and the blanket delete covered
			 * it while it existed.)
			 *
			 * @since 1.8.13
			 */
		}

		/**
		 * Remove data added by the Abilities API integration (1.8.13).
		 *
		 * Scoped to what THIS feature added, not a blanket log wipe.
		 *
		 * `charitable_abilities_usage` (Charitable_Abilities_Usage::OPTION) is a
		 * dedicated top-level option, never nested inside `charitable_settings`,
		 * so remove_settings() above cannot reach it - it needs its own line.
		 *
		 * Log rows are pruned by TYPE rather than the whole table being
		 * dropped: `charitable_logs` holds every log type Charitable writes,
		 * most of them unrelated to this feature, and remove_tables() above
		 * never drops that table at all (logs are not otherwise touched on
		 * uninstall). Guarded on the table existing, since a site that never
		 * triggered log_create_table() (Charitable_Log_DB::create_table()) has
		 * no table for a bare DELETE to run against.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @return void
		 */
		private function remove_abilities_data() {
			global $wpdb;

			delete_option( 'charitable_abilities_usage' );

			$table = $wpdb->prefix . 'charitable_logs';

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				// $table is $wpdb->prefix . 'charitable_logs' and carries no user input; MySQL cannot bind a table identifier as a prepared parameter, so interpolation is the only option.
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE types = %s", 'abilities' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			}
		}
	}

endif;
