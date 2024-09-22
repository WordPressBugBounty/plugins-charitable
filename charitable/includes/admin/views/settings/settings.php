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
 */

$active_tab      = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : 'general';
$tab_no_form_tag = array( 'import', 'export', 'tools' );
$group           = isset( $_GET['group'] ) ? esc_html( $_GET['group'] ) : $active_tab;
$sections        = charitable_get_admin_settings()->get_sections();
$show_return     = $group != $active_tab;

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
	<h1 class="screen-reader-text"><?php echo get_admin_page_title(); ?></h1>
	<h1><?php echo get_admin_page_title(); ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( $sections as $tab => $name ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $tab ), admin_url( 'admin.php?page=charitable-settings' ) ) ); ?>" class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
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
	<?php if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) : ?>
	<form method="post" action="options.php">
	<?php endif; ?>
		<table class="form-table">
		<?php
			 if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) :
				settings_fields( 'charitable_settings' );
			 endif;

			charitable_do_settings_fields( 'charitable_settings_' . $group, 'charitable_settings_' . $group );
		?>
		</table>
		<?php if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) : ?>
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
	<?php if ( ! in_array( strtolower( $active_tab ), $tab_no_form_tag ) ) : ?>
	</form>
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
