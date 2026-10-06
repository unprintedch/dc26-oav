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
$show_past_ui   = !get_field('nl_hide_past');

$selected_ids = !empty($selected_cats)
    ? array_map('intval', wp_list_pluck($selected_cats, 'term_id'))
    : [];

$all_ids       = $selected_ids;
$cat_hierarchy = [];

foreach ($selected_ids as $tid) {
    $children = get_term_children($tid, 'category');
    if (!empty($children) && !is_wp_error($children)) {
        $parent_term = get_term($tid, 'category');
        if (!$parent_term || is_wp_error($parent_term)) {
            continue;
        }
        $child_slugs = [];
        foreach ($children as $child_id) {
            $child_term = get_term((int) $child_id, 'category');
            if ($child_term && !is_wp_error($child_term)) {
                $child_slugs[] = $child_term->slug;
                $all_ids[]     = (int) $child_id;
            }
        }
        if (!empty($child_slugs)) {
            $cat_hierarchy[$parent_term->slug] = $child_slugs;
        }
    }
}
$all_ids = array_unique($all_ids);

// Toggle "Événements passés" — lien simple (pas un facet FacetWP), lu depuis
// l'URL sur le chargement initial ET reconstitué par FacetWP sur ses appels
// AJAX (load more / pills) via `extras.uri`.
$show_past_only = $show_past_ui && isset($_GET['vue']) && $_GET['vue'] === 'passes';

$query_args = [
    'post_type'      => ['post', 'documentation'],
    'post_status'    => 'publish',
    'posts_per_page' => $posts_per_page,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'facetwp'        => true,
    // Ordre chronologique calculé dans la requête (voir dc26-facetwp.php).
    'dc26_event_order' => true,
];

// Visiteur non connecté : uniquement le contenu public (exclusion dans la requête).
$hidden_ids = function_exists('dc26_grid_hidden_post_ids') ? dc26_grid_hidden_post_ids($query_args['post_type']) : [];
if ($hidden_ids) {
    $query_args['post__not_in'] = $hidden_ids;
}

if ($show_past_only) {
    $query_args['meta_key']     = 'date';
    $query_args['orderby']      = 'meta_value_num';
    $query_args['order']        = 'DESC';
    $query_args['meta_query']   = [[
        'key'     => 'date',
        'value'   => (int) current_time('Ymd'),
        'compare' => '<',
        'type'    => 'NUMERIC',
    ]];
}

// Pas d'UI "passés" : on les exclut dans la requête (pas après coup), pour que
// pagination et "Charger plus" restent justes.
if (!$show_past_ui) {
    $query_args['meta_query'] = [
        'relation' => 'OR',
        ['key' => 'date', 'compare' => 'NOT EXISTS'],
        ['key' => 'date', 'value' => '', 'compare' => '='],
        ['key' => 'date', 'value' => (int) current_time('Ymd'), 'compare' => '>=', 'type' => 'NUMERIC'],
    ];
}

if (!empty($all_ids)) {
    $query_args['tax_query'] = [[
        'taxonomy'         => 'category',
        'field'            => 'term_id',
        'terms'            => $all_ids,
        'include_children' => false,
    ]];
}

$toggle_url = $show_past_only
    ? remove_query_arg('vue')
    : add_query_arg('vue', 'passes');
?>

