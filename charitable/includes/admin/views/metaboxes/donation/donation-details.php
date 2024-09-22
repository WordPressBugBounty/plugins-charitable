<?php
/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.7.0.8
 */

global $post;

$meta = charitable_get_donation( $post->ID )->get_donation_meta();

?>
<div id="charitable-donation-details-metabox" class="charitable-metabox">
	<dl>
	<?php do_action( 'charitable_before_admin_donation_details_list', $post ); ?>
	<?php foreach ( $meta as $key => $details ) : ?>
		<dt><?php echo esc_html( $details['label'] ); ?></dt>
		<dd><?php echo $details['value']; ?></dd>
	<?php endforeach ?>
	<?php do_action( 'charitable_after_admin_donation_details_list', $post ); ?>
	</dl>
</div>
