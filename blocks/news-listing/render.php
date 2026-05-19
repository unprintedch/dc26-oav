<?php
/**
 * Posts listing block template.
 *
 * @param array $block      Block settings and attributes.
 * @param bool  $is_preview True in the block editor preview.
 */

$block_id = !empty($block['anchor']) ? $block['anchor'] : $block['id'];

$class_name = 'dc26-news-listing';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

if (!empty($is_preview)) : ?>
    <div class="dc26-news-listing__preview">
        <p><strong><?php esc_html_e('Posts listing', 'dc26-oav'); ?></strong></p>
        <p><?php esc_html_e('Les filtres et la liste sont visibles sur le front.', 'dc26-oav'); ?></p>
    </div>
    <?php return;
endif;

$selected_cats  = get_field('nl_categories') ?: [];
$posts_per_page = (int) (get_field('nl_posts_per_page') ?: 24);
$columns        = (int) (get_field('nl_columns') ?: 3);

$query_args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => $posts_per_page,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'facetwp'        => true,
];

if (!empty($selected_cats)) {
    $query_args['tax_query'] = [[
        'taxonomy' => 'category',
        'field'    => 'term_id',
        'terms'    => array_map('intval', wp_list_pluck($selected_cats, 'term_id')),
    ]];
}
?>

<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr($class_name); ?>">

    <div class="dc26-news-listing__toolbar">
        <?php if (function_exists('facetwp_display')) : ?>
            <?php echo facetwp_display('facet', 'categories_news_pills'); ?>
            <?php echo facetwp_display('facet', 'recherche'); ?>
        <?php endif; ?>
    </div>

    <div class="facetwp-template dc26-news-listing__grid dc26-news-listing__grid--cols-<?php echo esc_attr($columns); ?>">
        <?php
        $news_query = new WP_Query($query_args);

        if ($news_query->have_posts()) :
            while ($news_query->have_posts()) :
                $news_query->the_post();
                $post_id    = get_the_ID();
                $permalink  = get_permalink();
                $title      = get_the_title();
                $date_iso   = get_the_date('c');
                $date_label = get_the_date('j F Y');
                $excerpt    = wp_trim_words(get_the_excerpt(), 16, '&hellip;');

                // Catégories triées par term_id ASC (principale = plus petit ID)
                $cats = get_the_category();
                usort($cats, fn($a, $b) => $a->term_id - $b->term_id);

                // Champs event ACF (affichés si renseignés, cachés sinon)
                $event_date    = '';
                $event_time    = '';
                $event_adresse = '';
                $date_raw      = get_field('date', $post_id, false);
                if ($date_raw) {
                    $date_obj = DateTime::createFromFormat('Ymd', $date_raw);
                    if ($date_obj) {
                        $event_date = date_i18n('j F Y', $date_obj->getTimestamp());
                    }
                }
                $event_time    = get_field('heure', $post_id) ?: '';
                $event_adresse = get_field('adresse', $post_id) ?: '';

                // Icône — catégorie principale Yoast, fallback première cat par term_id
                $icon_url        = '';
                $primary_cat_id  = (int) get_post_meta($post_id, '_yoast_wpseo_primary_category', true);
                $icon_term_id    = $primary_cat_id ?: (!empty($cats) ? $cats[0]->term_id : 0);
                if ($icon_term_id) {
                    $icon_url = get_field('category_icon', 'category_' . $icon_term_id) ?: '';
                }

                // Détection event (pour masquer la date de publication)
                $is_event = false;
                $event_slugs = ['evenements', '5-a-7', 'formation'];
                foreach ($cats as $cat) {
                    if (in_array($cat->slug, $event_slugs, true)) {
                        $is_event = true;
                        break;
                    }
                }
                ?>
                <article class="dc26-news-card">

                    <div class="dc26-news-card__top">

                        <div class="dc26-news-card__event">
                            <div class="dc26-news-card__event-meta">
                                <?php if ($event_date) : ?>
                                    <span class="dc26-news-card__event-date"><?php echo esc_html($event_date); ?></span>
                                <?php endif; ?>
                                <?php if ($event_time) : ?>
                                    <span class="dc26-news-card__event-time"><?php echo esc_html($event_time); ?></span>
                                <?php endif; ?>
                                <?php if ($icon_url) : ?>
                                <div class="dc26-news-card__icon" aria-hidden="true">
                                    <img src="<?php echo esc_url($icon_url); ?>" alt="" width="30" height="30">
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($event_adresse) : ?>
                                <span class="dc26-news-card__event-adresse"><?php echo esc_html($event_adresse); ?></span>
                            <?php endif; ?>
                        </div>
                   


                        <h2 class="dc26-news-card__title"><?php echo esc_html($title); ?></h2>

                        <!-- <?php if ($excerpt) : ?>
                            <p class="dc26-news-card__excerpt"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?> -->

                    </div>

                    <div class="dc26-news-card__bottom">
                        <?php if (!$is_event) : ?>
                        <time class="dc26-news-card__date" datetime="<?php echo esc_attr($date_iso); ?>">
                            <?php echo esc_html($date_label); ?>
                        </time>
                        <?php endif; ?>
                        <div class="dc26-news-card__footer">
                            <?php if (!empty($cats)) : ?>
                                <div class="dc26-news-card__categories">
                                    <?php foreach ($cats as $cat) : ?>
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
                            </a>
                        </div>
                    </div>

                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p class="dc26-news-listing__empty">
                <?php esc_html_e('Aucun résultat.', 'dc26-oav'); ?>
            </p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>

    <?php if (function_exists('facetwp_display')) : ?>
        <?php echo facetwp_display('facet', 'pagination'); ?>
    <?php endif; ?>

</div>
