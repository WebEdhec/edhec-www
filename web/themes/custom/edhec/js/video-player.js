jQuery(function( $ ) {
    var $video = $(".media-video video");
    var $playBtn = $('.play-btn');

    // $video.removeAttr("controls");

    $playBtn.click(function(){
        $(this).parent().find("video").get(0).play();
         $(this).hide();
    })

    $video.on('play', function(){
        $(this).next('.play-btn').hide();
        $video.attr('controls', 'controls');
    })

    $video.on('pause', function(){
         $(this).next('.play-btn').show();
    })

     $video.on('ended',function(){
        $(this).next('.play-btn').show();
    });

    $(".slide-arrow").click(function(){
        $("div[aria-hidden='true'] video").trigger('pause');
    })
});