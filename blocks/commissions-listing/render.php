<?php
/**
 * Commissions listing block template.
 *
 * @param array $block The block settings and attributes.
 */
declare(strict_types=1);

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

if ( ! empty( $is_preview ) || ( ! isset( $is_preview ) && is_admin() ) ) : ?>
    <div class="dc26-commissions__preview">
        <p><?php echo esc_html__( 'Bloc Commissions OAV', 'dc26-oav' ); ?></p>
        <p><?php echo esc_html__( 'La liste des commissions est visible sur le front.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

$categories      = dc26_get_commissions_categories();
$member_link_map = dc26_commissions_member_permalink_map();
?>

<div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
    <?php if ( empty( $categories ) ) : ?>
        <p class="dc26-commissions__empty"><?php echo esc_html__( 'La liste des commissions est temporairement indisponible', 'dc26-oav' ); ?></p>
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

                        <div class="dc26-commission__label">
                            <h3 class="dc26-commission__title"><?php echo esc_html( $commission_name ); ?></h3>
                        </div>

                        <div class="dc26-commission__content">
                            <?php if ( is_array( $president ) ) :
                                $president_id   = trim( (string) ( $president['id'] ?? '' ) );
                                $president_name = trim( (string) ( ( $president['prenom'] ?? '' ) . ' ' . ( $president['nom'] ?? '' ) ) );
                                $president_info = is_array( $president['info_commission'] ?? null ) ? '' : trim( (string) ( $president['info_commission'] ?? '' ) );
                                $president_url  = $member_link_map[ $president_id ] ?? '';
                                $president_genre = trim( (string) ( $president['genre'] ?? '' ) );
                                $president_role  = 'F' === $president_genre ? __( 'Présidente', 'dc26-oav' ) : __( 'Président', 'dc26-oav' );
                                if ( '' !== $president_info ) {
                                    $president_role .= ' ' . $president_info;
                                }
                                ?>
                                <div class="dc26-commission__president-block">
                                    <h3 class="dc26-commission__president-name">
                                            <?php echo esc_html( $president_name ); ?>
                                    </h3>
                                    <h3 class="dc26-commission__president-role"><?php echo esc_html( $president_role ); ?></h3>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $lawyers ) ) : ?>
                                <details class="dc26-commission__members">
                                    <summary class="dc26-commission__members-toggle">
                                        <h3 class="dc26-commission__members-toggle-title"><?php echo esc_html__( 'Membres', 'dc26-oav' ); ?></h3>
                                    </summary>
                                    <ul class="dc26-commission__members-list">
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
                                                    <span class="dc26-commission__member-info"><?php echo esc_html( $lawyer_info ); ?></span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
