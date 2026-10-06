<?php
declare(strict_types=1);

/**
 * IDs à exclure des grilles (news-listing, documentation-listing) pour un
 * visiteur non connecté : contenus "Réservés aux connectés" (dc26-login,
 * meta individuelle ou règles batch) et contenus protégés par mot de passe WP.
 *
 * L'exclusion passe par `post__not_in` dans la WP_Query, pas par un masquage
 * après coup : pagination, compteurs et facettes FacetWP restent exacts.
 *
 * @param string[] $post_types Types de contenu de la grille.
 * @return int[] Vide pour un utilisateur connecté.
 */
function dc26_grid_hidden_post_ids(array $post_types): array {
    if (is_user_logged_in()) {
        return [];
    }

    static $cache = [];
    $key = implode(',', $post_types);
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $ids = get_posts([
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    $hidden = [];
    foreach ($ids as $post_id) {
        $post_id = (int) $post_id;
        if (post_password_required($post_id)
            || (function_exists('dc26_members_only_is_protected') && dc26_members_only_is_protected($post_id))) {
            $hidden[] = $post_id;
        }
    }

    return $cache[$key] = $hidden;
}
