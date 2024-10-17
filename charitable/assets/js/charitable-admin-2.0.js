/* global charitable_builder, jconfirm, charitable_panel_switch, Choices, Charitable, CharitableCampaignEmbedWizard, wpCookies, tinyMCE, CharitableUtils, List */ // eslint-disable-line no-unused-vars

var CharitableAdminUI = window.CharitableAdminUI || ( function( document, window, $ ) {

	var s = {};

	var elements = {};

    var app = {

		settings: {
			clickWatch: false
		},

		init: function() {

			// charitable_panel_switch = true;
			s = this.settings;

			// Document ready.
			$( app.ready );

		},

		ready: function() {

            // Move navigation elements on certain pages.
            app.moveNavigationElements();

			elements.$addNewCampaignButton = $( 'body.post-type-campaign .page-title-action' );

			// Bind all actions.
			app.bindUIActions();

            var urlParams = new URLSearchParams(window.location.search);

            if ( urlParams.has('create') && 'campaign' === urlParams.get('create') ) {
                app.newCampaignPopup();
                urlParams.delete('create');
                window.history.pushState({}, '', '/wp-admin/edit.php?' + urlParams.toString() );
            }

            // upon loading the page or a change of a hash - view the hash in the url and if that anchor exists, scroll to it.
            $(document).ready(function() {
                app.scrollToAnchor();
            }
            );
            $(window).on('hashchange', function() {
                app.scrollToAnchor();
            });

        },

        moveNavigationElements: function() {

            // if the body class has the class 'post-type-charitable' and 'edit-tags-php' then move the navigation elements "#charitable-tools-nav" right after form.search-form.
            if ( $('body.post-type-charitable.edit-tags-php').length > 0 ) {
                $('#charitable-tools-nav').insertBefore('h1.wp-heading-inline');
            }



        },


        /**
         * Bind all UI actions.
         *
         * @return {void}
         *
        */
        bindUIActions: function() {

            // Deprecated.
            // $('body.post-type-campaign').on( 'click', '.page-title-action', function( e ) {
            //     e.preventDefault();
            //     app.newCampaignPopup();
            // } );

            $('body.post-type-campaign').on( 'click', '.charitable-campaign-list-banner a.button-link', function( e ) {
                e.preventDefault();
                app.campaignListBannerPopup();
            } );

            $('body.post-type-campaign').on( 'click', '.jconfirm-closeIcon', function( e ) { // eslint-disable-line no-unused-vars
                s.clickWatch = false;
            } );
            if ( s.clickWatch === false ) {
                $('body.post-type-campaign').on( 'click', 'input.campaign_name', function( e ) {
                    e.preventDefault();
                    $(this).select();
                    s.clickWatch = true;
                } );
            }

            // Blank slate create new campaign button.
            if ( $('.charitable-blank-slate-create-campaign').length > 0 ) {

                $('body.post-type-campaign').on( 'click', '.charitable-blank-slate-create-campaign', function( e ) {
                    e.preventDefault();
                    app.newCampaignPopup();
                } );

            }

            // Welcome activation page.
            app.initWelcome();

            // Upgrade Modal.
            app.initUpgradeModal();

            // Notifications
            app.initNotifications();

        },

        /**
         * Initialize the notifications.
         *
         * @return {void}
         *
         * @since 1.8.2
         *
         */
        initNotifications: function() {

            // when a prev or next button is clicked inside the notification navigation.
            $('body .charitable-dashboard-notification-navigation').on( 'click', 'a', function( e ) {
                e.preventDefault();

                var $this = $(this),
                    // find the notification id and number of the notification that does not have the charitable-hidden css class.
                    notification_number = $this.closest('.charitable-dashboard-notifications').find('.charitable-dashboard-notification:not(.charitable-hidden)').data('notification-number'),
                    notification_id = $this.closest('.charitable-dashboard-notifications').find('.charitable-dashboard-notification:not(.charitable-hidden)').data('notification-id'),
                    notification_type = $this.closest('.charitable-dashboard-notifications').find('.charitable-dashboard-notification:not(.charitable-hidden)').data('notification-type'),
                    notification_count = $this.closest('.charitable-dashboard-notifications').find('.charitable-dashboard-notification').length,
                    $container = $this.closest('.charitable-dashboard-notifications');

                if ( $this.hasClass('next') ) {
                    // add the charitable-hidden of the current notification.
                    $container.find('.charitable-dashboard-notification[data-notification-number="' + notification_number + '"]').addClass('charitable-hidden');
                    notification_number++;
                    if ( notification_number > notification_count ) {
                        notification_number = 1;
                    }
                    // remove the charitable-hidden of the next notification.
                    $container.find('.charitable-dashboard-notification[data-notification-number="' + notification_number + '"]').removeClass('charitable-hidden');
                } else if ( $this.hasClass('prev') ) {
                    // add the charitable-hidden of the current notification.
                    $container.find('.charitable-dashboard-notification[data-notification-number="' + notification_number + '"]').addClass('charitable-hidden');
                    notification_number--;
                    if ( notification_number < 1 ) {
                        notification_number = notification_count;
                    }
                    // remove the charitable-hidden of the next notification.
                    $container.find('.charitable-dashboard-notification[data-notification-number="' + notification_number + '"]').removeClass('charitable-hidden');
                }
            }
            );

            // when the close button is clicked, remove the notificaiton from the HTML and do an ajax call removing the notficaition from the database.
            $('body .charitable-dashboard-notifications').on( 'click', '.charitable-remove-dashboard-notification', function( e ) {
                e.preventDefault();

                var $this = $(this),
                    $container = $this.closest('.charitable-dashboard-notifications'),
                    notification_id = $container.find('.charitable-dashboard-notification:not(.charitable-hidden)').data('notification-id');
                    // notification_id = $this.closest('.charitable-dashboard-notification').data('notification-id');

                // find the notificaiton that is currently being displayed... basically fine .charitable-dashboard-notification that doesn't have a css class of charitable-hidden.

                // ajax call to diable the notification.
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'charitable_disable_dashboard_notification',
                        notification_id: notification_id,
                        nonce: charitable_admin.nonce,
                    },
                    success: function( response ) {
                        if ( response.success ) {
                            // remove the element that has the notification id.
                            $container.find('.charitable-dashboard-notification[data-notification-id="' + notification_id + '"]').remove();
                            // count the number of notifications that are left.
                            var notification_count = $container.find('.charitable-dashboard-notification').length;
                            // if there are no more notifications, remove the entire container.
                            if ( notification_count === 0 ) {
                                $container.remove();
                            }
                        }
                    }
                });

            });

        },

        /**
         * Scroll to anchor.
         * If the url has a hash, scroll to the anchor.
         *
         * @return {void}
         *
         * @since 1.8.2
        */
        scrollToAnchor: function() {
            // get the hash from the url.
            var hash = window.location.hash;
            hash = hash.substring(1);

            if ( hash ) {

                // santitize the hash.
                hash = hash.replace(/[^a-zA-Z0-9-_]/g, '');

                var $target = $( 'a#wpchr-' + hash ),
                    $container = false;

                if ( $target.length ) {
                    $container = $target.length ? $target.closest('.charitable-growth-content') : false;
                }

                // remove all css classes 'charitable-selected' from all containers.
                $('.charitable-growth-content').removeClass('charitable-selected');

                if ( $target.length ) {
                    // scroll to the target.
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 1000);
                    // after 2 seconds, slowly fade the background color to white.
                    $container.addClass('charitable-selected');
                }
            }
        },

        /**
         * Create a new campaign popup (deprecated).
         *
        */
        newCampaignPopup: function() {

            var admin_url = typeof charitable_admin !== "undefined" && typeof charitable_admin.admin_url !== "undefined" ? charitable_admin.admin_url : '/wp-admin/', // eslint-disable-line no-undef
                box_width = $(window).width() * .50;

            if ( box_width > 770 ) {
                box_width = 770;
            }

            $.confirm( {
                title: 'Create Campaign',
                content: '' +
                '<form id="create-campaign-form" method="POST" action="' + admin_url + 'admin.php?page=charitable-campaign-builder&view=template" class="formName">' +
                '<div class="form-group">' +
                '<label>Name:</label>' +
                '<input type="text" placeholder="Campaign Name" value="My New Campaign" name="campaign_name" class="name campaign_name form-control" required />' +
                '</div>' +
                '</form>',
                closeIcon: true,
                boxWidth: box_width + 'px',
                useBootstrap: false,
                type: 'create-campaign',
                animation: 'none',
                buttons: {
                    formSubmit: {
                        text: 'Create Campaign',
                        btnClass: 'btn-green',
                        action: function () {
                            var campaign_name = this.$content.find('.campaign_name').val().trim();
                            if ( ! campaign_name ){
                                $.alert('Please provide a valid campaign name.');
                                return false;
                            } else {
                                $('.jconfirm-buttons button.btn').html('Creating...');
                                $('#create-campaign-form').submit();
                                return false;
                            }
                        }
                    }
                },
                onContentReady: function () {

                }
            } );

        },

        campaignListBannerPopup: function() {

            var plugin_asset_dir = typeof charitable_admin.plugin_asset_dir !== "undefined" ? charitable_admin.plugin_asset_dir : '/wp-content/plugins/charitable/assets'; // eslint-disable-line no-undef

            $.confirm( {
                title: false,
                content: '' +
                '<div class="charitable-lite-pro-popup">' +
                    '<div class="charitable-lite-pro-popup-left" >' +
                        '<h1>The Ambassadors Extension is only available for Charitable Pro users.</h1>' +
                        '<h2>Harness the power of supporter networks and friends to reach more people and raise more money for your cause.</h2>' +
                        '<ul>' +
						'<li><p>Create a crowdfunding platform (similar to GoFundMe)</p></li>' +
                        '<li><p>Simplified fundraiser creation and management</p></li>' +
                        '<li><p>Let supporters fundraise together through our Teams feature</p></li>' +
                        '<li><p>Integrate with email marketing to follow up with campaign creators</p></li>' +
                        '<li><p>Give people a place to fundraise for their own cause</p></li>' +
                        '</ul>' +
                        '<a href="https://wpcharitable.com/lite-vs-pro/?utm_source=WordPress&utm_medium=Ambassadors+Campaign+Modal+Unlock&utm_campaign=WP+Charitable" target="_blank" class="charitable-lite-pro-popup-button">Unlock Peer-to-Peer Fundraising</a>' +
                        '<a href="https://wpcharitable.com/lite-vs-pro/?utm_source=WordPress&utm_medium=Ambassadors+Campaign+Modal+More&utm_campaign=WP+Charitable" target="_blank" class="charitable-lite-pro-popup-link">Or learn more about the Ambassadors extension &rarr;</a>' +
                    '</div>' +
                    '<div class="charitable-lite-pro-popup-right" >' +
                    '<img src="' + plugin_asset_dir + 'images/lite-to-pro/ambassador.png" alt="Charitable Ambassador Extension" >' +
                    '</img>' +
                '</div>',
                closeIcon: true,
                alignMiddle: true,
                boxWidth: '986px',
                useBootstrap: false,
                animation: 'none',
                buttons: false,
                type: 'lite-pro-ad',
                onContentReady: function () {

                }
            } );

        },

		/**
		 * Welcome activation page.
		 *
		 */
		initWelcome: function() {

			// Open modal and play video.
			$( document ).on( 'click', '#charitable-welcome .play-video', function( event ) {
				event.preventDefault();

				const video = '<div class="video-container"><iframe width="1280" height="720" src="https://www.youtube-nocookie.com/embed/834h3huzzk8?rel=0&amp;showinfo=0&amp;autoplay=1" frameborder="0" allowfullscreen></iframe></div>';

                if ( typeof jconfirm !== 'undefined' ) {

                    // jquery-confirm defaults.
                    jconfirm.defaults = {
                        closeIcon: true,
                        backgroundDismiss: false,
                        escapeKey: true,
                        animationBounce: 1,
                        useBootstrap: false,
                        theme: 'modern',
                        animateFromElement: false
                    };

                    $.dialog( {
                        title: false,
                        content: video,
                        closeIcon: true,
                        boxWidth: '1300'
                    } );

                }

			} );
		},

        /**
         * Initialize the upgrade modal.
         *
         * @since 1.8.1.15
         *
         * @return {void}
         *
        */
        initUpgradeModal: function() {

            // Upgrade information modal for upgrade links.
            $( document ).on( 'click', '.charitable-upgrade-modal', function() {

                $.alert( {
                    title        : charitable_admin.thanks_for_interest,
                    content      : charitable_admin.upgrade_modal,
                    icon         : 'fa fa-info-circle',
                    type         : 'blue',
                    boxWidth     : '550px',
                    useBootstrap : false,
                    theme        : 'modern,charitable-install-form',
                    closeIcon    : false,
                    draggable    : false,
                    buttons: {
                        confirm: {
                            text: charitable_admin.ok,
                            btnClass: 'btn-confirm',
                            keys: [ 'enter' ],
                        },
                    },
                } );
            } );

        },

    };

    // Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ); // eslint-disable-line no-undef

CharitableAdminUI.init();
