<?php
/**
 * What's New modal header.
 *
 * @since 1.8.7
 *
 * @var string $title Header title.
 * @var string $image Logo URL.
 * @var string $description Header content.
 * @var string $version Major version (e.g. "1.8.11").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<header>
	<img src="<?php echo esc_url( $header['image'] ); ?>" alt="">
	<div class="charitable-splash-header-content">
		<h2>
			<span class="charitable-splash-header-title"><?php echo esc_html( $header['title'] ); ?></span>
			<?php if ( ! empty( $header['version'] ) ) : ?>
				<span class="charitable-splash-header-version">v<?php echo esc_html( $header['version'] ); ?></span>
			<?php endif; ?>
		</h2>
		<p><?php echo esc_html( $header['description'] ); ?></p>
	</div>
</header>
