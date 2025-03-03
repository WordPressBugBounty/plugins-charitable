<?php
/**
 * Display a list of campaign categories or tags.
 *
 * Override this template by copying it to yourtheme/charitable/widgets/campaign-terms.php
 *
 * @package Charitable/Templates/Widgets
 * @author  WP Charitable LLC
 * @since   1.5.4
 * @version 1.6.54
 */

$taxonomy_name = isset( $view_args['taxonomy'] ) ? $view_args['taxonomy'] : 'campaign_category';
$taxonomy      = get_taxonomies( array( 'name' => $taxonomy_name ), 'objects' );
$title         = ! empty( $view_args['title'] ) ? $view_args['title'] : '';
$dropdown_id   = $view_args['widget_id'] . '-dropdown';
$dropdown_args = array(
	'taxonomy'         => $taxonomy_name,
	'name'             => $taxonomy_name,
	'show_count'       => isset( $view_args['show_count'] ) && $view_args['show_count'],
	'hide_empty'       => isset( $view_args['hide_empty'] ) && $view_args['hide_empty'],
	'id'               => $dropdown_id,
	'show_option_none' => sprintf(
		/* translators: %s: taxonomy label */
		_x( 'Select %s', 'select campaign category/tag', 'charitable' ),
		$taxonomy[ $taxonomy_name ]->label
	),
	'value_field'      => 'slug',
	'selected'         => is_tax( $taxonomy_name ) ? get_query_var( $taxonomy_name ) : 0,
);

echo $view_args['before_widget'];

if ( ! empty( $title ) ) :

	echo $view_args['before_title'] . esc_html( $title ) . $view_args['after_title'];

endif;
?>
<form action="<?php echo esc_url( home_url() ); ?>" method="get">
	<label class="screen-reader-text" for="<?php echo esc_attr( $dropdown_id ); ?>"><?php echo esc_html( $title ); ?></label>
	<?php wp_dropdown_categories( $dropdown_args ); ?>
</form>
<script type='text/javascript'>
/* <![CDATA[ */
(function() {
	var dropdown = document.getElementById( "<?php echo esc_js( $dropdown_id ); ?>" );
	function onCatChange() {
		if ( dropdown.options[ dropdown.selectedIndex ].value !== -1 ) {
			dropdown.parentNode.submit();
		}
	}
	dropdown.onchange = onCatChange;
})();
/* ]]> */
</script>
<?php

echo $view_args['after_widget'];
