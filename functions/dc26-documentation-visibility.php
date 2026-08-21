<?php
declare(strict_types=1);

/**
 * Automatic members-only gating for the `documentation` CPT.
 *
 * Rule: a document is public if at least one of its `documentation-type`
 * terms has `public_default` checked, unless the document itself has
 * `force_private` checked — otherwise it's gated via the dc26-login plugin's
 * `_members_only` meta (post_status always stays publish). This reuses the
 * same members-only system as manually-flagged posts/pages, so the_content,
 * excerpt and REST output are all gated consistently for every post type.
 */

/**
 * Compute and apply the correct `_members_only` state for one documentation post.
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
        $is_public = false;
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
    }

    $members_only = ! $is_public;
    if ( (bool) get_post_meta( $post_id, '_members_only', true ) !== $members_only ) {
        update_post_meta( $post_id, '_members_only', $members_only );
    }

    if ( 'private' === $post->post_status ) {
        wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
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
