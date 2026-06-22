<?php
declare(strict_types=1);

/**
 * Capture the allowed category IDs from the FacetWP query's tax_query.
 * This runs during both initial page load AND AJAX refreshes.
 */
add_filter('facetwp_query_args', function (array $query_args, $class): array {
    if (!empty($query_args['tax_query'])) {
        foreach ($query_args['tax_query'] as $tq) {
            if (is_array($tq) && isset($tq['taxonomy']) && 'category' === $tq['taxonomy']) {
                $GLOBALS['dc26_news_listing_cat_ids'] = array_map('intval', (array) $tq['terms']);
                break;
            }
        }
    }
    return $query_args;
}, 10, 2);

/**
 * Limit the categories_news_pills facet choices to only the
 * categories selected in the news-listing block.
 */
add_filter('facetwp_facet_render_args', function (array $args): array {
    if ('categories_news_pills' !== ($args['facet']['name'] ?? '')) {
        return $args;
    }

    $allowed = $GLOBALS['dc26_news_listing_cat_ids'] ?? [];
    if (empty($allowed)) {
        return $args;
    }

    $args['values'] = array_values(array_filter(
        $args['values'],
        fn($choice) => in_array((int) $choice['term_id'], $allowed, true)
    ));

    return $args;
});
