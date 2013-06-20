jQuery( document ).ready( function () {
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
} );
