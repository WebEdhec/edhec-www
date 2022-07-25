/*global jQuery,URLSearchParams*/

jQuery(function( $ ) {

    $('a[href^="#"]').click(function() {
        var target = $(this.hash);
        // if (target.length == 0) target = $('a[name="' + this.hash.substr(1) + '"]');
        // if (target.length == 0) target = $('html');
        // $('html, body').animate({ scrollTop: target.offset().top - 150 }, 10);
        // return false;
    });

    //Add alt to images professors/researchers views
    $('.view-researchers.researchers-list .researcher-item img, .view-phd-researchers.researchers-list .researcher-item img').attr('alt', ' ');
    //Remove role attribute from language selector
    $('#block-selecteurdelangue').removeAttr('role');
    //Remove alt attribute from brochure links
    $('.paragraph--type--tabulation .paragraph--type--programme .brochure .accordion-body-programme a').removeAttr('alt');
    $('.paragraph--type--tabulation .paragraph--type--programme .brochure .accordion-body-programme a.bn.rouge.arrow_next').attr('aria-label', 'voir parcours');
    //Remove role & aria-expanded from tb-megamenu-submenu
    $('.tb-megamenu-submenu').removeAttr('role').removeAttr('aria-expanded');
    //Restructure brochure button on desktop
    function restrucureBrochureButtonsOnDesktopScreen() {
        var newWindowWidth = $(window).width();
            $('.accordion-button-programme-brochure').each(function() {
                if (newWindowWidth > 900) {
                   $(this).removeAttr('tabindex');
                }
                this.outerHTML = this.outerHTML.replace('<div', '<h3').replace('</div', '</h3');
            })
    }
    restrucureBrochureButtonsOnDesktopScreen();

    function changeParagraphToButtonInSearchersView() {
        $('.p-to-button').each(function() {
            this.outerHTML = this.outerHTML.replace('<p', '<button').replace('</p', '</button');
        })
    }

    changeParagraphToButtonInSearchersView();

    function openTabOnLinkClick () {  

        if ( '' !== window.location.hash ) {
            var targeted_tab = window.location.hash;
            $('.nav-link[data-bs-target]').each(function() {
                var $navLink = $(this);
                if ($navLink.attr('data-bs-target') == targeted_tab) {
                    $navLink.closest('.nav-tabs, .nav-pills').find('.nav-link').not(this).removeClass('active');
                    $('html').animate({ scrollTop: $navLink.offset().top - 300 }, 10, 'linear', function () {
                        $navLink.click();
                    });
                }
            })
            $('.accordion-button').each(function() {
                var $accordionButton = $(this);
                if ($accordionButton.attr('data-bs-target') == targeted_tab) {
                    $('html').animate({ scrollTop: ($accordionButton.offset().top - 300) }, 10, 'linear',  function () {
                        $accordionButton.click();
                    });
                }
            })
            $('.tab-pane').each(function() {
                var $tabPane = $(this);
                if ('#' + $tabPane.attr('id') == targeted_tab) {
                    $('.tab-pane').each(function() {
                        $(this).removeClass('active');
                    })
                    $tabPane.addClass('show active');
                }
            })
        }
    }
    $(window).on('load', openTabOnLinkClick);

    $('.menu.sf-menu .menu-group-title').parent().addClass('menu-group-title-container');
    $('.tb-megamenu-row .menu-group-title').parent().addClass('menu-group-title-container');
    

    //Correction RGAA Tabulation vertiacale
    $('.paragraph--type--tabulation.tabulation-0 .nav-link').on('click',function(){
        var myid = "#"+$(this).parent().parent().attr('id')
         $(myid +' .nav-link').removeClass('active');
        //$('.paragraph--type--tabulation.tabulation-0 .nav-link').removeClass('active');
        $(this).addClass('active');
    })
    
     //Correction RGAA Tabulation horizentale
    $('.paragraph--type--tabulation.tabulation-1 .nav-link').on('click',function(){
        var myid = "#"+$(this).closest("div[id]").attr('id');
         $(myid +' .nav-link').removeClass('active');
        //$('.paragraph--type--tabulation.tabulation-0 .nav-link').removeClass('active');
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

    //RGAA correction : Horizontal Tabs keyboard navigation
    var $verticalNavLink = $('.paragraph--type--tabulation.tabulation-0 .nav-link');

    function verticalTabKeyboardNavigation(){
        var vertActivSlide = $('.paragraph--type--tabulation.tabulation-0 .nav-link.active');
        var vertInactivSlide = $('.paragraph--type--tabulation.tabulation-0 .nav-link:not(.active)');
        
        $verticalNavLink.each(function(){
            $(this).attr('tabindex', '-1');
        })
        $('.paragraph--type--tabulation.tabulation-0 .nav-link.active').each(function(){
            $(this).attr('tabindex', '0');
        });
    }
    verticalTabKeyboardNavigation();
    $verticalNavLink.on('shown.bs.tab', verticalTabKeyboardNavigation);

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