<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr($class_name); ?>"
     data-cat-hierarchy="<?php echo esc_attr(wp_json_encode($cat_hierarchy)); ?>">

    <div class="dc26-news-listing__toolbar">
        <?php if (function_exists('facetwp_display')) : ?>
            <?php echo facetwp_display('facet', 'categories_news_pills'); ?>
            <?php echo facetwp_display('facet', 'recherche'); ?>
        <?php endif; ?>
        <?php if ($show_past_ui) : ?>
        <a class="dc26-sub-pill dc26-news-listing__past-toggle<?php echo $show_past_only ? ' is-active' : ''; ?>" href="<?php echo esc_url($toggle_url); ?>">
            <?php echo $show_past_only
                ? esc_html__('← Voir les événements à venir', 'dc26-oav')
                : esc_html__('Voir les événements passés', 'dc26-oav'); ?>
        </a>
        <?php endif; ?>
        <div class="dc26-news-listing__sub-pills" style="display:none"></div>
    </div>

    <?php
    // L'ordre vient de la requête (dc26_event_order) : à venir du plus proche au
    // plus lointain, puis événements passés du plus récent au plus ancien. Ici on
    // ne fait que répartir la page en deux sections, sans retrier.
    $today_ymd = (int) current_time('Ymd');
    $upcoming  = [];
    $past      = [];

    $news_query   = new WP_Query($query_args);
    $current_page = (int) ($news_query->query_vars['paged'] ?? 1) ?: 1;

    if ($news_query->have_posts()) :
        while ($news_query->have_posts()) :
            $news_query->the_post();
            $pid      = get_the_ID();
            $date_raw = get_field('date', $pid, false);
            $is_past  = !empty($date_raw) && (int) $date_raw < $today_ymd;

            if ($is_past) {
                $past[] = ['post_id' => $pid];
            } else {
                $upcoming[] = ['post_id' => $pid];
            }
        endwhile;
        wp_reset_postdata();
    endif;

    // Le titre "Événements passés" s'affiche au premier passé de toute la liste :
    // sur cette page s'il suit des "à venir", ou si l'élément précédent (fin de la
    // page d'avant) était encore "à venir".
    $show_past_heading = false;
    if (!empty($past)) {
        if (!empty($upcoming) || 1 === $current_page) {
            $show_past_heading = true;
        } else {
            $prev_vars = $news_query->query_vars;
            unset($prev_vars['paged']);
            $prev_vars['offset']         = ($current_page - 1) * $posts_per_page - 1;
            $prev_vars['posts_per_page'] = 1;
            $prev_vars['fields']         = 'ids';
            $prev_vars['no_found_rows']  = true;
            $prev_ids = (new WP_Query($prev_vars))->posts;
            if (!empty($prev_ids)) {
                $prev_raw = get_field('date', (int) $prev_ids[0], false);
                $show_past_heading = empty($prev_raw) || (int) $prev_raw >= $today_ymd;
            }
        }
    }

    $render_card = function ($post_id, $is_past = false) {
                $permalink  = get_permalink($post_id);
                $title      = get_the_title($post_id);
                $date_iso   = get_the_date('c', $post_id);
                $date_label = get_the_date('j F Y', $post_id);
                $excerpt    = wp_trim_words(get_the_excerpt($post_id), 16, '&hellip;');

                // Catégories triées par term_id ASC (principale = plus petit ID)
                $cats = get_the_category($post_id);
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
                $surtitre      = trim((string) get_field('surtitre', $post_id));

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
                <article class="dc26-news-card<?php echo $is_past ? ' dc26-news-card--past' : ''; ?>">

                    <div class="dc26-news-card__top">

                        <?php if ($event_date || $event_time || $event_adresse) : ?>
                        <div class="dc26-news-card__event">
                            <div class="dc26-news-card__event-meta">
                                <?php if ($event_date) : ?>
                                    <span class="dc26-news-card__event-date"><?php echo esc_html($event_date); ?></span>
                                <?php endif; ?>
                                <?php if ($event_time) : ?>
                                    <span class="dc26-news-card__event-time"><?php echo esc_html($event_time); ?></span>
                                <?php endif; ?>
                                <?php if ($event_date) : ?>
                                <div class="dc26-news-card__icon" aria-hidden="true">
                                    <?php if ($icon_url) : ?>
                                        <img src="<?php echo esc_url($icon_url); ?>" alt="" width="30" height="30">
                                    <?php else : ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="30" height="30" fill="currentColor">
                                            <path d="M208 64C216.8 64 224 71.2 224 80L224 128L416 128L416 80C416 71.2 423.2 64 432 64C440.8 64 448 71.2 448 80L448 128L480 128C515.3 128 544 156.7 544 192L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 192C96 156.7 124.7 128 160 128L192 128L192 80C192 71.2 199.2 64 208 64zM480 160L160 160C142.3 160 128 174.3 128 192L128 224L512 224L512 192C512 174.3 497.7 160 480 160zM512 256L128 256L128 480C128 497.7 142.3 512 160 512L480 512C497.7 512 512 497.7 512 480L512 256z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($event_adresse) : ?>
                                <span class="dc26-news-card__event-adresse"><?php echo esc_html($event_adresse); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($surtitre) : ?>
                        <div class="dc26-news-card__event">
                            <div class="dc26-news-card__event-meta">
                                <span class="dc26-news-card__event-date"><?php echo esc_html($surtitre); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

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
                <?php
    };
    ?>

    <div class="facetwp-template dc26-news-listing__results dc26-news-listing__grid dc26-news-listing__grid--cols-<?php echo esc_attr($columns); ?>">
        <?php foreach ($upcoming as $item) : $render_card($item['post_id']); endforeach; ?>

        <?php if (!empty($past)) : ?>
            <?php if ($show_past_heading) : ?>
                <h2 class="dc26-news-listing__section-title"><?php esc_html_e('Événements passés', 'dc26-oav'); ?></h2>
            <?php endif; ?>
            <?php foreach ($past as $item) : $render_card($item['post_id'], true); endforeach; ?>
        <?php endif; ?>

        <?php if (empty($upcoming) && empty($past)) : ?>
            <p class="dc26-news-listing__empty">
                <?php esc_html_e('Aucun résultat.', 'dc26-oav'); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (function_exists('facetwp_display')) : ?>
        <?php echo facetwp_display('facet', 'pagination'); ?>
    <?php endif; ?>

</div>
