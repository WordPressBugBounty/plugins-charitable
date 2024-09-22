<?php
/**
 * Display the Welcome page.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Welcome Page
 * @since   1.0.0
 * @version 1.8.0
 */

wp_enqueue_style( 'charitable-admin-pages' );

require_once ABSPATH . 'wp-admin/includes/translation-install.php';

$gateways        = Charitable_Gateways::get_instance()->get_active_gateways_names();
$campaigns       = wp_count_posts( 'campaign' );
$campaigns_count = $campaigns->publish + $campaigns->draft + $campaigns->future + $campaigns->pending + $campaigns->private;
$emails          = charitable_get_helper( 'emails' )->get_enabled_emails_names();
$install         = isset( $_GET['install'] ) && $_GET['install'];
$languages       = wp_get_available_translations();
$locale          = get_locale();
$language        = isset( $languages[ $locale ]['native_name'] ) ? $languages[ $locale ]['native_name'] : $locale;
$currency        = charitable_get_default_currency();
$currencies      = charitable_get_currency_helper()->get_all_currencies();
$all_extensions  = array(
	'payfast'                        => __( 'Accept donations in South African Rand', 'charitable' ),
	'payu-money'                     => __( 'Accept donations in Indian Rupees with PayUmoney', 'charitable' ),
	'easy-digital-downloads-connect' => __( 'Collect donations with Easy Digital Downloads', 'charitable' ),
	'recurring-donations'            => __( 'Accept recurring donations', 'charitable' ),
	'fee-relief'                     => __( 'Let donors cover the gateway fees', 'charitable' ),
	'stripe'                         => __( 'Accept credit card donations with Stripe', 'charitable' ),
	'authorize-net'                  => __( 'Collect donations with Authorize.Net', 'charitable' ),
	'ambassadors'                    => __( 'Peer to peer fundraising or crowdfunding', 'charitable' ),
	'windcave'                       => sprintf(
		/* translators: %s: currency code */
		__( 'Collect donations in %s', 'charitable' ),
		$currencies[ $currency ]
	),
	'anonymous-donations'            => __( 'Let donors give anonymously', 'charitable' ),
	'user-avatar'                    => __( 'Let your donors upload their own profile photo', 'charitable' ),
);

if ( 'en_ZA' == $locale || 'ZAR' == $currency ) {
	$extensions = array_intersect_key(
		$all_extensions,
		array(
			'payfast'             => '',
			'recurring-donations' => '',
			'ambassadors'         => '',
			'fee-relief'          => '',
		)
	);
} elseif ( 'hi_IN' == $locale || 'INR' == $currency ) {
	$extensions = array_intersect_key(
		$all_extensions,
		array(
			'payu-money'  => '',
			'ambassadors' => '',
			'fee-relief'  => '',
			'windcave'    => '',
		)
	);
} elseif ( in_array( $locale, array( 'en_NZ', 'ms_MY', 'ja', 'zh_HK' ) ) || in_array( $currency, array( 'NZD', 'MYR', 'JPY', 'HKD' ) ) ) {
	$extensions = array_intersect_key(
		$all_extensions,
		array(
			'recurring-donations' => '',
			'stripe'              => '',
			'windcave'            => '',
			'fee-relief'          => '',
		)
	);
} elseif ( in_array( $locale, array( 'th' ) ) || in_array( $currency, array( 'BND', 'FJD', 'KWD', 'PGK', 'SBD', 'THB', 'TOP', 'VUV', 'WST' ) ) ) {
	$extensions = array_intersect_key(
		$all_extensions,
		array(
			'windcave'            => '',
			'fee-relief'          => '',
			'ambassadors'         => '',
			'anonymous-donations' => '',
		)
	);
} elseif ( class_exists( 'EDD' ) ) {
	$extensions = array_intersect_key(
		$all_extensions,
		array(
			'ambassadors'                    => '',
			'easy-digital-downloads-connect' => '',
			'anonymous-donations'            => '',
			'user-avatar'                    => '',
		)
	);
} else {
	$extensions = array_intersect_key(
		$all_extensions,
		array(
			'recurring-donations' => '',
			'stripe'              => '',
			'authorize-net'       => '',
			'fee-relief'          => '',
		)
	);
}

