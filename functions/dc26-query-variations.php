<?php
/**
 * Query Loop variations pour les conférences du stage.
 * Injecte le filtre date ACF via query_loop_block_query_vars.
 */
declare(strict_types=1);

/**
 * Namespace du bloc core/query en cours de rendu.
 *
 * Le filtre `query_loop_block_query_vars` reçoit le bloc core/post-template,
 * qui ne porte pas le namespace du bloc Query parent : on le relève donc juste
 * avant le rendu de chaque core/query. Il n'est jamais « consommé » : chaque
 * bloc Query le réécrit, ce qui évite le bug où seule la première requête de
 * la page était filtrée (« Conférences passées » affichait les conférences à venir).
 */
function dc26_conference_current_namespace( ?string $set = null ): string {
    static $current = '';
    if ( null !== $set ) {
        $current = $set;
    }
    return $current;
}

add_filter( 'pre_render_block', function ( $pre, array $block ) {
    if ( 'core/query' === ( $block['blockName'] ?? '' ) ) {
        dc26_conference_current_namespace( (string) ( $block['attrs']['namespace'] ?? '' ) );
    }
    return $pre;
}, 10, 2 );

/**
 * Filtre date ACF sur les deux Query Loop de conférences, selon le namespace du bloc.
 *
 * Les conférences sans date ACF sont rangées dans « passées » (en fin de liste).
 *
 * @param array<string, mixed> $query Query vars du bloc.
 * @param WP_Block             $block Bloc core/post-template.
 * @return array<string, mixed>
 */
add_filter( 'query_loop_block_query_vars', function ( array $query, WP_Block $block ): array {
    $namespace = dc26_conference_current_namespace();
    $today     = (int) current_time( 'Ymd' );

    if ( 'dc26/conferences-a-venir' === $namespace ) {
        $query['meta_query'] = [
            'dc26_date' => [
                'key'     => 'date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ];
        $query['orderby'] = [ 'dc26_date' => 'ASC' ];
    } elseif ( 'dc26/conferences-passees' === $namespace ) {
        $query['meta_query'] = [
            'relation'  => 'OR',
            'dc26_date' => [
                'key'     => 'date',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => 'date',
                'compare' => 'NOT EXISTS',
            ],
        ];
        $query['orderby'] = [ 'dc26_date' => 'DESC', 'date' => 'DESC' ];
    }

    return $query;
}, 20, 2 );

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
