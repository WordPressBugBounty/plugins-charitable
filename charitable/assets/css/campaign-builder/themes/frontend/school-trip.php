<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#7A8347';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#000000';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#5F5F5F';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#7A8347';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'school-trip';
$wrapper         = '.charitable-campaign-wrap.template-' . $slug;
$preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $slug;

?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; ?>;
	--charitable_campaign_theme_button: <?php echo $button; ?>;
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

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) .charitable-field-section {
  background-color: <?php echo $primary; ?>;
  color: white;
  padding: 35px;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) * {
  color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) .charitable-field-section {
  background-color: transparent;

}

/* headlines */

<?php echo $wrapper; ?> div.charitable-campaign-row h5.charitable-field-template-headline {
  font-weight: 400;
  font-size: 32px;
  line-height: 34px;
}
<?php echo $wrapper; ?> div.charitable-campaign-row .charitable-campaign-column:nth-child(even) h5.charitable-field-template-headline {
  color: white;
}
<?php echo $wrapper; ?> .tab-content h5.charitable-field-template-headline {
  color: black !important;
  font-weight: 500 !important;
  text-transform: inherit;
  font-size: 32px !important;
  line-height: 38px !important;
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
  color: <?php echo $secondary; ?>;
}

/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.button,
<?php echo $wrapper; ?> button.charitable-button,
<?php echo $wrapper; ?> a.charitable-button {
	background-color: <?php echo $button; ?> !important;
	border-color: <?php echo $button; ?> !important;
  text-transform: uppercase;
  border-radius: 0px;
  margin-top: 0;
  margin-bottom: 0;
  width: 100%;
  font-weight: 400;
  min-height: 50px;
  height: 50px;
  font-size: 16px;
  line-height: 15px;
}

/* field: photo */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-photo .primary-image {
  border: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-photo .charitable-campaign-primary-image {
  width: 100%;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-photo img {
  width: 100%;
  border: 0;
  border-radius: 15px;
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
  background-color: #E0E0E0;
  border-radius: 20px;
  margin-top: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
  background-color: white;
  height: 13px !important;
  border-radius: 20px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {

}

/* field: social linking */

<?php echo $wrapper; ?> .charitable-campaign-field-social-links {

}
<?php echo $wrapper; ?> .charitable-field-template-social-linking {
  display: table;
  padding: 0;
  margin: 0;
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

/* field: donate form */

<?php echo $wrapper; ?> form.charitable-donation-form .donation-amount.selected {
  background-color: <?php echo $primary; ?>;
  border-color: <?php echo $primary; ?>;
}

<?php echo $wrapper; ?> form.charitable-donation-form .charitable-form-field.charitable-radio-list li {
  display: inline-block;
}

<?php echo $wrapper; ?> form.charitable-donation-form .charitable-notice {
  padding: 0;
}

/* tabs: container */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
  padding-top: 0px;
  padding-bottom: 0px;
  color: #000;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul > li {
    display: none;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content li {
    display: block;
}

/* tabs: nav */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
  width: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul,
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul {
    margin-left: 30px !important;
    margin-right: 30px !important;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
  border: 1px solid <?php echo $primary; ?>;
  background-color: transparent;
  margin: 0 15px 0 0;
  padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {
  color: black;
  display: block;
  font-weight: 500 !important;
  font-size: 14px !important;
  line-height: 15px !important;
  text-transform: none;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {
  background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {
  color: white !important;
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

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover a {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li.active {

}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li.active a {

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


