<?php

header("Content-type: text/css; charset: UTF-8");

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#9F190E';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#202020';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#FFFFFF';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#9F190E';

$slug    = 'disaster-relief';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

  font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $wrapper; ?> {
  /* field items in preview area */
}
<?php echo $wrapper; ?> .charitable-field {
    display: flex;
}
/* wide spread changes in header vs tabs */

<?php echo $wrapper; ?> header {
    background-color: <?php echo $tertiary; ?>;
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
    color: <?php echo $primary; ?>;
}

<?php echo $wrapper; ?> .tab-content > * {
    color: black;
}

<?php echo $wrapper; ?> header h5 {
    font-size: 24px;
    line-height: 28px;
}

<?php echo $wrapper; ?>  .placeholder {
    padding: 0;
}

/* aligns */

<?php echo $wrapper; ?> .charitable-preview-align-left > div {
    margin-left: 0;
    margin-right: auto;
}
<?php echo $wrapper; ?> .charitable-preview-align-center > div {
    margin-left: auto;
    margin-right: auto;
}
<?php echo $wrapper; ?> .charitable-preview-align-right > div {
    margin-left: auto;
    margin-right: 0;
}

/* column specifics */

<?php echo $wrapper; ?> .column[data-column-id="0"] {
    flex: 0 0 66%;
}
<?php echo $wrapper; ?> .column[data-column-id="1"] {
    border: 1px solid #ECECEC;
}

/* headlines in general */
<?php echo $wrapper; ?>  h5.charitable-field-preview-headline {
    color: <?php echo $primary; ?>;
}

/* field: campaign title */

<?php echo $wrapper; ?>  .charitable-field-campaign-title h1 {
    margin: 5px 0 5px 0;
    color: <?php echo $primary; ?>;
    font-size: 27px;
    line-height: 29px;
    font-weight: 600;
}

/* field: campaign description */

<?php echo $wrapper; ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
    padding: 0;
    color: #202020;
}


/* field: text */

<?php echo $wrapper; ?>  .charitable-field-text .charitable-campaign-builder-placeholder-preview-text {
    padding: 0;
    color: #202020;
}
<?php echo $wrapper; ?>  .charitable-field-text h5.charitable-field-preview-headline {
    color: <?php echo $primary; ?>;
}


/* field: button */

<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
  background-color: <?php echo $button; ?> !important;
  border-color: <?php echo $button; ?> !important;
  text-transform: uppercase;
  border-radius: 35px;
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
    color: #202020;
    font-weight: 500;
    font-size: 18px;
    line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
    color: <?php echo $primary; ?>;
    font-weight: 600;
    font-size: 24px;
    line-height: 28px;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress {
  border: 0;
  padding: 0;
  background-color: #E0E0E0;
  border-radius: 5px;
  margin-top: 15px;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: <?php echo $primary; ?>;
  height: 8px !important;
  border-radius: 5px;
  text-align: right;
  opacity: 1.0;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar span {
  display: inline-block;
  background-color: <?php echo $primary; ?>;
  border-radius: 25px;
  width: 25px;
  height: 25px;
  margin-right: -15px;
  margin-top: -10px;
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
<?php echo $wrapper; ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {
    font-size: 14px;
    line-height: 16px;
    color: #24231E;
    font-weight: 300;
    margin: 0 15px 0 0;
    padding: 5px;
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
<?php echo $wrapper; ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {
    font-size: 14px;
    line-height: 16px;
    color: #24231E;
    font-weight: 300;
    margin: 0 15px 0 0;
    padding: 5px;
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
    color: #92918E;
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

