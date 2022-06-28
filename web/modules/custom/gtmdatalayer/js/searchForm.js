(function ($) {
  if (!$('.path-admin').length) {
    Drupal.behaviors.gtmDatalayerSearchForm = {
      attach: function (context, settings) {
        /* --------- Custom Events --------- */

        /* --------- LINK #6 --------- */
        const searchFormSelector = `#${$(".views-exposed-form").attr("id")}`;
        let searchType = "";
        let searchedKeyword = "";

        // Submit search form
        $(`${searchFormSelector} input[type=submit]`, context).click(function (
          e
        ) {
          // e.preventDefault();

          switch (searchFormSelector) {
            case "#views-exposed-form-publications-recherche-page-1":
              searchType = "Publications";
              break;
            case "#views-exposed-form-publications-page-1":
              searchType = "Faculty Research Expertise";
              break;
            case "#views-exposed-form-publications-page-2":
              searchType = "Phd Theses";
              break;
            case "#views-exposed-form-researchers-block-1":
              searchType = "Professeurs et chercheurs";
              break;
            case "#views-exposed-form-phd-researchers-page-1":
              searchType = "Expertise en recherche du corps professoral";
              break;
            case "#views-exposed-form-presse-presse":
              searchType = "Espace presse";
              break;
            case "#views-exposed-form-newsroom-page-1":
              searchType = "Newsroom";
              break;
            case "#views-exposed-form-actualites-page-1":
              searchType = "Actualités";
              break;
            case "#views-exposed-form-associations-block-1":
              searchType = "Associations Etudiantes";
              break;
            case "#views-exposed-form-recrutement-block-1":
              searchType = "Offres d'emploi";
              break;
            case "#views-exposed-form-startup-block-1":
              searchType = "Les start up made in EDHEC";
              break;
            default:
              searchType = "";
              break;
          }

          if (searchType != "") {
            searchedKeyword = $(`${searchFormSelector} input[name=cles]`)
              .val()
              .trim();

            // Push search query to dataLayer
            if (searchedKeyword != "") {
              pushDataLayer({
                event: "internal_search",
                type_moteur: "principal",
                searched_keyword: searchedKeyword,
              });
            }
          }
        });
        /* --------- End LINK #6 --------- */

        /* --------- End Custom Events --------- */
      },
    };
  }
})(jQuery);
