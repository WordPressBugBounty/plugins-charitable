<?php

header("Content-type: text/css; charset: UTF-8");

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#3418d2';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#005eff';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#00a1ff';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#ec5f25';

$slug = 'youth-sports';
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview';

?>

/* row specifics */

<?php echo $wrapper; ?> .charitable-preview-row {
  background-color: <?php echo $primary; ?>;
  color: white;
}

/* field: progress bar */

<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress {
  background-color: rgba(255,255,255,0.5);
}
<?php echo $wrapper; ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: #fff;
}

/* field: button */

<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder {

}
<?php echo $wrapper; ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
    background-color: <?php echo $button; ?>;
    border-color: <?php echo $button; ?>;
}

/* field: donate amount */

<?php echo $wrapper; ?> .charitable-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {

}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected label {
  border-color: <?php echo $button; ?> !important;
}
<?php echo $wrapper; ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {

}

/* field: campaign summary */


<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary div span {
    color: white;
}
<?php echo $wrapper; ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
    color: white;
}
<?php echo $wrapper; ?> .tab-content .charitable-field-preview-campaign-summary div span {
    color: black;
}
<?php echo $wrapper; ?> .tab-content .charitable-field-preview-campaign-summary .campaign-summary-item {
    color: black;
}


/* tabs: tab nav */

<?php echo $wrapper; ?> article nav {
    border: 1px solid transparent;
    background-color: transparent;
}
<?php echo $wrapper; ?> article nav li {
}
<?php echo $wrapper; ?> article nav li a {
    color: #DDD;
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

}
<?php echo $wrapper; ?> article nav.tab-style-boxed li a {

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

}
<?php echo $wrapper; ?> article nav.tab-style-rounded li a {

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
    background-color: transparent;
    border-color: transparent;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li a {
    color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li.active {
    border-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li:hover {
    border-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li.active a {
    color: black;
}
<?php echo $wrapper; ?> article nav.tab-style-minimum li:hover a {
    color: <?php echo $tertiary; ?>;
}


/* missing addons? */

<?php echo $wrapper; ?> .charitable-field-missing {
    background-color: rgba(0,0,0,.7);
}
<?php echo $wrapper; ?> .charitable-field-missing .charitable-missing-addon-content {
    background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-field-missing .charitable-missing-addon-content,
<?php echo $wrapper; ?> .charitable-field-missing .charitable-missing-addon-content h2,
<?php echo $wrapper; ?> .charitable-field-missing .charitable-missing-addon-content p {
    color: white !important;
}