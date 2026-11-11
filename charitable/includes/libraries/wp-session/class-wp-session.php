<?php
/**
 * WordPress session managment.
 *
 * Standardizes WordPress session data using database-backed options for storage.
 * for storing user session information.
 *
 * @package WordPress
 * @subpackage Session
 * @since 3.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Session class for managing user session data.
 *
 * @package WordPress
 * @since 3.7.0
 */
final class WP_Session extends Recursive_ArrayAccess { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- This is a bundled library class. Changing it would break existing functionality.
	/**
	 * ID of the current session.
	 *
	 * @var string
	 */
	public $session_id;

	/**
	 * Unix timestamp when session expires.
	 *
	 * @var int
	 */
	protected $expires;

	/**
	 * Unix timestamp indicating when the expiration time needs to be reset.
	 *
	 * @var int
	 */
	protected $exp_variant;

	/**
	 * Singleton instance.
	 *
	 * @var WP_Session
	 */
	private static $instance;

	/**
	 * Retrieve the current session instance.
	 *
	 * @return WP_Session
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default constructor.
	 * Will rebuild the session collection from the given session ID if it exists. Otherwise, will
	 * create a new session with that ID.
	 *
	 * @param $session_id
	 */
	protected function __construct() {
		/*
		 * Charitable patch as of Lite 1.8.15.1: a session cookie that does not parse is treated as
		 * no session at all, rather than as a session whose ID is the empty string.
		 *
		 * The ID is derived by stripping every character outside [A-Za-z0-9_], so a cookie whose
		 * first crumb is punctuation (for example "!!!||0||0", or an empty first crumb) reduced to
		 * ''. That still took the branch below, which reads and writes option "_wp_session_" with
		 * nothing appended: a single row shared by every visitor in that state. Because a visitor
		 * with no cookie at all also has an empty ID and still writes at shutdown, real session
		 * data accumulated in that shared row, and anyone sending a deliberately malformed cookie
		 * read it back as their own session. Charitable stores donation keys there, and
		 * Charitable_Abstract_Donation::is_from_current_user() grants access to a donation receipt
		 * on the strength of a matching key, so the shared row let one visitor open another's
		 * receipt.
		 *
		 * Requiring all three crumbs also stops an undefined array key warning on the two absint()
		 * calls for any cookie that contains no "||" separator.
		 *
		 * An empty ID is already a supported state: Charitable_Session::has_session_id() reports
		 * false and the front end enqueues the script that writes a fresh cookie, so a visitor
		 * arriving with a malformed cookie simply starts a new session on that page load.
		 */
		$this->session_id = '';
		$this->container  = array();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookie data, handled by stripslashes and sanitized by preg_replace below
		if ( ! isset( $_COOKIE[WP_SESSION_COOKIE] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cookie data, sanitized by preg_replace below
		$cookie = stripslashes( wp_unslash( $_COOKIE[WP_SESSION_COOKIE] ) );
		$cookie_crumbs = explode( '||', $cookie );

		if ( count( $cookie_crumbs ) < 3 ) {
			return;
		}

		$session_id = preg_replace("/[^A-Za-z0-9_]/", '', $cookie_crumbs[0] );

		if ( '' === (string) $session_id ) {
			return;
		}

		$this->session_id = $session_id;
		$this->expires = absint( $cookie_crumbs[1] );
		$this->exp_variant = absint( $cookie_crumbs[2] );

		// Update the session expiration if we're past the variant time
		if ( time() > $this->exp_variant ) {
			$this->set_expiration();
			delete_option( "_wp_session_expires_{$this->session_id}" );
			add_option( "_wp_session_expires_{$this->session_id}", $this->expires, '', 'no' );
		}

		$this->read_data();
	}

	/**
	 * Set both the expiration time and the expiration variant.
	 *
	 * If the current time is below the variant, we don't update the session's expiration time. If it's
	 * greater than the variant, then we update the expiration time in the database.  This prevents
	 * writing to the database on every page load for active sessions and only updates the expiration
	 * time if we're nearing when the session actually expires.
	 *
	 * By default, the expiration time is set to 30 minutes.
	 * By default, the expiration variant is set to 24 minutes.
	 *
	 * As a result, the session expiration time - at a maximum - will only be written to the database once
	 * every 24 minutes.  After 30 minutes, the session will have been expired. No cookie will be sent by
	 * the browser, and the old session will be queued for deletion by the garbage collector.
	 *
	 * @uses apply_filters Calls `wp_session_expiration_variant` to get the max update window for session data.
	 * @uses apply_filters Calls `wp_session_expiration` to get the standard expiration time for sessions.
	 */
	protected function set_expiration() {
		$this->exp_variant = time() + (int) apply_filters( 'wp_session_expiration_variant', 24 * 60 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.
		$this->expires = time() + (int) apply_filters( 'wp_session_expiration', 30 * 60 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.
	}

	/**
	 * Set the session cookie
	 *
	 * @uses apply_filters Calls `wp_session_cookie_secure` to set the $secure parameter of setcookie()
	 * @uses apply_filters Calls `wp_session_cookie_httponly` to set the $httponly parameter of setcookie()
	 */
	protected function set_cookie() {
		$secure   = apply_filters( 'wp_session_cookie_secure', false ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.
		$httponly = apply_filters( 'wp_session_cookie_httponly', false ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a bundled library hook. Changing it would break existing functionality.
		setcookie( WP_SESSION_COOKIE, $this->session_id . '||' . $this->expires . '||' . $this->exp_variant,
			$this->expires,
			COOKIEPATH,
			COOKIE_DOMAIN,
			$secure,
			$httponly
		);
	}

	/**
	 * Read data from a transient for the current session.
	 *
	 * Automatically resets the expiration time for the session transient to some time in the future.
	 *
	 * @return array
	 */
	protected function read_data() {
		$this->container = get_option( "_wp_session_{$this->session_id}", array() );

		return $this->container;
	}

	/**
	 * Write the data from the current session to the data storage system.
	 */
	public function write_data() {
		/* Nothing has changed. */
		if ( ! $this->dirty ) {
			return;
		}

		/*
		 * Charitable patch as of Lite 1.8.15.1: never write without a session ID.
		 *
		 * A visitor with no cookie has an empty ID and never reads, but still reached this method
		 * at shutdown and wrote to the shared "_wp_session_" row described in the constructor.
		 * Guarding the read alone would leave that row populated on existing installs, and
		 * guarding the write alone would leave existing rows readable, so both are needed.
		 */
		if ( '' === (string) $this->session_id ) {
			return;
		}

		if ( empty( $this->container ) ) {
			delete_option( "_wp_session_{$this->session_id}" );
			delete_option( "_wp_session_expires_{$this->session_id}" );
			return;
		}

		if ( false === get_option( "_wp_session_{$this->session_id}" ) ) {
			add_option( "_wp_session_{$this->session_id}", $this->container, '', 'no' );
			add_option( "_wp_session_expires_{$this->session_id}", $this->expires, '', 'no' );
		} else {
			delete_option( "_wp_session_{$this->session_id}" );
			add_option( "_wp_session_{$this->session_id}", $this->container, '', 'no' );
		}
	}

	/**
	 * Output the current container contents as a JSON-encoded string.
	 *
	 * @return string
	 */
	public function json_out() {
		return wp_json_encode( $this->container );
	}

	/**
	 * Decodes a JSON string and, if the object is an array, overwrites the session container with its contents.
	 *
	 * @param string $data
	 *
	 * @return bool
	 */
	public function json_in( $data ) {
		$array = json_decode( $data );

		if ( is_array( $array ) ) {
			$this->container = $array;
			return true;
		}

		return false;
	}

	/**
	 * Regenerate the current session's ID.
	 *
	 * @param bool $delete_old Flag whether or not to delete the old session data from the server.
	 */
	public function regenerate_id( $delete_old = false ) {
		if ( $delete_old ) {
			delete_option( "_wp_session_{$this->session_id}" );
		}

		$this->session_id = WP_Session_Utils::generate_id();

		$this->set_cookie();
	}

	/**
	 * Check if a session has been initialized.
	 *
	 * @return bool
	 */
	public function session_started() {
		return !!self::$instance;
	}

	/**
	 * Return the read-only cache expiration value.
	 *
	 * @return int
	 */
	public function cache_expiration() {
		return $this->expires;
	}

	/**
	 * Flushes all session variables.
	 */
	public function reset() {
		$this->container = array();
	}
}
