<?php
/**
 * API commissions OAV (categories-commissions) — cache transient + helpers affichage.
 *
 * @package dc26-oav
 */

declare(strict_types=1);

if ( ! function_exists( 'dc26_get_kena_token' ) ) {
    /**
     * Token Kena pour app.oav.ch — aligné sur tm21 (option ACF `kena_token`).
     *
     * @return string Sanitized token ou chaîne vide.
     */
    function dc26_get_kena_token(): string {
        $token = '';
        if ( function_exists( 'get_field' ) ) {
            $token = trim( (string) get_field( 'kena_token', 'option' ) );
            if ( '' === $token ) {
                $token = trim( (string) get_field( 'oav_kena_token', 'option' ) );
            }
        }
        // Fallback direct options (certains champs ACF / password ne passent pas par get_field en front).
        if ( '' === $token ) {
            $token = trim( (string) get_option( 'options_kena_token', '' ) );
        }
        if ( '' === $token ) {
            $token = trim( (string) get_option( 'options_oav_kena_token', '' ) );
        }
        if ( '' === $token && defined( 'DC26_KENA_TOKEN' ) ) {
            $token = trim( (string) constant( 'DC26_KENA_TOKEN' ) );
        }
        // Ne pas utiliser sanitize_text_field() : il peut tronquer des tokens API.
        return trim( preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $token ) );
    }
}

if ( ! function_exists( 'dc26_commissions_normalize_items' ) ) {
    /**
     * Normalise les payloads API (objet unique ou liste).
     *
     * @param mixed  $value       Valeur brute.
     * @param string $single_hint Clé indiquant un objet unique.
     * @return array<int, array<string, mixed>>
     */
    function dc26_commissions_normalize_items( $value, string $single_hint ): array {
        if ( ! is_array( $value ) || [] === $value ) {
            return [];
        }

        if ( isset( $value[ $single_hint ] ) ) {
            return [ $value ];
        }

        $items = [];
        foreach ( $value as $item ) {
            if ( is_array( $item ) ) {
                $items[] = $item;
            }
        }
        return $items;
    }
}

if ( ! function_exists( 'dc26_commissions_member_permalink_map' ) ) {
    /**
     * Carte id_oav → permalink membre.
     *
     * @return array<string, string>
     */
    function dc26_commissions_member_permalink_map(): array {
        $members = get_posts(
            [
                'post_type'      => 'member',
                'post_status'    => [ 'publish', 'private' ],
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'ASC',
            ]
        );

        if ( empty( $members ) ) {
            return [];
        }

        $map = [];
        foreach ( $members as $member_id ) {
            $id_oav = trim( (string) get_field( 'id_oav', $member_id ) );
            if ( '' === $id_oav ) {
                continue;
            }
            $map[ $id_oav ] = get_permalink( $member_id );
        }
        return $map;
    }
}

if ( ! function_exists( 'dc26_get_commissions_categories' ) ) {
    /**
     * Charge et met en cache les catégories de commissions (API OAV).
     *
     * @return array<int, array<string, mixed>>
     */
    function dc26_get_commissions_categories(): array {
        $transient_key = 'oav_commissions';
        $cached_raw    = get_transient( $transient_key );

        if ( false === $cached_raw ) {
            $token = dc26_get_kena_token();
            if ( '' === $token ) {
                error_log( '[dc26-oav] Kena token manquant: option ACF `kena_token` (ou `oav_kena_token` / constante DC26_KENA_TOKEN).' );
                return [];
            }

            $response = wp_remote_post(
                'https://app.oav.ch/api/v2/categories-commissions',
                [
                    'timeout' => 20,
                    'headers' => [
                        'Kena-token' => $token,
                    ],
                ]
            );

            if ( is_wp_error( $response ) ) {
                error_log( '[dc26-oav] API commissions: ' . $response->get_error_message() );
                return [];
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            if ( $code < 200 || $code >= 300 ) {
                error_log( '[dc26-oav] API commissions HTTP ' . (string) $code . ' — ' . substr( $body, 0, 500 ) );
                return [];
            }
            if ( '' === $body ) {
                return [];
            }

            set_transient( $transient_key, $body, 6 * HOUR_IN_SECONDS );
            $cached_raw = $body;
        }

        $decoded = json_decode( (string) $cached_raw, true );
        if ( ! is_array( $decoded ) ) {
            delete_transient( $transient_key );
            error_log( '[dc26-oav] commissions JSON invalide (transient effacé): ' . substr( (string) $cached_raw, 0, 500 ) );
            return [];
        }

        if ( ! isset( $decoded['categorie'] ) || ! is_array( $decoded['categorie'] ) ) {
            delete_transient( $transient_key );
            error_log( '[dc26-oav] commissions: cle `categorie` absente ou invalide — ' . substr( (string) $cached_raw, 0, 500 ) );
            return [];
        }

        $categories = $decoded['categorie'];
        if ( isset( $categories['nom'] ) ) {
            $categories = [ $categories ];
        }

        $normalized = [];
        foreach ( $categories as $category ) {
            if ( ! is_array( $category ) ) {
                continue;
            }
            $name = trim( (string) ( $category['nom'] ?? '' ) );
            if ( '' === $name ) {
                continue;
            }

            $commissions_raw = $category['commissions'] ?? [];
            $groups          = is_array( $commissions_raw ) ? $commissions_raw : [];
            $commissions     = [];

            foreach ( $groups as $group ) {
                if ( ! is_array( $group ) ) {
                    continue;
                }

                if ( isset( $group['nom'] ) ) {
                    $commissions[] = $group;
                    continue;
                }

                foreach ( $group as $item ) {
                    if ( is_array( $item ) && isset( $item['nom'] ) ) {
                        $commissions[] = $item;
                    }
                }
            }

            $normalized[] = [
                'name'        => $name,
                'commissions' => $commissions,
            ];
        }

        return $normalized;
    }
}
