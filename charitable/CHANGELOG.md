# 1.8.7.2
* NEW: Added footer on Charitable admin pages with quick links to support and documentation.
* FIX: CSS tweaks to notifications displays and admin settings.
* UPDATE: Notification alert dot in header shows number of notifications.

# 1.8.7.1
* FIX: Minor CSS issues in CTA areas.
* FIX: Resolved an issue with Elementor embedded campaigns with the modal donation form setting selected.

# 1.8.7
* NEW: Square added as a default payment gateway. [More Information](https://www.wpcharitable.com/get-started/square/)
* NEW: ErrorHandler class that will suppress _load_textdomain_just_in_time messages.
* FIX: Updated code related to translations and PHP standards.

# 1.8.6.2
* NEW: Add ‘allow_unfiltered’ setting for CHARITABLE_ADMIN javascript variables for use in campaign builder.
* FIX: Improved realiablity of some license checks.
* FIX: Updated code related to improve security and PHP standards.

# 1.8.6.1
* FIX: Resolve security issue with public checkbox template.
* FIX: Resolve fatal error involving Elementor constants.
* FIX: Resolve issue involving some non-english translations and the campaign builder payment page.
* FIX: Resolved a CSS rendering issue with Youth Sports campaign template.

# 1.8.6
* NEW: Admins can directly reply to donors via donation notification emails.
* NEW: Four new Elementor widgets: Campaign, Donor Form, Donor Button, Campaign List.
* NEW: Ability to round donations in the campaign builder's progress bar.
* FIX: Resolved issue when tabs in visual campaigns not appearing in certain scenarios.
* FIX: Resolved issue with loading of frontend scripts in certain scenarios.
* FIX: Resolved Facebook sharing w/ sharing button on campaigns.
* FIX: Updated code related to translations, CSS, UI, and PHP standards.

# 1.8.5.3
* NEW: Added dialog box upon entering license that activates Pro if already installed.
* FIX: Improve santization of donor widget and video alt text in campaign builder.
* FIX: Tweak first/last/email field CSS on a "minimal" format form.

# 1.8.5.2
* FIX: Improved sanitation in decimal count in admin general settings.
* FIX: Improved support for certain currency settings when auto formatting certain fields in the campaign builder.
* FIX: Addressed some PHP notices that involved translations.

# 1.8.5.1
* FIX: Resolved fatal error in some scenarios upon activation/deactivation of license and installing Pro with third-party plugins.
* FIX: Resolved a JS error in settings due to incorrect sanitization.
* UPDATED: CTA related updates, including Marketing and Security tabs.

# 1.8.5.0
* NEW: Compatibility with new Charitable Pro plugin (including automatic upgrading when entering a license).
* NEW: "charitable_export_data" and "charitable_export_columns" filters for control in Charitable exporting in the admin.
* UPDATED: CSS tweaks to admin and donation form UI.
* FIX: Updated code related to translations, CSS, UI, and PHP standards.

# 1.8.4.8
* FIX: Resolved security issue involving the logout template.

# 1.8.4.7
* UPDATED: Terms and consent options are also now under "Privacy" settings and any Charitable settings in WordPress customizer are depreciated and will soon be removed.
* FIX: Made headers and spacing more consistent in settings area, along with tweaks in character output.

# 1.8.4.6
* FIX: Resolved an issue involving escpaed text on addons page.
* FIX: Tweaks to UI on certain element defaults in campaign builder.

# 1.8.4.5
* NEW: A minimum number of characters is now required for a password for user registeration. Customize via the 'charitable_minimum_password_length' filter.
* FIX: Resolved issues related to the campaign visual builder and several UI elements.
* FIX: Improved methods of exporting and importing donations via Tools.
* FIX: Updated code related to opt-in data collection, translations, CSS, UI, typos, and PHP standards.

# 1.8.4.4
* FIX: Adjusted escaping of setting labels resolving an issue rendering HTML beta tags.
* FIX: CSS adjustments to display banners on some admin screens.
* FIX: Resolved a bug with dashboard notifications.

# 1.8.4.3
* NEW: (Visual Campaign Builder) New options for Social Links and Social Sharing fields include YouTube (links only), Threads, and Bluesky.
* FIX: (Visual Campaign Builder) Visual tweaks to campaigns on "narrow content" themes.
* FIX: Adding additional check to resolve several JavaScript errors in some scenarios in the campaign builder.
* FIX: Resolved issue involving default fields appearing in visual builder for campaign summary.
* FIX: Resolved an issue regarding rendering HTML in emails in some scenarios.
* FIX: Updated code related to opt-in data collection, translations, CSS, UI, and PHP standards.

# 1.8.4.2
* NEW: Visual Campaign Builder: Changing button color in "advanced" will automatically adjust donation button text for contrast.
* FIX: Visual Campaign Builder: Updated documentation links.
* FIX: Visual Campaign Builder: Adjustments and tweaks to campaigns in visual builder (admin and frontend).
* FIX: Resolved issue involving sanitization of non-english dates in some scenarios resulting in incorrect "days until" calculations.
* FIX: Updated code related to opt-in data collection, translations, CSS, UI, and PHP standards.

# 1.8.4.1
* FIX: Resolved an issue involving the "Use This Template" button in visual campaign when choosing campaigns in some scenarios.
* FIX: Resolved an internal server issue when attempting to fetch CSS when campaign builder was viewing the Club template.
* FIX: Minor adjustments and tweaks to campaign templates including word breaking and word wrapping in the visual builder.
* FIX: Updated code related to typos, translations and PHP standards.

# 1.8.4
* NEW: Onboarding wizard on first install that guides you through setting up basic settings, gateways, plugins, and your first campaign!
* NEW: You can now opt-in into data collection to improve Charitable. See "advanced settings" screen for details.
* UPDATED: Revised output of the "Your Donations" title that was causing issues for those adding HTML via the 'charitable_donation_amount_legend' filter.
* FIX: Updated code related to translations, CSS, UI, and PHP standards.

# 1.8.3.7
* NEW: Stripe Gateway allows single or multiple credit card field layout (beta). [More Information](https://www.wpcharitable.com/documentation/credit-card-multiple-fields/)
* FIX: Ensured some additional settings are added to the database upon a new install.

# 1.8.3.6
* NEW: Form templates (beta) - standard/default and "minimal" - selectable in donation form settings in the "general" settings tab. [More Information](https://www.wpcharitable.com/documentation/donation-form-templates/)
* NEW: Added 'charitable_donation_amount_legend' filter to allow easier customization of "Your Donations" on donation form.
* NEW: Added beta global 'CHARITABLE_DISABLE_SHOW_CURRENT_DONATION_AMOUNT' to change behavior of showing a donation already "in session" on donation form.
* UPDATED: Turning off 'disable legacy mode' will now make more "add new" link for campaigns default to making new legacy campaigns.
* UPDATED: Donation buttons in visual campaign for campaigns that no longer accept donations will show a 'no longer accepting donations message' while button is disabled.
* FIX: Changed santitization of a url in Charitable upgrades which should allow upgrade to complete and prevent notice from appearing.
* FIX: Improved notifications and dashboard display in the admin for ltr displays.
* FIX: Resolved issue when donation module in campaign wasn't matching donation amount in modal triggered by donation button on same page.
* FIX: CSS adjustments to campaign templates: wrapper element, donation buttons, and tweaks to donation suggested amounts.
* FIX: Updated code related to translations and PHP standards.

# 1.8.3.5
* NEW: Added a 'charitable_gateway_fields_after_selector' action hook to insert content between gateway selectors and gateway fields on donation forms.
* UPDATED: Provided a 'campaign_categories' attribute to stat and donor shortcodes to help filter by Charitable campaign categories.
* FIX: Resolves PHP notices that appeared after WordPress 6.7.0 was released.
* FIX: Improved front-end buttons and CSS of several campaign tempalates.
* FIX: Remove notifications HTML when printing from a browser in the WordPress admin.
* FIX: Updated code related to translations and PHP standards.

# 1.8.3.4
* NEW: Added a new setting on privacy screen to allow the consent field to be required before submitting a donation.
* NEW: Added a 'charitable_redirection_after_gateway_processing' filter which helps override the URL that the donor should be redirected to after a donation in certain scenarios.
* FIX: Resolved an issue involving formatting a donation amount in non-English languages on the Charitable dashboard.
* UPDATED: Compatibility with WordPress 6.7.1.

# 1.8.3.3
* FIX: Resolved an issue when a button appeared unresponsive when verifying/disconnecting a license on the general settings tab.
* FIX: Updated translation strings and improved string output for email password reset email body.
* FIX: Resolved incorrect form field name on the privacy page for contact consent.

# 1.8.3.2
* UPDATED: To improve performance and security, user list for assigning campaign ownership in campaign settings checks for users with proper permissions.
* UPDATED: New filter 'charitable_campaign_builder_settings_general_users' to customize the updated user list.
* FIX: campaign_link() function returns $link rather than false.
* FIX: Visiting the settings page will attempt to double check licenses for any expiring or expired subscriptions.
* FIX: Notification tweaks including upgrading from older versions and showing UI even if feed is empty.
* FIX: Donation amount from custom donation field in a campaign page should now be carried to the donation page even with currency symbols included.
* FIX: Updated code related to translations, CSS, UI, and PHP standards.

# 1.8.3.1
* UPDATED: "Disable Legacy Campaign Mode" is set to on for new installs of the plugin. [More Information](https://www.wpcharitable.com/documentation/legacy-campaigns/)
* UPDATED: Plugin notification slide out UI can be closed by also clicking outside of the notification area.
* FIX: Addressed security issue on the donations list screen.
* FIX: Updated code related to translations, CSS, UI, and PHP standards.

# 1.8.3
* NEW: WordPress editor block: "Campaigns". Displays multiple Charitable campaigns in a grid.
* NEW: WordPress editor block: "Donations". Display a simple donation widget for a campaign.
* NEW: WordPress editor block: "Donors". Display a list of donors to one or all of your campaigns.
* NEW: WordPress editor block: "Campaign Stats". Allows various statistics to be dynamically calculated and displayed on a page.
* NEW: WordPress editor block: "Donation Button". Displays a donation button for a particular campaign.
* NEW: WordPress editor block: "Campaign Progress Bar". Displays a live progress bar for a particular campaign.
* NEW: WordPress editor block: "My Donations". Displayss a list of donations for the current logged in user.
* NEW: Added a new notifications engine to keep you informed of all important updates, license information, and news. New inbox icon and notifications menu items will alert when new notifications are available.
* NEW: Updated charitable.pot.
* UPDATED: Additional minification of JS and CSS to increase performance.
* FIX: Resolved an issue regarding campaign descriptions not displaying properly with the campaign shortcode in some scenarios.
* FIX: Fixed alt-text of a LinkedIn share icon in visual campaign builder.
* FIX: Code tweaks related to translations, CSS, UI, and PHP warnings.

# 1.8.2
* NEW: "Charitable Checklist" so new users can check vital settings, connect gateways, and get to building their first campaign.
* NEW: Warning messages for admin users - in gateway settings page and viewing donation forms (as admins) - if Stripe is enabled, but no keys are detected.
* NEW: Dashboard notifications for important messages and notices.
* NEW: Added 'charitable_email_fields_donation_field_value' filter which can help with output of custom field values in emails.
* NEW: Campaigns shortcode now has a new "description_limit" parameter that limits words displayed from campaign description.
* NEW: Added a donation button shortcode. [More Information](https://www.wpcharitable.com/documentation/donation-button-shortcode/)
* NEW: Added both a filter and an option (in beta) in the general setting tab to disable showing a login form or registeration reminder on donation forms. [More Information](https://www.wpcharitable.com/documentation/hiding-the-login-register-form-on-donation-forms/)
* UPDATED: Improved tour guide for visual campaign builder with more interactivity and showing more elements.
* UPDATED: Campaigns shortcode now displays a photo and campaign description from campaigns built with the visual builder.
* UPDATED: Moved "Categories", "Tags", and "Customize" menus from main menu. Now found in the Charitable -> "Tools" page.
* FIX: Adjusted appearance of the donation button in Youth Sports campaign theme when modal button setting was on.
* FIX: Resolved an issue with the donation summary field for the visual campaign builder involving showing/hiding items.
* FIX: Resolved several instances where donation amounts were not being formatted currently in non-English languages in the reporting screens.
* FIX: Improved loading for the WordPress dashboard widget.
* FIX: Adjustments to CSS on the Dashboard page.
* FIX: Minor translation, misc CSS, UI, and PHP coding updates.

# 1.8.1.15
* NEW: Added exit modal popup on getting started screen when user visits Pro page.
* NEW: Added ability via two global PHP variables to force/not force "tour" for visual campaign builder.
* FIX: Resolved issue resolving showing license information for lifetime licenses.
* FIX: Resolved a misalignment of the icon/field for the minimum donation amount field in the visual campaign builder.
* FIX: Addressed security issue involving creating new users primarily via Charitable registration shortcode.
* FIX: Resolved sanitization issue with the custom CSS field for HTML field in visual campaign builder.
* FIX: Minor translation, CSS, and PHP coding updates.

# 1.8.1.14
* FIX: Resolved a PHP fatal error when saving information for legacy campaigns.
* FIX: Resolved error on getting started screen after Stripe was connected.
* FIX: Improved retrieval of campaign descriptions for legacy campaigns.
* FIX: Resolved CSS issue in visual builder.

# 1.8.1.13
* UPDATE: Updated copy and links on getting started screen.
* FIX: Minified starting screen CSS.
* FIX: Resolved typos and PHP notices.
* FIX: Updated additional text in admin UI to allow for translations.

# 1.8.1.12
* NEW: Getting Started Screen for new users upon install and activation.
* NEW: Tour guide of visual builder for new users.
* NEW: Expiring license and expired license noticiations in WordPress admin Charitable screens.
* NEW: Progress bar in visual buider campaigns show currency in frontend. Filter added for further customization.
* NEW: Show currency symbol in visual builder settings under "Goal".
* NEW: Added filters for showing content before and after donor lists and donor wall using shortcodes.
* UPDATE: UI updates and tweaks related to visual builder and admin screens.
* UPDATE: UI updates to items in marketing and payment tabs in visual builder.
* FIX: Issue with Easy Digital Downloads addon card in addons screen resolved.
* FIX: Better sync for legacy and visual campaigns in certain scenarios.
* FIX: Resolved typos and PHP warnings and notices.

# 1.8.1.11
* FIX: Resolve issue when incorrectly connecting to Stripe in live mode on new installs in certain scenarios.

# 1.8.1.10
* NEW: UI updates and tweaks to notices and tooltips.
* NEW: Deactivate legacy campaigns now an option in advanced settings.
* NEW: Added and updated several items in the Growth Tools page.
* FIX: Addressed security for ajax functions related to reporting.
* FIX: Resolved typos and PHP warnings and notices.

# 1.8.1.9
* FIX: Added nonce and permissions check for depreciated function to show Stripe keys.
* FIX: Fixed typo in a permissions check for viewing a campaign builder preview.

# 1.8.1.8
* NEW: Add three WordPress hooks to allow developers to add custom content in the gateway fieldset in the donation form.
* NEW: Add 'charitable_stripe_payment_intent_data' filter for the "payment_intent_data" attribute sent to Stripe (example: use to remove "setup_future_usage" value for payment integrations).
* NEW: New menu item "SMTP" with ability to download and install WP Mail SMTP.
* NEW: Added hooks 'charitable_gateway_fields_front', 'charitable_gateway_fields_after_legend', and 'charitable_gateway_fields_end' to insert text into gateway fields area in donation form.
* NEW: Updated charitable.pot.
* FIX: Resolved a security issue when checking to see if a campaign can recieve donation.
* FIX: Resolved issue related to exporting recurring donations.
* FIX: Added nonce and permissions check to checking licenses via AJAX.
* FIX: Resolved typos and PHP warnings and notices.

# 1.8.1.7
* FIX: Resolve issue when incorrectly showing ("false alarm") dashboard security notice.
* FIX: Dashboard Guide Tools notice no longer displays over populated donation charts when changing days.

# 1.8.1.6
* NEW: Tools page menu with Export and Import menus (previously located on the Settings page).
* NEW: "System Info" tab on Tools page.
* NEW: WPCode Intergration! Browse from all Charitable snippets on WPCode without leaving Charitable (Seen on Tools page and Dashboard page).
* NEW: Growth Tools page.
* NEW: Guide Tools recommendations on Charitable Dashboard.
* NEW: Notice on Charitable Dashboard if Charitable detects recent failed donation attempts due to invalid security checks.
* NEW: Improvements to allow third-party gateway providors a dedicated menu in the Payments tab in the campaign visual builder.
* FIX: Improved display of currency on y axis on headline chart on dashboard and overview pages and print output.
* FIX: Resolved issue where certain settings of the donor wall in the campaign visual builder weren't being reflected on the campaign page.
* FIX: Tweaks to social sharing in campaign visual builder.
* FIX: Resolve issue involving previewing Charitable emails while using newer Block themes.
* FIX: Resolved typos and some PHP warnings and notices.

# 1.8.1.5
* NEW: Addon and plugin suggestions for campaign and donation screens when campaign or donations don't exist yet.
* NEW: Dismissable pointer boxes added.
* NEW: Updated text and promotional content.
* NEW: Updated addon suggestions on Charitable dashboard.
* FIX: "Date Created" populated in campaign CSV export.
* FIX: Update capbility of visual campaign builder so that roles with "manage_pages" (such as Campaign Managers) would have access.
* FIX: Minor code cleanup and tweaks for PHP notices.

# 1.8.1.4
* NEW: Improved marketing and payment tabs in visual builder that link to Newsletter Addon settings (if installed) or enable gateways/view settings for activated payment addons.
* NEW: Email address and time now appearing on donation list page in admin.
* NEW: Campaign and donation exports show fields foramtted for currency for donations, goal amounts, etc.
* NEW: Ability for Charitable addons to add additional items on the advanced tab in "Settings".
* NEW: Addition of 'charitable_report_overview_args' filter that allows adjustment of default values for the Overview report.
* FIX: Fixed a minor JavaScript issue on addon directory page that enables "link to search/filter" to work via "search" keyword in the URL.
* FIX: Issue saving legacy license codes in certain scenarios.
* FIX: Minor code cleanup and tweaks for PHP notices.

# 1.8.1.3
* NEW: Pagination for all donor reports, with a filter allowing customization of items per page based on the report.
* FIX: Lybunt reporting now sorts by total lifetime amount and includes non-default avatars if possible.
* FIX: Donor reports should be more reliable showing non-default avatars and "last donation" amounts where applicable.
* FIX: Top donor reports showing more rows (if available) in non-pagination display.
* FIX: Adjustment to cache logic for visual campaign builder when loading pre-built templates when creating campaigns.
* FIX: Improved communication with Charitable server and caching of failed API attempts for licenses.
* FIX: Added additional checks for activities database tables.
* FIX: Resolved a few PHP warnings and notices.

# 1.8.1.2
* FIX: Resolved a PHP fatal error for older PHP versions when checking license information for addons.

# 1.8.1.1
* FIX: Resolved a PHP 8.x fatal error in getting sample donors for Lite users on donor report page.
* FIX: Better results for activities on activity page when "donations (paid)" filter is selected.
* FIX: Improved communication with Charitable server and caching of API data when confirming addon and license information.
* FIX: Adjusted CSS for a call to action button for lite users on campaign page.
* FIX: Resolved an issue where sample activity data was appearing for lite users in certain scenarios.
* FIX: Minor cleanup of activity HTML on overview reporting page when no activities are available to display.

# 1.8.1
* NEW: New dashboard showing new data and reports related to campaigns, donations, and donors over last 7, 14, and 30 days. [More information](https://www.wpcharitable.com/dashboard-reporting).
* NEW: Introducing a new reporting tab that (among other reports) includes an overview showing donation breakdown, report filtering, payment breakdown, and more. [More information](https://www.wpcharitable.com/dashboard-reporting).
* NEW: Clear activity database option in Charitable settings->advanced tab.
* NEW: Addition of 'charitable_is_main_loop' filter which is involved in checking whether Charitable is currently in the main loop on a singular page.
* NEW: Updated CSS of frontend campaign visual templates, including adding word wrap CSS to campaign title headlines.
* FIX: Resolved fatal error in certain scenarios when 'donation form on page' was in set in general settings and a shortcode was used to add a single campaign.
* FIX: In Stripe gateway settings: card descriptor field for Stripe (with Stripe Connect) appears in both CHARITABLE_DEBUG mode true and false.
* FIX: Resolved issues related to embedding campaigns in the visual builder.
* FIX: Added checks for get_current_screen() to resolve a fatal error in some scenarios on an admin screen.
* FIX: Auto setting for donation receipt pages should work better for block themes, including Twenty Twenty-Three and Twenty Twenty-Four.
* FIX: Adjusting allowed HTML in the text block for campaign visual builder.

# 1.8.0.6
* FIX: Resolved an issue with an incorrect default campaign creator being assigned in the campaign builder.

# 1.8.0.5
* FIX: Resolved an issue with an activated Charitable license on a multisite blog.
* FIX: Resolve minor CSS hover issues on campaign list page.
* FIX: When a campaign ends and is past the completion date, a message appears on campaigns instead of a disabled donation button.
* FIX: Improved function to check and see if a Charitable block exists in the editor, resolving conflicts with some third party plugins in some scenarios.
* FIX: Minor bug fixes and code cleanup.

# 1.8.0.4
* NEW: Added additional tooltips in various settings in the Campaign Builder.
* NEW: Revise "hot keys" on campaign builder, adding new hot keys combos for tab screens, preview, and view live.
* FIX: Tweak campaign list page CSS for small width screens.
* FIX: Resolved an issue where one couldn't view the changelog of next updates on WordPress plugin screen.
* FIX: Improved experience when activating or installing/activating (with active license) addons from the Campaign Builder setting page.
* FIX: Improved UI with rate reminder banner, now renders below new Charitable header.
* FIX: Minor bug fixes and code cleanup.

# 1.8.0.3
* FIX: Resolved an issue where filtering donations was relating in an error.
* FIX: Visual tweaks to admin UI buttons in donation and campaign page, and text in certain modal windows.
* FIX: Updates to Campaign Block (logo and minor cleanup).
* FIX: Updates to campaign and donation exports to ensure exports with more accurate data.
* FIX: Resolved some PHP Warnings in adition to some code cleanup.

# 1.8.0.2
* FIX: Resolve fatal error when the User Avatar addon was active.

# 1.8.0.1
* FIX: Resolved PHP error related to PHP 7.2.
* FIX: Adjusted Organizer field in Campaign Builder to be more responsive.

# 1.8.0
* NEW: Visual Campaign Builder! [More information](https://www.wpcharitable.com/campaign-visual-builder).
* NEW: Overall UI updates to Charitable settings pages.
* NEW: Enabled a new block ("Campaign Block") in the WordPress editor.
* NEW: Addition of 'charitable_get_terms_and_conditions_content' filter.
* NEW: Added "Add Legacy" button to campaign page.
* FIX: Resolved an issue where snippets programmingly creating new campaigns even by passing '0' couldn't disable custom donations.
* FIX: Resolved PHP Warnings and Notices related to PHP 7.2, PHP 8.1 and PHP 8.2.

# 1.7.0.14
* FIX: Resolved a security issue when using the charitable_donors shortcode.

# 1.7.0.13
* FIX: Resolved issues related to users registering new accounts with administrator roles in some scenarios.

# 1.7.0.12
* FIX: Additional validation has been added when installing addons.

# 1.7.0.11
* FIX: Resolved a potential vulnerability in filter and export urls on campaign and donation WordPress admin list pages.

# 1.7.0.10
* FIX: Resolved bug that prevented seeing update changelog for new verisons of the core plugin in plugins page while Charitable was activated.
* FIX: Resolved fatal error in some scenarios with the donation history beta on donation edit pages.
* FIX: Added new filter (true/false) that can enable/disable ('charitable_donations_donor_history_show') showing donate history on donation edit pages.
* FIX: Removed test code and minor code cleanup.

# 1.7.0.9
* NEW: Add JS hook in Charitable Stripe to allow future functionality with Spam Blocker plugin
* NEW: Add filter to determine if submit button should be shown on donation form (charitable_show_donation_form_button)
* NEW (BETA): Campaign Summary Settings - change text of donate button on campaign page, hide the four values
* FIX: Setting drop box widths adjusted
* FIX: Tweak import/export tools for when there are no campaigns or donations to export
* FIX: Tweak calculations for the donor history beta in certain scenarios
* FIX: Reset the suggestion donations form to default setting when browser goes back in browser history, in some scenarios
* FIX: Add javascript test for wpColorPicker on Charitable admin pages
* FIX: Suggested donation bug fixes
* FIX: Improvements and bug fixes relating to built-in Charitable profile avatars
* FIX: Fixes a bug where Charitable was interrupting license confirmation via Ajax with several WordPress plugins.
* FIX: Correct date/month format in alert message when you input an incorrect data format for campaign/donation filters and exports in the admin.
* FIX: Fix campaign and donation export issue where it was reporting “incorrect date format” when the format was actually correctly entered (timezone issue).
* FIX: Stray error_log() functions now wrapped in CHARITABLE_DEBUG
* FIX: Fix bug that was auto-forcing minimal donation limit upon donation form submit to $1 when using Stripe in certain scenarios.
* FIX: Instead of assuming WordPress admin has date format set properly on WordPress settings page, Charitable uses it's own default to display donation dates.

# 1.7.0.8
* NEW: Updated date formats for campaign filters and campaign export forms to yyyy/mm/dd.
* NEW: CSS updates to labels on the donation overview screen.
* NEW: Replaced text field for “highighlight color” in General Settings with a color picker.
* NEW: Updated Advanced Settings tab and added third party warnings to things "clear cache" can wipe.
* NEW (BETA): Third party warnings are one-time dismissible messages alerting to potential issues with installed plugins based on support feedback, such as code snippet plugins.
* NEW (BETA): Added “donor history” metabox to the donation overview screen for admins to see other donations from same potential donor.
* FIX: Improved reliability of the default suggested donation feature in introduced in 1.7.0.4.
* FIX: Resolved a potential vulnerability in filter and export fields on campaign and donation WordPress admin list pages.

# 1.7.0.7
* NEW (BETA): Import and export donations and campaigns in new "tools" tab in Charitable settings.
* NEW (BETA): Minimum donation location, notice display location, and default highlight color settings in general tab.
* NEW: New "Clear Cache" checkbox in "Advanced" Tab - includes ability to remove "stuck upgrade message".
* FIX: Minor font and spacing adjustments to settings CSS.
* FIX: Resolved some PHP depreciation warnings that would appear on PHP 8.2.

# 1.7.0.6
* FIX: Resolve issue when a license is asked for when updating the core plugin.

# 1.7.0.5
* FIX: Resolve fatal error when the User Avatar addon was active.
* FIX: Removed typo that caused fatal when displaying a particular license upgrade message.

# 1.7.0.4
* NEW: You can now set a minimum donations for campaign in campaign settings.
* NEW: Ability to set a default suggested donation amount for campaigns.
* NEW: General Tab adds several new settings related to donation form.
* NEW: Added filter 'charitable_stripe_payment_method_types' to allow developers to hook in and add additional elements to the payment_method_types parameter for Stripe.
* NEW: Addon submenu allowing you to browse, install, active, or upgrade to available Charitable addons.
* NEW: License system redone - "legacy licenses" moved to Advanced Tab, License Tab removed, single-license codes now able to be added in "General" Tab
* FIX: Several PHP warnings were resolved.

# 1.7.0.3
* NEW: Ability to add minmum donation amounts per campaign, under "Donation Options" in campaign settings.
* NEW: Notifications related to Charitable news and offerings within dashboard, donation, and campaign pages.
* FIX: Prevent loading Stripe javascript if Stripe isn't an active gateway.
* FIX: Prevent fatal error in some use cases related to Stripe gateway.

# 1.7.0.2
* NEW: Add charitable_email_shortcode_get_value filter.
* NEW: Automatically add min 1.00 amount for donations if site is using Stripe gateway.
* FIX: Resolve issue involving the recurring support checkbox with Stripe Connect on front-end dontain forms.
* FIX: Resolve issue relating to licenses and WordPress multisite.
* FIX: Avoid triggering a fatal error in certain cases upon checkout when using Stripe Checkout.
* FIX: Add misc coding checks to prevent PHP warnings in certain install setups.

# 1.7.0.1
* FIX: Issue regarding deactivation of Stripe gateway in gateway settings resolved, which also resolves JavaScript error on donation page in certain scenarios.
* FIX: Checking recurring donations on donation page should not remove Stripe as an option.
* FIX: Avoid triggering a fatal error relating to Stripe Connect when submitting a donation.

# 1.7.0
* NEW: Integrated Stripe Connect for easy one-click connection to Stripe.
* NEW: Improved notices and menu updates to ensure donation forms are configured properly.
* NEW: Ability to add license for "Charitable Pro" in addition to the other addons (license tab in settings).
* NEW: New installs default to United States as country/currency.
* FIX: Charitable settings no longer have to be saved twice on first install.

# 1.6.61
* FIXED: Now displays proper "enter amount" prompt for donation in certain scenarios.

# 1.6.60
* NEW: Added the ability to set a maximum donation amount using the `charitable_maximum_donation_amount` filter.
* NEW: Added the ability to change the minimum & maximum donation amount on a per-campaign basis, using the `charitable_minimum_donation_amount` and `charitable_maximum_donation_amount` filters. These filters are used by the new Campaign Kit plugin which will allow you to easily set the minimum & maximum donation amounts for individual campaigns.
* NEW: Added the `charitable_email_subject` filter, allowing the subject line used for emails to be filtered. This filter is used by the new Campaign Kit plugin which will allow you to customize donation receipt emails on a per-campaign basis.

# 1.6.59
* NEW: Added the ability to send emails with attachments. This allows you to attach PDF receipts to your donation receipt emails automatically with the [PDF Receipts](https://www.wpcharitable.com/extensions/charitable-pdf-receipts/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=plugin-page-referrals).
* FIX: In some cases, the dark overlay that should appear when showing donation form in a modal would not appear. This has been fixed now.

# 1.6.58
* FIX: Remove testing code left behind.

# 1.6.57
* NEW: Added a printable page template, including custom styles for printing. This will be used by two plugins to be released in the coming weeks: PDF Receipts & Annual Receipts.
* NEW: Under the hood, the way SQL queries are set up for getting donation data has been improved to provide more flexibility. This lays the groundwork for improvements to the `[charitable_stat]` shortcode.
* NEW: The Offline Payment Instructions can now include shortcodes. This allows the instructions to be translated for different languages if you're using a plugin like Polylang. [#909](https://github.com/Charitable/Charitable/issues/909)
* FIX: Avoid PHP notices when displaying a donation form on a separate page.
* FIX: In the donation receipt, make sure the total donation amount shown in the summary includes any fees added through Fee Relief.
* FIX: The layout of the donation summary in the receipt has been cleaned up, avoiding display issues in some themes.
* FIX: Disable autocomplete for datepicker fields.
* FIX: Fixes a problem with the modal script not always loading as expected in block themes.
* FIX: Use a unique HTML ID for donation forms when there are multiple on the same page.
* FIX: Avoid a fatal error in the Donation Stats widget when using Polylang.
* FIX: Avoid triggering an error in the email verification template in certain circumstances.

# 1.6.56
* FIX: Fixes an issue in Charitable 1.6.55 that would prevent custom donation receipt content from showing.

# 1.6.55
* NEW: Added `charitable_template_donation_form()` function to easily display a particular campaign's donation form.
* NEW: Added new `charitable_currency` filter, as well as a `charitable_get_default_currency()` function, allowing the site currency to be filtered.
* NEW: Donations now store the currency used for the donation. The currency of a donation is available through the Donation Fields API. i.e. `charitable_get_donation( 123 )->get( 'currency' )`.
* FIX: Improves compatibility with block themes like Twenty Twenty Two by handling the way templates are loaded in those themes. In particular this caused issues on dynamically generated pages such as the Donation Receipt or Password Reset / Forgot pages.
* FIX: Resolves a bug where editing a donation in the admin could change its change date to January 1, 1970. This happened in situations where the person editing the donation had their profile language set to something other than English.
* FIX: In the donation receipt, display the total donation amount including any fees added through Fee Relief.

# 1.6.54
* NEW: Added methods for retrieving the subtotal (excluding Fee Relief fees) and total (including fees) donated for a donation, with `$donation->get( 'subtotal' )` and `$donation->get( 'total' )` respectively. This is used by the new Automation Connect integration.
* FIX: When creating a manual donation, the list of campaigns is now in alphabetical order to make it easier to find the campaign you're looking for. [#894](https://github.com/Charitable/Charitable/issues/894)
* FIX: Resolved an error when using the Campaign Categories / Terms widget.

# 1.6.53
* FIX: Resolves a number of issues that caused warnings or notices when using Charitable with PHP 8 or MySQL 8.
* FIX: When calling `$campaign->get( 'allow_custom_donations' )`, the return value will now always be an integer.
* FIX: A bug in the data processor class was preventing campaign terms from being saved correctly when submitting campaigns with Charitable Ambassadors.

# 1.6.52
* FIX: When adding manual donations, the donation date was incorrectly set to December 31, 1999.
* FIX: Use `wp_kses_post` when returning `false` to the `charitable_sanitize_suggested_amount_description` filtered adding in Charitable 1.6.51. This will allow HTML to be used in suggested donation descriptions, while still stripping out any dangerous code, such as Javascript. By default, `charitable_sanitize_suggested_amount_description` returns `true` and will be filtered to exclude all HTML.

# 1.6.51
* SECURITY FIX: Fixes a critical cross-site scripting vulnerability. See [our blog](https://www.wpcharitable.com/blog/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=release-notes&utm_content=1-6-51-release) for a post in the coming days.
* SECURITY FIX: Removes the `unfiltered_html` capability from Campaign Managers.
* FIX: Allow campaign category/tag taxonomies to be translated in the Campaign Categories / Tags widget.

# 1.6.50
* FIX: Resolves problem where donation amounts over $1000 were not sanitized correctly, resulting in the donation being processed with the incorrect donation amount. This was a bug that crept in with the changes in 1.6.49.

# 1.6.49
* FIX: Fixed problem where the page scrolls to the wrong spot when a donation form is submitted with errors. [#882](https://github.com/Charitable/Charitable/issues/882)
* FIX: Added support for namespaced class names in the `Charitable_Registry`. [#889](https://github.com/Charitable/Charitable/issues/889)
* FIX: Proper handling of minimum donation amounts where commas are used as the decimal separator and the minimum donation amount has cents (i.e. two euros and fifty cents). Please note that the minimum donation amount should be set with a period used as the decimal separator. [#888](https://github.com/Charitable/Charitable/issues/888)
* FIX: Proper handling of donation amount added via the Campaign Donation widget, where commas are used as the decimal separator. [#887](https://github.com/Charitable/Charitable/issues/887)

# 1.6.48
* FIX: Better compatibility with TranslatePress to allow certain strings that were otherwise not translateable to be translated.
* FIX: Removed a PHP notice that would appear on 404 pages. [#864](https://github.com/Charitable/Charitable/issues/864)

# 1.6.47
* SECURITY FIX: Prevent email verification emails from being triggered without the input of the registered user. [#863](https://github.com/Charitable/Charitable/issues/863)
* FIX: Updated Weglot support to handle updates in Weglot 3.3.0. [#861](https://github.com/Charitable/Charitable/issues/861)

# 1.6.46
* FIX: Prevent endless redirect when using Weglot and displaying donation forms on the same page as the campaign.
* FIX: Improve compatibility with WP Super Cache.

# 1.6.45
* FIX: If you are using Weglot, the donation receipt page and donation-related emails will be in the same language used by the donor when they donated. [#835](https://github.com/Charitable/Charitable/issues/835)
* FIX: Prevent errors from displaying in the donation processing response to avoid donations being blocked.
* FIX: Check hidden fields when checking if a donation is a recurring donation. [#819](https://github.com/Charitable/Charitable/issues/819)
* FIX: In some cases, using the logout shortcode would result in it being displayed multiple times or too early. This has been fixed. [#834](https://github.com/Charitable/Charitable/issues/834)
* FIX: Prevent a fatal error encountered when using the Twenty Twenty theme and Oxygen Builder. [#829](https://github.com/Charitable/Charitable/issues/829)

# 1.6.44
* NEW: When using WPML, the total funds raised by a campaign will now include both the campaign itself and any translations of the same campaign. [#811](https://github.com/Charitable/Charitable/issues/811)
* FIX: Ensure that the selected recurring donation amount is picked up when you reach a donation form via the Donate widget. [#804](https://github.com/Charitable/Charitable/issues/804)
* FIX: Improved display of checkboxes in TwentyTwenty theme. [#812](https://github.com/Charitable/Charitable/issues/812)
* FIX: When using WPML, clicking Donate button on the non-primary language version of a campaign results leads back to the primary language version of the campaign, instead of the donation form. [#810](https://github.com/Charitable/Charitable/issues/810)
* FIX: Prevent users with only subscriber access from reaching the WordPress dashboard. [#807](https://github.com/Charitable/Charitable/issues/807)
* FIX: Allow users with the 'translator' role in WPML to access the WordPress dashboard. [#801](https://github.com/Charitable/Charitable/issues/801)
* FIX: Use `jquery` as the dependency for Charitable scripts, instead of `jquery-core`. [#817](https://github.com/Charitable/Charitable/issues/817)
* FIX: Split some parts of the charitable.js file into small, single-purpose Javascript files to be loaded when needed. [#815](https://github.com/Charitable/Charitable/issues/815)
* FIX: Avoided clash of `charitable_user_address_fields` filter name. This was used in three separate places in different ways. The one most commonly used was in the context of loading fields to show in the Profile Form, and this is where the filter is still used unchanged. In `Charitable_User::get_address()`, the filter name has changed to `charitable_user_address_details`. [#816](https://github.com/Charitable/Charitable/issues/816)
* DEPRECATED: `Charitable_User::get_address_fields()` function is deprecated and will be removed. It was previously unused anywhere by Charitable or its extensions. [#816](https://github.com/Charitable/Charitable/issues/816)

# 1.6.43
* NEW: When using Polylang, the total funds raised by a campaign will now include both the campaign itself and any translations of the same campaign. [#798](https://github.com/Charitable/Charitable/issues/798)
* NEW: Made the picture form field a little more flexible, allowing the remove button to be always shown and to have its text customized. This change was required for updates in Ambassadors 2.0.9. [#796](https://github.com/Charitable/Charitable/issues/796)
* FIX: Changed the campaign status tags, allowing them to be translated. [#797](https://github.com/Charitable/Charitable/issues/797)
* FIX: When using TranslatePress, Charitable will record the locale used by the donor when making their donation and will use that locale when sending them donation-specific emails, like their donation receipt. [#795](https://github.com/Charitable/Charitable/issues/795)

# 1.6.42
* NEW: Built-in support for WP Debugging plugin, which will automatically added `CHARITABLE_DEBUG` constant to your wp-config.php file if you enable debugging. [#793](https://github.com/Charitable/Charitable/issues/793)
* FIX: When using Polylang, Charitable will now pick up the current language version of Charitable pages including the profile, login, registration, donation receipt, privacy policy and terms and conditions pages. [#790](https://github.com/Charitable/Charitable/issues/790)
* FIX: On sites using the User Dashboard menu, Charitable now does a better job of picking up whether you're on a dashboard page. This also includes tweaks to ensure that Polylang language versions of the dashboard menu are picked up automatically.
* FIX: Prevent Charitable from opening the donation form in a new window. [#789](https://github.com/Charitable/Charitable/issues/789)
* FIX: Provide the ability to prevent scrolling to the top of the donation form when there is an error, to allow for situations where inline error messaging can provide more clarity. [#791](https://github.com/Charitable/Charitable/issues/791)

# 1.6.41
* NEW: Add the site currency, country code and test mode setting to the Javascript variables to allow scripts to take that into account. This was specifically required by the new Braintree extension (coming soon!). [#784](https://github.com/Charitable/Charitable/issues/784)
* FIX: Improved styling for the campaign meta boxes and admin donation form fields.
* FIX: In some cases, specifically with the Ambassadors front-end campaign form, campaign categories and tags were removed when updating a campaign due to an issue with the way `Charitable_Campaign_Processor` class. [#785](https://github.com/Charitable/Charitable/issues/785)
* FIX: Prevent WordPress from treating any Charitable endpoints as a 404 page. This is a general fix but specifically resolves an issue encountered with Polylang when trying to verify your email address or change your password. [#776](https://github.com/Charitable/Charitable/issues/776)
* FIX: Block redirects caused by the Permalink Manager plugin when the "canonical redirect" setting is enabled. [#765](https://github.com/Charitable/Charitable/issues/765)
* FIX: Ensure that Polylang translations for donation form fields and campaign fields are picked up correctly when the language is determined from the content. [#776](https://github.com/Charitable/Charitable/issues/776)

# 1.6.40
* FIX: Use the correct positioning of the currency symbol when formatting amounts with the symbol via Javascript (only affects Fee Relief). [#777](https://github.com/Charitable/Charitable/issues/777)
* FIX: Get the month names for the current locale when showing a datepicker field in the front-end. Currently this only affects the front-end campaign in Charitable Ambassadors. [#781](https://github.com/Charitable/Charitable/issues/781)

# 1.6.39
* SECURITY FIX: Prevent disclosure of campaign and donation information in the WordPress dashboard to users who should not have access to it. [#775](https://github.com/Charitable/Charitable/issues/775)
* NEW: Add CSS classes via `post_class` filter to target campaigns that have/have not reached their fundraising goal, or which have/have not ended. [#769](https://github.com/Charitable/Charitable/issues/769)
* FIX: Donation Receipt & Donation Notification didn't send when marking a donation as paid via the Donation Actions meta box or by editing the donation. [#771](https://github.com/Charitable/Charitable/issues/771)
* FIX: Add default background colour of white to the custom donation amount field to avoid issues where themes do not provide a colour for the input field. [#766](https://github.com/Charitable/Charitable/issues/766)
* FIX: Ensure that all campaign field values are correctly populated when viewing/editing a Draft campaign in the WordPress dashboard. [#763](https://github.com/Charitable/Charitable/issues/763)

# 1.6.38
* NEW: Added additional currencies supported by our new [Windcave payment gateway integration](https://www.wpcharitable.com/extensions/charitable-windcave/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=windcave). [#760](https://github.com/Charitable/Charitable/issues/760)
* FIX: If you are using a zero-decimal currency such as the Japanese Yen, the decimal count setting will now automatically be set to 0. [#761](https://github.com/Charitable/Charitable/issues/761)
* FIX: When using Recurring Donations, the donation amount in the donor's session would not be used correctly in the donation form if the Variable recurring donation method is used. [#762](https://github.com/Charitable/Charitable/issues/762)

# 1.6.37
* FIX: Added a way to prioritize when endpoints should be loaded, which prevents issues with certain endpoints that overlap. [#754](https://github.com/Charitable/Charitable/issues/754)
* FIX: Apply Divi button class to Charitable buttons when using Divi child theme. [#757](https://github.com/Charitable/Charitable/issues/757)
* FIX: Avoid unexpected redirect when verifying email or resetting password when also using WooCommerce. [#755](https://github.com/Charitable/Charitable/issues/755)
* FIX: Remove notice about having sent email verification email after an email address is verified. [#756](https://github.com/Charitable/Charitable/issues/756)
* FIX: Clear notices after they are displayed at the top of a form to avoid showing the same notices repeatedly. [#758](https://github.com/Charitable/Charitable/issues/758)

# 1.6.36
* NEW: Added date and status filter options to the Campaigns page in the WordPress dashboard, allowing you to find campaigns based on when they were created, when they ended, or according to their current status. [#753](https://github.com/Charitable/Charitable/issues/753)
* NEW: Added ability to define arbitrary attributes to apply to the fields in admin meta boxes and in the admin donation form. [#752](https://github.com/Charitable/Charitable/issues/752)
* FIX: The Campaigns Export tool opened, but nothing happened when you tried to export the report. This has now been fixed. [#748](https://github.com/Charitable/Charitable/issues/748)
* FIX: Made sure that comments sections do not appear on most Charitable endpoints, other than the campaign page. [#751](https://github.com/Charitable/Charitable/issues/751)

# 1.6.35
* FIX: Fixed a problem prevening datepicker fields from working as expected in certain languages. Closes #747.
* FIX: Fixed an error in the way upgrades are run preventing them from completing.
* FIX: Removed timestamp from the donation log.

# 1.6.34
* NEW: Cached `Charitable_Donation` objects are updated when a donation's status is changed.
* FIX: Ensured that the client-side HTML form validation still works as expected across all browsers. This broke in Chrome with version 1.6.33.
* FIX: More robust handling of upgrades, with total & step counter working more reliably.
* FIX: Proper HTML value for the 'required' attribute.

# 1.6.33
* FIX: The fix to the Firefox form submission issue in 1.6.32 caused unexpected issues in other browsers, including Chrome. [#743](https://github.com/Charitable/Charitable/issues/743)

# 1.6.32
* NEW: Added ability to avoid resending an email verification email if one was sent within the last half hour. [#742](https://github.com/Charitable/Charitable/issues/742)
* FIX: A bug was preventing the `charitable_default_donation_amount` filter from correctly setting the default donation amount. [#737](https://github.com/Charitable/Charitable/issues/737)
* FIX: In Firefox, the way Charitable saved the clicked form button's name and value are not correctly passed through as a hidden field. [#740](https://github.com/Charitable/Charitable/issues/740)

# 1.6.31
* FIX: Respect the `hide_if_no_donors` argument in the Donors shortcode. [#734](https://github.com/Charitable/Charitable/issues/734)
* FIX: Ensure the redirect URL provided in the link to the registration page from the login form is encoded properly. [#734](https://github.com/Charitable/Charitable/issues/734)
* FIX: Only show the Customize submenu link under Charitable to user with the `manage_charitable_settings` permission. [#736](https://github.com/Charitable/Charitable/issues/736)
* FIX: Do not add the Profile page as a menu item if a profile page hasn't been set. [#733](https://github.com/Charitable/Charitable/issues/733)
* Removed unused and outdated translations.

# 1.6.30
* NEW: Added `charitable_gateway_object_{gateway_id}` filter.
* FIX: Preserve the name & value of the clicked button when submitting a form. [#723](https://github.com/Charitable/Charitable/issues/723)
* FIX: Improved styling of the end date field in the admin, related to changes in WordPress 5.3.

# 1.6.29
* NEW: Improved stylistic integration with the Twenty Twenty theme and some other popular themes (Twenty Nineteen, Divi, Hello Elementor).
* NEW: Made it easier to add Charitable pages to navigation menus. [#729](https://github.com/Charitable/Charitable/issues/729)
* NEW: Added the `charitable_button_class` filter to easily add/remove classes to Charitable buttons. [#494](https://github.com/Charitable/Charitable/issues/494)
* FIX: The default date in the admin donation form was incorrect in certain languages. [#728](https://github.com/Charitable/Charitable/issues/728)

# 1.6.28
* NEW: Added `select2` script for easier donor selection when adding a manual donation. [#727](https://github.com/Charitable/Charitable/issues/727)
* FIX: Display stored donor details when creating a manual donation for an existing donor. [#706](https://github.com/Charitable/Charitable/issues/706)
* FIX: Prevent form submit buttons from being clicked more than once. This applies to the Registration, Profile and Campaign form. [#723](https://github.com/Charitable/Charitable/issues/723)
* FIX: If your campaign name contains apostrophes or some other characters, donations to the campaign would have the campaign name escaped. [#725](https://github.com/Charitable/Charitable/issues/725)

# 1.6.27
* NEW: Added `charitable_disable_cookie` filter to provide the ability to completely disable the `charitable_session` cookie. The `charitable_session` cookie is a necessary cookie used to keep track of your donation history whilst your session is active. This allows you to access your donation receipt without being a registered, logged-in user. It is also used when you submit the Campaign Donation widget form to track the amount you would like to donate to the campaign, as well as the donation period (one-time, monthly, etc.). [#717](https://github.com/Charitable/Charitable/issues/717)
* NEW: Automatically integrated with [GDPR Cookie Compliance plugin](https://wordpress.org/plugins/gdpr-cookie-compliance/). This ensures that if a user opts to have *all* cookies disabled, including necessary ones, the `charitable_session` cookie is removed as well.
* FIX: In certain cases where Recurring Donations was active on a campaign, adding a custom donation amount would result in a notice about needing to donate more than $0. [#716](https://github.com/Charitable/Charitable/issues/716)
* FIX: In some cases, if you try to access the donation receipt for a donation you don't have access to (or you need to log in for), the login form would appear twice. [#715](https://github.com/Charitable/Charitable/issues/715)
* FIX: Improved how fields registered through the Fields APIs are sorted, allowing you to modify the position of a field by setting its `show_before` or `show_after` value. [#707](https://github.com/Charitable/Charitable/issues/707)


# 1.6.26
* FIX: Resolves a bug with Recurring Donations that resulted in a pre-set custom recurring donation amount not being registered as the active choice properly. [#713](https://github.com/Charitable/Charitable/issues/713)
* FIX: When using Ultimate Member, if your Profile page in Charitable was set to UM's User page, it messed up Charitable's Endpoints API. [#710](https://github.com/Charitable/Charitable/issues/710)
* FIX: Also with Ultimate Member, fixed issue where Charitable's user email verification process fails while UM is active. [#711](https://github.com/Charitable/Charitable/issues/711)
* FIX: Fatal error on PHP 5.2. [#712](https://github.com/Charitable/Charitable/issues/712)

# 1.6.25
* NEW: Automatically set the selected amount in the donation form by appending query parameters to the donation form URL. For example, going to `https://yoursite.com/campaigns/my-campaign/?amount=10` will load the donation form with a $10 donation preset. [#684](https://github.com/Charitable/Charitable/issues/684)
* NEW: Mark a user's email address as verified when they complete Ultimate Member's email activation process. [#709](https://github.com/Charitable/Charitable/issues/709)
* NEW: Registered `image` as a Campaign Field. `$campaign->get( 'image' )` will return the ID of the campaign's featured image.
* FIX: Add `singular.php` as a fallback template to use for Charitable endpoints.
* FIX: Ensure that `wp_title` has a page name set for Charitable endpoints. [#660](https://github.com/Charitable/Charitable/issues/660)
* FIX: Prevent adding duplicate pending processes to the donation form Javascript handler. [#697](https://github.com/Charitable/Charitable/issues/697)
* FIX: Ensure that shortcodes in the Terms & Conditions text are parsed when the donation form is loaded via AJAX. [#708](https://github.com/Charitable/Charitable/issues/708)
* FIX: Fixed error that prevented Charitable settings using a select element from correctly showing the current selected value if the value is 0. [#639](https://github.com/Charitable/Charitable/issues/639)
* UPDATE: Replaced `ambassadors_form` with `campaign_form` in the Campaign Fields API. `ambassadors_form` has thus far been unused; `campaign_form` will be supported in the next release of Charitable Ambassadors.

# 1.6.24
* UPDATE: Officially adopted Unicode CLDR recommendations for country names. Several country names have been updated. [#704](https://github.com/Charitable/Charitable/issues/704) and [#700](https://github.com/Charitable/Charitable/issues/700)
* FIX: Show which fields are required in the admin donation form. [#702](https://github.com/Charitable/Charitable/issues/702)
* FIX: When a manual donation submission fails because of some missing required fields, show the error notice. [#703](https://github.com/Charitable/Charitable/issues/703)
* FIX: In some cases, the End Date, Goal and Description fields would be removed from the campaign editor in the admin. [#690](https://github.com/Charitable/Charitable/issues/690)

# 1.6.23
* NEW: Added option to specify `year_range` parameter for datepicker fields. By default, the datepicker will show the previous 100 years. [#696](https://github.com/Charitable/Charitable/issues/696)
* NEW: Added `charitable_user_verified` action hook to do something after a user verifies their email address.
* NEW: Added `charitable_profile_endpoint_descendent_query_vars` filter, which is used internally when registering endpoints through Charitable's Endpoints API to handle cases where endpoints use the Profile endpoint as their base.
* FIX: In some cases, the js.cookie script was not loaded correctly due to the presence of Javascript module loaders, which resulted in Charitable's session script not working correctly. [#699](https://github.com/Charitable/Charitable/issues/699)

# 1.6.22
* NEW: Added gateway transaction ID to donation meta and as an optional email field. [#694](https://github.com/Charitable/Charitable/issues/694)
* NEW: Added client-side helper function to check whether the donation being processed is a recurring donation.
* FIX: Improved compatibility with Twenty Seventeen theme to avoid a Javascript error on the donation pages. [#693](https://github.com/Charitable/Charitable/issues/693)
* FIX: Corrected spelling of default text in Offline Donation Receipt.
* FIX: Fixed issue preventing conditionally loaded admin settings/form fields from working correctly when there is more than one on the page.

# 1.6.21
* FIX: Prevent Polylang from modifying the rewrite rule for the webhook listener endpoint. With certain configurations, Polylang prevented webhooks/IPNs from working. [#692](https://github.com/Charitable/Charitable/issues/692)

# 1.6.20
* FIX: Corrected error introduced by 1.6.19 resulting in donations not processing in certain situations when using Authorize.Net or Stripe.

# 1.6.19
* NEW: Registered campaigns fields for getting a campaign's tags and categories as commma-separated lists. These are now available in campaign-related emails as well as the Campaigns export. [#688](https://github.com/Charitable/Charitable/issues/688)
* NEW: Include Charitable version in Javascript vars. This is available as `CHARITABLE_VARS.version`. We also added a way to check that Charitable is at least a certain version, using `CHARITABLE.VersionCompare( version )` (where `version` is the version it must be).
* NEW: Added extra jQuery events when processing/viewing a donation form: `charitable:form:processed`, triggered right after a donation is created but before being redirected to the donation receipt or payment page; `charitable:form:amount:changed`, triggered when the donor changes the amount they are donating; `charitable:form:total:changed`, triggered when the donor changes the total amount they are donating (i.e. they choose a different payment amount or opt in to pay the processing fees).
* NEW: Added `charitable_my_donation_total_amount` filter to allow the donation amount shown in the output of `[charitable_my_donations]` to include any processing fees paid by the donor.
* FIX: Improved return links for settings pages nested under other settings page (i.e. individual MailChimp list settings page links back to MailChimp settings).
* FIX: `charitable_get_current_url()` returned incorrect URLs on multisite. [#687](https://github.com/Charitable/Charitable/issues/687)

# 1.6.18
* NEW: Added email tag for donor's last name. [#685](https://github.com/Charitable/Charitable/issues/685)
* NEW: Added an `inline-content` setting type.
* FIX: Added __sleep() methods to Campaign and Donation classes to avoid serializing Donation/Campaign Fields. This avoids errors relating to serialization of closures.
* FIX: Improved error message when max file size is exceeded while trying to upload a picture via the front-end picture field. [#683](https://github.com/Charitable/Charitable/issues/683)
* FIX: After clicking "Change" link in donation form to select a different donation amount, hide the link to avoid confusion. [#682](https://github.com/Charitable/Charitable/issues/682)

# 1.6.17
* FIX: Removing pending processes in Javascript fails in certain cases when there is more than one pending process. [#681](https://github.com/Charitable/Charitable/issues/681)
* FIX: Improved styling of `<legend>` elements inside Charitable meta boxes.
* FIX: Delete user dashboard menu transient if one isn't set. This fixes a bug in Reach. [#52](https://github.com/Charitable/Reach/issues/52)

# 1.6.16
* NEW: Allow `value_callback` in `admin_form` settings for a field registered with the Campaign Fields API to override the main `value_callback` parameter. [#679](https://github.com/Charitable/Charitable/issues/679)
* FIX: Prevent credit card validation in donation widget, which prevented it from working in certain specific scenarios. [#680](https://github.com/Charitable/Charitable/issues/680)

# 1.6.15
* NEW: Added Colombian Peso as currency. [#676](https://github.com/Charitable/Charitable/issues/676)
* FIX: Error resulted in donation receipts not getting sent initially for manually added donations, and then getting sent twice. [#631](https://github.com/Charitable/Charitable/issues/631)
* FIX: Avoid fatal error related to the new Webhook Listener Endpoint when Social Warfare is active. [#678](https://github.com/Charitable/Charitable/issues/678)

# 1.6.14
* FIX: Show terms and conditions, privacy policy and contact consent on the registration form if enabled, regardless of whether the settings have been saved. [#657](https://github.com/Charitable/Charitable/issues/657)
* FIX: If you have multiple extensions installed and you have an unfinished upgrade routine, the notice was shown repeatedly. [#655](https://github.com/Charitable/Charitable/issues/655)
* NEW: Added option to force HTTPS on the campaign donation page. This is off by default but can be enabled by returning `true` to the `charitable_campaign_donation_endpoint_force_https` filter. [#658](https://github.com/Charitable/Charitable/issues/658)
* NEW: Added Webhook Listener endpoint to provide pretty URL for IPNs/webhooks. <strong>Note: The IPN listener is now run on the `parse_query` hook, which is later than previously when it was run on the `init` hook.</strong> [#659](https://github.com/Charitable/Charitable/issues/659)
* NEW: Added option to force HTTPS for the IPN/Webhook listener URL. This is off by default but can be enabled by returning `true` to the `charitable_webhook_listener_endpoint_force_https` filter.
* NEW: Added ability for Charitable to check whether an extension update has minimum requirements (i.e. minimum PHP or Charitable version) and prevent update if those minimum requirements are not met.
* NEW: Added filter to set whether a campaign can be saved with custom donations disabled and no suggested donations. [#669](https://github.com/Charitable/Charitable/issues/669)
* NEW: Added `charitable_is_localhost` function to return whether installation is localhost. This also introduces the `charitable_localhost_ips` filter to filter the set of permitted IP addresses that will result in `charitable_is_localhost` returning `true`.
* NEW: Improved support for Comet Cache and Litespeed Cache. [#673](https://github.com/Charitable/Charitable/issues/673) and [#674](https://github.com/Charitable/Charitable/issues/674)
* FIX: Ensure that $0 donations work if `charitable_permit_0_donation` is returning `true`. [#668](https://github.com/Charitable/Charitable/issues/668)
* FIX: Numerous small errors fixed.

# 1.6.13
* FIX: "Remove" button in Picture field was hidden in the Twenty Nineteen theme. [#654](https://github.com/Charitable/Charitable/issues/654)
* FIX: Undefined variable notice in Picture field template. [#653](https://github.com/Charitable/Charitable/issues/653)
* FIX: Gracefully handle currency symbols included in the goal parameter in the `[charitable_stat]` shortcode. [#652](https://github.com/Charitable/Charitable/issues/652)
* FIX: Fix campaign grid layout in themes where campaigns don't have the `hentry` class. [#650](https://github.com/Charitable/Charitable/issues/650)
* FIX: Ensure campaign grid is responsive in themes where campaigns don't have the `hentry` class. [#651](https://github.com/Charitable/Charitable/issues/651)

# 1.6.12
* FIX: Picture drag & drop field would not show unless pictures were already set. [#648](https://github.com/Charitable/Charitable/issues/648)

# 1.6.11
* FIX: Fixed error that would show the incorrect amount in the admin donation form in sites that use commas for the decimal separator. [#497](https://github.com/Charitable/Charitable/issues/497)

# 1.6.10
* FIX: Load template files in admin area. This resolves a fatal error when using a Charitable shortcode in the Shortcode block, or when using Divi. [#646](https://github.com/Charitable/Charitable/issues/646) and [#605](https://github.com/Charitable/Charitable/issues/605)

# 1.6.9
* FIX: Handle deleted images set as the value of a Picture field. [#644](https://github.com/Charitable/Charitable/issues/644)
* FIX: Fixed logic error in check for whether the donation amount is above the minimum amount. This only affected situations where the minimum donation was $0 and someone tried to donate $0.
* NEW: Added a more flexible way for asynchronous processes to pause donation form processing. [#645](https://github.com/Charitable/Charitable/issues/645)

# 1.6.8
* FIX: Resolved bug related to WordPress core updates that prevented any donations from appearing under Charitable > Donations when on the "All" view. [#641](https://github.com/Charitable/Charitable/issues/641)
* NEW: Added "Subtotal" row to the Donation Overview table in the admin. This is not shown by default, but will be used by extensions like Gift Aid and our new Fee Relief extension. [#643](https://github.com/Charitable/Charitable/issues/643)
* NEW: Added support for querying by donation plan with `Charitable_Donations_Query`. This will be used by Recurring Donations. [#608](https://github.com/Charitable/Charitable/issues/608)
* NEW: Allow `Charitable_Donations_Query` to output a list of IDs. [#633](https://github.com/Charitable/Charitable/issues/633)
* NEW: Added the currency symbol to the Javascript variables used in the donation form. [#635](https://github.com/Charitable/Charitable/issues/635)

# 1.6.7
* FIX: Load scripts properly when adding a custom 'picture' field to the donation form. [#627](https://github.com/Charitable/Charitable/issues/627)
* FIX: Save settings on Extensions settings page when there is only a single checkbox field. This bug affected Donor Comments. [#629](https://github.com/Charitable/Charitable/issues/629)
* FIX: Add full support for radio & multi-checkbox fields with the Donation Fields API. [#577](https://github.com/Charitable/Charitable/issues/577) and [#576](https://github.com/Charitable/Charitable/issues/576)
* FIX: Properly reflect the value for the "Send after Registration" setting in the settings for the User Verification email. [#626](https://github.com/Charitable/Charitable/issues/626)
* FIX: Only define defaults for database fields that require one. This avoids duplicate primary key issues in certain environments (Windows servers). [#623](https://github.com/Charitable/Charitable/issues/623)
* FIX: Add responsive styling for campaign grid when masonry is turned on. [#628](https://github.com/Charitable/Charitable/issues/628)
* FIX: Removed confusing "Payment attempted" log message for new donations. [#624](https://github.com/Charitable/Charitable/issues/624)
* FIX: Added debug logging when CHARITABLE_DEBUG constant is set and true, and when a nonce check fails. [#630](https://github.com/Charitable/Charitable/issues/630)
* FIX: Made sure the `charitable_user_registration_fields` filter actually works. :)

# 1.6.6
* FIX: Prevents an endless loop in the donor id upgrade process added in version 1.6.5. In certain cases, the upgrade could not complete properly.

# 1.6.5
* NEW: Added `meta_query` and `date_query` support to `Charitable_Donations_Query` and `Charitable_Donor_Query`. [#615](https://github.com/Charitable/Charitable/issues/615) and [#614](https://github.com/Charitable/Charitable/issues/614)
* NEW: Added `charitable_get_campaign_creator_field` helper function to retrieve meta information about a campaign creator given a campaign ID and a key. Designed to be used with the Campaign Fields API.
* NEW: Added `charitable_donor_contact_consent_changed` hook to broadcast when a donor has updated their contact consent setting.
* NEW: Added a multi-select field for admin forms. [#609](https://github.com/Charitable/Charitable/issues/609)
* NEW: Added a Customizer control for select fields with optgroups. [#610](https://github.com/Charitable/Charitable/issues/610)
* FIX: Fixed an error that resulted in a database error when adding donations in certain conditions. This only occurred on sites that upgraded to version 1.6.0 - 1.6.4 without running the database upgrade, and could result in donations being prevented or in donations being recorded without a donor record. This version resolves this bug, and also adds an upgrade routine to retroactively add donor records for all donations missing one. [#616](https://github.com/Charitable/Charitable/issues/616)
* FIX: Help text for End Date & Goal fields were swapped. [#606](https://github.com/Charitable/Charitable/issues/606)
* FIX: Browser security warning when doing a donation in the PayPal sandbox. [#604](https://github.com/Charitable/Charitable/issues/604)
* FIX: Prevent time-outs in AJAX requests to process donations.

# 1.6.4
* FIX: Fixed a fatal error triggered during donations when using a previous version of WordPress. [#603](https://github.com/Charitable/Charitable/issues/603)

# 1.6.3
* FIX: Fixed bug that prevented donation form from submitting in certain cases when using Stripe or Authorize.Net alongside another payment gateway. [#601](https://github.com/Charitable/Charitable/issues/601)

# 1.6.2
* NEW: Allow donors to manage their contact consent preference via the Profile form. [#591](https://github.com/Charitable/Charitable/issues/591)
* NEW: Include terms and conditions, privacy policy and contact consent checkbox in Registration form. [#599](https://github.com/Charitable/Charitable/issues/599)
* NEW: Include donor's contact consent status in Donations export. [#590](https://github.com/Charitable/Charitable/issues/590)
* NEW: Show whether contact consent was given in donation details. [#589](https://github.com/Charitable/Charitable/issues/589)
* NEW: Added "User Privacy" and "Terms and Conditions" sections to the Charitable Customizer panel. [#592](https://github.com/Charitable/Charitable/issues/592)
* NEW: Added the `required` attribute to all required form fields. [#595](https://github.com/Charitable/Charitable/issues/595)
* FIX: Prevent donors from changing their profile email to same as existing donor. [#596](https://github.com/Charitable/Charitable/issues/596)
* FIX: Fixed `array_combine` PHP warning in donation form. [#593](https://github.com/Charitable/Charitable/issues/593)
* FIX: Misspelt function name in Donation Field Registry class. [#594](https://github.com/Charitable/Charitable/issues/594)
* FIX: Improved inline documentation for PayPal API fields. [#587](https://github.com/Charitable/Charitable/issues/587) and [#588](https://github.com/Charitable/Charitable/issues/588)

# 1.6.1
* FIX: Cleans up a fatal error in the previous release in certain versions of PHP.

# 1.6.0
* NEW: Added Terms and Conditions section to the donation form with privacy notice, terms and conditions checkbox and marketing consent checkbox. [#558](https://github.com/Charitable/Charitable/issues/558)
* NEW: Added Privacy Policy snippets for policy page builder in WordPress 3.9.6. [#557](https://github.com/Charitable/Charitable/issues/557)
* NEW: Included Charitable donation and donor data in user data erasure, with additional settings to control when data can be erased. [#551](https://github.com/Charitable/Charitable/issues/551) & [#556](https://github.com/Charitable/Charitable/issues/556)
* NEW: Included Charitable donation and donor data in user data export. [#550](https://github.com/Charitable/Charitable/issues/550)
* NEW: Added "Customize" link to the Charitable menu to expose Customizer options. [#559](https://github.com/Charitable/Charitable/issues/559)
* NEW: Added checkbox to the donation form to get donor consent to being contacted. [#420](https://github.com/Charitable/Charitable/issues/420)
* NEW: Added a [charitable_stat] shortcode. [#23](https://github.com/Charitable/Charitable/issues/23)
* NEW: Automatically refund donations in PayPal (more gateway support coming soon). [#269](https://github.com/Charitable/Charitable/issues/269)
* NEW: Added campaign export report. [#529](https://github.com/Charitable/Charitable/issues/529)
* NEW: Added Campaign Fields API. [#530](https://github.com/Charitable/Charitable/issues/530)
* NEW: Added a "meta" section within the "Your Details" donation form section. [#495](https://github.com/Charitable/Charitable/issues/495)
* NEW: Added ability to register new sections in forms through fields APIs. [#541](https://github.com/Charitable/Charitable/issues/541)
* NEW: Added Charitable REST API namespace in preparation for Gutenberg. [#542](https://github.com/Charitable/Charitable/issues/542)
* NEW: Added /reports/ REST API endpoint. [#543](https://github.com/Charitable/Charitable/issues/543)
* NEW: Added informed consent notice to Licenses settings page. [#547](https://github.com/Charitable/Charitable/issues/547)
* NEW: Added the ability to enter different PayPal addresses for live and testing (sandbox). [#517](https://github.com/Charitable/Charitable/issues/517)
* FIX: Improved underlying structure of the Campaign Settings meta box. [#531](https://github.com/Charitable/Charitable/issues/531)
* FIX: Support creating manual donations without an email address. [#535](https://github.com/Charitable/Charitable/issues/535)
* FIX: Added way to re-check license expiry dates in case of license renewal. [#477](https://github.com/Charitable/Charitable/issues/477)
* FIX: Prevent donation forms from displaying publicly for unpublished or inactive campaigns. This includes the Donate widget. [#276](https://github.com/Charitable/Charitable/issues/276).
* FIX: Removed "Change Status" toggle in Donation management page. Status changes are included in the Donation Actions meta box. [#579](https://github.com/Charitable/Charitable/issues/579)
* FIX: Bug that treated dates differently in the Donations Filter and Export. [#546](https://github.com/Charitable/Charitable/issues/546)

# 1.5.14
* SECURITY FIX: Prevent unauthorized users from accessing the user and donation details of previous donations through an exploit. See [our blog](http://www.wpcharitable.com/blog/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=release-notes&utm_content=1-5-14-release) for a post in the coming days.
* FIX: Avoid duplicate donations when a donation fails and is re-attempted by the donor. [#173](https://github.com/Charitable/Charitable/issues/173)

# 1.5.13
* FIX: In some cases, when a custom donation field is registered, it was left blank in donation receipts & other donation emails. This occurred with regular PayPal donations as well as recurring donations through Stripe, and most probably with other payment gateways as well. [#540](https://github.com/Charitable/Charitable/issues/540)
* FIX: A previous update broke the way required checkboxes in donation forms and other forms worked. When a required checkbox is not checked, the form should not submit. This was broken, but is now fixed. [#539](https://github.com/Charitable/Charitable/issues/539)

# 1.5.12
* FIX: In some themes, campaign pages displayed the campaign description and summary block twice. This has been fixed. [#536](https://github.com/Charitable/Charitable/issues/536)
* FIX: Prevent a fatal error related to the Endpoint interface in certain environments. [#534](https://github.com/Charitable/Charitable/issues/534)
* FIX: Tweaked admin styles to avoid select fields getting cropped in some browsers. [#516](https://github.com/Charitable/Charitable/issues/516)
* FIX: Check for type of donation before displaying receipt to avoid clash with Recurring Donations. [#510](https://github.com/Charitable/Charitable/issues/510)

# 1.5.11
* FIX: Preserve pre-existing donor data when editing donations. [#526](https://github.com/Charitable/Charitable/issues/526)
* FIX: Flush donation cache when transferring a donation from one campaign to another campaign. The stats of both the old and new campaigns should be updated automatically. [#527](https://github.com/Charitable/Charitable/issues/527)
* FIX: Improved the way the Javascript is structured to ensure better compatibility with pages containing multiple donation forms. In one specific scenario (using Stripe with Stripe Checkout enabled) this could prevent donation forms from processing on these pages.

# 1.5.10
* FIX: Ensure that campaigns without end dates are saved with the correct value of 0 for the end date meta field. [#524](https://github.com/Charitable/Charitable/issues/524)

# 1.5.9
* NEW: Added `Charitable_Campaign_Processor` class in preparation for new version of Charitable Ambassadors.
* NEW: Added `Charitable_Data_Processor` class in preparation for new version of Charitable Ambassadors. This will also be used eventually by other forms in Charitable core.
* NEW: Allow `Charitable_Deprecated` class to be extended by plugins. [#503](https://github.com/Charitable/Charitable/issues/503)
* NEW: Added support for showing helper text in Charitable's admin forms/meta boxes. [#511](https://github.com/Charitable/Charitable/pull/511)
* FIX: Tweaked Donor role in Charitable, ensuring it does not take away permissions from users who are already registered. [#522](https://github.com/Charitable/Charitable/issues/522)
* FIX: Ensured that custom donation fields with a checkbox are correctly saved. [#500](https://github.com/Charitable/Charitable/issues/500)
* FIX: Improved styling of links within frontend Charitable notices. [#519](https://github.com/Charitable/Charitable/issues/519)
* FIX: Always direct the donor to the main donation form when they submit the Donate widget. [#515](https://github.com/Charitable/Charitable/issues/515)
* FIX: Made sure the Charitable loading gif has a transparent background to avoid display issues. [#518](https://github.com/Charitable/Charitable/issues/518)
* FIX: Fixed a bug that broke the filtering of donations by date in the admin donations table in some non-English languages. [#506](https://github.com/Charitable/Charitable/issues/506)
* FIX: Remove the "Send an email receipt..." checkbox when adding donations manually if the donation receipt email is disabled. [#490](https://github.com/Charitable/Charitable/issues/490)
* FIX: Preserve redirection URL when proceeding to registration page from login page. [#504](https://github.com/Charitable/Charitable/issues/504)
* FIX: Allow `charitable_template_from_session` to receive a different class name, to allow extensions to re-use it. [#509](https://github.com/Charitable/Charitable/issues/509)
* FIX: Removed object caching for admin list of pages; we're using a single-request cache instead. [#505](https://github.com/Charitable/Charitable/issues/505)
* FIX: Added namespace to Charitable icons to avoid clashes with other plugins/themes, including Redux. [#499](https://github.com/Charitable/Charitable/issues/499)
* FIX: Re-order Forgot Password, Reset Password and Registration endpoints. [#502](https://github.com/Charitable/Charitable/issues/502)
* FIX: Avoid fatal error with A2 Optimized fork of W3TC. [#496](https://github.com/Charitable/Charitable/issues/496)
* FIX: Ensured that filters run as expected when checking what the current endpoint is. [#501](https://github.com/Charitable/Charitable/issues/501)

## 1.5.8
* FIX: Updated the data that is sent to PayPal to provide better compatibility with IPNs.

## 1.5.7
* NEW: Added a new optional masonry layout for the `[campaigns]` shortcode. To use it, just add `masonry=1` to the shortcode. [#326](https://github.com/Charitable/Charitable/issues/326)
* NEW: Additional donor meta like their address and phone number are automatically populated when a new manual donation is created for someone who donated previously. [#472](https://github.com/Charitable/Charitable/issues/472)
* NEW: Allowed email verification to be disabled for registrations. With this off, the email verification process can still be initiated by the donor from the My Donations page (i.e. the output of `[charitable_my_donations]`). [#482](https://github.com/Charitable/Charitable/issues/482)
* NEW: Added the username in the output of [charitable_profile]. [#325](https://github.com/Charitable/Charitable/issues/325)
* FIX: Resending the offline donation notification for admins previously sent the regular donation notification instead of the offline one. Bug squashed. [#481](https://github.com/Charitable/Charitable/issues/481)
* FIX: When custom donations are turned off, donors could proceed through the donation form without selecting a suggested amount. This is fixed now. [#478](https://github.com/Charitable/Charitable/issues/478)
* FIX: Prevented errors when overriding template functions. [#488](https://github.com/Charitable/Charitable/issues/488)
* FIX: For Charitable customers, correctly show lifetime licenses as having no expiry. [#479](https://github.com/Charitable/Charitable/issues/479)
* FIX: The Donors widget only showed the 10 most recent campaigns. It now correctly shows all. [#480](https://github.com/Charitable/Charitable/issues/480)
* FIX: Improved inconsistencies in the way shortcodes are set up under the hood. [#454](https://github.com/Charitable/Charitable/issues/454)
* FIX: Fixed an issue with the custom donation amount input extending outside the edge of the donate widget in the Storefront theme (and possibly other themes). [#488](https://github.com/Charitable/Charitable/issues/488)

## 1.5.6
* NEW: Allow a default donation amount to be set via the `charitable_default_donation_amount` filter. [#470](https://github.com/Charitable/Charitable/issues/470)
* NEW: Made the list of resendable donation emails a filterable list, using the `charitable_resendable_donation_emails` filter. [#476](https://github.com/Charitable/Charitable/issues/476)
* FIX: Fixed an incorrect PayPal URL that prevented IPNs from working correctly.
* FIX: Prevents Charitable from co-opting the forgot password page in WooCommerce. [#473](https://github.com/Charitable/Charitable/issues/473)
* FIX: Minor styling update to improve theme compatibility for [campaigns] display. [#475](https://github.com/Charitable/Charitable/issues/475)

## 1.5.5
* FIX: Fixes fatal error in the campaign submission form in Ambassadors. [#471](https://github.com/Charitable/Charitable/issues/471)

## 1.5.4
* NEW: You can now display campaign categories or tags in a dropdown through the Campaign Categories / Tags widget. [#408](https://github.com/Charitable/Charitable/issues/408)
* NEW: The donation status is now shown in the output of `[charitable_my_donations]`. [#287](https://github.com/Charitable/Charitable/issues/287)
* NEW: Added `Charitable_Donation_Log` class as a single purpose class designed to interact with all log entries related to a donation.
* NEW: Users with the `edit_products` capability can now access the WordPress dashboard. This improves compatibility with Easy Digital Downloads and WooCommerce. [#468](https://github.com/Charitable/Charitable/issues/468)
* NEW: Added index to the `donor_id` column in the `charitable_campaign_donations` table. Prevents a sub-optimal query using a full table scan. [#465](https://github.com/Charitable/Charitable/issues/465)
* NEW: Added `unsigned` to columns in both the `charitable_campaign_donations` and `charitable_donors` tables.
* NEW: Removed the PayPal sandbox test tool. This can now be downloaded separately as a utility plugin from [GitHub](https://github.com/Charitable/Charitable-PayPal-Tester/). [#418](https://github.com/Charitable/Charitable/issues/418)
* FIX: Set site base country as default for Country field in Donation Form. [#463](https://github.com/Charitable/Charitable/issues/463)
* FIX: Fixed SQL error in donor count queries. [#467](https://github.com/Charitable/Charitable/issues/467)
* FIX: Avoid fatal error when adding certain shortcodes to pages while Yoast SEO is installed. [#387](https://github.com/Charitable/Charitable/issues/387)
* FIX: Also avoided similar fatal errors when running the Relevanssi build index. [#397](https://github.com/Charitable/Charitable/issues/397)
* FIX: Improved backwards compatibility. The solution added in 1.5.1/1.5.2 did not work in all cases. [#469](https://github.com/Charitable/Charitable/issues/469)
* FIX: Prevent WP Super Cache caching pages that should not be cached (login, donation form, forgot password, etc.). [#398](https://github.com/Charitable/Charitable/issues/398)

## 1.5.3
* FIX: Properly handles differing site date formats when editing donations. [#461](https://github.com/Charitable/Charitable/issues/461)

## 1.5.2
* NEW: Automatically set a user's display name to their name when they update their profile through Charitable. [#437](https://github.com/Charitable/Charitable/issues/437)
* NEW: Adds a third parameter to the `charitable_template` function allowing extension developers to leverage this function. [#451](https://github.com/Charitable/Charitable/issues/451)
* NEW: Allow email send logs to be saved in user meta as well as post meta.
* FIX: Cleans up problems with themes like Charity Home and Giving Hand that yesterday's fix didn't quite solve.
* FIX: You can now specify a comma-separated list of categories or tags in the `[campaigns]` shortcode to include campaigns from multiple categories/tags. [#452](https://github.com/Charitable/Charitable/issues/452)
* FIX: Improves the layout of the campaign summary block on mobile. Kudos to [@kkoppenhaver](https://github.com/kkoppenhaver) for his contribution. [#315](https://github.com/Charitable/Charitable/issues/315)

## 1.5.1
* FIX: Resolves a backwards compatibility problem that we had not accounted for. This specifically affected users of certain themes, including (but probably not limited to) Charity Home and Giving Hand.

## 1.5.0
* NEW: Added a `[charitable_donation_form]` shortcode. Display a campaign's donation form anywhere like this: [charitable_donation_form campaign_id=123]. [#136](https://github.com/Charitable/Charitable/issues/136)
* NEW: Added a `[charitable_donors]` shortcode. [#129](https://github.com/Charitable/Charitable/issues/129)
* NEW: Create and edit donations in the WordPress dashboard. No more adding mock donations through the Offline payment method. You can easily change donor details, add notes or change the amount/campaign of the donation. [#18](https://github.com/Charitable/Charitable/issues/18), [#241](https://github.com/Charitable/Charitable/issues/241), [#172](https://github.com/Charitable/Charitable/issues/172)
* NEW: Resend receipts & admin notifications for donations from the WordPress dashboard. [#165](https://github.com/Charitable/Charitable/issues/165)
* NEW: Added new emails specifically for Offline donations. You can now set up an admin notification and donation receipt that are sent for Offline donations while they are still pending. Also added `[charitable_email show=offline_instructions]` email field to include the Offline payment instructions in Offline donation receipts. [#33](https://github.com/Charitable/Charitable/issues/33), [#324](https://github.com/Charitable/Charitable/issues/324)
* NEW: Added email verification step to donor registration. After email verification is complete, donors are automatically able to see all donations they made under the same email address. [#385](https://github.com/Charitable/Charitable/issues/385), [#409](https://github.com/Charitable/Charitable/issues/409), [#222](https://github.com/Charitable/Charitable/issues/222)
* NEW: Added `[charitable_logout]` shortcode. A logout link is also shown on login and registration forms when the user is logged in. [#431](https://github.com/Charitable/Charitable/issues/431)
* NEW: Added Donation Fields API for easy registering of custom donation fields. [#402](https://github.com/Charitable/Charitable/issues/402)
* NEW: Added Form View API to separate how forms are rendered from how they are set up. [#401](https://github.com/Charitable/Charitable/issues/401)
* NEW: Added Endpoints API to provide developers with an infrastructure for registering custom endpoints in Charitable. [#306](https://github.com/Charitable/Charitable/issues/306)
* NEW: Added `Charitable_Email_Fields` class for registering email fields and getting their values in emails. [#393](https://github.com/Charitable/Charitable/issues/393)
* NEW: Added `charitable_minimum_donation_amount` filter to easily change the minimum donation amount. [#298](https://github.com/Charitable/Charitable/issues/298)
* NEW: Added `can_receive_donations()` method to the `Charitable_Campaign` class. The return value can be filtered with the `charitable_campaign_can_receive_donations`, providing a programmatic way to mark certain campaigns as no longer being able to receive donations. [#447](https://github.com/Charitable/Charitable/issues/447)
* NEW: Added an autoloader. Kudos to [@helgatheviking](https://github.com/helgatheviking). [#404](https://github.com/Charitable/Charitable/issues/404)
* NEW: The Forgot Password & Reset Password pages will automatically inherit the page template of their parent page (i.e. the Login page). [#379](https://github.com/Charitable/Charitable/issues/379)
* NEW: Added a `count` option to the `Charitable_Query` class, providing an easy way to user the query classes to get the number of donations, donors, etc. [#322](https://github.com/Charitable/Charitable/issues/322)
* NEW: Added `charitable_monetary_amount` filter to tweak how monetary amounts are formatted. [#308](https://github.com/Charitable/Charitable/issues/308)
* NEW: Added `charitable_sanitize_value_{$section}_{$key}` filter to sanitize individual settings fields. [#352](https://github.com/Charitable/Charitable/issues/352)
* NEW: Added basic styling for the `datepicker` form field. [#317](https://github.com/Charitable/Charitable/issues/317)
* NEW: Improved the Campaigns table in the admin to provide more helpful information at a glance, such as the current campaign status, end date and total funds raised. [#417](https://github.com/Charitable/Charitable/issues/417)
* NEW: Removed deprecated PayPal variables (cbt and no_note). Kudos to [@bscottx](https://github.com/bscottx). [#413](https://github.com/Charitable/Charitable/issues/413)
* FIX: Sessions have been improved to work alongside full page caching solutions like Varnish. Previously these caused problems with things like the donation receipt becoming inaccessible to donors after they donated. [#383](https://github.com/Charitable/Charitable/issues/383)
* FIX: Avoid creating records in `wp_options` for sessions that have no data. [#399](https://github.com/Charitable/Charitable/pull/399)
* FIX: Significantly improved the speed of Charitable settings pages. [#236](https://github.com/Charitable/Charitable/issues/236)
* FIX: The thousands separator for currencies can now be set to None, which will result in no spaces/commas/decimals appearing in large numbers such as 100000. [#448](https://github.com/Charitable/Charitable/issues/448)
* FIX: The donation log now displays log times in local time, not UTC. [#446](https://github.com/Charitable/Charitable/issues/446)
* FIX: Resolved a database error that broke the donation search function. [#407](https://github.com/Charitable/Charitable/issues/407)
* FIX: Changed login prompt text in donation form to something more logical. [#384](https://github.com/Charitable/Charitable/issues/384)
* FIX: Close modals with the ESC key. [#191](https://github.com/Charitable/Charitable/issues/191)
* FIX: Removed the drag drop field in the picture field on mobile devices. [#373](https://github.com/Charitable/Charitable/issues/373)
* FIX: A bug in Mobile Safari broke the picture field. [#370](https://github.com/Charitable/Charitable/issues/370), [#259](https://github.com/Charitable/Charitable/issues/259)
* FIX: In certain cases, hitting return key in form fields opened a file upload prompt. [#363](https://github.com/Charitable/Charitable/issues/363)
* FIX: Uploading more than max uploads to picture field works in certain cases. [#376](https://github.com/Charitable/Charitable/issues/376)
* FIX: Display Read More link in campaign loop for expired campaigns. [#381](https://github.com/Charitable/Charitable/issues/381)
* FIX: On email or gateway settings pages, provide a link back to the parent settings page. [#351](https://github.com/Charitable/Charitable/issues/351)
* FIX: When Stripe Checkout is closed/cancelled, donation form submission afterwards failed. [#378](https://github.com/Charitable/Charitable/issues/378)
* FIX: Fixed styling issue in REHub theme. [#406](https://github.com/Charitable/Charitable/issues/406)
* FIX: Campaign metabox tabs broke with Social Warfare activated. [#364](https://github.com/Charitable/Charitable/issues/364)

## 1.4.20
* Improve how plugin updates are shown for Charitable extensions. [#382](https://github.com/Charitable/Charitable/issues/382)

## 1.4.19
* Fixed a bug that prevented suggested donations with cents from showing correctly in sites where a comma is used for the decimal separator (i.e. 9,50). [#356](https://github.com/Charitable/Charitable/issues/356)

## 1.4.18
* Added an end time when editing campaigns to make it clearer when a campaign ends. Previously, a campaign's end time was ambiguous and this caused confusion for some users. [#335](https://github.com/Charitable/Charitable/issues/335)
* Add space as a thousands separator for countries where `12 500,00` would be the correct way to format an amount. [#332](https://github.com/Charitable/Charitable/issues/332)
* Add campaign edit link as email shortcode option for campaign-related emails. [#345](https://github.com/Charitable/Charitable/issues/345)
* Fixed multiple HTML validation issues in Charitable forms and the campaigns widget. [#344](https://github.com/Charitable/Charitable/issues/344) and [#349](https://github.com/Charitable/Charitable/issues/349)
* Prevent a bug where the donated amount on a campaign is completely wrong after a site changes its decimal/thousands separators. [#279](https://github.com/Charitable/Charitable/issues/279)
* Flush the campaign donation cache in popular caching plugins (WP Super Cache, W3 Total Cache, WP Rocket and WP Fastest Cache). [#186](https://github.com/Charitable/Charitable/issues/186)
* Fixed an error during donation processing that prevented donations when database caching is enabled in W3 Total Cache. [#347](https://github.com/Charitable/Charitable/issues/347)
* Improved the way upgrades are run to ensure they are not re-run unneccesarily and store the minimum required information about each upgrade.

## 1.4.17
* **THANK YOU**: Thanks to first-time contributor [@qriouslad](https://github.com/qriouslad) for his contribution to this release!
* When using Stripe Checkout, amounts over $999 were sometimes incorrectly sent to the Stripe modal. [#339](https://github.com/Charitable/Charitable/issues/339)
* Avoid fatal error in rare instances (only encountered in the Layers theme by Obox) when the donation form scripts are loaded through an admin AJAX request. [#340](https://github.com/Charitable/Charitable/issues/340)
* Added support for linking to campaign donated to from donation-related emails. [#341](https://github.com/Charitable/Charitable/issues/341)
* Improved i18n for dates.
* Introduced unit testing for Javascript, using QUnit. Developers, this is only available with the full package download from [GitHub](github.com/Charitable/Charitable/).
* Sanitize result of queries for campaign totals and total number of site donations.
* Avoid error that happens in situations where Divi and Yoast SEO are both installed alongside Charitable. [#316](https://github.com/Charitable/Charitable/issues/316)
* Better formatting of code commenting to improve compatibility with WordPress coding standards.

## 1.4.16
* Updated bundled version of WP Session Manager library to latest version (1.2.2).

## 1.4.15
* Add recurring donations support to Offline gateway. [#329](https://github.com/Charitable/Charitable/issues/329)

## 1.4.14
* After submitting the donate widget, redirect to the actual donation form on the page. [#328](https://github.com/Charitable/Charitable/issues/328)
* Improved compatibility for the donate widget with Recurring Donations.

## 1.4.13
* Added `tag` parameter to the [campaigns] shortcode. [#313](https://github.com/Charitable/Charitable/issues/313)
* We cleaned up another bug involving our [Easy Digital Downloads Connect extension](https://www.wpcharitable.com/extensions/charitable-easy-digital-downloads-connect/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=edd-connect), which resulted in being unable to set an end date for contribution rules when the campaign doesn't have an end date. [#310](https://github.com/Charitable/Charitable/issues/310)
* Persist un-rendered notices across page loads. [#314](https://github.com/Charitable/Charitable/issues/314)
* Hide radio inputs when Javascript is enabled. [#312](https://github.com/Charitable/Charitable/issues/312)
* We made some minor improvements to how the donation form submission is processed in Javascript. Needed for improvements to Stripe extension.

## 1.4.12
* If you were using our [Easy Digital Downloads Connect extension](https://www.wpcharitable.com/extensions/charitable-easy-digital-downloads-connect/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=edd-connect) and your site language is not English, you may have had problems with end dates for your benefactor relationships not saving correctly. We've fixed up that bug now. [#305](https://github.com/Charitable/Charitable/issues/305)

## 1.4.11
* Corrected a problem that caused newly created/saved campaigns without an end date to stop showing in the `[campaigns]` shortcode output. [#301](https://github.com/Charitable/Charitable/issues/301)
* Fixed an issue that resulted in PayPal donations left as Pending when `allow_url_fopen` was turned off on the server. [#302](https://github.com/Charitable/Charitable/issues/302)
* Avoid displaying the donation form & campaign information outside of the loop (this caused weird issues in the Layers theme). [#303](https://github.com/Charitable/Charitable/issues/303)
* Provided a more flexible API for toggling settings based on other setting values.

## 1.4.10
* Correctly filter donations by date in the CSV export. This was broken in certain non-English languages. [#299](https://github.com/Charitable/Charitable/issues/299)
* Fixed an issue that prevented the custom donation amount from being picked up on sites using our new [Recurring Donations extension](https://www.wpcharitable.com/extensions/charitable-recurring-donations/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=recurring-donations).

## 1.4.9
* Added Ghanaian Cedi and Egyptian Pound to currencies. [#288](https://github.com/Charitable/Charitable/issues/288) and [#282](https://github.com/Charitable/Charitable/issues/282)
* Remove `$wp_version` global. [#294](https://github.com/Charitable/Charitable/pull/294)
* Miscellaneous accessibility improvements. [#291](https://github.com/Charitable/Charitable/pull/291), [#292](https://github.com/Charitable/Charitable/pull/292) and [#293](https://github.com/Charitable/Charitable/pull/293)

## 1.4.8
* Removed some code left over from plugin testing.

## 1.4.7
* Avoid issues with PayPal IPNs missing the 'invoice' parameter in certain cases — likely a bug on the PayPal end. This resulted in donations remaining stuck as Pending. We have reworked how IPNs are processed to avoid reliance on this and avoid further issues. [#289](https://github.com/Charitable/Charitable/issues/289)
* Store the PayPal transaction ID for donations after an IPN has been received. [#270](https://github.com/Charitable/Charitable/issues/270)
* Add a notice to the donation form when viewed by site admin to remind them that Test Mode is enabled. [#233](https://github.com/Charitable/Charitable/issues/233)
* Improve styling for the donation receipt summary. [#214](https://github.com/Charitable/Charitable/issues/214)
* Make sure that donor count and donors widget both include donations to child campaigns. [#263](https://github.com/Charitable/Charitable/issues/263) and [#264](https://github.com/Charitable/Charitable/issues/264)
* Correctly show the donor count in the Donation Stats widget, not the number of donations. [#268](https://github.com/Charitable/Charitable/issues/268)
* Show a blank field for formatted addresses in the donation export or admin donation pages when no address details were provided. Previously, the donor's name was shown. [#255](https://github.com/Charitable/Charitable/issues/255) and [#256](https://github.com/Charitable/Charitable/issues/256)
* Fix client-side credit card validation. [#280](https://github.com/Charitable/Charitable/issues/280)
* Fix issue causing incorrect donation status to be displayed in admin notification email and donation receipt. [#261](https://github.com/Charitable/Charitable/issues/261)
* Remove the Licenses tab from the Settings area when you don't have any extensions installed. [#249](https://github.com/Charitable/Charitable/issues/249)
* Miscellaneous other minor, under-the-hood improvements and tweaks.

## 1.4.6
* Properly activate Charitable on all sites when it is network activated. Also makes sure that Charitable is correctly installed when a new site is added to a network that has Charitable network-activated. [#225](https://github.com/Charitable/Charitable/issues/225)
* Display success messages to the user after settings are updated in the admin. [#54](https://github.com/Charitable/Charitable/issues/54)
* Fixes a bug that prevented donations from being displayed in the admin when filtering by campaign. [#242](https://github.com/Charitable/Charitable/issues/242)
* Removes PHP warnings that were displayed on the Charitable donations page in the dashboard when there are no donations. [#232](https://github.com/Charitable/Charitable/issues/232)
* Changed the hook that the Donation Receipt and Donation Notification emails are sent on from `save_post` to `charitable-completed_donation`. [#217](https://github.com/Charitable/Charitable/issues/217)
* Added a `CHARITABLE_DEBUG` constant for error logging. Currently, enabling this only logs the IPN responses that are received from PayPal after donations are made. [#229](https://github.com/Charitable/Charitable/issues/229)
* Ensure that the donation form script is always loaded for the campaign donation widget. [#239](https://github.com/Charitable/Charitable/issues/239)
* Fixes a bug that prevented the password reset from working correctly. [#238](https://github.com/Charitable/Charitable/issues/238)
* Refer to campaigns as campaigns instead of posts in admin update messages. [#234](https://github.com/Charitable/Charitable/issues/234)

## 1.4.5
* The permissions for accessing Donations and Campaigns in the WordPress dashboard has changed. Users who are set up as Campaign Managers can access both Donations and Campaigns, but cannot access Charitable settings. This permission is reserved for admin users. In addition, the `manage_charitable_settings` permission has been removed from Campaign Managers. [#209](https://github.com/Charitable/Charitable/issues/209)
* Fixes the way donations are processed in Javascript to avoid issues when the donation is *not* processed with AJAX (currently, this is only the case if you're using the Easy Digital Downloads extension, Pronamic iDEAL or an old version of one of our premium payment gateway extensions). [#223](https://github.com/Charitable/Charitable/issues/223)
* Fixes the registration form shortcode, which was being printed out too early on the page. [#224](https://github.com/Charitable/Charitable/issues/224)
* Adds a new filter for the list of active payment gateways: `charitable_active_gateways`. See `Charitable_Gateways::get_active_gateways()`.
* Three new methods have been added to the `Charitable_Donation` abstract class: `get_donation_type()` retrieves the type of donation; `get_donation_plan_id()` returns the ID of the recurring donation plan (to be used by the Recurring Donations extension); `get_donation_plan()` returns the recurring donation object. [PR #215](https://github.com/Charitable/Charitable/pull/215)
* Export files now include the type of export (note: this does not work if you are on PHP 5.2). [#200](https://github.com/Charitable/Charitable/issues/200)
* The `custom` parameter for PayPal donations now accepts a JSON string. [PR #198](https://github.com/Charitable/Charitable/pull/198)

## 1.4.4
* Resolves a new issue related to the donation form validation introduced in version 1.4.3, which prevented the donation widget form from being submitted. [#221](https://github.com/Charitable/Charitable/issues/204) and [#205](https://github.com/Charitable/Charitable/issues/221)
* Fixes a bug that resulted in logged in users who had never made a donation being able to see a list of all donations with the `[charitable_my_donations]` shortcode. No personal donor data was displayed, and the donation receipts remained inaccessible to the users. All they could see was the date of the donation, the campaign donated to and the amount of the donation. [#220](https://github.com/Charitable/Charitable/issues/204) and [#205](https://github.com/Charitable/Charitable/issues/220)

## 1.4.3
* Added a new sandbox testing tool to allow you to test your PayPal donation flow. If you're using PayPal, you should test this as soon as you can to avoid disruption, as PayPal is making some security upgrades to its platform which may cause problems for certain sites. [Read more about how PayPal's upgrades will affect you](https://www.wpcharitable.com/how-paypals-ssl-certificate-upgrade-will-affect-you-and-how-you-can-prepare-for-it/?utm_source=notice&utm_medium=wordpress-dashboard&utm_campaign=paypal-ssl-upgrade&utm_content=blog-post)
* Added honeypot form validation for the donation form and registration, password reset, forgot password and profile forms. This is an anti-spam measure designed to prevent fake donations from being created by bots.
* Prevent donations from being created if an invalid email address or payment gateway is used.
* Ensure that client-side validation is always performed for donations, even when the gateway integration has not been updated for compatibility with the AJAX-driven donations introduced in version 1.3. The only gateway that we know of that falls into this category is Pronamic iDEAL, so this is a nice update if you are using Pronamic iDEAL.

## 1.4.2
* Added a link to the registration form from the login form and vice versa. [#204](https://github.com/Charitable/Charitable/issues/204) and [#205](https://github.com/Charitable/Charitable/issues/205)
* Included two new parameters in the `[charitable_registration]` shortcode: `redirect` sets the default page that people should be redirect to after registering, and `login_link_text` sets the text of the login link (see above). [#208](https://github.com/Charitable/Charitable/issues/208) and [#205](https://github.com/Charitable/Charitable/issues/205)
* Also included a new parameter in the `[charitable_login]` shortcode: `registration_link_text` sets the text of the login link (see above). [#204](https://github.com/Charitable/Charitable/issues/204)
* Added a column for the campaign creator to the campaigns page in the WordPress dashboard. [#166](https://github.com/Charitable/Charitable/issues/166)
* Added three new fields that can be displayed in donation-related emails, like the donation receipt or admin notification: the total amount donated, the campaign(s) that received the donation and the categories of the campaign(s) that received the donation. [#202](https://github.com/Charitable/Charitable/issues/202) and [#203](https://github.com/Charitable/Charitable/issues/203)
* Made sure that setting the `order` parameter in the `[campaigns]` shortcode works with lowercase and uppercase. `ASC`, `DESC`, `asc` and `desc` are all valid options now. [#206](https://github.com/Charitable/Charitable/issues/206)
* Fixed a bug that prevented the campaign end date from saving when using Charitable in a non-English installation. [#201](https://github.com/Charitable/Charitable/issues/201)
* Fixed a bug that prevented the comments section from appearing on campaigns when modal donations were enabled. [#210](https://github.com/Charitable/Charitable/issues/210)
* Fixed a bug that caused a PHP warning when trying to use the `site_url` email shortcode parameter in emails.

## 1.4.1
* The donor address is split over multiple columns in the donation export. [#194](https://github.com/Charitable/Charitable/issues/194)
* In certain cases, credit card validation was getting triggered for non-credit card donations (i.e. PayPal or Offline). This bug has been fixed. [#189](https://github.com/Charitable/Charitable/issues/189)
* After a donor makes a successful donation is made, their session is cleared as expected. [#181](https://github.com/Charitable/Charitable/issues/181)
* Pending and draft campaigns are now included in the filtering options on the Donations page. [#187](https://github.com/Charitable/Charitable/issues/187)
* The Bolivian Boliviano currency (BOB) has been added. [#193](https://github.com/Charitable/Charitable/issues/193)

## 1.4.0
* Added the `[charitable_my_donations]` shortcode. Use this shortcode to allow logged in users to view a history of their donations, including links to the donation receipts. [#14](https://github.com/Charitable/Charitable/issues/14)
* Scale the campaign grid gracefully when viewing on smaller screens. The `[campaigns]` shortcode now supports a `responsive` paramater, which is enabled by default. You can set it to a specific px/em amount to change the breakpoint, or set it to `0` to disable responsive styling. [#88](https://github.com/Charitable/Charitable/issues/88)
* Also provided appropriately responsive styling for suggested donation amounts on small screens. [#159](https://github.com/Charitable/Charitable/issues/159)
* Added client-side validation for the donation form. This checks whether donors have filled out all the required fields, whether they're donating more than $0 (because seriously, a $0 donation won't go far :)) and whether they have used a valid credit card (if you're using our Stripe or Authorize.Net extensions). [#176](https://github.com/Charitable/Charitable/issues/176) and [#63](https://github.com/Charitable/Charitable/issues/63)
* Added a password reset process to provide a complete user-facing login and registration workflow. [#89](https://github.com/Charitable/Charitable/issues/89)
* Include an `order` paramater for the `[campaigns]` shortcode, to reverse the direction in which campaigns are displayed. [#64](https://github.com/Charitable/Charitable/issues/64)
* Allow campaigns in the `[campaigns]` shortcode to be ordered by any of the orderby options for [`WP_Query`](https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters).
* Added drag and drop support for the Picture form field, which is used in the User Avatar and Ambassadors extensions. [#111](https://github.com/Charitable/Charitable/issues/111)
* Improved how the plugin checks for updates to Charitable extensions, to keep the WordPress dashboard running smoothly. [#133](https://github.com/Charitable/Charitable/issues/133)
* Added a `charitable_create_donation()` function for developers who want to create donations programatically. [#109](https://github.com/Charitable/Charitable/issues/109)
* Added a new `Charitable_Donations_Query` class, which can be used by developers to retrieve donations from the database. [#155](https://github.com/Charitable/Charitable/issues/155)
* Added a new `Charitable_Deprecated` class, which is used to record any incorrect usage of Charitable functions or methods.
* Switched to using the built-in edit.php admin page for listing Charitable donations, instead of relying on a custom admin page with a custom posts table. While there, we also simplified the interface and added colour-coding to the donation statuses. [#110](https://github.com/Charitable/Charitable/issues/110)
* Include the donor's phone number, address and the payment method in the donations export CSV. [#154](https://github.com/Charitable/Charitable/issues/154)
* When multiple gateways are enabled, the default one is listed first in the donation form. [#139](https://github.com/Charitable/Charitable/issues/139)
* Automatically cancel a donation when the donor returns from the gateway before completing it. This works with PayPal, PayUMoney and PayFast. [#90](https://github.com/Charitable/Charitable/issues/90) and [#117](https://github.com/Charitable/Charitable/issues/117)
* Added a `Charitable_Donor::__toString()` method, so that echoing the object simply prints out the donor name.
* Added `charitable_sanitize_amount()` function to convert any amount of type string into a float.
* Trim the currency symbol from monetary amounts to prevent the symbol being treated as part of the amount. [#145](https://github.com/Charitable/Charitable/pull/145)
* Trim the currency symbol from the suggested donation amounts when saving a campaign. [#147](https://github.com/Charitable/Charitable/issues/147)
* When a donation fails and the user is redirected back to the donation form, they can re-attempt the same donation. Previously, a new donation would have been created, leaving a phantom pending donation behind. [#106](https://github.com/Charitable/Charitable/issues/106)
* Prevent duplicate donations caused by clicking the donate button repeatedly. [#164](https://github.com/Charitable/Charitable/issues/164)
* Fixed a bug related to empty content in the Layers theme. [#9](https://github.com/Charitable/Charitable/issues/9)

## 1.3.7
* Makes `Charitable_Currency::get_currency_symbol()` a publicly accessible method.
* Allow email shortcode values to be dynamically generated without being registered first. This simplifies the process of displaying dynamic data within emails if there is no existing shortcode output for it. [#134](https://github.com/Charitable/Charitable/issues/134)
* Provide a consistent api for determining the status of a campaign. Developers can use `$campaign->get_status_key()` (where `$campaign` is a `Charitable_Campaign` object) to check whether a campaign is inactive, ended, ended and successfully funded, ended and not successfully funded, ending soon, or active.
* Reset the positioning and styling of the modal when window or modal change in size. This prevents the modal from growing larger than the size of the window without having scrollbars. [#135](https://github.com/Charitable/Charitable/issues/135)

## 1.3.6
* Prevented campaigns being created with no suggested donation amounts and custom donations disabled. This results in $0 donations. [#127](https://github.com/Charitable/Charitable/issues/127)
* Fixed errors when exporting donations with errors set to display. [#128](https://github.com/Charitable/Charitable/issues/128)
* Deprecated `Charitable_Email::return_value_if_has_valid_donation()` method, since this was completely broken and should not be used.

## 1.3.5
* Added `is_preview()` method to `Charitable_Email` class.
* Added `get_donations()` method to `Charitable_Donor` class.
* Improved custom post status labels.
* Only include completed payments in the Donation Statistics dashboard widget, for the period summaries.
* Fixed PHP notices in email previews.
* Deprecated `Charitable_Session::get_session_id()`. We are no longer using a public session ID.

## 1.3.4
* Added selective refresh support for Charitable widgets.
* Added support for passing multiple campaign IDs to campaign donation queries. [#112](https://github.com/Charitable/Charitable/issues/112)
* Fixed a bug where donors without a completed donation were included in the donor count in the Donation Stats widget. [#114](https://github.com/Charitable/Charitable/issues/114)
* Fixed a bug that incorrectly set the from address for emails to always be the site email address, instead of using the provided settings. [#113](https://github.com/Charitable/Charitable/issues/113)
* Fixed a bug that stopped the cron scheduler from being activated in any new installs.
* Fixed display issues in the Charitable settings area with number fields.
* Deprecated usage of `shortcode_atts()` for the email shortcode, in favor of `wp_parse_args()`. If you relied on the `shortcode_atts_charitable_email` filter, this will no longer do anything and you should test & update your code.

## 1.3.3
* Fixes a bug that prevented donors being able to access their donation receipts after making their donation.
* Flush rewrite rules after installation to avoid "Page not found" errors.

## 1.3.2
* Fixed a bug that turned comments off everywhere. [#104](https://github.com/Charitable/Charitable/issues/104)
* Added a better fallback for donations for users with Javascript enabled, when using the modal donation forms. [#60](https://github.com/Charitable/Charitable/issues/60)
* Avoid sending donation notifications & receipts multiple times when a donation's status is toggled on/off Paid. [#96](https://github.com/Charitable/Charitable/issues/96)
* Donate button in campaign grids links to the campaign page when the donation form is set up to show on the same page as the campaign. [#107](https://github.com/Charitable/Charitable/issues/107)
* Fixed a bug that caused invalid shortcode options to show for custom emails sub-classing `Charitable_Email`. [#95](https://github.com/Charitable/Charitable/issues/95)

## 1.3.1
* **APOLOGIES**: 1.3.0 introduced a couple of bugs that we failed to pick up on before releasing the update. We have fixed those bugs now and are working on improving the process around how we push out updates, to avoid issues like this in the future.
* Removes leftover testing code that prevented campaigns from being created or edited.
* Format the donation amount so that PayPal can understand it (PayPal doesn't like amounts with more than two decimal places). [See issue](https://github.com/Charitable/Charitable/issues/102)
* Prevent PHP notice when making a donation. [See issue](https://github.com/Charitable/Charitable/issues/100)
* Fixes a bug that prevented the Donate widget from working as expected.

## 1.3.0
* **THANK YOU**: A massive thank you to the following contributors who have contributed to Charitable 1.3: [@helgatheviking](https://github.com/helgatheviking), [@rafecolton](https://github.com/rafecolton), [@ciegovolador](https://github.com/ciegovolador), [@ElStupid](https://github.com/ElStupid) and [@altatof](httsp://github.com/altatof).
* NEW: Export donations to CSV via the WordPress dashboard. Go to Charitable > Donations and click on the Export button to generate your report.
* NEW: Donations are now processed via AJAX, which results in a smoother donation flow, particularly if you're using modal donations. [See issue](https://github.com/Charitable/Charitable/issues/41)
* NEW: Added an `id` parameter to the `[campaigns]` shortcode to show just a single campaign's widget.
* NEW: Dutch & French translations! Major props to @ElStupid (Dutch translation) and @altatof (French translation).
* NEW: Added custom body classes for the following templates: donation receipt, donation processing and email preview. All body classes are added via a single function: `charitable_add_body_classes()`.
* Added ARS currency.
* Fixed a bug that prevented donations with cents from being stored/displayed correctly when using commas for the currency decimal. [See issue](https://github.com/Charitable/Charitable/issues/57)
* Fixed a bug that let to donations being saved with the incorrect donation time. Run the upgrade routines to fix this in all your existing donations.
* Removed `charitable_templates_start` hook and deprecated all methods in the `Charitable_Templates` class. If you were calling any of these directly or using the `charitable_templates_start` hook, update your application code. All templates are now loaded via a single method: `Charitable_Templates::template_loader()`. All custom body classes are added via `charitable_add_body_classes()`. `Charitable_Templates::remove_admin_bar_from_widget_template()` has been replaced with `charitable_hide_admin_bar()`.
* Fixes a styling bug that caused the progress bar to extend beyond the campaign widget when more than 100% of a campaign's has been raised. [See issue](https://github.com/Charitable/Charitable/issues/47)
* Fixes a Javascript bug that prevented the $ variable (jQuery) from being defined in certain cases in the admin scripts.
* Fixes a clash with Cart66.
* Fixed a bug in modal donation window. [See issue](https://github.com/Charitable/Charitable/issues/43)
* Changed the `amount` column in the `wp_charitable_campaign_donations` table to a DECIMAL, instead of FLOAT. [See issue](https://github.com/Charitable/Charitable/issues/56)

## 1.2.4 ##
* Updated for compatibility with WordPress 4.4.
* Improves the API for dealing with the donation processor. Both the `charitable_before_process_donation_form` and `charitable_before_process_donation_amount_form` hooks now pass the donation form object as a second parameter.
* Fixes a bug that prevented you from being able to select the donation amount inside a modal opened via AJAX.
* Fixes a bug that prevented the donation form display option from being set correctly when changed via the Customizer.
* Fixes a bug in the form submission handler.

## 1.2.3
* NEW: The `[campaigns]` widget now supports a new `button` argument, so you can specify whether you would like to show a "Read more" link, a "Donate" button, or nothing at all. [See the documentation](https://www.wpcharitable.com/documentation/the-campaigns-shortcode/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=plugin-page-referrals&utm_content=1-2-3-release) for details on how to use the new argument.
* Improved styling for the modal donation form.
* Added method to retrieve all donation IDs for a particular campaign.
* Fixes a bug that blocked donations with a dollar sign in the amount field.
* Fixes a bug that prevented template functions from being "pluggable" in themes.
* Fixes a bug that stopped the profile form from displaying the user's saved address fields.
* Fixes a bug that prevented form submission validating when submitting a value of 0 for required fields.

## 1.2.2
* Fixes a bug that prevented the donation form from working correctly when the donor is not logged in.

## 1.2.1
* Including missing files from 1.2.0 release.

## 1.2.0
* [Read the full release notes](https://www.wpcharitable.com/charitable-1-2-0-is-ready-to-download/?utm_source=readme&utm_medium=changelog-tab&utm_campaign=plugin-page-referrals&utm_content=1-2-0-release-notes).
* NEW: Change the highlight colour via the WordPress Customizer. You can preview your changes as you make them.
* NEW: There is a shiny new dashboard widget when you log into the WordPress dashboard to highlight your donation stats.
* NEW: You can now limit the donation form to only display required user fields.
* NEW: Create a static page with the [donation_receipt] shortcode to customize your donation receipt.
* NEW: All donation data is now displayed in the admin donation page.
* NEW: You can now change the campaign creator via the Campaign management page.
* NEW: Adds a login link to the donation form when donors are not logged in.
* NEW: When a user is logged in but has not filled out all required fields, they are presented with all the user fields.
* Removed the 'charitable_after_update_donation' hook. To respond to updates to a donation, use the 'save_post_donation'.
* Fixed a bug that resulted in `[campaigns orderby=popular]` to include non-complete donations when determining the order of campaigns.
* Fixed a bug that prevented donors from receiving their donation receipt after a donation is updated directly on the donation page.
* Fixes a bug that redirected donors to a "Page Not Found" page after donating on sites where the WordPress address and site address are not the same.
* Fixes bugs in the Donation Stats and Donors widget that causes them to include pending donations in the total.
* Fixes a bug in the Benefactors addon that caused fixed contribution amounts to not be saved.
* Major performance improvements in the WordPress dashboard.
* Better PHP 5.2 compatibility.

## 1.1.5
* Fixes a bug that allowed people to make a donation without entering required details, or with an amount of $0 or less.

## 1.1.4
* Fixes a critical bug that resulted in PayPal donations not working if you didn't have any other gateways installed.
* Fixes an error when trying to retrieve a donor name for a donation that does not have a matching donor.

## 1.1.3
* Enhancement: Added the ability to change the dimensions of the user avatars added using Charitable User Avatar, with a PHP filter function.
* Fixes an issue where only having one active gateway meant that those gateway's donation form fields would not show.
* Fixes a problem with the permalinks structure that prevented you being able to create pages with slugs of "/donate/" or "/widget".
* Fixes the WP Editor form field template to prevent the text from being wrapped in HTML tags.

## 1.1.2
* Security Fix: Prevent unauthorized users accessing your donation receipt.
* Fix: Localization with the .po/.mo files now really does work correctly. For real this time.

## 1.1.1
* Fix: Emails will now correctly be sent with the body, headline and subject you set, instead of the default.

## 1.1.0
* Enhancement: Added a new email that can be sent when a campaign has finished.

* Fix: Localization with the .po/.mo files now works correctly.
* Fix: Chrome 45 bug when clicking directly on suggested amount inputs is resolved.

## 1.0.3
* Improvement: Using `wp_list_pluck` instead of `array_column` for compatibility with versions of PHP prior to 5.5.
* PHP 5.2 Compatibility: Avoid T_PAAMAYIM_NEKUDOTAYIM error in older versions of PHP.

## 1.0.2
* Fix: Added missing file into the repo.

## 1.0.1
* Improvement: Moved the user dashboard functionality into the core of the plugin, so that it is always available.
* Fix: The installation routine now flushes permalinks correctly -- no more "Page not Found" problems!

## 1.0.0
* Initial release.
