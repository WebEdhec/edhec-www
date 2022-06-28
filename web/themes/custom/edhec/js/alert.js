jQuery(function( $ ) {

// Function to observe the presence of the alert message in DOM
    var sitewide_alert_obsevor = function(){
        var $sitewideAlert = $(".sitewide-alert");
        if ($sitewideAlert.length) {
            clearInterval(alert_observer_counter_id);
            $body.trigger('sitewide.alert.detected', [$sitewideAlert]);
        }
    };

    var $body = $("body")

    $body.on('sitewide.alert.detected', function(event, $sitewideAlert){
//Adding show and close buttons
        $sitewideAlert.after("<div class='hide-show-alert'>+</div>");
        $sitewideAlert.after("<div class='close-alert'>X</div>");

        var $hide_show_button = $(".hide-show-alert");
        var $close_alert_button = $(".close-alert");
        var alertMessageHeight  = $sitewideAlert.height();
        var alertMessageLineHeight = parseInt($sitewideAlert.css('line-height'));
        var alertNumberOfLines = Math.round(alertMessageHeight/alertMessageLineHeight);

        if(alertNumberOfLines<=1) {
            $(".hide-show-alert").hide();
        } else {
            if ($(window).width() < 778) {
               $sitewideAlert.height((3.5)*alertMessageLineHeight); 
            } else {
               $sitewideAlert.height(alertMessageLineHeight);
            }
        }

        $hide_show_button.on('click', function(){
            if("+" == $hide_show_button.html()) {
                $sitewideAlert.height(alertMessageHeight);
                $hide_show_button.html("-");
            } else {
                 if (778 > $(window).width()) {
                    $sitewideAlert.height(3.5*alertMessageLineHeight); 
                 } else {
                    $sitewideAlert.height(alertMessageLineHeight);
                 }
            $hide_show_button.html("+");
            }
        })

        $close_alert_button.on('click', function(){
            $sitewideAlert.add($hide_show_button).add($close_alert_button).fadeOut();
        })
    });

    var alert_observer_counter_id = setInterval(sitewide_alert_obsevor, 100);
});
