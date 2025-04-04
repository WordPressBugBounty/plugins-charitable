<?php
/**
 * Displays the campaign status tag.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/status-tag.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$campaign = $view_args['campaign'];
$tag      = $campaign->get_status_tag(); // phpcs:ignore

if ( empty( $tag ) ) {
	return;
}

?>
<div class="campaign-status-tag campaign-status-tag-<?php echo esc_attr( strtolower( str_replace( ' ', '-', $campaign->get_status_key() ) ) ); ?>">
	<?php echo esc_html( $tag ); ?>
</div>
