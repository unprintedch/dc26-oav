<?php
declare(strict_types=1);

/**
 * Member helper functions.
 *
 * Centralised data-access layer for the `member` CPT used by both
 * the member-profile and member-search blocks.
 *
 * @package dc26-oav
 */

/**
 * Retrieve the member post linked to a WordPress user.
 *
 * Strategy: direct link via user meta `_member_post_id` (fast), with a
 * fallback email lookup + transient cache. The direct link is persisted
 * on first successful lookup so subsequent calls are instant.
 *
 * @param WP_User $user Current user object.
 * @return WP_Post|null
 */
function dc26_get_member_by_user( WP_User $user ): ?WP_Post {
    $member_id = (int) get_user_meta( $user->ID, '_member_post_id', true );

    if ( $member_id ) {
        $post = get_post( $member_id );
        if ( $post && 'member' === $post->post_type ) {
            return $post;
        }
    }

    $cache_key = 'dc26_member_' . md5( $user->user_email );
    $cached_id = get_transient( $cache_key );

    if ( false !== $cached_id ) {
        $post = get_post( (int) $cached_id );
        if ( $post && 'member' === $post->post_type ) {
            return $post;
        }
    }

    $posts = get_posts( [
        'post_type'   => 'member',
        'post_status' => [ 'publish', 'private' ],
        'meta_key'    => 'email',
        'meta_value'  => $user->user_email,
        'numberposts' => 1,
    ] );

    if ( empty( $posts ) ) {
        return null;
    }

    set_transient( $cache_key, $posts[0]->ID, 5 * MINUTE_IN_SECONDS );
    update_user_meta( $user->ID, '_member_post_id', $posts[0]->ID );

    return $posts[0];
}

/**
 * Normalise a field value to a trimmed string.
 *
 * ACF sometimes returns arrays (e.g. for select fields). This helper
 * always returns a plain string so templates can use it directly.
 *
 * @param mixed $value Raw field value.
 * @return string
 */
function dc26_member_scalar( $value ): string {
    if ( is_array( $value ) ) {
        if ( isset( $value['label'] ) && is_string( $value['label'] ) ) {
            return $value['label'];
        }
        if ( isset( $value['value'] ) && is_string( $value['value'] ) ) {
            return $value['value'];
        }
        $first = reset( $value );
        return is_string( $first ) ? $first : '';
    }

    return is_scalar( $value ) ? trim( (string) $value ) : '';
}

/**
 * Build the gendered status label from taxonomy + genre field.
 *
 * @param int    $post_id Member post ID.
 * @param string $genre   Genre value ("M" or "F").
 * @return string
 */
function dc26_member_status_label( int $post_id, string $genre ): string {
    $statut_externe = dc26_member_scalar( get_field( 'statut_externe', $post_id ) );
    if ( $statut_externe ) {
        return $statut_externe;
    }

    $terms = get_the_terms( $post_id, 'statut' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    $status = $terms[0]->name;

    if ( 'M' === $genre ) {
        $status = str_replace( '(e)', '', $status );
    } else {
        $status = str_replace( '(e)', 'e', $status );
    }

    return $status;
}

/**
 * Get all speciality terms with their OAV IDs, split into FSA and regular.
 *
 * @return array{fsa: array, regular: array}
 */
function dc26_get_all_specialities(): array {
    $terms = get_terms( [
        'taxonomy'   => 'speciality',
        'hide_empty' => false,
    ] );

    $fsa     = [];
    $regular = [];

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $item = [
                'term_id' => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
                'oav_id'  => get_field( 'id_specialite_oav', $term ),
                'parent'  => (int) $term->parent,
            ];
            if ( 110 === (int) $term->parent ) {
                $fsa[] = $item;
            } else {
                $regular[] = $item;
            }
        }
    }

    return [ 'fsa' => $fsa, 'regular' => $regular ];
}

/**
 * Get all language terms with their OAV IDs.
 *
 * @return array
 */
function dc26_get_all_languages(): array {
    $terms  = get_terms( [
        'taxonomy'   => 'language',
        'hide_empty' => false,
    ] );
    $result = [];

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $result[] = [
                'term_id' => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
                'oav_id'  => get_field( 'id_langue_oav', $term ),
            ];
        }
    }

    return $result;
}

