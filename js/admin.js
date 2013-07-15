jQuery( document ).ready( function () {
    mdTagEditorPicture();
    mdTagEditorBatchEdit();
} );

function mdTagEditorPicture() {
    var mdCancelText = jQuery( 'p.submit a.button-cancel' ).html();
    if ( !mdCancelText ) mdCancelText = 'Cancel';
    var mdCancelReplacePicture = jQuery( '<button id="mdCancelReplacePicture_button" />' )
                                    .html( mdCancelText )
                                    .addClass( 'button' )
                                    .click( function () {
                                            jQuery( 'input#edit_tag_picture' ).val( '' ).change();
                                            return false;
                                        } )
                                    .hide();
    jQuery( 'input#edit_tag_picture' ).change( function () {
        if ( jQuery( this ).val() ) {
            jQuery( 'img.audiofile_picture' ).hide();
            jQuery( '#mdCancelReplacePicture_button' ).show();
        } else {
            jQuery( 'img.audiofile_picture' ).fadeIn();
            jQuery( '#mdCancelReplacePicture_button' ).hide();
        }
    } ).after( mdCancelReplacePicture );
    
    jQuery( 'img.audiofile_picture' ).css( {cursor:'se-resize'} ).click( function () {
        var aimg = jQuery( this );
        if ( !aimg.hasClass( 'animating' ) ) {
            aimg.addClass( 'animating' ).css( {cursor:'progress'} );
            if ( !aimg.hasClass( 'expanded' ) ) {
                aimg.addClass( 'expanded' ).animate( {width:'100%'}, function () {
                    jQuery( this ).removeClass( 'animating' ).css( {cursor:'nw-resize'} );
                } );
            } else {
                aimg.removeClass( 'expanded' ).animate( {width:'30%'}, function () {
                    jQuery( this ).removeClass( 'animating' ).css( {cursor:'se-resize'} );
                } );
            }
        }
    } );
}

function mdTagEditorBatchEdit() {
    jQuery( 'table.widefat.batchedit *[name^="edit_tag_"]' ).prop( 'disabled', 'disabled' ).addClass( 'disabled' );
    jQuery( 'table.widefat.batchedit input[type="checkbox"][name="enable_tag[]"]' ).change( function () {
        var tagname = jQuery( this ).val();
        if ( 'user_text' == tagname ) {
            var richarea = tinyMCE.get( 'edit_tag_user_text' );
            richarea.getBody().setAttribute( 'contenteditable', this.checked );
            richarea.getBody().style.backgroundColor = this.checked ? 'transparent' : '#CCC';
            for ( i in richarea.controlManager.controls ) {
                var richprop = richarea.controlManager.controls[i];
                if ( richprop ) richprop.setDisabled( !this.checked );
            }
        } else {
            var tagfield = jQuery( '*[name^="edit_tag_' + tagname + '"]', jQuery( this ).parents( 'table.widefat.batchedit' ) );
            if ( this.checked ) {
                tagfield.prop( 'disabled', null ).removeClass( 'disabled' ).focus();
            } else {
                tagfield.prop( 'disabled', 'disabled' ).addClass( 'disabled' );
            }
        }
    } );
    setTimeout( 'jQuery( \'table.widefat.batchedit input[type="checkbox"][name="enable_tag[]"][value="user_text"]\' ).change()', 500 );
}
