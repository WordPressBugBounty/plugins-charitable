<?php
/**
 * Tracking functions for reporting plugin usage (optin) and anonymous tracking (optin) to the Charitable site for users
 *
 * @access public
 * @package     Charitable
 * @subpackage  Admin
 * @copyright   Copyright (c) 2024, David Bisset
 * @since       1.8.4
 * @version     1.8.4.5
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tracking' ) ) {
	/**
	 * Usage tracking
	 */
	class Charitable_Tracking {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Tracking|null
		 */
		private static $instance = null;

		/**
		 * Class init function.
		 */
		public function __construct() {
		}

		/**
		 * Starts the process of sending in the optin usage checking.
		 *
		 * @since 1.8.4.2
		 *
		 * @param boolean $override            Override usage_optin_allowed.
		 * @param boolean $ignore_last_checkin Ignore last checkin flag.
		 *
		 * @return void
		 */
		public function send_checkins( $override = false, $ignore_last_checkin = false ) {
			$this->send_optin_usage_checkin( $override, $ignore_last_checkin );
			$this->send_tracking_checkin( $override, $ignore_last_checkin );
		}

		/**
		 * Manually sends tracking data if optin is enabled. Testing purposes only.
		 *
		 * @since 1.8.4
		 *
		 * @return void
		 */
		public function test_checkin() {
			if ( is_admin() && current_user_can( 'manage_options' ) && defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:ignore
				// detect the query string in the admin url.
				$send_checkin = isset( $_GET['charitable_send_checkin'] ) ? sanitize_text_field( wp_unslash( $_GET['charitable_send_checkin'] ) ) : false; // phpcs:ignore
				if ( charitable_is_debug() ) {
					error_log( 'charitable test checkin triggered' ); // phpcs:ignore
				}
				if ( 'usage' === $send_checkin ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable test checkin was run for usage' ); // phpcs:ignore
					}
					$this->send_optin_usage_checkin( true, true );
				} elseif ( 'tracking' === $send_checkin ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable test checkin was run for tracking' ); // phpcs:ignore
					}
					$this->send_tracking_checkin( true, true );
				} elseif ( 'both' === $send_checkin ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable test checkin was run for both' ); // phpcs:ignore
					}
					$this->send_optin_usage_checkin( true, true );
					$this->send_tracking_checkin( true, true );
				}
			}
		}

		/**
		 * Fetch tracking data.
		 *
		 * @since 1.8.4
		 * @version 1.8.4.5
		 *
		 * @return array $data Tracked data.
		 */
		private function get_optin_data() {

			global $wpdb;

			$data = array();

			// get charitable settings.
			$charitable_settings = get_option( 'charitable_settings' );

			// Retrieve current theme info.
			$theme_data = wp_get_theme();

			$count_b = 1;
			if ( is_multisite() ) {
				if ( function_exists( 'get_blog_count' ) ) {
					$count_b = get_blog_count();
				} else {
					$count_b = '0';
				}
			}

			$charitable_object = charitable();

			// Settings values reported individually. The raw licences array is
			// deliberately NOT among them any more; see the flat licence key below.
			$country         = ! empty( $charitable_settings['country'] ) ? $charitable_settings['country'] : '';
			$currency        = ! empty( $charitable_settings['currency'] ) ? $charitable_settings['currency'] : '';
			$default_gateway = ! empty( $charitable_settings['default_gateway'] ) ? $charitable_settings['default_gateway'] : '';

			$data['php_version']        = phpversion();
			$data['wpchar_version']     = $charitable_object !== null ? charitable()->get_version() : '';
			$data['wp_version']         = get_bloginfo( 'version' );
			$data['servertype']         = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
			$data['upgraded_from']      = get_option( 'charitable_upgraded_from', '' );
			$data['activated']          = get_option( 'charitable_activated', '' );
			$data['activated_datetime'] = get_option( 'wpcharitable_activated_datetime', '' );
			$data['first_campaign']     = get_option( 'charitable_first_campaign', '' );
			$data['first_donation']     = get_option( 'charitable_first_donation', '' );
			$data['multisite']          = is_multisite();
			$data['url']                = home_url();
			$data['id']                 = $this->get_site_aid();
			$data['themename']          = $theme_data->Name; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data['themeversion']       = $theme_data->Version; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data['email']              = get_bloginfo( 'admin_email' );
			// The raw licences array was removed from this payload in 1.8.17.4. It
			// shipped the site's ENTIRE
			// licence array, keys included, on every check-in. Nothing stored it: the
			// usage receiver's only use was a legacy fallback reading
			// licenses['charitable-v2']['license'] when the flat key is absent, and
			// there is no column for it; the tracking receiver never referenced it.
			//
			// The flat key below is a strict SUPERSET of that fallback. Both read the
			// same charitable_settings['licenses'], but get_payload_license_key()
			// prefers charitable-v2 and then falls back to the first available
			// licence, whereas the nested path only ever read charitable-v2.
			//
			// The receiver keeps its fallback: sites on 1.8.17.2 and earlier still
			// send the array and still need it.
			$data['license_key']        = $this->get_payload_license_key();
			$data['country']            = $country;
			$data['currency']           = $currency;
			$data['default_gateway']    = $default_gateway;
			$data['gateways_connected'] = $this->get_gateways_connected( $charitable_settings );
			$data['gateways_enabled']   = $this->get_gateways_enabled( $charitable_settings );
			$data['environment_type']   = $this->get_environment_type();
			$data['onboarding_status']  = $this->get_onboarding_status();
			$data['pro']                = function_exists( 'charitable_is_pro' ) ? charitable_is_pro() : false;
			$data['sites']              = $count_b;
			$data['usagetracking']      = get_option( 'charitable_usage_tracking_config', false );
			$data['usercount']          = function_exists( 'count_users' ) ? count_users()['total_users'] : '0';
			$data['timezoneoffset']     = gmdate( 'P' );
			$data['wc_active']          = $this->check_if_wc_active();
			$data['usages']             = array(
				'blocks'   => $this->block_count_summation(),
				'wp_pages' => $this->get_wp_pages(),
				'wp_posts' => $this->get_wp_posts(),
			);

			// Add recommendation tracking data
			$data['recommended_plugins_viewed'] = get_option( 'charitable_recommended_plugins_viewed', array() );
			$data['recommended_plugins_clicked'] = get_option( 'charitable_recommended_plugins_clicked', array() );
			$data['recommended_plugins_installed'] = get_option( 'charitable_recommended_plugins_installed', array() );
			$data['recommended_plugins_activated'] = get_option( 'charitable_recommended_plugins_activated', array() );
			$data['dashboard_enhance_section_views'] = get_option( 'charitable_dashboard_enhance_views', 0 );

			// Retrieve current plugin information.
			if ( ! function_exists( 'get_plugins' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php';
			}

			$plugins        = array_keys( get_plugins() );
			$active_plugins = get_option( 'active_plugins', array() );

			foreach ( $plugins as $key => $plugin ) {
				if ( in_array( $plugin, $active_plugins, true ) ) {
					// Remove active plugins from list so we can show active and inactive separately.
					unset( $plugins[ $key ] );
				}
			}

			$data['active_plugins']   = $active_plugins;
			$data['inactive_plugins'] = $plugins;
			$data['locale']           = get_locale();

			return $data;
		}


		/**
		 * Fetch tracking data.
		 *
		 * @since 1.8.4
		 *
		 * @return array $data Tracked data.
		 */
		private function get_tracking_data() {

			global $wpdb;

			$data = array();

			$data['id']          = $this->get_site_aid();
			$data['license_key'] = $this->get_payload_license_key();

			// get the total number of campaigns.
			$data['campaign_counts'] = (array) wp_count_posts( 'campaign' );

			// campaign data.
			$data['campaign_data'] = (array) $this->get_charitable_data();

			// donation data.
			$data['donation_data']           = $this->get_donation_data();
			$data['recurring_donation_data'] = $this->get_recurring_donation_data();

			return $data;
		}

		/**
		 * Get Charitable data.
		 *
		 * @return array $data Charitable data.
		 */
		public function get_charitable_data() {

			global $wpdb;

			$sql = "SELECT
                        SUM(subquery.total_amount) AS grand_total_amount,
                        AVG(subquery.total_amount) AS grand_average_amount,
                        SUM(subquery.total_count_donations) AS grand_total_count_donations
                    FROM (
                        SELECT
                            SUM(ccd.amount) AS total_amount,
                            COUNT(ccd.donation_id) AS total_count_donations,
                            cd.donor_id
                        FROM {$wpdb->prefix}charitable_donors cd
                        JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
                        GROUP BY cd.donor_id
                    ) AS subquery;
            ";

			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

			$data = [];

			if ( ! empty( $results ) ) {
				// Ensure each amount is converted to a string before sanitization.
				$data['total'] = 0 !== $results[0]['grand_total_amount']
					? charitable_sanitize_amount( (string) ( $results[0]['grand_total_amount'] ), true )
					: 0;

				$data['average']     = 0 !== $results[0]['grand_average_amount']
					? charitable_sanitize_amount( (string) ( $results[0]['grand_average_amount'] ), true )
					: 0;
				$data['donor_count'] = 0 !== $results[0]['grand_total_count_donations'] ? intval( $results[0]['grand_total_count_donations'] ) : 0;
			}

			if ( ! empty( $data ) ) {
				return $data;
			}
		}

		/**
		 * Get donation related data.
		 *
		 * @since 1.8.4
		 *
		 * @return array $data Donation data.
		 */
		public function get_donation_data() {

			global $wpdb;

			$defaults = array(
				'start_date'  => false,
				'end_date'    => false,
				'post_status' => 'charitable-completed',
				'campaign_id' => false,
				'category_id' => false,
			);

			// Extract individual variables from defaults array.
			$start_date  = $defaults['start_date'];
			$end_date    = $defaults['end_date'];
			$post_status = $defaults['post_status'];
			$campaign_id = $defaults['campaign_id'];
			$category_id = $defaults['category_id'];

			$where_sql   = array();
			$where_sql[] = 'WHERE 1=1';
			$where_sql[] = 'p.post_type = "%s"';
			$where_sql[] = 'p.post_status = "' . $post_status . '"';
			$where_sql[] = 'pm1.meta_key = "donation_gateway"';

			// remove all empty values from the array.
			$where_sql  = array_filter( $where_sql );
			$where_args = implode( ' AND ', $where_sql );

			$left_join   = array();
			$left_join[] = $wpdb->prefix . 'charitable_campaign_donations cd ON p.ID = cd.donation_id';
			$left_join[] = $wpdb->prefix . 'charitable_donors cdonors ON cd.donor_id = cdonors.donor_id';
			$left_join[] = $wpdb->prefix . 'postmeta pm1 ON p.ID = pm1.post_id';

			// remove all empty values from the array.
			$left_join      = array_filter( $left_join );
			$left_join_args = 'LEFT JOIN ' . implode( ' LEFT JOIN ', $left_join );

			$sql = "SELECT SUM(cd.amount) AS total_amount,
			cd.donation_id AS donation_id,
			cd.campaign_id AS campaign_id,
			cd.donor_id AS donor_id,
			cd.amount AS amount,
			pm1.meta_value AS payment_gateway,
			p.post_date AS post_date,
			p.post_date_gmt AS post_date_gmt,
			p.post_status AS donation_status,
			p.post_type AS donation_type,
			p.post_parent AS donation_parent_id
			FROM $wpdb->posts p
			" . $left_join_args . '
			' . $where_args . '
			GROUP BY p.ID';

			$donations = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					$sql, // phpcs:ignore
					'donation'
				)
			);

			return $this->summarise_donations( is_array( $donations ) ? $donations : array() );
		}

		/**
		 * Aggregate donation rows into the payload's donation_data block.
		 *
		 * Extracted from get_donation_data() so it is testable without a $wpdb
		 * stub; that method now does the SQL and delegates here.
		 *
		 * COUNTS ARE NEW IN 1.8.17.4 AND THE REASON THIS EXISTS.
		 * total_donations / donations_7_days / donations_30_days are MONEY
		 * AMOUNTS, but the Active User Intensity bands are defined in donation
		 * COUNTS ("1 to 2 donations", "3 to 10", "10+"). With only amounts
		 * available, the classifier's IntensityResolver compared a cloud count
		 * against a telemetry dollar figure via max(), so a single $50 donation
		 * would classify a customer as Engaged-Heavy. The amount keys keep their
		 * existing meaning; the count keys are additive.
		 *
		 * Window comparisons deliberately use post_date, matching the pre-existing
		 * amount windows, so a count and its amount always describe the same set
		 * of donations. last_donation_at uses post_date_gmt instead, because a
		 * timestamp leaving the site has to be unambiguous; post_date is
		 * site-local. That inconsistency is inherited, not introduced, and is not
		 * silently "fixed" here because changing the existing windows would move
		 * numbers the receiver has been storing for two years.
		 *
		 * @since 1.8.12
		 *
		 * @param array $donations Rows from get_donation_data()'s query.
		 * @return array
		 */
		private function summarise_donations( array $donations ) {

			$total_donations           = 0;
			$donations_7_days          = 0;
			$donations_30_days         = 0;
			$count_total               = 0;
			$count_7_days              = 0;
			$count_30_days             = 0;
			$last_donation_at          = null;
			$donations_payment_gateway = array();

			$cutoff_7  = strtotime( '-7 days' );
			$cutoff_30 = strtotime( '-30 days' );

			foreach ( $donations as $donation ) {

				$amount = isset( $donation->total_amount ) ? $donation->total_amount : 0;
				$when   = isset( $donation->post_date ) ? strtotime( (string) $donation->post_date ) : false;

				$total_donations += $amount;
				++$count_total;

				if ( false !== $when && $when > $cutoff_7 ) {
					$donations_7_days += $amount;
					++$count_7_days;
				}

				if ( false !== $when && $when > $cutoff_30 ) {
					$donations_30_days += $amount;
					++$count_30_days;
				}

				// GMT for the outbound timestamp; see the note above.
				$gmt = isset( $donation->post_date_gmt ) ? strtotime( (string) $donation->post_date_gmt . ' UTC' ) : false;

				if ( false !== $gmt && ( null === $last_donation_at || $gmt > $last_donation_at ) ) {
					$last_donation_at = $gmt;
				}

				$gateway = isset( $donation->payment_gateway ) ? (string) $donation->payment_gateway : '';

				if ( ! isset( $donations_payment_gateway[ $gateway ] ) ) {
					$donations_payment_gateway[ $gateway ] = 0;
				}

				$donations_payment_gateway[ $gateway ] += $amount;
			}

			return array(
				'total_donations'           => $total_donations,
				'donations_7_days'          => $donations_7_days,
				'donations_30_days'         => $donations_30_days,
				'donations_payment_gateway' => $donations_payment_gateway,
				// New in 1.8.17.4.
				'donation_count_total'      => $count_total,
				'donation_count_7_days'     => $count_7_days,
				'donation_count_30_days'    => $count_30_days,
				'last_donation_at'          => $last_donation_at,
			);
		}

		/**
		 * Get recurring donation related data.
		 *
		 * @since 1.8.4
		 *
		 * @return array $data Recurring donation data.
		 */
		public function get_recurring_donation_data() {

			global $wpdb;

			// if the recurring donations plugin is not active, return an empty array.
			if ( ! class_exists( 'Charitable_Recurring' ) ) {
				return array();
			}

			$where_sql   = array();
			$where_sql[] = 'WHERE 1=1';
			$where_sql[] = 'p.post_type = "%s"';
			$where_sql[] = 'p.post_status = "charitable-active"';
			$where_sql[] = 'pm1.meta_key = "donation_gateway"';
			$where_sql[] = 'pm2.meta_key = "donation_period"';
			$where_sql[] = 'pm3.meta_key = "_first_donation"';
			$where_sql[] = 'pm4.meta_key = "_expiration_date"';

			// remove all empty values from the array.
			$where_sql  = array_filter( $where_sql );
			$where_args = implode( ' AND ', $where_sql );

			$left_join      = array();
			$left_join[]    = $wpdb->prefix . 'postmeta pm1 ON p.ID = pm1.post_id';
			$left_join[]    = $wpdb->prefix . 'postmeta pm2 ON p.ID = pm2.post_id';
			$left_join[]    = $wpdb->prefix . 'postmeta pm3 ON p.ID = pm3.post_id';
			$left_join[]    = $wpdb->prefix . 'postmeta pm4 ON p.ID = pm4.post_id';
			$left_join      = array_filter( $left_join );
			$left_join_args = 'LEFT JOIN ' . implode( ' LEFT JOIN ', $left_join );

			$sql = "SELECT SUM(pm4.meta_value) AS total_recurring_amount,
			pm1.meta_value AS payment_gateway,
			pm2.meta_value AS donation_period,
			pm3.meta_value AS first_donation,
			pm4.meta_value AS expiration_date,
			p.post_date AS post_date,
			p.post_date_gmt AS post_date_gmt,
			p.post_status AS donation_status,
			p.post_parent AS donation_parent_id
			FROM $wpdb->posts p
			" . $left_join_args . '
			' . $where_args . '
			GROUP BY p.ID';

			$recurring_donations = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					$sql, // phpcs:ignore
					'recurring_donation'
				)
			);

			return $this->summarise_recurring_donations( is_array( $recurring_donations ) ? $recurring_donations : array() );
		}

		/**
		 * Aggregate recurring rows into the payload's recurring_donation_data block.
		 *
		 * Extracted from get_recurring_donation_data() so it is testable without a
		 * $wpdb stub.
		 *
		 * recurring_donations_count is NEW in 1.8.17.4 and is the point. Stage 5
		 * Power leans on recurring activity, but the only recurring signal in the
		 * payload was total_recurring_amount, a money figure. On the cloud side
		 * subscription_analyses covers just 193 of 2,547 accounts and
		 * RefreshSubscriptionAnalysisCommand never writes active_subscriptions at
		 * all, so there was no reliable recurring COUNT anywhere.
		 *
		 * NOTE ON $donation->first_donation: in this query that is the
		 * _first_donation POST META, which is a MONEY AMOUNT, not a date. It shares
		 * a name with the charitable_first_donation option, which IS a date and is
		 * reported separately as $data['first_donation']. Do not conflate them.
		 *
		 * @since 1.8.12
		 *
		 * @param array $recurring_donations Rows from the recurring query.
		 * @return array
		 */
		private function summarise_recurring_donations( array $recurring_donations ) {

			$total_recurring_amount              = 0;
			$recurring_count                     = 0;
			$recurring_donations_7_days          = 0;
			$recurring_donations_30_days         = 0;
			$recurring_donations_payment_gateway = array();
			$recurring_donations_period          = array();

			$cutoff_7  = strtotime( '-7 days' );
			$cutoff_30 = strtotime( '-30 days' );

			foreach ( $recurring_donations as $donation ) {

				// A money amount, despite the name. See the docblock.
				$amount = isset( $donation->first_donation ) ? $donation->first_donation : 0;
				$when   = isset( $donation->post_date ) ? strtotime( (string) $donation->post_date ) : false;

				$total_recurring_amount += $amount;
				++$recurring_count;

				if ( false !== $when && $when > $cutoff_7 ) {
					$recurring_donations_7_days += $amount;
				}

				if ( false !== $when && $when > $cutoff_30 ) {
					$recurring_donations_30_days += $amount;
				}

				$gateway = isset( $donation->payment_gateway ) ? (string) $donation->payment_gateway : '';

				if ( ! isset( $recurring_donations_payment_gateway[ $gateway ] ) ) {
					$recurring_donations_payment_gateway[ $gateway ] = 0;
				}

				$recurring_donations_payment_gateway[ $gateway ] += $amount;

				$period = isset( $donation->donation_period ) ? (string) $donation->donation_period : '';

				if ( ! isset( $recurring_donations_period[ $period ] ) ) {
					$recurring_donations_period[ $period ] = 0;
				}

				$recurring_donations_period[ $period ] += $amount;
			}

			return array(
				'total_recurring_amount'              => $total_recurring_amount,
				'recurring_donations_payment_gateway' => $recurring_donations_payment_gateway,
				'recurring_donations_period'          => $recurring_donations_period,
				'recurring_donations_7_days'          => $recurring_donations_7_days,
				'recurring_donations_30_days'         => $recurring_donations_30_days,
				// New in 1.8.17.4.
				'recurring_donations_count'           => $recurring_count,
			);
		}

		/**
		 * Send optin usage data.
		 *
		 * @since 1.8.4
		 *
		 * @param boolean $override            Override usage_optin_allowed.
		 * @param boolean $ignore_last_checkin Ignore last checkin flag.
		 * @return boolean
		 */
		public function send_optin_usage_checkin( $override = false, $ignore_last_checkin = false ) {

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log('send_optin_usage_checkin');
				}
				// phpcs:enable
			}

			if ( ! $this->usage_optin_allowed() && ! $override ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					if ( charitable_is_debug() ) {
						error_log('charitable usage tracking not allowed');
					}
					// phpcs:enable
				}
				return false;
			}

			// Send a maximum of once per week.
			$last_send = get_option( 'charitable_usage_tracking_last_checkin' );
			if ( $this->checkin_is_throttled( $last_send ) && ! $ignore_last_checkin ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					if ( charitable_is_debug() ) {
						error_log('charitable usage tracking not allowed because of last checkin');
					}
					// phpcs:enable
				}
				return false;
			}

			$charitable_object  = charitable();
			$charitable_version = $charitable_object !== null ? charitable()->get_version() : '';

			$request = wp_remote_post(
				'https://usage.wpcharitable.com/capture',
				array(
					'method'      => 'POST',
					'timeout'     => 15,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'sslverify'   => false,
					'body'        => $this->get_optin_data(),
					'user-agent'  => 'CH/' . $charitable_version . '; ' . get_bloginfo( 'url' ),
				)
			);

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log( 'send_optin_usage_checkin' );
					error_log( print_r( $request, true  ) );
					error_log( print_r( $this->get_optin_data(), true ) );
				}
				// phpcs:enable
			}

			return $this->handle_checkin_result( 'usage', $request, 'charitable_usage_tracking_last_checkin' );
		}

		/**
		 * Send tracking data.
		 *
		 * @since 1.8.4
		 *
		 * @param boolean $override            Override usage_optin_allowed.
		 * @param boolean $ignore_last_checkin Ignore last checkin flag.
		 * @return boolean
		 */
		public function send_tracking_checkin( $override = false, $ignore_last_checkin = false ) {

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log('send_tracking_checkin');
				}
				// phpcs:enable
			}

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log( 'send_checkin' );
					error_log( $override );
					error_log( $ignore_last_checkin );
					error_log( 'tracking allowed' );
					error_log( $this->usage_optin_allowed() );
				}
				// phpcs:enable
			}

			if ( ! $this->tracking_allowed() && ! $override ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					if ( charitable_is_debug() ) {
						error_log( 'charitable tracking not allowed' );
					}
					// phpcs:enable
				}
				return false;
			}

			// Send a maximum of once per week.
			$last_send = get_option( 'charitable_tracking_last_checkin' );
			if ( $this->checkin_is_throttled( $last_send ) && ! $ignore_last_checkin ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					if ( charitable_is_debug() ) {
						error_log('charitable tracking not allowed because of last checkin');
					}
					// phpcs:enable
				}
				return false;
			}

			$charitable_object  = charitable();
			$charitable_version = $charitable_object !== null ? charitable()->get_version() : '';

			$request = wp_remote_post(
				'https://tracking.wpcharitable.com/capture',
				array(
					'method'      => 'POST',
					'timeout'     => 15,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'sslverify'   => false,
					'body'        => $this->get_tracking_data(),
					'user-agent'  => 'GenericTrackingClient/1.0',
				)
			);

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log( print_r( $request, true ) );
					error_log( print_r( $last_send, true ) );
					error_log( print_r( $charitable_version, true ) );
					error_log( print_r( $this->get_tracking_data(), true ) );
				}
			}

			// This endpoint's OWN throttle option, the same one read above. It used
			// to read this one but write the usage endpoint's instead, so the option
			// it tested was never written and this endpoint was never throttled at
			// all. The two need separate throttles, so the write was corrected to
			// match the read rather than the reverse. Passing the name through
			// explicitly is what makes that mismatch impossible to reintroduce.
			//
			// The other endpoint's option name is deliberately NOT written out
			// anywhere in this method: a source-level guard in
			// Test_Charitable_Tracking_Throttle asserts it never appears here, and a
			// comment quoting it would fail that guard.
			return $this->handle_checkin_result( 'tracking', $request, 'charitable_tracking_last_checkin' );
		}

		/**
		 * Check if optin usage tracking is allowed.
		 *
		 * @since 1.8.4
		 *
		 * @return boolean
		 */
		private function usage_optin_allowed() {

			// DISABLE means disable. This branch used to `return true`, i.e. ALLOWED,
			// so a site owner defining this constant to opt out got tracking
			// force-ENABLED, and because the check precedes the consent read below it
			// bypassed the opt-out entirely. Harmless only because the weekly cron
			// never fired before 1.8.17.4; fixed in the release that makes it fire.
			//
			// Debug force-enable is unaffected: that path uses
			// send_checkins( $override = true ), not this constant.
			if ( defined( 'CHARITABLE_DISABLE_OPTIN_USAGE' ) && CHARITABLE_DISABLE_OPTIN_USAGE ) {
				return false;
			}

			return (bool) apply_filters( 'charitable_usage_tracking', $this->get_usage_tracking_setting() );
		}

		/**
		 * The site's declared environment type.
		 *
		 * Added in 1.8.17.4 so the Active User denominator can EXCLUDE non-production
		 * sites. This matters more than it looks: usage_optin_allowed() has no
		 * environment gate at all (only the tracking path has one, and it excludes
		 * 'staging' alone), and `aid` lives in a WordPress option rather than being
		 * derived from the URL. So a cloned staging or local site reports a row
		 * carrying the PRODUCTION aid and licence key, which then joins to real
		 * donation data and inflates every figure.
		 *
		 * Reported rather than acted on: this deliberately does NOT change who
		 * sends. Gating sends here would silently shrink the reporting population
		 * and confound the 1.8.17.2 adoption trend. The receiver and classifier can
		 * filter once the data exists.
		 *
		 * @since 1.8.12
		 *
		 * @return string 'production', 'staging', 'development', 'local', or ''.
		 */
		private function get_environment_type() {

			if ( ! function_exists( 'wp_get_environment_type' ) ) {
				return '';
			}

			return (string) wp_get_environment_type();
		}

		/**
		 * The onboarding checklist status.
		 *
		 * Separates "installed and abandoned" from "installed and mid-setup", which
		 * is the distinction the Installed-over-30-days stuck flag needs to avoid
		 * firing on people who are simply still working through setup.
		 *
		 * Reads the raw option DELIBERATELY rather than calling
		 * Charitable_Checklist::is_checklist_completed(). That method routes through
		 * get_checklist_data(), which WRITES the default schema when the option is
		 * empty, and is_step_completed() takes $update_option = true by default. A
		 * payload build must never mutate site state, least of all from a cron
		 * request.
		 *
		 * @since 1.8.12
		 *
		 * @return string 'init', 'completed', 'skipped', or '' when unknown.
		 */
		private function get_onboarding_status() {

			$checklist = get_option( 'charitable_onboarding_checklist' );

			if ( ! is_array( $checklist ) || ! isset( $checklist['status'] ) || ! is_scalar( $checklist['status'] ) ) {
				return '';
			}

			return (string) $checklist['status'];
		}

		/**
		 * Whether a check-in is inside the once-per-week throttle window.
		 *
		 * @since 1.8.12
		 *
		 * @param mixed $last_send Stored last-check-in timestamp.
		 * @return bool
		 */
		private function checkin_is_throttled( $last_send ) {
			return is_numeric( $last_send ) && $last_send > strtotime( '-1 week' );
		}

		/**
		 * Timestamp to store after a FAILED send, so it retries in ~24 hours.
		 *
		 * Fixes a long-standing bug. Both send methods used to store
		 * time() + DAY_IN_SECONDS here, with the comment "recheck in 24 hours".
		 * But the throttle test is `$last_send > strtotime( '-1 week' )`, and a
		 * timestamp 24 hours in the FUTURE satisfies that for EIGHT days. So a
		 * single failed send suppressed retries for eight days, not one.
		 *
		 * Storing a timestamp that is already six days old makes the one-week
		 * test start failing 24 hours from now, which is what was intended.
		 *
		 * @since 1.8.12
		 *
		 * @return int
		 */
		private function get_retry_timestamp() {
			return time() - ( WEEK_IN_SECONDS - DAY_IN_SECONDS );
		}

		/**
		 * Apply the outcome of a check-in POST: throttle, record, and report.
		 *
		 * EXTRACTED so it can be tested. The two send methods each build a payload
		 * that needs wp_count_posts(), $wpdb, get_plugins() and a theme object, so
		 * they cannot be driven from the standalone suite; the result handling can.
		 * Without this, checkin_failure_reason() and record_checkin_failure() were
		 * unit-tested in isolation while NOTHING asserted that the send methods
		 * actually called them. A mutation test proved it: reverting the usage-send
		 * wiring left the whole suite green.
		 *
		 * Extracting it also removes a duplication that has already caused one bug.
		 * The two endpoints had near-identical copies of this logic, and the
		 * tracking copy READ charitable_tracking_last_checkin while WRITING
		 * charitable_usage_tracking_last_checkin, so that endpoint was never
		 * throttled at all. The option name is now a parameter, which makes that
		 * class of mismatch impossible rather than merely fixed.
		 *
		 * On success: clear any recorded failure and book the next check-in a week
		 * out. On failure: record why, and set the option to a timestamp that
		 * expires in 24 hours. See get_retry_timestamp() -- the old value was 24
		 * hours in the FUTURE, which suppressed retries for eight days.
		 *
		 * @since 1.8.12
		 *
		 * @param  string         $endpoint    'usage' or 'tracking'.
		 * @param  array|WP_Error $request     The wp_remote_post() return value.
		 * @param  string         $option_name The throttle option for this endpoint.
		 * @return bool True when the check-in succeeded.
		 */
		private function handle_checkin_result( $endpoint, $request, $option_name ) {

			$failure = $this->checkin_failure_reason( $request );

			if ( null === $failure ) {
				$this->clear_checkin_failure( $endpoint );
				update_option( $option_name, time() );

				return true;
			}

			$this->record_checkin_failure( $endpoint, $failure );
			update_option( $option_name, $this->get_retry_timestamp() );

			return false;
		}

		/**
		 * Why a check-in failed, or null when it succeeded.
		 *
		 * Two distinct failure modes, both of which used to pass silently.
		 *
		 * TRANSPORT (WP_Error): DNS, timeout, or TLS verification. This is the
		 * mode that has to be visible before `sslverify` can be turned on: a
		 * verification failure returns WP_Error, the caller stores a retry
		 * timestamp and returns false, and until now nothing recorded it. Turning
		 * verification on blind would silently drop a biased subset of sites
		 * (TLS-intercepting corporate networks and hosts) with no signal saying
		 * who or why, on a project whose whole purpose is a defensible
		 * denominator. The cURL code and message are what distinguish a
		 * certificate problem from a timeout, so both are kept.
		 *
		 * HTTP STATUS: a non-2xx response. This is the more serious of the two and
		 * was found while building the first. `! is_wp_error( $request )` is TRUE
		 * for any completed round trip, including a 500, so a receiver error
		 * stamped a SUCCESSFUL check-in and suppressed this site for a full week.
		 * The usage receiver has a live 500 on any payload missing `usages`, so
		 * this is not hypothetical. It mattered little while the weekly cron was
		 * broken and almost nothing was sent; from 1.8.17.4 on it matters weekly.
		 *
		 * The returned string is deliberately short and bounded. It is stored in
		 * an option and may later be reported, so it must never become an open
		 * channel for arbitrary content. A transport message is a cURL string and
		 * carries no part of the payload.
		 *
		 * @since 1.8.12
		 *
		 * @param  array|WP_Error $request The wp_remote_post() return value.
		 * @return string|null Null on success, otherwise a short reason.
		 */
		private function checkin_failure_reason( $request ) {

			if ( is_wp_error( $request ) ) {
				$reason = 'transport:' . $request->get_error_code();
				$detail = trim( preg_replace( '/\s+/', ' ', (string) $request->get_error_message() ) );

				if ( '' === $detail ) {
					return $reason;
				}

				return substr( $reason . ': ' . $detail, 0, 200 );
			}

			$code = (int) wp_remote_retrieve_response_code( $request );

			// 2xx only. A 3xx is not a success either: redirection is already
			// handled by wp_remote_post's own 'redirection' option, so a 301
			// arriving here means it was not followed.
			if ( $code < 200 || $code > 299 ) {
				return 'http:' . $code;
			}

			return null;
		}

		/**
		 * Record a failed check-in so it is not lost.
		 *
		 * Stored per endpoint, because the two have separate throttles and fail
		 * independently; one failing must not read as both failing.
		 *
		 * The count is the useful part. A site whose telemetry is permanently
		 * broken retries daily, and "failed 40 times since 12 June" is actionable
		 * where a boolean is not. A CHANGED reason restarts the count, since that
		 * is a different problem rather than more of the same one.
		 *
		 * Written with autoload false: this is diagnostic data read on demand, not
		 * something to load into memory on every request of every page view.
		 *
		 * NOT TRANSMITTED. This option stays on the site. Sending it would be a
		 * new field in the telemetry payload, and no new category of collected
		 * data goes in without a privacy review, so that is a separate decision.
		 * The consequence, worth being explicit about: this gives a support agent
		 * something to ask for, but it does not by itself let us measure the
		 * TLS-failure rate across the install base.
		 *
		 * @since 1.8.12
		 *
		 * @param  string $endpoint 'usage' or 'tracking'.
		 * @param  string $reason   From checkin_failure_reason().
		 * @return void
		 */
		private function record_checkin_failure( $endpoint, $reason ) {

			$failures = get_option( 'charitable_telemetry_send_failures', array() );

			if ( ! is_array( $failures ) ) {
				$failures = array();
			}

			$previous = isset( $failures[ $endpoint ] ) && is_array( $failures[ $endpoint ] )
				? $failures[ $endpoint ]
				: array();

			$same_reason = isset( $previous['reason'] ) && $previous['reason'] === $reason;

			$failures[ $endpoint ] = array(
				'reason' => $reason,
				'first'  => $same_reason && isset( $previous['first'] ) ? $previous['first'] : time(),
				'last'   => time(),
				'count'  => $same_reason && isset( $previous['count'] ) ? (int) $previous['count'] + 1 : 1,
			);

			update_option( 'charitable_telemetry_send_failures', $failures, false );

			// error_log() only when the site has asked for debug output. The option
			// above is the durable record and is always written; an unconditional
			// error_log() from the telemetry subsystem would write to the logs of
			// sites that may not even have consented to tracking, and would buy
			// nothing we do not already have. Logged on the FIRST occurrence of each
			// distinct reason only, so a permanently broken site does not add an
			// identical line every day forever.
			$debugging = ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE )
				|| ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG );

			if ( $debugging && ! $same_reason ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Charitable telemetry: %s check-in failed (%s)', $endpoint, $reason ) );
			}
		}

		/**
		 * Clear a recorded failure after a successful check-in.
		 *
		 * Only this endpoint's entry, so a recovered usage send does not hide a
		 * still-failing tracking send.
		 *
		 * @since 1.8.12
		 *
		 * @param  string $endpoint 'usage' or 'tracking'.
		 * @return void
		 */
		private function clear_checkin_failure( $endpoint ) {

			$failures = get_option( 'charitable_telemetry_send_failures', array() );

			if ( ! is_array( $failures ) || ! array_key_exists( $endpoint, $failures ) ) {
				return;
			}

			unset( $failures[ $endpoint ] );

			update_option( 'charitable_telemetry_send_failures', $failures, false );
		}

		/**
		 * Read the usage-tracking consent setting from any request context.
		 *
		 * charitable_get_usage_tracking_setting() lives in
		 * includes/admin/charitable-core-admin-functions.php, which is required
		 * only by Charitable_Admin::load_dependencies(). That runs only when
		 * is_admin() is true, so the function does NOT exist during a
		 * wp-cron.php request. Calling it unguarded from the cron path, as the
		 * consent gates used to, is a fatal error.
		 *
		 * The fallback is a byte-for-byte copy of that function's body rather
		 * than a guessed default, so consent semantics are identical in both
		 * contexts. Lite is opt-in only, so erring permissive here would send
		 * telemetry from sites that never agreed to it.
		 *
		 * @since 1.8.12
		 *
		 * @return int
		 */
		private function get_usage_tracking_setting() {

			if ( function_exists( 'charitable_get_usage_tracking_setting' ) ) {
				return charitable_get_usage_tracking_setting();
			}

			return (int) get_option( 'charitable_usage_tracking', false );
		}

		/**
		 * Check if tracking is allowed.
		 *
		 * @since 1.8.4
		 *
		 * @return boolean
		 */
		private function tracking_allowed() {

			// DISABLE means disable. See usage_optin_allowed() for the full note. This
			// one was worse: returning true here also bypassed
			// environment_allows_tracking(), so it defeated the staging exclusion too.
			if ( defined( 'CHARITABLE_DISABLE_TRACKING' ) && CHARITABLE_DISABLE_TRACKING ) {
				return false;
			}

			if ( ! $this->environment_allows_tracking() ) {
				return false;
			}

			return (bool) apply_filters( 'charitable_usage_tracking', $this->get_usage_tracking_setting() );
		}

		/**
		 * Whether the environment allows sending telemetry data.
		 *
		 * @since 1.8.4
		 *
		 * @return bool
		 */
		private function environment_allows_tracking() {

			if ( function_exists( 'wp_get_environment_type' ) && 'staging' === wp_get_environment_type() ) {
				return false;
			}

			return true;
		}

		/**
		 * Schedule send tracking data event.
		 *
		 * @since 1.8.4
		 *
		 * @return void
		 */
		public function schedule_send() {
			if ( ! wp_next_scheduled( 'charitable_usage_tracking_cron' ) ) {
				$tracking             = array();
				$tracking['day']      = wp_rand( 0, 6 );
				$tracking['hour']     = wp_rand( 0, 23 );
				$tracking['minute']   = wp_rand( 0, 59 );
				$tracking['second']   = wp_rand( 0, 59 );
				$tracking['offset']   = ( $tracking['day'] * DAY_IN_SECONDS ) +
									( $tracking['hour'] * HOUR_IN_SECONDS ) +
									( $tracking['minute'] * MINUTE_IN_SECONDS ) +
									$tracking['second'];
				// First send within 1 to 24 hours, NOT anchored to a Sunday.
				//
				// This used to be strtotime( 'next sunday' ) + $tracking['offset'],
				// where offset is itself up to ~7 days, so a new install waited
				// between 1 and 14 days for its first report.
				//
				// Load spreading is not lost: installs and opt-ins are already
				// scattered across the week, so anchoring the weekly recurrence
				// to that moment spreads slots at least as well as anchoring to
				// Sunday, and the 23-hour jitter window prevents a herd.
				//
				// day/hour/minute/second/offset are still computed and stored:
				// the whole $tracking array ships in the payload as
				// $data['usagetracking'] and the receiver json_encodes it, so
				// the shape must not change.
				$tracking['initsend'] = time() + wp_rand( HOUR_IN_SECONDS, DAY_IN_SECONDS );

				wp_schedule_event( $tracking['initsend'], 'weekly', 'charitable_usage_tracking_cron' );
				update_option( 'charitable_usage_tracking_config', $tracking );
			}
		}

		/**
		 * Add schedules.
		 *
		 * @since 1.8.4
		 *
		 * @param array $schedules Available/current schedules.
		 * @return array $schedules Schedules array.
		 */
		public function add_schedules( $schedules = array() ) {
			// Adds once weekly to the existing schedules.
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once Weekly', 'charitable' ),
			);
			return $schedules;
		}

		/**
		 * Get WP Posts count.
		 *
		 * @since 1.8.4
		 *
		 * @return array $wp_post_count WP Posts count.
		 */
		public function get_wp_posts() {
			global $wpdb;

			$wp_post_count = 0;

			$results = $wpdb->get_var( // phpcs:ignore
				"SELECT COUNT(`ID`) `hits`
				FROM {$wpdb->posts}
				WHERE `post_type` = 'post' AND `post_status` = 'publish';"
			);
			if ( ! empty( $results ) ) {
				$wp_post_count = $results;
			}

			return $wp_post_count;
		}

		/**
		 * Get WP Pages count.
		 *
		 * @since 1.8.4
		 *
		 * @return array $wp_pages_count WP Pages count.
		 */
		public function get_wp_pages() {
			global $wpdb;

			$wp_pages_count = 0;

			$results = $wpdb->get_var( // phpcs:ignore
				"SELECT COUNT(`ID`) `hits`
				FROM {$wpdb->posts}
				WHERE `post_type` = 'page' AND `post_status` = 'publish';"
			);
			if ( ! empty( $results ) ) {
				$wp_pages_count = $results;
			}

			return $wp_pages_count;
		}

		/**
		 * Insert time for first campaign.
		 *
		 * @since 1.8.4.5
		 *
		 * @param string $insert_or_update Insert or update.
		 * @param int    $campaign_id       Campaign ID.
		 * @param array  $data              Data.
		 * @param object $object            Object.
		 *
		 * @return void
		 */
		public function insert_time_to_first_campaign( $insert_or_update, $campaign_id, $data, $object ) {
			global $wpdb;

			// Only do this for inserts.
			if ( 'insert' !== $insert_or_update ) {
				return;
			}

			$first_campaign = get_option( 'charitable_first_campaign' );

			if ( ! $first_campaign ) {
				$first_campaign = time();
				update_option( 'charitable_first_campaign', $first_campaign );
			}
		}

		/**
		 * Insert time for first donation.
		 *
		 * @since 1.8.4.5
		 *
		 * @param int    $donation_id       Donation ID.
		 * @param object $object            Charitable_Donation_Processor Object.
		 *
		 * @return void
		 */
		public function insert_time_to_first_donation( $donation_id, $object ) {
			global $wpdb;

			$first_donation = get_option( 'charitable_first_donation' );

			if ( ! $first_donation ) {
				$first_donation = time();
				update_option( 'charitable_first_donation', $first_donation );
			}
		}

		/**
		 * Get block count summation.
		 *
		 * @since 1.8.4
		 *
		 * @return array $blocks_usage_sum Block usage summation.
		 */
		public function block_count_summation() {
			// Get all _wpchar_block_usage data.
			global $wpdb;

			$tablename = $wpdb->prefix . 'postmeta';
			$sql       = "SELECT meta_value FROM $tablename";
			$sql      .= ' WHERE meta_key = %s';
			$safe_sql  = $wpdb->prepare( $sql, '_wpchar_block_usage' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results   = $wpdb->get_results( $safe_sql ); // phpcs:ignore

			$blocks_usage_sum = array();

			// Sum all block usage data.
			if ( $results ) {
				foreach ( $results as $result ) {
					if ( $result->meta_value ) {
						$page_usage_data = maybe_unserialize( $result->meta_value );
						if ( is_array( $page_usage_data ) ) {
							foreach ( $page_usage_data as $type => $value ) {
								if ( array_key_exists( $type, $blocks_usage_sum ) ) {
									// If set.
									$blocks_usage_sum[ $type ] = array(
										'name'  => $blocks_usage_sum[ $type ]['name'],
										'count' => $blocks_usage_sum[ $type ]['count'] + $value['count'], // Sum count.
									);
								}

								if ( ! array_key_exists( $type, $blocks_usage_sum ) ) {
									// If block type is not set.
									$blocks_usage_sum[ $type ] = $value;
								}
							}
						}
					}
				}
			}

			return $blocks_usage_sum;
		}

		/**
		 * Check if WooCommerce is active or not.
		 *
		 * @since 1.8.4
		 *
		 * @return boolean true|false Return if WC active.
		 */
		public function check_if_wc_active() {
			// Check if WooCommerce is active.
			return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
		}

		/**
		 * Get a map of payment gateways that hold a stored credential.
		 *
		 * Booleans only. No key, token, merchant ID or email is read into the
		 * payload, only whether one is present, so this reports site
		 * configuration state and adds no donor-level data (parent spec 8.4).
		 *
		 * Reads the settings array directly rather than going through
		 * charitable_get_option() or a gateway object, for three reasons.
		 * Charitable_Gateways::get_gateway_object() calls `new $class()` and
		 * gateway constructors register hooks, which must not happen while
		 * building a cron payload. The Stripe Connect settings filter nulls
		 * stored keys out when the Connect addon is inactive, and the stored
		 * fact is what we want to measure, not the currently retrievable
		 * value. And the caller already holds this array, so this costs no
		 * extra queries.
		 *
		 * 'offline' has no credential of any kind, so for that gateway being
		 * enabled IS its whole configuration, and that is what is reported.
		 *
		 * @since 1.8.12
		 *
		 * @param array $settings The charitable_settings option.
		 * @return array<string,bool>
		 */
		private function get_gateways_connected( $settings ) {

			$settings = is_array( $settings ) ? $settings : array();

			$stripe          = isset( $settings['gateways_stripe'] ) && is_array( $settings['gateways_stripe'] ) ? $settings['gateways_stripe'] : array();
			$paypal          = isset( $settings['gateways_paypal'] ) && is_array( $settings['gateways_paypal'] ) ? $settings['gateways_paypal'] : array();
			$paypal_commerce = isset( $settings['gateways_paypal_commerce'] ) && is_array( $settings['gateways_paypal_commerce'] ) ? $settings['gateways_paypal_commerce'] : array();
			$square          = isset( $settings['gateways_square'] ) && is_array( $settings['gateways_square'] ) ? $settings['gateways_square'] : array();

			// Mirrors Charitable_Gateway_Stripe_AM::check_keys_exist(), which
			// requires BOTH the secret and the publishable key for a mode.
			$stripe_keyed = ( ! empty( $stripe['live_secret_key'] ) && ! empty( $stripe['live_public_key'] ) )
				|| ( ! empty( $stripe['test_secret_key'] ) && ! empty( $stripe['test_public_key'] ) );

			// Written on Connect authorise, deleted on disconnect. Covers
			// Connect sites whose stored keys the settings filter hides.
			$stripe_connect = (bool) get_option( 'charitable_using_stripe_connect', false );

			$paypal_connected = ! empty( $paypal_commerce['live_seller_merchant_id'] )
				|| ! empty( $paypal_commerce['sandbox_seller_merchant_id'] )
				|| ! empty( $paypal['paypal_email'] )
				|| ! empty( $paypal['sandbox_paypal_email'] );

			// Square stores an OAuth token nested per mode. Presence only; the
			// value is encrypted on Connect sites and is never decrypted here.
			//
			// The nested level is is_array()-guarded like every parent above it,
			// and that is not defensive noise. empty() tolerates a string, int,
			// null or bool parent, but on an OBJECT parent it throws
			// "Cannot use object of type X as array", which would be a fatal
			// inside get_optin_data() during a cron run. charitable_settings is
			// an unserialised option, so an object there is unlikely but not
			// impossible, and this is the only two-level read in this method.
			$square_live = isset( $square['live'] ) && is_array( $square['live'] ) ? $square['live'] : array();
			$square_test = isset( $square['test'] ) && is_array( $square['test'] ) ? $square['test'] : array();

			$square_connected = ! empty( $square_live['access_token'] )
				|| ! empty( $square_test['access_token'] );

			return array(
				'stripe'  => (bool) ( $stripe_keyed || $stripe_connect ),
				'paypal'  => (bool) $paypal_connected,
				'square'  => (bool) $square_connected,
				'offline' => in_array( 'offline', $this->get_gateways_enabled( $settings ), true ),
			);
		}

		/**
		 * Get the list of enabled payment gateway IDs.
		 *
		 * Gateway IDs only, never the class names stored alongside them.
		 *
		 * Read raw from the settings array rather than via
		 * Charitable_Gateways::get_active_gateways(), which additionally drops
		 * gateways whose class is not currently loaded and, in Pro, applies
		 * PayPal tier visibility. Raw is the site's own saved configuration,
		 * which is what we want to measure, and it cannot shift depending on
		 * which addons happen to be active when the cron fires.
		 *
		 * array_values() matters: the receiver json_decode()s this, and a
		 * non-sequential array would encode as a JSON object, not an array.
		 *
		 * @since 1.8.12
		 *
		 * @param array $settings The charitable_settings option.
		 * @return string[]
		 */
		private function get_gateways_enabled( $settings ) {

			if ( ! is_array( $settings ) || empty( $settings['active_gateways'] ) || ! is_array( $settings['active_gateways'] ) ) {
				return array();
			}

			return array_values( array_map( 'strval', array_keys( $settings['active_gateways'] ) ) );
		}

		/**
		 * Derive the licence key for telemetry payloads.
		 *
		 * Prefers the charitable-v2 licence, falling back to the first licence
		 * present so installs storing a licence under another key are not dropped.
		 * Sanitised to alphanumerics so the value is byte-identical across both
		 * the usage and tracking payloads, which is what makes them joinable.
		 *
		 * @since  1.8.12
		 *
		 * @return string Sanitised licence key, or an empty string.
		 */
		private function get_payload_license_key() {

			$settings = get_option( 'charitable_settings' );
			$licenses = ! empty( $settings['licenses'] ) ? $settings['licenses'] : array();

			if ( empty( $licenses ) || ! is_array( $licenses ) ) {
				return '';
			}

			if ( isset( $licenses['charitable-v2']['license'] ) ) {
				$license = $licenses['charitable-v2']['license'];
			} else {
				$first   = reset( $licenses );
				$license = isset( $first['license'] ) ? $first['license'] : '';
			}

			return (string) preg_replace( '/[^a-zA-Z0-9]/', '', (string) $license );
		}

		/**
		 * Get the AID for the site.
		 *
		 * @since 1.8.4
		 *
		 * @return string The AID for the site.
		 */
		private function get_site_aid() {
			// Check if the AID already exists in the database.
			$aid = get_option( 'charitable_site_tracking_aid' );

			if ( $aid === false ) {
				// If it doesn't exist, generate a new one.
				$site_url    = get_site_url();  // Get the site URL.
				$random_salt = wp_generate_password( 20, false, false ); // Generate a random salt.
				// Create a hash based on the site URL and salt.
				$aid = hash( 'sha256', $site_url . $random_salt );

				// Save the generated AID in the database.
				update_option( 'charitable_site_tracking_aid', $aid );
			}

			return $aid;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.4
		 *
		 * @return Charitable_Tracking
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}


}
