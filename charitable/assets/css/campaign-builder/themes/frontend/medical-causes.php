<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#192E45';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#215DB7';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#48A9F5';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#48A9F5';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'medical-causes';
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


/* headings, headlines */


<?php echo $wrapper; ?> div.charitable-campaign-row h5.charitable-field-template-headline {
	color: white;
	font-weight: 400 !important;
	font-size: 64px !important;
	line-height: 64px !important;
	margin-top: 0px;
	margin-bottom: 20px;
}
<?php echo $wrapper; ?> div.charitable-campaign-row .tab-content h5.charitable-field-template-headline {
	color: black !important;
	font-weight: 500 !important;
	text-transform: inherit;
	font-size: 32px !important;
	line-height: 38px !important;
	margin-top: 20px;
	margin-bottom: 20px;
}


/* row specifics */

<?php echo $wrapper; ?> .charitable-campaign-row {
	background-color: <?php echo $secondary; ?>;
	padding: 50px;
	color: white;
	padding: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-row > * {
	/* color: <?php echo $secondary; ?>; */
	font-size: 14px;
	line-height: 24px;
}
<?php echo $wrapper; ?> div.charitable-campaign-row.no-padding,
<?php echo $wrapper; ?> div.charitable-campaign-row.no-padding .charitable-campaign-column {
	padding: 0 !important;
}

/* column specifics */

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) {
	flex: 2;
	border: 0;
	padding-top: 50px;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) {
	border: 0;
	flex: 1 1 26%;
	padding-top: 15px;
	padding-bottom: 15px;
}

/* section specifics */

/* header */

/* field: campaign title */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-campaign-title h1 {
  margin: 5px 0 5px 0;
  font-size: 55px !important;
  line-height: 58px !important;
  font-weight: 100 !important;
  word-wrap: anywhere;
}

/* field: campaign description */
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #D8DAD7;
}
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 24px;
	line-height: 38px;
	font-weight: 300;
}

/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.button {
	background-color: <?php echo $button; ?> !important;
	border-color: <?php echo $button; ?> !important;
  text-transform: uppercase;
  border-radius: 0px;
  margin-top: 0;
  margin-bottom: 0;
  font-weight: 400;
  min-height: 50px;
  height: 50px;
  font-size: 16px;
  line-height: 15px;
}

/* field: photo */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-photo img {
  width: 100%;
  border: 5px solid <?php echo $primary; ?>;
}

/* field: text */

<?php echo $wrapper; ?> .section[data-section-type="fields"] .charitable-campaign-field-text {
  color: white;
}

/* field: summary */

<?php echo $wrapper; ?> .charitable-field-template-campaign-summary {
  padding-left: 0;
  padding-right: 0;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary div {
  font-weight: 400 !important;
  font-size: 14px !important;
  line-height: 16px !important;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary div span {
  font-weight: 100 !important;
  font-size: 32px !important;
  line-height: 38px !important;
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

<?php echo $wrapper; ?> .section[data-section-type="fields"] .charitable-field-template-campaign-summary div span,
<?php echo $wrapper; ?> .section[data-section-type="fields"] .charitable-field-template-campaign-summary .campaign-summary-item {
  color: white;
}



/* field: progress bar */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
  color: #FFFFFF;
  font-size: 21px;
  line-height: 21px;
  font-weight: 100;
  padding-left: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
  color: white;
  font-weight: 100;
  font-size: 21px;
  line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
  border: 0;
  padding: 0;
  background-color: #E0E0E0;
  border-radius: 20px;
  margin-top: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
  background-color: <?php echo $tertiary; ?>;
  height: 13px !important;
  border-radius: 20px;
  text-align: right;
  opacity: 1.0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
  display: none;
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

<?php echo $wrapper; ?> .charitable-field-preview-social-linking {
  display: table;
}

<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-field-preview-social-linking-headline-container {
  display: block;
  float: left;
  padding: 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-field-row {
  display: block;
  float: left;
  width: auto;
  margin: 0 0 0 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking h5.charitable-field-template-headline {
  font-size: 14px;
  line-height: 16px;
  color: <?php echo $secondary; ?>;
  font-weight: 300;
  margin: 0 15px 0 0;
  padding: 5px 5px 5px 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-placeholder {
  padding: 10px;
}

/* field: social sharing */

<?php echo $wrapper; ?> .charitable-field-preview-social-sharing {
  display: table;
}

<?php echo $wrapper; ?> .charitable-field-preview-social-sharing .charitable-field-preview-social-sharing-headline-container {
  display: block;
  float: left;
  padding: 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing .charitable-field-row {
  display: block;
  float: left;
  width: auto;
  margin: 0 0 0 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing h5.charitable-field-template-headline {
  font-size: 14px;
  line-height: 16px;
  color: <?php echo $secondary; ?>;
  font-weight: 300;
  margin: 0 15px 0 0;
  padding: 5px 5px 5px 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing .charitable-placeholder {
  padding: 10px;
}

/* tabs: container */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
  background-color: white;
  padding-top: 20px;
  padding-bottom: 20px;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul li {
    padding-top: 20px;
    padding-bottom: 25px;
}

/* tabs: tab nav */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
  width: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul {
    margin-top: 30px !important;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul,
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul {
    margin-left: 30px !important;
    margin-right: 30px !important;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
  border: 1px solid <?php echo $secondary; ?>;
  background-color: transparent;
  margin: 0 15px 0 0;
  padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {
  display: block;
  font-weight: 500 !important;
  font-size: 14px !important;
  line-height: 15px !important;
  text-transform: none;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {
  background-color: <?php echo $secondary; ?>;
}


/* tabs: style */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: black;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {
	background-color: <?php echo $secondary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {
	background-color: <?php echo $secondary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {
	color: white;
}

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {
	color: black;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover {
	background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover a {
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active {
	background-color: <?php echo $primary; ?>;
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



