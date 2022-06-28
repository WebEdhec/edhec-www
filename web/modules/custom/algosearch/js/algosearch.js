(function ($) {
  /* ---------------- Init JS Values (From Twig) -------------- */

  const _taxonomies = [];
  const _contentTypes = [];

  for (const key in taxonomies) {
    _taxonomies.push({
      key: isNaN(key) ? key : parseInt(key),
      value: taxonomies[key],
      resultCount: 0,
    });
  }

  for (const key in contentTypes) {
    _contentTypes.push({
      key,
      value: contentTypes[key],
      resultCount: 0,
    });
  }

  // overwrite original values (window.taxonomies | window.contentTypes )
  taxonomies = _taxonomies;
  contentTypes = _contentTypes;

  const taxonomyAllIndex = taxonomies.findIndex((obj) => obj.key == "All");

  /* ---------------- End Init JS Values (From Twig) -------------- */

  // Vue App
  const { createApp, ref, reactive, watch, computed, onMounted, nextTick } =
    Vue;

  const vueApp = createApp({
    delimiters: ["$v{", "}"],
    setup() {
      /* ------------------------- Reactive ------------------------- */
      // Reactive state
      const taxonomies = reactive(window.taxonomies);
      const contentTypes = reactive(window.contentTypes);

      const keywordSearch = ref("");
      const filters = reactive({
        contentTypes: [],
        taxonomies: [], // getKeysExeptAll(taxonomies), // Specific taxonomies to search
        currentTaxonomyFilter: "",
      });

      const results = reactive({
        list: [],
        count: 0,
        pagination: {
          nbPages: 0,
          page: 0,
        },
      });

      const loader = ref(false);

      // Reactivity: Build query of all filters
      filtersQuery = computed(() => {
        // Build query of content type filter
        const contentTypeFilterQuery = buildConditionsFilter(
          "type_1",
          filters.contentTypes
        );

        // Build query of taxonomy filter
        const taxonomyFilterQuery = buildConditionsFilter(
          "field_cible_group",
          filters.taxonomies
        );

        // Build query of all content types filter
        const allContentTypesFilterQuery = buildConditionsFilter(
          "type_1",
          getKeysExeptAll(contentTypes)
        );

        // Build query of all taxonomies filter
        const allTaxonomiesFilterQuery = buildConditionsFilter(
          "field_cible_group",
          getKeysExeptAll(taxonomies)
        );

        // Build query of language filter
        const languageFilterQuery = `langcode:${language}`;

        // Build query of all filters exept taxonomy filter
        const filtersQueryWithoutTaxonomyFilter = contentTypeFilterQuery
          ? `${languageFilterQuery} AND ${contentTypeFilterQuery}`
          : languageFilterQuery;

        // Build query of all filters exept content type filter
        const filtersQueryWithoutContentTypeFilter = taxonomyFilterQuery
          ? `${languageFilterQuery} AND ${taxonomyFilterQuery}`
          : languageFilterQuery;

        // Build query of all taxonomies and all content types filter
        const allTaxonomiesAndAllContentTypesFilterQuery = `${languageFilterQuery} AND ${allContentTypesFilterQuery} AND ${allTaxonomiesFilterQuery}`;

        // Build query of all filters
        let filtersQuery = languageFilterQuery;

        if (contentTypeFilterQuery)
          filtersQuery += ` AND ${contentTypeFilterQuery}`;

        if (taxonomyFilterQuery) filtersQuery += ` AND ${taxonomyFilterQuery}`;

        return {
          languageFilterQuery,
          contentTypeFilterQuery,
          taxonomyFilterQuery,
          filtersQueryWithoutTaxonomyFilter,
          filtersQueryWithoutContentTypeFilter,
          allTaxonomiesAndAllContentTypesFilterQuery,
          allFiltersQuery: filtersQuery,
        };
      });

      // Reactivity: Watch (keywordSearch value)
      /* watch(keywordSearch, (currentVal, oldVal) => {
        // Get data from algolia
        search();
      }); */

      /* ------------------------- End Reactive ------------------------- */

      /* ------------------------- Methods ------------------------- */
      function filterByContentType(contentType, event) {
        // Update selected filter
        togglePush(filters.contentTypes, contentType);

        // Get data from algolia
        search();
      }

      function filterByTaxonomy(taxonomy, event) {
        filters.currentTaxonomyFilter = taxonomy;

        // Update selected filter
        if (isNaN(taxonomy) && taxonomy.toLowerCase() == "all")
          filters.taxonomies = [];
        // getKeysExeptAll(taxonomies); // Specific taxonomies to search
        else filters.taxonomies = [taxonomy]; // @TODO - multiple taxonomy

        // Get data from algolia
        search();

        // Reactive Dom
        nextTick(() => {
          jQuery("#taxonomyFilter a").removeClass("active");
          jQuery(event.target).tab("show");
        });
      }

      /**
       * Setup Algolia Autocomplete
       */
      function setupAutocomplete() {
        // This uses the `search` query parameter as the initial query
        keywordSearch.value = new URL(window.location).searchParams.get(
          "query"
        );

        let $autoCompleteScope = null;

        autocomplete({
          container: "#keywordSearch",
          placeholder: transKeywordSearch,
          detachedMediaQuery: "none",
          // debug: true,
          initialState: {
            // This uses the `search` query parameter as the initial query
            query: keywordSearch.value,
          },
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
                        filters: filtersQuery.value.allFiltersQuery,
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
          const $autocompleteItem = $(this).find(".autocomplete-selected");

          const itemLink = $autocompleteItem.length
            ? $autocompleteItem.data("itemlink")
            : "";

          /*const query = $autocompleteItem.length
            ? $autocompleteItem.html()
            : "";

          $autoCompleteScope.setQuery(query);
          $autoCompleteScope.setIsOpen(false);
          $autoCompleteScope.refresh();
          search();*/

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
            search();
            event.preventDefault();
          }
        });
      }

      /**
       * Go to item link
       *
       * @param {String} itemLink
       */
      function goToItemLink(itemLink) {
        window.location.href = itemLink;
      }

      /**
       * Search data from Algolia
       *
       * @param {Boolean} resetPage
       * @param {string} calledFrom e.g. "onMounted hook"
       */
      function search(resetPage = true, calledFrom = "") {
        loader.value = true;

        // Reset current page number
        if (resetPage) resetPagination();

        const query = keywordSearch.value ? keywordSearch.value.trim() : "";

        // Get data count from Algolia | Group by taxonomy
        getCount(query);

        // Get hits (items)
        algoliaIndex
          .search(query, {
            filters: filtersQuery.value.allFiltersQuery,
            // facets: ["field_cible_group", "type_1"],
            hitsPerPage: 10,
            page: results.pagination.page,
          })
          .then(({ hits, nbHits, nbPages, page }) => {
            results.list = hits;
            results.count = nbHits;
            results.pagination = { nbPages, page };

            loader.value = false;
          });

        if (!$('.path-admin').length && query != "" && calledFrom != "onMounted") {
          // Push search query to dataLayer
          pushDataLayer({
            event: "internal_search",
            type_moteur: "principal",
            searched_keyword: query,
          });
        }
      }

      /**
       * Get data count from Algolia | Group by taxonomy and content type
       *
       * @param {String} query
       */
      function getCount(query) {
        // Get data count from Algolia | Group by taxonomy
        algoliaIndex
          .search(query, {
            filters: filtersQuery.value.filtersQueryWithoutTaxonomyFilter,
            facets: ["field_cible_group"],
            hitsPerPage: 0,
          })
          .then(({ facets }) => {
            console.log("facets field_cible_group", facets);

            /* ---------- Update all taxonomies (filters) count value ---------- */

            // Update <All> count value
            taxonomies[taxonomyAllIndex].resultCount = 0;

            for (key in facets.field_cible_group) {
              taxonomies[taxonomyAllIndex].resultCount +=
                facets.field_cible_group[key];
            }

            // Update taxonomies count value
            for (index in taxonomies) {
              if (taxonomies[index].key.toLowerCase() == "all") continue;

              // Reset count value
              taxonomies[index].resultCount = 0;
              const key = taxonomies[index].key;

              if (
                facets &&
                typeof facets.field_cible_group !== "undefined" &&
                typeof facets.field_cible_group[key] !== "undefined"
              ) {
                taxonomies[index].resultCount = facets.field_cible_group[key];
              }
            }
          });

        // Get data count from Algolia | Group by content type
        algoliaIndex
          .search(query, {
            filters: filtersQuery.value.filtersQueryWithoutContentTypeFilter,
            facets: ["type_1"],
            hitsPerPage: 0,
          })
          .then(({ facets }) => {
            // console.log("facets type_1", facets);

            /* ---------- Update all content types (filters) count value ---------- */
            for (index in contentTypes) {
              // Reset count value
              contentTypes[index].resultCount = 0;
              const key = contentTypes[index].key;

              if (
                facets &&
                typeof facets.type_1 !== "undefined" &&
                typeof facets.type_1[key] !== "undefined"
              ) {
                contentTypes[index].resultCount = facets.type_1[key];
              }
            }
          });
      }

      /**
       *  Trancate text (e.g., description)
       *
       * @param {String} text
       * @param {integer} length
       * @param {String} clamp
       *
       * @return {String}
       */
      function truncate(text, length = 150, clamp = "...") {
        clamp = clamp || "...";

        return text && text.length > length
          ? text.slice(0, length) + clamp
          : text;
      }

      /**
       * Paginate Algolia query
       *
       * @param {String} dir
       *
       * @return void
       */
      function paginate(dir = "next") {
        results.pagination.page += dir == "prev" ? -1 : 1;

        // Get data from algolia
        search(false);
      }

      /**
       * Reset current page number | If (keyword, filter) has changed
       */
      function resetPagination() {
        results.pagination.page = 0;
      }

      /**
       *  Build Algolia query using filters values
       *
       * @param {String} fieldKey
       * @param {Array} arr
       * @param {String} operator
       *
       * @return {String}
       */
      function buildConditionsFilter(fieldKey, arr, operator = "OR") {
        let query = "";

        if (arr.length) {
          arr.map((val, i) => {
            if (i == 0) query += `(${fieldKey}:${val}`;
            else query += ` ${operator} ${fieldKey}:${val}`;
          });

          query += ")";
        }

        return query;
      }

      /**
       * Update selected filter (In array)
       */
      function togglePush(arr, val) {
        const index = arr.indexOf(val);

        if (index === -1) arr.push(val);
        else arr.splice(index, 1);
      }

      /**
       * Return all keys exept <All> key
       *
       * @param {Array of object}
       *
       * @return {Array}
       */
      function getKeysExeptAll(arr) {
        const keys = [];

        arr.forEach((obj) => {
          if (isNaN(obj.key) && obj.key.toLowerCase() == "all") return;

          keys.push(obj.key);
        });

        return keys;
      }
      /* ------------------------- End Methods ------------------------- */

      /* ------------------------- Lifecycle hooks ------------------------- */
      onMounted(() => {
        nextTick(() => {
          setupAutocomplete();
          search(true, "onMounted");

          $("#v-app .v-content").removeClass("d-none"); // @TODO
        });
      });
      /* ------------------------- End Lifecycle hooks ------------------------- */

      // Expose data
      return {
        taxonomies,
        taxonomyAllIndex,
        contentTypes,
        filterByContentType,
        filterByTaxonomy,
        filters,
        search,
        results,
        keywordSearch,
        paginate,
        loader,
        truncate,
      };
    },
  });

  vueApp.mount("#v-app");
})(jQuery);
