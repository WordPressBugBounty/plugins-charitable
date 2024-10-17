<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#3418d2';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#005eff';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#00a1ff';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#ec5f25';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug    = 'youth-sports';
$wrapper = '.charitable-campaign-wrap.template-' . $slug;

?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; ?>;
	--charitable_campaign_theme_button: <?php echo $button; ?>;
}

<?php echo $wrapper; ?> {
	font-family: -apple-system, BlinkMacSystemFont, sans-serif !important;
}
<?php echo $wrapper; ?> * {
	font-family: inherit !important;
}

<?php echo $wrapper; ?> div.charitable-campaign-row {
	background-color: <?php echo $primary; ?>;
	padding: 30px;
	color: white;
}


<?php echo $wrapp; ?>  div.charitable-campaign-row .section[data-section-type="fields"] .charitable-campaign-field {
	margin-top: 10px;
	margin-bottom: 10px;
}

/* column specifics */

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) {
	flex: 1;
	border: 0;
	padding-top: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) {
	border: 0;
	flex: 1;
	padding-top: 0;
	padding-bottom: 0;
}

/* section specifics */

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) .charitable-field-section {
	color: white;
	padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) * {
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) .charitable-field-section {
	background-color: transparent;

}

/* headlines */

<?php echo $wrapper; ?> div.charitable-campaign-row h5.charitable-field-template-headline {
	font-weight: 500;
	font-size: 14px;
	line-height: 18px;
	text-transform: uppercase;
}
<?php echo $wrapper; ?> div.charitable-preview-row h5.charitable-field-template-headline {
	color: white;
}
<?php echo $wrapper; ?> .tab-content h5.charitable-field-template-headline {
	color: black !important;
	font-weight: 700 !important;
	text-transform: inherit;
	font-size: 42px !important;
	line-height: 48px !important;
	margin-top: 20px;
	margin-bottom: 20px;
}

<?php echo $wrapper; ?> .charitable-header h5.charitable-field-template-headline {
	color: <?php echo $primary; ?> !important;
	font-weight: 500 !important;
	text-transform: inherit;
	font-size: 42px !important;
	line-height: 48px !important;
	margin-top: 20px;
	margin-bottom: 20px;
}

/* field: campaign title */

<?php echo $wrapper; ?> .charitable-campaign-field_campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo $secondary; ?>;
	font-weight: 600;
	font-size: 42px;
	line-height: 60px;
	text-transform: uppercase;
	word-wrap: anywhere;
}

/* field: campaign description */

<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 15px;
	line-height: 28px;
	font-weight: 400;
}

/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.button,
<?php echo $wrapper; ?> button.charitable-button,
<?php echo $wrapper; ?> a.charitable-button {
	background-color: <?php echo $button; ?> !important;
	border-color: <?php echo $button; ?> !important;
	text-transform: none;
	border-radius: 0px;
	text-transform: uppercase;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	height: 50px;
	font-size: 16px;
	line-height: 16px;
}
<?php echo $wrapper; ?> a.charitable-button.donate-button {
	font-size: 16px;
	line-height: 36px;
  	display: inline-block;
  	text-align: center;
  	text-decoration: none;
}

/* field: progress bar */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	font-size: 28px;
	line-height: 18px;
	font-weight: 500;
	text-transform: uppercase;
	padding-left: 0;
	text-align: left;
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row {
	font-size: 14px;
	line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	font-weight: 500;
	font-size: 14px;
	line-height: 18px;
	text-transform: uppercase;
	text-align: right;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	border-radius: 0;
	margin-top: 25px;
	height: 10px;
	overflow: hidden;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	height: 18px;
	border-radius: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
	display: none;
}

/* field: social linking */

