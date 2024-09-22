<?php

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#F9F6EE';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#24231E';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#61AA4F';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#61AA4F';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'environmental';
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



/* row specifics */

/* column specifics */

<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(even) {
	padding: 50px 25px 0 25px;
	background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) {
	padding-left: 0;
	padding-top: 0;
	padding-right: 0;
	padding-bottom: 0;
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-column:nth-child(odd) .charitable-campaign-field.charitable-campaign-field-photo {
	margin-top: 0;
	margin-bottom: 0;
}

/* headlines */

<?php echo $wrapper; ?> .charitable-campaign-row h1,
<?php echo $wrapper; ?> .charitable-campaign-row h2,
<?php echo $wrapper; ?> .charitable-campaign-row h3,
<?php echo $wrapper; ?> .charitable-campaign-row h4,
<?php echo $wrapper; ?> .charitable-campaign-row h5,
<?php echo $wrapper; ?> .charitable-campaign-row h6 {
	color: <?php echo $secondary; ?>
}

<?php echo $wrapper; ?> h5.charitable-campaign-field-headline {
	color: <?php echo $secondary; ?> !important;
	font-weight: 500;
	text-transform: uppercase;
	font-size: 21px;
	line-height: 23px;
}

/* text */

<?php echo $wrapper; ?>  .charitable-campaign-field-text .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;

}
<?php echo $wrapper; ?> .charitable-campaign-row .charitable-campaign-field-text h5 {
	color: <?php echo $tertiary; ?>;
}

/* field: campaign title */

<?php echo $wrapper; ?> .charitable-campaign-field_campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo $secondary; ?> !important;
	font-size: 68px !important;
	line-height: 72px !important;
	font-weight: 500 !important;
}

/* field: campaign description */

<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #D8DAD7;
}
<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 24px !important;
	line-height: 38px !important;
	font-weight: 300 !important;
}
<?php echo $wrapper; ?>  .charitable-campaign-field-campaign-description h5.charitable-field-template-headline {
	font-size: 36px;
	line-height: 42px;
	color: <?php echo $secondary; ?> !important;
	font-weight: 700;
}

/* campaign summary */

<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div {
	color: #92918E;
	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item span {
	color: <?php echo $secondary; ?>;
	font-weight: 600;
	font-size: 32px;
	line-height: 38px;
}
<?php echo $wrapper; ?> .charitable-campaign-field-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
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

/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field-donate-button button.button {
	background-color: <?php echo $button; ?> !important;
	border-color: <?php echo $button; ?> !important;
	text-transform: uppercase;
	border-radius: 10px;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	min-height: 50px;
	height: 50px;
	font-size: 16px;
	line-height: 16px;
	border-radius: 0;
	color: white;
}

/* tabs: tab container */

<?php echo $wrapper; ?> .charitable-tab-wrap {
	font-size: 14px;
	line-height: 24px;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article {
	color: #76838B;
	margin-top: 20px;
}

/* tabs: tab nav */

<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav {
	border: 1px solid transparent;
	background-color: transparent;
	width: auto;
	margin-left: 0;
	margin-right: 0;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav li {
	border-top: 0;
	border-right: 0;
	border-bottom: 0;
	border-left: 0;
	background-color: transparent;
	margin: 0 10px 0 0;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav li a {
	color: black;
	display: block;
	text-transform: none;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav li.active {
	background-color: <?php echo $primary; ?>;
	color: white;
	text-decoration: none;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav li:hover {
	background-color: <?php echo $primary; ?>;
	color: white;
	text-decoration: none;
	filter: brightness(90%);
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav li.active a,
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav li:hover a {
	color: black;
}

/* tabs: style */

<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: transparent;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: <?php echo $button; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {
	background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {
	color: black;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {
	background-color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {
	color: black;
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

<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.tab-size-small li {

}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.tab-size-small li a {
	font-weight: 500;
	font-size: inherit;
	line-height: inherit;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.tab-size-medium li {

}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.tab-size-medium li a {
	font-weight: 500;
	font-size: inherit;
	line-height: inherit;
}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.tab-size-large li {

}
<?php echo $wrapper; ?> .section[data-section-type="tabs"] article nav.tab-size-large li a {
	font-weight: 500;
	font-size: inherit;
	line-height: inherit;
}
<?php echo $wrapper; ?>  article nav.tab-size-small li {
	font-size:10px;
	padding:0
}
<?php echo $wrapper; ?>  article nav.tab-size-small li a {
	font-size:16px;
	padding:18px
}
<?php echo $wrapper; ?>  article nav.tab-size-medium li {
	font-size:14px;
	padding:0
}
<?php echo $wrapper; ?>  article nav.tab-size-medium li a {
	font-size:21px;
	padding:23px
}
<?php echo $wrapper; ?>  article nav.tab-size-large li {
	font-size:21px;
	padding:0
}
<?php echo $wrapper; ?>  article nav.tab-size-large li a {
	font-size:30px;
	padding:32px
}


