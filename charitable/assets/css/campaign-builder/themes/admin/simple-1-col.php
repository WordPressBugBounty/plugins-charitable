<?php

header("Content-type: text/css; charset: UTF-8");

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#000000';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#2B66D1';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#F99E36';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#5AA152';

$slug    = 'simple-1-col';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';
$wrapper_no_fields = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap.no-fields-mode .charitable-campaign-preview';

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

  font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $wrapper; ?> {
  /* field items in preview area */
  font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

<?php echo $wrapper; ?> .charitable-field {
    display: flex;
}

/* wide spread changes in header vs tabs */

<?php echo $wrapper; ?> > header {
    margin-bottom: 40px;
    font-size: 40px;
    line-height: 70px;
    font-weight: 600;
}
<?php echo $wrapper; ?> h1:empty,
<?php echo $wrapper; ?> h2:empty,
<?php echo $wrapper; ?> h3:empty,
<?php echo $wrapper; ?> h4:empty,
<?php echo $wrapper; ?> h5:empty,
<?php echo $wrapper; ?> h6:empty {
    display: none;
}
<?php echo $wrapper; ?> header h1,
<?php echo $wrapper; ?> header h2,
<?php echo $wrapper; ?> header h3,
<?php echo $wrapper; ?> header h4,
<?php echo $wrapper; ?> header h5,
<?php echo $wrapper; ?> header h6 {

}
<?php echo $wrapper; ?> .tab-content h1,
<?php echo $wrapper; ?> .tab-content h2,
<?php echo $wrapper; ?> .tab-content h3,
<?php echo $wrapper; ?> .tab-content h4,
<?php echo $wrapper; ?> .tab-content h5,
<?php echo $wrapper; ?> .tab-content h6 {

}

<?php echo $wrapper; ?> .tab-content > * {
    color: black;
}

<?php echo $wrapper; ?> header h5 {
    font-size: 18px;
    line-height: 21px;
    font-weight: 500;
}

<?php echo $wrapper; ?>  .placeholder {
    padding-left: 0;
    padding-right: 0;
    padding-top: 0;
    padding-bottom: 0;
}

<?php echo $wrapper; ?> > .charitable-preview-header .charitable-field-wrap.charitable-field-target{
    min-height: 150px;
}
<?php echo $wrapper; ?> > .charitable-preview-row .charitable-field-wrap.charitable-field-target {
    min-height: 600px;
}

/* aligns */

<?php echo $wrapper; ?> .charitable-preview-align-left > div {
    margin-left: 0;
    margin-right: auto;
    display: table;
    width: 100%;
}
<?php echo $wrapper; ?> .charitable-preview-align-center > div {
    margin-left: auto;
    margin-right: auto;
    display: table;
    width: 100%;
}
<?php echo $wrapper; ?> .charitable-preview-align-right > div {
    margin-left: auto;
    margin-right: 0;
    display: table;
    width: 100%;
}

<?php echo $wrapper; ?> .charitable-preview-align-left div.charitable-field-campaign-title,
<?php echo $wrapper; ?> .charitable-preview-align-center div.charitable-field-campaign-title,
<?php echo $wrapper; ?> .charitable-preview-align-right div.charitable-field-campaign-title {
    width: auto;
}

<?php echo $wrapper; ?> .charitable-preview-align-left .donation-wall h5,
<?php echo $wrapper; ?> .charitable-preview-align-left .donation-wall ol {
    text-align: left;
}
<?php echo $wrapper; ?> .charitable-preview-align-center .donation-wall h5,
<?php echo $wrapper; ?> .charitable-preview-align-center .donation-wall ol {
    text-align: center;
}
<?php echo $wrapper; ?> .charitable-preview-align-right .donation-wall h5,
<?php echo $wrapper; ?> .charitable-preview-align-right .donation-wall ol {
    text-align: right;
}

/* column specifics */

<?php echo $wrapper; ?> .column[data-column-id="0"] {
    flex: 1;
    border: 0;
    padding-left: 0;
    padding-top: 0;
    padding-bottom: 0;
}
<?php echo $wrapper; ?> .column[data-column-id="0"] .charitable-field-photo {
    padding: 0;
    margin: 0;
    border: 0;
}
<?php echo $wrapper; ?> .column[data-column-id="1"] {
    flex: 1;
    border: 0;
}

<?php echo $wrapper; ?> .charitable-panel-fields .charitable-field-wrap {
    padding-bottom: 0;
}

/* headlines in general */

<?php echo $wrapper; ?>  h5.charitable-field-preview-headline {

}

/* field: campaign title */

<?php echo $wrapper; ?>  .charitable-field-campaign-title h1 {
    margin: 15px auto;
    font-size: 72px;
    line-height: 84px;
    font-weight: 600;
}

/* field: campaign description */

<?php echo $wrapper; ?> .charitable-field-campaign-description h5.charitable-field-preview-headline {
    font-size: 27px;
    line-height: 29px;
    font-weight: 500;
}
<?php echo $wrapper; ?> .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
    padding: 0;
    margin: 0;
    color: #000000;
}
<?php echo $wrapper; ?> .charitable-campaign-builder-no-description-preview div {
    margin: 0;
    float: none;
}


