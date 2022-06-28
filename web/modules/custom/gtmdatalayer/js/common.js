(function ($) {
  Drupal.behaviors.gtmDatalayerCommon = {
    attach: function (context, settings) {},
  };
})(jQuery);

/**
 * Push data to GTM
 *
 * @param {object} data
 */
function pushDataLayer(data) {
  if (typeof dataLayer !== "undefined") {
    dataLayer.push(data);
  }
}

/**
 * Get element content without html tags and trim it to get the text only
 *
 * @param {object} element
 *
 * @return {string}
 */
function getText($element) {
  if ($element.length) {
    if ($element.length == 1) return $element.text().trim();
    return $element.clone().children().remove().end().text().trim();
  }

  return "";
}

/**
 * Check if string is an external link
 *
 * @param {string} url
 *
 * @return {string}
 */
const isExternalLink = (url) => {
  if (url) {
    if (url.includes("mailto")) return false;

    // get domain name from url by regex
    const match = url.match(
      /(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/g
    );

    if (match) {
      const domain = match[0];
      console.log("domain", domain);
      return domain !== window.location.host;
    }
  }

  return false;
};
