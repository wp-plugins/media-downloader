var pluginURL='';
function initMediaDownloader(){
    $("table.mediaTable.embedPlayer th:contains('Download')").each(function () {
        var thcont='<th>Play</th>';
        if($(this).parents('table.mediaTable').hasClass('embedposafter')){
            $(this).after(thcont);
        }else{
            $(this).before(thcont);
        }
    });
    $('table.mediaTable.embedPlayer td.mediaDownload a').each(function (){
        if($(this).attr('href').indexOf('getfile.php')>-1){
          pluginURL=$(this).attr('href').split('getfile.php?f=')[0];
          var link=$(this).attr('href').split('getfile.php?f=')[1]+'.mp3';
        }else{
          pluginURL='/wp-content/plugins/media-downloader/';
          var link=$(this).attr('href');
        }
console.log(link)
        var title=$(this).attr('title').replace('Download:','Play:');
        var text=$(this).html().replace('Download:','Play:');
        var tdcont='<td class="mediaPlay"><a href="'+link+'" title="'+title+'">'+text+'</a></td>';
        if($(this).parents('table.mediaTable').hasClass('embedposafter')){
            $(this).parent().after(tdcont);
        }else{
            $(this).parent().before(tdcont);
        }
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
    return '<tr class="mediaPlayer"><td colspan="3" align="center">' + '<object type="application/x-shockwave-flash" name="audioplayer_1" style="outline: none" data="'+pluginURL+'js/audio-player.swf?ver=2.0.4.1" width="100%" height="25" id="audioplayer_1">' + '<param name="bgcolor" value="#F8FAF7">' + '<param name="movie" value="'+pluginURL+'js/audio-player.swf?ver=2.0.4.1">' + '<param name="menu" value="false">' + '<param name="flashvars" value="animation=yes&amp;encode=no&amp;initialvolume=80&amp;remaining=no&amp;noinfo=no&amp;buffer=5&amp;' + 'checkpolicy=no&amp;rtl=no&amp;bg=E7E7E7&amp;text=333333&amp;leftbg=CCCCCC&amp;lefticon=333333&amp;volslider=666666&amp;' + 'voltrack=FFFFFF&amp;rightbg=B4B4B4&amp;rightbghover=999999&amp;righticon=333333&amp;righticonhover=FFFFFF&amp;' + 'track=FFFFFF&amp;loader=A2CC39&amp;border=CCCCCC&amp;tracker=DDDDDD&amp;skip=666666&amp;autostart=yes&amp;soundFile=' + url + '&amp;playerID=audioplayer_1"></object></td></tr>';
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
    $('tr.mediaPlayer').find('object').remove().end().remove();
    playingURL='';
}
