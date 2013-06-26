<?php

require_once( 'getid3/getid3.php' );
require_once( 'getid3/write.php' );
if( !function_exists( 'Markdown' ) ) include_once( 'markdown/markdown.php' );

global $mdtags, $mdsortingfields;

$mdfile = array_key_exists( 'mdfile', $_REQUEST ) ? $_REQUEST[ 'mdfile' ] : '';

$mdfolder = array_key_exists( 'mdfolder', $_REQUEST ) ? str_replace( '../', '/', $_REQUEST[ 'mdfolder' ] ) : '';
if ( '/' == substr( $mdfolder, 0, 1 ) ) $mdfolder = substr( $mdfolder, 1 );

// MP3 folder
$mdir = '/' . get_option( 'mp3folder' );
// MP3 folder URL
$murl = get_option( 'siteurl' ) . $mdir;
// MP3 folder relative URL
$mrelative = str_replace('http'.(isset($_SERVER['HTTPS'])?'s':'').'://','',$murl); $mrelative = explode( '/', $mrelative ); array_shift($mrelative); $mrelative = '/'.implode('/', $mrelative);
$mpath = ABSPATH . substr($mdir, 1);

// Should we re-encode the tags?
$mdoencode = get_option( 'tagencoding' );
if ( !$mdoencode ) $mdoencode = 'UTF-8';

// Should we re-encode the file names?
$mdofnencode = get_option( 'filenameencoding' );
if ( !$mdofnencode ) $mdofnencode = 'UTF-8';

// How should we sort the files?
$msort = get_option( 'sortfiles' );
// "Backward compatibilaziness": it used to be a boolean value
if ( isset( $msort ) && !array_key_exists( $msort . '', $mdsortingfields ) ) $msort = 'title';

// Should the sorting be reversed?
$mreverse = ( get_option( 'reversefiles' ) == true );

$errors = array();
$ifiles = array();
$idirs = array();
$ipath = $mpath . '/' . $mdfolder;
if ( $mdofnencode != 'UTF-8' ) $ipath = iconv( $mdofnencode, 'UTF-8', $ipath );

$mdbreadcrumbs = '<a href="' . add_query_arg( array( 'mdfolder' => null, 'mdfile' => null ) ) . '">' . $mdir . '</a>';
$buildlevels = array();
if ( trim( $mdfolder ) ) foreach ( explode( '/', $mdfolder ) as $mdlevel ) :
    $buildlevels[] = $mdlevel;
    $mdbreadcrumbs .= ' <a href="' . add_query_arg( array( 'mdfolder' => implode( '/', $buildlevels ), 'mdfile' => null ) ) . '">/' . $mdlevel . '</a>';
endforeach;

?>

