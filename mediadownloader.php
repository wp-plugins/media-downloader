<?php
/*
Plugin Name: Media Downloader
Plugin URI: http://ederson.peka.nom.br
Description: Media Downloader plugin lists MP3 files from a folder by replacing the [media] smarttag.
Version: 0.1.97
Author: Ederson Peka
Author URI: http://ederson.peka.nom.br
*/

// Possible encodings (dealing with only two so far)
$mdencodings = array( 'UTF-8', 'ISO-8859-1' );
// Possible fields by which file list should be sorted,
// and respective sorting functions
$mdsortingfields = array(
    'none' => null,
    'title' => 'orderByTitle',
    'file date' => 'orderByFileDate',
    'track number' => 'orderByTrackNumber',
    'album' => 'orderByAlbum',
    'artist' => 'orderByArtist',
    'file size' => 'orderByFileSize',
);
// Settings and respective sanitize functions
$mdsettings = array(
    'mp3folder' => 'sanitizeRDir',
    'sortfiles' => 'sanitizeSortingField',
    'reversefiles' => 'sanitizeBoolean',
    'showtags' => null,
    'customcss' => null,
    'removeextension' => 'sanitizeBoolean',
    'embedplayer' => 'sanitizeBoolean',
    'embedwhere' => 'sanitizeBeforeAfter',
    'tagencoding' => 'sanitizeTagEncoding',
    'filenameencoding' => 'sanitizeTagEncoding',
    'cachedir' => 'sanitizeWDir',
    'scriptinfooter' => 'sanitizeBoolean'
);
// Possible ID3 tags
$mdtags = array( 'title', 'artist', 'album', 'year', 'genre', 'comments', 'track_number', 'bitrate', 'filesize', 'filedate', 'directory', 'file' );

// Markup settings and respective sanitize functions
$mdmarkupsettings = array(
    'downloadtext' => null,
    'playtext' => null,
    'replaceheaders' => null,
);

// Default player colors
$mdembedplayerdefaultcolors = array(
    'bg' => 'E7E7E7',
    'text' => '333333',
    'leftbg' => 'CCCCCC',
    'lefticon' => '333333',
    'volslider' => '666666',
    'voltrack' => 'FFFFFF',
    'rightbg' => 'B4B4B4',
    'rightbghover' => '999999',
    'righticon' => '333333',
    'righticonhover' => 'FFFFFF',
    'track' => 'FFFFFF',
    'loader' => 'A2CC39',
    'border' => 'CCCCCC',
    'tracker' => 'DDDDDD',
    'skip' => '666666',
);

// Pre-2.6 compatibility ( From: http://codex.wordpress.org/Determining_Plugin_and_Content_Directories )
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

// MarkDown, used for text formatting
if( !function_exists( 'Markdown' ) ) include_once( "markdown/markdown.php" );

// Friendly file size
if( !function_exists( 'byte_convert' ) ){
    function byte_convert($bytes){
        $symbol = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        $exp = 0;
        $converted_value = 0;
        if( $bytes > 0 )
        {
          $exp = floor( log($bytes)/log(1024) );
          $converted_value = ( $bytes/pow(1024,floor($exp)) );
        }

        return sprintf( '%.2f '.$symbol[$exp], $converted_value );
    }
}

// Scans an array of strings searching for a common prefix in all items
function calculatePrefix($arr){
    $prefix = '';
    if ( count( $arr ) ) {
        $prefix = strip_tags( array_pop( $arr ) );
        foreach ( $arr as $i ) {
            for ( $c=1; $c<strlen($i); $c++ ) {
                if ( strncasecmp( $prefix, $i, $c ) != 0 ) break;
            }
            $prefix = substr( $prefix, 0, $c-1 );
        }
    }
    return $prefix;
}

function replaceUnderscores( $t ) {
    if ( $t && false === strpos(' ', $t) ) {
        if ( false === strpos('_', $t) ) $t = str_replace( '-', '_', $t ) ;
        $t = preg_replace( '/_(_+)/i', ' - ', $t );
        $t = str_replace( '_', ' ', $t ) ;
    }
    return $t ;
}

