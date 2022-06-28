/*global jQuery */

jQuery(function( $ ) {
    // RGAA : disbled links
            // Init and afterchange slick events
    $('body').on('init afterChange', function(event, slick){
        try {
            if(slick.constructor.name == 'Slick') {
                // Remove all 'disabled' attributes from all arrows
                slick.$prevArrow.add(slick.$nextArrow).prop('disabled', false)
                // Add 'disabled' attribute when the edge is hitted
                                                      .filter('.slick-disabled').prop('disabled', true);
                slick.$slides.filter('.slick-active').find('a').removeAttr('tabindex');
                slick.$slides.not('.slick-active').find('a').attr('tabindex', '-1');
            }
        } catch(e) {
            console.log(e);
        }
    });
//Show carousel controls only if there is hidden items
     $('.carrousel-media, .carrousel-1-3, .carrousel-1-4, .carrousel-1-6, .carrousel-logo, .carrousel-1-1 ').on('init', function(event, slick){
        if(slick.$slider.hasClass("slick-dotted")) {
            slick.$list.prependTo(slick.$slider);
            slick.$dots.insertAfter(slick.$prevArrow);
            $(this).next().append(slick.$prevArrow[0], slick.$dots[0], slick.$nextArrow[0]);
        }
    });

// Carousel common propreties
    var carrousel_common_propreties = {
        infinite: true,
        centerMode: true,
        dots: true,
        prevArrow: `<button class="slide-arrow prev-arrow slick-prev"><span class="visually-hidden">${buttonPrevTrans}</span></button>`,
        nextArrow: `<button class="slide-arrow next-arrow slick-next"><span class="visually-hidden">${buttonNextTrans}</span></button>`,
    }

// Carousel custom proerties
    var carrousel_logo_propreties =  $.extend(carrousel_common_propreties, 
    {
        infinite: false,
        centerMode: false,
        centerPadding: '0 20px',
        slidesToShow: 6,
        responsive: 
            [{
                breakpoint: 1000,
                settings: 
                {
                    slidesToShow: 3
                }
            },
                {
                breakpoint: 480,
                settings: 
                {
                    slidesToShow: 2
                }
            }]
    });

    $('.carrousel-logo').slick(carrousel_logo_propreties);

    var carrousel_timeline_propreties =  $.extend(carrousel_common_propreties, 
    {
        infinite: false,
        centerMode: false,
        centerPadding: '0 20px',
        slidesToShow: 6,
        responsive: 
            [{
                breakpoint: 1000,
                settings: 
                {
                    infinite: false,
                    slidesToShow: 3
                }
            },
                {
                breakpoint: 480,
                settings: 
                {
                    infinite: false,
                    centerMode: false,
                    centerPadding: '0',
                    slidesToShow: 2
                }
            }]
    });

    $('.timelinelist-list').slick(carrousel_timeline_propreties);

    var carrousel_1_4_propreties = $.extend(carrousel_common_propreties,
    {
        centerPadding: '0',
        slidesToShow: 4,
        responsive:
        [{
            breakpoint: 1300,
            settings: {
                centerMode: false,
                centerPadding: '0',
                slidesToShow: 3
            }
        },
        {
            breakpoint: 1000,
            settings: {
                centerMode: false,
                centerPadding: '0',
                slidesToShow: 2
            }
        },
        {
            breakpoint: 600,
            settings: {
                centerMode: false,
                centerPadding: '0',
                slidesToShow: 1
            }
        }]
    })

    $('.carrousel-1-4').slick(carrousel_1_4_propreties);

    var carrousel_1_3_propreties = $.extend(carrousel_common_propreties,
    {
        infinite: false,
        centerPadding: '0',
        slidesToShow: 3,
        responsive: 
        [{
            breakpoint: 1100,
            settings: {
                centerMode: false,
                centerPadding: '0',
                slidesToShow: 3
            }
        },
        {
            breakpoint: 800,
            settings: {
                centerMode: false,
                centerPadding: '0',
                slidesToShow: 2
            }
        },
        {
            breakpoint: 480,
            settings: {
                centerMode: false,
                centerPadding: '0',
                slidesToShow: 1
            }
        }]
    })

    $('.carrousel-1-3').slick(carrousel_1_3_propreties);

    if ($(window).width() < 700) {
        var carrousel_1_1_propreties = $.extend(carrousel_common_propreties,
            {
                infinite: false,
                centerPadding: '0',
                slidesToShow: 3,
                centerMode: true,
                rows: 0,
                responsive: 
                [{
                    breakpoint: 1100,
                    settings: {
                        centerMode: false,
                        centerPadding: '0',
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 800,
                    settings: {
                        centerMode: false,
                        centerPadding: '0',
                        slidesToShow: 1
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        centerMode: false,
                        centerPadding: '0',
                        slidesToShow: 1
                    }
                }]
            })

        $('.carrousel-1-1').slick(carrousel_1_1_propreties);
    }
    var carrousel_galerie_propreties = $.extend(carrousel_common_propreties,
    {
        centerPadding: '8px',
        slidesToShow: 1,
        responsive: 
        [{
            breakpoint: 1100,
            settings: {
                centerMode: true,
                centerPadding: '8px',
                slidesToShow: 1
            }
        },
        {
            breakpoint: 800,
            settings: {
                centerMode: true,
                centerPadding: '0',
                slidesToShow: 1
            }
        },
        {
            breakpoint: 480,
            settings: {
                centerMode: true,
                centerPadding: '8px',
                slidesToShow: 1
            }
        }]
    });
    $('.carrousel-media').slick(carrousel_galerie_propreties);

    /*
    var Height = $(".view-edhecvox.view-display-id-block_5 .carrousel-1-1 .slick-track").outerHeight() - 10;
    $('.view-edhecvox.view-display-id-block_5 .carrousel-1-1 .slick-slide').css("height", Height);
    */

});

