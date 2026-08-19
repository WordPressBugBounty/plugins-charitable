<?php
/**
 * Reports > Site Analysis tab body. No data is sent on load - only when "Run analysis" is clicked.
 *
 * @package Charitable/Admin Views/Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="charitable-site-analysis">

	<div class="charitable-container charitable-title-card">
		<div class="charitable-title-card-content">

			<h2><?php esc_html_e( 'Free Site Analysis', 'charitable' ); ?></h2>
			<p class="description charitable-site-analysis-intro">
				<?php esc_html_e( 'See how your fundraising setup measures up and get specific, prioritized steps to raise more, based on your current Charitable configuration. Free to run any time, and no donor data is ever sent.', 'charitable' ); ?>
			</p>

			<?php
			/*
			 * SAMPLE score hero - a blurred, clearly-labeled EXAMPLE of what a completed analysis looks
			 * like (all four areas filled). The number and bars are illustrative stand-ins, NOT this
			 * site's data; the real score renders in #charitable-site-analysis-results after "Run
			 * Analysis". aria-hidden so assistive tech skips the decorative preview.
			 */
			?>
			<div class="charitable-sa-sample" aria-hidden="true">
				<div class="charitable-sa-score">
					<div class="charitable-sa-ring charitable-sa-ring--sample">
						<span class="charitable-sa-ring-v"><b>72</b><s>/ 100</s></span>
					</div>
					<div class="charitable-sa-score-meta">
						<h3 class="charitable-sa-score-title">
							<?php esc_html_e( 'Fundraising Score', 'charitable' ); ?>
							<span class="charitable-sa-band" style="background:#dba617;"><?php esc_html_e( 'Good', 'charitable' ); ?></span>
						</h3>
						<p class="charitable-sa-score-desc">
							<?php esc_html_e( 'A snapshot of your fundraising health across four areas, with specific steps to raise your score.', 'charitable' ); ?>
						</p>
						<div class="charitable-sa-dims">
							<div class="charitable-sa-dim">
								<span class="charitable-sa-dim-n"><?php esc_html_e( 'Activation', 'charitable' ); ?></span>
								<span class="charitable-sa-dim-bar"><i style="width:90%;background:#5AA152;"></i></span>
							</div>
							<div class="charitable-sa-dim">
								<span class="charitable-sa-dim-n"><?php esc_html_e( 'Health', 'charitable' ); ?></span>
								<span class="charitable-sa-dim-bar"><i style="width:75%;background:#5AA152;"></i></span>
							</div>
							<div class="charitable-sa-dim">
								<span class="charitable-sa-dim-n"><?php esc_html_e( 'Growth', 'charitable' ); ?></span>
								<span class="charitable-sa-dim-bar"><i style="width:58%;background:#dba617;"></i></span>
							</div>
							<div class="charitable-sa-dim">
								<span class="charitable-sa-dim-n"><?php esc_html_e( 'Optimization', 'charitable' ); ?></span>
								<span class="charitable-sa-dim-bar"><i style="width:52%;background:#dba617;"></i></span>
							</div>
						</div>
					</div>
				</div>
				<span class="charitable-sa-sample-badge"><?php esc_html_e( 'Example data', 'charitable' ); ?></span>
			</div>

			<div class="charitable-sa-value">
				<h3 class="charitable-sa-value-title"><?php esc_html_e( 'What You Get', 'charitable' ); ?></h3>
				<ul>
					<li><strong><?php esc_html_e( 'A Fundraising Score', 'charitable' ); ?></strong> <?php esc_html_e( 'across the four areas below, so you can see your strengths and gaps at a glance.', 'charitable' ); ?></li>
					<li><strong><?php esc_html_e( 'Prioritized recommendations', 'charitable' ); ?></strong> <?php esc_html_e( 'matched to your exact setup: what to fix first, quick wins, and bigger growth opportunities.', 'charitable' ); ?></li>
					<li><strong><?php esc_html_e( 'A clear path forward', 'charitable' ); ?></strong> <?php esc_html_e( 'every recommendation shows the points it would add, so you always know where to focus next.', 'charitable' ); ?></li>
				</ul>
			</div>

			<?php
			/*
			 * The four scored areas as cards (icon + name + what each covers). Illustrative preview;
			 * the real per-area scores render after "Run Analysis". Icons are decorative (aria-hidden).
			 */
			?>
			<div class="charitable-sa-area-cards">
				<div class="charitable-sa-area-card charitable-sa-area--activation">
					<i class="charitable-sa-area-icon fa fa-bullhorn" aria-hidden="true"></i>
					<span class="charitable-sa-area-name"><?php esc_html_e( 'Activation', 'charitable' ); ?></span>
					<span class="charitable-sa-area-desc"><?php esc_html_e( 'Getting live and bringing in your first donations.', 'charitable' ); ?></span>
				</div>
				<div class="charitable-sa-area-card charitable-sa-area--health">
					<i class="charitable-sa-area-icon fa fa-heartbeat" aria-hidden="true"></i>
					<span class="charitable-sa-area-name"><?php esc_html_e( 'Health', 'charitable' ); ?></span>
					<span class="charitable-sa-area-desc"><?php esc_html_e( 'Core setup best practices: receipts, campaign goals, and more than one way to give.', 'charitable' ); ?></span>
				</div>
				<div class="charitable-sa-area-card charitable-sa-area--growth">
					<i class="charitable-sa-area-icon fa fa-line-chart" aria-hidden="true"></i>
					<span class="charitable-sa-area-name"><?php esc_html_e( 'Growth', 'charitable' ); ?></span>
					<span class="charitable-sa-area-desc"><?php esc_html_e( 'Recurring giving, list building, and steady donation momentum.', 'charitable' ); ?></span>
				</div>
				<div class="charitable-sa-area-card charitable-sa-area--optimization">
					<i class="charitable-sa-area-icon fa fa-tachometer" aria-hidden="true"></i>
					<span class="charitable-sa-area-name"><?php esc_html_e( 'Optimization', 'charitable' ); ?></span>
					<span class="charitable-sa-area-desc"><?php esc_html_e( 'Getting more from every gift: larger average gifts, covered fees, and repeat donors.', 'charitable' ); ?></span>
				</div>
			</div>

			<p class="charitable-sa-privacy-note">
				<?php
				printf(
					/* translators: %s: "Read more" link to the Site Analysis info page. */
					esc_html__( 'No campaign content or donor data (including names, emails, or donation amounts) are ever sent or stored. %s', 'charitable' ),
					'<a href="https://wpcharitable.com/documentation/about-site-analysis/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn more', 'charitable' ) . '</a>'
				);
				?>
			</p>

			<?php
			/*
			 * Usage-data toggle. If usage tracking is already ON site-wide, there's nothing to opt into,
			 * so we hide the toggle entirely (the JS then treats consent as true). If it's OFF, show the
			 * toggle defaulted ON so sharing is the friendly default the user can switch off.
			 *
			 * As of 1.8.12.1 leaving this ticked turns the Advanced > Misc "Usage Tracking" setting ON
			 * persistently, so the copy below says so. It is one-way: unticking never turns tracking off
			 * (see Charitable_Site_Analysis::enable_usage_tracking()), and once tracking is on this
			 * toggle is not rendered at all, so there is nothing to untick.
			 */
			$charitable_usage_on = function_exists( 'charitable_get_usage_tracking_setting' )
				? (bool) apply_filters( 'charitable_usage_tracking', charitable_get_usage_tracking_setting() )
				: false;
			if ( ! $charitable_usage_on ) :
			?>
			<p class="charitable-site-analysis-consent">
				<label class="charitable-sa-toggle">
					<input type="checkbox" id="charitable-site-analysis-consent" value="1" checked />
					<span class="charitable-sa-toggle-track"><span class="charitable-sa-toggle-thumb"></span></span>
				</label>
				<span class="charitable-sa-toggle-label">
					<strong><?php esc_html_e( 'Share Usage Data:', 'charitable' ); ?></strong>
					<?php
					printf(
						/* translators: %s: "Read More" link to the usage-tracking documentation. */
						esc_html__( 'Helps improve site analysis and improve Charitable plugin for your use. Leaving this on turns on usage tracking, which you can switch off at any time in Settings &rarr; Advanced. %s', 'charitable' ),
						'<a href="https://www.wpcharitable.com/documentation/usage-tracking/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn more', 'charitable' ) . '</a>'
					);
					?>
				</span>
			</p>
			<?php endif; ?>

			<p class="charitable-site-analysis-actions">
				<button type="button" class="button button-primary" id="charitable-site-analysis-run">
					<?php esc_html_e( 'Run Analysis', 'charitable' ); ?>
				</button>
				<span class="spinner" id="charitable-site-analysis-spinner" style="float:none;"></span>
			</p>

		</div>
	</div>

	<div id="charitable-site-analysis-error" class="notice notice-error inline" style="display:none;"><p></p></div>
	<div id="charitable-site-analysis-results"></div>
</div>
