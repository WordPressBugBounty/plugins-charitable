<?php
/**
 * Charitable Reports UI.
 *
 * @package   Charitable/Classes/Charitable_Reports
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Reports' ) ) :

	/**
	 * Charitable_Reports
	 *
	 * @final
	 * @since 1.8.1
	 */
	class Charitable_Reports {

		/**
		 * The longest range a previous-period comparison will be attempted for, in days.
		 *
		 * A comparison means building a SECOND overview report, and this page has a long-range
		 * timeout history. Doubling that work is exactly the shape of the original fault, so past
		 * this many days no comparison is attempted and the trend arrows simply do not render.
		 * A missing arrow costs a nicety; a timeout costs the page.
		 *
		 * @since 1.8.14
		 *
		 * @var   int
		 */
		const COMPARISON_MAX_DAYS = 92;

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Reports|null
		 */
		private static $instance = null;

		/**
		 * How many breakdown rows were marked as having no activity at all.
		 *
		 * Set by generate_donations_breakdown_rows() as it renders, and read straight back by
		 * get_breakdown_quiet_note_html() - so the "N weeks with no donations are hidden" count
		 * cannot disagree with the number of rows that were actually marked.
		 *
		 * @since 1.8.14
		 *
		 * @var   int
		 */
		public $breakdown_empty_days = 0;

		/**
		 * The previous period a comparison was actually measured against, formatted for display.
		 *
		 * Written by get_previous_period_comparison(), which is the only place that knows the
		 * window, and read by get_headline_tiles() for the "vs. Jul 25 - Aug 23" captions. A
		 * percentage with no period named is the thing that genuinely misleads.
		 *
		 * @since 1.8.14
		 *
		 * @var   string
		 */
		public $comparison_range_label = '';

		/**
		 * The date filter values.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $date_filter_values = array();

		/**
		 * The report type.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $report_type = false;

		/**
		 * The start date.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $start_date = false;

		/**
		 * The end date.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $end_date = false;

		/**
		 * The key filter for the report.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $filter = false;

		/**
		 * The (post) status filter for the report, usually reflecting post_status.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $status = false;

		/**
		 * The campaign ID filter for the report.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $campaign_id = false;

		/**
		 * The Charitable custom tax category ID filter for the report.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $category_id = false;

		/**
		 * The donation data axis for the highlight report.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donation_axis = array();

		/**
		 * The date axis for the highlight report.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $date_axis = array();

		/**
		 * The donation data.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donations = array();

		/**
		 * The donor data.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donors = array();

		/**
		 * The donor total for the query.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donors_total = 0;

		/**
		 * The payment percentages data for the payment report/graph.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $payment_percentages = array( '100' );

		/**
		 * The payment percentages labels for the payment report/graph.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $payment_labels = array( 'No donations found' );

		/**
		 * The payment gateway keys for the payment report/graph (used for color mapping).
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $payment_keys = array();

		/**
		 * The activities data.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $activities = array();

		/**
		 * The dot ... threshold for the pagination.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $pagination_dot_threshold = 5;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.1
		 */
		public function __construct() {
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $status The status.
		 * @param  int    $campaign_id The campaign ID.
		 * @param  int    $category_id The category ID.
		 *
		 * @return void
		 */
		public function init( $report_type = 'overview', $start_date = false, $end_date = false, $status = false, $campaign_id = false, $category_id = false ) {

			do_action( 'charitable_admin_reports_init_start', $this );

			// Get the date filter values.
			$this->date_filter_values = apply_filters(
				'charitable_dashboard_date_filter_values',
				array(
					'7'  => __( 'Last 7 Days', 'charitable' ),
					'14' => __( 'Last 14 Days', 'charitable' ),
					'30' => __( 'Last 30 Days', 'charitable' ),
					'0'  => __( 'Custom Date Range', 'charitable' ),
				)
			);

			$this->report_type = $report_type;
			$this->start_date  = $start_date;
			$this->end_date    = $end_date;
			$this->status      = $status;
			$this->campaign_id = $campaign_id;
			$this->category_id = $category_id;

			// Customize how we grab data from the database based on the report type.
			if ( $this->is_report_type_donor( $report_type ) ) {
				$this->get_donors_data( $report_type, $start_date, $end_date, $campaign_id, $category_id );
			} else {
				$this->get_data( $report_type, $start_date, $end_date, $status, $campaign_id, $category_id );
			}

			$this->maybe_load_scripts();
			$this->maybe_add_reports_cta();

			do_action( 'charitable_admin_reports_init_end', $this );
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $args The report args.
		 *
		 * @return void
		 */
		public function init_with_array( $report_type = 'overview', $args = array() ) {

			do_action( 'charitable_admin_reports_init_start', $this );

			// Get the date filter values.
			$this->date_filter_values = apply_filters(
				'charitable_dashboard_date_filter_values',
				array(
					'7'  => __( 'Last 7 Days', 'charitable' ),
					'14' => __( 'Last 14 Days', 'charitable' ),
					'30' => __( 'Last 30 Days', 'charitable' ),
					'0'  => __( 'Custom Date Range', 'charitable' ),
				)
			);

			$defaults = array(
				'report_type' => 'overview',
				'start_date'  => false,
				'end_date'    => false,
				'status'      => false,
				'campaign_id' => false,
				'category_id' => false,
				'limit'       => false,
				'offset'      => false,
				'ppage'       => false,
			);

			$args = wp_parse_args( $args, $defaults );

			$this->report_type = $args['report_type'];
			$this->start_date  = $args['start_date'];
			$this->end_date    = $args['end_date'];
			$this->status      = $args['status'];
			$this->campaign_id = $args['campaign_id'];
			$this->category_id = $args['category_id'];

			// Customize how we grab data from the database based on the report type.
			if ( $this->is_report_type_donor( $report_type ) ) {
				// override the report type in $args with what was passed in.
				$args['report_type'] = $report_type;

				$this->get_donors_data_array( $report_type, $args );
			} else {
				$this->get_data( $report_type, $args );
			}

			$this->maybe_load_scripts();
			$this->maybe_add_reports_cta();

			do_action( 'charitable_admin_reports_init_end', $this );
		}

		/**
		 * Generate HTML for the Top Campaigns list.
		 *
		 * @since  1.8.1
		 * @version 1.8.3 Make status translatable.
		 *
		 * @param  object $top_campaigns The top campaigns data.
		 * @param  bool   $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_top_campaigns( $top_campaigns = false, $include_icons = true, $args = array() ) {

			/*
			WHY THE LAYOUT IS A PARAMETER AND NOT A GATE CHECK.

			This method has four callers and only two of them want the reworked markup: the Reports
			Overview view and the AJAX filter that refreshes it. The other two - the legacy
			Dashboard card and the printed/exported report - supply a <ul> and must keep getting
			<li> elements. Reading charitable_reports_v3_enabled() directly here would emit table
			rows into both of those, because the gate is true for the whole admin request.

			Deciding it from the request context instead was tried and rejected: get_current_screen()
			is the Reports screen during the print POST too (it is set before admin_init, and the
			form posts back to this page), so a screen test would break the print report, while an
			is-AJAX test would have to enumerate action names to tell the reports filter from any
			other AJAX call.

			So the two callers that must stay classic say so, and 'auto' means "ask the gate".

			@since 1.8.14
			*/
			$args = wp_parse_args(
				(array) $args,
				array(
					'layout' => 'auto',
				)
			);

			$charitable_v3_campaigns = 'v3' === $args['layout']
				|| (
					'auto' === $args['layout']
					&& function_exists( 'charitable_reports_v3_enabled' )
					&& charitable_reports_v3_enabled()
				);

			if ( empty( $top_campaigns ) || empty( $top_campaigns->posts ) ) {
				return;
			}

			ob_start();

			foreach ( $top_campaigns->posts as $campaign ) :

				$_campaign       = $campaign->ID ? charitable_get_campaign( $campaign->ID ) : false;

				/*
				A missing campaign is skipped rather than rendered. charitable_get_campaign()
				returns false when the post has gone, and every accessor below was being called on
				the result unguarded - so one deleted campaign still listed in the report cache
				took the whole page down with a fatal on get_donated_amount().
				*/
				if ( ! $_campaign ) {
					continue;
				}

				$status          = $_campaign->get_status();
				switch ( $status ) {
					case 'active':
						$status_string = esc_html__( 'Active', 'charitable' );
						break;
					case 'draft':
						$status_string = esc_html__( 'Draft', 'charitable' );
						break;
					case 'completed':
						$status_string = esc_html__( 'Completed', 'charitable' );
						break;
					case 'failed':
						$status_string = esc_html__( 'Failed', 'charitable' );
						break;
					default:
						$status_string = ucwords( $status );
						break;
				}
				$donated_amount  = $_campaign->get_donated_amount();
				$donor_count     = $_campaign->get_donor_count();
				$donation_goal   = $_campaign->get_goal();
				$time_ended      = $_campaign->get_time_since_ended();
				$end_date        = $_campaign->get_end_date();
				$percent_donated = $_campaign->get_percent_donated_raw();
				if ( $percent_donated > 100 ) {
					$percent_donated = 100;
				}

				/*
				Goal pacing. v3 only.

				The card already ranked campaigns by what they raised, which says what has worked.
				Pacing says where to act: progress against goal measured against progress through
				time, so 39% with days left reads as "Behind" rather than as a number.

				It assumes giving accrues evenly, which it does not - campaigns spike at the
				deadline. That assumption is what every pacing measure makes, and it is why the
				label says "Behind" rather than a prediction. A campaign with no deadline gets NO
				pacing claim at all rather than a flattering one: is_endless() means there is
				nothing to be behind against.

				get_percent_donated_raw() is read again here rather than using $percent_donated
				above, which has already been clamped to 100 - the clamp is right for a bar's width
				and wrong for a figure, where 108% is the interesting part.

				All four reset per iteration, not just the first. foreach does not scope its body,
				so a campaign with no goal following one that has a goal would otherwise render the
				PREVIOUS campaign's percentage and days-left as its own - the values would simply
				still be set from the last pass.

				The four colours match assets/css/admin/charitable-admin-reports-v3.css, which is
				where the rest of this card's palette lives; canon brand green is #31714c, which is
				not what a four-step good/warn/bad ladder wants.

				@since 1.8.14
				*/
				$charitable_pace        = false;
				$charitable_pace_pct    = null;
				$charitable_pace_left   = '';
				$charitable_pace_colour = '#5aa152';

				if ( $charitable_v3_campaigns && $_campaign->has_goal() ) {

					$charitable_raw_pct = (float) $_campaign->get_percent_donated_raw();

					if ( $charitable_raw_pct >= 100 ) {
						$charitable_pace        = array(
							'tone'  => 'good',
							'label' => __( 'Goal met', 'charitable' ),
						);
						$charitable_pace_colour = '#276c35';
					} elseif ( ! $_campaign->is_endless() ) {

						$charitable_left  = (int) $_campaign->get_seconds_left();
						$charitable_end   = (int) $_campaign->get_end_time();
						$charitable_start = strtotime( $campaign->post_date_gmt . ' GMT' );
						$charitable_span  = $charitable_end - $charitable_start;

						if ( $charitable_left <= 0 ) {
							$charitable_pace        = array(
								'tone'  => 'bad',
								'label' => __( 'Ended short of goal', 'charitable' ),
							);
							$charitable_pace_colour = '#d63638';
						} elseif ( $charitable_span > 0 ) {
							// Share of the campaign's life already spent, as a percentage.
							$charitable_spent = ( 1 - ( $charitable_left / $charitable_span ) ) * 100;

							// Below a week in, pacing is noise: one gift swings it wildly.
							if ( $charitable_spent >= 15 ) {
								$charitable_ratio = $charitable_raw_pct / max( 1, $charitable_spent );

								if ( $charitable_ratio >= 1 ) {
									$charitable_pace        = array(
										'tone'  => 'good',
										'label' => __( 'On track', 'charitable' ),
									);
									$charitable_pace_colour = '#5aa152';
								} elseif ( $charitable_ratio >= 0.5 ) {
									/*
									Half pace is the boundary. At 0.6 the middle band was so narrow
									that 39% raised with 70% of the time gone came out "At risk" at
									a ratio of 0.557. Half the required pace is also the easier
									line to defend out loud: below it, finishing needs the rest of
									the campaign to outperform everything so far by more than
									double.
									*/
									$charitable_pace        = array(
										'tone'  => 'warn',
										'label' => __( 'Behind', 'charitable' ),
									);
									$charitable_pace_colour = '#dba617';
								} else {
									$charitable_pace        = array(
										'tone'  => 'bad',
										'label' => __( 'At risk', 'charitable' ),
									);
									$charitable_pace_colour = '#d63638';
								}
							}
						}
					}

					$charitable_pace_pct  = (int) round( $charitable_raw_pct );
					$charitable_pace_left = ( ! $_campaign->is_endless() && (int) $_campaign->get_seconds_left() > 0 )
						? human_time_diff( time(), (int) $_campaign->get_end_time() )
						: '';
				}

				?>
				<?php if ( $charitable_v3_campaigns ) : ?>
					<?php
					/*
					v3 renders each campaign as a TABLE ROW: campaign, raised, goal, pacing, ends.

					The shipping list put four labelled facts in a paragraph per campaign - "Total
					Donations: $179.55  Total Donors: 2  End Date: July 25, 2026" - which cannot be
					compared down a column, and that comparison is the entire job of a "top
					campaigns" card. Same data, arranged so the eye can rank it.

					A campaign with no goal shows an em dash in both the goal and pacing cells
					rather than a zero bar. No target means no progress, and a 0% bar would read as
					failure instead of as "not measured that way".
					*/
					?>
					<tr>
						<td class="charitable-reports-v3-campaign-name">
							<?php
							/*
							A campaign can reach this list with an empty title - a draft somebody
							started and never named. In the old paragraph layout that produced a
							blank line; in a table it produces a row that looks like a rendering
							fault. The post ID is what identifies it in the admin, so that is what
							the link says.
							*/
							$charitable_campaign_title = '' !== trim( (string) $campaign->post_title )
								? $campaign->post_title
								/* translators: %d: the campaign's post ID. */
								: sprintf( __( 'Untitled campaign #%d', 'charitable' ), (int) $campaign->ID );
							?>
							<a href="<?php echo esc_url( charitable_get_admin_campaign_edit_url( $campaign->ID ) ); ?>" target="_blank"><?php echo esc_html( $charitable_campaign_title ); ?></a>
						</td>
						<td class="num"><?php echo charitable_format_money( $donated_amount ); // phpcs:ignore ?></td>
						<td class="num">
							<?php
							echo $donation_goal
								? charitable_format_money( $donation_goal ) // phpcs:ignore
								: '<span class="charitable-reports-v3-campaign-none">&mdash;</span>';
							?>
						</td>
						<td class="charitable-reports-v3-campaign-pacing">
							<?php if ( $donation_goal && null !== $charitable_pace_pct ) : ?>
								<span class="charitable-reports-v3-campaign-track">
									<i style="width: <?php echo esc_attr( max( 0, min( 100, (int) $charitable_pace_pct ) ) ); ?>%;<?php echo $charitable_pace ? ' background:' . esc_attr( $charitable_pace_colour ) . ';' : ''; ?>"></i>
								</span>
								<span class="charitable-reports-v3-campaign-pct"><?php echo esc_html( number_format_i18n( $charitable_pace_pct ) . '%' ); ?></span>
							<?php else : ?>
								<span class="charitable-reports-v3-campaign-none">&mdash;</span>
							<?php endif; ?>
						</td>
						<td class="num charitable-reports-v3-campaign-ends">
							<?php if ( false !== $charitable_pace ) : ?>
								<span class="charitable-reports-v3-pace-pill charitable-reports-v3-pace-pill--<?php echo esc_attr( $charitable_pace['tone'] ); ?>"><?php echo esc_html( $charitable_pace['label'] ); ?></span>
							<?php elseif ( '' !== $charitable_pace_left ) : ?>
								<?php
								printf(
									/* translators: %s: a human-readable duration, e.g. "4 days". */
									esc_html__( '%s left', 'charitable' ),
									esc_html( $charitable_pace_left )
								);
								?>
							<?php else : ?>
								<span class="charitable-reports-v3-campaign-none"><?php echo esc_html( $status_string ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php else : ?>
				<li class="campaign-completed">
					<div class="main">
						<div class="info">
							<p class="campaign-title"><a href="<?php echo esc_url( charitable_get_admin_campaign_edit_url( $campaign->ID ) ); ?>" target="_blank"><?php echo esc_html( $campaign->post_title ); ?></a></p>
							<div class="info-summary">
								<p class="total-donations"><?php echo esc_html__( 'Total Donations', 'charitable' ); ?>: <strong><?php echo charitable_format_money( $donated_amount ); // phpcs:ignore ?></strong></p>
								<p class="cr"><?php echo esc_html__( 'Total Donors', 'charitable' ); ?>: <strong><?php echo intval( $donor_count ); ?></strong></p>
								<?php if ( $donation_goal ) : ?>
								<p class="cr"><?php echo esc_html__( 'Goal', 'charitable' ); ?>: <strong><?php echo charitable_format_money( $donation_goal ); // phpcs:ignore ?></strong></p>
								<?php endif; ?>
								<?php if ( $end_date ) : ?>
								<p class="cr"><?php echo esc_html__( 'End Date', 'charitable' ); ?>: <strong><?php echo esc_html( $end_date ); ?></strong></p>
								<?php endif; ?>
							</div>
						</div>
						<div class="status">
							<p><span class="badge <?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_string ); ?></span></p>
						</div>
					</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
					<div class="progress-bar">
					<?php
					if ( $donated_amount > 0 && $donation_goal && $percent_donated > 0 ) :
						?>
						<div class="progress" style="width: <?php echo intval( $percent_donated ); ?>%"></div>
						<?php
						endif;
					?>
					</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
				</li>
				<?php endif; ?>
				<?php endforeach; ?>
			<?php

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the Top Donor List
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donors The donor data.
		 * @param  bool  $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_top_donors( $donors = array(), $include_icons = true ) {

			ob_start();

			if ( empty( $donors ) ) {
				?>

				<div class="no-items the-list">
					<p><strong><?php echo esc_html__( 'There are no donors in this date range.', 'charitable' ); ?></strong></p>
				</div>

			<?php } else { ?>

				<div class="the-list">
					<ul id="charitable-top-donors-list">

				<?php

				$unique_donors = array();

				/*
				One query for every donor on the card, before the loop. Inside it this would be one
				query per row.
				*/
				$donor_first_dates = array();

				foreach ( (array) $donors as $top_donor_ids ) {
					if ( ! empty( $top_donor_ids->donor_id ) ) {
						$donor_first_dates[] = $top_donor_ids->donor_id;
					}
				}

				$donor_first_dates = $this->get_donor_first_donation_dates( $donor_first_dates );

				foreach ( $donors as $top_donor ) :

					if ( in_array( $top_donor->donor_id, $unique_donors, true ) ) {
						continue;
					}

					/*
					If this is a donation related to a recurring donation, then this parent will
					have a type of 'recurring'.

					Guard against the parent donation having been deleted: charitable_get_donation()
					returns false for an invalid or orphaned parent ID, and chaining ->get() on
					false fatals the whole report page. The previous form did exactly that.

					@since 1.8.14
					*/
					$donation_parent = false;
					if ( ! empty( $top_donor->donation_parent_id ) ) {
						$parent_donation = charitable_get_donation( $top_donor->donation_parent_id );
						if ( $parent_donation ) {
							$donation_parent = $parent_donation->get( 'donation_type' );
						}
					}

					$donor_name = ! empty( $top_donor->donor_name ) ? esc_html( $top_donor->donor_name ) : 'Unknown';

					/*
					INITIALS, NOT A PHOTO - and this is the fix for a broken image, not only a
					restyle.

					The old markup emitted <img src="<?php echo esc_url( $donor_avatar ); ?>">
					unconditionally. $donor_avatar was false whenever a donor had neither an avatar
					URL nor an email, so the src rendered EMPTY and the browser drew its
					broken-image icon - visible on any donor recorded without an email, e.g. an
					offline or manual donation.

					A monogram cannot break. It also carries more than the alternative did:
					gravatar answers an unknown address with a generic silhouette, so the common
					case for a donation plugin was a row of identical grey blobs.

					mb_* where available so a name in a non-Latin script yields its own first
					character rather than half a multi-byte sequence.

					@since 1.8.14
					*/
					$donor_initials = '';

					foreach ( preg_split( '/\s+/u', trim( wp_strip_all_tags( (string) $donor_name ) ), -1, PREG_SPLIT_NO_EMPTY ) as $donor_name_part ) {
						$donor_initials .= function_exists( 'mb_substr' ) ? mb_substr( $donor_name_part, 0, 1 ) : substr( $donor_name_part, 0, 1 );

						if ( 2 <= ( function_exists( 'mb_strlen' ) ? mb_strlen( $donor_initials ) : strlen( $donor_initials ) ) ) {
							break;
						}
					}

					$donor_initials = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $donor_initials ) : strtoupper( $donor_initials );

					// A name that yielded nothing at all still needs something in the circle.
					if ( '' === $donor_initials ) {
						$donor_initials = '?';
					}

					$display_amount = ! empty( $top_donor->total_donation_amount ) ? charitable_format_money( $top_donor->total_donation_amount, false, true ) : charitable_format_money( esc_html( $top_donor->amount ), false, true );
					$display_count  = ! empty( $top_donor->total_donation_count ) ? intval( $top_donor->total_donation_count ) : 1;

					/*
					The line under the name: how many donations, and what kind of donor.

					"donation" throughout, never "gift" - the term is not used anywhere on this
					page. A recurring donor is described as monthly; a first-time donor as new; and
					everyone else by when they started, which is the fact that turns a name into a
					relationship. The date comes from the single query above.
					*/
					$donor_first = isset( $donor_first_dates[ (int) $top_donor->donor_id ] )
						? $donor_first_dates[ (int) $top_donor->donor_id ]
						: false;

					/* translators: %s: number of donations this donor has made. */
					$donor_meta = sprintf( _n( '%s donation', '%s donations', $display_count, 'charitable' ), number_format_i18n( $display_count ) );

					/*
					The qualifier is a BADGE, not more sentence. "Monthly donor" and "New donor"
					are classifications - the two facts on this row somebody scans for - and a pill
					is how this page already marks a classification. "first gave Jan 2025" is a
					date rather than a class, so it stays in the line as text.
					*/
					$donor_badge     = '';
					$donor_badge_css = '';

					if ( 'recurring' === strtolower( (string) $donation_parent ) ) {
						$donor_badge     = __( 'Monthly donor', 'charitable' );
						$donor_badge_css = 'monthly';
					} elseif ( $display_count <= 1 ) {
						$donor_badge     = __( 'New donor', 'charitable' );
						$donor_badge_css = 'new';
					} elseif ( false !== $donor_first ) {
						/* translators: %s: month and year of the donor's first donation, e.g. "Jan 2025". */
						$donor_meta .= ' · ' . sprintf( __( 'first gave %s', 'charitable' ), gmdate( 'M Y', $donor_first ) );
					}

					// ensure we don't display the same donor twice.
					$unique_donors[] = $top_donor->donor_id;

					?>
				<li>
					<?php if ( $include_icons ) : ?>
					<div class="avatar">
						<span class="charitable-reports-v3-donor-mono" aria-hidden="true"><?php echo esc_html( $donor_initials ); ?></span>
					</div>
					<?php endif; ?>
					<div class="info">
						<p class="name">
						<?php echo esc_html( $donor_name ); ?>
						</p>
					<p class="email charitable-reports-v3-donor-meta"><?php echo esc_html( $donor_meta ); ?><?php if ( '' !== $donor_badge ) : ?> <span class="charitable-reports-v3-donor-badge charitable-reports-v3-donor-badge--<?php echo esc_attr( $donor_badge_css ); ?>"><?php echo esc_html( $donor_badge ); ?></span><?php endif; ?></p>
					</div>
					<div class="donor-donation-info">
						<p><span class="amount"><?php echo $display_amount; // phpcs:ignore ?></span></p>
					</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
				</li>
				<?php endforeach; ?>
					</ul>
				</div>

				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the Recent Donation List (appears on dashboard).
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donations The donation data.
		 * @param  bool  $limit Whether to limit the number of donations.
		 * @param  bool  $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_recent_donations_list( $donations = array(), $limit = false, $include_icons = true ) { // phpcs:ignore

			if ( empty( $donations ) ) {
				return;
			}

			$limit_counter = 0;

			ob_start();

			$statuses = charitable_get_valid_donation_statuses();

			// reverse the donations array.
			$donations = array_reverse( $donations );

			foreach ( $donations as $donation ) :

				if ( $limit && $limit_counter >= $limit ) {
					break;
				}

				$donation_id     = intval( $donation->donation_id );
				$donor_name      = esc_html( $donation->first_name ) . ' ' . esc_html( $donation->last_name );
				$payment_method  = esc_html( $donation->payment_gateway );
				$donation_status = esc_html( $donation->donation_status );

				$donation_status_label = array_key_exists( $donation_status, $statuses ) ? $statuses[ $donation_status ] : $donation_status;

				?>
			<li>
				<div class="main">
					<div class="info">
						<p class="donor-id-name">#<?php echo esc_html( $donation_id ); ?> <?php echo esc_html__( 'by', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?></p>
						<div class="info-summary">
							<p class="amount-donated"><?php echo esc_html__( 'Amount Donated', 'charitable' ); ?>: <strong><?php echo charitable_format_money( $donation->amount ); // phpcs:ignore ?></strong></p>
							<p class="amount-donated"><?php echo esc_html__( 'Method', 'charitable' ); ?>: <strong><?php echo esc_html( ucwords( $payment_method ) ); ?></strong></p>
						</div>
					</div>
					<div class="status">
						<p><span class="badge <?php echo esc_attr( strtolower( $donation_status ) ); ?>"><?php echo esc_html( $donation_status_label ); ?></span></p>
					</div>
				</div>
			</li>
				<?php

				++$limit_counter;

			endforeach;

			return ob_get_clean();
		}

		/**
		 * Return the HTML of the activity filter select dropdown.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $selected_values The selected values to possibly pre-select the <select>.
		 *
		 * @return string
		 */
		public function get_activity_report_filter_dropdown( $selected_values = array() ) {

			if ( ! is_array( $selected_values ) ) {
				$selected_values = array( $selected_values );
			}

			$options_values = apply_filters(
				'charitable_dashboard_activity_report_type_filter_values',
				array(
					'donations-made'      => __( 'Donations Made (All)', 'charitable' ),
					'donations-made-paid' => __( 'Donations Made (Paid)', 'charitable' ),
					'refunds'             => __( 'Refunds', 'charitable' ),
					/*
					 * 'user-comments' was offered here but nothing ever implemented it: there is
					 * no comment activity table, no insert, and no case for it in
					 * Charitable_Admin_Activities::get_activity(). Selecting it left $db_types
					 * empty, which get_activity_db_data() reads as "no type filter", so the
					 * report answered with every activity on the site instead.
					 *
					 * @since 1.8.14.1
					 */
					'campaigns-created'   => __( 'Campaigns Created', 'charitable' ),
				)
			);

			ob_start();

			?>

			<select name="action" id="report-activity-type-filter">
				<option value=""><?php echo esc_html__( 'All Activity', 'charitable' ); ?> </option>
				<?php foreach ( $options_values as $value => $label ) : ?>
					<?php $selected = ! empty( $selected_values ) && in_array( $value, $selected_values, true ) ? 'selected="selected"' : ''; ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php echo esc_html( $selected ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the Activity List
		 *
		 * @since  1.8.1
		 *
		 * @param  array  $activities The activity data.
		 * @param  bool   $include_icons Whether to include icons.
		 * @param  string $show_date_value The date value to show when showing "time ago". Default is 'date_to_sort'.
		 *
		 * @return string
		 */
		public function generate_activity_list( $activities = array(), $include_icons = true, $show_date_value = 'date_to_sort' ) {

			ob_start();

			if ( empty( $activities ) ) {
				?>


					<p><strong><?php echo esc_html__( 'There are no activity within the date range.', 'charitable' ); ?></strong></p>


			<?php } else { ?>

					<ul id="charitable-activity-list" class="charitable-activity-list">

					<?php

					foreach ( $activities as $activity ) :

						$time_ago = ! empty( $activity->$show_date_value ) ? human_time_diff( strtotime( $activity->$show_date_value ), time() ) : false;
						$item_id  = $activity->type === 'campaign' ? $activity->campaign_id : $activity->item_id;

						?>
						<li>
							<?php if ( $include_icons ) : ?>
							<div class="icon">
								<img src="<?php echo esc_url( $this->get_activity_icon_url( $activity->icon ) ); ?>" alt="<?php echo esc_html( $activity->status_label ); ?>">
							</div>
							<?php endif; ?>
							<div class="info">
								<p>
									<?php

									/* Determine Label */

									/*
									If the user isn't using pro and is on a page that isn't the report overview, give
									them a generic label.

									The overview is the NO-TAB case as well as tab=overview - reports.php defaults
									$active_tab to 'overview' when tab is absent, and nothing ever links to
									&tab=overview. Testing ! isset( $_GET['tab'] ) therefore treated the overview
									itself as "not the overview", so on Lite every row there was labelled
									"Charitable Donor" - a newly created campaign included, which is how it was
									reported. Only a tab that is present and is not 'overview' is a non-overview page.
									*/
									if ( ! charitable_is_pro() && isset( $_GET['tab'] ) && 'overview' !== $_GET['tab'] ) { // phpcs:ignore
										$action_label = esc_html__( 'Charitable Donor', 'charitable' );
									} else {
										// If this is a campaign, show the campaign name vs just any number ID.
										$action_label = isset( $activity->type ) && $activity->type === 'campaign' ? str_replace( '%id', '"' . esc_html( $activity->campaign_title ) . '"', $activity->action_label ) : str_replace( '%id', $activity->item_id_prefix . $item_id, $activity->action_label );
									}

									echo $action_label; // phpcs:ignore

									?>
									<?php if ( ! empty( $activity->status_label ) ) : ?>
										<span class="badge <?php echo esc_attr( $activity->status ); ?>"><?php echo esc_html( $activity->status_label ); ?></span>
									<?php endif; ?>
								</p>
								<?php

								/* Determine Secondary Info */

								// If this is a campaign, get the created_by value and show a name of who created it.
								if ( isset( $activity->type ) && $activity->type === 'campaign' && ! empty( $activity->created_by ) ) {
									$created_by = get_userdata( $activity->created_by );
									$created_by = $created_by ? $created_by->display_name : '';
									// get the WordPress user admin url to edit this user.
									$admin_user_url = get_edit_user_link( $activity->created_by );

									$secondary_info = '<p class="charitable-reports-activity-secondary-info amount">' . esc_html__( 'Created By:', 'charitable' ) . ' <a href="' . esc_url( $admin_user_url ) . '">' . esc_html( $created_by ) . '</a></p>';
								} else {
									$secondary_info = $this->get_activity_secondary_info( $activity );
								}

								echo $secondary_info; // phpcs:ignore

								// phpcs:ignore ?>
							</div>
							<?php if ( $time_ago ) : ?>
							<div class="time-ago">
								<?php echo esc_html( $time_ago ); ?> <?php echo esc_html__( 'ago', 'charitable' ); ?>
							</div>
							<?php endif; ?>
							<?php
							if ( ! $include_icons ) :
								?>
								<br class="clear" /><?php endif; ?>
						</li>
					<?php endforeach; ?>
					</ul>

				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the "secondary" info for an activity.
		 *
		 * @since  1.8.1
		 *
		 * @param  object $activity The activity data.
		 *
		 * @return string
		 */
		public function get_activity_secondary_info( $activity ) {

			// Each "activity" needs at least an activity type.
			if ( empty( $activity ) || empty( $activity->type ) ) {
				return;
			}

			switch ( $activity->type ) {
				case 'donation':
					return $this->get_donation_activity_secondary_info( $activity );
				case 'campaign':
					return $this->get_campaign_activity_secondary_info( $activity );
				default:
					return;
			}
		}

		/**
		 * Generate HTML for the "secondary" info for a donation activity.
		 *
		 * @since  1.8.1
		 *
		 * @param  object $activity The activity data.
		 *
		 * @return string
		 */
		public function get_donation_activity_secondary_info( $activity ) {

			if ( empty( $activity ) || empty( $activity->type ) ) {
				return;
			}

			$secondary_info = '';

			$primary_action = isset( $activity->primary_action ) ? $activity->primary_action : false;

			switch ( $primary_action ) {
				case 'charitable-completed':
				case 'charitable-pending':
				case 'charitable-refunded':
				case 'charitable-failed':
					$admin_campaign_url = ! empty( $activity->campaign_id ) ? charitable_get_admin_campaign_edit_url( $activity->campaign_id ) : '#';
					$admin_donation_url = ! empty( $activity->item_id ) ? charitable_get_admin_donation_edit_url( $activity->item_id ) : false;
					$campaign_title     = ! empty( $activity->campaign_title ) ? ' to <a target="_blank" href="' . $admin_campaign_url . '"><span class="campaign-title">' . esc_html( $activity->campaign_title ) . '</span></a> ' : '';
					$secondary_info     = $admin_donation_url ? '<p class="charitable-reports-activity-secondary-info amount"><a href="' . $admin_donation_url . '" target="_blank">' . charitable_format_money( $activity->amount, false, true ) . '</a>' . $campaign_title . '</p>' : '<p class="charitable-reports-activity-secondary-info amount">' . charitable_format_money( $activity->amount, false, true ) . $campaign_title . '</p>';
					break;
				default:
					$admin_campaign_url = ! empty( $activity->campaign_id ) ? charitable_get_admin_campaign_edit_url( $activity->campaign_id ) : '#';
					$admin_donation_url = ! empty( $activity->item_id ) ? charitable_get_admin_donation_edit_url( $activity->item_id ) : false;
					$campaign_title     = ! empty( $activity->campaign_title ) ? ' to <a target="_blank" href="' . $admin_campaign_url . '"><span class="campaign-title">' . esc_html( $activity->campaign_title ) . '</span></a> ' : '';
					$secondary_info     = $admin_donation_url ? '<p class="charitable-reports-activity-secondary-info amount"><a href="' . $admin_donation_url . '" target="_blank">' . charitable_format_money( $activity->amount, false, true ) . '</a>' . $campaign_title . '</p>' : '<p class="charitable-reports-activity-secondary-info amount">' . charitable_format_money( $activity->amount, false, true ) . $campaign_title . '</p>';
					break;
			}

			return $secondary_info;
		}

		/**
		 * Generate HTML for the "secondary" info for a campaign activity.
		 *
		 * @since  1.8.1
		 *
		 * @param  object $activity The activity data.
		 *
		 * @return string
		 */
		public function get_campaign_activity_secondary_info( $activity ) {

			$secondary_info = '';

			switch ( $activity->primary_action ) {
				case 'update':
					$secondary_info = '<p class="campaign-title">' . esc_html( $activity->campaign_title ) . '</p>';
					break;
				default:
					$secondary_info = '<p class="campaign-title">' . esc_html( $activity->campaign_title ) . '</p>';
					break;
			}

			return $secondary_info;
		}

		/**
		 * Generates HTML for the payment methods list.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $payment_breakdown The payment breakdown data.
		 * @param  bool  $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_payment_methods_list( $payment_breakdown = array(), $include_icons = true, $args = array() ) {

			ob_start();

			// See generate_top_campaigns() for why the layout is a parameter and not a gate check.
			$args = wp_parse_args(
				(array) $args,
				array(
					'layout' => 'auto',
				)
			);

			/*
			v3 renders these as money rows with a bar rather than a donut legend: the name, what it
			took, its share, and a bar underneath. In a 330px rail a donut plus a legend spends most
			of the card on a shape that carries four numbers, and the numbers are the point.

			The share is computed from the AMOUNT here, not from the donation count the classic
			markup below uses. That is deliberate and it is why this is a separate branch: a row
			reading "$144.00 &middot; 33%" where the money is money and the percentage is a count is
			worse than either figure alone, because every reader takes the percentage to refer to
			the sum beside it. The classic view keeps its count-based share untouched.

			@since 1.8.14
			*/
			$charitable_pm_v3 = 'v3' === $args['layout']
				|| (
					'auto' === $args['layout']
					&& function_exists( 'charitable_reports_v3_enabled' )
					&& charitable_reports_v3_enabled()
				);

			if ( $charitable_pm_v3 && ! empty( $payment_breakdown['donations'] ) ) {

				$charitable_pm_total = 0.0;
				$charitable_pm_rows  = $payment_breakdown['donations'];

				foreach ( $charitable_pm_rows as $charitable_pm_data ) {
					$charitable_pm_total += isset( $charitable_pm_data['amount'] ) ? (float) $charitable_pm_data['amount'] : 0.0;
				}

				/*
				Sorted by amount, largest first. The card's bar colours descend by POSITION, which
				only means anything if position is rank - and the breakdown arrives in whatever
				order the gateways were encountered, which can put a gateway's 5.6% above another's
				54.2% and give the smallest gateway the strongest colour.

				Biggest-first is also the more useful read: the first row answers "where is the
				money coming from" without scanning.
				*/
				uasort(
					$charitable_pm_rows,
					function ( $a, $b ) {
						$a_amount = isset( $a['amount'] ) ? (float) $a['amount'] : 0.0;
						$b_amount = isset( $b['amount'] ) ? (float) $b['amount'] : 0.0;

						if ( $a_amount === $b_amount ) {
							return 0;
						}

						return ( $b_amount < $a_amount ) ? -1 : 1;
					}
				);

				foreach ( $charitable_pm_rows as $charitable_pm_method => $charitable_pm_data ) {

					$charitable_pm_amount = isset( $charitable_pm_data['amount'] ) ? (float) $charitable_pm_data['amount'] : 0.0;

					// A gateway that took nothing is noise in a four-row card.
					if ( $charitable_pm_amount <= 0 ) {
						continue;
					}

					$charitable_pm_share = $charitable_pm_total > 0
						? round( ( $charitable_pm_amount / $charitable_pm_total ) * 100, 1 )
						: 0;
					?>
					<li class="<?php echo esc_attr( $charitable_pm_method ); ?> charitable-reports-v3-pm-row">
						<span class="charitable-reports-v3-pm-name"><?php echo esc_html( $charitable_pm_data['label'] ); ?></span>
						<span class="charitable-reports-v3-pm-amount">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: formatted money amount, 2: share of the total as a percentage. */
									_x( '%1$s &middot; %2$s%%', 'payment method amount and share', 'charitable' ),
									charitable_format_money( $charitable_pm_amount ),
									number_format_i18n( $charitable_pm_share, 1 )
								)
							);
							?>
						</span>
						<span class="charitable-reports-v3-pm-bar"><i style="width: <?php echo esc_attr( min( 100, $charitable_pm_share ) ); ?>%;"></i></span>
					</li>
					<?php
				}

				return ob_get_clean();
			}

			if ( empty( $payment_breakdown ) ) {
				?>

			<li class="none">
				<div class="icon">
					<span></span>
				</div>
				<div class="info">
					<span><?php echo esc_html__( 'No payments in this date range.', 'charitable' ); ?></span>
				</div>
				<?php
				/*
				No total column on this row. It held a bare "-", which is a dash standing in for a
				figure that does not exist rather than for one that is zero or unknown - so it read
				as a missing value in a row whose whole point is that there is nothing to miss.
				*/
				?>
			</li>

				<?php
			} else {

				foreach ( $payment_breakdown['donations'] as $payment_method => $data ) :

					$percentage = intval( $data['donors'] ) / $payment_breakdown['donations_total'] * 100;
					// round to nearest .1 number.
					$percentage = round( $percentage, 1 );

					$title = ! empty( $data['title'] ) ? 'title="' . esc_html( $data['title'] ) . '"' : false;

					?>
			<li class="<?php echo esc_attr( $payment_method ); ?>">
					<?php if ( $include_icons ) : ?>
				<div class="icon">
					<span <?php echo $title; // phpcs:ignore ?>></span>
				</div>
				<?php endif; ?>
				<div class="info">
					<span <?php echo $title; // phpcs:ignore ?>><?php echo esc_html( $data['label'] ); ?></span>
				</div>
				<div class="total">
					<span><?php echo esc_html( $percentage ); ?>%</span>
				</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
			</li>
					<?php
			endforeach;

			}

			return ob_get_clean();
		}

		/**
		 * Generates HTML rows for donation breakdown table.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donation_data The donation data.
		 * @param  array $refunds_data The refunds data.
		 *
		 * @return string
		 */
		public function generate_donations_breakdown_rows( $donation_data = array(), $refunds_data = array() ) {

			ob_start();

			if ( empty( $donation_data ) ) {
				?>

				<td colspan="5"><strong><?php echo esc_html__( 'There are no donations in this date range.', 'charitable' ); ?></strong></td>

				<?php
			} else {

				$this->breakdown_empty_days = 0;

				foreach ( $donation_data as $day => $data ) :

					$net          = 0;
					$refund_count = 0;
					$css_class    = false;
					$prefix       = false;
					$row_id       = strtotime( $day );

					$net           = $data['amount'] - $refunds_data['refunds_by_day'][ $day ]['amount'];
					$prefix        = intval( $net ) < 0 ? '-' : '+';
					$css_class     = intval( $net ) < 0 ? 'negative' : 'positive';
					$css_class     = intval( $net ) === 0 ? 'zero' : $css_class;
					$refund_count  = intval( $refunds_data['refunds_by_day'][ $day ]['donors'] );
					$refund_amount = $refunds_data['refunds_by_day'][ $day ]['amount'];

					/*
					A day with no donations, no donors and no refund is the DEFAULT state, not a
					finding - and on a month-long range it is most of the table. A 30-day filter
					with one gift rendered 29 rows of $0.00 and pushed everything below the card
					off the screen.

					Marked rather than skipped, so the rows are still in the DOM and the "show
					every day" toggle needs no second request. The CSV export is untouched and
					still contains every day.

					@since 1.8.14
					*/
					$charitable_row_empty = 0 === intval( $data['donors'] )
						&& 0.0 === (float) $data['amount']
						&& 0.0 === (float) $refund_amount;

					if ( $charitable_row_empty ) {
						++$this->breakdown_empty_days;
					}

					?>

					<tr id="type-donations-donor-<?php echo intval( $row_id ); ?>" class="row-type-donation-breakdown<?php echo $charitable_row_empty ? ' charitable-reports-v3-row-empty' : ''; ?>">
						<td class="donated column-date" data-colname="Date"><?php echo esc_html( $data['label'] ); ?></td>
						<td class="donated column-donations" data-colname="Donations"><?php echo charitable_format_money( esc_html( $data['amount'] ) ); // phpcs:ignore ?></td>
						<td class="donated column-donors" data-colname="Number of Donors"><?php echo esc_html( $data['donors'] ); ?></td>
						<td class="donated column-refunds" data-colname="Refunds"><?php echo charitable_format_money( ( $refund_amount ) ); // phpcs:ignore ?></td>
						<td class="donated column-net <?php echo $css_class; ?>" data-colname="Net"><?php echo $prefix . charitable_format_money( esc_html( $net ) ); // phpcs:ignore ?></td>
					</tr>

					<?php
				endforeach;

			}

			return ob_get_clean();
		}

		/**
		 * Generates meta info (title, column names) for the report table.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 *
		 * @return array
		 */
		public function get_donor_report_table_meta( $report_type = 'donors-top' ) {

			$table_meta = array();

			switch ( $report_type ) {
				case 'donors-top':
					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'           => '',
							'name-email'       => esc_html__( 'Name', 'charitable' ),
							'total-donations'  => esc_html__( 'Total Donations', 'charitable' ),
							'number-donations' => esc_html__( 'Number of Donations', 'charitable' ),
							'average'          => esc_html__( 'Average', 'charitable' ),
							'number-campaigns' => esc_html__( 'Number of Campaigns', 'charitable' ),
							'last-donation'    => esc_html__( 'Last Donation', 'charitable' ),
							'actions'          => esc_html__( 'Actions', 'charitable' ),
						),
					);
					break;

				case 'donors-recurring':
					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'           => '',
							'name-email'       => esc_html__( 'Name', 'charitable' ),
							'total-donations'  => esc_html__( 'Total Donations', 'charitable' ),
							'number-donations' => esc_html__( 'Number of Donations', 'charitable' ),
							'average'          => esc_html__( 'Average', 'charitable' ),
							'number-campaigns' => esc_html__( 'Number of Campaigns', 'charitable' ),
							'last-donation'    => esc_html__( 'Last Donation', 'charitable' ),
							'actions'          => esc_html__( 'Actions', 'charitable' ),
						),
					);
					break;

				case 'donors-first-time':
					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'          => '',
							'name-email'      => esc_html__( 'Name', 'charitable' ),
							'total-donations' => esc_html__( 'Donation', 'charitable' ),
							'last-donation'   => esc_html__( 'Campaign', 'charitable' ),
							'date'            => esc_html__( 'Date', 'charitable' ),
							'actions'         => esc_html__( 'Actions', 'charitable' ),
						),
					);
					break;

				default:
					// code...
					break;
			}

			return $table_meta;
		}

		/**
		 * Generates HTML for the donor breakdown table on the overview page.
		 *
		 * @since  1.8.1
		 * @since  1.8.3 Added $limit_per_page parameter and generate_pagination_html().
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $donor_breakdown The donor breakdown data.
		 * @param  int    $current_page The current page.
		 * @param  int    $limit_per_page The limit per page.
		 *
		 * @return string
		 */
		public function generate_donor_breakdown_table_html( $report_type = 'donors-top', $donor_breakdown = array(), $current_page = 1, $limit_per_page = 10 ) {

			$table_meta = $this->get_donor_report_table_meta( $report_type );

			ob_start();

			?>

				<div class="alignleft actions">

					<h2><?php echo esc_html( $table_meta['title'] ); ?></h2>

				</div>
				<div class="alignright">

					<?php if ( ! charitable_is_pro() ) : ?>

						<button disabled="disabled" value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>

					<?php else : ?>

					<form action="" method="post" class="charitable-report-donor-form" id="charitable-donor-download-form">
						<input name="charitable_report_action" type="hidden" value="charitable_report_download_donors">
						<input name="report_type" type="hidden" value="<?php echo esc_attr( $report_type ); ?>">
						<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
						<button value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" type="submit" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>
					</form>

					<?php endif; ?>

				</div>

				<br class="clear">

				<div class="charitable-report-table-container">

					<?php do_action( 'charitable_report_before_donor_table' ); ?>

					<?php
					if ( ! charitable_is_pro() ) :
						?>
						<div class="charitable-restricted"><div class="restricted-access-overlay"></div><?php endif; ?>

					<table class="wp-list-table widefat fixed striped table-view-list donor-breakdown charitable-report-ui">
						<thead>
							<tr>
								<?php
								foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
									if ( $column_slug === 'avatar' ) {
										echo '<th style="width: 32px;" class="charitable-avatar"></th>';
									} else {
										echo '<th scope="col" id="' . esc_attr( $column_slug ) . '" class="manage-column column-' . esc_attr( $column_slug ) . '"><span>' . esc_html( $column_title ) . '</span></th>';
									}
								endforeach;
								?>
							</tr>
						</thead>
						<tbody id="donations-breakdown-list">
							<?php echo $this->generate_donor_breakdown_rows( $donor_breakdown, $report_type ); // phpcs:ignore ?>
						</tbody>
						<tfoot>
							<tr>
								<?php
								foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
									if ( $column_slug === 'avatar' ) {
										echo '<th style="width: 32px;" class="charitable-avatar"></th>';
									} else {
										echo '<th scope="col" id="' . esc_html( $column_slug ) . '" class="manage-column column-' . esc_attr( $column_slug ) . '"><span>' . esc_html( $column_title ) . '</span></th>';
									}
								endforeach;
								?>
							</tr>
						</tfoot>
					</table>

					<?php
					if ( ! charitable_is_pro() ) :
						?>
						</div><?php endif; ?>

					<?php do_action( 'charitable_report_after_donor_table' ); ?>

					<br class="clear">

					<?php echo $this->generate_pagination_html( $limit_per_page, $current_page, $report_type ); // phpcs:ignore ?>

					<?php do_action( 'charitable_report_after_donor_table_pagination' ); ?>

				</div>

			<?php

			return ob_get_clean();
		}

		/**
		 * Generates HTML for the donation breakdown table on the overview page.
		 *
		 * @since  1.8.1
		 *
		 * @param array  $donor_data The donor data.
		 * @param string $report_type The report type.
		 *
		 * @return string
		 */
		public function generate_donor_breakdown_rows( $donor_data = array(), $report_type = 'donors-top' ) {

			$suffix = ( charitable_is_debug() || charitable_is_script_debug() ) ? '#charitabledebug' : false;

			ob_start();

			if ( empty( $donor_data ) ) {
				?>

				<td colspan="5"><strong><?php echo esc_html__( 'There are no donors found.', 'charitable' ); ?></strong></td>

				<?php

			} else {

				foreach ( $donor_data as $index => $data ) :

					$donor_id               = isset( $data->donor_id ) ? intval( $data->donor_id ) : 0;
					$donor_email            = isset( $data->email ) ? esc_html( $data->email ) : '';
					$donor_avatar           = ( is_email( $donor_email ) ) ? get_avatar_url( $donor_email ) : charitable()->get_path( 'assets', false ) . 'images/misc/placeholder-avatar-small.jpg';
					$donor_name             = esc_html( $data->first_name ) . ' ' . esc_html( $data->last_name );
					$donor_number_donations = isset( $data->total_count_donations ) ? intval( $data->total_count_donations ) : 0;
					$donor_number_campaigns = isset( $data->total_count_campaigns ) ? intval( $data->total_count_campaigns ) : 0;
					$donor_average          = $donor_number_donations > 0 ? $data->total_amount / $donor_number_donations : 0;
					$donation_id            = isset( $data->donation_id ) ? intval( $data->donation_id ) : 0;
					$is_sample_data         = isset( $data->sample ) ? true : false;

					// date format: M j, Y plus time of day in am/pm.
					$last_donation_date          = $data->last_donation_date ? gmdate( 'M j, Y g:i a', strtotime( $data->last_donation_date ) ) : 'N/A';
					$last_donation_campaign_name = $data->last_campaign_title ? esc_html( $data->last_campaign_title ) : 'N/A';
					$last_donation_campaign_id   = $data->last_campaign_id ? intval( $data->last_campaign_id ) : 0;

					$action_link  = '#';
					$action_label = false;

					// Determine the (view) link.
					if ( 'donors-first-time' === $report_type && $donation_id > 0 ) {

						$action_link  = ( $donor_id !== 0 ) ? admin_url( 'post.php?post=' . $donation_id . '&action=edit' ) : '#';
						$action_label = esc_html__( 'View Donation', 'charitable' );

					} elseif ( $last_donation_campaign_id > 0 ) {

						// Does this post exist?
						if ( false === get_post_status( $last_donation_campaign_id ) ) {

							$action_link = '#';

						} else {

							// Determine if this is a "legacy" campaign.
							$is_legacy_campaign = charitable_is_campaign_legacy( $last_donation_campaign_id );

							$action_link  = $is_legacy_campaign ? admin_url( 'post.php?post=' . $last_donation_campaign_id . '&action=edit' ) : admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . $last_donation_campaign_id ) . $suffix;
							$action_label = 'donors-top' === $report_type ? esc_html__( 'View Last Donated Campaign', 'charitable' ) : esc_html__( 'View Campaign', 'charitable' );

						}
					}

					if ( 'donors-first-time' === $report_type ) {
						if ( $last_donation_campaign_name && $last_donation_campaign_id ) {
							$last_donation_html = '<a href="' . $action_link . '" target="_blank">' . $last_donation_campaign_name . '</a>';
						} elseif ( $is_sample_data ) {
							$last_donation_html = $last_donation_campaign_name;
						}
					} elseif ( $last_donation_date && $last_donation_campaign_name && $last_donation_campaign_id ) {
						$last_donation_html = charitable_format_money( esc_html( $data->amount ), false, true ) . '<br/><a href="' . $action_link . '" target="_blank">' . $last_donation_campaign_name . '</a><br/>' . $last_donation_date;
					} elseif ( $is_sample_data ) {
						$last_donation_html = charitable_format_money( esc_html( $data->amount ), false, true ) . '<br/>' . $last_donation_campaign_name . '<br/>' . $last_donation_date;
					}

					if ( $report_type === 'donors-first-time' ) {
						?>

					<tr id="type-<?php echo esc_attr( $report_type ); ?>-donor-<?php echo intval( $donor_id ); ?>" class="type-<?php echo esc_attr( $report_type ); ?>">
						<td class="charitable-avatar" style="width: 32px;"><img src="<?php echo esc_url( $donor_avatar ); ?>" class="avatar avatar-32 photo charitable-avatar-donor-<?php echo intval( $donor_id ); ?>" width="32" height="32" alt="<?php echo esc_html__( 'Profile picture of', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?>"></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Name / Email', 'charitable' ); ?>"><?php echo esc_html( $donor_name ); ?><br/><?php echo esc_html( $donor_email ); ?></td>
						<td class="donated column-donation-amount" data-colname="<?php echo esc_html__( 'Donation Amount', 'charitable' ); ?>"><?php echo charitable_format_money( esc_html( $data->total_amount ), false, true ); // phpcs:ignore ?></td>
						<td class="donated column-campaign" data-colname="<?php echo esc_html__( 'Campaign', 'charitable' ); ?>"><?php echo $last_donation_html; // phpcs:ignore ?></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Date', 'charitable' ); ?>"><?php echo esc_html( $last_donation_date ); ?></td>
						<?php
						$display = '<td class="donated column-actions " data-colname="Actions">';
						if ( $action_label !== false ) {
							$display .= '<a class="charitable-campaign-action-button" title="' . $action_label . '" href="' . esc_url( $action_link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . 'images/icons/eye.svg" width="14" height="14" alt="' . $action_label . '" /></a>';
						}
							$display .= '</td>';

						echo $display; // phpcs:ignore
						?>
					</tr>

					<?php } else { ?>

						<tr id="type-<?php echo esc_attr( $report_type ); ?>-donor-<?php echo intval( $donor_id ); ?>" class="type-<?php echo esc_attr( $report_type ); ?>">
						<td class="charitable-avatar" style="width: 32px;"><img src="<?php echo esc_url( $donor_avatar ); ?>" class="avatar avatar-32 photo charitable-avatar-donor-<?php echo intval( $donor_id ); ?>" width="32" height="32" alt="<?php echo esc_html__( 'Profile picture of', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?>"></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Name / Email', 'charitable' ); ?>"><?php echo esc_html( $donor_name ); ?><br/><?php echo esc_html( $donor_email ); ?></td>
						<td class="donated column-total-donations" data-colname="<?php echo esc_html__( 'Total Donations', 'charitable' ); ?>"><?php echo charitable_format_money( esc_html( $data->total_amount ), false, true ); // phpcs:ignore ?></td>
						<td class="donated column-number-donations" data-colname="Number of Donations"><?php echo esc_html( $donor_number_donations ); ?></td>
						<td class="donated column-average" data-colname="Average"><?php echo charitable_format_money( $donor_average, false, true ); // phpcs:ignore ?></td>
						<td class="donated column-number-campaigns" data-colname="Number of Campaigns"><?php echo esc_html( $donor_number_campaigns ); ?></td>
						<td class="donated column-last-donation" data-colname="Last Donation"><?php echo $last_donation_html; // phpcs:ignore ?></td>
							<?php
							$display = '<td class="donated column-actions" data-colname="Actions">';
							if ( $action_label !== false ) {
								$display .= '<a class="charitable-campaign-action-button" title="' . $action_label . '" href="' . esc_url( $action_link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . 'images/icons/eye.svg" width="14" height="14" alt="' . $action_label . '" /</a>';
							}
							$display .= '</td>';

						echo $display; // phpcs:ignore
							?>
					</tr>

							<?php
					}

				endforeach;

			}

			return ob_get_clean();
		}

		/**
		 * Enqueue assets and write JS vars for the report.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function report_vars() {

			$localized_strings = $this->get_localized_strings();

			?>
			<script id="charitable-report-data-js">
				var charitable_reporting = <?php echo wp_json_encode( $localized_strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
			</script>

			<?php
		}

		/**
		 * Get the donations stored in the class.
		 *
		 * @since 1.8.1
		 *
		 * @return array
		 */
		public function get_donations() {
			return $this->donations;
		}

		/**
		 * Get the donations stored in the class, and return sorted/organized by payment method.
		 *
		 * @since 1.8.1
		 *
		 * @return array
		 */
		public function get_donations_by_payment() {
			if ( empty( $this->donations ) ) {
				return;
			}

			// setup array so that it holds all the days in the date range.
			$donations_by_payment = apply_filters(
				'charitable_reports_default_payment_types',
				array(
					'stripe'  => array(
						'label'  => esc_html__( 'Stripe', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations made through the Stripe gateway.', 'charitable' ),
					),
					'paypal'  => array(
						'label'  => esc_html__( 'PayPal', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations made through the PayPal gateway.', 'charitable' ),
					),
					'manual'  => array(
						'label'  => esc_html__( 'Manual', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations manually entered into the WordPress admin.', 'charitable' ),
					),
					'offline' => array(
						'label'  => esc_html__( 'Offline', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations made outside of the website.', 'charitable' ),
					),
				)
			);

			// Go through the array and group the amounts and number of donors by day.
			foreach ( $this->donations as $key => $donation ) {

				$payment = ( ! empty( $donation->payment_gateway ) ? strtolower( esc_attr( $donation->payment_gateway ) ) : $payment = 'unknown' );

				// Get the proper label from the gateway object if available.
				$label = '';
				$gateways = charitable_get_helper( 'gateways' );
				if ( $gateways && $gateways->is_valid_gateway( $payment ) ) {
					$gateway_object = $gateways->get_gateway_object( $payment );
					if ( $gateway_object ) {
						$label = $gateway_object->get_label();
					}
				}

				// Fallback to manual label generation if gateway object is not available.
				if ( empty( $label ) ) {
					// cap the P in Paypal manually.
					$label = ( 'paypal' === strtolower( $donation->payment_gateway ) ) ? 'PayPal' : ucwords( str_replace( '_', ' ', $donation->payment_gateway ) );
				}

				if ( isset( $donations_by_payment[ $payment ] ) ) {
					// Update the label if it wasn't set in the default array.
					if ( ! empty( $label ) ) {
						$donations_by_payment[ $payment ]['label'] = esc_html( $label );
					}
					$donations_by_payment[ $payment ]['amount'] += $donation->total_amount;
					$donations_by_payment[ $payment ]['donors'] += 1;
				} else {
					$donations_by_payment[ $payment ]['label']  = esc_html( $label );
					$donations_by_payment[ $payment ]['amount'] = $donation->total_amount;
					$donations_by_payment[ $payment ]['donors'] = 1;

					/*
					Not every donation row carries a title - the query only selects it for some
					gateways - and reading it unguarded logged an "Undefined property:
					stdClass::$title" warning per affected gateway on EVERY report build. Observed
					on this page while testing 1.8.14, once per load.

					@since 1.8.14
					*/
					$donations_by_payment[ $payment ]['title']  = isset( $donation->title ) ? $donation->title : '';
				}
			}

			$this->payment_percentages = array();
			$this->payment_labels      = array();
			$this->payment_keys        = array();

			foreach ( $donations_by_payment as $key => $donation ) {

				$percentage = intval( $donation['donors'] ) / count( $this->donations ) * 100;
				// round to nearest .1 number.
				$percentage = round( $percentage, 1 );

				$this->payment_percentages[] = $percentage;
				$this->payment_labels[]      = $donation['label']; // . '% (' . charitable_format_money( $donation['amount'] ) . ')';
				$this->payment_keys[]        = $key; // Store the payment gateway key for color mapping.

			}

			if ( empty( $this->payment_labels ) && empty( $this->payment_percentages ) ) {
				// no donations were likely found so let's add the "not found" data.
				$this->payment_percentages[] = 100;
				$this->payment_labels[]      = __( 'No donations found.', 'charitable' );
				$this->payment_keys[]        = 'none';
			} else {
				$this->payment_percentages[] = 0;
				$this->payment_labels[]      = '';
				$this->payment_keys[]        = '';
			}

			$donations_by_payment = apply_filters( 'charitable_reports_donations_by_payment_data', $donations_by_payment );

			return array(
				'donations'       => $donations_by_payment,
				'donations_total' => count( $this->donations ),
			);
		}


		/**
		 * Get unique donor count from donations.
		 *
		 * @since 1.8.1
		 *
		 * @return int
		 */
		public function get_donors_count() {
			if ( empty( $this->donations ) ) {
				return 0;
			}

			// Go through multi-dimenssional array and get a count of all unique values of donor_id.
			$donor_ids      = array();
			$donation_total = 0;

			foreach ( $this->donations as $donation ) {
				/*
				A NULL donor_id must be SKIPPED, not collected. The donations query LEFT JOINs
				charitable_campaign_donations, so a donation post with no row there comes back with
				every joined column NULL. Pushed unconditionally, that NULL survived
				array_unique() as its own value and counted as an extra donor, so the tile read 7
				where six people had given. It only shows up where a donation lost its
				campaign_donations row, which is why it has stood.

				@since 1.8.14
				*/
				if ( ! empty( $donation->donor_id ) ) {
					$donor_ids[] = $donation->donor_id;
				}
				// Add the donation amount to the total.
				$donation_total += $donation->total_amount;
			}

			return ( count( array_unique( $donor_ids ) ) );
		}

		/**
		 * Get donation amount total from donations.
		 *
		 * @since 1.8.1
		 *
		 * @return float
		 */
		public function get_donations_total() {
			if ( empty( $this->donations ) ) {
				return 0;
			}

			// Go through multi-dimenssional array and get a count of all unique values of donor_id.
			$donation_total = 0;

			foreach ( $this->donations as $donation ) {
				// Add the donation amount to the total.
				$donation_total += $donation->total_amount;
			}

			return ( (float) $donation_total );
		}

		/**
		 * Get a breakdown of donations per day based on the start and end dates.
		 *
		 * @since 1.8.1
		 *
		 * @param  bool $format_money Whether to format the money (say for downloadable reports ).
		 *
		 * @return array
		 */
		public function get_donations_by_day( $format_money = false ) {
			if ( empty( $this->donations ) ) {
				return;
			}

			$campaign_donations_table = charitable_get_table( 'campaign_donations' );

			// setup array so that it holds all the days in the date range.
			$donations_by_day  = array();
			$days              = $this->get_days_between_dates( $this->start_date, $this->end_date );
			$refunded_statuses = array( 'charitable-refunded' );

			/**
			 * Two grouped queries for the whole range, rather than two queries per calendar
			 * day. The old per-day loop made the cost a function of how long the organisation
			 * had existed rather than how much data it had, so a 24-year range issued ~18,000
			 * queries and hit max_execution_time before returning anything.
			 *
			 * The range is taken from $days rather than from $this->start_date /
			 * $this->end_date, because get_days_between_dates() normalises a falsy date to
			 * "now". Reading the bounds back off $days guarantees the query covers exactly
			 * the days this loop is about to render, however those dates were normalised.
			 */
			$day_keys    = array_keys( $days );
			$range_start = reset( $day_keys );
			$range_end   = end( $day_keys );

			$completed_by_day = $campaign_donations_table->get_donations_summary_by_day_range( $range_start, $range_end, array( 'charitable-completed', 'charitable-refunded' ) );
			$refunded_by_day  = $campaign_donations_table->get_donations_summary_by_day_range( $range_start, $range_end, $refunded_statuses );

			foreach ( $days as $day_unformatted => $day_label ) {

				$donation_day = gmdate( 'Y-m-d', strtotime( $day_unformatted ) );

				$donations_by_day[ $donation_day ]['label']   = gmdate( 'F d, Y', strtotime( $day_unformatted ) );
				$donations_by_day[ $donation_day ]['amount']  = isset( $completed_by_day[ $donation_day ] ) ? $completed_by_day[ $donation_day ]->amount : 0;
				$donations_by_day[ $donation_day ]['donors']  = 0;
				$donations_by_day[ $donation_day ]['refunds'] = isset( $refunded_by_day[ $donation_day ] ) ? $refunded_by_day[ $donation_day ]->amount : 0;
				$donations_by_day[ $donation_day ]['net']     = 0;
			}

			// Go through the array and group the amounts and number of donors by day.
			foreach ( $this->donations as $donation ) {

				$donation_day = gmdate( 'Y-m-d', strtotime( $donation->post_date ) );

				if ( ! empty( $donation->email ) ) {
					if ( isset( $unique_donors[ $donation_day ][ $donation->email ] ) ) {
						$unique_donors[ $donation_day ][ $donation->email ] = $unique_donors[ $donation_day ][ $donation->email ] + 1;
					} else {
						$unique_donors[ $donation_day ][ $donation->email ] = 1;
					}
				}

				if ( isset( $donations_by_day[ $donation_day ] ) ) {
					$donations_by_day[ $donation_day ]['label'] = gmdate( 'F d, Y', strtotime( $donation->post_date ) );
					// $donations_by_day[ $donation_day ]['refunds'] += in_array( $donation->donation_status, $refunded_statuses, true ) ? $donation->total_amount : 0;
				} else {
					$donations_by_day[ $donation_day ]['label'] = gmdate( 'F d, Y', strtotime( $donation->post_date ) );
					// $donations_by_day[ $donation_day ]['refunds'] = in_array( $donation->donation_status, $refunded_statuses, true ) ? $donation->total_amount : 0;
				}
			}

			// Go through the array and (1) add the net values now that the totals are calculated (2) format amount and refunds (3) add unique donors.
			foreach ( $donations_by_day as $day => $data ) {

				$donations_by_day[ $day ]['net']    = $this->get_net( $data['amount'], $data['refunds'] );
				$donations_by_day[ $day ]['donors'] = ! empty( $unique_donors[ $day ] ) ? count( $unique_donors[ $day ] ) : 0;

				if ( $format_money ) :
					$donations_by_day[ $day ]['amount']  = $this->charitable_reports_format_money( $data['amount'] );
					$donations_by_day[ $day ]['refunds'] = $this->charitable_reports_format_money( $data['refunds'] );
					$donations_by_day[ $day ]['net']     = $this->charitable_reports_format_money( $donations_by_day[ $day ]['net'] );
				endif;
			}

			// reverse array.
			$donations_by_day = array_reverse( $donations_by_day );

			return $donations_by_day;
		}

		/**
		 * Get the total amount of refunds from the stored activities.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @param  int $limit The limit of activities to get.
		 *
		 * @return float
		 */
		public function get_activity_refund_total( $campaign_id = false, $limit = false ) {

			if ( $this->is_activities_empty() ) {
				$this->activities = $this->get_activity( $campaign_id, $limit );
			}

			if ( $this->is_activities_empty() ) {
				return 0;
			}

			$refunded_total = 0;

			foreach ( $this->activities as $activity ) {
				if ( 'refunded' === $activity->status ) {
					$refunded_total += $activity->amount;
				}
			}

			return $refunded_total;
		}

		/**
		 * Get the total count of refunds from the stored activities.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @param  int $limit The limit of activities to get.
		 *
		 * @return int
		 */
		public function get_activity_refund_count( $campaign_id = false, $limit = false ) {

			if ( $this->is_activities_empty() ) {
				$this->activities = $this->get_activity( $campaign_id, $limit );
			}

			if ( $this->is_activities_empty() ) {
				return 0;
			}

			$refunded_count = 0;

			foreach ( $this->activities as $activity ) {
				if ( 'refunded' === $activity->status ) {
					++$refunded_count;
				}
			}

			return $refunded_count;
		}

		/**
		 * Get activity for the report.
		 *
		 * @since 1.8.1
		 *
		 * @param array $args The array of args.
		 *
		 * @return array
		 */
		public function get_activity( $args = array() ) {

			$defaults = array(
				'activity_filter_types' => array(),
				'activity_action_types' => array(),
				'start_date'            => false,
				'end_date'              => false,
				'status'                => false,
				'campaign_id'           => false,
				'category_id'           => false,
				'limit'                 => false,
				'offset'                => false,
				'ppage'                 => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$activity_filter_types = $args['activity_filter_types'] ?? array();
			$activity_action_types = $args['activity_action_types'] ?? array();
			$start_date            = $args['start_date'] ?? false;
			$end_date              = $args['end_date'] ?? false;
			$status                = $args['status'] ?? false;
			$campaign_id           = $args['campaign_id'] ?? false;
			$category_id           = $args['category_id'] ?? false;
			$limit                 = $args['limit'] ?? false;
			$offset                = $args['offset'] ?? false;
			$ppage                 = $args['ppage'] ?? false;

			if ( ! class_exists( 'Charitable_Admin_Activities' ) || ! class_exists( 'Charitable_Donation_Activities_DB' ) || ! class_exists( 'Charitable_Campaign_Activities_DB' ) ) {
				return;
			}

			$charitable_donation_activities_db = Charitable_Donation_Activities_DB::get_instance();

			// check to see if db tables exist.
			if ( ! $charitable_donation_activities_db->table_exists() ) {
				return;
			}

			$charitable_campaign_activities_db = Charitable_Campaign_Activities_DB::get_instance();

			// check to see if db tables exist.
			if ( ! $charitable_campaign_activities_db->table_exists() ) {
				return;
			}

			// If not passed, grab from the local vars in the class.
			$campaign_id = $campaign_id ? $campaign_id : $this->campaign_id;
			$category_id = absint( $category_id ) > 0 ? $category_id : $this->category_id;

			$admin_activities = new Charitable_Admin_Activities();

			// Get the "raw" activities data.
			$activities = $admin_activities->get_activity(
				array(
					'activity_filter_types' => $activity_filter_types,
					'activity_action_types' => $activity_action_types,
					'campaign_id'           => $campaign_id,
					'category_id'           => $category_id,
					'start_date'            => $this->start_date,
					'end_date'              => $this->end_date,
					'limit'                 => $limit,
					'offset'                => $offset,
				)
			);

			// Get the more "formatted" (with additional data) reports data.
			$report_activities = $admin_activities->get_activity_report_data( $activities );

			return $report_activities;
		}

		/**
		 * Generate Pagination HTML
		 *
		 * @since 1.8.1
		 *
		 * @param  int   $total_pages The total number of pages.
		 * @param  int   $current_page The current page.
		 * @param  int   $total_records Total number of records.
		 * @param  int   $offset The offset.
		 * @param  int   $limit The limit.
		 * @param  array $arr_params The params for the pagination links, which might depend on report type.
		 * @param  int   $dot_threshold The dot threshold.
		 *
		 * @return void
		 */
		public function show_pagination( $total_pages = 1, $current_page = 1, $total_records = false, $offset = false, $limit = 10, $arr_params = array(), $dot_threshold = false ) {

			// Only display if pro.
			if ( ! charitable_is_pro() ) {
				return;
			}

			if ( false === $dot_threshold ) {
				$dot_threshold = $this->pagination_dot_threshold;
			}

			$total_pages = intval( $total_pages );

			echo '<nav class="charitable-reports-pagination-nav">';

			if ( $total_records ) :
				// translators: %d: total number of records.
				echo '<div class="page-item total-count"><span class="page-total">' . sprintf( esc_html__( 'Total: %d', 'charitable' ), intval( $total_records ) ) . '</span></div>';
			endif;

			// If there are no pages, don't display anything further.
			if ( ! $total_pages || $total_pages <= 1 ) {
				echo '</nav>';
				return;
			}

			echo '<ul>';

			if ( intval( $total_pages ) > intval( $dot_threshold ) ) {
				if ( intval( $current_page ) === 1 ) {
					echo '<li class="page-item disabled"><span class="page-link">‹</span></li>';
					echo '<li class="page-item active" aria-current="page"><span class="page-link">1</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) - 1, $arr_params ) ) . '">‹</a></li>';
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( 1 ) ) . '">1</a></li>';
				}

				if ( $total_pages - $current_page > 3 ) {
					if ( intval( $current_page ) > 4 ) {
						echo '<li class="page-item"><span class="page-link">...</span></li>';
						echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) - 1, $arr_params ) ) . '">' . ( intval( $current_page ) - 1 ) . '</a></li>';
						echo '<li class="page-item active" aria-current="page"><span class="page-link">' . ( intval( $current_page ) ) . '</span></li>';
						echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) + 1, $arr_params ) ) . '">' . ( intval( $current_page ) + 1 ) . '</a></li>';
					} else {
						for ( $page_no = 2; $page_no <= 5; $page_no++ ) {
							if ( intval( $current_page ) === $page_no ) {
								echo '<li class="page-item active" aria-current="page"><span class="page-link">' . ( intval( $page_no ) ) . '</span></li>';
							} else {
								echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( $page_no, $arr_params ) ) . '">' . intval( $page_no ) . '</a></li>';
							}
						}
					}
				}

				if ( $total_pages - $current_page < 4 ) {
					echo '<li class="page-item"><span class="page-link">...</span></li>';
					for ( $page_no = $total_pages - 4; $page_no <= $total_pages - 1; $page_no++ ) {
						if ( intval( $current_page ) === $page_no ) {
							echo '<li class="page-item active" aria-current="page"><span class="page-link">' . intval( $page_no ) . '</span></li>';
						} else {
							echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( $page_no, $arr_params ) ) . '">' . intval( $page_no ) . '</a></li>';
						}
					}
				} else {
					echo '<li class="page-item"><span class="page-link">...</span></li>';
				}

				if ( intval( $current_page ) === intval( $total_pages ) ) {
					echo '<li class="page-item active" aria-current="page"><span class="page-link">' . ( intval( $total_pages ) ) . '</span></li>';
					echo '<li class="page-item disabled"><span class="page-link">›</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( $total_pages, $arr_params ) ) . '">' . ( esc_html( $total_pages ) ) . '</a></li>';
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) + 1, $arr_params ) ) . '">›</a></li>';
				}
			} else {
				if ( intval( $current_page ) === 1 ) {
					echo '<li class="page-item disabled"><span class="page-link">‹</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" ' . $this->get_pagination_data_attributes( $total_pages, $current_page, $total_records, $offset, intval( $current_page - 1 ), $limit, $arr_params ) . ' href="' . esc_url( $this->get_pagination_url( intval( $current_page ) - 1, $arr_params ) ) . '">‹</a></li>'; // phpcs:ignore
				}
				for ( $page_no = 1; $page_no <= $total_pages; $page_no++ ) {
					if ( intval( $current_page ) === $page_no ) {
						echo '<li class="page-item active" aria-current="page"><span class="page-link">' . intval( $page_no ) . '</span></li>';
					} else {
						echo '<li class="page-item"><a class="page-link" ' . $this->get_pagination_data_attributes( $total_pages, $current_page, $total_records, $offset, $page_no, $limit, $arr_params ) . ' href="' . esc_url( $this->get_pagination_url( $page_no, $arr_params ) ) . '">' . intval( $page_no ) . '</a></li>'; // phpcs:ignore
					}
				}
				if ( intval( $current_page ) === intval( $total_pages ) ) {
					echo '<li class="page-item disabled"><span class="page-link">›</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" ' . $this->get_pagination_data_attributes( $total_pages, $current_page, $total_records, $offset, intval( $current_page + 1 ), $limit, $arr_params ) . ' href="' . esc_url( $this->get_pagination_url( intval( $current_page ) + 1, $arr_params ) ) . '">›</a></li>'; // phpcs:ignore
				}
			}

			echo '</ul></nav>';
		}

		/**
		 * Get link for the charitable pagination
		 *
		 * @since 1.8.1.3
		 *
		 * @param  string $total_pages Current page number.
		 * @param  string $current_page Current page.
		 * @param  string $total_records Total number of records.
		 * @param  string $offset The offset.
		 * @param  string $ppage The ppage.
		 * @param  string $limit The limit.
		 * @param  array  $arr_params Params to build the attributes.
		 *
		 * @return string
		 */
		public function get_pagination_data_attributes( $total_pages = '', $current_page = '', $total_records = 0, $offset = 0, $ppage = 1, $limit = 10, $arr_params = array() ) {

			$data_attributes = array(
				'total-pages'   => $total_pages,
				'current-page'  => $current_page,
				'total-records' => $total_records,
				// 'offset'        => $offset,
				'ppage'         => $ppage, // a page in the results.
				'limit'         => $limit, // the limit of items per page.
			);

			if ( ! empty( $arr_params ) ) {
				foreach ( $arr_params as $key => $value ) {
					$data_attributes[ sanitize_title( $key ) ] = $value;
				}
			}

			// create a string of data attributes for an HTML element based on $data_attributes.
			$data_attributes_string = '';
			foreach ( $data_attributes as $key => $value ) {
				$data_attributes_string .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}

			return $data_attributes_string;
		}

		/**
		 * Get link for the charitable pagination
		 *
		 * @since 1.8.1
		 *
		 * @param  string $page_number Current page number.
		 * @param  array  $arr_params Params to build the URL.
		 *
		 * @return array
		 */
		public function get_pagination_url( $page_number, $arr_params = array() ) {

			$arr_params['ppage'] = $page_number;

			return add_query_arg( $arr_params, admin_url( 'admin.php' ) );
		}

		/**
		 * Generate the HTML for the pagination.
		 *
		 * @since 1.8.1
		 *
		 * @param  int    $limit The limit of items per page.
		 * @param  int    $current_page The (current) page number.
		 * @param  string $report_type The report type.
		 *
		 * @return float
		 */
		public function generate_pagination_html( $limit = 10, $current_page = 1, $report_type = 'donors-top' ) {

			$donors = (array) $this->donors;

			$limit = charitable_reports_is_ajax() && isset( $_POST['limit'] ) && is_numeric( $_POST['limit'] ) ? intval( $_POST['limit'] ) : $limit; // phpcs:ignore
			$limit = false !== $limit && is_numeric( $limit ) ? $limit : charitable_reports_get_pagination_per_page();

			$current_page = charitable_reports_is_ajax() && isset( $_POST['ppage'] ) && is_numeric( $_POST['ppage'] ) ? intval( $_POST['ppage'] ) : $current_page; // phpcs:ignore
			$current_page = is_numeric( $current_page ) ? $current_page : 1;

			$pagination = array(
				'length'      => isset( $_GET['limit'] ) ? (int) $_GET['limit'] : $limit, // phpcs:ignore
				'total'       => 0 !== $this->donors_total ? $this->donors_total : count( $donors ),
				'currentPage' => isset( $_GET['ppage'] ) ? (int) $_GET['ppage'] : $current_page, // phpcs:ignore
			);

			$pagination['total_pages'] = ceil( $pagination['total'] / $pagination['length'] );
			$pagination['offset']      = ( $pagination['currentPage'] * $pagination['length'] ) - $pagination['length'];

			// The params for the pagination links, which might depend on report type.
			$arr_params = array(
				'page'        => 'charitable-reports',
				'tab'         => 'donors',
				'report-type' => isset( $_GET['report_type'] ) ? sanitize_text_field( wp_unslash( $_GET['report_type'] ) ) : $report_type, // phpcs:ignore
			);

			ob_start();

			$this->show_pagination( $pagination['total_pages'], $pagination['currentPage'], $pagination['total'], $pagination['offset'], $pagination['length'], $arr_params );

			return ob_get_clean();
		}

		/**
		 * Get the activity icon url from a status string.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $icon The icon name.
		 *
		 * @return string
		 */
		public function get_activity_icon_url( $icon = false ) {

			if ( false === $icon || '' === $icon ) {
				return false;
			}

			return charitable()->get_path( 'directory', false ) . 'assets/images/icons/activities/' . $icon . '.png';
		}

		/**
		 * Get the top donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 * @param  int $offset The offset of donors to get.
		 *
		 * @return string
		 */
		public function get_top_donors( $limit = false, $offset = false ) { // phpcs:ignore
			if ( empty( $this->donors ) ) {
				return;
			}

			return $this->donors;
		}

		/**
		 * Get the top donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 *
		 * @return string
		 */
		public function get_recurring_donors( $limit = false ) { // phpcs:ignore
			if ( empty( $this->donors ) ) {
				return;
			}

			return $this->donors;
		}

		/**
		 * Get the first time donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 *
		 * @return string
		 */
		public function get_first_time_donors( $limit = false ) { // phpcs:ignore
			if ( empty( $this->donors ) ) {
				return;
			}

			return $this->donors;
		}

		/**
		 * Get the donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $args The array of args.
		 *
		 * @return array|bool
		 */
		public function get_donor_report_by_type( $report_type = false, $args = array() ) {

			$defaults = array(
				'limit'  => false,
				'offset' => false,
				'ppage'  => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$limit  = $args['limit'] ?? false;
			$offset = $args['offset'] ?? false;
			$ppage  = $args['ppage'] ?? false;

			if ( ! $offset && $ppage && $limit ) {
				$offset = ( $ppage - 1 ) * $limit;
			}

			switch ( $report_type ) {
				case 'donors-top':
					return ( $this->get_top_donors( $limit, $offset ) );
					break; // phpcs:ignore
				case 'donors-recurring':
					return ( $this->get_recurring_donors( $limit, $offset ) );
					break; // phpcs:ignore
				case 'donors-first-time':
					return ( $this->get_first_time_donors( $limit, $offset ) );
					break; // phpcs:ignore

				default:
					return false;
					break; // phpcs:ignore
			}
		}

		/**
		 * Return the HTML of the filter select dropdown.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $selected_value The selected value.
		 *
		 * @return string
		 */
		public function get_donor_report_type_dropdown( $selected_value = false ) {

			$options_values = apply_filters(
				'charitable_dashboard_donor_report_type_filter_values',
				array(
					'donors-top'        => __( 'Top Donors', 'charitable' ),
					'donors-first-time' => __( 'First Time Donors', 'charitable' ),
					'donors-recurring'  => __( 'Recurring Donors', 'charitable' ),
				)
			);

			ob_start();

			?>

			<select name="action" id="report-donor-type-filter">
				<?php foreach ( $options_values as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Get the top donors for the overview report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 *
		 * @return string
		 */
		public function get_top_donors_overview( $limit = false ) { // phpcs:ignore
			if ( empty( $this->donations ) ) {
				return;
			}

			$donations_by_donors = (array) $this->donations;
			$unique_donors       = array();

			// sort multi-dimensional array by 'amount'.
			usort(
				$donations_by_donors,
				function ( $a, $b ) {
					return $b->total_amount - $a->total_amount;
				}
			);

			/*
			ORPHANED ROWS ARE DROPPED, not skipped.

			Both guards below used to `continue`, which skipped the donor lookup but left the row
			in $donations_by_donors - so it still reached generate_top_donors(), where
			`! empty( $top_donor->donor_name ) ? ... : 'Unknown'` printed it as "Unknown". That
			produced two visible faults from one cause.

			FAULT 1 - a donation whose donor row no longer exists in wp_charitable_donors, a
			deleted or erased donor leaving campaign_donations behind, appeared on a Top DONORS
			card as "Unknown": no name, no email, no donor to click through to.

			FAULT 2 - because the `continue` fired BEFORE the $unique_donors aggregation below, the
			row never received total_donation_amount / total_donation_count either, so
			generate_top_donors()'s fallback showed a SINGLE donation's amount and a hardcoded
			count of 1. A donor with two $25 donations read "$25.00 / 1 donation" instead of
			$50.00 / 2 - understating the figure by half.

			Dropping the row is the right resolution rather than aggregating it under a "Deleted
			donor" label: this card exists so somebody can recognise and contact their best
			supporters, which a deleted donor serves neither of - and where the donor was erased on
			request, re-surfacing their total partially re-identifies them. It is also what the
			donor_id guard already intended; it just failed to do it.

			NO FINANCIAL FIGURE MOVES. The tiles and the breakdown read $this->donations directly
			(see get_donations_total()), and $donations_by_donors is a value copy of it, so the
			money stays in every total - it simply stops being attributed to a donor who cannot be
			named. If every row is orphaned the card falls through to generate_top_donors()'s own
			"no donors in this date range" state.

			@since 1.8.14
			*/
			$resolved_donors = array();

			foreach ( $donations_by_donors as $donation ) {

				if ( empty( $donation->donor_id ) || 0 === $donation->donor_id ) {
					continue;
				}

				$donor = charitable_get_table( 'donors' )->get_personal_data_by_donor_id( $donation->donor_id );

				if ( empty( $donor ) ) {
					continue;
				}

				$donor = $donor[0];

				// combine first and last name from $donor into $donor_name, if either exist.
				$donor_name  = ( ! empty( $donor->first_name ) || ! empty( $donor->last_name ) ? $donor->first_name . ' ' . $donor->last_name : 'Unknown' );
				$donor_email = ( ! empty( $donor->email ) ) ? $donor->email : '';

				$donation->donor_name  = trim( $donor_name );
				$donation->donor_email = $donor_email;

				$donation->donor_avatar_url = isset( $donation->user_id ) && $donation->user_id !== 0 ? get_avatar_url( $donation->user_id, 32 ) : 0;

				/*
				Guard: charitable_get_donation() returns false if the donation post has been
				deleted but a row still exists in the reports cache. Chaining ->get() on false
				fatals the page.
				*/
				$donation_object         = charitable_get_donation( $donation->donation_id );
				$donation->donation_type = $donation_object ? $donation_object->get( 'donation_type' ) : '';

				/*
				A `donation_parent_id` assignment stood here and has been dropped as dead code. It
				read `$donations_by_donors[ $index ]->donation_parent_id = $donation->donation_parent_id`,
				and since $donation IS $donations_by_donors[ $index ] - array elements holding
				objects are handles to the same instance - it assigned the property to itself.
				*/

				$resolved_donors[] = $donation;

				if ( ! isset( $unique_donors[ $donation->donor_id ] ) ) {
					$unique_donors[ $donation->donor_id ]['amount'] = $donation->total_amount;
					$unique_donors[ $donation->donor_id ]['count']  = 1;
				} else {
					$unique_donors[ $donation->donor_id ]['amount'] += $donation->total_amount;
					$unique_donors[ $donation->donor_id ]['count']   = $unique_donors[ $donation->donor_id ]['count'] + 1;
				}
			}

			// Only the rows that resolved to a real donor go on from here.
			$donations_by_donors = $resolved_donors;

			// go back and add the total donation amount to the $donations_by_donors array.
			foreach ( $donations_by_donors as $index => $donation ) {

				if ( ! empty( $unique_donors[ $donation->donor_id ] ) ) {
					$donations_by_donors[ $index ]->total_donation_amount = $unique_donors[ $donation->donor_id ]['amount'];
					$donations_by_donors[ $index ]->total_donation_count  = $unique_donors[ $donation->donor_id ]['count'];
				}
			}

			// sort multi-dimensional array by 'total_donation_amount'.
			// @since 1.8.8.6 - Fixed undefined property warning by checking if property exists before accessing.
			usort(
				$donations_by_donors,
				function ( $a, $b ) {
					// @since 1.8.8.6 - Check if total_donation_amount exists, default to 0 if not set.
					$a_amount = isset( $a->total_donation_amount ) ? $a->total_donation_amount : 0;
					$b_amount = isset( $b->total_donation_amount ) ? $b->total_donation_amount : 0;
					return $b_amount - $a_amount;
				}
			);

			return $donations_by_donors;
		}

		/**
		 * Get the name of the cache value (or key) for the report we use for the database.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $post_status The post status.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 *
		 * @return string
		 */
		public function get_cache_key( $report_type = 'overview', $start_date = false, $end_date = false, $post_status = false, $campaign_id = false, $category_id = false ) {

			$cache_key = sanitize_title( $start_date . $end_date . str_replace( 'charitable', '', $post_status ) . $campaign_id . $category_id );

			// remove all dashes from the cache key.
			$cache_key = 'wpch-report-' . $report_type . '-' . str_replace( '-', '', $cache_key );

			return $cache_key;
		}

		/**
		 * Get the cached report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $post_status The post status.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 *
		 * @return array|bool
		 */
		public function get_cached_report( $report_type = 'overview', $start_date = false, $end_date = false, $post_status = false, $campaign_id = false, $category_id = false ) {

			if ( ! $this->maybe_cache_report( true ) ) {
				return false;
			}

			$cache_key = $this->get_cache_key( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id );

			$data = get_transient( $cache_key );

			if ( false === $data ) {
				return false;
			}

			return $data;
		}

		/**
		 * Set the cached report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $post_status The post status.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 * @param  array  $data The data to cache.
		 *
		 * @return bool
		 */
		public function set_cached_report( $report_type = 'overview', $start_date = false, $end_date = false, $post_status = false, $campaign_id = false, $category_id = false, $data = array() ) {

			if ( ! $this->maybe_cache_report( true ) ) {
				return false;
			}

			$cache_key = $this->get_cache_key( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id );

			$was_set = set_transient( $cache_key, $data, MINUTE_IN_SECONDS );

			return $was_set;
		}

		/**
		 * Check if the report should be cached.
		 *
		 * @since 1.8.1
		 *
		 * @param  bool $force_cache Whether to force the cache.
		 *
		 * @return bool
		 */
		public function maybe_cache_report( $force_cache = false ) {

			// Check the globals to see if caching is enabled specifically for reports.
			if ( $force_cache ) {
				return true;
			}
			if ( charitable_is_debug() ) {
				return false;
			}
			if ( defined( 'CHARITABLE_REPORTS_NO_CACHE' ) && CHARITABLE_REPORTS_NO_CACHE ) { // phpcs:ignore
				return false;
			}

			return true;
		}

		/**
		 * This accepts a report type and date and attemps to create a report that has multiple data points
		 *
		 * @param  string $report_type The type of report to create.
		 * @param  array  $args The array of args.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_data( $report_type = 'overview', $args = array() ) {

			global $wpdb;

			$defaults = array(
				'report_type' => 'overview',
				'start_date'  => false,
				'end_date'    => false,
				'post_status' => 'charitable-completed',
				'campaign_id' => false,
				'category_id' => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$report_type = $args['report_type'] ?? 'overview';
			$start_date  = $args['start_date'] ?? false;
			$end_date    = $args['end_date'] ?? false;
			$post_status = $args['post_status'] ?? 'charitable-completed';
			$campaign_id = $args['campaign_id'] ?? false;
			$category_id = $args['category_id'] ?? false;

			// Check the post_status and set it to the default if it's not a valid status.
			if ( ! in_array( $post_status, array( 'charitable-completed', 'charitable-pending', 'charitable-failed', 'charitable-cancelled', 'charitable-refunded' ), true ) ) {
				$post_status = 'charitable-completed';
			}

			$this->donations = ! charitable_is_pro() && $report_type !== 'overview' ? $this->get_data_sample( $report_type ) : false;

			// Determine if this particular report has been cached recently.
			$this->donations = false; // $this->get_cached_report( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id );

			if ( false === $this->donations ) {

				// No cache? Let's run the data.

				$where_sql   = array();
				$where_sql[] = 'WHERE 1=1';
				$where_sql[] = ( intval( $campaign_id ) > 0 ) ? 'cd.campaign_id = ' . intval( $campaign_id ) : false;
				$where_sql[] = 'p.post_type = "%s"';
				$where_sql[] = 'p.post_status = "' . $post_status . '"';
				$where_sql[] = ( $start_date ) ? "p.post_date >= '" . $start_date . " 00:00:00'" : false;
				$where_sql[] = ( $end_date ) ? "p.post_date <= '" . $end_date . " 23:59:59'" : false;
				$where_sql[] = ( intval( $category_id ) > 0 ) ? 'tr.term_taxonomy_id = ' . intval( $category_id ) : false;
				$where_sql[] = 'pm1.meta_key = "donation_gateway"';
				// remove all empty values from the array.
				$where_sql  = array_filter( $where_sql );
				$where_args = implode( ' AND ', $where_sql );

				$left_join   = array();
				$left_join[] = $wpdb->prefix . 'charitable_campaign_donations cd ON p.ID = cd.donation_id';
				$left_join[] = $wpdb->prefix . 'charitable_donors cdonors ON cd.donor_id = cdonors.donor_id';
				$left_join[] = $wpdb->prefix . 'postmeta pm1 ON p.ID = pm1.post_id';
				$left_join[] = ( intval( $category_id ) > 0 ) ? $wpdb->prefix . 'term_relationships tr ON tr.object_id = campaign_id' : false;
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
				p.post_parent AS donation_parent_id,
				cdonors.first_name AS first_name,
				cdonors.last_name AS last_name,
				cdonors.email AS email
				FROM $wpdb->posts p
				" . $left_join_args . '
				' . $where_args . '
				GROUP BY p.ID';

				// get all donations (posts with a post type of donation) between a start date and an end date.
				$this->donations = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						$sql, // phpcs:ignore
						'donation'
					)
				);

				// Save this to the cache.
				// $this->set_cached_report( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id, $this->donations );.

			}

			$this->init_axis_with_donations( $this->donations, $start_date, $end_date );

			return $this->donations;
		}

		/**
		 * This accepts a report type and date and attemps to create a report specific to a donor type report.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The type of report to create.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 *
		 * @return array
		 */
		public function get_donors_data( $report_type = 'donors-top', $start_date = false, $end_date = false, $campaign_id = false, $category_id = false ) { // phpcs:ignore

			global $wpdb;

			// Determine if this particular report gets sample data, or if not if it's has been cached recently.
			$this->donors = ! charitable_is_pro() ? $this->get_data_sample( $report_type ) : false; // $this->get_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id );

			if ( false === $this->donors ) {

				// No cache? Let's run the data.

				$having_sql = '';
				$order_by   = '';

				switch ( $report_type ) {
					case 'donors-recurring':
						$having_sql = 'HAVING total_count_donations > 1';
						$order_by   = 'ORDER BY %s DESC';
						$limit      = 'LIMIT ' . apply_filters( 'charitable_dashboard_donor_report_recurring_sql_limit', 100 );

						$sql = "SELECT SUM( ccd.amount ) AS total_amount,
								AVG( ccd.amount ) AS average,
								COUNT( ccd.donation_id ) AS total_count_donations,
								COUNT( DISTINCT( ccd.campaign_id ) ) AS total_count_campaigns,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								{$limit}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								$sql, // phpcs:ignore
								'total_amount'
							)
						);

						break;

					case 'donors-first-time':
						$having_sql = 'HAVING total_count_donations = 1';
						$order_by   = 'ORDER BY %s DESC'; // not currently being used.
						$limit      = 'LIMIT ' . apply_filters( 'charitable_dashboard_donor_report_first_time_sql_limit', 100 );

						$sql = "SELECT ccd.amount AS total_amount,
								COUNT( ccd.donation_id ) AS total_count_donations,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								GROUP BY cd.donor_id

								{$having_sql}

								ORDER BY last_donation_date DESC

								{$limit}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								$sql // phpcs:ignore
							)
						);

						break;

					default:
						$order_by = 'ORDER BY %s DESC';
						$limit    = 'LIMIT ' . apply_filters( 'charitable_dashboard_donor_report_default_sql_limit', 100, $report_type );

						$sql = "SELECT SUM( ccd.amount ) AS total_amount,
								AVG( ccd.amount ) AS average,
								COUNT( ccd.donation_id ) AS total_count_donations,
								COUNT( DISTINCT( ccd.campaign_id ) ) AS total_count_campaigns,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								{$limit}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								$sql, // phpcs:ignore
								'total_amount'
							)
						);

						break;
				}

				// Save this to the cache.
				// $this->set_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id, $this->donors );.

				return $this->donors;

			}
		}

		/**
		 * This accepts a report type and date and attemps to create a report that has multiple data points
		 *
		 * @since  1.8.1
		 * @since  1.8.3 Updated w/ limit and offset sql, etc.
		 *
		 * @param  string $report_type The type of report to create.
		 * @param  array  $args The array of args.
		 *
		 * @return array
		 */
		public function get_donors_data_array( $report_type = 'donors-top', $args = array() ) {

			global $wpdb;

			$defaults = array(
				'report_type' => 'donors-top',
				'start_date'  => false,
				'end_date'    => false,
				'campaign_id' => false,
				'category_id' => false,
				'limit'       => false,
				'offset'      => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$report_type = $args['report_type'] ?? 'donors-top';
			$start_date  = $args['start_date'] ?? false;
			$end_date    = $args['end_date'] ?? false;
			$campaign_id = $args['campaign_id'] ?? false;
			$category_id = $args['category_id'] ?? false;
			$limit       = $args['limit'] ?? false;
			$offset      = $args['offset'] ?? false;
			$ppage       = $args['ppage'] ?? false;

			// Determine if this particular report gets sample data, or if not if it's has been cached recently.
			$this->donors = ! charitable_is_pro() ? $this->get_data_sample( $report_type ) : false; // $this->get_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id );

			if ( false === $this->donors ) {

				// No cache? Let's run the data.

				$having_sql = '';
				$order_by   = '';
				$where      = '';
				// generate mysql LIMIT and OFFSET.
				if ( ! $offset && $ppage && $limit ) {
					$offset = ( $ppage - 1 ) * $limit;
				}
				$limit_sql  = ( $limit ) ? 'LIMIT ' . $limit : '';
				$offset_sql = ( $offset ) ? 'OFFSET ' . $offset : '';

				switch ( $report_type ) {
					case 'donors-recurring':
						$having_sql = 'HAVING total_count_donations > 1';
						$order_by   = 'ORDER BY total_amount DESC';

						$query = "SELECT SUM( ccd2.amount ) AS total_amount,
									AVG( ccd2.amount ) AS average,
									COUNT( ccd2.donation_id ) AS total_count_donations,
									COUNT( DISTINCT( ccd2.campaign_id ) ) AS total_count_campaigns,
									last_donation.post_date AS last_donation_date,
									last_campaign.post_title AS last_campaign_title,
									last_campaign.ID AS last_campaign_id,
									cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount, ccd.campaign_donation_id AS CAMPAIGN_DONATION_ID

									FROM {$wpdb->prefix}charitable_donors cd

									JOIN ( 	SELECT {$wpdb->prefix}charitable_campaign_donations.donor_id, {$wpdb->prefix}charitable_campaign_donations.donation_id, {$wpdb->prefix}charitable_campaign_donations.campaign_id, {$wpdb->prefix}charitable_campaign_donations.amount, {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
											FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN ( SELECT MAX(campaign_donation_id) AS max_campaign_donation_id, donor_id FROM {$wpdb->prefix}charitable_campaign_donations JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed') GROUP BY donor_id ) last_campaign_donation_id ON last_campaign_donation_id.max_campaign_donation_id = {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
										) ccd ON ccd.donor_id = cd.donor_id

									JOIN ( 	SELECT donor_id, donation_id, campaign_id, amount, campaign_donation_id FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID
											WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed')

										) ccd2 ON ccd2.donor_id = cd.donor_id

									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_donation ON ccd.donation_id = last_donation.ID
									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_campaign ON ccd.campaign_id = last_campaign.ID

									{$where}

									GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$query . ' ' . $limit_sql . ' ' . $offset_sql // phpcs:ignore
						);

						break;

					case 'donors-first-time':
						$having_sql = 'HAVING total_count_donations = 1';
						$order_by   = 'ORDER BY last_donation_date ASC';

						$query = "SELECT ccd.amount AS total_amount,
								COUNT( ccd.donation_id ) AS total_count_donations,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								{$where}

								GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$query . ' ' . $limit_sql . ' ' . $offset_sql // phpcs:ignore
						);

						break;

					default:
						$order_by = 'ORDER BY total_amount DESC';

						$query = "SELECT SUM( ccd2.amount ) AS total_amount,
									AVG( ccd2.amount ) AS average,
									COUNT( ccd2.donation_id ) AS total_count_donations,
									COUNT( DISTINCT( ccd2.campaign_id ) ) AS total_count_campaigns,
									last_donation.post_date AS last_donation_date,
									last_campaign.post_title AS last_campaign_title,
									last_campaign.ID AS last_campaign_id,
									cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount, ccd.campaign_donation_id AS CAMPAIGN_DONATION_ID

									FROM {$wpdb->prefix}charitable_donors cd

									JOIN ( 	SELECT {$wpdb->prefix}charitable_campaign_donations.donor_id, {$wpdb->prefix}charitable_campaign_donations.donation_id, {$wpdb->prefix}charitable_campaign_donations.campaign_id, {$wpdb->prefix}charitable_campaign_donations.amount, {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
											FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN ( SELECT MAX(campaign_donation_id) AS max_campaign_donation_id, donor_id FROM {$wpdb->prefix}charitable_campaign_donations JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed') GROUP BY donor_id ) last_campaign_donation_id ON last_campaign_donation_id.max_campaign_donation_id = {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
										) ccd ON ccd.donor_id = cd.donor_id

									JOIN ( 	SELECT donor_id, donation_id, campaign_id, amount, campaign_donation_id FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID
											WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed')

										) ccd2 ON ccd2.donor_id = cd.donor_id

									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_donation ON ccd.donation_id = last_donation.ID
									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_campaign ON ccd.campaign_id = last_campaign.ID

									{$where}

									GROUP BY cd.donor_id

									{$having_sql}

									{$order_by}";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$query . ' ' . $limit_sql . ' ' . $offset_sql // phpcs:ignore
						);

						break;
				}

				$total_query        = 'SELECT COUNT(1) FROM (' . $query . ') AS combined_table';
				$this->donors_total = $wpdb->get_var( $total_query ); // phpcs:ignore

				// Save this to the cache.
				// $this->set_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id, $this->donors );.

				return $this->donors;

			}
		}

		/**
		 * This accepts donations and attempts to create data for the headline chart with donation data.
		 *
		 * @param  array  $donations An array of donations.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function init_axis_with_donations( $donations = array(), $start_date = false, $end_date = false ) {

			if ( false === $donations ) {
				return;
			}

			$date_data     = array();
			$donation_data = array();

			foreach ( $donations as $donation ) {

				$donation_day = gmdate( 'M d', strtotime( $donation->post_date ) );

				if ( ! in_array( $donation_day, $date_data, true ) ) {
					$date_data[] = $donation_day;
				}

				if ( isset( $donation_data[ $donation_day ]['amount'] ) ) {
					$donation_data[ $donation_day ]['amount'] += $donation->total_amount;
				} else {
					$donation_data[ $donation_day ]['amount'] = $donation->total_amount;
				}

			}

			$this->set_date_axis( $date_data, $start_date, $end_date );
			$this->set_donation_axis( $donation_data, $start_date, $end_date );
		}

		/**
		 * This generates the donation axis for the headline chart.
		 *
		 * @param  array  $donation_data An array of donations.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function set_donation_axis( $donation_data, $start_date, $end_date ) {

			$this->donation_axis = array();

			$days = $this->get_days_between_dates( $start_date, $end_date );

			if ( ! $days || empty( $days ) ) {
				return;
			}

			foreach ( $days as $day ) {
				$this->donation_axis[ $day ] = ! empty( $donation_data[ $day ]['amount'] ) ? $donation_data[ $day ]['amount'] : 0;
			}

			// Remove the keys from the array but keep the order of the values.
			$this->donation_axis = array_values( $this->donation_axis );

			// if the days is only one day, add a day before and after that date so the chart has at least 3 days for display.
			if ( count( $days ) === 1 ) {
				$this->donation_axis = array_merge( array( 0 ), $this->donation_axis, array( 0 ) );
			}

			return $this->donation_axis;
		}

		/**
		 * This generates the date axis for the headline chart.
		 *
		 * @param  array  $date_data An array of dates.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function set_date_axis( $date_data, $start_date, $end_date ) {

			$days = $this->get_days_between_dates( $start_date, $end_date, false );

			// if the days is only one day, then add a day before and after that date so the chart has at least 3 days for display.
			if ( count( $days ) === 1 ) {
				$days = array_merge( array( gmdate( 'M d', strtotime( $start_date . ' -1 day' ) ) ), $days, array( gmdate( 'M d', strtotime( $end_date . ' +1 day' ) ) ) );
			}

			$this->date_axis = $days;

			return $days;
		}

		/**
		 * Util function that gets dates between two given dates.
		 *
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $array_key_format The format of the array key.
		 *
		 * @since  1.8.1
		 *
		 * @return mixed
		 */
		public function get_days_between_dates( $start_date, $end_date, $array_key_format = 'Y-m-d' ) { // phpcs:ignore
			$days = array();

			/*
			Both constructions are guarded, and the try MUST wrap both: unguarded, guarding only
			the first leaves half the bug.

			On PHP 8.3+ `new DateTime( 'not a date' )` throws DateMalformedStringException where
			older PHP returned false, so an unguarded call turns a bad date into an uncaught fatal.
			That is reachable: class-charitable-reports-ajax.php takes $_POST['start_date'] and
			$_POST['end_date'] through sanitize_text_field() only, which strips tags and normalises
			whitespace and does NOT validate a date.

			Catching \Exception rather than the 8.3 class name keeps this working on the older PHP
			versions that threw a plain Exception here.

			An empty range is returned deliberately, NOT today's date. Every caller renders
			whatever days come back, so falling back to "now" would show a confident one-day report
			for a range nobody asked for. A money report showing nothing is recoverable; one
			showing the wrong period silently is not.

			A FALSY date must keep normalising to "now" and must not land here: `new DateTime( '' )`
			and `new DateTime( false )` both mean now and throw nothing, so they pass through
			untouched. That behaviour is load-bearing - charitable_reports_donation_breakdown()
			reads its query bounds back off this return precisely because of it.

			@since 1.8.14
			*/
			/*
			NULL IS COERCED TO '' FIRST, and that is not cosmetic.

			`new DateTime( null )` and `new DateTime( '' )` both mean "now" and both throw
			nothing, so the falsy-means-now behaviour described above is identical either way.
			But on PHP 8.1+ passing null to a non-nullable string parameter is DEPRECATED, so the
			null form logs two deprecation notices per call - and null is a legitimate input
			here, not an error path. Coercing keeps the semantics exactly and stops the log
			noise. Found by fuzzing this method with ( null, null ).
			*/
			$start_date = null === $start_date ? '' : $start_date;
			$end_date   = null === $end_date ? '' : $end_date;

			try {
				$current_date = new DateTime( $start_date );
				$end_date     = new DateTime( $end_date );
			} catch ( Exception $e ) {
				return $days;
			}

			while ( $current_date <= $end_date ) {
				if ( $array_key_format ) :
					$days[ $current_date->format( $array_key_format ) ] = $current_date->format( 'M d' );
				else :
					$days[] = $current_date->format( 'M d' );
				endif;

				$current_date->modify( '+1 day' );
			}

			return $days;
		}

		/**
		 * This accepts a report type and date and attemps to create a refund "report".
		 *
		 * @since  1.8.1
		 * @version 1.8.8.6
		 *
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $limit The limit of refunds to get.
		 *
		 * @return array
		 */
		public function get_refunds( $campaign_id = false, $limit = false ) { // phpcs:ignore

			global $wpdb;

			$where_sql   = array();
			$prepare_args = array();

			$where_sql[] = 'p.post_status = %s';
			$prepare_args[] = 'charitable-refunded';

			// add to $where_sql depending on the values of $start_date and $end_date.
			if ( $this->start_date ) {
				$where_sql[] = 'p.post_date >= %s';
				$prepare_args[] = $this->start_date . ' 00:00:00';
			}
			if ( $this->end_date ) {
				$where_sql[] = 'p.post_date <= %s';
				$prepare_args[] = $this->end_date . ' 23:59:59';
			}

			$where_sql[] = 'pm1.meta_key = %s';
			$prepare_args[] = 'donation_gateway';

			$where_clause = implode( ' AND ', $where_sql );

			// Build prepare args with post_type first, then where clause args.
			$all_prepare_args = array_merge( array( 'donation' ), $prepare_args );

			// get all donations (posts with a post type of donation) between a start date and an end date.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// $where_clause is built with placeholders (%s) that are properly processed by $wpdb->prepare().
			// The interpolation is safe because all values in $where_clause are placeholders, not raw values.
			$refunded_donations = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT SUM(cd.amount) AS total_amount,
						COUNT(p.ID) AS total_number_of_refunds,
						cd.donation_id AS donation_id,
						cd.campaign_id AS campaign_id,
						cd.donor_id AS donor_id,
						cd.amount AS amount,
						pm1.meta_value AS payment_gateway,
						p.post_date AS post_date,
						p.post_date_gmt AS post_date_gmt
					FROM {$wpdb->posts} p
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations cd ON p.ID = cd.donation_id
					LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id
					WHERE p.post_type = %s AND {$where_clause}
					GROUP BY p.ID",
						...$all_prepare_args
					)
				);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			$refunds_by_day                  = array();
			$refunded_donations_total_amount = 0;
			$refunded_donations_total_count  = 0;

			$days = $this->get_days_between_dates( $this->start_date, $this->end_date );

			/**
			 * Bucket the refunds by day once, up front, then read from that.
			 *
			 * The day loop below used to iterate the day LABEL ('M d') rather than the
			 * 'Y-m-d' key, so strtotime() stamped every bucket with the CURRENT year. The
			 * Refunds column and Net in the breakdown table therefore read 0 for any range
			 * outside the current year, and for a multi-year range refunds from different
			 * years merged into a single month-day bucket. (The totals were already
			 * computed up front, so the Refunds card itself was correct.)
			 *
			 * Buckets on post_date, matching the WHERE clause above and the donation series
			 * in get_donations_by_day(). Bucketing on post_date_gmt while filtering on
			 * post_date could put a late-evening refund in a day outside the range on a
			 * non-UTC site.
			 *
			 * The GROUP BY p.ID in the query already guarantees one row per donation, so no
			 * per-day dedup is needed. The previous "$donation_id > 0" guard also silently
			 * dropped a refunded donation that has no campaign_donations row from the totals;
			 * such a refund is now counted, with a zero amount, so it stays visible.
			 */
			$refunds_by_date = array();

			if ( ! empty( $refunded_donations ) ) {
				foreach ( $refunded_donations as $refunded_donation ) {
					$refund_day = gmdate( 'Y-m-d', strtotime( $refunded_donation->post_date ) );

					if ( ! isset( $refunds_by_date[ $refund_day ] ) ) {
						$refunds_by_date[ $refund_day ] = array(
							'amount' => 0,
							'count'  => 0,
						);
					}

					$refunds_by_date[ $refund_day ]['amount'] += floatval( $refunded_donation->amount );
					++$refunds_by_date[ $refund_day ]['count'];

					$refunded_donations_total_amount += floatval( $refunded_donation->amount );
					++$refunded_donations_total_count;
				}
			}

			foreach ( $days as $day_unformatted => $day_label ) {
				$donation_day = gmdate( 'Y-m-d', strtotime( $day_unformatted ) );

				$refunds_by_day[ $donation_day ]['label']  = gmdate( 'F d, Y', strtotime( $day_unformatted ) );
				$refunds_by_day[ $donation_day ]['amount'] = isset( $refunds_by_date[ $donation_day ] ) ? $refunds_by_date[ $donation_day ]['amount'] : 0;
				$refunds_by_day[ $donation_day ]['donors'] = isset( $refunds_by_date[ $donation_day ] ) ? $refunds_by_date[ $donation_day ]['count'] : 0;
			}

			return array(
				'donations'      => $refunded_donations,
				'total_amount'   => $refunded_donations_total_amount,
				'total_count'    => $refunded_donations_total_count,
				'refunds_by_day' => $refunds_by_day,
			);
		}


		/**
		 * This accepts a report type and date and attemps to create a report that has multiple data points.
		 *
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function get_donation_breakdown_data( $start_date = false, $end_date = false ) { // phpcs:ignore

			global $wpdb;

			// Get all posts with a custom post type of 'donation' that were created on and including between $start_data and $end_date.
			$donations = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT SUM(cd.amount) AS total_amount, p.ID FROM $wpdb->posts p
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations cd ON p.ID = cd.donation_id
					WHERE p.post_type = 'donation' AND p.post_status = 'charitable-completed'
					GROUP BY p.ID"
				) // phpcs:ignore
			);

			// Get all posts with a custom post type of 'donation' that were created on and including between $start_data and $end_date - group them by day.
			$donations_by_day = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT SUM(cd.amount) AS total_amount, COUNT(p.ID) AS total_donors, DATE(p.post_date) AS post_date FROM $wpdb->posts p
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations cd ON p.ID = cd.donation_id
					WHERE p.post_type = 'donation' AND p.post_status = 'charitable-completed'
					GROUP BY DATE(p.post_date)"
				) // phpcs:ignore
			);
		}


		/**
		 * This gathers info from the database with a focus on donors.
		 *
		 * @param  string $slug Slug.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $return_as The return type. Object or array. Default is object.
		 *
		 * @since  1.8.1
		 */
		public function get_donors_from_db( $slug = 'compare_from', $start_date = false, $end_date = false, $return_as = 'object' ) {

			// what we need:
			//
			// Total Donation count between start_date and end_date.
			// Total Donation amount between start_date and end_date.

			global $wpdb;

			$return_as = ( 'array' === $return_as ) ? 'ARRAY_A' : 'OBJECT_K';

			return ( $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT cd.donor_id AS donor_id, COUNT(ccd.donation_id) as %s, SUM(ccd.amount) AS %s,
					cd.user_id AS user_id,
					cd.email AS donor_email,
					cd.first_name AS donor_first_name,
					cd.last_name AS donor_last_name
					FROM {$wpdb->prefix}charitable_donors cd
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON ccd.donor_id = cd.donor_id
					LEFT JOIN {$wpdb->prefix}posts p ON p.ID = ccd.donation_id
					WHERE p.post_date >= %s AND p.post_date <= %s AND p.post_status = 'charitable-completed'
					GROUP BY cd.donor_id",
					'total_donation_count_' . $slug,
					'total_donation_amount_' . $slug,
					$start_date,
					$end_date
				),
				$return_as
			) );
		}

		/**
		 * Generates HTML for almost the entire activities area.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $advanced_report_data The report data.
		 * @param  array  $report_args The report args.
		 * @param  bool   $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function generate_advanced_breakdown_report_html( $report_type = 'lybunt', $advanced_report_data = array(), $report_args = array(), $show_actions = false ) {

			$table_meta = (array) $this->get_advanced_report_table_meta( $report_type, $report_args, $show_actions );

			// Allow certain HTML tags in the column titles.
			$title_whitelist_tags = array(

				'br' => array(),

			);

			ob_start();

			?>

			<div class="charitable-headline-reports">


				<?php
				if ( ! charitable_is_pro() ) :
					?>
					<div class="restricted-access-overlay"></div><?php endif; ?>

				<?php echo $this->generate_advanced_cards( $advanced_report_data, $report_type, $report_args ); // phpcs:ignore ?>

			</div>

			<div class="tablenav charitable-section no-bottom">

				<div class="alignleft actions">

					<h2><?php echo esc_html( $table_meta['title'] ); ?></h2>

				</div>

				<div class="alignright">

					<?php if ( ! charitable_is_pro() ) : ?>

						<button disabled="disabled" value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/download.svg'; // phpcs:ignore ?>" alt=""></button>

					<?php else : ?>

					<form action="" method="post" class="charitable-advanced-download-form" id="charitable-advanced-download-form">
						<input name="charitable_report_action" type="hidden" value="charitable_report_download_advanced">
						<input name="start_date_compare_from" type="hidden" value="<?php echo esc_html( $report_args['start_date_compare_from'] ); ?>">
						<input name="end_date_compare_from" type="hidden" value="<?php echo esc_html( $report_args['end_date_compare_from'] ); ?>">
						<input name="start_date_compare_to" type="hidden" value="<?php echo esc_html( $report_args['start_date_compare_to'] ); ?>">
						<input name="end_date_compare_to" type="hidden" value="<?php echo esc_html( $report_args['end_date_compare_to'] ); ?>">
						<input name="campaign_id" type="hidden" value="-1">
						<input name="report_type" type="hidden" value="<?php echo esc_html( $report_type ); ?>">
						<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
						<button value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" type="submit" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>
					</form>

					<?php endif; ?>

				</div>

			</div>

			<br class="clear">

			<div class="charitable-report-table-container">

				<?php do_action( 'charitable_report_before_advanced_table' ); ?>

			<?php
			if ( ! charitable_is_pro() ) :
				?>
				<div class="charitable-restricted"><div class="restricted-access-overlay"></div><?php endif; ?>

			<table class="wp-list-table widefat fixed striped table-view-list donations-breakdown charitable-report-ui">
				<thead>
					<tr>
						<?php
						if ( ! empty( $table_meta['columns'] ) ) :
							foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
								if ( $column_slug === 'avatar' ) {
									echo '<th style="width: 32px;" class="charitable-avatar"></th>';
								} else {
									echo '<th scope="col" id="' . esc_attr( $column_slug ) . '" class="manage-column column-' . esc_attr( $column_slug ) . '"><span>' . wp_kses( $column_title, $title_whitelist_tags ) . '</span></th>';
								}
							endforeach;
						endif;
						?>
					</tr>
				</thead>
				<tbody id="donations-breakdown-list">
					<?php echo $this->generate_advanced_breakdown_rows( $advanced_report_data, $report_type, count( $table_meta['columns'], $show_actions ) ); // phpcs:ignore ?>
				</tbody>
				<tfoot>
					<tr>
						<?php
						foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
							if ( $column_slug === 'avatar' ) {
								echo '<th style="width: 32px;" class="charitable-avatar"></th>';
							} else {
								echo '<th scope="col" id="' . esc_html( $column_slug ) . '" class="manage-column column-' . esc_html( $column_slug ) . '"><span>' . wp_kses( $column_title, $title_whitelist_tags ) . '</span></th>';
							}
						endforeach;
						?>
					</tr>
				</tfoot>
			</table>

			<?php
			if ( ! charitable_is_pro() ) :
				?>
				</div><?php endif; ?>

			<?php

			do_action( 'charitable_report_after_advanced_table' );

			?>
			</div>
			<?php

			return ob_get_clean();
		}

		/**
		 * Determines what function to generate table rows for advanced based on the report type.
		 *
		 * @since  1.8.1
		 *
		 * @param  array  $advanced_report_data The report data.
		 * @param  string $report_type The report type.
		 * @param  int    $number_of_columns The number of columns.
		 * @param  bool   $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function generate_advanced_breakdown_rows( $advanced_report_data = array(), $report_type = 'lybunt', $number_of_columns = false, $show_actions = false ) {

			$rows = false;

			switch ( $report_type ) {
				case 'lybunt':
					$rows = $this->generate_lybunt_rows( $advanced_report_data, $number_of_columns, $show_actions );
					break;

				default:
					// code...
					break;
			}

			return $rows;
		}

		/**
		 * Generates HTML for the "report cards" at the top of the advanced page.
		 *
		 * @since  1.8.1
		 *
		 * @param  array  $advanced_report_data The report data.
		 * @param  string $report_type The report type.
		 * @param  array  $report_args The report args.
		 *
		 * @return string
		 */
		public function generate_advanced_cards( $advanced_report_data = array(), $report_type = 'lybunt', $report_args = array() ) {

			ob_start();

			if ( 'lybunt' === $report_type ) :

				if ( charitable_is_pro() ) :

					if ( ! is_array( $advanced_report_data ) ) {
						$advanced_report_data = array();
					}

					// count all elements in a multi-dimenstional array that has a particular value greator than 0.
					$total_count_donors_from = number_format(
						count(
							array_filter(
								array_column( $advanced_report_data, 'total_donation_count_compare_from' ),
								function ( $value ) {
									return $value > 0;
								}
							)
						)
					);
					$total_count_donors_to   = number_format(
						count(
							array_filter(
								array_column( $advanced_report_data, 'total_donation_count_compare_to' ),
								function ( $value ) {
									return $value > 0;
								}
							)
						)
					);

					$total_amount_donations_from = array_sum( array_column( $advanced_report_data, 'total_donation_amount_compare_from' ) );
					$total_amount_donations_to   = array_sum( array_column( $advanced_report_data, 'total_donation_amount_compare_to' ) );

					// if value does not have decimals, then add two decimal places.
					$total_amount_donations_from = ( false === strpos( $total_amount_donations_from, '.' ) ) ? $total_amount_donations_from . '.00' : $total_amount_donations_from;
					$total_amount_donations_to   = ( false === strpos( $total_amount_donations_to, '.' ) ) ? $total_amount_donations_to . '.00' : $total_amount_donations_to;

					$from_date_range = '( ' . $report_args['start_date_compare_from'] . ' - ' . $report_args['end_date_compare_from'] . ' )';
					$to_date_range   = '( ' . $report_args['start_date_compare_to'] . ' - ' . $report_args['end_date_compare_to'] . ' )';

				else :

					$total_count_donors_from     = number_format( 143 );
					$total_count_donors_to       = number_format( 1332 );
					$total_amount_donations_from = '$500.00';
					$total_amount_donations_to   = '$25,040.00';
					$from_date_range             = '2022/01/01 - 2022/12/31';
					$to_date_range               = '2023/01/01 - 2023/12/31';

				endif;


				?>

				<div class="charitable-cards">
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo ( intval( $total_count_donors_from ) ); ?></span></strong>
						<p><?php echo esc_html__( 'Donors', 'charitable' ); ?></p>
						<p><?php echo esc_html( $from_date_range ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo ( intval( $total_count_donors_to ) ); ?></span></strong>
						<p><?php echo esc_html__( 'Donors', 'charitable' ); ?></p>
						<p><?php echo esc_html( $to_date_range ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo charitable_format_money( $total_amount_donations_from, false, true ); // phpcs:ignore ?></span></strong>
						<p><?php echo esc_html__( 'Donations', 'charitable' ); ?></p>
						<p><?php echo esc_html( $from_date_range ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo charitable_format_money( $total_amount_donations_to, false, true ); // phpcs:ignore ?></span></strong>
						<p><?php echo esc_html__( 'Donations', 'charitable' ); ?></p>
						<p><?php echo esc_html( $to_date_range ); ?></p>
					</div>
				</div>

				<?php

			endif;

			return ob_get_clean();
		}

		/**
		 * Generates HTML for the "report table" at the middle of the advanced page.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $report_type The report type.
		 * @param  array $report_args The report args.
		 * @param  array $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function get_advanced_report_table_meta( $report_type = 'lybunt', $report_args = array(), $show_actions = false ) {

			$table_meta = array();

			switch ( $report_type ) {
				case 'lybunt':
					$number_donations_1_date_range = ! empty( $report_args['start_date_compare_from'] ) && ! empty( $report_args['end_date_compare_from'] ) ? esc_html( $report_args['start_date_compare_from'] ) . ' - ' . esc_html( $report_args['end_date_compare_from'] ) : '';
					$number_donations_2_date_range = ! empty( $report_args['start_date_compare_to'] ) && ! empty( $report_args['end_date_compare_to'] ) ? esc_html( $report_args['start_date_compare_to'] ) . ' - ' . esc_html( $report_args['end_date_compare_to'] ) : '';

					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'             => '',
							'name-email'         => esc_html__( 'Name', 'charitable' ),
							'total-donations'    => esc_html__( 'Last Donation', 'charitable' ),
							'number-donations-1' => esc_html__( 'Total Donations', 'charitable' ) . '<br/>' . $number_donations_1_date_range,
							'number-donations-2' => esc_html__( 'Total Donations', 'charitable' ) . '<br/>' . $number_donations_2_date_range,
							'last-campaign'      => esc_html__( 'Last Campaign', 'charitable' ),
							'life-total'         => esc_html__( 'Life Total', 'charitable' ),
						),
					);

					if ( $show_actions ) {
						$table_meta['columns']['actions'] = esc_html__( 'Actions', 'charitable' );
					}

					break;

				default:
					// code...
					break;
			}

			return $table_meta;
		}

		/**
		 * This gets report args and an iniital report.
		 *
		 * @param  string $report_args The report args.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_lybunt_data( $report_args = array() ) {

			global $wpdb;

			$start_date_compare_from = esc_html( $report_args['start_date_compare_from'] );
			$end_date_compare_from   = esc_html( $report_args['end_date_compare_from'] );
			$start_date_compare_to   = esc_html( $report_args['start_date_compare_to'] );
			$end_date_compare_to     = esc_html( $report_args['end_date_compare_to'] );
			$limit                   = ! empty( $report_args['limit'] ) ? intval( $report_args['limit'] ) : apply_filters( 'charitable_report_advanced_lybunt_limit', 100 );
			$order_by                = ! empty( $report_args['order_by'] ) ? esc_html( $report_args['order_by'] ) : 'total_life_amount';
			$order                   = ! empty( $report_args['order'] ) ? esc_html( $report_args['order'] ) : 'DESC';

			$_donors = array();

			// Determine if this particular report has been cached recently.
			// For now though we are not caching.
			$donors = $this->maybe_cache_report( false ) ? false : $this->get_cached_report( 'lybunt', $start_date_compare_from, $end_date_compare_to, false, false, false );

			if ( false === $donors ) {

				// Get the foundation of the report.
				$donors_compare_from = (array) $this->get_donors_from_db( 'compare_from', $start_date_compare_from, $end_date_compare_from, 'array' );
				$donors_compare_to   = (array) $this->get_donors_from_db( 'compare_to', $start_date_compare_to, $end_date_compare_to, 'array' );

				// Merge the two arrays - from and to.
				if ( ! empty( $donors_compare_from ) ) :

					foreach ( $donors_compare_from as $key => $donor ) :
						$_donors[ $donor['donor_id'] ] = $donor;
					endforeach;

				endif;

				if ( ! empty( $donors_compare_to ) ) :

					foreach ( $donors_compare_to as $key => $donor ) :
						// if donor_id exists in $_donors, merge the two arrays.
						if ( is_array( $_donors ) && array_key_exists( $donor['donor_id'], $_donors ) ) {
							$_donors[ $donor['donor_id'] ] = array_merge( $_donors[ $donor['donor_id'] ], $donor );
						} else {
							$_donors[ $donor['donor_id'] ] = $donor;
						}
					endforeach;

				endif;

				if ( empty( $_donors ) ) {
					return false;
				}

				// Convert array of arrays to array of objects, keeping the array keys.
				$_donors = array_map(
					function ( $donor ) {
						return (object) $donor;
					},
					$_donors
				);

				$donors = array();

				foreach ( $_donors as $key => $donor ) {
					if ( ! empty( $donor->donor_id ) && 0 !== intval( $donor->donor_id ) ) :
						$donors[ $donor->donor_id ] = $donor;
					endif;
				}

				foreach ( $donors as $donor_id => $donor_info ) {

					$donor                                     = new Charitable_Donor( $donor_id );
					$last_donation                             = $donor->get_last_donation();
					$last_donation_amount                      = $last_donation->get_total_donation_amount();
					$last_donation_campaign_links              = $last_donation->get_campaigns_links();
					$donors[ $donor_id ]->last_donation_amount = $last_donation_amount;
					$donors[ $donor_id ]->last_donation_campaign_links = $last_donation_campaign_links;
					$donors[ $donor_id ]->total_life_amount            = $donor->get_amount();

					if ( empty( $donors[ $donor_id ]->total_donation_count_compare_from ) ) {
						$donors[ $donor_id ]->total_donation_count_compare_from = 0;
					}
					if ( empty( $donors[ $donor_id ]->total_donation_amount_compare_from ) ) {
						$donors[ $donor_id ]->total_donation_amount_compare_from = 0;
					}
					if ( empty( $donors[ $donor_id ]->total_donation_count_compare_to ) ) {
						$donors[ $donor_id ]->total_donation_count_compare_to = 0;
					}
					if ( empty( $donors[ $donor_id ]->total_donation_amount_compare_to ) ) {
						$donors[ $donor_id ]->total_donation_amount_compare_to = 0;
					}
				}

				// Sort the data.
				if ( is_array( $donors ) && count( $donors ) > 0 ) :
					$donors = $this->sort_lybunt_data( $donors, $order_by, $order );
				endif;

				// Reduce the array to the limit.
				if ( $limit && is_array( $donors ) && count( $donors ) > $limit ) :
					$donors = array_slice( $donors, 0, $limit, true );
				endif;

				// Save this to the cache.
				$this->set_cached_report( 'lybunt', $start_date_compare_from, $end_date_compare_to, false, false, false, $donors );

			}

			return $donors;
		}

		/**
		 * Sorts the lybunt data.
		 *
		 * @since  1.8.1.3
		 *
		 * @param  array  $donors The donors data.
		 * @param  string $order_by The order by. Default is total_life_amount.
		 * @param  string $order The order (DESC OR ASC). Default is DESC.
		 *
		 * @return array
		 */
		public function sort_lybunt_data( $donors = array(), $order_by = 'total_life_amount', $order = 'DESC' ) {

			if ( ! empty( $donors ) ) :

				usort(
					$donors,
					function ( $a, $b ) use ( $order_by, $order ) {
						if ( $order === 'DESC' && ! empty( $a->$order_by ) && ! empty( $b->$order_by ) ) {
							return $b->$order_by <=> $a->$order_by;
						} elseif ( $order === 'ASC' && ! empty( $a->$order_by ) && ! empty( $b->$order_by ) ) {
							return $a->$order_by <=> $b->$order_by;
						} else {
							return;
						}
					}
				);

			endif;

			return $donors;
		}

		/**
		 * Generates HTML rows for doantion breakdown table.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donors_data The donors data.
		 * @param  int   $number_of_columns The number of columns.
		 * @param  bool  $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function generate_lybunt_rows( $donors_data = array(), $number_of_columns = false, $show_actions = false ) {

			ob_start();

			if ( empty( $donors_data ) ) {
				?>

				<td colspan="<?php echo intval( $number_of_columns ); ?>"><strong><?php echo esc_html__( 'There are no donations within the date range.', 'charitable' ); ?></strong></td>

				<?php

			} else {

				foreach ( $donors_data as $donor_id => $data ) :

					$donor_name                         = $data->donor_first_name . ' ' . $data->donor_last_name;
					$donor_email                        = $data->donor_email;
					$donor_avatar                       = ( is_email( $donor_email ) ) ? get_avatar_url( $donor_email ) : charitable()->get_path( 'assets', false ) . 'images/misc/placeholder-avatar-small.jpg';
					$total_donation_count_compare_to    = $data->total_donation_count_compare_to;
					$total_donation_amount_compare_to   = number_format( $data->total_donation_amount_compare_to, 2 );
					$total_donation_count_compare_from  = $data->total_donation_count_compare_from;
					$total_donation_amount_compare_from = number_format( $data->total_donation_amount_compare_from, 2 );
					$last_donation_amount               = $data->last_donation_amount;
					$total_life_amount                  = number_format( $data->total_life_amount, 2 );
					$campaign_links                     = implode( ',', $data->last_donation_campaign_links );
					$link                               = false;
					$actions_display                    = '';

					?>

					<tr id="type-lybunt-donor-<?php echo intval( $donor_id ); ?>" class="row-type-lybunt">
						<td class="charitable-avatar" style="width: 32px;"><img src="<?php echo esc_url( $donor_avatar ); ?>" class="avatar avatar-<?php echo esc_html( $donor_id ); ?> photo" width="32" height="32" alt="<?php esc_html__( 'Profile picture of', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?>"></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Date.', 'charitable' ); ?>"><?php echo esc_html( $donor_name ); ?><br/><?php echo esc_html( $donor_email ); ?></td>
						<td class="donated column-donations" data-colname="Donations"><?php echo charitable_format_money( $last_donation_amount ); // phpcs:ignore ?></td>
						<td class="donated column-donors" data-colname="Number of Donors"><?php echo esc_html( $total_donation_count_compare_from ); ?> <?php echo esc_html__( 'Donations', 'charitable' ); ?><br/><small><?php echo charitable_format_money( $total_donation_amount_compare_from, false, true ); // phpcs:ignore ?></small></td>
						<td class="donated column-donors" data-colname="Number of Donors"><?php echo esc_html( $total_donation_count_compare_to ); ?> <?php echo esc_html__( 'Donations', 'charitable' ); ?><br/><small><?php echo charitable_format_money( $total_donation_amount_compare_to, false, true ); // phpcs:ignore ?></small></td>
						<td class="donated column-refunds" data-colname="Refunds"><?php echo $campaign_links; // phpcs:ignore ?></td>
						<td class="donated column-net " data-colname="Net"><?php echo charitable_format_money( $total_life_amount, false, true ); // phpcs:ignore ?></td>
						<?php

						if ( $show_actions ) :
							if ( $link && charitable_is_pro() ) :

								$actions_display = '<td class="donated column-actions " data-colname="Actions"><a class="charitable-campaign-action-button" title="' . esc_html__( 'View User', 'charitable' ) . '" href="' . esc_url( $link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . 'images/icons/eye.svg" width="14" height="14" alt="' . esc_html__( 'View User', 'charitable' ) . '" /></td>';

							else :

								$actions_display = '<td class="donated column-actions " data-colname="Actions"><a class="charitable-campaign-action-button" title="' . esc_html__( 'View User', 'charitable' ) . '" href="#" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . 'images/icons/eye.svg" width="14" height="14" alt="' . esc_html__( 'View User', 'charitable' ) . '" /></td>';

							endif;
						endif;

						echo $actions_display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

					</tr>

					<?php
				endforeach;

			}

			return ob_get_clean();
		}

		/**
		 * Return the HTML of the filter select dropdown.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $selected_value The selected value.
		 *
		 * @return string
		 */
		public function get_advanced_report_type_dropdown( $selected_value = false ) {

			$options_values = apply_filters(
				'charitable_reports_advanced_report_type_filter_values',
				array(
					'lybunt' => __( 'LYBUNT / SYBUNT', 'charitable' ),
				)
			);

			ob_start();

			?>

			<select autocomplete="off" name="action" id="report-advanced-type-filter">
				<?php foreach ( $options_values as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Get the donors for the dedicated advanced report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_args The report args.
		 *
		 * @return array|bool
		 */
		public function get_advanced_data_by_report_type( $report_args = array() ) {

			if ( empty( $report_args['report_type'] ) ) {
				return false;
			}

			switch ( sanitize_title( $report_args['report_type'] ) ) {
				case 'lybunt':
					return ( $this->get_lybunt_data( $report_args ) );
					break; // phpcs:ignore

				default:
					return false;
					break; // phpcs:ignore
			}
		}

		/**
		 * Return net value
		 *
		 * @since 1.8.1
		 *
		 * @param  float $donation_amount The donation amount.
		 * @param  float $refund_amount The refund amount.
		 *
		 * @return float
		 */
		public function get_net( $donation_amount = 0, $refund_amount = 0 ) {

			// If the donation amount is less than the refund amount, return 0.
			if ( $donation_amount < $refund_amount ) {
				return 0;
			}

			return $donation_amount - $refund_amount;
		}

		/**
		 * Amount formatting for reports - includes currency symbol.
		 *
		 * @since  1.8.1
		 *
		 * @param  int $amount The amount.
		 *
		 * @return string
		 */
		public function charitable_reports_format_money( $amount = 0 ) {

			return html_entity_decode( charitable_format_money( $amount ) );
		}

		/**
		 * Returns if activities are empty.
		 *
		 * @since 1.8.1
		 *
		 * @return boolean
		 */
		public function is_activities_empty() {

			if ( $this->activities === false || empty( $this->activities ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get the date of the earliest donation on the site.
		 *
		 * Used to build the "All Time" preset in the reporting date picker, so that
		 * lifetime figures are one click away rather than a hand-typed range.
		 *
		 * Every valid donation status is considered, not just completed ones, because
		 * the reports can be filtered by status and a narrower floor would hide the
		 * earliest refunded or cancelled donation.
		 *
		 * @since 1.8.12.4
		 *
		 * @return string Date in Y/m/d format. Falls back to one month ago when the
		 *                site has no donations yet.
		 */
		public function get_earliest_donation_date() {

			$cached = get_transient( 'charitable-reports-earliest-donation-date' );

			if ( false !== $cached ) {
				return $cached;
			}

			global $wpdb;

			$statuses = array_keys( charitable_get_valid_donation_statuses() );

			// `charitable_donation_statuses` is a public filter, and an empty list would
			// build `IN ( )`. Nothing to measure in that case, so use the fallback.
			if ( empty( $statuses ) ) {
				return gmdate( 'Y/m/d', strtotime( '-1 month' ) );
			}

			$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

			$earliest = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT MIN( post_date ) FROM {$wpdb->posts}
					WHERE post_type = 'donation'
					AND post_status IN ( {$placeholders} )", // phpcs:ignore
					$statuses
				)
			);

			if ( empty( $earliest ) || '0000-00-00 00:00:00' === $earliest ) {
				/*
				 * Deliberately not cached. A site with no donations today may be one
				 * import away from four years of history, and a cached fallback would
				 * leave "All Time" starting a month ago for a full day afterwards -
				 * which is the exact wrong-figure trap this preset exists to close.
				 */
				return gmdate( 'Y/m/d', strtotime( '-1 month' ) );
			}

			$date = gmdate( 'Y/m/d', strtotime( $earliest ) );

			set_transient( 'charitable-reports-earliest-donation-date', $date, DAY_IN_SECONDS );

			return $date;
		}

		/**
		 * Get localized strings.
		 *
		 * @since 1.8.1
		 *
		 * @return array
		 */
		private function get_localized_strings() {

			$charitable_reports_advanced_report_strings = apply_filters(
				'charitable_reports_advanced_report_strings',
				array(
					'lybunt' => array(
						'title'       => __( 'LYBUNT / SYBUNT', 'charitable' ),
						'description' => wp_kses_post(
							__(
								'<p>LYBUNT stands for <strong>Last Year But Unfortunately Not This Year</strong>, meaning the donor supported your organization during the last fiscal year but did not return to donate again this year. SYBUNT stands for <strong>Some Year But Unfortunately Not This Year</strong>. </p>

							<p><strong>Why This Is Important:</strong> LYBUNT and SYBUNT reports are important to nonprofits because they allow you to look for low-hanging fruit. Nonprofit organizations have an average retention rate of 43.6%. In order to meet or exceed this retention rate, it is important to nurture the donors you have.</p>',
								'charitable'
							),
							array(
								'p'      => array(),
								'strong' => array(),
							)
						),
					),
				)
			);

			$currency_helper = charitable_get_currency_helper();

			// Decode HTML entities in currency symbol to prevent double-encoding in JSON.
			// The currency symbol comes as HTML entities (e.g., &#36; for $), so we decode it
			// before passing to JavaScript to avoid double-encoding issues.
			// This ensures the currency symbol is a plain character that won't be double-encoded.
			$currency_symbol = $currency_helper->get_currency_symbol();
			// Decode HTML entities to get the plain character (e.g., &#36; becomes $).
			$currency_symbol = html_entity_decode( $currency_symbol, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$decimal_count   = $currency_helper->get_decimals();

			$strings = array(
				'version'                       => Charitable::VERSION,
				'nonce'                         => wp_create_nonce( 'charitable-reporting' ),
				'admin_nonce'                   => wp_create_nonce( 'charitable-admin' ),
				'currency_symbol'               => $currency_symbol,
				'decimal_count'                 => (int) $decimal_count,
				'ajax_url'                      => admin_url( 'admin-ajax.php' ),
				'date_select_day'               => 'DD',
				'date_select_month'             => 'MM',
				'headline_chart_options'        => array(
					'donation_axis' => (array) $this->donation_axis,
					'date_axis'     => (array) $this->date_axis,
				),
				'payment_methods_chart_options' => array(
					'payment_percentages' => (array) array_reverse( $this->payment_percentages ),
					'payment_labels'      => (array) array_reverse( $this->payment_labels ),
					'payment_keys'        => (array) array_reverse( $this->payment_keys ),
				),
				'default_start_date'            => current_time( gmdate( 'Y-m-d', strtotime( '-1 month' ) ) ),
				'default_end_date'              => current_time( gmdate( 'Y-m-d' ) ),
				'earliest_donation_date'        => $this->get_earliest_donation_date(),
				// The date picker uses each preset's label as its object key, so the
				// label has to travel to JS rather than being written there. It rides
				// this same array as `earliest_donation_date` on purpose: the
				// reporting JS contains no wp.i18n calls, so a second localization
				// mechanism for one string would be the inconsistent choice.
				'all_time_label'                => __( 'All Time', 'charitable' ),
				'advanced_reports'              => $charitable_reports_advanced_report_strings,
				'i18n_report_timeout'           => __( 'This report took too long to load and was stopped by the server. The figures have been cleared so they are not mistaken for the range you asked for. Try a shorter date range.', 'charitable' ),
				'i18n_report_error'             => __( 'This report could not be loaded. The figures have been cleared so they are not mistaken for the range you asked for. Please try again.', 'charitable' ),
			);

			$strings = apply_filters( 'charitable_reporting_strings', $strings );

			return $strings;
		}

		/**
		 * Return the array of tabs used on the settings page.
		 *
		 * @since  1.8.1
		 *
		 * @return string[]
		 */
		public function get_sections() {
			/**
			 * Filter the settings tabs.
			 *
			 * @since 1.8.1
			 *
			 * @param string[] $tabs List of tabs in key=>label format.
			 */
			return apply_filters(
				'charitable_reports_tabs',
				array(
					'overview'  => __( 'Overview', 'charitable' ),
					'advanced'  => __( 'Advanced', 'charitable' ),
					'activity'  => __( 'Activity', 'charitable' ),
					'donors'    => __( 'Donors', 'charitable' ),
					'analytics' => __( 'Analytics', 'charitable' ),
				)
			);
		}

		/**
		 * Return the array of terms for custom campaign categories.
		 *
		 * @since  1.8.1
		 *
		 * @return string[]
		 */
		public function get_campaign_categories() {

			return apply_filters(
				'charitable_reports_filter_campaign_categories',
				get_terms(
					'campaign_category'
				)
			);
		}

		/**
		 * Provides the content (title/description) to display inside the title card for a particular report.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 *
		 * @return string[]
		 */
		public function generate_title_card_html( $report_type = false ) {

			if ( false === $report_type ) {
				return;
			}

			switch ( sanitize_title( $report_type ) ) {
				case 'donors-top':
					$title = __( 'Top Donors', 'charitable' );
					// use wp_kses_post to allow html in the string.
					$description = wp_kses_post(
						__(
							'<p>This report shows which donors have made the largest gifts and donations.</p>
					<p><strong>Why This Is Important:</strong> It will show you which donors you can turn to when you need to make an ask for major needs or projects. Given that they are donating a significant amount of money at one time, you don’t want to lump them in with campaigns asking people for smaller, one-off donations. This can help you identify donors who are increasing their giving year over year and who may be good candidates for a major gift ask.</p>
					<p>You can also use this report to thank top donors for their support.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				case 'donors-recurring':
					$title = __( 'Recurring Donors', 'charitable' );
					// use wp_kses_post to allow html in the string.
					$description = wp_kses_post(
						__(
							'<p>This report shows which donors have made repeated donations the largest gifts and donations.</p>
					<p><strong>Why This Is Important:</strong> It will show you which donors you can turn to when you need to make an ask for major needs or projects. Sometimes you do not want to target all donors - especially those that have given once or in small amounts - in repeated campaigns.</p>
					<p>You can also use this report to thank recurring donors for their support.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				case 'donors-first-time':
					$title = __( 'First Time Donors', 'charitable' );
					// use wp_kses_post to allow html in the string.
					$description = wp_kses_post(
						__(
							'<p>This report shows which donors have made one donation - their first.</p>
					<p><strong>Why This Is Important:</strong> Seeing which donors are new to your campaigns can help you determine where your traffic is coming from or how donors are finding you.</p>
					<p>You can also use this report to thank first time donors for their support.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				case 'lybunt':
					$title       = __( 'LYBUNT / SYBUNT', 'charitable' );
					$description = wp_kses_post(
						__(
							'<p>LYBUNT stands for <strong>Last Year But Unfortunately Not This Year</strong>, meaning the donor supported your organization during the last fiscal year but did not return to donate again this year. SYBUNT stands for <strong>Some Year But Unfortunately Not This Year</strong>. </p>

							<p><strong>Why This Is Important:</strong> LYBUNT and SYBUNT reports are important to nonprofits because they allow you to look for low-hanging fruit. Nonprofit organizations have an average retention rate of 43.6%. In order to meet or exceed this retention rate, it is important to nurture the donors you have.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				default:
					// code...
					break;
			}

			ob_start();

			?>
			<h2><?php echo esc_html( $title ); ?></h2>
			<div class="charitable-report-description"><?php echo $description; // phpcs:ignore ?></div>
			<?php

			return ob_get_clean();
		}

		/**
		 * Return the HTML of the filter select dropdown.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $selected_value The selected value.
		 *
		 * @return string
		 */
		public function get_filter_dropdown( $selected_value = false ) {

			$options_values = $this->date_filter_values;

			ob_start();

			?>

			<select name="action" id="report-date-range-filter">
				<?php foreach ( $options_values as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Return sample data for the example tables.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $data_type The data type. Object or array.
		 * @param  int    $number_of_records The number of records to return. Default 10.
		 *
		 * @return array
		 */
		public function get_data_sample( $report_type = false, $data_type = 'object', $number_of_records = 10 ) {

			if ( false === $report_type ) {
				return;
			}

			if ( $data_type === 'object' ) {
				$data = new stdClass();
			} else {
				$data = array();
			}

			if ( $this->is_report_type_donor( $report_type ) ) {

				for ( $x = 0; $x < $number_of_records; $x++ ) {

					if ( $data_type === 'object' ) {

						$random_price = wp_rand( 50, 10000 ) / 10;

						$data->{$x} = new stdClass();

						$data->{$x}->total_amount = $random_price;
						$data->{$x}->average      = $random_price;

						$data->{$x}->total_count_donations = 1;
						$data->{$x}->total_count_campaigns = 1;
						$data->{$x}->last_donation_date    = '2023-11-28 16:59:33';
						$data->{$x}->last_campaign_title   = 'My New Campaign 1';
						$data->{$x}->last_campaign_id      = 0;
						$data->{$x}->donor_id              = 0;
						$data->{$x}->user_id               = 0;
						$data->{$x}->email                 = 'example_donor_' . $x . '@wpcharitable.com';
						$data->{$x}->first_name            = 'Roger';
						$data->{$x}->last_name             = 'Wilco';
						$data->{$x}->date_joined           = '2023-11-28 16:59:33';
						$data->{$x}->donation_id           = 0;
						$data->{$x}->campaign_id           = 0;
						$data->{$x}->amount                = $random_price;
						$data->{$x}->sample                = true;

					} else {

						$random_price_1 = mt_rand( 50, 1000 ) / 10; // phpcs:ignore

						$data[] = array(

							'total_amount'          => $random_price_1,
							'average'               => $random_price_1,
							'total_count_donations' => 1,
							'total_count_campaigns' => 1,
							'last_donation_date'    => '2023-11-28 16:59:33',
							'last_campaign_title'   => 'My New Campaign 1',
							'last_campaign_id'      => 0,
							'donor_id'              => 0,
							'user_id'               => 0,
							'email'                 => 'example_donor_' . $x . '@wpcharitable.com',
							'first_name'            => 'Roger',
							'last_name'             => 'Wilco',
							'date_joined'           => '2024-01-28 16:59:33',
							'donation_id'           => 0,
							'campaign_id'           => 0,
							'amount'                => $random_price_1,
							'sample'                => true,
						);
					}
				}

			} elseif ( $report_type === 'advanced' ) {

				$random_price_1 = wp_rand( 50, 10000 ) / 10;
				$random_price_2 = wp_rand( 50, 10000 ) / 10;
				$random_price_3 = wp_rand( 50, 10000 ) / 10;
				$random_price_4 = wp_rand( 50, 10000 ) / 10;

				$data = array(
					1  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'dbisset@donoremail.com',
						'donor_first_name'                 => 'David',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					2  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'sarah@donoremail.com',
						'donor_first_name'                 => 'Sarah',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					3  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'jim@donoremail.com',
						'donor_first_name'                 => 'Jim',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					4  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'george@donoremail.com',
						'donor_first_name'                 => 'George',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					5  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'zoe123@donoremail.com',
						'donor_first_name'                 => 'Zoe',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					6  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'avas@donoremail.com',
						'donor_first_name'                 => 'Ava',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					7  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'zoe123@donoremail.com',
						'donor_first_name'                 => 'Zoe',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					8  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'avas@donoremail.com',
						'donor_first_name'                 => 'Ava',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					9  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'dbisset@donoremail.com',
						'donor_first_name'                 => 'David',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					10 => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'sarah@donoremail.com',
						'donor_first_name'                 => 'Sarah',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
				);

			}

			$data = apply_filters(
				'charitable_report_donation_sample_data',
				$data,
				$report_type,
				$data_type,
				$number_of_records
			);

			return $data;
		}

		/**
		 * Determine if the report type is a particular type of donor report.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 *
		 * @return string
		 */
		public function is_report_type_donor( $report_type = false ) {
			return ( strpos( $report_type, 'donors-' ) === 0 );
		}

		/**
		 * Checks and sees if the string is a valid date, and if so return it in Y-m-d format.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $date_string The date string.
		 *
		 * @return string
		 */
		public function get_valid_date_string( $date_string = false ) {

			// Determine if $date_string is a valid date.
			$parsed_date = gmdate( 'Y/m/d', strtotime( $date_string ) );

			$date = DateTime::createFromFormat( 'Y/m/d', $parsed_date );

			if ( $date && $date->format( 'Y/m/d' ) === $parsed_date ) {
				return $date->format( 'Y-m-d' );
			} else {
				return false;
			}
		}

		/**
		 * Display upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since   1.8.1
		 * @version 1.8.8
		 *
		 * @param string $view Current view inside the plugin settings page.
		 *
		 * @return void
		 */
		public function reports_cta( $view = false, $css_class = 'reports-lite-cta', $show_close_button = false ) { // phpcs:ignore

			if ( charitable_is_pro() ) {
				// no need to display this cta since they have a valid license.
				return;
			}

			if ( get_option( 'charitable_lite_reports_upgrade', false ) || apply_filters( 'charitable_lite_reports_upgrade', false ) ) {
				return;
			}
			?>
			<?php charitable_render_global_upgrade_cta( $css_class ); ?>
			<?php
		}

		/**
		 * Dismiss upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since 1.8.1
		 */
		public function reports_cta_dismiss() {

			if ( ! charitable_current_user_can() ) {
				wp_send_json_error();
			}

			update_option( 'charitable_lite_reports_upgrade', time() );

			wp_send_json_success();
		}

		/**
		 * See if it's the right time to load scripts.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function maybe_load_scripts() {

			$screen = get_current_screen();

			// Add Javascript vars at the footer of the WordPress admin, at the dashboard and the overview tab of reporting.
			if ( ! is_null( $screen ) && ( $screen->id === 'charitable_page_charitable-reports' && ( empty( $_GET['tab'] ) || ( ! empty( $_GET['tab'] ) && charitable_reports_allow_tab_load_scripts( strtolower( $_GET['tab'] ) ) ) ) ) ) { // phpcs:ignore

				/*
				ONE instance prints the chart data, and only one.

				report_vars() emits "var charitable_reporting = {...}" carrying date_axis and
				donation_axis. Any second instance that reaches this method registers the hook
				again, and the later script tag simply overwrites the first - so the chart ends up
				drawing whatever range that second instance was built for.

				That is not hypothetical, and it became reachable in 1.8.14:
				get_previous_period_comparison() builds a second Charitable_Reports for the
				PREVIOUS period. On a Jul 1 - Sep 30 report the page would carry two tags - the
				first with the correct bucketed weekly axis ("Sep 24, Sep 17, Sep 10...") and the
				second with 92 DAILY labels running "Mar 31, Apr 01, Apr 02..." - the comparison
				window, at the wrong granularity, winning because it came last. It presents as
				"the x-axis has too many labels".

				First registration wins, which is the primary instance: it hooks during admin
				setup, long before the view asks for a comparison.
				*/
				static $charitable_report_vars_hooked = false;

				if ( ! $charitable_report_vars_hooked ) {
					$charitable_report_vars_hooked = true;

					// Specific styles for the "overview" tab.
					add_action( 'admin_footer', [ $this, 'report_vars' ], 100 );
				}
			}
		}

		/**
		 * See if it's the right time to load cta.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function maybe_add_reports_cta() {
			add_action( 'charitable_report_before_donor_table', [ $this, 'reports_cta' ] );
			add_action( 'charitable_report_before_activity_table', [ $this, 'reports_cta' ] );
			add_action( 'charitable_report_before_advanced_table', [ $this, 'reports_cta' ] );
		}

		/**
		 * Whether an addon's plugin file is on disk.
		 *
		 * A file check, not an "is active" check, because the two states want different copy:
		 * installed-but-inactive on a site that bought a bundle is a click away, and telling
		 * somebody to buy what they already own is the worst version of an upsell.
		 *
		 * @since 1.8.14
		 *
		 * @param  string $slug The addon's plugin folder and file name, e.g. 'charitable-recurring'.
		 * @return bool
		 */
		public function addon_is_installed( $slug ) {

			$slug = sanitize_key( (string) $slug );

			if ( '' === $slug ) {
				return false;
			}

			return file_exists( WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php' );
		}

		/**
		 * Bucket the per-day breakdown to a granularity the range can actually show.
		 *
		 * WHY THIS EXISTS. The table rendered one row per day and the chart one point per day, so
		 * a 30-day range was 30 rows and 30 x-labels and a year was 365 of each. The two surfaces
		 * had been crowding independently - the chart by squeezing labels, the table by growing
		 * past 1,800px - and each had its own workaround. Bucketing once and deriving both means
		 * they always agree on granularity, which is the thing a reader assumes without checking.
		 *
		 * The thresholds: past 180 days, calendar months; past 30, seven-day chunks; below that,
		 * days left alone. Deliberately the same numbers the Dashboard's chart bucketing uses,
		 * because the two screens show the same figures and two different ideas of "too many
		 * points" would be visible the moment somebody compared them.
		 *
		 * Months group by RUNS of the same calendar month rather than a fixed chunk, because
		 * months are 28-31 days and a fixed chunk drifts further out of phase every bucket.
		 *
		 * Returns refunds in the same 'refunds_by_day' shape it was given, keyed by bucket, so
		 * generate_donations_breakdown_rows() consumes the result with no changes at all.
		 *
		 * The daily case still goes through here rather than skipping it. Below 31 days the
		 * buckets are one day each, so the output is the input - but the code path is the same
		 * one, and a branch that only runs on long ranges is a branch nobody tests.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $donation_data Per-day rows: label, amount, donors.
		 * @param  array $refunds_data  Per-day refunds under 'refunds_by_day'.
		 * @param  int   $days          Length of the range in days.
		 * @return array {
		 *     @type string $granularity   'day', 'week' or 'month'.
		 *     @type array  $rows          Bucketed rows, same shape as $donation_data.
		 *     @type array  $refunds       Bucketed refunds, same shape as $refunds_data.
		 *     @type array  $donation_axis Chart values, one per bucket.
		 *     @type array  $date_axis     Chart labels, one per bucket.
		 * }
		 */
		public function bucket_donation_rows( $donation_data, $refunds_data, $days ) {

			$donation_data  = (array) $donation_data;
			$refunds_by_day = isset( $refunds_data['refunds_by_day'] ) ? (array) $refunds_data['refunds_by_day'] : array();
			$days           = (int) $days;

			if ( $days > 180 ) {
				$granularity = 'month';
			} elseif ( $days > 30 ) {
				$granularity = 'week';
			} else {
				$granularity = 'day';
			}

			$rows    = array();
			$refunds = array();
			$values  = array();
			$labels  = array();

			$spans    = array();
			$position = 0;

			foreach ( $donation_data as $day => $data ) {

				$timestamp = strtotime( $day );
				$refund    = isset( $refunds_by_day[ $day ] ) ? (array) $refunds_by_day[ $day ] : array();

				if ( 'month' === $granularity ) {
					// A run of consecutive days sharing a year-month IS one calendar month.
					$bucket     = false !== $timestamp ? gmdate( 'Y-m', $timestamp ) : (string) $day;
					$row_label  = false !== $timestamp ? gmdate( 'F Y', $timestamp ) : (string) $day;
					$axis_label = false !== $timestamp ? gmdate( 'M', $timestamp ) : (string) $day;
				} elseif ( 'week' === $granularity ) {
					/*
					Seven-day chunks.

					Labelled with the week's SPAN, not one of its days, and that is a correction
					worth spelling out: get_donations_by_day() returns newest-first, so the first
					day a bucket sees is its LATEST. Labelling from it produced "Sep 30" on a
					bucket covering Sep 24-30, which reads as the week starting the 30th. A single
					date cannot say which week it means, so the span is filled in below once both
					ends are known.
					*/
					$bucket     = 'w' . (int) floor( $position / 7 );
					$row_label  = '';
					$axis_label = '';
				} else {
					$bucket     = $day;
					$row_label  = isset( $data['label'] ) ? $data['label'] : (string) $day;
					$axis_label = false !== $timestamp ? gmdate( 'M d', $timestamp ) : (string) $day;
				}

				if ( ! isset( $rows[ $bucket ] ) ) {
					$rows[ $bucket ]    = array(
						'label'  => $row_label,
						'amount' => 0.0,
						'donors' => 0,
					);
					$spans[ $bucket ]   = array(
						'min' => $timestamp,
						'max' => $timestamp,
					);
					$refunds[ $bucket ] = array(
						'amount' => 0.0,
						'donors' => 0,
					);
					$values[]           = 0.0;
					$labels[]           = $axis_label;
				}

				if ( false !== $timestamp ) {
					if ( false === $spans[ $bucket ]['min'] || $timestamp < $spans[ $bucket ]['min'] ) {
						$spans[ $bucket ]['min'] = $timestamp;
					}
					if ( false === $spans[ $bucket ]['max'] || $timestamp > $spans[ $bucket ]['max'] ) {
						$spans[ $bucket ]['max'] = $timestamp;
					}
				}

				$rows[ $bucket ]['amount']    += isset( $data['amount'] ) ? (float) $data['amount'] : 0.0;
				$rows[ $bucket ]['donors']    += isset( $data['donors'] ) ? (int) $data['donors'] : 0;
				$refunds[ $bucket ]['amount'] += isset( $refund['amount'] ) ? (float) $refund['amount'] : 0.0;
				$refunds[ $bucket ]['donors'] += isset( $refund['donors'] ) ? (int) $refund['donors'] : 0;

				/*
				NET, not the gross amount. The card plots a line above a table whose last column is
				Net, and this accumulated $data['amount'] - so a day with 20.00 taken and 15.00
				refunded drew a peak at 20.00 above a row reading +5.00, and the chart and its own
				table disagreed about the same day. The refund was already being tracked one line
				up and simply never reached the series.

				get_donations_by_day() has already done this subtraction through get_net(), and both
				callers of this method feed it from there (the view at views/reports/overview.php and
				Charitable_Reports_Ajax::get_overview_data()), neither passing $format_money - so
				'net' is present and is a float, not a formatted string. Reusing it keeps one net
				rule rather than a second one here. Note get_net() floors at 0, which agrees with
				the chart's own y-axis: charitable-admin-reporting.js pins min to 0, so a negative
				series could not be drawn anyway.

				The subtraction is kept as a fallback for any future caller that assembles the
				breakdown without going through get_donations_by_day().
				*/
				$values[ count( $values ) - 1 ] += isset( $data['net'] )
					? (float) $data['net']
					: max(
						0.0,
						( isset( $data['amount'] ) ? (float) $data['amount'] : 0.0 )
						- ( isset( $refund['amount'] ) ? (float) $refund['amount'] : 0.0 )
					);

				++$position;
			}

			/*
			Month axis labels carry the year only where the year CHANGES.

			A bare "M" repeats across a multi-year report - three separate "Sep" labels on a
			Jan 2024 - Sep 2026 range with nothing to tell them apart - while putting the year on
			every label widens all of them to solve an ambiguity that only exists at the
			boundaries. So the year appears exactly where it starts to matter and nowhere else.

			Done as a pass over the finished axis rather than inside the loop because the decision
			needs the PREVIOUS bucket, and $labels is index-parallel to array_keys( $rows ) - both
			are appended together, once per new bucket, in axis order. Note that order is
			newest-first, so "where the year changes" is read the way the axis is read.
			*/
			if ( 'month' === $granularity ) {
				$axis_prev_year = '';
				$axis_index     = 0;

				foreach ( array_keys( $rows ) as $axis_bucket ) {
					$axis_bucket_ts = strtotime( $axis_bucket . '-01' );

					if ( false !== $axis_bucket_ts && isset( $labels[ $axis_index ] ) ) {
						$axis_bucket_year = gmdate( 'Y', $axis_bucket_ts );

						$labels[ $axis_index ] = $axis_bucket_year !== $axis_prev_year
							? gmdate( 'M Y', $axis_bucket_ts )
							: gmdate( 'M', $axis_bucket_ts );

						$axis_prev_year = $axis_bucket_year;
					}

					++$axis_index;
				}
			}

			/*
			Week labels, now that each bucket's two ends are known. "Sep 24 - 30" where the span
			stays inside one month, "Sep 28 - Oct 4" where it crosses one - the year is carried by
			the table's label and left off the chart's, which has no room for it.
			*/
			if ( 'week' === $granularity ) {
				$index = 0;

				foreach ( $rows as $bucket => $row ) {
					$min = $spans[ $bucket ]['min'];
					$max = $spans[ $bucket ]['max'];

					if ( false === $min || false === $max ) {
						++$index;
						continue;
					}

					$same_month = gmdate( 'Y-m', $min ) === gmdate( 'Y-m', $max );

					$rows[ $bucket ]['label'] = $same_month
						? gmdate( 'M j', $min ) . ' - ' . gmdate( 'j, Y', $max )
						: gmdate( 'M j', $min ) . ' - ' . gmdate( 'M j, Y', $max );

					if ( isset( $labels[ $index ] ) ) {
						$labels[ $index ] = gmdate( 'M j', $min );
					}

					++$index;
				}
			}

			foreach ( $values as $index => $value ) {
				$values[ $index ] = round( $value, 2 );
			}

			foreach ( $rows as $bucket => $row ) {
				$rows[ $bucket ]['amount'] = round( $row['amount'], 2 );
			}

			/*
			THE CHART READS OLDEST TO NEWEST. The table does not.

			get_donations_by_day() returns newest-first, and every array above is built by walking
			it in that order, so the x-axis came out descending - "Oct 22, Oct 21, Oct 20 ..." left
			to right, with the line running backwards through time.

			Only the two chart axes are flipped. $rows stays newest-first because that is right for
			a table, where the most recent day belongs at the top, and because the whole point of
			this card is a chart and a table of the SAME data presented each the way its own form
			wants to be read.

			Reversed here, at the end, rather than by walking the source backwards: the month-year
			and week-span label passes above both depend on $labels staying index-parallel to
			array_keys( $rows ) while they run, and the month pass additionally reads the PREVIOUS
			bucket to decide where the year changes. Flipping earlier would have broken both.
			array_values() keeps $values a JSON list rather than an object once reindexed.
			*/
			$values = array_values( array_reverse( $values ) );
			$labels = array_values( array_reverse( $labels ) );

			// A week bucket labelled for a week must not pretend the range ends mid-week: the last
			// bucket is short whenever the range is not a multiple of seven, which is normal and
			// needs no special case beyond summing what is there.
			return array(
				'granularity'   => $granularity,
				'rows'          => $rows,
				'refunds'       => array( 'refunds_by_day' => $refunds ),
				'donation_axis' => $values,
				'date_axis'     => $labels,
			);
		}

		/**
		 * Bucket a breakdown and publish the chart axes, for the view AND the AJAX handler.
		 *
		 * WHY THIS IS A METHOD. Bucketing in the view alone would mean filtering the page skipped
		 * it entirely: Charitable_Reports_Ajax::get_overview_data() would send the raw daily rows
		 * and the raw daily axis, so a 92-day filter would produce 92 x-axis labels and daily
		 * table rows while the same range typed into the URL produced 14 weekly ones - the same
		 * report disagreeing with itself depending on how you asked for it. One method, two
		 * callers.
		 *
		 * @since 1.8.14
		 *
		 * @param  array  $breakdown  Rows from get_donations_by_day().
		 * @param  array  $refunds    Refund data to bucket alongside them.
		 * @param  string $start_date Range start.
		 * @param  string $end_date   Range end.
		 * @return array { rows: array, refunds: array, granularity: string }
		 */
		public function bucket_for_range( $breakdown, $refunds, $start_date, $end_date ) {

			$start = strtotime( (string) $start_date );
			$end   = strtotime( (string) $end_date );

			$days = ( $start && $end )
				? (int) floor( ( $end - $start ) / DAY_IN_SECONDS ) + 1
				: count( (array) $breakdown );

			$bucketed = $this->bucket_donation_rows( $breakdown, $refunds, $days );

			/*
			The axes go onto the object because report_vars() reads them from there on
			admin_footer, and the AJAX handler reads them straight back out of these same two
			properties - so both paths publish the bucketed axis rather than the raw one.
			*/
			$this->donation_axis = $bucketed['donation_axis'];
			$this->date_axis     = $bucketed['date_axis'];

			return array(
				'rows'        => $bucketed['rows'],
				'refunds'     => array_merge( (array) $refunds, $bucketed['refunds'] ),
				'granularity' => $bucketed['granularity'],
			);
		}

		/**
		 * The "by week" / "by month" note beside the Donations Breakdown heading.
		 *
		 * Say what a row is when it is not a day. A table of "September 2026" rows under a heading
		 * that says nothing about months makes a reader check the dates to work out what they are
		 * looking at. Empty at day granularity, where the heading is already accurate.
		 *
		 * @since 1.8.14
		 *
		 * @param  string $granularity 'day', 'week' or 'month'.
		 * @return string
		 */
		public function get_breakdown_granularity_label( $granularity ) {

			if ( 'month' === $granularity ) {
				return __( 'by month', 'charitable' );
			}

			if ( 'week' === $granularity ) {
				return __( 'by week', 'charitable' );
			}

			return '';
		}

		/**
		 * The "N weeks with no donations are hidden" note, and its toggle.
		 *
		 * A method rather than view markup because the AJAX filter has to replace it. Switching
		 * from a 92-day range to Last 7 Days reloads the table, and a note built in the view would
		 * be left reading "11 weeks with no donations are hidden" over seven days of rows - it
		 * would describe the range the page loaded with, not the one on screen.
		 *
		 * Returns an empty string when nothing is hidden, so the guard lives here and the AJAX
		 * handler can send the same markup.
		 *
		 * @since 1.8.14
		 *
		 * @param  string $granularity 'day', 'week' or 'month'.
		 * @param  int    $empty_count How many buckets are being hidden.
		 * @return string HTML, or '' when nothing is hidden.
		 */
		public function get_breakdown_quiet_note_html( $granularity, $empty_count ) {

			$empty_count = (int) $empty_count;

			// Nothing hidden means nothing to explain.
			if ( $empty_count < 1 ) {
				return '';
			}

			/*
			The unit follows the GRANULARITY, because a row is not always a day. On a year-long
			range the rows are months, and "7 days with no donations are hidden" under a table of
			months is simply wrong - it counts hidden rows and calls them days.

			Three separate _n() sentences rather than one with an interpolated unit, so a
			translator sees each whole sentence and can agree the number with its noun.
			*/
			if ( 'month' === $granularity ) {
				/* translators: %s: number of months with no donations, already formatted. */
				$sentence = _n( '%s month with no donations is hidden.', '%s months with no donations are hidden.', $empty_count, 'charitable' );
				$show     = __( 'Show Every Month', 'charitable' );
				$hide     = __( 'Hide Quiet Months', 'charitable' );
			} elseif ( 'week' === $granularity ) {
				/* translators: %s: number of weeks with no donations, already formatted. */
				$sentence = _n( '%s week with no donations is hidden.', '%s weeks with no donations are hidden.', $empty_count, 'charitable' );
				$show     = __( 'Show Every Week', 'charitable' );
				$hide     = __( 'Hide Quiet Weeks', 'charitable' );
			} else {
				/* translators: %s: number of days with no donations, already formatted. */
				$sentence = _n( '%s day with no donations is hidden.', '%s days with no donations are hidden.', $empty_count, 'charitable' );
				$show     = __( 'Show Every Day', 'charitable' );
				$hide     = __( 'Hide Quiet Days', 'charitable' );
			}

			return sprintf(
				'<p class="charitable-reports-v3-empty-days"><span>%1$s</span> <button type="button" class="charitable-reports-v3-empty-days-toggle" aria-expanded="false" data-show="%2$s" data-hide="%3$s">%2$s</button></p>',
				esc_html( sprintf( $sentence, number_format_i18n( $empty_count ) ) ),
				esc_attr( $show ),
				esc_attr( $hide )
			);
		}

		/**
		 * A signed percentage change between two figures, or '' where there is no honest one.
		 *
		 * Returns '' rather than a number when either side is zero or below: a rise from nothing
		 * is not a percentage, and "+100%" against a zero baseline reads as a measurement when it
		 * is an artefact of dividing by nothing.
		 *
		 * Clamped at +/-99.9% so one unusually quiet previous period cannot print "+41,200%" and
		 * make the tile unreadable.
		 *
		 * @since 1.8.14
		 *
		 * @param  float $previous The earlier figure.
		 * @param  float $current  The figure on screen.
		 * @return string e.g. "+18.4%", or ''.
		 */
		private function percentage_change( $previous, $current ) {

			$previous = (float) $previous;
			$current  = (float) $current;

			if ( $previous <= 0 || $current <= 0 ) {
				return '';
			}

			$percentage = round( ( ( $current - $previous ) / $previous ) * 100, 1 );

			if ( $percentage > 99.9 ) {
				return '+99.9%';
			}

			if ( $percentage < -99.9 ) {
				return '-99.9%';
			}

			if ( 0.0 === $percentage ) {
				return '';
			}

			return ( $percentage > 0 ? '+' : '' ) . $percentage . '%';
		}

		/**
		 * The four headline figures for the period immediately before this one, as percentage
		 * changes ready for charitable_get_stat_change_html().
		 *
		 * The previous period is the same LENGTH as the one on screen, ending the day before it
		 * starts - so a 30-day view compares against the 30 days before it, and a single day
		 * against the day before. Comparing against a fixed window instead would make the
		 * percentages meaningless as soon as somebody changed the range.
		 *
		 * WHY THE GUARD. A comparison means building a SECOND overview report, and this page has a
		 * long-range timeout history. Past self::COMPARISON_MAX_DAYS no comparison is attempted
		 * and the arrows simply do not render.
		 *
		 * A second instance rather than re-initialising the shared one: this class has a public
		 * constructor alongside get_instance(), and re-initialising the singleton mid-render would
		 * change the range under every figure the view has already taken off it.
		 *
		 * Results ride the existing per-range report cache, so only the first load of a given
		 * range pays for it.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $args The current report args, carrying start_date and end_date.
		 * @return array Keys donations, average, donors, refunds. Values are signed percentage
		 *               strings like "+18.4%", or '' where no comparison is possible.
		 */
		public function get_previous_period_comparison( $args = array() ) {

			$empty = array(
				'donations' => '',
				'average'   => '',
				'donors'    => '',
				'refunds'   => '',
			);

			if ( empty( $args['start_date'] ) || empty( $args['end_date'] ) ) {
				return $empty;
			}

			$start = strtotime( $args['start_date'] );
			$end   = strtotime( $args['end_date'] );

			if ( false === $start || false === $end || $end < $start ) {
				return $empty;
			}

			// Inclusive, so a start equal to the end is one day.
			$days = (int) floor( ( $end - $start ) / DAY_IN_SECONDS ) + 1;

			if ( $days > self::COMPARISON_MAX_DAYS ) {
				return $empty;
			}

			$previous_args               = $args;
			$previous_args['start_date'] = gmdate( 'Y/m/d', $start - ( $days * DAY_IN_SECONDS ) );
			$previous_args['end_date']   = gmdate( 'Y/m/d', $start - DAY_IN_SECONDS );

			/*
			Formatted here because this is the only place that knows the previous window. Built
			from the same two strings the query uses, so the caption cannot describe a different
			range from the one the percentages came from.

			The year is dropped when both ends share the current year, which is the common case and
			where it is only noise: "Jul 25 - Aug 23" rather than "Jul 25, 2026 - Aug 23, 2026".
			*/
			$previous_start_ts = strtotime( $previous_args['start_date'] );
			$previous_end_ts   = strtotime( $previous_args['end_date'] );

			if ( false !== $previous_start_ts && false !== $previous_end_ts ) {
				$same_year = gmdate( 'Y', $previous_start_ts ) === gmdate( 'Y', $previous_end_ts )
					&& gmdate( 'Y', $previous_end_ts ) === gmdate( 'Y', (int) current_time( 'timestamp', true ) );

				$this->comparison_range_label = $same_year
					? gmdate( 'M j', $previous_start_ts ) . ' - ' . gmdate( 'M j', $previous_end_ts )
					: gmdate( 'M j, Y', $previous_start_ts ) . ' - ' . gmdate( 'M j, Y', $previous_end_ts );
			}

			$previous = new Charitable_Reports();
			$previous->init_with_array( 'overview', $previous_args );

			$previous_donations = $previous->get_donations();
			$previous_count     = is_array( $previous_donations ) ? count( $previous_donations ) : 0;
			$previous_total     = (float) $previous->get_donations_total();
			$previous_donors    = (int) $previous->get_donors_count();
			$previous_refunds   = $previous->get_refunds();
			$previous_refunded  = isset( $previous_refunds['total_amount'] ) ? (float) $previous_refunds['total_amount'] : 0.0;
			$previous_average   = $previous_count > 0 ? $previous_total / $previous_count : 0.0;

			$current_donations = $this->get_donations();
			$current_count     = is_array( $current_donations ) ? count( $current_donations ) : 0;
			$current_total     = (float) $this->get_donations_total();
			$current_donors    = (int) $this->get_donors_count();
			$current_refunds   = $this->get_refunds();
			$current_refunded  = isset( $current_refunds['total_amount'] ) ? (float) $current_refunds['total_amount'] : 0.0;
			$current_average   = $current_count > 0 ? $current_total / $current_count : 0.0;

			return array(
				'donations' => $this->percentage_change( $previous_total, $current_total ),
				'average'   => $this->percentage_change( $previous_average, $current_average ),
				'donors'    => $this->percentage_change( $previous_donors, $current_donors ),
				'refunds'   => $this->percentage_change( $previous_refunded, $current_refunded ),
			);
		}

		/**
		 * The earliest donation date for each of a set of donors, across all time.
		 *
		 * One query, not one per donor.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $donor_ids Donor IDs to look up.
		 * @return array Donor ID => unix timestamp of their first donation.
		 */
		public function get_donor_first_donation_dates( $donor_ids ) {

			$donor_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $donor_ids ) ) ) );

			if ( empty( $donor_ids ) ) {
				return array();
			}

			global $wpdb;

			$placeholders = implode( ',', array_fill( 0, count( $donor_ids ), '%d' ) );

			/*
			The suppression is about what the sniff cannot see, not about skipping safety. The
			interpolated parts are {$wpdb->prefix}, {$wpdb->posts} and a placeholder run generated
			from a count - every VALUE still goes through a placeholder, bound by prepare() from
			the array passed as its second argument.
			*/
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT cd.donor_id, MIN( p.post_date ) AS first_donation
					 FROM {$wpdb->prefix}charitable_campaign_donations cd
					 INNER JOIN {$wpdb->posts} p ON p.ID = cd.donation_id
					 WHERE cd.donor_id IN ( {$placeholders} )
					 GROUP BY cd.donor_id",
					$donor_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

			$dates = array();

			foreach ( (array) $rows as $row ) {
				$first = strtotime( (string) $row->first_donation );

				if ( false !== $first ) {
					$dates[ (int) $row->donor_id ] = $first;
				}
			}

			return $dates;
		}

		/**
		 * How many of this range's donors were giving for the FIRST time.
		 *
		 * The split the headline tile needs: growth from new donors and growth from existing ones
		 * giving more are two different problems, and a single donor count cannot tell them apart.
		 *
		 * One extra query, not one per donor. The range's donor ids are already in memory from
		 * $this->donations, so this asks only for the earliest donation date per donor across ALL
		 * time and compares it with the range's start.
		 *
		 * "First ever" counts donations of ANY status, and that is a correction rather than a
		 * choice. Filtering to completed looks more principled - a failed attempt is not giving -
		 * but it breaks the invariant that matters more: get_donors_count() does not filter by
		 * status, so a donor whose only donations are pending would return no row here and land in
		 * NEITHER bucket, and the caption would not add up to the figure directly above it.
		 *
		 * @since 1.8.14
		 *
		 * @param  string $start_date Range start, any strtotime-parsable form.
		 * @return array { @type int $new. @type int $returning. } Both zero when it cannot be told.
		 */
		public function get_new_vs_returning_donors( $start_date ) {

			$empty = array(
				'new'       => 0,
				'returning' => 0,
			);

			if ( empty( $this->donations ) ) {
				return $empty;
			}

			$start = strtotime( (string) $start_date );

			if ( false === $start ) {
				return $empty;
			}

			$donor_ids = array();

			foreach ( $this->donations as $donation ) {
				if ( ! empty( $donation->donor_id ) ) {
					$donor_ids[ (int) $donation->donor_id ] = true;
				}
			}

			$donor_ids = array_keys( $donor_ids );

			if ( empty( $donor_ids ) ) {
				return $empty;
			}

			$new = 0;

			foreach ( $this->get_donor_first_donation_dates( $donor_ids ) as $first ) {
				// Compared at day resolution, because the range itself is a date and a donation
				// made earlier on the start date is still inside it.
				if ( gmdate( 'Y-m-d', $first ) >= gmdate( 'Y-m-d', $start ) ) {
					++$new;
				}
			}

			/*
			Returning is the REMAINDER, not its own count. Counting both independently is how they
			stop adding up to the donor figure they sit under - a row that fails to parse, or a
			donor the join misses, silently vanishes from the caption instead of from one side of
			it. Derived, the two halves always account for every donor on the tile.
			*/
			$total = count( $donor_ids );

			return array(
				'new'       => min( $new, $total ),
				'returning' => max( 0, $total - $new ),
			);
		}

		/**
		 * The four headline tiles' captions and trend markup, from one place.
		 *
		 * WHY THIS IS A METHOD. Built inline in the view, these strings could not be produced by
		 * the AJAX filter: filtering updates the page through
		 * Charitable_Reports_Ajax::get_overview_data(), and anything the view computes for itself
		 * is simply left behind - a tile would read "$86.00" over "0 donations - vs. Sep 8 -
		 * Sep 15", the figure refreshed and the caption still describing the range the page first
		 * loaded with. Built here, the view and the AJAX handler render the same strings from the
		 * same code and cannot drift.
		 *
		 * Returns the comparison percentages too, so the view does not call
		 * get_previous_period_comparison() separately - one call per request, not two.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $args The overview args (start_date, end_date, campaign_id, category_id).
		 * @return array { captions: array, trends: array, changes: array }
		 */
		public function get_headline_tiles( $args = array() ) {

			$changes = array(
				'donations' => '',
				'average'   => '',
				'donors'    => '',
				'refunds'   => '',
			);

			$changes = (array) $this->get_previous_period_comparison( $args ) + $changes;

			$donation_count = count( (array) $this->donations );
			$gross          = (float) $this->get_donations_total();
			$refunds        = (array) $this->get_refunds();
			$refund_count   = isset( $refunds['total_count'] ) ? (int) $refunds['total_count'] : 0;
			$refund_amount  = isset( $refunds['total_amount'] ) ? (float) $refunds['total_amount'] : 0.0;

			/*
			The comparison window, named. A percentage with no period is the thing that actually
			misleads - 18.4% against what? - and this label is set by the method that ran the
			comparison, so it cannot name a range the number did not come from.
			*/
			$vs = '' !== $this->comparison_range_label
				/* translators: %s: the previous period, e.g. "Jul 25 - Aug 23". */
				? sprintf( __( 'vs. %s', 'charitable' ), $this->comparison_range_label )
				: '';

			/* translators: %s: number of donations in the range. */
			$count_text = sprintf( _n( '%s donation', '%s donations', $donation_count, 'charitable' ), number_format_i18n( $donation_count ) );

			$captions = array(
				'donations' => '' !== $vs ? $count_text . ' · ' . $vs : $count_text,
				'average'   => $vs,
				'donors'    => $vs,
				'refunds'   => '',
			);

			/*
			Donors get the new/returning split rather than the comparison window: the tile already
			carries a percentage change, so repeating the range spends the line on something the
			reader can see anyway. get_new_vs_returning_donors() derives "returning" as the
			remainder, so the two halves always add up to the figure above them.
			*/
			if ( ! empty( $args['start_date'] ) ) {
				$total_donors = (int) $this->get_donors_count();

				if ( $total_donors > 0 ) {
					$split = (array) $this->get_new_vs_returning_donors( $args['start_date'] );

					$captions['donors'] = sprintf(
						/* translators: 1: number of first-time donors, 2: number of donors who had given before. */
						_x( '%1$s new · %2$s returning', 'donor tile caption', 'charitable' ),
						number_format_i18n( isset( $split['new'] ) ? (int) $split['new'] : 0 ),
						number_format_i18n( isset( $split['returning'] ) ? (int) $split['returning'] : 0 )
					);
				}
			}

			/*
			Refunds carry a count and a share of gross, which says more about three refunds than
			repeating the window would. Guarded against a zero gross, where the share is undefined
			rather than zero.
			*/
			if ( $refund_count > 0 ) {
				/* translators: %s: number of refunds. */
				$refund_text = sprintf( _n( '%s refund', '%s refunds', $refund_count, 'charitable' ), number_format_i18n( $refund_count ) );
				$share       = $gross > 0 ? ( $refund_amount / $gross ) * 100 : 0;

				$captions['refunds'] = $share > 0
					? sprintf(
						/* translators: 1: number of refunds, 2: their share of gross as a percentage. */
						_x( '%1$s · %2$s%% of gross', 'refund tile caption', 'charitable' ),
						$refund_text,
						number_format_i18n( $share, 1 )
					)
					: $refund_text;
			}

			$trends = array();

			foreach ( array( 'donations', 'average', 'donors', 'refunds' ) as $key ) {
				$trends[ $key ] = function_exists( 'charitable_get_stat_change_html' )
					? charitable_get_stat_change_html( isset( $changes[ $key ] ) ? $changes[ $key ] : '' )
					: '';
			}

			return array(
				'captions' => $captions,
				'trends'   => $trends,
				'changes'  => $changes,
			);
		}

		/**
		 * Where this range's donations came from, by UTM source and medium.
		 *
		 * The one analytics figure this page can show without a Google connection. The Google
		 * Analytics addon captures the donor's UTM parameters at donation insert and stores them
		 * as ordinary post meta, so "where did this money come from" is answerable locally.
		 *
		 * NOT gated on the addon being active, and that matters more in Lite than in Pro: the meta
		 * is post meta and outlives both deactivation and a downgrade, so a site that ran Pro with
		 * tracking for a season and then fell back to Lite still has a season of attribution worth
		 * reading. generate_donation_sources_list() distinguishes "nothing captured because
		 * tracking was never set up" from "tracking ran but this period has no attributed
		 * donations", because those need different actions from the reader.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $args Report args carrying start_date and end_date.
		 * @return array { rows: array, total: float, attributed: int, unattributed: int }
		 */
		public function get_donation_attribution( $args = array() ) {
			global $wpdb;

			$start = isset( $args['start_date'] ) ? $args['start_date'] : '';
			$end   = isset( $args['end_date'] ) ? $args['end_date'] : '';

			$empty = array(
				'rows'         => array(),
				'total'        => 0.0,
				'attributed'   => 0,
				'unattributed' => 0,
			);

			if ( '' === $start || '' === $end ) {
				return $empty;
			}

			// The report page hands dates around as Y/m/d; SQL wants Y-m-d.
			$start = str_replace( '/', '-', (string) $start );
			$end   = str_replace( '/', '-', (string) $end );

			$statuses = charitable_get_approval_statuses();

			if ( empty( $statuses ) ) {
				return $empty;
			}

			$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

			/*
			One grouped query rather than a meta lookup per donation. LEFT JOIN, not INNER, so the
			untracked donations come back as NULL and can be counted as unattributed in the same
			pass instead of needing a second query to find what was missed.

			Amount comes from campaign_donations, which is the table the rest of this class totals
			from, so this card cannot disagree with the tiles above it.
			*/

			/*
			Both suppressions are about what the sniffs cannot see, not about skipping safety.
			InterpolatedNotPrepared fires on {$wpdb->posts} and the generated placeholder run,
			which are composed from trusted internals - every VALUE still goes through a
			placeholder. ReplacementsWrongNumber cannot count arguments through array_merge(), so
			it reads one replacement where the array supplies all of them; $wpdb->prepare()
			accepts an array as its second argument and binds each element.
			*/
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$sql = $wpdb->prepare(
				"SELECT COALESCE( src.meta_value, '' ) AS source,
				        COALESCE( med.meta_value, '' ) AS medium,
				        COUNT( DISTINCT p.ID ) AS donations,
				        COALESCE( SUM( cd.amount ), 0 ) AS amount
				   FROM {$wpdb->posts} p
				   INNER JOIN {$wpdb->prefix}charitable_campaign_donations cd ON cd.donation_id = p.ID
				   LEFT JOIN {$wpdb->postmeta} src ON src.post_id = p.ID AND src.meta_key = '_charitable_ga_utm_source'
				   LEFT JOIN {$wpdb->postmeta} med ON med.post_id = p.ID AND med.meta_key = '_charitable_ga_utm_medium'
				  WHERE p.post_type = 'donation'
				    AND p.post_status IN ( {$status_placeholders} )
				    AND p.post_date BETWEEN %s AND %s
				  GROUP BY source, medium
				  ORDER BY amount DESC",
				array_merge( $statuses, array( $start . ' 00:00:00', $end . ' 23:59:59' ) )
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

			if ( empty( $results ) ) {
				return $empty;
			}

			$rows         = array();
			$total        = 0.0;
			$attributed   = 0;
			$unattributed = 0;

			foreach ( $results as $row ) {
				$source = trim( (string) $row->source );
				$amount = (float) $row->amount;
				$count  = (int) $row->donations;

				if ( '' === $source ) {
					$unattributed += $count;
					continue;
				}

				$medium = trim( (string) $row->medium );

				$rows[] = array(
					'source' => $source,
					'medium' => $medium,
					'label'  => '' !== $medium ? $source . ' / ' . $medium : $source,
					'count'  => $count,
					'amount' => $amount,
				);

				$total      += $amount;
				$attributed += $count;
			}

			return array(
				'rows'         => $rows,
				'total'        => $total,
				'attributed'   => $attributed,
				'unattributed' => $unattributed,
			);
		}

		/**
		 * The Donation Sources card body.
		 *
		 * Rendered through one method the view AND the AJAX handler call, so filtering the page
		 * cannot produce different markup from loading it - which matters here because the card's
		 * two empty states have no list to fill, so the whole body is replaced rather than a <ul>
		 * inside it.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $attribution  Output of get_donation_attribution().
		 * @param  bool  $ga_installed Whether the Google Analytics addon is on disk.
		 * @return string
		 */
		public function generate_donation_sources_list( $attribution = array(), $ga_installed = false ) {
			$rows         = isset( $attribution['rows'] ) ? (array) $attribution['rows'] : array();
			$total        = isset( $attribution['total'] ) ? (float) $attribution['total'] : 0.0;
			$attributed   = isset( $attribution['attributed'] ) ? (int) $attribution['attributed'] : 0;
			$unattributed = isset( $attribution['unattributed'] ) ? (int) $attribution['unattributed'] : 0;

			ob_start();

			if ( empty( $rows ) ) {
				?>
				<p class="charitable-reports-v3-sources-note">
					<?php
					if ( $ga_installed ) {
						echo esc_html__( 'No donations in this period have a recorded source. Attribution is captured from a donor\'s landing URL, so it begins once tracking is live.', 'charitable' );
					} else {
						echo esc_html__( 'Connect Google Analytics to see which channels bring in donations. Sources are recorded on each donation, so no Google account is needed to read this card.', 'charitable' );
					}
					?>
				</p>
				<?php
				return ob_get_clean();
			}

			// Five is what fits the rail without a scrollbar; the tail is summarised below.
			$shown = array_slice( $rows, 0, 5 );
			$rest  = count( $rows ) - count( $shown );
			?>
			<div class="the-graph">
				<div class="the-legend">
					<ul>
						<?php
						foreach ( $shown as $source ) :
							$share = $total > 0 ? round( ( $source['amount'] / $total ) * 100, 1 ) : 0;
							?>
							<li class="charitable-reports-v3-pm-row">
								<span class="charitable-reports-v3-pm-name"><?php echo esc_html( $source['label'] ); ?></span>
								<span class="charitable-reports-v3-pm-amount">
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: formatted money amount. 2: share of the attributed total as a percentage. */
											_x( '%1$s &middot; %2$s%%', 'donation source amount and share', 'charitable' ),
											charitable_format_money( $source['amount'] ),
											number_format_i18n( $share, 1 )
										)
									);
									?>
								</span>
								<span class="charitable-reports-v3-pm-bar"><i style="width: <?php echo esc_attr( min( 100, $share ) ); ?>%;"></i></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div><!-- /.the-legend -->
			</div>
			<?php
			/*
			The coverage line is not decoration. A source list showing only what it could attribute
			reads as the whole picture, and on any site that added tracking part-way through it is
			not - so the count that could not be attributed is stated rather than quietly dropped.
			*/
			?>
			<p class="charitable-reports-v3-sources-note">
				<?php
				if ( $unattributed > 0 ) {
					printf(
						/* translators: 1: number of donations with a known source. 2: number without one. */
						esc_html__( '%1$s attributed, %2$s without a source.', 'charitable' ),
						esc_html( number_format_i18n( $attributed ) ),
						esc_html( number_format_i18n( $unattributed ) )
					);
				} else {
					printf(
						/* translators: %s: number of donations with a known source. */
						esc_html__( 'All %s donations attributed.', 'charitable' ),
						esc_html( number_format_i18n( $attributed ) )
					);
				}

				if ( $rest > 0 ) {
					echo ' ';
					printf(
						/* translators: %s: number of further sources not shown. */
						esc_html__( '%s more source(s) not shown.', 'charitable' ),
						esc_html( number_format_i18n( $rest ) )
					);
				}
				?>
			</p>
			<?php

			return ob_get_clean();
		}

		/**
		 * Recurring giving as it stands, or null when the addon is not present.
		 *
		 * WHY LITE HAS THIS AT ALL. The Recurring Donations addon's only activation gate is
		 * Charitable core at 1.6.51+ - it carries no Pro check and no licence check - so it runs
		 * on Lite, and a Lite site with it active has real recurring revenue to report. Showing
		 * that site a blurred "upgrade" card would be telling somebody to buy what they already
		 * own. The companion locked card is for the sites that genuinely do not have it.
		 *
		 * NO READ OF get_mrr_data(), and that is the substantive decision here. The addon's
		 * Charitable_Report_Advanced_Recurring::get_mrr_data() reaches each plan's amount through
		 * a chain of LEFT JOINs and, when any hop misses, contributes 0.0 while still counting the
		 * plan as active - silently. So the card shows no MRR at all and leads with "Due Next 30
		 * Days", which is derived here, exactly, from public accessors.
		 *
		 * NO MINIMUM ADDON VERSION, and that is a choice rather than an oversight. The gate is a
		 * capability check on the class this actually uses, because a version number cannot say
		 * whether a method exists on a customer's install, and every accessor reached from here
		 * has been public since Recurring 1.1 - verified present in 1.5.8 as well as 2.3.6. The
		 * one thing a version floor would buy: below 2.3.3 the per-plan loop in
		 * get_recurring_due_soon() triggers "Creation of dynamic property" deprecations on
		 * PHP 8.2+, bounded by that method's five-minute transient. A card withheld is worse than
		 * some log noise on an install that is already behind.
		 *
		 * NO DATE RANGE, also deliberate. Every figure here describes the present or the near
		 * future, so there is no range to honour; each tile label names its own window instead.
		 *
		 * Returns null - not zeroes - when the addon is absent, so the view renders the locked
		 * card rather than an empty live one.
		 *
		 * @since 1.8.14
		 *
		 * @return array|null
		 */
		public function get_recurring_revenue() {

			if ( ! class_exists( 'Charitable_Recurring_Donation' ) ) {
				return null;
			}

			$counts = $this->get_recurring_plan_counts();

			/*
			No active plans and nothing lapsed is a site that has the addon installed and has never
			used it. The card would be three zeroes, which says less than no card at all.

			The lapsed count is part of that test deliberately. A site whose plans have every one
			gone on hold reports active = 0, and that is the state where this card is worth the
			most - it is the only place on the page that would say so.
			*/
			if ( $counts['active'] <= 0 && $counts['attention'] <= 0 ) {
				return null;
			}

			$due = $this->get_recurring_due_soon();

			return array(
				'active'    => $counts['active'],
				'periods'   => $due['periods'],
				'due'       => $due,
				'attention' => array(
					'total'   => $counts['attention'],
					'onhold'  => $counts['onhold'],
					'expired' => $counts['expired'],
				),
			);
		}

		/**
		 * Active, on-hold and expired recurring plan counts.
		 *
		 * wp_count_posts() rather than a query per status: it is one cached call for all of them,
		 * and it counts the same post statuses the addon registers.
		 *
		 * @since 1.8.14
		 *
		 * @return array { active: int, onhold: int, expired: int, attention: int }
		 */
		private function get_recurring_plan_counts() {

			$counts  = (array) wp_count_posts( 'recurring_donation' );
			$active  = isset( $counts['charitable-active'] ) ? (int) $counts['charitable-active'] : 0;
			$onhold  = isset( $counts['charitable-onhold'] ) ? (int) $counts['charitable-onhold'] : 0;
			$expired = isset( $counts['charitable-expired'] ) ? (int) $counts['charitable-expired'] : 0;

			return array(
				'active'    => $active,
				'onhold'    => $onhold,
				'expired'   => $expired,
				'attention' => $onhold + $expired,
			);
		}

		/**
		 * The charges the active plans have scheduled in the next 30 days.
		 *
		 * @since 1.8.14
		 *
		 * @return array { amount: float, count: int, plans: array, periods: string }
		 */
		private function get_recurring_due_soon() {

			$cached = get_transient( 'charitable_reports_recurring_due_v3' );

			if ( is_array( $cached ) ) {
				return $cached;
			}

			$due = array(
				'amount'  => 0.0,
				'count'   => 0,
				'plans'   => array(),
				'periods' => '',
			);

			if ( ! class_exists( 'Charitable_Recurring_Donation' ) ) {
				return $due;
			}

			$ids = get_posts(
				array(
					'post_type'        => 'recurring_donation',
					'post_status'      => array( 'charitable-active' ),
					'posts_per_page'   => 500,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => false,
				)
			);

			$now     = time();
			$horizon = $now + ( 30 * DAY_IN_SECONDS );
			$rows    = array();
			$periods = array();

			foreach ( (array) $ids as $id ) {
				$plan = new Charitable_Recurring_Donation( $id );

				/*
				Tallied HERE, above the date filters, because it describes every active plan and
				not only the ones charging inside the horizon. Below the `continue`s it would
				silently become "the period mix of the plans due this month", which is a different
				claim printed under a label reading "Active Plans".
				*/
				if ( method_exists( $plan, 'get_donation_period' ) ) {
					$period = (string) $plan->get_donation_period();

					if ( '' !== $period ) {
						$periods[ $period ] = isset( $periods[ $period ] ) ? $periods[ $period ] + 1 : 1;
					}
				}

				if ( ! method_exists( $plan, 'get_renewal_date' ) ) {
					continue;
				}

				$date = (string) $plan->get_renewal_date( 'Y-m-d' );

				if ( '' === $date ) {
					continue;
				}

				$when = strtotime( $date );

				// A renewal date in the PAST is not a charge coming up. Stale plans on a site
				// whose cron has not run would otherwise be counted as though due tomorrow.
				if ( false === $when || $when < $now || $when > $horizon ) {
					continue;
				}

				$amount = method_exists( $plan, 'get_recurring_donation_amount' )
					? (float) $plan->get_recurring_donation_amount()
					: 0.0;

				$due['amount'] += $amount;
				++$due['count'];

				$rows[] = array(
					'timestamp' => $when,
					'date'      => date_i18n( 'F j, Y', $when ),
					'donor'     => $this->get_recurring_donor_name( $plan ),
					'campaign'  => $this->get_recurring_campaign_name( $plan ),
					'amount'    => $amount,
				);
			}

			// Soonest first. The table answers "what happens next", so the order carries as much
			// as the rows do, and get_posts() returned them by post date.
			usort(
				$rows,
				function ( $a, $b ) {
					return $a['timestamp'] - $b['timestamp'];
				}
			);

			/*
			Six rows, so the card stays about the height of the ones beside it on a site with
			hundreds of plans. Truncating cannot misstate anything: the tile above the table
			carries the full count and the full amount, and both come from the loop above rather
			than from what survived this slice.
			*/
			$due['plans'] = array_slice( $rows, 0, 6 );

			// Formatted here rather than in the view: "all monthly" versus a two-period list is
			// branching logic, and a template is the wrong place to keep it.
			$due['periods'] = $this->format_recurring_period_mix( $periods );

			set_transient( 'charitable_reports_recurring_due_v3', $due, 5 * MINUTE_IN_SECONDS );

			return $due;
		}

		/**
		 * A recurring plan's donor name, or a stated fallback.
		 *
		 * @since 1.8.14
		 *
		 * @param  object $plan The recurring donation.
		 * @return string
		 */
		private function get_recurring_donor_name( $plan ) {

			$name = '';

			if ( ! method_exists( $plan, 'get_donor' ) ) {
				return __( 'Unknown donor', 'charitable' );
			}

			$donor = $plan->get_donor();

			if ( ! is_object( $donor ) ) {
				return __( 'Unknown donor', 'charitable' );
			}

			if ( method_exists( $donor, 'get_name' ) ) {
				$name = trim( html_entity_decode( (string) $donor->get_name(), ENT_QUOTES, 'UTF-8' ) );
			}

			if ( '' === $name && method_exists( $donor, 'get_donor_meta' ) ) {
				// get_donor_meta() returns null for a key it cannot resolve and an array when
				// called with no key at all, so neither result can be cast to a string blind.
				$first = $donor->get_donor_meta( 'first_name' );
				$last  = $donor->get_donor_meta( 'last_name' );
				$first = is_scalar( $first ) ? (string) $first : '';
				$last  = is_scalar( $last ) ? (string) $last : '';
				$name  = trim( $first . ' ' . $last );
			}

			// A plan always has a date and an amount, so a missing name must not blank the row -
			// the rest of it is still the most useful thing on the card.
			return '' !== $name ? $name : __( 'Unknown donor', 'charitable' );
		}

		/**
		 * The campaign(s) a recurring plan gives to, or an em dash.
		 *
		 * @since 1.8.14
		 *
		 * @param  object $plan The recurring donation.
		 * @return string
		 */
		private function get_recurring_campaign_name( $plan ) {

			if ( ! method_exists( $plan, 'get_campaigns_donated_to' ) ) {
				return '—';
			}

			$campaigns = trim( (string) $plan->get_campaigns_donated_to() );

			return '' !== $campaigns ? $campaigns : '—';
		}

		/**
		 * "All monthly", or the two commonest periods, for the Active Plans caption.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $periods Period slug => count of active plans on it.
		 * @return string
		 */
		private function format_recurring_period_mix( $periods ) {

			$labels = array(
				'day'        => __( 'daily', 'charitable' ),
				'week'       => __( 'weekly', 'charitable' ),
				'month'      => __( 'monthly', 'charitable' ),
				'quarter'    => __( 'quarterly', 'charitable' ),
				'semiannual' => __( 'twice yearly', 'charitable' ),
				'year'       => __( 'yearly', 'charitable' ),
			);

			$named = array_intersect_key( (array) $periods, $labels );

			if ( empty( $named ) ) {
				return '';
			}

			// Commonest first, so a mixed site leads with the period that describes most of it.
			arsort( $named );

			if ( 1 === count( $named ) ) {
				$only = key( $named );

				return sprintf(
					/* translators: %s: a billing period, e.g. "monthly". */
					__( 'All %s', 'charitable' ),
					$labels[ $only ]
				);
			}

			/*
			Two at most. A site with four periods in play would otherwise wrap this caption onto
			three lines inside a tile a quarter of the card wide, and the tail of that list is the
			part nobody needs - the footer leads to the full list.
			*/
			$parts = array();

			foreach ( array_slice( $named, 0, 2, true ) as $slug => $count ) {
				$parts[] = sprintf(
					/* translators: 1: number of plans. 2: a billing period, e.g. "monthly". */
					__( '%1$s %2$s', 'charitable' ),
					number_format_i18n( $count ),
					$labels[ $slug ]
				);
			}

			return implode( __( ', ', 'charitable' ), $parts );
		}

		/**
		 * Abandoned and recovered donation figures, or null when the addon is not present.
		 *
		 * Like the recurring card above, this exists in Lite because the Donation Recovery addon
		 * CAN run here: its gate is charitable_is_pro(), which is licence-derived rather than
		 * edition-derived, so a Lite install carrying a valid licence satisfies it. All the
		 * figures come from the addon's own public analytics class.
		 *
		 * Every method is capability-checked before it is called, so a future addon release that
		 * drops one of them degrades to the locked card instead of fatally.
		 *
		 * @since 1.8.14
		 *
		 * @param  array $args Report args carrying start_date and end_date.
		 * @return array|null
		 */
		public function get_abandoned_donations( $args = array() ) {

			if ( ! class_exists( 'Charitable_Donation_Recovery_Analytics' ) ) {
				return null;
			}

			$analytics = new Charitable_Donation_Recovery_Analytics();

			foreach ( array( 'get_total_abandoned', 'get_total_recovered', 'get_recovery_rate' ) as $method ) {
				if ( ! method_exists( $analytics, $method ) ) {
					return null;
				}
			}

			$start = ! empty( $args['start_date'] ) ? gmdate( 'Y-m-d 00:00:00', strtotime( $args['start_date'] ) ) : gmdate( 'Y-m-01 00:00:00' );
			$end   = ! empty( $args['end_date'] ) ? gmdate( 'Y-m-d 23:59:59', strtotime( $args['end_date'] ) ) : gmdate( 'Y-m-t 23:59:59' );

			return array(
				'abandoned' => (int) $analytics->get_total_abandoned( $start, $end ),
				'recovered' => (int) $analytics->get_total_recovered( $start, $end ),
				'rate'      => (float) $analytics->get_recovery_rate( $start, $end ),
				'revenue'   => method_exists( $analytics, 'get_recovered_revenue' )
					? (float) $analytics->get_recovered_revenue( $start, $end )
					: 0.0,
			);
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Reports
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
