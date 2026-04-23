<?php
declare(strict_types=1);

/**
 * Member profile REST API endpoints.
 *
 * Provides /dc26/v1/member/update and /dc26/v1/member/photo for
 * inline profile editing from the member-profile block.
 *
 * @package dc26-oav
 */

add_action( 'rest_api_init', 'dc26_register_member_routes' );

function dc26_register_member_routes(): void {
    register_rest_route( 'dc26/v1', '/member/update', [
        'methods'             => 'POST',
        'callback'            => 'dc26_rest_member_update',
        'permission_callback' => 'is_user_logged_in',
    ] );

    register_rest_route( 'dc26/v1', '/member/photo', [
        'methods'             => 'POST',
        'callback'            => 'dc26_rest_member_photo',
        'permission_callback' => 'is_user_logged_in',
    ] );
}

/**
 * Handle section-based profile updates.
 */
function dc26_rest_member_update( WP_REST_Request $request ): WP_REST_Response {
    $member = dc26_get_member_by_user( wp_get_current_user() );
    if ( ! $member ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Aucun profil membre trouvé.' ], 404 );
    }

    $post_id = $member->ID;
    $section = sanitize_key( $request->get_param( 'section' ) ?? '' );

    switch ( $section ) {
        case 'personal':
            update_field( 'prenom', sanitize_text_field( $request->get_param( 'prenom' ) ?? '' ), $post_id );
            update_field( 'nom', sanitize_text_field( $request->get_param( 'nom' ) ?? '' ), $post_id );
            update_field( 'profession', sanitize_text_field( $request->get_param( 'profession' ) ?? '' ), $post_id );
            break;

        case 'address':
            $use_etude = (bool) get_field( 'coordonnees_idem_etude', $post_id );
            if ( $use_etude ) {
                return new WP_REST_Response( [
                    'success' => false,
                    'message' => "L'adresse est liée à l'étude et ne peut pas être modifiée ici.",
                ], 400 );
            }
            update_field( 'rue', sanitize_text_field( $request->get_param( 'rue' ) ?? '' ), $post_id );
            update_field( 'rue_no', sanitize_text_field( $request->get_param( 'rue_no' ) ?? '' ), $post_id );
            update_field( 'complement_adresse', sanitize_text_field( $request->get_param( 'complement_adresse' ) ?? '' ), $post_id );
            update_field( 'case_postale', sanitize_text_field( $request->get_param( 'case_postale' ) ?? '' ), $post_id );
            update_field( 'npa', sanitize_text_field( $request->get_param( 'npa' ) ?? '' ), $post_id );
            update_field( 'localite', sanitize_text_field( $request->get_param( 'localite' ) ?? '' ), $post_id );
            break;

        case 'contact':
            update_field( 'tel1', sanitize_text_field( $request->get_param( 'tel1' ) ?? '' ), $post_id );
            update_field( 'fax', sanitize_text_field( $request->get_param( 'fax' ) ?? '' ), $post_id );
            $homepage = $request->get_param( 'homepage' ) ?? '';
            if ( $homepage ) {
                $homepage = esc_url_raw( $homepage );
            }
            update_field( 'homepage', $homepage, $post_id );
            break;

        case 'specialities':
            $term_ids = $request->get_param( 'term_ids' );
            if ( ! is_array( $term_ids ) ) {
                $term_ids = [];
            }
            $term_ids   = array_map( 'absint', $term_ids );
            $non_fsa    = array_filter( $term_ids, function ( int $id ): bool {
                $term = get_term( $id, 'speciality' );
                return $term && 110 !== (int) $term->parent;
            } );
            if ( count( $non_fsa ) > 7 ) {
                return new WP_REST_Response( [
                    'success' => false,
                    'message' => "Veuillez sélectionner au maximum 7 domaines d'activité.",
                ], 400 );
            }
            wp_set_object_terms( $post_id, $term_ids, 'speciality', false );
            break;

        case 'languages':
            $term_ids = $request->get_param( 'term_ids' );
            if ( ! is_array( $term_ids ) ) {
                $term_ids = [];
            }
            wp_set_object_terms( $post_id, array_map( 'absint', $term_ids ), 'language', false );
            break;

        case 'password':
            $current  = $request->get_param( 'current_password' ) ?? '';
            $new_pass = $request->get_param( 'new_password' ) ?? '';

            $user = wp_get_current_user();
            if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
                return new WP_REST_Response( [
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect.',
                ], 400 );
            }
            if ( strlen( $new_pass ) < 8 ) {
                return new WP_REST_Response( [
                    'success' => false,
                    'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
                ], 400 );
            }
            wp_set_password( $new_pass, $user->ID );
            wp_set_auth_cookie( $user->ID );
            return new WP_REST_Response( [ 'success' => true, 'message' => 'Mot de passe modifié.' ] );

        default:
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Section inconnue.' ], 400 );
    }

    dc26_sync_member_to_api( $post_id );

    return new WP_REST_Response( [
        'success' => true,
        'data'    => dc26_get_member_data( $post_id ),
    ] );
}

