<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', esc_html( $_GET['p'] ) ) : '#3E4735'; // phpcs:ignore
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', esc_html( $_GET['s'] ) ) : '#B49A5F'; // phpcs:ignore
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', esc_html( $_GET['t'] ) ) : '#F4F0EE'; // phpcs:ignore
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', esc_html( $_GET['b'] ) ) : '#B49A5F'; // phpcs:ignore

$slug    = 'club-organization';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';

?>

.charitable-preview.charitable-builder-template-<?php echo esc_attr( $slug ); ?> { /* everything wraps in this */

	font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area div.charitable-preview-row/tabs */

<?php echo esc_attr( $wrapper ); ?> {
	/* field items in preview area */
}

<?php echo esc_attr( $wrapper ); ?> .charitable-field {
	display: flex;
}
/* wide spread changes in div.charitable-preview-row vs tabs */

<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row {
	background-color: <?php echo esc_attr( $primary ); ?>;
	color: #606060;
}
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row.no-padding,
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row.no-padding div.column {
	padding: 0 !important;
}
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h1,
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h2,
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h3,
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h4,
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h5,
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h6 {
	color: <?php echo esc_attr( $secondary ); ?>
}
<?php echo esc_attr( $wrapper ); ?> .tab-content h1,
<?php echo esc_attr( $wrapper ); ?> .tab-content h2,
<?php echo esc_attr( $wrapper ); ?> .tab-content h3,
<?php echo esc_attr( $wrapper ); ?> .tab-content h4,
<?php echo esc_attr( $wrapper ); ?> .tab-content h5,
<?php echo esc_attr( $wrapper ); ?> .tab-content h6 {
	color: black;
}
<?php echo esc_attr( $wrapper ); ?> .tab-content {
	color: <?php echo esc_attr( $tertiary ); ?>;
}
<?php echo esc_attr( $wrapper ); ?> .tab-content > * {
	color: #92908F;
}
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row > * {
	color: #D8DAD7;
}
<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h5 {
	font-size: 24px;
	line-height: 28px;
}

<?php echo esc_attr( $wrapper ); ?>  .placeholder {
	padding: 0;
}

/* aligns */

<?php echo esc_attr( $wrapper ); ?> .charitable-preview-align-left > div {
	margin-left: 0;
	margin-right: auto;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-preview-align-center > div {
	margin-left: auto;
	margin-right: auto;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-preview-align-right > div {
	margin-left: auto;
	margin-right: 0;
}

/* column specifics */

<?php echo esc_attr( $wrapper ); ?> .column[data-column-id="1"] {
	flex: 2;
	border: 0;
	padding-top: 50px;
}
<?php echo esc_attr( $wrapper ); ?> .column[data-column-id="0"] {
	border: 0;
	flex: 1;
	padding-top: 15px;
	padding-bottom: 15px;
}

#charitable-design-wrap .charitable-preview-row {
	container-type: inline-size;
	container-name: previewArea;
}

/* If the container is larger than 700px */
@container previewArea (max-width: 700px) {
	<?php echo esc_attr( $wrapper ); ?> .column[data-column-id="1"] {
		flex: auto;
	}
}

/* headlines in general */

<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row h5.charitable-field-preview-headline {
	color: <?php echo esc_attr( $secondary ); ?>;
	font-weight: 500;
	text-transform: uppercase;
	font-size: 21px;
	line-height: 23px;
}
<?php echo esc_attr( $wrapper ); ?> .tab-content h5.charitable-field-preview-headline {
	color: black;
	font-weight: 500;
	text-transform: inherit;
	font-size: 21px;
	line-height: 23px;
}

/* field: campaign title */

<?php echo esc_attr( $wrapper ); ?>  .charitable-field-campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo esc_attr( $secondary ); ?>;
	font-size: 55px;
	line-height: 58px;
	font-weight: 100;
}

/* field: campaign description */

<?php echo esc_attr( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #D8DAD7;
	max-height: 100%;
}
<?php echo esc_attr( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo esc_attr( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 24px;
	line-height: 38px;
	font-weight: 300;
}


/* field: text */

<?php echo esc_attr( $wrapper ); ?>  .charitable-field-text .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;

}
<?php echo esc_attr( $wrapper ); ?>  .charitable-field-text h5.charitable-field-preview-headline {

}


/* field: button */

<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
	background-color: <?php echo esc_attr( $button ); ?> !important;
	border-color: <?php echo esc_attr( $button ); ?> !important;
	text-transform: uppercase;
	border-radius: 0px;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	min-height: 50px;
	height: 50px;
	font-size: 16px;
	line-height: 50px;
}

