<?php
declare(strict_types=1);

/**
 * OAV — facettes FacetWP : option de tri « Par étude », année des conférences, tri des années.
 * Déplacé depuis dc26-base v1.2.2 (functions/dc26-facet.php), où ce code était propre à OAV.
 */
add_filter('facetwp_facet_sort_options', function (array $options, array $params): array {
    if (empty($params['facet']['name']) || 'sort_firm' !== $params['facet']['name']) {
        return $options;
    }

    $options['etude'] = array(
        'label'      => __('Par étude', 'dc26-oav'),
        'query_args' => array(
            'orderby'  => 'taxonomy',
            'tax_name' => 'etude',
            'tax_orderby' => 'name',
            'order'       => 'desc',
        ),
    );

    return $options;
}, 10, 2);

add_filter('facetwp_index_row', function ($params, $class) {
    // FacetWP passe false quand une ligne est déjà ignorée par un autre filtre.
    if (!is_array($params)) {
        return $params;
    }

    $facet_name = (string) ($params['facet_name'] ?? '');
    $facet_source = (string) ($params['facet_source'] ?? '');

    // Keep this list aligned with your FacetWP "Name" values
    // for the conference year filter(s).
    $year_facets = array('date_conference', 'annee', 'conference_year');
    $is_year_facet = in_array($facet_name, $year_facets, true);
    $is_date_conference_source = false !== strpos($facet_source, 'date_conference');

    // Apply if facet name matches OR source field is date_conference.
    if (!$is_year_facet && !$is_date_conference_source) {
        return $params;
    }

    $raw = trim((string) ($params['facet_value'] ?? ''));
    if ('' === $raw) {
        $raw = trim((string) ($params['facet_display_value'] ?? ''));
    }
    if ('' === $raw) {
        return $params;
    }

    // Already a year (e.g. "2017")
    if (preg_match('/^\d{4}$/', $raw)) {
        $params['facet_value'] = $raw;
        $params['facet_display_value'] = $raw;
        $params['facet_sort_value'] = $raw;
        return $params;
    }

    // Date formats like "2017-01-09" or similar -> "2017"
    $timestamp = strtotime($raw);
    if (false !== $timestamp) {
        $year = gmdate('Y', $timestamp);
        $params['facet_value'] = $year;
        $params['facet_display_value'] = $year;
        $params['facet_sort_value'] = $year;
    }

    return $params;
}, 10, 2);

add_filter('facetwp_facet_orderby', function ($orderby, array $facet) {
    if (empty($facet['name']) || 'date_conference' !== $facet['name']) {
        return $orderby;
    }

    // Values are normalized to YYYY in the index hook above.
    // Numeric sort ensures newest years first.
    return 'CAST(f.facet_value AS UNSIGNED) DESC';
}, 10, 2);