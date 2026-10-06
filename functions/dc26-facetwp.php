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

/**
 * Annuaire : membres actifs en premier, puis conseil, invités, UE/AELE et
 * stagiaires en dernier (au lieu de l'ordre de date, où les stagiaires
 * récemment importés passaient devant). L'ordre existant sert de départage.
 *
 * Activé par l'argument `dc26_statut_priority` de la WP_Query du bloc
 * member-search ; ignoré quand FacetWP impose un ordre de pertinence
 * (recherche texte : orderby post__in).
 */
add_filter('posts_clauses', function (array $clauses, WP_Query $query): array {
    if (!$query->get('dc26_statut_priority') || 'post__in' === $query->get('orderby')) {
        return $clauses;
    }

    global $wpdb;

    $priority = $wpdb->prepare(
        "(SELECT MIN(CASE t.slug WHEN %s THEN 0 WHEN %s THEN 1 WHEN %s THEN 2 WHEN %s THEN 3 WHEN %s THEN 4 ELSE 5 END)
          FROM {$wpdb->term_relationships} tr
          INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
          INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
          WHERE tr.object_id = {$wpdb->posts}.ID)",
        'membre_actif',
        'avocat_conseil',
        'membre_invite',
        'membre_eu_aele',
        'stagiaire',
        'statut'
    );

    $clauses['orderby'] = $priority . ' ASC' . ('' !== trim((string) $clauses['orderby']) ? ', ' . $clauses['orderby'] : '');

    return $clauses;
}, 10, 2);

/**
 * Grille d'actualités/agenda : ordre chronologique calculé dans la requête
 * (et non page par page), pour que « Charger plus » prolonge l'ordre sans
 * rupture : d'abord les contenus à venir, du plus proche au plus lointain,
 * puis les événements passés, du plus récent au plus ancien.
 *
 * Un contenu sans date d'événement (champ ACF `date`) est « à venir » et
 * se positionne selon sa date de publication.
 *
 * Activé par l'argument `dc26_event_order` de la WP_Query du bloc news-listing.
 */
add_filter('posts_clauses', function (array $clauses, WP_Query $query): array {
    if (!$query->get('dc26_event_order')) {
        return $clauses;
    }

    global $wpdb;

    $event_date = "(SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = {$wpdb->posts}.ID AND pm.meta_key = 'date' AND pm.meta_value <> '' LIMIT 1)";
    $effective  = "COALESCE({$event_date}, DATE_FORMAT({$wpdb->posts}.post_date, '%Y%m%d'))";
    $today      = (int) current_time('Ymd');

    // $today est un entier issu de current_time() : pas de prepare() ici, il
    // traiterait les %Y%m%d des formats de date comme des placeholders.
    $clauses['orderby'] = "({$event_date} IS NOT NULL AND {$event_date} < {$today}) ASC,
         ABS(DATEDIFF(STR_TO_DATE({$effective}, '%Y%m%d'), STR_TO_DATE('{$today}', '%Y%m%d'))) ASC,
         {$wpdb->posts}.ID DESC";

    return $clauses;
}, 11, 2);

/**
 * Libellés français pour le filtre « Période » (date_range) de FacetWP.
 */
add_filter('facetwp_i18n', function (string $string): string {
    $labels = [
        'Start date' => 'Du',
        'End date'   => 'Au',
        'Date'       => 'Date',
    ];
    return $labels[$string] ?? $string;
});
