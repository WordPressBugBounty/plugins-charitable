<?php
/**
 * Donation abilities: list, read, manual creation, and period summary.
 *
 * Two constraints shape this file more than anything else.
 *
 * C7 — donor contact details are withheld unless the caller holds
 * `view_charitable_sensitive_data`. `edit_donations` is enough to see that a
 * donation exists and for how much; it is not enough to read the donor's email
 * or address. This mirrors the fix to the donors REST route, which exposed
 * emails to any Contributor.
 *
 * C3 — Pro's version of this file has every money figure come from a query
 * that reads COALESCE( base_amount, amount ), never the raw `amount` column:
 * base_amount is the gift converted into the site's base currency, and on a
 * multi-currency site summing `amount` adds yen to dollars. Lite has no
 * base_amount column and no multi-currency feature (see create_table() in
 * class-charitable-campaign-donations-db.php, which defines only
 * `amount decimal(13,4)`), so every money figure here reads `amount` directly.
 * See get_donations_summary_by_day_range() in that same file for the same
 * substitution and rationale.
 *
 * @package   Charitable/Classes/Charitable_Donation_Abilities
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Abilities' ) ) :

	/**
	 * Charitable_Donation_Abilities
	 *
	 * @since 1.8.13
	 */
	class Charitable_Donation_Abilities extends Charitable_Abilities_Registrar {

		/**
		 * Register the donation abilities.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public function register() {
			$this->register_ability(
				'charitable/list-donations',
				array(
					'label'               => __( 'List Donations', 'charitable' ),
					'description'         => __( "Lists donations newest first, optionally narrowed to one campaign, one donor or one status. Returns each donation's amount, status, date, gateway and the campaigns it was split across. Use to answer questions about recent giving or to find a specific donation before reading it in full. Donor email addresses are included only for users who can view sensitive data.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_list_donations' ),
					'permission_callback' => array( __CLASS__, 'permission_donations' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'campaign_id' => array(
								'type'        => 'integer',
								'description' => __( 'Only donations that include this campaign.', 'charitable' ),
								'minimum'     => 1,
							),
							'donor_id'    => array(
								'type'        => 'integer',
								'description' => __( 'Only donations from this donor. This is a Charitable donor id, not a WordPress user id.', 'charitable' ),
								'minimum'     => 1,
							),
							'status'      => array(
								'type'        => 'string',
								'description' => __( 'Only donations with this status. Omit for every status.', 'charitable' ),
								'enum'        => array_keys( charitable_get_valid_donation_statuses() ),
							),
							'start_date'  => array(
								'type'        => 'string',
								'description' => __( 'Earliest donation date, as YYYY-MM-DD.', 'charitable' ),
							),
							'end_date'    => array(
								'type'        => 'string',
								'description' => __( 'Latest donation date, as YYYY-MM-DD. The whole day is included.', 'charitable' ),
							),
							'per_page'    => array(
								'type'        => 'integer',
								'description' => __( 'Results per page, 1 to 100. Defaults to 20.', 'charitable' ),
								'minimum'     => 1,
								'maximum'     => 100,
							),
							'page'        => array(
								'type'        => 'integer',
								'description' => __( 'Page number, starting at 1.', 'charitable' ),
								'minimum'     => 1,
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'donations' => array(
								'type'  => 'array',
								'items' => self::donation_summary_schema(),
							),
							'total'     => array( 'type' => 'integer' ),
							'pages'     => array( 'type' => 'integer' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-donation',
				array(
					'label'               => __( 'Get Donation', 'charitable' ),
					'description'         => __( 'Returns one donation in full: amount, status, date, gateway, transaction reference, the per-campaign breakdown, and the donor it came from. Use after List Donations when you need the detail of a specific gift. The donor email address is included only for users who can view sensitive data.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_donation' ),

					/*
					 * permission_read_donation(), NOT permission_donations(): this
					 * one takes a donation_id, so it can and must ask whose
					 * donation it is. See that callback's docblock.
					 */
					'permission_callback' => array( __CLASS__, 'permission_read_donation' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'donation_id' => array(
								'type'        => 'integer',
								'description' => __( 'The donation to read.', 'charitable' ),
								'minimum'     => 1,
							),
						),
						'required'             => array( 'donation_id' ),
						'additionalProperties' => false,
					),
					'output_schema'       => self::donation_detail_schema(),
				)
			);

			$this->register_ability(
				'charitable/create-manual-donation',
				array(
					'label'               => __( 'Create Manual Donation', 'charitable' ),
					'description'         => __( 'Records a donation entered by hand, the way an administrator would add one in the dashboard — for a check, cash or bank transfer received outside the site. No payment is processed and no gateway is charged. Use for donations that already happened offline. Do not use it to test the donation form or to correct an existing donation.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_create_manual_donation' ),
					'permission_callback' => array( __CLASS__, 'permission_create_manual_donation' ),
					'annotations'         => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'campaign_id' => array(
								'type'        => 'integer',
								'description' => __( 'The campaign the donation is for.', 'charitable' ),
								'minimum'     => 1,
							),
							'amount'      => array(
								'type'        => array( 'number', 'string' ),
								'description' => __( 'The amount donated, in the site currency. On a currency with no decimal places, supply a whole number.', 'charitable' ),
							),
							'donor'       => array(
								'type'                 => 'object',
								'description'          => __( 'Who gave. An existing donor is matched on email address rather than duplicated.', 'charitable' ),
								'properties'           => array(
									'first_name' => array( 'type' => 'string' ),
									'last_name'  => array( 'type' => 'string' ),
									'email'      => array( 'type' => 'string' ),
								),
								'required'             => array( 'first_name', 'last_name', 'email' ),
								'additionalProperties' => false,
							),
							'status'      => array(
								'type'        => 'string',
								'description' => __( 'Whether the money has been received. Defaults to paid.', 'charitable' ),
								'enum'        => array( 'charitable-completed', 'charitable-pending' ),
							),
							'date'        => array(
								'type'        => 'string',
								'description' => __( 'The date the donation was received, as YYYY-MM-DD. Defaults to today. A future date is refused.', 'charitable' ),
							),
							'note'        => array(
								'type'        => 'string',
								'description' => __( 'An internal note about the donation, such as a check number.', 'charitable' ),
							),
							'send_receipt' => array(
								'type'        => 'boolean',
								'default'     => false,
								'description' => __( 'Email the donor a receipt for this donation. Off by default, so a donation recorded in error does not email the donor.', 'charitable' ),
							),
						),
						'required'             => array( 'campaign_id', 'amount', 'donor' ),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'donation_id'      => array( 'type' => 'integer' ),
							'donor_id'         => array( 'type' => 'integer' ),
							'campaign_id'      => array( 'type' => 'integer' ),
							'amount'           => array( 'type' => 'number' ),
							'amount_formatted' => array( 'type' => 'string' ),
							'status'           => array( 'type' => 'string' ),
							'gateway'          => array( 'type' => 'string' ),
							'date'             => array( 'type' => 'string' ),
							'receipt_sent'     => array(
								'type'        => 'boolean',
								'description' => __( 'Whether a receipt email was sent to the donor.', 'charitable' ),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-donation-summary',
				array(
					'label'               => __( 'Get Donation Summary', 'charitable' ),
					'description'         => __( 'Returns how much was raised over a date range: total, donation count, average gift, and a count of donations in each status. Defaults to the current month. Use to answer "how much have we raised this month?" without listing every donation. For per-campaign or per-day figures use the report abilities instead.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_donation_summary' ),
					'permission_callback' => array( __CLASS__, 'permission_donations' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'start_date'  => array(
								'type'        => 'string',
								'description' => __( 'Start of the range, as YYYY-MM-DD. Defaults to the first of the current month.', 'charitable' ),
							),
							'end_date'    => array(
								'type'        => 'string',
								'description' => __( 'End of the range, as YYYY-MM-DD. Defaults to today. The whole day is included.', 'charitable' ),
							),
							'campaign_id' => array(
								'type'        => 'integer',
								'description' => __( 'Only donations that include this campaign.', 'charitable' ),
								'minimum'     => 1,
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'start_date'        => array( 'type' => 'string' ),
							'end_date'          => array( 'type' => 'string' ),
							'total'             => array(
								'type'        => 'number',
								'description' => __( 'Amount raised, counting only donations in a status the site treats as money received. Does NOT include pending, failed, cancelled or refunded gifts, so this will not match the sum of by_status.', 'charitable' ),
							),
							'total_formatted'   => array( 'type' => 'string' ),
							'count'             => array(
								'type'        => 'integer',
								'description' => __( 'How many donations make up total — again only those in a status the site treats as money received. by_status counts EVERY donation whatever its status, so by_status will usually add up to more than this. When asked how many donations came in, say which of the two the answer uses.', 'charitable' ),
							),
							'average'           => array(
								'type'        => 'number',
								'description' => __( 'total divided by count, so it is the average received gift and excludes pending, failed, cancelled and refunded donations.', 'charitable' ),
							),
							'average_formatted' => array( 'type' => 'string' ),
							'currency'          => array( 'type' => 'string' ),
							'by_status'         => self::status_count_schema(),
						),
					),
				)
			);
		}

		/**
		 * Output schema for the by_status map.
		 *
		 * The keys are enumerated from the live status list rather than left as a
		 * bare `type: object`. Two reasons: the contract suite requires every
		 * object schema to declare its properties, and a client reading the
		 * schema learns which statuses exist on this site — including any a
		 * filter has added — instead of having to infer them from a response.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function status_count_schema() {
			$properties = array();

			foreach ( charitable_get_valid_donation_statuses() as $status => $label ) {
				$properties[ $status ] = array(
					'type'        => 'integer',
					'description' => sprintf(
						/* translators: %s: a donation status label, e.g. "Paid". */
						__( 'Number of donations with the status "%s".', 'charitable' ),
						$label
					),
				);
			}

			return array(
				'type'       => 'object',
				'properties' => $properties,
			);
		}

		/**
		 * Permission callback for create-manual-donation.
		 *
		 * `edit_donations` is the base requirement. Recording a donation for
		 * somebody other than yourself additionally requires
		 * `edit_others_donations`, which is the same split the admin donation
		 * form enforces (class-charitable-admin-donation-form.php:424-443).
		 *
		 * The admin form's response to a user lacking that capability is to
		 * silently REWRITE the donation onto that user's own donor record. An
		 * ability must not do that: quietly attributing a gift to a different
		 * person than the caller named is worse than refusing, because the
		 * caller has no way to see it happened. The email is compared here, at
		 * the permission gate, so the refusal arrives before anything is written.
		 *
		 * @since 1.8.13
		 *
		 * @param  array|null $input Ability input.
		 * @return boolean
		 */
		public static function permission_create_manual_donation( $input = null ) {
			if ( ! current_user_can( 'edit_donations' ) ) {
				return false;
			}

			if ( current_user_can( 'edit_others_donations' ) ) {
				return true;
			}

			$email = '';

			if ( is_array( $input ) && isset( $input['donor']['email'] ) ) {
				$email = strtolower( trim( (string) $input['donor']['email'] ) );
			}

			$current = strtolower( (string) wp_get_current_user()->user_email );

			return '' !== $email && $email === $current;
		}

		/**
		 * Execute charitable/list-donations.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_list_donations( $input ) {
			$per_page = self::input_int( $input, 'per_page', 20, 1, 100 );
			$page     = self::input_int( $input, 'page', 1, 1 );

			/*
			 * Every charitable-* donation status is registered with
			 * `exclude_from_search => true`. WP_Query's default post_status is
			 * `publish`, which no donation ever has, and `'any'` explicitly
			 * EXCLUDES exclude_from_search statuses — so both return zero rows.
			 * Charitable_Donations::query() supplies no post_status default of
			 * its own, so the explicit list has to be passed here. Proven
			 * repeatedly elsewhere in this codebase; see the notes in
			 * charitable-paypal-commerce-hooks.php.
			 */
			$statuses = array_keys( charitable_get_valid_donation_statuses() );

			/*
			 * Read through input_string(): charitable_is_valid_donation_status()
			 * calls array_key_exists(), which THROWS a TypeError on an array or
			 * object key rather than returning false.
			 */
			$requested_status = self::input_string( $input, 'status' );

			if ( '' !== $requested_status ) {
				if ( ! charitable_is_valid_donation_status( $requested_status ) ) {
					return new WP_Error(
						'charitable_invalid_status',
						__( 'That is not a donation status on this site.', 'charitable' )
					);
				}

				$statuses = array( $requested_status );
			}

			$args = array(
				'post_status'    => $statuses,
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			);

			/*
			 * Author-scoped for anyone without `edit_others_donations`, which is
			 * exactly what wp-admin's own donation list shows such a user
			 * (class-charitable-donation-list-table.php:783 applies the same
			 * `author` filter for the same reason). This listing cannot use an
			 * object-level capability — it has no donation_id to check — so the
			 * scope has to be applied to the QUERY instead. Same shape as
			 * execute_list_campaigns() (class-charitable-campaign-abilities.php:1129),
			 * where this defect was already fixed for list-campaigns.
			 *
			 * Without it, a role holding `edit_donations` without
			 * `edit_others_donations` receives every donation on the site: amount,
			 * status, gateway, donor name and the ids needed to address
			 * get-donation. Verified over wp-abilities/v1 before the fix — HTTP
			 * 200, all 98 donations on the audit's test site.
			 *
			 * Invisible on a default install: both stock roles that hold
			 * `edit_donations` (administrator, campaign_manager) also hold
			 * `edit_others_donations`, so neither is scoped by this and neither
			 * sees one donation fewer. That is precisely why it has to be written
			 * rather than assumed.
			 *
			 * DIVERGENCE FROM PRO, deliberate and documented: Pro 1.8.18 (the
			 * pinned source this file was ported from) has the same defect and
			 * should adopt this change so the shared schema does not drift
			 * silently.
			 */
			if ( ! current_user_can( 'edit_others_donations' ) ) {
				$args['author'] = get_current_user_id();
			}

			$dates = self::parse_date_range( $input, '', '' );

			if ( is_wp_error( $dates ) ) {
				return $dates;
			}

			if ( '' !== $dates['start_date'] || '' !== $dates['end_date'] ) {
				$args['date_query'] = array( self::date_query_clause( $dates ) );
			}

			/*
			 * campaign_id and donor_id are columns of the campaign_donations
			 * table, not post meta, so they cannot be expressed as a meta_query.
			 * Resolve them to a donation id list first and constrain the post
			 * query with post__in. An empty list means "no matches" and must
			 * short-circuit: passing an empty post__in to WP_Query is ignored,
			 * which would return every donation instead of none.
			 */
			$restrict_to = null;

			/*
			 * is_array( $input ) guards the array subscript, not a
			 * ! empty()-replaces-0 rewrite: $input itself can be a non-array
			 * (a bare stdClass reaches this method directly, since execute_*
			 * has to be public), and subscripting a non-array throws before
			 * ! empty() ever gets a chance to evaluate it. is_array() has to
			 * come first so PHP short-circuits before the subscript runs.
			 *
			 * ! empty( $input['campaign_id'] ) is kept deliberately, rather
			 * than read through input_id() and tested against 0: a supplied
			 * id that casts to 0 (a non-numeric string such as "abc", or a
			 * decimal like "0.0") must still narrow the query to NOTHING,
			 * not fall through as if campaign_id had never been supplied at
			 * all. An earlier version of this fix read the id through
			 * input_id() first and tested `0 !== $campaign_id`, which is
			 * fail-OPEN for exactly that input: input_id() casts "abc" to 0,
			 * 0 doesn't pass the check, the filter is skipped entirely, and
			 * the query returns every donation instead of none. Caught by
			 * test_list_donations_with_an_unusable_campaign_id_returns_nothing_not_everything().
			 * Not in this task's original list of seven reads — found by the
			 * same hostile-input sweep that found the eighth, in
			 * parse_named_fields()'s description branch.
			 */
			if ( is_array( $input ) && ! empty( $input['campaign_id'] ) ) {
				$campaign_id = self::input_id( $input, 'campaign_id' );
				$restrict_to = array_map(
					'intval',
					(array) charitable_get_table( 'campaign_donations' )->get_donation_ids_for_campaign( $campaign_id )
				);
			}

			if ( is_array( $input ) && ! empty( $input['donor_id'] ) ) {
				$donor_id  = self::input_id( $input, 'donor_id' );
				$by_donor  = charitable_get_table( 'campaign_donations' )->get_donations_by_donor( $donor_id, true );
				$donor_ids = array();

				foreach ( (array) $by_donor as $row ) {
					$donor_ids[] = (int) ( is_object( $row ) ? $row->donation_id : $row );
				}

				$restrict_to = is_null( $restrict_to ) ? $donor_ids : array_intersect( $restrict_to, $donor_ids );
			}

			if ( is_array( $restrict_to ) ) {
				$restrict_to = array_values( array_unique( array_filter( $restrict_to ) ) );

				if ( empty( $restrict_to ) ) {
					return array(
						'donations' => array(),
						'total'     => 0,
						'pages'     => 0,
					);
				}

				$args['post__in'] = $restrict_to;
			}

			$query = Charitable_Donations::query( $args );

			$donation_ids = wp_list_pluck( $query->posts, 'ID' );
			$amounts      = charitable_get_table( 'campaign_donations' )->get_donated_amounts_by_donation( $donation_ids );

			/*
			 * The per-campaign split, batched into ONE query for the page.
			 *
			 * This ability's description has always said it returns "the
			 * campaigns it was split across", and no row carried them — only
			 * get-donation did. So "which campaign did each of these go to?" had
			 * nothing behind it (item 53's shape: a description promising content
			 * the ability does not deliver).
			 *
			 * Batched rather than per row deliberately.
			 * Charitable_Donation::get_campaign_donations() issues its own query
			 * per donation, which is exactly why the amounts above are batched;
			 * satisfying the description with an N+1 would trade one defect for a
			 * slower one.
			 */
			$campaign_rows = charitable_get_table( 'campaign_donations' )->get_campaign_rows_by_donation( $donation_ids );

			$donations = array();

			foreach ( $query->posts as $post ) {
				$batched = isset( $amounts[ $post->ID ] ) ? $amounts[ $post->ID ] : array();

				/*
				 * 0, not null, when the batch has no entry for this donation.
				 * Passing null makes donation_summary() fall back to
				 * Charitable_Donation::get_donor_id(), and that fallback is
				 * provably useless here: the batch was built from these exact
				 * ids, so a missing entry means the donation has no
				 * campaign_donations rows at all — and get_donor_id() resolves
				 * through those same rows, so it returns false and costs a query
				 * to do it. Measured: at per_page=100 over a database holding 18
				 * such orphaned records, the fallback took the ability from 6
				 * queries to 21. The null path is kept for get-donation, which
				 * is a single read with no batch to draw on.
				 */
				$row = $this->donation_summary(
					charitable_get_donation( $post->ID ),
					isset( $batched['amount'] ) ? $batched['amount'] : 0,
					isset( $batched['donor_id'] ) ? $batched['donor_id'] : 0
				);

				$row['campaigns'] = $this->campaign_split(
					isset( $campaign_rows[ $post->ID ] ) ? $campaign_rows[ $post->ID ] : array()
				);

				$donations[] = $row;
			}

			return array(
				'donations' => $donations,
				'total'     => (int) $query->found_posts,
				'pages'     => (int) $query->max_num_pages,
			);
		}

		/**
		 * Execute charitable/get-donation.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_donation( $input ) {
			$donation = $this->find_donation( self::input_id( $input, 'donation_id' ) );

			if ( is_wp_error( $donation ) ) {
				return $donation;
			}

			/* include_address: true — a single read, so no per-row cost and no bulk-export concern. */
			$summary = $this->donation_summary( $donation, $donation->get_total_donation_amount(), null, true );

			$campaigns = array();

			foreach ( (array) $donation->get_campaign_donations() as $campaign_donation ) {
				/*
				 * Reads cd.amount directly rather than Pro's
				 * isset( base_amount ) fallback: base_amount belongs to the
				 * multi-currency schema and does not exist on this table in
				 * Lite (see get_donations_summary_by_day_range() in
				 * class-charitable-campaign-donations-db.php for the same
				 * substitution and rationale).
				 */
				$campaign_amount = $campaign_donation->amount;

				$money = self::money( $campaign_amount );

				$campaigns[] = array(
					'campaign_id'      => (int) $campaign_donation->campaign_id,
					'campaign_name'    => (string) $campaign_donation->campaign_name,
					'amount'           => $money['raw'],
					'amount_formatted' => $money['formatted'],
				);
			}

			return array_merge(
				$summary,
				array(
					'campaigns'      => $campaigns,
					'note'           => (string) $donation->get_notes(),
					'transaction_id' => (string) $donation->get_gateway_transaction_id(),
					'test_mode'      => (bool) $donation->get_test_mode( false ),
				)
			);
		}

		/**
		 * Execute charitable/create-manual-donation.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_create_manual_donation( $input ) {
			$ability = 'charitable/create-manual-donation';

			$campaign_id = self::input_id( $input, 'campaign_id' );
			$campaign    = get_post( $campaign_id );

			if ( ! $campaign || 'campaign' !== $campaign->post_type ) {
				$error = new WP_Error(
					'charitable_campaign_not_found',
					__( 'No campaign with that id exists. Use List Campaigns to find the right one.', 'charitable' )
				);

				self::log_failure( $ability, $error );

				return $error;
			}

			if ( in_array( $campaign->post_status, array( 'trash', 'auto-draft' ), true ) ) {
				$error = new WP_Error(
					'charitable_campaign_not_available',
					__( 'That campaign is in the trash, so a donation cannot be recorded against it. Restore the campaign first.', 'charitable' )
				);

				self::log_failure( $ability, $error );

				return $error;
			}

			$parsed = self::parse_money( isset( $input['amount'] ) ? $input['amount'] : null );

			if ( is_wp_error( $parsed ) ) {
				self::log_failure( $ability, $parsed );

				return $parsed;
			}

			if ( $parsed <= 0 ) {
				$error = new WP_Error(
					'charitable_invalid_amount',
					__( 'The amount must be greater than zero.', 'charitable' )
				);

				self::log_failure( $ability, $error );

				return $error;
			}

			$email = isset( $input['donor']['email'] ) ? sanitize_email( (string) $input['donor']['email'] ) : '';

			if ( ! is_email( $email ) ) {
				$error = new WP_Error(
					'charitable_invalid_donor',
					__( 'A valid donor email address is required.', 'charitable' )
				);

				self::log_failure( $ability, $error );

				return $error;
			}

			$date = $this->parse_donation_date( self::input_string( $input, 'date' ) );

			if ( is_wp_error( $date ) ) {
				self::log_failure( $ability, $date );

				return $date;
			}

			$status = self::input_string( $input, 'status', 'charitable-completed' );

			if ( ! in_array( $status, array( 'charitable-completed', 'charitable-pending' ), true ) ) {
				$error = new WP_Error(
					'charitable_invalid_status',
					__( 'A manual donation can only be recorded as paid or pending.', 'charitable' )
				);

				self::log_failure( $ability, $error );

				return $error;
			}

			$values = array(

				/*
				 * `user_id => 0` is load-bearing, not defensive.
				 * Charitable_Donation_Processor::get_donor_id() falls through to
				 * `new Charitable_User( get_donation_data_value( 'user_id',
				 * get_current_user_id() ) )` and then calls add_donor(), which
				 * stores `user_id => $this->ID`. Omitting the key therefore binds
				 * a brand-new donor record to the WordPress account of whoever is
				 * driving the ability — the administrator, not the donor. The
				 * admin donation form passes the same explicit 0
				 * (class-charitable-admin-donation-form.php:410), and every
				 * `manual` donation row in the dev database has post_author 0,
				 * which confirms that is the real behavior rather than a guess.
				 */
				'user_id'   => 0,

				/*
				 * The key is `gateway`. save_donation_meta() (line 747) reads
				 * `get_donation_data_value( 'gateway' )`; the admin form's
				 * `donation_gateway` key holds a TRANSLATED "Manual" label under a
				 * different key that the create path never reads, and the value
				 * only lands as `manual` because
				 * charitable_sanitize_donation_meta() defaults an empty gateway.
				 * Pass the lowercase slug explicitly rather than relying on that
				 * default. `manual` is NOT `offline` — offline is a donor-facing
				 * gateway a site can enable, and conflating the two misreports
				 * how the money arrived.
				 */
				'gateway'   => 'manual',
				'status'    => $status,
				'date_gmt'  => $date['date_gmt'],
				'note'      => sanitize_textarea_field( self::input_string( $input, 'note' ) ),
				'campaigns' => array(
					array(
						'campaign_id'   => $campaign_id,
						'campaign_name' => $campaign->post_title,
						'amount'        => $parsed,
					),
				),
				'user'      => array(
					'first_name' => isset( $input['donor']['first_name'] ) ? sanitize_text_field( (string) $input['donor']['first_name'] ) : '',
					'last_name'  => isset( $input['donor']['last_name'] ) ? sanitize_text_field( (string) $input['donor']['last_name'] ) : '',
					'email'      => $email,
				),
			);

			$send_receipt = ! empty( self::input_array( $input )['send_receipt'] );

			/*
			 * Receipts are OFF unless asked for.
			 *
			 * save_donation() fires charitable_after_save_donation, which sends
			 * the donor receipt and the admin notification. An assistant that
			 * mis-reads a figure would otherwise email a stranger a donation
			 * receipt in the organisation's name - a document with tax
			 * significance in many jurisdictions. Pro sends by default; Lite
			 * does not, because an AI client is a likelier source of a wrong
			 * donation than a human on the Add Donation screen.
			 *
			 * Removed in the finally block below, not at the end of the
			 * request, so an exception thrown between the add and the remove
			 * cannot leave receipts suppressed for the rest of the request -
			 * including real donations from real donors made later in the
			 * same request.
			 *
			 * @since 1.8.13
			 */
			$suppress = static function () {
				return false;
			};

			if ( ! $send_receipt ) {
				add_filter( 'charitable_send_donation_receipt', $suppress, 999 );
			}

			try {
				$donation_id = charitable_create_donation( $values );
			} finally {
				if ( ! $send_receipt ) {
					remove_filter( 'charitable_send_donation_receipt', $suppress, 999 );
				}
			}

			/*
			 * save_donation() returns the integer 0 on every failure path rather
			 * than a WP_Error, and pushes its reason into charitable_get_notices()
			 * instead of returning it. So 0 is the only signal available here.
			 */
			if ( ! $donation_id ) {
				$error = new WP_Error(
					'charitable_donation_not_created',
					__( 'The donation could not be recorded. Check the campaign and donor details and try again.', 'charitable' )
				);

				self::log_failure( $ability, $error );

				return $error;
			}

			$donation = charitable_get_donation( $donation_id );
			$money    = self::money( $donation->get_total_donation_amount() );

			self::log_write(
				$ability,
				sprintf(
					/* translators: 1: formatted donation amount, 2: campaign title. */
					__( 'Recorded a manual donation of %1$s to "%2$s".', 'charitable' ),
					$money['formatted'],
					$campaign->post_title
				),
				array(
					'donation_id' => $donation_id,
					'campaign_id' => $campaign_id,
					'donor_id'    => (int) $donation->get_donor_id(),
					'object_id'   => $donation_id,
				)
			);

			return array(
				'donation_id'      => (int) $donation_id,
				'donor_id'         => (int) $donation->get_donor_id(),
				'campaign_id'      => $campaign_id,
				'amount'           => $money['raw'],
				'amount_formatted' => $money['formatted'],
				'status'           => $status,
				'gateway'          => 'manual',
				'date'             => $donation->get_date( 'Y-m-d' ),
				'receipt_sent'     => $send_receipt,
			);
		}

		/**
		 * Execute charitable/get-donation-summary.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_donation_summary( $input ) {
			/*
			 * current_time(), NOT gmdate(). Both the day-range accessor and
			 * WP_Query's date_query compare against `post_date`, which is the
			 * site's LOCAL time, so a range computed in UTC is compared against
			 * local timestamps. Reproduced: on a site 9.5 hours ahead of UTC a
			 * donation recorded "now" carries post_date 2026-09-05 while
			 * gmdate( 'Y-m-d' ) is still 2026-09-04, and the default
			 * "this month to today" range silently excluded it — the summary
			 * reported 0 raised on a site that had just taken a donation.
			 */
			$dates = self::parse_date_range( $input, current_time( 'Y-m-01' ), current_time( 'Y-m-d' ) );

			if ( is_wp_error( $dates ) ) {
				return $dates;
			}

			$table = charitable_get_table( 'campaign_donations' );

			/*
			 * One query shape for both the filtered and unfiltered case, and all
			 * of them reusing get_report_sql_where_clause(). The earlier version
			 * branched — day-range accessor when no campaign was given,
			 * get_amount_report() when one was — and paid for the branch by
			 * counting donations with an UNBOUNDED get_posts() per status:
			 * `posts_per_page => -1` six times over. On a site with a few hundred
			 * thousand donations in range that loads every id into PHP just to
			 * call count() on it.
			 *
			 * Taking the total and the count from the same $args also makes them
			 * coherent by construction, so `average = total / count` cannot
			 * disagree with itself.
			 */
			$args = array(
				'start_date' => $dates['start_date'] . ' 00:00:00',
				'end_date'   => $dates['end_date'] . ' 23:59:59',
			);

			$campaign_filter = self::input_id( $input, 'campaign_id' );

			if ( $campaign_filter > 0 ) {
				$args['campaign_id'] = $campaign_filter;
			}

			/*
			 * Author-scoped for anyone without `edit_others_donations`, the same
			 * rule and the same reason as execute_list_donations() above and as
			 * wp-admin's own donation list
			 * (class-charitable-donation-list-table.php:783). A summary has no
			 * donation_id either, so again the scope goes on the QUERY.
			 *
			 * Set on $args BEFORE $approved is derived from it, so the total, the
			 * count and the per-status breakdown are all scoped the same way and
			 * `average = total / count` stays coherent. A summary scoped for the
			 * total but not the count would report an average no donation
			 * resembles.
			 *
			 * `author` is honoured by get_report_sql_where_clause() as
			 * `p.post_author = %d`. Both stock roles holding `edit_donations` also
			 * hold `edit_others_donations`, so neither is scoped by this.
			 *
			 * DIVERGENCE FROM PRO, deliberate and documented: Pro 1.8.18 has the
			 * same defect and should adopt this change.
			 */
			if ( ! current_user_can( 'edit_others_donations' ) ) {
				$args['author'] = get_current_user_id();
			}

			$approved = array_merge( $args, array( 'status' => charitable_get_approval_statuses() ) );

			$total = (float) $table->get_amount_report( $approved, false );
			$count = $table->count_donations_report( $approved );

			/* One grouped query for every status, rather than one query each. */
			$counts_by_status = $table->count_donations_by_status_report( $args );
			$by_status        = array();

			foreach ( array_keys( charitable_get_valid_donation_statuses() ) as $status ) {
				$by_status[ $status ] = isset( $counts_by_status[ $status ] ) ? (int) $counts_by_status[ $status ] : 0;
			}

			$average = $count > 0 ? $total / $count : 0.0;

			$total_money   = self::money( $total );
			$average_money = self::money( $average );

			return array(
				'start_date'        => $dates['start_date'],
				'end_date'          => $dates['end_date'],
				'total'             => $total_money['raw'],
				'total_formatted'   => $total_money['formatted'],
				'count'             => $count,
				'average'           => $average_money['raw'],
				'average_formatted' => $average_money['formatted'],
				'currency'          => charitable_get_currency(),
				'by_status'         => $by_status,
			);
		}

		/**
		 * Build one donation's summary row.
		 *
		 * The amount is supplied by the caller rather than read here, so a list
		 * can batch it into a single query.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Donation $donation        The donation.
		 * @param  mixed               $amount          Pre-resolved converted total.
		 * @param  int|null            $donor_id        Pre-resolved donor id. Null makes this
		 *                                               read it from the donation, which costs a
		 *                                               query -- pass it when a batch already has it.
		 * @param  boolean             $include_address Whether to include the postal address.
		 *                                              Single reads only; see the note below.
		 * @return array
		 */
		private function donation_summary( $donation, $amount, $donor_id = null, $include_address = false ) {
			/*
			 * self::money() rather than $donation->get_amount_formatted(). That
			 * method formats with $donation->get_currency() — the DONOR's
			 * currency — while the amount above is the gift converted into the
			 * site's base currency. On a multi-currency site that pairing prints
			 * a converted figure under the wrong symbol.
			 */
			$money = self::money( $amount );

			/*
			 * The donor id is supplied by the caller when it already has it, so a
			 * list can batch it. Charitable_Donation::get_donor_id() resolves it
			 * through get_campaign_donations(), which issues its own query per
			 * donation — 20 extra queries for a page of 20, to learn something
			 * the batched amount query returned in the same row.
			 */
			if ( is_null( $donor_id ) ) {
				$donor_id = (int) $donation->get_donor_id();
			}

			$row = array(
				'donation_id'      => (int) $donation->get_donation_id(),
				'number'           => (string) $donation->get_number(),
				'date'             => $donation->get_date( 'Y-m-d' ),
				'status'           => $donation->get_status(),
				'status_label'     => $donation->get_status_label(),
				'gateway'          => (string) $donation->get_gateway(),
				'gateway_label'    => (string) $donation->get_gateway_label(),
				'amount'           => $money['raw'],
				'amount_formatted' => $money['formatted'],
				'currency'         => charitable_get_currency(),
				'donor'            => array(
					'donor_id' => (int) $donor_id,
					'name'     => $this->donor_display_name( $donation ),
				),
			);

			/*
			 * C7. `edit_donations` is enough to see that a donation exists and for
			 * how much; the donor's contact details are not part of that. The
			 * filed precedent is the donors REST route, which exposed emails to
			 * any Contributor. Both stock Charitable roles bundle
			 * view_charitable_sensitive_data with edit_donations, so this gate is
			 * invisible on a default install and only bites on a role-editor
			 * setup — which is exactly why it must be written rather than assumed.
			 */
			if ( self::permission_sensitive() ) {
				$donor_data = (array) $donation->get_donor_data();

				$row['donor']['email'] = isset( $donor_data['email'] ) ? (string) $donor_data['email'] : '';

				/*
				 * The postal address is returned by the single-donation read
				 * only, never by a list, and for two reasons that point the same
				 * way. Privacy: a page of addresses turns one permitted read
				 * into a bulk export of donor addresses, which is the same
				 * reasoning that keeps all contact detail out of list-donors.
				 * Cost: get_donor_address() constructs a Charitable_Donor per
				 * row — measured at 2 queries each, 40 of the 76 queries a
				 * 20-row page used to issue.
				 */
				if ( $include_address ) {
					$row['donor']['address'] = $donation->get_donor_address();
				}
			}

			return $row;
		}

		/**
		 * Shape a batch of campaign_donations rows into the reported split.
		 *
		 * Shared so a list row and get-donation's single read describe a campaign
		 * split identically — the money is formatted through self::money() in both,
		 * which is what keeps a converted amount under the site's own symbol
		 * rather than the donor's (C3).
		 *
		 * @since 1.8.13
		 *
		 * @param  array $rows Rows from get_campaign_rows_by_donation().
		 * @return array
		 */
		private function campaign_split( array $rows ) {
			$campaigns = array();

			foreach ( $rows as $row ) {
				$money = self::money( isset( $row['amount'] ) ? $row['amount'] : 0 );

				$campaigns[] = array(
					'campaign_id'      => isset( $row['campaign_id'] ) ? (int) $row['campaign_id'] : 0,
					'campaign_name'    => isset( $row['campaign_name'] ) ? (string) $row['campaign_name'] : '',
					'amount'           => $money['raw'],
					'amount_formatted' => $money['formatted'],
				);
			}

			return $campaigns;
		}

		/**
		 * The donor's display name, without reaching for contact details.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Donation $donation The donation.
		 * @return string
		 */
		private function donor_display_name( $donation ) {
			$donor_data = (array) $donation->get_donor_data();

			$name = trim(
				sprintf(
					'%s %s',
					isset( $donor_data['first_name'] ) ? $donor_data['first_name'] : '',
					isset( $donor_data['last_name'] ) ? $donor_data['last_name'] : ''
				)
			);

			return '' !== $name ? $name : __( 'Unknown donor', 'charitable' );
		}

		/**
		 * Find a donation, or return a scrubbed WP_Error.
		 *
		 * Charitable_get_donation() constructs an object for any post id, so the
		 * post type has to be checked separately — otherwise a campaign id passed
		 * where a donation id belongs reads back as an empty donation rather than
		 * an error.
		 *
		 * @since 1.8.13
		 *
		 * @param  int $donation_id The donation id.
		 * @return Charitable_Donation|WP_Error
		 */
		private function find_donation( $donation_id ) {
			$post = $donation_id > 0 ? get_post( $donation_id ) : null;

			if ( ! $post || 'donation' !== $post->post_type ) {
				return new WP_Error(
					'charitable_donation_not_found',
					__( 'No donation with that id exists. Use List Donations to find the right one.', 'charitable' )
				);
			}

			$donation = charitable_get_donation( $donation_id );

			if ( ! $donation ) {
				return new WP_Error(
					'charitable_donation_not_found',
					__( 'No donation with that id exists. Use List Donations to find the right one.', 'charitable' )
				);
			}

			return $donation;
		}

		/**
		 * Parse and validate a donation date.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $value A date as YYYY-MM-DD, or empty for now.
		 * @return array|WP_Error
		 */
		private function parse_donation_date( $value ) {
			$value = trim( (string) $value );

			if ( '' === $value ) {
				return array( 'date_gmt' => current_time( 'mysql', true ) );
			}

			$parsed = self::validate_ymd( $value );

			if ( is_wp_error( $parsed ) ) {
				return $parsed;
			}

			/*
			 * A future donation date is refused rather than accepted. Charitable's
			 * reports read post_date, so a gift dated next month is invisible in
			 * this month's totals and then appears retroactively — which reads as
			 * data loss rather than a date entry mistake.
			 *
			 * Compared against current_time(), not gmdate(): the supplied date is
			 * a date in the site's own calendar, and on a site ahead of UTC
			 * "today" locally is already tomorrow in UTC, so a UTC comparison
			 * refuses a perfectly ordinary same-day entry.
			 */
			if ( $parsed > current_time( 'Y-m-d' ) ) {
				return new WP_Error(
					'charitable_invalid_date',
					__( 'A donation cannot be dated in the future.', 'charitable' )
				);
			}

			/*
			 * The caller names a LOCAL date; the processor wants `date_gmt` and
			 * derives post_date from it with get_date_from_gmt(). Converting local
			 * noon rather than passing the bare date as if it were GMT keeps the
			 * donation on the day the caller asked for: storing "2026-09-04
			 * 00:00:00" as GMT lands on 2026-09-03 locally anywhere behind UTC.
			 * Noon is far enough from both boundaries for every real offset
			 * (-12 to +14).
			 */
			return array( 'date_gmt' => get_gmt_from_date( $parsed . ' 12:00:00' ) );
		}

		/**
		 * Shared output schema for a donation list row.
		 *
		 * PUBLIC because charitable/get-donor-donations reports these same rows —
		 * execute_get_donor_donations() delegates straight to
		 * execute_list_donations() — and it declared only five of the eleven keys
		 * it actually returned. Reusing this is what stops the two drifting.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public static function donation_summary_schema() {
			return array(
				'type'       => 'object',
				'properties' => array(
					'donation_id'      => array( 'type' => 'integer' ),
					'number'           => array( 'type' => 'string' ),
					'date'             => array( 'type' => 'string' ),
					'status'           => array( 'type' => 'string' ),
					'status_label'     => array( 'type' => 'string' ),
					'gateway'          => array( 'type' => 'string' ),
					'gateway_label'    => array( 'type' => 'string' ),
					'amount'           => array( 'type' => 'number' ),
					'amount_formatted' => array( 'type' => 'string' ),
					'currency'         => array( 'type' => 'string' ),
					'donor'            => array(
						'type'       => 'object',
						'properties' => array(
							'donor_id' => array( 'type' => 'integer' ),
							'name'     => array( 'type' => 'string' ),
							'email'    => array(
								'type'        => 'string',
								'description' => __( 'Present only for users who can view sensitive data.', 'charitable' ),
							),
						),
					),
					'campaigns'        => self::campaign_split_schema(),
				),
			);
		}

		/**
		 * Output schema for a donation's per-campaign split.
		 *
		 * Declared once and used by both the list row and the single read. A
		 * client reads the output schema to decide what it can answer, so a key
		 * the ability returns but does not declare is a key the model does not
		 * know it has.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function campaign_split_schema() {
			return array(
				'type'        => 'array',
				'description' => __( 'The campaigns this donation was split across, with the amount each received. A donation to a single campaign has one entry.', 'charitable' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'campaign_id'      => array( 'type' => 'integer' ),
						'campaign_name'    => array( 'type' => 'string' ),
						'amount'           => array( 'type' => 'number' ),
						'amount_formatted' => array( 'type' => 'string' ),
					),
				),
			);
		}

		/**
		 * Output schema for a single donation read.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function donation_detail_schema() {
			$schema = self::donation_summary_schema();

			/* `campaigns` now comes from the summary schema — both surfaces report it. */

			/*
			 * The address is added to the DETAIL schema only. The list row does not
			 * carry one: a page of addresses is a bulk export, and resolving each
			 * costs its own pair of queries.
			 */
			$schema['properties']['donor']['properties']['address'] = array(
				'type'        => 'string',
				'description' => __( 'Present only for users who can view sensitive data.', 'charitable' ),
			);

			$schema['properties']['note']           = array( 'type' => 'string' );
			$schema['properties']['transaction_id'] = array( 'type' => 'string' );
			$schema['properties']['test_mode']      = array( 'type' => 'boolean' );

			return $schema;
		}
	}

endif;
