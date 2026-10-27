<?php
/**
 * Avantages listing block template.
 *
 * @param array $block The block settings and attributes.
 */
declare(strict_types=1);

$anchor = '';
if ( ! empty( $block['anchor'] ) ) {
    $anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

$class_name = 'dc26-avantages';
if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
}

if ( ! empty( $is_preview ) || ( ! isset( $is_preview ) && is_admin() ) ) : ?>
    <div class="dc26-avantages__preview">
        <p><?php echo esc_html__( 'Bloc Avantages membres', 'dc26-oav' ); ?></p>
        <p><?php echo esc_html__( 'La grille des avantages est visible sur le front.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

if ( ! function_exists( 'dc26_avantages_query' ) ) {
    function dc26_avantages_query( array $tax_query ): WP_Query {
        return new WP_Query( array(
            'post_type'      => 'avantage',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
            'no_found_rows'  => true,
            'tax_query'      => $tax_query,
        ) );
    }
}

$groups = array();
$terms  = get_terms( array( 'taxonomy' => 'avantage-categorie', 'hide_empty' => true ) );
if ( is_array( $terms ) ) {
    usort( $terms, static function ( $a, $b ): int {
        $oa = get_term_meta( $a->term_id, 'ordre', true );
        $ob = get_term_meta( $b->term_id, 'ordre', true );
        $oa = '' === $oa ? PHP_INT_MAX : (int) $oa;
        $ob = '' === $ob ? PHP_INT_MAX : (int) $ob;
        return $oa <=> $ob ?: strcasecmp( $a->name, $b->name );
    } );
    foreach ( $terms as $term ) {
        $groups[] = array( 'name' => $term->name, 'query' => dc26_avantages_query( array(
            array( 'taxonomy' => 'avantage-categorie', 'terms' => $term->term_id ),
        ) ) );
    }
}
// Avantages sans catégorie : en fin de liste (ou seule grille si aucune catégorie).
$groups[] = array( 'name' => '', 'query' => dc26_avantages_query( array(
    array( 'taxonomy' => 'avantage-categorie', 'operator' => 'NOT EXISTS' ),
) ) );
$groups = array_filter( $groups, static fn( $g ) => $g['query']->have_posts() );
?>

<div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
    <?php if ( ! $groups ) : ?>
        <p class="dc26-avantages__empty"><?php echo esc_html__( 'Aucun avantage disponible pour le moment.', 'dc26-oav' ); ?></p>
    <?php endif; ?>
    <?php foreach ( $groups as $group ) : $query = $group['query']; ?>
        <?php if ( '' !== $group['name'] && count( $groups ) > 1 ) : ?>
            <h2 class="dc26-avantages__group-title"><?php echo esc_html( $group['name'] ); ?></h2>
        <?php endif; ?>
        <ul class="dc26-avantages__grid">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php
                $external = (string) get_post_meta( get_the_ID(), 'lien_externe', true );
                $href     = $external !== '' ? $external : get_permalink();
                $target   = $external !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
                ?>
                <li class="dc26-avantage">
                    <a class="dc26-avantage__link" href="<?php echo esc_url( $href ); ?>"<?php echo $target; ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <span class="dc26-avantage__media"><?php the_post_thumbnail( 'medium' ); ?></span>
                        <?php endif; ?>
                        <span class="dc26-avantage__title"><?php echo esc_html( get_the_title() ); ?></span>
                        <span class="dc26-avantage__button"><?php echo esc_html__( 'En savoir plus', 'dc26-oav' ); ?></span>
                    </a>
                </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    <?php endforeach; ?>
</div>
