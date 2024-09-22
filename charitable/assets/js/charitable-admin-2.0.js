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

        },

        scrollToAnchor: function() {
            // get the hash from the url.
            var hash = window.location.hash;
            hash = hash.substring(1);

            if ( hash ) {
                var $target = $( 'a#wpchr-' + hash ),
                    $container = $target.length ? $target.closest('.charitable-growth-content') : false;

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