// Searches post content for our smarttag and do all the magic
function listMedia( $t ){
    global $mdtags, $tagvalues, $mdsortingfields;
    $errors = array();

    // MP3 folder
    $mdir = '/' . get_option( 'mp3folder' );
    // MP3 folder URL
    $murl = get_option( 'siteurl' ) . $mdir;
    // MP3 folder relative URL
    $mrelative = str_replace('http://','',$murl); $mrelative = explode( '/', $mrelative ); array_shift($mrelative); $mrelative = '/'.implode('/', $mrelative);
    $mpath = ABSPATH . substr($mdir, 1);
    
    // Player position (before or after download link)
    $membedwhere = get_option( 'embedwhere' ) ;

    // Should we re-encode the tags?
    $mdoencode = ( get_option( 'tagencoding' ) != 'UTF-8' ) ;

    // Should we re-encode the file names?
    $mdofnencode = ( get_option( 'filenameencoding' ) != 'UTF-8' ) ;
    
    // How should we sort the files?
    $msort = get_option( 'sortfiles' );
    // "Backward compatibilaziness": it used to be a boolean value
    if ( isset( $msort ) && !array_key_exists( $msort, $mdsortingfields ) ) $msort = 'title';

    // Should the sorting be reversed?
    $mreverse = ( get_option( 'reversefiles' ) == true ) ;

    // Which tags to show?
    $mshowtags = array_intersect( explode( ',', get_option( 'showtags' ) ), $mdtags ) ;
    // If none, shows the first tag (title)
    if ( !count($mshowtags) ) $mshowtags = array( $mdtags[0] ) ;
    
    // Markup options
    $downloadtext = get_option( 'downloadtext' );
    $playtext = get_option( 'playtext' );
    $replaceheaders = array();
    $arrreplaceheaders = explode( "\n", trim( get_option( 'replaceheaders' ) ) );
    foreach ( $arrreplaceheaders as $line ) {
        $arrline = explode( ':', trim( $line ) );
        if ( count( $arrline ) >= 2 ) $replaceheaders[ trim( array_shift( $arrline ) ) ] = implode( ':', $arrline );
    }

    // Searching for our smarttags
    $t = preg_replace( '/<p>\[media:([^\]]*)\]<\/p>/i', '[media:$1]', $t );
    preg_match_all( '/\[media:([^\]]*)\]/i', $t, $matches );
    // Any?
    if ( count( $matches ) ) {
        // Each...
        foreach ( $matches[1] as $folder ) {
            // Encoding folder name
            $pfolder = array_filter( explode( '/', $folder ) );
            foreach( $pfolder as &$p ) $p = rawurlencode( $p );
            $ufolder = implode( '/', $pfolder );
            // Removing paragraph
            $t = str_replace('<p>[media:'.$folder.']</p>', '[media:'.$folder.']', $t);
            // Initializing variables
            $ihtml = '';
            $ifiles = array();
            $ititles = array();
            $zip = array();
            $pdf = array();
            $epub = array();
            $ipath = $mpath . '/' . $folder;
            // Populating arrays with respective files
            if ( is_dir( $ipath ) ) {
                if ( is_readable( $ipath ) ) {
                    $idir = dir( $ipath );
                    while (false !== ($ifile = $idir->read())) {
                        if ( substr( $ifile, -4 ) == '.mp3' ) $ifiles[] = substr( $ifile, 0, -4 );
                        if ( substr( $ifile, -4 ) == '.zip' ) $zip[] = $ifile;
                        if ( substr( $ifile, -4 ) == '.pdf' ) $pdf[] = $ifile;
                        if ( substr( $ifile, -5 ) == '.epub' ) $epub[] = $ifile;
                    }
                } else {
                    $errors[] = _md( 'Could not read: ' . $ipath );
                }
            }
            // Any MP3 file?
            if ( count( $ifiles ) ) {
                // Calculating file "prefixes"
                $prefix = calculatePrefix( $ifiles ) ;
                $hlevel = explode( '/', $folder ) ; $hlevel = array_pop( $hlevel ) ;

                // Initializing array of tag values
                $tagvalues = array() ;
                foreach ( $mshowtags as $mshowtag ) $tagvalues[$mshowtag] = array() ;
                $alltags = array();
                foreach ( $ifiles as $ifile ) {
                    // Getting ID3 info
                    $finfo = mediadownloaderMP3Info( $mrelative.'/'.$folder.'/'.$ifile ) ;
                    // Loading all possible tags
                    $ftags = $finfo['tags']['id3v2'] ;
                    $ftags['bitrate'] = array( floatval( $finfo['audio']['bitrate'] ) / 1000 . 'kbps' ) ;
                    $ftags['filesize'] = array( byte_convert( $finfo['filesize'] ) ) ;
                    $ftags['filedate'] = array( date_i18n( get_option('date_format'), filemtime( $finfo['filepath'] . '/' . $finfo['filename'] ) ) ) ;
                    $ftags['directory'] = array( $hlevel ) ;
                    $ftags['file'] = array( $ifile ) ;
                    $alltags[$ifile] = $ftags;
                    // Populating array of tag values with selected tags
                    foreach ( $mshowtags as $mshowtag )
                        $tagvalues[$mshowtag][$ifile] = ( 'comments' == $mshowtag ) ? Markdown( $ftags[$mshowtag][0] ) : $ftags[$mshowtag][0] ;
                }
                // Calculating tag "prefixes"
                $tagprefixes = array() ;
                foreach ( $mshowtags as $mshowtag )
                    if ( 'file' == $mshowtag || 'title' == $mshowtag )
                        $tagprefixes[$mshowtag] = calculatePrefix( $tagvalues[$mshowtag] );
                // If set, sorting array
                if ( $msort != 'none' ) {
                    sort( &$ifiles );
                    uasort( &$ifiles, $mdsortingfields[$msort] );
                }
                // If set, reversing array
                if ( $mreverse ) $ifiles = array_reverse( $ifiles );

                // Building markup for each file...
                foreach ( $ifiles as $ifile ) {
                    $ititle = '';
                    // Each tag list item
                    foreach ( $mshowtags as $mshowtag ) {
                        $tagvalue = $tagvalues[$mshowtag][$ifile] ;
                        if ( '' != $tagvalue ) {
                            // Removing "prefix" of this tag
                            if ( '' != $tagprefixes[$mshowtag] )
                                $tagvalue = str_replace( $tagprefixes[$mshowtag], '', $tagvalue ) ;
                            $tagvalue = str_replace( $prefix, '', $tagvalue );
                            // Cleaning...
                            $tagvalue = replaceUnderscores( $tagvalue ) ;
                            // Encoding...
                            if ( 'file' == $mshowtag || 'directory' == $mshowtag ) {
                                if ( $mdofnencode ) $tagvalue = utf8_encode( $tagvalue ) ;
                            } elseif ( $mdoencode ) {
                                $tagvalue = utf8_encode( $tagvalue ) ;
                            }
                            // Item markup
                            $columnheader = ucwords( _md( $mshowtag ) );
                            if ( array_key_exists( $mshowtag, $replaceheaders ) ) $columnheader = $replaceheaders[$mshowtag];
                            $ititle .= '<dt class="mdTag'.$mshowtag.'">'.$columnheader.':</dt>' ;
                            $ititle .= '<dd class="mdTag'.$mshowtag.'">'.$tagvalue.'</dd>' ;
                        }
                    }
                    // List markup (if any item)
                    if ( '' != $ititle )
                        $ititle = '<dl class="mdTags">' . $ititle . '</dl>' ;
                    $ititles[$ifile] = $ititle ;
                }


                /* -- CASE SPECIFIC: -- */
                
                // If any "extra" files, inserting an extra table
                // (this was very case specific and remained here)
                if ( count( $zip ) + count( $pdf ) + count( $epub ) ) $ihtml .= '<table class="bookInfo">
<thead>
<tr>
<th class="chapterCol">' . _md( 'By Chapter' ) . '</th>
<th class="wholeBookCol">' . _md( 'Whole Book' ) . '</th>
</tr>
</thead>
<tbody>
<tr>
<td class="chapterCol">';

                /* -- END CASE SPECIFIC; -- */


                // Building general markup
                $tableClass = array( 'mediaTable' );
                if ( TRUE == get_option( 'embedplayer' ) ) $tableClass[] = 'embedPlayer';
                $tableClass[] = 'embedpos' . $membedwhere ;
                $ihtml .= '<table class="' . implode( ' ', $tableClass ) . '">' . "\n";
                $ihtml .= '<thead>
<tr>
<th class="mediaTitle">&nbsp;</th>
<th class="mediaDownload">'._md('Download').'</th>
</tr>
</thead>
<tbody>';
                // Each file...
                foreach ( $ifiles as $ifile ) {
                    // File name
                    $showifile = $ifile ;
                    // Removing prefix
                    if ( array_key_exists( 'file', $tagprefixes ) )
                        $showifile = str_replace( $tagprefixes['file'], '', $showifile ) ;
                    // Cleaning
                    $showifile = replaceUnderscores( $showifile );
                    $alltags[$ifile]['file'][0] = $showifile;
                    // Download text
                    $idownloadtext = $downloadtext ? $downloadtext : 'Download: [file]';
                    $iplaytext = $playtext ? $playtext : 'Play: [file]';
                    foreach ( $mdtags as $mdtag ) {
                        if ( !array_key_exists( $mdtag, $alltags[$ifile] ) ) $alltags[$ifile][$mdtag] = array( '' );
                        $tagvalue = $alltags[$ifile][$mdtag][0];
                        if ( 'file' == $mdtag || 'directory' == $mdtag ) {
                            if ( $mdofnencode ) $tagvalue = utf8_encode( $tagvalue ) ;
                        } elseif ( $mdoencode ) {
                            $tagvalue = utf8_encode( $tagvalue ) ;
                        }
                        $idownloadtext = str_replace( '[' . $mdtag . ']', $tagvalue, $idownloadtext );
                        $iplaytext = str_replace( '[' . $mdtag . ']', $tagvalue, $iplaytext );
                    }
                    
                    // Getting stored markup
                    $ititle = $ititles[$ifile] ;
                    $ititle = str_replace( $prefix, '', $ititle ) ;

                    // Markup
                    // 20100107 - I took it away: strtoupper( $hlevel )
                    $ihtml .= '<tr>'."\n" ;
                    $ihtml .= '<td class="mediaTitle">'.$ititle.'</td>'."\n" ;
                    $ihtml .= '<td class="mediaDownload"><a href="'.$mrelative.'/'.($ufolder?$ufolder.'/':'').rawurlencode( $ifile ).'.mp3" title="' . htmlentities( $showifile, ENT_COMPAT, 'UTF-8' ) . '" rel="mediaDownloaderPlayText:' . urlencode( htmlentities( $iplaytext, ENT_COMPAT, 'UTF-8' ) ) . '">'.$idownloadtext.'</a></td>'."\n" ;
                    $ihtml .= '</tr>'."\n" ;
                }
                $ihtml .= '</tbody></table>'."\n" ;


                /* -- CASE SPECIFIC: -- */
                
                // If any "extra" files, inserting extra elements
                // (this was very case specific and remained here)
                if ( count( $zip ) + count( $pdf ) + count( $epub ) ) {
                    $afolder = explode( '/', $folder ) ;
                    for ( $a=0; $a<count($afolder); $a++ ) $afolder[$a] = rawurlencode( $afolder[$a] ) ;
                    $cfolder = implode( '/', $afolder ) ;
                    $ihtml .= '</td>
<td class="wholeBookCol">

<ul>' ;
                    $czf = 0; if ( count($zip) ) foreach($zip as $zipf){
                        $czf++;
                        $ihtml .= '<li class="dZip"><a href="'.$mrelative.'/'.($cfolder).'/'.rawurlencode( $zipf ).'">'._md( 'Download ZIP' ).(count($zip)>1?' '.$czf:'').' <small>'._md( '(Audio chapters)' ).'</small></a></li>' ;
                    }
                    $cpf=0; if ( count($pdf) ) foreach($pdf as $pdff){
                        $cpf++;
                        $ihtml.='<li class="dPdf"><a href="'.$mrelative.'/'.($cfolder).'/'.rawurlencode( $pdff ).'">'._md( 'Download PDF' ).(count($pdf)>1?' '.$cpf:'').' <small>'._md( '(Text file)' ).'</small></a></li>' ;
                    }
                    $cef=0; if ( count($epub) ) foreach($epub as $epubf){
                        $cef++;
                        $ihtml.='<li class="dEpub"><a href="'.$mrelative.'/'.($cfolder).'/'.rawurlencode( $epubf ).'">'._md( 'Download EPUB' ).(count($epub)>1?' '.$cef:'').' <small>'._md( '(Text file)' ).'</small></a></li>' ;
                    }
                    $ihtml.='</ul>

</td>
</tr>
</tbody>
</table>' ;
                }

            // If any "extra" files, inserting extra elements
            // (this was very case specific and remained here)
            } elseif ( count( $zip ) + count( $pdf ) + count( $epub ) ){
                $afolder = explode( '/', $folder ) ;
                for ( $a=0; $a<count($afolder); $a++ ) $afolder[$a] = rawurlencode( $afolder[$a] ) ;
                $cfolder = implode( '/', $afolder ) ;
                $allf = array_merge( $zip, $pdf, $epub );
                asort(&$allf);
                $ihtml .= '<table class="mediaTable bookInfo">' . "\n";
                $ihtml .= '<thead>
<tr>
<th>'._md('Download').'</th>
</tr>
</thead>
<tbody>
<tr>
<td class="wholeBookCol">
<ul>';
                foreach($allf as $thisf){
                    $arrf = explode('.', $thisf);
                    $fext = array_pop(&$arrf);
                    $fname = implode('.', $arrf);
                    $ihtml .= '<li class="d' . strtoupper(substr($fext,0,1)) . substr($fext,1) . '"><a href="'.$mrelative.'/'.($cfolder).'/'.rawurlencode( $thisf ).'">'.$fname.'</a></li>' ;
                }
                $ihtml.='</ul>
</td>
</tr>
</tbody>
</table>' ;
            }
            /* -- END CASE SPECIFIC; -- */
            
            if ( count( $errors ) ) {
                $errorHtml = '<div class="mediaDownloaderErrors">';
                foreach ( $errors as $error ) $errorHtml .= '<p><strong>' . _md( 'Error:' ) . '</strong> ' . $error . '</p>';
                $errorHtml .= '</div>';
                $ihtml .= $errorHtml;
            }
            // Finally, replacing our smart tag
            $t = str_replace( '[media:'.$folder.']', $ihtml, $t ) ;
        }
    }
    return $t ;
}
// To sort file array by some tag
function orderByTag( $a, $b, $tag ) {
    if ( !is_array( $tag ) ) $tag = array( $tag );
    global $tagvalues;
    $ret = 0;
    foreach ( $tag as $t ) {
        $ret = strnatcmp( $tagvalues[$t][$a], $tagvalues[$t][$b] );
        if ( 0 != $ret ) break;
    }
    if ( 0 == $ret ) $ret = strnatcmp( $a, $b );
    return $ret;
}
function orderByTitle( $a, $b ) {
    return orderByTag( $a, $b, array( 'title', 'filedate' ) );
}
function orderByFileDate( $a, $b ) {
    return orderByTag( $a, $b, 'filedate' );
}
function orderByTrackNumber( $a, $b ) {
    return orderByTag( $a, $b, 'track_number' );
}
function orderByAlbum( $a, $b ) {
    return orderByTag( $a, $b, array( 'album', 'track_number' ) );
}
function orderByArtist( $a, $b ) {
    return orderByTag( $a, $b, array( 'artist', 'album', 'track_number' ) );
}
function orderByFileSize( $a, $b ) {
    return orderByTag( $a, $b, 'filesize' );
}

