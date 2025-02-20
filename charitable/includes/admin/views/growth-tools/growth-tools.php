<?php
/**
 * Display the main tools page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 * @version   1.8.1.6
 */

$active_tab      = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : '';  // phpcs:ignore

$sections      = [
	'featured'   => esc_html__( 'Featured', 'charitable' ),
	'traffic'    => esc_html__( 'Traffic', 'charitable' ),
	'engagement' => esc_html__( 'Engagement', 'charitable' ),
	'revenue'    => esc_html__( 'Revenue', 'charitable' ),
	'guides'     => esc_html__( 'Guides & Resources', 'charitable' ),
];
$section_tools = array();

$growth_tools = Charitable_Guide_Tools::get_instance();

$tools       = $growth_tools->get_growth_tools();
$show_button = true;

if ( is_array( $tools ) && ! empty( $tools ) ) :

	foreach ( $tools as $slug => $tool_info ) :

		if ( isset( $tool_info['gt_section'] ) ) {
			$section_tools[ $tool_info['gt_section'] ][ $slug ] = $tool_info;
		}

endforeach;

endif;

ob_start();
?>

<div id="charitable-growth-tools" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $sections as $section_slug => $section_title ) :

			if ( ! isset( $section_tools[ $section_slug ] ) || empty( $section_tools[ $section_slug ] ) ) {
				continue;
			}

			?>
		<a href="#<?php echo esc_attr( sanitize_title( $section_slug ) ); ?>" class="nav-tab <?php echo $active_tab === $section_slug ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $section_title ); ?></a>
		<?php endforeach; ?>

	</h2>

	<main id="charitable-growth-tools" class="charitable-growth-tools">

		<div class="charitable-growth-container">

			<?php

			if ( is_array( $sections ) && ! empty( $sections ) ) :

				foreach ( $sections as $section_slug => $section_title ) :

					if ( ! isset( $section_tools[ $section_slug ] ) || empty( $section_tools[ $section_slug ] ) ) {
						continue;
					}

					?>

					<section id="charitable-section-<?php echo esc_attr( $section_slug ); ?>" class="charitable-growth-block">
						<a id="wpchr-<?php echo esc_attr( $section_slug ); ?>"></a>
						<h2 class="charitable-growth-block-title"><?php echo esc_html( $section_title ); ?></h2>
						<div class="charitable-growth-item">

						<?php

						foreach ( $section_tools[ $section_slug ] as $tool_slug => $tool_info ) :

							if ( empty( $tool_info ) ) {
								continue;
							}

							$content_class = '';

							if ( isset( $tool_info['coming_soon'] ) && true === $tool_info['coming_soon'] ) {
								$content_class = 'charitable-growth-coming-soon';
								$show_button   = false;
							}

							?>

								<div class="charitable-growth-content <?php echo esc_attr( $content_class ); ?>" id="charitable-growth-content-<?php echo esc_attr( $tool_info['id'] ); ?>">

									<a id="wpchr-<?php echo esc_attr( $tool_info['id'] ); ?>"></a>

									<div class="charitable-growth-content-icon_container">
										<div class="charitable-growth-content-icon icon-<?php echo esc_attr( $tool_info['id'] ); ?>"></div>
									</div>

									<div class="charitable-growth-content-desc_container">
										<h3 class="charitable-growth-desc-title"><?php echo esc_html( $tool_info['title'] ); ?></h3>
										<?php if ( ! empty( $tool_info['excerpt'] ) ) : ?>
										<p class="charitable-growth-desc-excerpt">
											<?php
											echo wp_kses(
												$tool_info['excerpt'],
												[
													'a'    => [
														'href'   => [],
														'target' => [],
														'rel'    => [],
													],
													'span' => [
														'class' => [],
													],
												]
											);
											?>
										</p>
										<?php endif; ?>
										<?php if ( ! empty( $tool_info['why'] ) ) : ?>
												<p class="charitable-growth-desc-why"><strong><?php echo esc_html__( 'TIP:', 'charitable' ); ?></strong> <?php echo $tool_info['why']; // phpcs:ignore ?></p>
										<?php endif; ?>
									</div>
									<div class="charitable-growth-content-button_container">
										<div>
											<?php if ( $show_button ) : ?>
												<?php

													$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();
													$plugin_button_html             = $charitable_plugins_third_party->get_plugin_button_html( $tool_slug, false, '' );
                                                    echo $plugin_button_html; // phpcs:ignore

												?>
											<?php endif; ?>
										</div>
									</div>
								</div>

								<?php

							endforeach;

						?>

						</div>
					</section>

					<?php
				endforeach;

			endif;

			?>

		</div>

	</main>



</div>
<?php
echo ob_get_clean(); // phpcs:ignore
