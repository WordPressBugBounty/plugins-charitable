<?php
/**
 * Class that sets up admin pointers / tooltips for Charitable.
 *
 * @package   Charitable/Classes/Charitable_Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.5
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Pointers' ) ) :

	/**
	 * Charitable_Admin_Pointers
	 *
	 * @since 1.8.1.5
	 */
	class Charitable_Admin_Pointers {

		/**
		 * Pointers.
		 *
		 * @var array
		 */
		public $pointers = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'setup_pointers_for_screen' ) );
			add_action( 'in_admin_header', array( $this, 'register_pointer' ) );
			add_action( 'wp_ajax_charitable_dismiss_pointer', array( $this, 'ajax_charitable_dismiss_pointer' ) );
		}

		/**
		 * Dismiss pointer via ajax.
		 *
		 * @version 1.8.1.5
		 */
		public function ajax_charitable_dismiss_pointer() {

			$dismissed = $this->dismiss_pointer();

			wp_send_json_success( $dismissed );
			exit;
		}

		/**
		 * Load correct pointers if any.
		 *
		 * @version 1.8.1.5
		 */
		public function setup_pointers_for_screen() {

			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( ! $screen || ! is_object( $screen ) ) {
				return;
			}

			if ( ! in_array( $screen->id, charitable_get_charitable_screens() ) ) {
				return;
			}

			/*
			Don't show this immediately for new users.

			A NEW SLUG, not the old 'donor-pointers'. The transient below suppresses the pointer
			once it has been seen, so reusing the donor slug would mean every site that already
			dismissed the donor-management pointer never sees this one - which is the opposite of
			the point, since the message is different. A new slug gives it a fresh impression.

			@since 1.8.14
			*/
			$slug = 'reports-pointers';

			// determine when to display this message. for now, there should be some sensible boundaries before showing the notification: a minimum of 14 days of use, created one donation form and received at least one donation.
			$activated_datetime = ( false !== get_option( 'wpcharitable_activated_datetime' ) ) ? get_option( 'wpcharitable_activated_datetime' ) : false;
			$activated_datetime = ( false === $activated_datetime ) ? get_option( 'charitable_activated', 0 ) : $activated_datetime;
			$days = 0;
			if ( $activated_datetime ) {
				$diff = current_time( 'timestamp' ) - $activated_datetime;
				$days = abs(ceil($diff / 86400));
			}

			$count_campaigns = wp_count_posts( 'campaign' );
			$total_campaigns = isset( $count_campaigns->publish ) ? $count_campaigns->publish : 0;

			// If days is at least 1 and the user isn't already on the 'charitable-reports' page, show the pointer.
			if ( $days >= apply_filters( 'charitable_days_since_activated', 1 ) && isset( $_GET['page'] ) && $_GET['page'] !== 'charitable-reports' ) { // phpcs:ignore
				// check transient.
				$help_pointers = get_transient( 'charitable_' . $slug . '_onboarding' );

				// render five star rating banner/notice.
				if ( ! $help_pointers ) {
					wp_enqueue_style( 'wp-pointer' );
					wp_enqueue_script( 'wp-pointer' );
					$this->create_new_features_showoff();
				}
			}
		}

		/**
		 * Pointers for creating a feature show off!
		 *
		 * @version 1.8.1.5
		 * @version 1.8.5.1
		 *
		 * @return void
		 */
		public function create_new_features_showoff() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( defined( 'CHARITABLE_DISABLE_ADMIN_POINTERS' ) && CHARITABLE_DISABLE_ADMIN_POINTERS ) {
				return;
			}

			/*
			POINTS AT REPORTS, not Donors, as of 1.8.14.

			The key is 'reports' rather than the old 'tools'. That key is what gets written into
			the charitable-pointer-slug-dismissed user meta, so renaming it means a user who
			dismissed the donor pointer is not silently opted out of this one. ('tools' was a
			misnomer for a pointer aimed at the Donors menu item in any case.)

			NO UPGRADE LINK, which is a change of intent rather than an omission. The old pointer
			sold Pro donor management. This one announces an improvement that is free and already
			installed, and "we improved your reports, now buy Pro" reads as a bait in one
			tooltip. The Reports page it opens carries its own upsell cards, so the selling
			happens at the destination where the reader can see what they would get.

			The target class is the one WordPress actually renders for this submenu item -
			li.reports, verified in the DOM rather than inferred from the donors entry.

			@since 1.8.14
			*/
			$this->pointers = array(
				'pointers' => array(
					'reports'   => array(
						'target'       => '#toplevel_page_charitable ul li.reports',
						'next'         => false,
						'next_trigger' => array(),
						'options'      => array(
							/*
							"include the change from", NOT "compare against".

							Both describe the same thing - a signed percentage and an arrow on each
							headline tile, measured against the preceding period of equal length - but
							"compare" is the NAME of a Pro feature this release deliberately does not
							ship: the Compare dialog that puts two arbitrary ranges side by side. A Lite
							reader seeing "compare" in a tooltip about their own reports will reasonably
							go looking for that control and not find it. The claim was accurate and the
							wording was still wrong.
							*/
							'content'      => '<h3>' . esc_html__( 'Improved Reporting', 'charitable' ) . '</h3>' .
											'<p>' . esc_html__( 'The Reports page has been redesigned. Headline figures now include the change from the previous period, long date ranges group by week or month, and new cards show where your donations are coming from.', 'charitable' ) . '</p>',
							'position'     => array(
								'edge'  => 'left',
								'align' => 'left',
							),
							'visit_button' => array(
								'url'   => admin_url( 'admin.php?page=charitable-reports' ),
								'label' => esc_html__( 'View Reports', 'charitable' ),
							),
						),
					),
				),
			);

			// if CHARITABLE_FORCE_ADMIN_POINTERS isn't defined, we'll check if the user has dismissed the pointers before.
			if ( ! defined( 'CHARITABLE_FORCE_ADMIN_POINTERS' ) || ! CHARITABLE_FORCE_ADMIN_POINTERS ) {

				// Check user meta - if the user has seen these before, don't show them again.
				$dismissed = get_user_meta( get_current_user_id(), 'charitable-pointer-slug-dismissed', true );

				if ( ! empty( $dismissed ) ) {
					foreach ( $dismissed as $pointer ) {
						if ( isset( $this->pointers['pointers'][ $pointer ] ) ) {
							unset( $this->pointers['pointers'][ $pointer ] );
						}
					}
					// if any of the pointer keys are found in dismissed remove all the keys.
					if ( in_array( array_keys( $this->pointers['pointers'] ), $dismissed, true ) ) {
						foreach ( $this->pointers['pointers'] as $pointer => $pointer_info ) {
							unset( $this->pointers['pointers'][ $pointer ] );
						}
					}
				}

			}
		}

		/**
		 * Register pointers and added needed JavaScript.
		 *
		 * @version 1.8.1.5
		 *
		 * @return void
		 */
		public function register_pointer() {

			if ( empty( $this->pointers ) ) {
				return;
			}

			$pointers = wp_json_encode( $this->pointers );

			// phpcs:disable
			echo(
				"<script>
				jQuery( function( $ ) {
					var wpchar_pointers = {$pointers};

					setTimeout( init_wpchar_pointers, 800 );

					function init_wpchar_pointers() {
						$.each( wpchar_pointers.pointers, function( i ) {
							show_wpchar_pointer( i );
							return false;
						});
					}

					function show_wpchar_pointer( id ) {
						// Check if WordPress pointer function is available
						if ( typeof $.fn.pointer === 'undefined' ) {
							console.warn( 'Charitable: WordPress pointer function not available' );
							return;
						}

						var pointer = wpchar_pointers.pointers[ id ];
						var options = $.extend( pointer.options, {
							pointerClass: 'wp-pointer charitable-pointer',
							pointerWidth: 420,
							close: function() {
								if ( pointer.next ) {
									show_wpchar_pointer( pointer.next );
								}
							},
							buttons: function( event, t ) {
								var close       = '" . esc_js( __( 'Dismiss', 'charitable' ) ) . "',
									next        = '" . esc_js( __( 'Next', 'charitable' ) ) . "',
									visit	    = '" . esc_js( __( 'Go Here', 'charitable' ) ) . "',
									button      = $( '<a class=\"close\" href=\"#\">' + close + '</a>' ),
									button2     = pointer.next ? $( '<a class=\"button button-primary\" href=\"#\">' + next + '</a>' ) : '';
									button_goto = $( '<a class=\"button button-primary button-visit\" href=\"' + pointer.options.visit_button.url + '\">' + pointer.options.visit_button.label + '</a>' ),
									wrapper     = $( '<div class=\"charitable-pointer-buttons\" />' );

								button.bind( 'click.pointer', function(e) {
									e.preventDefault();
									t.element.pointer('destroy');

									$.ajax({
										type: 'POST',
										data: {
											action  : 'charitable_dismiss_pointer',
											pointer : id,
											notice  : '',
										},
										dataType: 'json',
										url: ajaxurl
									}).fail(function ( response ) {
										if ( window.console && window.console.log ) {
											console.log( response );
										}
									});

								});

								if ( button2 !== '' ) {
									button2.bind( 'click.pointer', function(e) {
										e.preventDefault();
										t.element.pointer('close');

										$.ajax({
											type: 'POST',
											data: {
												action  : 'charitable_dismiss_pointer',
												pointer : id,
												notice  : '',
											},
											dataType: 'json',
											url: ajaxurl
										}).fail(function ( response ) {
											if ( window.console && window.console.log ) {
												console.log( response );
											}
										});

									});
								}

								wrapper.append( button );
								if ( button2 !== '' ) {
									wrapper.append( button2 );
								}
								wrapper.append( button_goto );

								return wrapper;
							},
						} );
						var this_pointer = $( pointer.target ).pointer( options );
						this_pointer.pointer( 'open' );

						if ( pointer.next_trigger ) {
							$( pointer.next_trigger.target ).on( pointer.next_trigger.event, function() {
								setTimeout( function() { this_pointer.pointer( 'close' ); }, 400 );
							});
						}
					}
				});</script>"
			);
			// phpcs:enable
		}

		/**
		 * Dismiss pointer.
		 *
		 * @version 1.8.1.5
		 *
		 * @return bool
		 */
		public function dismiss_pointer() {

			if ( isset( $_POST['action'] ) && 'charitable_dismiss_pointer' == $_POST['action'] ) { // phpcs:ignore
				// get dismissed pointer slugs from the current user.
				$dismissed = (array) get_user_meta( get_current_user_id(), 'charitable-pointer-slug-dismissed', true );
				if ( false === $dismissed ) {
					$dismissed = array();
				}
				$dismissed[] = esc_attr( $_POST['pointer'] ); // phpcs:ignore
				$dismissed   = array_unique( $dismissed );
				$dismissed   = array_filter( $dismissed );
				return update_user_meta(
					get_current_user_id(),
					'charitable-pointer-slug-dismissed',
					$dismissed
				);
			}
		}
	}

	new Charitable_Admin_Pointers();

endif;
