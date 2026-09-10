<?php
declare(strict_types=1);

/**
 * Gravity Forms — cours de Pilates (form 256).
 *
 * Le champ dropdown "Date de la séance" (field 23) liste des séances en
 * texte libre (Gravity Perks Inventory gère déjà l'épuisement des places).
 * Comme ce ne sont pas des choix générés dynamiquement, une séance passée
 * resterait visible et sélectionnable indéfiniment sans ce filtre.
 *
 * @package dc26-oav
 */

/**
 * Parse a "11 septembre 2026" French label into a DateTime at midnight.
 * Returns null when the label doesn't match the expected format.
 */
function dc26_pilates_parse_date_choice( string $label ): ?DateTime {
    static $months = [
        'janvier'   => 1,
        'février'   => 2,
        'fevrier'   => 2,
        'mars'      => 3,
        'avril'     => 4,
        'mai'       => 5,
        'juin'      => 6,
        'juillet'   => 7,
        'août'      => 8,
        'aout'      => 8,
        'septembre' => 9,
        'octobre'   => 10,
        'novembre'  => 11,
        'décembre'  => 12,
        'decembre'  => 12,
    ];

    if ( ! preg_match( '/^(\d{1,2})\s+(\p{L}+)\s+(\d{4})$/u', trim( $label ), $matches ) ) {
        return null;
    }

    $month = $months[ mb_strtolower( $matches[2] ) ] ?? null;
    if ( ! $month ) {
        return null;
    }

    $date = DateTime::createFromFormat( 'Y-n-j H:i:s', "{$matches[3]}-{$month}-{$matches[1]} 00:00:00" );

    return $date ?: null;
}

/**
 * Drop past-date choices from the Pilates session dropdown (field 23).
 *
 * @param array $form
 * @return array
 */
function dc26_pilates_hide_past_dates( $form ) {
    $today = new DateTime( current_time( 'Y-m-d' ) . ' 00:00:00' );

    foreach ( $form['fields'] as &$field ) {
        if ( (int) $field->id !== 23 || empty( $field->choices ) ) {
            continue;
        }

        $field->choices = array_values( array_filter(
            $field->choices,
            static function ( $choice ) use ( $today ) {
                $date = dc26_pilates_parse_date_choice( $choice['text'] );
                return ! $date || $date >= $today;
            }
        ) );
    }
    unset( $field );

    return $form;
}
add_filter( 'gform_pre_render_256', 'dc26_pilates_hide_past_dates' );
add_filter( 'gform_pre_validation_256', 'dc26_pilates_hide_past_dates' );
add_filter( 'gform_admin_pre_render_256', 'dc26_pilates_hide_past_dates' );
add_filter( 'gform_pre_submission_filter_256', 'dc26_pilates_hide_past_dates' );
