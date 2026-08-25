<?php
declare(strict_types=1);

/**
 * Allows `documentation` posts to be tagged with the core `category`
 * taxonomy (e.g. "À la une"), so they can appear in the news-listing block
 * alongside regular posts.
 */

add_action( 'init', function (): void {
    register_taxonomy_for_object_type( 'category', 'documentation' );
}, 20 );
