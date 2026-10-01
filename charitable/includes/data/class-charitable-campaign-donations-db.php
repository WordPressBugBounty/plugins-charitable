<?php
/**
 * Charitable Campaign Donations DB class.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Donations_DB
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.57
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Donations_DB' ) ) :

	/**
	 * Charitable_Campaign_Donations_DB
	 *
	 * @since 1.0.0
	 */
	class Charitable_Campaign_Donations_DB extends Charitable_DB {

		/**
		 * The version of our database table
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $version = '1.5.4';

		/**
		 * The name of the primary column
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $primary_key = 'campaign_donation_id';

		/**
		 * Stores whether the site is using commas for decimals in amounts.
		 *
		 * @since 1.3.0
		 *
		 * @var   boolean
		 */
		private $comma_decimal;

		/**
		 * Set up the database table name.
		 *
		 * @since 1.0.0
		 *
		 * @global WPDB $wpdb
		 */
		public function __construct() {
			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_campaign_donations';
		}

		/**
		 * Create the table.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
                    campaign_donation_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    donation_id bigint(20) unsigned NOT NULL,
                    donor_id bigint(20) unsigned NOT NULL,
                    campaign_id bigint(20) unsigned NOT NULL,
                    campaign_name text NOT NULL,
                    amount decimal(13, 4) NOT NULL,
                    PRIMARY KEY  (campaign_donation_id),
                    KEY donation (donation_id),
                    KEY campaign (campaign_id),
                    KEY donor (donor_id)
                    ) $charset_collate;";

			$this->_create_table( $sql );
		}

		/**
		 * Whitelist of columns.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'campaign_donation_id' => '%d',
				'donation_id'          => '%d',
				'donor_id'             => '%d',
				'campaign_id'          => '%d',
				'campaign_name'        => '%s',
				'amount'               => '%f',
			);
		}

		/**
		 * Default column values.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_column_defaults() {
			return array();
		}

		/**
		 * Add a new campaign donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $data Data we are inserting.
		 * @param  string $type Type of record we are inserting.
		 * @return int The ID of the inserted campaign donation
		 */
		public function insert( $data, $type = 'campaign_donation' ) {
			if ( ! isset( $data['campaign_name'] ) ) {
				$data['campaign_name'] = get_the_title( $data['campaign_id'] );
			}

			Charitable_Campaign::flush_donations_cache( $data['campaign_id'] );

			return parent::insert( $data, $type );
		}

		/**
		 * Update campaign donation record.
		 *
		 * @since  1.0.0
		 *
		 * @param  int    $row_id The primary key ID for the row we are retrieving.
		 * @param  array  $data   The updated date.
		 * @param  string $where  Column used in where argument.
		 * @return boolean
		 */
		public function update( $row_id, $data = array(), $where = '' ) {
			$this->flush_campaign_caches( $row_id, $where );

			return parent::update( $row_id, $data, $where );
		}

		/**
		 * Delete a row identified by the primary key.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $row_id The primary key ID.
		 * @return boolean
		 */
		public function delete( $row_id = 0 ) {
			$this->flush_campaign_caches( $row_id );

			return parent::delete( $row_id );
		}

		/**
		 * Delete all campaign donation records for a given donation.
		 *
		 * @since   1.2.0
		 * @version 1.8.8.1
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean
		 */
		public static function delete_donation_records( $donation_id ) {
			// Only act on Charitable donation post type.
			$post_type = get_post_type( (int) $donation_id );
			if ( empty( $post_type ) || ( defined( 'Charitable::DONATION_POST_TYPE' ) ? $post_type !== Charitable::DONATION_POST_TYPE : $post_type !== 'donation' ) ) { // Fallback to 'donation' slug if constant is unavailable.
				return false;
			}

			$table = charitable_get_table( 'campaign_donations' );
			if ( empty( $table ) || ! is_object( $table ) || ! method_exists( $table, 'get_campaigns_for_donation' ) ) {
				return false;
			}

			foreach ( $table->get_campaigns_for_donation( $donation_id ) as $campaign_id ) {
				Charitable_Campaign::flush_donations_cache( $campaign_id );
			}

			if ( ! method_exists( $table, 'delete_by' ) ) {
				return false;
			}

			return $table->delete_by( 'donation_id', $donation_id );
		}

		/**
		 * Get the total amount donated, ever.
		 *
		 * @since  1.0.0
		 *
		 * @global $wpdb WPDB
		 * @param  string[] $statuses List of statuses.
		 * @return float
		 */
		public function get_total( $statuses = array() ) {
			global $wpdb;

			if ( empty( $statuses ) ) {
				$statuses = charitable_get_approval_statuses();
			}

			list( $status_clause, $parameters ) = $this->get_donation_status_clause( $statuses );

			if ( ! empty( $status_clause ) ) {
				$where_sql = 'WHERE ' . $status_clause;
			}

			$sql = "SELECT COALESCE( SUM(cd.amount), 0 )
                    FROM $this->table_name cd
                    INNER JOIN $wpdb->posts p
                    ON p.ID = cd.donation_id
                    $where_sql";

			$total = $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( $this->is_comma_decimal() ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Return an object containing all campaign donations associated with a particular
		 * campaign ID or a particular donation ID.
		 *
		 * @since  1.4.0
		 *
		 * @global WPDB $wpdb
		 * @param  string    $field The field we are retrieving donations by. Either 'campaign' or 'donation'.
		 * @param  int|int[] $donation_id A single donation ID or an array of IDs.
		 * @return object
		 */
		public function get_campaign_donations_by( $field, $donation_id ) {
			global $wpdb;

			$column = $this->get_sanitized_column( $field );

			list( $in, $parameters ) = $this->get_in_clause_params( $donation_id );

			$sql = "SELECT *
                    FROM $this->table_name
                    WHERE $column IN ( $in );";

			$records = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ), OBJECT_K ); // phpcs:ignore

			if ( $this->is_comma_decimal() ) {
				$records = array_map( array( $this, 'sanitize_amounts' ), $records );
			}

			return $records;
		}

		/**
		 * Return a list of all distinct IDs based on a particular field.
		 *
		 * @since  1.4.0
		 *
		 * @global WPDB $wpdb
		 * @param  string    $field       The distinct field we are retrieving.
		 * @param  int|int[] $id          An ID or an array of IDs.
		 * @param  string    $where_field Column used for the where argument.
		 * @return object
		 */
		public function get_distinct_ids( $field, $id, $where_field = 'campaign_id' ) {
			global $wpdb;

			$select_column = $this->get_sanitized_column( $field );
			$where_column  = $this->get_sanitized_column( $where_field );

			list( $in, $parameters ) = $this->get_in_clause_params( $id );

			$sql = "SELECT DISTINCT $select_column
                    FROM $this->table_name
                    WHERE $where_column IN ( $in );";

			return $wpdb->get_col( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		/**
		 * Get an object of all campaign donations associated with one or more donations.
		 *
		 * @uses  Charitable_Campaign_Donations_DB::get_campaign_donations_by()
		 *
		 * @since  1.0.0
		 *
		 * @param  int|int[] $donation_id A single donation ID or an array of IDs.
		 * @return object
		 */
		public function get_donation_records( $donation_id ) {
			return $this->get_campaign_donations_by( 'donation_id', $donation_id );
		}

		/**
		 * Get the amount donated in a single donation.
		 *
		 * @since  1.3.0
		 *
		 * @global WPDB $wpdb
		 * @param  int|int[] $donation_id A single donation ID or an array of IDs.
		 * @param  int|int[] $campaign    Optional. If set, this will only return the total donated to that campaign, or array of campaigns.
		 * @return decimal
		 */
		public function get_donation_amount( $donation_id, $campaign = '' ) {
			global $wpdb;

			list( $in, $parameters ) = $this->get_in_clause_params( $donation_id );

			$where_clause = "donation_id IN ( $in )";

			if ( ! empty( $campaign ) ) {

				list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaign );

				$where_clause .= " AND campaign_id IN ( $campaigns_in )";
				$parameters    = array_merge( $parameters, $campaigns_parameters );

			}

			$sql = "SELECT SUM(amount)
                    FROM $this->table_name
                    WHERE $where_clause;";

			$total = $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( $this->is_comma_decimal() ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Get the total amount donated in a single donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donation_id Donation ID.
		 * @return decimal
		 */
		public function get_donation_total_amount( $donation_id ) {
			return $this->get_donation_amount( $donation_id );
		}

		/**
		 * Return the total donated on each of several donations, grouped by
		 * donation id, in a single query.
		 *
		 * The donation-side counterpart to get_donated_amounts_by_campaign().
		 * Added because no batched per-donation accessor existed: listing a page
		 * of donations and calling get_donation_total_amount() per row issues one
		 * query per donation, which is the same N+1 that had to be fixed out of
		 * the campaign list.
		 *
		 * No status clause: the donation ids are already selected by status by
		 * the caller, and a per-donation total must not be filtered by the
		 * donation's own status — the donation IS the status.
		 *
		 * The donor id comes back alongside the total, and that is the point of
		 * it rather than a convenience. Charitable_Donation::get_donor_id()
		 * resolves it through get_campaign_donations(), which issues its own
		 * `SELECT * FROM campaign_donations WHERE donation_id IN (N)` per
		 * donation — so a page of 20 donations paid 20 extra queries to learn
		 * something this one grouped query already had in hand. Measured on the
		 * development site: dropping that call took charitable/list-donations
		 * from 76 queries to 36.
		 *
		 * MIN( cd.donor_id ) rather than any aggregate cleverness: every row of
		 * a single donation carries the same donor_id, because the donor is a
		 * property of the donation and not of the split across campaigns.
		 *
		 * Ported from Charitable Pro, with one change: Pro's version reads
		 * COALESCE( cd.base_amount, cd.amount ) because Pro's multi-currency
		 * feature adds a base_amount column to this table. Lite has no
		 * base_amount column and no multi-currency feature, so this sums
		 * cd.amount directly — the same substitution every other aggregate
		 * method in this Lite file already carries (see
		 * get_donations_summary_by_day_range() below for the same rationale).
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  int[] $donation_ids Donation IDs.
		 * @return array Keyed by donation_id (int), each value an array with
		 *               'amount' (float) and 'donor_id' (int). A donation with no
		 *               campaign_donations rows is simply absent — callers should
		 *               treat a missing key as zero.
		 */
		public function get_donated_amounts_by_donation( $donation_ids ) {
			global $wpdb;

			list( $donations_in, $parameters ) = $this->get_in_clause_params( $donation_ids );

			if ( empty( $donations_in ) || empty( $parameters ) ) {
				return array();
			}

			$sql = "SELECT cd.donation_id,
						COALESCE( SUM( cd.amount ), 0 ) as total,
						MIN( cd.donor_id ) as donor_id
					FROM $this->table_name cd
					WHERE cd.donation_id IN ( $donations_in )
					GROUP BY cd.donation_id";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			$totals = array();

			foreach ( $results as $row ) {
				$totals[ (int) $row->donation_id ] = array(
					'amount'   => (float) $row->total,
					'donor_id' => (int) $row->donor_id,
				);
			}

			return $totals;
		}

		/**
		 * Return the per-campaign rows for MANY donations in one query.
		 *
		 * The batched counterpart to get_campaigns_for_donation() below. A
		 * listing that wants to show what each gift was split across needs this
		 * shape or it pays a query per row — the same reason
		 * get_donated_amounts_by_donation() above exists, and that method is the
		 * model for this one.
		 *
		 * Ordered by id so a split donation's campaigns come back in the order
		 * they were recorded, which is the order the donation form collected
		 * them.
		 *
		 * Ported from Charitable Pro, with one change: Pro's version reads
		 * COALESCE( cd.base_amount, cd.amount ) because Pro's multi-currency
		 * feature adds a base_amount column to this table. Lite has no
		 * base_amount column and no multi-currency feature, so this reads
		 * cd.amount directly — the same substitution get_donated_amounts_by_donation()
		 * above carries.
		 *
		 * @since 1.8.13
		 *
		 * @global wpdb $wpdb
		 * @param  int[] $donation_ids The donation IDs.
		 * @return array donation_id => array of { campaign_id, campaign_name, amount }
		 */
		public function get_campaign_rows_by_donation( $donation_ids ) {
			global $wpdb;

			list( $donations_in, $parameters ) = $this->get_in_clause_params( $donation_ids );

			if ( empty( $donations_in ) || empty( $parameters ) ) {
				return array();
			}

			$sql = "SELECT cd.donation_id,
						cd.campaign_id,
						cd.campaign_name,
						cd.amount
					FROM $this->table_name cd
					WHERE cd.donation_id IN ( $donations_in )
					ORDER BY cd.donation_id ASC, cd.campaign_donation_id ASC";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			$rows = array();

			foreach ( (array) $results as $row ) {
				$rows[ (int) $row->donation_id ][] = array(
					'campaign_id'   => (int) $row->campaign_id,
					'campaign_name' => (string) $row->campaign_name,
					'amount'        => (float) $row->amount,
				);
			}

			return $rows;
		}

		/**
		 * Return an array of campaigns donated to in a single donation.
		 *
		 * @since  1.2.0
		 *
		 * @global wpdb $wpdb WPDB.
		 * @param  int $donation_id Donation ID.
		 * @return object
		 */
		public function get_campaigns_for_donation( $donation_id ) {
			global $wpdb;

			$sql = "SELECT DISTINCT campaign_id
                    FROM $this->table_name
                    WHERE donation_id = %d;";

			return $wpdb->get_col( $wpdb->prepare( $sql, intval( $donation_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		/**
		 * Get an object of all donations on a campaign.
		 *
		 * @uses   Charitable_Campaign_Donations_DB::get_campaign_donations_by()
		 *
		 * @since  1.0.0
		 *
		 * @param  int|int[] $campaign_id Campaign ID.
		 * @return object
		 */
		public function get_donations_on_campaign( $campaign_id ) {
			return $this->get_campaign_donations_by( 'campaign_id', $campaign_id );
		}

		/**
		 * Get total amount donated to a campaign.
		 *
		 * @since  1.0.0
		 *
		 * @global wpdb $wpdb WPDB.
		 * @param  int|int[] $campaigns   A campaign ID. Optionally, you can pass an array of campaign IDs to get the total of all put together.
		 * @param  boolean   $include_all Whether donations with non-approved statuses should be included.
		 * @param  boolean   $sanitize    Whether to sanitize the amount if we're using commas for decimals.
		 * @return string
		 */
		public function get_campaign_donated_amount( $campaigns, $include_all = false, $sanitize = true ) {
			return $this->get_amount_report(
				array(
					'status'    => $include_all ? array() : charitable_get_approval_statuses(),
					'campaigns' => $campaigns,
				),
				$sanitize
			);
		}

		/**
		 * Return the total amount donated to each of several campaigns, grouped
		 * by campaign id, in a single query.
		 *
		 * Exists so a caller iterating N campaigns (e.g. the charitable/list-campaigns
		 * ability) can resolve every row's donated amount in one query instead of
		 * N calls to get_campaign_donated_amount() / Charitable_Campaign::get_donated_amount() —
		 * one query per campaign multiplied by a page size is the exact N+1 shape
		 * that made the long-range Reports Overview timeout ~18,000 queries before
		 * it was batched the same way.
		 *
		 * Ported from Charitable Pro, with one change: Pro's version reads
		 * COALESCE( cd.base_amount, cd.amount ) because Pro's multi-currency
		 * feature adds a base_amount column to this table. Lite has no
		 * base_amount column and no multi-currency feature, so this reads
		 * cd.amount directly — the same column every other aggregate method in
		 * this Lite file already uses (see get_amount_by_campaign_report()).
		 *
		 * Deliberately mirrors get_campaign_donated_amount( $campaigns, false, false )'s
		 * raw, un-locale-converted output (no sanitize_database_amount() pass) so a
		 * batched row and a get_campaign_donated_amount() call for the same campaign
		 * return numerically identical values — callers that need the site's
		 * comma/dot display convention should convert the same way that call site
		 * already does.
		 *
		 * This is a raw aggregate accessor: it does NOT apply the
		 * `charitable_campaign_donated_amount` filter that
		 * Charitable_Campaign::get_donated_amount() applies on every call. A
		 * caller that wants a figure consistent with the rest of the product —
		 * not just a faster one — must apply that filter itself, per
		 * campaign_id, with the same arguments get_donated_amount() uses:
		 * `apply_filters( 'charitable_campaign_donated_amount', $amount,
		 * $campaign, $sanitize )`. See
		 * Charitable_Campaign_Abilities::campaign_summary() for a worked example.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  int[]   $campaign_ids Campaign IDs.
		 * @param  boolean $include_all  Whether to include all donations (true), or only approved (false).
		 * @return array Keyed by campaign_id (int), each value a float total. A
		 *               campaign with no matching donations is simply absent from
		 *               the array — callers should treat a missing key as 0.
		 */
		public function get_donated_amounts_by_campaign( $campaign_ids, $include_all = false ) {
			global $wpdb;

			list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaign_ids );

			if ( empty( $campaigns_in ) || empty( $campaigns_parameters ) ) {
				return array();
			}

			$statuses = $include_all ? array() : charitable_get_approval_statuses();

			list( $status_clause, $status_parameters ) = $this->get_donation_status_clause( $statuses );

			$sql_where_clauses = array( "cd.campaign_id IN ( $campaigns_in )" );

			if ( ! empty( $status_clause ) ) {
				$sql_where_clauses[] = $status_clause;
			}

			$sql_where  = 'WHERE ' . implode( ' AND ', $sql_where_clauses );
			$parameters = array_merge( $campaigns_parameters, $status_parameters );

			$sql = "SELECT cd.campaign_id, COALESCE( SUM( cd.amount ), 0 ) as total
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
					$sql_where
					GROUP BY cd.campaign_id";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			$totals = array();

			foreach ( $results as $row ) {
				$totals[ (int) $row->campaign_id ] = (float) $row->total;
			}

			return $totals;
		}

		/**
		 * Return the number of distinct donors for each of several campaigns,
		 * grouped by campaign id, in a single query.
		 *
		 * Same rationale as get_donated_amounts_by_campaign(): count_campaign_donors()
		 * has no caching layer at all (unlike the donated-amount path, which is at
		 * least transient-cached), so calling it once per row in a list is the
		 * more expensive half of that ability's N+1.
		 *
		 * Ported from Charitable Pro unchanged: this query never touches
		 * base_amount, so Lite's lack of that column does not affect it.
		 *
		 * This is a raw aggregate accessor: it does NOT apply the
		 * `charitable_campaign_donor_count` filter that
		 * Charitable_Campaign::get_donor_count() applies on every call. A caller
		 * that wants a figure consistent with the rest of the product must apply
		 * that filter itself, per campaign_id: `apply_filters(
		 * 'charitable_campaign_donor_count', $count, $campaign )`. See
		 * Charitable_Campaign_Abilities::campaign_summary() for a worked example.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  int[]   $campaign_ids Campaign IDs.
		 * @param  boolean $include_all  Whether to include all donations (true), or only approved (false).
		 * @return array Keyed by campaign_id (int), each value an int count. A
		 *               campaign with no matching donors is simply absent from the
		 *               array — callers should treat a missing key as 0.
		 */
		public function count_campaign_donors_by_campaign( $campaign_ids, $include_all = false ) {
			global $wpdb;

			list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaign_ids );

			if ( empty( $campaigns_in ) || empty( $campaigns_parameters ) ) {
				return array();
			}

			$statuses = $include_all ? array() : charitable_get_approval_statuses();

			list( $status_clause, $status_parameters ) = $this->get_donation_status_clause( $statuses );

			$sql_where_clauses = array( "cd.campaign_id IN ( $campaigns_in )" );

			if ( ! empty( $status_clause ) ) {
				$sql_where_clauses[] = $status_clause;
			}

			$sql_where  = 'WHERE ' . implode( ' AND ', $sql_where_clauses );
			$parameters = array_merge( $campaigns_parameters, $status_parameters );

			$sql = "SELECT cd.campaign_id, COUNT( DISTINCT cd.donor_id ) as donor_count
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
					$sql_where
					GROUP BY cd.campaign_id";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			$counts = array();

			foreach ( $results as $row ) {
				$counts[ (int) $row->campaign_id ] = (int) $row->donor_count;
			}

			return $counts;
		}

		/**
		 * Get an array of all donation ids for a campaign.
		 *
		 * @uses   Charitable_Campaign_Donations_DB::get_distinct_ids()
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id Campaign ID.
		 * @return object
		 */
		public function get_donation_ids_for_campaign( $campaign_id ) {
			return $this->get_distinct_ids( 'donation_id', $campaign_id, 'campaign_id' );
		}

		/**
		 * The donor IDs of all who have donated to the given campaign.
		 *
		 * @uses   Charitable_Campaign_Donations_DB::get_distinct_ids()
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id The campaign ID to get donors for.
		 * @return object
		 */
		public function get_campaign_donors( $campaign_id ) {
			return $this->get_distinct_ids( 'donor_id', $campaign_id, 'campaign_id' );
		}

		/**
		 * Return the number of users who have donated to the given campaign.
		 *
		 * @since  1.0.0
		 *
		 * @global wpdb $wpdb WPDB.
		 * @param  int|int[] $campaign    The campaign ID, or list of campaign IDs.
		 * @param  boolean   $include_all Whether to include all donations (true), or only include approved donations (false).
		 * @return int
		 */
		public function count_campaign_donors( $campaign, $include_all = false ) {
			global $wpdb;

			$statuses = $include_all ? array() : charitable_get_approval_statuses();

			list( $status_clause, $status_parameters )   = $this->get_donation_status_clause( $statuses );
			list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaign );

			$sql_where_clauses[] = "cd.campaign_id IN ( $campaigns_in )";
			$sql_where_clauses[] = $status_clause;
			$sql_where           = 'WHERE ' . implode( ' AND ', $sql_where_clauses );

			$parameters = array_merge( $campaigns_parameters, $status_parameters );

			$sql = "SELECT COUNT( DISTINCT cd.donor_id )
                    FROM $this->table_name cd
                    INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
                    $sql_where;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		/**
		 * Count distinct donations (transactions) for a campaign.
		 *
		 * Ported from Charitable Pro unchanged: a pure COUNT query, it never
		 * touches base_amount, so Lite's lack of that column does not affect it.
		 * Backs Charitable_Campaign::get_donation_count(), which the
		 * charitable/get-campaign-performance ability calls.
		 *
		 * @since  1.8.13
		 *
		 * @global wpdb $wpdb
		 * @param  int|int[] $campaign    The campaign ID, or list of campaign IDs.
		 * @param  boolean   $include_all Whether to include all donations (true), or only approved (false).
		 * @return int
		 */
		public function count_campaign_donations( $campaign, $include_all = false ) {
			global $wpdb;

			$statuses = $include_all ? array() : charitable_get_approval_statuses();

			list( $status_clause, $status_parameters )   = $this->get_donation_status_clause( $statuses );
			list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaign );

			$sql_where_clauses = array();

			if ( ! empty( $campaigns_in ) && ! empty( $campaigns_parameters ) ) {
				$sql_where_clauses[] = "cd.campaign_id IN ( $campaigns_in )";
			}

			if ( ! empty( $status_clause ) ) {
				$sql_where_clauses[] = $status_clause;
			}

			if ( empty( $sql_where_clauses ) ) {
				return 0;
			}

			$sql_where  = 'WHERE ' . implode( ' AND ', $sql_where_clauses );
			$parameters = array_merge( $campaigns_parameters, $status_parameters );

			$sql = "SELECT COUNT( DISTINCT cd.donation_id )
                    FROM $this->table_name cd
                    INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
                    $sql_where;";

			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		/**
		 * Return all donations made by a donor.
		 *
		 * @since  1.0.0
		 *
		 * @global wpdb $wpdb WPDB.
		 * @param  int     $donor_id           The donor ID.
		 * @param  boolean $distinct_donations Whether to get distinct donations.
		 * @return object[]
		 */
		public function get_donations_by_donor( $donor_id, $distinct_donations = false ) {
			global $wpdb;

			if ( $distinct_donations ) {
				$select_fields = 'DISTINCT( cd.donation_id ), cd.campaign_id, cd.campaign_name, cd.amount';
			} else {
				$select_fields = 'cd.campaign_donation_id, cd.donation_id, cd.campaign_id, cd.campaign_name, cd.amount';
			}

			$sql = "SELECT $select_fields
                    FROM $this->table_name cd
                    WHERE cd.donor_id = %d;";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $donor_id ), OBJECT_K ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( $this->is_comma_decimal() ) {
				$results = array_map( array( $this, 'sanitize_amounts' ), $results );
			}

			return $results;
		}

		/**
		 * Return total amount donated by a donor.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb
		 * @param  int $donor_id The donor ID.
		 * @return int
		 */
		public function get_total_donated_by_donor( $donor_id ) {
			global $wpdb;

			$sql = "SELECT COALESCE( SUM(cd.amount), 0 )
                    FROM $this->table_name cd
                    WHERE cd.donor_id = %d;";

			$total = $wpdb->get_var( $wpdb->prepare( $sql, $donor_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( $this->is_comma_decimal() ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Count the number of donations made by the donor.
		 *
		 * @since  1.0.0
		 *
		 * @global wpdb $wpdb WPDB.
		 * @param  int     $donor_id           The donor ID.
		 * @param  boolean $distinct_donations If true, will only count unique donations.
		 * @return int
		 */
		public function count_donations_by_donor( $donor_id, $distinct_donations = false ) {
			global $wpdb;

			$count = $distinct_donations ? 'DISTINCT donation_id' : 'donation_id';

			$sql = "SELECT COUNT( $count )
                    FROM $this->table_name
                    WHERE donor_id = %d;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $donor_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		/**
		 * Count the number of campaigns that the donor has supported.
		 *
		 * @since  1.0.0
		 *
		 * @global wpdb $wpdb WPDB.
		 * @param  int $donor_id The donor to retrieve campaigns for.
		 * @return int
		 */
		public function count_campaigns_supported_by_donor( $donor_id ) {
			global $wpdb;

			$sql = "SELECT COUNT( DISTINCT campaign_id )
                    FROM $this->table_name
                    WHERE donor_id = %d;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $donor_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		}

		/**
		 * Return a set of donations, filtered by the provided arguments.
		 *
		 * @since  1.0.0
		 * @since  1.8.1.8 Added filter.
		 *
		 * @global WPDB $wpdb
		 * @param  array $args Query arguments.
		 * @return array
		 */
		public function get_donations_report( $args ) {
			global $wpdb;

			$sql_order                      = $this->get_orderby_clause( $args, 'ORDER BY p.post_date ASC' );
			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );
			$limit                          = $this->get_limit_clause( $args );

			/* This is our base SQL query */
			$sql = "SELECT cd.donation_id, cd.campaign_id, cd.campaign_name, cd.amount, d.email, d.first_name, d.last_name, p.post_date, p.post_content, p.post_status
					FROM $this->table_name cd
					INNER JOIN {$wpdb->prefix}charitable_donors d
					ON d.donor_id = cd.donor_id
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					$sql_where
					$sql_order
					$limit";

			$sql = apply_filters( 'charitable_campaign_donations_db_sql', $sql, $args, $sql_where, $sql_order, $parameters );

			if ( ! empty( $parameters ) ) {
				$sql = $wpdb->prepare( $sql, $parameters ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( $this->is_comma_decimal() ) {
				$results = array_map( array( $this, 'sanitize_amounts' ), $results );
			}

			return $results;
		}

		/**
		 * Return a total amount donated
		 *
		 * @since  1.6.57
		 *
		 * @global WPDB $wpdb
		 * @param  array   $args     Query arguments.
		 * @param  boolean $sanitize Whether to sanitize the amount if we're using commas for decimals.
		 * @return int The total amount
		 */
		public function get_amount_report( $args, $sanitize = true ) {
			global $wpdb;

			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );

			/* Get the total */
			$sql = "SELECT COALESCE( SUM(amount), 0 )
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					$sql_where";

			if ( ! empty( $parameters ) ) {
				$sql = $wpdb->prepare( $sql, $parameters ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$total = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( $this->is_comma_decimal() && $sanitize ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Return a total amount donated, grouped by campaign.
		 * Created for Charitable-Stats-Shortcode.
		 *
		 * @since  1.6.57
		 *
		 * @global WPDB $wpdb
		 *
		 * @param  array   $args     Query arguments.
		 * @param  boolean $sanitize Whether to sanitize the amount if we're using commas for decimals.
		 * @return array An array of amounts and campaign names
		 */
		public function get_amount_by_campaign_report( $args, $sanitize = true ) {
			global $wpdb;

			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );

			/* Get the total */
			$sql = "SELECT cd.campaign_name, COALESCE( SUM(amount), 0 ) as total
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					$sql_where
					GROUP BY cd.campaign_id, cd.campaign_name";

			if ( ! empty( $parameters ) ) {
				$sql = $wpdb->prepare( $sql, $parameters ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$results = $wpdb->get_results( $sql, 'ARRAY_N' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( $this->is_comma_decimal() && $sanitize ) {
				$results = array_map( array( $this, 'sanitize_grouped_amounts' ), $results );
			}

			return $results;
		}

		/**
		 * Count the distinct donations matching a report query.
		 *
		 * Counts DISTINCT donation_id, so a donation split across three
		 * campaigns counts once rather than three times — unlike
		 * count_donations_by_status(), which counts campaign_donations ROWS and
		 * cannot be date-ranged.
		 *
		 * Exists because the alternative is loading every matching donation id
		 * into PHP just to call count() on it. Filtering reuses
		 * get_report_sql_where_clause(), so campaigns, status and the date range
		 * behave exactly as they do for get_amount_report() — which means a
		 * total and a count taken from the same $args are always coherent, and
		 * average = total / count cannot disagree with itself.
		 *
		 * THE INNER JOIN IS DELIBERATE, and it is a behaviour decision worth
		 * stating. A donation POST with no campaign_donations row is not
		 * counted, because such a record carries no amount and so contributes
		 * nothing to any money figure. Counting it would report more donations
		 * than the money accounts for: on the development database, 18 donation
		 * posts (abandoned importer and gateway-test records) have no rows at
		 * all, and counting donation posts against a campaign_donations total
		 * produced an all-time average gift of 9.49 when the real average of
		 * the donations that carry money is 14.02 — an average no actual
		 * donation resembles. Taking the count from the same table as the total
		 * is what makes the reported average true.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  array $args Query arguments: campaigns, status, start_date, end_date, donor_id.
		 * @return int
		 */
		public function count_donations_report( $args = array() ) {
			global $wpdb;

			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );

			$sql = "SELECT COUNT( DISTINCT cd.donation_id )
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					$sql_where";

			if ( ! empty( $parameters ) ) {
				$sql = $wpdb->prepare( $sql, $parameters ); // phpcs:ignore
			}

			return (int) $wpdb->get_var( $sql ); // phpcs:ignore
		}

		/**
		 * Count the distinct donors matching a report query.
		 *
		 * A donor_id of 0 is excluded: a row can carry it when the donation was
		 * recorded without a resolvable donor, and counting those as one donor
		 * would understate a site's supporter count while counting them
		 * individually would overstate it. Excluding is the only honest option.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  array $args Query arguments: campaigns, status, start_date, end_date.
		 * @return int
		 */
		public function count_donors_report( $args = array() ) {
			global $wpdb;

			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );

			$sql_where = '' !== $sql_where
				? $sql_where . ' AND cd.donor_id != 0'
				: 'WHERE cd.donor_id != 0';

			$sql = "SELECT COUNT( DISTINCT cd.donor_id )
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					$sql_where";

			if ( ! empty( $parameters ) ) {
				$sql = $wpdb->prepare( $sql, $parameters ); // phpcs:ignore
			}

			return (int) $wpdb->get_var( $sql ); // phpcs:ignore
		}

		/**
		 * Count the distinct donations in a report query, grouped by status.
		 *
		 * ONE query for every status, rather than one query per status. Pass no
		 * `status` argument: get_report_sql_where_clause() then constrains to
		 * all valid donation statuses, which is exactly the set to group over.
		 *
		 * Ported from Charitable Pro unchanged: this query never touches
		 * base_amount, so Lite's lack of that column does not affect it.
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  array $args Query arguments: campaigns, start_date, end_date, donor_id.
		 * @return array Keyed by post status, each value an int count. A status with
		 *               no donations is absent — callers should treat a missing key
		 *               as 0.
		 */
		public function count_donations_by_status_report( $args = array() ) {
			global $wpdb;

			unset( $args['status'] );

			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );

			$sql = "SELECT p.post_status, COUNT( DISTINCT cd.donation_id ) as donation_count
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					$sql_where
					GROUP BY p.post_status";

			if ( ! empty( $parameters ) ) {
				$sql = $wpdb->prepare( $sql, $parameters ); // phpcs:ignore
			}

			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			$counts = array();

			foreach ( (array) $results as $row ) {
				$counts[ (string) $row->post_status ] = (int) $row->donation_count;
			}

			return $counts;
		}

		/**
		 * Return campaigns ranked by amount raised over a date range.
		 *
		 * The get_amount_by_campaign_report() accessor is the closest existing one
		 * and is correctly converted, but it selects `campaign_name` WITHOUT
		 * campaign_id and applies no ordering or limit. A caller that needs to
		 * link to the campaign, or that wants the top N, therefore cannot use
		 * it: names are not unique, and they drift when a campaign is renamed
		 * after the donation was recorded.
		 *
		 * Filtering reuses get_report_sql_where_clause(), so campaigns, status,
		 * start_date, end_date and donor_id behave exactly as they do for every
		 * other report query, including its default of all valid donation
		 * statuses when none is given.
		 *
		 * Sums cd.amount rather than Pro's COALESCE( cd.base_amount, cd.amount ):
		 * base_amount belongs to the multi-currency schema and does not exist on
		 * this table in Lite (see get_donations_summary_by_day_range() below for
		 * the same substitution and rationale).
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  array $args Query arguments: campaigns, status, start_date,
		 *                     end_date, donor_id, limit.
		 * @return object[] Rows of campaign_id, campaign_name, total, donation_count.
		 */
		public function get_top_campaigns_report( $args = array() ) {
			global $wpdb;

			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );

			$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 10;
			$limit = $limit > 0 ? $limit : 10;

			$sql = "SELECT cd.campaign_id,
						cd.campaign_name,
						COALESCE( SUM( cd.amount ), 0 ) as total,
						COUNT( DISTINCT cd.donation_id ) as donation_count
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					$sql_where
					GROUP BY cd.campaign_id, cd.campaign_name
					ORDER BY total DESC
					LIMIT %d";

			$parameters[] = $limit;

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore

			if ( $this->is_comma_decimal() ) {
				$results = array_map( array( $this, 'sanitize_amounts' ), $results );
			}

			return $results;
		}

		/**
		 * Return donors ranked by amount given over a date range.
		 *
		 * Charitable_Reports::get_top_donors_overview() is the existing
		 * equivalent, and is not usable here: it requires the whole donation set
		 * to have been loaded into PHP first via init_with_array() plus
		 * get_donations(), sorts it in PHP, and calls charitable_get_donation()
		 * once per row. This is one grouped query.
		 *
		 * Ranked on cd.amount rather than Pro's COALESCE( base_amount, amount ):
		 * base_amount belongs to the multi-currency schema and does not exist on
		 * this table in Lite (see get_donations_summary_by_day_range() below for
		 * the same substitution and rationale).
		 *
		 * @since 1.8.13
		 *
		 * @global WPDB $wpdb
		 * @param  array $args Query arguments: campaigns, status, start_date,
		 *                     end_date, limit.
		 * @return object[] Rows of donor_id, first_name, last_name, email, total,
		 *                  donation_count.
		 */
		public function get_top_donors_report( $args = array() ) {
			global $wpdb;

			list( $sql_where, $parameters ) = $this->get_report_sql_where_clause( $args );

			$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 10;
			$limit = $limit > 0 ? $limit : 10;

			/*
			 * donor_id 0 must not be grouped: a campaign_donations row can carry
			 * it when the donation was recorded without a resolvable donor, and
			 * grouping those would present the sum of several unrelated gifts as
			 * one large anonymous top donor.
			 *
			 * The INNER JOIN on the donors table below is what actually excludes
			 * them, because no donors row has donor_id 0 — the column is
			 * AUTO_INCREMENT and starts at 1. This clause is therefore SECONDARY
			 * and, on any normal install, redundant. It is kept because it states
			 * the intent at the point the grouping happens, so a future change
					 * from INNER to LEFT JOIN (to include gifts whose donor record
			 * was erased, say) does not silently reintroduce the anonymous-group
			 * bug.
			 *
			 * Honest note on coverage: this clause is NOT independently testable.
			 * Removing it leaves every test green, because the join already
			 * covers the case, and a donors row with donor_id 0 cannot be created
			 * to exercise it — MySQL treats an inserted 0 in an AUTO_INCREMENT
			 * column as a request for the next value unless NO_AUTO_VALUE_ON_ZERO
			 * is set on the session. Verified by mutation rather than assumed.
			 */
			$sql_where = '' !== $sql_where
				? $sql_where . ' AND cd.donor_id != 0'
				: 'WHERE cd.donor_id != 0';

			$sql = "SELECT cd.donor_id,
						d.first_name,
						d.last_name,
						d.email,
						COALESCE( SUM( cd.amount ), 0 ) as total,
						COUNT( DISTINCT cd.donation_id ) as donation_count
					FROM $this->table_name cd
					INNER JOIN $wpdb->posts p
					ON p.ID = cd.donation_id
					INNER JOIN {$wpdb->prefix}charitable_donors d
					ON d.donor_id = cd.donor_id
					$sql_where
					GROUP BY cd.donor_id, d.first_name, d.last_name, d.email
					ORDER BY total DESC
					LIMIT %d";

			$parameters[] = $limit;

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore

			if ( $this->is_comma_decimal() ) {
				$results = array_map( array( $this, 'sanitize_amounts' ), $results );
			}

			return $results;
		}

		/**
		 * Return an SQL limit for the report
		 *
		 * @since  1.8.1.10
		 *
		 * @param  array $args Query arguments.
		 * @return array
		 */
		public function get_limit_clause( $args ) {
			$limit = '';

			if ( isset( $args['limit'] ) ) {
				$limit = 'LIMIT ' . $args['limit'];
			}

			return $limit;
		}

		/**
		 * Return an SQL where clause for the report
		 *
		 * @since  1.6.57
		 *
		 * @param  array $args Query arguments.
		 * @return array
		 */
		public function get_report_sql_where_clause( $args ) {
			$parameters        = array();
			$sql_where         = '';
			$sql_where_clauses = array();

			/* campaign_id should become 'campaigns'. */
			if ( isset( $args['campaign_id'] ) ) {
				$args['campaigns'] = $args['campaign_id'];
			}

			if ( isset( $args['campaigns'] ) ) {
				list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $args['campaigns'] );

				$sql_where_clauses[] = "cd.campaign_id IN ( $campaigns_in )";
				$parameters          = array_merge( $parameters, $campaigns_parameters );
			}

			if ( isset( $args['status'] ) ) {
				if ( ! is_array( $args['status'] ) ) {
					/* Ensure this is an array. */
					$args['status'] = array( $args['status'] );
				}

				list( $status_sql, $status_parameters ) = $this->get_donation_status_clause( $args['status'] );
				$sql_where_clauses[]                    = $status_sql;
				$parameters                             = array_merge( $parameters, $status_parameters );

			} else {
				/* If ALL: select all valid statuses */
				$statuses            = array_keys( charitable_get_valid_donation_statuses() );
				$in                  = charitable_get_query_placeholders( count( $statuses ), '%s' );
				$sql_where_clauses[] = "p.post_status IN ( $in )";
				$parameters          = array_merge( $parameters, $statuses );
			}

			if ( ! empty( $args['start_date'] ) ) {
				$sql_where_clauses[] = 'p.post_date >= %s';
				$parameters[]        = $args['start_date'];
			}

			if ( ! empty( $args['end_date'] ) ) {
				$sql_where_clauses[] = 'p.post_date <= %s';
				$parameters[]        = $args['end_date'];
			}

			// Added in 1.8.1.8.
			if ( isset( $args['post_parent'] ) ) {
				$sql_where_clauses[] = 'p.post_parent = %d';
				$parameters[]        = $args['post_parent'];
			}

			/*
			 * `author` narrows to one donation author, which for a donation is
			 * the donor's WordPress user id
			 * (Charitable_Donation_Processor::parse_donation_data() sets
			 * post_author from `user_id`). Added in 1.8.13 for the ownership
			 * scoping on charitable/get-donation-summary: a report caller without
			 * `edit_others_donations` must see only their own donations, which is
			 * the rule wp-admin's donation list already applies
			 * (class-charitable-donation-list-table.php:783) via WP_Query's own
			 * `author` argument. The report accessors do not go through WP_Query,
			 * so the equivalent has to exist here.
			 *
			 * isset(), not ! empty(): author 0 is a real value — guest and
			 * manually recorded donations carry post_author 0 — so treating it as
			 * "no filter" would be fail-OPEN, returning every donation to a
			 * caller who asked to be scoped. No caller passes 0 today, because
			 * the only one that sets this key is gated behind `edit_donations`
			 * and so is always a logged-in user; isset() is what keeps that true
			 * if a future caller is not.
			 *
			 * @since 1.8.13
			 */
			if ( isset( $args['author'] ) ) {
				$sql_where_clauses[] = 'p.post_author = %d';
				$parameters[]        = (int) $args['author'];
			}

			if ( ! empty( $sql_where_clauses ) ) {
				$sql_where = 'WHERE ' . implode( ' AND ', $sql_where_clauses );
			}

			return array( $sql_where, $parameters );
		}

		/**
		 * Return a count and sum of donations for a given period.
		 *
		 * @since  1.2.0
		 *
		 * @global WPDB $wpdb
		 * @param  string   $period   The period to get donations for.
		 * @param  string[] $statuses List of statuses.
		 * @return array
		 */
		public function get_donations_summary_by_period( $period = '', $statuses = array() ) {
			global $wpdb;

			if ( empty( $statuses ) ) {
				$statuses = charitable_get_approval_statuses();
			}

			list( $status_clause, $parameters ) = $this->get_donation_status_clause( $statuses );

			array_unshift( $parameters, $period );

			$sql_where_clauses[] = "DATE_FORMAT( p.post_date, '%%Y-%%m-%%d' ) LIKE %s";
			$sql_where_clauses[] = $status_clause;
			$sql_where           = 'WHERE ' . implode( ' AND ', $sql_where_clauses );

			$sql = "SELECT COALESCE( SUM( cd.amount ), 0 ) as amount, COUNT( cd.donation_id ) as count
				FROM {$wpdb->prefix}charitable_campaign_donations cd
				INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
				$sql_where";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( empty( $results ) ) {
				return (object) array(
					'amount' => 0.0000,
					'count'  => 0,
				);
			}

			$result = $results[0];

			if ( $this->is_comma_decimal() ) {
				$result = $this->sanitize_amounts( $result );
			}

			return $result;
		}

		/**
		 * Return a count and sum of donations for every day in a date range, keyed by day.
		 *
		 * Callers that need a per-day series must use this instead of calling
		 * get_donations_summary_by_period() once per calendar day. That pattern issued one
		 * uncached query per day, so the query count scaled with the LENGTH of the range
		 * rather than the amount of data, and a multi-year range timed out. It also wrapped
		 * post_date in DATE_FORMAT(), which prevented any index being used, making every
		 * one of those queries a full scan of the join.
		 *
		 * Days with no donations are absent from the return value; callers should treat a
		 * missing key as zero.
		 *
		 * @since  1.8.12.2
		 * @since  1.8.13 Added $campaign_id parameter.
		 *
		 * @global WPDB $wpdb
		 * @param  string   $start_date  Start of the range, Y-m-d. Inclusive.
		 * @param  string   $end_date    End of the range, Y-m-d. Inclusive of the whole day.
		 * @param  string[] $statuses    List of statuses.
		 * @param  int      $campaign_id Optional. Restrict to donations that include this
		 *                               campaign. 0, the default, means every campaign —
		 *                               which is what existing callers want.
		 * @return array Keyed by Y-m-d, each value an object with 'amount' and 'count'.
		 */
		public function get_donations_summary_by_day_range( $start_date, $end_date, $statuses = array(), $campaign_id = 0 ) {
			global $wpdb;

			if ( empty( $start_date ) || empty( $end_date ) ) {
				return array();
			}

			if ( empty( $statuses ) ) {
				$statuses = charitable_get_approval_statuses();
			}

			list( $status_clause, $status_parameters ) = $this->get_donation_status_clause( $statuses );

			// Every status was filtered out as invalid, so nothing could match. Without
			// this the IN clause would be emitted empty and the query would error.
			if ( empty( $status_parameters ) ) {
				return array();
			}

			$parameters = array_merge(
				array( $start_date . ' 00:00:00', $end_date . ' 23:59:59' ),
				$status_parameters
			);

			/*
			 * Appended AFTER the status clause, so the extra parameter lands last
			 * in $parameters and the placeholder order still lines up. Filtering
			 * on cd.campaign_id rather than joining anything: a donation split
			 * across campaigns has one row per campaign in this table, and the
			 * per-campaign row is exactly what a per-campaign figure means.
			 */
			$campaign_clause = '';
			$campaign_id     = (int) $campaign_id;

			if ( $campaign_id > 0 ) {
				$campaign_clause = ' AND cd.campaign_id = %d';
				$parameters[]    = $campaign_id;
			}

			// Sums cd.amount, matching get_donations_summary_by_period() above. The
			// base_amount column belongs to the multi-currency schema and does not exist
			// on this table in Lite, so it must not appear here.
			$sql = "SELECT DATE( p.post_date ) as day,
				COALESCE( SUM( cd.amount ), 0 ) as amount,
				COUNT( cd.donation_id ) as count
				FROM {$wpdb->prefix}charitable_campaign_donations cd
				INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
				WHERE p.post_date >= %s AND p.post_date <= %s AND {$status_clause}{$campaign_clause}
				GROUP BY DATE( p.post_date )";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( empty( $results ) ) {
				return array();
			}

			$comma_decimal = $this->is_comma_decimal();
			$summary       = array();

			foreach ( $results as $result ) {
				if ( $comma_decimal ) {
					$result = $this->sanitize_amounts( $result );
				}

				$summary[ $result->day ] = $result;
			}

			return $summary;
		}

		/**
		 * Returns the orderby clause.
		 *
		 * @since  1.3.4
		 *
		 * @param  array  $args    Query arguments.
		 * @param  string $default Default.
		 * @return string
		 */
		public function get_orderby_clause( $args, $default = '' ) { // phpcs:ignore
			if ( ! isset( $args['orderby'] ) && ! isset( $args['order'] ) ) {
				return $default;
			}

			$orderby = isset( $args['orderby'] ) ? $args['orderby'] : 'ID';
			$order   = isset( $args['order'] ) && in_array( $args['order'], array( 'ASC', 'DESC' ) ) ? $args['order'] : 'ASC'; // phpcs:ignore

			switch ( $orderby ) {
				case 'date':
					$ret = 'ORDER BY p.post_date ';
					break;

				default:
					$ret = 'ORDER BY cd.campaign_donation_id ';
			}

			$ret .= $order;

			return $ret;
		}

		/**
		 * Count donations by status.
		 *
		 * @since  1.0.0
		 *
		 * @param  string|string[] $statuses List of statuses.
		 * @return int
		 */
		public function count_donations_by_status( $statuses ) {
			global $wpdb;

			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			list( $status_clause, $parameters ) = $this->get_donation_status_clause( $statuses );
			if ( ! empty( $status_clause ) ) {
				$sql_where = 'WHERE ' . $status_clause;
			}

			$sql = "SELECT COUNT( * )
					FROM {$wpdb->prefix}charitable_campaign_donations cd
					INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
					$sql_where;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		/**
		 * Returns the donation status clause.
		 *
		 * @since  1.0.0
		 *
		 * @param  string[] $statuses List of statuses.
		 * @return array
		 */
		private function get_donation_status_clause( $statuses = array() ) {
			if ( empty( $statuses ) ) {
				return array( '', array() );
			}

			$statuses = array_filter( $statuses, 'charitable_is_valid_donation_status' );
			$in       = charitable_get_query_placeholders( count( $statuses ), '%s' );
			$sql      = "p.post_status IN ( $in )";

			return array( $sql, $statuses );
		}

		/**
		 * Returns an array containing the placeholders and sanitized parameters for an IN clause.
		 *
		 * @since  1.4.0
		 *
		 * @param  int|int[] $list List of elements.
		 * @return array
		 */
		private function get_in_clause_params( $list ) { // phpcs:ignore
			if ( ! is_array( $list ) ) {
				$list = array( $list );
			}

			/* Filter out any non-numeric campaign IDs, then convert to int */
			$list = array_filter( $list, 'is_numeric' );
			$list = array_map( 'intval', $list );

			return array( charitable_get_query_placeholders( count( $list ), '%d' ), $list );
		}

		/**
		 * Checks whether we are using commas for decimals.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		private function is_comma_decimal() {
			if ( ! isset( $this->comma_decimal ) ) {
				$this->comma_decimal = Charitable_Currency::get_instance()->is_comma_decimal();
			}

			return $this->comma_decimal;
		}

		/**
		 * Sanitize amounts retrieved from the database.
		 *
		 * @since  1.3.0
		 *
		 * @param  object $campaign_donation Campaign donation record.
		 * @return object
		 */
		private function sanitize_amounts( $campaign_donation ) {
			$campaign_donation->amount = Charitable_Currency::get_instance()->sanitize_database_amount( $campaign_donation->amount );
			return $campaign_donation;
		}

		/**
		 * Sanitize amounts for grouped queries
		 *
		 * @since  1.6.57
		 *
		 * @param  array $item in array is a label, item[1] is the amount. We need to sanitize the amount.
		 * @return object
		 */
		private function sanitize_grouped_amounts( $item ) {
			$item[1] = Charitable_Currency::get_instance()->sanitize_database_amount( $item[1] );
			return $item;
		}

		/**
		 * Return a sanitized column name.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $field Return the column name.
		 * @return string
		 */
		private function get_sanitized_column( $field ) {
			switch ( $field ) {
				case 'campaign':
				case 'campaign_id':
					$column = 'campaign_id';
					break;

				case 'donation':
				case 'donation_id':
					$column = 'donation_id';
					break;

				case 'donor':
				case 'donor_id':
					$column = 'donor_id';
					break;

				default:
					charitable_get_deprecated()->doing_it_wrong(
						__METHOD__,
						__( 'Field expected to be `campaign`, `campaign_id`, `donation`, `donation_id`, `donor` or `donor_id`.', 'charitable' ),
						'1.4.0'
					);

					$column = false;
			}

			return $column;
		}

		/**
		 * Flush campaign caches when updating one or more rows.
		 *
		 * @since  1.5.11
		 *
		 * @param  int    $row_id The primary key ID for the row we are retrieving.
		 * @param  string $where  Column used in where argument.
		 * @return void
		 */
		private function flush_campaign_caches( $row_id, $where = '' ) {
			if ( empty( $where ) ) {
				$where = $this->primary_key;
			}

			$campaign_ids = array_unique( $this->get_column_all_by( 'campaign_id', $where, $row_id ) );

			foreach ( $campaign_ids as $campaign_id ) {
				Charitable_Campaign::flush_donations_cache( $campaign_id );
			}
		}
	}

endif;
