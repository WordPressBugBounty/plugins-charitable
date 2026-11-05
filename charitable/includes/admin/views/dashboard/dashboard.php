<?php
/**
 * Display the Charitable dashboard new page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Dashboard New
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.8
 * @version   1.8.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The header is automatically included by Charitable_Post_Types::admin_header()
// when the page is registered in the applicable_post_types array

// Get the dashboard instance
$charitable_dashboard = Charitable_Dashboard::get_instance();

/*
Resolved once rather than at each of the three call sites below, so the reworked sections cannot
get out of step with each other - two of them were already written with the gate inline and
disagreeing about it is the kind of thing that ships half a dashboard.

Lite's own gate, not Pro's charitable_dashboard_v3_enabled(): 1.8.14 deliberately gave the Reports
port its own, and 1.8.15 widened that one to cover this screen. function_exists() because this view
is also reached in contexts where the gate file has not loaded.
@since 1.8.15
*/
$charitable_v3 = function_exists( 'charitable_reports_v3_enabled' ) && charitable_reports_v3_enabled();
?>
<div id="charitable-dashboard-v2" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php do_action( 'charitable_maybe_show_notification' ); ?>

	<?php
		/**
		 * Do or render something right before the dashboard new area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_before_admin_dashboard_v2' );
	?>

	<?php $charitable_dashboard->render_dashboard_header(); ?>

	<?php $charitable_dashboard->render_dashboard_notifications(); ?>

	<div class="charitable-dashboard-v2-content">
		<div class="charitable-dashboard-v2-layout">
			<!-- Left Column -->
			<div class="charitable-dashboard-v2-left-column">
				<?php $charitable_dashboard->render_stats_section(); ?>

				<?php $charitable_dashboard->render_tabs_section(); ?>

				<?php
				/*
				Charitable's own fundraising addons, ranked against this install. Sits directly below
				the campaigns/donations tables because it is about what to do next with the fundraising
				those tables describe. Its rail counterpart, Enhance Your Campaigns, carries only
				Awesome Motive and partner plugins, so the two never show the same item.

				On Lite every paid card resolves to the upgrade rung, because
				charitable_current_plan_meets_requirement() does not exist here - see the regression
				test in tests/unit-tests/test-dashboard-integrations-card.php.
				@since 1.8.15
				*/
				if ( $charitable_v3 ) {
					$charitable_dashboard->render_raise_more_section();
				}
				?>

				<?php
				/*
				The integrations logo wall, below the addon recommendations. Left column because the
				cards plus a footer line need the width. @since 1.8.15
				*/
				if ( $charitable_v3 ) {
					$charitable_dashboard->render_integrations_section();
				}
				?>

				<?php $charitable_dashboard->render_upgrade_section(); ?>

				<?php 
				// Show Quick Access in left column for Pro users (when upgrade section is hidden)
				if ( charitable_is_pro() ) {
					$charitable_dashboard->render_quick_access_section();
				}
				?>
			</div>

			<!-- Right Column -->
			<div class="charitable-dashboard-v2-right-column">
				<?php
				/*
				The health card: the version line plus the Site Status rows, at the top of the rail.
				Ported with the dashboard in 1.8.15 and wired in here, which is the step that makes it
				appear - the methods landed earlier but nothing called them, so the whole block was
				dormant.

				Behind the gate the rail is unchanged, so a site that has switched the reworked
				screens off does not get a card whose styling has been removed with them.

				The version header is drawn by the card itself, so this is one call rather than a
				version line plus a grid.
				@since 1.8.15
				*/
				if ( $charitable_v3 ) {
					$charitable_dashboard->render_rail_status_cards();
				}
				?>

				<?php $charitable_dashboard->render_enjoying_section(); ?>

				<?php $charitable_dashboard->render_enhance_campaign_section(); ?>

				<?php $charitable_dashboard->render_site_analysis_section(); ?>

				<?php $charitable_dashboard->render_latest_updates_section(); ?>

				<?php 
				// Show Quick Access in right column for non-Pro users
				if ( ! charitable_is_pro() ) {
					$charitable_dashboard->render_quick_access_section();
				}
				?>
			</div>
		</div>
	</div>

	<?php
		/**
		 * Do or render something right after the dashboard new area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_after_admin_dashboard_v2' );
	?>

	<?php $charitable_dashboard->render_dashboard_scripts(); ?>
</div>