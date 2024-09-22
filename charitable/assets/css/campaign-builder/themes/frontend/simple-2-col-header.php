<?php

header("Content-type: text/css; charset: UTF-8");

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#000000';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#2B66D1';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#F99E36';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#5AA152';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'simple-2-col-header';
$wrapper         = '.charitable-campaign-wrap.template-' . $slug;
$preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $slug;

/* what should change from admin vs. frontend */

// .charitable-field        ----------> .charitable-campaign-field
// .charitable-preview-*    ----------> .charitable-campaign-*

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $wrapper; ?> {
/* field items in preview area */
font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

<?php echo $wrapper; ?> .charitable-campaign-field {
  /* display: flex; */
  display: table;
}

/* wide spread changes in header vs tabs */

<?php echo $wrapper; ?> > header {
  margin-bottom: 40px;
  font-size: 40px;
  line-height: 70px;
  font-weight: 600;
  color: <?php echo $secondary; ?>;
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

/* aligns */

<?php echo $wrapper; ?> .charitable-campaign-align-left {
  margin-left: 0;
  margin-right: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-align-center,
<?php echo $wrapper; ?> .charitable-campaign-align-center > div {
  margin-left: auto;
  margin-right: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-align-right {
  margin-left: auto;
  margin-right: 0;
}

<?php echo $wrapper; ?> .charitable-campaign-align-left div.charitable-field-campaign-title {
    margin-left: 0;
    margin-right: auto;
    text-align: left;
}
<?php echo $wrapper; ?> .charitable-campaign-align-center div.charitable-field-campaign-title {
    margin-left: auto;
    margin-right: auto;
    text-align: center;
}
<?php echo $wrapper; ?> .charitable-campaign-align-right div.charitable-field-campaign-title {
    margin-left: auto;
    margin-right: 0;
    text-align: right;
}
<?php echo $wrapper; ?> .charitable-campaign-align-left.charitable-campaign-field-photo img {
    margin-left: 0;
    margin-right: auto;
    display: block;
}
<?php echo $wrapper; ?> .charitable-campaign-align-center.charitable-campaign-field-photo img {
    margin-left: auto;
    margin-right: auto;
    display: block;
}
<?php echo $wrapper; ?> .charitable-campaign-align-right.charitable-campaign-field-photo img {
    margin-left: auto;
    margin-right: 0;
    display: block;
}
<?php echo $wrapper; ?> .charitable-campaign-align-left .charitable-field-template-social-sharing,
<?php echo $wrapper; ?> .charitable-campaign-align-left .charitable-field-template-social-linking {
    margin-left: 0;
    margin-right: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-align-center .charitable-field-template-social-sharing,
<?php echo $wrapper; ?> .charitable-campaign-align-center .charitable-field-template-social-linking {
    margin-left: auto;
    margin-right: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-align-right .charitable-field-template-social-sharing,
<?php echo $wrapper; ?> .charitable-campaign-align-right .charitable-field-template-social-linking {
    margin-left: auto;
    margin-right: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-align-left .donation-wall ol {
    text-align: left;
}
<?php echo $wrapper; ?> .charitable-campaign-align-center .donation-wall ol {
    text-align: center;
}
<?php echo $wrapper; ?> .charitable-campaign-align-right .donation-wall ol {
    text-align: right;
}

<?php echo $wrapper; ?> .charitable-placeholder {
    width: 100%;
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
    color: <?php echo $primary; ?>;
}

/* field: campaign description */

<?php echo $wrapper; ?> .charitable-campaign-field-campaign-description h5.charitable-field-template-headline {
  font-size: 27px;
  line-height: 29px;
  font-weight: 500;
  margin-top: 5px;
  margin-bottom: 5px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text {
  padding: 0;
  margin: 0;
  color: #000000;
  font-size: 14px;
  line-height: 24px;
}
<?php echo $wrapper; ?> .charitable-campaign-builder-no-description-preview div {
  margin: 0;
  float: none;
}

/* field: campaign text */

<?php echo $wrapper; ?> .charitable-campaign-field-text {
  padding: 0;
  color: #000000;
  font-size: 14px;
  line-height: 24px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-text h5.charitable-field-template-headline {
  font-size: 27px;
  line-height: 29px;
  font-weight: 500;
  margin-top: 5px;
  margin-bottom: 5px;
}

/* field: html */

<?php echo $wrapper; ?> .charitable-field.charitable-field-html .placeholder {
  padding: 0;
  margin: 0;
}

/* field: button */
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button {
    margin-top: 15px;
    margin-bottom: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.charitable-button,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button a.charitable-button {
    background-color: <?php echo $button; ?>;
    border-color: <?php echo $button; ?>;
    text-transform: none;
    border-radius: 0px;
    margin-top: 0;
    margin-bottom: 0;
    width: 100%;
    font-weight: 400;
    font-size: 16px;
    line-height: 25px;
}

/* field: photo */

<?php echo $wrapper; ?> .charitable-campaign-field-photo .charitable-campaign-primary-image {
  border: transparent;
  border-radius: 0px;
  margin-top: 25px;
  margin-bottom: 25px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-photo .charitable-campaign-primary-image img {
  max-width: 100%;
}
<?php echo $wrapper; ?> .tab-content .charitable-campaign-field-photo .charitable-campaign-primary-image img {
  border-radius: 0px;
}


<?php echo $wrapper; ?>  .charitable-field-photo .charitable-campaign-align-center .primary-image-container {
  text-align: center;
}
<?php echo $wrapper; ?>  .charitable-field-photo .charitable-campaign-align-left .primary-image-container {
  text-align: left;
}
<?php echo $wrapper; ?>  .charitable-field-photo .charitable-campaign-align-right .primary-image-container {
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

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
    color: <?php echo $primary; ?>;
    font-weight: 400;
    font-size: 18px;
    line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
    font-weight: 400;
    font-size: 18px;
    line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
    border: 0;
    padding: 0;
    background-color: #E0E0E0;
    border-radius: 5px;
    margin-top: 15px;
    margin-bottom: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
    background-color: <?php echo $secondary; ?>;
    height: 8px !important;
    border-radius: 5px;
    text-align: right;
    opacity: 1.0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
    display: inline-block;
    border-radius: 25px;
    width: 25px;
    height: 25px;
    margin-right: -15px;
    margin-top: -10px;
}

/* field: social linking */

<?php echo $wrapper; ?> .charitable-campaign-field-social-links  {
  display: table;
}

<?php echo $wrapper; ?> .charitable-campaign-field-social-links .charitable-field-template-social-linking-headline-container {
    display: block;
    float: left;
    padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field-social-links .charitable-field-row {
    display: block;
    float: left;
    width: auto;
    margin: 0 0 0 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field-social-links h5.charitable-field-preview-headline {
  font-size: 14px !important;
  line-height: 26px !important;
  font-weight: 300 !important;
  margin: 0 20px 0 0;
  padding: 0;
  color: <?php echo $primary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-social-links .charitable-placeholder {
    padding: 10px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking {
    display: table;
    margin-top: 10px;
    margin-bottom: 10px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row p {
    display: none;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking h5.charitable-field-template-headline {
    font-size: 14px !important;
  line-height: 26px !important;
  font-weight: 300 !important;
  margin: 0 20px 0 0;
  padding: 0;
  color: <?php echo $primary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .charitable-social-field-column {
    float: left;
    margin-right: 20px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .charitable-social-field-column .chartiable-campaign-social-link {
    margin-top: 5px;
    min-height: 20px !important;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .chartiable-campaign-social-link img,
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .chartiable-campaign-social-link a {
    width: 25px;
    height: 25px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-linking .charitable-field-row .chartiable-campaign-social-link a:hover {
    opacity: 0.65;
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

<?php echo $wrapper; ?> .charitable-campaign-field-social-sharing {
  display: table;
}

<?php echo $wrapper; ?> .charitable-campaign-field-social-sharing .charitable-field-template-social-sharing-headline-container {
  display: block;
  float: left;
  padding: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field-social-sharing .charitable-field-row {
  display: block;
  float: left;
  width: auto;
  margin: 0 0 0 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field-social-sharing h5.charitable-field-preview-headline {
  font-size: 14px;
  line-height: 26px;
  font-weight: 300;
  margin: 0 20px 0 0;
  padding: 0;
  color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-field-social-sharing .charitable-placeholder {
  padding: 10px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing {
    display: table;
    margin-top: 10px;
    margin-bottom: 10px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row p {
    display: none;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {
    font-size: 14px !important;
  line-height: 26px !important;
  font-weight: 300 !important;
  margin: 0 20px 0 0;
  padding: 0;
  color: <?php echo $primary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-social-field-column {
    float: left;
    margin-right: 20px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-social-field-column .chartiable-campaign-social-link {
    margin-top: 5px;
    min-height: 20px !important;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .chartiable-campaign-social-link img,
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .chartiable-campaign-social-link a {
    width: 25px;
    height: 25px;
}
<?php echo $wrapper; ?> .charitable-field-template-social-sharing .charitable-field-row .chartiable-campaign-social-link a:hover {
    opacity: 0.65;
}

/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-field-template-campaign-summary {
  padding-left: 0;
  padding-right: 0;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary div {
  color: <?php echo $primary; ?>;
  font-weight: 400;
  font-size: 14px;
  line-height: 16px;
  text-align: left;
  text-transform: capitalize;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary div span {
  color: <?php echo $secondary; ?>;
  font-weight: 600;
  font-size: 32px;
  line-height: 38px;
  text-align: left;
  text-transform: capitalize;
}
<?php echo $wrapper; ?> .charitable-field-template-campaign-summary .campaign-summary-item {
  color: <?php echo $primary; ?>;
  border: 0;
  margin-top: 5px;
  margin-bottom: 5px;
  text-align: left;
  text-transform: capitalize;
}

/* field: donate amount */

<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-campaign-ield-donate-amount input.custom-donation-input[type="text"] {
    color: <?php echo $primary; ?>;
    border: 1px solid <?php echo $primary; ?> !important;
    pointer-events: auto;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
    border: 10px solid <?php echo $secondary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
    color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-template-donation-options ul.charitable-template-donation-amounts {
    margin: 0;
}
<?php echo $wrapper; ?> .charitable-template-donation-options ul.charitable-template-donation-amounts .charitable-template-donation-amount,
<?php echo $wrapper; ?> .charitable-template-donation-options ul.charitable-template-donation-amounts .charitable-template-donation-amount.custom-donation-amount input[type="text"] {
    color: <?php echo $primary; ?> !important;
    border-color: <?php echo $primary; ?> !important;
    pointer-events: auto !important;
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

<?php echo $wrapper; ?> .charitable-campaign-tab-container {
  background-color: transparent;
  margin-top: 0px;
}

<?php echo $wrapper; ?>  .tab-content img {
  max-width: 100%;
}
<?php echo $wrapper; ?>  .tab-content > ul {
  margin-left: 0;
  margin-right: 0;
}
<?php echo $wrapper; ?> .tab-content > ul > li {
    margin: 0;
}

/* tabs: tab nav */

<?php echo $wrapper; ?> article nav.charitable-campaign-nav {
  border: 1px solid transparent;
  background-color: transparent;
  width: auto;
  margin-left: 0;
  margin-right: 0;
}
<?php echo $wrapper; ?> article nav.charitable-campaign-nav li {
  border-top: 0;
  border-right: 0;
  border-bottom: 0;
  border-left: 0;
  background-color: transparent;
  margin: 0 10px 0 0;
  padding: 0;
  border: 0;
}
<?php echo $wrapper; ?> article nav.charitable-campaign-nav li a {
  color: black;
  display: block;
  text-transform: none;
  border: 0;
}
<?php echo $wrapper; ?> article nav.charitable-campaign-nav li.active {
  background-color: <?php echo $tertiary; ?>;
  color: white;
  text-decoration: none;
  border: 0;
}
<?php echo $wrapper; ?> article nav.charitable-campaign-nav li:hover {
  background-color: <?php echo $tertiary; ?>;
  color: white;
  text-decoration: none;
  filter: brightness(90%);
}
<?php echo $wrapper; ?> article nav.charitable-campaign-nav li.active a,
<?php echo $wrapper; ?> article nav.charitable-campaign-nav li:hover a {
  color: white;
}

/* tabs: style */

<?php echo $wrapper; ?> article nav.charitable-tab-style-boxed li {

}
<?php echo $wrapper; ?> article nav.charitable-tab-style-boxed li a {

}
<?php echo $wrapper; ?> article nav.charitable-tab-style-rounded li {
  border-radius: 35px;
}
<?php echo $wrapper; ?> article nav.charitable-tab-style-rounded li a {

}
<?php echo $wrapper; ?> article nav.charitable-tab-style-minimum li {
  background-color: transparent !important;
  border-top: 0;
  border-right: 0;
  border-left: 0;
}
<?php echo $wrapper; ?> article nav.charitable-tab-style-minimum li a {
  color: <?php echo $primary; ?> !important;
  border-top: 0;
  border-right: 0;
  border-left: 0;
}
<?php echo $wrapper; ?> article nav.charitable-tab-style-minimum li.active {
  border-bottom: 1px solid <?php echo $button; ?> !important;
}

/* tabs: sized */

<?php echo $wrapper; ?> article nav.charitable-tab-size-small li {

}
<?php echo $wrapper; ?> article nav.charitable-tab-size-small li a {
  font-weight: 500;
  font-size: inherit;
  line-height: inherit;
}
<?php echo $wrapper; ?> article nav.charitable-tab-size-medium li {

}
<?php echo $wrapper; ?> article nav.charitable-tab-size-medium li a {
  font-weight: 500;
  font-size: inherit;
  line-height: inherit;
}
<?php echo $wrapper; ?> article nav.charitable-tab-size-large li {

}
<?php echo $wrapper; ?> article nav.charitable-tab-size-large li a {
  font-weight: 500;
  font-size: inherit;
  line-height: inherit;
}

<?php echo $wrapper; ?>  article nav.charitable-tab-size-small li {
  font-size:10px;
  padding:0
}
<?php echo $wrapper; ?>  article nav.charitable-tab-size-small li a {
  font-size:16px;
  padding:18px
}
<?php echo $wrapper; ?>  article nav.charitable-tab-size-medium li {
  font-size:14px;
  padding:0
}
<?php echo $wrapper; ?>  article nav.charitable-tab-size-medium li a {
  font-size:21px;
  padding:23px
}
<?php echo $wrapper; ?>  article nav.charitable-tab-size-large li {
  font-size:21px;
  padding:0
}
#charitable-design-wrap article nav.charitable-tab-size-large li a {
  font-size:30px;
  padding:32px
}

/* field: donor wall */

<?php echo $wrapper; ?> .charitable-campaign-field-donation-wall {
    font-size: 18px;
    line-height: 24px;
    margin-top: 15px;
    margin-bottom: 15px;
}

/* field: organizer */
<?php echo $wrapper; ?> .charitable-campaign-field-organizer {
    font-size: 15px;
    line-height: 25px;
    font-weight: 400;
}
<?php echo $wrapper; ?> .charitable-campaign-field-organizer .charitable-organizer-name {
    font-weight: 600;
    font-size: 21px;
    line-height: 31px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-organizer p,
<?php echo $wrapper; ?> .charitable-campaign-field-organizer h1,
<?php echo $wrapper; ?> .charitable-campaign-field-organizer h2,
<?php echo $wrapper; ?> .charitable-campaign-field-organizer h3,
<?php echo $wrapper; ?> .charitable-campaign-field-organizer h4,
<?php echo $wrapper; ?> .charitable-campaign-field-organizer h5 {
    margin-top: 0;
    margin-bottom: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field-organizer h5 {
    margin: 0;
    padding: 0;
    font-size: 15px;
    line-height: 25px;
    font-weight: 400;
}