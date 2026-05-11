<?php
/**
 * Form panel (Lite upgrade-prompt stub).
 *
 * Lite does not ship the visual form builder. This panel renders only the
 * navigation button; clicking it opens an upgrade modal instead of switching
 * views. The full panel is provided by Charitable Pro.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.10.5
 * @version   1.8.10.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Form' ) ) :

	/**
	 * Form panel (Lite upgrade stub).
	 *
	 * @since 1.8.10.5
	 */
	class Charitable_Builder_Panel_Form extends Charitable_Builder_Panel {

		/**
		 * All systems go.
		 *
		 * @since 1.8.10.5
		 */
		public function init() {

			$this->name    = esc_html__( 'Form', 'charitable' );
			$this->slug    = 'form';
			$this->icon    = 'panel_form.svg';
			$this->order   = 25;
			$this->sidebar = false;
		}

		/**
		 * Render the navigation button.
		 *
		 * Overrides the base implementation so the button does not carry a
		 * `data-panel` attribute — we intercept the click in JS and open an
		 * upgrade modal rather than switching panels.
		 *
		 * @since 1.8.10.5
		 *
		 * @param mixed  $campaign Current campaign object.
		 * @param string $view     The current view.
		 */
		public function button( $campaign, $view ) {
			?>
			<button type="button" class="charitable-panel-<?php echo esc_attr( $this->slug ); ?>-button charitable-panel-form-upgrade">
				<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/' . $this->icon ); ?>" />
				<span><?php echo esc_html( $this->name ); ?></span>
			</button>
			<?php
		}

		/**
		 * Suppress panel output — Lite has no Form panel content.
		 *
		 * @since 1.8.10.5
		 *
		 * @param object $campaign Current campaign object.
		 * @param string $view     Active Campaign Builder view (panel).
		 */
		public function panel_output( $campaign, $view = 'design' ) {
		}
	}

endif;

new Charitable_Builder_Panel_Form();
