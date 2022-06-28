(function ($) {
  // Vue App
  const { createApp, ref, reactive, watch, computed, onMounted, nextTick } =
    Vue;

  const vueApp = createApp({
    delimiters: ["$v{", "}"],
    setup() {
      /* ------------------------- Reactive ------------------------- */
      // Reactive state
      const keywordSearch = ref("");
      /* ------------------------- End Reactive ------------------------- */

      /* ------------------------- Methods ------------------------- */

      /**
       * Setup Algolia Autocomplete
       */
      function setupAutocomplete() {
        let $autoCompleteScope = null;

        // Build filters query
        const filtersQuery = `langcode:${language}`;

        autocomplete({
          container: "#keywordSearch",
          placeholder: transKeywordSearch,
          detachedMediaQuery: "none",
          // debug: true,
          getSources({ query, setQuery, setIsOpen, refresh }) {
            $autoCompleteScope = {
              setQuery,
              setIsOpen,
              refresh,
              keywordSearch,
            };

            if (!query) {
              return [];
            }

            return [
              {
                sourceId: "edhec",
                getItems({ query }) {
                  return getAlgoliaResults({
                    searchClient,
                    queries: [
                      {
                        indexName: algoliaIndexName,
                        query,
                        filters: filtersQuery,
                        params: {
                          hitsPerPage: 12,
                          clickAnalytics: true,
                          attributesToSnippet: ["title:10", "field_chapo:5"],
                          snippetEllipsisText: "…",
                        },
                      },
                    ],
                  });
                },
                templates: {
                  item({ item, createElement }) {
                    return createElement("div", {
                      dangerouslySetInnerHTML: {
                        __html: `<span class='autocomplete-selected' data-autocompleteval='${item.title}' data-itemlink='${item.view_node}'>${item.title}</span>`,
                      },
                    });
                  },
                  noResults() {
                    return transNoResults;
                  },
                },
              },
            ];
          },
          onStateChange({ prevState, state }) {
            if (prevState.query !== state.query) {
              keywordSearch.value = state.query;
            }
          },
        });

        // Click Event on autocomplete selected getItems
        $(document).on("click", ".aa-List .aa-Item", function () {
          $autocompleteItem = $(this).find(".autocomplete-selected");

          const itemLink = $autocompleteItem.length
            ? $autocompleteItem.data("itemlink")
            : "";

          /*const query = $autocompleteItem.length
            ? $autocompleteItem.html()
            : "";

          $autoCompleteScope.setQuery(query);
          $autoCompleteScope.setIsOpen(false);
          $autoCompleteScope.refresh();*/

          // Push search query to dataLayer
          const query = keywordSearch.value ? keywordSearch.value.trim() : "";

          if (!$('.path-admin').length && query != "") {
            pushDataLayer({
              event: "internal_search",
              type_moteur: "principal",
              searched_keyword: query,
            });
          }

          setTimeout(() => {
            goToItemLink(itemLink);
          }, 100);
        });

        // Disable submit Event (Enter key)
        jQuery("#keywordSearch .aa-Input").keypress(function (event) {
          if (event.which == "13") {
            $autoCompleteScope.setIsOpen(false);
            $autoCompleteScope.refresh();
            goToSearchPage();
            event.preventDefault();
          }
        });
      }

      /**
       * Go to search page
       */
      function goToSearchPage() {
        // Push search query to dataLayer
        const query = keywordSearch.value ? keywordSearch.value.trim() : "";

        if (!$('.path-admin').length && query != "") {
          pushDataLayer({
            event: "internal_search",
            type_moteur: "principal",
            searched_keyword: query,
          });
        }

        setTimeout(() => {
          window.location.href = `/recherche?query=${query}`;
        }, 100);
      }

      /**
       * Go to item link
       *
       * @param {String} itemLink
       */
      function goToItemLink(itemLink) {
        window.location.href = itemLink;
      }

      /* ------------------------- End Methods ------------------------- */

      /* ------------------------- Lifecycle hooks ------------------------- */
      onMounted(() => {
        nextTick(() => {
          setupAutocomplete();
        });
      });
      /* ------------------------- End Lifecycle hooks ------------------------- */

      // Expose data
      return {
        goToSearchPage,
      };
    },
  });

  vueApp.mount("#v-app");
})(jQuery);
