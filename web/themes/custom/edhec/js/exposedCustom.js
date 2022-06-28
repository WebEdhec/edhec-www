/*global jQuery,URLSearchParams*/

jQuery(function( $ ) {

    datepickerSetDefaultDateFormat();

    $(document).ajaxComplete(function(event, jqXHR, ajaxOptions ){
        datepickerSetDefaultDateFormat();
        hideSearchFiltersOptionsAfterSearch();
    });

    $('body').on('click', function(event){
        var clickedElt = event.target;
        $('.filters-parameters .card, .block-views-exposed-filter-blockpartenaires-internationaux-block-1 .card').each(function() {
            if ( $(this).has(clickedElt).length ) return;
            collapsibleDropdownClose(this);
        });
    });

    function hideSearchFiltersOptionsAfterSearch() {
        $('.filters-parameters .card').each(function() {
            collapsibleDropdownClose(this);
        });
    }
    function datepickerSetDefaultDateFormat() {
        if ( 'undefined' !== typeof $.datepicker ) {
            $.datepicker.setDefaults({ dateFormat: "yy/mm/dd" });
        }
    }
    function collapsibleDropdownClose(collapsibleElt) {
        $(collapsibleElt).removeAttr('open')
                         .children('summary')
                         .attr({'aria-expanded': false,'aria-pressed': false});
    }

});