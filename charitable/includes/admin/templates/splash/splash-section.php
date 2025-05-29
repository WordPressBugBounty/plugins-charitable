<?php
/**
 * What's New modal section.
 *
 * @since 1.8.7
 *
 * @var string $title Section title.
 * @var string $content Section content.
 * @var array $img Section image.
 * @var string $new Is new feature.
 * @var array $buttons Section buttons.
 * @var string $layout Section layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = [
	'charitable-splash-section',
	'charitable-splash-section-' . $section['layout'],
	'charitable-splash-section-' . $section['class'],
];
?>

<section class="<?php echo charitable_sanitize_classes( $classes, true ); ?>">
	<div class="charitable-splash-section-content">
		<?php
		if ( ! empty( $section['new'] ) ) {
			printf(
				'<span class="charitable-splash-badge">%s</span>',
				esc_html__( 'New Feature', 'charitable-lite' )
			);
		}
		?>
		<h3><?php echo esc_html( $section['title'] ); ?></h3>
		<p><?php echo wp_kses_post( $section['content'] ); ?></p>

		<?php if ( ! empty( $section['buttons'] ) ) : ?>
			<div class="charitable-splash-section-buttons">
				<?php
				foreach ( $section['buttons'] as $button_type => $button ) {
					$button_class = $button_type === 'main' ? 'charitable-btn-orange' : 'charitable-btn-bordered';

					printf(
						'<a href="%1$s" class="charitable-btn %3$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( $button['url'] ),
						esc_html( $button['text'] ),
						esc_attr( $button_class )
					);
				}
				?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $section['img'] ) ) : ?>
		<div class="charitable-splash-section-image charitable-image-shadow-<?php echo charitable_sanitize_classes( $section['img']['shadow'] ?? 'none' ); ?>">
			<img src="<?php echo esc_url( $section['img']['url'] ); ?>" alt="">
		</div>
	<?php endif; ?>
</section>
