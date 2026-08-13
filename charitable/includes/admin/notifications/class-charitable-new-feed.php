<?php
/**
 * Charitable New Feed.
 *
 * Fetches the new Charitable announcement feed (separate from the legacy feed).
 * Non-dismissible items stay pinned until removed from the feed itself.
 *
 * @package   Charitable/Classes/Charitable_New_Feed
 * @since     1.8.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Charitable_New_Feed {

	private static $instance = null;

	/**
	 * New announcement feed URL.
	 * Placeholder — update when endpoint is live.
	 */
	const SOURCE_URL = 'https://plugin.wpcharitable.com/wp-content/charitable-announcements.json';

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		// Inject at priority 1 — before everything else (new feed items go to very top).
		add_filter( 'charitable_notifications_get', [ $this, 'inject_new_feed' ], 1 );
	}

	/**
	 * Inject new feed notifications at the top of the feed.
	 *
	 * @param  array $notifications
	 * @return array
	 */
	public function inject_new_feed( array $notifications ) {
		$settings = Charitable_Notification_Settings::get_instance();
		if ( ! $settings->is_category_enabled( 'new_feed' ) ) {
			return $notifications;
		}

		$items = $this->get_stored();
		if ( empty( $items ) ) {
			return $notifications;
		}

		$dismissed = Charitable_Notifications::get_instance()->get_option()['dismissed'];

		$to_inject = [];
		foreach ( $items as $item ) {
			if ( empty( $item['id'] ) ) {
				continue;
			}

			$dismissible = ! isset( $item['dismissible'] ) || (bool) $item['dismissible'];

			// Non-dismissible: always show regardless of dismissed array.
			if ( ! $dismissible ) {
				$item['dismissible'] = false;
				$to_inject[]         = $item;
				continue;
			}

			// Dismissible: respect dismissed array.
			if ( ! in_array( (string) $item['id'], $dismissed, true ) ) {
				$to_inject[] = $item;
			}
		}

		// Sort: non-dismissible first.
		usort( $to_inject, function( $a, $b ) {
			$da = ! isset( $a['dismissible'] ) || (bool) $a['dismissible'];
			$db = ! isset( $b['dismissible'] ) || (bool) $b['dismissible'];
			if ( $da === $db ) return 0;
			return $da ? 1 : -1; // non-dismissible first.
		} );

		return array_merge( $to_inject, $notifications );
	}

	/**
	 * Fetch the remote feed and store results. Called by cron.
	 */
	public function fetch_and_store() {
		$response = wp_remote_get( self::SOURCE_URL, [
			'timeout'    => 10,
			'sslverify'  => false,
			'user-agent' => charitable_get_default_user_agent(),
		] );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return;
		}

		$items = json_decode( $body, true );
		if ( ! is_array( $items ) ) {
			return;
		}

		// Validate and sanitize each item.
		$clean = [];
		foreach ( $items as $item ) {
			if ( empty( $item['id'] ) || empty( $item['content'] ) ) {
				continue;
			}

			// Sanitize each field before storing.
			$sanitized = [];

			$sanitized['id']                = sanitize_key( $item['id'] );
			$sanitized['title']             = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			$sanitized['content']           = wp_kses_post( $item['content'] );
			$sanitized['badge_label']       = 'Announcement';
			$sanitized['badge_type']        = 'announcement';
			$sanitized['category']          = 'new_feed';
			$sanitized['notification_type'] = isset( $item['notification_type'] ) ? sanitize_text_field( $item['notification_type'] ) : '';

			// Sanitize btns array.
			$sanitized['btns'] = [];
			if ( ! empty( $item['btns'] ) && is_array( $item['btns'] ) ) {
				foreach ( $item['btns'] as $btn_key => $btn ) {
					if ( is_array( $btn ) ) {
						$sanitized['btns'][ sanitize_key( $btn_key ) ] = [
							'url'  => isset( $btn['url'] ) ? esc_url_raw( $btn['url'] ) : '',
							'text' => isset( $btn['text'] ) ? sanitize_text_field( $btn['text'] ) : '',
						];
					}
				}
			}

			// Preserve dismissible as boolean.
			if ( isset( $item['dismissible'] ) ) {
				$sanitized['dismissible'] = (bool) $item['dismissible'];
			}

			// Preserve start as sanitized text.
			if ( isset( $item['start'] ) ) {
				$sanitized['start'] = sanitize_text_field( $item['start'] );
			}

			$clean[] = $sanitized;
		}

		Charitable_Notifications::get_instance()->save_option_partial( [ 'new_feed' => $clean ] );
	}

	/**
	 * Get stored new feed items.
	 *
	 * @return array
	 */
	private function get_stored() {
		return Charitable_Notifications::get_instance()->get_option()['new_feed'];
	}
}
