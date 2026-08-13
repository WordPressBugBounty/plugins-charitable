<?php
/**
 * Charitable Notification Triggers.
 *
 * Hooks into donation/campaign events to fire one-time milestone
 * and lifecycle notifications. All handlers are try/catch wrapped
 * at priority 9999 — donation processing is fully complete before
 * any of this code runs and any exception is silently logged.
 *
 * @package   Charitable/Classes/Charitable_Notification_Triggers
 * @since     1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Charitable_Notification_Triggers {

	private static $instance = null;

	/** Donor count milestones to fire notifications at. */
	const DONOR_MILESTONES = [ 1, 5, 10, 50, 100 ];

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		// Donation completed — milestones + goal check.
		add_action(
			'charitable_donation_status_charitable-completed',
			function( $donation, $old_status ) {
				try {
					self::get_instance()->handle_donation_completed( $donation );
				} catch ( Exception $e ) {
					if ( charitable_is_debug() ) { error_log( 'Charitable notification trigger error (donation): ' . $e->getMessage() ); }
				}
			},
			9999,
			2
		);

		// Campaign status transitions — lifecycle events.
		add_action(
			'transition_post_status',
			function( $new_status, $old_status, $post ) {
				if ( 'campaign' !== get_post_type( $post ) ) {
					return;
				}
				try {
					self::get_instance()->handle_campaign_status_change( $new_status, $old_status, $post );
				} catch ( Exception $e ) {
					if ( charitable_is_debug() ) { error_log( 'Charitable notification trigger error (campaign): ' . $e->getMessage() ); }
				}
			},
			9999,
			3
		);
	}

	/**
	 * Handle donation completed event.
	 *
	 * @param Charitable_Donation $donation
	 */
	public function handle_donation_completed( $donation ) {
		// Exclude test mode donations.
		if ( $donation->get( 'test_mode' ) ) {
			return;
		}

		$this->check_donor_milestones( $donation );
		$this->check_largest_donation( $donation );
		$this->check_campaign_goal( $donation );
	}

	/**
	 * Handle campaign status change.
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function handle_campaign_status_change( $new_status, $old_status, $post ) {
		$local = Charitable_Local_Notifications::get_instance();

		// Campaign went live.
		if ( 'publish' === $new_status && 'publish' !== $old_status ) {
			$local->add(
				'lifecycle_campaign_live_' . $post->ID,
				[
					'title'             => sprintf(
						/* translators: %s: campaign title */
						__( '"%s" is now live', 'charitable' ),
						get_the_title( $post )
					),
					'content'           => '<p>' . __( 'Your campaign is published and accepting donations.', 'charitable' ) . '</p>',
					'notification_type' => 'info',
					'dismissible'       => true,
					'badge_label'       => __( 'Campaign', 'charitable' ),
					'badge_type'        => 'info',
					'category'          => 'lifecycle',
					'btns'              => [
						'main' => [
							'url'  => get_permalink( $post->ID ),
							'text' => __( 'View Campaign', 'charitable' ),
						],
					],
				]
			);
		}

		// Campaign ended (transitioned away from publish).
		if ( 'publish' === $old_status && 'publish' !== $new_status ) {
			$campaign = new Charitable_Campaign( $post );
			$goal     = $campaign->get_goal();
			$raised   = $campaign->get_donated_amount();
			$met_goal = $goal && $raised >= $goal;

			$local->add(
				'lifecycle_campaign_ended_' . $post->ID,
				[
					'title'             => sprintf(
						/* translators: %s: campaign title */
						$met_goal
							? __( '"%s" ended — goal met!', 'charitable' )
							: __( '"%s" has ended', 'charitable' ),
						get_the_title( $post )
					),
					'content'           => '<p>' . sprintf(
						/* translators: %s: amount raised (wrapped in stat span) */
						__( 'Total raised: %s.', 'charitable' ),
						'<span class="charitable-notif-stat">' . charitable_format_money( $raised ) . '</span>'
					) . '</p>',
					'notification_type' => $met_goal ? 'success' : 'info',
					'dismissible'       => true,
					'badge_label'       => __( 'Campaign', 'charitable' ),
					'badge_type'        => $met_goal ? 'success' : 'info',
					'category'          => 'lifecycle',
					'btns'              => [
						'main' => [
							'url'  => admin_url( 'post.php?action=edit&post=' . $post->ID ),
							'text' => __( 'View Campaign', 'charitable' ),
						],
					],
				]
			);
		}
	}

	/**
	 * Check if a donor count milestone has just been crossed.
	 *
	 * @param Charitable_Donation $donation
	 */
	private function check_donor_milestones( $donation ) {
		$local        = Charitable_Local_Notifications::get_instance();
		$total_donors = $this->get_unique_real_donor_count();

		foreach ( self::DONOR_MILESTONES as $milestone ) {
			$id = 'milestone_donors_' . $milestone;

			// Already fired?
			$notifications = Charitable_Notifications::get_instance();
			$option        = $notifications->get_option();
			$already_fired = false;
			foreach ( $option['local'] as $item ) {
				if ( isset( $item['id'] ) && $item['id'] === $id ) {
					$already_fired = true;
					break;
				}
			}
			if ( $already_fired ) {
				continue;
			}

			if ( $total_donors >= $milestone ) {
				if ( 1 === $milestone ) {
					$title   = __( 'You received your first donation!', 'charitable' );
					$content = '<p>' . __( 'Your first real donation has been received. Congratulations!', 'charitable' ) . '</p>';
				} else {
					$title   = sprintf(
						/* translators: %d: donor count */
						__( "You've reached %d unique donors!", 'charitable' ),
						$milestone
					);
					$content = '<p>' . sprintf(
						/* translators: %s: donor count (wrapped in stat span) */
						__( '%s real donors have contributed to your campaigns.', 'charitable' ),
						'<span class="charitable-notif-stat">' . number_format_i18n( $milestone ) . '</span>'
					) . '</p>';
				}

				$local->add( $id, [
					'title'             => $title,
					'content'           => $content,
					'notification_type' => 'success',
					'dismissible'       => true,
					'badge_label'       => __( 'Milestone', 'charitable' ),
					'badge_type'        => 'success',
					'category'          => 'milestone',
					'btns'              => [
						'main' => [
							'url'  => admin_url( 'admin.php?page=charitable-donors' ),
							'text' => __( 'View Donors', 'charitable' ),
						],
					],
				] );
				break; // Only fire the highest newly-crossed milestone per donation.
			}
		}
	}

	/**
	 * Check if this donation is the largest ever received.
	 *
	 * @param Charitable_Donation $donation
	 */
	private function check_largest_donation( $donation ) {
		$amount     = $donation->get_total_donation_amount( true );
		$stored_max = (float) get_option( 'charitable_notifications_largest_donation', 0 );

		if ( $amount <= $stored_max ) {
			return;
		}

		update_option( 'charitable_notifications_largest_donation', $amount );

		if ( $stored_max > 0 ) { // Don't fire on the very first donation (covered by donor milestone).
			Charitable_Local_Notifications::get_instance()->add(
				'milestone_largest_donation',
				[
					'title'             => __( 'New largest single donation received!', 'charitable' ),
					'content'           => '<p>' . sprintf(
						/* translators: %s: formatted amount (wrapped in stat span) */
						__( 'A new record: %s in a single donation.', 'charitable' ),
						'<span class="charitable-notif-stat">' . charitable_format_money( $amount ) . '</span>'
					) . '</p>',
					'notification_type' => 'success',
					'dismissible'       => true,
					'badge_label'       => __( 'Milestone', 'charitable' ),
					'badge_type'        => 'success',
					'category'          => 'milestone',
					'btns'              => [
						'main' => [
							'url'  => admin_url( 'post.php?action=edit&post=' . $donation->get_donation_id() ),
							'text' => __( 'View Donation', 'charitable' ),
						],
					],
				]
			);
		}
	}

	/**
	 * Check if the campaign associated with this donation has just reached its goal.
	 *
	 * @param Charitable_Donation $donation
	 */
	private function check_campaign_goal( $donation ) {
		$campaign_donations = $donation->get_campaign_donations(); // returns array of objects with ->campaign_id
		if ( empty( $campaign_donations ) ) {
			return;
		}

		$local = Charitable_Local_Notifications::get_instance();

		foreach ( $campaign_donations as $cd ) {
			$campaign_id = isset( $cd->campaign_id ) ? (int) $cd->campaign_id : 0;
			if ( ! $campaign_id ) {
				continue;
			}

			$campaign = new Charitable_Campaign( get_post( $campaign_id ) );

			if ( ! $campaign->has_goal() || ! $campaign->has_achieved_goal() ) {
				continue;
			}

			$notif_id = 'lifecycle_campaign_goal_' . $campaign_id;

			// Already fired for this campaign?
			$notifications = Charitable_Notifications::get_instance();
			$option        = $notifications->get_option();
			foreach ( $option['local'] as $item ) {
				if ( isset( $item['id'] ) && $item['id'] === $notif_id ) {
					continue 2; // Already recorded.
				}
			}

			$local->add( $notif_id, [
				'title'             => sprintf(
					/* translators: %s: campaign title */
					__( '"%s" has reached its goal!', 'charitable' ),
					get_the_title( $campaign_id )
				),
				'content'           => '<p>' . sprintf(
					/* translators: %s: goal amount (wrapped in stat span) */
					__( 'Your campaign hit its %s target.', 'charitable' ),
					'<span class="charitable-notif-stat">' . charitable_format_money( $campaign->get_goal() ) . '</span>'
				) . '</p>',
				'notification_type' => 'celebration',
				'dismissible'       => true,
				'badge_label'       => __( 'Campaign', 'charitable' ),
				'badge_type'        => 'success',
				'category'          => 'lifecycle',
				'btns'              => [
					'main' => [
						'url'  => admin_url( 'post.php?action=edit&post=' . $campaign_id ),
						'text' => __( 'View Campaign', 'charitable' ),
					],
				],
			] );
		}
	}

	/**
	 * Get total unique real (non-test) donor count site-wide.
	 *
	 * @return int
	 */
	private function get_unique_real_donor_count() {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore
			"SELECT COUNT(DISTINCT donor_id)
			 FROM {$wpdb->prefix}charitable_campaign_donations cd
			 INNER JOIN {$wpdb->posts} p ON p.ID = cd.donation_id
			 WHERE p.post_status = 'charitable-completed'
			 AND NOT EXISTS (
			     SELECT 1 FROM {$wpdb->postmeta} pm
			     WHERE pm.post_id = p.ID
			     AND pm.meta_key = '_test_mode'
			     AND pm.meta_value = '1'
			 )"
		);
	}
}