function mediadownloader( $t ) {
    $t = listMedia( $t );
    if ( TRUE == get_option( 'removeextension' ) ) {
        $t = preg_replace(
            '/href\=[\\\'\"](.*)\.mp3[\\\'\"]/im',
            "href=\"".WP_PLUGIN_URL."/media-downloader/getfile.php?f=$1\"",
            $t
        );
    }
    
    /* -- CASE SPECIFIC: -- */
    $t = listarCategorias( $t );
    $t = listarCategoriasEx( $t );
    $t = listarIdiomas( $t );
    /* -- END CASE SPECIFIC; -- */
    return $t;
}


function mediadownloaderMP3Length( $f ) {
    // Initialize getID3 engine
    $getID3 = new getID3;
    // Analyze file and store returned data in $ThisFileInfo
    $ThisFileInfo = $getID3->analyze( $filename );
    // Optional: copies data from all subarrays of [tags] into [comments] so
    // metadata is all available in one location for all tag formats
    // metainformation is always available under [tags] even if this is not called
    getid3_lib::CopyTagsToComments( $ThisFileInfo );
}

// Get ID3 tags from file
function mediadownloaderMP3Info( $f ) {
    $relURL = str_replace( 'http://'.$_SERVER['SERVER_NAME'], '', get_option( 'siteurl' ) );
    // File path
    $f = ABSPATH . str_replace( $relURL, '', $f ) . '.mp3';

    // Checking cache
    $hash = md5( $f );
    $cachedir = trim( get_option( 'cachedir' ) ) ;
    $cachefile = ABSPATH . '/' . $cachedir . '/md-' . $hash . '.cache' ;
    if ( $cachedir && is_readable( $cachefile )  && ( filemtime( $cachefile ) >= filemtime( $f ) ) ) {

        return unserialize( file_get_contents( $cachefile ) );

    } else {

        // include getID3() library (can be in a different directory if full path is specified)
        require_once('getid3/getid3.php');
        // Initialize getID3 engine
        $getID3 = new getID3;
        // Analyze file and store returned data in $ThisFileInfo
        $ThisFileInfo = $getID3->analyze( $f );
        // Saving cache
        if ( $cachedir && is_writeable( ABSPATH . '/' . $cachedir ) ) file_put_contents( $cachefile, serialize( $ThisFileInfo ) );
        return $ThisFileInfo;
    }
}
// File size
function mediadownloaderMP3Size( $f ){
    $f = ABSPATH . substr( $f, 1 ) . '.mp3';
    if ( !file_exists( $f ) ) $f = urldecode( $f );
    return filesize( $f );
}
// Extract MP3 links form post content
function mediadownloaderEnclosures(){
    $ret = array();
    global $post;
    $cont = listMedia( get_the_content( $post->ID ) );
    preg_match_all( '/href=[\\\'"](.*)\.mp3[\\\'"]/im', $cont, $matches );
    preg_match_all( '/href=[\\\'"].*getfile\.php\?\=(.*)[\\\'"]/im', $cont, $newmatches );
    if ( count( $matches ) && count( $matches[1] ) ) $ret = array_unique( array_merge( $matches[1], $newmatches[1] ) );
    return $ret;
} 
// Generate ATOM tags
function mediadownloaderAtom(){
    $t = '';
    $matches = mediadownloaderEnclosures();
    foreach ( $matches as $m ) {
        //$t.='<link rel="enclosure" title="'.basename($m).'" length="'.mediadownloaderMP3Size($m).'" href="'.WP_PLUGIN_URL.'/media-downloader/getfile.php?f='.urlencode($m).'" type="audio/mpeg" />';
        $t .= '<link rel="enclosure" title="' . basename( $m ) . '" length="' . mediadownloaderMP3Size( $m ) . '" href="' . ( $m . '.mp3' ) . '" type="audio/mpeg" />';
	}
    echo $t;
    //return $t;
}
// Generate RSS tags
function mediadownloaderRss(){
    $t = '';
    $matches = mediadownloaderEnclosures();
    foreach ( $matches as $m ) {
        //$t.='<enclosure title="'.basename($m).'" url="'.WP_PLUGIN_URL.'/media-downloader/getfile.php?f='.urlencode($m).'" length="'.mediadownloaderMP3Size($m).'" type="audio/mpeg" />';
        $t .= '<enclosure title="' . basename( $m ) . '" url="' . ( $m . '.mp3' ) . '" length="' . mediadownloaderMP3Size( $m ) . '" type="audio/mpeg" />';
	}
    echo $t;
    //return $t; 
}

