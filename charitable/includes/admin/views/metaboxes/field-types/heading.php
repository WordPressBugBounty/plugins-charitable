<?php
/**
 * Display heading in metabox.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.2.0
 * @version   1.5.0
 */

$level = array_key_exists( 'level', $view_args ) ? $view_args['level'] : 'h4';
?>
<<?php echo $level; ?> class="charitable-metabox-header" <?php echo charitable_get_arbitrary_attributes( $view_args ); ?>><?php echo esc_html( $view_args['title'] ); ?></<?php echo $level; ?>>