<?php
/**
 * This class is responsible for adding the Charitable admin pages.
 *
 * @package   Charitable/Classes/Charitable_Admin_Pages
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.1.12
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Pages' ) ) :

	/**
	 * Charitable_Admin_Pages
	 *
	 * @since 1.0.0
	 */
	final class Charitable_Admin_Pages {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Admin_Pages|null
		 */
		private static $instance = null;

		/**
		 * The page to use when registering sections and fields.
		 *
		 * @var     string
		 */
		private $admin_menu_parent_page;

		/**
		 * The capability required to view the admin menu.
		 *
		 * @var     string
		 */
		private $admin_menu_capability;

		/**
		 * Create class object.
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			/**
			 * The default capability required to view Charitable pages.
			 *
			 * @since 1.0.0
			 *
			 * @param string $cap The capability required.
			 */
			$this->admin_menu_capability  = apply_filters( 'charitable_admin_menu_capability', 'view_charitable_sensitive_data' );
			$this->admin_menu_parent_page = 'charitable';
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Admin_Pages
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * This forces the charitable menu to be open for category and tag pages.
		 *
		 * @since  1.7.0
		 *
		 * @param  string $parent_file The parent file.
		 *
		 * @return string
		 */
		public function menu_highlight( $parent_file ) {
			global $current_screen;

			$taxonomy = isset( $current_screen->taxonomy ) ? $current_screen->taxonomy : false;
			if ( false !== $taxonomy && ( 'campaign_category' === $taxonomy || 'campaign_tag' === $taxonomy ) ) {
				$parent_file = 'charitable';
			}

			$post_type = isset( $current_screen->post_type ) ? $current_screen->post_type : false;
			if ( false !== $post_type && ( 'campaign' === $post_type || 'donation' === $post_type ) ) {
				$parent_file = 'charitable';
			}

			return $parent_file;
		}

		/**
		 * Add Settings menu item under the Campaign menu tab.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function add_menu() {
			add_menu_page(
				'Charitable',
				'Charitable',
				'edit_campaigns', // phpcs:ignore
				$this->admin_menu_parent_page,
				array( $this, 'render_welcome_page' )
			);

			foreach ( $this->get_submenu_pages() as $page ) {
				if ( ! isset( $page['page_title'] )
					|| ! isset( $page['menu_title'] )
					|| ! isset( $page['menu_slug'] ) ) {
					continue;
				}

				$page_title = $page['page_title'];
				$menu_title = $page['menu_title'];
				$capability = isset( $page['capability'] ) ? $page['capability'] : $this->admin_menu_capability;
				$menu_slug  = $page['menu_slug'];
				$function   = isset( $page['function'] ) ? $page['function'] : '';

				add_submenu_page(
					$this->admin_menu_parent_page,
					$page_title,
					$menu_title,
					$capability,
					$menu_slug,
					$function
				);
			}
		}

		/**
		 * Returns an array with all the submenu pages.
		 *
		 * @since   1.0.0
		 *
		 * @return array
		 */
		private function get_submenu_pages() {
			$campaign_post_type = get_post_type_object( 'campaign' );
			$donation_post_type = get_post_type_object( 'donation' );

			/**
			 * Filter the list of submenu pages that come
			 * under the Charitable menu tab.
			 *
			 * @since   1.0.0
			 * @version 1.8.1 Added dashboard and reports.
			 * @version 1.8.1.6 Added tools.
			 * @version 1.8.1.8 Added SMTP.
			 * @version 1.8.2 Remove categories, tags, and customize.
			 *
			 * @param array $pages Every page is an array with at least a page_title,
			 *                     menu_title and menu_slug set.
			 */
			$pages = apply_filters(
				'charitable_submenu_pages',
				array(
					array(
						'page_title' => __( 'Dashboard', 'charitable' ),
						'menu_title' => __( 'Dashboard', 'charitable' ),
						'menu_slug'  => 'charitable-dashboard',
						'function'   => array( $this, 'render_dashboard_page' ),
						'capability' => 'manage_charitable_settings',
					),
					array(
						'page_title' => $campaign_post_type->labels->menu_name,
						'menu_title' => $campaign_post_type->labels->menu_name,
						'menu_slug'  => 'edit.php?post_type=campaign',
						'capability' => 'edit_campaigns',
					),
					array(
						'page_title' => $campaign_post_type->labels->add_new,
						'menu_title' => $campaign_post_type->labels->add_new,
						'menu_slug'  => 'post-new.php?post_type=campaign',
						'capability' => 'edit_campaigns',
					),
					array(
						'page_title' => $donation_post_type->labels->menu_name,
						'menu_title' => $donation_post_type->labels->menu_name,
						'menu_slug'  => 'edit.php?post_type=donation',
						'capability' => 'edit_donations',
					),
					array(
						'page_title' => __( 'Charitable Donors', 'charitable' ),
						'menu_title' => __( 'Donors', 'charitable' ) . ' <span class="charitable-menu-new-indicator">&nbsp;' . esc_html__( 'NEW', 'charitable' ) . '!</span>',
						'menu_slug'  => 'charitable-donors',
						'function'   => array( $this, 'render_donors_page' ),
						'capability' => 'manage_charitable_settings',
					),
					array(
						'page_title' => __( 'Charitable Reports', 'charitable' ),
						'menu_title' => __( 'Reports', 'charitable' ),
						'menu_slug'  => 'charitable-reports',
						'function'   => array( $this, 'render_reports_page' ),
						'capability' => 'manage_charitable_settings',
					),
					array(
						'page_title' => __( 'Charitable Tools', 'charitable' ),
						'menu_title' => __( 'Tools', 'charitable' ),
						'menu_slug'  => 'charitable-tools',
						'function'   => array( $this, 'render_tools_page' ),
						'capability' => 'manage_charitable_settings',
					),
					array(
						'page_title' => __( 'Charitable Settings', 'charitable' ),
						'menu_title' => __( 'Settings', 'charitable' ),
						'menu_slug'  => 'charitable-settings',
						'function'   => array( $this, 'render_settings_page' ),
						'capability' => 'manage_charitable_settings',
					),
					array(
						'page_title' => __( 'Charitable Addons', 'charitable' ),
						'menu_title' => '<span style="color:#f18500">' . __( 'Addons', 'charitable' ) . '</span>',
						'menu_slug'  => 'charitable-addons',
						'function'   => array( $this, 'render_addons_directory_page' ),
						'capability' => 'manage_charitable_settings',
					),
					array(
						'page_title' => __( 'SMTP', 'charitable' ),
						'menu_title' => __( 'SMTP', 'charitable' ),
						'menu_slug'  => 'charitable-smtp',
						'function'   => array( $this, 'render_smtp_page' ),
						'capability' => 'manage_charitable_settings',
					),
					array(
						'page_title' => __( 'Growth Tools', 'charitable' ),
						'menu_title' => __( 'Growth Tools', 'charitable' ),
						'menu_slug'  => 'charitable-growth-tools',
						'function'   => array( $this, 'render_growth_tools_page' ),
						'capability' => 'manage_charitable_settings',
					),
				)
			);

			// Remove the item in the array that has the menu_slug 'charitable-donors'.
			unset( $pages[ array_search( 'charitable-donors', array_column( $pages, 'menu_slug' ) ) ] );

			return $pages;
		}

		/**
		 * Set up the redirect to the welcome page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function setup_welcome_redirect() {
			add_action( 'admin_init', array( self::get_instance(), 'redirect_to_welcome' ) );
		}

		/**
		 * Redirect to the welcome page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function redirect_to_welcome() {
			wp_safe_redirect( admin_url( 'admin.php?page=charitable&install=true' ) );
			exit;
		}

		/**
		 * Display the Charitable donors page.
		 *
		 * @since  1.8.5
		 *
		 * @return void
		 */
		public function render_donors_page() {
			charitable_admin_view( 'donors/donors' );
		}

		/**
		 * Display the Charitable reports page.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function render_reports_page() {
			charitable_admin_view( 'reports/reports' );
		}

		/**
		 * Display the Charitable tools page.
		 *
		 * @since  1.8.1.6
		 *
		 * @return void
		 */
		public function render_tools_page() {
			charitable_admin_view( 'tools/tools' );
		}

		/**
		 * Display the Charitable SMTP page.
		 *
		 * @since  1.8.1.8
		 *
		 * @return void
		 */
		public function render_smtp_page() {
			charitable_admin_view( 'smtp/smtp' );
		}

		/**
		 * Display the Charitable Growth Tools page.
		 *
		 * @since  1.8.1.6
		 *
		 * @return void
		 */
		public function render_growth_tools_page() {
			charitable_admin_view( 'growth-tools/growth-tools' );
		}

		/**
		 * Display the Charitable dashboard page.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function render_dashboard_page() {
			charitable_admin_view( 'dashboard/dashboard' );
		}

		/**
		 * Display the Charitable settings page.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function render_settings_page() {
			charitable_admin_view( 'settings/settings' );
		}

		/**
		 * Display the Charitable addons page.
		 *
		 * @since  1.7.0.4
		 *
		 * @return void
		 */
		public function render_addons_directory_page() {
			charitable_admin_view( 'addons-directory/addons-directory' );
		}

		/**
		 * Display the Charitable donations page.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 *
		 * @deprecated 1.4.0
		 */
		public function render_donations_page() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				__( 'Donations page now rendered by WordPress default manage_edit-donation_columns', 'charitable' )
			);

			charitable_admin_view( 'donations-page/page' );
		}

		/**
		 * Display the Charitable welcome page.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function render_welcome_page() {
			charitable_admin_view( 'welcome-page/page' );
		}

		/**
		 * Return a preview URL for the customizer.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		private function get_customizer_campaign_preview_url() {
			$campaign = Charitable_Campaigns::query(
				array(
					'posts_per_page' => 1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'     => '_campaign_end_date',
							'value'   => date( 'Y-m-d H:i:s' ),
							'compare' => '>=',
							'type'    => 'datetime',
						),
						array(
							'key'     => '_campaign_end_date',
							'value'   => 0,
							'compare' => '=',
						),
					),
				)
			);

			if ( $campaign->found_posts ) {
				$url = charitable_get_permalink(
					'campaign_donation',
					array(
						'campaign_id' => current( $campaign->posts ),
					)
				);
			}

			if ( ! isset( $url ) || false === $url ) {
				$url = home_url();
			}

			return urlencode( $url );
		}
	}

endif;
