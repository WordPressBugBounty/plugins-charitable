<?php
/**
 * Renders the donation donor history meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.7.0.8
 * @version   1.8.1.5 - added donation ID check in foreach.
 */

global $post;

$donor_id           = charitable_get_donation( $post->ID )->get_donor_id();
$date_format        = get_option( 'date_format' );
$time_format        = get_option( 'time_format' );
$distinct_donations = false;
$show_campaign_name = apply_filters( 'charitable_donations_donor_history_show_campaign', true );

$meta = charitable_get_donation( $post->ID )->get_donation_meta();

$donations            = charitable_get_table( 'campaign_donations' )->get_donations_by_donor( $donor_id, $distinct_donations );
$total_amount_donated = charitable_get_table( 'campaign_donations' )->get_total_donated_by_donor( $donor_id );
$number_of_donations  = charitable_get_table( 'campaign_donations' )->count_donations_by_donor( $donor_id, $distinct_donations );

?>
<div id="charitable-donation-donor-history-metabox" class="charitable-metabox">

	<?php do_action( 'charitable_before_donor_history_meta_info', $donor_id, $post ); ?>

	<?php if ( $number_of_donations && ! empty( $donations ) && intval( $donor_id ) > 0 && isset( $meta['donor']['value'] ) && '' !== trim( $meta['donor']['value'] ) ) : ?>

		<p>
			<?php esc_html_e( 'This user has donated ', 'charitable' ); ?>
			<strong><?php echo intval( $number_of_donations ); ?></strong>
			<?php if ( count( $donations ) === 1 ) : ?>
				<?php esc_html_e( 'time', 'charitable' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'times', 'charitable' ); ?>
			<?php endif; ?>
			<?php esc_html_e( ' for a total of', 'charitable' ); ?>
			<strong><?php echo esc_html( charitable_format_money( $total_amount_donated, false, false, charitable_get_currency() ) ); ?></strong>.
			<a href="#" class="donor-list-view-donations"><?php esc_html_e( 'Show Donations', 'charitable' ); ?></a>
		</p>

		<table class="widefat charitable-donor-history-table" style="display:none;">

		<?php $donations = array_reverse( $donations ); ?>

		<?php
		foreach ( $donations as $donation ) {

			if ( empty( $donation->donation_id ) || intval( $donation->donation_id ) === 0 || ! charitable_get_donation( intval( $donation->donation_id ) ) ) {
				continue;
			}

			$donation_date       = charitable_get_donation( intval( $donation->donation_id ) )->get_date();
			$donation_time       = charitable_get_donation( intval( $donation->donation_id ) )->get_time();
			$donation_status     = charitable_get_donation( intval( $donation->donation_id ) )->get_status();
			$amount              = charitable_get_donation( intval( $donation->donation_id ) )->get_amount_formatted();
			$admin_donation_link = get_edit_post_link( $donation->donation_id );
			?>

			<tr>
				<td>
					<?php
					if ( $show_campaign_name && 0 !== intval( $donation->campaign_id ) ) :
							$campaign = charitable_get_campaign( intval( $donation->campaign_id ) );

						?>
						<p><strong><a href="<?php echo esc_url( get_edit_post_link( $donation->campaign_id ) ); ?>"><?php echo esc_html( get_the_title( $donation->campaign_id ) ); ?></a></strong></p>
					<?php endif; ?>
					<p><a href="<?php echo esc_url( $admin_donation_link ); ?>"><?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d', strtotime( $donation_date ) ), $date_format ) ); ?></a></p>
					<p><a href="<?php echo esc_url( $admin_donation_link ); ?>"><?php echo esc_html( get_date_from_gmt( gmdate( 'H:i:s', strtotime( $donation_time ) ), $time_format ) ); ?></a></p>
				</td>
				<td><p><?php echo esc_html( $amount ); ?></p>
					<p>
					<?php
					$display = sprintf(
						'<mark class="status %s">%s</mark>',
						esc_attr( charitable_get_donation( $donation->donation_id )->get_status() ),
						strtolower( charitable_get_donation( $donation->donation_id )->get_status_label() )
					);
						echo $display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
						</p>
					<?php do_action( 'charitable_donations_donor_history_after_status', $donation, $post ); ?>
				</td>
			</tr>

		<?php } ?>

		<?php do_action( 'charitable_after_donor_history_meta_info', $donor_id, $post ); ?>

		</table>

	<?php else : ?>

		<?php esc_html_e( 'No history for this donor.', 'charitable' ); ?>

	<?php endif; ?>

</div>