add_filter( 'the_content', 'mediadownloader' );
add_action( 'atom_entry', 'mediadownloaderAtom' );
//add_action( 'rss_item', 'mediadownloaderRss' );
add_action( 'rss2_item', 'mediadownloaderRss' );

function mediaDownloaderEnqueueScripts() {
    // If any custom css, we enqueue our php that throws this css
    $customcss = trim( get_option( 'customcss' ) );
    if ( '' != $customcss ) {
        wp_register_style( 'mediadownloaderCss', WP_PLUGIN_URL . '/media-downloader/css/mediadownloader-css.php' );
        wp_enqueue_style( 'mediadownloaderCss' );
    }
    // Enqueuing our javascript
    wp_enqueue_script( 'mediadownloaderJs', WP_PLUGIN_URL . '/media-downloader/js/mediadownloader.js', 'jquery', date( 'YmdHis', filemtime( __DIR__ . '/js/mediadownloader.js' ) ), get_option( 'scriptinfooter' ) );
    
    // Passing options to our javascript
    add_action( 'get_header', 'mediaDownloaderLocalizeScript' );
}
add_action( 'init', 'mediaDownloaderEnqueueScripts' );
    
// Passing options to our javascript
function mediaDownloaderLocalizeScript() {
    global $mdembedplayerdefaultcolors;
    $mdembedcolors = array();
    foreach( $mdembedplayerdefaultcolors as $mdcolor => $mddefault ) {
        $mdembedcolors[$mdcolor] = get_option( $mdcolor . '_embed_color' );
        if ( !trim($mdembedcolors[$mdcolor]) ) $mdembedcolors[$mdcolor] = $mddefault;
    }
    wp_localize_script( 'mediadownloaderJs', 'mdEmbedColors', $mdembedcolors );
}


