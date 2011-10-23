<?php
/*
Plugin Name: Media Downloader
Plugin URI: http://ederson.peka.nom.br
Description: Media Downloader plugin lists MP3 files from a folder by replacing the [media] smarttag.
Version: 0.1.93
Author: Ederson Peka
Author URI: http://ederson.peka.nom.br
*/

$mdencodings = array( 'UTF-8', 'ISO-8859-1' );
$mdsettings = array(
    'mp3folder'=>'sanitizeRDir',
    'sortfiles'=>'sanitizeBoolean',
    'reversefiles'=>'sanitizeBoolean',
    'showtags'=>null,
    'customcss'=>null,
    'removeextension'=>'sanitizeBoolean',
    'embedplayer'=>'sanitizeBoolean',
    'embedwhere'=>'sanitizeBeforeAfter',
    'tagencoding'=>'sanitizeTagEncoding',
    'cachedir'=>'sanitizeWDir'
);
$mdtags = array( 'title', 'artist', 'album', 'year', 'genre', 'comments', 'track_number', 'bitrate', 'filesize', 'directory', 'file' );

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

function calculatePrefix($arr){
    $prefix='';
    if(count($arr)){
        $prefix=strip_tags(array_pop($arr));
        foreach($arr as $i){
            for($c=1;$c<strlen($i);$c++){
                if(strncasecmp($prefix,$i,$c)!=0) break;
            }
            $prefix=substr($prefix,0,$c-1);
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

function listMedia($t){
    global $mdtags, $tagvalues;

    $mdir = '/' . get_option( 'mp3folder' );
    $murl = get_option( 'siteurl' ) . $mdir;
    $mrelative = str_replace('http://','',$murl); $mrelative = explode( '/', $mrelative ); array_shift($mrelative); $mrelative = '/'.implode('/', $mrelative);
    $mpath = ABSPATH . substr($mdir, 1);
    
    $membedwhere = get_option( 'embedwhere' ) ;

    $mdoencode = ( get_option( 'tagencoding' ) != 'UTF-8' ) ;
    
    $msort = ( get_option( 'sortfiles' ) == true ) ;

    $mreverse = ( get_option( 'reversefiles' ) == true ) ;

    $mshowtags = array_intersect( explode( ',', get_option( 'showtags' ) ), $mdtags ) ;
    if ( !count($mshowtags) ) $mshowtags = array( $mdtags[0] ) ;

    $t=preg_replace('/<p>\[media:([^\]]*)\]<\/p>/i','[media:$1]',$t);
    preg_match_all('/\[media:([^\]]*)\]/i',$t,$matches);
    if(count($matches)){
        foreach($matches[1] as $folder){
            $pfolder = array_filter( explode( '/', $folder ) );
            foreach( $pfolder as &$p ) $p = rawurlencode( $p );
            $ufolder = implode( '/', $pfolder );
            $t=str_replace('<p>[media:'.$folder.']</p>', '[media:'.$folder.']', $t);
            $ihtml='';
            $ifiles=array();
            $ititles=array();
            $zip=array();
            $pdf=array();
            $epub=array();
            $ipath = $mpath . '/' . $folder;
            if ( is_dir($ipath) ){
                $idir = dir( $ipath );
                while (false !== ($ifile = $idir->read())) {
                    if(substr($ifile,-4)=='.mp3') $ifiles[]=substr($ifile,0,-4);
                    if(substr($ifile,-4)=='.zip') $zip[]=$ifile;
                    if(substr($ifile,-4)=='.pdf') $pdf[]=$ifile;
                    if(substr($ifile,-5)=='.epub') $epub[]=$ifile;
                }
            }
            if ( count($ifiles) ){
                $prefix = calculatePrefix( $ifiles ) ;
                $hlevel = explode( '/', $folder ) ; $hlevel = array_pop( $hlevel ) ;

                $tagvalues = array() ;
                foreach ( $mshowtags as $mshowtag ) $tagvalues[$mshowtag] = array() ;
                foreach ( $ifiles as $ifile ) {
                    $finfo = mediadownloaderMP3Info( $mrelative.'/'.$folder.'/'.$ifile ) ;
                    $ftags = $finfo['tags']['id3v2'] ;
                    $ftags['bitrate'] = array( floatval( $finfo['audio']['bitrate'] ) / 1000 . 'kbps' ) ;
                    $ftags['filesize'] = array( byte_convert( $finfo['filesize'] ) ) ;
                    $ftags['directory'] = array( $hlevel ) ;
                    $ftags['file'] = array( $ifile ) ;
                    foreach ( $mshowtags as $mshowtag )
                        $tagvalues[$mshowtag][$ifile] = ( 'comments' == $mshowtag ) ? Markdown( $ftags[$mshowtag][0] ) : $ftags[$mshowtag][0] ;
                }
                $tagprefixes = array() ;
                foreach ( $mshowtags as $mshowtag )
                    if ( 'file' == $mshowtag || 'title' == $mshowtag )
                        $tagprefixes[$mshowtag] = calculatePrefix( $tagvalues[$mshowtag] );
                if ( $msort ) {
                    sort( &$ifiles );
                    uasort( &$ifiles, 'ordenaPorTags' );
                }
                if ( $mreverse ) $ifiles = array_reverse( $ifiles );
                foreach ( $ifiles as $ifile ) {
                    $ititle = '';
                    foreach ( $mshowtags as $mshowtag ) {
                        $tagvalue = $tagvalues[$mshowtag][$ifile] ;
                        if ( '' != $tagvalue ) {
                            if ( '' != $tagprefixes[$mshowtag] )
                                $tagvalue = str_replace( $tagprefixes[$mshowtag], '', $tagvalue ) ;
                            $tagvalue = str_replace( $prefix, '', $tagvalue );
                            $tagvalue = replaceUnderscores( $tagvalue ) ;
                            if ( $mdoencode ) $tagvalue = utf8_encode( $tagvalue ) ;
                            $ititle .= '<dt class="mdTag'.$mshowtag.'">'.ucwords( _md( $mshowtag ) ).':</dt>' ;
                            $ititle .= '<dd class="mdTag'.$mshowtag.'">'.$tagvalue.'</dd>' ;
                        }
                    }
                    if ( '' != $ititle )
                        $ititle = '<dl class="mdTags">' . $ititle . '</dl>' ;
                    $ititles[$ifile] = $ititle ;
                }
                
                if ( count($zip)+count($pdf)+count($epub) ) $ihtml .= '<table class="bookInfo">
<thead>
<tr>
<th class="chapterCol">'._md( 'By Chapter' ).'</th>
<th class="wholeBookCol">'._md( 'Whole Book' ).'</th>
</tr>
</thead>
<tbody>
<tr>
<td class="chapterCol">';

                $tableClass = array( 'mediaTable' );
                if ( TRUE == get_option( 'embedplayer' ) ) $tableClass[] = 'embedPlayer';
                $tableClass[] = 'embedpos' . $membedwhere ;
                $ihtml .= '<table class="' . implode( ' ', $tableClass ) . '">' . "\n";
                $ihtml .= '<thead>
<tr>
<th>&nbsp;</th>
<th>'._md('Download').'</th>
</tr>
</thead>
<tbody>';
                foreach ( $ifiles as $ifile ) {
                    $showifile = $ifile ;
                    if ( array_key_exists( 'file', $tagprefixes ) )
                        $showifile = str_replace( $tagprefixes['file'], '', $showifile ) ;
                    $showifile = replaceUnderscores( $showifile );
                    $ititle = $ititles[$ifile] ;
                    $ititle = str_replace( $prefix, '', $ititle ) ;

                    // 20100107 - I took it away: strtoupper( $hlevel )
                    $ihtml .= '<tr>'."\n" ;
                    $ihtml .= '<td class="mediaTitle">'.$ititle.'</td>'."\n" ;
                    $ihtml .= '<td class="mediaDownload"><a href="'.$mrelative.'/'.($ufolder?$ufolder.'/':'').rawurlencode( $ifile ).'.mp3" title="' . htmlentities( $showifile ) . '">'._md( 'Download' ).( $ifile?': '.$showifile:'' ).'</a></td>'."\n" ;
                    $ihtml .= '</tr>'."\n" ;
                }
                $ihtml .= '</tbody></table>'."\n" ;
                if ( count($zip)+count($pdf)+count($epub) ) {
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
            }elseif( count($zip)+count($pdf)+count($epub) ){
                $afolder = explode( '/', $folder ) ;
                for ( $a=0; $a<count($afolder); $a++ ) $afolder[$a] = rawurlencode( $afolder[$a] ) ;
                $cfolder = implode( '/', $afolder ) ;
                $allf = array_merge($zip, $pdf, $epub);
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
            $t = str_replace( '[media:'.$folder.']', $ihtml, $t ) ;
        }
    }
    return $t ;
}
function ordenaPorTags($a, $b){
    global $tagvalues;
    return strnatcmp($tagvalues['title'][$a], $tagvalues['title'][$b]);
}

function mediadownloader($t){
    $t=listMedia($t);
    if ( TRUE == get_option( 'removeextension' ) ) {
        $t=preg_replace(
            '/href\=[\\\'\"](.*)\.mp3[\\\'\"]/im',
            "href=\"".WP_PLUGIN_URL."/media-downloader/getfile.php?f=$1\"",
            $t
        );
    }
    $t = listarCategorias($t);
    $t = listarCategoriasEx($t);
    $t = listarIdiomas($t);
    return $t;
}
function corrige_qtrans_excludeUntransPosts($where){
    if(function_exists('qtrans_getLanguage')){
        $l=qtrans_getLanguage();
        if(trim($l)){
	        global $q_config, $wpdb;
	        if($q_config['hide_untranslated']) {
		        $where .= " AND post_content LIKE '%<!--:".$l."-->%'";
	        }
	    }
	}
	return $where;
}

function mediadownloaderMP3Length($f){
    // Initialize getID3 engine
    $getID3 = new getID3;
    // Analyze file and store returned data in $ThisFileInfo
    $ThisFileInfo = $getID3->analyze($filename);
    // Optional: copies data from all subarrays of [tags] into [comments] so
    // metadata is all available in one location for all tag formats
    // metainformation is always available under [tags] even if this is not called
    getid3_lib::CopyTagsToComments($ThisFileInfo);
}

function mediadownloaderMP3Info( $f ) {
    $relURL = str_replace( 'http://'.$_SERVER['SERVER_NAME'], '', get_option( 'siteurl' ) );
    $f = ABSPATH . str_replace( $relURL, '', $f ) . '.mp3';
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
        $ThisFileInfo = $getID3->analyze($f);
        if( $cachedir && is_writeable( ABSPATH . '/' . $cachedir ) ) file_put_contents( $cachefile, serialize( $ThisFileInfo ) );
        return $ThisFileInfo;
    }
}
function mediadownloaderMP3Size($f){
    $f=ABSPATH . substr($f,1) . '.mp3';
    if(!file_exists($f)) $f=urldecode($f);
    return filesize($f);
}
function mediadownloaderEnclosures(){
    $ret=array();
    global $post;
    $cont=listMedia(get_the_content($post->ID));
    preg_match_all('/href=[\\\'"](.*)\.mp3[\\\'"]/im',$cont,$matches);
    preg_match_all('/href=[\\\'"].*getfile\.php\?\=(.*)[\\\'"]/im',$cont,$newmatches);
    if(count($matches) && count($matches[1])) $ret=array_unique(array_merge($matches[1], $newmatches[1]));
    return $ret;
} 
function mediadownloaderAtom(){
    $t='';
    $matches=mediadownloaderEnclosures();
    foreach($matches as $m){
        //$t.='<link rel="enclosure" title="'.basename($m).'" length="'.mediadownloaderMP3Size($m).'" href="'.WP_PLUGIN_URL.'/media-downloader/getfile.php?f='.urlencode($m).'" type="audio/mpeg" />';
        $t.='<link rel="enclosure" title="'.basename($m).'" length="'.mediadownloaderMP3Size($m).'" href="'.($m.'.mp3').'" type="audio/mpeg" />';
	}
    echo $t;
    //return $t;
}
function mediadownloaderRss(){
    $t='';
    $matches=mediadownloaderEnclosures();
    foreach($matches as $m){
        //$t.='<enclosure title="'.basename($m).'" url="'.WP_PLUGIN_URL.'/media-downloader/getfile.php?f='.urlencode($m).'" length="'.mediadownloaderMP3Size($m).'" type="audio/mpeg" />';
        $t.='<enclosure title="'.basename($m).'" url="'.($m.'.mp3').'" length="'.mediadownloaderMP3Size($m).'" type="audio/mpeg" />';
	}
    echo $t;
    //return $t; 
}

add_filter('get_previous_post_where', 'corrige_qtrans_excludeUntransPosts');
add_filter('get_next_post_where', 'corrige_qtrans_excludeUntransPosts');
add_filter('posts_where_request', 'corrige_qtrans_excludeUntransPosts');
add_filter('the_content', 'mediadownloader');
add_action('atom_entry', 'mediadownloaderAtom');
//add_action('rss_item', 'mediadownloaderRss');
add_action('rss2_item', 'mediadownloaderRss');

$customcss = trim( get_option( 'customcss' ) );
if ( '' != $customcss ) {
    wp_register_style('mediadownloaderCss', WP_PLUGIN_URL."/media-downloader/css/mediadownloader-css.php");
    wp_enqueue_style('mediadownloaderCss');
}
wp_enqueue_script('jQuery', 'http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js');
wp_enqueue_script('mediadownloaderJs', WP_PLUGIN_URL."/media-downloader/js/mediadownloader.js" );

function tiraDoParagrafo($tag, $t){
    return str_replace('<p>'.$tag.'</p>', $tag, $t);
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

add_action('admin_menu', 'mediadownloader_menu');

function mediadownloader_menu() {
    add_options_page('Media Downloader Options', 'Media Downloader', 'administrator', 'mediadownloader-options', 'mediadownloader_options');
}

function mediadownloader_options() {
    require_once("mediadownloader-options.php");
}

add_action('admin_init', 'mediadownloader_settings');

function mediadownloader_settings() {
    global $mdsettings;
    foreach( $mdsettings as $mdsetting => $mdsanitizefunction ) register_setting( 'md_options', $mdsetting, $mdsanitizefunction );
}

function sanitizeRDir( $d ){
    return is_readable( ABSPATH . $d ) ? $d : '' ;
}
function sanitizeWDir( $d ){
    return is_writeable( ABSPATH . $d ) ? $d : '' ;
}
function sanitizeArray( $i, $a ){
    return in_array( $i, $a ) ? $i : '' ;
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

?>