?>
<div class="wrap about-wrap charitable-wrap">
	<h1>
		<strong><?php esc_html_e( 'Charitable', 'charitable' ); ?></strong>
		<sup class="version"><?php echo charitable()->get_version(); ?></sup>
	</h1>
	<div class="badge v2">
		<a href="<?php
		echo charitable_ga_url(
			'https://www.wpcharitable.com',
			rawurlencode( 'Welcome Page Icon' ),
			rawurlencode( 'Icon' )
		)
		?>" target="_blank"><div class="mascot"></div></a>
	</div>
	<div class="intro">
		<?php

		echo esc_html__( 'Thank you for installing Charitable!', 'charitable' );

		$view_pricing_url = charitable_ga_url(
			'https://wpcharitable.com/pricing/',
			rawurlencode( 'Welcome Page Sidebar Link' ),
			rawurlencode( 'View Pricing' )
		);

		do_action( 'charitable_maybe_show_notification' );

		?>
	</div>
	<hr />
	<div class="column-left">
		<div class="column-inside">
			<h2><?php _e( 'The WordPress Fundraising Toolkit', 'charitable' ); ?></h2>
			<p><?php _e( 'Charitable is everything you need to start accepting donations today. Stripe, PayPal and offline donations work right out of the box, and when your organization is ready to grow, our extensions give you the tools you need to move forward.', 'charitable' ); ?></p>
			<?php if ( current_user_can( 'manage_charitable_settings' ) ) : ?>
				<hr />
				<h3><?php _e( 'Getting Started', 'charitable' ); ?></h3>
				<ul class="checklist">
					<?php if ( count( $gateways ) > 0 ) : ?>
						<li class="done">
						<?php
							printf(
								_x( 'You have activated %1$s. <a href="%2$s">Change settings</a>', 'You have activated x and y. Change gateway settings.', 'charitable' ),
								charitable_list_to_sentence_part( $gateways ),
								admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
							);
						?>
						</li>
					<?php else : ?>
						<li class="not-done"><a href="<?php echo admin_url( 'admin.php?page=charitable-settings&tab=gateways' ); ?>"><?php _e( 'You need to enable a payment gateway', 'charitable' ); ?></a></li>
					<?php endif ?>
					<?php if ( $campaigns_count > 0 ) : ?>
						<li class="done">
						<?php
							printf(
								__( 'You have created your first campaign. <a href="%s">Create another one.</a>', 'charitable' ),
								admin_url( 'post-new.php?post_type=campaign' )
							);
						?>
						</li>
					<?php else : ?>
						<li class="not-done"><a href="<?php echo admin_url( 'post-new.php?post_type=campaign' ); ?>"><?php _e( 'Create your first campaign', 'charitable' ); ?></a></li>
					<?php endif ?>
					<?php if ( count( $emails ) > 0 ) : ?>
						<li class="done">
						<?php
							printf(
								_x( 'You have turned on the %1$s. <a href="%2$s">Change settings</a>', 'You have activated x and y. Change email settings.', 'charitable' ),
								charitable_list_to_sentence_part( $emails ),
								admin_url( 'admin.php?page=charitable-settings&tab=emails' )
							);
						?>
						</li>
					<?php else : ?>
						<li class="not-done"><a href="<?php echo admin_url( 'admin.php?page=charitable-settings&tab=emails' ); ?>"><?php _e( 'Turn on email notifications', 'charitable' ); ?></a></li>
					<?php endif ?>
				</ul>
				<?php

					$doc_url = charitable_ga_url(
						'https://www.wpcharitable.com/documentation/',
						rawurlencode( 'Dashboard Intro Copy Doc' ),
						rawurlencode( 'our documentation' )
					);

					$support_url = charitable_ga_url(
						'https://www.wpcharitable.com/support/',
						rawurlencode( 'Dashboard Intro Copy Support' ),
						rawurlencode( 'our support' )
					);

				?>
				<p style="margin-bottom: 0;">
				<?php
					printf(
						__( 'Need a hand with anything? You might find the answer in <a target="_blank" href="%1$s">our documentation</a>, or you can always get in touch with us via <a target="_blank" href="%2$s">our support page</a>.', 'charitable' ),
						$doc_url,
						$support_url
					);
				?>
				</p>
			<?php endif ?>
			<hr />
			<?php if ( strpos( $locale, 'en' ) !== 0 ) : ?>
				<h3><?php printf( _x( 'Translate Charitable into %s', 'translate Charitable into language', 'charitable' ), $language ); ?></h3>
				<p>
				<?php
				printf(
					__( 'You can help us translate Charitable into %s by <a target="_blank" href="https://translate.wordpress.org/projects/wp-plugins/charitable">contributing to the translation project</a>.', 'charitable' ),
					$language
				);
				?>
				</p>
				<hr />
			<?php endif ?>
			<h3><?php _e( 'Try Reach, a free theme designed for fundraising', 'charitable' ); ?></h3>
			<img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/reach-mockup.png" alt="<?php _e( 'Screenshot of Reach, a WordPress fundraising theme designed to complement Charitable', 'charitable' ); ?>" style="margin-bottom: 21px; float: right; margin-left: 20px;" width="336" height="166" />
			<p><?php _e( 'We built Reach to help non-profits &amp; social entrepreneurs run beautiful online fundraising campaigns. Whether you’re creating a website for your organization’s peer-to-peer fundraising event or building an online crowdfunding platform, Reach is the perfect starting point.', 'charitable' ); ?></p>
			<?php

				$download_url = charitable_ga_url(
					'https://www.wpcharitable.com/download-reach/',
					rawurlencode( 'Dashboard Reach Download Button' ),
					rawurlencode( 'Download It Free' )
				);

				$view_demo = charitable_ga_url(
					'https://demo.wpcharitable.com/reach/',
					rawurlencode( 'Dashboard Demo Reach Button' ),
					rawurlencode( 'View Demo' )
				);

				?>
			<p><a href="<?php echo esc_url( $download_url ); ?>" class="button-primary" style="margin-right: 8px;" target="_blank"><?php _e( 'Download it free', 'charitable' ); ?></a><a href="<?php echo $view_demo; ?>" class="button-secondary" target="_blank"><?php _e( 'View demo', 'charitable' ); ?></a></p>
		</div>
	</div>
	<div class="column-right">
		<div class="bundle-promo">
			<h2><?php _e( 'Get Charitable Pro', 'charitable' ); ?></h2>
			<p><?php _e( 'Upgrade to Charitable Pro today and unlock access to powerful features like Fee Relief, Recurring Donations, Crowdfunding, Peer-to-Peer Fundraising Campaigns and more.', 'charitable' ); ?></p>

			<p style="text-align: center;"><a href="<?php echo esc_url( $view_pricing_url ); ?>" class="button-primary" target="_blank" rel="noopener"><?php _e( 'Get Started with Charitable Pro', 'charitable' ); ?></a></p>

			<small><em><?php _e( 'Bonus: Charitable Lite users save $300 or more off the regular price, automatically applied at checkout!', 'charitable' ); ?></em></small>
		</div>
		<div class="column-inside">
			<h3><?php _e( 'Recommended Extensions', 'charitable' ); ?></h3>
			<ul class="extensions">
				<?php
				foreach ( $extensions as $extension => $description ) :

					$extension_url = charitable_ga_url(
						'https://wpcharitable.com/extensions/charitable-' . $extension,
						rawurlencode( 'Recommended Extensions' ),
						$extension
					);

					?>
					<li class="<?php echo $extension; ?>">
						<a href="<?php echo $extension_url; ?>" target="_blank" rel="noopener"><img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/extensions/<?php echo $extension; ?>.png" width="640" height="300" alt="<?php echo esc_attr( sprintf( _x( '%s banner', 'extension banner', 'charitable' ), $extension ) ); ?>" /><?php echo $description; ?></a>
					</li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
</div>