/**
 * Gather every displayable field for a member into a single array.
 *
 * Handles the etude-vs-personal address logic internally.
 *
 * @param int $post_id Member post ID.
 * @return array<string, mixed>
 */
function dc26_get_member_data( int $post_id ): array {
    $first_name = dc26_member_scalar( get_field( 'prenom', $post_id ) );
    $last_name  = dc26_member_scalar( get_field( 'nom', $post_id ) );
    $genre      = dc26_member_scalar( get_field( 'genre', $post_id ) );

    $etude_terms = get_the_terms( $post_id, 'etude' );
    $etude       = ( ! empty( $etude_terms ) && ! is_wp_error( $etude_terms ) ) ? $etude_terms[0] : null;
    $etude_id    = $etude ? (int) $etude->term_taxonomy_id : 0;

    $use_etude_addr = (bool) get_field( 'coordonnees_idem_etude', $post_id );
    $addr_src       = ( $use_etude_addr && $etude_id ) ? 'etude_' . $etude_id : $post_id;

    $homepage       = get_field( 'homepage', $post_id );
    $homepage_url   = '';
    $homepage_label = '';
    if ( is_array( $homepage ) ) {
        $homepage_url   = ! empty( $homepage['url'] ) ? (string) $homepage['url'] : '';
        $homepage_label = ! empty( $homepage['title'] ) ? (string) $homepage['title'] : $homepage_url;
    } else {
        $homepage_url   = dc26_member_scalar( $homepage );
        $homepage_label = $homepage_url;
    }
    if ( $homepage_url && ! preg_match( '#^https?://#i', $homepage_url ) ) {
        $homepage_url = 'https://' . ltrim( $homepage_url, '/' );
    }

    // Specialities split: FSA (parent 110) vs regular.
    $all_specs       = get_the_terms( $post_id, 'speciality' );
    $specs_fsa       = [];
    $specs_regular   = [];
    if ( ! empty( $all_specs ) && ! is_wp_error( $all_specs ) ) {
        foreach ( $all_specs as $term ) {
            if ( 110 === (int) $term->parent ) {
                $specs_fsa[] = $term;
            } else {
                $specs_regular[] = $term;
            }
        }
    }

    $languages = get_the_terms( $post_id, 'language' );
    if ( empty( $languages ) || is_wp_error( $languages ) ) {
        $languages = [];
    }

    // Commissions repeater.
    $commissions = [];
    if ( have_rows( 'commission', $post_id ) ) {
        while ( have_rows( 'commission', $post_id ) ) {
            the_row();
            $commissions[] = [
                'name'      => (string) get_sub_field( 'commission_name' ),
                'president' => (bool) get_sub_field( 'commission_president' ),
            ];
        }
    }

    return [
        'full_name'          => trim( $first_name . ' ' . $last_name ),
        'first_name'         => $first_name,
        'last_name'          => $last_name,
        'genre'              => $genre,
        'titre'              => dc26_member_scalar( get_field( 'profession', $post_id ) ),
        'status'             => dc26_member_status_label( $post_id, $genre ),
        'email'              => dc26_member_scalar( get_field( 'email', $post_id ) ),
        'phone'              => dc26_member_scalar( get_field( 'tel1', $post_id ) ),
        'fax'                => dc26_member_scalar( get_field( 'fax', $post_id ) ),
        'photo_id'           => get_field( 'img_membre', $post_id ),
        'homepage_url'       => $homepage_url,
        'homepage_label'     => $homepage_label,
        'etude_name'         => $etude ? $etude->name : '',
        'etude_slug'         => $etude ? $etude->slug : '',
        'etude_id'           => $etude_id,
        'rue'                => dc26_member_scalar( get_field( 'rue', $addr_src ) ),
        'rue_no'             => dc26_member_scalar( get_field( 'rue_no', $addr_src ) ),
        'npa'                => dc26_member_scalar( get_field( 'npa', $addr_src ) ),
        'ville'              => dc26_member_scalar( get_field( 'localite', $addr_src ) ),
        'case_postale'       => dc26_member_scalar( get_field( 'case_postale', $addr_src ) ),
        'complement_adresse' => dc26_member_scalar( get_field( 'complement_adresse', $addr_src ) ),
        'specialities_fsa'   => $specs_fsa,
        'specialities'       => $specs_regular,
        'languages'          => $languages,
        'commissions'        => $commissions,
    ];
}
