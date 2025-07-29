<?php
/**
 * Charitable Admin Splash modal template.
 *
 * @package Charitable/Admin/Templates
 * @since 1.8.6
 *
 * @var array $header Header data.
 * @var array $footer Footer data.
 * @var array $blocks Blocks data.
 * @var array $license License type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/html" id="tmpl-charitable-splash-modal-content">
	<div id="charitable-splash-modal">
		<?php
		echo wp_kses_post(
			charitable_render(
				'admin/templates/splash/splash-header',
				[
					'header' => $data['header'],
				],
				true
			)
		);
		?>
		<main>
			<?php
			if ( ! empty( $data['sections'] ) ) {

				foreach ( $data['sections'] as $section ) {
					echo wp_kses_post(
						charitable_render(
							'admin/templates/splash/splash-section',
							[
								'section' => $section,
							],
							true
						)
					);
				}
			}
			?>
		</main>
		<?php
		$license = isset( $license ) ? $license : 'lite';
		echo wp_kses_post(
			charitable_render(
				'admin/templates/splash/splash-footer',
				[
					'footer' => $data['footer'],
				],
				true
			)
		);
		?>
	</div>
</script>