/* field: text */

<?php echo $wrapper; ?>  .charitable-field-text .charitable-campaign-builder-placeholder-preview-text {
    padding: 0;
    color: #202020;
}
<?php echo $wrapper; ?>  .charitable-field-text h5.charitable-field-preview-headline {

}

/* field: html */

<?php echo $wrapper; ?> .charitable-field.charitable-field-html .placeholder {
    padding: 0;
    margin: 0;
}

/* field: button */

<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder {
    padding: 0;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
  text-transform: none;
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

<?php echo $wrapper; ?> .charitable-field.charitable-field-photo .primary-image {
    border: transparent;
    border-radius: 0px;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-photo .primary-image img {
    max-width: 100%;
}
<?php echo $wrapper; ?> .tab-content .charitable-field.charitable-field-photo .primary-image img {
    border-radius: 0px;
}


<?php echo $wrapper; ?>  .charitable-field-photo .charitable-preview-align-center .primary-image-container {
    text-align: center;
}
<?php echo $wrapper; ?>  .charitable-field-photo .charitable-preview-align-left .primary-image-container {
    text-align: left;
}
<?php echo $wrapper; ?>  .charitable-field-photo .charitable-preview-align-right .primary-image-container {
    text-align: right;
}

/* field: photo */

<?php echo $wrapper; ?> header .primary-image-container {
    margin: 0;
    padding: 0;
}

<?php echo $wrapper; ?> .tab-content .primary-image-container {
    margin: 0;
    padding: 0;
}

/* field: progress bar */

<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
    color: <?php echo $primary; ?>;
    font-weight: 400;
    font-size: 18px;
    line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
    font-weight: 400;
    font-size: 18px;
    line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress {
  border: 0;
  padding: 0;
  background-color: #E0E0E0;
  border-radius: 5px;
  margin-top: 15px;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: <?php echo $secondary; ?>;
  height: 8px !important;
  border-radius: 5px;
  text-align: right;
  opacity: 1.0;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar span {
  display: inline-block;
  border-radius: 25px;
  width: 25px;
  height: 25px;
  margin-right: -15px;
  margin-top: -10px;
}

/* field: social linking */

<?php echo $wrapper; ?> .charitable-field-preview-social-linking {
    display: table;
    width: auto !important;
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
<?php echo $wrapper; ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {
    font-size: 14px;
  line-height: 26px;
  font-weight: 300;
  margin: 0 20px 0 0;
  padding: 0;
  color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-placeholder {
    padding: 10px;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-social-field-column {
    padding-top: 10px;
}

<?php /* echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-twitter .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-twitter .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/twitter-dark.svg');
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-facebook .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-facebook .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/facebook-dark.svg');
}

<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-linkedin .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-linkedin .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/linkedin-dark.svg');
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-instagram .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-instagram .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/instagram-dark.svg');
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-pinterest .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-pinterest .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/pinterest-dark.svg');
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-tiktok charitable-.placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-tiktok .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/tiktok-dark.svg');
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-mastodon .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-mastodon .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/mastodon-dark.svg');
} */ ?>

/* field: social sharing */

<?php echo $wrapper; ?> .charitable-field-preview-social-sharing {
    display: table;
    width: auto !important;
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
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {
    font-size: 14px;
    line-height: 26px;
    font-weight: 300;
    margin: 0 20px 0 0;
    padding: 0;
    color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing .charitable-placeholder {
    padding: 10px;
}

/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary {
    padding-left: 0;
    padding-right: 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div {
    color: <?php echo $primary; ?>;
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div span {
    color: <?php echo $secondary; ?>;
    font-weight: 600;
    font-size: 32px;
    line-height: 38px;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
    color: <?php echo $primary; ?>;
    border: 0;
    margin-top: 5px;
    margin-bottom: 5px;
}

/* field: donate amount */

<?php echo $wrapper; ?> .charitable-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
  color: <?php echo $primary; ?>;
  border: 1px solid <?php echo $primary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
  border: 10px solid <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
  color: <?php echo $primary; ?>;
}

/* field: donate form */

<?php echo $wrapper; ?> .charitable-field-donation-form .charitable-form-placeholder {
  width: 100%;
  margin-left: auto;
  margin-right: auto;
  background-color: #f6f6f6;
  height: auto;
  max-height: auto;
  min-height: 200px;
  padding-top: 0;
  padding-bottom: 0;
  display: inline-block;
}

/* field: shortcode */

<?php echo $wrapper; ?>  .shortcode-preview {
    padding-left: 10px;
    padding-right: 10px;
}

/* tabs: container */

<?php echo $wrapper; ?> .charitable-preview-tab-container {
    background-color: transparent;
    margin-top: 0px;
}

<?php echo $wrapper; ?> .charitable-preview-tab-container .tab-content img {
    max-width: 100%;
}
<?php echo $wrapper; ?> .charitable-preview-tab-container .tab-content > ul {
    margin-left: 0;
    margin-right: 0;
}

/* tabs: tab nav */

<?php echo $wrapper; ?> article nav {
    border: 1px solid transparent;
    background-color: transparent;
    width: auto;
    margin-left: 0;
    margin-right: 0;
}
<?php echo $wrapper; ?> article nav li {
    border-top: 0;
    border-right: 0;
    border-bottom: 0;
    border-left: 0;
    background-color: transparent;
    margin: 0 10px 0 0;
}
<?php echo $wrapper; ?> article nav li a {
    color: black;
    display: block;
    text-transform: none;
}
<?php echo $wrapper; ?> article nav li.active {
    background-color: <?php echo $primary; ?>;
    color: white;
    text-decoration: none;
}
<?php echo $wrapper; ?> article nav li:hover {
    background-color: <?php echo $primary; ?>;
    color: white;
    text-decoration: none;
    filter: brightness(90%);
}
<?php echo $wrapper; ?> article nav li.active a,
<?php echo $wrapper; ?> article nav li:hover a {
    color: white;
}

/* tabs: style */

<?php echo $wrapper; ?> article nav.tab-style-boxed li {

}
<?php echo $wrapper; ?> article nav.tab-style-boxed li a {

}
<?php echo $wrapper; ?> article nav.tab-style-rounded li {

}
<?php echo $wrapper; ?> article nav.tab-style-rounded li a {

}
<?php echo $wrapper; ?> article nav.tab-style-minimum li {
    background-color: transparent;
    border-bottom: 1px solid <?php echo $button; ?>;
    border-top: 0;
    border-right: 0;
    border-left: 0;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li a {
    color: <?php echo $primary; ?>;
    border-top: 0;
    border-right: 0;
    border-left: 0;
}

/* tabs: sized */

<?php echo $wrapper; ?> article nav.tab-size-small li {

}
<?php echo $wrapper; ?> article nav.tab-size-small li a {
    font-weight: 500;
    font-size: inherit;
    line-height: inherit;
}
<?php echo $wrapper; ?> article nav.tab-size-medium li {

}
<?php echo $wrapper; ?> article nav.tab-size-medium li a {
    font-weight: 500;
    font-size: inherit;
    line-height: inherit;
}
<?php echo $wrapper; ?> article nav.tab-size-large li {

}
<?php echo $wrapper; ?> article nav.tab-size-large li a {
    font-weight: 500;
    font-size: inherit;
    line-height: inherit;
}

