<?php
/**
 * Display the upgrades page.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Upgrades
 * @since   1.0.0
 * @version 1.8.1
 */

$page   = $view_args['page'];
$action = $page->get_action();
$step   = $page->get_step();
$total  = $page->get_total();
$number = $page->get_number();
$nonce  = $page->get_nonce();
$steps  = $page->get_steps( $total, $number );
$args   = array(
	'charitable-upgrade' => $action,
	'page'               => 'charitable-upgrades',
	'step'               => $step,
	'total'              => $total,
	'steps'              => $steps,
	'nonce'              => $nonce,
);

$timeout_url  = 'index.php?charitable_action=' . $action;
$timeout_url .= '&step=' . $step;

if ( $total ) {
	$timeout_url .= '&total=' . $total;
}

if ( $nonce ) {
	$timeout_url .= '&nonce=' . $nonce;
}


update_option( 'charitable_doing_upgrade', $args );

if ( $step > $steps ) {
	// Prevent a weird case where the estimate was off. Usually only a couple.
	$steps = $step;
}
?>
<div class="wrap">
	<h2><?php _e( 'Charitable - Upgrades', 'charitable' ); ?></h2>

	<div id="charitable-upgrade-status">
		<p><?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'charitable' ); ?></p>
		<?php if ( ! empty( $total ) ) : ?>
			<p><strong><?php printf( __( 'Step %d of approximately %d running', 'charitable' ), $step, $steps ); ?></strong></p>
		<?php endif; ?>
	</div>
	<script type="text/javascript">
		setTimeout(function() { document.location.href = "<?php echo $timeout_url; ?>"; }, 250);
	</script>
</div>