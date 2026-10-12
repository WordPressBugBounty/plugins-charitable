<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display the donation amount wrapper.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.5.0
 * @version 1.8.4 // updated filtering for legend filter that was added in 1.8.3.6.
 * @version 1.8.8.6
 * @version 1.8.13.1
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form    = $view_args['form'];
$charitable_field   = $view_args['field'];
$charitable_classes = $view_args['classes'];
$charitable_fields  = isset( $charitable_field['fields'] ) ? $charitable_field['fields'] : array();
$charitable_legend  = apply_filters( 'charitable_donation_amount_legend', wp_kses_post( $charitable_field['legend'] ), $charitable_form );

if ( ! count( $charitable_fields ) ) :
	return;
endif;

/*
 * Give the amount group a programmatic name (WCAG 1.3.1 Info and Relationships) -- 1.8.13.1.
 *
 * This is already a <fieldset>, and it already renders a visible heading, but that heading is a
 * <div class="charitable-form-header"> rather than a <legend>, so the group had no accessible
 * name: a screen-reader user heard each amount option but never what the group was for.
 *
 * Pointing aria-labelledby at the EXISTING heading rather than adding a <legend> is deliberate:
 *
 * - It introduces no second copy of the text, so nothing can drift between them.
 * - <legend> carries its own hard-to-override browser layout, and this heading is a styled block
 *   in a form whose markup themes override; swapping the element risks visual regressions on
 *   every existing site for no accessibility gain over aria-labelledby.
 * - Where the heading is absent (the legend can be filtered away), aria-label supplies a name
 *   instead, so the group is never left anonymous.
 */
$charitable_has_legend = isset( $charitable_field['legend'] ) && '' !== trim( wp_strip_all_tags( (string) $charitable_legend ) );
$charitable_legend_id  = 'charitable-donation-amount-legend-' . $charitable_form->get_form_identifier();

if ( $charitable_has_legend ) {
	$charitable_group_attr = sprintf( ' aria-labelledby="%s"', esc_attr( $charitable_legend_id ) );
} else {
	$charitable_group_attr = sprintf(
		' aria-label="%s"',
		esc_attr_x( 'Select a donation amount', 'donation amount group, accessible name', 'charitable' )
	);
}

?>
<fieldset class="<?php echo esc_attr( $charitable_classes ); ?>"<?php echo $charitable_group_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
	<?php
	if ( isset( $charitable_field['legend'] ) ) :
		?>
		<?php do_action( 'charitable_before_donation_amount_wrapper_header' ); ?>
		<div class="charitable-form-header" id="<?php echo esc_attr( $charitable_legend_id ); ?>"><?php echo $charitable_legend; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php do_action( 'charitable_after_donation_amount_wrapper_header' ); ?>
		<?php
	endif;

	echo $charitable_form->maybe_show_current_donation_amount(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	?>
	<div id="charitable-donation-options-<?php echo esc_attr( $charitable_form->get_form_identifier() ); ?>">
		<?php $charitable_form->view()->render_fields( $charitable_fields ); ?>
	</div><!-- charitable-donation-options-<?php echo esc_attr( $charitable_form->get_form_identifier() ); ?> -->
</fieldset>
