<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#5C8AF3';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#21458F';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#F5F0EE';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#5C8AF3';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'medical-bills';
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

<?php echo $wrapper; ?> .charitable-campaign-row {
	color: #76838B;
}
<?php echo $wrapper; ?> article {
	color: #76838B;
	margin-top: 0;
}



/* row specifics */

<?php echo $wrapper; ?> .charitable-campaign-row {
	padding: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-row > * {
	color: <?php echo $secondary; ?>;
	font-size: 14px;
	line-height: 24px;
}
<?php echo $wrapper; ?> .charitable-campaign-row h5 {
	color: <?php echo $primary; ?>;
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 600;
}
<?php echo $wrapper; ?> #charitable-template-row-1 {
	/* background-color: <?php echo $tertiary; ?>; */
}

/* column specifics */

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) {
	flex: 1;
	padding: 0 25px 0 25px;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) {
	flex: 1;
	padding: 0;
}

/* section specifics */

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) .charitable-field-section {
	background-color: white;
	padding: 25px;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) .charitable-field-section {

}

/* header */

<?php echo $wrapper; ?> .charitable-campaign-row-type-header h1,
<?php echo $wrapper; ?> .charitable-campaign-row-type-header .charitable-campaign-field_campaign-title h1 {
	font-size: 40px !important;
	line-height: 70px !important;
	font-weight: 600  !important;
	color: <?php echo $secondary; ?> !important;
	word-wrap: anywhere;
}

/* field: campaign title */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo $secondary; ?> !important;
	font-size: 68px !important;
	line-height: 72px !important;
	font-weight: 500 !important;
	word-wrap: anywhere;
}

/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field-donate-button button.button {
	background-color: <?php echo $button; ?> !important;
	border-color: <?php echo $button; ?> !important;
}

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: #202020;
	font-weight: 500;
	font-size: 18px;
	line-height: 21px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: <?php echo $primary; ?>;
	font-weight: 600;
	font-size: 24px;
	line-height: 28px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 5px;
	margin-top: 15px;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	background-color: <?php echo $primary; ?>;
	height: 8px !important;
	border-radius: 5px;
	text-align: right;
	opacity: 1.0;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
}

/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary {

}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div {
	color: #92918E;

}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div span {
	color: <?php echo $secondary; ?> !important;

}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary .campaign-summary-item {

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

/* tabs: tab nav */


<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
  background-color: #F7F7F7;
  margin: 0;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
	border-top: 0;
	border-right: 0
	border-bottom: 0;
	border-left: 0;
	margin: 0 5px 0 0;
	padding: 0;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {
	display: block;
	font-weight: 500 !important;
	font-size: 17px !important;
	line-height: 17px !important;
	text-transform: none;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {

	border: 0;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {

}

/* tabs: style */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: <?php echo $secondary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {
	background-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {
	background-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {
	color: white;
}

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {
	color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover {
	background-color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover a {
	color: white;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active {
	background-color: <?php echo $button; ?>;
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



