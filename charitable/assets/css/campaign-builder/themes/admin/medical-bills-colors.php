<?php

header("Content-type: text/css; charset: UTF-8");

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#5C8AF3';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#21458F';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#F7F7F7';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#5C8AF3';

$slug    = 'medical-bills';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

  font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $wrapper; ?> {
  /* field items in preview area */
}

/* wide spread changes in header vs tabs */

<?php echo $wrapper; ?> header {
    /* background-color: <?php echo $primary; ?>; */
    color: #606060;
}
<?php echo $wrapper; ?> header h1,
<?php echo $wrapper; ?> header h2,
<?php echo $wrapper; ?> header h3,
<?php echo $wrapper; ?> header h4,
<?php echo $wrapper; ?> header h5,
<?php echo $wrapper; ?> header h6 {
    color: <?php echo $secondary; ?>
}
<?php echo $wrapper; ?> .tab-content h1,
<?php echo $wrapper; ?> .tab-content h2,
<?php echo $wrapper; ?> .tab-content h3,
<?php echo $wrapper; ?> .tab-content h4,
<?php echo $wrapper; ?> .tab-content h5,
<?php echo $wrapper; ?> .tab-content h6 {
    color: <?php echo $secondary; ?>;
}

<?php echo $wrapper; ?> .tab-content > * {
    color: black;
}

<?php echo $wrapper; ?> header h5 {

}

<?php echo $wrapper; ?>  .placeholder {

}

/* aligns */

<?php echo $wrapper; ?> .charitable-preview-align-left > div {

}
<?php echo $wrapper; ?> .charitable-preview-align-center > div {

}
<?php echo $wrapper; ?> .charitable-preview-align-right > div {

}

/* column specifics */

<?php echo $wrapper; ?> .column[data-column-id="0"] {

}
<?php echo $wrapper; ?> .column[data-column-id="0"] .charitable-field-photo {

}
<?php echo $wrapper; ?> .column[data-column-id="1"] {

}

/* tab nav */
<?php echo $wrapper; ?> article#charitable-preview-tab-container {

}
<?php echo $wrapper; ?> article nav.charitable-campaign-preview-nav {
    background-color: <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> article nav.charitable-campaign-preview-nav ul li {
    border-right: 1px solid #EDECEC;
}
<?php echo $wrapper; ?> article nav.charitable-campaign-preview-nav ul li a {

    color: <?php echo $secondary; ?>
}
<?php echo $wrapper; ?> article nav li.active {

}
<?php echo $wrapper; ?> article nav li.active,
<?php echo $wrapper; ?> article nav.charitable-campaign-preview-nav ul li.active {
    background-color: <?php echo $primary; ?>;

}
<?php echo $wrapper; ?> article nav.charitable-campaign-preview-nav ul li.active a,
<?php echo $wrapper; ?> article nav.charitable-campaign-preview-nav ul li:hover a {
    color: white;

}
<?php echo $wrapper; ?> article .tab-content {
    color: #76838B;

}
<?php echo $wrapper; ?> article .tab-content ul {

}

/* tab sizes / styles */

<?php echo $wrapper; ?> article nav.tab-style-medium li {

}


/* headlines in general */

<?php echo $wrapper; ?> h5.charitable-field-preview-headline {
    color: <?php echo $secondary; ?>;
}

/* field: campaign title */

<?php echo $wrapper; ?>  .charitable-field-campaign-title h1 {

    color: <?php echo $secondary; ?>;

}

/* field: campaign description */

<?php echo $wrapper; ?>  .charitable-field-campaign-description h5.charitable-field-preview-headline {

}
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {

}


/* field: text */

<?php echo $wrapper; ?>  .charitable-field-text .charitable-campaign-builder-placeholder-preview-text {
    color: #76838B;

}
<?php echo $wrapper; ?>  .charitable-field-text h5.charitable-field-preview-headline {
    color: <?php echo $primary; ?>;
}


/* field: button */

<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
  background-color: <?php echo $button; ?> !important;
  border-color: <?php echo $button; ?> !important;

}

/* field: photo */

<?php echo $wrapper; ?> .charitable-field.charitable-field-photo .primary-image {


}
<?php echo $wrapper; ?> .charitable-field.charitable-field-photo .primary-image img {

}
<?php echo $wrapper; ?> .tab-content .charitable-field.charitable-field-photo .primary-image img {

}


<?php echo $wrapper; ?>  .charitable-field-photo .charitable-preview-align-center .primary-image-container {

}
<?php echo $wrapper; ?>  .charitable-field-photo .charitable-preview-align-left .primary-image-container {

}
<?php echo $wrapper; ?>  .charitable-field-photo .charitable-preview-align-right .primary-image-container {

}

/* field: photo */

<?php echo $wrapper; ?> header .primary-image-container {

}

<?php echo $wrapper; ?> .tab-content .primary-image-container {

}

/* field: progress bar */

<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
    color: <?php echo $primary; ?>;

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar span {
  background-color: transparent;
}

/* field: social linking */

<?php echo $wrapper; ?> .charitable-field-preview-social-linking {

}

<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-field-preview-social-linking-headline-container {

}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-field-row {

}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {

}
<?php echo $wrapper; ?> .charitable-field-preview-social-linking .charitable-placeholder {

}

<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-twitter .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-twitter .charitable-placeholder {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-facebook .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-facebook .charitable-placeholder {

}

<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-linkedin .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-linkedin .charitable-placeholder {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-instagram .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-instagram .charitable-placeholder {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-pinterest .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-pinterest .charitable-placeholder {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-tiktok charitable-.placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-tiktok .charitable-placeholder {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-mastodon .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-mastodon .charitable-placeholder {

}

/* field: social sharing */

<?php echo $wrapper; ?> .charitable-field-preview-social-sharing {

}

<?php echo $wrapper; ?> .charitable-field-preview-social-sharing .charitable-field-preview-social-sharing-headline-container {

}
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing .charitable-field-row {

}
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {

}
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing .charitable-placeholder {

}

/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary {

}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div {

}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div span {
    color: <?php echo $secondary; ?>;

}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item {

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
