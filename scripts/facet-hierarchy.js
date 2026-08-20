(function () {
  function getHierarchy() {
    var block = document.querySelector(".dc26-news-listing[data-cat-hierarchy]");
    if (!block) return {};
    try {
      return JSON.parse(block.dataset.catHierarchy);
    } catch (e) {
      return {};
    }
  }

  function getParentOfChild(hierarchy, slug) {
    for (var parent in hierarchy) {
      if (hierarchy[parent].includes(slug)) return parent;
    }
    return null;
  }

  function reorganize() {
    var hierarchy = getHierarchy();
    if (!Object.keys(hierarchy).length) return;

    var facetEl = document.querySelector(
      ".facetwp-facet-categories_news_pills"
    );
    if (!facetEl) return;

    var block = facetEl.closest(".dc26-news-listing");
    if (!block) return;

    var subContainer = block.querySelector(".dc26-news-listing__sub-pills");
    if (!subContainer) return;

    var allChildSlugs = new Set();
    Object.values(hierarchy).forEach(function (arr) {
      arr.forEach(function (s) {
        allChildSlugs.add(s);
      });
    });

    var activeValues =
      typeof FWP !== "undefined" && FWP.facets
        ? FWP.facets["categories_news_pills"] || []
        : [];
    var activeSlug = activeValues[0] || "";

    var showChildrenOf = null;
    if (hierarchy[activeSlug]) {
      showChildrenOf = activeSlug;
    } else if (allChildSlugs.has(activeSlug)) {
      showChildrenOf = getParentOfChild(hierarchy, activeSlug);
    }

    facetEl.querySelectorAll(".facetwp-radio").forEach(function (el) {
      if (allChildSlugs.has(el.dataset.value)) {
        el.style.display = "none";
      }
    });

    subContainer.innerHTML = "";

    if (showChildrenOf && hierarchy[showChildrenOf]) {
      hierarchy[showChildrenOf].forEach(function (slug) {
        var original = facetEl.querySelector(
          '.facetwp-radio[data-value="' + slug + '"]'
        );
        if (!original) return;

        var pill = document.createElement("button");
        pill.type = "button";
        pill.className = "dc26-sub-pill";
        if (activeSlug === slug) pill.classList.add("is-active");
        pill.textContent = original.textContent
          .replace(/\s*\(\d+\)\s*$/, "")
          .trim();

        pill.addEventListener("click", function () {
          if (activeSlug === slug) {
            var parentRadio = facetEl.querySelector(
              '.facetwp-radio[data-value="' + showChildrenOf + '"]'
            );
            if (parentRadio) parentRadio.click();
          } else {
            original.click();
          }
        });

        subContainer.appendChild(pill);
      });

      subContainer.style.display = "";
    } else {
      subContainer.style.display = "none";
    }
  }

  /**
   * Default the categories_news_pills radio facet to "À la une"
   * (slug "actualite") on first page load only — never overrides a
   * deep-linked selection (e.g. ?_categories_news_pills=formation) and
   * never fires again on subsequent AJAX refreshes.
   */
  function applyDefaultPill() {
    if (FWP.loaded) return;

    var current =
      typeof FWP !== "undefined" && FWP.facets
        ? FWP.facets["categories_news_pills"] || []
        : [];
    if (current.length) return;

    var defaultPill = document.querySelector(
      '.facetwp-facet-categories_news_pills .facetwp-radio[data-value="actualite"]'
    );
    if (defaultPill) defaultPill.click();
  }

  document.addEventListener("facetwp-loaded", applyDefaultPill);
  document.addEventListener("facetwp-loaded", reorganize);
})();
