<?php
/**
 * Display the main settings page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.19
 * @version   1.8.5.1
 */

$active_tab      = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : 'general'; // phpcs:ignore
$tab_no_form_tag = array( 'import', 'export', 'tools' );
$group           = isset( $_GET['group'] ) ? esc_html( $_GET['group'] ) : $active_tab; // phpcs:ignore
$sections        = charitable_get_admin_settings()->get_sections();
$show_return     = $group !== $active_tab;
$css             = '';

if ( $show_return ) {
	/**
	 * Filter the return link text.
	 *
	 * @since 1.6.19
	 *
	 * @param string $default    The default return link text.
	 * @param string $active_tab The active tab.
	 * @param string $group      The current group.
	 */
	$return_tab_text = apply_filters(
		'charitable_settings_return_tab_text',
		sprintf(
			/* translators: %s: tab name */
			__( '&#8592; Return to %s', 'charitable' ),
			$active_tab
		),
		$active_tab,
		$group
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
	$return_tab_url = apply_filters(
		'charitable_settings_return_tab_url',
		add_query_arg(
			array( 'tab' => $active_tab ),
			admin_url( 'admin.php?page=charitable-settings' )
		),
		$active_tab,
		$group
	);
}

ob_start();
?>
<div id="charitable-settings" class="wrap">
	<h1 class="screen-reader-text"><?php echo get_admin_page_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
	<h1><?php echo get_admin_page_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( $sections as $section_key => $section_name ) : ?>
			<?php

			$css                 = '';
			$url_query_arg_array = array( 'tab' => $section_key );
			if ( 'security' === $section_key && defined( 'CHARITABLE_SPAMBLOCKER_FEATURE_PLUGIN' ) ) {
				$css = 'no-pro-tab';
			}

			?>
			<a href="<?php echo esc_url( add_query_arg( $url_query_arg_array, admin_url( 'admin.php?page=charitable-settings' ) ) ); ?>" class="nav-tab nav-tab-<?php echo esc_attr( $section_key ); ?> <?php echo $css; ?> <?php echo ( esc_attr( $active_tab ) === esc_attr( $section_key ) ) ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $section_name ); ?></a>
		<?php endforeach ?>
	</h2>
	<?php if ( $show_return ) : ?>
		<?php /* translators: %s: active settings tab label */ ?>
		<p><a href="<?php echo esc_url( $return_tab_url ); ?>"><?php echo $return_tab_text; ?></a></p>
	<?php endif ?>
	<?php
		/**
		 * Do or render something right before the settings form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $group The settings group we are viewing.
		 */
		do_action( 'charitable_before_admin_settings', $group );
	?>
	<?php
	if ( 'marketing' === $active_tab || 'donors' === $active_tab || ( ! defined( 'CHARITABLE_SPAMBLOCKER_FEATURE_PLUGIN' ) && 'security' === $active_tab ) ) :
		?>
		<?php do_action( 'charitable_pro_settings_cta', $active_tab ); ?>
	<?php else : ?>

		<?php if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) : // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict ?>
		<form method="post" action="options.php">
		<?php endif; ?>
			<table class="form-table">
			<?php
			if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) : // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				settings_fields( 'charitable_settings' );
				endif;

				charitable_do_settings_fields( 'charitable_settings_' . $group, 'charitable_settings_' . $group );
			?>
			</table>
			<?php if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) : // phpcs:ignore ?>
				<?php
					/**
					 * Filter the submit button at the bottom of the settings table.
					 *
					 * @since 1.6.0
					 *
					 * @param string $button The button output.
					 */
					echo apply_filters( 'charitable_settings_button_' . $group, get_submit_button( null, 'primary', 'submit', true, null ) );
				?>
			<?php endif; ?>
		<?php if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) : // phpcs:ignore ?>
		</form>
		<?php endif; ?>

	<?php endif; ?>
	<?php
		/**
		 * Do or render something right after the settings form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $group The settings group we are viewing.
		 */
		do_action( 'charitable_after_admin_settings', $group );
	?>
</div>
<?php
echo ob_get_clean();
