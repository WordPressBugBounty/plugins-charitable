<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#9F190E';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#202020';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#FFFFFF';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#9F190E';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'disaster-relief';
$wrapper         = '.charitable-campaign-wrap.template-' . $slug;
$preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $slug;

?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; ?>;
	--charitable_campaign_theme_button: <?php echo $button; ?>;
}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $wrapper; ?> .charitable-campaign-row {
	background-color: <?php echo $tertiary; ?>;
	color: #606060;
}



/* row specifics */

<?php echo $wrapper; ?> .charitable-campaign-row {
	padding: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-row > * {
	color: <?php echo $secondary; ?>;
	font-size: 14px;
	line-height: 24px;
}
<?php echo $wrapper; ?> .charitable-campaign-row h5 {
	color: <?php echo $primary; ?>;
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo $wrapper; ?> #charitable-template-row-1 {
	background-color: <?php echo $tertiary; ?>;
}

/* column specifics */

<?php echo $wrapper; ?> #charitable-template-row-0 .charitable-campaign-column:nth-child(even) {
	border: 1px solid #ECECEC;
	padding: 25px;
}
<?php echo $wrapper; ?> #charitable-template-row-0 .charitable-campaign-column:nth-child(odd) {
	flex: 0 0 66%;
	padding: 25px;
}
<?php echo $wrapper; ?> #charitable-template-row-0 .charitable-campaign-column:nth-child(even) .charitable-campaign-field {
	margin-top: 5px;
	margin-bottom: 5px;
}

/* field: campaign description */

<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #D8DAD7;
}
<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 24px;
	line-height: 38px;
	font-weight: 300;
}


/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field-donate-button button.button {
	border-radius: 35px;
	background-color: <?php echo $button; ?> !important;
	border-color: <?php echo $button; ?> !important;
}

/* field: progress bar */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: #202020;
	font-weight: 500;
	font-size: 18px;
	line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: <?php echo $primary; ?>;
	font-weight: 600;
	font-size: 24px;
	line-height: 28px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 5px;
	margin-top: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	background-color: <?php echo $primary; ?>;

}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
	display: inline-block;
	background-color: <?php echo $primary; ?>;
	border-radius: 25px;
	width: 25px;
	height: 25px;
	margin-right: -15px;
	margin-top: -10px;
}


/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary {
	margin-bottom: 10px !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div {

}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div span {
	color: <?php echo $secondary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
}

/* field: donate amount */

<?php echo $wrapper; ?> .charitable-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
	color: <?php echo $secondary; ?>;
	border: 1px solid <?php echo $secondary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
	background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo $tertiary; ?>;
}


/* field: social linking */

<?php echo $wrapper; ?> .charitable-campaign-field-social-links {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking {
	display: table;
}

<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-template-social-linking-headline-container  {
	float: left;
	display: table-cell;
	vertical-align: middle;
	padding: 0;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking-headline-container h5 {
	margin-right: 10px;
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
}

<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-template-social-sharing-headline-container   {
	float: left;
	display: table-cell;
	vertical-align: middle;
	padding: 0;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing-headline-container h5 {
	margin-right: 10px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}

<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row p {
	display: none;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {

	color: <?php echo $secondary; ?>;

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
	border: 0;
	padding: 0px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing {
	border: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking img,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing img {
	height: 30px !important;
}

/* tabs: tab container */

<?php echo $wrapper; ?> .charitable-tab-wrap {
	font-size: 14px;
	line-height: 24px;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article {
	padding: 10px;
}

/* tabs: tab nav */


<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav {

}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
	border-top: 0;
	border-right: 1px solid <?php echo $secondary; ?>;
	border-bottom: 0;
	border-left: 0;
	margin: 0;
	padding: 0;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {

	display: block;
	font-weight: 500 !important;
	font-size: 17px !important;
	line-height: 17px !important;
	text-transform: none;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {

	border: 0;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {
	color: white;
}

/* tabs: style */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {
	background-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {
	background-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {
	color: white;
}

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {
	color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover {
	background-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover a {
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active {
	background-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active a {
	color: white;
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

/* field: donor wall */


/* field: organizer */

