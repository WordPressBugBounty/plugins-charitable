<?php
/**
 * Class that sets up the Campaign builder preview abilities.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Campaign_Builder_Preview' ) ) :

	/**
	 * Campaign preview.
	 *
	 * @since 1.8.0
	 * @version 1.8.8.6
	 */
	class Campaign_Builder_Preview { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Class name is used by other code. Changing it would break existing functionality.

		/**
		 * One is the loneliest number that you'll ever do.
		 *
		 * @since 1.8.0
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Whether a campaign preview render is already in progress anywhere up the call stack.
		 *
		 * Static on purpose, and this is the whole point of it. This class is instantiated in more
		 * than one place - once from the plugin bootstrap and again from
		 * templates/campaign/builder/content.php, which the render itself includes - and every
		 * instance whose is_preview_page() passes registers its own the_content callback. WordPress
		 * keys object callbacks by spl_object_hash, so the remove_filter() around the render can only
		 * ever detach the *calling* instance's callback. The instance created during the render is a
		 * new object identity, so it re-arms the hook the level above just disarmed, and each nesting
		 * level adds another handler: measured at 2, 3, 4 and climbing, exhausting 256M within a
		 * handful of renders (HelpScout #11576). A per-instance guard cannot see that; a class-level
		 * one can.
		 *
		 * @since 1.8.12.4
		 *
		 * @var bool
		 */
		private static $is_rendering = false;

		/**
		 * Campaign data.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $campaign_data;

		/**
		 * Constructor.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			if ( ! $this->is_preview_page() ) {
				return;
			}

			$this->hooks();
		}

		/**
		 * Main Instance.
		 *
		 * @since 1.8.0
		 *
		 * @return Charitable_Builder
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Campaign_Builder_Preview ) ) {

				self::$instance = new Campaign_Builder_Preview();

			}

			return self::$instance;
		}

		/**
		 * Init
		 *
		 * @since 1.8.0
		 *
		 * @return void
		 */
		public function init() {
		}

		/**
		 * Check if current page request meets requirements for campaign preview page.
		 *
		 * @since 1.8.0
		 *
		 * @return bool
		 */
		public function is_preview_page() {

			// Only proceed for the campaign preview page.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET['charitable_campaign_preview'] ) ) {
				return false;
			}

			// Check for logged-in user with correct capabilities.
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$campaign_id = isset( $_GET['p'] ) ? absint( $_GET['p'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$campaign_id = ( 0 === $campaign_id && ! empty( $_GET['charitable_campaign_preview'] ) ) ? absint( $_GET['charitable_campaign_preview'] ) : $campaign_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 0 === $campaign_id && ! empty( $_GET['charitable_campaign_preview'] ) && 1 === intval( $_GET['charitable_campaign_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				global $post;
				if ( ! empty( $post->post_id ) ) {
					$campaign_id = absint( $post->post_id );
				}
			}

			// check if this custom post type actually exists.
			global $wpdb;
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID=%s AND post_status != 'trash'", array( $campaign_id ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				return false;
			}

			// check permissions.
			if ( ! charitable_current_user_can( 'edit_post', $campaign_id ) ) { // this was 'view_campaign_single'.
				return false;
			}

			// Fetch campaign details.
			$this->campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

			// Check valid campaign was found.
			if ( empty( $this->campaign_data ) || ( empty( $this->campaign_data['id'] ) && empty( absint( $_GET['charitable_campaign_preview'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return false;
			}

			return true;
		}

		/**
		 * Hooks.
		 *
		 * @since 1.8.0
		 */
		public function hooks() {

			add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
			add_filter( 'the_title', [ $this, 'the_title' ], 100, 1 );
			add_filter( 'the_content', [ $this, 'the_content' ], 999 );
			// Accept the second argument get_the_excerpt passes (the WP_Post being excerpted) so the
			// handler can tell whose excerpt it is and leave other posts alone. See the_content().
			add_filter( 'get_the_excerpt', [ $this, 'the_content' ], 999, 2 );
			add_filter( 'home_template_hierarchy', [ $this, 'force_page_template_hierarchy' ], 999 );
			add_filter( 'frontpage_template_hierarchy', [ $this, 'force_page_template_hierarchy' ], 999 );
			add_filter( 'post_thumbnail_html', '__return_empty_string' );
			add_filter( 'charitable_campaign_can_receive_donations', [ $this, 'enable_campaign_form' ], 10, 2 );
		}

		/**
		 * The campaign donation form should be enabled for a campaign preview page, so on a preview page it's "ok" for a campaign to recieve a donation.
		 *
		 * @param boolean $can        Whether the campaign form accepts donations.
		 * @param mixed   $the_object Charitable_Campaign.
		 *
		 * @return boolean Whether the campaign form can be enabled for the given object.
		 */
		public function enable_campaign_form( $can, $the_object ) { // phpcs:ignore
			return true;
		}

		/**
		 * Modify query, limit to one post.
		 *
		 * @since 1.8.0
		 *
		 * @param \WP_Query $query The WP_Query instance.
		 */
		public function pre_get_posts( $query ) {

			if ( is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$query->set( 'page_id', '' );
			$query->set( 'post_type', 'campaign' );
			$query->set( 'post__in', empty( $this->campaign_data['id'] ) ? [] : [ (int) $this->campaign_data['id'] ] );
			$query->set( 'posts_per_page', 1 );
		}

		/**
		 * Customize campaign preview page title.
		 *
		 * @since 1.5.1
		 *
		 * @param string $title Page title.
		 *
		 * @return string
		 */
		public function the_title( $title ) {

			if ( in_the_loop() ) {
				$title = sprintf( /* translators: %s - campaign title. */
					esc_html__( '%s Preview', 'charitable' ),
					! empty( $this->campaign_data['settings']['campaign_title'] ) ? sanitize_text_field( $this->campaign_data['settings']['campaign_title'] ) : esc_html__( 'Campaign', 'charitable' )
				);
			}

			return $title;
		}

		/**
		 * The ID of the campaign this request is previewing.
		 *
		 * A brand-new campaign that has never been saved has no id in its payload, in which case the
		 * only place the ID exists is the query argument, so fall back to that.
		 *
		 * @since 1.8.12.4
		 *
		 * @return int Campaign ID, or 0 if none could be resolved.
		 */
		public function get_preview_campaign_id() {

			$campaign_id = isset( $this->campaign_data['id'] ) ? absint( $this->campaign_data['id'] ) : 0;

			if ( 0 === $campaign_id && ! empty( $_GET['charitable_campaign_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$campaign_id = absint( $_GET['charitable_campaign_preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			return $campaign_id;
		}

		/**
		 * Customize campaign preview page content.
		 *
		 * @since 1.8.0
		 * @version 1.8.12.3 Detach from the content filters while the campaign renders, to prevent an infinite the_content loop that exhausts memory.
		 * @version 1.8.12.4 Leave other posts' excerpts alone. This handler is also attached to
		 *                   get_the_excerpt, and returned the whole rendered campaign for whatever post
		 *                   was being excerpted, so any excerpt elsewhere on the preview page (a query
		 *                   loop, a posts list) was replaced by a duplicate of the campaign.
		 *
		 * @param string       $content The incoming content or excerpt.
		 * @param WP_Post|null $post    Only supplied by get_the_excerpt: the post being excerpted.
		 *                              Null on the_content, which passes a single argument.
		 *
		 * @return string
		 */
		public function the_content( $content = '', $post = null ) {

			/*
			 * get_the_excerpt names the post it is asking about, so when it is asking about anything
			 * other than the campaign being previewed, hand the excerpt straight back untouched.
			 * Without this the handler ignored the question and answered with the full campaign render
			 * every time, so a preview page containing any other post's excerpt showed the campaign
			 * again in its place.
			 *
			 * Deliberately keyed on the argument rather than on in_the_loop() or get_the_ID(): both of
			 * those read the global post, which under a block theme is only set up when core's
			 * singular-template loop workaround fires (it is gated on is_singular(),
			 * wp-includes/block-template.php). The passed WP_Post is reliable regardless of theme.
			 *
			 * the_content is left alone on purpose. It passes no post argument, and core's
			 * core/post-content block calls get_the_content() with no ID precisely so the queried
			 * object wins, so that path is expected to act on the ambient post.
			 */
			if ( $post instanceof WP_Post && absint( $post->ID ) !== $this->get_preview_campaign_id() ) {
				return $content;
			}

			/*
			 * A render is already running further up the stack, so this application of the filter is
			 * a re-entry. Hand the content back untouched instead of starting a second render.
			 *
			 * This is what actually stops the loop. The remove_filter() further down cannot, because
			 * it only detaches THIS instance's callback, and the instance created inside the render
			 * (content.php, "preview page check") re-arms the hook under a fresh object identity. See
			 * the note on self::$is_rendering.
			 */
			if ( self::$is_rendering ) {
				return $content;
			}

			/*
			 * Never render the campaign into an excerpt. This catches BOTH the direct
			 * get_the_excerpt application and the indirect one: core's wp_trim_excerpt() re-applies
			 * the_content from inside the get_the_excerpt filter, so an SEO or Open Graph plugin
			 * asking for a meta description costs a full campaign render every time it asks. That is
			 * what exhausted 256M on the reporting site, and it is why deactivating SiteSEO there
			 * made the preview work (HelpScout #11576). doing_filter() sees the enclosing filter, so
			 * it recognises the nested the_content application that a check on the current filter
			 * alone would miss.
			 *
			 * Deliberately not bounded by a render counter instead: whichever caller asked first
			 * would win, and an SEO plugin building meta tags in wp_head asks before the body
			 * renders, which would leave the preview body empty.
			 */
			if ( doing_filter( 'get_the_excerpt' ) ) {

				/*
				 * Two different applications reach here while an excerpt is being built: the
				 * get_the_excerpt filter itself, and the the_content filter that core's
				 * wp_trim_excerpt() applies from inside it. Only the first is asking us for a
				 * summary. The second is asking "what is this post's content", about whatever post
				 * is being excerpted, which may not be the campaign at all, so answering it with the
				 * campaign's description would put the campaign's words into an unrelated post's
				 * excerpt. Hand that one straight back.
				 *
				 * Note wp_trim_excerpt() only takes that branch when the post has no excerpt of its
				 * own (formatting.php, '' === trim( $text )), which is why a post WITH an excerpt
				 * never exposed this.
				 */
				if ( 'get_the_excerpt' !== current_filter() ) {
					return $content;
				}

				/*
				 * Answer with the campaign's own description rather than nothing, so an SEO or Open
				 * Graph plugin still gets a usable meta description on a preview page. This reads the
				 * settings array is_preview_page() already loaded, so it costs no render and no query,
				 * and charitable_find_description_in_campaign_settings() ends on a Charitable filter
				 * rather than the_content, so it cannot re-enter this handler.
				 *
				 * Tags are stripped and the result trimmed because the caller wants a summary, not
				 * markup. 55 words is about the length a meta description survives at.
				 */
				if ( ! function_exists( 'charitable_find_description_in_campaign_settings' ) ) {
					return $content;
				}

				$charitable_preview_description = charitable_find_description_in_campaign_settings( $this->campaign_data, 0 );

				if ( ! is_string( $charitable_preview_description ) ) {
					return $content;
				}

				$charitable_preview_description = wp_strip_all_tags( $charitable_preview_description );

				if ( '' === trim( $charitable_preview_description ) ) {
					return $content;
				}

				return wp_trim_words( $charitable_preview_description, 55, '…' );
			}

			if ( ! isset( $this->campaign_data['id'] ) ) {
				return '';
			}

			if ( ! charitable_current_user_can( 'edit_posts', $this->campaign_data['id'] ) ) {
				return '';
			}

			$links = [];

			if ( charitable_current_user_can( 'edit_posts', $this->campaign_data['id'] ) ) {
				$links[] = [
					'url'  => esc_url(
						add_query_arg(
							[
								'page'        => 'charitable-campaign-builder',
								'view'        => 'design',
								'campaign_id' => absint( $this->campaign_data['id'] ),
							],
							admin_url( 'admin.php' )
						)
					),
					'text' => esc_html__( 'Edit Campaign', 'charitable' ),
				];
			}

			if ( charitable_current_user_can( 'read', $this->campaign_data['id'] ) ) {
				$links[] = [
					'url'  => esc_url(
						add_query_arg(
							[
								'post_type'   => 'donation',
								'campaign_id' => absint( $this->campaign_data['id'] ),
							],
							admin_url( 'edit.php' )
						)
					),
					'text' => esc_html__( 'View Donations', 'charitable' ),
				];
			}

			$links[] = [
				'url'  => esc_url(
					charitable_utm_link( 'https://www.wpcharitable.com/documentation/', 'Campaign Preview', 'Documentation' )
				),
				'text' => esc_html__( 'Documentation', 'charitable' ),
			];

			if ( ! empty( $_GET['new_window'] ) ) { // phpcs:ignore
				$links[] = [
					'url'  => 'javascript:window.close();',
					'text' => esc_html__( 'Close this window', 'charitable' ),
				];
			}

			$content = '<div class="charitable-preview-messages"><p>';

			if ( 'publish' === get_post_status( $this->campaign_data['id'] ) ) :

				if ( ! empty( $_GET['charitable_campaign_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					$content .= '<p>';

					$content .= sprintf(
						wp_kses(
							/* translators: %s - Charitable doc link. */
							__( 'You are viewing a preview of a <a href="%s" target="_blank">published campaign</a>. If this does not match your campaign page in the campaign builder, save your changes and then refresh this page. Your current theme can also effect the look of this page.', 'charitable' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						esc_url( get_permalink( $this->campaign_data['id'] ) )
					);

					$content .= '</p>';

					$content .= '<p>' . esc_html__( 'Some functionality and elements (like donation buttons, forms, social sharing, etc.) are disabled.', 'charitable' ) . '</p>';

				} else {

					$content .= '<p>';

					$content .= sprintf(
						wp_kses(
							/* translators: %s - Charitable doc link. */
							__( 'This is a preview of the latest saved revision of your campaign page. If this does not match your campaign page in the campaign builder, save your changes and then refresh this page. Your current theme can also effect the look of this page.', 'charitable' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						esc_url( get_permalink( $this->campaign_data['id'] ) )
					);

					$content .= '</p>';

					$content .= '<p>' . esc_html__( 'Some functionality and elements (like donation buttons, forms, social sharing, etc.) are disabled.', 'charitable' ) . '</p>';

				}

				array_unshift(
					$links,
					[
						'url'  => esc_url(
							get_permalink( $this->campaign_data['id'] )
						),
						'text' => esc_html__( 'View Live Campaign', 'charitable' ),
					]
				);

			elseif ( 'draft' === get_post_status( $this->campaign_data['id'] ) ) :

				$content .= '<p>';

				$content .= sprintf(
					wp_kses(
						/* translators: %s - Charitable doc link. */
						__( 'This is a preview of the latest saved revision of your campaign page. If this does not match your campaign page in the campaign builder, save your changes and then refresh this page. Your current theme can also effect the look of this page.', 'charitable' ),
						[
							'a' => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
						]
					),
					esc_url( get_permalink( $this->campaign_data['id'] ) )
				);

				$content .= '</p>';

				$content .= '<p>' . esc_html__( 'Some functionality and elements (like donation buttons, forms, social sharing, etc.) are disabled.', 'charitable' ) . '</p>';

			endif;

			if ( ! empty( $links ) ) {
				$content .= '<span class="charitable-preview-notice-links">';

				foreach ( $links as $key => $link ) {
					$content .= '<a href="' . $link['url'] . '">' . $link['text'] . '</a>';
					$l        = array_keys( $links );

					if ( end( $l ) !== $key ) {
						$content .= ' <span style="display:inline-block;margin:0 6px;opacity: 0.5">|</span> ';
					}
				}

				$content .= '</span>';
			}
			$content .= '</p>';

			$content .= '</div>';

			// If there is no campaign ID in the campaign_data, this MIGHT be an initial preview on a
			// new campaign, in which case the ID is only in the URL. Same resolution the excerpt guard
			// above uses, so the two cannot disagree about which campaign is being previewed.
			$campaign_id = $this->get_preview_campaign_id();

			// Mark a render as in progress for every instance of this class, not just this one, so a
			// re-application of the_content from inside the render short-circuits at the guard above.
			// This is the load-bearing part; the remove_filter() below is belt and braces that only
			// covers this instance's own callback.
			// Detach this handler from the content filters before rendering the campaign.
			// The campaign render re-applies the_content (via the campaign's get_content), and
			// with certain content-processing plugins active that re-application would re-enter
			// this method and render the campaign again, looping until PHP's memory limit is
			// exhausted (HelpScout #11576). The campaign endpoint already guards its own render
			// the same way. The finally re-adds the filters unconditionally, so even if the
			// render throws they are never left detached for the rest of the request.
			remove_filter( 'the_content', [ $this, 'the_content' ], 999 );
			remove_filter( 'get_the_excerpt', [ $this, 'the_content' ], 999 );

			try {
				// Inside the try so the finally below always clears it. Set outside, a throw between
				// the assignment and the try would leave every later application short-circuiting.
				self::$is_rendering = true;

				$content .= do_shortcode( '[campaign version="2" id=' . $campaign_id . ']' );
			} finally {
				self::$is_rendering = false;
				add_filter( 'the_content', [ $this, 'the_content' ], 999 );
				// The 2 matters. Re-adding without it silently downgrades this hook to one argument
				// for the rest of the request, so the excerpt guard above would stop receiving the
				// WP_Post and would go back to answering every excerpt with the campaign render.
				add_filter( 'get_the_excerpt', [ $this, 'the_content' ], 999, 2 );
			}

			return $content;
		}

		/**
		 * Force page template types.
		 *
		 * @since 1.8.0
		 *
		 * @param array $templates A list of template candidates, in descending order of priority.
		 *
		 * @return array
		 */
		public function force_page_template_hierarchy( $templates ) { // phpcs:ignore

			return [ 'page.php', 'single.php', 'index.php' ];
		}

		/**
		 * Adjust value of the {page_title} smart tag.
		 *
		 * @since 1.7.7
		 *
		 * @param string $content          Content.
		 * @param array  $campaign_data    Campaign data.
		 * @param array  $fields           List of fields.
		 * @param string $entry_id         Entry ID.
		 * @param object $smart_tag_object The smart tag object or the Generic object for those cases when class unregistered.
		 *
		 * @return string
		 */
		public function smart_tags_process_page_title_value( $content, $campaign_data, $fields, $entry_id, $smart_tag_object ) { // phpcs:ignore

			return sprintf( /* translators: %s - campaign title. */
				esc_html__( '%s Preview', 'charitable' ),
				! empty( $campaign_data['settings']['campaign_title'] ) ? sanitize_text_field( $campaign_data['settings']['campaign_title'] ) : esc_html__( 'Campaign', 'charitable' )
			);
		}

		/**
		 * Force page template types.
		 *
		 * @since 1.5.1
		 * @deprecated 1.8.0
		 *
		 * @return string
		 */
		public function template_include() {

			_deprecated_function( __METHOD__, '1.8.0 of the Charitable plugin' );

			return locate_template( [ 'page.php', 'single.php', 'index.php' ] );
		}
	}

endif;

new Campaign_Builder_Preview();
