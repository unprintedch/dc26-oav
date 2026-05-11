<?php
/**
 * Documentation listing block template.
 *
 * @param array $block The block settings and attributes.
 */

$anchor = '';
if (!empty($block['anchor'])) {
    $anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}

$class_name = 'dc26-doc-listing';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$is_admin = is_admin();

$query_args = array(
    'posts_per_page' => -1,
    'post_type' => array('documentation', 'post', 'page'),
    // 'post_type' => array('documentation'),
    'post_status' => array('publish', 'private'),
    'orderby' => array('date' => 'DESC'),
    'facetwp' => true,
);
?>

<div <?php echo esc_attr($anchor); ?> class="<?php echo esc_attr($class_name); ?>">
    <?php if ($is_admin) : ?>
        <div class="dc26-doc-listing__preview">
            <p><?php echo esc_html__('Bloc Documentation listing', 'dc26-oav'); ?></p>
            <p><?php echo esc_html__('Les filtres et la liste seront visibles sur le front.', 'dc26-oav'); ?></p>
        </div>
    <?php else : ?>
        <div class="dc26-doc-listing__layout">
            <aside class="dc26-doc-listing__filters" aria-label="<?php echo esc_attr__('Filtres documentation', 'dc26-oav'); ?>">
                <div class="dc26-doc-listing__search">
                    <?php if (function_exists('facetwp_display')) : ?>
                        <?php echo facetwp_display('facet', 'recherche'); ?>
                    <?php endif; ?>
                </div>
                <div class="dc26-doc-listing__types">
                    <?php if (function_exists('facetwp_display')) : ?>
                        <?php echo facetwp_display('facet', 'documentation_type'); ?>
                    <?php endif; ?>
                    <button class="dc26-doc-listing__reset" type="button" onclick="FWP.reset()">
                        <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                        <span><?php echo esc_html__('Annuler les filtres', 'dc26-oav'); ?></span>
                    </button>
                </div>
            </aside>

            <div class="dc26-doc-listing__results">
                <div class="facetwp-template dc26-doc-listing__items">
                    <?php
                    /** @var \WP_Query $documentation_query */
                    $documentation_query = new WP_Query($query_args);
                    if ($documentation_query->have_posts()) :
                        $icon_base_path = get_stylesheet_directory_uri() . '/assets/img/';
                        while ($documentation_query->have_posts()) :
                            $documentation_query->the_post();
                            $post_id = get_the_ID();
                            $post_title = get_the_title($post_id);
                            $post_link = get_permalink($post_id);
                            ?>
                            <?php
                            $taxonomies = ['category', 'post_tag', 'documentation-type'];
                            $terms      = [];
                            foreach ($taxonomies as $tax) {
                                $t = get_the_terms($post_id, $tax);
                                if ($t && !is_wp_error($t)) {
                                    $terms = array_merge($terms, $t);
                                }
                            }
                            $post_date = get_the_date('j F Y', $post_id);
                            ?>
                            <article class="dc26-doc-item<?php echo !have_rows('documents', $post_id) ? ' dc26-doc-item--link' : ''; ?>">
                                <?php if (!empty($terms) || $post_date) : ?>
                                <div class="dc26-doc-item__meta">
                                    <?php foreach ($terms as $term) : ?>
                                        <a class="dc26-doc-item__tag" href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                                    <?php endforeach; ?>
                                    <?php if ($post_date) : ?>
                                        <span class="dc26-doc-item__date"><?php echo esc_html($post_date); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <h3 class="dc26-doc-item__title">
                                    <a class="dc26-doc-item__link" href="<?php echo esc_url($post_link); ?>">
                                        <?php echo esc_html($post_title); ?>
                                    </a>
                                </h3>
                                <?php if (have_rows('documents', $post_id)) : ?>
                                    <div class="dc26-doc-item__attachments">
                                        <p class="dc26-doc-item__label"><?php echo esc_html__('Documents et liens', 'dc26-oav'); ?></p>
                                        <ul class="dc26-doc-item__list">
                                            <?php while (have_rows('documents', $post_id)) : the_row(); ?>
                                                <?php if (get_row_layout() === 'link') : ?>
                                                    <?php
                                                    $link = get_sub_field('link');
                                                    $link_title = !empty($link['title']) ? $link['title'] : '';
                                                    $link_url = !empty($link['url']) ? $link['url'] : '';
                                                    ?>
                                                    <?php if ($link_title && $link_url) : ?>
                                                        <li class="dc26-doc-item__list-item">
                                                            <a class="dc26-doc-item__resource" href="<?php echo esc_url($link_url); ?>">
                                                                <img src="<?php echo esc_url($icon_base_path . 'link.svg'); ?>" alt="" aria-hidden="true">
                                                                <?php echo esc_html($link_title); ?>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if (get_row_layout() === 'document') : ?>
                                                    <?php
                                                    $document = get_sub_field('document');
                                                    $document_title = !empty($document['title']) ? $document['title'] : '';
                                                    $document_url = !empty($document['url']) ? $document['url'] : '';
                                                    ?>
                                                    <?php if ($document_title && $document_url) : ?>
                                                        <li class="dc26-doc-item__list-item">
                                                            <a class="dc26-doc-item__resource" href="<?php echo esc_url($document_url); ?>">
                                                                <img src="<?php echo esc_url($icon_base_path . 'document.svg'); ?>" alt="" aria-hidden="true">
                                                                <?php echo esc_html($document_title); ?>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>
                                <?php else : ?>
                                    <a class="dc26-doc-item__more" href="<?php echo esc_url($post_link); ?>">
                                        <?php echo esc_html__('Lire la suite', 'dc26-oav'); ?>
                                    </a>
                                <?php endif; ?>
                            </article>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p class="dc26-doc-listing__empty">
                            <?php echo esc_html__('Aucun résultat.', 'dc26-oav'); ?>
                        </p>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
