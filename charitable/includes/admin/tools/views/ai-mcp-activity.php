<?php
/**
 * The AI Activity panel on Charitable > Tools > AI MCP.
 *
 * A view over the log records Charitable_Abilities_Registrar already writes -
 * no new storage. Included from
 * Charitable_Tools_AI_MCP::render_activity_panel(), which prepares every
 * variable below; this file only renders them.
 *
 * Two honesty notes are load-bearing, not decoration, and both are required
 * to render verbatim rather than be paraphrased:
 *
 * - Reads are never logged by default (Charitable_Abilities_Registrar::
 *   log_read() is opt-in), so this panel can only ever show changes and
 *   refusals, never a full activity trail. Said in the description text below
 *   on every state, including the empty one.
 * - Denial logging depends on `wp_ability_invoked`, core @since 7.1.0. Below
 *   that version writes and refusals still log, but a denial never fires, so
 *   $observation_complete gates a second notice that says so rather than
 *   letting an empty denial column read as "nothing was ever denied".
 *
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.13
 *
 * @var array<int,array<string,string>>    $rows                  get_activity_rows()'s return: up to 25 rows, most recent first.
 * @var bool                               $observation_complete  False on WordPress below 7.1, where denials cannot be recorded at all.
 * @var array<string,array<string,string>> $clients                Active MCP client plugins, from Charitable_Abilities::get_active_mcp_clients().
 * @var string                             $logs_url               The filtered Tools > Logs URL this panel links out to.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_ai_outcome_labels = array(
	'applied' => __( 'Applied', 'charitable' ),
	'refused' => __( 'Refused', 'charitable' ),
	'denied'  => __( 'Denied', 'charitable' ),
);
?>
<div class="charitable-ai-activity charitable-ai-activity-collapsible is-collapsed">

	<header class="charitable-ai-activity-head">
		<h3 class="charitable-ai-activity-title"><?php esc_html_e( 'AI Activity', 'charitable' ); ?></h3>

		<?php if ( ! empty( $clients ) ) : ?>
			<p class="charitable-ai-activity-client">
				<?php
				printf(
					/* translators: %s: comma-separated list of active MCP client plugin names, e.g. "WPVibe". */
					esc_html__( 'Connected via: %s', 'charitable' ),
					esc_html( implode( ', ', wp_list_pluck( $clients, 'label' ) ) )
				);
				?>
			</p>
		<?php endif; ?>

		<?php
		/*
		 * A real <button> with aria-expanded rather than an <a href="#">, which
		 * is what the dashboard's equivalent toggle uses. This panel can run to
		 * 25 rows and is collapsed on load, so the control is the only way to
		 * reach the content and has to be announced correctly.
		 *
		 * The arrow is a dashicon, not the dashboard's `fa fa-angle-down`:
		 * Font Awesome is not enqueued on this tab (see
		 * charitable-tools-admin-hooks.php, which loads only this screen's own
		 * CSS and JS), and every other icon on this screen is already a
		 * dashicon. arrow-down-alt2 is the same downward chevron shape, so it
		 * reads as the same affordance without pulling in a whole icon font.
		 *
		 * `is-collapsed` is set in the markup rather than applied by JS on load,
		 * so the table is never painted and then hidden - no flash of the very
		 * content we are collapsing.
		 */
		?>
		<button
			type="button"
			class="charitable-ai-activity-toggle"
			aria-expanded="false"
			aria-controls="charitable-ai-activity-content"
		>
			<span class="screen-reader-text"><?php esc_html_e( 'Show AI activity', 'charitable' ); ?></span>
			<span class="charitable-ai-activity-angle dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
		</button>
	</header>

	<div class="charitable-ai-activity-content" id="charitable-ai-activity-content">

	<p class="description charitable-ai-activity-note">
		<?php esc_html_e( 'Reads are not recorded. This list shows changes an assistant made and anything it was refused.', 'charitable' ); ?>
	</p>

	<?php if ( ! $observation_complete ) : ?>
		<p class="description charitable-ai-activity-note">
			<?php esc_html_e( 'Refused and denied requests are recorded on WordPress 7.1 and later. This site runs an earlier version, so only completed changes appear.', 'charitable' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( empty( $rows ) ) : ?>

		<div class="charitable-ai-activity-empty">
			<p class="charitable-ai-activity-empty-title"><?php esc_html_e( 'Nothing has used this yet.', 'charitable' ); ?></p>
			<p class="charitable-ai-activity-empty-sub"><?php esc_html_e( 'Once an assistant makes a change, or is refused one, it will show up here.', 'charitable' ); ?></p>
		</div>

	<?php else : ?>

		<div class="charitable-ai-activity-table-wrap">
			<table class="charitable-ai-activity-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'charitable' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Ability', 'charitable' ); ?></th>
						<th scope="col"><?php esc_html_e( 'User', 'charitable' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Outcome', 'charitable' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Details', 'charitable' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $charitable_ai_row ) : ?>
						<?php
						$charitable_ai_outcome = isset( $charitable_ai_row['outcome'] ) ? (string) $charitable_ai_row['outcome'] : '';
						$charitable_ai_label   = isset( $charitable_ai_outcome_labels[ $charitable_ai_outcome ] )
							? $charitable_ai_outcome_labels[ $charitable_ai_outcome ]
							: $charitable_ai_outcome;
						?>
						<tr>
							<td class="charitable-ai-activity-col-date"><?php echo esc_html( $charitable_ai_row['date'] ); ?></td>
							<td class="charitable-ai-activity-col-ability"><code><?php echo esc_html( $charitable_ai_row['ability'] ); ?></code></td>
							<td class="charitable-ai-activity-col-user"><?php echo esc_html( $charitable_ai_row['user'] ); ?></td>
							<td class="charitable-ai-activity-col-outcome">
								<span class="charitable-ai-outcome charitable-ai-outcome-<?php echo esc_attr( $charitable_ai_outcome ); ?>">
									<?php echo esc_html( $charitable_ai_label ); ?>
								</span>
							</td>
							<td class="charitable-ai-activity-col-message"><?php echo esc_html( wp_strip_all_tags( $charitable_ai_row['message'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	<?php endif; ?>

	</div><!-- /.charitable-ai-activity-content -->

	<?php
	/*
	 * OUTSIDE the collapsing region, deliberately. Collapsed is the default
	 * state, and the route to the full log has to stay reachable in it - a
	 * panel that hides both its rows AND the way to see more rows is a dead
	 * end. This is also the one link that still means something when there are
	 * no rows at all, since Tools > Logs holds the history this panel only
	 * summarises.
	 */
	?>
	<p class="charitable-ai-activity-more">
		<a href="<?php echo esc_url( $logs_url ); ?>">
			<?php esc_html_e( 'View full history at Tools > Logs', 'charitable' ); ?>
			<span aria-hidden="true">&rarr;</span>
		</a>
	</p>

</div>