// Our options screens...
add_action( 'admin_menu', 'mediadownloader_menu' );

function mediadownloader_menu() {
    add_options_page('Media Downloader Options', 'Media Downloader', 'manage_options', 'mediadownloader-options', 'mediadownloader_options');
}

function mediadownloader_options() {
    // Basically, user input forms...
    if ( isset( $_GET['markup-options'] ) ) {
        require_once("mediadownloader-markup-options.php");
    } elseif ( isset( $_GET['more-options'] ) ) {
        require_once("mediadownloader-more-options.php");
    } else {
        require_once("mediadownloader-options.php");
    }
}

// Add Settings link to plugins - code from GD Star Ratings
// (as seen in http://www.whypad.com/posts/wordpress-add-settings-link-to-plugins-page/785/ )
function mediadownloader_settings_link( $links, $file ) {
    $this_plugin = plugin_basename(__FILE__);
    if ( $file == $this_plugin ) {
        $settings_link = '<a href="options-general.php?page=mediadownloader-options">' . _md( 'Settings' ) . '</a>';
        array_unshift( $links, $settings_link );
    }
    return $links;
}
add_filter( 'plugin_action_links', 'mediadownloader_settings_link', 10, 2 );

// Registering our settings...
add_action( 'admin_init', 'mediadownloader_settings' );

