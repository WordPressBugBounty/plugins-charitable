<?php
/**
 * The template used to display the mini donation widget.
 *
 * Override this template by copying it to:
 * yourtheme/charitable/shortcodes/mini-widget.php
 *
 * @author  David Bisset
 * @package Charitable/Templates/Shortcodes
 * @since   1.8.12
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$campaign_id      = $view_args['campaign_id'];
$cta_mode         = $view_args['cta_mode'];
$cta_label        = $view_args['cta_label'];
$recurring_active = $view_args['recurring_active'];
$show_currency    = $view_args['show_currency'];
$min_amount       = $view_args['min_amount'];
$max_amount       = $view_args['max_amount'];
$accent_color     = $view_args['accent_color'];
$extra_class      = $view_args['extra_class'];
$monthly_amounts  = $view_args['monthly_amounts'];
$monthly_default  = $view_args['monthly_default'];
$monthly_show_other = $view_args['monthly_show_other'];
$monthly_impact   = $view_args['monthly_impact'];
$onetime_amounts  = $view_args['onetime_amounts'];
$onetime_default  = $view_args['onetime_default'];
$onetime_show_other = $view_args['onetime_show_other'];
$onetime_impact   = $view_args['onetime_impact'];
$campaign_url     = $view_args['campaign_url'];
$currency_code    = $view_args['currency_code'];
$currency_symbol  = $view_args['currency_symbol'];
$currency_pos     = $view_args['currency_pos'];
$widget_align     = isset( $view_args['widget_align'] ) ? $view_args['widget_align'] : 'left';
$widget_width     = isset( $view_args['widget_width'] ) ? $view_args['widget_width'] : '';
$impact_align     = isset( $view_args['impact_align'] ) ? $view_args['impact_align'] : '';
$monthly_label    = ! empty( $view_args['monthly_label'] ) ? $view_args['monthly_label'] : __( 'Monthly', 'charitable' );
$give_once_label  = ! empty( $view_args['give_once_label'] ) ? $view_args['give_once_label'] : __( 'Give Once', 'charitable' );
$widget_title_raw = isset( $view_args['widget_title'] ) ? $view_args['widget_title'] : '';
$widget_title     = $widget_title_raw
	? str_replace( '{{campaign_title}}', get_the_title( $campaign_id ), $widget_title_raw )
	: '';

// Wrapper classes.
$wrapper_classes = 'charitable-mini-widget';
if ( $extra_class ) {
	$wrapper_classes .= ' ' . $extra_class;
}

// Build inline style.
$style_parts = array();
if ( $accent_color ) {
	$style_parts[] = '--charitable-mini-widget-accent:' . esc_attr( $accent_color );
}
if ( $widget_width ) {
	$style_parts[] = 'max-width:' . esc_attr( $widget_width );
	if ( '100%' !== $widget_width ) {
		$style_parts[] = 'width:100%';
	}
}
if ( 'center' === $widget_align ) {
	$style_parts[] = 'margin-left:auto';
	$style_parts[] = 'margin-right:auto';
} elseif ( 'right' === $widget_align ) {
	$style_parts[] = 'margin-left:auto';
	$style_parts[] = 'margin-right:0';
}
$inline_style = $style_parts ? ' style="' . implode( ';', $style_parts ) . '"' : '';

// Determine starting tab.
$start_tab = $recurring_active ? 'monthly' : 'onetime';

// Use onetime amounts as the active tab when recurring is off.
$active_amounts    = $recurring_active ? $monthly_amounts : $onetime_amounts;
$active_default    = $recurring_active ? $monthly_default : $onetime_default;
$active_show_other = $recurring_active ? $monthly_show_other : $onetime_show_other;
$active_impact     = $recurring_active ? $monthly_impact : $onetime_impact;

// Data attributes for JS initialization.
$data_config = array(
	'campaignId'      => $campaign_id,
	'ctaMode'         => $cta_mode,
	'campaignUrl'     => $campaign_url,
	'recurringActive' => $recurring_active,
	'showCurrency'    => $show_currency,
	'currencyCode'    => $currency_code,
	'currencySymbol'  => $currency_symbol,
	'currencyPos'     => $currency_pos,
	'minAmount'       => $min_amount,
	'maxAmount'       => $max_amount,
	'monthly'         => array(
		'amounts'   => $monthly_amounts,
		'default'   => $monthly_default,
		'showOther' => $monthly_show_other,
		'impact'    => $monthly_impact,
	),
	'onetime'         => array(
		'amounts'   => $onetime_amounts,
		'default'   => $onetime_default,
		'showOther' => $onetime_show_other,
		'impact'    => $onetime_impact,
	),
);
?>

<div
	class="<?php echo esc_attr( $wrapper_classes ); ?>"
	data-charitable-mini-widget="<?php echo esc_attr( wp_json_encode( $data_config ) ); ?>"
	<?php echo $inline_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>

	<?php if ( $widget_title ) : ?>
	<p class="charitable-mini-widget__title"><?php echo esc_html( $widget_title ); ?></p>
	<?php endif; ?>

	<?php if ( $recurring_active ) : ?>
	<div class="charitable-mini-widget__tabs" role="tablist">
		<button
			class="charitable-mini-widget__tab charitable-mini-widget__tab--active"
			data-tab="monthly"
			role="tab"
			aria-selected="true"
		><?php echo esc_html( $monthly_label ); ?></button>
		<button
			class="charitable-mini-widget__tab"
			data-tab="onetime"
			role="tab"
			aria-selected="false"
		><?php echo esc_html( $give_once_label ); ?></button>
	</div>
	<?php endif; ?>

	<div class="charitable-mini-widget__amounts" role="group" aria-label="<?php esc_attr_e( 'Select donation amount', 'charitable' ); ?>">
		<?php foreach ( $active_amounts as $amount ) : ?>
			<?php
			$is_selected = ( (float) $amount === (float) $active_default );
			$btn_class   = 'charitable-mini-widget__amount' . ( $is_selected ? ' charitable-mini-widget__amount--selected' : '' );
			?>
			<button
				class="<?php echo esc_attr( $btn_class ); ?>"
				data-amount="<?php echo esc_attr( $amount ); ?>"
				type="button"
			>
				<?php if ( 'right' === $currency_pos ) : ?>
					<span class="charitable-mini-widget__amount-number"><?php echo esc_html( number_format( $amount, 0, '.', '' ) . $currency_symbol ); ?></span>
					<?php if ( $show_currency ) : ?>
						<span class="charitable-mini-widget__amount-currency"><?php echo esc_html( $currency_code ); ?><?php if ( $recurring_active ) { echo '/mo'; } ?></span>
					<?php endif; ?>
				<?php else : ?>
					<span class="charitable-mini-widget__amount-number"><?php echo esc_html( $currency_symbol . number_format( $amount, 0, '.', '' ) ); ?></span>
					<?php if ( $show_currency ) : ?>
						<span class="charitable-mini-widget__amount-currency"><?php echo esc_html( $currency_code ); ?><?php if ( $recurring_active ) { echo '/mo'; } ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>

		<?php if ( $active_show_other ) : ?>
			<button
				class="charitable-mini-widget__amount charitable-mini-widget__amount--other"
				data-amount="other"
				type="button"
			><?php esc_html_e( 'Other', 'charitable' ); ?></button>
		<?php endif; ?>
	</div>

	<div class="charitable-mini-widget__other-wrap" style="display:none;">
		<div class="charitable-mini-widget__other-input-wrap">
			<?php if ( 'right' !== $currency_pos ) : ?>
				<span class="charitable-mini-widget__currency-symbol"><?php echo esc_html( $currency_symbol ); ?></span>
			<?php endif; ?>
			<input
				class="charitable-mini-widget__other-input"
				type="number"
				min="0"
				step="0.01"
				placeholder="<?php esc_attr_e( 'Enter amount', 'charitable' ); ?>"
				aria-label="<?php esc_attr_e( 'Custom donation amount', 'charitable' ); ?>"
			/>
			<?php if ( 'right' === $currency_pos ) : ?>
				<span class="charitable-mini-widget__currency-symbol"><?php echo esc_html( $currency_symbol ); ?></span>
			<?php endif; ?>
		</div>
		<div class="charitable-mini-widget__error" role="alert" aria-live="polite"></div>
	</div>

	<div class="charitable-mini-widget__impact" aria-live="polite"<?php echo $impact_align ? ' style="text-align:' . esc_attr( $impact_align ) . '"' : ''; ?>>
		<?php
		// Show impact for the default amount if set.
		$default_key = (string) $active_default;
		if ( isset( $active_impact[ $default_key ] ) && '' !== $active_impact[ $default_key ] ) {
			echo wp_kses( $active_impact[ $default_key ], array() );
		}
		?>
	</div>

	<button
		class="charitable-mini-widget__cta<?php echo esc_attr( ( 'modal' === $cta_mode ) ? ' charitable-modal-trigger' : '' ); ?>"
		type="button"
		<?php if ( ! $active_default ) : ?>disabled<?php endif; ?>
		<?php if ( 'modal' === $cta_mode ) : ?>
		data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
		data-amount="<?php echo esc_attr( $active_default ); ?>"
		data-period="<?php echo esc_attr( $recurring_active ? 'monthly' : 'once' ); ?>"
		<?php endif; ?>
	><?php echo esc_html( $cta_label ); ?></button>

</div>
