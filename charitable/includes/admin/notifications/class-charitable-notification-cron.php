<?php
/**
 * Charitable Notification Cron.
 *
 * Schedules periodic notification checks (health, compliance, nudges)
 * with a page-load fallback for sites where WP Cron is unreliable.
 *
 * @package   Charitable/Classes/Charitable_Notification_Cron
 * @since     1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Charitable_Notification_Cron {

	private static $instance = null;

	const CRON_HEALTH     = 'charitable_notifications_cron_health';
	const CRON_COMPLIANCE = 'charitable_notifications_cron_compliance';
	const CRON_NUDGES     = 'charitable_notifications_cron_nudges';
	const CRON_NEW_FEED   = 'charitable_notifications_cron_new_feed';

	/** Transient key prefix for tracking last-run timestamps. */
	const TRANSIENT_PREFIX = 'charitable_notif_cron_last_';

	/** Transient key for consecutive miss counter per job. */
	const MISS_PREFIX = 'charitable_notif_cron_miss_';

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		// Register custom cron interval if needed.
		add_filter( 'cron_schedules', [ $this, 'add_cron_intervals' ] );

		// Hook cron actions.
		add_action( self::CRON_HEALTH,     [ $this, 'run_health_check' ] );
		add_action( self::CRON_COMPLIANCE, [ $this, 'run_compliance_check' ] );
		add_action( self::CRON_NUDGES,     [ $this, 'run_nudge_check' ] );
		add_action( self::CRON_NEW_FEED,   [ $this, 'run_new_feed_fetch' ] );

		// Schedule events if not already scheduled.
		add_action( 'admin_init', [ $this, 'maybe_schedule' ] );

		// Page-load fallback on any Charitable admin page.
		add_action( 'admin_init', [ $this, 'maybe_run_fallback' ], 20 );
	}

	/**
	 * Register WP Cron schedules.
	 */
	public function add_cron_intervals( $schedules ) {
		return $schedules; // Using built-in 'hourly' and 'daily' — no custom intervals needed yet.
	}

	/**
	 * Schedule all cron jobs if not already scheduled.
	 */
	public function maybe_schedule() {
		$jobs = [
			self::CRON_HEALTH     => apply_filters( 'charitable_notifications_health_interval', 'hourly' ),
			self::CRON_COMPLIANCE => apply_filters( 'charitable_notifications_compliance_interval', 'daily' ),
			self::CRON_NUDGES     => apply_filters( 'charitable_notifications_nudges_interval', 'daily' ),
			self::CRON_NEW_FEED   => apply_filters( 'charitable_notifications_feed_interval', 'daily' ),
		];

		foreach ( $jobs as $hook => $recurrence ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time(), $recurrence, $hook );
			}
		}
	}

	/**
	 * Page-load fallback: run any job whose last-run timestamp is stale.
	 * Only runs on Charitable admin pages to avoid overhead on every admin load.
	 */
	public function maybe_run_fallback() {
		if ( ! $this->is_charitable_admin_page() ) {
			return;
		}

		$now              = time();
		$hourly_threshold = (int) apply_filters( 'charitable_notifications_health_fallback_threshold', 2 * HOUR_IN_SECONDS );
		$daily_threshold  = (int) apply_filters( 'charitable_notifications_daily_fallback_threshold', 26 * HOUR_IN_SECONDS );
		$miss_threshold   = (int) apply_filters( 'charitable_notifications_cron_miss_threshold', 3 );

		$jobs = [
			self::CRON_HEALTH     => $hourly_threshold,
			self::CRON_COMPLIANCE => $daily_threshold,
			self::CRON_NUDGES     => $daily_threshold,
			self::CRON_NEW_FEED   => $daily_threshold,
		];

		foreach ( $jobs as $hook => $threshold ) {
			$last_run = (int) get_transient( self::TRANSIENT_PREFIX . $hook );
			$stale    = ( $last_run === 0 ) || ( ( $now - $last_run ) > $threshold );

			if ( $stale ) {
				$this->record_miss( $hook, $miss_threshold );
				do_action( $hook ); // Trigger the cron callback inline.
			}
		}
	}

	/**
	 * Record a cron miss. If threshold exceeded, add a health notification.
	 *
	 * @param string $hook
	 * @param int    $threshold
	 */
	private function record_miss( $hook, $threshold ) {
		$key   = self::MISS_PREFIX . $hook;
		$count = (int) get_transient( $key );
		$count++;
		set_transient( $key, $count, WEEK_IN_SECONDS );

		if ( $count >= $threshold && self::CRON_HEALTH !== $hook ) {
			Charitable_Local_Notifications::get_instance()->add(
				'cron_not_running',
				[
					'title'             => __( 'WordPress Cron may not be running', 'charitable' ),
					'content'           => '<p>' . __( 'Some Charitable notifications (health alerts, compliance reminders) may be delayed because WordPress Cron does not appear to be running on this site.', 'charitable' ) . '</p>',
					'notification_type' => 'warning',
					'dismissible'       => true,
					'badge_label'       => 'Health Alert',
					'badge_type'        => 'critical',
					'category'          => 'health',
					'btns'              => [],
				]
			);
		}
	}

	/**
	 * Mark a cron job as successfully run.
	 *
	 * @param string $hook
	 */
	private function mark_ran( $hook ) {
		set_transient( self::TRANSIENT_PREFIX . $hook, time(), WEEK_IN_SECONDS );
		delete_transient( self::MISS_PREFIX . $hook );
	}

	/**
	 * HEALTH CHECK — failed donations in last 24 hours.
	 *
	 * Queries charitable_donations post type for posts with status
	 * 'charitable-failed' modified in the last 24 hours (excluding test mode).
	 */
	public function run_health_check() {
		try {
			$this->mark_ran( self::CRON_HEALTH );

			$threshold = apply_filters( 'charitable_notifications_health_failed_threshold', 3 );
			$since     = date( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ); // phpcs:ignore

			$failed = new WP_Query( [
				'post_type'      => 'donation',
				'post_status'    => 'charitable-failed',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'date_query'     => [
					[
						'column' => 'post_modified',
						'after'  => $since,
					],
				],
				'meta_query'     => [ // phpcs:ignore
					[
						'key'     => '_test_mode',
						'compare' => 'NOT EXISTS',
					],
				],
			] );

			$count = $failed->found_posts;
			$local = Charitable_Local_Notifications::get_instance();

			if ( $count >= $threshold ) {
				$local->add(
					'health_failed_donations',
					[
						'title'             => sprintf(
							/* translators: %d: number of failed donations */
							_n( '%d failed donation in the last 24 hours', '%d failed donations in the last 24 hours', $count, 'charitable' ),
							$count
						),
						'content'           => '<p>' . __( 'Donors may be experiencing checkout issues. Review failed transactions to identify the cause.', 'charitable' ) . '</p>',
						'notification_type' => 'warning',
						'dismissible'       => true,
						'badge_label'       => __( 'Health Alert', 'charitable' ),
						'badge_type'        => 'critical',
						'category'          => 'health',
						'btns'              => [
							'main' => [
								'url'  => admin_url( 'admin.php?page=charitable-donations&status=charitable-failed' ),
								'text' => __( 'View Failed Donations', 'charitable' ),
							],
						],
					]
				);
			} else {
				// Condition resolved — remove the notification.
				$local->remove( 'health_failed_donations' );
			}
		} catch ( \Throwable $e ) {
			if ( charitable_is_debug() ) { error_log( 'Charitable: run_health_check() failed: ' . $e->getMessage() ); } // phpcs:ignore
		}
	}

	/**
	 * COMPLIANCE CHECK — Gift Aid settings incomplete.
	 *
	 * Fires only if 14+ days have passed since the first campaign was created.
	 */
	public function run_compliance_check() {
		try {
			$this->mark_ran( self::CRON_COMPLIANCE );

			$local = Charitable_Local_Notifications::get_instance();

			// Only relevant if Gift Aid is enabled/applicable (UK sites).
			// Check: first campaign created more than 14 days ago?
			$first_campaign_ts = $this->get_first_campaign_timestamp();
			$offset            = (int) apply_filters( 'charitable_notifications_gift_aid_offset', 14 * DAY_IN_SECONDS );

			if ( ! $first_campaign_ts || ( time() - $first_campaign_ts ) < $offset ) {
				return;
			}

			// Check if Gift Aid settings are configured.
			$gift_aid_enabled     = get_option( 'charitable_gift_aid_enabled', false );
			$gift_aid_configured  = apply_filters( 'charitable_gift_aid_is_configured', $gift_aid_enabled );

			if ( $gift_aid_configured ) {
				$local->remove( 'compliance_gift_aid' );
				return;
			}

			$local->add(
				'compliance_gift_aid',
				[
					'title'             => __( 'Gift Aid settings may need attention', 'charitable' ),
					'content'           => '<p>' . __( 'If you accept donations from UK taxpayers, completing your Gift Aid settings can increase donation values by 25% at no cost to donors.', 'charitable' ) . '</p>',
					'notification_type' => 'warning',
					'dismissible'       => true,
					'badge_label'       => __( 'Compliance', 'charitable' ),
					'badge_type'        => 'warning',
					'category'          => 'legal',
					'btns'              => [
						'main' => [
							'url'  => admin_url( 'admin.php?page=charitable-settings&tab=gateways&subtab=gift-aid' ),
							'text' => __( 'Review Gift Aid Settings', 'charitable' ),
						],
					],
				]
			);
		} catch ( \Throwable $e ) {
			if ( charitable_is_debug() ) { error_log( 'Charitable: run_compliance_check() failed: ' . $e->getMessage() ); } // phpcs:ignore
		}
	}

	/**
	 * NUDGE CHECK — no campaign created yet.
	 */
	public function run_nudge_check() {
		try {
			$this->mark_ran( self::CRON_NUDGES );

			$local = Charitable_Local_Notifications::get_instance();

			$campaigns = new WP_Query( [
				'post_type'      => 'campaign',
				'post_status'    => [ 'publish', 'draft', 'pending' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			] );

			if ( $campaigns->found_posts > 0 ) {
				$local->remove( 'nudge_no_campaign' );
				return;
			}

			$local->add(
				'nudge_no_campaign',
				[
					'title'             => __( "You haven't created a campaign yet", 'charitable' ),
					'content'           => '<p>' . __( "You're all set up - the next step is creating your first fundraising campaign.", 'charitable' ) . '</p>',
					'notification_type' => 'info',
					'dismissible'       => true,
					'badge_label'       => '',
					'badge_type'        => 'nudge',
					'category'          => 'nudge',
					'btns'              => [
						'main' => [
							'url'  => admin_url( 'post-new.php?post_type=campaign' ),
							'text' => __( 'Create a Campaign', 'charitable' ),
						],
					],
				]
			);
		} catch ( \Throwable $e ) {
			if ( charitable_is_debug() ) { error_log( 'Charitable: run_nudge_check() failed: ' . $e->getMessage() ); } // phpcs:ignore
		}
	}

	/**
	 * NEW FEED FETCH — delegates to Charitable_New_Feed.
	 */
	public function run_new_feed_fetch() {
		$this->mark_ran( self::CRON_NEW_FEED );
		Charitable_New_Feed::get_instance()->fetch_and_store();
	}

	/**
	 * Get unix timestamp of first campaign ever created on this site.
	 *
	 * @return int|null
	 */
	private function get_first_campaign_timestamp() {
		$campaigns = new WP_Query( [
			'post_type'      => 'campaign',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'fields'         => 'ids',
		] );

		if ( empty( $campaigns->posts ) ) {
			return null;
		}

		return strtotime( get_post( $campaigns->posts[0] )->post_date );
	}

	/**
	 * Check if the current page is a Charitable admin page.
	 *
	 * @return bool
	 */
	private function is_charitable_admin_page() {
		if ( ! is_admin() ) {
			return false;
		}
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		return (
			strpos( $screen->id, 'charitable' ) !== false ||
			strpos( $screen->post_type, 'campaign' ) !== false ||
			strpos( $screen->post_type, 'donation' ) !== false
		);
	}
}
