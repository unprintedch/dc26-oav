<?php
declare(strict_types=1);

/**
 * One-time default visibility for the `documentation` CPT.
 *
 * The only ongoing control for a document's visibility is the dc26-login
 * "Accès > Réservé aux connectés" toggle (native `_members_only` meta,
 * works the same for every post type). This file only SEEDS that value the
 * first time a document is published, based on its `documentation-type`
 * terms' `public_default` field — after that single moment, the toggle is
 * never touched automatically again, so editors always have one clear place
 * to change it.
 */

add_action( 'transition_post_status', 'dc26_documentation_seed_visibility', 10, 3 );

function dc26_documentation_seed_visibility( string $new_status, string $old_status, WP_Post $post ): void {
    if ( 'documentation' !== $post->post_type ) {
        return;
    }
    // Only the first transition into publish/private seeds the default —
    // never override a value that's already been set (migrated or manual).
    if ( in_array( $old_status, [ 'publish', 'private' ], true ) ) {
        return;
    }
    if ( ! in_array( $new_status, [ 'publish', 'private' ], true ) ) {
        return;
    }
    if ( metadata_exists( 'post', $post->ID, '_members_only' ) ) {
        return;
    }

    $term_ids  = wp_get_object_terms( $post->ID, 'documentation-type', [ 'fields' => 'ids' ] );
    $is_public = false;
    if ( ! is_wp_error( $term_ids ) ) {
        foreach ( $term_ids as $term_id ) {
            if ( (bool) get_field( 'public_default', 'documentation-type_' . $term_id ) ) {
                $is_public = true;
                break;
            }
        }
    }

    update_post_meta( $post->ID, '_members_only', ! $is_public );
}
