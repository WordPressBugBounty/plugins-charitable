/* global wpchar, wpCookies */
// eslint-disable-line no-unused-vars

var CharitableOnboarding = window.CharitableOnboarding || (function (document, window, $) {

    var s = {},
        $checklist,
        elements = {};

    var app = {

        settings: {
            onboarded: false
        },

		/**
		 * Start the engine.
		 *
		 * @since 1.8.1
		 */
        init: function () {

            s = this.settings;

            // Document ready.
            $(app.ready);

        },

		/**
		 * Document ready.
		 *
		 * @since 1.8.1.12
		 *
		 */
        ready: function () { // check to see if javascript has been defined.

            // check to see if charitable_reporting has been defined.
            // if ( typeof charitable_reporting === 'undefined' ) {
            //     return;
            // }

            // wpchar.debug('charitable_reporting');
            // wpchar.debug(charitable_reporting);

            $checklist = $('#charitable-setup-checklist');

            // $reports = $('#charitable-reports');

            // if ( $reports.length === 0 ) {
            //     return;
            // }

            // // UI elements.
            // elements.$start_datepicker       = $( '#charitable-reports-start_date' );
            // elements.$end_datepicker         = $( '#charitable-reports-end_date' );
            // elements.$filter_button          = $( '#charitable-reports-filter-button');
            // elements.$report_campaign_filter = $('#report-campaign-filter');
            // elements.$report_category_filter = $('#report-category-filter');

            // // Data containers.
            // elements.$top_donation_amount                = $('#charitable-top-donation-total-amount');
            // elements.$top_donation_count                 = $('#charitable-top-donation-total-count');
            // elements.$top_donation_average               = $('#charitable-top-donation-average');
            // elements.$top_donation_donors_count          = $('#charitable-top-donor-count');
            // elements.$top_charitable_refund_total_amount = $('#charitable-top-refund-total-amount');
            // elements.$top_charitable_refund_count        = $('#charitable-top-refund-count');
            // elements.$top_donors_list                    = $('#charitable-top-donors-list');
            // elements.$donations_breakdown_list           = $('#donations-breakdown-list');
            // elements.$payment_methods_list               = $('ul#charitable-payment-methods-list');
            // elements.$activity_list                      = $('#charitable-activity-list');
            // elements.$top_campaigns_list                 = $('#charitable-top-campaigns-list');
            // elements.$report_date_range_filter           = $('#report-date-range-filter');
            // elements.$topnav_datepicker                  = $('#charitable-reports-topnav-datepicker');
            // elements.$topnav_datepicker_comparefrom      = $('#charitable-reports-topnav-datepicker-comparefrom-lybunt');
            // elements.$topnav_datepicker_compareto        = $('#charitable-reports-topnav-datepicker-compareto-lybunt');

            // s.datePickerStartDate = '';
            // s.datePickerEndDate   = '';

            // s.datePickerCompareFromStartDate = '';
            // s.datePickerCompareFromEndDate   = '';
            // s.datePickerCompareToStartDate   = '';
            // s.datePickerCompareToEndDate     = '';


            // if ( app.isAdvancedPage() ) {
            //     app.initDatePickerRanges( 'ranged' );
            // } else {
            //     app.initDatePickerRanges( '' );
            // }

            // app.initDatePicker();

            // Bind all actions.
            app.bindUIActions();

        },

		/**
		 * Element bindings.
		 *
		 * @since 1.8.1
		 */
        bindUIActions: function () {

            $checklist.on('click', '.charitable-toggle', function (e) {
                e.preventDefault();
                // remove the focus from the button.
                $(this).blur();
                app.fieldSectionToggle($(this), 'click');
            });

            $checklist.on('click', '#wpchar-no-stripe', function (e) {
                // if this checkbox is checked, then add a css class to charitable-connect-stripe.
                if ($(this).is(':checked')) {
                    $('.charitable-connect-stripe').addClass('wpchar-disabled');
                } else {
                    $('.charitable-connect-stripe').removeClass('wpchar-disabled');
                }
            });

        },

        /**
         * Toggle field group visibility in the field sidebar.
         *
         * @since 1.8.1.12
         *
         * @param {mixed}  el     DOM element or jQuery object.
         * @param {string} action Action.
         */
        fieldSectionToggle: function (el, action) {

            var $this = $(el),
                $nearestContainer = $this.closest('section.charitable-step'),
                $toggleGroup = $nearestContainer.find('.charitable-toggle-container'),
                sectionName = $nearestContainer.data('section-name'),
                $icon = $this.find('i'),
                cookieName = 'charitable_checklist_section_' + sectionName;

            if (action === 'click') {

                $icon.toggleClass('charitable-angle-right');

                $toggleGroup.stop().slideToggle('', function () {
                    $nearestContainer.toggleClass('charitable-closed');
                    if ($nearestContainer.hasClass('charitable-closed')) {
                        $nearestContainer.removeClass('charitable-open');
                        wpCookies.remove(cookieName);
                    } else {
                        wpCookies.set(cookieName, 'true', 2592000); // 1 month
                    }
                });

                return;
            }

        },

    };

    return app;

}(document, window, jQuery)); // eslint-disable-line no-undef

CharitableOnboarding.init();
