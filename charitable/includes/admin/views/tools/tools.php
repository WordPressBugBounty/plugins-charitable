<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main tools page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 * @version   1.8.1.6
 * @version   1.8.8.6
 */

$charitable_active_tab      = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : 'export';  // phpcs:ignore
$charitable_group           = isset( $_GET['group'] ) ? esc_html( $_GET['group'] ) : $charitable_active_tab; // phpcs:ignore
$charitable_tab_no_form_tag = array( 'import', 'export', 'system-info', 'snippets', 'customize' );
$charitable_tab_no_fields   = array( 'system-info', 'snippets', 'customize' );
$charitable_sections        = charitable_get_admin_tools()->get_sections();
$charitable_show_return     = $charitable_group !== $charitable_active_tab;

ob_start();
?>

<div id="charitable-tools" class="wrap">
	<h1 class="screen-reader-text"><?php echo get_admin_page_title(); // phpcs:ignore ?></h1>
	<h1><?php echo get_admin_page_title(); // phpcs:ignore ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( $charitable_sections as $charitable_tab => $charitable_name ) : // phpcs:ignore ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $charitable_tab ), admin_url( 'admin.php?page=charitable-tools' ) ) ); ?>" class="nav-tab <?php echo $charitable_active_tab == $charitable_tab ? 'nav-tab-active' : ''; ?>"><?php echo $charitable_name; // phpcs:ignore ?></a>
		<?php endforeach ?>
	</h2>
	<?php
		/**
		 * Do or render something right before the tools form.
		 *
		 * @since 1.8.1.6
		 *
		 * @param string $group The tools group we are viewing.
		 */
		do_action( 'charitable_before_admin_tools', $charitable_group );
	?>
	<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag, true ) ) : ?>
	<form method="post" action="options.php">
	<?php endif; ?>
		<table class="form-table">
		<?php
		if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag, true ) ) :
			settings_fields( 'charitable_tools' );
		endif;

		if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_fields, true ) ) :

			charitable_do_tools_fields( 'charitable_tools_' . $charitable_group, 'charitable_tools_' . $charitable_group );

		else :

			charitable_admin_view( 'tools/' . $charitable_active_tab );

		endif;
		?>
		</table>
		<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag, true ) ) : ?>
			<?php
				/**
				 * Filter the submit button at the bottom of the tools table.
				 *
				 * @since 1.6.0
				 *
				 * @param string $button The button output.
				 */
				echo apply_filters( 'charitable_tools_button_' . esc_attr( $charitable_group ), get_submit_button( null, 'primary', 'submit', true, null ) ); // phpcs:ignore
			?>
		<?php endif; ?>
	<?php if ( ! in_array( strtolower( $charitable_active_tab ), $charitable_tab_no_form_tag, true ) ) : ?>
	</form>
	<?php endif; ?>
	<?php
		/**
		 * Do or render something right after the tools form.
		 *
		 * @since 1.8.1.6
		 *
		 * @param string $group The tools group we are viewing.
		 */
		do_action( 'charitable_after_admin_tools', $charitable_group );
	?>
</div>
<?php
echo ob_get_clean(); // phpcs:ignore
