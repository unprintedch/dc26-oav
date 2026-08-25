<?php
/**
 * Post Attachments block template.
 * Reads `documents` (CPT documentation), `liens_ou_documents` (posts/articles),
 * or `document_repeater` (events / 5 à 7), whichever has data. Outputs nothing
 * if empty.
 *
 * @param array $block      Block settings and attributes.
 * @param bool  $is_preview True in the block editor preview.
 * @param int   $post_id    Current post ID.
 */

$icon_base = get_stylesheet_directory_uri() . '/assets/img/';

if (have_rows('documents', $post_id)) {
    $field_source = 'documents';
} elseif (have_rows('liens_ou_documents', $post_id)) {
    $field_source = 'liens_ou_documents';
} elseif (have_rows('document_repeater', $post_id)) {
    $field_source = 'document_repeater';
} else {
    if ($is_preview) {
        echo '<div class="dc26-post-attachments--empty"><p>Documents &amp; Liens — aucune donnée sur ce post.</p></div>';
    }
    return;
}

$items = [];
while (have_rows($field_source, $post_id)) : the_row();
    $url   = '';
    $label = '';
    $type  = 'link';

    if ($field_source === 'documents') {
        if (get_row_layout() === 'document') {
            $file  = get_sub_field('document');
            $url   = $file['url'] ?? '';
            $label = $file['title'] ?? ($file['filename'] ?? '');
            $type  = 'document';
        } elseif (get_row_layout() === 'link') {
            $link  = get_sub_field('link');
            $url   = $link['url'] ?? '';
            $label = $link['title'] ?? $url;
        }
    } elseif ($field_source === 'document_repeater') {
        $url   = get_sub_field('document_file') ?: '';
        $label = get_sub_field('intitule') ?: ($url ? basename((string) $url) : '');
        $type  = 'document';
    } elseif ($field_source === 'liens_ou_documents') {
        $texte = get_sub_field('texte') ?: '';
        if (get_row_layout() === 'document') {
            $file  = get_sub_field('lien_document');
            $url   = $file['url'] ?? '';
            $label = $texte ?: ($file['title'] ?? '');
            $type  = 'document';
        } elseif (get_row_layout() === 'lien_interne') {
            $url   = get_sub_field('lien') ?: '';
            $label = $texte ?: $url;
        } elseif (get_row_layout() === 'lien_externe') {
            $url   = get_sub_field('lien') ?: '';
            $label = $texte ?: $url;
        }
    }

    if ($url && $label) {
        $items[] = ['url' => $url, 'label' => $label, 'type' => $type];
    }
endwhile;

if (empty($items)) {
    return;
}
?>
<div class="wp-block-group alignfull has-gray-light-background-color has-background has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
    <div class="wp-block-columns alignwide is-layout-flex wp-block-columns-is-layout-flex" style="margin-top:var(--wp--preset--spacing--60);margin-bottom:0;padding-top:var(--wp--preset--spacing--70);padding-right:0;padding-bottom:var(--wp--preset--spacing--70);padding-left:0">
        <div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:33.33%">
            <div class="wp-block-group has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
                <h2 class="wp-block-heading has-primary-color has-text-color"><?php echo esc_html__('Documents et liens', 'dc26-oav'); ?></h2>
            </div>
        </div>
        <div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:66.66%">
            <ul class="dc26-post-attachments__list">
                <?php foreach ($items as $item) :
                    $icon    = $item['type'] === 'document' ? 'document.svg' : 'link.svg';
                    $is_file = $item['type'] === 'document';
                ?>
                    <li class="dc26-post-attachments__item">
                        <a class="dc26-post-attachments__link dc26-post-attachments__link--<?php echo esc_attr($item['type']); ?>"
                           href="<?php echo esc_url($item['url']); ?>"
                           <?php if ($is_file) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
                            <img src="<?php echo esc_url($icon_base . $icon); ?>" alt="" aria-hidden="true" class="dc26-post-attachments__icon">
                            <span><?php echo esc_html($item['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