function mediadownloader_settings() {
    global $mdsettings;
    foreach ( $mdsettings as $mdsetting => $mdsanitizefunction ) register_setting( 'md_options', $mdsetting, $mdsanitizefunction );

    global $mdmarkupsettings;
    foreach ( $mdmarkupsettings as $mdmarkupsetting => $mdsanitizefunction ) register_setting( 'md_markup_options', $mdmarkupsetting, $mdsanitizefunction );

    global $mdembedplayerdefaultcolors;
    foreach ( $mdembedplayerdefaultcolors as $mdcolor => $mddefault ) register_setting( 'md_more_options', $mdcolor . '_embed_color', 'sanitizeHEXColor' );
}


// Functions to sanitize user input
function sanitizeRDir( $d ){
    return is_readable( ABSPATH . $d ) ? $d : '' ;
}
function sanitizeWDir( $d ){
    return is_writeable( ABSPATH . $d ) ? $d : '' ;
}
function sanitizeArray( $i, $a ){
    return in_array( $i, $a ) ? $i : '' ;
}
function sanitizeSortingField( $t ){
    global $mdsortingfields;
    return sanitizeArray( $t, array_keys( $mdsortingfields ) );
}
function sanitizeBeforeAfter( $t ){
    return sanitizeArray( $t, array( 'before', 'after' ) );
}
function sanitizeTagEncoding( $t ){
    global $mdencodings;
    return sanitizeArray( $t, $mdencodings );
}
function sanitizeBoolean( $b ){
    return $b == 1 ;
}
function sanitizeHEXColor( $c ){
    return preg_match( '/^\s*#?[0-9A-F]{3,6}\s*$/i', $c, $m ) ? trim( str_replace( '#', '', $c ) ) : '';
}


