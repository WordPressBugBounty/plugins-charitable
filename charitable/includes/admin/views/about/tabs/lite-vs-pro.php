<?php
/**
 * Display the Lite vs Pro tab content.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/About
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 * @version   1.8.7.6
 */

// Get license information
$license = 'lite'; // Default to lite for now
$next_license = 'Pro'; // Next license level

ob_start();
?>

<div class="charitable-admin-about-section charitable-admin-about-section-squashed">
	<h1 class="centered">
		<strong><?php echo esc_html( ucfirst( $license ) ); ?></strong> vs <strong><?php echo esc_html( $next_license ); ?></strong>
	</h1>

	<p class="centered">
		<?php esc_html_e( 'Get the most out of Charitable by upgrading to Pro and unlocking all of the powerful features.', 'charitable' ); ?>
	</p>
</div>

<div class="charitable-admin-about-section charitable-admin-about-section-squashed charitable-admin-about-section-hero charitable-admin-about-section-table charitable-admin-about-section-table-no-padding">

	<div class="charitable-admin-about-section-hero-main no-border charitable-admin-columns-lite-vs-pro">
		<div class="charitable-admin-column-33">
			<h3 class="no-margin">
				<?php esc_html_e( 'Feature', 'charitable' ); ?>
			</h3>
		</div>
		<div class="charitable-admin-column-33">
			<h3 class="no-margin">
				<?php echo esc_html( ucfirst( $license ) ); ?>
			</h3>
		</div>
		<div class="charitable-admin-column-33">
			<h3 class="no-margin">
				<?php echo esc_html( $next_license ); ?>
			</h3>
		</div>
	</div>
	<div class="charitable-admin-about-section-hero-extra no-padding charitable-admin-columns-lite-vs-pro">

		<table>
			<?php
			// Get features list - Charitable specific features
			$features = array(
				'campaigns'         => esc_html__( 'Campaigns', 'charitable' ),
				'payment_gateways'  => esc_html__( 'Payment Gateways', 'charitable' ),
				'recurring_donations' => esc_html__( 'Recurring Donations', 'charitable' ),
				'suggested_amounts' => esc_html__( 'Suggested Donation Amounts', 'charitable' ),
				'fundraising_goals' => esc_html__( 'Fundraising Goals & Progress Bars', 'charitable' ),
				'peer_to_peer'     => esc_html__( 'Peer-to-Peer Fundraising', 'charitable' ),
				'ambassadors'      => esc_html__( 'Ambassadors / Team Fundraising', 'charitable' ),
				'donation_data'    => esc_html__( 'Donation Data Management', 'charitable' ),
				'donor_comments'   => esc_html__( 'Donor Comments', 'charitable' ),
				'donor_dashboard'  => esc_html__( 'Donor Dashboard', 'charitable' ),
				'pdf_receipts'     => esc_html__( 'PDF Receipts', 'charitable' ),
				'fee_relief'       => esc_html__( 'Fee Relief', 'charitable' ),
				'marketing'        => esc_html__( 'Marketing Integrations', 'charitable' ),
				'reporting'        => esc_html__( 'Reporting & Analytics', 'charitable' ),
				'extensions'       => esc_html__( 'Extensions / Addons', 'charitable' ),
				'support'          => esc_html__( 'Customer Support', 'charitable' ),
			);

			$about_class = new Charitable_About();
			
			foreach ( $features as $slug => $name ) {
				$current = $about_class->get_license_data( $slug, $license );
				$next    = $about_class->get_license_data( $slug, strtolower( $next_license ) );

				if ( empty( $current ) || empty( $next ) ) {
					continue;
				}

				$current_status = $current['status'];

				if ( $current['text'] !== $next['text'] && $current_status === 'full' ) {
					$current_status = 'partial';
				}
				?>
				<tr class="charitable-admin-columns-lite-vs-pro">
					<td class="charitable-admin-column-33">
						<p><?php echo esc_html( $name ); ?></p>
					</td>
					<td class="charitable-admin-column-33">
						<?php if ( is_array( $current ) ) : ?>
							<p class="features-<?php echo esc_attr( $current_status ); ?>">
								<?php echo wp_kses_post( implode( '<br>', $current['text'] ) ); ?>
							</p>
						<?php endif; ?>
					</td>
					<td class="charitable-admin-column-33">
						<?php if ( is_array( $current ) ) : ?>
							<p class="features-full">
								<?php echo wp_kses_post( implode( '<br>', $next['text'] ) ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
				<?php
			}
			?>
		</table>

	</div>

</div>

<div class="charitable-admin-about-section charitable-admin-about-section-hero">
	<div class="charitable-admin-about-section-hero-main no-border">
		<h3 class="call-to-action centered">
			<?php
			printf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">',
				esc_url( 'https://wpcharitable.com/pricing?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable' )
			);
			echo '🔥 Get Charitable Pro today and unlock all the powerful features to grow your fundraising.';
			?>
			</a>
		</h3>

		<?php if ( $license === 'lite' ) { ?>
			<p class="centered">
				<?php
				echo wp_kses(
					__( 'Bonus: Charitable Lite users get <span class="price-20-off">50% off regular price</span>, automatically applied at checkout.', 'charitable' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				);
				?>
			</p>
		<?php } ?>
	</div>
</div>

<div id="wpfooter">
	<?php charitable_admin_view( 'admin-footer-promotion' ); ?>
</div>

<?php
echo ob_get_clean(); // phpcs:ignore
