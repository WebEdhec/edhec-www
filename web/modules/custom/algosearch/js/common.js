const language = jQuery("html").attr("lang");

/* ------------------------- Init Algolia ------------------------- */
const appId = "2CI5HFVL81";
const apiKey = "7c29b7b2168a6cd5949b1c208e117e9e";
const algoliaIndexName = "edhec";
const searchClient = algoliasearch(appId, apiKey);
const algoliaIndex = searchClient.initIndex(algoliaIndexName);

// Autocomplete
const { autocomplete, getAlgoliaResults } = window["@algolia/autocomplete-js"];
/* ------------------------- End Init Algolia ------------------------- */
