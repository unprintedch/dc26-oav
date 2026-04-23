<?php
declare(strict_types=1);

/**
 * Examen progress REST API endpoint.
 *
 * Provides /dc26/v1/examen-progress for toggling per-document
 * completion state, stored as JSON in user meta.
 *
 * @package dc26-oav
 */

add_action( 'rest_api_init', 'dc26_register_examen_routes' );

function dc26_register_examen_routes(): void {
    register_rest_route( 'dc26/v1', '/examen-progress', [
        'methods'             => 'POST',
        'callback'            => 'dc26_rest_examen_progress',
        'permission_callback' => 'is_user_logged_in',
    ] );
}

/**
 * Toggle a document's completion state for the current user.
 *
 * Expects JSON body: { "key": "{post_id}_{md5}", "done": true|false }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function dc26_rest_examen_progress( WP_REST_Request $request ): WP_REST_Response {
    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_REST_Response( [ 'error' => 'Invalid nonce.' ], 403 );
    }

    $key  = sanitize_text_field( $request->get_param( 'key' ) ?? '' );
    $done = (bool) $request->get_param( 'done' );

    if ( ! preg_match( '/^\d+_[a-f0-9]{32}$/', $key ) ) {
        return new WP_REST_Response( [ 'error' => 'Invalid key format.' ], 400 );
    }

    $user_id  = get_current_user_id();
    $meta_key = 'dc26_examen_progress';
    $progress = get_user_meta( $user_id, $meta_key, true );

    if ( ! is_array( $progress ) ) {
        $progress = [];
    }

    if ( $done ) {
        $progress[ $key ] = true;
    } else {
        unset( $progress[ $key ] );
    }

    update_user_meta( $user_id, $meta_key, $progress );

    return new WP_REST_Response( [ 'ok' => true, 'key' => $key, 'done' => $done ] );
}
