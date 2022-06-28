/*global jQuery,URLSearchParams*/

jQuery(function( $ ) {
    // $('.node--type-edito .paragraph--type--tabulation.tabulation-1 .slick-slide .nav-link').on('click',function(){
    //     $('.node--type-edito .paragraph--type--tabulation.tabulation-1 .slick-slide .nav-link').removeClass('active');
    //     $(this).addClass('active');
    // })

    function openTabOnLinkClick () {
        $('a').each(function(){
            var $link = $(this);
            var $href = $link.prop('href');
            if ($href.indexOf("#nav") >= 0) {
                var $destination = $href.substring($href.indexOf("#"), $href.length);
                var $tab_id = $destination.substring($destination.indexOf("nav"), $destination.length);
            }
            $link.on('click', function(event){
                $('.nav-link').each(function() {
                    $navLink = $(this);
                    if ($navLink.prop('id') == $tab_id) {
                        $navLink.click();
                    }
                })
                $('.tab-pane').each(function() {
                    var $tabPane = $(this);
                    if ($tabPane.attr('aria-labelledby') == $tab_id) {
                        $('.tab-pane').each(function() {
                            $(this).removeClass('active');
                        })
                        $tabPane.addClass('show active');
                    }
                })
            })
        })
    }

    openTabOnLinkClick();

    //Correction RGAA Tabulation vertiacale
    $('.paragraph--type--tabulation.tabulation-0 .nav-link').on('click',function(){
        $('.paragraph--type--tabulation.tabulation-0 .nav-link').removeClass('active');
        $(this).addClass('active');
    })

    //RGAA correction : Horizontal Tabs keyboard navigation
    var $navLink = $('.paragraph--type--tabulation.tabulation-1 .nav-link');

    function horizontalTabsKeyboardNavigation(){
        var $activSlide = $('.paragraph--type--tabulation.tabulation-1 .slick-slide.slick-active');
        var $inactivSlick = $('.paragraph--type--tabulation.tabulation-1 .slick-slide:not(.slick-active)');

        $navLink.each(function(){
            $(this).attr('tabindex', '-1').attr('aria-selected', 'false').removeAttr('aria-hidden');
        })
        $('.paragraph--type--tabulation.tabulation-1 .nav-link.active').each(function(){
            $(this).attr('tabindex', '0').attr('aria-selected', 'true');
        });
        $inactivSlick.each(function(){
            $(this).removeAttr('aria-hidden');
        })
        $activSlide.each(function(){
            $(this).attr('aria-hidden', 'false');
        })
    }
    horizontalTabsKeyboardNavigation();
    $navLink.on('shown.bs.tab', horizontalTabsKeyboardNavigation);

    //Correction RGAA :
    $('#edit-lieu').attr('title', 'Ville');
    $('#edit-lieu').before('<label for="edit-lieu" class="visually-hidden">Ville</label>');

    $('.view-publications-recherche .author-container a').each(function(){
        if($(this).attr('href').match('^/auteur-externe')) {
            $(this).css('cursor', 'default');
            $(this).css('color', '#5d5c5c');
            $(this).on('click', function(event){
                event.preventDefault();
            });
        }
    })

    $(document).ajaxComplete(function() {
        $('.view-publications-recherche .author-container a').each(function(){
            if($(this).attr('href').match('^/auteur-externe')) {
                $(this).css('cursor', 'default');
                $(this).css('color', '#5d5c5c');
                $(this).on('click', function(event){
                    event.preventDefault();
                });
            }
        })
    })
        var initialPartnerResultsCount = $('.view-partenaires-internationaux .initial-partner-results-number').text();
        $('.view-partenaires-internationaux-map .partner-results-number').text(initialPartnerResultsCount);
        $(document).ajaxComplete(function() {
            var initialPartnerResultsCount = $('.view-partenaires-internationaux .initial-partner-results-number').text();
            $('.view-partenaires-internationaux-map .partner-results-number').text(initialPartnerResultsCount);
        });
        
        $('span.separator').parent().addClass('separator');

        // RGAA
        $('.slick-dots').removeAttr('role');
        $('.slick-dots li:not(.slick-active) button').attr('aria-selected', 'false');
        $('.view-agenda summary .summary').attr('aria-hidden', 'true');

    });