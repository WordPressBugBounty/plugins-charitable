<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#F58A07';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#1D3444';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#5B5B5B';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#FFFFFF';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'save-the-museum';
$wrapper         = '.charitable-campaign-wrap.template-' . $slug;
$preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $slug;

?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; ?>;
	--charitable_campaign_theme_button: <?php echo $button; ?>;
}

<?php echo $wrapper; ?> {
  font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

<?php echo $wrapper; ?> .charitable-campaign-row {
  background-color: white;
  color: #5B5B5B;
  padding: 0 35px;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
  background-color: white;
  color: #5B5B5B;
}

/* column specifics */

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) {
	border: 0;
  flex: 1;
	padding: 50px 0 0 0;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) {
  flex: 1.5;
  border: 0;
}

/* headlines */

<?php echo $wrapper; ?> h5.charitable-campaign-field-headline {
	color: <?php echo $secondary; ?>;
	font-weight: 500;
	text-transform: uppercase;
	font-size: 21px;
	line-height: 23px;
}
<?php echo $wrapper; ?> .tab-content h5.charitable-field-template-headline {
	color: black;
	font-weight: 500;
	text-transform: inherit;
	font-size: 32px;
	line-height: 38px;
	margin-bottom: 10px;
}
<?php echo $wrapper; ?> .charitable-campaign-row h5 {
  font-size: 42px;
  line-height: 50px;
  font-weight: 500;
  letter-spacing: inherit;
}

/* field: campaign title */

<?php echo $wrapper; ?> .charitable-campaign-field-campaign-title h1.charitable-campaign-title {
  margin: 5px 0 5px 0;
  color: <?php echo $secondary; ?> !important;
  font-size: 68px !important;
  line-height: 72px !important;
  font-weight: 500 !important;
  font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

/* field: campaign description */

<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text,
<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text p {
  font-size: 18px;
  line-height: 27px;
  font-weight: 300;
  color: #5B5B5B;
}

/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field-donate-button button.button {
  background-color: transparent !important;
  border: 1px solid black !important;
  text-transform: uppercase;
  border-radius: 0px;
  margin-top: 0;
  margin-bottom: 0;
  width: 100%;
  font-weight: 400;
  min-height: 50px;
  font-size: 16px;
  line-height: 15px;
  color: black;
}

/* field: progress bar */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
    color: <?php echo $secondary; ?>;
    font-size: 21px;
    line-height: 21px;
    font-weight: 100;
    padding-left: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
    color: <?php echo $primary; ?>;
    font-weight: 100;
    font-size: 21px;
    line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
    border: 0;
    padding: 0;
    background-color: #E0E0E0;
    border-radius: 0px;
    margin-top: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
    background-color: <?php echo $primary; ?>;
    height: 13px !important;
    border-radius: 0px;
    text-align: right;
    opacity: 1.0;
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
  font-size: 14px;
  line-height: 16px;
  color: <?php echo $secondary; ?>;
  font-weight: 300;
  margin: 0 15px 0 0;
  padding: 5px 5px 5px 0;
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
<?php echo $wrapper; ?> .charitable-field-template-social-sharing-headline-container  h5 {
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
  font-size: 14px;
  line-height: 16px;
  color: <?php echo $secondary; ?>;
  font-weight: 300;
  margin: 0 15px 0 0;
  padding: 5px 5px 5px 0;
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


/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary {
  padding-left: 0;
  padding-right: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div {
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item span {
    color: <?php echo $primary; ?>;
    font-weight: 500;
    font-size: 32px;
    line-height: 38px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary .campaign-summary-item {
    border: 0;
    margin-top: 5px;
    margin-bottom: 5px;
    color: <?php echo $secondary; ?>;
    text-align: left;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
  width: 34%;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
  width: 43%;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
  width: 23%;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
  width: 100%;
}

/* field: donate amount */

<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount input.custom-donation-input[type="text"] {
  color: <?php echo $secondary; ?>;
  border: 1px solid <?php echo $secondary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
  background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
  color: <?php echo $tertiary; ?>;
}

/* tabs: container */

<?php echo $wrapper; ?> .charitable-campaign-tab-container {
  background-color: <?php echo $tertiary; ?>;
}

/* tabs: tab nav */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
    padding: 30px;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
    border: 1px solid <?php echo $primary; ?>;
    background-color: <?php echo $primary; ?>;
    width: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
    border-top: 0;
    border-right: 1px solid <?php echo $primary; ?>;
    border-bottom: 0;
    border-left: 0;
    background-color: transparent;
    margin: 0;
    padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {
    color: white;
    display: block;
    font-weight: 500 !important;
    font-size: 14px !important;
    line-height: 15px !important;
    text-transform: none !important;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active,
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li:hover {
    background-color: <?php echo $primary; ?>;
    text-decoration: none;
    filter: brightness(90%);
    border: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {
    color: white;
}


/* tabs: style */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {

}

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active a {

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


