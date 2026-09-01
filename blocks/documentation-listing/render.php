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
    // Exclut les contenus avec la coche "Exclure de la grille Documentation" (ACF field group_dc26_doc_grid_exclude).
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key'     => 'exclude_from_doc_grid',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => 'exclude_from_doc_grid',
            'value'   => '1',
            'compare' => '!=',
        ),
    ),
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
                                // Documents are stored under different field names depending on
                                // content type: `documents` (CPT documentation), `liens_ou_documents`
                                // (posts/articles) or `document_repeater` (events / 5 à 7) — see
                                // blocks/post-attachments/render.php for the same field-source logic.
                                if (have_rows('documents', $post_id)) {
                                    $attachments_source = 'documents';
                                } elseif (have_rows('liens_ou_documents', $post_id)) {
                                    $attachments_source = 'liens_ou_documents';
                                } elseif (have_rows('document_repeater', $post_id)) {
                                    $attachments_source = 'document_repeater';
                                } else {
                                    $attachments_source = '';
                                }
                                $has_attachments = '' !== $attachments_source;
                                $is_private = function_exists('dc26_members_only_is_protected')
                                    && dc26_members_only_is_protected($post_id);
                                ?>
                                <div class="dc26-doc-row" role="row">
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
                                                <?php while (have_rows($attachments_source, $post_id)) : the_row(); ?>
                                                    <?php if ('documents' === $attachments_source && get_row_layout() === 'link') : ?>
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
                                                    <?php if ('documents' === $attachments_source && get_row_layout() === 'document') : ?>
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
                                                    <?php if ('liens_ou_documents' === $attachments_source) : ?>
                                                        <?php
                                                        $texte = get_sub_field('texte') ?: '';
                                                        $link_layout = get_row_layout();
                                                        if ('document' === $link_layout) {
                                                            $file = get_sub_field('lien_document');
                                                            $file_url = !empty($file['url']) ? $file['url'] : '';
                                                            $file_title = $texte ?: (!empty($file['title']) ? $file['title'] : '');
                                                            $file_icon = 'document.svg';
                                                        } else {
                                                            $file_url = get_sub_field('lien') ?: '';
                                                            $file_title = $texte ?: $file_url;
                                                            $file_icon = 'link.svg';
                                                        }
                                                        ?>
                                                        <?php if ($file_title && $file_url) : ?>
                                                            <li>
                                                                <a class="dc26-doc-row__file" href="<?php echo esc_url($file_url); ?>">
                                                                    <img src="<?php echo esc_url($icon_base_path . $file_icon); ?>" alt="">
                                                                    <?php echo esc_html($file_title); ?>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if ('document_repeater' === $attachments_source) : ?>
                                                        <?php
                                                        $file_url = get_sub_field('document_file') ?: '';
                                                        $file_title = get_sub_field('intitule') ?: ($file_url ? basename((string) $file_url) : '');
                                                        ?>
                                                        <?php if ($file_title && $file_url) : ?>
                                                            <li>
                                                                <a class="dc26-doc-row__file" href="<?php echo esc_url($file_url); ?>">
                                                                    <img src="<?php echo esc_url($icon_base_path . 'document.svg'); ?>" alt="">
                                                                    <?php echo esc_html($file_title); ?>
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

                                    <a class="dc26-doc-row__row-link" href="<?php echo esc_url($post_link); ?>" tabindex="-1" aria-hidden="true"></a>
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
