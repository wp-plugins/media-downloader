var mediadownloaderPluginURL = '';
function initMediaDownloader() {
    jQuery("table.mediaTable.embedPlayer th.mediaDownload").each( function () {
        var thcont='<th class="mediaPlay">Play</th>';
        if ( jQuery(this).parents('table.mediaTable').hasClass('embedposafter') ) {
            jQuery(this).after(thcont);
        } else {
            jQuery(this).before(thcont);
        }
    } );
    jQuery('table.mediaTable.embedPlayer td.mediaDownload a').each( function () {
        if ( jQuery(this).attr('href').indexOf('getfile.php')>-1 ) {
          mediadownloaderPluginURL = jQuery(this).attr('href').split('getfile.php?f=')[0];
          var link = jQuery(this).attr('href').split('getfile.php?f=')[1]+'.mp3';
        } else {
          mediadownloaderPluginURL = '/wp-content/plugins/media-downloader/';
          var scripts = jQuery('script[src*="js/mediadownloader.js"]');
          if( scripts.length ) mediadownloaderPluginURL = scripts[0].src.split('js/mediadownloader.js')[0];
          var link = jQuery(this).attr('href');
        }
        var title = jQuery(this).attr('title').replace('Download:','Play:');
        var text = jQuery(this).html().replace('Download:','Play:');
        var arrrel = (jQuery(this).attr('rel')+'').split(';');
        for ( var r=0; r<arrrel.length; r++ ) {
            var arrparm = arrrel[r].split(':');
            if ( arrparm.length == 2 ) {
                if ( arrparm[0] == 'mediaDownloaderPlayText' ) {
                    text = unescape( arrparm[1].replace( /\+/g, ' ' ) );
                    break;
                }
            }
        }
        var tdcont = '<td class="mediaPlay"><a href="'+link+'" title="'+title+'">'+text+'</a></td>';
        if ( jQuery(this).parents('table.mediaTable').hasClass('embedposafter') ) {
            jQuery(this).parent().after(tdcont);
        } else {
            jQuery(this).parent().before(tdcont);
        }
    } );
    jQuery('table.mediaTable.embedPlayer td.mediaPlay a').click( function () {
        var link=jQuery(this).attr('href');
        if( link != mediaplayerPlayingURL ){
            mediaplayerPlay( link, jQuery(this).html().replace('Play:','') );
            jQuery('a.mediaStop').removeClass('mediaStop');
            jQuery('td.mediaPlaying').removeClass('mediaPlaying');
            jQuery(this).addClass('mediaStop').parents('td.mediaPlay').addClass('mediaPlaying');
        } else {
            mediaplayerStop();
            jQuery(this).removeClass('mediaStop').parents('td.mediaPlaying').removeClass('mediaPlaying');
        }
        return false;
    } );
}

//$.noConflict();
jQuery(document).ready(function($) {
    initMediaDownloader();
});

function mediaplayerStr( url, title, tdcolspan ) {
    var strColors = '';
    var mdBgColor = 'FFF';
    if ( typeof(mdEmbedColors) != 'undefined' ) {
        for ( i in mdEmbedColors ) strColors += i + '=' + mdEmbedColors[i] + '&amp;';
        mdBgColor = mdEmbedColors.bg;
    }
    if ( typeof(tdcolspan) == 'undefined' ) tdcolspan = 3;
    return '<tr class="mediaPlayer"><td colspan="'+tdcolspan+'" align="center">' + '<object type="application/x-shockwave-flash" name="audioplayer_1" style="outline: none" data="'+mediadownloaderPluginURL+'js/audio-player.swf?ver=2.0.4.1" width="100%" height="25" id="audioplayer_1">' + '<param name="bgcolor" value="#' + mdBgColor + '">' + '<param name="movie" value="'+mediadownloaderPluginURL+'js/audio-player.swf?ver=2.0.4.1">' + '<param name="menu" value="false">' + '<param name="flashvars" value="animation=yes&amp;encode=no&amp;initialvolume=80&amp;remaining=no&amp;noinfo=no&amp;buffer=5&amp;' + 'checkpolicy=no&amp;rtl=no&amp;' + strColors + 'autostart=yes&amp;soundFile=' + escape(url) + '&amp;playerID=audioplayer_1"></object></td></tr>';
}
    
var mediaplayerPlayingURL = '';
function mediaplayerPlay( url, title ) {
    if( url != mediaplayerPlayingURL ) {
        mediaplayerStop();
        var linktr = jQuery('a[href="'+url+'"]').first().parents('tr');
        var tdcolspan = 0;
        linktr.children('td').each( function () {
            var currentcolspan = parseInt( '0' + jQuery(this).attr('colspan'), 10 );
            if ( !currentcolspan ) currentcolspan = 1;
            tdcolspan += currentcolspan;
        } );
        linktr.after( mediaplayerStr( url, title, tdcolspan ) );
        mediaplayerPlayingURL = url;
    }
}

function mediaplayerStop() {
    jQuery('tr.mediaPlayer').find('object').remove().end().remove();
    mediaplayerPlayingURL = '';
}
