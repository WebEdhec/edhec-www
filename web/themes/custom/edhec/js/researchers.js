/*global jQuery*/

jQuery(function ($) {
  // Change the plus/minus button on click
  $('body').on('show.bs.collapse', '.researcher-item .card-more', function () {
    var $plusMinusBtn = $('i[data-bs-target="#' + $(this).attr('id') + '"]');
    $plusMinusBtn.removeClass('fa-plus').addClass('fa-minus');
  }).on('hide.bs.collapse', '.researcher-item .card-more', function () {
    var $plusMinusBtn = $('i[data-bs-target="#' + $(this).attr('id') + '"]');
    $plusMinusBtn.removeClass('fa-minus').addClass('fa-plus');
  });
});
