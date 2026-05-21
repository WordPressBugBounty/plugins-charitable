<?php
/**
 * Charitable Admin Splash modal template.
 *
 * @package Charitable/Admin/Templates
 * @since 1.8.6
 * @version 1.8.8.6
 *
 * @var array $header Header data.
 * @var array $footer Footer data.
 * @var array $blocks Blocks data.
 * @var array $license License type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build kses allowed HTML for splash output — wp_kses_post strips <source>,
 * so we extend the "post" set with the media tags we render in sections.
 *
 * phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- local helper.
 */
$charitable_splash_allowed = wp_kses_allowed_html( 'post' );
$charitable_splash_allowed['video']  = array(
	'autoplay'    => true,
	'muted'       => true,
	'playsinline' => true,
	'controls'    => true,
	'preload'     => true,
	'poster'      => true,
	'src'         => true,
	'class'       => true,
);
$charitable_splash_allowed['source'] = array(
	'src'  => true,
	'type' => true,
);
?>

<script type="text/html" id="tmpl-charitable-splash-modal-content">
	<div id="charitable-splash-modal">
		<?php
		echo wp_kses(
			charitable_render(
				'admin/templates/splash/splash-header',
				[
					'header' => $data['header'],
				],
				true
			),
			$charitable_splash_allowed
		);
		?>
		<main>
			<?php
			if ( ! empty( $data['sections'] ) ) {

				foreach ( $data['sections'] as $charitable_section ) {
					echo wp_kses(
						charitable_render(
							'admin/templates/splash/splash-section',
							[
								'section' => $charitable_section,
							],
							true
						),
						$charitable_splash_allowed
					);
				}
			}
			?>
		</main>
		<?php
		$charitable_license = isset( $license ) ? $license : 'lite'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $license may be passed in from external context.
		echo wp_kses(
			charitable_render(
				'admin/templates/splash/splash-footer',
				[
					'footer' => $data['footer'],
				],
				true
			),
			$charitable_splash_allowed
		);
		?>
	</div>
</script>
