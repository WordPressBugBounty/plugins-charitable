<?php
/**
 * Display the Logs tab on the Tools page.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_enabled     = Charitable_Log::is_enabled();
$retention_days = Charitable_Log::get_retention_days();
$nonce          = wp_create_nonce( 'charitable_logs_nonce' );
$db             = Charitable_Log::get_instance()->get_db();
$table_exists   = $db->table_exists();
$has_logs       = $table_exists && $db->count() > 0;

// Build base URL for exports.
$base_url = admin_url( 'admin-ajax.php' );

// Current filter params for filtered export.
$filter_params = array(
	'action'     => 'charitable_export_logs_csv',
	'nonce'      => $nonce,
	'log_type'   => isset( $_GET['log_type'] ) ? sanitize_key( $_GET['log_type'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'log_level'  => isset( $_GET['log_level'] ) ? sanitize_key( $_GET['log_level'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'log_source' => isset( $_GET['log_source'] ) ? sanitize_key( $_GET['log_source'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	's'          => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
);

$export_filtered_url = add_query_arg( $filter_params, $base_url );
$export_all_url      = add_query_arg(
	array(
		'action'     => 'charitable_export_logs_csv',
		'nonce'      => $nonce,
		'export_all' => '1',
	),
	$base_url
);
?>

<div class="charitable-logs-wrap">

	<h4 id="charitable-logs-heading"><?php esc_html_e( 'Logs', 'charitable' ); ?></h4>

	<?php // A. Settings bar. ?>
	<div class="charitable-logs-settings">
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Logging', 'charitable' ); ?></th>
				<td>
					<label class="charitable-toggle">
						<input type="checkbox"
							id="charitable-logs-enable"
							value="1"
							<?php checked( $is_enabled ); ?>
						/>
						<span class="charitable-toggle-slider"></span>
					</label>
					<span class="charitable-logs-status" data-enabled="<?php echo esc_attr__( 'Enabled', 'charitable' ); ?>" data-disabled="<?php echo esc_attr__( 'Disabled', 'charitable' ); ?>"><?php echo $is_enabled ? esc_html__( 'Enabled', 'charitable' ) : esc_html__( 'Disabled', 'charitable' ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="charitable-logs-retention"><?php esc_html_e( 'Auto-Delete After', 'charitable' ); ?></label>
				</th>
				<td>
					<select id="charitable-logs-retention">
						<?php
						$retention_options = array( 7, 14, 30, 60, 90 );
						foreach ( $retention_options as $days ) :
							?>
							<option value="<?php echo esc_attr( $days ); ?>" <?php selected( $retention_days, $days ); ?>>
								<?php
								/* translators: %d: number of days */
								echo esc_html( sprintf( _n( '%d day', '%d days', $days, 'charitable' ), $days ) );
								?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Log records older than this will be automatically deleted daily.', 'charitable' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<?php if ( ! $table_exists ) : ?>

		<?php // Missing table notice — shown instead of the list table to avoid DB errors. ?>
		<div class="notice notice-error inline charitable-logs-missing-table" style="margin: 15px 0;">
			<p>
				<strong><?php esc_html_e( 'Logs table missing.', 'charitable' ); ?></strong>
				<?php esc_html_e( 'The charitable_logs database table does not exist. This can happen after upgrading from a version prior to 1.8.11 or on a fresh install where the table was not created.', 'charitable' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Deactivating and reactivating the Charitable plugin will recreate the table.', 'charitable' ); ?>
			</p>
		</div>

	<?php elseif ( $is_enabled || $has_logs ) : ?>

		<?php // B. Action buttons. ?>
		<div class="charitable-logs-actions">
			<?php if ( $has_logs ) : ?>
				<a href="<?php echo esc_url( $export_filtered_url ); ?>" class="button"><?php esc_html_e( 'Export Filtered (CSV)', 'charitable' ); ?></a>
				<a href="<?php echo esc_url( $export_all_url ); ?>" class="button"><?php esc_html_e( 'Export All (CSV)', 'charitable' ); ?></a>
				<button type="button" class="button button-link-delete" id="charitable-delete-all-logs"><?php esc_html_e( 'Delete All Logs', 'charitable' ); ?></button>
			<?php endif; ?>
		</div>

		<?php // C. List table. ?>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="charitable-tools" />
			<input type="hidden" name="tab" value="logs" />
			<?php
			$list_table = new Charitable_Log_List_Table();
			$list_table->prepare_items();
			$list_table->search_box( __( 'Search Logs', 'charitable' ), 'charitable-logs' );
			$list_table->display();
			?>
		</form>

	<?php else : ?>
		<div class="charitable-logs-empty">
			<p><?php esc_html_e( 'Logging is currently disabled. Enable it above to start capturing log entries.', 'charitable' ); ?></p>
		</div>
	<?php endif; ?>

</div>

<?php // D. Modal template. ?>
<div id="charitable-log-modal" class="charitable-log-modal" style="display:none;">
	<div class="charitable-log-modal-overlay"></div>
	<div class="charitable-log-modal-content">
		<button type="button" class="charitable-log-modal-close">&times;</button>
		<div class="charitable-log-modal-body">
			<h2 class="charitable-log-modal-title"></h2>
			<div class="charitable-log-modal-meta">
				<div class="charitable-log-modal-meta-row">
					<span class="charitable-log-modal-label"><?php esc_html_e( 'Date:', 'charitable' ); ?></span>
					<span class="charitable-log-modal-date"></span>
				</div>
				<div class="charitable-log-modal-meta-row">
					<span class="charitable-log-modal-label"><?php esc_html_e( 'Level:', 'charitable' ); ?></span>
					<span class="charitable-log-modal-level"></span>
				</div>
				<div class="charitable-log-modal-meta-row">
					<span class="charitable-log-modal-label"><?php esc_html_e( 'Types:', 'charitable' ); ?></span>
					<span class="charitable-log-modal-types"></span>
				</div>
				<div class="charitable-log-modal-meta-row">
					<span class="charitable-log-modal-label"><?php esc_html_e( 'Source:', 'charitable' ); ?></span>
					<span class="charitable-log-modal-source"></span>
				</div>
			</div>
			<div class="charitable-log-modal-message-wrap">
				<h4><?php esc_html_e( 'Message', 'charitable' ); ?></h4>
				<div class="charitable-log-modal-message"></div>
			</div>
			<div class="charitable-log-modal-ids" style="display:none;">
				<h4><?php esc_html_e( 'Related Records', 'charitable' ); ?></h4>
				<div class="charitable-log-modal-ids-grid"></div>
			</div>
		</div>
	</div>
</div>
