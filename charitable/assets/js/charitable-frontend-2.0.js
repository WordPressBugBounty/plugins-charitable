( function( $ ) {

    // Clickable Tabs
    $('.charitable-campaign-nav').on( 'click', 'li.tab_title a', function( e ) {

        e.preventDefault();

        var $preview = $( '.charitable-campaign-preview' ),
            tab_id = $( this ).parent().data('tab-id'),
            tab_type = $preview.find( 'li#tab_' + tab_id + '_title').attr( 'data-tab-type' ); // eslint-disable-line

        // clear the active states of the tabs and content areas in the preview area
        $('.tab-content ul li').removeClass('active');
        $('nav li.tab_title').removeClass('active');

        // make the clicked on tab and it's content area active
        $( this ).parent().addClass('active');
        $('.tab-content ul li#tab_' + tab_id + '_content').addClass('active');

        // now clear any of the left option panel 'tabs' from being active... then add active to the proper one
        $('.charitable-layout-options-tab .charitable-group').removeClass('active');
        $(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').addClass('active').removeClass('charitable-closed');
        $(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').find('.charitable-group-rows').show();
        $(".charitable-layout-options-tab").find('[data-group_id=' + tab_id + ']').find('.charitable-toggleable-group i').removeClass('.charitable-angle-right').addClass('charitable-angle-down');
        $( '#layout-options a' ).click();

    } );

    // Clickable Donate Amount
    $('.charitable-campaign-field').on( 'click', 'ul.charitable-template-donation-amounts li', function( e ) { // eslint-disable-line

        // do the UI inside the donate field itself on the frontend.
        $( this ).parent().find('li').removeClass('selected');
        $( this ).addClass('selected');
        $( this ).find('input[type="radio"]').prop("checked", true);

        if ( $( this ).hasClass('custom-donation-amount' ) ) { // eslint-disable-line no-undef

        } else {

            const donationAmountSelected = $( this ).find( 'input[type="radio"]' ).val(); // example: 10.00

            // update any donate button in the <header> of this campaign form to reflect the selected amount
            $ (this ).closest('.charitable-campaign-container').find('form.campaign-donation input[name="charitable_donation_amount"]').val( donationAmountSelected );

        }
    } );

    $('.charitable-campaign-field').on( 'keyup', 'ul.charitable-template-donation-amounts input[name="custom_donation_amount"]', function( e ) { // eslint-disable-line

        $( this ).closest('.charitable-campaign-container').find('form.campaign-donation input[name="charitable_donation_amount"]').val( $( this ).val() );

    } );

    // Sharing that requires JS.

        // Grab link from the DOM
        const mastodonShareButton = document.querySelector('.charitable-mastodon-share');

        if ( mastodonShareButton !== null ) {

            // When a user clicks the link
            mastodonShareButton.addEventListener('click', (e) => {

                // If the user has already entered their instance and it is in localstorage
                // write out the link href with the instance and the current page title and URL
                if(localStorage.getItem('mastodon-instance')) {
                    mastodonShareButton.href = `
                    https://${localStorage.getItem('mastodon-instance')}/share?text=${encodeURIComponent(document.title)}%0A${encodeURIComponent(location.href)}`;
                // otherwise, prompt the user for their instance and save it to localstorage
                } else {
                    e.preventDefault();
                    let instance = window.prompt(
                    'Please tell me your Mastodon instance'
                    );
                    localStorage.setItem('mastodon-instance', instance);
                }

            });

        }

})( jQuery ); // eslint-disable-line

