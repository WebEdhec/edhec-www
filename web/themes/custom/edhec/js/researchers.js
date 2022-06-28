/*global jQuery*/

jQuery(function ($) {
  $(".researchers-list .researcher-item .btn-more").click(function () {
    $(this).toggleClass("fa fa-plus fa fa-minus");
  });
  $(".researchers-list .researcher-item .card-body h5").click(function () {
    $(this).parents().eq(3).find('.btn-more').toggleClass("fa fa-plus fa fa-minus");
  });
  $(".researchers-list .researcher-item .photo-item h5").click(function () {
    $(this).parents().eq(1).find('.btn-more').toggleClass("fa fa-plus fa fa-minus");
  });
});
