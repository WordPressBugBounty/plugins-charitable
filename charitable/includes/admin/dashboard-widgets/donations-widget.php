<?php
/**
 * Display the donations widget on the dashboard.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Dashboard Widgets
 * @since   1.2.0
 * @version 1.7.0.7
 */

$statuses = charitable_get_valid_donation_statuses();

$donation_args = array(
	'post_type'      => Charitable::DONATION_POST_TYPE,
	'posts_per_page' => 5,
	'post_status'    => array_keys( $statuses ),
	'fields'         => 'ids',
);
$donation_args = apply_filters( 'charitable_donations_widget_summary_donation_args', $donation_args );
$donations     = get_posts( $donation_args );

$table = charitable_get_table( 'campaign_donations' );

$today      = $table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_today', wp_date( 'Y-m-d%' ) ) );
$this_month = $table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_this_month', wp_date( 'Y-m%' ) ) );
$last_month = $table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_last_month', wp_date( 'Y-m%', strtotime( '-1 month' ) ) ) );
$this_year  = $table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_this_year', wp_date( 'Y-%' ) ) );

?>
<div class="charitable-donation-statistics">
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $today->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">today</span>', '%d donations <span class="time-period">today</span>', $today->count, 'charitable' ) ),
					intval( $today->count )
				);
				?>
		</p>
	</div>
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $this_month->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">this month</span>', '%d donations <span class="time-period">this month</span>', $this_month->count, 'charitable' ) ),
					intval( $this_month->count )
				);
				?>
		</p>
	</div>
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $last_month->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">last month</span>', '%d donations <span class="time-period">last month</span>', $last_month->count, 'charitable' ) ),
					intval( $last_month->count )
				);
				?>
		</p>
	</div>
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $this_year->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">this year</span>', '%d donations <span class="time-period">this year</span>', $this_year->count, 'charitable' ) ),
					intval( $this_year->count )
				);
				?>
		</p>
	</div>
</div>
<?php if ( count( $donations ) ) : ?>
	<div class="recent-donations">
		<table>
			<caption><h3><?php esc_html_e( 'Recent Donations', 'charitable' ); ?></h3></caption>
			<?php
			foreach ( $donations as $donation_id ) :
				$donation = charitable_get_donation( $donation_id );
				?>
			<tr>
				<td class="donation-date"><?php echo esc_html( $donation->get_date() ); ?></td>
				<td class="donation-id">#<?php echo esc_html( $donation->get_number() ); ?></td>
				<td class="donation-status"><?php echo esc_html( $donation->get_status_label() ); ?></td>
				<td class="donation-total"><?php echo esc_html( charitable_format_money( $donation->get_total(), false, true, $donation->get_currency() ) ); ?></td>
			</tr>
				<?php
			endforeach;
			?>
		</table>
	</div>
<?php endif ?>
