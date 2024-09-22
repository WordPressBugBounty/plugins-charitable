<?php

header("Content-type: text/css; charset: UTF-8");

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#000000';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#2B66D1';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#F99E36';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#5AA152';

$slug    = 'simple-2-col';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';

?>

/* field: headlines */

<?php echo $wrapper; ?> h5.charitable-field-preview-headline,
<?php echo $wrapper; ?> .charitable-campaign-title,
<?php echo $wrapper; ?> .charitable-field-preview-headline {
    color: <?php echo $primary; ?>;
}

/* field: donation amount */

<?php echo $wrapper; ?> .charitable-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
  color: <?php echo $primary; ?>;
  border-color: <?php echo $primary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
  border-color: <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
  color: <?php echo $primary; ?>;
}

/* field: progress bar */

<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
    color: <?php echo $primary; ?>;

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: <?php echo $secondary; ?>;
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar span {

}

/* field: button */

<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
    background-color: <?php echo $button; ?>;
    border-color: <?php echo $button; ?>;
}


/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary {

}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div {
    color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div span {
    color: <?php echo $secondary; ?>;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
    color: <?php echo $primary; ?>;
}


/* tabs: tab nav */

<?php echo $wrapper; ?> article nav {
    border: 1px solid transparent;
    background-color: transparent;
}
<?php echo $wrapper; ?> article nav li {
}
<?php echo $wrapper; ?> article nav li a {
}
<?php echo $wrapper; ?> article nav li.active {
    background-color: transparent;
}
<?php echo $wrapper; ?> article nav li:hover {
    background-color: transparent;
}
<?php echo $wrapper; ?> article nav li.active a {
    color: black;
}
<?php echo $wrapper; ?> article nav li:hover a {
    color: <?php echo $tertiary; ?>;
}

/* tabs: style */

<?php echo $wrapper; ?> article nav.tab-style-boxed li {
    background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-boxed li a {
    color: white;
}
<?php echo $wrapper; ?> article nav.tab-style-boxed li.active {
    background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-boxed li:hover {
    background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-boxed li.active a {
    color: white;
}
<?php echo $wrapper; ?> article nav.tab-style-boxed li:hover a {
    color: white;
}

<?php echo $wrapper; ?> article nav.tab-style-rounded li {
    background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-rounded li a {
    color: white;
}
<?php echo $wrapper; ?> article nav.tab-style-rounded li.active {
    background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-rounded li:hover {
    background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-rounded li.active a {
    color: white;
}
<?php echo $wrapper; ?> article nav.tab-style-rounded li:hover a {
    color: white;
}

<?php echo $wrapper; ?> article nav.tab-style-minimum li {
    border-color: transparent;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li a {
    color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li.active {
    border-bottom: 1px solid <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li:hover {
    border-bottom: 1px solid <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li.active a {
    color: black;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li:hover a {
    color: <?php echo $tertiary; ?>;
}