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

$type_filter = get_field('type_filter') ?: '';

$query_args = array(
    'posts_per_page' => -1,
    'post_type' => array('documentation', 'post', 'page'),
    // 'post_type' => array('documentation'),
    'post_status' => 'publish',
    'orderby' => array('date' => 'DESC'),
    'facetwp' => true,
);

if ($type_filter) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'documentation-type',
            'field' => 'term_id',
            'terms' => (int) $type_filter,
        ),
    );
}
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
                <?php if (!$type_filter) : ?>
                    <div class="dc26-doc-listing__types">
                        <?php if (function_exists('facetwp_display')) : ?>
                            <?php echo facetwp_display('facet', 'documentation_type'); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="dc26-doc-listing__dates">
                    <?php if (function_exists('facetwp_display')) : ?>
                        <?php echo facetwp_display('facet', 'date_de_publication'); ?>
                    <?php endif; ?>
                </div>
                <button class="dc26-doc-listing__reset" type="button" onclick="FWP.reset()">
                    <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                    <span><?php echo esc_html__('Annuler les filtres', 'dc26-oav'); ?></span>
                </button>
            </aside>

            <div class="dc26-doc-listing__results">
                <div class="dc26-doc-listing__table">
                    <div class="dc26-doc-row dc26-doc-row--head" role="row">
                        <span role="columnheader"><?php echo esc_html__('Date', 'dc26-oav'); ?></span>
                        <span role="columnheader"><?php echo esc_html__('Titre', 'dc26-oav'); ?></span>
                        <span role="columnheader"><?php echo esc_html__('Catégorie', 'dc26-oav'); ?></span>
                        <span role="columnheader" class="dc26-doc-row__actions-head"><span class="screen-reader-text"><?php echo esc_html__('Documents', 'dc26-oav'); ?></span></span>
                    </div>

                    <div class="facetwp-template dc26-doc-listing__items">
                        <?php
                        /** @var \WP_Query $documentation_query */
                        $documentation_query = new WP_Query($query_args);
                        if ($documentation_query->have_posts()) :
                            $icon_base_path = get_stylesheet_directory_uri() . '/assets/img/';
                            $lock_icon_path = get_stylesheet_directory() . '/assets/icons/SVG/lock-sharp-regular-full.svg';
                            $lock_icon = file_exists($lock_icon_path)
                                ? str_replace('fill: #007582;', 'fill: currentColor;', file_get_contents($lock_icon_path))
                                : '';
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
                                $has_attachments = have_rows('documents', $post_id);
                                $is_private = function_exists('dc26_members_only_is_protected')
                                    && dc26_members_only_is_protected($post_id);
                                ?>
                                <div class="dc26-doc-row<?php echo !$has_attachments ? ' dc26-doc-row--link' : ''; ?>" role="row">
                                    <span class="dc26-doc-row__date" role="cell"><?php echo esc_html($post_date); ?></span>

                                    <span class="dc26-doc-row__title" role="cell">
                                        <a class="dc26-doc-row__link" href="<?php echo esc_url($post_link); ?>">
                                            <?php if ($is_private && $lock_icon) : ?>
                                                <span class="dc26-doc-row__lock" aria-hidden="true" title="<?php echo esc_attr__('Document privé', 'dc26-oav'); ?>"><?php echo $lock_icon; ?></span>
                                                <span class="screen-reader-text"><?php echo esc_html__('Document privé', 'dc26-oav'); ?></span>
                                            <?php endif; ?>
                                            <?php echo esc_html($post_title); ?>
                                        </a>
                                        <?php if ($has_attachments) : ?>
                                            <ul class="dc26-doc-row__files">
                                                <?php while (have_rows('documents', $post_id)) : the_row(); ?>
                                                    <?php if (get_row_layout() === 'link') : ?>
                                                        <?php
                                                        $link = get_sub_field('link');
                                                        $link_title = !empty($link['title']) ? $link['title'] : '';
                                                        $link_url = !empty($link['url']) ? $link['url'] : '';
                                                        ?>
                                                        <?php if ($link_title && $link_url) : ?>
                                                            <li>
                                                                <a class="dc26-doc-row__file" href="<?php echo esc_url($link_url); ?>">
                                                                    <img src="<?php echo esc_url($icon_base_path . 'link.svg'); ?>" alt="">
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
                                                            <li>
                                                                <a class="dc26-doc-row__file" href="<?php echo esc_url($document_url); ?>">
                                                                    <img src="<?php echo esc_url($icon_base_path . 'document.svg'); ?>" alt="">
                                                                    <?php echo esc_html($document_title); ?>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endwhile; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </span>

                                    <span class="dc26-doc-row__category" role="cell">
                                        <?php foreach ($terms as $term) : ?>
                                            <a class="dc26-doc-row__tag" href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                                        <?php endforeach; ?>
                                    </span>

                                    <span class="dc26-doc-row__actions" role="cell">
                                        <?php if (!$has_attachments) : ?>
                                            <a class="dc26-doc-row__more" href="<?php echo esc_url($post_link); ?>" aria-label="<?php echo esc_attr__('Lire la suite', 'dc26-oav'); ?>"></a>
                                        <?php endif; ?>
                                    </span>
                                </div>
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
        </div>
    <?php endif; ?>
</div>
