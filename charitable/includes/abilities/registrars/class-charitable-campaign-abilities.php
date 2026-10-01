<?php
/**
 * Campaign read abilities: list, detail, performance and builder templates.
 *
 * These four abilities are the read half of the campaign domain — the domain
 * the product demo lives in. list-campaigns is the entry point (it is how a
 * client finds a campaign_id at all); get-campaign and get-campaign-performance
 * both take that id and answer a different question about the same campaign
 * (configuration vs. how it's doing); list-campaign-templates is unrelated to
 * a specific campaign and exists to inform a future create-campaign call.
 *
 * Task 5 extends this same class with create-campaign, update-campaign and
 * set-campaign-status — the write half of this domain — so every private
 * helper below (find_campaign(), campaign_id_input_schema(), money-shaping
 * helpers) is written to be reusable by that follow-up rather than assuming
 * read-only forever.
 *
 * LOAD ORDER: create-campaign's `mode: visual` path needs
 * Charitable_Campaign_Builder_Templates and
 * charitable_template_layout_to_campaign_layout(), both of which
 * charitable.php loads only inside its is_admin() gate. The execute methods
 * below lazily require_once them before use, which is why an MCP call over
 * REST or WP-CLI works. Anything added to that path must do the same or it
 * will fatal on a non-admin request.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Abilities
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 * @version   1.8.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Abilities' ) ) :

	/**
	 * Charitable_Campaign_Abilities
	 *
	 * @since 1.8.13
	 */
	class Charitable_Campaign_Abilities extends Charitable_Abilities_Registrar {

		/**
		 * Register the campaign abilities.
		 *
		 * @since 1.8.13
		 *
		 * @return void
		 */
		public function register() {
			$this->register_ability(
				'charitable/list-campaigns',
				array(
					'label'               => __( 'List Campaigns', 'charitable' ),
					'description'         => __( "Lists fundraising campaigns with their goal, amount raised and status. Use to find a campaign's ID before reading or updating it, or to answer questions about which campaigns exist.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_list_campaigns' ),
					'permission_callback' => array( __CLASS__, 'permission_campaigns' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'status'   => array(
								'type'    => 'string',
								'enum'    => array_merge( self::writable_campaign_statuses(), array( 'any' ) ),
								'default' => 'any',
							),
							'search'   => array(
								'type' => 'string',
							),
							'per_page' => array(
								'type'    => 'integer',
								'minimum' => 1,
								'maximum' => 100,
								'default' => 20,
							),
							'page'     => array(
								'type'    => 'integer',
								'minimum' => 1,
								'default' => 1,
							),
							'orderby'  => array(
								'type'    => 'string',
								'enum'    => array( 'date', 'title', 'amount', 'goal' ),
								'default' => 'date',
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaigns' => array(
								'type'  => 'array',
								'items' => self::campaign_summary_schema(),
							),
							'total'     => array( 'type' => 'integer' ),
							'pages'     => array( 'type' => 'integer' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-campaign',
				array(
					'label'               => __( 'Get Campaign', 'charitable' ),
					'description'         => __( 'Returns the full configuration of one campaign including goal, dates, suggested donation amounts and description. Use after List Campaigns when you need detail on a specific campaign.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_campaign' ),
					'permission_callback' => array( __CLASS__, 'permission_read_campaign' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::campaign_id_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                      => array( 'type' => 'integer' ),
							'title'                   => array( 'type' => 'string' ),
							'status'                  => array( 'type' => 'string' ),
							'url'                     => array( 'type' => 'string' ),
							'description'             => array( 'type' => 'string' ),
							'has_goal'                => array( 'type' => 'boolean' ),
							'goal'                    => array( 'type' => array( 'number', 'null' ) ),
							'goal_formatted'          => array( 'type' => array( 'string', 'null' ) ),
							'is_endless'              => array( 'type' => 'boolean' ),
							'end_date'                => array( 'type' => array( 'string', 'null' ) ),
							'has_ended'               => array( 'type' => 'boolean' ),
							'allow_custom_donations'  => array( 'type' => 'boolean' ),
							'minimum_donation_amount' => array( 'type' => 'number' ),
							'minimum_donation_amount_formatted' => array( 'type' => 'string' ),
							'suggested_donations'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'amount'           => array( 'type' => 'number' ),
										'amount_formatted' => array( 'type' => 'string' ),
										'description'      => array( 'type' => 'string' ),
									),
								),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-campaign-performance',
				array(
					'label'               => __( 'Get Campaign Performance', 'charitable' ),
					'description'         => __( 'Returns fundraising performance for one campaign: amount raised, percent of goal, donation count, donor count and average gift. Use to answer how a specific campaign is doing.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_campaign_performance' ),
					'permission_callback' => array( __CLASS__, 'permission_read_campaign' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::campaign_id_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id'            => array( 'type' => 'integer' ),
							'raised'                 => array( 'type' => 'number' ),
							'raised_formatted'       => array( 'type' => 'string' ),
							'has_goal'               => array( 'type' => 'boolean' ),
							'percent_raised'         => array( 'type' => array( 'number', 'null' ) ),
							'donation_count'         => array( 'type' => 'integer' ),
							'donor_count'            => array( 'type' => 'integer' ),
							'average_gift'           => array( 'type' => 'number' ),
							'average_gift_formatted' => array( 'type' => 'string' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/list-campaign-templates',
				array(
					'label'               => __( 'List Campaign Templates', 'charitable' ),
					'description'         => __( "Lists the campaign builder templates available when creating a campaign. Use before Create Campaign if the user wants a specific look, or to tell them what designs exist. Pass the id you get back as Create Campaign's template. A template's internal layout and field structure are deliberately not described here and you do not need them: Create Campaign applies the template and fills its text, goal and images from the brief you give it. To check what a campaign ended up with, call Get Campaign and Get Campaign Styles. Do not read the campaign's stored settings or post meta directly, through WP-CLI or SQL or any other route - those are internal, are not a supported interface, and will not tell you anything these abilities do not.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_list_campaign_templates' ),
					'permission_callback' => array( __CLASS__, 'permission_campaigns' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::no_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'templates' => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'id'          => array( 'type' => 'string' ),
										'label'       => array( 'type' => 'string' ),
										'description' => array( 'type' => 'string' ),
										'categories'  => array(
											'type'  => 'array',
											'items' => array( 'type' => 'string' ),
										),
									),
								),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/create-campaign',
				array(
					'label'               => __( 'Create Campaign', 'charitable' ),
					'description'         => __( "Creates a new fundraising campaign with a title, goal, end date and optional suggested donation amounts. Creates it as a draft unless status is given. Set mode to 'visual' with a template (see List Campaign Templates for the available options) to build a campaign editable afterwards in the drag-and-drop Campaign Builder — this is what an AI-built fundraiser should use. Visual mode DOES set up: the template's layout and colours (its sample story, photos and placeholder copy are cleared so nothing fake ships beside your content), and a default ladder of suggested donation amounts whenever suggested_amounts is not supplied, so the donation form is never published with no amounts for a donor to pick. That default ladder is scaled to the campaign's goal, so a large goal gets proportionately larger suggestions instead of a ladder a donor would have to give 250 times to reach it; a campaign with a small goal, or none at all, gets Charitable's usual starter amounts. Pass suggested_amounts to choose your own, or an empty array for none; each entry is a plain number, or an object with amount and description to caption it for donors. Pass description to write the campaign's story, and image_url (a public https link to a photo) to set its main image — the image is fetched into the Media Library, and if it cannot be fetched the campaign is still created with image_attached false. Visual mode does NOT set up a payment gateway, and it does not invent a goal or an end date — supply those yourself if the campaign needs them. The default mode, 'legacy', creates a bare campaign edited in the classic WordPress post editor: no template, no colors and no suggested amounts. For an end date, get the real calendar date from Get Reports Date Ranges' future_dates block — NOT from its backward-looking report ranges, whose end dates are today. Use Set Campaign Status afterwards to publish it.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_create_campaign' ),
					'permission_callback' => array( __CLASS__, 'permission_create_campaign' ),
					'annotations'         => array(
						'destructive' => false,
						'idempotent'  => false,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'title'               => array(
								'type' => 'string',
							),
							'goal'                => array(
								'type' => array( 'number', 'string' ),
							),
							'end_date'            => array(
								'type' => 'string',
							),
							'description'         => array(
								'type' => 'string',
							),
							'suggested_amounts'   => self::suggested_amounts_input_schema(),
							'allow_custom_amount' => array(
								'type' => 'boolean',
							),
							'status'              => array(
								'type'    => 'string',
								'enum'    => self::writable_campaign_statuses(),
								'default' => 'draft',
							),
							'mode'                => array(
								'type'    => 'string',
								'enum'    => array( 'legacy', 'visual' ),
								'default' => 'legacy',
							),
							'template'            => array(
								'type' => 'string',
							),
							'image_url'           => array(
								'type' => 'string',
							),
						),
						'required'             => array( 'title' ),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id'    => array( 'type' => 'integer' ),
							'status'         => array( 'type' => 'string' ),
							'url'            => array( 'type' => 'string' ),
							'image_attached' => array(
								'type'        => 'boolean',
								'description' => __( 'Whether a photo from image_url was fetched and set as the campaign\'s main image. False when no image_url was given, or when it could not be fetched (see image_error).', 'charitable' ),
							),
							'image_error'    => array(
								'type'        => 'string',
								'description' => __( 'Present only when image_url was supplied but could not be used; a short reason. The campaign is still created without the image.', 'charitable' ),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/update-campaign',
				array(
					'label'               => __( 'Update Campaign', 'charitable' ),
					'description'         => __( 'Updates named fields on an existing campaign, leaving every field you do not supply untouched. Use to change a goal, end date, description or suggested amounts. Supplying suggested_amounts replaces the whole ladder, but a plain number keeps the caption that amount already had, so changing one amount does not wipe the others\' captions — pass an object with an explicit empty description to clear one. Does not change publish status — use Set Campaign Status for that.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_update_campaign' ),
					'permission_callback' => array( __CLASS__, 'permission_edit_campaign' ),
					'annotations'         => array(

						/*
						 * destructive is FALSE, and that is a REST-channel requirement
						 * rather than a matter of taste. Core maps
						 * `destructive && idempotent` onto the DELETE method
						 * (class-wp-rest-abilities-v1-run-controller.php:114), so an
						 * ability annotated both ways returns 405 to a POST and is
						 * callable only as a DELETE carrying its whole input nested in
						 * the query string. Verified live against wp-abilities/v1: any
						 * client that POSTs an update gets a 405.
						 *
						 * false is also the more accurate of the two readings. The
						 * annotation asks whether the ability may perform DESTRUCTIVE
						 * updates; this one changes named fields and destroys nothing.
						 * charitable/trash-campaign keeps destructive: true and is
						 * correctly a DELETE.
						 */
						'destructive' => false,
						'idempotent'  => true,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'campaign_id'         => array(
								'type' => 'integer',
							),
							'title'               => array(
								'type' => 'string',
							),
							'goal'                => array(
								'type' => array( 'number', 'string' ),
							),
							'end_date'            => array(
								'type' => 'string',
							),
							'description'         => array(
								'type' => 'string',
							),
							'suggested_amounts'   => self::suggested_amounts_input_schema(),
							'allow_custom_amount' => array(
								'type' => 'boolean',
							),
						),
						'required'             => array( 'campaign_id' ),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id'    => array( 'type' => 'integer' ),
							'updated_fields' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/set-campaign-status',
				array(
					'label'               => __( 'Set Campaign Status', 'charitable' ),
					'description'         => __( 'Publishes, unpublishes or changes the status of a campaign. Use this to launch a campaign that was created as a draft, or to take one offline. Taking a LIVE campaign that already has donations offline - any change away from published - is refused unless confirm_has_donations is true, because unpublishing stops it accepting donations immediately. Publishing a campaign, and changing the status of one that is not currently published, are never gated: neither interrupts fundraising.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_set_campaign_status' ),
					'permission_callback' => array( __CLASS__, 'permission_publish_campaigns' ),
					'annotations'         => array(

						/*
						 * destructive is FALSE, and that is a REST-channel requirement
						 * rather than a matter of taste. Core maps
						 * `destructive && idempotent` onto the DELETE method
						 * (class-wp-rest-abilities-v1-run-controller.php:114), so an
						 * ability annotated both ways returns 405 to a POST and is
						 * callable only as a DELETE carrying its whole input nested in
						 * the query string. Verified live against wp-abilities/v1: any
						 * client that POSTs an update gets a 405.
						 *
						 * false is also the more accurate of the two readings. The
						 * annotation asks whether the ability may perform DESTRUCTIVE
						 * updates; this one changes named fields and destroys nothing.
						 * charitable/trash-campaign keeps destructive: true and is
						 * correctly a DELETE.
						 */
						'destructive' => false,
						'idempotent'  => true,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'campaign_id'           => array(
								'type' => 'integer',
							),
							'status'                => array(
								'type' => 'string',
								'enum' => self::writable_campaign_statuses(),
							),

							/*
							 * Same property, same name and same default as
							 * charitable/trash-campaign's, deliberately rather than a
							 * new name: a client that already knows how to confirm a
							 * destructive campaign operation needs no new vocabulary,
							 * and the two refusals share the
							 * `charitable_campaign_has_donations` error code for the
							 * same reason.
							 *
							 * ⚠️ PRO SHOULD ADOPT THIS. charitable/set-campaign-status
							 * is a shared ability schema across both editions, and Pro
							 * 1.8.18 has no such guard here. Two editions that disagree
							 * about whether a destructive operation needs confirming is
							 * worse than either answer: an assistant carrying a tool
							 * definition cached from a Pro site would omit the flag and
							 * be refused on Lite, and a site downgrading would silently
							 * lose the guard. Port it to Pro next cycle.
							 *
							 * @since 1.8.13
							 */
							'confirm_has_donations' => array(
								'type'    => 'boolean',
								'default' => false,
							),
						),
						'required'             => array( 'campaign_id', 'status' ),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id' => array( 'type' => 'integer' ),
							'status'      => array( 'type' => 'string' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/trash-campaign',
				array(
					'label'               => __( 'Trash Campaign', 'charitable' ),
					'description'         => __( 'Moves a campaign to the trash, where it can be restored later. Use this to undo a campaign that was created by mistake. Refuses campaigns that already have donations unless confirm_has_donations is true, because trashing them hides real fundraising history. Trashing is the ONLY removal available and that is deliberate: there is no ability that deletes a campaign permanently, because a campaign\'s donation records reference it, and destroying it would orphan real money that donors gave and that the site still has to report on. If a permanent delete is asked for, explain that rather than only refusing, and offer the trash as what can be done.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_trash_campaign' ),
					'permission_callback' => array( __CLASS__, 'permission_trash_campaign' ),
					'annotations'         => array(
						'destructive' => true,
						'idempotent'  => true,
					),
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'campaign_id'           => array(
								'type' => 'integer',
							),
							'confirm_has_donations' => array(
								'type'    => 'boolean',
								'default' => false,
							),
						),
						'required'             => array( 'campaign_id' ),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id'     => array( 'type' => 'integer' ),
							'previous_status' => array( 'type' => 'string' ),
							'trashed'         => array( 'type' => 'boolean' ),
							'donation_count'  => array( 'type' => 'integer' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/restore-campaign',
				array(
					'label'               => __( 'Restore Campaign', 'charitable' ),
					'description'         => __( 'Restores a campaign from the trash. Use this to undo a trash-campaign call. The campaign comes back as a draft, so publish it separately if it should be live again.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_restore_campaign' ),
					'permission_callback' => array( __CLASS__, 'permission_edit_campaign' ),
					'annotations'         => array(

						/*
						 * destructive is FALSE, and that is a REST-channel requirement
						 * rather than a matter of taste. Core maps
						 * `destructive && idempotent` onto the DELETE method
						 * (class-wp-rest-abilities-v1-run-controller.php:114), so an
						 * ability annotated both ways returns 405 to a POST and is
						 * callable only as a DELETE carrying its whole input nested in
						 * the query string. Verified live against wp-abilities/v1: any
						 * client that POSTs an update gets a 405.
						 *
						 * false is also the more accurate of the two readings. The
						 * annotation asks whether the ability may perform DESTRUCTIVE
						 * updates; this one changes named fields and destroys nothing.
						 * charitable/trash-campaign keeps destructive: true and is
						 * correctly a DELETE.
						 */
						'destructive' => false,
						'idempotent'  => true,
					),
					'input_schema'        => self::campaign_id_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id' => array( 'type' => 'integer' ),
							'status'      => array( 'type' => 'string' ),
							'restored'    => array( 'type' => 'boolean' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/get-campaign-styles',
				array(
					'label'               => __( 'Get Campaign Styles', 'charitable' ),
					'description'         => __( 'Returns the colours a campaign uses and the colour names that can be changed. The free version of Charitable exposes the primary, secondary and button colours; Charitable Pro adds background, text, progress bar and tab colours plus typography and animation.', 'charitable' ),
					'execute_callback'    => array( $this, 'execute_get_campaign_styles' ),
					'permission_callback' => array( __CLASS__, 'permission_read_campaign' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id' => array(
								'type'        => 'integer',
								'description' => __( 'The campaign to read.', 'charitable' ),
							),
						),
						'required'   => array( 'campaign_id' ),
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id'      => array( 'type' => 'integer' ),
							'colors'           => array( 'type' => 'object' ),
							'has_saved_styles' => array( 'type' => 'boolean' ),
							'editable_keys'    => array( 'type' => 'array' ),
						),
					),
				)
			);

			$this->register_ability(
				'charitable/update-campaign-styles',
				array(
					'label'               => __( 'Update Campaign Styles', 'charitable' ),
					'description'         => __( "Changes a campaign's colours. Accepts primary, secondary and button_bg as six-digit hex values. Any other name is reported back in `ignored` rather than applied - use Get Campaign Styles to see what this site accepts.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_update_campaign_styles' ),
					'permission_callback' => array( __CLASS__, 'permission_edit_campaign' ),
					'annotations'         => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id' => array(
								'type'        => 'integer',
								'description' => __( 'The campaign to restyle.', 'charitable' ),
							),
							'colors'      => array(
								'type'        => 'object',
								'description' => __( 'Colour name => six-digit hex, for example { "primary": "#31714c" }.', 'charitable' ),
							),
						),
						'required'   => array( 'campaign_id', 'colors' ),
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_id' => array( 'type' => 'integer' ),
							'updated'     => array( 'type' => 'array' ),
							'ignored'     => array( 'type' => 'array' ),
							'colors'      => array( 'type' => 'object' ),
						),
					),
				)
			);

			/*
			 * Pro drift catch-up (pinned a776b2cde8): Pro added
			 * charitable/describe-editing-schema mid-port, after Tasks 1-5 had
			 * already landed in Lite. This is NOT a verbatim port of that
			 * commit — see execute_describe_editing_schema()'s docblock for why
			 * Lite's output is a genuine subset of Pro's rather than a copy of
			 * it. Structure, registration args and output SHAPE follow Pro;
			 * the CONTENT is derived from what this edition actually supports.
			 */
			$this->register_ability(
				'charitable/describe-editing-schema',
				array(
					'label'               => __( 'Describe Editing Schema', 'charitable' ),
					'description'         => __( "Describes what this site lets an assistant edit on a campaign: the fields Update Campaign accepts, the colour names Update Campaign Styles accepts, and the templates Create Campaign's visual mode can build from. Call it before those abilities so you use names and values this site actually accepts instead of guessing, which avoids a refused write. It reports the vocabulary only; it takes no campaign_id and reads no campaign, so it never changes anything. This IS the whole editable surface - if a thing is not named here, no ability on this site can change it, and inspecting the campaign's stored settings or post meta directly will not reveal an extra lever. Read a specific campaign with Get Campaign and Get Campaign Styles rather than reading its meta through WP-CLI or SQL. Charitable Lite's Campaign Builder does not support editing donation-form fields through an ability, which is why none are listed here — use the campaign's own edit screen for that.", 'charitable' ),
					'execute_callback'    => array( $this, 'execute_describe_editing_schema' ),
					'permission_callback' => array( __CLASS__, 'permission_campaigns' ),
					'annotations'         => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'input_schema'        => self::no_input_schema(),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'campaign_fields'    => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'style_colors'       => array( 'type' => 'object' ),
							'campaign_templates' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
						),
					),
				)
			);
		}

		/**
		 * Ability colour key => the two Lite storage keys it writes.
		 *
		 * TWO targets, because Lite has two live colour stores and different
		 * things read each: the Design panel reads
		 * layout.advanced.theme_color_*, while the front-end shortcode and the
		 * campaign endpoint read the top-level color_base_*. Writing one only
		 * produces an ability that reports success while the published page is
		 * unchanged. Pro's own create-campaign bridge writes both for the same
		 * reason.
		 *
		 * The KEY names are Pro's style-token names, deliberately. Lite's set is
		 * a strict SUBSET of Pro's reported tokens, so upgrading gains keys and
		 * loses none. `tertiary` is excluded even though Lite's panel stores it,
		 * because Pro has no such token and adding one here would make Lite a
		 * superset - divergence in the direction that breaks on upgrade.
		 *
		 * @since 1.8.13
		 *
		 * @return array<string,array{0:string,1:string}>
		 */
		private static function style_color_map() {
			return array(
				'primary'   => array( 'theme_color_primary', 'color_base_primary' ),
				'secondary' => array( 'theme_color_secondary', 'color_base_secondary' ),
				'button_bg' => array( 'theme_color_button', 'color_base_button' ),
			);
		}

		/**
		 * Read a campaign's colours, merged across both stores.
		 *
		 * layout.advanced wins where set, falling back to the top-level legacy
		 * key. Without the fallback a campaign coloured by the onboarding wizard
		 * and never opened in the builder reports no saved styles while
		 * rendering custom colours.
		 *
		 * @since 1.8.13
		 *
		 * @param  int $campaign_id The campaign.
		 * @return array<string,string> Ability key => '#'-prefixed hex, '' when unset.
		 */
		private static function read_campaign_colors( $campaign_id ) {
			$settings = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
			$settings = is_array( $settings ) ? $settings : array();
			$advanced = isset( $settings['layout']['advanced'] ) && is_array( $settings['layout']['advanced'] )
				? $settings['layout']['advanced']
				: array();

			$colors = array();

			foreach ( self::style_color_map() as $key => $targets ) {
				list( $layout_key, $base_key ) = $targets;

				$value = '';

				if ( isset( $advanced[ $layout_key ] ) && '' !== trim( (string) $advanced[ $layout_key ] ) ) {
					$value = (string) $advanced[ $layout_key ];
				} elseif ( isset( $settings[ $base_key ] ) && '' !== trim( (string) $settings[ $base_key ] ) ) {
					$value = (string) $settings[ $base_key ];
				}

				$colors[ $key ] = '' !== $value ? charitable_sanitize_hex( $value, true ) : '';
			}

			return $colors;
		}

		/**
		 * Write colours to both stores.
		 *
		 * @since 1.8.13
		 *
		 * @param  int                   $campaign_id The campaign.
		 * @param  array<string,string>  $colors      Ability key => hex, already validated.
		 * @return void
		 */
		private static function write_campaign_colors( $campaign_id, array $colors ) {
			$settings = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
			$settings = is_array( $settings ) ? $settings : array();

			if ( ! isset( $settings['layout'] ) || ! is_array( $settings['layout'] ) ) {
				$settings['layout'] = array();
			}

			if ( ! isset( $settings['layout']['advanced'] ) || ! is_array( $settings['layout']['advanced'] ) ) {
				$settings['layout']['advanced'] = array();
			}

			$map = self::style_color_map();

			foreach ( $colors as $key => $hex ) {
				if ( ! isset( $map[ $key ] ) ) {
					continue;
				}

				list( $layout_key, $base_key ) = $map[ $key ];

				$settings['layout']['advanced'][ $layout_key ] = $hex;
				$settings[ $base_key ]                          = $hex;
			}

			update_post_meta( $campaign_id, 'campaign_settings_v2', $settings );
		}

		/**
		 * Report a campaign's editable colours.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_campaign_styles( $input ) {
			$campaign_id = self::input_id( $input, 'campaign_id' );
			/*
			 * Tested as a BOOLEAN, which is what the callback returns.
			 *
			 * This was `if ( is_wp_error( $permitted ) )`, and
			 * permission_read_campaign() is declared `@return boolean` and
			 * returns true or false — so is_wp_error( false ) was always false
			 * and the branch could never be taken. The method looked as though
			 * it enforced permission and did not.
			 *
			 * Not exploitable over REST, because core's
			 * check_ability_permissions() runs the real permission callback
			 * before ever reaching this execute callback. It matters because a
			 * `public execute_*` is a reachable entry point for any internal
			 * caller, and this codebase already has one: see the note at
			 * input_id() about Charitable_Donor_Abilities::
			 * execute_get_donor_donations() calling
			 * Charitable_Donation_Abilities::execute_list_donations() directly.
			 * Guarded by
			 * test_the_style_methods_enforce_permission_on_a_direct_call().
			 */
			if ( ! self::permission_read_campaign( $input ) ) {
				return new WP_Error(
					'charitable_invalid_permissions',
					__( 'You do not have permission to view this campaign.', 'charitable' ),
					array( 'status' => 403 )
				);
			}

			if ( ! $campaign_id || 'campaign' !== get_post_type( $campaign_id ) ) {
				return new WP_Error(
					'charitable_campaign_not_found',
					__( 'That campaign could not be found.', 'charitable' )
				);
			}

			$colors = self::read_campaign_colors( $campaign_id );

			self::log_read( 'charitable/get-campaign-styles', array( 'campaign_id' => $campaign_id ) );

			return array(
				'campaign_id'      => $campaign_id,
				'colors'           => $colors,
				'has_saved_styles' => (bool) array_filter( $colors ),
				'editable_keys'    => array_keys( self::style_color_map() ),
			);
		}

		/**
		 * Change a campaign's colours.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_update_campaign_styles( $input ) {
			$campaign_id = self::input_id( $input, 'campaign_id' );
			/*
			 * Tested as a BOOLEAN — same dead-guard fix as
			 * execute_get_campaign_styles() above, and it needs its own line
			 * because it calls a DIFFERENT callback: permission_edit_campaign(),
			 * which additionally resolves `edit_others_campaigns` for a campaign
			 * the caller did not author. Fixing only the read method would have
			 * left this write open to a direct internal call.
			 */
			if ( ! self::permission_edit_campaign( $input ) ) {
				return new WP_Error(
					'charitable_invalid_permissions',
					__( 'You do not have permission to edit this campaign.', 'charitable' ),
					array( 'status' => 403 )
				);
			}

			if ( ! $campaign_id || 'campaign' !== get_post_type( $campaign_id ) ) {
				return new WP_Error(
					'charitable_campaign_not_found',
					__( 'That campaign could not be found.', 'charitable' )
				);
			}

			$input    = self::input_array( $input );
			$incoming = isset( $input['colors'] ) && is_array( $input['colors'] ) ? $input['colors'] : array();
			$map      = self::style_color_map();

			$accepted = array();
			$ignored  = array();

			foreach ( $incoming as $key => $value ) {
				if ( ! isset( $map[ $key ] ) ) {
					$ignored[] = (string) $key;

					continue;
				}

				/*
				 * The raw value's SHAPE is validated before it ever reaches
				 * charitable_sanitize_hex(), not after. That sanitiser (see
				 * includes/utilities/charitable-utility-functions.php) walks
				 * six character positions and falls back to the previous
				 * digit (or 'F') for anything non-hex, so it ALWAYS returns a
				 * well-formed six-digit hex string - even for garbage input -
				 * making its output indistinguishable from a deliberate
				 * colour. Confirmed directly: sanitizing
				 * 'url(javascript:alert(1))' returns '#FFFFFa', which itself
				 * matches a hex-shape check, so re-validating the SANITIZED
				 * value (as opposed to the raw one) can never catch anything
				 * and would silently store a colour the caller never asked
				 * for. Rejecting the raw shape first, before sanitizing,
				 * is what actually stops that.
				 */
				if ( ! is_scalar( $value ) ) {
					$ignored[] = (string) $key;

					continue;
				}

				$raw = trim( (string) $value );

				if ( ! preg_match( '/^#?[0-9A-Fa-f]{6}$|^#?[0-9A-Fa-f]{3}$/', $raw ) ) {
					$ignored[] = (string) $key;

					continue;
				}

				$hex = charitable_sanitize_hex( $raw, true );

				$accepted[ $key ] = $hex;
			}

			if ( ! empty( $accepted ) ) {
				self::write_campaign_colors( $campaign_id, $accepted );
			}

			self::log_write(
				'charitable/update-campaign-styles',
				sprintf(
					/* translators: %1$d campaign id, %2$s comma-separated colour names. */
					__( 'Updated colours on campaign %1$d: %2$s', 'charitable' ),
					$campaign_id,
					implode( ', ', array_keys( $accepted ) )
				),
				array( 'campaign_id' => $campaign_id )
			);

			return array(
				'campaign_id' => $campaign_id,
				'updated'     => array_keys( $accepted ),
				'ignored'     => array_values( array_unique( $ignored ) ),
				'colors'      => self::read_campaign_colors( $campaign_id ),
			);
		}

		/**
		 * Campaign field names charitable/update-campaign actually accepts.
		 *
		 * This IS the source, the same way style_color_map() is one for
		 * colours: an explicit list, not derived from anything else, because
		 * parse_named_fields() decides what happens to each field with its own
		 * per-field logic (money parsing, date parsing, sanitisation) rather
		 * than a loop this method could introspect. It mirrors that method's
		 * array_key_exists() checks and update-campaign's own input_schema
		 * property keys (everything but campaign_id) exactly. If a field is
		 * ever added to or removed from either of those, update this list in
		 * the same commit or execute_describe_editing_schema() will drift out
		 * of sync with what update-campaign actually does.
		 *
		 * @since 1.8.13
		 *
		 * @return string[]
		 */
		private static function writable_campaign_fields() {
			return array(
				'title',
				'goal',
				'end_date',
				'description',
				'suggested_amounts',
				'allow_custom_amount',
			);
		}

		/**
		 * Execute charitable/describe-editing-schema.
		 *
		 * NOT a port of Pro's output. Pro's version additionally reports
		 * donation-form field types (with each type's properties and, for the
		 * choice types, a required `options`) and the fixed typography/
		 * animation enums, because Pro's Campaign Builder Form panel and its
		 * style system both support those. Lite's Form panel is a stub —
		 * its panel_output() returns nothing — and the three donation-form
		 * abilities (get/update-campaign-donation-form,
		 * add-campaign-donation-form-field) are Pro-gated and never
		 * registered here (see Test_Charitable_Abilities_Contract::GATED), so
		 * advertising form-field vocabulary would tell an assistant it can
		 * build something this site will refuse. Reporting only what Lite
		 * genuinely supports is this ability's entire purpose: the one
		 * ability whose job is preventing false expectations must not be the
		 * source of them.
		 *
		 * Every value below is assembled from the SAME single source its
		 * matching read/write ability already uses, so what this advertises
		 * and what that ability actually does with the same names cannot
		 * drift apart:
		 * - campaign_fields: writable_campaign_fields(), the list
		 *   update-campaign's own input_schema and parse_named_fields() are
		 *   built from.
		 * - style_colors: style_color_map(), the exact set
		 *   update-campaign-styles accepts — anything else is reported back
		 *   in that ability's own `ignored` array rather than applied.
		 * - campaign_templates: execute_list_campaign_templates()'s own
		 *   template ids, not a second read of
		 *   Charitable_Campaign_Builder_Templates, so the two abilities can
		 *   never disagree about which templates exist.
		 *
		 * Reports vocabulary only. Takes no campaign_id and reads no
		 * campaign, which is why it is readonly and needs only the general
		 * campaigns capability rather than a per-campaign check.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input (unused; no-input ability).
		 * @return array
		 */
		public function execute_describe_editing_schema( $input = array() ) {
			$style_colors = array();

			foreach ( array_keys( self::style_color_map() ) as $key ) {
				$style_colors[ $key ] = sprintf(
					/* translators: %s: the colour key name, e.g. "primary", to pass inside Update Campaign Styles' colors object. */
					__( 'Six-digit hex value, for example #31714c. Pass as colors.%s to Update Campaign Styles.', 'charitable' ),
					$key
				);
			}

			$templates          = $this->execute_list_campaign_templates();
			$campaign_templates = isset( $templates['templates'] ) && is_array( $templates['templates'] )
				? wp_list_pluck( $templates['templates'], 'id' )
				: array();

			self::log_read( 'charitable/describe-editing-schema' );

			return array(
				'campaign_fields'    => self::writable_campaign_fields(),
				'style_colors'       => $style_colors,
				'campaign_templates' => array_values( $campaign_templates ),
			);
		}

		/**
		 * Permission callback for charitable/create-campaign.
		 *
		 * `publish_campaigns` for the two publish-tier statuses (`publish` and
		 * `private`); `edit_campaigns` for the two edit-tier statuses (the
		 * `draft` default, and `pending`) — see permission_publish_campaigns()'s
		 * docblock on the abstract registrar for why `edit_campaigns` alone is
		 * not sufficient once a site's roles no longer bundle the two
		 * capabilities together.
		 *
		 * `private` belongs on the publish-tier side, not the edit-tier side —
		 * fix round 3 correction: an earlier version of this docblock claimed
		 * core's own publish gate is "specific to the publish transition, not
		 * to every non-draft status," which is factually wrong and was
		 * corrected rather than left to mislead a future edit. WordPress core
		 * treats `publish` and `private` identically for this purpose:
		 * `WP_REST_Posts_Controller::handle_status_param()`'s `case 'private':`
		 * checks `current_user_can( $post_type->cap->publish_posts )`
		 * (class-wp-rest-posts-controller.php:1576), the exact same check its
		 * `case 'publish':` makes two lines later (:1586); and
		 * `_wp_translate_postdata()` (wp-admin/includes/post.php) downgrades a
		 * submitted `private` status to `pending` when the actor lacks
		 * `publish_posts`. `pending` staying on the edit-tier side is likewise
		 * correct and intentional: submitting for review is an edit-tier action
		 * in core too.
		 *
		 * Defaults to 'draft' the same way execute_create_campaign() does (via
		 * self::input_string( $input, 'status', 'draft' ) there, since 1.8.18's
		 * abilities-hardening pass), so an absent or non-scalar status resolves
		 * to the same tier in both places: 'draft' is edit-tier, which is what
		 * this method checks whenever $status is not strictly 'publish' or
		 * 'private'. A scalar-but-invalid status (e.g. "foobar") is likewise
		 * treated as edit-tier here, the lower bound, and is then refused by
		 * execute_create_campaign()'s own enum check before anything is
		 * written — so the permission check can grant a tier the write still
		 * declines to use, but never the reverse.
		 *
		 * Must tolerate $input being `array()` (or `null`) without a notice:
		 * WP_Ability::invoke_callback() always passes the ability's input
		 * through to a declared permission_callback when an input_schema is
		 * set (class-wp-ability.php), and the contract suite's
		 * test_no_ability_permits_a_subscriber() calls
		 * check_permissions( array() ) directly on every registered ability —
		 * bypassing normalize_input()/validate_input() entirely, so this can be
		 * invoked with no `status` key present at all. WP_UnitTestCase turns a
		 * PHP notice into a failing assertion, so an unguarded
		 * $input['status'] read here would break that generic test for every
		 * ability in this plugin, not just this one — using isset() avoids
		 * that entirely.
		 *
		 * @since 1.8.13
		 *
		 * @param  array|null $input Ability input.
		 * @return boolean
		 */
		public static function permission_create_campaign( $input = null ) {
			$status = isset( $input['status'] ) ? $input['status'] : 'draft';

			if ( in_array( $status, array( 'publish', 'private' ), true ) ) {
				return current_user_can( 'publish_campaigns' );
			}

			return current_user_can( 'edit_campaigns' );
		}

		/**
		 * Permission callback for charitable/trash-campaign.
		 *
		 * Input-aware for the same reason permission_create_campaign() is:
		 * WordPress's map_meta_cap() resolves the `delete_post` meta cap to
		 * `delete_published_campaigns` for a published post, and to the plain
		 * `delete_campaigns` primitive cap for a draft/pending one — trashing a
		 * live campaign requires a stronger capability than trashing one that
		 * was never published.
		 *
		 * `private` is publish-tier, not edit-tier, exactly like
		 * permission_create_campaign()'s `status` check: WordPress core treats
		 * `publish` and `private` identically for this purpose —
		 * `WP_REST_Posts_Controller::handle_status_param()`'s `case 'private':`
		 * checks `current_user_can( $post_type->cap->publish_posts )`
		 * (class-wp-rest-posts-controller.php:1576), the same check its
		 * `case 'publish':` makes two lines later. So a private campaign needs
		 * `delete_published_campaigns`, the same as a published one.
		 *
		 * Must tolerate $input being `array()` (or `null`) without a notice —
		 * see permission_create_campaign()'s docblock for the full explanation:
		 * the contract suite's test_no_ability_permits_a_subscriber() calls
		 * check_permissions( array() ) directly on every registered ability,
		 * bypassing normalize_input()/validate_input() entirely. An absent or
		 * unresolvable campaign_id is deliberately treated as the WEAKER
		 * delete_campaigns tier here, never as an error and never by reading
		 * $input['campaign_id'] unguarded — execute_trash_campaign() rejects a
		 * bad id separately with a clean WP_Error, so nothing unsafe is
		 * permitted by resolving to the lower tier at the permission-check
		 * stage.
		 *
		 * AUTHORSHIP CHECK added by the Audit 1 remediation. Everything above
		 * this point picks the right capability TIER for the post's status; it
		 * never asked whose post it is. A role holding delete_published_campaigns
		 * without delete_others_campaigns could therefore trash anybody's
		 * campaign through this ability — access wp-admin refuses, because
		 * core's map_meta_cap() resolves `delete_post` to
		 * `delete_others_campaigns` for a non-author. Confirmed by execution
		 * before the fix.
		 *
		 * @since 1.8.13
		 *
		 * @param  array|null $input Ability input.
		 * @return boolean
		 */
		public static function permission_trash_campaign( $input = null ) {
			$campaign_id = self::input_id( $input, 'campaign_id' );
			$post        = $campaign_id > 0 ? get_post( $campaign_id ) : null;

			/*
			 * Authorship first, tier second. The tier logic below picks the RIGHT
			 * capability for the post's status; it never asks whose post it is. A
			 * role holding delete_published_campaigns without
			 * delete_others_campaigns could therefore trash anybody's campaign
			 * through this ability, which wp-admin refuses — core's map_meta_cap()
			 * resolves `delete_post` to `delete_others_campaigns` for a non-author.
			 * Confirmed by execution before the fix. Caught by
			 * test_a_publisher_cannot_trash_someone_elses_campaign().
			 */
			if ( $post && 'campaign' === $post->post_type
				&& (int) $post->post_author !== get_current_user_id()
				&& ! current_user_can( 'delete_others_campaigns' ) ) {
				return false;
			}

			if ( $post && 'campaign' === $post->post_type && in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
				return current_user_can( 'delete_published_campaigns' );
			}

			return current_user_can( 'delete_campaigns' );
		}

		/**
		 * Execute charitable/list-campaigns.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array
		 */
		public function execute_list_campaigns( $input ) {
			/*
			 * Read through input_string(): a raw ! empty( $input['status'] )
			 * check is TRUE for an array or object value, and using either
			 * one as a post_status raises "Array to string conversion" (or
			 * worse, inside WP_Query's SQL building). Same reasoning for
			 * orderby.
			 */
			$status   = self::input_string( $input, 'status', 'any' );
			$search   = self::input_string( $input, 'search' );
			$per_page = self::input_int( $input, 'per_page', 20, 1, 100 );
			$page     = self::input_int( $input, 'page', 1, 1 );
			$orderby  = self::input_string( $input, 'orderby', 'date' );

			$status  = '' !== $status ? $status : 'any';
			$orderby = '' !== $orderby ? $orderby : 'date';

			$args = array(
				'post_status'    => $status,
				'posts_per_page' => $per_page,
				'paged'          => $page,
			);

			/*
			 * Author-scoped for anyone without `edit_others_campaigns`, which is
			 * what wp-admin's own campaign list shows such a user. This listing
			 * cannot use an object-level capability — it has no campaign_id to
			 * check — so the scope has to be applied to the QUERY instead.
			 *
			 * Without it, Charitable Ambassadors' `campaign_creator` role (granted
			 * automatically on front-end campaign signup, and holding exactly
			 * `read` + `edit_campaigns`) receives the id, goal, amount raised and
			 * donor count of every campaign on the site, plus the ids needed to
			 * address the other campaign abilities.
			 */
			if ( ! current_user_can( 'edit_others_campaigns' ) ) {
				$args['author'] = get_current_user_id();
			}

			if ( '' !== $search ) {
				$args['s'] = $search;
			}

			switch ( $orderby ) {
				case 'title':
					$args['orderby'] = 'title';
					$args['order']   = 'ASC';
					$query           = Charitable_Campaigns::query( $args );
					break;

				case 'amount':
					$query = Charitable_Campaigns::ordered_by_amount( $args );
					break;

				case 'goal':
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$args['meta_key'] = '_campaign_goal';
					$args['orderby']  = 'meta_value_num';
					$args['order']    = 'DESC';
					$query            = Charitable_Campaigns::query( $args );
					break;

				case 'date':
				default:
					$args['orderby'] = 'date';
					$args['order']   = 'DESC';
					$query           = Charitable_Campaigns::query( $args );
					break;
			}

			$campaign_ids = wp_list_pluck( $query->posts, 'ID' );
			$table        = charitable_get_table( 'campaign_donations' );
			$raised_by_id = $table->get_donated_amounts_by_campaign( $campaign_ids );
			$donors_by_id = $table->count_campaign_donors_by_campaign( $campaign_ids );

			$campaigns = array();

			foreach ( $query->posts as $post ) {
				$campaigns[] = $this->campaign_summary(
					charitable_get_campaign( $post ),
					isset( $raised_by_id[ $post->ID ] ) ? $raised_by_id[ $post->ID ] : 0,
					isset( $donors_by_id[ $post->ID ] ) ? $donors_by_id[ $post->ID ] : 0
				);
			}

			return array(
				'campaigns' => $campaigns,
				'total'     => (int) $query->found_posts,
				'pages'     => (int) $query->max_num_pages,
			);
		}

		/**
		 * Execute charitable/get-campaign.
		 *
		 * `has_goal` mirrors Charitable_Campaign::has_goal() exactly
		 * (`0 < (float) get_meta( '_campaign_goal' )`) so a stored goal of 0 —
		 * indistinguishable from "no goal was ever set" in Charitable's own
		 * model — reports the same way here: `goal` and `goal_formatted` come
		 * back null rather than a misleading `0`/`'$0.00'`. Fix round for Task
		 * 16 Step 7: a campaign that raised real money but has no goal used to
		 * report `goal: 0, percent_raised: 0`, which reads to an assistant as
		 * "raised $0 of a $0 goal" instead of "no goal set".
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_campaign( $input ) {
			$campaign = $this->find_campaign( self::input_id( $input, 'campaign_id' ) );

			if ( is_wp_error( $campaign ) ) {
				return self::scrub_error( $campaign );
			}

			$has_goal = $campaign->has_goal();
			$goal     = $has_goal ? self::money( $campaign->get_meta( '_campaign_goal' ) ) : null;
			$min      = self::money( $campaign->get_min_donation_amount() );
			$end      = $campaign->get_end_date( 'Y-m-d H:i:s' );

			return array(
				'id'                                => $campaign->get_campaign_id(),
				'title'                             => (string) $campaign->post_title,
				'status'                            => (string) $campaign->post_status,
				'url'                               => (string) $campaign->get_permalink(),
				'description'                       => $this->raw_description( $campaign ),
				'has_goal'                          => $has_goal,
				'goal'                              => $has_goal ? $goal['raw'] : null,
				'goal_formatted'                    => $has_goal ? $goal['formatted'] : null,
				'is_endless'                        => (bool) $campaign->is_endless(),
				'end_date'                          => false === $end ? null : $end,
				'has_ended'                         => (bool) $campaign->has_ended(),
				'allow_custom_donations'            => (bool) $campaign->get_allow_custom_donations(),
				'minimum_donation_amount'           => $min['raw'],
				'minimum_donation_amount_formatted' => $min['formatted'],
				'suggested_donations'               => $this->suggested_donations( $campaign ),
			);
		}

		/**
		 * Execute charitable/get-campaign-performance.
		 *
		 * `percent_raised` had the exact same "no goal reads as a goal of zero"
		 * defect Task 16 Step 7 fixed on list-campaigns and get-campaign:
		 * percent_raised() below delegates to
		 * Charitable_Campaign::get_percent_donated_raw(), which returns `false`
		 * (not 0) specifically when the campaign has no goal — a distinction
		 * this method used to collapse into `0.0`, indistinguishable from a
		 * campaign that genuinely has a goal and has raised nothing toward it.
		 * `has_goal` is exposed here for the same reason it is on the other two
		 * abilities: so a client can tell "no goal" from "0% of a real goal"
		 * without having to separately call get-campaign to find out.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_get_campaign_performance( $input ) {
			$campaign = $this->find_campaign( self::input_id( $input, 'campaign_id' ) );

			if ( is_wp_error( $campaign ) ) {
				return self::scrub_error( $campaign );
			}

			$raised         = self::money( $campaign->get_donated_amount( true ) );
			$donation_count = (int) $campaign->get_donation_count();
			$average        = $donation_count > 0 ? ( $raised['raw'] / $donation_count ) : 0;
			$average_money  = self::money( $average );

			return array(
				'campaign_id'            => $campaign->get_campaign_id(),
				'raised'                 => $raised['raw'],
				'raised_formatted'       => $raised['formatted'],
				'has_goal'               => $campaign->has_goal(),
				'percent_raised'         => $this->percent_raised( $campaign ),
				'donation_count'         => $donation_count,
				'donor_count'            => (int) $campaign->get_donor_count(),
				'average_gift'           => $average_money['raw'],
				'average_gift_formatted' => $average_money['formatted'],
			);
		}

		/**
		 * Execute charitable/list-campaign-templates.
		 *
		 * Charitable_Campaign_Builder_Templates is deliberately NOT in
		 * charitable-class-map.php — every call site in this codebase
		 * (ajax-actions.php, class-charitable-request.php,
		 * class-social-sharing.php, panels/class-design.php, etc.) loads it with
		 * an explicit require_once immediately before `new`, so this does the
		 * same rather than assuming the autoloader will find it.
		 *
		 * Charitable_Campaign_Builder_Templates::get_instance() is also not used:
		 * it instantiates the WRONG class (`new Charitable_Campaign_Builder()`, a
		 * pre-existing bug in that method unrelated to this ability), and every
		 * real call site bypasses it with a direct `new` for exactly that reason.
		 *
		 * get_templates_data() (not the lower-level get_source_template_data())
		 * is used so any addon-contributed templates registered through the
		 * `charitable_campaign_builder_template_data` filter are included — a
		 * plain get_source_template_data() call would silently omit them. The
		 * heavier per-template preview/field_options/tab_options HTML that same
		 * method computes is only added when is_admin() is true, which a
		 * REST/MCP-driven ability call never is, so this stays cheap. The method
		 * caches its result in the `charitable_campaign_builder_templates`
		 * option on a cache miss — a pre-existing, expected side effect of this
		 * call shared with every other caller, not something this ability adds.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		public function execute_list_campaign_templates() {
			if ( ! class_exists( 'Charitable_Campaign_Builder_Templates' ) ) {
				require_once charitable()->get_path( 'includes' ) . 'admin/campaign-builder/templates/class-templates.php';
			}

			$data      = ( new Charitable_Campaign_Builder_Templates() )->get_templates_data();
			$templates = isset( $data['templates'] ) && is_array( $data['templates'] ) ? $data['templates'] : array();

			$rows = array();

			foreach ( $templates as $template_id => $template ) {
				$meta = isset( $template['meta'] ) && is_array( $template['meta'] ) ? $template['meta'] : array();

				$rows[] = array(
					'id'          => (string) $template_id,
					'label'       => isset( $meta['label'] ) ? (string) $meta['label'] : '',
					'description' => isset( $meta['description'] ) ? (string) $meta['description'] : '',
					'categories'  => isset( $meta['categories'] ) && is_array( $meta['categories'] ) ? array_values( $meta['categories'] ) : array(),
				);
			}

			return array( 'templates' => $rows );
		}

		/**
		 * Execute charitable/create-campaign.
		 *
		 * Validates and parses every supplied field via parse_named_fields() BEFORE
		 * creating any post — a rejected goal/end_date/suggested_amounts leaves
		 * nothing behind, rather than an orphaned draft campaign with half its
		 * fields applied. custom donations default to enabled when the caller
		 * doesn't specify allow_custom_amount: a brand-new campaign with no
		 * suggested amounts AND custom donations off would have no way to accept
		 * a donation of any size at all, so this ability makes the same choice
		 * Charitable_Campaign_Processor::get_default_args() makes for the same
		 * reason. `status` defaults to draft (never applied via
		 * parse/write_named_fields, which know nothing about status or template)
		 * so an AI cannot accidentally publish a campaign by omission.
		 *
		 * Task 17: `mode` (`legacy|visual`, default `legacy` — no behavior
		 * change for a caller that omits it) and `template` are back in the
		 * schema. History for why `template` was removed and is now returning
		 * narrowly, rather than simply un-reverting fix round 1: a stakeholder
		 * demo showed an AI-created campaign opening in the classic post
		 * editor instead of the Campaign Builder, which undercuts an
		 * AI-built-my-fundraiser story even though the campaign itself was
		 * otherwise correct. `mode: visual` now has a real, narrow write path —
		 * write_visual_campaign_settings() below — that fix round 1 correctly
		 * judged did not exist yet for a bare `template` field with no mode
		 * switch. The validation this method applies before creating anything:
		 * `template` is REJECTED (not silently dropped) when `mode` is
		 * `legacy` — accepting-and-ignoring an input inside a success response
		 * is the exact defect fix round 1 already paid for once, on this same
		 * field — and `template` is REQUIRED, and validated against the real
		 * template registry via validate_template(), when `mode` is `visual`.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_create_campaign( $input ) {
			/*
			 * Normalised first because this method reads $input with
			 * array_key_exists(), which THROWS a TypeError on a non-array rather
			 * than merely warning. Reachable: execute_* methods must be public.
			 */
			$input = self::input_array( $input );

			$mode = self::input_string( $input, 'mode', 'legacy' );
			$mode = '' !== $mode ? $mode : 'legacy';

			if ( 'legacy' === $mode && array_key_exists( 'template', $input ) ) {
				$error = new WP_Error(
					'charitable_template_requires_visual_mode',
					__( 'template can only be used with mode: visual. A legacy campaign has no Campaign Builder template to apply — omit template, or set mode to visual.', 'charitable' )
				);

				self::log_failure( 'charitable/create-campaign', $error );
				return self::scrub_error( $error );
			}

			if ( 'legacy' === $mode && array_key_exists( 'image_url', $input ) ) {
				$error = new WP_Error(
					'charitable_image_requires_visual_mode',
					__( 'image_url can only be used with mode: visual. A legacy campaign has no Campaign Builder photo field to place an image in — omit image_url, or set mode to visual.', 'charitable' )
				);

				self::log_failure( 'charitable/create-campaign', $error );
				return self::scrub_error( $error );
			}

			$template = '';

			if ( 'visual' === $mode ) {
				$template = isset( $input['template'] ) ? (string) $input['template'] : '';

				if ( '' === $template ) {
					$error = new WP_Error(
						'charitable_template_required',
						__( 'template is required when mode is visual. Use List Campaign Templates to see the available options.', 'charitable' )
					);

					self::log_failure( 'charitable/create-campaign', $error );
					return self::scrub_error( $error );
				}

				$valid_template = $this->validate_template( $template );

				if ( is_wp_error( $valid_template ) ) {
					self::log_failure( 'charitable/create-campaign', $valid_template );
					return self::scrub_error( $valid_template );
				}
			}

			$parsed = $this->parse_named_fields( $input );

			if ( is_wp_error( $parsed ) ) {
				self::log_failure( 'charitable/create-campaign', $parsed );
				return self::scrub_error( $parsed );
			}

			if ( ! array_key_exists( 'allow_custom_amount', $parsed ) ) {
				$parsed['allow_custom_amount'] = 1;
			}

			/*
			 * Item 52/53 fix round: seed a default suggested-amount ladder for
			 * visual mode when the caller supplied none.
			 *
			 * The defect this closes, verified on a real club-organization
			 * campaign built through this ability: `mode: visual` describes
			 * itself as producing "a campaign with real content and colors
			 * already in place ... what an AI-built fundraiser should use".
			 * The colors did land. The amounts did not —
			 * _campaign_suggested_donations was unset, no amount key existed
			 * anywhere in campaign_settings_v2, the visual form's
			 * suggested-donation-amounts field carries no amounts of its own
			 * (it reads them off the campaign), and the RENDERED form
			 * therefore contained zero preset amount inputs: nothing but the
			 * custom-amount box. A donor had to type a number into an empty
			 * field on a live fundraiser, and no response anywhere reported a
			 * problem.
			 *
			 * Gated on $input, not $parsed, and with array_key_exists() rather
			 * than isset()/empty(). That is what keeps an explicitly supplied
			 * `suggested_amounts: []` — a deliberate "no preset amounts" —
			 * honoured instead of being overwritten, since
			 * parse_named_fields() also keys off array_key_exists() and so
			 * yields $parsed['suggested_amounts'] === array() for that case,
			 * indistinguishable from an omission by any emptiness test.
			 *
			 * Scoped to visual mode only. `legacy` — the default, and what
			 * every caller that omits `mode` gets — advertises "a bare
			 * campaign"; bare is its contract, and seeding there would be a
			 * silent behavior change for existing callers.
			 *
			 * Routed through the SAME parse_suggested_amounts() a caller's own
			 * array goes through, so the seeded rows cannot drift out of the
			 * stored shape if that parser ever changes. It cannot fail on a
			 * hardcoded list of positive integers; the guard is there so a
			 * hypothetical future parser change degrades to "no seed" rather
			 * than writing a WP_Error into post meta.
			 */
			if ( 'visual' === $mode && ! array_key_exists( 'suggested_amounts', $input ) ) {
				/*
				 * The goal is read from $parsed, NOT from $input and NOT from
				 * post meta, and that is the whole defence against the locale
				 * trap. _campaign_goal can be STORED as "1,000.00", and a raw
				 * floatval() on that reads 1 — which would drop a $1,000
				 * campaign into the smallest band. $parsed['goal'] has already
				 * been through self::parse_money(), i.e.
				 * Charitable_Currency::sanitize_monetary_amount( $v, true ),
				 * whose final step is
				 * floatval( filter_var( ..., FILTER_SANITIZE_NUMBER_FLOAT ) ) —
				 * that filter strips the thousands comma, so "1,000.00"
				 * arrives here as 1000.0 (verified directly, not assumed).
				 *
				 * It is also the SAME float write_named_fields() is about to
				 * store as _campaign_goal, so the band can never disagree with
				 * the goal the campaign ends up having. Re-reading the meta
				 * afterwards would reintroduce exactly the string this avoids.
				 *
				 * Absent when the caller supplied no goal, since
				 * parse_named_fields() only produces keys present in $input —
				 * which is the no-goal case, and gets the smallest ladder.
				 */
				$goal = array_key_exists( 'goal', $parsed ) ? (float) $parsed['goal'] : 0.0;

				$seeded = $this->parse_suggested_amounts( self::default_suggested_amount_ladder( $goal ) );

				if ( ! is_wp_error( $seeded ) ) {
					$parsed['suggested_amounts'] = $seeded;
				}
			}

			if ( ! array_key_exists( 'end_date', $parsed ) ) {
				/*
				 * Task 17 fix round, Finding 1 (Critical), verified live on zeta
				 * campaign 1324. Charitable's OWN storage convention for "no end
				 * date" is the string '0' — not '' and not an absent meta key.
				 * A real Campaign Builder save always writes an explicit `0`
				 * base-meta default for end_date (ajax-actions.php's
				 * `$base_meta_keys['end_date'] = 0` block) even when the caller
				 * never touched the field; write_named_fields() below, by
				 * contrast, only calls update_post_meta() for keys present in
				 * $parsed, so leaving end_date unset here means the meta is
				 * either never written (reads back as '' — WordPress's
				 * documented behavior for a missing single meta value) or, if
				 * ever written directly, could be '' — either way, the wrong
				 * value.
				 *
				 * Why this matters: Charitable_Campaign::is_endless() is
				 * `0 == $this->get_meta( '_campaign_end_date' )`. Under PHP 8,
				 * `0 == '0'` is TRUE (endless — correct) but `0 == ''` is FALSE,
				 * because PHP 8 changed int-vs-non-numeric-string comparison
				 * semantics. So a campaign that follows the product's own
				 * convention behaves correctly, and one that doesn't reports
				 * has_ended() === true / can_receive_donations() === false — a
				 * donate button that reads "closed" on a brand-new campaign.
				 *
				 * DO NOT "simplify" this to '' — that is the bug this fix
				 * removes. See Charitable_Campaign::is_endless()
				 * (includes/campaigns/class-charitable-campaign.php:416-418),
				 * which is intentionally NOT being touched here: its loose
				 * `0 ==` comparison is a separate, pre-existing, out-of-scope
				 * defect (four campaigns on zeta already store '' from some
				 * other source and are broken independently of this ability).
				 * Matching the convention here fixes every campaign this
				 * ability creates without changing that comparison at all.
				 *
				 * Applies to BOTH legacy and visual mode — this runs before the
				 * mode branch below and $parsed flows into write_named_fields()
				 * (always) and write_visual_campaign_settings() (visual only)
				 * identically.
				 */
				$parsed['end_date'] = '0';
			}

			/*
			 * Read through input_string() and validated here, not taken
			 * straight off $input.
			 *
			 * A raw array or object status flows unguarded into
			 * wp_insert_post()'s post_status and would raise "Array to string
			 * conversion" or throw — currently masked, when title is empty,
			 * by wp_insert_post() itself returning WP_Error( 'empty_content' )
			 * before touching the database. That is an accident of a missing
			 * title, not a guard; supply one and the unguarded read would
			 * reach wpdb. input_string() falls back to the 'draft' default
			 * for both an absent key and a non-scalar one, matching this
			 * ability's own description ("Creates it as a draft unless
			 * status is given").
			 *
			 * Both the input_schema `status` enum above and this check read
			 * self::writable_campaign_statuses(), so they cannot drift apart.
			 */
			$status = self::input_string( $input, 'status', 'draft' );

			if ( ! in_array( $status, self::writable_campaign_statuses(), true ) ) {
				$error = new WP_Error(
					'charitable_invalid_status',
					__( 'Supply a status of draft, publish, pending or private.', 'charitable' )
				);

				self::log_failure( 'charitable/create-campaign', $error );
				return self::scrub_error( $error );
			}

			$title = isset( $parsed['title'] ) ? $parsed['title'] : '';

			unset( $parsed['title'] );

			$campaign_id = wp_insert_post(
				array(
					'post_type'   => 'campaign',
					'post_title'  => $title,
					'post_status' => $status,
				),
				true
			);

			if ( is_wp_error( $campaign_id ) ) {
				self::log_failure( 'charitable/create-campaign', $campaign_id );
				return self::scrub_error( $campaign_id );
			}

			$written = $this->write_named_fields( $campaign_id, $parsed );

			if ( is_wp_error( $written ) ) {
				self::log_failure( 'charitable/create-campaign', $written );
				return self::scrub_error( $written );
			}

			$image_result = array( 'attached' => false, 'error' => null );

			if ( 'visual' === $mode ) {
				$image_url    = isset( $input['image_url'] ) ? (string) $input['image_url'] : '';
				$image_result = $this->write_visual_campaign_settings( $campaign_id, $template, $title, $parsed, $status, $image_url );
			}

			self::log_write(
				'charitable/create-campaign',
				__( 'Campaign created via ability.', 'charitable' ),
				array( 'campaign_id' => $campaign_id )
			);

			$campaign = charitable_get_campaign( $campaign_id );

			$response = array(
				'campaign_id'    => $campaign_id,
				'status'         => (string) get_post_status( $campaign_id ),
				'url'            => (string) $campaign->get_permalink(),
				'image_attached' => (bool) $image_result['attached'],
			);

			if ( ! empty( $image_result['error'] ) ) {
				$response['image_error'] = (string) $image_result['error'];
			}

			return $response;
		}

		/**
		 * Execute charitable/update-campaign.
		 *
		 * Partial by construction: parse_named_fields() only produces entries for
		 * keys actually present in $input (checked with array_key_exists(), not
		 * isset() or empty(), so an explicit `false`/`0`/`""` still counts as
		 * supplied), and write_named_fields() only ever touches the meta/post
		 * fields corresponding to those entries. A field never mentioned in
		 * $input is never read back, never re-written, and never defaulted — it
		 * simply isn't visited. `status` and `template` are not in this ability's
		 * input schema at all (see the class docblock note below on why), so
		 * publish status can never change here regardless of what a caller sends.
		 *
		 * All parsing happens before any write, so a rejected goal/end_date/
		 * suggested_amounts on a multi-field call leaves the existing campaign
		 * completely unchanged rather than partially applied.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_update_campaign( $input ) {
			$campaign_id = self::input_id( $input, 'campaign_id' );
			$campaign    = $this->find_campaign( $campaign_id );

			if ( is_wp_error( $campaign ) ) {
				self::log_failure( 'charitable/update-campaign', $campaign );
				return self::scrub_error( $campaign );
			}

			$fields = $input;
			unset( $fields['campaign_id'] );

			$parsed = $this->parse_named_fields( $fields, $campaign_id );

			if ( is_wp_error( $parsed ) ) {
				self::log_failure( 'charitable/update-campaign', $parsed );
				return self::scrub_error( $parsed );
			}

			$written = $this->write_named_fields( $campaign_id, $parsed );

			if ( is_wp_error( $written ) ) {
				self::log_failure( 'charitable/update-campaign', $written );
				return self::scrub_error( $written );
			}

			self::log_write(
				'charitable/update-campaign',
				sprintf(
					/* translators: %s: comma-separated list of updated field names, or a fixed placeholder when none were supplied. */
					__( 'Campaign updated via ability. Fields: %s', 'charitable' ),
					$written ? implode( ', ', $written ) : __( '(none supplied)', 'charitable' )
				),
				array( 'campaign_id' => $campaign_id )
			);

			return array(
				'campaign_id'    => $campaign_id,
				'updated_fields' => $written,
			);
		}

		/**
		 * Execute charitable/set-campaign-status.
		 *
		 * The only ability that changes a campaign's post_status — deliberately
		 * separate from create-campaign (which defaults new campaigns to draft)
		 * and update-campaign (which excludes status from its schema entirely) so
		 * publishing is always an explicit, single-purpose call.
		 *
		 * Since 1.8.13 it also refuses to take a LIVE campaign that has donations
		 * offline without confirm_has_donations, mirroring
		 * charitable/trash-campaign. See the guard below for why only that one
		 * direction is gated.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_set_campaign_status( $input ) {
			$campaign_id = self::input_id( $input, 'campaign_id' );
			$campaign    = $this->find_campaign( $campaign_id );

			if ( is_wp_error( $campaign ) ) {
				self::log_failure( 'charitable/set-campaign-status', $campaign );
				return self::scrub_error( $campaign );
			}

			/*
			 * Read through input_string() and validated here, not taken straight
			 * off $input.
			 *
			 * `status` is a required property, so core refuses a call without it
			 * before this method runs — but every execute_* method is public and
			 * therefore reachable by an internal caller that bypasses core's
			 * validator. Taken raw, a missing key emitted one "Undefined array
			 * key" warning and then handed null to wp_update_post(), which set a
			 * PUBLISHED campaign to draft and returned {"status":"draft"} as a
			 * success. A silent unpublish is not an acceptable answer to a
			 * missing key. Caught by
			 * test_set_campaign_status_with_no_status_refuses_rather_than_unpublishing().
			 *
			 * Both the input_schema `status` enum above and this check read
			 * self::writable_campaign_statuses(), so they cannot drift apart.
			 */
			$status = self::input_string( $input, 'status' );

			if ( ! in_array( $status, self::writable_campaign_statuses(), true ) ) {
				$error = new WP_Error(
					'charitable_invalid_status',
					__( 'Supply a status of publish, draft, pending or private.', 'charitable' )
				);

				self::log_failure( 'charitable/set-campaign-status', $error );

				return self::scrub_error( $error );
			}

			/*
			 * TAKING A LIVE, DONATION-RECEIVING CAMPAIGN OFFLINE needs the same
			 * explicit confirmation charitable/trash-campaign needs, and for the
			 * same reason: unpublishing stops the campaign accepting donations
			 * the moment it happens, which is as disruptive in the moment as
			 * trashing it. An AI acting on a vague instruction ("tidy up the old
			 * campaigns") should not be able to close off real fundraising
			 * without saying so.
			 *
			 * SCOPED TO THE OFFLINE DIRECTION ONLY, and the asymmetry is the
			 * point:
			 *
			 * - Guarded: currently `publish`, requested anything else. That and
			 *   only that interrupts fundraising.
			 * - NOT guarded: any change TO `publish`. Publishing is not
			 *   destructive, and gating it would make launching a campaign
			 *   harder for no safety gained.
			 * - NOT guarded: a campaign that is not currently published. A draft
			 *   moving to pending was never taking donations, so there is
			 *   nothing to interrupt - and `publish` to `publish` is likewise
			 *   untouched, which keeps the `idempotent: true` annotation honest
			 *   for a client retrying after a timeout.
			 *
			 * Read from $campaign->post_status, captured BEFORE wp_update_post()
			 * below - the guarded operation is the thing that changes the value
			 * the guard reads, so a check made afterwards would always see the
			 * new status and never fire.
			 *
			 * @since 1.8.13
			 */
			$previous_status = (string) $campaign->post_status;
			$going_offline   = 'publish' === $previous_status && 'publish' !== $status;

			if ( $going_offline ) {
				$donation_count = (int) $campaign->get_donation_count();
				$confirmed      = ! empty( $input['confirm_has_donations'] );

				if ( $donation_count > 0 && ! $confirmed ) {
					$error = new WP_Error(
						'charitable_campaign_has_donations',
						sprintf(
							/* translators: %d: number of donations already recorded against the campaign. */
							__( 'This campaign is live and has %d donation(s) recorded against it. Taking it offline stops it accepting donations immediately. Set confirm_has_donations to true to change its status anyway.', 'charitable' ),
							$donation_count
						)
					);

					self::log_failure( 'charitable/set-campaign-status', $error );
					return self::scrub_error( $error );
				}
			}

			$updated = wp_update_post(
				array(
					'ID'          => $campaign_id,
					'post_status' => $status,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				self::log_failure( 'charitable/set-campaign-status', $updated );
				return self::scrub_error( $updated );
			}

			self::log_write(
				'charitable/set-campaign-status',
				sprintf(
					/* translators: %s: new campaign post status. */
					__( 'Campaign status set to %s via ability.', 'charitable' ),
					$status
				),
				array( 'campaign_id' => $campaign_id )
			);

			return array(
				'campaign_id' => $campaign_id,
				'status'      => (string) get_post_status( $campaign_id ),
			);
		}

		/**
		 * Execute charitable/trash-campaign.
		 *
		 * Refuses to trash a campaign with one or more recorded donations unless
		 * the caller explicitly sets confirm_has_donations — trashing hides the
		 * campaign (and, with it, the fundraising history attached to it) from
		 * every normal admin view without deleting anything, but an AI acting on
		 * a vague instruction ("clean up old campaigns") should not be able to
		 * do that to a campaign that actually raised money without saying so
		 * explicitly first.
		 *
		 * No permanent-delete path exists anywhere in this ability, deliberately:
		 * wp_charitable_campaign_donations.campaign_id is a NOT NULL indexed
		 * column referencing the campaign post, so a hard delete would orphan
		 * donation rows and corrupt fundraising history. Trash is reversible
		 * (see execute_restore_campaign() below) and is therefore the only
		 * "undo" this domain needs or should ever get.
		 *
		 * Fix round 1, Finding 1: wp_trash_post() itself calls
		 * wp_delete_post( $post_id, true ) — a PERMANENT delete — whenever
		 * EMPTY_TRASH_DAYS is falsy (wp-includes/post.php), which directly
		 * violates the no-permanent-delete constraint above. A round 0 version
		 * of this method only checked the resulting post status AFTER calling
		 * wp_trash_post(), which correctly turned a false "success" into a
		 * WP_Error but could not undo the delete that had already happened by
		 * the time it ran — detection cannot undo deletion. EMPTY_TRASH_DAYS is
		 * now checked BEFORE wp_trash_post() is ever called, and the request is
		 * refused outright if trash is disabled. Failing closed here costs
		 * nothing: a site with trash disabled has made this ability
		 * structurally incapable of honouring its own contract.
		 *
		 * Fix round 1, Finding 2 (idempotency): wp_trash_post() short-circuits
		 * with `return false` for a post already in the trash
		 * (wp-includes/post.php), which — with no guard — fell into the
		 * generic failure branch below and reported an error for an operation
		 * that had, in fact, already succeeded. This ability is annotated
		 * `idempotent: true`; an agent retrying after a timeout, or
		 * re-confirming its own earlier action, must see success twice, not
		 * success then failure. A campaign already in the trash is therefore
		 * detected up front and returned as a successful no-op, bypassing the
		 * donation-count guard entirely — nothing new is being trashed, so
		 * there is nothing left to confirm.
		 *
		 * Fix round 1, Finding 3: the remaining failure paths below are no
		 * longer collapsed into one error code. A caller cannot afford to
		 * confuse "nothing happened" (charitable_trash_declined) with
		 * "wp_trash_post() reported success but the campaign isn't actually in
		 * the trash, so something unexpected may have happened to it"
		 * (charitable_trash_unexpected_result) — the second is exactly the
		 * ambiguity a post-hoc-only check could never resolve, which is why the
		 * pre-flight EMPTY_TRASH_DAYS check above exists as the primary
		 * defense and this remains only a backstop for the genuinely
		 * unexpected (e.g. a `pre_trash_post` filter veto, or some other
		 * plugin's hook altering the post underneath this call).
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_trash_campaign( $input ) {
			$campaign_id = self::input_id( $input, 'campaign_id' );
			$campaign    = $this->find_campaign( $campaign_id );

			if ( is_wp_error( $campaign ) ) {
				self::log_failure( 'charitable/trash-campaign', $campaign );
				return self::scrub_error( $campaign );
			}

			$donation_count = (int) $campaign->get_donation_count();

			if ( 'trash' === $campaign->post_status ) {
				self::log_write(
					'charitable/trash-campaign',
					__( 'Campaign was already in the trash; treated as a successful no-op.', 'charitable' ),
					array( 'campaign_id' => $campaign_id )
				);

				return array(
					'campaign_id'     => $campaign_id,
					'previous_status' => 'trash',
					'trashed'         => true,
					'donation_count'  => $donation_count,
				);
			}

			$confirmed = ! empty( $input['confirm_has_donations'] );

			if ( $donation_count > 0 && ! $confirmed ) {
				$error = new WP_Error(
					'charitable_campaign_has_donations',
					sprintf(
						/* translators: %d: number of donations already recorded against the campaign. */
						__( 'This campaign has %d donation(s) recorded against it. Set confirm_has_donations to true to trash it anyway.', 'charitable' ),
						$donation_count
					)
				);

				self::log_failure( 'charitable/trash-campaign', $error );
				return self::scrub_error( $error );
			}

			if ( ! EMPTY_TRASH_DAYS ) {
				$error = new WP_Error(
					'charitable_trash_disabled',
					__( 'This site defines EMPTY_TRASH_DAYS as 0, which disables the trash entirely. Trashing this campaign would permanently delete it and orphan its donation records instead of moving it to the trash, so the request was refused before making any change.', 'charitable' )
				);

				self::log_failure( 'charitable/trash-campaign', $error );
				return self::scrub_error( $error );
			}

			$previous_status = (string) $campaign->post_status;
			$trashed         = wp_trash_post( $campaign_id );

			if ( ! $trashed ) {
				$error = new WP_Error(
					'charitable_trash_declined',
					__( 'The campaign was not moved to the trash. Nothing was changed — another plugin may have vetoed the request.', 'charitable' )
				);

				self::log_failure( 'charitable/trash-campaign', $error );
				return self::scrub_error( $error );
			}

			if ( 'trash' !== get_post_status( $campaign_id ) ) {
				$error = new WP_Error(
					'charitable_trash_unexpected_result',
					__( 'Trashing reported success, but the campaign is not in the trash afterwards. It may have been altered unexpectedly — check it manually before assuming it is safe.', 'charitable' )
				);

				self::log_failure( 'charitable/trash-campaign', $error );
				return self::scrub_error( $error );
			}

			self::log_write(
				'charitable/trash-campaign',
				__( 'Campaign trashed via ability.', 'charitable' ),
				array( 'campaign_id' => $campaign_id )
			);

			return array(
				'campaign_id'     => $campaign_id,
				'previous_status' => $previous_status,
				'trashed'         => true,
				'donation_count'  => $donation_count,
			);
		}

		/**
		 * Execute charitable/restore-campaign.
		 *
		 * Rejects any campaign not currently in the 'trash' status with a clean
		 * WP_Error rather than calling wp_untrash_post() on it — that function
		 * already no-ops (returns false) for a non-trashed post, but a bare
		 * false is not a usable error for an AI caller, so this checks status
		 * first and reports why.
		 *
		 * Does NOT republish: wp_untrash_post() restores a non-attachment post
		 * to 'draft' by default (the `wp_untrash_post_status` filter can change
		 * that, but nothing in this codebase hooks it), never back to whatever
		 * status it held before being trashed. That is asserted here, not
		 * assumed — see the "must not republish" test in the campaigns test
		 * file.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input Ability input.
		 * @return array|WP_Error
		 */
		public function execute_restore_campaign( $input ) {
			$campaign_id = self::input_id( $input, 'campaign_id' );
			$campaign    = $this->find_campaign( $campaign_id );

			if ( is_wp_error( $campaign ) ) {
				self::log_failure( 'charitable/restore-campaign', $campaign );
				return self::scrub_error( $campaign );
			}

			if ( 'trash' !== $campaign->post_status ) {
				$error = new WP_Error(
					'charitable_campaign_not_trashed',
					__( 'This campaign is not in the trash, so it cannot be restored.', 'charitable' )
				);

				self::log_failure( 'charitable/restore-campaign', $error );
				return self::scrub_error( $error );
			}

			$restored = wp_untrash_post( $campaign_id );

			if ( ! $restored || 'trash' === get_post_status( $campaign_id ) ) {
				$error = new WP_Error(
					'charitable_restore_failed',
					__( 'The campaign could not be restored from the trash.', 'charitable' )
				);

				self::log_failure( 'charitable/restore-campaign', $error );
				return self::scrub_error( $error );
			}

			$status = (string) get_post_status( $campaign_id );

			self::log_write(
				'charitable/restore-campaign',
				__( 'Campaign restored from trash via ability.', 'charitable' ),
				array( 'campaign_id' => $campaign_id )
			);

			return array(
				'campaign_id' => $campaign_id,
				'status'      => $status,
				'restored'    => true,
			);
		}

		/**
		 * Build the `list-campaigns` row shape for one campaign.
		 *
		 * $raised_amount and $donor_count are pre-fetched by execute_list_campaigns()
		 * via one grouped query each for the whole page, rather than looked up here
		 * per campaign — see get_donated_amounts_by_campaign() and
		 * count_campaign_donors_by_campaign() on Charitable_Campaign_Donations_DB.
		 * Calling Charitable_Campaign::get_donated_amount()/get_donor_count() once
		 * per row here would put this method back on the N+1 path (donor count has
		 * no caching layer at all, and get_donated_amount() re-reads its transient
		 * on every call with no in-request memoization) that a 100-row page would
		 * turn into roughly 200 queries warm, ~400 cold.
		 *
		 * The two grouped queries only resolve the RAW aggregate — they cannot run
		 * the `charitable_campaign_donated_amount` / `charitable_campaign_donor_count`
		 * filters themselves (those are per-campaign hooks, not expressible in a
		 * GROUP BY). Both are live in this codebase — Charitable_WPML_Compat and
		 * Charitable_Polylang_Compat auto-hook them at priority 5 to roll a
		 * campaign's translation siblings' totals together, the Ambassadors addon
		 * hooks them to roll parent + child team-campaign totals together, and
		 * Charitable's own published snippet library
		 * (library/campaigns/change-amount-donated.php, change-donor-count.php)
		 * teaches customers to hook them directly — so this method applies both,
		 * per campaign, with the SAME arguments Charitable_Campaign::get_donated_amount()/
		 * get_donor_count() pass, rather than trusting the raw grouped total to
		 * already be the product-correct figure. Skipping this step is exactly how
		 * list-campaigns and get-campaign-performance would silently disagree on
		 * `raised`/`donor_count`/`percent_raised` for the same campaign_id on any
		 * multilingual or Ambassadors site, or for any customer who followed
		 * Charitable's own documented snippet.
		 *
		 * This does mean a site with one of those integrations active pays a
		 * per-row cost again for exactly this figure (whatever those hooks
		 * themselves query) — accepted deliberately: correctness with the rest of
		 * the product outranks staying fast while disagreeing with it. A default
		 * install with nothing hooked on either filter pays only the cost of an
		 * `apply_filters()` call with no registered callbacks, which is negligible
		 * and issues no additional queries — the O(1) query count this class's
		 * batching achieves still holds for that common case.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Campaign $campaign      The campaign.
		 * @param  float               $raised_amount Raw, unfiltered amount already donated to this
		 *                                             campaign, resolved via the grouped query.
		 * @param  int                 $donor_count   Raw, unfiltered distinct donor count already
		 *                                             resolved for this campaign via the grouped query.
		 * @return array
		 */
		private function campaign_summary( Charitable_Campaign $campaign, $raised_amount, $donor_count ) {
			/*
			 * Matches Charitable_Campaign::get_donated_amount( true )'s own filter call
			 * exactly: same hook, same argument order, and `true` for $sanitize because
			 * the per-row code this replaces always called get_donated_amount( true ).
			 */
			$raised_amount = apply_filters( 'charitable_campaign_donated_amount', $raised_amount, $campaign, true );

			/* Matches Charitable_Campaign::get_donor_count()'s own filter call exactly. */
			$donor_count = apply_filters( 'charitable_campaign_donor_count', (int) $donor_count, $campaign );

			/*
			 * Task 16 Step 7 fix: has_goal mirrors Charitable_Campaign::has_goal()
			 * exactly (`0 < (float) get_meta( '_campaign_goal' )`). A campaign with
			 * no goal — or an explicit goal of 0, which Charitable's own model
			 * treats identically — used to report goal: 0, goal_formatted: '$0.00'
			 * and percent_raised: 0, indistinguishable from a campaign that
			 * genuinely has a $0 goal and raised nothing. Reporting null for all
			 * three instead lets a client (or an assistant relaying this to a
			 * donor) say "no goal set, $X raised" rather than the nonsensical
			 * "0% of its $0.00 goal".
			 */
			$has_goal = $campaign->has_goal();
			$goal     = $has_goal ? self::money( $campaign->get_meta( '_campaign_goal' ) ) : null;
			$raised   = self::money( $raised_amount );
			$end      = $campaign->get_end_date( 'Y-m-d H:i:s' );

			return array(
				'id'               => $campaign->get_campaign_id(),
				'title'            => (string) $campaign->post_title,
				'status'           => (string) $campaign->post_status,
				'url'              => (string) $campaign->get_permalink(),
				'has_goal'         => $has_goal,
				'goal'             => $has_goal ? $goal['raw'] : null,
				'goal_formatted'   => $has_goal ? $goal['formatted'] : null,
				'raised'           => $raised['raw'],
				'raised_formatted' => $raised['formatted'],
				'percent_raised'   => $has_goal ? $this->percent_from_totals( $goal['raw'], $raised['raw'] ) : null,
				'donor_count'      => (int) $donor_count,
				'end_date'         => false === $end ? null : $end,
				'has_ended'        => (bool) $campaign->has_ended(),
			);
		}

		/**
		 * Percentage of goal raised, computed from already-parsed goal/raised
		 * floats rather than by delegating to Charitable_Campaign::get_percent_donated_raw().
		 *
		 * That model method re-derives its own donated amount internally (another
		 * call to get_donated_amount(), the exact per-row query this class's
		 * batching in execute_list_campaigns() exists to avoid), and re-parses the
		 * goal meta with its own separate locale-stripping logic. Both values are
		 * already available here, already correctly parsed by self::money() (goal
		 * and raised are computed from the SAME parsed numbers returned to the
		 * caller), so this is arithmetic on numbers already in hand rather than a
		 * second, redundant resolution of them.
		 *
		 * Used by list-campaigns only. get-campaign-performance is a single-item
		 * ability with no N+1 exposure, so it keeps calling
		 * get_percent_donated_raw() directly — see percent_raised() below.
		 *
		 * Deliberately not rounded: round( $x, 2 ) is the exact hardcoded-decimal
		 * anti-pattern this ability suite avoids for money, and a percentage
		 * carries no currency-decimal question to begin with. Callers can round
		 * a raw float for display however they like.
		 *
		 * @since 1.8.13
		 *
		 * @param  float $goal   Parsed campaign goal (self::money()'s 'raw').
		 * @param  float $raised Parsed amount raised (self::money()'s 'raw').
		 * @return float
		 */
		private function percent_from_totals( $goal, $raised ) {
			if ( $goal <= 0 ) {
				return 0.0;
			}

			return ( $raised / $goal ) * 100;
		}

		/**
		 * Percentage of goal raised for a single campaign, or null for a
		 * campaign with no goal set.
		 *
		 * Delegates to Charitable_Campaign::get_percent_donated_raw(), which
		 * already strips the site's thousands separator from the goal meta
		 * before casting to float — the same locale-formatted-goal trap this
		 * class's own money() calls guard against, solved once inside the model
		 * rather than re-implemented here. Safe to call per-campaign because
		 * get-campaign-performance only ever resolves one campaign per request —
		 * see percent_from_totals() above for the batched, N-campaign equivalent
		 * used by list-campaigns.
		 *
		 * get_percent_donated_raw() returns `false`, specifically, for a
		 * campaign with no goal (it calls has_goal() as its own first check) —
		 * NOT 0. Task 16 Step 7 fix: this used to collapse that into 0.0,
		 * indistinguishable from a real goal that is 0% funded. Returning null
		 * instead lets a caller tell the two apart.
		 *
		 * Deliberately not rounded — see percent_from_totals()'s docblock.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Campaign $campaign The campaign.
		 * @return float|null
		 */
		private function percent_raised( Charitable_Campaign $campaign ) {
			$percent = $campaign->get_percent_donated_raw();

			return false === $percent ? null : (float) $percent;
		}

		/**
		 * The campaign's raw, unrendered description.
		 *
		 * Deliberately reads the _campaign_description meta directly rather than
		 * going through Charitable_Campaign::get( 'description' ) /
		 * description_content(), which runs YouTube/Vimeo embedding, wpautop(),
		 * shortcode_unautop() and do_shortcode() — rendering for display, not
		 * reporting configuration. An ability that returns "full configuration"
		 * should hand back the editable source text, not a rendered preview.
		 * Falls back to post_content for the same reason description_content()
		 * does: some campaigns store their extended description there instead of
		 * in _campaign_description.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Campaign $campaign The campaign.
		 * @return string
		 */
		private function raw_description( Charitable_Campaign $campaign ) {
			$description = (string) $campaign->get_meta( '_campaign_description' );

			if ( '' === trim( $description ) ) {
				$description = (string) $campaign->post_content;
			}

			return $description;
		}

		/**
		 * Build the `suggested_donations` array for charitable/get-campaign.
		 *
		 * Each suggested amount is stored the same way the campaign goal is —
		 * potentially as a locale-formatted string — so it is routed through
		 * money() individually rather than trusted as a bare float.
		 *
		 * @since 1.8.13
		 *
		 * @param  Charitable_Campaign $campaign The campaign.
		 * @return array
		 */
		private function suggested_donations( Charitable_Campaign $campaign ) {
			$rows = array();

			foreach ( $campaign->get_suggested_donations() as $donation ) {
				if ( ! is_array( $donation ) || ! isset( $donation['amount'] ) ) {
					continue;
				}

				$money = self::money( $donation['amount'] );

				$rows[] = array(
					'amount'           => $money['raw'],
					'amount_formatted' => $money['formatted'],
					'description'      => isset( $donation['description'] ) ? wp_strip_all_tags( (string) $donation['description'] ) : '',
				);
			}

			return $rows;
		}

		/**
		 * Locate a campaign by id, rejecting ids with no backing post.
		 *
		 * The charitable_get_campaign() helper ALWAYS returns a Charitable_Campaign
		 * object — never false — even for a nonexistent id; it unconditionally
		 * does `new Charitable_Campaign( $campaign_id )`, and the constructor's
		 * own `get_post( $post )` call simply leaves the model's internal post
		 * as null. Existence has to be checked before calling it, not by
		 * trusting its return value.
		 *
		 * @since 1.8.13
		 *
		 * @param  int $campaign_id The campaign (post) id.
		 * @return Charitable_Campaign|WP_Error
		 */
		private function find_campaign( $campaign_id ) {
			$post = get_post( $campaign_id );

			if ( ! $post || 'campaign' !== $post->post_type ) {
				return new WP_Error(
					'charitable_campaign_not_found',
					__( 'No campaign was found with that ID.', 'charitable' )
				);
			}

			return charitable_get_campaign( $post );
		}

		/**
		 * Validate and sanitize the named fields shared by create-campaign and
		 * update-campaign, WITHOUT writing anything.
		 *
		 * Every field is gated behind array_key_exists() against the raw $input
		 * array, never isset()/empty() — a caller-supplied `false`, `0` or `""`
		 * must still count as "supplied" for update-campaign's partial-update
		 * contract to hold. Money and date fields are parsed eagerly so a bad
		 * value on either ability is reported as a WP_Error before either
		 * ability writes anything: create-campaign never inserts the post, and
		 * update-campaign never touches the existing campaign.
		 *
		 * `status` and `template` are deliberately not handled here — `status`
		 * because update-campaign's schema excludes it (see the class-level
		 * note on that ability), and `template` because it is not in EITHER
		 * ability's schema — see validate_template()'s docblock for why a
		 * create-campaign `template` field was removed entirely in fix round 1
		 * rather than kept as a validated-but-silently-ignored no-op. Callers
		 * that need `status` read it directly off $input.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $input       Raw ability input (or a subset of it with
		 *                            campaign_id/status already removed).
		 * @param  int   $campaign_id Optional. The campaign being updated, passed
		 *                            through to parse_suggested_amounts() so an
		 *                            amount's existing description can be carried
		 *                            forward. 0 on a create.
		 * @return array|WP_Error Map of field name => sanitized value for every
		 *                        field present in $input, or a WP_Error from the
		 *                        first field that failed to parse.
		 */
		private function parse_named_fields( array $input, $campaign_id = 0 ) {
			$parsed = array();

			if ( array_key_exists( 'title', $input ) ) {
				/*
				 * is_scalar() before the cast: array_key_exists() is true for an
				 * array or object value, and (string) on either raises or throws.
				 * A non-scalar title becomes an empty one, which write_named_fields()
				 * then applies as an empty post_title — the same outcome as
				 * supplying "".
				 */
				$parsed['title'] = is_scalar( $input['title'] )
					? sanitize_text_field( (string) $input['title'] )
					: '';
			}

			if ( array_key_exists( 'goal', $input ) ) {
				/*
				 * self::parse_money() returns a currency-agnostic, always
				 * period-decimal float — see write_named_fields()'s docblock for
				 * why that value is written to _campaign_goal AS IS, with no
				 * further sanitization pass through Charitable_Campaign's own
				 * (site-locale-aware) goal sanitizer.
				 */
				$goal = self::parse_money( $input['goal'] );

				if ( is_wp_error( $goal ) ) {
					return $goal;
				}

				$parsed['goal'] = $goal;
			}

			if ( array_key_exists( 'end_date', $input ) ) {
				$end_date = $this->parse_end_date( $input['end_date'] );

				if ( is_wp_error( $end_date ) ) {
					return $end_date;
				}

				$parsed['end_date'] = $end_date;
			}

			if ( array_key_exists( 'description', $input ) ) {
				/*
				 * Same is_scalar() guard as the title branch above, and for the
				 * same reason: array_key_exists() does not care whether the
				 * value is a string, and (string) on an array or object raises
				 * or throws before sanitize_campaign_description() ever runs.
				 * This one was not in this task's original list of seven reads
				 * — it surfaced as an eighth failure in the hostile-input sweep
				 * (test_no_execute_method_crashes_on_hostile_input), because
				 * the sweep tests every key the surface reads, not only the
				 * ones already known about.
				 */
				$parsed['description'] = is_scalar( $input['description'] )
					? Charitable_Campaign::sanitize_campaign_description( (string) $input['description'] )
					: '';
			}

			if ( array_key_exists( 'suggested_amounts', $input ) ) {
				$rows = $this->parse_suggested_amounts( $input['suggested_amounts'], $campaign_id );

				if ( is_wp_error( $rows ) ) {
					return $rows;
				}

				$parsed['suggested_amounts'] = $rows;
			}

			if ( array_key_exists( 'allow_custom_amount', $input ) ) {
				$parsed['allow_custom_amount'] = empty( $input['allow_custom_amount'] ) ? 0 : 1;
			}

			return $parsed;
		}

		/**
		 * Write a parse_named_fields() result to the campaign post and its meta.
		 *
		 * `title` is applied via wp_update_post() with ONLY `ID` and `post_title`
		 * in the array. wp_update_post() fetches the existing post and merges the
		 * passed array over it (`array_merge( $post, $postarr )` inside core's
		 * wp_update_post()) rather than replacing the row outright, so every
		 * other core field (post_status, post_content, etc.) survives untouched
		 * — this is what makes it safe to call for update-campaign even though
		 * only a title is being changed. When `title` is absent from $parsed
		 * entirely (the normal case for a partial update-campaign call), this
		 * method never calls wp_update_post() at all, so post_title is never even
		 * read, let alone rewritten to its own current value.
		 *
		 * Every other field is a flat meta write: update_post_meta() for exactly
		 * the keys present in $parsed, nothing more.
		 *
		 * `goal` and each row inside `suggested_amounts` arrive here already
		 * parsed by self::parse_money() (in parse_named_fields()) into a
		 * canonical, currency-agnostic, ALWAYS PERIOD-DECIMAL float — the same
		 * convention self::money() uses when reading _campaign_goal back out
		 * elsewhere in this class. Both are written straight to
		 * update_post_meta() below with NO further sanitization pass. This is
		 * deliberate: do NOT "helpfully" route them through
		 * Charitable_Campaign::sanitize_campaign_goal() or
		 * ::sanitize_campaign_suggested_donations() for consistency with the
		 * campaign builder's own save path. Both of those static methods
		 * ultimately call Charitable_Currency::sanitize_monetary_amount() with
		 * $db_format defaulting to FALSE, i.e. they assume the value they're
		 * given is already in the SITE'S DISPLAY LOCALE (e.g. "1.234,56" typed
		 * into a comma-decimal site's admin form) — the opposite of
		 * parse_money()'s contract. Re-sanitizing an already-canonical value
		 * through either would silently corrupt it on a comma-decimal-locale
		 * site: e.g. a parsed 1234.5 stringifies to "1234.5", whose lone "."
		 * that sanitizer's locale branch reads as a THOUSANDS separator and
		 * strips, storing 12345 instead. parse_money() has already validated
		 * and normalised the value by the time it reaches $parsed; writing it
		 * as-is is correct, not a shortcut. (This mismatch cannot be exercised
		 * under this test environment's default locale — see
		 * test_create_campaign_goal_round_trips_and_is_stored_canonically()'s
		 * docblock — which is exactly why it is documented here in prose
		 * instead of only pinned by a test.)
		 *
		 * @since 1.8.13
		 *
		 * @param  int   $campaign_id The campaign (post) id.
		 * @param  array $parsed      Output of parse_named_fields().
		 * @return array|WP_Error The field names actually written, or a WP_Error
		 *                        if the title write failed.
		 */
		private function write_named_fields( $campaign_id, array $parsed ) {
			if ( array_key_exists( 'title', $parsed ) ) {
				$updated = wp_update_post(
					array(
						'ID'         => $campaign_id,
						'post_title' => $parsed['title'],
					),
					true
				);

				if ( is_wp_error( $updated ) ) {
					return $updated;
				}
			}

			$meta_map = array(
				'goal'                => '_campaign_goal',
				'end_date'            => '_campaign_end_date',
				'description'         => '_campaign_description',
				'suggested_amounts'   => '_campaign_suggested_donations',
				'allow_custom_amount' => '_campaign_allow_custom_donations',
			);

			foreach ( $meta_map as $field => $meta_key ) {
				if ( array_key_exists( $field, $parsed ) ) {
					update_post_meta( $campaign_id, $meta_key, $parsed[ $field ] );
				}
			}

			$this->bridge_named_fields_to_builder_settings( $campaign_id, $parsed );

			return array_keys( $parsed );
		}

		/**
		 * Keep the campaign builder's copy of these five fields in step.
		 *
		 * Each of them is stored TWICE on a builder campaign: in the legacy post
		 * meta written above, which the front end reads, and inside
		 * `campaign_settings_v2`, which the campaign builder reads. Writing only
		 * the first produced the worst of the two possible failures — the
		 * published page showed the new value, the builder showed the old one,
		 * and the next Save in the builder wrote the old one BACK over it.
		 * Verified live: after an ability set a goal to 40000 the campaign page
		 * said 40,000 and Settings > General still said 25,000.00.
		 *
		 * Both halves of that are load-bearing:
		 *
		 * - The builder's Settings panels have NO post-meta fallback for any of
		 *   these five. `campaign_data_settings()` takes an optional `$meta_key`
		 *   argument for exactly that purpose and none of the relevant call sites
		 *   passes it — class-settings-general.php:146,172,185 (description, goal,
		 *   end_date) and class-settings-donation-options.php:152,195
		 *   (donation_amounts, allow_custom_donations).
		 * - `charitable_save_campaign()` (ajax-actions.php's `$base_meta_keys`
		 *   loop and the block under it) DERIVES the legacy meta from the POSTed
		 *   settings blob on every builder save. So a stale copy is not merely
		 *   displayed, it wins.
		 *
		 * `create-campaign` already had to solve this for creation — see
		 * write_visual_campaign_settings(), which populates the same keys from
		 * the same `$parsed` array. This is that contract for updates, and the
		 * mapping is deliberately identical so the two paths cannot disagree
		 * about what a value looks like in the blob.
		 *
		 * ONLY BRIDGES AN EXISTING BLOB. A campaign with no
		 * `campaign_settings_v2` is left alone, because
		 * charitable_is_campaign_legacy() is `empty( campaign_settings_v2 )` and
		 * creating one would flip a classic campaign onto the builder render path
		 * with an empty layout — the conversion that broke campaign pages through
		 * update-campaign-styles. That guard is also what makes this safe to call
		 * from create-campaign, where write_named_fields() runs BEFORE
		 * write_visual_campaign_settings() and the blob does not exist yet: this
		 * is a no-op there and the full write follows.
		 *
		 * `title` is deliberately NOT bridged. It is the one field already
		 * reconciled at the other end: the builder reads `post_title` on load and
		 * overwrites its own copy (class-charitable-campaign-builder.php:200-219,
		 * the 1.8.17.6 fix for renames made outside the builder), so post_title is
		 * authoritative for it and a second writer here would be redundant.
		 *
		 * @since 1.8.13
		 *
		 * @param  int   $campaign_id The campaign (post) id.
		 * @param  array $parsed      Output of parse_named_fields().
		 * @return void
		 */
		private function bridge_named_fields_to_builder_settings( $campaign_id, array $parsed ) {
			$settings = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

			if ( ! is_array( $settings ) || empty( $settings ) ) {
				return;
			}

			if ( ! isset( $settings['settings'] ) || ! is_array( $settings['settings'] ) ) {
				$settings['settings'] = array();
			}

			foreach ( array( 'general', 'donation-options' ) as $section ) {
				if ( ! isset( $settings['settings'][ $section ] ) || ! is_array( $settings['settings'][ $section ] ) ) {
					$settings['settings'][ $section ] = array();
				}
			}

			$changed = false;

			/*
			 * The TITLE, which lives at the top level of campaign_settings_v2
			 * rather than under settings.general like the five below.
			 *
			 * Omitting it was the same defect this method exists to fix, and it
			 * bit harder than the others. write_named_fields() sends the title
			 * to `post_title` via wp_update_post() and stopped there, so:
			 * Charitable_Campaign_Builder::get_campaign_settings() loads
			 * campaign_settings_v2 into $campaign_data, the builder's title
			 * input renders $campaign_data['title'] (the STALE one), and the
			 * next Save posts it back through charitable_create_campaign(),
			 * which writes it straight over the post_title the ability had just
			 * set. The reported symptom was "upon saving, the title of the
			 * campaign can not be changed" - the change landed and was then
			 * reverted by the builder, which is indistinguishable from never
			 * having applied.
			 *
			 * execute_create_campaign() already sets $settings['title'] on the
			 * create path, so this only closes the update path's half.
			 */
			if ( array_key_exists( 'title', $parsed ) ) {
				$settings['title'] = $parsed['title'];
				$changed           = true;
			}

			if ( array_key_exists( 'goal', $parsed ) ) {
				/*
				 * A locale-formatted DISPLAY string, not the canonical float
				 * `_campaign_goal` holds — mirroring both
				 * write_visual_campaign_settings() and charitable_save_campaign()'s
				 * own "Sanitize some misc fields" block, because this value goes
				 * straight into the goal text field's value attribute.
				 */
				$currency_helper = charitable_get_currency_helper();

				$settings['settings']['general']['goal'] = number_format(
					(float) $parsed['goal'],
					(int) $currency_helper->get_decimals(),
					$currency_helper->get_decimal_separator(),
					$currency_helper->get_thousands_separator()
				);

				$changed = true;
			}

			if ( array_key_exists( 'end_date', $parsed ) ) {
				$settings['settings']['general']['end_date'] = $parsed['end_date'];
				$changed = true;
			}

			if ( array_key_exists( 'description', $parsed ) ) {
				$settings['settings']['general']['description'] = $parsed['description'];

				/*
				 * An explicit blank has to clear the description FIELD's own
				 * content too, or Charitable_Field_Campaign_Description::render()
				 * falls back to it and the template's stale default text keeps
				 * rendering to donors. Same reasoning, same helper, as the
				 * equivalent branch in execute_create_campaign().
				 */
				if ( '' === $parsed['description'] ) {
					$settings = $this->clear_visual_description_field_content( $settings );
				}

				$changed = true;
			}

			if ( array_key_exists( 'suggested_amounts', $parsed ) && is_array( $parsed['suggested_amounts'] ) ) {
				$amounts = array();

				foreach ( array_values( $parsed['suggested_amounts'] ) as $index => $row ) {
					$amounts[ $index ] = array(
						'amount'      => $row['amount'],
						'description' => isset( $row['description'] ) ? $row['description'] : '',
					);
				}

				$settings['settings']['donation-options']['donation_amounts'] = $amounts;
				$changed = true;
			}

			if ( array_key_exists( 'allow_custom_amount', $parsed ) ) {
				$settings['settings']['donation-options']['allow_custom_donations'] = empty( $parsed['allow_custom_amount'] ) ? '0' : '1';
				$changed = true;
			}

			if ( ! $changed ) {
				return;
			}

			update_post_meta( $campaign_id, 'campaign_settings_v2', $settings );
		}

		/**
		 * Validate and normalize an inbound `end_date` (required format: YYYY-MM-DD).
		 *
		 * The format is checked with a regex AND checkdate() before ever calling
		 * Charitable_Campaign::sanitize_campaign_end_date() — that static helper
		 * silently returns 0 (meaning "endless") for anything it can't parse via
		 * charitable_sanitize_date(), which would make a typo'd date quietly
		 * remove the campaign's end date instead of failing loudly. Validating
		 * first turns that into a WP_Error.
		 *
		 * Reuses sanitize_campaign_end_date() (rather than formatting the string
		 * directly) so the stored value goes through the SAME i18n-aware
		 * decline_months() branch every other campaign end date in this codebase
		 * does — see that method's docblock.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $value Inbound end_date.
		 * @return string|WP_Error 'Y-m-d H:i:s'-formatted end date, or a WP_Error.
		 */
		private function parse_end_date( $value ) {
			if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				return new WP_Error(
					'charitable_invalid_end_date',
					__( 'end_date must be a string in YYYY-MM-DD format.', 'charitable' )
				);
			}

			list( $year, $month, $day ) = array_map( 'intval', explode( '-', $value ) );

			if ( ! checkdate( $month, $day, $year ) ) {
				return new WP_Error(
					'charitable_invalid_end_date',
					__( 'end_date is not a real calendar date.', 'charitable' )
				);
			}

			return Charitable_Campaign::sanitize_campaign_end_date( $value );
		}

		/**
		 * A stable string key for matching an amount against an existing row.
		 *
		 * Floats are unusable as array keys — PHP casts them to int, so 25.5 and
		 * 25.9 collide on 25 — and a direct float comparison would make the
		 * carry-forward depend on binary representation. Six decimal places is
		 * far beyond any currency's precision and makes the key exact for every
		 * amount a caller can actually supply.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $amount An amount already normalised by parse_money().
		 * @return string
		 */
		private static function amount_key( $amount ) {
			return number_format( (float) $amount, 6, '.', '' );
		}

		/**
		 * The descriptions a campaign's stored amount ladder already carries,
		 * keyed by amount_key().
		 *
		 * Reads the RAW meta rather than Charitable_Campaign::get_suggested_donations(),
		 * which applies the `charitable_campaign_suggested_donations` filter. This
		 * value is on its way back into the database, and writing a filtered value
		 * back would bake a filter's output into stored data — the same reasoning
		 * that keeps legacy_style_seed() on raw campaign_settings_v2.
		 *
		 * Each stored amount is normalised through the SAME sanitiser parse_money()
		 * uses, because a stored amount may be a locale-formatted string
		 * ("1,000.00") and a raw float cast on that reads 1. Both sides of the
		 * match therefore go through Charitable_Currency::sanitize_monetary_amount(
		 * $value, true ).
		 *
		 * Empty descriptions are omitted, so an absent key means "nothing to carry
		 * forward" rather than "carry forward an empty string".
		 *
		 * @since 1.8.13
		 *
		 * @param  int $campaign_id The campaign, or 0 on a create.
		 * @return array Amount key => description string.
		 */
		private function existing_amount_descriptions( $campaign_id ) {
			$campaign_id = (int) $campaign_id;

			if ( $campaign_id < 1 ) {
				return array();
			}

			$rows = get_post_meta( $campaign_id, '_campaign_suggested_donations', true );

			if ( ! is_array( $rows ) ) {
				return array();
			}

			$helper = charitable_get_currency_helper();
			$map    = array();

			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['amount'] ) || ! is_scalar( $row['amount'] ) ) {
					continue;
				}

				$description = isset( $row['description'] ) && is_scalar( $row['description'] )
					? (string) $row['description']
					: '';

				if ( '' === $description ) {
					continue;
				}

				$amount = $helper->sanitize_monetary_amount( (string) $row['amount'], true );

				$map[ self::amount_key( $amount ) ] = $description;
			}

			return $map;
		}

		/**
		 * Validate and parse an inbound `suggested_amounts` entry list into the
		 * `array( array( 'amount' => float, 'description' => string ) )` shape
		 * _campaign_suggested_donations is stored in.
		 *
		 * Each amount is routed through self::parse_money() individually — NOT
		 * through Charitable_Campaign::sanitize_campaign_suggested_donations(),
		 * which sanitizes each amount via charitable_sanitize_amount() with
		 * $db_format defaulting to false (i.e. it expects a value already in the
		 * SITE'S display locale, such as "1.234,56" typed into a comma-decimal
		 * site's admin form). self::parse_money()'s contract is the opposite: a
		 * currency-agnostic, always-period-decimal number regardless of site
		 * locale (the same convention self::money() reads _campaign_goal back
		 * with). Feeding parse_money()'s output back through the locale-aware
		 * sanitizer on a comma-decimal site would silently mis-parse it — e.g.
		 * 1234.5 stringifies to "1234.5", and the comma-decimal branch of
		 * sanitize_monetary_amount() treats that lone "." as a thousands
		 * separator and strips it, storing 12345. Building the row shape
		 * directly, as done here, avoids that mismatch entirely.
		 *
		 * Each entry is either a bare amount or an object carrying `amount` and
		 * optionally `description`. A bare amount inherits whatever description
		 * that amount already has on the campaign, which is what stops a caller
		 * changing one number from silently deleting every donor-facing caption
		 * in the ladder — the defect this shape exists to close. An explicit
		 * SCALAR `description`, including an empty one, wins over the inherited
		 * value; a non-scalar `description` is not stored (is_scalar() guards
		 * the cast below) and the entry silently inherits instead — unreachable
		 * over REST, where the schema requires `description` to be a string,
		 * but still possible on a direct call.
		 *
		 * @since 1.8.13
		 *
		 * @param  mixed $amounts     Inbound suggested_amounts.
		 * @param  int   $campaign_id Optional. The campaign being updated, for the
		 *                            carry-forward. 0 on a create, where there is
		 *                            nothing to carry.
		 * @return array|WP_Error
		 */
		private function parse_suggested_amounts( $amounts, $campaign_id = 0 ) {
			if ( ! is_array( $amounts ) ) {
				return new WP_Error(
					'charitable_invalid_amount',
					__( 'suggested_amounts must be an array of amounts.', 'charitable' )
				);
			}

			$existing = $this->existing_amount_descriptions( $campaign_id );
			$rows     = array();

			foreach ( $amounts as $entry ) {
				$description = null;
				$amount      = $entry;

				if ( is_array( $entry ) ) {
					/*
					 * Unreachable over REST: the input schema declares `amount`
					 * required inside the object branch, and core validates the
					 * schema BEFORE this callback runs, so a missing amount comes
					 * back as ability_invalid_input. Kept because every execute_*
					 * method is public and therefore reachable by an internal
					 * caller that bypasses core's validator entirely.
					 */
					if ( ! array_key_exists( 'amount', $entry ) ) {
						return new WP_Error(
							'charitable_invalid_amount',
							__( 'Each entry in suggested_amounts must be an amount, or an object with an amount.', 'charitable' )
						);
					}

					$amount = $entry['amount'];

					if ( array_key_exists( 'description', $entry ) && is_scalar( $entry['description'] ) ) {
						$description = sanitize_text_field( (string) $entry['description'] );
					}
				}

				$parsed = self::parse_money( $amount );

				if ( is_wp_error( $parsed ) ) {
					return $parsed;
				}

				if ( null === $description ) {
					$key         = self::amount_key( $parsed );
					$description = isset( $existing[ $key ] ) ? $existing[ $key ] : '';
				}

				$rows[] = array(
					'amount'      => $parsed,
					'description' => $description,
				);
			}

			return $rows;
		}

		/**
		 * The suggested-amount ladder seeded into a visual campaign when the
		 * caller supplies none. See execute_create_campaign().
		 *
		 * These numbers are NOT invented for this ability. 5 / 10 / 15 / 20 is
		 * Charitable's OWN default ladder for a brand-new campaign, and it is
		 * the same in all three places the product already falls back to one:
		 *
		 *  - includes/admin/campaign-builder/class-builder-form-fields.php
		 *    (~line 1660), the campaign builder's amounts table, which
		 *    pre-populates exactly 5/10/15/20 whenever it renders with no
		 *    saved value and no campaign_id — i.e. a new campaign;
		 *  - includes/admin/campaign-builder/fields/class-donate-amount.php
		 *    (~line 167), the builder's own preview of a new campaign, which
		 *    renders $5/$10/$15/$20;
		 *  - includes/admin/onboarding/class-charitable-setup.php's
		 *    get_donation_options(), the setup wizard's donation_amounts.
		 *
		 * Reusing them means a campaign this ability creates matches one an
		 * admin creates by hand in the builder, rather than introducing a
		 * fourth, ability-only ladder.
		 *
		 * Checked for and deliberately not used: there is NO site-level
		 * default to prefer over this. `charitable_get_option(
		 * 'default_minimum_donation_amount' )` is a per-donation floor, not a
		 * ladder, and no option, constant or filter for a default suggested
		 * set exists anywhere in the plugin.
		 *
		 * Descriptions are left empty rather than copied from the builder's
		 * fallback, which pairs each amount with placeholder copy ("This is a
		 * small donation."). That text exists to be edited by an admin looking
		 * at the form; on an AI-built campaign that may be published without
		 * anyone opening the builder, it would render to donors as the amount's
		 * own label. Empty also matches what parse_suggested_amounts() produces
		 * for caller-supplied amounts, so seeded and supplied rows are the same
		 * shape.
		 *
		 * The builder's `suggested_donations_default` (index 3, pre-selecting
		 * 15) is intentionally not seeded: it is a separate setting, this
		 * ability has never written it for caller-supplied amounts either, and
		 * no pre-selection is the safer default for a donor.
		 *
		 * SCALING TO THE GOAL
		 *
		 * 5/10/15/20 alone is only right for a small or goalless campaign. A
		 * $5,000 goal whose largest suggestion is $20 needs 250 gifts to reach
		 * it, which is not a usable fundraiser — and "usable on arrival" is the
		 * entire reason visual mode seeds anything. Since the goal is already
		 * known at creation time, the ladder is picked from it.
		 *
		 * An explicit BAND TABLE, deliberately not arithmetic. Multiplying or
		 * dividing the goal produces suggestions like $37 or $412, which no
		 * fundraiser would choose and which change shape unpredictably across
		 * the range. A table is predictable, reviewable, and every edge of it
		 * is covered by a test.
		 *
		 * Bands are inclusive-low / exclusive-high:
		 *
		 *   no goal, <= 0, or < 500  ->    5,  10,  15,   20   (the builder's own)
		 *   500     - 1,999          ->   10,  25,  50,  100
		 *   2,000   - 9,999          ->   25,  50, 100,  250
		 *   10,000  - 49,999         ->   50, 100, 250,  500
		 *   50,000 and above         ->  100, 250, 500, 1000
		 *
		 * The first row is load-bearing: a campaign with no goal, a zero goal
		 * or a goal under 500 keeps EXACTLY the builder's 5/10/15/20. That
		 * parity for small and goalless campaigns is what makes this a
		 * refinement of Charitable's own default rather than a departure from
		 * it, and there is a test pinning each of those three cases.
		 *
		 * CURRENCY ASSUMPTION, noted rather than overlooked: the band edges are
		 * in raw site-currency units, with no per-currency scaling. That is the
		 * same assumption the builder's own 5/10/15/20 already makes — it is
		 * not adjusted for a zero-decimal currency either, where 5 JPY is a
		 * meaningless suggestion and the whole ladder would want to be three
		 * orders of magnitude larger. Solving that means a currency-aware
		 * ladder for the builder, this ability and the setup wizard together;
		 * it is deliberately out of scope here rather than half-solved in one
		 * of the three places.
		 *
		 * @since 1.8.13
		 *
		 * @param  float $goal The campaign's goal, ALREADY normalised to a
		 *                     canonical float by self::parse_money(). Never a
		 *                     locale-formatted string, and never read back out
		 *                     of _campaign_goal — see the call site in
		 *                     execute_create_campaign() for why that matters.
		 *                     0.0 means no goal was supplied.
		 * @return int[]
		 */
		private static function default_suggested_amount_ladder( $goal = 0.0 ) {
			$goal = (float) $goal;

			/*
			 * Descending, so the first matching floor wins and the table reads
			 * in the same direction as the docblock. A goal of 0, a negative
			 * goal and anything under 500 all fall through to the final
			 * return, which is the builder's own ladder.
			 */
			$bands = array(
				50000 => array( 100, 250, 500, 1000 ),
				10000 => array( 50, 100, 250, 500 ),
				2000  => array( 25, 50, 100, 250 ),
				500   => array( 10, 25, 50, 100 ),
			);

			foreach ( $bands as $floor => $ladder ) {
				if ( $goal >= (float) $floor ) {
					return $ladder;
				}
			}

			return array( 5, 10, 15, 20 );
		}

		/**
		 * Validate a campaign `template` id against the real template registry.
		 *
		 * Reachable again as of Task 17 — see execute_create_campaign()'s
		 * docblock. History for why it was orphaned, and why THIS is what
		 * finally reconnects it (kept below rather than deleted, because it
		 * remains exactly correct):
		 *
		 * This existed originally to validate create-campaign's `template`
		 * field, which was then intentionally left unapplied: there was no
		 * clean, narrow way to apply a template selection to a new campaign,
		 * because the only place a chosen template is recorded is the
		 * `campaign_settings_v2` post meta blob, and this class was, at the
		 * time, kept off that meta key's write path entirely. Two AJAX handlers
		 * in includes/admin/campaign-builder/ajax-actions.php
		 * (`charitable_new_campaign`, `charitable_update_campaign_template`)
		 * look like a narrower alternative, but both call
		 * `charitable()->get( 'form' )` /
		 * `charitable()->get( 'builder_templates' )->is_valid_template()` — the
		 * `Charitable` singleton has no `get()` method at all (only
		 * `registry()`), and no `builder_templates` key is ever registered, so
		 * both handlers fatal if actually invoked. They are not a real, working
		 * alternative. Filed as a pre-existing bug, not fixed here.
		 *
		 * Fix round 1 removed `template` from create-campaign's input schema
		 * entirely, rather than keeping this validation reachable: a caller
		 * sending a valid `template` id got a successful response with the
		 * value silently unapplied, which is worse than a clean rejection —
		 * nothing downstream could tell a real create from a silently-ignored
		 * field.
		 *
		 * Task 17 is the narrow, safe write path fix round 1 anticipated:
		 * `mode: visual` (see execute_create_campaign() and
		 * write_visual_campaign_settings()) now writes a minimal, correct
		 * `campaign_settings_v2` seeded from this validated template, rather
		 * than accepting an arbitrary partial one. `legacy` mode (the default)
		 * still rejects `template` outright, for the exact reason fix round 1
		 * gave: silently accepting-and-ignoring it would be worse than refusing
		 * it.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $template_id The requested template id.
		 * @return true|WP_Error
		 */
		private function validate_template( $template_id ) {
			if ( ! class_exists( 'Charitable_Campaign_Builder_Templates' ) ) {
				require_once charitable()->get_path( 'includes' ) . 'admin/campaign-builder/templates/class-templates.php';
			}

			$data      = ( new Charitable_Campaign_Builder_Templates() )->get_templates_data();
			$templates = isset( $data['templates'] ) && is_array( $data['templates'] ) ? $data['templates'] : array();

			if ( ! array_key_exists( (string) $template_id, $templates ) ) {
				/*
				 * Fix round 2, Finding 1. The brief asked for an error "naming a
				 * few valid slugs"; this keeps that AND the pointer to List
				 * Campaign Templates, because they serve different moments — the
				 * examples give an agent something to retry with immediately,
				 * the pointer is the authoritative full list. The examples are
				 * pulled live from $templates (the same registry read above)
				 * rather than hardcoded, so a template rename can't leave a
				 * stale slug in this message.
				 */
				$examples = array_slice( array_keys( $templates ), 0, 3 );

				if ( $examples ) {
					$message = sprintf(
						/* translators: %s: a comma-separated sample of real template ids (not the full list) read live from the template registry. */
						__( 'That template does not exist. Examples of valid templates: %s. Use List Campaign Templates for the full list.', 'charitable' ),
						implode( ', ', $examples )
					);
				} else {
					// No templates registered at all — fall back to the pointer alone; there is nothing to sample.
					$message = __( 'That template does not exist. Use List Campaign Templates to see the available options.', 'charitable' );
				}

				return new WP_Error( 'charitable_invalid_template', $message );
			}

			return true;
		}

		/**
		 * Build and persist `campaign_settings_v2` for a freshly created visual
		 * campaign, from a validated template id.
		 *
		 * Reuses the two PHP functions Task 17's investigation verified run with
		 * no HTTP request, no nonce and no admin screen loaded —
		 * Charitable_Campaign_Builder_Templates::get_template_data() and
		 * charitable_template_layout_to_campaign_layout() — for the structurally
		 * hard part: turning a template into `layout.rows` plus the top-level
		 * `fields` config array. Deliberately does NOT attempt to reconstruct
		 * `settings.general`/`settings.donation-options`/`tabs` the way the real
		 * campaign-builder save handler (`charitable_save_campaign()`) does —
		 * that function builds them by generically deserializing a POSTed flat
		 * `{name,value}` list that is only correct because browser JS
		 * (admin-builder.js) populated the DOM from the template first, and no
		 * PHP function returns those defaults as data; they live as inline
		 * render fallbacks scattered across ~30 panel classes. Reimplementing
		 * that is explicitly out of scope.
		 *
		 * Judgement call, verified empirically rather than assumed (see the
		 * docblock on populate_visual_layout_field_ids() below): calling
		 * charitable_template_layout_to_campaign_layout() alone, with no
		 * pre-existing POST-built $campaign_settings_v2, produces layout.rows
		 * with every row/column/section/tab TYPE correctly populated but every
		 * section's and tab's OWN `fields` id list — and every row's id => type
		 * map — reset to an empty array. That is not a theoretical gap: it was
		 * confirmed by calling the function directly against
		 * animal-sanctuary's real template data before writing this method.
		 * content.php's front-end renderer iterates exactly those id lists
		 * (`foreach ( $section['fields'] as $key => $field_id )` for a
		 * `fields` section, `$row['fields'][ $field_id ]` for the type) to
		 * decide what to render inside each row — so leaving them empty
		 * produces a campaign whose `layout.rows` array is non-empty (passing
		 * the literal check content.php:113-130 warns about) but whose every
		 * row is still an empty shell once rendered, which is the exact
		 * outcome that check exists to prevent, reached by a different key.
		 * populate_visual_layout_field_ids() closes that gap using only the
		 * template's own PHP layout array — not browser JS, and not any panel
		 * class's inline defaults — walked in the same order
		 * charitable_template_layout_to_campaign_layout()'s own field-copying
		 * loop already walks it, so the ids it assigns agree with the field
		 * CONFIG that function already copied into `fields`.
		 *
		 * `settings.general`/`settings.donation-options` ARE populated here,
		 * narrowly, from $parsed — NOT because the brief asked for full
		 * settings-defaults parity (it explicitly ruled that out), but because
		 * these specific keys are exactly the fields THIS ability itself
		 * manages (goal, end_date, description, suggested amounts, allow
		 * custom amount) and campaign_data_settings() — the Settings panel's
		 * own accessor — has no legacy-postmeta fallback for any of them
		 * (verified: none of class-settings-general.php's or
		 * class-settings-donation-options.php's campaign_data_settings() calls
		 * for these fields pass its optional $meta_key argument). Without this,
		 * a visual campaign created with e.g. goal: 5000 would show a BLANK
		 * goal field the first time an admin opens Settings > General in the
		 * builder, even though get-campaign and the front end both already
		 * report it correctly from `_campaign_goal` — a real, demo-visible
		 * inconsistency, not a hypothetical one.
		 *
		 * `campaign_settings_v2['title']` is set to the SAME $title already
		 * written to post_title, never re-derived — see the class docblock's
		 * note on the rename bug (fixed upstream in 30f104dd40) that drift
		 * between the two caused.
		 *
		 * Returns the image sideload outcome only: every other input has
		 * already been validated by the time this is called (the template id
		 * via validate_template(), the parsed
		 * fields via parse_named_fields()), and get_template_data() is
		 * guaranteed to return the SAME template validate_template() just
		 * confirmed exists — both read the identical, option-cached
		 * get_templates_data() result. There is no realistic failure path left
		 * to report.
		 *
		 * @since 1.8.13
		 *
		 * @param  int    $campaign_id The newly created campaign's post id.
		 * @param  string $template_id A template id already confirmed valid by
		 *                              validate_template().
		 * @param  string $title       The (already sanitized) title just written
		 *                              to post_title.
		 * @param  array  $parsed      parse_named_fields() output already
		 *                              applied via write_named_fields().
		 * @param  string $status      The resolved post_status ('draft' by
		 *                              default).
		 * @param  string $image_url   Optional caller-supplied image_url ('' to
		 *                              skip — see sideload_campaign_image()).
		 * @return array { attached: bool, error: string|null } — whether an
		 *                image_url was fetched and attached, for
		 *                execute_create_campaign() to report as image_attached/
		 *                image_error.
		 */
		private function write_visual_campaign_settings( $campaign_id, $template_id, $title, array $parsed, $status, $image_url = '' ) {
			if ( ! class_exists( 'Charitable_Campaign_Builder_Templates' ) ) {
				require_once charitable()->get_path( 'includes' ) . 'admin/campaign-builder/templates/class-templates.php';
			}

			if ( ! function_exists( 'charitable_template_layout_to_campaign_layout' ) ) {
				require_once charitable()->get_path( 'includes' ) . 'admin/campaign-builder/ajax-actions.php';
			}

			$template_data = ( new Charitable_Campaign_Builder_Templates() )->get_template_data( $template_id );
			$template_data = is_array( $template_data ) ? $template_data : array();
			$meta          = isset( $template_data['meta'] ) && is_array( $template_data['meta'] ) ? $template_data['meta'] : array();
			$layout_source = isset( $template_data['layout'] ) && is_array( $template_data['layout'] ) ? $template_data['layout'] : array();

			$settings = charitable_template_layout_to_campaign_layout(
				array(),
				array( 'template_id' => array( 'value' => $template_id ) )
			);

			$settings = $this->populate_visual_layout_field_ids( $settings, $layout_source );
			$settings = $this->populate_visual_tabs( $settings, $layout_source );

			if ( ! isset( $settings['layout']['advanced'] ) || ! is_array( $settings['layout']['advanced'] ) ) {
				$settings['layout']['advanced'] = array();
			}

			/*
			 * The AI's story (or '' when none was given) — never the template's
			 * sample. apply_visual_content_overlay() below writes it to BOTH the
			 * campaign-description field's own content and, via $general further
			 * down, settings.general.description, the way a real builder save keeps
			 * the two in step, and clears the template's sample text, html and
			 * photo placeholders so nothing fake ships beside the AI's content. If
			 * image_url was given it is fetched and set as the hero photo; the
			 * outcome is returned so create-campaign can report image_attached.
			 */
			$ai_description = ( isset( $parsed['description'] ) && '' !== $parsed['description'] ) ? (string) $parsed['description'] : '';

			/*
			 * Only fetch image_url when the template actually has somewhere to
			 * put it — a `photo` field OR a `campaign-hero` field (Beacon's hero
			 * image, a Lite-only field type; see visual_layout_has_hero_field()).
			 * Otherwise the download and the attachment would be wasted and
			 * orphaned, and the "attached" report would be a lie. A template
			 * with neither reports the image as not attached, with a reason, and
			 * nothing is fetched.
			 */
			if ( '' !== (string) $image_url && ! $this->visual_layout_has_photo_field( $settings ) && ! $this->visual_layout_has_hero_field( $settings ) ) {
				$image = array(
					'attached' => false,
					'url'      => '',
					'error'    => __( 'This template has no photo or hero field to place an image in.', 'charitable' ),
				);
			} else {
				$image = $this->sideload_campaign_image( $image_url, $campaign_id );
			}

			$settings = $this->apply_visual_content_overlay( $settings, $ai_description, $image );

			/*
			 * Maps template.meta.colors (verified 1:1 against the saved
			 * color_base_* fields) onto BOTH color_base_* and
			 * layout.advanced.theme_color_* — the same two targets, and the
			 * same hex-validation, ajax-actions.php's own $color_bridge uses
			 * (the "Bridge the four route-backed Global Colors" block) when a
			 * real Styles-tab save writes them, so a freshly created visual
			 * campaign already carries the colors the campaign-CSS endpoint
			 * and the style-token migration path both read.
			 */
			$colors    = isset( $meta['colors'] ) && is_array( $meta['colors'] ) ? $meta['colors'] : array();
			$color_map = array(
				'primary'   => array( 'color_base_primary', 'theme_color_primary' ),
				'secondary' => array( 'color_base_secondary', 'theme_color_secondary' ),
				'tertiary'  => array( 'color_base_tertiary', 'theme_color_tertiary' ),
				'button_bg' => array( 'color_base_button', 'theme_color_button' ),
			);

			foreach ( $color_map as $template_key => $targets ) {
				if ( empty( $colors[ $template_key ] ) ) {
					continue;
				}

				$hex = '#' . ltrim( (string) $colors[ $template_key ], '#' );

				if ( ! preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex ) ) {
					continue;
				}

				$settings[ $targets[0] ]                       = $hex;
				$settings['layout']['advanced'][ $targets[1] ] = $hex;
			}

			$currency_helper = charitable_get_currency_helper();

			$general = array(

				/*
				 * settings.general.description and the campaign-description field's
				 * own content are both set to $ai_description by
				 * apply_visual_content_overlay() above — the AI's story, or '' when
				 * none was given, never the template's sample. Keeping the two in
				 * step is what a real Campaign Builder save always does, so the
				 * front-end renderer's fallback from general.description to the
				 * field's own content never surfaces a stale template default.
				 */
				'description' => $ai_description,
				'end_date'    => isset( $parsed['end_date'] ) ? $parsed['end_date'] : '',
			);

			if ( array_key_exists( 'goal', $parsed ) ) {
				/*
				 * Mirrors ajax-actions.php's own real-save-path formatting of
				 * settings.general.goal exactly (charitable_save_campaign(),
				 * the "Sanitize some misc fields" block): a locale-formatted
				 * display string, not the bare canonical float _campaign_goal
				 * is stored as. This is what the Settings > General goal text
				 * field's value attribute expects.
				 */
				$general['goal'] = number_format(
					(float) $parsed['goal'],
					(int) $currency_helper->get_decimals(),
					$currency_helper->get_decimal_separator(),
					$currency_helper->get_thousands_separator()
				);
			}

			$donation_options = array(
				'allow_custom_donations' => empty( $parsed['allow_custom_amount'] ) ? '0' : '1',
			);

			if ( isset( $parsed['suggested_amounts'] ) && is_array( $parsed['suggested_amounts'] ) ) {
				$amounts = array();

				foreach ( array_values( $parsed['suggested_amounts'] ) as $index => $row ) {
					$amounts[ $index ] = array(
						'amount'      => $row['amount'],
						'description' => isset( $row['description'] ) ? $row['description'] : '',
					);
				}

				$donation_options['donation_amounts'] = $amounts;
			}

			$settings['settings'] = array(
				'general'          => $general,
				'donation-options' => $donation_options,
			);

			$settings['id']                = (string) $campaign_id;
			$settings['template_id']       = $template_id;
			$settings['template_label']    = isset( $meta['label'] ) ? (string) $meta['label'] : $template_id;
			$settings['form_saved']        = (string) round( microtime( true ) * 1000 );
			$settings['post_status']       = $status;
			$settings['post_status_label'] = ucfirst( $status );
			/* Must match post_title exactly — see the class docblock's rename-bug note. */
			$settings['title']    = $title;
			$settings['field_id'] = charitable_find_highest_field_key( $settings );

			update_post_meta( $campaign_id, 'campaign_settings_v2', $settings );

			return array(
				'attached' => (bool) $image['attached'],
				'error'    => isset( $image['error'] ) ? $image['error'] : null,
			);
		}

		/**
		 * Fill the field-id linkage charitable_template_layout_to_campaign_layout()
		 * leaves empty when called standalone — see write_visual_campaign_settings()'s
		 * docblock for how this gap was confirmed (empirically, by calling that
		 * function directly against real template data) rather than assumed.
		 *
		 * Walks $layout_source — Charitable_Campaign_Builder_Templates::get_template_data()'s
		 * own `layout` array, the same array charitable_template_layout_to_campaign_layout()
		 * itself parses — in exactly the same row -> column -> section ->
		 * (fields OR tabs->tab->fields) order, with column/section counters
		 * that (matching that function's own loop) never reset per row, and a
		 * field-id counter that increments once per field encountered whether
		 * or not it has a usable `type`. That last detail matters: it is what
		 * keeps the ids this method assigns in agreement with the ids
		 * charitable_template_layout_to_campaign_layout()'s OWN field-copying
		 * loop already used to populate `$settings['fields']` — two separate
		 * walks of the same array, in the same order, landing on the same
		 * numbers, rather than a second, disagreeing numbering scheme.
		 *
		 * This is template PHP data, not browser JS or a panel class's inline
		 * render fallback — reading it to answer "which field ids belong to
		 * this section" is squarely the "structurally hard part" Task 17's
		 * investigation names as reusable PHP, not a reimplementation of
		 * anything JS-authored.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $settings      charitable_template_layout_to_campaign_layout()'s
		 *                               return value.
		 * @param  array $layout_source The template's raw `layout` array.
		 * @return array $settings, with every row/section/tab field id list filled in.
		 */
		private function populate_visual_layout_field_ids( array $settings, array $layout_source ) {
			$column_id  = 0;
			$section_id = 0;
			$field_id   = 0;

			foreach ( $layout_source as $row_id => $row ) {
				if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
					continue;
				}

				foreach ( $row['columns'] as $column ) {
					if ( ! is_array( $column ) ) {
						continue;
					}

					foreach ( $column as $section ) {
						$section_type = isset( $section['type'] ) ? $section['type'] : '';

						if ( 'fields' === $section_type && ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) {
							$ids = array();

							foreach ( $section['fields'] as $field_data ) {
								$type = is_array( $field_data ) && ! empty( $field_data['type'] ) ? sanitize_key( $field_data['type'] ) : '';

								if ( '' !== $type ) {
									$ids[] = $field_id;
									$settings['layout']['rows'][ $row_id ]['fields'][ $field_id ] = $type;
								}

								++$field_id;
							}

							$settings['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['fields'] = $ids;
						} elseif ( 'tabs' === $section_type && ! empty( $section['tabs'] ) && is_array( $section['tabs'] ) ) {
							foreach ( $section['tabs'] as $tab_index => $tab ) {
								$tab_ids = array();

								if ( ! empty( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
									foreach ( $tab['fields'] as $field_data ) {
										$type = is_array( $field_data ) && ! empty( $field_data['type'] ) ? sanitize_key( $field_data['type'] ) : '';

										if ( '' !== $type ) {
											$tab_ids[] = $field_id;
											$settings['layout']['rows'][ $row_id ]['fields'][ $field_id ] = $type;
										}

										++$field_id;
									}
								}

								$settings['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['tabs'][ $tab_index ]['fields'] = $tab_ids;
							}
						}

						++$column_id;
						++$section_id;
					}
				}
			}

			return $settings;
		}

		/**
		 * Derive the top-level `tabs` map (and `tab_order`) from the template's
		 * own `tabs`-type sections.
		 *
		 * Content.php reads `$campaign_data['tabs'][ $tab_id ]` directly (for
		 * `title`/`type`/`desc`/`visible_nav`) wherever a `tabs`-type section
		 * renders — an id present in `layout.rows[...]sections[...]['tabs']`
		 * with no matching top-level `tabs` entry would be an undefined-array-key
		 * warning on the front end, not a graceful degrade. Mirrors
		 * ajax-actions.php's own `$campaign_settings_v2['tab_order'] =
		 * array_keys( $campaign_settings_v2['tabs'] )` exactly.
		 *
		 * Known limitation, inherited from the underlying data model rather than
		 * introduced here: a tab's numeric key is LOCAL to its own `tabs`
		 * section (each section's `tabs` list is independently zero-indexed by
		 * the template), so a template with more than one `tabs`-type section
		 * whose local tab indexes collide would have one overwrite the other
		 * here. Not a risk for this task's own test template (animal-sanctuary
		 * declares exactly one `tabs` section), and not a new defect: the real
		 * save path's `tab_order = array_keys( $campaign_settings_v2['tabs'] )`
		 * line makes the identical single-namespace assumption.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $settings      charitable_template_layout_to_campaign_layout()'s
		 *                               return value (as already amended by
		 *                               populate_visual_layout_field_ids()).
		 * @param  array $layout_source The template's raw `layout` array.
		 * @return array $settings, with `tabs`/`tab_order` filled in when the
		 *                template declares any tabs.
		 */
		private function populate_visual_tabs( array $settings, array $layout_source ) {
			$tabs = array();

			foreach ( $layout_source as $row ) {
				if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
					continue;
				}

				foreach ( $row['columns'] as $column ) {
					if ( ! is_array( $column ) ) {
						continue;
					}

					foreach ( $column as $section ) {
						if ( empty( $section['type'] ) || 'tabs' !== $section['type'] || empty( $section['tabs'] ) || ! is_array( $section['tabs'] ) ) {
							continue;
						}

						foreach ( $section['tabs'] as $tab_id => $tab_info ) {
							$tabs[ $tab_id ] = array(
								'title' => isset( $tab_info['title'] ) ? sanitize_text_field( $tab_info['title'] ) : '',
							);
						}
					}
				}
			}

			if ( ! empty( $tabs ) ) {
				$settings['tabs']      = $tabs;
				$settings['tab_order'] = array_keys( $tabs );
			}

			return $settings;
		}

		/**
		 * Whether the built visual layout has at least one `photo` field.
		 *
		 * Used before fetching to decide whether image_url has anywhere to go
		 * (see write_visual_campaign_settings()). A template with no photo
		 * field must not trigger a download that would only orphan the
		 * attachment.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $settings campaign_settings_v2, with `fields` populated.
		 * @return bool
		 */
		private function visual_layout_has_photo_field( array $settings ) {
			if ( empty( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
				return false;
			}

			foreach ( $settings['fields'] as $field ) {
				if ( is_array( $field ) && isset( $field['type'] ) && 'photo' === $field['type'] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Whether the built visual layout has at least one `campaign-hero` field.
		 *
		 * Lite-only — Pro has no `campaign-hero` field type or Beacon templates,
		 * so this has no Pro counterpart to stay in sync with. Used both to
		 * decide whether image_url has anywhere to go (alongside
		 * visual_layout_has_photo_field(), see write_visual_campaign_settings())
		 * and, inside apply_visual_content_overlay(), to decide whether a
		 * sideloaded image is routed to the hero's background_image instead of a
		 * `photo` field.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $settings campaign_settings_v2, with `fields` populated.
		 * @return bool
		 */
		private function visual_layout_has_hero_field( array $settings ) {
			if ( empty( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
				return false;
			}

			foreach ( $settings['fields'] as $field ) {
				if ( is_array( $field ) && isset( $field['type'] ) && 'campaign-hero' === $field['type'] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Overlay the AI's content onto a freshly built visual layout, and clear
		 * the template's sample copy.
		 *
		 * A template layout arrives with its own sample story, headlines, HTML and
		 * photos — placeholder content meant to be replaced before publishing.
		 * Left in place it renders next to whatever the AI wrote, which is exactly
		 * the confusion reported from the field. So on create we:
		 *
		 * - set every `campaign-description` field's content to $ai_description
		 *   (the AI's story, or '' when none was given — never the sample);
		 * - blank every `text` field's content and headline, and every `html`
		 *   field's content, keeping the slot so the builder still shows it;
		 * - blank every `campaign-hero` field's `background_image`,
		 *   `avatar_image` and `title_override` — the Beacon templates hardcode
		 *   all three to sample values (a stock photo, a stock avatar, and the
		 *   literal headline "Save Maple Grove Park"). `accent_color`,
		 *   `show_raised`, `enable_recurring`, the `onetime_*` amount config and
		 *   `cta_label` are left alone; those are structural/config, not sample
		 *   copy, the same distinction `photo`'s own untouched properties make;
		 * - clear every `photo` field's image, EXCEPT the one field this create
		 *   call's sideloaded image_url (if any) is routed to. Every photo also
		 *   loses its template `default`, so a cleared one falls back to the
		 *   neutral placeholder, not a stock photo.
		 *
		 * Image routing — LITE DEVIATION FROM PRO, documented here the way
		 * style_color_map() documents its own Lite/Pro divergence above: Pro has
		 * no `campaign-hero` field or Beacon templates at all, so Pro's own
		 * image_url routing (always the first `photo` field) has nothing to say
		 * about a hero. Per an explicit decision from the project owner, Lite
		 * routes the sideloaded image to the FIRST `campaign-hero` field's
		 * `background_image` whenever the template has one, and only falls back
		 * to the first `photo` field when it does not. Blanking the hero without
		 * routing the image there would leave an empty hero — worse than the
		 * sample it replaced, since the hero is the first thing a visitor sees,
		 * and Beacon's two templates (beacon-split, beacon-single) are pinned to
		 * the top of List Campaign Templates, so this is what an assistant
		 * picking the first template gets.
		 *
		 * Structural/dynamic fields (title, summary, progress bar, donate button,
		 * social, etc.) are left untouched — they carry no free-text sample copy.
		 *
		 * @since 1.8.13
		 *
		 * @param  array  $settings       campaign_settings_v2, with `fields` populated.
		 * @param  string $ai_description The resolved description ('' when none).
		 * @param  array  $image          sideload_campaign_image()'s return.
		 * @return array $settings, with sample content cleared and the AI's in place.
		 */
		private function apply_visual_content_overlay( array $settings, $ai_description, array $image ) {
			if ( empty( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
				return $settings;
			}

			$route_to_hero = $this->visual_layout_has_hero_field( $settings );
			$image_placed  = false;

			foreach ( $settings['fields'] as $key => $field ) {
				if ( ! is_array( $field ) || empty( $field['type'] ) ) {
					continue;
				}

				switch ( $field['type'] ) {
					case 'campaign-description':
						$settings['fields'][ $key ]['content'] = $ai_description;

						/*
						 * And its HEADLINE, which this case used to leave alone.
						 *
						 * The template ships one - Beacon (1 Column) carries
						 * "Support Hometown Heroes for Maple Grove" - and setting
						 * only `content` left that sitting above the AI's story.
						 * Reported from 1.8.13 testing as leftover template sample
						 * text in the story heading, and it is the literal example
						 * the testing guide names as must-not-ship.
						 *
						 * Blanked rather than replaced with the title, matching the
						 * `text` case below, which has always cleared both. This
						 * method's own docblock already said "blank every text
						 * field's content and headline"; campaign-description was
						 * simply missed.
						 *
						 * NOT a blanket clear of every field with a headline:
						 * `donation-wall` ships "Donor Wall" and `donate-amount`
						 * ships "Select An Amount", which are functional labels
						 * rather than placeholder prose, and blanking those would
						 * publish an unlabelled donation form.
						 */
						if ( array_key_exists( 'headline', $field ) ) {
							$settings['fields'][ $key ]['headline'] = '';
						}
						break;

					case 'text':
						$settings['fields'][ $key ]['content'] = '';
						if ( array_key_exists( 'headline', $field ) ) {
							$settings['fields'][ $key ]['headline'] = '';
						}
						break;

					case 'html':
						$settings['fields'][ $key ]['content'] = '';
						break;

					case 'campaign-hero':
						$settings['fields'][ $key ]['background_image'] = '';
						$settings['fields'][ $key ]['avatar_image']     = '';
						$settings['fields'][ $key ]['title_override']   = '';

						if ( $route_to_hero && ! $image_placed && ! empty( $image['attached'] ) && '' !== (string) $image['url'] ) {
							$settings['fields'][ $key ]['background_image'] = (string) $image['url'];
							$image_placed                                   = true;
						}
						break;

					case 'photo':
						unset( $settings['fields'][ $key ]['default'] );

						if ( ! $route_to_hero && ! $image_placed && ! empty( $image['attached'] ) && '' !== (string) $image['url'] ) {
							$settings['fields'][ $key ]['file'] = (string) $image['url'];
							$image_placed                        = true;
						} else {
							$settings['fields'][ $key ]['file'] = '';
						}
						break;
				}
			}

			return $settings;
		}

		/**
		 * Fetch image_url into the Media Library and attach it to the campaign.
		 *
		 * Runs only inside create-campaign, a write ability, so it is already
		 * behind the AI-write gate and never fetches anything on a read-only site.
		 * A remote fetch is an SSRF surface, so the URL must be a public http(s)
		 * URL whose RESOLVED ADDRESS this class checks itself — see
		 * validate_image_url_address(), which is applied both to the supplied URL
		 * and to every redirect target — on top of wp_http_validate_url() and
		 * reject_unsafe_urls, which are kept as core's own redundant layer. The
		 * download is size- and time-capped, and the stored file extension is
		 * always derived from the real image bytes — never the URL — and rejected
		 * outright when it is not JPEG/PNG/GIF/WebP. Any failure is non-fatal:
		 * the campaign is still created, and the caller reports
		 * image_attached: false with a short reason.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $url         The caller-supplied image_url ('' to skip).
		 * @param  int    $campaign_id The campaign to attach the image to.
		 * @return array { attached: bool, url: string, id?: int, error: string|null }
		 */
		private function sideload_campaign_image( $url, $campaign_id ) {
			$url = trim( (string) $url );

			if ( '' === $url ) {
				return array( 'attached' => false, 'url' => '', 'error' => null );
			}

			$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );

			if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
				return array( 'attached' => false, 'url' => '', 'error' => __( 'image_url must be a public http or https URL.', 'charitable' ) );
			}

			/*
			 * Our OWN resolved-address check, BEFORE wp_http_validate_url(), and
			 * deliberately not delegated to it. Two live SSRF findings in the
			 * 1.8.13 audit are both cases where core's check does not hold:
			 *
			 * M-1: core only began rejecting link-local (169.254.0.0/16 — the
			 * cloud instance-metadata endpoint) in WordPress 7.0, and this
			 * plugin's declared floor is far below that, so on a 6.9.x site
			 * http://169.254.169.254/latest/meta-data/ was fetched successfully.
			 *
			 * M-2: wp_http_validate_url() skips its address check ENTIRELY when
			 * the requested host matches the site's own host (its `$same_host`
			 * carve-out), so on a fully patched 7.1.2
			 * http://<site-host>:8080/ok.png reached a service bound only to
			 * 127.0.0.1 and produced a real attachment.
			 *
			 * Running first also means every rejected address reports THIS
			 * ability's own single message rather than core's, which is what the
			 * tests assert to distinguish "our guard ran" from "core's redundant
			 * layer happened to catch it too".
			 */
			/*
			 * THE UPLOAD CAPABILITY, and it is checked FIRST — before the
			 * address guard, before any DNS resolution and before any HTTP
			 * request is made.
			 *
			 * Sideloading ends in media_handle_sideload(), which writes a file
			 * into the Media Library and performs NO capability check of its
			 * own: every other route to that behaviour in WordPress gates on
			 * `upload_files` before calling it
			 * (WP_REST_Attachments_Controller::create_item_permissions_check(),
			 * wp-admin/media-new.php, wp-admin/async-upload.php). This ability
			 * never asked for it, so a caller holding `edit_campaigns` alone
			 * wrote publicly served files into wp-content/uploads. Confirmed by
			 * execution: HTTP 200 and a new attachment for a caller whose
			 * current_user_can( 'upload_files' ) was false.
			 *
			 * Why it was invisible: permission_create_campaign() gates on
			 * `edit_campaigns`, and BOTH stock roles holding that — administrator
			 * and campaign_manager — are granted `upload_files` by
			 * Charitable_Roles::add_roles(). On a default install every caller who
			 * can reach this code happens to be allowed to upload. The exposed
			 * caller is a role with `edit_campaigns` and not `upload_files`, which
			 * is exactly what Charitable Ambassadors grants its `campaign_creator`
			 * role automatically to any visitor who starts a campaign through the
			 * front-end form — see the note in permission_donations().
			 *
			 * Refused HERE rather than in permission_create_campaign() on
			 * purpose. The image is one optional part of the operation, and the
			 * documented contract is that a campaign is still created with
			 * image_attached false when the photo cannot be fetched. Failing the
			 * whole call would break campaign creation for a caller who is
			 * entitled to it, and would tell an assistant it cannot create
			 * campaigns at all. Checking before the fetch also means an
			 * unauthorised caller cannot use this path to probe addresses.
			 *
			 * ⚠️ PRO SHOULD ADOPT THIS. Pro 1.8.18 carries the same sideload with
			 * the same missing check.
			 *
			 * Guarded by
			 * test_sideloading_a_photo_requires_the_upload_capability(), with
			 * test_a_caller_with_the_upload_capability_still_sideloads() as the
			 * control.
			 */
			if ( ! current_user_can( 'upload_files' ) ) {
				return array(
					'attached' => false,
					'url'      => '',
					'error'    => __( 'You do not have permission to upload files, so the image could not be added to the Media Library. The campaign was created without it.', 'charitable' ),
				);
			}

			$address = $this->validate_image_url_address( $url );

			if ( is_wp_error( $address ) ) {
				return array( 'attached' => false, 'url' => '', 'error' => $address->get_error_message() );
			}

			if ( ! wp_http_validate_url( $url ) ) {
				return array( 'attached' => false, 'url' => '', 'error' => __( 'image_url must be a public http or https URL.', 'charitable' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$timeout   = (int) apply_filters( 'charitable_create_campaign_image_timeout', 15 );
			$max_bytes = (int) apply_filters( 'charitable_create_campaign_image_max_bytes', 8 * MB_IN_BYTES );

			/*
			 * Re-validate EVERY hop, not just the URL we were handed, AND bound
			 * the transfer itself rather than judging its size only after the
			 * fact. download_url() streams straight to disk (no in-PHP-memory
			 * risk from the body itself), but with no limit_response_size the
			 * whole attacker-supplied response still lands in the temp
			 * directory before the byte-cap check below ever runs — disk use
			 * bounded only by the timeout. Requests\Transport\Curl::stream_body()
			 * honours response_byte_limit (what limit_response_size becomes)
			 * BEFORE each fwrite(), so this caps the write itself, not just the
			 * post-hoc filesize() check.
			 *
			 * The +1 is deliberate: it lets an oversized file arrive at exactly
			 * cap+1 bytes, so the existing `> $max_bytes` comparison below still
			 * reports "larger than the allowed size" instead of a confusing
			 * truncated-image error for a file that was cut off mid-stream at
			 * precisely the cap.
			 *
			 * reject_unsafe_urls closes the redirect SSRF hole: download_url()
			 * follows redirects with the unsafe wp_remote_get() by default, so a
			 * URL that passes wp_http_validate_url() above could still 302 to a
			 * private or loopback address; this makes WP_Http run
			 * wp_http_validate_url() on each redirect target too. Both args are
			 * scoped to this one fetch and removed immediately after.
			 */
			$harden = static function ( $args ) use ( $max_bytes ) {
				$args['reject_unsafe_urls']  = true;
				$args['limit_response_size'] = $max_bytes + 1;
				return $args;
			};

			/*
			 * Re-run validate_image_url_address() on every redirect TARGET, not
			 * just the URL we were handed. M-2 is reachable through a 302 from a
			 * public URL, so a pre-flight-only check leaves the hole wide open,
			 * and reject_unsafe_urls above inherits the very same_host carve-out
			 * that M-2 exploits (WP_Http::validate_redirects() calls
			 * wp_http_validate_url() on each hop).
			 *
			 * `requests-requests.before_redirect` is the WordPress action that
			 * WP_HTTP_Requests_Hooks::dispatch() maps the Requests library's own
			 * `requests.before_redirect` hook onto, fired by
			 * WpOrg\Requests\Requests::parse_response() with the already-absolute
			 * redirect target as its first argument. Throwing a
			 * WpOrg\Requests\Exception from it is exactly how core's own
			 * validate_redirects() aborts an unsafe hop: it unwinds out of
			 * Requests::request() into WP_Http::request()'s
			 * `catch ( WpOrg\Requests\Exception $e )`, which turns it into a
			 * WP_Error rather than a fatal.
			 *
			 * One consequence of reaching the hop through a WordPress action
			 * rather than the Requests hook object core uses: WP_Hook does its
			 * iteration bookkeeping after the callback loop, so throwing skips
			 * it and leaves this hook's nesting level raised for the rest of
			 * the request. Harmless in practice, and asserted rather than
			 * assumed — test_visual_mode_image_url_redirecting_to_a_blocked_address_is_rejected()
			 * runs a second sideload after a refused redirect and requires it
			 * to succeed.
			 *
			 * NOTE ON WHICH LAYER ACTUALLY REFUSES A HOP. Core registers its own
			 * validate_redirects() on the Requests hook object, and
			 * WP_HTTP_Requests_Hooks::dispatch() runs parent::dispatch() BEFORE
			 * the WordPress action - so for a redirect to a plainly private
			 * address on a default site, core throws first and the guard below
			 * never runs. It is reached where core does not refuse: the
			 * same_host carve-out of finding M-2, which is precisely the gap
			 * this exists to close. A hop refused by core therefore reports
			 * download_url()'s generic "could not be downloaded", not the
			 * address message; that is the pre-existing generic failure string
			 * rather than a new distinguishable outcome, so it does not widen
			 * the oracle.
			 *
			 * $blocked_redirect exists for the hops this guard DOES refuse: it
			 * makes those report the same single refusal a blocked pre-flight
			 * address reports, instead of being separable from it.
			 *
			 * The scheme is re-checked here as well as the address. Core's
			 * validate_redirects() does check it, but the entire point of this
			 * fix is not to depend on that, and $harden sets reject_unsafe_urls
			 * at priority 10 where a later site filter could unset it - which
			 * would otherwise leave a hop to gopher:// or ftp:// unchecked.
			 */
			$blocked_redirect = false;

			$guard_redirects = function ( $location ) use ( &$blocked_redirect ) {
				$location = (string) $location;
				$scheme   = strtolower( (string) wp_parse_url( $location, PHP_URL_SCHEME ) );

				if ( in_array( $scheme, array( 'http', 'https' ), true )
					&& ! is_wp_error( $this->validate_image_url_address( $location ) ) ) {
					return;
				}

				$blocked_redirect = true;

				/*
				 * esc_html() at each throw, not once on $message above: this
				 * message does not stop here - the HTTP layer catches the
				 * exception and turns it into a WP_Error whose message can reach
				 * an admin notice. Escaping at the throw site is also what the
				 * EscapeOutput sniff can actually see; escaping the variable
				 * earlier leaves the throw looking unescaped to static analysis.
				 */
				$message = $this->image_address_error()->get_error_message();

				if ( class_exists( 'WpOrg\Requests\Exception' ) ) {
					throw new \WpOrg\Requests\Exception( esc_html( $message ), 'charitable.image_url_address_not_allowed' );
				}

				throw new \Requests_Exception( esc_html( $message ), 'charitable.image_url_address_not_allowed' );
			};

			add_filter( 'http_request_args', $harden );
			add_action( 'requests-requests.before_redirect', $guard_redirects ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			/*
			 * try/finally, not two bare statements: the redirect guard above
			 * THROWS, and although WP_Http::request() catches that exception on
			 * every version that has the Abilities API, a scoped hook that can
			 * be left registered by an unwinding stack would silently apply to
			 * unrelated HTTP requests for the rest of the page load.
			 */
			try {
				$tmp = download_url( $url, $timeout );
			} finally {
				remove_action( 'requests-requests.before_redirect', $guard_redirects ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				remove_filter( 'http_request_args', $harden );
			}

			if ( is_wp_error( $tmp ) ) {
				if ( $blocked_redirect ) {
					return array( 'attached' => false, 'url' => '', 'error' => $this->image_address_error()->get_error_message() );
				}

				return array( 'attached' => false, 'url' => '', 'error' => __( 'image_url could not be downloaded.', 'charitable' ) );
			}

			if ( (int) filesize( $tmp ) > $max_bytes ) {
				wp_delete_file( $tmp );
				return array( 'attached' => false, 'url' => '', 'error' => __( 'image_url is larger than the allowed size.', 'charitable' ) );
			}

			$info = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( false === $info || empty( $info['mime'] ) ) {
				wp_delete_file( $tmp );
				return array( 'attached' => false, 'url' => '', 'error' => __( 'image_url did not resolve to an image.', 'charitable' ) );
			}

			/*
			 * The 8 MB byte cap above bounds the FILE on disk, not the DECODED
			 * pixel buffer — the compression ratio is entirely attacker
			 * controlled. A hand-built PNG declaring 20000x20000 dimensions is
			 * ~45 bytes on disk (well under the byte cap) but is 400,000,000
			 * pixels; feeding that to imagecreatefromstring() (as
			 * media_handle_sideload()'s downstream image-editor calls eventually
			 * do) allocates the decoded bitmap via libgd, OUTSIDE PHP's own
			 * allocator — so PHP's memory_limit gives no protection and the
			 * request is OOM-killed rather than failing gracefully. Measured
			 * directly: a 20000x20000 decompression bomb this size drove RSS to
			 * ~1.9 GB in well under a second. getimagesize() already reports the
			 * declared dimensions in $info[0]/$info[1] without decoding a single
			 * pixel, so this check is free.
			 *
			 * 25 MP, not 50 MP: extrapolating from that measured ~5 bytes of RSS
			 * per pixel (400 MP -> ~1900 MB), 50 MP is still roughly 240-260 MB
			 * of libgd allocation before WordPress's own subsize-generation loop
			 * (wp_generate_attachment_metadata()) adds more on top — leaving
			 * only ~250 MB of headroom in a 512 MB cgroup, and considerably less
			 * on shared hosting where several PHP-FPM workers share one. 25 MP
			 * roughly halves that to ~130 MB.
			 *
			 * This is DELIBERATELY set below flagship-phone resolution — an
			 * iPhone's 48 MP photo is 8064x6048 = 48.8 MP, over this cap — NOT
			 * an oversight to "fix" by raising the number back toward 50 MP.
			 * The project owner was shown that exact tradeoff and chose the
			 * lower, safer cap anyway, accepting that some legitimate photos
			 * will need resizing first. Because a real user will hit this, not
			 * only an attacker, the rejection message below states the limit
			 * and tells the caller to resize, rather than only saying "refused."
			 */
			$max_pixels = (int) apply_filters( 'charitable_create_campaign_image_max_pixels', 25000000 ); // 25 MP.

			if ( ( (int) $info[0] * (int) $info[1] ) > $max_pixels ) {
				wp_delete_file( $tmp );
				return array(
					'attached' => false,
					'url'      => '',
					'error'    => __( 'image_url is too large in pixel dimensions; images up to about 25 megapixels are accepted, so resizing it will let it through.', 'charitable' ),
				);
			}

			$ext = $this->image_extension_for_mime( (string) $info['mime'] );

			if ( '' === $ext ) {
				wp_delete_file( $tmp );
				return array( 'attached' => false, 'url' => '', 'error' => __( 'image_url must be a JPEG, PNG, GIF or WebP image.', 'charitable' ) );
			}

			/*
			 * Name the file from the URL's basename but ALWAYS with the extension
			 * derived from the real bytes, never the one in the URL. A URL ending
			 * .php (or with no extension) that carries real image bytes is stored
			 * as .png/.jpg and passes media_handle_sideload()'s mime check, and a
			 * mismatched or executable extension can never ride in from the URL.
			 */
			$base = preg_replace( '/\.[^.]*$/', '', wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
			$base = '' !== (string) $base ? $base : 'campaign-image';
			$name = sanitize_file_name( $base . '.' . $ext );

			$file_array = array(
				'name'     => $name,
				'tmp_name' => $tmp,
			);

			$attachment_id = media_handle_sideload( $file_array, (int) $campaign_id );

			if ( is_wp_error( $attachment_id ) ) {
				wp_delete_file( $tmp );
				return array( 'attached' => false, 'url' => '', 'error' => __( 'image_url could not be added to the Media Library.', 'charitable' ) );
			}

			return array(
				'attached' => true,
				'url'      => (string) wp_get_attachment_url( $attachment_id ),
				'id'       => (int) $attachment_id,
				'error'    => null,
			);
		}

		/**
		 * The ONE refusal every rejected image_url address returns.
		 *
		 * Deliberately a single code and a single message for loopback,
		 * link-local, RFC1918, unique-local, unspecified, broadcast,
		 * IPv4-mapped IPv6, an unresolvable host and a host with no address at
		 * all — pre-flight and mid-redirect alike. The 1.8.13 audit logged a
		 * blind-SSRF oracle (six distinguishable errors plus timing) as a
		 * separate Low, so the address check must not add a family of
		 * distinguishable outcomes on top of it: a caller learns that the
		 * address it supplied is refused and nothing whatsoever about what is
		 * or is not listening behind it. No IP, hostname or port is echoed
		 * back, so nothing about internal topology is disclosed.
		 *
		 * It is a WP_Error, not an exception: sideload_campaign_image() converts
		 * it into the array contract its caller depends on, so a refused address
		 * never fails the whole create-campaign call.
		 *
		 * @since 1.8.13
		 *
		 * @return WP_Error
		 */
		private function image_address_error() {
			return new WP_Error(
				'charitable_image_url_address_not_allowed',
				__( 'image_url resolves to an address that is not allowed.', 'charitable' )
			);
		}

		/**
		 * Refuse an image_url whose host resolves to a non-public address.
		 *
		 * Checks the RESOLVED ADDRESS, never the hostname: a hostname allowlist
		 * or denylist is bypassed by any attacker who controls a DNS record, and
		 * wp_http_validate_url()'s host-string `$same_host` carve-out is exactly
		 * how audit finding M-2 reached a service bound to 127.0.0.1. There is
		 * therefore NO exemption here for the site's own host.
		 *
		 * Every address a host resolves to has to pass, not just the first one.
		 * core's wp_http_validate_url() uses gethostbyname(), which returns a
		 * single address, so a host publishing one public and one private A
		 * record satisfies it half the time; gethostbynamel() below returns the
		 * whole set, and AAAA records are read too because a host with a public
		 * A record and an AAAA of ::1 would otherwise pass while cURL, which
		 * prefers IPv6 on a dual-stack machine, connects to loopback.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $url The URL whose host is to be checked.
		 * @return true|WP_Error True when the address may be fetched.
		 */
		private function validate_image_url_address( $url ) {
			$host = (string) wp_parse_url( (string) $url, PHP_URL_HOST );

			/*
			 * parse_url() keeps the brackets on an IPv6 literal host
			 * ('[::1]'), which is not what inet_pton() or the resolver accept.
			 */
			if ( '' !== $host && '[' === substr( $host, 0, 1 ) && ']' === substr( $host, -1 ) ) {
				$host = substr( $host, 1, -1 );
			}

			$host = trim( $host, '.' );

			if ( '' === $host ) {
				return $this->image_address_error();
			}

			/*
			 * A DNS name cannot exceed 253 bytes, and `image_url` carries no
			 * maxLength in the input schema, so without this an over-long host
			 * reaches gethostbynamel() and makes it raise an E_WARNING -
			 * "Host name is too long, the limit is 255 characters", measured on
			 * both 7.4.33 and 8.4.17. A plain NXDOMAIN does NOT warn, so this
			 * over-length path is the only one that does.
			 *
			 * That matters because it is caller-controlled: under any
			 * warning-to-ErrorException handler - which error trackers and
			 * managed hosts commonly install - the warning escapes this method
			 * and fails the whole create-campaign call, breaking the
			 * "any failure is non-fatal" contract this method is built around.
			 * Refusing the host is also simply correct; no such name resolves.
			 */
			if ( strlen( $host ) > 253 ) {
				return $this->image_address_error();
			}

			$addresses = $this->resolve_image_url_host( $host );

			/*
			 * Fail CLOSED on a host that resolves to nothing. An unresolvable
			 * host cannot be fetched anyway, so refusing it costs nothing, and
			 * "resolution failed" must never be the path that skips the check.
			 */
			if ( empty( $addresses ) ) {
				return $this->image_address_error();
			}

			foreach ( $addresses as $address ) {
				$packed = $this->packed_ip( $address );

				if ( false === $packed ) {
					return $this->image_address_error();
				}

				if ( $this->image_address_is_public( $packed ) ) {
					continue;
				}

				/**
				 * Filters whether a non-public resolved address may be fetched
				 * by create-campaign's image sideload.
				 *
				 * Default is false — CLOSED — for every address in the loopback,
				 * link-local, private, unique-local, unspecified and broadcast
				 * ranges. A site that genuinely sideloads campaign images from an
				 * internal host (an intranet media server, a Docker service name)
				 * can opt that address back in here. Returning true is a
				 * deliberate re-opening of an SSRF path, so scope it to the exact
				 * address or host you mean rather than returning a bare true.
				 *
				 * @since 1.8.13
				 *
				 * @param bool   $allowed Whether to allow the address. Default false.
				 * @param string $address The resolved IP address, as a string.
				 * @param string $host    The host it was resolved from.
				 */
				if ( ! apply_filters( 'charitable_create_campaign_image_allow_internal_address', false, (string) $address, $host ) ) {
					return $this->image_address_error();
				}
			}

			return true;
		}

		/**
		 * Every address an image_url host resolves to.
		 *
		 * A literal IP host is returned as-is — it needs no lookup, and passing
		 * one to the resolver would be a needless network round trip. Note that
		 * the resolver is still what handles the non-dotted encodings of an IPv4
		 * address ('2130706433', '0x7f000001'), which filter_var() correctly
		 * refuses to call IP addresses: those fall through to gethostbynamel(),
		 * which resolves them to 127.0.0.1 and so is checked as loopback, and if
		 * the platform's resolver declines them instead the empty result fails
		 * closed in the caller.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $host The host to resolve.
		 * @return string[] Resolved IP addresses; empty when resolution failed.
		 */
		private function resolve_image_url_host( $host ) {
			/**
			 * Filters the addresses an image_url host resolves to, replacing
			 * resolution entirely.
			 *
			 * This is the resolution SEAM, not an allowlist: whatever it returns
			 * is still checked by validate_image_url_address(), and returning an
			 * empty array (or anything that is not an array) fails closed.
			 * Return null — the default — to resolve normally; return an array
			 * to supply the addresses and skip the lookup, which is how the unit
			 * tests drive one public hostname to a loopback address and back
			 * without touching DNS, and how a site behind a split-horizon
			 * resolver can supply the addresses its HTTP client will really use.
			 *
			 * @since 1.8.13
			 *
			 * @param string[]|null $addresses Addresses to use, or null to resolve.
			 * @param string        $host      The host being resolved.
			 */
			$addresses = apply_filters( 'charitable_create_campaign_image_resolved_ips', null, $host );

			if ( null === $addresses ) {
				$addresses = array();

				if ( false !== $this->packed_ip( $host ) ) {
					$addresses[] = $host;
				} else {
					// Suppressed like its dns_get_record() neighbour below: a
					// resolver diagnostic must never become the caller's problem.
					$ipv4 = @gethostbynamel( $host ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

					if ( is_array( $ipv4 ) ) {
						$addresses = array_merge( $addresses, $ipv4 );
					}

					/*
					 * gethostbynamel() is IPv4-only and there is no IPv6
					 * equivalent, so AAAA records need dns_get_record(). Some
					 * hosts disable it; when it is unavailable this falls back
					 * to checking the A records alone, which leaves an
					 * AAAA-only bypass on a dual-stack server. Documented
					 * rather than fixed by failing closed, because failing
					 * closed there would refuse EVERY sideload on such a host.
					 */
					if ( function_exists( 'dns_get_record' ) ) {
						$ipv6 = @dns_get_record( $host, DNS_AAAA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

						if ( is_array( $ipv6 ) ) {
							foreach ( $ipv6 as $record ) {
								if ( ! empty( $record['ipv6'] ) ) {
									$addresses[] = (string) $record['ipv6'];
								}
							}
						}
					}
				}
			}

			if ( ! is_array( $addresses ) ) {
				return array();
			}

			return array_values( array_unique( array_filter( array_map( 'strval', $addresses ) ) ) );
		}

		/**
		 * Pack an IP address string into its binary form.
		 *
		 * filter_var() gates inet_pton() rather than the reverse because
		 * inet_pton() raises an E_WARNING on a malformed address under PHP 7,
		 * and this plugin's PHP floor is 7.4. No range flags are passed — see
		 * image_address_is_public() for why the flags cannot be trusted on the
		 * floor — so this is purely "is this a syntactically valid IP".
		 *
		 * @since 1.8.13
		 *
		 * @param  string $address The address to pack.
		 * @return string|false Packed 4- or 16-byte address, or false.
		 */
		private function packed_ip( $address ) {
			$address = (string) $address;

			if ( false === filter_var( $address, FILTER_VALIDATE_IP ) ) {
				return false;
			}

			return inet_pton( $address );
		}

		/**
		 * Whether a packed IP address is one this ability may fetch from.
		 *
		 * Hand-rolled binary prefix maths rather than FILTER_VALIDATE_IP with
		 * FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE, because those
		 * flags DO NOT cover the required ranges on this plugin's PHP floor.
		 * Measured directly against real interpreters rather than assumed:
		 *
		 *   PHP 7.4.33  every IPv4-mapped and IPv4-compatible IPv6 form is
		 *               ALLOWED THROUGH — ::ffff:127.0.0.1, ::ffff:7f00:1,
		 *               ::ffff:10.0.0.1, ::ffff:169.254.169.254,
		 *               ::ffff:192.168.1.1 and ::127.0.0.1 all pass both flags.
		 *   PHP 7.3.33  the same six, plus every alternate spelling of IPv6
		 *               loopback and unspecified (0:0:0:0:0:0:0:1, ::0:1, 0::1,
		 *               0:0:0:0:0:0:0:0) — eleven wrong answers in total.
		 *   PHP 8.3.30, 8.4.7, 8.5.2  all correct.
		 *
		 * Relying on the flags would therefore reproduce in PHP precisely the
		 * defect this fix exists to remove from WordPress: protection whose
		 * coverage silently depends on the version underneath. The maths below
		 * behaves identically on every supported version.
		 *
		 * Normalising IPv4-in-IPv6 first is what closes that gap: every IPv6
		 * form that carries an IPv4 address in its low 32 bits is judged as
		 * that IPv4 address, because that is where the packet ends up.
		 *
		 * The IPv4 list is a deliberate SUPERSET of the one
		 * wp_http_validate_url() applies, not just the loopback/link-local/
		 * RFC1918/broadcast minimum. A guard whose whole justification is that
		 * core's coverage depends on the version underneath must not itself be
		 * narrower than core: on a WordPress 6.9 site - the floor the Abilities
		 * API requires, and the version finding M-1 was reproduced on - core's
		 * list is only 127/8, 10/8, 0/8, 172.16/12 and 192.168/16, so
		 * 100.100.100.200 (Alibaba Cloud's instance-metadata endpoint, inside
		 * CGNAT 100.64/10) would otherwise pass BOTH layers.
		 *
		 * 0.0.0.0/8 is rejected whole rather than only the single address
		 * 0.0.0.0: every address in it is "this network" (RFC 6890) and 0.x.y.z
		 * reaches local interfaces on Linux, which is the same treatment
		 * core's own check and FILTER_FLAG_NO_RES_RANGE give it. 240.0.0.0/4
		 * subsumes the 255.255.255.255 broadcast address.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $packed A 4- or 16-byte address from packed_ip().
		 * @return bool True when the address is publicly routable.
		 */
		private function image_address_is_public( $packed ) {
			$length = strlen( (string) $packed );

			if ( 16 === $length ) {
				return $this->ipv6_address_is_public( $packed );
			}

			if ( 4 !== $length ) {
				return false;
			}

			return $this->ipv4_address_is_public( $packed );
		}

		/**
		 * Whether a packed 4-byte IPv4 address is publicly routable.
		 *
		 * Every range wp_http_validate_url() rejects, so this can never be the
		 * weaker of the two layers on any WordPress version. See
		 * image_address_is_public() for why that matters.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $packed A 4-byte address.
		 * @return bool
		 */
		private function ipv4_address_is_public( $packed ) {
			if ( 4 !== strlen( (string) $packed ) ) {
				return false;
			}

			$octets = array_values( unpack( 'C4', $packed ) );

			$blocked = array(
				// "This network", including the unspecified address 0.0.0.0.
				0 === $octets[0],
				// Private use (RFC 1918).
				10 === $octets[0],
				172 === $octets[0] && $octets[1] >= 16 && $octets[1] <= 31,
				192 === $octets[0] && 168 === $octets[1],
				// Loopback.
				127 === $octets[0],
				// Link local, and AWS/GCP/Azure instance metadata.
				169 === $octets[0] && 254 === $octets[1],
				// Carrier-grade NAT, and Alibaba Cloud instance metadata.
				100 === $octets[0] && $octets[1] >= 64 && $octets[1] <= 127,
				// IETF protocol assignments.
				192 === $octets[0] && 0 === $octets[1] && 0 === $octets[2],
				// 6to4 relay anycast.
				192 === $octets[0] && 88 === $octets[1] && 99 === $octets[2],
				// Benchmarking.
				198 === $octets[0] && $octets[1] >= 18 && $octets[1] <= 19,
				// TEST-NET-1, TEST-NET-2, TEST-NET-3.
				192 === $octets[0] && 0 === $octets[1] && 2 === $octets[2],
				198 === $octets[0] && 51 === $octets[1] && 100 === $octets[2],
				203 === $octets[0] && 0 === $octets[1] && 113 === $octets[2],
				// Multicast.
				$octets[0] >= 224 && $octets[0] <= 239,
				// Reserved, including the 255.255.255.255 broadcast address.
				$octets[0] >= 240,
			);

			return ! in_array( true, $blocked, true );
		}

		/**
		 * Whether a packed 16-byte IPv6 address is publicly routable.
		 *
		 * Anything carrying an IPv4 address in its low 32 bits is handed to
		 * ipv4_address_is_public(), so ::ffff:169.254.169.254 and
		 * 64:ff9b::a9fe:a9fe are refused for the same reason 169.254.169.254
		 * is. The tunnelling and transition prefixes are refused wholesale
		 * rather than decoded: 6to4 and Teredo are deprecated, and none of
		 * these prefixes is a plausible host for a campaign photo, so the
		 * simpler check is also the safer one.
		 *
		 * @since 1.8.13
		 *
		 * @param  string $packed A 16-byte address.
		 * @return bool
		 */
		private function ipv6_address_is_public( $packed ) {
			if ( 16 !== strlen( (string) $packed ) ) {
				return false;
			}

			$low = substr( $packed, 12, 4 );

			$embedded_ipv4 = array(
				// ::ffff:0:0/96 — IPv4-mapped (RFC 4291).
				"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff",
				// ::ffff:0:0:0/96 — IPv4-translated (RFC 2765).
				"\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00\x00",
				// 64:ff9b::/96 — the NAT64 well-known prefix (RFC 6052).
				"\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00",
			);

			foreach ( $embedded_ipv4 as $prefix ) {
				if ( 0 === strncmp( $packed, $prefix, 12 ) ) {
					return $this->ipv4_address_is_public( $low );
				}
			}

			if ( 0 === strncmp( $packed, str_repeat( "\x00", 12 ), 12 ) ) {
				// :: (unspecified) and ::1 (loopback).
				if ( "\x00\x00\x00\x00" === $low || "\x00\x00\x00\x01" === $low ) {
					return false;
				}

				// ::a.b.c.d — the deprecated IPv4-compatible form.
				return $this->ipv4_address_is_public( $low );
			}

			$first  = ord( $packed[0] );
			$second = ord( $packed[1] );
			$third  = ord( $packed[2] );
			$fourth = ord( $packed[3] );

			$blocked = array(
				// fc00::/7 — unique local, the IPv6 counterpart of RFC 1918.
				0xfc === ( $first & 0xfe ),
				/*
				 * fe00::/8 as a whole: fe80::/10 is link local, fec0::/10 is
				 * site local (deprecated by RFC 3879 but still routed on some
				 * networks), and fe00::/9 is reserved. None is global unicast,
				 * so there is nothing legitimate to lose by refusing the lot.
				 */
				0xfe === $first,
				// ff00::/8 — multicast.
				0xff === $first,
				// 2002::/16 — 6to4, which tunnels to an embedded IPv4 address.
				0x20 === $first && 0x02 === $second,
				// 2001:0000::/32 — Teredo.
				0x20 === $first && 0x01 === $second && 0x00 === $third && 0x00 === $fourth,
				// 2001:db8::/32 — documentation.
				0x20 === $first && 0x01 === $second && 0x0d === $third && 0xb8 === $fourth,
			);

			return ! in_array( true, $blocked, true );
		}

		/**
		 * Map an image mime type to a file extension for the sideload filename.
		 *
		 * media_handle_sideload() validates the filename's extension against the
		 * real mime, so a URL ending in no extension (or a misleading one) needs a
		 * correct extension derived from the bytes to be accepted. Returns '' for
		 * anything outside the supported web-image set, which the caller treats as
		 * "unsupported image type" rather than forcing a wrong extension on, say,
		 * a BMP or TIFF (getimagesize already rejects SVG by returning false).
		 *
		 * @since 1.8.13
		 *
		 * @param  string $mime The mime reported by getimagesize().
		 * @return string A safe image extension, or '' when the type is unsupported.
		 */
		private function image_extension_for_mime( $mime ) {
			$map = array(
				'image/jpeg' => 'jpg',
				'image/png'  => 'png',
				'image/gif'  => 'gif',
				'image/webp' => 'webp',
			);

			return isset( $map[ $mime ] ) ? $map[ $mime ] : '';
		}

		/**
		 * Blank every `campaign-description` field's own `content`.
		 *
		 * Used by update-campaign when a caller passes an explicit `description:
		 * ''`: the blank is stored in settings.general.description, but
		 * Charitable_Field_Campaign_Description::render() falls back to the
		 * field's own content when general.description is empty, so the field's
		 * content has to be blanked too or the template's stale default keeps
		 * rendering. create-campaign clears this through
		 * apply_visual_content_overlay() instead, which sets every
		 * campaign-description field to the resolved description in one pass.
		 *
		 * @since 1.8.13
		 *
		 * @param  array $settings campaign_settings_v2, with `fields` populated.
		 * @return array $settings with every campaign-description field blanked.
		 */
		private function clear_visual_description_field_content( array $settings ) {
			if ( empty( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
				return $settings;
			}

			foreach ( $settings['fields'] as $field_id => $field ) {
				if ( is_array( $field ) && isset( $field['type'] ) && 'campaign-description' === $field['type'] ) {
					$settings['fields'][ $field_id ]['content'] = '';
				}
			}

			return $settings;
		}

		/**
		 * The `suggested_amounts` input schema, shared by create- and
		 * update-campaign.
		 *
		 * A UNION `type` rather than `oneOf`, deliberately. The contract suite's
		 * assert_schema_is_structurally_valid() requires a `type` key on every
		 * schema node including `items`, and a `oneOf` branch has none — so a
		 * oneOf here fails test_every_schema_is_valid_json_schema(). The union
		 * shape below was verified against rest_validate_value_from_schema()
		 * directly: bare numbers, numeric strings, objects and mixed arrays are
		 * accepted, while an object missing `amount`, an object carrying an
		 * unknown key, and a boolean are all rejected. When the value is a
		 * number, core's rest_handle_multi_type_schema() narrows `type` to
		 * `number` before validating, so `properties` does not bite.
		 *
		 * `string` is offered alongside `number` for the same reason `goal` is:
		 * a numeric string is a legitimate amount and parse_money() handles it.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function suggested_amounts_input_schema() {
			return array(
				'type'        => 'array',
				'description' => __( 'The preset amounts a donor can pick. Give a plain number for each, or an object with amount and description to set the caption a donor sees under it. A plain number KEEPS whatever description that amount already has, so you can change one amount without wiping the rest; pass an explicit empty description to clear one. An empty array means no preset amounts at all.', 'charitable' ),
				'items'       => array(
					'type'                 => array( 'number', 'string', 'object' ),
					'properties'           => array(
						'amount'      => array(
							'type'        => array( 'number', 'string' ),
							'description' => __( 'The amount, in the site currency.', 'charitable' ),
						),
						'description' => array(
							'type'        => 'string',
							'description' => __( 'The caption shown to a donor under this amount. An empty string clears it.', 'charitable' ),
						),
					),
					'required'             => array( 'amount' ),
					'additionalProperties' => false,
				),
			);
		}

		/**
		 * The campaign post statuses a caller may SET through create-campaign
		 * and set-campaign-status.
		 *
		 * Single source for both those abilities' `status` input_schema enum AND
		 * their execution-time validation, so the schema an assistant reads and
		 * the check the write runs cannot drift apart. list-campaigns layers
		 * 'any' on top of this for querying; 'any' is not included here because a
		 * campaign cannot be SAVED as 'any'.
		 *
		 * @since 1.8.13
		 *
		 * @return string[]
		 */
		public static function writable_campaign_statuses() {
			return array( 'draft', 'publish', 'pending', 'private' );
		}

		/**
		 * Reusable input schema for any ability keyed on a single campaign_id.
		 *
		 * Shared by get-campaign and get-campaign-performance today; Task 5's
		 * update-campaign and set-campaign-status also key on campaign_id, so
		 * this is factored out rather than duplicated inline.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function campaign_id_input_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'campaign_id' => array(
						'type' => 'integer',
					),
				),
				'required'             => array( 'campaign_id' ),
				'additionalProperties' => false,
			);
		}

		/**
		 * Reusable output schema for one campaign row in charitable/list-campaigns.
		 *
		 * @since 1.8.13
		 *
		 * @return array
		 */
		private static function campaign_summary_schema() {
			return array(
				'type'       => 'object',
				'properties' => array(
					'id'               => array( 'type' => 'integer' ),
					'title'            => array( 'type' => 'string' ),
					'status'           => array( 'type' => 'string' ),
					'url'              => array( 'type' => 'string' ),
					'has_goal'         => array( 'type' => 'boolean' ),
					'goal'             => array( 'type' => array( 'number', 'null' ) ),
					'goal_formatted'   => array( 'type' => array( 'string', 'null' ) ),
					'raised'           => array( 'type' => 'number' ),
					'raised_formatted' => array( 'type' => 'string' ),
					'percent_raised'   => array( 'type' => array( 'number', 'null' ) ),
					'donor_count'      => array( 'type' => 'integer' ),
					'end_date'         => array( 'type' => array( 'string', 'null' ) ),
					'has_ended'        => array( 'type' => 'boolean' ),
				),
			);
		}
	}

endif;