// I used these functions below to "internationalize" (localize) some strings,
// left them here for "backward compatibilaziness"

function _md( $t ) {
//    if ( function_exists( 'icl_register_string' ) ) {
//        icl_register_string( 'Media Downloader', $t, $t );
//        return icl_t( 'Media Downloader', $t, $t );
//    } else {
        return __( $t, 'mediadownloader' ) ;
//    }
}
function _mde( $t ) {
//    if ( function_exists( 'icl_register_string' ) ) {
//        icl_register_string( 'Media Downloader', $t, $t );
//        echo icl_t( 'Media Downloader', $t, $t );
//    } else {
        return _e( $t, 'mediadownloader' ) ;
//    }
}
function _mdn( $ts, $tp, $n ) {
//    if ( function_exists( 'icl_register_string' ) ) {
//        icl_register_string( 'Media Downloader', $ts, $ts );
//        icl_register_string( 'Media Downloader', $tp, $tp );
//        if ( 1 != $n ) {
//            return icl_t( 'Media Downloader', $tp, $tp );
//        } else {
//            return icl_t( 'Media Downloader', $ts, $ts );
//        }
//    } else {
        return _n( $ts, $tp, $n ) ;
//    }
}


/* -- CASE SPECIFIC: -- */

add_filter( 'get_previous_post_where', 'corrige_qtrans_excludeUntransPosts' );
add_filter( 'get_next_post_where', 'corrige_qtrans_excludeUntransPosts' );
add_filter( 'posts_where_request', 'corrige_qtrans_excludeUntransPosts' );

