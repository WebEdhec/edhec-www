/*global jQuery*/

jQuery(function( $ ) {

    $(".btn-search").on("click", function(){
        $(".additional_search").fadeToggle();
        $(this).toggleClass("search-active");
        $(this).attr('aria-expanded', function (i, attr) {
            return attr == 'true' ? 'false' : 'true'
        });
        $('#keywordSearch input').focus();
    })

    $('body').on('change', '.filters-parameters input[type=radio][name=sort_bef_combine]', function(){
        $(".filters-parameters input[type='submit']").trigger('click');
    }).on('click', 'span.sort-bef-combine-title-asc', function(){
        $('.filters-parameters .sort-bef-combine-title-asc input').prop('checked', true);
        $(".filters-parameters input[type='submit']").trigger('click');
    }).on('click', 'span.sort-bef-combine-title-desc', function(){
        $('.filters-parameters .sort-bef-combine-title-desc input').prop('checked', true);
        $(".filters-parameters input[type='submit']").trigger('click');
    }).on('click', 'span.sort-bef-combine-created-desc', function(){
        $('.filters-parameters .sort-bef-combine-created-desc input').prop('checked', true);
        $(".filters-parameters input[type='submit']").trigger('click');
    }).on('click', '.select-filter-combine', function(){
        var $selectedTaxonomyName = $(this).attr('for');
        $("input[name='" + $selectedTaxonomyName + "']").attr('value', '');
        $(this).remove();
        $(".filters-parameters input[type='submit']").trigger('click');
    })

    // Search views 
    $('.filters-parameters select').selectpicker(); 

    $(document).ajaxComplete(function() {
        $('.search-filters').prepend($('.resultat-filter'));
        $('.resultat-filter.combine').insertBefore('.search-filters');
        $('.select-filter').each(function(){
            $(this).on('click', function(){
                $selectedTaxonomyName = $(this).attr('for');
                $("select[name='" + $selectedTaxonomyName + "']").val('All').trigger('change');
                $(this).remove();
                $(".filters-parameters input[type='submit']").trigger('click');
            })
        });


        // Search views 
        $('.filters-parameters select').selectpicker(); 
    })
});