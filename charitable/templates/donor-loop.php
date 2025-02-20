<?php
/**
 * Display a list of donors, either for a specific campaign or sitewide.
 *
 * Override this template by copying it to yourtheme/charitable/donor-loop.php
 *
 * @package Charitable/Templates/Donor
 * @author  WP Charitable LLC
 * @since   1.5.0
 * @version 1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Donors have to be included in the view args. */
if ( ! array_key_exists( 'donors', $view_args ) ) {
	return;
}

$donors            = $view_args['donors'];
$args              = $view_args;
$campaign_id       = $view_args['campaign'];
$hide_if_no_donors = array_key_exists( 'hide_if_no_donors', $view_args ) && $view_args['hide_if_no_donors'];

if ( ! charitable_is_campaign_page() && 'current' === $campaign_id ) {
	return;
}

if ( ! $donors->count() && $hide_if_no_donors ) {
	return;
}

if ( 'all' == $campaign_id ) {
	$args['campaign'] = false;
} elseif ( 'current' == $campaign_id ) {
	$args['campaign'] = get_the_ID();
}

$orientation = array_key_exists( 'orientation', $view_args ) ? $view_args['orientation'] : 'vertical';
$style       = '';

if ( 'horizontal' == $orientation ) {
	$width = array_key_exists( 'width', $view_args ) ? $view_args['width'] : get_option( 'thumbnail_size_w', 100 );
	if ( 100 !== $width ) {
		$style = '<style>.donors-list.donors-list-horizontal .donor{ width:' . intval( $width ) . 'px; }</style>';
	}
}

/**
 * Add something before the donors loop logic.
 *
 * @since   1.8.1.12
 *
 * @param   mixed    $campaign_id The campaign ID.
 * @param   array    $args      Loop args.
 */
do_action( 'charitable_donor_list_before', $campaign_id, $args );

if ( $donors->count() ) :
	echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	/**
	 * Add something before the donors loop.
	 *
	 * @since   1.8.1.12
	 *
	 * @param   array  $donors      The donors.
	 * @param   mixed  $campaign_id The campaign ID.
	 * @param   array  $args        Loop args.
	 */
	do_action( 'charitable_donor_list_loop_before', $donors, $campaign_id, $args );

	?>
	<ol class="donors-list donors-list-<?php echo esc_attr( $orientation ); ?>">
		<?php
		foreach ( $donors as $donor ) :

			$args['donor'] = $donor;

			charitable_template( 'donor-loop/donor.php', $args );

		endforeach;
		?>
	</ol>
<?php else : ?>
	<?php if ( is_admin() && 1 === intval( $view_args['builder_preview'] ) ) : ?>
		<?php
			/* fake data for preview area */
			$limit = isset( $view_args['number'] ) && intval( $view_args['number'] > 0 ) && intval( $view_args['number'] <= 10 ) ? intval( $view_args['number'] ) : 10;

			/**
			 * Add something before the donors loop.
			 *
			 * @since   1.8.1.12
			 *
			 * @param   array  $donors      The donors.
			 * @param   mixed  $campaign_id The campaign ID.
			 * @param   array  $args        Loop args.
			 */
			do_action( 'charitable_campaign_loop_before', $donors, $campaign_id, $args );

		?>
		<ol class="donors-list donors-list-<?php echo esc_attr( $orientation ); ?>">
			<?php for ( $x = 1; $x <= $limit; $x++ ) : ?>
			<li class="donor">
				<?php if ( ! empty( $args['show_avatar'] ) && 1 === intval( $args['show_avatar'] ) ) : ?>
				<img alt="" src="<?php echo esc_url( charitable()->get_path( 'directory', false ) ) . 'assets/images/campaign-builder/fields/donor-wall/avatar.jpg'; ?>" class="avatar avatar-100 photo" loading="lazy" decoding="async" width="100" height="100">
				<?php endif; ?>
				<?php if ( ! empty( $args['show_name'] ) && 1 === intval( $args['show_name'] ) ) : ?>
				<p class="donor-name"><?php _e( 'John Smith', 'charitable' ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $args['show_location'] ) && 1 === intval( $args['show_location'] ) ) : ?>
				<div class="donor-location"><?php _e( 'US', 'charitable' ); ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $args['show_amount'] ) && 1 === intval( $args['show_amount'] ) ) : ?>
				<div class="donor-donation-amount"><?php _e( '$100.00', 'charitable' ); ?></div>
				<?php endif; ?>
			</li><!-- .donor-x -->
			<?php endfor; ?>
		</ol>
		<?php

		/**
		 * Add something before the donors loop.
		 *
		 * @since   1.8.1.12
		 *
		 * @param   array  $donors      The donors.
		 * @param   mixed  $campaign_id The campaign ID.
		 * @param   array  $args        Loop args.
		 */
		do_action( 'charitable_donor_list_loop_after', $donors, $campaign_id, $args );

		?>
		<?php else : ?>
			<p><?php echo esc_html__( 'No donors yet. Be the first!', 'charitable' ); ?></p>
		<?php endif; ?>
	<?php
endif;

/**
 * Add something before the donors loop logic.
 *
 * @since   1.8.1.12
 *
 * @param   mixed  $campaign_id The campaign ID.
 * @param   array  $args        Loop args.
 */
do_action( 'charitable_donor_list_after', $campaign_id, $args );
