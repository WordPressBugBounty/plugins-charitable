<?php
/**
 * Display inline content in the settings area.
 *
 * Unlike the 'content' field type, the content in this case is not full-width but is
 * added to the second <td></td> in the settings table row.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.18
 * @version   1.6.18
 */

echo $view_args['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
