<?php
declare(strict_types=1);

/**
 * Automatic public/private visibility for the `documentation` CPT.
 *
 * Rule: a document is public (post_status=publish) if at least one of its
 * `documentation-type` terms has `public_default` checked, unless the
 * document itself has `force_private` checked — otherwise private.
 * Native WP_Query already excludes `private` posts for logged-out visitors,
 * so no extra front-end gating is needed.
 */

/**
 * Compute and apply the correct post_status for one documentation post.
 *
 * @param int $post_id Documentation post ID.
 */
function dc26_documentation_apply_visibility( int $post_id ): void {
    $post = get_post( $post_id );
    if ( ! $post || 'documentation' !== $post->post_type ) {
        return;
    }
    // Only govern posts already live (publish/private) — never fight the
    // editor while a post is still a draft.
    if ( ! in_array( $post->post_status, [ 'publish', 'private' ], true ) ) {
        return;
    }

    if ( (bool) get_field( 'force_private', $post_id ) ) {
        $target_status = 'private';
    } else {
        $term_ids = wp_get_object_terms( $post_id, 'documentation-type', [ 'fields' => 'ids' ] );
        $is_public = false;
        if ( ! is_wp_error( $term_ids ) ) {
            foreach ( $term_ids as $term_id ) {
                if ( (bool) get_field( 'public_default', 'documentation-type_' . $term_id ) ) {
                    $is_public = true;
                    break;
                }
            }
        }
        $target_status = $is_public ? 'publish' : 'private';
    }

    if ( $target_status !== $post->post_status ) {
        wp_update_post( [ 'ID' => $post_id, 'post_status' => $target_status ] );
    }
}

/**
 * React to documentation-type term (re)assignment — any save pathway.
 */
add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy ): void {
    if ( 'documentation-type' !== $taxonomy ) {
        return;
    }
    dc26_documentation_apply_visibility( (int) $object_id );
}, 10, 4 );

/**
 * React to the `force_private` toggle changing (ACF save, classic or REST).
 */
add_action( 'acf/save_post', function ( $post_id ): void {
    if ( ! is_numeric( $post_id ) ) {
        return;
    }
    dc26_documentation_apply_visibility( (int) $post_id );
}, 20 );
