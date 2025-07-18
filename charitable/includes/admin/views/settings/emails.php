<?php
/**
 * Display the table of emails.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.2 Added checklist querystring value to action_url.
 * @version   1.8.7 Removed cf class from the email settings table.
 */

$helper = charitable_get_helper( 'emails' );
$emails = $helper->get_available_emails();

if ( count( $emails ) ) :

	foreach ( $emails as $email ) :

		$email      = new $email();
		$is_enabled = $helper->is_enabled_email( $email->get_email_id() );
		$action_url = esc_url(
			add_query_arg(
				array(
					'charitable_action' => $is_enabled ? 'disable_email' : 'enable_email',
					'email_id'          => $email->get_email_id(),
					'_nonce'            => wp_create_nonce( 'email' ),
				),
				admin_url( 'admin.php?page=charitable-settings&tab=emails' )
			)
		);
		// if the querystring value of checklist exists then add it to the action_url.
		if ( isset( $_GET['checklist'] ) ) { // phpcs:ignore
			$action_url .= '&checklist=' . esc_attr( $_GET['checklist'] ); // phpcs:ignore
		}

		?>
		<div class="charitable-settings-object charitable-email">
			<h4><?php echo esc_html( $email->get_name() ); ?></h4>
			<span class="actions">
				<?php
				if ( $is_enabled ) :
					$settings_url = esc_url(
						add_query_arg(
							array(
								'group' => 'emails_' . $email->get_email_id(),
							),
							admin_url( 'admin.php?page=charitable-settings&tab=emails' )
						)
					);
					?>
					<a href="<?php echo $settings_url; ?>" class="button button-primary"><?php _e( 'Email Settings', 'charitable' ); ?></a>
				<?php endif ?>
				<?php if ( ! $email->is_required() ) : ?>
					<?php if ( $is_enabled ) : ?>
						<a href="<?php echo $action_url; ?>" class="button"><?php _e( 'Disable Email', 'charitable' ); ?></a>
					<?php else : ?>
						<a href="<?php echo $action_url; ?>" class="button"><?php _e( 'Enable Email', 'charitable' ); ?></a>
					<?php endif ?>
				<?php endif ?>
			</span>
		</div>
	<?php endforeach ?>
	<?php
else :
	_e( 'There are no emails available in your system.', 'charitable' );
endif;
