/*global jQuery*/

jQuery(function ($) {
    animateUnanimatedNumbersBoxesWhenInsideTheViewPort();
    // Change contact paragraph format on Screen resize
    $(window).on("resize", function (e) {
        checkScreenSize();
    });

    checkScreenSize();

    function checkScreenSize() {
        var newWindowWidth = $(window).width();
        if (newWindowWidth < 1024) {
            $('.only-desctop').remove();
        }
        else {
            $('.only-mobile').remove();
            $(function () {
                $('.masonry-container').masonry({
                    itemSelector: '.field.box',
                    columnWidth: '',
                    percentPosition: true
                });
            });
        }
    }

    // Fil d'ariane
    // Remove doublon from sticky menu
    function deleteStickyMenuDuplicatedElt() {
        var lastMenuItem = $(".breadcrumb-menu div[data-breadcrumb-menu--level]").last().find('.filter-option-inner-inner');
        var currentMenuItem = $("div[data-breadcrumb-menu--current-menu-element]");
        if (lastMenuItem.text() == currentMenuItem.text() || lastMenuItem.text() == 'Accueil' || lastMenuItem.text() == 'Home') {
            currentMenuItem.remove();
        }
    }

    $('body').on('bcm.select.implanted', '.breadcrumb-menu', function (event, $wrappedSelect) {
        $wrappedSelect.find('select').selectpicker({ noneSelectedText: '' });
    }).on('bcm.created', '.breadcrumb-menu', function () {
        // Menu secondaire mobile
        var mobileParentMenu = $("div[data-breadcrumb-menu--level='3'] option[selected='selected']").text();
        $('#superfish-main-toggle span').html(mobileParentMenu);
        $('#superfish-main').html(mobileParentMenu);

        deleteStickyMenuDuplicatedElt();
    });

    $(".paragraph--type--text table").wrap("<div class='table-responsive'></div>");
    $("img.image-style-vignette-gauche").parent().addClass('vignette-gauche');

    // Cta edito ------------------------------------------------------------------------------------
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.toggle-cta').fadeIn();
        } else {
            $('.toggle-cta').fadeOut();
        }
    });

    $('.toggle-cta').click(function () {
        $("html, body").animate({
            scrollTop: 0
        }, 500);
        return false;
    });

    // Menu Vox block --------------------------------------------------------------------------------

    $(".btn-menuvox").on("click", function () {
        $(".region_menu_edhecvox").slideToggle();
        $(this).toggleClass("close-active");
        $(this).attr('aria-expanded', function (i, attr) {
            return attr == 'true' ? 'false' : 'true'
        });
    })

    //Animation(Show items on scroll) ---------------------------------------------------------------
    function reveal() {
        var $reveals = $('.reveal');
        var windowHeight = window.innerHeight;

        $reveals.each(function () {
            var $itemToAnimate = $(this);
            var revealTop = $itemToAnimate[0].getBoundingClientRect().top;

            if (revealTop <= windowHeight + $itemToAnimate.height() * 0.4) {
                $itemToAnimate.addClass('revealed');
            } else {
                $itemToAnimate.removeClass('revealed');
            }
        })
    }

    function innerItemReveal() {
        var $itemReveals = $('.inner-item-reveal');
        $itemReveals.each(function () {
            var windowHeight = window.innerHeight;
            var $innerItemToAnimate = $(this);
            var revealTop = $innerItemToAnimate[0].getBoundingClientRect().top;

            if (revealTop < windowHeight + $innerItemToAnimate.height()) {
                $innerItemToAnimate.addClass('inner-item-revealed');
            } else {
                $innerItemToAnimate.removeClass('inner-item-revealed');
            }
        })
    }

    function programReveal() {
        var $programs = $('.tab-pane.active .field--name-field-programme-contenu .field__item.field__item__container , '
            + '.node--type-homepage .tab-pane.active .paragraph--type--paragraph-accordion .paragraph_bg,'
            + '.node--type-homepage .tab-pane.active .promoted-actualites .listing,'
            + '.node--type-homepage .view-display-id-block_3 .container .row .listing.cards');
        var windowHeight = window.innerHeight;
        $programs.each(function () {
            var $program = $(this);
            var programTop = this.getBoundingClientRect().top;
            if (programTop < windowHeight - $program.height() * 0.3) {
                $program.addClass('fadeIn-zoomOut');
            }
        });
    }

    $('.tab-content').on('scroll', function () {
        $(window).trigger('scroll');
    });

    $('[data-bs-toggle="pill"]').on('shown.bs.tab', programReveal);

    $('[data-bs-toggle="pill"]').on('hidden.bs.tab', function (event) {
        var targetTabPaneSelector = $(event.target).attr('data-bs-target');
        var $targetTabPane = $(targetTabPaneSelector);
        $targetTabPane.find('.fadeIn-zoomOut').removeClass('fadeIn-zoomOut');
    });

    //Animation page contacts
    function contactsItemReveal() {
        $('.node--type-edito .paragraph--type--tabulation.tabulation-0 .tab-pane.active .paragraph--type--paragraph-accordion .paragraph--contacts .paragraph_bg').addClass('fadeIn-zoomOut');

        $('.node--type-edito .paragraph--type--tabulation.tabulation-1 .tab-content .nav-link').on('shown.bs.tab', function (event) {
            var targetTabPaneSelector = $(event.target).attr('data-bs-target');
            var $targetTabPane = $(targetTabPaneSelector);
            $targetTabPane.find('.paragraph_bg').addClass('fadeIn-zoomOut');
        }).on('hidden.bs.tab', function (event) {
            var targetTabPaneSelector = $(event.target).attr('data-bs-target');
            var $targetTabPane = $(targetTabPaneSelector);
            $targetTabPane.find('.fadeIn-zoomOut').removeClass('fadeIn-zoomOut');
        });
        // $('.node--type-edito .paragraph--type--tabulation.tabulation-1 .nav-link').on('shown.bs.tab', function(event){
        //     contactsItemReveal();
        // });
    }
    // Paragraph contacts accordion
    var newWindowWidth = $(window).width();
    if (newWindowWidth < 500) {
        $('.paragraph--type--contact-item .row .title-accordion').on('click', function () {
            $clickedAccordion = $(this);
            $clickedAccordion.parent().next().slideToggle();
            $clickedAccordion.find('.accordion-contact-chevron').toggleClass('rotate-arrow');
        })
        $('.paragraph--type--encadre .titre-encadre').on('click', function () {
            $clickedEncadre = $(this);
            $clickedEncadre.parent().next().slideToggle();
            $clickedEncadre.toggleClass('collapsed').attr('aria-expanded', 'true' == $clickedEncadre.attr('aria-expanded') ? 'false' : 'true');
            $clickedEncadre.find('.accordion-encadre-chevron').toggleClass('rotate-arrow');
        })
    } else if (newWindowWidth > 500 && newWindowWidth < 900) {
        $('.paragraph--type--encadre .titre-encadre').on('click', function () {
            $clickedEncadre = $(this);
            $clickedEncadre.parent().next().slideToggle();
            $clickedEncadre.toggleClass('collapsed').attr('aria-expanded', 'true' == $clickedEncadre.attr('aria-expanded') ? 'false' : 'true');
            $clickedEncadre.find('.accordion-encadre-chevron').toggleClass('rotate-arrow');
        })
    } else {
        $('.paragraph--type--contact-item .row .title-accordion button').attr('tabindex', '-1');
    }

    function imageDescriptionRevealOnScroll() {
        var $imageTextParagraph = $('.paragraph--type--image-text .position-2');
        var windowHeight = window.innerHeight;

        $imageTextParagraph.each((function () {
            var $imageText = $(this);
            var $imageTopDistance = $imageText[0].getBoundingClientRect().top;

            if ($imageTopDistance < 0.2 * windowHeight) {
                $(this).addClass('imageTextFadeIn')
            } else {
                $(this).removeClass('imageTextFadeIn')
            }
        }))
    }

    reveal();
    innerItemReveal();
    programReveal();
    // contactsItemReveal();

    $(window).on("scroll", function () {
        programReveal();
        reveal();
        innerItemReveal();
        imageDescriptionRevealOnScroll();
        animateUnanimatedNumbersBoxesWhenInsideTheViewPort();
    });

    // Animation counter ----------------------------------------------------------------------------
    function animateUnanimatedNumbersBoxesWhenInsideTheViewPort() {
        $('.paragraph--chjffres_cles .box-number:not(.upcounting-animation-done)').each(function(i){
            var $boxNumber = $(this);
            if (isFullyInsideTheViewport(this)) {
                $boxNumber.addClass('upcounting-animation-done');
                $boxNumber.delay(i * 250).fadeTo(1000, 1);
                var $count = $boxNumber.find('.chiffre .count');
                var decimals = $count.text().split(".")[1] ? $count.text().split(".")[1].length : 0;
                $count.delay(i * 250).prop('Counter', 0).animate({
                    Counter: $count.text()
                }, {
                    duration: 1000,
                    step: function (func) {
                        $count.text(parseFloat(func).toFixed(decimals));
                    }
                });
            }
        })
    }
    //Ancres menus -----------------------------------------------------------------------------------
    $('.nav.nav-tabs').slick({
        infinite: false,
        slidesToShow: 1,
        slidesToScroll: 1,
        variableWidth: true,
        cssEase: 'linear',
        arrows: false,
        speed: 500,
        prevArrow: '<button class="simple slide-arrow prev-arrow slick-prev"><span class="visually-hidden">Previous</span></button>',
        nextArrow: '<button class="simple slide-arrow next-arrow slick-next"><span class="visually-hidden">Next</span></button>',
    });

    $('#ancres-container').slick({
        infinite: false,
        slidesToShow: 3,
        variableWidth: true,
        cssEase: 'linear',
        arrows: true,
        speed: 500,
        prevArrow: '<button class="simple slide-arrow prev-arrow slick-prev"><span class="visually-hidden">Previous</span></button>',
        nextArrow: '<button class="simple slide-arrow next-arrow slick-next"><span class="visually-hidden">Next</span></button>',
    });

    var $ancresLinks = $("#ancres-container .slick-slide");

    $ancresLinks.each(function () {
        $(this).on("click", function () {
            $ancresLinks.removeClass("ancre-active");
            $(this).addClass("ancre-active");
        });
    });

    var $navSlickLinks = $(".nav-tabs .slick-slide");
    var $navSlickLinksButton = $(".nav-tabs .slick-slide button");
    $navSlickLinks.each(function () {
        $(this).on("click", function () {
            $navSlickLinks.removeClass("slick-current slick-active");
            $navSlickLinksButton.removeClass("active");
            $(this).addClass("slick-current slick-active");
            $(".slick-current.slick-active button").addClass("active");
        });
    });

    $(document).ready(function () {
        if ($('#menu-ancres').length != 0) {
            var stickyTop = $('#menu-ancres').offset().top;
            var headerHeight = $("#navbarSupportedContent").height();
            $(window).scroll(function () {
                var windowTop = $(window).scrollTop();
                if (windowTop > stickyTop - headerHeight) {
                    $('#menu-ancres-container').addClass("slided");
                } else {
                    $('#menu-ancres-container').removeClass("slided");
                }
            });
        }
        // Animation tpics hero --------------------------------------------------------------------------
        $(".field-topic").each(function (i) {
            $(this).delay((i++) * 300).fadeTo(1000, 1);
        });
    });

    // Convert programme to accordion sur mobile ------------------------------------------------------
    $(".mobile .accordion-button-programme").on('click', function () {
        $(this).toggleClass('collapsed');
        $(this).parent().next().slideToggle();
    });
    $(".mobile .accordion-button-programme-brochure").click(function () {
        $(this).toggleClass('collapsed');
        $(this).next().slideToggle();
    });
    $(".mobile .accordion-button-programme-brochure").keydown(function (event) {
        if (event.which == '32') {
            event.preventDefault();
            $(this).toggleClass('collapsed');
            $(this).next().slideToggle();
        }
    });

    // Height tabs ----------------------------------------------------------------------------------
    var navHeight = $('.tabulation-0 .only-desctop .nav').height();
    if (navHeight > 700) {
        $(".tabulation-0 .only-desctop .tab-content").css({ 'max-height': navHeight + "px" });
    }


    // Header hero -------------------------------------------------------------------------------------
    if ($(window).height() < 900) {
        var headerH = $('header').height();
        var heightHero = $(window).height() - headerH;
        var heightSlogan = $(window).height() - 450;
        var heightSloganmobile = $(window).height() - 380;

        $(".page-node-type-homepage .field--name-field-slogan-image-desktop .media.media--image").css("height", heightSlogan);
        $(".page-node-type-homepage .field--name-field-slogan-image-mobile .media.media--image").css("height", heightSloganmobile);
        $(".node--type-homepage .hero").css("height", heightHero);
    }

    //Function to manage sticky menu / Main menu---------------------------------------------------------
    function isEmpty(el) {
        return !$.trim(el.html())
    }

    function sticky_relocate() {
        var windowTop = $(window).scrollTop();
        var barTop = $('.region-header').outerHeight();
        var offsetTop = 0;
        var adminMenu = 0;
        var divTop = offsetTop + barTop + 30 - adminMenu;
        var desktopLogo = $('.navbar-brand').not('.logo-mobile').html();
        var mobileLogo = $('.navbar-brand.logo-mobile');
        if (windowTop > divTop) {
            if ($(window).width() > 1200) {
                $('.region.region-nav-main').addClass('stick');
                $('.search-label').show();
                if (isEmpty(mobileLogo)) {
                    mobileLogo.append(desktopLogo);
                    $('.navbar-brand').not('.logo-mobile').empty()
                }
            } else {
                mobileLogo.append(desktopLogo);
                $('.navbar-brand').not('.logo-mobile').empty()
            }
            $('header').addClass('sticky');
            $('.search-label').show();
        } else {
            if ($(window).width() > 1200) {
                $('.search-label').hide();
                $('.navbar-brand').not('.logo-mobile').append(mobileLogo.html());
                mobileLogo.empty()
            } else {
                $('.search-label').show();
                mobileLogo.append(desktopLogo);
                $('.navbar-brand').not('.logo-mobile').empty()
            }
            $('header').removeClass('sticky');
            $('.region.region-nav-main').removeClass('stick');
        }
    }
    $(window).scroll(sticky_relocate);
    sticky_relocate();

    $(".hero").addClass("animated");

    $(".menu-name--main").each(function () {

        var attr = $(this).attr('id');

        if (typeof attr == 'undefined') {
            $(this).find('a').addClass('single-menu-item');
        }
    })
    // Tabulaion (Correction RGAA)
    $('.node--type-edito .paragraph--type--paragraph-accordion .paragraph--type--tabulation .nav-link').on('hidden.bs.tab', function () {
        $(this).attr('tabindex', '-1');
    }).on('shown.bs.tab', function () {
        $(this).attr('tabindex', '0');
    });

    // Word counter
    function wordCounter() {
        var targetToCountFrom = $('article').text();
        var regex = /\s+/gi;
        var wordCount = targetToCountFrom.trim().split(regex).length;
        var lectureTime = Math.ceil(wordCount / 350);
        if (lectureTime < 1) {
            lectureTime = 1;
        }
        $('.js-lecture-time').html(lectureTime + 'min');
    }
    wordCounter();

    // Remove animation from background HP
    $('.paragraph--type--image-text').parent().removeClass('reveal');

    // Menu parent button retour
    $(".node--type-edito .menu-parent").append($.trim($(".breadcrumb .breadcrumb-item:last").prev().html()));

    // Add aria-current attribute to the last element in Breadcrumb
    $(".breadcrumb-item").last().attr('aria-current', 'page');

    //Remove main menu from DOM in mobile screens
    if (newWindowWidth <= 1200) {
        $('#block-mainnavigation').remove();
    }
    //Remove links from level 1 and level 2 of side menu
    if (newWindowWidth < 500) {
        $('#mm-1 .menu-name--main .mm-listitem__text, #mm-3 .menu-name--main .mm-listitem__text').each(function () {
            if ($(this).next().length) {
                $(this).next().text($(this).text());
                $(this).remove();
            }
        })
    }

    //Remove <separator> from sticky menu
    function remove_separator_from_sticky_menu() {
        $('.breadcrumb-menu button').on('click', function () {
            $(this).next().find('li').each(function () {
                if ($(this).text() === '<separator>') {
                    $(this).find('a').remove();
                }
            })
        })
    }
    remove_separator_from_sticky_menu();

    $('body').on('bcm.select.implanted', '.breadcrumb-menu', function (event, $wrappedSelect) {
        remove_separator_from_sticky_menu();
    });
    

    function isFullyInsideTheViewport(elt) {
        var $elt = $(elt);
        if ( 0 === $elt.length )
            return false;
        
        
        var $window = $(window),
            eletOffsetTop = $elt.offset().top,
            windowScrollTop = $window.scrollTop();

        return (eletOffsetTop < windowScrollTop + $window.outerHeight() - $elt.innerHeight()) 
            && (eletOffsetTop > windowScrollTop);
    }
});