/**
 * Handle photo upload via simple file input.
 */
function dc26_rest_member_photo( WP_REST_Request $request ): WP_REST_Response {
    $member = dc26_get_member_by_user( wp_get_current_user() );
    if ( ! $member ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Aucun profil membre trouvé.' ], 404 );
    }

    $files = $request->get_file_params();
    if ( empty( $files['photo'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Aucun fichier envoyé.' ], 400 );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $file = $files['photo'];
    $allowed = [ 'image/jpeg', 'image/png', 'image/webp' ];
    if ( ! in_array( $file['type'], $allowed, true ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Format non supporté (JPG, PNG ou WebP).' ], 400 );
    }

    $_FILES['photo'] = $file;
    $attachment_id = media_handle_upload( 'photo', $member->ID );

    if ( is_wp_error( $attachment_id ) ) {
        return new WP_REST_Response( [
            'success' => false,
            'message' => $attachment_id->get_error_message(),
        ], 500 );
    }

    update_field( 'img_membre', $attachment_id, $member->ID );

    $img_src = wp_get_attachment_image_url( $attachment_id, 'medium' );

    dc26_sync_member_to_api( $member->ID );

    return new WP_REST_Response( [
        'success'   => true,
        'photo_id'  => $attachment_id,
        'photo_url' => $img_src,
    ] );
}

/**
 * Sync member data to the external OAV API.
 *
 * Reproduces the exact body format from tm21 gravity.php post_to_third_party().
 */
function dc26_sync_member_to_api( int $post_id ): void {
    $d = dc26_get_member_data( $post_id );

    $etude_terms = get_the_terms( $post_id, 'etude' );
    $etude_name  = ( ! empty( $etude_terms ) && ! is_wp_error( $etude_terms ) ) ? $etude_terms[0]->name : '';

    $spec_ids = [];
    $all_specs = get_the_terms( $post_id, 'speciality' );
    if ( ! empty( $all_specs ) && ! is_wp_error( $all_specs ) ) {
        foreach ( $all_specs as $term ) {
            $oav_id = get_field( 'id_specialite_oav', $term );
            if ( $oav_id ) {
                $spec_ids[] = $oav_id;
            }
        }
    }

    $lang_ids = [];
    $all_langs = get_the_terms( $post_id, 'language' );
    if ( ! empty( $all_langs ) && ! is_wp_error( $all_langs ) ) {
        foreach ( $all_langs as $term ) {
            $oav_id = get_field( 'id_langue_oav', $term );
            if ( $oav_id ) {
                $lang_ids[] = $oav_id;
            }
        }
    }

    $domaine_fields = [
        'domaine1' => 'avocat_plafa',
        'domaine2' => 'avocat_enlevement_enfant',
        'domaine3' => 'avocat_procedure_mesure_contrainte',
        'domaine4' => 'permanence_avocats_1ere_heure',
        'domaine5' => 'defenseur_office',
        'domaine6' => 'appels_en_dehors',
        'domaine7' => 'avocat_mpc',
        'domaine8' => 'curatelles',
    ];
    $domaines = [];
    foreach ( $domaine_fields as $key => $acf_field ) {
        $domaines[ $key ] = get_field( $acf_field, $post_id ) ? '1' : '';
    }

    $body = array_merge(
        [
            'prenom'              => $d['first_name'],
            'nom'                 => $d['last_name'],
            'titre'               => $d['titre'],
            'etude'               => $etude_name,
            'rue'                 => $d['rue'],
            'rue_no'              => $d['rue_no'],
            'case_postale'        => $d['case_postale'],
            'complement_adresse'  => $d['complement_adresse'],
            'npa'                 => $d['npa'],
            'localite'            => $d['ville'],
            'email'               => $d['email'],
            'id'                  => dc26_member_scalar( get_field( 'id_oav', $post_id ) ),
            'specialities'        => wp_json_encode( $spec_ids ),
            'languages'           => wp_json_encode( $lang_ids ),
        ],
        $domaines
    );

    wp_remote_post( 'https://app.oav.ch/exchange/request-update-avocat.php', [
        'body'    => $body,
        'timeout' => 15,
    ] );
}
