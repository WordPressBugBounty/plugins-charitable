<?php

header("Content-type: text/css; charset: UTF-8");

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#192E45';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#215DB7';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#48A9F5';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#48A9F5';

$slug = 'medical-causes';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';

?>

/* this narrows things down a little to the preview area div.charitable-preview-row/tabs */

<?php echo $wrapper; ?> {
/* field items in preview area */
}

/* wide spread changes in div.charitable-preview-row vs tabs */


<?php echo $wrapper; ?> div.charitable-preview-row {
  background-color: <?php echo $secondary; ?>;

  color: white;
}


<?php echo $wrapper; ?> div.charitable-preview-row h1,
<?php echo $wrapper; ?> div.charitable-preview-row h2,
<?php echo $wrapper; ?> div.charitable-preview-row h3,
<?php echo $wrapper; ?> div.charitable-preview-row h4,
<?php echo $wrapper; ?> div.charitable-preview-row h5,
<?php echo $wrapper; ?> div.charitable-preview-row h6 {
  color: white;
}
<?php echo $wrapper; ?> .tab-content h1,
<?php echo $wrapper; ?> .tab-content h2,
<?php echo $wrapper; ?> .tab-content h3,
<?php echo $wrapper; ?> .tab-content h4,
<?php echo $wrapper; ?> .tab-content h5,
<?php echo $wrapper; ?> .tab-content h6 {
  color: black;
}
<?php echo $wrapper; ?> .tab-content {
  color: <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> .tab-content > * {
  color: #92908F;
}
<?php echo $wrapper; ?> div.charitable-preview-row > * {
  color: #D8DAD7;
}
<?php echo $wrapper; ?> div.charitable-preview-row h5 {

}

<?php echo $wrapper; ?>  .placeholder {
  padding: 0;
}

/* aligns */


/* column specifics */

/* headlines in general */

<?php echo $wrapper; ?> div.charitable-preview-row h5.charitable-field-preview-headline {
  color: white;

<?php echo $wrapper; ?> .tab-content h5.charitable-field-preview-headline {
  color: black !important;

}

/* field: campaign title */

<?php echo $wrapper; ?>  .charitable-field-campaign-title h1 {

  color: <?php echo $secondary; ?>;


/* field: campaign description */

<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {

  color: #D8DAD7;
}
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {

}
<?php echo $wrapper; ?>  .tab-content .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {

  color: black;

}

/* field: text */



/* field: button */

<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
background-color: <?php echo $button; ?> !important;
border-color: <?php echo $button; ?> !important;

}

/* field: photo */

<?php echo $wrapper; ?> .charitable-field.charitable-field-photo .primary-image {
  border: transparent;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-photo img {
  border: 5px solid <?php echo $primary; ?>;
}

/* field: photo */


/* field: progress bar */

<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
  color: #FFFFFF;

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
  color: white;

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress {

  background-color: #E0E0E0;

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: <?php echo $tertiary; ?>;

}


/* field: social linking */

<?php echo $wrapper; ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {

  color: <?php echo $secondary; ?>;

}

/* field: social sharing */


<?php echo $wrapper; ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {
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

/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary {
  padding-left: 0;
  padding-right: 0;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div {

  font-weight: 400;
  font-size: 14px;
  line-height: 16px;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div span {
  color: white;
  font-weight: 100;
  font-size: 32px;
  line-height: 38px;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
  border: 0;
  margin-top: 5px;
  margin-bottom: 5px;
  text-transform: capitalize;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
  width: 34%;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
  width: 43%;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
  width: 23%;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
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

/* tabs: container */

<?php echo $wrapper; ?> .charitable-preview-tab-container {
  background-color: white;
}

/* tabs: tab nav */

<?php echo $wrapper; ?> article nav {
  width: auto;
}
<?php echo $wrapper; ?> article nav li {
  border: 1px solid <?php echo $secondary; ?>;
  background-color: transparent;
  margin: 0 15px 0 0;
}
<?php echo $wrapper; ?> article nav li a {
  color: black;
  display: block;
  font-weight: 500;
  font-size: 14px;
  line-height: 15px;
  text-transform: none;
}
<?php echo $wrapper; ?> article nav li.active {
  background-color: <?php echo $secondary; ?>;
}
<?php echo $wrapper; ?> article nav li.active a {
  color: white;
}

/* missing addons? */

<?php echo $wrapper; ?> .charitable-field-video,
<?php echo $wrapper; ?> .charitable-field-charitable-videos {

}
<?php echo $wrapper; ?> .charitable-field-video .charitable-missing-addon-content,
<?php echo $wrapper; ?> .charitable-field-charitable-videos .charitable-missing-addon-content {
    background-color: transparent;
}
