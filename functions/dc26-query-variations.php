<?php
/**
 * Query Loop variations pour les conférences du stage.
 * Utilise pre_render_block + pre_get_posts pour injecter
 * le filtre date ACF — plus fiable que query_loop_block_query_vars.
 */
declare(strict_types=1);

/**
 * Capture le namespace du bloc Query Loop juste avant son rendu.
 * Stocké dans un static pour être lu par pre_get_posts.
 */
add_filter( 'pre_render_block', function ( $pre, array $block ) {
    if ( 'core/query' !== ( $block['blockName'] ?? '' ) ) {
        return $pre;
    }

    $namespace = $block['attrs']['namespace'] ?? '';
    if ( in_array( $namespace, [ 'dc26/conferences-a-venir', 'dc26/conferences-passees' ], true ) ) {
        dc26_set_conference_namespace( $namespace );
    }

    return $pre;
}, 10, 2 );

/**
 * Stockage du namespace courant (static).
 */
function dc26_set_conference_namespace( string $ns = '' ): string {
    static $current = '';
    if ( '' !== $ns ) {
        $current = $ns;
    }
    return $current;
}

/**
 * Injecte le filtre date ACF sur la query de conférences.
 */
add_action( 'pre_get_posts', function ( WP_Query $query ) {
    $namespace = dc26_set_conference_namespace();

    if ( '' === $namespace ) {
        return;
    }
    if ( 'conference-du-stage' !== $query->get( 'post_type' ) ) {
        return;
    }

    $today = date( 'Ymd' );

    if ( 'dc26/conferences-a-venir' === $namespace ) {
        $query->set( 'meta_key', 'date' );
        $query->set( 'meta_value', $today );
        $query->set( 'meta_compare', '>=' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'order', 'ASC' );
    } elseif ( 'dc26/conferences-passees' === $namespace ) {
        $query->set( 'meta_key', 'date' );
        $query->set( 'meta_value', $today );
        $query->set( 'meta_compare', '<' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'order', 'DESC' );
    }

    // Reset après usage pour ne pas polluer les autres queries.
    dc26_set_conference_namespace( '__done__' );
} );

/**
 * Enqueue le script de variations dans l'éditeur.
 */
add_action( 'enqueue_block_editor_assets', function (): void {
    $path = get_stylesheet_directory() . '/scripts/query-variations.js';
    wp_enqueue_script(
        'dc26-query-variations',
        get_stylesheet_directory_uri() . '/scripts/query-variations.js',
        [ 'wp-blocks', 'wp-dom-ready', 'wp-i18n' ],
        file_exists( $path ) ? (string) filemtime( $path ) : '1.0.0',
        true
    );
} );
