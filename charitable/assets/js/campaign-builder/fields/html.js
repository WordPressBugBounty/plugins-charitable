/* global charitable_builder, wpchar, jconfirm, charitable_panel_switch, Choices, Charitable, CharitableCampaignEmbedWizard, wpCookies, tinyMCE, CharitableUtils, List */

var CharitableCampaignBuilderFieldHTML = window.CharitableCampaignBuilderFieldHTML || ( function( document, window, $ ) {

    var $builder,
        cm_editor = false,
        cm_field_ids = [];

    var app = {

            settings: {
                // spinner:          '<i class="charitable-loading-spinner"></i>',
                // spinnerInline:    '<i class="charitable-loading-spinner charitable-loading-inline"></i>',
                // tinymceDefaults:  { tinymce: { toolbar1: 'bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,link' }, quicktags: true },
                // pagebreakTop:     false,
                // pagebreakBottom:  false,
                // upload_img_modal: false,
            },

            /**
             * Start the engine.
             *
             * @since 1.0.0
             */
            init: function() {

                wpchar.debug('init', 'field-html-js');

                var that = this;

                charitable_panel_switch = true;
                s = this.settings;

                // Document ready.
                $( app.ready );

                // Page load.
                $( window ).on( 'load', function() {

                    // In the case of jQuery 3.+, we need to wait for a ready event first.
                    if ( typeof $.ready.then === 'function' ) {
                        $.ready.then( app.load );
                    } else {
                        app.load();
                    }
                } );

            },

            /**
             * Page load.
             *
             * @since 1.0.0
             * @since 1.7.9 Added `CharitableCampaignBuilderReady` hook.
             */
            ready: function() {

                wpchar.debug('ready', 'field-html-js');

                $builder = $( '#charitable-builder' );

                $builder.on( 'charitableFieldEdit', function(event, type, section, edit_field_id, field_id, field_type ) {

                    if ( field_type !== 'html' ) {
                        return;
                    }

                    if ( edit_field_id == '' && field_id !== '' ) {
                        edit_field_id = field_id;
                    }

                    if ( parseInt( edit_field_id ) > 0 && cm_field_ids.indexOf( edit_field_id ) === -1 ) {

                        wpchar.debug('init a codemirror at ' + edit_field_id, 'field-html-js');

                        app.codemirrorInit( edit_field_id );

                    }

                });

            },

            codemirrorInit( edit_field_id ) {

                var cm_editorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {},
                    field_object =  $('#charitable-panel-field-settings-field_html_html_' + edit_field_id);

                wpchar.debug( 'field_object', 'field-html-js');
                wpchar.debug( field_object, 'field-html-js');

                // check and see if this isn't already init
                if ( field_object.parent().find('.CodeMirror').length > 0 ) {
                    wpchar.debug( 'already codemirror');
                    return;
                }

                cm_editorSettings.codemirror = _.extend(
                    {},
                    cm_editorSettings.codemirror,
                    {
                        lineNumbers: true,
                        indentUnit: 2,
                        tabSize: 2,
                    }
                );

                wpchar.debug( 'charitable_codemirror_init', 'field-html-js');
                wpchar.debug( $('#charitable-panel-field-settings-field_html_html_' + edit_field_id ), 'field-html-js' );
                wpchar.debug( edit_field_id , 'field-html-js');
                //cm_editor = wp.codeEditor.initialize( $('.campaign-builder-codeeditor'), cm_editorSettings );

                field_object.css('visibility', 'hidden');
                setTimeout(function() { cm_editor = wp.codeEditor.initialize( field_object ); field_object.css('visibility', 'visible'); }, 500);
                cm_field_ids.indexOf(edit_field_id) === -1 ? cm_field_ids.push(edit_field_id) : console.log("This item already exists");


                wpchar.debug( cm_field_ids, 'field-html-js');

                $builder = $( '#charitable-builder' );

                $builder.on('keyup', '.CodeMirror-code', function(){

                    cm_editor.codemirror.save();

                    var $textarea = $('#charitable-panel-field-settings-field_html_html_' + edit_field_id ); //$( this ).closest('.charitable-panel-field').find('textarea.campaign-builder-codeeditor');

                    $textarea.trigger('change');

                    wpchar.debug( $( this ) , 'field-html-js');
                    wpchar.debug( $textarea , 'field-html-js');
                    wpchar.debug( cm_editor.codemirror.getValue() , 'field-html-js');
                    // $('#code_editor_page_head').html(cm_editor.codemirror.getValue());
                    // $('#code_editor_page_head').trigger('change');
                });

            },

        }

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

CharitableCampaignBuilderFieldHTML.init();

