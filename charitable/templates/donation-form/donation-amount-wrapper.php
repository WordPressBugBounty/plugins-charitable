<?php
/**
 * The template used to display the donation amount wrapper.
 *
 * @author 	WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.5.0
 * @version 1.5.0
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form 	 = $view_args['form'];
$field 	 = $view_args['field'];
$classes = $view_args['classes'];
$fields  = isset( $field['fields'] ) ? $field['fields'] : array();

if ( ! count( $fields ) ) :
	return;
endif;

?>
<fieldset class="<?php echo $classes ?>">
	<?php
	if ( isset( $field['legend'] ) ) : ?>
		<?php do_action( 'charitable_before_donation_amount_wrapper_header' ); ?>
		<div class="charitable-form-header"><?php echo $field['legend'] ?></div>
		<?php do_action( 'charitable_after_donation_amount_wrapper_header' ); ?>
	<?php
	endif;

	echo $form->maybe_show_current_donation_amount();

	?>
	<div id="charitable-donation-options-<?php echo esc_attr( $form->get_form_identifier() ) ?>">
		<?php $form->view()->render_fields( $fields ) ?>
	</div><!-- charitable-donation-options-<?php echo esc_attr( $form->get_form_identifier() ) ?> -->
</fieldset>
