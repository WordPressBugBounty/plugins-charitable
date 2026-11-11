<?php
/**
 * Class used to provide information about the current request.
 *
 * @package   Charitable/Classes/Charitable_Request
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.58
 * @version   1.8.15.1 - Honour ?amount= on campaign pages that display the donation form inline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Request' ) ) :

	/**
	 * Charitable_Request.
	 *
	 * @since 1.0.0
	 * @final
	 */
	final class Charitable_Request {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Request|null
		 */
		private static $instance = null;

		/**
		 * Campaign object.
		 *
		 * @var Charitable_Campaign|false
		 */
		private $campaign;

		/**
		 * Original campaign object.
		 *
		 * @var Charitable_Campaign|false
		 */
		private $original_campaign;

		/**
		 * Original campaign ID.
		 *
		 * @var int
		 */
		private $original_campaign_id;

		/**
		 * Campaign ID.
		 *
		 * @var int
		 */
		private $campaign_id;

		/**
		 * Donation object.
		 *
		 * @var Charitable_Donation
		 */
		private $donation;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the on_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			/* Set the current campaign on the_post hook. */
			add_action( 'the_post', array( $this, 'set_current_campaign' ) );

			/* Add any supported donation parameters to the session. */
			add_action( 'charitable_is_donate_page', array( $this, 'add_donation_params_to_session' ) );

			/* Do the same on campaign pages that show the donation form inline. */
			add_action( 'wp', array( $this, 'maybe_add_donation_params_to_session' ) );

			/* Set the current campaign before a donation form is displayed, and reset to original after. */
			add_action( 'charitable_donation_form_before', array( $this, 'set_current_campaign_before_donation_form' ) );
			add_action( 'charitable_donation_form_after', array( $this, 'reset_current_campaign_after_donation_form' ) );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Request
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * When the_post is set, sets the current campaign to the current post if it is a campaign.
		 *
		 * @since  1.0.0
		 *
		 * @param  WP_Post $post The Post object.
		 * @return void
		 */
		public function set_current_campaign( $post ) {
			if ( 'campaign' == $post->post_type ) {
				$this->campaign = new Charitable_Campaign( $post );
			} else {
				unset( $this->campaign, $this->campaign_id );
			}
		}

		/**
		 * Returns the current campaign. If there is no current campaign, return false.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Campaign|false Campaign object if we're viewing a campaign within a loop. False otherwise.
		 */
		public function get_current_campaign() {
			if ( ! isset( $this->campaign ) ) {
				if ( $this->get_current_campaign_id() > 0 ) {
					$this->campaign = new Charitable_Campaign( $this->get_current_campaign_id() );
				} else {
					$this->campaign = false;
				}
			}

			return $this->campaign;
		}

		/**
		 * Returns the current campaign ID. If there is no current campaign, return 0.
		 *
		 * @since  1.0.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return int
		 */
		public function get_current_campaign_id() {
			global $wp_query;

			if ( isset( $this->campaign ) && $this->campaign ) {
				$this->campaign_id = $this->campaign->ID;
			} else {
				$this->campaign_id = 0;

				if ( get_post_type() == Charitable::CAMPAIGN_POST_TYPE ) {
					$this->campaign_id = get_the_ID();
				} elseif ( $wp_query && $wp_query->get( 'donate', false ) ) {
					$session_donation = charitable_get_session()->get( 'donation' );

					if ( false !== $session_donation ) {
						$this->campaign_id = $session_donation->get( 'campaign_id' );
					}
				}
			}//end if

			if ( ! $this->campaign_id ) {
				$this->campaign_id = $this->get_campaign_id_from_submission();
			}

			return $this->campaign_id;
		}

		/**
		 * Returns the current campaign template. If there is no current template (like with legacy 1.x campaigns), return 0.
		 *
		 * @since  1.8.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return string
		 */
		public function get_current_campaign_template() {

			$campaign_settings = get_post_meta( $this->get_current_campaign_id(), 'campaign_settings_v2', true );

			if ( false === $campaign_settings ) { // This is a legacy campaign. Probably.
				return false;
			}

			$campaign_template_id = ! empty( $campaign_settings['template_id'] ) ? esc_attr( $campaign_settings['template_id'] ) : false;

			$templates_data = get_option( 'charitable_campaign_builder_templates' );
			// if false, then the template data was not stored and we will need to get it manually.
			if ( false === $templates_data ) {
				require_once charitable()->get_path( 'includes' ) .  'admin/campaign-builder/templates/class-templates.php';
				$builder_template = new Charitable_Campaign_Builder_Templates();
				$templates_data   = $builder_template->get_templates_data( $campaign_template_id, $campaign_settings );
			}

			$template_data = ! empty( $templates_data['templates'][ $campaign_template_id ] ) ? $templates_data['templates'][ $campaign_template_id ] : false;

			return $template_data;
		}

		/**
		 * Returns the current campaign template. If there is no current template (like with legacy 1.x campaigns), return 0.
		 *
		 * @since  1.8.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return string
		 */
		public function get_current_campaign_template_id() {

			$campaign_settings    = get_post_meta( $this->get_current_campaign_id(), 'campaign_settings_v2', true );
			$campaign_template_id = ! empty( $campaign_settings['template_id'] ) ? esc_attr( $campaign_settings['template_id'] ) : false;

			return $campaign_template_id;
		}

		/**
		 * Returns the current campaign template. If there is no current template (like with legacy 1.x campaigns), return 0.
		 *
		 * @since  1.8.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return string
		 */
		public function get_current_campaign_builder_data() {
			global $wp_query;

			$campaign_settings = get_post_meta( $this->get_current_campaign_id(), 'campaign_settings_v2', true );

			return $campaign_settings;
		}



		/**
		 * Returns the campaign ID from a form submission.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_campaign_id_from_submission() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( ! isset( $_POST['campaign_id'] ) ) {
				return 0;
			}

			$campaign_id = absint( sanitize_text_field( wp_unslash( $_POST['campaign_id'] ) ) );
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( Charitable::CAMPAIGN_POST_TYPE !== get_post_type( $campaign_id ) ) {
				return 0;
			}

			return $campaign_id;
		}

		/**
		 * Returns the current donation object. If there is no current donation, return false.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Donation|false
		 */
		public function get_current_donation() {
			if ( ! isset( $this->donation ) ) {
				$donation_id    = $this->get_current_donation_id();
				$this->donation = $donation_id ? charitable_get_donation( $donation_id ) : false;
			}

			return $this->donation;
		}

	/**
	 * Returns the current donation ID. If there is no current donation, return 0.
	 *
	 * @since  1.0.0
	 * @version 1.8.9.1
	 *
	 * @return int
	 */
	public function get_current_donation_id() {
		$donation_id = get_query_var( 'donation_id', 0 );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! $donation_id && isset( $_GET['donation_id'] ) ) {
			$donation_id = absint( sanitize_text_field( wp_unslash( $_GET['donation_id'] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $donation_id;
	}

	/**
	 * If set, add supported donation parameters to the session.
	 *
	 * @since  1.6.25
	 * @version 1.8.9.1
	 * @version 1.8.15.1 - Validate the amount and period before they reach the session.
	 *
	 * @param  int $campaign_id The campaign receiving the donation.
	 * @return boolean Whether anything was added to the session.
	 */
	public function add_donation_params_to_session( $campaign_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! array_key_exists( 'amount', $_REQUEST ) ) {
			return false;
		}

		$amount = $this->sanitize_requested_amount( wp_unslash( $_REQUEST['amount'] ), $campaign_id );

		if ( false === $amount ) {
			return false;
		}

		$period = array_key_exists( 'period', $_REQUEST )
			? $this->sanitize_requested_period( wp_unslash( $_REQUEST['period'] ) )
			: 'once';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		charitable_get_session()->add_donation( $campaign_id, $amount, $period );

		return true;
	}

	/**
	 * Sanitize a donation amount arriving as a URL or form parameter.
	 *
	 * Returns false for anything that should never reach the session. Storing a junk value is
	 * worse than ignoring it: the session amount is always cast to a float, so an unusable
	 * value is no longer the integer 0 that donation-amount-list.php tests for with
	 * `0 === $charitable_amount`, and the campaign's default suggested amount stops being
	 * pre-selected. The donor is then shown a form with no amount selected at all, and the
	 * value persists for the life of the session.
	 *
	 * @since  1.8.15.1
	 *
	 * @param  mixed $amount      The raw parameter value.
	 * @param  int   $campaign_id The campaign receiving the donation.
	 * @return float|false Sanitized amount, or false if it should be ignored.
	 */
	private function sanitize_requested_amount( $amount, $campaign_id = 0 ) {
		if ( ! is_scalar( $amount ) ) {
			return false;
		}

		$amount = charitable_get_currency_helper()->cast_to_decimal_format( sanitize_text_field( $amount ) );

		if ( ! is_numeric( $amount ) ) {
			return false;
		}

		$amount = (float) $amount;

		if ( ! is_finite( $amount ) || $amount <= 0 ) {
			return false;
		}

		/*
		 * Above roughly 1e14, casting a float back to a string produces scientific notation,
		 * which the amount templates re-parse into an unrelated number. The donor would then
		 * be shown one figure while a different one is submitted, so refuse these outright.
		 */
		if ( $amount >= 1000000000000 ) {
			return false;
		}

		$maximum = charitable_get_maximum_donation_amount( $campaign_id );

		if ( false !== $maximum && $amount > (float) $maximum ) {
			return false;
		}

		return $amount;
	}

	/**
	 * Sanitize a donation period arriving as a URL or form parameter.
	 *
	 * Anything unrecognised becomes 'once'. The raw value was previously stored as the
	 * session's donation_period, and donation-amount-list.php suppresses every amount button
	 * when the period is not one the campaign accepts, so an arbitrary string left the form
	 * with nothing selected.
	 *
	 * @since  1.8.15.1
	 *
	 * @param  mixed $period The raw parameter value.
	 * @return string
	 */
	private function sanitize_requested_period( $period ) {
		if ( ! is_scalar( $period ) ) {
			return 'once';
		}

		$period  = sanitize_text_field( $period );
		$allowed = array( 'once', 'month', 'quarter', 'semiannual', 'year' );

		if ( function_exists( 'charitable_recurring_get_donation_periods_i18n' ) ) {
			$allowed = array_merge( array( 'once' ), array_keys( (array) charitable_recurring_get_donation_periods_i18n() ) );
		}

		return in_array( $period, $allowed, true ) ? $period : 'once';
	}

	/**
	 * If set, add supported donation parameters to the session on campaign pages.
	 *
	 * add_donation_params_to_session() is hooked to 'charitable_is_donate_page', which is
	 * only fired by Charitable_Campaign_Donation_Endpoint::setup_template(). That endpoint
	 * only becomes the current endpoint when /donate/ is actually in the URL, because
	 * Charitable_Endpoints::get_current_endpoint() resolves endpoints with 'strict' => true
	 * and the endpoint's is_page() returns false under a strict check without it.
	 *
	 * When the donation form is shown on the campaign page itself, /donate/ is never in the
	 * URL: with the 'same_page' display setting, the endpoint's own get_page_url() returns
	 * the plain campaign permalink. Anything sending a donor to that URL with ?amount= set,
	 * such as the mini widget shortcode in redirect mode, therefore had the requested amount
	 * silently dropped, and the form fell back to the campaign's default suggested amount.
	 *
	 * @since  1.8.15.1
	 *
	 * @global WP_Query $wp_query
	 * @return void
	 */
	public function maybe_add_donation_params_to_session() {
		global $wp_query;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! array_key_exists( 'amount', $_REQUEST ) ) {
			return;
		}

		if ( ! is_singular( Charitable::CAMPAIGN_POST_TYPE ) ) {
			return;
		}

		/*
		 * The /donate/ endpoint fires 'charitable_is_donate_page' for itself, so leave that
		 * path untouched rather than writing the same values to the session twice.
		 */
		if ( isset( $wp_query->query_vars ) && array_key_exists( 'donate', $wp_query->query_vars ) ) {
			return;
		}

		$campaign_id = charitable_get_current_campaign_id();

		if ( ! $campaign_id ) {
			return;
		}

		if ( ! $this->add_donation_params_to_session( $campaign_id ) ) {
			return;
		}

		/*
		 * What this page renders now depends on a query parameter, so it must not be written to
		 * a full page cache: otherwise the first visitor's amount is served to everyone who
		 * follows. Charitable_Endpoints::disable_endpoint_cache() does this for the /donate/
		 * endpoint, which is exactly the endpoint that does not resolve here.
		 */
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- DONOTCACHEPAGE is a third-party constant used by caching plugins for compatibility.
			define( 'DONOTCACHEPAGE', true );

			/** This action is documented in includes/endpoints/class-charitable-endpoints.php */
			do_action( 'charitable_do_not_cache' );
		}
	}

		/**
		 * Temporarily set the current campaign to the one for the donation form.
		 *
		 * @since  1.6.55
		 *
		 * @param  Charitable_Donation_Form $form The donation form object.
		 * @return void
		 */
		public function set_current_campaign_before_donation_form( Charitable_Donation_form $form ) {
			if ( isset( $this->campaign_id ) && $this->campaign_id === $form->get_campaign()->ID ) {
				return;
			}

			if ( isset( $this->campaign ) ) {
				$this->original_campaign = $this->campaign;
			}

			if ( isset( $this->campaign_id ) ) {
				$this->original_campaign_id = $this->campaign_id;
			}

			$this->campaign    = $form->get_campaign();
			$this->campaign_id = $this->campaign->ID;
		}

		/**
		 * Reset the $campaign and $campaign_id properties to what they originally were.
		 *
		 * @since  1.6.55
		 *
		 * @return void
		 */
		public function reset_current_campaign_after_donation_form() {
			$this->campaign    = $this->original_campaign;
			$this->campaign_id = $this->original_campaign ? $this->original_campaign->ID : false;
		}
	}

endif;
