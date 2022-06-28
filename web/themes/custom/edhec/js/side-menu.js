/*global jQuery, drupalSettings, Mmenu*/

drupalSettings.responsive_menu.custom = {
    options: {
        extensions: [
            "position-front",
            "theme-dark",
            "position-right",
            "pagedim-black",
            "multiline",
        ],

        iconPanels: true,

        navbars: [
            {
                position: "top",
                content: ['close'],
            },
            {
                position: 'bottom',
                content: [jQuery(".links").clone()[0]]
            },

        ]
    }
}

jQuery(function( $ ) {

    var $mburgerButton = $(".mburger--collapse");
    var $mmenuCloseButton = $(".mm-btn--close");
    var $mmenuBody = $("#off-canvas");
    var $body = $("body");
    var $mmenuBlocker = $(".mm-wrapper__blocker");

    $mburgerButton.on("click", function(event){
        event.preventDefault();
        if(778 > $(window).width()) {
            if($body.hasClass("mm-wrapper--opened")){
                $mmenuBody.animate({right: "-440px"}, function(){
                 $mmenuBlocker.fadeOut( "slow", "linear", function() {
                    $body.removeClass("mm-wrapper--opened");
                    $mburgerButton.removeClass("menu-opened");
                });
                $mburgerButton.removeClass("menu-opened");
            });
            } else {
                $mmenuBody.animate({right: ""});
                $mmenuBlocker.fadeIn( "slow", "linear", function() {
                    $body.addClass("mm-wrapper--opened");
                });
                $mburgerButton.addClass("menu-opened");
            }
            } else {
                $mmenuBody.animate({right: "-3px"});
                $mmenuBlocker.fadeIn( "slow", "linear", function() {
                    $body.addClass("mm-wrapper--opened");
                });
            }
    })

    $("#mm-0").add($mmenuCloseButton).click(function(event){
        event.preventDefault();
        $mmenuBody.animate({right: "-440px"}, function(){
            $mmenuBlocker.fadeOut( "slow", "linear", function() {
                $body.removeClass("mm-wrapper--opened");
            });
            $mburgerButton.removeClass("menu-opened");
        });
    })
    $("a:not([href])").remove();
     var newWindowWidth = $(window).width();
        if (newWindowWidth < 500) {
            $("#mm-0").remove();
            $(document).on('keydown', function(event) {
                var $focused = $(':focus').html();
                // console.log($focused);
               if ($focused == 'En') {
                    $mburgerButton.removeClass("menu-opened");
                }
                if (event.key == 'Escape') {
                    $mburgerButton.removeClass("menu-opened");
                }
            });
        }
});