/* field: photo */

<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image {
	border: transparent;
	border-radius: 0px;
}

<?php echo esc_attr( $wrapper ); ?> div.charitable-preview-row .primary-image-container {
	margin: 0;
	padding: 0;
}

<?php echo esc_attr( $wrapper ); ?> .tab-content .primary-image-container {
	margin: 0;
	padding: 0;
}

<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-photo img {
	max-width: 100%;
}

/* field: progress bar */

<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: #FFFFFF;
	font-size: 21px;
	line-height: 21px;
	font-weight: 100;
	padding-left: 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: <?php echo esc_attr( $secondary ); ?>;
	font-weight: 100;
	font-size: 21px;
	line-height: 21px;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 0px;
	margin-top: 15px;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
	background-color: <?php echo esc_attr( $secondary ); ?>;
	height: 13px !important;
	border-radius: 0px;
	text-align: right;
	opacity: 1.0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar span {
	display: none;
}

/* field: social linking */

<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-linking {
	display: table;
}

<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-field-preview-social-linking-headline-container {
	display: block;
	float: left;
	padding: 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {
	font-size: 14px;
	line-height: 16px;
	color: <?php echo esc_attr( $secondary ); ?>;
	font-weight: 300;
	margin: 0 15px 0 0;
	padding: 5px 5px 5px 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-placeholder {
	padding: 10px;
}

/* field: social sharing */

<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-sharing {
	display: table;
}

<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-preview-social-sharing-headline-container {
	display: block;
	float: left;
	padding: 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {
	font-size: 14px;
	line-height: 16px;
	color: <?php echo esc_attr( $secondary ); ?>;
	font-weight: 300;
	margin: 0 15px 0 0;
	padding: 5px 5px 5px 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-placeholder {
	padding: 10px;
}

/* field: campaign summary */

<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary div {

	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary div span {
	color: <?php echo esc_attr( $secondary ); ?>;
	font-weight: 100;
	font-size: 32px;
	line-height: 38px;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
	width: 34%;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
	width: 43%;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
	width: 23%;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
	width: auto;
}


/* field: donate amount */

<?php echo esc_attr( $wrapper ); ?> .charitable-field-donate-amount label,
<?php echo esc_attr( $wrapper ); ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
	color: <?php echo esc_attr( $secondary ); ?>;
	border: 1px solid <?php echo esc_attr( $secondary ); ?> !important;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
	background-color: <?php echo esc_attr( $primary ); ?>;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo esc_attr( $tertiary ); ?>;
}
<?php echo esc_attr( $wrapper ); ?> .charitable-field-preview-campaign-summary {
	text-transform: capitalize;
}

/* tabs: container */

<?php echo esc_attr( $wrapper ); ?> .charitable-preview-tab-container {
	background-color: <?php echo esc_attr( $tertiary ); ?>;
}

/* tabs: tab nav */

<?php echo esc_attr( $wrapper ); ?> article nav {
	border: 1px solid <?php echo esc_attr( $secondary ); ?>;
	width: auto;
}
<?php echo esc_attr( $wrapper ); ?> article nav li {
	border-top: 0;
	border-right: 1px solid <?php echo esc_attr( $secondary ); ?>;
	border-bottom: 0;
	border-left: 0;
	background-color: transparent;
	margin: 0;
}
<?php echo esc_attr( $wrapper ); ?> article nav li a {
	color: <?php echo esc_attr( $secondary ); ?>;
	display: block;
	font-weight: 500;
	font-size: 14px;
	line-height: 15px;
	text-transform: none;
}
<?php echo esc_attr( $wrapper ); ?> article nav li.active {
	background-color: <?php echo esc_attr( $secondary ); ?>;
}
<?php echo esc_attr( $wrapper ); ?> article nav li.active a {
	color: white;
}