function corrige_qtrans_excludeUntransPosts( $where ) {
    if ( function_exists( 'qtrans_getLanguage' ) ) {
        $l = qtrans_getLanguage();
        if ( trim( $l ) ) {
	        global $q_config, $wpdb;
	        if ( $q_config['hide_untranslated'] ) {
		        $where .= " AND post_content LIKE '%<!--:".$l."-->%'";
	        }
	    }
	}
	return $where;
}

function listarCategorias($t){
    preg_match_all('/\[cat:([^\]]*)\]/i',$t,$matches);
    if(count($matches)){
        foreach($matches[1] as $catname){
            $myposts = get_posts(array('numberposts'=>-1,'post_type'=>'post','category_name'=>$catname,'suppress_filters'=>0));
            $listposts='';

            if(count($myposts)){
                global $post;
                $prepost=$post;
                $listposts.='<ul class="inner-cat">';
                foreach($myposts as $post) $listposts.='<li><a href="'.get_permalink().'">'.get_the_title().'</a></li>';
                $listposts.='</ul>';
                $post=$prepost;
            }
            $t = tiraDoParagrafo('[cat:'.$catname.']', $t);
            $t = str_replace('[cat:'.$catname.']', $listposts, $t);
        }
    }
    return $t;
}

function listarCategoriasEx($t){
    preg_match_all('/\[catex:([^\]]*)\]/i',$t,$matches);
    if(count($matches)){
        foreach($matches[1] as $catname){
            $myposts = get_posts(array('post_type'=>'post','category_name'=>$catname,'suppress_filters'=>0));
            $listposts='';
            if(count($myposts)){
                global $post;
                $prepost=$post;
                $listposts.='<dl class="inner-cat">';
                foreach($myposts as $post) $listposts.='<dt><a href="'.get_permalink().'">'.get_the_title().'</a></dt>'.(trim($post->post_excerpt)?'<dd>'.$post->post_excerpt.'</dd>':'');
                $listposts.='</dl>';
                $post=$prepost;
            }
            $t = tiraDoParagrafo('[catex:'.$catname.']', $t);
            $t = str_replace('[catex:'.$catname.']', $listposts, $t);
        }
    }
    return $t;
}

function listarIdiomas($t){
    if ( stripos($t, '[languages]')!==false && function_exists('qtrans_generateLanguageSelectCode') ){
        ob_start();
        qtrans_generateLanguageSelectCode();
        $i=ob_get_contents();
        ob_end_clean();
        ob_end_flush();
        $t = tiraDoParagrafo('[languages]', $t);
        $t = str_replace('[languages]', $i, $t);
    }
    return $t;
}

function tiraDoParagrafo($tag, $t){
    return str_replace('<p>'.$tag.'</p>', $tag, $t);
}

/* -- END CASE SPECIFIC; -- */

?>
