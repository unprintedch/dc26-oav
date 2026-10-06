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

$query = new WP_Query( array(
    'post_type'      => 'avantage',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
    'no_found_rows'  => true,
) );
?>

<div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
    <?php if ( ! $query->have_posts() ) : ?>
        <p class="dc26-avantages__empty"><?php echo esc_html__( 'Aucun avantage disponible pour le moment.', 'dc26-oav' ); ?></p>
    <?php else : ?>
        <ul class="dc26-avantages__grid">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <li class="dc26-avantage">
                    <a class="dc26-avantage__link" href="<?php echo esc_url( get_permalink() ); ?>">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <span class="dc26-avantage__media"><?php the_post_thumbnail( 'medium' ); ?></span>
                        <?php endif; ?>
                        <span class="dc26-avantage__title"><?php echo esc_html( get_the_title() ); ?></span>
                        <span class="dc26-avantage__button"><?php echo esc_html__( 'En savoir plus', 'dc26-oav' ); ?></span>
                    </a>
                </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    <?php endif; ?>
</div>
