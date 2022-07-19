/*global jQuery, main_breadcrumb_menu*/
jQuery(function ($) {

    function make_select_field(optionslist, selectedOption, data_level = 0, prependAnEmptyValue = true) {

        let $select = $("<select aria-label='menuitem' />");

        if (prependAnEmptyValue) 
            $("<option/>", {value: '0'}).appendTo($select);

        $.each(optionslist, function(optionIndex, optionObject) {
            let $option = $("<option/>", {
                value: optionIndex,
                text: optionObject.title
            });

            if ( optionObject.url ) {
                $option.attr('data-breadcrumb-menu--url', optionObject.url);
            }
            if ('' != optionObject.classes) {
                $option.addClass(optionObject.classes);
            }
            if ( selectedOption === optionIndex ) {
                $option.attr('selected', true);
            }
            $option.appendTo($select);
        });

        $('.breadcrumb-menu').trigger('bcm.select.made', [$select]);

        let $wrappedSelect = $("<div/>", {
            "class": "col breadcrumb-menu--col",
            'data-breadcrumb-menu': '',
            'data-breadcrumb-menu--level': data_level
        }).append($select);

        return $wrappedSelect;
    }

    function generate_unnested_menu_list(nestedMenuList, level = 1) {

        generated_menus['level_'+level+'_menu'] = {
            elements : {},
            selected : null,
            level : level
        };

        $.each(nestedMenuList, function(index, menu) {

            generated_menus['level_'+level+'_menu'].elements[index] = {
                title: menu.title,
                url: menu.url,
                classes: menu.classes
            };

            if (menu.in_active_trail) {
                generated_menus['level_'+level+'_menu'].selected = index;
    
                if ( 0 < Object.keys(menu.below).length ) {

                    generate_unnested_menu_list(menu.below, level + 1);

                }
    
            }
        });

    }

    function get_menu_from_nested_menues_by_index(nested_menues, searched_index) {

        for (let [menu_index, menu] of Object.entries(nested_menues)) {

            if ( searched_index === menu_index ) {
                return menu;
            }
            if ( 0 < Object.keys(menu.below).length ) {
                let menu_from_nested_menues_by_index = get_menu_from_nested_menues_by_index(menu.below, searched_index);
                if (false != menu_from_nested_menues_by_index) {
                    return menu_from_nested_menues_by_index;
                }
            }
        }

        return false;
    }

    function build_unnested_menus_as_select_fields_and_hook_to(unnested_menus_to_build, hookTo) {

        $.each(unnested_menus_to_build, function(index, generated_menu) {
            let $generated_menu_select_field = make_select_field(
                generated_menu.elements,
                generated_menu.selected,
                generated_menu.level,
                false
            );

            $generated_menu_select_field.appendTo(hookTo);

            $('.breadcrumb-menu').trigger('bcm.select.implanted', [$generated_menu_select_field]);
        });

        var current_page_title = $('body').data('current-page-title');

        if ('' !== current_page_title) {
            $('<div/>', {
                'class' : 'col breadcrumb-menu--col',
                'data-breadcrumb-menu' : '',
                'data-breadcrumb-menu--current-menu-element' : '',
            }).append($('<div/>', {text : current_page_title})).appendTo(hookTo);
        }
    }

    function if_the_chosen_option_has_url_open_it($referencedSelect) {
        let selectedOptionUrl;

        if ( typeof (selectedOptionUrl = $referencedSelect.find(':selected').data('breadcrumb-menu--url')) != 'undefined' ) {
            window.location.href = selectedOptionUrl;
        }
    }

    function remove_empty_option($selectElt) {
        $selectElt.find('[value="0"]').remove();
    }

    function remove_all_next_levels_selects($referencedSelect) {
        $referencedSelect.closest('[data-breadcrumb-menu]').nextAll('[data-breadcrumb-menu]').remove();
    }

    function make_an_unnested_menu_list_from_a_nested_one(nested_menu, level = 0) {
        let submenu = {
            elements : {},
            selected : null,
            level : level
        };

        $.each(nested_menu.below, function(index, menu) {
            submenu.elements[index] = {
                title: menu.title,
                url: menu.url,
                classes: menu.classes
            };
        });

        return submenu;
    }

    function get_active_menu(nested_menues = main_breadcrumb_menu) {
        for (let [menu_index, menu] of Object.entries(nested_menues)) {
    
            if ( menu.in_active_trail && 0 === Object.keys(menu.below).length )
                return menu;
    
            if ( 0 < Object.keys(menu.below).length ) {
                let active_menu = get_active_menu(menu.below);
                if ( false != active_menu )
                    return active_menu;
            }
        }
    
        return false;
    }

    let generated_menus = {};
    generate_unnested_menu_list(main_breadcrumb_menu);

    build_unnested_menus_as_select_fields_and_hook_to(generated_menus, '.breadcrumb-menu .row');

    $('.breadcrumb-menu').trigger('bcm.created');

    $('body').on('change', '[data-breadcrumb-menu] select', function() {

        let $this = $(this);

        if_the_chosen_option_has_url_open_it($this);
        remove_empty_option($this);
        remove_all_next_levels_selects($this);


        let selected_menu_index = $this.val();
        let next_sublevel = $this.closest('[data-breadcrumb-menu]').data('breadcrumb-menu--level') + 1;
        let nested_submenu = get_menu_from_nested_menues_by_index(main_breadcrumb_menu, selected_menu_index);
        let submenu = make_an_unnested_menu_list_from_a_nested_one(nested_submenu, next_sublevel);

        if ($.isEmptyObject(submenu.elements)) {
            return;
        }

        let $generated_submenu_select_field = make_select_field(submenu.elements, submenu.selected, submenu.level);

        $generated_submenu_select_field.appendTo('.breadcrumb-menu .row');

        $('.breadcrumb-menu').trigger('bcm.select.implanted', [$generated_submenu_select_field]);

    });

});
