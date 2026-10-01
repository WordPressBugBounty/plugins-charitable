<?php
/**
 * Report abilities: overview, day-by-day activity, top campaigns, top donors,
 * and the date ranges the other four accept.
 *
 * Three constraints govern this file.
 *
 * C3 — Pro's version of this file has every figure come from a query that
 * reads COALESCE( base_amount, amount ), never the raw `amount` column, so
 * that for the two RANKED abilities a donor who gave 792 JPY is never ranked
 * above one who gave 100 USD. Lite has no base_amount column and no
 * multi-currency feature (see create_table() in
 * class-charitable-campaign-donations-db.php, which defines only
 * `amount decimal(13,4)`), so every figure here reads `amount` directly — the
 * JPY-vs-USD scenario cannot occur, because every donation is already in the
 * site's one currency. See get_donations_summary_by_day_range() in that same
 * file for the same substitution and rationale.
 *
 * The days trap — charitable_get_days_between_dates() returns
 * array( 'Y-m-d' => 'M d' ). Iterating the VALUE and re-parsing it stamps every
 * day with the CURRENT year, so across an N-year range every date collapses
 * onto the same current-year day and the total is accumulated N times ($800
 * became $20,022 once). This file does not call that helper at all: the day
 * series is built here from unambiguous Y-m-d strings.
 *
 * Bounded answers — a day-by-day series over a very long range is slow by
 * design, so get-reports-activity caps the range and its description states the
 * cap. A model asking for "all time" gets a refusal naming the maximum rather
 * than a timeout.
 *
 * @package   Charitable/Classes/Charitable_Report_Abilities
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Report_Abilities' ) ) :

	/**
	 * Charitable_Report_Abilities
	 *
	 * @since 1.8.13
	 */
	class Charitable_Report_Abilities extends Charitable_Abilities_Registrar {

		/**
		 * Longest range get-reports-activity will build a day series for.
		 *
		 * 732 days is two years plus a leap day. Chosen so "the last two years"
		 * always fits regardless of which years the caller means, while still
		 * refusing the all-time ranges that made the reports screen time out.
		 * get-reports-overview has no such cap: it is a single grouped query and
		 * returns one row however long the range.
		 *
		 * @since 1.8.13
		 */
		const MAX_ACTIVITY_DAYS = 732;

		/**
		 * Register the report abilities.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public function register() {
			$this->register_ability(
				'charitable/get-reports-overview',
				array(
					'label'               => __( 'Get Reports Overview', 'charitable' ),
					'description'         => __( 'Returns the headline fundraising figures for a date range: total raised, number of donations, average donation, number of donors, and refunds. Defaults to the current month. Use this to answer "how much have we raised?" for any period. Any range length is accepted, because this returns a single set of totals rather than a day-by-day series.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_reports_overview' ),
					'permission_callback' => array( __CLASS__, 'permission_reports' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::range_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'start_date'                => array( 'type' => 'string' ),
							'end_date'                  => array( 'type' => 'string' ),
							'currency'                  => array( 'type' => 'string' ),
							'total_raised'              => array( 'type' => 'number' ),
							'total_raised_formatted'    => array( 'type' => 'string' ),
							'donation_count'            => array( 'type' => 'integer' ),
							'donor_count'               => array( 'type' => 'integer' ),
							'average_donation'          => array( 'type' => 'number' ),
							'average_donation_formatted' => array( 'type' => 'string' ),
							'refunded'                  => array( 'type' => 'number' ),
							'refunded_formatted'        => array( 'type' => 'string' ),
							'refund_count'              => array( 'type' => 'integer' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-reports-activity',
				array(
					'label'               => __( 'Get Reports Activity', 'charitable' ),
					'description'         => sprintf(
						/* translators: %d: the maximum number of days the range may span. */
						__( 'Returns a day-by-day series of amount raised and donation count across a date range, including days with no donations, so it can be charted directly. Defaults to the current month. The range may span at most %d days — for a longer period use Get Reports Overview, which returns totals for any range. Use this to answer when donations arrived rather than how much in total.', 'charitable' ),
						self::MAX_ACTIVITY_DAYS
					),
					'execute_callback'    => array( $this, 'execute_get_reports_activity' ),
					'permission_callback' => array( __CLASS__, 'permission_reports' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::range_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'start_date'             => array( 'type' => 'string' ),
							'end_date'               => array( 'type' => 'string' ),
							'currency'               => array( 'type' => 'string' ),
							'total_raised'           => array( 'type' => 'number' ),
							'total_raised_formatted' => array( 'type' => 'string' ),
							'days'                   => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'date'             => array( 'type' => 'string' ),
										'amount'           => array( 'type' => 'number' ),
										'amount_formatted' => array( 'type' => 'string' ),
										'count'            => array( 'type' => 'integer' ),
									),
								),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-top-campaigns',
				array(
					'label'               => __( 'Get Top Campaigns', 'charitable' ),
					'description'         => __( 'Returns the campaigns that raised the most, highest first, with the amount raised and the number of donations for each. With no dates supplied it ranks over ALL TIME, which answers "which campaign has done best?" directly. Pass start_date and end_date to rank within a period instead — a campaign that raised far more in an earlier year will then be ranked below a newer one, which is the point of asking for a period. Each amount is what that campaign raised within the period covered, not its lifetime total unless the period is all time. The reply echoes the start_date and end_date it used, and the answer should state that period. For one campaign\'s own figures use Get Campaign Performance.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_top_campaigns' ),
					'permission_callback' => array( __CLASS__, 'permission_reports' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::range_input_schema(
						array(
							'limit' => array(
								'type'        => 'integer',
								'description' => __( 'How many campaigns to return, 1 to 50. Defaults to 10.', 'charitable' ),
								'minimum'     => 1,
								'maximum'     => 50,
							),
						),
						true
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'start_date' => self::ranking_start_date_schema(),
							'end_date'   => array( 'type' => 'string' ),
							'currency'   => array( 'type' => 'string' ),
							'campaigns'  => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'campaign_id'      => array( 'type' => 'integer' ),
										'title'            => array( 'type' => 'string' ),
										'raised'           => array( 'type' => 'number' ),
										'raised_formatted' => array( 'type' => 'string' ),
										'donation_count'   => array( 'type' => 'integer' ),
									),
								),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-top-donors',
				array(
					'label'               => __( 'Get Top Donors', 'charitable' ),
					'description'         => __( "Returns the donors who gave the most, highest first, with each donor's total, donation count and email address. Requires permission to run reports AND to view sensitive data, because it is a report that names donors. With no dates supplied it ranks over ALL TIME, so \"who are our biggest supporters?\" and \"our top donors\" are answered over the site's whole history — use it for that and to build a thank-you list. Pass start_date and end_date to rank within a period instead, such as one appeal or one year. Each total is what that donor gave within the period covered, not their lifetime total unless the period is all time. The reply echoes the start_date and end_date it used, and the answer should state that period.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_top_donors' ),

					/*
					 * BOTH capabilities. This is a report, so it requires what
					 * its four siblings above and below require
					 * (`export_charitable_reports`), and it names donors, so the
					 * PII gate sits on top of that rather than instead of it.
					 * permission_sensitive() alone served a ranked list of donor
					 * names, emails and totals to a caller the other four report
					 * abilities refused. See permission_sensitive_reports().
					 */
					'permission_callback' => array( __CLASS__, 'permission_sensitive_reports' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::range_input_schema(
						array(
							'limit' => array(
								'type'        => 'integer',
								'description' => __( 'How many donors to return, 1 to 50. Defaults to 10.', 'charitable' ),
								'minimum'     => 1,
								'maximum'     => 50,
							),
						),
						true
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'start_date' => self::ranking_start_date_schema(),
							'end_date'   => array( 'type' => 'string' ),
							'currency'   => array( 'type' => 'string' ),
							'donors'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'donor_id'                => array( 'type' => 'integer' ),
										'name'                    => array( 'type' => 'string' ),
										'email'                   => array( 'type' => 'string' ),
										'total_donated'           => array( 'type' => 'number' ),
										'total_donated_formatted' => array( 'type' => 'string' ),
										'donation_count'          => array( 'type' => 'integer' ),
									),
								),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-reports-date-ranges',
				array(
					'label'               => __( 'Get Reports Date Ranges', 'charitable' ),
					'description'         => __( 'Turns a period named in words into real dates on this site, in the site\'s own timezone rather than a guess about where the site is. Returns two SEPARATE blocks that must not be mixed up. `ranges` holds REPORT periods only: each looks backward and its end_date is never later than today, because no donation can have happened on a day that has not happened yet — so `this-month` runs from the 1st to TODAY, not to the last day of the month. Pass a range\'s start_date and end_date to the other report abilities, which take dates rather than a period name. `future_dates` holds forward-looking single calendar dates — the real end of this month, next month and this year — and is the block to use for a DEADLINE, such as a campaign end_date. Never take a future date out of `ranges`: reading a deadline from `this-month.end_date` gives today, which creates a campaign that expires the day it was made. Never pass a `future_dates` date to a report ability either. A period in neither block — a quarter, a season, a named appeal — has to be turned into dates yourself, and the answer should say which dates were used.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_reports_date_ranges' ),
					'permission_callback' => array( __CLASS__, 'permission_reports' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::no_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'today'        => array( 'type' => 'string' ),
							'timezone'     => array( 'type' => 'string' ),
							'ranges'       => array(
								'type'        => 'array',
								'description' => __( 'Backward-looking REPORT periods. Every end_date is today or earlier. These are the only entries valid as start_date/end_date input to the other report abilities. Not a source of future dates — see future_dates.', 'charitable' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'key'        => array( 'type' => 'string' ),
										'label'      => array( 'type' => 'string' ),
										'start_date' => array( 'type' => 'string' ),
										'end_date'   => array( 'type' => 'string' ),
										'days'       => array( 'type' => 'integer' ),
									),
								),
							),
							'future_dates' => array(
								'type'        => 'object',
								'description' => __( 'Forward-looking single calendar dates, for DEADLINES — a campaign end_date, for example. Each entry is one date, deliberately NOT a start_date/end_date pair, so it cannot be mistaken for a report range. Never pass one to a report ability: it lies in the future and no donation data exists for it.', 'charitable' ),
								'properties'  => array(
									'end-of-this-month' => self::future_date_schema(),
									'end-of-next-month' => self::future_date_schema(),
									'end-of-this-year'  => self::future_date_schema(),
								),
							),
						),
					),
				)
			);
		}

		/**
		 * Execute charitable/get-reports-overview.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_reports_overview( $input ) {
			$dates = $this->resolve_range( $input );

			if ( is_wp_error( $dates ) ) {
				return $dates;
			}

			$table = charitable_get_table( 'campaign_donations' );
			$args  = $this->report_args( $dates, $input );

			/*
			 * One grouped query for the whole range, whatever its length —
			 * which is why this ability has no range cap. get_amount_report()
			 * reads `amount` directly; Lite has no base_amount column (see the
			 * C3 note at the top of this file).
			 */
			$approved = array_merge( $args, array( 'status' => charitable_get_approval_statuses() ) );
			$total    = (float) $table->get_amount_report( $approved, false );
			$count    = $table->count_donations_report( $approved );

			$refund_args  = array_merge( $args, array( 'status' => array( 'charitable-refunded' ) ) );
			$refunded     = (float) $table->get_amount_report( $refund_args, false );
			$refund_count = $table->count_donations_report( $refund_args );

			$average = $count > 0 ? $total / $count : 0.0;

			$total_money    = self::money( $total );
			$average_money  = self::money( $average );
			$refunded_money = self::money( $refunded );

			return array(
				'start_date'                 => $dates['start_date'],
				'end_date'                   => $dates['end_date'],
				'currency'                   => charitable_get_currency(),
				'total_raised'               => $total_money['raw'],
				'total_raised_formatted'     => $total_money['formatted'],
				'donation_count'             => $count,
				'donor_count'                => $table->count_donors_report( $approved ),
				'average_donation'           => $average_money['raw'],
				'average_donation_formatted' => $average_money['formatted'],
				'refunded'                   => $refunded_money['raw'],
				'refunded_formatted'         => $refunded_money['formatted'],
				'refund_count'               => $refund_count,
			);
		}

		/**
		 * Execute charitable/get-reports-activity.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_reports_activity( $input ) {
			$dates = $this->resolve_range( $input );

			if ( is_wp_error( $dates ) ) {
				return $dates;
			}

			/*
			 * COUNTED before it is BUILT, and that order is the whole point —
			 * see count_days_in_range(). The cap used to be checked against
			 * count( $days ), which meant the series had to be materialised
			 * before it could be judged too long. "From the year 1000 to today"
			 * is 375,012 date strings and a measured 99.6MB, on top of a
			 * WordPress baseline near 78MB: on a 128M site that is a fatal
			 * BEFORE this refusal can be returned, so the caller got a blank
			 * screen instead of the sentence explaining what to ask for
			 * instead. Guarded by
			 * test_an_ancient_range_is_refused_without_building_the_series().
			 */
			$requested_days = $this->count_days_in_range( $dates['start_date'], $dates['end_date'] );

			if ( $requested_days > self::MAX_ACTIVITY_DAYS ) {
				return new WP_Error(
					'charitable_range_too_long',
					sprintf(
						/* translators: 1: requested number of days, 2: the maximum allowed. */
						__( 'That range covers %1$d days, and a day-by-day series is limited to %2$d. Ask for a shorter range, or use Get Reports Overview, which returns totals for any range.', 'charitable' ),
						$requested_days,
						self::MAX_ACTIVITY_DAYS
					)
				);
			}

			$days = $this->day_series( $dates['start_date'], $dates['end_date'] );

			/*
			 * One query for the whole range. get_donations_summary_by_day_range()
			 * is the batched, already-converted accessor added in 1.8.18 to
			 * replace a per-calendar-day loop whose cost scaled with the LENGTH
			 * of the range rather than the amount of data. Days with no
			 * donations are absent from its return value, which is why the
			 * series is built from $days and the result merely looked up.
			 *
			 * The campaign filter is read through report_args(), the same helper
			 * the other three report abilities use, rather than from $input
			 * directly — that is what keeps the four of them agreeing about what
			 * `campaign_id` means. This ability declared the input and silently
			 * discarded it, so a request for one campaign's daily figures
			 * returned the whole site's. Caught by
			 * test_activity_honours_the_campaign_filter().
			 */
			$args = $this->report_args( $dates, $input );

			$by_day = (array) charitable_get_table( 'campaign_donations' )->get_donations_summary_by_day_range(
				$dates['start_date'],
				$dates['end_date'],
				charitable_get_approval_statuses(),
				isset( $args['campaign_id'] ) ? (int) $args['campaign_id'] : 0
			);

			$series = array();
			$total  = 0.0;

			foreach ( $days as $day ) {
				$amount = isset( $by_day[ $day ] ) ? (float) $by_day[ $day ]->amount : 0.0;
				$count  = isset( $by_day[ $day ] ) ? (int) $by_day[ $day ]->count : 0;
				$money  = self::money( $amount );

				$total += $money['raw'];

				$series[] = array(
					'date'             => $day,
					'amount'           => $money['raw'],
					'amount_formatted' => $money['formatted'],
					'count'            => $count,
				);
			}

			$total_money = self::money( $total );

			return array(
				'start_date'             => $dates['start_date'],
				'end_date'               => $dates['end_date'],
				'currency'               => charitable_get_currency(),
				'total_raised'           => $total_money['raw'],
				'total_raised_formatted' => $total_money['formatted'],
				'days'                   => $series,
			);
		}

		/**
		 * Execute charitable/get-top-campaigns.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_top_campaigns( $input ) {
			$dates = $this->resolve_ranking_range( $input );

			if ( is_wp_error( $dates ) ) {
				return $dates;
			}

			$args = array_merge(
				$this->report_args( $dates, $input ),
				array(
					'status' => charitable_get_approval_statuses(),
					'limit'  => self::input_int( $input, 'limit', 10, 1, 50 ),
				)
			);

			$rows = (array) charitable_get_table( 'campaign_donations' )->get_top_campaigns_report( $args );

			$campaigns = array();

			foreach ( $rows as $row ) {
				$money = self::money( $row->total );

				/*
				 * The live post title is preferred over cd.campaign_name, which
				 * is a snapshot taken when the donation was recorded and is stale
				 * for any campaign renamed since. The snapshot is the fallback
				 * for a campaign whose post has since been deleted, where it is
				 * the only surviving name.
				 */
				$title = get_post_field( 'post_title', (int) $row->campaign_id, 'raw' );

				$campaigns[] = array(
					'campaign_id'      => (int) $row->campaign_id,
					'title'            => '' !== (string) $title ? (string) $title : (string) $row->campaign_name,
					'raised'           => $money['raw'],
					'raised_formatted' => $money['formatted'],
					'donation_count'   => (int) $row->donation_count,
				);
			}

			return array(
				'start_date' => $this->reported_start_date( $dates ),
				'end_date'   => $dates['end_date'],
				'currency'   => charitable_get_currency(),
				'campaigns'  => $campaigns,
			);
		}

		/**
		 * Execute charitable/get-top-donors.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_top_donors( $input ) {
			$dates = $this->resolve_ranking_range( $input );

			if ( is_wp_error( $dates ) ) {
				return $dates;
			}

			$args = array_merge(
				$this->report_args( $dates, $input ),
				array(
					'status' => charitable_get_approval_statuses(),
					'limit'  => self::input_int( $input, 'limit', 10, 1, 50 ),
				)
			);

			$rows = (array) charitable_get_table( 'campaign_donations' )->get_top_donors_report( $args );

			$donors = array();

			foreach ( $rows as $row ) {
				$money = self::money( $row->total );

				$name = trim( sprintf( '%s %s', (string) $row->first_name, (string) $row->last_name ) );

				$donors[] = array(
					'donor_id'                => (int) $row->donor_id,
					'name'                    => '' !== $name ? $name : __( 'Unknown donor', 'charitable' ),
					'email'                   => (string) $row->email,
					'total_donated'           => $money['raw'],
					'total_donated_formatted' => $money['formatted'],
					'donation_count'          => (int) $row->donation_count,
				);
			}

			return array(
				'start_date' => $this->reported_start_date( $dates ),
				'end_date'   => $dates['end_date'],
				'currency'   => charitable_get_currency(),
				'donors'     => $donors,
			);
		}

		/**
		 * Execute charitable/get-reports-date-ranges.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public function execute_get_reports_date_ranges() {
			/*
			 * Every date here comes from current_time(), so the ranges are in the
			 * SITE's timezone. That is the reason this ability exists at all: a
			 * client working out "this month" from its own clock can be a day out
			 * either side of the site's, and post_date — the column every report
			 * query filters on — is local time.
			 */
			$today = current_time( 'Y-m-d' );
			$now   = strtotime( $today );

			$definitions = array(
				array(
					'key'        => 'today',
					'label'      => __( 'Today', 'charitable' ),
					'start_date' => $today,
					'end_date'   => $today,
				),
				array(
					'key'        => 'last-7-days',
					'label'      => __( 'Last 7 days', 'charitable' ),
					'start_date' => gmdate( 'Y-m-d', strtotime( '-6 days', $now ) ),
					'end_date'   => $today,
				),
				array(
					'key'        => 'last-30-days',
					'label'      => __( 'Last 30 days', 'charitable' ),
					'start_date' => gmdate( 'Y-m-d', strtotime( '-29 days', $now ) ),
					'end_date'   => $today,
				),
				array(
					'key'        => 'this-month',
					'label'      => __( 'This month', 'charitable' ),
					'start_date' => current_time( 'Y-m-01' ),
					'end_date'   => $today,
				),
				array(
					'key'        => 'last-month',
					'label'      => __( 'Last month', 'charitable' ),
					'start_date' => gmdate( 'Y-m-01', strtotime( 'first day of last month', $now ) ),
					'end_date'   => gmdate( 'Y-m-t', strtotime( 'first day of last month', $now ) ),
				),
				array(
					'key'        => 'this-year',
					'label'      => __( 'This year', 'charitable' ),
					'start_date' => current_time( 'Y-01-01' ),
					'end_date'   => $today,
				),
				array(
					'key'        => 'last-year',
					'label'      => __( 'Last year', 'charitable' ),
					'start_date' => gmdate( 'Y-01-01', strtotime( '-1 year', strtotime( current_time( 'Y-01-01' ) ) ) ),
					'end_date'   => gmdate( 'Y-12-31', strtotime( '-1 year', strtotime( current_time( 'Y-01-01' ) ) ) ),
				),
			);

			$ranges = array();

			foreach ( $definitions as $definition ) {
				$definition['days'] = count(
					$this->day_series( $definition['start_date'], $definition['end_date'] )
				);

				$ranges[] = $definition;
			}

			return array(
				'today'        => $today,
				'timezone'     => (string) wp_timezone_string(),
				'ranges'       => $ranges,
				'future_dates' => $this->future_dates( $now ),
			);
		}

		/**
		 * Forward-looking calendar dates, for deadlines.
		 *
		 * Why this block exists, and why it is deliberately NOT part of
		 * `ranges`: every entry in `ranges` is a report period bounded by
		 * today, which is correct — you cannot report on days that have not
		 * happened. But the ability's description used to tell callers to look
		 * a period up here "whenever a question mentions a period in words",
		 * with nothing scoping that to reports. So a real request — "the
		 * deadline is end of this month" — was answered by reading
		 * `this-month.end_date`, which is TODAY, and passing it to
		 * charitable/create-campaign as end_date. That creates a campaign
		 * which expires the day it was made, and every call in the chain
		 * returns 200 with nothing reporting a problem.
		 *
		 * These entries are single dates, NOT start_date/end_date pairs, and
		 * that shape is load-bearing rather than cosmetic. A forward-looking
		 * entry shaped like a range would be fed straight into the report
		 * abilities — the same silent failure pointing the other way, this
		 * time a report padded with empty future days. Keep them here, keep
		 * them single, and do not "tidy" them into $definitions above.
		 *
		 * Computed from current_time()/$now like the ranges are, so the dates
		 * are the SITE's, not the server's or the caller's.
		 *
		 * @since 1.8.13
		 *
		 * @param  int $now Timestamp of the site's today at midnight, as built
		 *                  by execute_get_reports_date_ranges().
		 * @return array
		 */
		private function future_dates( $now ) {
			/*
			 * 'first day of next month' rather than '+1 month'. On the 31st,
			 * strtotime( '+1 month' ) overflows into the month AFTER next
			 * (31 Jan +1 month = 3 March), so a 'Y-m-t' taken from it returns
			 * the end of the wrong month entirely. Anchoring to the 1st first
			 * removes the overflow.
			 */
			$first_of_next_month = strtotime( 'first day of next month', $now );

			return array(
				'end-of-this-month' => array(
					'date'  => current_time( 'Y-m-t' ),
					'label' => __( 'End of this month', 'charitable' ),
				),
				'end-of-next-month' => array(
					'date'  => gmdate( 'Y-m-t', $first_of_next_month ),
					'label' => __( 'End of next month', 'charitable' ),
				),
				'end-of-this-year'  => array(
					'date'  => current_time( 'Y-12-31' ),
					'label' => __( 'End of this year', 'charitable' ),
				),
			);
		}

		/**
		 * The `start_date` the two ranked abilities return.
		 *
		 * Documented because it is not simply the input echoed back: when no
		 * start_date is supplied the ranking has no lower bound, and the field
		 * reports the date of the site's earliest donation so it stays a real date.
		 * See reported_start_date().
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function ranking_start_date_schema() {
			return array(
				'type'        => 'string',
				'description' => __( 'The start of the period this ranking covers, YYYY-MM-DD. When no start_date was supplied the ranking is over all time and this is the date of the site\'s earliest donation, or today if the site has none. State this period alongside the answer.', 'charitable' ),
			);
		}

		/**
		 * The repeated sub-schema for one `future_dates` entry.
		 *
		 * A single `date`, and no start_date/end_date — see future_dates().
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function future_date_schema() {
			return array(
				'type'       => 'object',
				'properties' => array(
					'date'  => array(
						'type'        => 'string',
						'description' => __( 'A single future calendar date, YYYY-MM-DD, in the site\'s timezone.', 'charitable' ),
					),
					'label' => array( 'type' => 'string' ),
				),
			);
		}

		/**
		 * Build the inclusive list of Y-m-d dates between two dates.
		 *
		 * The charitable_get_days_between_dates() helper is DELIBERATELY not used.
		 * It returns array( 'Y-m-d' => 'M d' ), and iterating the VALUE —
		 * 'Sep 04' — then re-parsing it stamps the CURRENT year on every entry,
		 * so across an N-year range every date collapses onto the same day and
		 * any total accumulated in that loop is multiplied by N. That mistake has
		 * already turned $800 into $20,022 on a reporting surface. Building the
		 * series here from unambiguous Y-m-d strings removes the ambiguity rather
		 * than relying on remembering to iterate keys.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $start_date Start, Y-m-d, inclusive.
		 * @param  string $end_date   End, Y-m-d, inclusive.
		 * @return string[]
		 */
		private function day_series( $start_date, $end_date ) {
			$days = array();

			$current = strtotime( $start_date . ' 00:00:00' );
			$last    = strtotime( $end_date . ' 00:00:00' );

			if ( ! $current || ! $last || $current > $last ) {
				return $days;
			}

			/*
			 * Stepped with gmdate()/strtotime() on a midnight UTC timestamp
			 * rather than a DateTime in the site timezone: '+1 day' across a
			 * daylight-saving boundary can land on the same calendar date twice,
			 * or skip one, which would either duplicate or drop a day of the
			 * series.
			 */
			while ( $current <= $last ) {
				$days[]   = gmdate( 'Y-m-d', $current );
				$current += DAY_IN_SECONDS;
			}

			return $days;
		}

		/**
		 * How many inclusive days a range covers, WITHOUT building them.
		 *
		 * The cap on get-reports-activity has to be decided before
		 * day_series() runs, because the cost being capped is the series
		 * itself. A range the cap exists to refuse is exactly the range that
		 * cannot afford to be built first: "the year 1000 to today" is 375,012
		 * entries and 99.6MB, and the refusal was unreachable on any site whose
		 * memory limit is the usual 128M.
		 *
		 * Deliberately the same arithmetic day_series() steps with — integer
		 * division of the timestamp difference by DAY_IN_SECONDS, inclusive of
		 * both ends — rather than a DateTime::diff(). That is what makes the two
		 * agree BY CONSTRUCTION, including across a daylight-saving boundary,
		 * where the difference between two midnights is not a whole number of
		 * days: day_series() steps in fixed DAY_IN_SECONDS increments and
		 * compares against the same `$last`, so flooring here lands on the same
		 * count it would have produced. A DateTime diff would be calendar-correct
		 * and therefore sometimes one MORE than the series it is meant to
		 * measure, which would admit a range the series cannot afford.
		 * test_the_reported_day_count_is_exact_at_the_cap_boundary() holds the
		 * two together at the only place the difference changes the outcome.
		 *
		 * Returns 0 for an unparseable or inverted range, matching day_series()'s
		 * own empty return, so a caller cannot be refused for length when the
		 * real problem is the dates — those keep their own error codes in
		 * parse_date_range().
		 *
		 * @since 1.8.13
		 *
		 * @param  string $start_date Start, Y-m-d, inclusive.
		 * @param  string $end_date   End, Y-m-d, inclusive.
		 * @return int
		 */
		private function count_days_in_range( $start_date, $end_date ) {
			$first = strtotime( $start_date . ' 00:00:00' );
			$last  = strtotime( $end_date . ' 00:00:00' );

			if ( ! $first || ! $last || $first > $last ) {
				return 0;
			}

			return (int) floor( ( $last - $first ) / DAY_IN_SECONDS ) + 1;
		}

		/**
		 * Resolve the requested range.
		 *
		 * @since 1.8.13
		 *
		 * @param  array       $input         Ability input.
		 * @param  string|null $default_start Start to use when the caller supplies
		 *                                    none. Null means the first of the
		 *                                    current month; an empty string means
		 *                                    NO lower bound at all — see
		 *                                    resolve_ranking_range().
		 * @return array|WP_Error
		 */
		private function resolve_range( $input, $default_start = null ) {
			/*
			 * current_time(), not gmdate(): every report query filters on
			 * post_date, which is the site's LOCAL time. A default range computed
			 * in UTC is compared against local timestamps, and on a site ahead of
			 * UTC that silently drops today's donations — reproduced while
			 * building the donation abilities, where the summary reported 0
			 * raised on a site that had just taken a donation.
			 */
			return self::parse_date_range(
				is_array( $input ) ? $input : array(),
				null === $default_start ? current_time( 'Y-m-01' ) : $default_start,
				current_time( 'Y-m-d' )
			);
		}

		/**
		 * Resolve the range for the two RANKED abilities, which default to all time.
		 *
		 * Why these two differ from get-reports-overview and get-reports-activity,
		 * which keep the current-month default: a TOTAL that echoes its date range
		 * carries its own scope, and "raised in September" is a coherent answer. A
		 * RANKING does not — "your best campaign is X" reads as a fact about the
		 * site, whatever range produced it. On the development site
		 * get-top-campaigns with no dates answered "Las Vegas Poker Night, $100",
		 * a campaign an hour old holding a single cheque, where the all-time answer
		 * was "Sunshine Animal Sanctuary, $15,030". 150x out, and a different
		 * campaign, with nothing in the reply signalling either.
		 *
		 * The default is an EMPTY start, not an early date: get_report_sql_where_clause()
		 * omits the `p.post_date >= %s` clause entirely for an empty start_date, so
		 * all time means no lower bound rather than a guessed sentinel year. What is
		 * ECHOED is filled in by reported_start_date() afterwards, so the query stays
		 * unbounded while the output still names a real date.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		private function resolve_ranking_range( $input ) {
			return $this->resolve_range( $input, '' );
		}

		/**
		 * The start_date the two ranked abilities REPORT for a range with no lower bound.
		 *
		 * Both abilities echo start_date and end_date, and both must stay truthful
		 * now that the default range is open-ended: an empty string would leave a
		 * typed date field blank, and a sentinel year would be a date the site never
		 * saw. So an unbounded range reports the date of the site's earliest
		 * donation, which is the real beginning of the period the answer covers and
		 * something a caller can state back.
		 *
		 * A site with NO donations at all reports TODAY — the same date as end_date.
		 * That path is deliberate rather than undefined: the ranking that comes with
		 * it is empty, and an empty one-day period is a truthful description of it.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $dates Resolved range, as returned by resolve_ranking_range().
		 * @return string Y-m-d.
		 */
		private function reported_start_date( array $dates ) {
			if ( '' !== (string) $dates['start_date'] ) {
				return $dates['start_date'];
			}

			$earliest = $this->earliest_donation_date();

			return '' !== $earliest ? $earliest : $dates['end_date'];
		}

		/**
		 * The date of the site's earliest donation, or '' when it has none.
		 *
		 * EVERY valid status counts, not just the approved ones, so this agrees
		 * with the reporting screen's own All Time preset
		 * (Charitable_Reports::get_earliest_donation_date()): the two must not
		 * disagree about when the site's history starts.
		 *
		 * post_date, and the first ten characters of it rather than a
		 * strtotime()/gmdate() round trip. post_date is already the site's local
		 * wall clock — it is the column every report query filters on — so parsing
		 * it and reformatting through gmdate() can shift the answer by a day on any
		 * site whose PHP timezone is not UTC.
		 *
		 * Deliberately NOT cached, unlike the reporting screen's version. This is
		 * one indexed MIN() on an ability call, and a cached value would leave the
		 * echoed period a day stale on exactly the site that just imported four
		 * years of history.
		 *
		 * @since 1.8.13
		 *
		 * @global wpdb $wpdb
		 *
		 * @return string Y-m-d, or '' when the site has no donations.
		 */
		private function earliest_donation_date() {
			global $wpdb;

			$statuses = array_keys( charitable_get_valid_donation_statuses() );

			/*
			 * `charitable_donation_statuses` is a public filter, and an empty list
			 * would build `IN ( )`. Nothing to measure in that case.
			 */
			if ( empty( $statuses ) ) {
				return '';
			}

			$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

			$earliest = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->prepare(
					"SELECT MIN( post_date ) FROM {$wpdb->posts}
					WHERE post_type = 'donation'
					AND post_status IN ( {$placeholders} )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$statuses
				)
			);

			if ( empty( $earliest ) || '0000-00-00 00:00:00' === $earliest ) {
				return '';
			}

			return substr( (string) $earliest, 0, 10 );
		}

		/**
		 * Build the shared report query arguments from a resolved range.
		 *
		 * The get_report_sql_where_clause() builder compares start_date and end_date
		 * against post_date directly, so the end date is widened to the end of the day.
		 * Without it an end_date of 2026-09-04 excludes everything donated that
		 * day, because '2026-09-04 14:00:00' > '2026-09-04'.
		 *
		 * An EMPTY bound is omitted entirely rather than passed through, which is what
		 * makes the ranked abilities' all-time default unbounded. It also has to be
		 * omitted rather than merely empty-checked downstream: ' 00:00:00' — the
		 * result of concatenating onto an empty date — is not empty, so
		 * get_report_sql_where_clause() would add `p.post_date >= ' 00:00:00'` and
		 * compare a datetime column against a malformed string.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $dates Resolved start_date/end_date. Either may be ''.
		 * @param  array $input Ability input, for the optional campaign filter.
		 * @return array
		 */
		private function report_args( array $dates, $input ) {
			$args = array();

			if ( '' !== (string) $dates['start_date'] ) {
				$args['start_date'] = $dates['start_date'] . ' 00:00:00';
			}

			if ( '' !== (string) $dates['end_date'] ) {
				$args['end_date'] = $dates['end_date'] . ' 23:59:59';
			}

			$campaign_filter = self::input_id( $input, 'campaign_id' );

			if ( $campaign_filter > 0 ) {
				$args['campaign_id'] = $campaign_filter;
			}

			return $args;
		}

		/**
		 * The shared date-range input schema.
		 *
		 * @since 1.8.13
		 *
		 * @param  array   $extra    Optional additional properties.
		 * @param  boolean $all_time Whether this ability's default range is all time
		 *                            rather than the current month. The start_date
		 *                            description has to say which, or the schema
		 *                            contradicts the ability. See
		 *                            resolve_ranking_range().
		 * @return array
		 */
		private static function range_input_schema( array $extra = array(), $all_time = false ) {
			$properties = array(
				'start_date'  => array(
					'type'        => 'string',
					'description' => $all_time
						? __( 'Start of the range, as YYYY-MM-DD, in the site\'s timezone. Omit it to rank over all time — there is then no lower bound, and the start_date returned alongside the answer is the date of the site\'s earliest donation.', 'charitable' )
						: __( 'Start of the range, as YYYY-MM-DD. Defaults to the first of the current month, in the site\'s timezone.', 'charitable' ),
				),
				'end_date'    => array(
					'type'        => 'string',
					'description' => __( 'End of the range, as YYYY-MM-DD. Defaults to today. The whole day is included.', 'charitable' ),
				),
				'campaign_id' => array(
					'type'        => 'integer',
					'description' => __( 'Restrict to donations that include this campaign.', 'charitable' ),
					'minimum'     => 1,
				),
			);

			return array(
				'type'                 => 'object',
				'properties'           => array_merge( $properties, $extra ),
				'additionalProperties' => false,
			);
		}
	}

endif;