<?php echo $wrapper; ?> .charitable-campaign-field-social-links {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking {
	display: table;
	padding: 0;
	margin-top: 40px;
}

<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-template-social-linking-headline-container  {
	float: left;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking-headline-container h5 {
	margin-right: 10px !important;
	font-weight: 400 !important;
	font-size: 18px !important;
	line-height: 24px !important;
	color: white !important;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}

<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row p {
	display: none;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking h5.charitable-field-template-headline {
	color: <?php echo $secondary; ?>;
	margin: 0 30px 10px 0 !important;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-placeholder {
	padding: 10px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .charitable-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .charitable-field-column .chartiable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}

<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .chartiable-campaign-social-link a:hover {
	opacity: 0.65;
}


/* field: social sharing */

<?php echo $wrapper; ?> .charitable-campaign-field-social-sharing {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing {
	display: table;
	padding: 0;
	margin-top: 40px;
}

<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-template-social-sharing-headline-container   {
	float: left;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing-headline-container  h5 {
	margin-right: 10px !important;
	font-weight: 400 !important;
	font-size: 18px !important;
	line-height: 24px !important;
	color: white !important;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row {
	display: block;
	float: none;
	width: auto;
	margin: 0 0 0 0;
}

<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row p {
	display: none;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {
	color: <?php echo $secondary; ?>;
	margin: 0 20px 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-placeholder {
	padding: 10px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-field-column .chartiable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}

<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .chartiable-campaign-social-link a:hover {
	opacity: 0.65;
}

/* field: social sharing AND linking */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-row .chartiable-campaign-social-link,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-row .chartiable-campaign-social-link {
	border: 1px solid <?php echo $tertiary; ?>;
	border-radius: 40px;
	padding: 10px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing {
	border: 1px solid rgba(0, 0, 0, 0.20);
	border-radius: 10px;
	display: table;
	width: 100%;
	padding: 0px;
}

/* field: social sharing AND linking */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-row .chartiable-campaign-social-link,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-row .chartiable-campaign-social-link {
	border: 0;
	padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing {
	border: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking img,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing img {
	height: 20px !important;
}

<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-field-row .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-field-row .charitable-placeholder {
	padding: 10px;
}

/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-field-template-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary div {

	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary div span {
	color: white;
	font-weight: 100;
	font-size: 32px;
	line-height: 38px;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
	text-transform: capitalize;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
	width: 34%;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
	width: 43%;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
	width: 23%;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
	width: 100%;
}

/* field: donate amount */

<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount input.custom-donation-input[type="text"] {
	color: white !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
	border: 0 !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount input.custom-donation-input[type="text"] {
	border: 1px solid !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected label,
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount:hover label {
	border-width: 1px;
	border-style: solid;
	border-color: <?php echo $button; ?> !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {

}




/* field: shortcode */

<?php echo $wrapper; ?>  .shortcode-campaign {
padding-left: 10px;
padding-right: 10px;
}

/* tabs: container */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article {

}

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content img {
max-width: 100%;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul {
margin-left: 0;
margin-right: 0;
}

/* tabs: container */

<?php echo $wrapper; ?> .charitable-preview-tab-container {
	background-color: white;
}

/* tabs: tab nav */
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
	padding: 0 50px;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav {
	width: auto;
	margin-left: 0;
	margin-right: 0;
	margin-bottom: 40px;
	margin-top: 40px;
	padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav li {
	border-top: 0;
	border-right: 0;
	border-bottom: 0;
	border-left: 0;
	margin: 0 25px 0 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav li a {
	display: block;
	font-weight: 400;
	font-size: 14px;
	line-height: 15px;
	text-transform: uppercase;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav li.active a {
	font-weight: 600;
}

/* tabs: style */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li {
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover {

	border-bottom: 2px solid;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover a {
	color: black;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li a {
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li.active {
	border-bottom: 2px solid <?php echo $button; ?> !important;
}

/* tabs: sized */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li a {

}
<?php echo $wrapper; ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li {
	font-size:10px;
	padding:0
}
<?php echo $wrapper; ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li a {
	font-size:16px;
	padding:18px
}
<?php echo $wrapper; ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li {
	font-size:14px;
	padding:0
}
<?php echo $wrapper; ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li a {
	font-size:21px;
	padding:23px
}
<?php echo $wrapper; ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li {
	font-size:21px;
	padding:0
}
<?php echo $wrapper; ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li a {
	font-size:30px;
	padding:32px
}


