jQuery(function( $ ) {
        $(window).on('scroll', function () {
            // $('.progress-bar.progress-bar-primary').not('.progress-bar-initialized').each(function(){
            //     console.log($(this).attr('aria-valuenow'))
            //     $(this).animate({
            //         width : $(this).attr('aria-valuenow') + '%!important',
            //     })
            //     $(this).addClass('progress-bar-initialized');
            // })
            
            $('.graphique_container').not('.graphic-initialized').each(function(){
                if (window.innerHeight > this.getBoundingClientRect().top + $(this).height()*0.4) {
                    $(this).find(".progress-bar-primary").each(function(){
                        // console.log($(this).attr('aria-valuenow'))
                        $(this).animate({
                            width : $(this).attr('aria-valuenow') + '%',
                        })
                        $(this).addClass('progress-bar-initialized');
                    })

                    $(this).find(".graphique-type").each(function(key, value){
                        if($.trim($(this).text()) == '0') {
                            var $containerId = $(this).attr('data-container');
                            $jsFieldContent = $(".js-field-content-"+$containerId);
                            var $jsFieldContents = [];

                            $jsFieldContent.each(function(){
                                var $trimmedLabel = $.trim($(this).html());
                                $jsFieldContents.push($trimmedLabel);
                            });

                            var $trimmedjsFieldContents = $.trim($jsFieldContents);
                            var $parsedtrimmedjsFieldContents = JSON.parse("["+$trimmedjsFieldContents+"]");

                            var pieColors = (function () {
                            var colors = [],
                                base = Highcharts.getOptions().colors[0],
                                i;
                            }());

                            var $containerName = "container-"+$containerId ;

                            Highcharts.chart($containerName, {
                                chart: {
                                    plotBackgroundColor: null,
                                    plotBorderWidth: null,
                                    plotShadow: false,
                                    type: 'pie'
                                },
                                title: {
                                    text: ''
                                },
                                tooltip: {
                                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                                },
                                plotOptions: {
                                    pie: {
                                        allowPointSelect: true,
                                        cursor: 'pointer',
                                        colors: pieColors,
                                        dataLabels: {
                                            enabled: true,
                                            //format: '<b>{point.name}</b><br>{point.y} %',
                                            format: '<b>{point.name}: <b>{point.percentage:.1f}% ',
                                            //distance: -50,
                                            filter: {
                                                property: 'percentage',
                                                operator: '>',
                                                value: 4
                                            }
                                        }
                                    }
                                },
                                series: [{
                                    name: '',
                                    colorByPoint: false,
                                    data: $parsedtrimmedjsFieldContents,
                                    animation: {
                                    duration: 1000,
                                    easing: 'linear'
                                    }
                                    
                                }]
                            });
                    } else if ($.trim($(this).text()) == '1') {
                        var $containerId = $(this).attr('data-container');
                        $jsFieldContent = $(".js-field-content-"+$containerId);
                        var $containerName = "container-"+$containerId ;
                        var $jsFieldContents = [];

                        $jsFieldContent.each(function(){
                            var $trimmedLabel = $.trim($(this).html());
                            $jsFieldContents.push($trimmedLabel);
                        });

                        var $trimmedjsFieldContents = $.trim($jsFieldContents);
                        var $parsedtrimmedjsFieldContents = JSON.parse("["+$trimmedjsFieldContents+"]");

                        Highcharts.chart($containerName, {
                            chart: {
                                type: 'column',
                            },
                            xAxis: {
                                type: 'category',
                                labels: {
                                    rotation: 0,
                                    style: {
                                        fontSize: '13px',
                                        fontFamily: 'Verdana, sans-serif'
                                    }
                                }
                            },
                            legend: {
                                enabled: false
                            },
                            tooltip: {
                                pointFormat: '<b>{point.y:.1f}</b>'
                            },
                            series: [{
                                name: '',
                                data: $parsedtrimmedjsFieldContents,
                                dataLabels: {
                                    enabled: true,
                                    color: '#000',
                                    align: 'center',
                                    format: '{point.y:.1f}',
                                    y: 220,
                                    style: {
                                        fontSize: '13px',
                                        fontFamily: 'Verdana, sans-serif',
                                    }
                                },
                                animation: {
                                duration: 1000,
                                easing: 'linear'
                                }
                            }]
                        });
                    }
                })
                    $(this).addClass('graphic-initialized');
                }
            })
        });
});