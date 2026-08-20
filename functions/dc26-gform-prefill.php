<?php
declare(strict_types=1);

/**
 * Gravity Forms dynamic population — prefill form 256 with the current
 * user's member data (identité + coordonnées).
 *
 * Server-side prefill via gform_field_value_{parameter_name} instead of
 * query string params: works regardless of how the page is reached and
 * never exposes member data in the URL.
 *
 * @package dc26-oav
 */

/**
 * Member data for the current request, loaded once regardless of how many
 * gform_field_value_* filters fire (one per prefilled field).
 *
 * @return array<string, mixed>
 */
function dc26_gform_prefill_member_data(): array {
    static $data = null;

    if ( null !== $data ) {
        return $data;
    }

    if ( ! is_user_logged_in() ) {
        return $data = [];
    }

    $member = dc26_get_member_by_user( wp_get_current_user() );
    $data   = $member ? dc26_get_member_data( $member->ID ) : [];

    return $data;
}

/**
 * Resolve one gform_field_value_{parameter_name} filter against the
 * member data map. Falls back to GF's original $value (empty by default)
 * when logged out or no member profile is linked.
 *
 * @param mixed $value Value GF would otherwise use.
 * @return mixed
 */
function dc26_gform_prefill_field( $value ) {
    static $map = [
        'gform_field_value_prenom' => 'first_name',
        'gform_field_value_nom'    => 'last_name',
        'gform_field_value_email'  => 'email',
        'gform_field_value_tel1'   => 'phone',
    ];

    $data_key = $map[ current_filter() ] ?? null;
    if ( ! $data_key ) {
        return $value;
    }

    $data = dc26_gform_prefill_member_data();

    return $data[ $data_key ] ?? $value;
}

add_filter( 'gform_field_value_prenom', 'dc26_gform_prefill_field' );
add_filter( 'gform_field_value_nom', 'dc26_gform_prefill_field' );
add_filter( 'gform_field_value_email', 'dc26_gform_prefill_field' );
add_filter( 'gform_field_value_tel1', 'dc26_gform_prefill_field' );
