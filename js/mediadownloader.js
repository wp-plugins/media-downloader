var pluginURL='';
function initMediaDownloader(){
    $("table.mediaTable.embedPlayer th:contains('Download')").after('<th>Play</th>');
    $('table.mediaTable.embedPlayer td.mediaDownload a').each(function (){
        pluginURL=$(this).attr('href').split('getfile.php?f=')[0];
        var link=$(this).attr('href').split('getfile.php?f=')[1]+'.mp3';
        var title=$(this).attr('title').replace('Download:','Play:');
        var text=$(this).html().replace('Download:','Play:');
        $(this).parent().after('<td class="mediaPlay"><a href="'+link+'" title="'+title+'">'+text+'</a></td>');
    });
    $('table.mediaTable.embedPlayer td.mediaPlay a').click(function (){
        var link=$(this).attr('href');
        if(link!=playingURL){
            mediaplayerPlay(link, $(this).html().replace('Play:',''));
            $('a.mediaStop').removeClass('mediaStop');
            $(this).addClass('mediaStop');
        }else{
            mediaplayerStop();
            $(this).removeClass('mediaStop');
        }
        return false;
    });
}

$(document).ready(initMediaDownloader);

function playerStr(url, title){
    return '<tr class="mediaPlayer"><td colspan="3" align="center"><object type="application/x-shockwave-flash" width="100%" height="20" data="'+pluginURL+'js/xspf_player_slim.swf?song_url='+url+'&amp;song_title='+title+'&amp;autoplay=true"><param name="movie" value="'+pluginURL+'js/xspf_player_slim.swf?song_url='+url+'&amp;song_title='+title+'&amp;autoplay=true" /></object></td></tr>';
    //return '<tr class="mediaPlayer"><td colspan="3" align="center"><object width="300" height="42"><param name="src" value="'+url+'"><param name="autoplay" value="true"><param name="controller" value="true"><param name="bgcolor" value="#FF9900"><embed src="'+url+'" type="application/x-mplayer2" autostart="true" loop="false" width="300" height="42" controller="true" bgcolor="#FF9900"></embed><br /><small><a href="'+url+'">MP3 plugin not found. Download MP3 file.</a></small></object></td></tr>';
    //return '<tr class="mediaPlayer"><td colspan="3" align="center"><embed src="http://www.odeo.com/flash/audio_player_standard_gray.swf" quality="high" width="300" height="42" allowScriptAccess="always" wmode="transparent" type="application/x-shockwave-flash" flashvars="valid_sample_rate=true&amp;external_url='+url+'" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed></td></tr>';
}
    
var playingURL='';
function mediaplayerPlay(url, title){
    if(url!=playingURL){
        mediaplayerStop();
        $('a[href='+url+']').parent().parent().after(playerStr(url, title));
        playingURL=url;
    }
}

function mediaplayerStop(){
    $('tr.mediaPlayer').remove();
    playingURL='';
}
