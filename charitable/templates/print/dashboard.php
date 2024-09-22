<?php
/**
 * Format this page for PDF or print.
 *
 * Override this template by copying it to yourtheme/charitable/print/dashboard.php
 *
 * @package charitable
 * @author  WPCharitable
 * @since   1.8.1
 * @version 1.8.1.6
 */

$charitable_action = $view_args['action'] === 'print' ? 'print' : 'download';

$charitable_cards         = $view_args['charitable_cards'];
$charitable_reports       = $view_args['charitable_reports'];
$charitable_admin_2_0_css = $view_args['charitable_admin_2_0_css'];
$charitable_chart_js      = $view_args['charitable_chart_js'];

$start_date = ! empty( $view_args['start_date'] ) ? $view_args['start_date'] : false;
$end_date   = ! empty( $view_args['end_date'] ) ? $view_args['end_date'] : false;

$headline_chart_options = $view_args['headline_chart_options'];

// Regarding the donation and data axis for the headline chart: convert php array values into a javascript array.
$headline_chart_options_donation_axis = wp_json_encode( $headline_chart_options['donation_axis'] );
$headline_chart_options_date_axis     = wp_json_encode( $headline_chart_options['date_axis'] );

$payment_methods_chart_options_payment_percentages = wp_json_encode( array() );
$payment_methods_chart_options_payment_labels      = wp_json_encode( array() );

$currency_symbol = $view_args['currency_symbol'];
$currency_symbol = ( false !== $currency_symbol ) ? html_entity_decode( $currency_symbol ) : '$';

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo esc_html__( 'Dashboard Report', 'charitable' ); ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
			<?php
			echo file_get_contents( $charitable_admin_2_0_css ); // phpcs:ignore

			/**
			 * Add any custom styles to the PDF.
			 *
			 * @since 1.8.1
			 *
			 * @param array $view_args The view arguments.
			 */
			do_action( 'charitable_pdf_dashboard_styles', $view_args );
			?>
		</style>
	</head>

	<body id="charitable-pdf" class="charitable-pdf-<?php echo esc_attr( $charitable_action ); ?> charitable-pdf-dashboard-<?php echo esc_attr( $charitable_action ); ?>" style="margin: 0px; padding: 0; width: 100%; border: 0; overflow-x: hidden;">

		<div class="charitable-dashboard-report"
		<?php
		if ( $charitable_action === 'download' ) :
			?>
			style="width: 100%; background-color: white; margin: 0; padding: 0;"<?php endif; ?>>

			<div class="charitable-headline-reports"
			<?php
			if ( $charitable_action === 'download' ) :
				?>
				style="margin: 0 auto; padding: 0;"<?php endif; ?>>

				<div class="charitable-print-header">
					<h1><?php echo esc_html__( 'Dashboard Report', 'charitable' ); ?></h1>
					<?php if ( $start_date && $end_date ) : ?>
						<p><?php echo esc_html( $start_date ); ?> - <?php echo esc_html( $end_date ); ?></p>
					<?php endif; ?>
				</div>

				<div id="charitable-dashboard-report-cards">
					<?php echo $charitable_cards; // phpcs:ignore ?>
				</div>

				<div class="charitable-container charitable-report-ui charitable-headline-graph-container">
					<div id="charitable-headline-graph" class="charitable-headline-graph"></div>
				</div>

			</div>

			<div class="charitable-section charitable-section-flexible">

				<div id="charitable-dashboard-report-sections">
					<?php echo $charitable_reports; // phpcs:ignore ?>
				</div>

			</div>

		</div>

		<?php if ( $charitable_action === 'print' ) : ?>

			<script src="<?php echo esc_url( $charitable_chart_js ); // phpcs:ignore ?>" id="charitable-apex-charts-js"></script>

			<script id="charitable-report-data-js">

					var charitable_reporting = {
						'currency_symbol' : "<?php echo $currency_symbol; // phpcs:ignore ?>",
						"headline_chart_options":{
							"donation_axis":<?php echo $headline_chart_options_donation_axis; // phpcs:ignore ?>,
							"date_axis":<?php echo $headline_chart_options_date_axis; // phpcs:ignore ?>},
						"payment_methods_chart_options":{
							"payment_percentages":<?php echo $payment_methods_chart_options_payment_percentages; // phpcs:ignore ?>,
							"payment_labels":<?php echo $payment_methods_chart_options_payment_labels; // phpcs:ignore ?>
						}
					};

			</script>

			<script type="text/javascript" id="charitable-report-headline-chart-js">
				var charitable_headline_chart = new ApexCharts( document.querySelector("#charitable-headline-graph"), {
					chart: {
						animations: {
							enabled: false
						},
						background: '#fff',
						foreColor: "#757781",
						type: 'area',
						width: '900px',
						stacked: true,
						toolbar: {
							show: false
						},
						zoom: {
							enabled: false
						}
					},
					colors: ["#5AA15226"],
					grid: {
						borderColor: "#C9D4CA",
						clipMarkers: false,
						yaxis: {
							lines: {
								show: true
							}
						}
					},
					dataLabels: {
						enabled: false
					},
					series: [{
						name: 'Donations',
						data: charitable_reporting.headline_chart_options.donation_axis
					}],
					stroke: {
						width: 3,
						colors: ["#5AA152"]
					},
					fill: {
						type: "solid"
					},
					markers: {
						size: 5,
						colors: ["#FFFFFF"],
						strokeColor: "#5AA152",
						strokeWidth: 4
					},
					legend: {
						show: false
					},
					xaxis: {
						categories: charitable_reporting.headline_chart_options.date_axis,
					},
					yaxis: {
						labels: {
							formatter: function (val) {
								return charitable_decodeHtml(charitable_reporting.currency_symbol) + val.toFixed(2)
							}
						}
					}
				});

				charitable_headline_chart.render();

				/* utils */

				/**
				 * Util function that decodes HTML entities.
				 *
				 * @since 1.8.1
				 *
				 */
				function charitable_decodeHtml( html ) {
					var txt = document.createElement("textarea");
					txt.innerHTML = html;
					return txt.value;
				}

				window.onload = function() { window.print(); }

			</script>

		<?php endif; ?>

	</body>

</html>
