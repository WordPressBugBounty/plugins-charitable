<?php
/**
 * WPCode integration code snippets page.
 *
 * @since 1.8.1.6
 * @package Charitable/Admin/Tools
 *
 * @var array  $snippets        WPCode snippets list.
 * @var bool   $action_required Indicate that user should install or activate WPCode.
 * @var string $action          Popup button action.
 * @var string $plugin          WPCode Lite download URL | WPCode Lite plugin slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php

$action_required    = false;
$plugin_slug        = 'wpcode';
$plugin_button_html = '';
$snippets           = charitable_get_intergration_wpcode()->load_charitable_snippets();

$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();

$is_installed = $charitable_plugins_third_party->is_plugin_installed( $plugin_slug );
$is_activated = $charitable_plugins_third_party->is_plugin_activated( $plugin_slug );

if ( ! $is_installed ) {
	$action_required = true;

	$popup_title       = esc_html__( 'Please Install WPCode to Use the Charitable Snippet Library', 'charitable' );
	$popup_button_text = esc_html__( 'Install WPCode', 'charitable' );

	$plugin_button_html = $charitable_plugins_third_party->get_plugin_button_html( $plugin_slug, false, '' );

} elseif ( ! $is_activated ) {
	$action_required = true;

	$popup_title       = esc_html__( 'Please Activate WPCode to Use the Charitable Snippet Library', 'charitable' );
	$popup_button_text = esc_html__( 'Activate WPCode', 'charitable' );

	$plugin_button_html = $charitable_plugins_third_party->get_plugin_button_html( $plugin_slug, false, '' );
}

$container_class = $action_required ? 'charitable-wpcode-blur' : '';

?>

<div class="charitable-wpcode">
	<?php if ( $action_required ) : ?>
		<div class="charitable-wpcode-popup">
			<div class="charitable-wpcode-popup-title"><?php echo esc_html( $popup_title ); ?></div>
			<div class="charitable-wpcode-popup-description">
				<?php esc_html_e( 'Using WPCode, you can install Charitable code snippets with 1 click right from this page or the WPCode Library in the WordPress admin.', 'charitable' ); ?>
			</div>

			<?php echo $plugin_button_html; // phpcs:ignore ?>

			<a
					href="https://wordpress.org/plugins/insert-headers-and-footers/?utm_source=charitableplugin&utm_medium=WPCode+WordPress+Repo&utm_campaign=plugin&utm_content=WPCode"
					target="_blank"
					class="charitable-wpcode-popup-link">
				<?php esc_html_e( 'Learn more about WPCode', 'charitable' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="charitable-wpcode-container <?php echo sanitize_html_class( $container_class ); ?>">
		<div class="charitable-setting-row tools charitable-wpcode-header">
			<div class="charitable-wpcode-header-meta">
				<p>
					<?php
					printf(
						wp_kses( /* translators: %s - WPCode library website URL. */
							__( 'Using WPCode, you can install <a href="%1$s" target="_blank" rel="noopener noreferrer">Charitable code snippets</a> with 1 click directly from this page or the <a href="%2$s" target="_blank" rel="noopener noreferrer">WPCode library</a>.', 'charitable' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://library.wpcode.com/profile/wpcharitable/',
						esc_url( admin_url( 'admin.php?page=wpcode-library' ) )
					);
					?>
				</p>
				<p>
					<?php
					printf(
						wp_kses( /* translators: %s - WPCode library website URL. */
							__( '<strong>Need help?</strong> Read more on <a href="%1$s" target="_blank" rel="noopener noreferrer">how to use WPCode</a> and exmaine <a href="%2$s" target="_blank" rel="noopener noreferrer">our documentation</a> to learn more on how to customize Charitable.', 'charitable' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
								'strong' => [],
							]
						),
						'https://www.wpcharitable.com/documentation/customizing-donation-forms-with-code-snippets-using-wpcode/',
						'https://www.wpcharitable.com/documentation/',
					);
					?>
				</p>
			</div>
			<div class="charitable-wpcode-header-search">
				<label for="charitable-wpcode-snippet-search"></label>
				<input
						type="search" placeholder="<?php esc_attr_e( 'Search Snippets', 'charitable' ); ?>"
						id="charitable-wpcode-snippet-search">
			</div>
		</div>

		<div id="charitable-wpcode-snippets-list">
			<div class="charitable-wpcode-snippets-list">
				<?php
				foreach ( $snippets as $snippet ) :
					$button_text       = $snippet['installed'] ? __( 'Edit Snippet', 'charitable' ) : __( 'Install Snippet', 'charitable' );
					$button_type_class = $snippet['installed'] ? 'button-primary' : 'button-secondary';
					$button_action     = $snippet['installed'] ? 'edit' : 'install';
					$badge_text        = $snippet['installed'] ? __( 'Installed', 'charitable' ) : '';
					$library_id        = ! empty( $snippet['library_id'] ) ? ( $snippet['library_id'] ) : false;
					?>
					<div class="charitable-wpcode-snippet">
						<div class="charitable-wpcode-snippet-header">
							<?php if ( $library_id ) : ?>
							<a class="charitable-wpcode-snippet-external-link" title="<?php esc_html_e( 'View this snippet on WPCode.com', 'charitable' ); ?>" href="https://library.wpcode.com/profile/wpcharitable/?code_type=all&order=popular&view=all&search=<?php echo rawurlencode( $snippet['title'] ); ?>" target="_blank"><span class="dashicons dashicons-external"></span></a>
							<?php endif; ?>
							<h3 class="charitable-wpcode-snippet-title"><?php echo esc_html( $snippet['title'] ); ?></h3>
							<div class="charitable-wpcode-snippet-note"><?php echo esc_html( $snippet['note'] ); ?></div>
						</div>
						<div class="charitable-wpcode-snippet-footer">
							<div class="charitable-wpcode-snippet-badge"><?php echo esc_html( $badge_text ); ?></div>
							<a
								href="<?php echo esc_url( $snippet['install'] ); ?>"
								class="button charitable-wpcode-snippet-button <?php echo sanitize_html_class( $button_type_class ); ?>"
								data-action="<?php echo esc_attr( $button_action ); ?>"><?php echo esc_html( $button_text ); ?> </a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div id="charitable-wpcode-no-results"><?php esc_html_e( "Sorry, we didn't find any snippets that match your criteria.", 'charitable' ); ?></div>
		</div>
	</div>
</div>
