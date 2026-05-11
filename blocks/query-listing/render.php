<?php
/**
 * Query Listing block template.
 *
 * @param array  $block      Block settings and attributes.
 * @param string $content    Inner HTML.
 * @param bool   $is_preview True in the block editor preview.
 * @param int    $post_id    Current post ID.
 */
declare(strict_types=1);

$block_id = !empty($block['anchor']) ? $block['anchor'] : $block['id'];

$class_name = 'dc26-query-listing';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

if (!empty($is_preview)) : ?>
    <div class="dc26-query-listing__preview">
        <p><strong><?php esc_html_e('Query Listing', 'dc26-oav'); ?></strong></p>
        <p><?php esc_html_e('La liste est visible sur le front.', 'dc26-oav'); ?></p>
    </div>
    <?php return;
endif;

$post_type      = get_field('post_type') ?: 'post';
$posts_per_page = (int) (get_field('posts_per_page') ?: 12);
$orderby        = get_field('orderby') ?: 'date';
$order          = get_field('order') ?: 'DESC';

$allowed_orderby = ['date', 'title', 'menu_order', 'meta_value', 'rand'];
if (!in_array($orderby, $allowed_orderby, true)) {
    $orderby = 'date';
}

$query = new WP_Query([
    'post_type'      => sanitize_key($post_type),
    'posts_per_page' => $posts_per_page,
    'orderby'        => $orderby,
    'order'          => in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC',
    'post_status'    => 'publish',
]);
?>

<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr($class_name); ?>">
    <ul class="dc26-query-listing__grid facetwp-template columns-3">
        <?php if ($query->have_posts()) : ?>
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                $permalink  = get_permalink();
                $title      = get_the_title();
                $date_iso   = get_the_date('c');
                $date_label = get_the_date('j F Y');
                $excerpt    = get_the_excerpt();
                $categories = get_the_category();
                ?>
                <li class="dc26-query-listing__item">
                    <div class="dc26-news-card">
                        <div class="dc26-news-card__top">
                            <h2 class="dc26-news-card__title">
                                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                            </h2>
                            <?php if ($excerpt) : ?>
                                <p class="dc26-news-card__excerpt"><?php echo esc_html($excerpt); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="dc26-news-card__bottom">
                            <time class="dc26-news-card__date" datetime="<?php echo esc_attr($date_iso); ?>">
                                <?php echo esc_html($date_label); ?>
                            </time>
                            <div class="dc26-news-card__footer">
                                <?php if (!empty($categories)) : ?>
                                    <div class="dc26-news-card__categories">
                                        <?php foreach ($categories as $cat) : ?>
                                            <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
                                               class="dc26-news-card__cat-pill">
                                                <?php echo esc_html($cat->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($permalink); ?>"
                                   class="dc26-news-card__readmore"
                                   aria-label="<?php echo esc_attr(sprintf(__('Lire : %s', 'dc26-oav'), $title)); ?>">
                                    &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
            <?php endwhile; ?>
        <?php else : ?>
            <li class="dc26-query-listing__empty">
                <p><?php esc_html_e('Aucun contenu trouvé.', 'dc26-oav'); ?></p>
            </li>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </ul>
</div>
