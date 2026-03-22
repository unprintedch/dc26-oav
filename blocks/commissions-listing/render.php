<?php
/**
 * Commissions listing block template.
 *
 * @param array $block The block settings and attributes.
 */
declare(strict_types=1);

if ( ! function_exists( 'dc26_commissions_normalize_items' ) ) {
    /**
     * Normalize API payloads that can be object or array of objects.
     *
     * @param mixed  $value       Raw value.
     * @param string $single_hint Key hint identifying single item structures.
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
     * Build member permalink map keyed by id_oav.
     *
     * @return array<string, string>
     */
    function dc26_commissions_member_permalink_map(): array {
        $members = get_posts( [
            'post_type'      => 'member',
            'post_status'    => [ 'publish', 'private' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ] );

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
     * Load and cache commission categories from remote API.
     *
     * @return array<int, array<string, mixed>>
     */
    function dc26_get_commissions_categories(): array {
        $transient_key = 'oav_commissions';
        $cached_raw    = get_transient( $transient_key );

        if ( false === $cached_raw ) {
            $token = '';
            if ( function_exists( 'get_field' ) ) {
                // ACF option field priority.
                $token = trim( (string) get_field( 'oav_kena_token', 'option' ) );
                if ( '' === $token ) {
                    // Backward-compatible alternate field name.
                    $token = trim( (string) get_field( 'kena_token', 'option' ) );
                }
            }
            if ( '' === $token && defined( 'DC26_KENA_TOKEN' ) ) {
                $token = trim( (string) DC26_KENA_TOKEN );
            }
            if ( '' === $token ) {
                // Legacy fallback matching tm21 behavior.
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
                return [];
            }

            $body = wp_remote_retrieve_body( $response );
            if ( '' === $body ) {
                return [];
            }

            set_transient( $transient_key, $body, 6 * HOUR_IN_SECONDS );
            $cached_raw = $body;
        }

        $decoded = json_decode( (string) $cached_raw, true );
        if ( ! is_array( $decoded ) || empty( $decoded['categorie'] ) || ! is_array( $decoded['categorie'] ) ) {
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

                // Case A: single commission object.
                if ( isset( $group['nom'] ) ) {
                    $commissions[] = $group;
                    continue;
                }

                // Case B: nested array of commissions.
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

$anchor = '';
if ( ! empty( $block['anchor'] ) ) {
    $anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

$class_name = 'dc26-commissions';
if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
}

if ( is_admin() ) : ?>
    <div class="dc26-commissions__preview">
        <p><?php echo esc_html__( 'Bloc Commissions OAV', 'dc26-oav' ); ?></p>
        <p><?php echo esc_html__( 'La liste des commissions est visible sur le front.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

if ( ! is_user_logged_in() ) : ?>
    <div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
        <p class="dc26-commissions__empty"><?php echo esc_html__( 'Veuillez vous connecter pour consulter les commissions.', 'dc26-oav' ); ?></p>
        <p><a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php echo esc_html__( 'Se connecter', 'dc26-oav' ); ?></a></p>
    </div>
    <?php return;
endif;

$categories     = dc26_get_commissions_categories();
$member_link_map = dc26_commissions_member_permalink_map();
?>

<div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
    <?php if ( empty( $categories ) ) : ?>
        <p class="dc26-commissions__empty"><?php echo esc_html__( 'La liste des commissions est temporairement indisponible', 'dc26-oav' ); ?></p>
        <p class="dc26-commissions__empty"><?php echo esc_html__( 'Nous travaillons actuellement a une resolution.', 'dc26-oav' ); ?></p>
    <?php else : ?>
        <?php foreach ( $categories as $category ) : ?>
            <section class="dc26-commissions__category">
                <h2 class="dc26-commissions__category-title"><?php echo esc_html( $category['name'] ); ?></h2>

                <?php foreach ( $category['commissions'] as $commission ) :
                    $commission_name = trim( (string) ( $commission['nom'] ?? '' ) );
                    if ( '' === $commission_name ) {
                        continue;
                    }

                    $lawyers_raw = $commission['avocats']['avocat'] ?? [];
                    $lawyers     = dc26_commissions_normalize_items( $lawyers_raw, 'id' );

                    $president_index = null;
                    foreach ( $lawyers as $idx => $lawyer ) {
                        if ( '1' === (string) ( $lawyer['est_president_commission'] ?? '' ) ) {
                            $president_index = $idx;
                            break;
                        }
                    }
                    $president = null;
                    if ( null !== $president_index ) {
                        $president = $lawyers[ $president_index ];
                        unset( $lawyers[ $president_index ] );
                    }
                ?>
                    <article class="dc26-commission">
                        <h3 class="dc26-commission__title"><?php echo esc_html( $commission_name ); ?></h3>

                        <?php if ( is_array( $president ) ) :
                            $president_id   = trim( (string) ( $president['id'] ?? '' ) );
                            $president_name = trim( (string) ( ( $president['prenom'] ?? '' ) . ' ' . ( $president['nom'] ?? '' ) ) );
                            $president_info = is_array( $president['info_commission'] ?? null ) ? '' : trim( (string) ( $president['info_commission'] ?? '' ) );
                            $president_url  = $member_link_map[ $president_id ] ?? '';
                            ?>
                            <p class="dc26-commission__president">
                                <?php if ( $president_url ) : ?>
                                    <a href="<?php echo esc_url( $president_url ); ?>"><?php echo esc_html( $president_name ); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html( $president_name ); ?>
                                <?php endif; ?>
                                <?php echo esc_html__( ' - president.e', 'dc26-oav' ); ?>
                                <?php if ( '' !== $president_info ) : ?>
                                    <?php echo ' ' . esc_html( $president_info ); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <?php if ( ! empty( $lawyers ) ) : ?>
                            <details class="dc26-commission__members">
                                <summary><?php echo esc_html__( 'Membres', 'dc26-oav' ); ?></summary>
                                <ul>
                                    <?php foreach ( $lawyers as $lawyer ) :
                                        $lawyer_id   = trim( (string) ( $lawyer['id'] ?? '' ) );
                                        $lawyer_name = trim( (string) ( ( $lawyer['prenom'] ?? '' ) . ' ' . ( $lawyer['nom'] ?? '' ) ) );
                                        $lawyer_info = is_array( $lawyer['info_commission'] ?? null ) ? '' : trim( (string) ( $lawyer['info_commission'] ?? '' ) );
                                        $lawyer_url  = $member_link_map[ $lawyer_id ] ?? '';
                                        ?>
                                        <li>
                                            <?php if ( $lawyer_url ) : ?>
                                                <a href="<?php echo esc_url( $lawyer_url ); ?>"><?php echo esc_html( $lawyer_name ); ?></a>
                                            <?php else : ?>
                                                <?php echo esc_html( $lawyer_name ); ?>
                                            <?php endif; ?>
                                            <?php if ( '' !== $lawyer_info ) : ?>
                                                <?php echo ' ' . esc_html( $lawyer_info ); ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
