<?php
/**
 * Sets up the campaign list table in the admin.
 *
 * @package   Charitable/Classes/Charitable_Campaign_List_Table
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.8.1.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_List_Table' ) ) :

	/**
	 * Charitable_Campaign_List_Table class.
	 *
	 * @since 1.5.0
	 */
	final class Charitable_Campaign_List_Table {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Campaign_List_Table|null
		 */
		private static $instance = null;

		/**
		 * Post type.
		 *
		 * @var string
		 */
		protected $list_table_type = 'campaign';

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Campaign_List_Table
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Updates body class for admin.
		 *
		 * @since  1.8.0
		 *
		 * @param  string $classes The current body classes.
		 *
		 * @return string
		 */
		public function admin_body_class( $classes ) {

			global $pagenow;

			if ( in_array( $pagenow, array( 'edit.php' ), true ) && ! empty( $_GET['post_type'] ) && $_GET['post_type'] === 'campaign' ) { // phpcs:ignore.
				$count_posts = ( wp_count_posts( 'campaign' )->draft + wp_count_posts( 'campaign' )->publish + wp_count_posts( 'campaign' )->future + wp_count_posts( 'campaign' )->pending + wp_count_posts( 'campaign' )->private );
				$classes    .= ' charitable-campaigns charitable-campaigns-' . intval( $count_posts ) . '-published'; // this should be at start: charitable-campaigns charitable-campaigns-0-published.
			}

			if ( in_array( $pagenow, array( 'edit.php' ), true ) && ! empty( $_GET['post_status'] ) && $_GET['post_status'] === 'trash' ) { // phpcs:ignore.
				$count_posts = ( wp_count_posts( 'campaign' )->trash );
				$classes    .= ' charitable-trash charitable-campaigns-' . intval( $count_posts ) . '-trash';
			}

			return $classes;
		}

		/**
		 * Customize campaigns columns.
		 *
		 * @see    get_column_headers
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function dashboard_columns() {
			/**
			 * Filter the columns shown in the campaigns list table.
			 *
			 * @since 1.5.0
			 *
			 * @param array $columns The list of columns.
			 */
			return apply_filters(
				'charitable_campaign_dashboard_column_names',
				array(
					'cb'           => '<input type="checkbox"/>',
					// 'ID'       => __( '#', 'charitable' ),
					'title'        => __( 'Title', 'charitable' ),
					'creator'      => __( 'Creator', 'charitable' ),
					'donated'      => __( 'Donations', 'charitable' ),
					'status'       => __( 'Status', 'charitable' ),
					'end_date'     => __( 'End Date', 'charitable' ),
					'date_created' => __( 'Date Created', 'charitable' ),
					'actions'      => __( 'Actions', 'charitable' ),
				)
			);
		}

		/**
		 * Add information to the dashboard campaign table listing.
		 *
		 * @see    WP_Posts_List_Table::single_row()
		 *
		 * @since  1.5.0
		 *
		 * @param  string $column_name The name of the column to display.
		 * @param  int    $post_id     The current post ID.
		 * @return void
		 */
		public function dashboard_column_item( $column_name, $post_id ) {
			$campaign = charitable_get_campaign( $post_id );
			$display  = '';

			switch ( $column_name ) {
				case 'ID':
					$display = $post_id;
					break;

				case 'creator':
					$creator_id   = $campaign->get_campaign_creator();
					$creator_name = $campaign->get_campaign_creator_name();

					if ( intval( $creator_id ) > 0 ) {
						$display .= '<a href="' . get_edit_user_link( $creator_id ) . '" >';
						$display .= '<img class="charitable-campaign-creator-avatar" alt="' . $creator_name . '" title="' . $creator_name . '" src="' . esc_url( get_avatar_url( $creator_id ) ) . '" />';
					}
					$display .= '<span class="charitable-campaign-creator-name">' . $creator_name . '</span>';
					if ( intval( $creator_id ) > 0 ) {
						$display .= '</a>';
					}
					break;

				case 'donated':
					$display = charitable_format_money( $campaign->get_donated_amount() );
					$percent = absint( $campaign->get_percent_donated() );
					if ( $percent > 100 ) {
						$percent = 100;
					}
					if ( ! $campaign->is_endless() ) {
						$display .= '<div class="meter">
										<span style="width: ' . $percent . '%"></span>
									</div>';
					}
					break;

				case 'date_created':
					$date_format = get_option( 'date_format' );
					$date_format = ( '' === trim( $date_format ) ) ? 'd/m/Y' : $date_format;
					$display     = date( $date_format, strtotime( $campaign->post_date ) );
					break;

				case 'end_date':
					$display = $campaign->is_endless() ? '&#8734;' : $campaign->get_end_date();
					break;

				case 'actions':
					// determine if we share the preview or the actual live URL.
					$status = get_post_status( $post_id );

					if ( 'trash' === $status ) {
						break;
					}

					$link    = ( 'publish' === $status || 'private' === $status ) ? get_permalink( $post_id ) : charitable_get_campaign_preview_url( $post_id, true, $status );
					$display = '<a class="charitable-campaign-action-button" title="' . esc_html__( 'Preview', 'charitable' ) . '" href="' . esc_url( $link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/eye.svg" width="14" height="14" alt="' . esc_html__( 'Preview', 'charitable' ) . '" /></a><a class="charitable-campaign-action-button" title="' . esc_html__( 'Trash', 'charitable' ) . '" href="' . get_delete_post_link( $post_id ) . '"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/trash.svg" width="14" height="14" alt="' . esc_html__( 'Trash', 'charitable' ) . '" /></a>';

					// <button><i class="fa fa-envelope"></i></button>

					break;

				case 'status':
					$status = $campaign->get_status();

					if ( 'finished' == $status && $campaign->has_goal() ) {
						$status = $campaign->has_achieved_goal() ? 'successful' : 'unsuccessful';
					}

					$display = '<mark class="' . esc_attr( $status ) . '">' . $status . '</mark>';
					break;

				default:
					$display = '';
			}

			/**
			 * Filter the output of the cell.
			 *
			 * @since 1.5.0
			 *
			 * @param string              $display     The content that will be displayed.
			 * @param string              $column_name The name of the column.
			 * @param int                 $post_id     The ID of the campaign being shown.
			 * @param Charitable_Campaign $campaign    The Charitable_Campaign object.
			 */
			echo apply_filters( 'charitable_campaign_column_display', $display, $column_name, $post_id, $campaign ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Modify bulk messages
		 *
		 * @since  1.5.0
		 *
		 * @param  array $bulk_messages Messages to show after bulk actions.
		 * @param  array $bulk_counts   Array showing how many items were affected by the action.
		 * @return array
		 */
		public function bulk_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ Charitable::CAMPAIGN_POST_TYPE ] = array(
				/* translators: %s: number of items */
				'updated'   => _n( '%d campaign updated.', '%d campaigns updated.', $bulk_counts['updated'], 'charitable' ),
				'locked'    => ( 1 == $bulk_counts['locked'] )
								? __( '1 campaign not updated, somebody is editing it.', 'charitable' )
								/* translators: %s: number of items */
								: _n( '%s campaign not updated, somebody is editing it.', '%s campaigns not updated, somebody is editing them.', $bulk_counts['locked'], 'charitable' ),
				/* translators: %s: number of items */
				'deleted'   => _n( '%s campaign permanently deleted.', '%s campaigns permanently deleted.', $bulk_counts['deleted'], 'charitable' ),
				/* translators: %s: number of items */
				'trashed'   => _n( '%s campaign moved to the Trash.', '%s campaigns moved to the Trash.', $bulk_counts['trashed'], 'charitable' ),
				/* translators: %s: number of items */
				'untrashed' => _n( '%s campaign restored from the Trash.', '%s campaigns restored from the Trash.', $bulk_counts['untrashed'], 'charitable' ),
			);

			return $bulk_messages;
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @since  1.6.0
		 *
		 * @param  string $which The context where this is being called.
		 * @return void
		 */
		public function add_export( $which ) {
			if ( ! current_user_can( 'export_charitable_reports' ) ) {
				return;
			}

			if ( 'top' === $which && $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/export' );
			}
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @since  1.6.0
		 *
		 * @param  string $which The context where this is being called.
		 * @return void
		 */
		public function add_legacy_link( $which ) {
			if ( ! current_user_can( 'publish_posts' ) ) {
				return;
			}

			if ( 'top' === $which && $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/legacy-add' );
			}
		}


		/**
		 * Add filters above the campaigns table.
		 *
		 * @since  1.6.36
		 *
		 * @global string $typenow The current post type.
		 *
		 * @return void
		 */
		public function add_filters() {
			global $typenow;

			/* Show custom filters to filter orders by donor. */
			if ( in_array( $typenow, array( Charitable::CAMPAIGN_POST_TYPE ) ) ) {
				charitable_admin_view( 'campaigns-page/filters' );
			}
		}

		/**
		 * Add modal template to footer.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function modal_forms() {
			if ( $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/filter-form' );
				charitable_admin_view( 'campaigns-page/export-form' );
			}
		}

		/**
		 * Admin scripts and styles.
		 *
		 * Set up the scripts & styles used for the modal.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $hook The current page hook/slug.
		 * @return void
		 */
		public function load_scripts( $hook ) {
			if ( 'edit.php' != $hook ) {
				return;
			}

			if ( $this->is_campaigns_page() ) {
				wp_enqueue_style( 'lean-modal-css' );
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'lean-modal' );
				wp_enqueue_script( 'charitable-admin-tables' );
			}
		}

		/**
		 * Add custom filters to the query that returns the campaigns to be displayed.
		 *
		 * @since  1.6.36
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  array $vars The array of args to pass to WP_Query.
		 * @return array
		 */
		public function filter_request_query( $vars ) {
			if ( ! $this->is_campaigns_page() ) {
				return $vars;
			}

			/* No Status: fix WP's crappy handling of "all" post status. */
			if ( ! isset( $_GET['post_status'] ) || empty( $_GET['post_status'] ) || 'all' === $_GET['post_status'] ) {
				$vars['post_status'] = array_keys( get_post_statuses() );
				$vars['perm']        = 'readable';
			} else {
				switch ( $_GET['post_status'] ) {
					case 'active':
						$vars['post_status'] = 'publish';
						$vars['meta_query']  = array(
							'relation' => 'OR',
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ),
								'compare' => '>',
								'type'    => 'datetime',
							),
							array(
								'key'     => '_campaign_end_date',
								'value'   => 0,
								'compare' => '=',
							),
						);
						break;

					case 'finish':
						$vars['post_status'] = 'publish';
						$vars['meta_query']  = array(
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ),
								'compare' => '<=',
								'type'    => 'datetime',
							),
						);
						break;

					default:
						$vars['post_status'] = esc_attr( $_GET['post_status'] ); // phpcs:ignore
						$vars['perm']        = 'readable';
				}
			}

			if ( ! empty( $_GET['charitable_nonce'] ) &&
				wp_verify_nonce( esc_html( $_GET['charitable_nonce'] ), 'charitable_filter_campaigns' ) ) :  // phpcs:ignore

				/* Set up start date query */
				if ( isset( $_GET['start_date_from'] ) && ! empty( $_GET['start_date_from'] ) ) {
					$start_date_from             = $this->get_parsed_date( esc_html( $_GET['start_date_from'] ) );  // phpcs:ignore
					$vars['date_query']['after'] = array(
						'year'  => $start_date_from['year'],
						'month' => $start_date_from['month'],
						'day'   => $start_date_from['day'],
					);
				}

				if ( isset( $_GET['start_date_to'] ) && ! empty( $_GET['start_date_to'] ) ) {
					$start_date_to                = $this->get_parsed_date( esc_html( $_GET['start_date_to'] ) );  // phpcs:ignore
					$vars['date_query']['before'] = array(
						'year'  => $start_date_to['year'],
						'month' => $start_date_to['month'],
						'day'   => $start_date_to['day'],
					);
				}

				/* Set up end date query */
				if ( isset( $_GET['end_date_from'] ) && ! empty( $_GET['end_date_from'] ) ) {
					$end_date_from        = $this->get_parsed_date( esc_html( $_GET['end_date_from'] ) ); // phpcs:ignore
					$vars['meta_query'][] = array(
						'key'     => '_campaign_end_date',
						'value'   => sprintf( '%d-%d-%d 00:00:00', $end_date_from['year'], $end_date_from['month'], $end_date_from['day'] ),
						'compare' => '>=',
						'type'    => 'datetime',
					);
				}

				if ( isset( $_GET['end_date_to'] ) && ! empty( $_GET['end_date_to'] ) ) {
					$end_date_to          = $this->get_parsed_date( esc_html( $_GET['end_date_to'] ) );  // phpcs:ignore
					$vars['meta_query'][] = array(
						'key'     => '_campaign_end_date',
						'value'   => sprintf( '%d-%d-%d 00:00:00', $end_date_to['year'], $end_date_to['month'], $end_date_to['day'] ),
						'compare' => '<=',
						'type'    => 'datetime',
					);
				}

				if ( isset( $vars['date_query'] ) ) {
					$vars['date_query']['inclusive'] = true;
				}

				/* Filter by campaign. */
				if ( isset( $_GET['campaign_id'] ) && false !== stripos( esc_html( $_GET['charitable_nonce'] ), 'all' ) ) {  // phpcs:ignore
					$vars['post__in'] = charitable_get_table( 'campaign_donations' )->get_donation_ids_for_campaign( intval( $_GET['campaign_id'] ) );
				}

			endif;

			/* Restrict by author if user can only edit their own. */
			if ( ! current_user_can( 'edit_others_campaigns' ) ) {
				$vars['author'] = get_current_user_id();
			}

			/**
			 * Filter the campaign list query vars.
			 *
			 * @since 1.6.36
			 *
			 * @param array $vars The request query vars.
			 */
			return apply_filters( 'charitable_filter_campaigns_list_request_vars', $vars );
		}

		/**
		 * Given a date, returns an array containing the date, month and year.
		 * Updated in 1.7.0.8 to reflect a change in date format, but points to a new date format function in case someone else is using the old one.
		 *
		 * @since  1.6.36
		 *
		 * @param  string $date A date as a string that can be parsed by strtotime.
		 * @return string[]
		 */
		protected function get_parsed_date( $date ) {
			$time = charitable_sanitize_date_alt_format( $date );

			$args = array(
				'year'  => date( 'Y', $time ),
				'month' => date( 'm', $time ),
				'day'   => date( 'd', $time ),
			);

			return $args;
		}

		/**
		 * Checks whether this is the campaigns page.
		 *
		 * @since  1.6.0
		 *
		 * @global string $typenow The current post type.
		 * @global string $pagenow The current admin page.
		 *
		 * @return boolean
		 */
		private function is_campaigns_page() {
			global $typenow, $pagenow;

			return 'edit.php' === $pagenow && in_array( $typenow, array( Charitable::CAMPAIGN_POST_TYPE ), true );
		}

		/**
		 * Show blank slate.
		 *
		 * @since 1.8.1.5
		 *
		 * @param string $which String which tablenav is being shown.
		 *
		 * @return void
		 */
		public function maybe_render_blank_state( $which = '' ) {
			global $post_type;

			if ( $post_type === $this->list_table_type && 'bottom' === $which ) {
				$counts = (array) wp_count_posts( $post_type );
				unset( $counts['auto-draft'] );
				$count = array_sum( $counts );

				if ( ! defined( 'CHARITABLE_FORCE_BLANK_SLATE' ) ) :
					if ( 0 < $count || ( defined( 'CHARITABLE_DISABLE_BLANK_SLATE' ) && CHARITABLE_DISABLE_BLANK_SLATE ) ) {
						return;
					}
				endif;

				$this->render_blank_state();

				echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub, .wrap .search-box, .wrap .tablenav-pages  { display: none; } #posts-filter .tablenav.bottom { height: auto; } body.post-type-donation .page-title-action { display: inline-block; } </style>';
			}
		}

		/**
		 * Renders advice in the event that no orders exist yet.
		 *
		 * @since 1.8.1.5
		 *
		 * @return void
		 */
		public function render_blank_state() {
			?>
				<div class="charitable-blank-slate">

					<img class="charitable-blank-slate-hero-image" src="<?php echo esc_url( charitable()->get_path( 'directory', false ) ) . 'assets/images/icons/blank-slate-campaigns.svg'; ?>" alt=""  />

					<h2 class="charitable-blank-slate-message">
						<?php esc_html_e( 'Get Started And Create Your First Campaign!', 'charitable' ); ?>
					</h2>

					<div class="charitable-blank-slate-buttons">
						<a class="charitable-blank-slate-cta charitable-button" target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>"><?php esc_html_e( 'Create Campaign', 'charitable' ); ?></a>
						<div class="charitable-blank-slate-buttons-legacy">
							<div><a target="_blank" href="https://www.wpcharitable.com/documentation/creating-your-first-campaign/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=campaign-page&utm_content=Read%20The%20Full%20Guide"><?php esc_html_e( 'Read The Guide On Creating Your First Campaign', 'charitable' ); ?></a></div>
							<?php if ( ! charitable_disable_legacy_campaigns() ) : ?>
								<div><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>"><?php esc_html_e( 'Create Campaign (Legacy)', 'charitable' ); ?></a></div>
							<?php endif; ?>
						</div>
					</div>

					<?php
					/**
					 * Renders after the 'blank state' message for the order list table has rendered.
					 *
					 * @since 1.8.1.5
					 */
					do_action( 'charitable_marketplace_suggestions_campaigns_empty_state' ); // phpcs:ignore

					$recommendations = $this->render_blank_slate_recommendations();

					?>

					<div class="marketplace-suggestions-container showing-suggestion" data-marketplace-suggestions-context="orders-list-empty-header">
						<?php if ( false !== $recommendations ) : ?>
							<div class="marketplace-suggestion-container" data-suggestion-slug="orders-empty-header">
									<h4><?php esc_html_e( 'Improve The Potential Of Your Campaigns:', 'charitable' ); ?></h4>
							</div>
						<?php endif; ?>
					</div>
						<?php if ( false !== $recommendations ) : ?>
						<div class="charitable-intergration-steps">
							<?php echo $recommendations; // phpcs:ignore ?>
						</div>
					<?php endif; ?>
					<div class="marketplace-suggestions-container marketplace-suggestions-container-footer showing-suggestion">
						<div class="marketplace-suggestion-container marketplace-suggestion-container-footer">
							<div class="marketplace-suggestion-container-content"></div>
							<div class="marketplace-suggestion-container-cta">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-addons' ) ); ?>" class="linkout"><?php esc_html_e( 'Browse all addons', 'charitable' ); ?></a>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-growth-tools' ) ); ?>" target="_blank" class="linkout"><?php esc_html_e( 'Growth Tools', 'charitable' ); ?></a>
							</div>
						</div>
					</div>


				</div>
			<?php
		}

		/**
		 * Renders the blank slate recommendations.
		 *
		 * @since 1.8.1.5
		 *
		 * @return string
		 */
		public function render_blank_slate_recommendations() {

			$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();
			$user_recommended_list          = $charitable_plugins_third_party->get_recommendations( 'campaign', 3 );

			if ( empty( $user_recommended_list ) ) {
				return false;
			}

			ob_start();

			if ( ! empty( $user_recommended_list ) ) {

				foreach ( $user_recommended_list as $slug => $item ) {
					echo $this->render_blank_slate_recommendation( $slug );
				}

			} else {

				return;

			}

			$output = ob_get_clean();

			return $output;
		}

		/**
		 * Renders a blank slate recommendation.
		 *
		 * @since 1.8.1.5
		 *
		 * @param string $slug The slug of the recommendation.
		 *
		 * @return string
		 */
		public function render_blank_slate_recommendation( $slug = '' ) {

			// check and see if the slug refers to a Charitable addon first.
			$recommended_addons = get_transient( '_charitable_addons' ); // @codingStandardsIgnoreLine - testing.

			// Get addons data from transient or perform API query if no transient.
			if ( false === $recommended_addons ) {
				$recommended_addons = charitable_get_addons_data_from_server();
			}

			$recommended_addon = array();
			$addon_slug        = $slug;

			if ( $recommended_addons ) {

				foreach ( (array) $recommended_addons as $i => $addon ) {

					if ( ! empty( $addon['slug'] ) && (string) $addon_slug === (string) $addon['slug'] ) {
						$recommended_addon = $addon;
						break;
					}

				}

			}

			if ( ! empty( $recommended_addon ) ) {

				// this is a Chartiable addon.
				$title       = str_replace( 'Charitable', '', $recommended_addon['name'] );
				$sections    = ! empty( $recommended_addon['sections'] ) ? unserialize( $recommended_addon['sections'] ) : false;
				$description = is_array( $sections ) && ! empty( $sections['description'] ) ? $sections['description'] : '';

				ob_start();

				?>

				<div class="charitable-intergration-step charitable-addon charitable-plugin-suggestion" data-status="<?php echo esc_attr( $slug ); ?>">
					<div class="instructions">
						<header class="charitable-intergration-step-header">
							<div class="sub-header">
								<h3><?php echo esc_html( $title ); ?></h3>
								<span class="badge"><a href="#"><?php esc_html_e( 'Pro', 'charitable' ); ?></a></span>
							</div>
						</header>
						<p class="description"><?php echo wp_strip_all_tags( $description ); ?></p>
						<a href="<?php echo admin_url( 'admin.php?page=charitable-addons&search=' . esc_attr( str_replace( '-', ' ', $slug ) ) ); ?>" class="charitable-button button-link charitable-addons"><?php esc_html_e( 'View Addons', 'charitable' ); ?></a>
					</div>
					<div class="step">
						<div class="vertical-wrapper">
							<div class="step-image"><a class="suggestion-dismiss" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>" data-plugin-slug="<?php echo esc_attr( $slug ); ?>" data-plugin-type="addon" href="#"><i class="" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>"></i></a></div>
						</div>
					</div>
				</div>


				<?php

				$output = ob_get_clean();

			} else {

				// this is a third party plugin.

				$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();
				$plugin_data                    = $charitable_plugins_third_party->get_plugin( esc_attr( $slug ) );

				if ( ! $plugin_data ) {
					return '';
				}

				ob_start();

				?>

				<div class="charitable-intergration-step charitable-third-party charitable-plugin-suggestion" data-status="<?php echo esc_attr( $slug ); ?>">
					<div class="instructions">

						<header class="charitable-intergration-step-header">
							<div class="sub-header">
								<h3><?php echo esc_html( $plugin_data['title'] ); ?></h3>
								<span class="badge"><a href="#"><?php esc_html_e( 'Partner', 'charitable' ); ?></a></span>
							</div>
						</header>

						<p class="description"><?php echo esc_html( $plugin_data['excerpt'] ); ?></p>
						<?php
							$plugin_button_html = $charitable_plugins_third_party->get_plugin_button_html( $slug, false, '' );
							echo $plugin_button_html; // phpcs:ignore
						?>
					</div>
					<div class="step">
						<div class="vertical-wrapper">

							<div class="step-image"><a class="suggestion-dismiss" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>" data-plugin-slug="<?php echo esc_attr( $slug ); ?>" data-plugin-type="partner" href="#"><i class="" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>"></i></a></div>

						</div>
					</div>
				</div>


				<?php

				$output = ob_get_clean();

			}

			return $output;
		}
	}

endif;
