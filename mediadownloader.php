<?php
/*
Plugin Name: Media Downloader
Plugin URI: http://ederson.peka.nom.br
Description: Media Downloader plugin lists MP3 files from a folder by replacing the [media] smarttag.
Version: 0.1.99.75
Author: Ederson Peka
Author URI: http://ederson.peka.nom.br
*/

// Possible encodings
$mdencodings = array( 'UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'cp866', 'cp1251', 'cp1252', 'KOI8-R', 'BIG5', 'GB2312', 'BIG5-HKSCS', 'Shift_JIS', 'EUC-JP' );
// Possible fields by which file list should be sorted,
// and respective sorting functions
$mdsortingfields = array(
    'none' => null,
    'title' => 'orderByTitle',
    'file date' => 'orderByFileDate',
    'year' => 'orderByYear',
    'track number' => 'orderByTrackNumber',
    'album' => 'orderByAlbum',
    'artist' => 'orderByArtist',
    'file size' => 'orderByFileSize',
    'sample rate' => 'orderBySampleRate',
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
    'scriptinfooter' => 'sanitizeBoolean',
    'handlefeed' => 'sanitizeBoolean',
    'calculateprefix' => 'sanitizeBoolean',
);
// Possible ID3 tags
$mdtags = array( 'title', 'artist', 'album', 'year', 'genre', 'comments', 'track_number', 'bitrate', 'filesize', 'filedate', 'directory', 'file', 'sample_rate' );

// Markup settings and respective sanitize functions
$mdmarkupsettings = array(
    'downloadtext' => null,
    'playtext' => null,
    'stoptext' => null,
    'replaceheaders' => null,
    'markuptemplate' => 'sanitizeMarkupTemplate',
);
// Possible markup templates
$mdmarkuptemplates = array(
    'definition-list' => '<strong>"DL" mode:</strong> One table cell containing a definition list (one definition term for each tag)',
    'table-cells' => '<strong>"TR" mode:</strong> One table cell for each tag'
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
    function byte_convert( $bytes ){
        $symbol = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );

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

// Friendly file size
if( !function_exists( 'hertz_convert' ) ){
    function hertz_convert( $hertz ){
        $symbol = array( 'Hz', 'kHz', 'MHz', 'GHz', 'THz', 'PHz', 'EHz', 'ZHz', 'YHz' );

        $exp = 0;
        $converted_value = 0;
        if( $hertz > 0 ) {
          $exp = floor( log( $hertz, 10 ) / 3 );
          $converted_value = ( $hertz / pow( 1000 , floor( $exp ) ) );
        }

        return sprintf( '%.2f '.$symbol[$exp], $converted_value );
    }
}

// Scans an array of strings searching for a common prefix in all items
function calculatePrefix($arr){
    $prefix = '';
    if ( get_option( 'calculateprefix' ) && count( $arr ) > 1 ) {
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

function get_replaceheaders() {
    $replaceheaders = array();
    $arrreplaceheaders = explode( "\n", trim( get_option( 'replaceheaders' ) ) );
    foreach ( $arrreplaceheaders as $line ) {
        $arrline = explode( ':', trim( $line ) );
        if ( count( $arrline ) >= 2 ) $replaceheaders[ strtolower( trim( array_shift( $arrline ) ) ) ] = implode( ':', $arrline );
    }
    return $replaceheaders;
}

// Searches post content for our smarttag and do all the magic
function listMedia( $t ){
    global $mdtags, $tagvalues, $mdsortingfields, $mdmarkuptemplates;
    $errors = array();

    // MP3 folder
    $mdir = '/' . get_option( 'mp3folder' );
    // MP3 folder URL
    $murl = get_option( 'siteurl' ) . $mdir;
    // MP3 folder relative URL
    $mrelative = str_replace('http'.(isset($_SERVER['HTTPS'])?'s':'').'://','',$murl); $mrelative = explode( '/', $mrelative ); array_shift($mrelative); $mrelative = '/'.implode('/', $mrelative);
    $mpath = ABSPATH . substr($mdir, 1);
    
    // Player position (before or after download link)
    $membedwhere = get_option( 'embedwhere' ) ;

    // Should we re-encode the tags?
    $mdoencode = get_option( 'tagencoding' );
    if ( !$mdoencode ) $mdoencode = 'UTF-8';

    // Should we re-encode the file names?
    $mdofnencode = get_option( 'filenameencoding' );
    if ( !$mdofnencode ) $mdofnencode = 'UTF-8';
    
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
    $stoptext = get_option( 'stoptext' );
    $replaceheaders = get_replaceheaders();
    $markuptemplate = get_option( 'markuptemplate' );
    if ( !sanitizeMarkupTemplate( $markuptemplate ) ) $markuptemplate = array_shift( array_keys( $mdmarkuptemplates ) ); // Default: first option

    // Searching for our smarttags
    $t = preg_replace( '/<p>\[media:([^\]]*)\]<\/p>/i', '[media:$1]', $t );
    preg_match_all( '/\[media:([^\]]*)\]/i', $t, $matches );
    // Any?
    if ( count( $matches ) ) {
        // Each...
        foreach ( $matches[1] as $folder ) {
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
                $folderalone = $folder;
                if ( is_readable( $ipath ) ) {
                    $idir = dir( $ipath );
                    while (false !== ($ifile = $idir->read())) {
                        if ( substr( $ifile, -4 ) == '.mp3' ) $ifiles[] = substr( $ifile, 0, -4 );
                        if ( substr( $ifile, -4 ) == '.zip' ) $zip[] = $ifile;
                        if ( substr( $ifile, -4 ) == '.pdf' ) $pdf[] = $ifile;
                        if ( substr( $ifile, -5 ) == '.epub' ) $epub[] = $ifile;
                    }
                } else {
                    $errors[] = sprintf( _md( 'Could not read: %1$s' ), $ipath );
                }
            } elseif ( file_exists( $ipath ) && is_readable( $ipath ) ) {
                $folderalone = implode( '/', array_slice( explode( '/', $folder ), 0, -1 ) );
                $apath = explode( '/', $ipath );
                $ifile = array_pop( $apath );
                if ( substr( $ifile, -4 ) == '.mp3' ) $ifiles[] = substr( $ifile, 0, -4 );
                if ( substr( $ifile, -4 ) == '.zip' ) $zip[] = $ifile;
                if ( substr( $ifile, -4 ) == '.pdf' ) $pdf[] = $ifile;
                if ( substr( $ifile, -5 ) == '.epub' ) $epub[] = $ifile;
                $ipath = implode( '/', $apath );
            }
            // Encoding folder name
            $pfolder = array_filter( explode( '/', $folderalone ) );
            foreach( $pfolder as &$p ) $p = rawurlencode( $p );
            $ufolder = implode( '/', $pfolder );
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
                    $finfo = mediadownloaderMP3Info( $mrelative.'/'.$folderalone.'/'.$ifile ) ;
                    // Loading all possible tags
                    $ftags = $finfo['tags']['id3v2'] ;
                    $ftags['bitrate'] = array( floatval( $finfo['audio']['bitrate'] ) / 1000 . 'kbps' ) ;
                    $ftags['filesize'] = array( byte_convert( $finfo['filesize'] ) ) ;
                    $ftags['filedate'] = array( date_i18n( get_option('date_format'), filemtime( $finfo['filepath'] . '/' . $finfo['filename'] ) ) ) ;
                    $ftags['directory'] = array( $hlevel ) ;
                    $ftags['file'] = array( $ifile ) ;
                    $ftags['sample_rate'] = array( hertz_convert( intval( '0' . $finfo['audio']['sample_rate'] ) ) );
                    $alltags[$ifile] = $ftags;
                    // Populating array of tag values with all tags
                    foreach ( $mdtags as $mshowtag )
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

                $tablecellsmode_header = '';
                $tablecellsmode_firstfile = true;
                // Building markup for each file...
                foreach ( $ifiles as $ifile ) {
                    $ititle = '';
                    // Each tag list item
                    foreach ( $mshowtags as $mshowtag ) {
                        $tagvalue = $tagvalues[$mshowtag][$ifile] ;
                        if ( '' == $tagvalue ) {
                            $tagvalue = '&nbsp;';
                        } else {
                            // Removing "prefix" of this tag
                            if ( '' != $tagprefixes[$mshowtag] )
                                $tagvalue = str_replace( $tagprefixes[$mshowtag], '', $tagvalue ) ;
                            // $tagvalue = str_replace( $prefix, '', $tagvalue ); // Causing weird behavior in some cases
                            // Cleaning...
                            $tagvalue = replaceUnderscores( $tagvalue ) ;
                            // Encoding...
                            if ( 'file' == $mshowtag || 'directory' == $mshowtag ) {
                                if ( $mdofnencode != 'UTF-8' ) $tagvalue = iconv( $mdofnencode, 'UTF-8', $tagvalue );
                            } elseif ( $mdoencode != 'UTF-8' ) {
                                $tagvalue = iconv( $mdoencode, 'UTF-8', $tagvalue );
                            }
                        }
                        // Item markup
                        $columnheader = ucwords( _md( $mshowtag ) );
                        if ( array_key_exists( $mshowtag, $replaceheaders ) ) $columnheader = $replaceheaders[$mshowtag];
                        if ( 'table-cells' == $markuptemplate ) {
                            // For "table cells" markup template,
                            // we store a "row with headers", so it
                            // just needs to run once
                            if ( $tablecellsmode_firstfile ) {
                                $tablecellsmode_header .= '<th class="mdTag'.$mshowtag.'">'.$columnheader.'</th>' ;
                            }
                            $ititle .= '<td class="mdTag'.$mshowtag.'">'.$tagvalue.'</td>' ;
                        } elseif ( 'definition-list' == $markuptemplate )  {
                            $ititle .= '<dt class="mdTag'.$mshowtag.'">'.$columnheader.':</dt>' ;
                            $ititle .= '<dd class="mdTag'.$mshowtag.'">'.$tagvalue.'</dd>' ;
                        }
                    }
                    // List markup (if any item)
                    if ( '' != $ititle ) {
                        if ( 'definition-list' == $markuptemplate ) {
                            $ititle = '<dl class="mdTags">' . $ititle . '</dl>' ;
                        }
                    }
                    $ititles[$ifile] = $ititle ;
                    // "Row with headers" is stored already,
                    // so skip the task next iteration
                    $tablecellsmode_firstfile = false;
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
                $ihtml .= "<thead>\n<tr>\n";
                if ( 'table-cells' == $markuptemplate ) {
                    $ihtml .= $tablecellsmode_header;
                } elseif ( 'definition-list' == $markuptemplate ) {
                    $ihtml .= "\n" . '<th class="mediaTitle">&nbsp;</th>' . "\n";
                }
                $downloadheader = _md( 'Download' );
                if ( array_key_exists( 'download', $replaceheaders ) ) $downloadheader = $replaceheaders['download'];
                $ihtml .= '<th class="mediaDownload">'.$downloadheader.'</th>
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
                    // Play, Stop, Title and Artist texts (for embed player)
                    $iplaytext = $playtext ? $playtext : 'Play: [file]';
                    $istoptext = $stoptext ? $stoptext : 'Stop: [file]';
                    $ititletext = $showifile;
                    $iartisttext = '';
                    foreach ( $mdtags as $mdtag ) {
                        if ( !array_key_exists( $mdtag, $alltags[$ifile] ) ) $alltags[$ifile][$mdtag] = array( '' );
                        $tagvalue = $alltags[$ifile][$mdtag][0];
                        if ( 'file' == $mdtag || 'directory' == $mdtag ) {
                            if ( $mdofnencode != 'UTF-8' ) $tagvalue = iconv( $mdofnencode, 'UTF-8', $tagvalue );
                        } elseif ( $mdoencode != 'UTF-8' ) {
                            $tagvalue = iconv( $mdoencode, 'UTF-8', $tagvalue );
                        }
                        // Replacing wildcards
                        $idownloadtext = str_replace( '[' . $mdtag . ']', $tagvalue, $idownloadtext );
                        $iplaytext = str_replace( '[' . $mdtag . ']', $tagvalue, $iplaytext );
                        $istoptext = str_replace( '[' . $mdtag . ']', $tagvalue, $istoptext );
                        // If "title", populate "Title text"
                        if ( 'title' == $mdtag ) $ititletext = $tagvalue;
                        // If "artist", populate "Artist text"
                        if ( 'artist' == $mdtag && $tagvalue ) $iartisttext = str_replace( '-', '[_]', $tagvalue ) . ' - ';
                    }
                    
                    // Getting stored markup
                    $ititle = $ititles[$ifile] ;

                    // $ititle = str_replace( $prefix, '', $ititle ) ; // Causing weird behavior in some cases

                    // Markup
                    // 20100107 - I took it away: strtoupper( $hlevel )
                    $ihtml .= '<tr class="mdTags">'."\n" ;
                    if ( 'table-cells' == $markuptemplate ) {
                        // a group of "td's"
                        $ihtml .= $ititle . "\n";
                    } elseif ( 'definition-list' == $markuptemplate ) {
                        // one "td" with a "dl" inside
                        $ihtml .= '<td class="mediaTitle">'.$ititle.'</td>'."\n" ;
                    }
                    // Play, Stop and Title (concatenated with Artist) texts
                    // all packed in rel attribute, for embed player to read
                    // and do its black magic
                    $irel = array();
                    if ( $iplaytext ) $irel[] = 'mediaDownloaderPlayText:' . htmlentities( $iplaytext, ENT_COMPAT, 'UTF-8' );
                    if ( $istoptext ) $irel[] = 'mediaDownloaderStopText:' . htmlentities( $istoptext, ENT_COMPAT, 'UTF-8' );
                    $ititletext = $iartisttext . $ititletext;
                    if ( $ititletext ) $irel[] = 'mediaDownloaderTitleText:' . htmlentities( $ititletext, ENT_COMPAT, 'UTF-8' );
                    $irel = implode( ';', $irel );
                    $ihtml .= '<td class="mediaDownload"><a href="'.home_url($mdir).'/'.($ufolder?$ufolder.'/':'').rawurlencode( $ifile ).'.mp3" title="' . htmlentities( $showifile, ENT_COMPAT, 'UTF-8' ) . '" ' . ( $irel ? 'rel="' . $irel . '"' : '' ) . ' id="mdfile_' . sanitize_title( $ifile ) . '">'.$idownloadtext.'</a></td>'."\n" ;
                    $ihtml .= '</tr>'."\n" ;
                }
                $ihtml .= '</tbody></table>'."\n" ;


                /* -- CASE SPECIFIC: -- */
                
                // If any "extra" files, inserting extra elements
                // (this was very case specific and remained here)
                if ( count( $zip ) + count( $pdf ) + count( $epub ) ) {
                    $afolder = explode( '/', $folderalone ) ;
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
                $afolder = explode( '/', $folderalone ) ;
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
function orderByYear( $a, $b ) {
    return orderByTag( $a, $b, array( 'year', 'track_number', 'filedate' ) );
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
function orderBySampleRate( $a, $b ) {
    return orderByTag( $a, $b, 'sample_rate' );
}


function mediadownloader( $t ) {
    if ( !is_feed() || !get_option( 'handlefeed' ) ) :
        $t = listMedia( $t );
        if ( TRUE == get_option( 'removeextension' ) ) {
            $t = preg_replace(
                '/href\=[\\\'\"](.*)\.mp3[\\\'\"]/im',
                "href=\"".WP_PLUGIN_URL."/media-downloader/getfile.php?f=$1\"",
                $t
            );
        };
    elseif ( is_feed() ) :
        $t = preg_replace( '/<p>\[media:([^\]]*)\]<\/p>/i', '<p><small>' . _md( '(See attached files...)' ) . '</small></p>', $t );
    endif;
        
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
    $relURL = str_replace( 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['SERVER_NAME'], '', get_option( 'siteurl' ) );
    // File path
    $f = ABSPATH . str_replace( $relURL, '', $f ) . '.mp3';

    // Checking cache
    $hash = md5( $f );
    $cachedir = trim( get_option( 'cachedir' ) ) ;
    $cachefile = ABSPATH . '/' . $cachedir . '/md-' . $hash . '.cache' ;
    if ( $cachedir && is_readable( $cachefile )  && file_exists( $f ) && ( filemtime( $cachefile ) >= filemtime( $f ) ) ) {

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
    if ( 0 === stripos( $f, get_option( 'siteurl' ) ) ) $f = str_replace( get_option( 'siteurl' ), '', $f );
    $f = ABSPATH . substr( $f, 1 ) . '.mp3';
    if ( !file_exists( $f ) ) $f = urldecode( $f );
    return filesize( $f );
}
// Extract MP3 links form post content
function mediadownloaderEnclosures( $adjacentmarkup = false ){
    $ret = array();
    global $post;
    $cont = listMedia( get_the_content( $post->ID ) );
    preg_match_all( '/href=[\\\'"](.*)\.mp3[\\\'"]/im', $cont, $matches );
    preg_match_all( '/href=[\\\'"].*getfile\.php\?\=(.*)[\\\'"]/im', $cont, $newmatches );

    // It makes no sense, "there can be only one", but just in case...
    if ( count( $matches ) && count( $matches[1] ) ) $ret = array_unique( array_merge( $matches[1], $newmatches[1] ) );
    
    // Should we get only the MP3 URL's?
    if ( !$adjacentmarkup ) {
        foreach ( $ret as &$r ) if ( '/' == substr( $r, 0, 1 ) ) $r = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://' . $_SERVER['SERVER_NAME'] . $r;
        return $ret;
    
    // Or get all the markup around them?
    } else {
        $markuptemplate = get_option( 'markuptemplate' );
        $adj = array();
        $tablehead = '';
        // For each MP3 URL...
        foreach ( $ret as $r ) {
            $adj[$r] = $r;
            // Dirty magic to get the markup around it...
            $rarr = explode( $r, $cont );
            if ( count( $rarr ) > 1 ) {
                $line = substr( $rarr[0], strripos( $rarr[0], '<tr class="mdTags">' ) );
                $line .= substr( $rarr[1], 0, stripos( $rarr[1], '</tr>' ) ) .'</tr>';
                if ( 'definition-list' == $markuptemplate ) {
                    $line = substr( $line, strripos( $line, '<dl class="mdTags">' ) );
                    $line = substr( $line, 0, stripos( $line, '</dl>' ) ) . '</dl>';
                    $adj[$r] = $line;
                } elseif ( 'table-cells' == $markuptemplate ) {

                    if ( '' == $tablehead ) {
                        $safe_r = str_replace( array('/', '.', ':', '%', '-'), array('\\/', '\\.', '\\:', '\\%', '\\-'), $r );
                        preg_match_all( '/\<table([^\>]*)\>(.*?)'.$safe_r.'(.*?)\<\/table\>/ims', $cont, $adjtable );
                        if ( count( $adjtable ) && count( $adjtable[0] ) ) {
                            $ftable = $adjtable[0][0];
                            $ftable = substr( $ftable, strripos( $ftable, '<table' ) );
                            $tablehead = substr( $ftable, 0, stripos( $ftable, '</thead>' ) ) . '</thead>';
                        }
                    }

                    $adj[$r] = ($tablehead?$tablehead:'<table>') . '<tbody>' . $line . '</tbody></table>';
                }
            }
        }
        return $adj;
    }
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
    global $post;
    $postdate = strtotime( $post->post_date_gmt );
    $t = '';
    $matches = mediadownloaderEnclosures( true );
    foreach ( $matches as $m => $adjacentmarkup ) {
        $postdate -= 2;
        //$t.='<enclosure title="'.basename($m).'" url="'.WP_PLUGIN_URL.'/media-downloader/getfile.php?f='.urlencode($m).'" length="'.mediadownloaderMP3Size($m).'" type="audio/mpeg" />';
        //$t .= '<enclosure title="' . basename( $m ) . '" url="' . ( $m . '.mp3' ) . '" length="' . mediadownloaderMP3Size( $m ) . '" type="audio/mpeg" />';
        $t .= '</item>';
        $t .= '<item>';
        $t .= '<title>' . sprintf( _md( 'Attached file: %1$s - %2$s' ), urldecode( basename( $m ) ), get_the_title($post->ID) ) . '</title>';
        $t .= '<link>' . get_permalink($post->ID) . '#mdfile_' . sanitize_title( basename( urldecode( $m ) ) ) . '</link>';
        $t .= '<description><![CDATA[' . $adjacentmarkup . ']]></description>';
        $t .= '<pubDate>' . date( DATE_RSS, $postdate ) . '</pubDate>';
        $t .= '<guid>' . get_permalink($post->ID) . '#mdfile_' . sanitize_title( basename( urldecode( $m ) ) ) . '</guid>';
        $t .= '<enclosure url="' . ( $m . '.mp3' ) . '" length="' . mediadownloaderMP3Size( $m ) . '" type="audio/mpeg" />';
	}
    echo $t;
    //return $t; 
}
  
add_filter( 'the_content', 'mediadownloader' );

if ( get_option( 'handlefeed' ) ) :
    add_action( 'atom_entry', 'mediadownloaderAtom' );
    //add_action( 'rss_item', 'mediadownloaderRss' );
    add_action( 'rss2_item', 'mediadownloaderRss' );
    // Lowering cache lifetime to 4 hours
    add_filter( 'wp_feed_cache_transient_lifetime', create_function('$a','$newvalue = 4*3600; if ( $a < $newvalue ) $a = $newvalue; return $a;') );
endif;

function mediaDownloaderEnqueueScripts() {
    // If any custom css, we enqueue our php that throws this css
    $customcss = trim( get_option( 'customcss' ) );
    if ( '' != $customcss ) {
        wp_register_style( 'mediadownloaderCss', WP_PLUGIN_URL . '/media-downloader/css/mediadownloader-css.php' );
        wp_enqueue_style( 'mediadownloaderCss' );
    }
    // Enqueuing our javascript
    wp_enqueue_script( 'mediadownloaderJs', WP_PLUGIN_URL . '/media-downloader/js/mediadownloader.js', array('jquery'), date( 'YmdHis', filemtime( dirname(__FILE__) . '/js/mediadownloader.js' ) ), get_option( 'scriptinfooter' ) );
    
    // Passing options to our javascript
    add_action( 'get_header', 'mediaDownloaderLocalizeScript' );
}
    
// Passing options to our javascript
function mediaDownloaderLocalizeScript() {
    global $mdembedplayerdefaultcolors;
    $mdembedcolors = array();
    foreach( $mdembedplayerdefaultcolors as $mdcolor => $mddefault ) {
        $mdembedcolors[$mdcolor] = get_option( $mdcolor . '_embed_color' );
        if ( !trim($mdembedcolors[$mdcolor]) ) $mdembedcolors[$mdcolor] = $mddefault;
    }
    $replaceheaders = get_replaceheaders();
    $playheader = _md( 'Play' );
    if ( array_key_exists( 'play', $replaceheaders ) ) $playheader = $replaceheaders['play'];
    wp_localize_script( 'mediadownloaderJs', 'mdEmbedColors', $mdembedcolors );
    wp_localize_script( 'mediadownloaderJs', 'mdStringTable', array(
        'pluginURL' => WP_PLUGIN_URL . '/media-downloader/',
        'playColumnText' => $playheader,
        'downloadTitleText' => _md( 'Download:' ),
        'playTitleText' => _md( 'Play:' ),
        'stopTitleText' => _md( 'Stop:' ),
    ) );
}

function mediaDownloaderInit() {
    load_plugin_textdomain( 'media-downloader', false, basename( dirname( __FILE__ ) ) . '/languages' );
    /*
    // I'm testing the lines below to avoid problems with symlinks,
    // but it's not over yet...
    $pdir = array_key_exists( 'SCRIPT_FILENAME', $_SERVER ) ? array_shift( explode( '/wp-', $_SERVER["SCRIPT_FILENAME"] ) ) . '/wp-content/plugins/media-downloader' : dirname( plugin_basename( __FILE__ ) );
    load_plugin_textdomain( 'media-downloader', false, $pdir . '/languages/' );
    */
    mediaDownloaderEnqueueScripts();
}
add_action( 'init', 'mediaDownloaderInit' );

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
function sanitizeMarkupTemplate( $t ){
    global $mdmarkuptemplates;
    return sanitizeArray( $t, array_keys( $mdmarkuptemplates ) );
}


// I used these functions below to "internationalize" (localize) some strings,
// left them here for "backward compatibilaziness"

function _md( $t ) {
//    if ( function_exists( 'icl_register_string' ) ) {
//        icl_register_string( 'Media Downloader', $t, $t );
//        return icl_t( 'Media Downloader', $t, $t );
//    } else {
        return __( $t, 'media-downloader' ) ;
//    }
}
function _mde( $t ) {
//    if ( function_exists( 'icl_register_string' ) ) {
//        icl_register_string( 'Media Downloader', $t, $t );
//        echo icl_t( 'Media Downloader', $t, $t );
//    } else {
        return _e( $t, 'media-downloader' ) ;
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
        return _n( $ts, $tp, $n, 'media-downloader' ) ;
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
