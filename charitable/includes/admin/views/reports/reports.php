<?php
/**
 * Display the main reports page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8,1
 * @version   1.8.1
 */

$active_tab  = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : 'overview';  // phpcs:ignore
$group       = isset( $_GET['group'] ) ? esc_html( $_GET['group'] ) : $active_tab; // phpcs:ignore
$sections    = charitable_get_admin_reports()->get_sections();
$show_return = $group !== $active_tab;

ob_start();
?>
<div id="charitable-reports" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php do_action( 'charitable_maybe_show_notification' ); ?>

	<h1><?php echo esc_html__( 'Reports', 'charitable' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $sections as $the_tab => $name ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $the_tab ), admin_url( 'admin.php?page=charitable-reports' ) ) ); ?>" class="nav-tab <?php echo esc_attr( sanitize_title( $name ) ); ?> <?php echo $active_tab === $the_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $name ); ?></a>
		<?php endforeach ?>
	</h2>

	<?php
		/**
		 * Do or render something right before the reports form.
		 *
		 * @since 1.8.1
		 *
		 * @param string $group The reports group we are viewing.
		 */
		do_action( 'charitable_before_admin_reports', $group );

	switch ( $active_tab ) {
		case 'overview':
			charitable_admin_view( 'reports/overview' );
			break;

		case 'advanced':
			charitable_admin_view( 'reports/advanced' );
			break;

		case 'activity':
			charitable_admin_view( 'reports/activity' );
			break;

		case 'donors':
			charitable_admin_view( 'reports/donors' );
			break;

		case 'analytics':
			charitable_admin_view( 'reports/analytics' );
			break;

		default:
			charitable_admin_view( 'reports/overview' );
			break;
	}

		/**
		 * Do or render something right after the reports form.
		 *
		 * @since 1.8.1
		 *
		 * @param string $group The reports group we are viewing.
		 */
		do_action( 'charitable_after_admin_reports', $group );
	?>
</div>
<?php
echo ob_get_clean(); // phpcs:ignore
