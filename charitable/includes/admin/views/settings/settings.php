<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main settings page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.9
 */

$charitable_active_tab      = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : 'general'; // phpcs:ignore
$charitable_active_sub_tab  = isset( $_GET['sub_tab'] ) ? esc_html( $_GET['sub_tab'] ) : ''; // phpcs:ignore
$charitable_tab_no_form_tag = array( 'import', 'export', 'tools' );
$charitable_group           = isset( $_GET['group'] ) ? esc_html( $_GET['group'] ) : $charitable_active_tab; // phpcs:ignore
$charitable_sections        = charitable_get_admin_settings()->get_sections();
$charitable_sub_sections    = 'marketing' === $charitable_active_tab ? charitable_get_admin_settings()->get_sub_sections_marketing() : array();
$charitable_newsletter_addon_active = class_exists( 'Charitable_Newsletter_Connect' );
$charitable_show_return     = $charitable_group !== $charitable_active_tab;
$charitable_css             = '';

// Resolve the effective sub_tab for routing. Marketing always has a sub_tab; default to newsletters.
if ( 'marketing' === $charitable_active_tab && '' === $charitable_active_sub_tab ) {
	$charitable_active_sub_tab = 'newsletters';
}

if ( $charitable_show_return ) {
	/**
	 * Filter the return link text.
	 *
	 * @since 1.6.19
	 *
	 * @param string $default    The default return link text.
	 * @param string $active_tab The active tab.
	 * @param string $group      The current group.
	 */
	$charitable_return_tab_text = apply_filters(
		'charitable_settings_return_tab_text',
		sprintf(
			/* translators: %s: tab name */
			__( '&#8592; Return to %s', 'charitable' ),
			$charitable_active_tab
		),
		$charitable_active_tab,
		$charitable_group
	);

	/**
	 * Filter the return link URL.
	 *
	 * @since 1.6.19
	 *
	 * @param string $default   The default return link URL
	 * @param string $active_tab The active tab.
	 * @param string $group      The current group.
	 */
	$charitable_return_tab_url = apply_filters(
		'charitable_settings_return_tab_url',
		add_query_arg(
			array( 'tab' => $charitable_active_tab ),
			admin_url( 'admin.php?page=charitable-settings' )
		),
		$charitable_active_tab,
		$charitable_group
	);
}

ob_start();
?>
<div id="charitable-settings" class="wrap">
	<h1 class="screen-reader-text"><?php echo get_admin_page_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
	<h1><?php echo get_admin_page_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( $charitable_sections as $charitable_section_key => $charitable_section_name ) : ?>
			<?php
			$charitable_url_query_arg_array = array( 'tab' => $charitable_section_key );
			if ( 'marketing' === $charitable_section_key ) {
				$charitable_url_query_arg_array['sub_tab'] = 'newsletters';
			}
			?>
			<a href="<?php echo esc_url( add_query_arg( $charitable_url_query_arg_array, admin_url( 'admin.php?page=charitable-settings' ) ) ); ?>" class="nav-tab nav-tab-<?php echo esc_attr( $charitable_section_key ); ?> <?php echo ( esc_attr( $charitable_active_tab ) === esc_attr( $charitable_section_key ) ) ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $charitable_section_name ); ?></a>
		<?php endforeach ?>
	</h2>
	<?php if ( 'marketing' === $charitable_active_tab && ! empty( $charitable_sub_sections ) ) : ?>
		<h3 class="nav-sub-tab-wrapper">
			<?php foreach ( $charitable_sub_sections as $charitable_sub_section_key => $charitable_sub_section_label ) :
				$charitable_sub_tab_slug = str_replace( 'marketing__', '', $charitable_sub_section_key );
				$charitable_is_active    = ( $charitable_active_sub_tab === $charitable_sub_tab_slug );
				?>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'marketing', 'sub_tab' => $charitable_sub_tab_slug ), admin_url( 'admin.php?page=charitable-settings' ) ) ); ?>" class="nav-tab nav-tab-marketing nav-sub-tab-<?php echo esc_attr( $charitable_sub_tab_slug ); ?><?php echo $charitable_is_active ? ' nav-tab-active' : ''; ?>"><?php
					echo wp_kses(
						$charitable_sub_section_label,
						array(
							'span' => array( 'class' => array() ),
						)
					);
				?></a>
			<?php endforeach ?>
		</h3>
	<?php endif; ?>
	<?php if ( $charitable_show_return ) : ?>
		<?php /* translators: %s: active settings tab label */ ?>
		<p><a href="<?php echo esc_url( $charitable_return_tab_url ); ?>"><?php echo $charitable_return_tab_text; // phpcs:ignore ?></a></p>
	<?php endif ?>
	<?php
		/**
		 * Do or render something right before the settings form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $group The settings group we are viewing.
		 */
		do_action( 'charitable_before_admin_settings', $charitable_group );

	?>

	<?php
	// Donors → always show CTA in Lite.
	// Marketing → render the real form only on Newsletters sub-tab when the Newsletter
	//   Connect addon is active (it works on Lite). All other Marketing sub-tabs
	//   (conversion_tracking, google_analytics) — and Newsletters with no addon —
	//   render their CTA via the charitable_pro_settings_cta action.
	$charitable_show_cta = 'donors' === $charitable_active_tab
		|| ( 'marketing' === $charitable_active_tab
			&& ( 'newsletters' !== $charitable_active_sub_tab || ! $charitable_newsletter_addon_active )
		);

	// When rendering the real form for the Newsletters sub-tab, route fields to the
	// marketing__newsletters group. The legacy addon still hooks into the extensions
	// section, so we use 'extensions' as the field-group key for the form (see the
	// bridge in Charitable_Settings::register_settings()).
	if ( 'marketing' === $charitable_active_tab && 'newsletters' === $charitable_active_sub_tab && $charitable_newsletter_addon_active && empty( $_GET['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$charitable_group = 'extensions';
	}

	if ( $charitable_show_cta ) :
		?>
		<?php do_action( 'charitable_pro_settings_cta', $charitable_active_tab ); ?>
	<?php else : ?>

		<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict ?>
		<form method="post" action="options.php">
		<?php endif; ?>
			<table class="form-table">
			<?php
			if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				settings_fields( 'charitable_settings' );
				endif;

				charitable_do_settings_fields( 'charitable_settings_' . $charitable_group, 'charitable_settings_' . $charitable_group );
			?>
			</table>
			<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore ?>
				<?php
					/**
					 * Filter the submit button at the bottom of the settings table.
					 *
					 * @since 1.6.0
					 *
					 * @param string $button The button output.
					 */
					echo apply_filters( 'charitable_settings_button_' . $charitable_group, get_submit_button( null, 'primary', 'submit', true, null ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endif; ?>
		<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag ) ) : // phpcs:ignore ?>
		</form>
		<?php endif; ?>

	<?php endif; ?>

	<?php if ( 'security' === $charitable_active_tab) : ?>
		<?php do_action( 'charitable_pro_settings_cta', $charitable_active_tab ); ?>
	<?php endif; ?>

	<?php
		/**
		 * Do or render something right after the settings form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $group The settings group we are viewing.
		 */
		do_action( 'charitable_after_admin_settings', $charitable_group );
	?>
</div>
<?php

echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
