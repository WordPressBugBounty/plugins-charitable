<?php
/**
 * Class that sets up editer blocks for Charitable.
 *
 * @package   Charitable/Classes/Charitable_Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Blocks' ) ) :

	/**
	 * Charitable_Admin_Blocks
	 *
	 * @final
	 * @since 1.8.0
	 */
	final class Charitable_Admin_Blocks {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Admin|null
		 */
		private static $instance = null;

		/**
		 * Set up the class.
		 *
		 * @since  1.8.0
		 */
		public function __construct() {

			if ( $this->allow_load() ) {

				add_action( 'init', array( $this, 'register_blocks' ) );

				do_action( 'charitable_admin_blocks_loaded' );

			}
		}

		/**
		 * Indicate if current integration is allowed to load.
		 *
		 * @since 1.4.8
		 *
		 * @return bool
		 */
		public function allow_load() {

			return function_exists( 'register_block_type' );
		}

		/**
		 * Registers the block using the metadata loaded from the `block.json` file.
		 * Behind the scenes, it registers also all assets so they can be enqueued
		 * through the block editor in the corresponding context.
		 *
		 * @see https://developer.wordpress.org/reference/functions/register_block_type/
		 *
		 * @since  1.8.0
		 */
		public function register_blocks() {
			if ( ! $this->is_block_registered( 'create-block/campaignblock' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_campaign_widget' ),
					)
				);
			}
		}

		/**
		 * Checks if a block is registered.
		 *
		 * @since  1.8.0
		 *
		 * @param string $block_name The block name.
		 * @return bool
		 */
		private function is_block_registered( $block_name ) {

			if ( class_exists( 'WP_Block_Type_Registry' ) ) {
				return WP_Block_Type_Registry::get_instance()->is_registered( $block_name );
			}

			return false;

		}

		/**
		 * Renders the campaign widget block.
		 *
		 * @since  1.8.0
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_campaign_widget( $attributes ) {
			$campaign_id = $attributes['campaignID'];

			ob_start();

			echo do_shortcode( '[campaign id="' . intval( $campaign_id ) . '"]' );

			return ob_get_clean();
		}

		/**
		 * Loads admin-only scripts and stylesheets.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {

			$min     = ''; // charitable_get_min_suffix(); // todo: undo this.
			$version = charitable()->get_version();

			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			// add data only for block editor pages, where the campaign block (among others) would be used.
			if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {

				wp_localize_script(
					'create-block-campaignblock-editor-script',
					'charitable_block_data',
					$this->get_localized_strings_block_data()
				);
			}

			do_action( 'after_charitable_admin_block_enqueue_scripts', $min, $version, $assets_dir );
		}

		/**
		 * Retrieves localized strings for the Charitable Blocks editor.
		 *
		 * @since 1.8.0
		 *
		 * @param array $args Optional. Arguments to customize the localized strings.
		 * @return array An array of localized strings.
		 */
		private function get_localized_strings_block_data( $args = array() ) {

			$defaults = array(
				'post_type'      => array( Charitable::CAMPAIGN_POST_TYPE ),
				'posts_per_page' => -1,
				'post_status'    => array( 'publish' ),
			);

			$args = wp_parse_args( $args, $defaults );

			$query = new WP_Query( $args );

			$campaigns_for_dropdown = array();

			if ( ! empty( $query->posts ) ) :
				foreach ( $query->posts as $post_id => $campaign_post ) :
					$campaigns_for_dropdown[] = array(
						'label' => $campaign_post->post_title,
						'value' => $campaign_post->ID,
					);
				endforeach;
				// sory multidimensional array alphabetically by label.
				usort(
					$campaigns_for_dropdown,
					function ( $a, $b ) {
						return strcmp( $a['label'], $b['label'] );
					}
				);
			endif;

			// add blank element.
			array_unshift(
				$campaigns_for_dropdown,
				array(
					'label' => '',
					'value' => '',
				)
			);

			$strings = array(
				'campaigns'              => $campaigns_for_dropdown,
				'charitable_addons_page' => esc_url( admin_url( 'admin.php?page=charitable-addons' ) ),
				'version'                => '1.8.0',
				'logo'                   => esc_url( home_url( 'wp-content/plugins/charitable/assets/images/charitable-header-logo.png' ) ),
				'charitable_assets_dir'  => apply_filters(
					'charitable_campaign_builder_charitable_assets_dir',
					charitable()->get_path( 'directory', false ) . 'assets/'
				),
			);

			return $strings;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.0
		 *
		 * @return Charitable_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	new Charitable_Admin_Blocks();

endif;
