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
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Donors have to be included in the view args. */
if ( ! array_key_exists( 'donors', $view_args ) ) {
	return;
}

$charitable_donors            = $view_args['donors'];
$charitable_args              = $view_args;
$charitable_campaign_id       = $view_args['campaign'];
$charitable_hide_if_no_donors = array_key_exists( 'hide_if_no_donors', $view_args ) && $view_args['hide_if_no_donors'];

if ( ! charitable_is_campaign_page() && 'current' === $charitable_campaign_id ) {
	return;
}

if ( ! $charitable_donors->count() && $charitable_hide_if_no_donors ) {
	return;
}

if ( 'all' == $charitable_campaign_id ) {
	$charitable_args['campaign'] = false;
} elseif ( 'current' == $charitable_campaign_id ) {
	$charitable_args['campaign'] = get_the_ID();
}

$charitable_orientation = array_key_exists( 'orientation', $view_args ) ? $view_args['orientation'] : 'vertical';
$charitable_style       = '';

if ( 'horizontal' == $charitable_orientation ) {
	$charitable_width = array_key_exists( 'width', $view_args ) ? $view_args['width'] : get_option( 'thumbnail_size_w', 100 );
	if ( 100 !== $charitable_width ) {
		$charitable_style = '<style>.donors-list.donors-list-horizontal .donor{ width:' . intval( $charitable_width ) . 'px; }</style>';
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
do_action( 'charitable_donor_list_before', $charitable_campaign_id, $charitable_args );

if ( $charitable_donors->count() ) :
	echo $charitable_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	/**
	 * Add something before the donors loop.
	 *
	 * @since   1.8.1.12
	 *
	 * @param   array  $donors      The donors.
	 * @param   mixed  $campaign_id The campaign ID.
	 * @param   array  $args        Loop args.
	 */
	do_action( 'charitable_donor_list_loop_before', $charitable_donors, $charitable_campaign_id, $charitable_args );

	?>
	<ol class="donors-list donors-list-<?php echo esc_attr( $charitable_orientation ); ?>">
		<?php
		foreach ( $charitable_donors as $charitable_donor ) :

			$charitable_args['donor'] = $charitable_donor;

			charitable_template( 'donor-loop/donor.php', $charitable_args );

		endforeach;
		?>
	</ol>
<?php else : ?>
	<p><?php echo esc_html__( 'No donors yet. Be the first!', 'charitable' ); ?></p>
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
do_action( 'charitable_donor_list_after', $charitable_campaign_id, $charitable_args );
