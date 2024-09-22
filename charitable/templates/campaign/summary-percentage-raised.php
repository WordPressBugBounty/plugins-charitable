<?php
/**
 * Displays the percentage of its goal that the campaign has raised.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/summary-percentage-raised.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.0.0
 */

$campaign = $view_args['campaign'];

?>
<div class="campaign-raised campaign-summary-item">
	<?php
	printf(
		/* translators: %s: percentage raised */
		_x( '%s Raised', 'percentage raised', 'charitable' ),
		'<span class="amount">' . $campaign->get_percent_donated() . '</span>'
	);
	?>
</div>
