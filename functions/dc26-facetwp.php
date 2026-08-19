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

/**
 * Group the date_de_publication facet by month instead of indexing every
 * exact post_date timestamp as its own value.
 */
add_filter('facetwp_indexer_row_data', function (array $output, array $params): array {
    if ('date_de_publication' !== ($params['facet']['name'] ?? '')) {
        return $output;
    }

    foreach ($output as &$row) {
        $timestamp = strtotime((string) $row['facet_value']);
        if (!$timestamp) {
            continue;
        }
        $row['facet_value'] = gmdate('Y-m', $timestamp);
        $row['facet_display_value'] = date_i18n('F Y', $timestamp);
    }
    unset($row);

    return $output;
}, 10, 2);
