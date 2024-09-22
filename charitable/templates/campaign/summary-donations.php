<?php
/**
 * Displays the campaign's donation summary.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/summary-donations.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.8.1.9
 */

$campaign = $view_args['campaign'];

if ( ! class_exists('Charitable_Campaign') || ! $campaign instanceof Charitable_Campaign ) {
	return;
}
$donation_summary_content = $campaign->get_donation_summary();

if ( '' !== trim( $donation_summary_content ) ) :

?>
<div class="campaign-figures campaign-summary-item">
	<?php echo $campaign->get_donation_summary(); ?>
</div>

<?php endif; ?>