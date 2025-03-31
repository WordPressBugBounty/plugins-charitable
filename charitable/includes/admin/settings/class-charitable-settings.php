<?php
/**
 * Charitable Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Settings' ) ) :

	/**
	 * Charitable_Settings
	 *
	 * @final
	 * @since 1.0.0
	 */
	final class Charitable_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Settings|null
		 */
		private static $instance = null;

		/**
		 * Dynamic groups.
		 *
		 * @since 1.5.7
		 *
		 * @var   array
		 */
		private $dynamic_groups;

		/**
		 * List of static pages, used in some settings.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		private $pages;

		/**
		 * Create object instance.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			do_action( 'charitable_admin_settings_start', $this );

			add_action( 'charitable_after_admin_settings', [ $this, 'settings_cta' ] );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Return the array of tabs used on the settings page.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_sections() {
			/**
			 * Filter the settings tabs.
			 *
			 * @since 1.0.0
			 * @version 1.8.1.6 removed 'tools' tab
			 *
			 * @param string[] $tabs List of tabs in key=>label format.
			 */
			return apply_filters(
				'charitable_settings_tabs',
				array(
					'general'  => __( 'General', 'charitable' ),
					'gateways' => __( 'Payment Gateways', 'charitable' ),
					'emails'   => __( 'Emails', 'charitable' ),
					'privacy'  => __( 'Privacy', 'charitable' ),
					'advanced' => __( 'Advanced', 'charitable' ),
				)
			);
		}

		/**
		 * Optionally add the extensions tab.
		 *
		 * @since  1.3.0
		 *
		 * @param  string[] $tabs The existing set of tabs.
		 * @return string[]
		 */
		public function maybe_add_extensions_tab( $tabs ) {
			$actual_tab = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'general'; // phpcs:ignore

			/* Set the tab to 'extensions' */
			$_GET['tab'] = 'extensions';

			/**
			 * Filter the settings in the extensions tab.
			 *
			 * @since 1.3.0
			 *
			 * @param array $fields Array of fields. Empty by default.
			 */
			$settings = apply_filters( 'charitable_settings_tab_fields_extensions', array() );

			/* Set the tab back to whatever it actually is */
			$_GET['tab'] = $actual_tab;

			if ( ! empty( $settings ) ) {
				$tabs = charitable_add_settings_tab(
					$tabs,
					'extensions',
					__( 'Extensions', 'charitable' ),
					array(
						'index' => 4,
					)
				);
			}

			return $tabs;
		}

		/**
		 * Add the hidden "extensions" section field.
		 *
		 * @since  1.6.7
		 *
		 * @param  array $fields All the settings fields.
		 * @return array
		 */
		public function add_hidden_extensions_setting_field( $fields ) {
			if ( ! array_key_exists( 'extensions', $fields ) ) {
				return $fields;
			}

			$fields['extensions']['section'] = array(
				'title'    => '',
				'type'     => 'hidden',
				'priority' => 10000,
				'value'    => 'extensions',
				'save'     => false,
			);

			return $fields;
		}

		/**
		 * Register setting.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function register_settings() {
			if ( ! charitable_is_settings_view() ) {
				return;
			}

			register_setting( 'charitable_settings', 'charitable_settings', array( $this, 'sanitize_settings' ) );

			$fields = $this->get_fields();

			if ( empty( $fields ) ) {
				return;
			}

			$sections = array_merge( $this->get_sections(), $this->get_dynamic_groups() );

			/* Register each section */
			foreach ( $sections as $section_key => $section ) {
				$section_id = 'charitable_settings_' . $section_key;

				add_settings_section(
					$section_id,
					__return_null(),
					'__return_false',
					$section_id
				);

				if ( ! isset( $fields[ $section_key ] ) || empty( $fields[ $section_key ] ) ) {
					continue;
				}

				/* Sort by priority */
				$section_fields = $fields[ $section_key ];
				uasort( $section_fields, 'charitable_priority_sort' );

				/* Add the individual fields within the section */
				foreach ( $section_fields as $key => $field ) {
					$this->register_field( $field, array( $section_key, $key ) );
				}
			}
		}

		/**
		 * Sanitize submitted settings before saving to the database
		 * This includes: "workaround" for Stripe keys with 1.7.0.0, additional settings options after 1.7.0.6+
		 *
		 * @since  1.0.0
		 *
		 * @param  array $values The submitted values.
		 * @return string
		 */
		public function sanitize_settings( $values ) {

			$old_values = get_option( 'charitable_settings', array() );
			$new_values = array();

			if ( ! is_array( $old_values ) ) {
				$old_values = array();
			}

			if ( ! is_array( $values ) ) {
				$values = array();
			}

			/* Loop through all fields, merging the submitted values into the master array */
			foreach ( $values as $section => $submitted ) {
				$new_values = array_merge( $new_values, $this->get_section_submitted_values( $section, $submitted ) );
			}

			$settings_keys = array_keys( $new_values );

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'santiize_settings' );
				error_log( 'values:' );
				error_log( print_r( $values, true ) );
				error_log( 'old_values:' );
				error_log( print_r( $old_values, true ) );
				error_log( 'new_values:' );
				error_log( print_r( $new_values, true ) );
			}

			// determine if Charitable gateway "test mode" is being changed, and if so add a notice to the user.
			if ( array_key_exists( 'test_mode', $new_values ) && array_key_exists( 'test_mode', $old_values ) && $old_values['test_mode'] !== $new_values['test_mode'] ) {
				$old_settings_keys = array_keys( $old_values );
				$dismissible       = true;
				if ( in_array( 'gateways_stripe', $old_settings_keys ) ) {
					charitable_get_admin_notices()->add_notice( 'Some active payment gateways <strong>(including Stripe)</strong> might have reset their connections due to an update in the test mode. Please check your active payment gateways in ensure they are still connected.', 'warning', false, $dismissible );
				} else {
					charitable_get_admin_notices()->add_notice( 'Some active payment gateways might have reset their connections due to an update in the test mode. Please check your active payment gateways in ensure they are still connected.', 'warning', false, $dismissible );
				}
			}

			if ( in_array( 'gateways_stripe', $settings_keys ) && charitable()->is_stripe_connect_addon() && false === charitable_using_stripe_connect() ) {
				// non-existant array keys in the array for API keys might be a result of the "manual update key" feature which is based on ajax and might not load keys into form if the user doesn't click on the link in the settings to view/edit them.
				if ( ! array_key_exists( 'live_secret_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['live_secret_key'] ) && ( false !== $old_values['gateways_stripe']['live_secret_key'] ) ) {
					$values['gateways_stripe']['live_secret_key'] = $old_values['gateways_stripe']['live_secret_key'];
				}
				if ( ! array_key_exists( 'live_public_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['live_public_key'] ) && ( false !== $old_values['gateways_stripe']['live_public_key'] ) ) {
					$values['gateways_stripe']['live_public_key'] = $old_values['gateways_stripe']['live_secret_key'];
				}
				if ( ! array_key_exists( 'test_secret_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['test_secret_key'] ) && ( false !== $old_values['gateways_stripe']['test_secret_key'] ) ) {
					$values['gateways_stripe']['test_secret_key'] = $old_values['gateways_stripe']['test_secret_key'];
				}
				if ( ! array_key_exists( 'test_public_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['test_public_key'] ) && ( false !== $old_values['gateways_stripe']['test_public_key'] ) ) {
					$values['gateways_stripe']['test_public_key'] = $old_values['gateways_stripe']['test_public_key'];
				}
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'update:' );
					error_log( print_r( $values, true ) );
				}
			}

			// determine if stripe is being returned as new values - if so, then perform the API key save workaround.
			if ( in_array( 'gateways_stripe', $settings_keys ) && charitable()->is_stripe_connect_addon() && false === charitable_using_stripe_connect() ) {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'there is a stripe connect addon but we are not using stripe connect' );
				}
				// the stripe connect addon is installed AND the AM Stripe Connect isn't being used... so it's possible the keys could be manual and the user could be updating them... therefore let's just make sure the new values match the values incoming.
				$new_values['gateways_stripe']['live_secret_key'] = ( isset( $values['gateways_stripe']['live_secret_key'] ) && ( false !== $values['gateways_stripe']['live_secret_key'] ) ) ? $values['gateways_stripe']['live_secret_key'] : null;
				$new_values['gateways_stripe']['live_public_key'] = ( isset( $values['gateways_stripe']['live_public_key'] ) && ( false !== $values['gateways_stripe']['live_public_key'] ) ) ? $values['gateways_stripe']['live_public_key'] : null;
				$new_values['gateways_stripe']['test_secret_key'] = ( isset( $values['gateways_stripe']['test_secret_key'] ) && ( false !== $values['gateways_stripe']['test_secret_key'] ) ) ? $values['gateways_stripe']['test_secret_key'] : null;
				$new_values['gateways_stripe']['test_public_key'] = ( isset( $values['gateways_stripe']['test_public_key'] ) && ( false !== $values['gateways_stripe']['test_public_key'] ) ) ? $values['gateways_stripe']['test_public_key'] : null;
			} elseif ( in_array( 'gateways_stripe', $settings_keys ) ) {
				// otherwise we preserve the keys.
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'charitable debug, final with debug on:' );
					$new_values['gateways_stripe']['live_secret_key'] = ( isset( $values['gateways_stripe']['live_secret_key'] ) && ( false !== $values['gateways_stripe']['live_secret_key'] ) ) ? $values['gateways_stripe']['live_secret_key'] : null;
					$new_values['gateways_stripe']['live_public_key'] = ( isset( $values['gateways_stripe']['live_public_key'] ) && ( false !== $values['gateways_stripe']['live_public_key'] ) ) ? $values['gateways_stripe']['live_public_key'] : null;
					$new_values['gateways_stripe']['test_secret_key'] = ( isset( $values['gateways_stripe']['test_secret_key'] ) && ( false !== $values['gateways_stripe']['test_secret_key'] ) ) ? $values['gateways_stripe']['test_secret_key'] : null;
					$new_values['gateways_stripe']['test_public_key'] = ( isset( $values['gateways_stripe']['test_public_key'] ) && ( false !== $values['gateways_stripe']['test_public_key'] ) ) ? $values['gateways_stripe']['test_public_key'] : null;
				} else {
					$new_values['gateways_stripe']['live_secret_key'] = ( isset( $old_values['gateways_stripe']['live_secret_key'] ) && ( false !== $old_values['gateways_stripe']['live_secret_key'] ) ) ? $old_values['gateways_stripe']['live_secret_key'] : null;
					$new_values['gateways_stripe']['live_public_key'] = ( isset( $old_values['gateways_stripe']['live_public_key'] ) && ( false !== $old_values['gateways_stripe']['live_public_key'] ) ) ? $old_values['gateways_stripe']['live_public_key'] : null;
					$new_values['gateways_stripe']['test_secret_key'] = ( isset( $old_values['gateways_stripe']['test_secret_key'] ) && ( false !== $old_values['gateways_stripe']['test_secret_key'] ) ) ? $old_values['gateways_stripe']['test_secret_key'] : null;
					$new_values['gateways_stripe']['test_public_key'] = ( isset( $old_values['gateways_stripe']['test_public_key'] ) && ( false !== $old_values['gateways_stripe']['test_public_key'] ) ) ? $old_values['gateways_stripe']['test_public_key'] : null;
				}
			}

			// preserve any webhook keys, regardless if stripe connect addon is active or not.
			if ( in_array( 'gateways_stripe', $settings_keys ) ) {
				if ( ! empty( $old_values['gateways_stripe'] ) && array_key_exists( 'test_webhook_id', $old_values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['test_webhook_id'] ) ) {
					$new_values['gateways_stripe']['test_webhook_id'] = $old_values['gateways_stripe']['test_webhook_id'];
				}
				if ( ! empty( $old_values['gateways_stripe'] ) && array_key_exists( 'live_webhook_id', $old_values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['live_webhook_id'] ) ) {
					$new_values['gateways_stripe']['live_webhook_id'] = $old_values['gateways_stripe']['live_webhook_id'];
				}
			}

			// account for incoming legacy licenses (1.7.0.4+)
			// all we are doing is adding the licenses into the values so that the save_license hook can retreieve them and validate, etc.
			if ( ! empty( $values['legacy_licenses'] ) && isset( $values['advanced']['section_hidden_licenses'] ) && 'licenses' === $values['advanced']['section_hidden_licenses'] ) {
				if ( ! isset( $new_values['licenses']['legacy'] ) ) {
					$new_values['licenses_legacy'] = (array) $values['legacy_licenses'];
				}
			}

			// ensure any hex color text files are valid hex fields.
			if ( isset( $new_values['donation_form_default_highlight_colour'] ) ) {
				$color_to_test = esc_html( $new_values['donation_form_default_highlight_colour'] );
				$color_valid   = false;
				// check for a hex color string
				if ( preg_match( '/^#[a-f0-9]{6}$/i', $color_to_test ) ) {
					// hex color is valid
					$color_valid = true;
				}
				// Check for a hex color string without hash.
				if ( preg_match( '/^[a-f0-9]{6}$/i', $color_to_test ) ) {
					// hex color is valid
					$color_valid = true;
					$new_values['donation_form_default_highlight_colour'] = '#' . $color_to_test;
				}
				if ( false === $color_valid ) {
					// not valid, make sure it is blank.
					$new_values['donation_form_default_highlight_colour'] = false;
				}
				// clear the transient that stores the color styles.
				delete_transient( 'charitable_custom_styles' );
			}

			$values = wp_parse_args( $new_values, $old_values );

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'santiize_settings values updated' );
				error_log( print_r( $values, true ) );
			}

			/**
			 * Filter sanitized settings.
			 *
			 * @since 1.0.0
			 *
			 * @param array $values     All values, merged.
			 * @param array $new_values Newly submitted values.
			 * @param array $old_values Old settings.
			 */

			$values = apply_filters( 'charitable_save_settings', $values, $new_values, $old_values );

			// save a temp transient for legacy licenses (1.7.0.8+)
			// purpose is to provide better error reporting (transient should be deleted after settings page (advanced tab) is loaded).
			if ( isset( $_POST['charitable_settings']['legacy_licenses'] ) ) {
				$referer = wp_get_referer();
				if ( false !== strpos( $referer, 'admin.php?page=charitable-settings&tab=advanced' ) ) {
					set_transient( '_charitable_legacy_license_info', $_POST['charitable_settings']['legacy_licenses'], MINUTE_IN_SECONDS );
				}
			}

			$this->add_update_message( __( 'Settings saved', 'charitable' ), 'success' );

			return $values;
		}

		/**
		 * Checkbox settings should always be either 1 or 0.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed $value Submitted value for field.
		 * @param  array $field Field definition.
		 * @return int
		 */
		public function sanitize_checkbox_value( $value, $field ) {
			if ( isset( $field['type'] ) && 'checkbox' == $field['type'] ) {
				$value = intval( $value && 'on' == $value );
			}

			return $value;
		}

		/**
		 * Render field. This is the default callback used for all fields, unless an alternative callback has been specified.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Field definition.
		 * @return void
		 */
		public function render_field( $args ) {
			$field_type = isset( $args['type'] ) ? $args['type'] : 'text';

			charitable_admin_view( 'settings/' . $field_type, $args );
		}

		/**
		 * Returns an array of all pages in the id=>title format.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_pages() {
			if ( ! isset( $this->pages ) ) {
				$this->pages = charitable_get_pages_options();
			}

			return $this->pages;
		}

		/**
		 * Add an update message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message     The message text.
		 * @param  string  $type        The type of message. Options: 'error', 'success', 'warning', 'info'.
		 * @param  boolean $dismissible Whether the message can be dismissed.
		 * @return void
		 */
		public function add_update_message( $message, $type = 'error', $dismissible = true ) {
			if ( ! in_array( $type, array( 'error', 'success', 'warning', 'info' ) ) ) {
				$type = 'error';
			}

			charitable_get_admin_notices()->add_notice( $message, $type, false, $dismissible );
		}

		/**
		 * Recursively add settings fields, given an array.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $field The setting field.
		 * @param  array $keys  Array containing the section key and field key.
		 * @return void
		 */
		private function register_field( $field, $keys ) {
			$section_id = 'charitable_settings_' . $keys[0];

			if ( isset( $field['render'] ) && ! $field['render'] ) {
				return;
			}

			/* Drop the first key, which is the section identifier */
			$field['name'] = implode( '][', $keys );

			if ( ! $this->is_dynamic_group( $keys[0] ) ) {
				array_shift( $keys );
			}

			$field['key']     = $keys;
			$field['classes'] = $this->get_field_classes( $field );
			$callback         = isset( $field['callback'] ) ? $field['callback'] : array( $this, 'render_field' );
			$label            = $this->get_field_label( $field, end( $keys ) );

			add_settings_field(
				sprintf( 'charitable_settings_%s', implode( '_', $keys ) ),
				$label,
				$callback,
				$section_id,
				$section_id,
				$field
			);
		}

		/**
		 * Return the label for the given field.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $field The field definition.
		 * @param  string $key   The field key.
		 * @return string
		 */
		private function get_field_label( $field, $key ) { // phpcs:ignore
			$label = '';

			if ( isset( $field['label_for'] ) ) {
				$label = $field['label_for'];
			}

			if ( isset( $field['title'] ) ) {
				$label = $field['title'];
			}

			return $label;
		}

		/**
		 * Return a space separated string of classes for the given field.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		private function get_field_classes( $field ) {
			$classes = array( 'charitable-settings-field' );

			if ( isset( $field['class'] ) ) {
				$classes[] = $field['class'];
			}

			/**
			 * Filter the list of classes to apply to settings fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $classes The list of classes.
			 * @param array $field   The field definition.
			 */
			$classes = apply_filters( 'charitable_settings_field_classes', $classes, $field );

			return implode( ' ', $classes );
		}

		/**
		 * Return an array with all the fields & sections to be displayed.
		 *
		 * @uses   charitable_settings_fields
		 * @see    Charitable_Settings::register_setting()
		 * @since  1.0.0
		 *
		 * @return array
		 */
		private function get_fields() {
			/**
			 * Use the charitable_settings_tab_fields to include the fields for new tabs.
			 * DO NOT use it to add individual fields. That should be done with the
			 * filters within each of the methods.
			 */
			$fields = array();

			foreach ( $this->get_sections() as $section_key => $section ) {
				/**
				 * Filter the array of fields to display in a particular tab.
				 *
				 * @since 1.0.0
				 *
				 * @param array $fields Array of fields.
				 */
				$fields[ $section_key ] = apply_filters( 'charitable_settings_tab_fields_' . $section_key, array() );
			}

			/**
			 * Filter the array of settings fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $fields Array of fields.
			 */
			return apply_filters( 'charitable_settings_tab_fields', $fields );
		}

		/**
		 * Get the submitted value for a particular setting.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key       The key of the setting being saved.
		 * @param  array  $field     The setting field.
		 * @param  array  $submitted The submitted values.
		 * @param  string $section   The section being saved.
		 * @return mixed|null        Returns null if the value was not submitted or is not applicable.
		 */
		private function get_setting_submitted_value( $key, $field, $submitted, $section ) {
			$value = null;

			if ( isset( $field['save'] ) && ! $field['save'] ) {
				return $value;
			}

			$field_type = isset( $field['type'] ) ? $field['type'] : '';

			switch ( $field_type ) {

				case 'checkbox':
					$value = intval( array_key_exists( $key, $submitted ) && ( 'on' === $submitted[ $key ] || 1 === (int) $submitted[ $key ] ) );
					break;

				case 'multi-checkbox':
					$value = isset( $submitted[ $key ] ) ? $submitted[ $key ] : array();
					break;

				case '':
				case 'heading':
					return $value;

				default:
					if ( ! array_key_exists( $key, $submitted ) ) {
						return $value;
					}

					$value = $submitted[ $key ];

			}//end switch

			/**
			 * General way to sanitize values. If you only need to sanitize a
			 * specific setting, used the filter below instead.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed  $value     The current setting value.
			 * @param array  $field     The field configuration.
			 * @param array  $submitted All submitted data.
			 * @param string $key       The setting key.
			 * @param string $section   The section being saved.
			 */
			$value = apply_filters( 'charitable_sanitize_value', $value, $field, $submitted, $key, $section );

			/**
			 * Sanitize the setting value.
			 *
			 * The filter hook is formatted like this: charitable_sanitize_value_{$section}_{$key}.
			 *
			 * @since 1.5.0
			 *
			 * @param mixed $value     The current setting value.
			 * @param array $field     The field configuration.
			 * @param array $submitted All submitted data.
			 */
			return apply_filters( 'charitable_sanitize_value_' . $section . '_' . $key, $value, $field, $submitted );
		}

		/**
		 * Return the submitted values for the given section.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $section   The section being edited.
		 * @param  array  $submitted The submitted values.
		 * @return array
		 */
		private function get_section_submitted_values( $section, $submitted ) {
			$values      = array();
			$form_fields = $this->get_fields();

			if ( ! isset( $form_fields[ $section ] ) ) {
				return $values;
			}

			foreach ( $form_fields[ $section ] as $key => $field ) {
				$value = $this->get_setting_submitted_value( $key, $field, $submitted, $section );

				if ( is_null( $value ) ) {
					continue;
				}

				if ( $this->is_dynamic_group( $section ) ) {
					$values[ $section ][ $key ] = $value;
					continue;
				}

				$values[ $key ] = $value;
			}

			return $values;
		}

		/**
		 * Return list of dynamic groups.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		private function get_dynamic_groups() {
			if ( ! isset( $this->dynamic_groups ) ) {
				/**
				 * Filter the list of dynamic groups.
				 *
				 * @since 1.0.0
				 *
				 * @param array $groups The dynamic groups.
				 */
				$this->dynamic_groups = apply_filters( 'charitable_dynamic_groups', array() );
			}

			return $this->dynamic_groups;
		}

		/**
		 * Returns whether the given key indicates the start of a new section of the settings.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $composite_key The unique key for this group.
		 * @return boolean
		 */
		private function is_dynamic_group( $composite_key ) {
			return array_key_exists( $composite_key, $this->get_dynamic_groups() );
		}

		/* DEPRECATED FUNCTIONS */

		/**
		 * Get the update messages.
		 *
		 * @deprecated 1.7.0
		 *
		 * @since 1.4.13 Deprecated.
		 */
		public function get_update_messages() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.13',
				'Charitable_Admin_Notices::get_notices()'
			);

			return charitable_get_admin_notices()->get_notices();
		}

		/**
		 * Display upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since 1.7.0.4
		 *
		 * @param string $view Current view inside the plugin settings page.
		 */
		public function settings_cta( $view = false, $css_class = 'reports-lite-cta', $show_close_button = false ) { // phpcs:ignore

			if ( charitable_is_pro() ) {
				// no need to display this cta since they have a valid license.
				return;
			}

			if ( get_option( 'charitable_lite_settings_upgrade', false ) || apply_filters( 'charitable_lite_settings_upgrade', false ) ) {
				return;
			}
			?>
			<div class="charitable-cta-lite-to-pro <?php echo esc_attr( $css_class ); ?> <?php echo $show_close_button ? 'show-close-button' : 'no-close-button'; ?>">
				<?php if ( $show_close_button ) : ?>
					<button type="button" class="button-link charitable-banner-dismiss dismiss">x</button>
				<?php endif; ?>
				<div class="charitable-cta-content">
					<div class="charitable-cta-content-left">
						<h5><?php esc_html_e( 'Upgrade Charitable to Pro and Unlock all Features!', 'charitable' ); ?></h5>
						<p><?php esc_html_e( 'Thanks for being a loyal Charitable Lite user. Upgrade to Charitable Pro and take your fundraising to the next level!', 'charitable' ); ?></p>
						<div class="charitable-cta-list">
							<ul>
								<li><?php esc_html_e( 'Manage donations in a centralized dashboard', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'View, edit, and export your donor database', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Allow donors to give anonymously', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Give donors their own dashboard', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Send automated thank you and confirmation emails', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Get powerful insights with built-in analytics & reports', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Build steady income with recurring donations', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Auto-generate PDF & Annual receipts', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Ask donors to cover transaction fees', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Give donors their preferred payment methods', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Integrate with your email marketing service', 'charitable' ); ?></li>
								<li><?php esc_html_e( 'Raise more funds with peer-to-peer fundraising', 'charitable' ); ?></li>
							</ul>
							<div class="charitable-cta-button-container">
								<a class="button button-primary" href="<?php echo esc_url( charitable_pro_upgrade_url( 'settings-upgrade' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Charitable Pro', 'charitable' ); ?></a>
								<p>
										<?php

										$bonus_text = sprintf(
											/* translators: %1$s: HTML-wrapped "Bonus:" text, %2$s: HTML-wrapped discount amount */
											__( '%1$s Get up to %2$s off regular price', 'charitable' ),
											'<strong>' . esc_html__( 'Bonus:', 'charitable' ) . '</strong><br/>',
											'<span class="green">$300</span>'
										);

										echo wp_kses(
											$bonus_text,
											array(
												'br'     => array(),
												'strong' => array(),
												'span'   => array(
													'class' => array(),
												),
											)
										);
										?>
								</p>
							</div>
						</div>
					</div>
					<div class="charitable-cta-featured-image">

					</div>
				</div>
			</div>
			<script type="text/javascript">
				jQuery( function ( $ ) {
					$( document ).on( 'click', '.settings-lite-cta .dismiss', function ( event ) {
						event.preventDefault();
						$.post( ajaxurl, {
							action: 'charitable_lite_settings_upgrade',
							charitable_action: 'remove_lite_cta'
						} );
						$( '.settings-lite-cta' ).remove();
					} );
				} );
			</script>
			<?php
		}

		/**
		 * Dismiss upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since 1.7.0.4
		 *
		 * @return void
		 */
		public function settings_cta_dismiss() {

			if ( ! charitable_current_user_can() ) {
				wp_send_json_error();
			}

			update_option( 'charitable_lite_settings_upgrade', time() );

			wp_send_json_success();
		}
	}

endif;
