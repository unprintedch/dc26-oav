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
 * Find a dc26/news-listing block (at any depth) and return its selected category IDs.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @return int[]|null Category IDs, or null when the content has no news-listing block.
 */
function dc26_news_listing_block_cat_ids(array $blocks): ?array {
    foreach ($blocks as $block) {
        if ('dc26/news-listing' === ($block['blockName'] ?? '')) {
            return array_map('intval', (array) ($block['attrs']['data']['nl_categories'] ?? []));
        }
        if (!empty($block['innerBlocks'])) {
            $found = dc26_news_listing_block_cat_ids($block['innerBlocks']);
            if (null !== $found) {
                return $found;
            }
        }
    }
    return null;
}

/**
 * Preselect the "À la une" pill server-side on pages whose news-listing block
 * offers it, so the first render already matches the highlighted pill (no
 * "everything" flash while a JS click catches up). Deep-linked selections
 * (?_categories_news_pills=…) are never overridden.
 *
 * FacetWP applies this filter on `init`, before the main query exists, so the
 * page is resolved from the request URL.
 */
add_filter('facetwp_preload_url_vars', function (array $url_vars): array {
    if (!empty($url_vars['categories_news_pills']) || empty($_SERVER['REQUEST_URI'])) {
        return $url_vars;
    }

    $path    = (string) strtok(wp_unslash((string) $_SERVER['REQUEST_URI']), '?');
    $post_id = url_to_postid(home_url($path));
    if (!$post_id) {
        return $url_vars;
    }

    $default = get_term_by('slug', 'actualite', 'category');
    $cat_ids = dc26_news_listing_block_cat_ids(parse_blocks((string) get_post_field('post_content', $post_id)));
    if ($default && null !== $cat_ids && in_array((int) $default->term_id, $cat_ids, true)) {
        $url_vars['categories_news_pills'] = ['actualite'];
    }

    return $url_vars;
});

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