<div class="wrap">

    <?php include('mediadownloader-options-header.php'); ?>

    <?php if ( $mdfile ) :

        $mdaction = array_key_exists( 'mdaction', $_REQUEST ) ? $_REQUEST[ 'mdaction' ] : '';
        if ( 'updatefile' == $mdaction ) {
            $getID3 = new getID3;
            $getID3->setOption( array( 'encoding' => $mdoencode ) );
            $tagwriter = new getid3_writetags;
            $ThisFileInfo = $getID3->analyze( $mpath . '/' . $mdfolder . '/' . $mdfile );
            $tags = $ThisFileInfo['tags']['id3v2'];
            if ( is_array( $tags ) ) array_walk( $tags, 'array_sobe_nivel' );
            foreach ( array( 'title', 'artist', 'band', 'album', 'year', 'genre', 'track_number', 'comment', 'user_text' ) as $atag ) if ( array_key_exists( 'edit_tag_' . $atag, $_POST ) ) $tags[ $atag ] = stripslashes( $_POST[ 'edit_tag_' . $atag ] );
            if ( array_key_exists( 'edit_tag_recording_dates', $_POST ) && $_POST['edit_tag_recording_dates'] ) {
                if ( $rd_timestamp = strtotime( $_POST['edit_tag_recording_dates'] ) ) {
                    $tags['recording_dates'] = date( 'Y-m-d', $rd_timestamp );
                    $tags['original_release_year'] = date( 'Y', $rd_timestamp );
                }
            } else {
                $tags['recording_dates'] = '';
            }

            $tagwriter->filename = $mpath . '/' . $mdfolder . '/' . stripslashes( $mdfile );
            $tagwriter->tagformats = array('id3v2.3');
            $tagwriter->overwrite_tags = true;
            $tagwriter->tag_encoding = $mdoencode;
            $tagwriter->remove_other_tags = true;

            // populate data array
            $TagData = array(
	            'title'         => array( $tags['title'] ),
	            'artist'        => array( $tags['artist'] ),
	            'band'          => array( $tags['band'] ),
	            'album'         => array( $tags['album'] ),
	            'year'          => array( $tags['year'] ),
	            'genre'         => array( $tags['genre'] ),
	            'comment'       => array( $tags['comment'] ),
	            'user_text'     => array( $tags['user_text'] ),
	            'track'         => array( intval( '0' . $tags['track_number'] ) ),
    	        'recording_dates' => array( $tags['recording_dates'] ),
            );

            if ( array_key_exists( 'comments', $ThisFileInfo ) && is_array( $ThisFileInfo['comments'] ) && array_key_exists( 'picture', $ThisFileInfo['comments'] ) && is_array( $ThisFileInfo['comments']['picture'] ) && is_array( $ThisFileInfo['comments']['picture'][0] ) && array_key_exists( 'data', $ThisFileInfo['comments']['picture'][0] ) ) :
                $TagData['attached_picture'] = array(
	                                        array(
	                                                'data' => $ThisFileInfo['comments']['picture'][0]['data'],
	                                                'picturetypeid' => 3,
	                                                'description' => $tags['title'],
	                                                'mime' => $ThisFileInfo['comments']['picture'][0]['image_mime'],
	                                            ),
	                                    );
	        endif;

            $picwarnings = array();
            if ( is_array( $_FILES ) && array_key_exists( 'edit_tag_picture', $_FILES ) ) :
                $uploadedfile = $_FILES['edit_tag_picture'];
                if ( is_array( $uploadedfile ) && ( ( !array_key_exists( 'error', $uploadedfile ) ) || !$uploadedfile['error'] ) && array_key_exists( 'tmp_name', $uploadedfile ) && is_file( $uploadedfile['tmp_name'] ) && is_readable( $uploadedfile['tmp_name'] ) ) :
                    if ( strpos( $uploadedfile['type'], 'image' ) === 0 ) :
                        $filedata = file_get_contents( $uploadedfile['tmp_name'] );
                        $TagData['attached_picture'] = array(
	                                                        array(
	                                                                'data' => $filedata,
	                                                                'picturetypeid' => 3,
	                                                                'description' => $uploadedfile['name'],
	                                                                'mime' => $uploadedfile['type'],
	                                                            ),
	                                                    );
	                else :
    	                $picwarnings[] = sprintf( _md( 'File type not allowed: <code>%s</code>' ), $uploadedfile['type'] );
	                endif;
	            elseif ( is_array( $uploadedfile ) && array_key_exists( 'error', $uploadedfile ) && array_key_exists( 'name', $uploadedfile ) && $uploadedfile['name'] ) :
	                $responses = array(
                        'err_' . UPLOAD_ERR_OK => 'There is no error, the file uploaded with success.',
                        'err_' . UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                        'err_' . UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                        'err_' . UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                        'err_' . UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        'err_' . UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.',
                        'err_' . UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Introduced in PHP 5.1.0.',
                        'err_' . UPLOAD_ERR_EXTENSION => 'File upload stopped by extension. Introduced in PHP 5.2.0.',
                    );
                    $ekey = 'err_' . $uploadedfile['error'];
	                $picwarnings[] = _md( array_key_exists( $ekey, $responses ) ? $responses[ $ekey ] : 'Unknown upload error.' );
	            endif;
            endif;

            $tagwriter->tag_data = $TagData;

            $ret = array();
            // write tags
            if ( $tagwriter->WriteTags() ) {
	            $ret[] = '<strong>' . _md( 'File saved!' ) . '</strong><br /><a href="' . add_query_arg( array( 'mdfile' => null ) ) . '">' . _md( '&larr; Back' ) . '</a>';
	            $allwarnings = $tagwriter->warnings;
	            if ( !empty( $picwarnings ) ) $allwarnings = array_merge( $allwarnings, $picwarnings );
	            if ( !empty( $allwarnings ) ) {
		            $ret[] = '<p><em>' . _md( 'Warnings:' ) . '</em></p> <ul><li>' . implode( '</li><li>', $allwarnings ) . '</li></ul>';
	            }
            } else {
	            $ret[] = '<strong>' . _md( 'Failed!' ) . '</strong><br />';
	            $ret[] = '<p><em>' . _md( 'Errors:' ) . '</em></p><ul><li>' . implode( '</li><li>', $tagwriter->errors ) . '</li></ul>';
            }
            echo '<div id="setting-error-settings_updated" class="updated settings-error"><p>' . implode( "\n", $ret ) . '</p></div>';
        }

        $ifile = explode( '.', $mdfile );
        $iext = array_pop( $ifile );
        $ifile = implode( '.', $ifile );
        $finfo = mediadownloaderFileInfo( $mdir . '/' . $mdfolder . '/' . stripslashes( $ifile ), $iext );
        if ( $finfo ) :
            $ftags = array();
            foreach ( array( 'id3v2', 'quicktime', 'ogg', 'asf', 'flac', 'real', 'riff', 'ape', 'id3v1', 'comments' ) as $poss ) {
                if ( array_key_exists( 'tags', $finfo ) && array_key_exists( $poss, $finfo['tags'] ) ) {
                    $ftags = array_merge( $finfo['tags'][$poss], $ftags );
                    if ( array_key_exists( 'comments', $finfo['tags'][$poss] ) ) {
                        $ftags = array_merge( $finfo['tags'][$poss]['comments'], $ftags );
                    }
                }
            }
            $ftags['user_text'] = array_key_exists( 'text', $ftags ) ? $ftags['text'] : $ftags['comment'];
            
            $current_img = '';
            if ( array_key_exists( 'comments', $finfo ) && is_array( $finfo['comments'] ) && array_key_exists( 'picture', $finfo['comments'] ) && is_array( $finfo['comments']['picture'] ) && is_array( $finfo['comments']['picture'][0] ) && array_key_exists( 'data', $finfo['comments']['picture'][0] ) )
                $current_img = $finfo['comments']['picture'][0]['data'];
            
            unset( $finfo );
            ?>
            <h3><?php printf( _md( 'Editing File: <code>%s</code>' ), $mdbreadcrumbs . ' /' . stripslashes( $mdfile ) ); ?></h3>
            
            <form method="post" action="<?php self_link(); ?>" enctype="multipart/form-data">
            <input type="hidden" name="mdaction" value="updatefile" />
            <table class="widefat">
            <tbody>
                <?php
                $edit_tags = array(
                                    'title' => array( 'Title', 'text' ),
                                    'artist' => array( 'Artist', 'text' ),
                                    'album' => array( 'Album', 'text' ),
                                    'year' => array( 'Year', 'number' ),
                                    'genre' => array( 'Genre', 'genre' ),
                                    'comment' => array( 'Comment', 'textarea' ),
                                    'user_text' => array( 'Formatted Comment', 'richtext' ),
                                    'recording_dates' => array( 'Recording Date', 'date' ),
                                    'track_number' => array( 'Track Number', 'number' ),
                                );
                foreach ( $edit_tags as $mdtag => $tagoptions ) :
                    $tagvalue = stripslashes( $ftags[ $mdtag ][0] );
                    if ( $mdoencode != 'UTF-8' ) $tagvalue = iconv( $mdoencode, 'UTF-8', $tagvalue );
                    ?>
                    <tr>
                        <td>
                            <label for="edit_tag_<?php echo $mdtag; ?>">
                                <?php _mde( $tagoptions[0] ); ?>
                            </label>
                        </td>
                        <td>
                            <?php if ( 'genre' == $tagoptions[1] ) : ?>
                                <select id="edit_tag_<?php echo $mdtag; ?>" name="edit_tag_<?php echo $mdtag; ?>">
                                    <?php foreach ( list_genres() as $lgenre ) : ?>
                                        <option value="<?php echo esc_attr( $lgenre ); ?>"<?php if ( $tagvalue == $lgenre ) echo ' selected="selected"'; ?>><?php echo htmlentities( $lgenre, ENT_COMPAT, 'UTF-8' ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ( 'textarea' == $tagoptions[1] ) : ?>
                                <textarea id="edit_tag_<?php echo $mdtag; ?>" name="edit_tag_<?php echo $mdtag; ?>" cols="150" rows="10" class="widefat"><?php echo $tagvalue; ?></textarea>
                            <?php elseif ( 'richtext' == $tagoptions[1] ) : ?>
                                <?php wp_editor( $tagvalue, 'edit_tag_' . $mdtag, array( 'media_buttons' => false, 'textarea_rows' => 10, 'quicktags' => false ) ); ?>
                            <?php elseif ( 'text' == $tagoptions[1] ) : ?>
                                <input id="edit_tag_<?php echo $mdtag; ?>" name="edit_tag_<?php echo $mdtag; ?>" type="text" size="150" class="widefat" value="<?php echo esc_attr( $tagvalue ); ?>" />
                            <?php elseif ( 'number' == $tagoptions[1] ) : ?>
                                <input id="edit_tag_<?php echo $mdtag; ?>" name="edit_tag_<?php echo $mdtag; ?>" type="number" size="4" class="small-text" value="<?php echo esc_attr( $tagvalue ); ?>" />
                            <?php else : ?>
                                <input id="edit_tag_<?php echo $mdtag; ?>" name="edit_tag_<?php echo $mdtag; ?>" type="<?php echo $tagoptions[1]; ?>" class="medium-text feature-filter" value="<?php echo $tagvalue; ?>" />
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td>
                        <label for="edit_tag_picture">
                            <?php _mde( 'Picture:' ); ?>
                        </label>
                    </td>
                    <td>
                        <?php if ( $current_img ) : ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode( $current_img ); ?>" class="audiofile_picture" alt="<?php echo esc_attr( stripslashes( $ftags[ 'title' ][0] ) ); ?>" />
                            <label for="edit_tag_picture">
                                <?php _mde( 'Replace picture:' ); ?>
                            </label>
                        <?php endif; ?>
                        <input type="file" id="edit_tag_picture" name="edit_tag_picture" accept="image/*" />
                    </td>
                </tr>
            </tbody>
            </table>
            
            <p class="submit">
            <input type="submit" value="<?php _mde( 'Save Changes' ) ;?>" class="button button-primary" />
            <a href="<?php echo add_query_arg( array( 'mdfile' => null ) );?>" class="button alignright button-cancel"><?php _mde( 'Cancel' ) ;?></a>
            </p>
            </fieldset>

            </form>
        <?php else: ?>
            <div class="error settings-error"><p><strong><?php printf( _md( 'Could not read: <code>%s</code>' ), $mdfolder . '/' . stripslashes( $mdfile ) ); ?></strong></p></div>
        <?php endif; ?>

    <?php else :
        // Populating arrays with respective files
        if ( is_dir( $ipath ) && is_readable( $ipath ) ) {
            $idir = dir( $ipath );
            while ( false !== ( $ifile = $idir->read() ) ) {
                if ( ( '.' != $ifile ) && ( '..' != $ifile ) ) {
                    if ( $mdofnencode != 'UTF-8' ) $ifile = iconv( $mdofnencode, 'UTF-8', $ifile );
                    if ( !is_dir( $ipath . '/' . $ifile ) ) {
                        $ifiles[] = $ifile;
                    } else {
                        $idirs[] = $ifile;
                    }
                }
            }
        } else {
            $errors[] = sprintf( _md( 'Could not read: <code>%s</code>' ), $ipath );
        }

        // If set, sorting array
        if ( $msort != 'none' ) {
            sort( $ifiles );
            uasort( $ifiles, $mdsortingfields[$msort] );
            sort( $idirs );
        }
        // If set, reversing array
        if ( $mreverse ) $ifiles = array_reverse( $ifiles );
        
        $iall = array_merge( $idirs, $ifiles );

        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option( 'per_page', 'option' );
        $ipp = get_user_meta( $user, $option, true );
        if ( empty ( $ipp ) || $ipp < 1 ) $ipp = $screen->get_option( 'per_page', 'default' );
        $ipages = ceil( count( $iall ) / $ipp );
        $ipaged = array_key_exists( 'paged', $_REQUEST ) ? intval( $_REQUEST['paged'] ) : 1;
        if ( $ipaged < 1 || $ipaged > $ipages ) $ipaged = 1;
        $pageditems = array_slice( $iall, ( $ipp * ( $ipaged - 1 ) ), $ipp );

        ?>

        <?php if ( count( $iall ) ) : ?>

            <form method="get" action="?">
                <?php
                $furl = explode( '?', add_query_arg( array( 'paged' => null ) ) );
                array_shift( $furl );
                $furl = implode( '?', $furl );
                foreach ( explode( '&', $furl ) as $parm ) : $parm = explode( '=', $parm ); ?>
                    <input type="hidden" name="<?php echo array_shift( $parm ); ?>" <?php if ( count( $parm ) ) : ?>value="<?php echo implode( '=', $parm ); ?>" <?php endif; ?>/>
                <?php endforeach; ?>
                <div class="tablenav top">
                <div class="alignleft">
                    <h3><?php printf( _md( 'Directory: <code>%s</code>' ), $mdbreadcrumbs ); ?></h3>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf( _md( '%d items' ), count( $iall ) ); ?></span>
                    <?php if ( $ipages > 1 ) : ?>
                        <span class="pagination-links"><a class="first-page<?php if ( $ipaged <= 1 ) : ?> disabled<?php endif; ?>" title="<?php _mde( 'Go to first page' ); ?>" href="<?php echo add_query_arg( array( 'paged' => null ) ); ?>">«</a>
                        <a class="prev-page<?php if ( $ipaged <= 1 ) : ?> disabled<?php endif; ?>" title="<?php _mde( 'Go to previous page' ); ?>" href="<?php echo add_query_arg( array( 'paged' => $ipaged > 1 ? $ipaged - 1 : null ) ); ?>">‹</a>
                        <span class="paging-input"><?php printf( _md( '%s of <span class="total-pages">%d</span>' ), '<input class="current-page" title="' . _md( 'Current page' ) . '" type="text" name="paged" value="' . $ipaged . '" size="1">', $ipages ); ?></span>
                        <a class="next-page<?php if ( $ipaged >= $ipages ) : ?> disabled<?php endif; ?>" title="<?php _mde( 'Go to next page' ); ?>" href="<?php echo add_query_arg( array( 'paged' => min( $ipaged + 1, $ipages ) ) ); ?>">›</a>
                        <a class="last-page<?php if ( $ipaged >= $ipages ) : ?> disabled<?php endif; ?>" title="<?php _mde( 'Go to last page' ); ?>" href="<?php echo add_query_arg( array( 'paged' => $ipages ) ); ?>">»</a></span>
                    <?php endif; ?>
                </div>
                </div>
            </form>

            <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col"><?php _mde( 'Name' ); ?></th>
                        <th scope="col"><?php _mde( 'Size' ); ?></th>
                        <th scope="col"><?php _mde( 'Modification Time' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( trim( str_replace( '/', '', $mdfolder ) ) ) : ?>
                        <tr>
                            <td class="dirlink"><a href="<?php echo add_query_arg( array( 'mdfolder' => implode( '/', array_slice( explode( '/', $mdfolder ), 0, -1 ) ) ) ); ?>"><?php echo '../'; ?></a></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ( $pageditems as $ifile ) :
                        if ( is_dir( $ipath . '/' . $ifile ) ) : ?>
                            <tr>
                                <td class="dirlink"><a href="<?php echo add_query_arg( array( 'mdfolder' => $mdfolder . '/' . $ifile ) ); ?>"><?php echo './' . $ifile; ?></a></td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        <?php else :
                            $arrfile = explode( '.', $ifile );
                            $fext = '';
                            if ( count( $arrfile ) > 1 ) $fext = array_pop( $arrfile );
                            if ( in_array( $fext, md_mediaExtensions() ) ) :
                                ?>
                                <tr>
                                    <td><a href="<?php echo add_query_arg( array( 'mdfile' => $ifile ) ); ?>"><?php echo $ifile; ?></a></td>
                                    <td><?php echo byte_convert( filesize( $ipath . '/' . $ifile ) ); ?></td>
                                    <td><?php $timemask = sprintf( _md( '%1$s \a\t %2$s' ), get_option('date_format'), get_option('time_format') ); echo date_i18n( $timemask, filemtime( $ipath . '/' . $ifile ) ); ?></td>                    
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><?php echo $ifile; ?></td>
                                    <td><?php echo byte_convert( filesize( $ipath . '/' . $ifile ) ); ?></td>
                                    <td><?php $timemask = sprintf( _md( '%1$s \a\t %2$s' ), get_option('date_format'), get_option('time_format') ); echo date_i18n( $timemask, filemtime( $ipath . '/' . $ifile ) ); ?></td>                    
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h3><?php printf( _md( 'Directory: <code>%s</code>' ), $mdbreadcrumbs ); ?></h3>
            <div class="error settings-error"><p><strong><?php _mde( 'Empty directory?' ); ?></strong></p></div>
        <?php endif; ?>

    <?php endif; ?>

</div>


<?php

function array_sobe_nivel( &$arr, $key ) {
    if ( is_array( $arr ) ) $arr = $arr[ array_pop( array_keys( $arr ) ) ];
}

function list_genres() {
    return explode( ',', 'Unknown,Blues,Classic Rock,Country,Dance,Disco,Funk,Grunge,Hip-Hop,Jazz,Metal,New Age,Oldies,Other,Pop,R&B,Rap,Reggae,Rock,Techno,Industrial,Alternative,Ska,Death Metal,Pranks,Soundtrack,Euro-Techno,Ambient,Trip-Hop,Vocal,Jazz+Funk,Fusion,Trance,Classical,Instrumental,Acid,House,Game,Sound Clip,Gospel,Noise,Alternative Rock,Bass,Soul,Punk,Space,Meditative,Instrumental Pop,Instrumental Rock,Ethnic,Gothic,Darkwave,Techno-Industrial,Electronic,Pop-Folk,Eurodance,Dream,Southern Rock,Comedy,Cult,Gangsta,Top 40,Christian Rap,Pop/Funk,Jungle,Native US,Cabaret,New Wave,Psychadelic,Rave,Showtunes,Trailer,Lo-Fi,Tribal,Acid Punk,Acid Jazz,Polka,Retro,Musical,Rock & Roll,Hard Rock,Folk,Folk-Rock,National Folk,Swing,Fast Fusion,Bebob,Latin,Revival,Celtic,Bluegrass,Avantgarde,Gothic Rock,Progressive Rock,Psychedelic Rock,Symphonic Rock,Slow Rock,Big Band,Chorus,Easy Listening,Acoustic,Humour,Speech,Chanson,Opera,Chamber Music,Sonata,Symphony,Booty Bass,Primus,Porn Groove,Satire,Slow Jam,Club,Tango,Samba,Folklore,Ballad,Power Ballad,Rhythmic Soul,Freestyle,Duet,Punk Rock,Drum Solo,Acapella,Euro-House,Dance Hall,Goa,Drum & Bass,Club - House,Hardcore,Terror,Indie,BritPop,Negerpunk,Polsk Punk,Beat,Christian Gangsta Rap,Heavy Metal,Black Metal,Crossover,Contemporary Christian,Christian Rock,Merengue,Salsa,Thrash Metal,Anime,JPop,Synthpop' );
}

?>
