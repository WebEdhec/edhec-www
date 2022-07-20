(function ($) {
  if (!$(".path-admin").length) {
    Drupal.behaviors.gtmDatalayerCTA = {
      attach: function (context, settings) {
        /* --------- Custom Events --------- */

        /* --------- LINK #10 --------- */
        // CTA click
        $("a.bn", context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkUrl = $linkTag.attr("href")?.trim() || "";

          // Push search query to dataLayer
          pushDataLayer({
            event: "clic_cta_lien_interne",
            label: getText($linkTag),
            page_url: window.location.href,
            link_url: linkUrl,
            BU_initial: "", // @TODO
            BU_destination: "", // @TODO
          });

          // setTimeout(() => {
          //   window.location.href = linkUrl;
          // }, 100);
        });
        /* --------- End LINK #10 --------- */

        /* --------- LINK #17 --------- */
        // @TODO
        /* --------- End LINK #17 --------- */

        /* --------- LINK #30 --------- */
        // @TODO
        /* --------- End LINK #30 --------- */

        /* --------- Custom Events --------- */

        /* --------- Interactions --------- */

        /* --------- LINK #32 | #38 --------- */
        // Diaporama click (all pages)

        jQuery(context).on(
          "click",
          ".slick-list .slick-slide a:not('#ancres-container .slick-list .slick-slide a')",
          function (e) {
            // e.preventDefault();
            // e.stopPropagation();

            const $linkTag = $(this);

            if ($linkTag.length) {
              const linkUrl = $linkTag.attr("href")?.trim() || "";

              // Exception (newsroom)
              if ($linkTag.hasClass("link-more"))
                var linkLabel = getText(
                  $linkTag
                    .closest(".slick-list .slick-slide")
                    .find(".card-title")
                );
              else var linkLabel = getText($linkTag);

              const data = {
                event: "interaction",
                actionName: "clic_diaporama_visuel",
                actionParameter1: linkLabel,
                actionParameter2: window.location.href,
                actionParameter3: linkUrl,
              };

              pushDataLayer(data);

              // Exception (homepage) | #38
              if (
                $linkTag.closest(".carrousel-encre").length &&
                $linkTag.closest(".node--type-homepage").length
              ) {
                data.actionName = "clic_diaporama_homepage";
                pushDataLayer(data);
              }
            }
          }
        );
        /* --------- End LINK #32 | #38 --------- */

        /* --------- LINK #44 | #50 --------- */
        // CTA click
        $("a.bn, a.don", context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkUrl = $linkTag.attr("href")?.trim() || "";
          const linkLabel = getText($linkTag);

          const data = {
            event: "interaction",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
            actionParameter3: linkUrl,
          };

          // Exception (homepage) | #44
          if ($(".node--type-homepage").length) {
            data.actionName = "clic_CTA_homepage";
          }
          // Exception (homepage) | #50
          else if ($(".node--type-newsroom").length) {
            // data.actionName = "clic_cta_principal"; // @TODO
          }

          // Push search query to dataLayer
          if (data.actionName) pushDataLayer(data);
        });
        /* --------- End LINK #44 | #50 --------- */

        /* --------- LINK #56 --------- */
        // CTA click
        $(".nav.nav-tabs .nav-link, .nav.nav-pills .nav-link", context).click(
          function (e) {
            // e.preventDefault();
            // e.stopPropagation();

            const $linkTag = $(this);
            const linkUrl = $linkTag.data("bs-target"); // @TODO
            const linkLabel = getText($linkTag);

            // Push search query to dataLayer
            pushDataLayer({
              event: "interaction",
              actionName: "clic_bandeau_onglet",
              actionParameter1: linkLabel,
              actionParameter2: window.location.href,
              actionParameter3: linkUrl,
            });
          }
        );
        /* --------- End LINK #56 --------- */

        /* --------- LINK #62 --------- */
        // CTA click
        $(".accordion .accordion-item", context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this).find(".accordion-button:first-child");
          const linkLabel = getText($linkTag);
          const linkLevel = 1; // @TODO

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_accordeon",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
            actionParameter3: linkLevel,
          });
        });
        /* --------- End LINK #62 --------- */

        /* --------- LINK #68 --------- */
        $(".field--name-field-sidebar-cta .field__item", context).click(
          function (e) {
            // e.preventDefault();
            // e.stopPropagation();

            const $linkTag = $(this).find("a:first-child");
            const linkUrl = $linkTag.attr("href")?.trim() || "";
            const linkLabel = getText($linkTag);

            let typeCta = "";

            if ($linkTag.closest(".field--name-field-sidebar-cta").length) {
              typeCta = "pop-in";
            }

            // Push search query to dataLayer
            pushDataLayer({
              event: "interaction",
              actionName: "clic_cta_sticky_popup",
              actionParameter1: typeCta,
              actionParameter2: linkLabel,
              actionParameter3: window.location.href,
              actionParameter4: linkUrl,
            });
          }
        );
        /* --------- End LINK #68 --------- */

        /* --------- LINK #75 | #80 | #85 --------- */
        // CTA click
        $(
          ".btn-add-google-calendar, .btn-add-outlook-calendar, .btn-subscribe",
          context
        ).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkLabel = getText($linkTag);

          let actionName = "";

          if (
            $linkTag.hasClass("btn-add-google-calendar") ||
            $linkTag.hasClass("btn-add-outlook-calendar")
          ) {
            actionName = "clic_cta_add_to_calendar";
          } else if ($linkTag.hasClass("btn-subscribe")) {
            actionName = "clic_cta_register";
          }

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName,
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
          });
        });
        /* --------- End LINK #75 | #80 | #85 --------- */

        /* --------- LINK #90 Canceled --------- */
        // CTA click
        /*$('a[href^="mailto:"]', context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkLabel = getText($linkTag);

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_cta_mailto",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
          });
        });*/
        /* --------- End LINK #90 --------- */

        /* --------- LINK #90 --------- */
        // CTA click
        $(".btn-download, a.down", context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkLabel = getText($linkTag);

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "document_download",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
          });
        });
        /* --------- End LINK #90 --------- */

        /* --------- LINK #95 --------- */
        // CTA click
        $(".btn-simulate", context).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "simulateur_aide_financiere",
          });

          console.log({
            event: "interaction",
            actionName: "simulateur_aide_financiere",
          })
        });
        /* --------- End LINK #95 --------- */

        /* --------- LINK #109 Canceled --------- */
        // CTA click
        /*$(
          `a:not('#toolbar-administration a, #block-edhec-local-tasks a, .a2a_dd'):not([href="${window.location.hostname}"])`,
          context
        ).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkLabel = getText($linkTag);
          const linkUrl = $linkTag.attr("href")?.trim() || "";

          if (isExternalLink(linkUrl)) {
            // Push search query to dataLayer
            pushDataLayer({
              event: "interaction",
              actionName: "clic_sortant_partenaire",
              actionParameter1: linkLabel,
              actionParameter2: window.location.href,
              actionParameter3: linkUrl,
            });
          }
        });*/
        /* --------- End LINK #109 --------- */

        /* --------- LINK #115 Canceled --------- */
        // CTA click
        /*if ($("#block-views-block-associations-block-1").length) {
          $("#edit-submit-associations", context).click(function (e) {
            // e.preventDefault();
            // e.stopPropagation();

            let filters = [];

            $(".filters-parameters .form-check-input:checked").each(
              function () {
                const filter = getText($(this).next());

                filters.push(filter);
              }
            );

            // Push search query to dataLayer
            pushDataLayer({
              event: "interaction",
              actionName: "clic_cta_voir_les_associations",
              actionParameter1: filters.join(),
            });
          });
        }*/
        /* --------- End LINK #115 --------- */

        /* --------- LINK #137 | #143 | #149 Canceled --------- */
        // CTA click
        /* $(
          ".field--name-field-sidebar-cta .field__item .sidebar-candidate, .field--name-field-sidebar-cta .field__item .sidebar-brochure, .field--name-field-sidebar-cta .field__item .sidebar-contact",
          context
        ).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkLabel = getText($linkTag);
          const linkUrl = $linkTag.attr("href")?.trim() || "";

          let actionName = "";

          if ($linkTag.hasClass("sidebar-candidate")) {
            actionName = "clic_candidater";
          } else if ($linkTag.hasClass("sidebar-brochure")) {
            actionName = "clic_brochure";
          } else if ($linkTag.hasClass("sidebar-contact")) {
            actionName = "clic_picto_contact";
          }

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName,
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
            actionParameter3: linkUrl,
          });
        });*/
        /* --------- End LINK #137 | #143 | #149 --------- */

        /* --------- LINK #143 --------- */
        // CTA click
        $(
          ".field--name-field-sidebar-cta .field__item a[href^='/brochure']",
          context
        ).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkLabel = getText($linkTag);
          const linkUrl = $linkTag.attr("href")?.trim() || "";

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_brochure",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
            actionParameter3: linkUrl,
          });
        });
        /* --------- End LINK #143 --------- */

        /* --------- LINK #155 Canceled --------- */
        // CTA click
        /*$("#block-selecteurdelangue .links .language-link").click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkLabel = getText($linkTag);
          const linkUrl = $linkTag.attr("href")?.trim() || "";

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_retour_site",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
            actionParameter3: linkUrl,
          });
        });*/
        /* --------- End LINK #155 --------- */

        /* --------- LINK #191 --------- */
        // CTA click
        $(".vote-thumb.up").click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "like_button",
            actionParameter1: window.location.href,
            actionParameter2: "", // @TODO: get the current page url
          });
        });
        /* --------- End LINK #191 --------- */

        /* --------- LINK #196 Canceled --------- */
        // CTA click
        /*$("#a2apage_mini_services").hover(function () {
          $(this)
            .find(".a2a_i")
            .attr(
              "onclick",
              "Drupal.behaviors.gtmDatalayerCTA.pushSocialLinks(this)"
            );
        });*/
        /* --------- End LINK #196 --------- */

        /* --------- LINK #207 Canceled --------- */
        // CTA click
        /*$("a[href^='#']", context)
          .filter(function (index) {
            return $(this).attr("href").trim() != "#";
          })
          .click(function (e) {
            // e.preventDefault();
            // e.stopPropagation();

            const $linkTag = $(this);
            const linkLabel = getText($linkTag);

            // Push search query to dataLayer
            pushDataLayer({
              event: "interaction",
              actionName: "click_ancre",
              actionParameter1: linkLabel,
              actionParameter2: window.location.href,
            });
          });*/
        /* --------- End LINK #207 --------- */

        /* --------- LINK #230 Canceled --------- */
        // CTA click
        /*if ($("#block-views-block-startup-block-1").length) {
          $("#edit-submit-startup", context).click(function (e) {
            // e.preventDefault();
            // e.stopPropagation();

            let filters = [];

            $(".filters-parameters .form-check-input:checked").each(
              function () {
                const filter = getText($(this).next());

                filters.push(filter);
              }
            );

            // Push search query to dataLayer
            pushDataLayer({
              event: "interaction",
              actionName: "filtre_recherche_startup",
              actionParameter1: filters.join(),
              actionParameter2: window.location.href,
            });
          });
        }*/
        /* --------- End LINK #230 --------- */

        /* --------- LINK #224 --------- */
        $(
          ".view-actualites.view-id-actualites a, .views-remonte-auto a, .view-publications-recherche.view-id-publications_recherche a, .view-publications.view-id-publications a",
          context
        ).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const linkUrl = $linkTag.attr("href")?.trim() || "";
          let linkLabel = getText($linkTag);

          if ($linkTag.find("img").length) {
            linkLabel = "Image";
          }

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_liens_sous_articles",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
            actionParameter3: linkUrl,
          });
        });
        /* --------- End LINK #224 --------- */

        /* --------- LINK #282 --------- */
        $(
          ".view-publications-recherche.view-id-publications_recherche .read-more a",
          context
        ).click(function (e) {
          // e.preventDefault();
          // e.stopPropagation();

          const $linkTag = $(this);
          const $itemContainer = $linkTag.closest(".field-content");
          const linkUrl = $linkTag.attr("href")?.trim() || "";
          const linkLabel = getText($itemContainer.find(".card-title"));
          const publicationType = getText(
            $itemContainer.find(".additional-data .publication-type")
          );
          const faculte = getText(
            $itemContainer.find(".additional-data .field-faculte")
          );
          const domaine = getText(
            $itemContainer.find(".additional-data .field-domaine")
          );

          // Push search query to dataLayer
          pushDataLayer({
            event: "interaction",
            actionName: "clic_en_savoir_plus",
            actionParameter1: linkLabel,
            actionParameter2: window.location.href,
            actionParameter3: publicationType,
            actionParameter4: faculte,
            actionParameter5: domaine,
          });
        });
        /* --------- End LINK #282 --------- */

        /* --------- End Interactions --------- */
      },
      /* --------- Related To LINK #196 --------- */
      pushSocialLinks: function (element) {
        const $linkTag = $(element);
        const linkLabel = getText($linkTag.last());

        // Push search query to dataLayer
        pushDataLayer({
          event: "interaction",
          actionName: "share_button",
          actionParameter1: window.location.href,
          actionParameter2: linkLabel,
        });
      },
      /* --------- End Related To LINK #196 --------- */
    };
  }
})(jQuery);
