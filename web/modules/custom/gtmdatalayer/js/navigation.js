(function ($) {
  if (!$('.path-admin').length) {
    Drupal.behaviors.gtmDatalayerNav = {
      attach: function (context, settings) {
        /* --------- Interactions --------- */

        /* --------- LINK #6 --------- */
        // Click main navigation
        const mainNavSelector =
          "#block-mainnavigation .tb-megamenu-nav .tb-megamenu-item a";

        $(mainNavSelector, context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const level = parseInt($(this).parent().attr("data-level") || 1);
          const parentName =
            level > 1
              ? getText(
                  $(this)
                    .closest(`.tb-megamenu-item[data-level='${level - 1}']`)
                    .children("a")
                )
              : "";

          const $linkTag = $(this);
          const linkType = "Menu principal"; // @TODO
          const linkLabel = getText($linkTag);
          const linkUrl = $linkTag.attr("href")?.trim() || '';

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_menu_navigation",
            actionParameter1: level,
            actionParameter2: parentName,
            actionParameter3: linkType,
            actionParameter4: linkLabel,
          });

          // setTimeout(() => {
          //   window.location.href = linkUrl;
          // }, 100);
        });
        /* --------- End LINK #6 --------- */

        /* --------- LINK #13 --------- */
        // Click CTA menu navigation
        const ctaNavSelector =
          "#off-canvas .mm-panels .mm-listview .mm-listitem .mm-listitem__text";

        $(ctaNavSelector, context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkType = "CTA Menu"; // @TODO
          const linkLabel = getText($linkTag);

          // Extract link level
          const levelClass = $linkTag.closest(`.mm-listview`).attr("class");
          let level = 1;

          if (levelClass) {
            const levelRegex = /\d+/g;
            const resultLevelRegex = levelRegex.exec(levelClass);

            if (resultLevelRegex && resultLevelRegex.length)
              level = parseInt(resultLevelRegex[0]) + 1;
          }

          const parentName =
            level > 1
              ? getText(
                  $linkTag
                    .closest(`.mm-panel.mm-panel--opened`)
                    .children(".mm-navbar")
                    .children(".mm-navbar__title")
                )
              : "";

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_bloc_cta_menu",
            actionParameter1: level,
            actionParameter2: parentName,
            actionParameter3: linkType,
            actionParameter4: linkLabel,
          });

          // setTimeout(() => {
          //   window.location.href = linkUrl;
          // }, 100);
        });
        /* --------- End LINK #13 --------- */

        /* --------- LINK #20 & #26 --------- */
        // Click Header & Footer navigation
        const headerNavSelector =
          "#block-topmenu .navbar-nav .nav-item .nav-link";
        const footerNavSelector =
          "#block-footermenu .navbar-nav .nav-item .nav-link";

        $(`${headerNavSelector}, ${footerNavSelector}`, context).click(
          function (e) {
            // e.preventDefault();
            // e.stopPropagation();

            const $linkTag = $(this);
            const linkLabel = getText($linkTag);
            const linkUrl = $linkTag.attr("href")?.trim() || '';
            const navSelector = $linkTag
              .closest(".block.block-menu.navigation")
              .attr("id");
            const actionName =
              navSelector === "block-topmenu"
                ? "clic_lien_header"
                : "clic_lien_footer";

            // Push search query to dataLayer
            pushDataLayer({
              event: "interaction",
              actionName,
              actionParameter1: linkLabel,
              actionParameter2: window.location.href,
              actionParameter3: linkUrl,
            });

            // setTimeout(() => {
            //   window.location.href = linkUrl;
            // }, 100);
          }
        );
        /* --------- End LINK #20 & #26 --------- */

        /* --------- End Interactions --------- */
      },
    };
  }
})(jQuery);
