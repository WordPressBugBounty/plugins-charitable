<?php
/**
 * Model for creating and retrieving the plans related to a particular campaign.
 *
 * @package   Charitable Square
 * @author    Studio 164a
 * @copyright Copyright (c) 2022, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 */

// namespace Charitable\Pro\Square\Gateway;

// use Charitable\Pro\Square\Domain\PackageLoader;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Charitable_Square_Subscription_Plan' ) ) :

	/**
	 * SubscriptionPlan
	 *
	 * @since 1.0.0
	 */
	class Charitable_Square_Subscription_Plan {

		/**
		 * Campaign ID.
		 *
		 * @since 1.0.0
		 *
		 * @var   int
		 */
		private $campaign_id;

		/**
		 * Options.
		 *
		 * @since 1.0.0
		 *
		 * @var   array|null
		 */
		private $options;

		/**
		 * Mode.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $mode;

		/**
		 * The campaign's stored plans.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $plans;

		/**
		 * Internal arguments used for defining a particular plan.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $args;

		/**
		 * Plan args.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $plan_args;

		/**
		 * Plan key.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $plan_key;

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 *
		 * @param int        $campaign_id The campaign id.
		 * @param array      $args        Mixed set of args.
		 * @param array|null $options     Additional options to pass to Square in API request.
		 */
		public function __construct( $campaign_id, $args, $options = null ) {
			$this->campaign_id = $campaign_id;
			$this->mode        = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$this->args        = $args;
			$this->options     = $options;
		}

		/**
		 * Return a class property if set.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $prop The class property to return.
		 * @return mixed Returns null if the class property is not set.
		 */
		public function __get( $prop ) {
			return isset( $this->$prop ) ? $this->$prop : null;
		}

		/**
		 * Get the API object.
		 *
		 * @since  1.0.0
		 *
		 * @return \Charitable\Pro\Square\Gateway\Api
		 */
		public function api() {
			$api = new Charitable_Square_API();
			return $api;
			// return PackageLoader::container()->get( 'api' );
		}

		/**
		 * Return the plans for the campaign.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_plans() {

			error_log( 'get_plans: plans are already set' );
			error_log( print_r( $this->plans, true ) );

			if ( isset( $this->plans ) ) {
				return $this->plans;
			}

			error_log( 'get_plans: plans are not set so getting them from the database from the campaign' );
			$all_plans = get_post_meta( $this->campaign_id, 'square_donation_plans', true );
			error_log( print_r( $all_plans, true ) );

			if ( ! is_array( $all_plans ) || ! array_key_exists( $this->mode, $all_plans ) ) {
				$this->plans = array();
				return $this->plans;
			}

			$this->plans = $all_plans[ $this->mode ];

			return $this->plans;
		}

		/**
		 * Return the plan args.
		 * charitable_recurring_get_plan_args comes from Charitable's recurring addon.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_plan_args() {
			if ( array_key_exists( 'recurring', $this->args ) ) {
				$this->plan_args = charitable_recurring_get_plan_args(
					array(
						'period'   => $this->args['recurring']->get_donation_period(),
						'amount'   => charitable_sanitize_amount( (string) $this->args['recurring']->get_recurring_donation_amount( false ) ),
						'interval' => $this->args['recurring']->get_donation_interval(),
					)
				);
			} else {
				$this->plan_args = charitable_recurring_get_plan_args( $this->args );
			}

			return $this->plan_args;
		}

		/**
		 * Return the key for a plan.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_plan_key() {
			if ( ! isset( $this->plan_key ) ) {
				$this->plan_key = charitable_recurring_get_plan_key( $this->get_plan_args() );
			}

			return $this->plan_key;
		}

		/**
		 * Return the plan id, or false if none exists.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $check_api Whether to check the API for the plan.
		 * @return string|false Plan ID if set. False otherwise.
		 */
		public function get_plan( $check_api = false ) {
			$plan_key = $this->get_plan_key();
			$plans    = $this->get_plans();

			if ( ! array_key_exists( $plan_key, $plans ) ) {
				return false;
			}

			if ( ! $check_api ) {
				return $plans[ $plan_key ];
			}

			return $this->plan_exists( $plans[ $plan_key ] );
		}

		/**
		 * Checks whether a plan still exists in Square.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $plan_id The plan ID.
		 * @return string|false The plan ID if it still exists. False otherwise.
		 */
		public function plan_exists( $plan_id ) {
			$plan = $this->api()->get( 'catalog/object/' . $plan_id );
			if ( ! $plan ) {
				return false;
			}
			return $plan_id;
		}

		/**
		 * Create a plan.
		 *
		 * @since  1.8.7
		 *
		 * @return string The Plan ID.
		 */
		public function create_plan() {
			$currency_helper       = charitable_get_currency_helper();
			$currency              = charitable_get_currency();
			$zero_decimal_currency = $currency_helper->is_zero_decimal_currency( $currency );
			$plan_args             = $this->get_plan_args();
			$period                = $this->get_plan_period( $plan_args );
			$amount                = $this->sanitize_plan_amount( $plan_args['amount'], $currency, $zero_decimal_currency );
			$amount_description    = strval( $zero_decimal_currency ? $amount : $amount / 100 );
			$plan_name             = sprintf(
				/* translators: %1$s: campaign title; %2$s: amount; %3$s: currency; %4$s: period */
				_x( '%1$s - %2$s %3$s every %4$s', 'campaign title — amount every period', 'charitable-square' ),
				str_replace( '&ndash;', '-', get_post( $this->campaign_id )->post_title ),
				charitable_sanitize_amount( $amount_description ),
				$currency,
				charitable_recurring_get_donation_periods_i18n( 1, $plan_args['period'] )
			);
			$variation_name        = 'Standard Variation';

			$phases = array(
						'cadence' => $period,
						'pricing' => array(
							'type' => 'STATIC',
							'price' => array(
								'amount'   => $amount,
								'currency' => $currency,
							),
						),
					);

			$subscription_plan_variations = array(
				'id' => '#variation_1',
				'type' => 'SUBSCRIPTION_PLAN_VARIATION',
				'subscription_plan_variation_data' => array(
					'name' => $variation_name,
					'phases' => array(
						$phases
					),
				),
			);

			$args = array(
				'idempotency_key' => uniqid(),
				'object'          => array(
					'id'                     => '#subscription',
					'type'                   => 'SUBSCRIPTION_PLAN',
					'subscription_plan_data' => array(
						'name'       => $plan_name,
						'phases'     => array(
							$phases
						),
						'subscription_plan_variations' => array(
							$subscription_plan_variations
						),
					),
				),
			);

			// $subscription_plan_variation_data = new \Square\Models\CatalogSubscriptionPlanVariation( $plan_name, $phases );
			// $subscription_plan_variation_data->setSubscriptionPlanId( $plan_id );
			// $subscription_plan_variation_data->setName( $plan_name );

			// $object = new \Square\Models\CatalogObject( 'SUBSCRIPTION_PLAN_VARIATION', '#1' );
			// $object->setSubscriptionPlanVariationData( $subscription_plan_variation_data );


			error_log( 'Square Create Plan Request:' );
			error_log( print_r( $args, true ) );

			$new_plan = $this->api()->post( 'catalog/object/', $args );

			if ( false === $new_plan ) {
				$response = $this->api()->get_last_response();
				error_log( 'Square API Response:' );
				error_log( print_r( $response, true ) );

				$response_body = wp_remote_retrieve_body( $response );
				error_log( 'Response Body:' );
				error_log( $response_body );

				$decoded_response = json_decode( $response_body );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					error_log( 'JSON Decode Error: ' . json_last_error_msg() );
					charitable_get_notices()->add_error( __( 'Invalid response from Square API', 'charitable-square' ) );
					return false;
				}

				if ( isset( $decoded_response->errors ) ) {
					$this->handle_errors( $decoded_response->errors );
				} else {
					charitable_get_notices()->add_error( __( 'Unknown error occurred while creating subscription plan', 'charitable-square' ) );
				}
				return false;
			}

			$plan_id = $new_plan->catalog_object->id;

			$plan_variation_id = $new_plan->catalog_object->subscription_plan_data->subscription_plan_variations[0]->id;

			error_log( 'new plan is' );
			error_log( print_r( $new_plan, true ) );
			error_log( 'plan variation id is' );
			error_log( print_r( $plan_variation_id, true ) );

			error_log( 'create_plan: plan id is' );
			error_log( print_r( $plan_id, true ) );

			$this->save_plan( $plan_id );

			return $plan_id;
		}

		public function create_plan_variation_id( $plan_id, $plan ) {

			error_log( 'create_plan_variation_id: plan id is' );
			error_log( print_r( $plan_id, true ) );

			$catalog_object = $this->api()->get( 'catalog/object/' . $plan_id );

			error_log( 'create_plan_variation_id: catalog object is' );
			error_log( print_r( $catalog_object, true ) );

			$plan_variation_id = $catalog_object->object->subscription_plan_data->subscription_plan_variations[0]->id;

			error_log( 'create_plan_variation_id: plan variation id is' );
			error_log( print_r( $plan_variation_id, true ) );

			return $plan_variation_id;
			// error_log( 'create_plan_variation_id: plan variation id is' );
			// error_log( print_r( $plan_variation_id, true ) );

			// return $plan_variation_id;
		}

		// {
		// 	"catalog_object": {
		// 	"type": "SUBSCRIPTION_PLAN",
		// 		"id": "VVH3YXQSQATSL3XR4LIKD3QM",
		// 		"updated_at": "2022-11-09T20:28:12.208Z",
		// 		"created_at": "2022-11-09T20:28:12.208Z",
		// 		"version": 1668025692208,
		// 		"present_at_all_locations": true,
		// 		"subscription_plan_data": {
		// 			"name": "Coffee Subscription",
		// 	"subscription_plan_variations": [{
		// 				"type": "SUBSCRIPTION_PLAN_VARIATION",
		// 				"id": "CUPS23SKJ7J4FD4F3IMVAEOH",
		// 				"updated_at": "2022-11-09T20:52:58.857Z",
		// 				"created_at": "2022-11-09T20:52:58.857Z",
		// 		  "version": 1668027178857,
		// 				"present_at_all_locations": true,
		// 				"subscription_plan_variation_data": {
		// 					"name": "Coffee of the Month Club",
		// 					"phases": [{
		// 						"uid": "CR7TS35JYEVXC5BSDV5N7Z4Y",
		// 						"cadence": "MONTHLY",
		// 						"ordinal": 0,
		// 	"periods": 1,
		// 						"pricing": {
		// 							"type": "STATIC",
		// 							"price": {
		// 								"amount": 1000,
		// 								"currency": "USD"
		// 							}
		// 						}
		// 					},
		// 						{
		// 						"uid": "AW9ES43NVGRFC8AQRK8J5X1S",
		// 						"cadence": "MONTHLY",
		// 						"ordinal": 1,
		// 						"pricing": {
		// 							"type": "RELATIVE",
		// 							"discount_ids": ["5PFBH6YH5SB2F63FOIHJ7HWR"]
		// 							}
		// 					}],
		// 					"subscription_plan_id": "VVH3YXQSQATSL3XR4LIKD3QM"
		// 					}
		// 				}],
		// 				"eligible_category_ids": ["2CJLFP5C6G74W3U3HD5YAE5W"],
		// 				"all_items": false
		// 			}
		// 		}
		// 	}


		/**
		 * Save plan to campaign meta.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $plan_id Save a new plan ID.
		 * @return mixed
		 */
		public function save_plan( $plan_id ) {
			error_log('save_plan: plan id is');
			error_log(print_r($plan_id, true));

			$mode_plans                          = $this->get_plans();
			$mode_plans[ $this->get_plan_key() ] = $plan_id;

			$all_plans = get_post_meta( $this->campaign_id, 'square_donation_plans', true );

			if ( ! is_array( $all_plans ) ) {
				$all_plans = array();
			}

			$all_plans[ $this->mode ] = $mode_plans;

			return update_post_meta( $this->campaign_id, 'square_donation_plans', $all_plans );
		}

		/**
		 * Return the Square period given a set of plan args.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args The plan args.
		 * @return string
		 */
		public function get_plan_period( $args ) {
			switch ( $args['period'] ) {
				case 'day':
					$period = 'DAILY';
					break;
				case 'week':
					$period = 'WEEKLY';
					break;
				case 'month':
					$period = 'MONTHLY';
					break;
				case 'quarter':
					$period = 'QUARTERLY';
					break;
				case 'semiannual':
					$period = 'EVERY_SIX_MONTHS';
					break;
				case 'year':
					$period = 'ANNUAL';
					break;
			}

			return $period;
		}

		/**
		 * Sanitize the plan amount.
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $amount                The plan amount.
		 * @param  string  $currency              The site currency.
		 * @param  boolean $zero_decimal_currency Whether the site is using a zero decimal currency.
		 * @return string
		 */
		public function sanitize_plan_amount( $amount, $currency, $zero_decimal_currency ) {
			/* Unless it's a zero decimal currency, multiply the currency x 100 to get the amount in cents. */
			if ( $zero_decimal_currency ) {
				$amount = $amount * 1;
			} else {
				$amount = $amount * 100;
			}

			return absint( round( $amount ) );
		}

		/**
		 * Handle some common errors with nicer messages
		 *
		 * @since 1.0.0
		 *
		 * @param array $errors The errors returned by Square
		 * @return void
		 */
		private function handle_errors( $errors ) {
			if ( 0 === count( $errors ) ) {
				charitable_get_notices()->add_error( __( 'An unexpected error occurred trying to process your payment', 'charitable-square' ) );
				return;
			}

			// Only worry about the first error.
			$error = $errors[0];

			// For authentication errors, keep the message generic.
			if ( 'AUTHENTICATION_ERROR' === $error->category ) {
				charitable_get_notices()->add_error( __( 'Unable to connect to Square. If you are an administrator, please check your Square Gateway settings.', 'charitable-square' ) );
				return;
			}

			// Just output the error code provided by Square.
			charitable_get_notices()->add_error(
				sprintf(
				/* translators: %s: error message from Square */
					__( 'Payment request failed with error: %s.', 'charitable-square' ),
					$error->code
				)
			);
			return;
		}
	}

endif;
