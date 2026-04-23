<?php
/**
 * Toggle Panel block template.
 *
 * @param array $block The block settings and attributes.
 */

$is_admin = is_admin();

$labels = get_field('labels') ?: array();
$label_left = !empty($labels['label_left']) ? $labels['label_left'] : __('Un particulier', 'dc26-oav');
$label_right = !empty($labels['label_right']) ? $labels['label_right'] : __('Une avocat·e', 'dc26-oav');
$default_side = !empty($labels['default_side']) ? $labels['default_side'] : 'left';
$remember_choice = !empty($labels['remember_choice']);
$layout_variant = !empty($labels['layout_variant']) ? $labels['layout_variant'] : 'cards';
$member_link = !empty($labels['member_link']) ? $labels['member_link'] : null;
$custom_anchor = !empty($labels['anchor']) ? $labels['anchor'] : '';

$left_items = get_field('left_items') ?: array();
$right_items = get_field('right_items') ?: array();

$anchor_value = '';
if (!empty($block['anchor'])) {
    $anchor_value = $block['anchor'];
} elseif (!empty($custom_anchor)) {
    $anchor_value = $custom_anchor;
}
$anchor_attribute = $anchor_value ? 'id="' . esc_attr($anchor_value) . '"' : '';

$class_name = 'dc26-toggle-panel';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}
if (!empty($layout_variant)) {
    $class_name .= ' is-variant-' . sanitize_html_class($layout_variant);
}
if ($is_admin) {
    $class_name .= ' is-editor';
}

$default_side = $default_side === 'right' ? 'right' : 'left';
$block_id = !empty($block['id']) ? $block['id'] : uniqid('dc26-toggle-panel-', true);
$left_button_id = $block_id . '-toggle-left';
$right_button_id = $block_id . '-toggle-right';
$left_panel_id = $block_id . '-panel-left';
$right_panel_id = $block_id . '-panel-right';
$storage_key = 'dc26-toggle-panel-' . $block_id;
?>

<div <?php echo esc_attr($anchor_attribute); ?>
    class="<?php echo esc_attr($class_name); ?>"
    data-default-side="<?php echo esc_attr($default_side); ?>"
    data-remember-choice="<?php echo esc_attr($remember_choice ? '1' : '0'); ?>"
    data-storage-key="<?php echo esc_attr($storage_key); ?>">
    <div class="dc26-toggle-panel__controls">
        <div class="dc26-toggle-panel__switch" role="group" aria-label="<?php echo esc_attr__('Toggle panels', 'dc26-oav'); ?>">
            <button
                type="button"
                id="<?php echo esc_attr($left_button_id); ?>"
                class="dc26-toggle-panel__button<?php echo $default_side === 'left' ? ' is-active' : ''; ?>"
                data-side="left"
                aria-pressed="<?php echo $default_side === 'left' ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($left_panel_id); ?>">
                <?php echo esc_html($label_left); ?>
            </button>
            <button
                type="button"
                id="<?php echo esc_attr($right_button_id); ?>"
                class="dc26-toggle-panel__button<?php echo $default_side === 'right' ? ' is-active' : ''; ?>"
                data-side="right"
                aria-pressed="<?php echo $default_side === 'right' ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($right_panel_id); ?>">
                <?php echo esc_html($label_right); ?>
            </button>
        </div>
        <?php if (!empty($member_link) && !empty($member_link['url'])) : ?>
            <?php
            $member_link_url = $member_link['url'];
            $member_link_title = !empty($member_link['title']) ? $member_link['title'] : '';
            $member_link_target = !empty($member_link['target']) ? $member_link['target'] : '_self';
            ?>
            <div class="dc26-toggle-panel__member-link">
                <a class="dc26-toggle-panel__link" href="<?php echo esc_url($member_link_url); ?>" target="<?php echo esc_attr($member_link_target); ?>">
                    <?php echo esc_html($member_link_title); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="dc26-toggle-panel__panels">
        <section
            id="<?php echo esc_attr($left_panel_id); ?>"
            class="dc26-toggle-panel__panel<?php echo $default_side === 'left' ? ' is-active' : ''; ?>"
            data-side="left"
            role="region"
            aria-labelledby="<?php echo esc_attr($left_button_id); ?>">
            <?php if ($is_admin) : ?>
                <div class="dc26-toggle-panel__panel-label"><?php echo esc_html__('Panneau A', 'dc26-oav'); ?></div>
            <?php endif; ?>
            <?php if (!empty($left_items)) : ?>
                <div class="dc26-toggle-panel__items">
                    <?php foreach ($left_items as $item) : ?>
                        <div class="dc26-toggle-panel__item">
                            <?php
                            $icon_id = !empty($item['icon']) ? $item['icon'] : 0;
                            if ($icon_id) {
                                echo wp_get_attachment_image(
                                    $icon_id,
                                    'thumbnail',
                                    false,
                                    array('class' => 'dc26-toggle-panel__icon')
                                );
                            }
                            ?>
                            <?php if (!empty($item['title'])) : ?>
                                <h3 class="dc26-toggle-panel__title"><?php echo esc_html($item['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['text'])) : ?>
                                <p class="dc26-toggle-panel__text"><?php echo esc_html($item['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['links'])) : ?>
                                <div class="dc26-toggle-panel__links">
                                    <?php foreach ($item['links'] as $link_row) : ?>
                                        <?php
                                        $link = !empty($link_row['link']) ? $link_row['link'] : null;
                                        if (!$link) {
                                            continue;
                                        }
                                        $link_url = !empty($link['url']) ? $link['url'] : '';
                                        $link_title = !empty($link['title']) ? $link['title'] : '';
                                        $link_target = !empty($link['target']) ? $link['target'] : '_self';
                                        ?>
                                        <?php if ($link_url && $link_title) : ?>
                                            <a class="dc26-toggle-panel__link" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>">
                                                <?php echo esc_html($link_title); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section
            id="<?php echo esc_attr($right_panel_id); ?>"
            class="dc26-toggle-panel__panel<?php echo $default_side === 'right' ? ' is-active' : ''; ?>"
            data-side="right"
            role="region"
            aria-labelledby="<?php echo esc_attr($right_button_id); ?>">
            <?php if ($is_admin) : ?>
                <div class="dc26-toggle-panel__panel-label"><?php echo esc_html__('Panneau B', 'dc26-oav'); ?></div>
            <?php endif; ?>
            <?php if (!empty($right_items)) : ?>
                <div class="dc26-toggle-panel__items">
                    <?php foreach ($right_items as $item) : ?>
                        <div class="dc26-toggle-panel__item">
                            <?php
                            $icon_id = !empty($item['icon']) ? $item['icon'] : 0;
                            if ($icon_id) {
                                echo wp_get_attachment_image(
                                    $icon_id,
                                    'thumbnail',
                                    false,
                                    array('class' => 'dc26-toggle-panel__icon')
                                );
                            }
                            ?>
                            <?php if (!empty($item['title'])) : ?>
                                <h3 class="dc26-toggle-panel__title"><?php echo esc_html($item['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['text'])) : ?>
                                <p class="dc26-toggle-panel__text"><?php echo esc_html($item['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['links'])) : ?>
                                <div class="dc26-toggle-panel__links">
                                    <?php foreach ($item['links'] as $link_row) : ?>
                                        <?php
                                        $link = !empty($link_row['link']) ? $link_row['link'] : null;
                                        if (!$link) {
                                            continue;
                                        }
                                        $link_url = !empty($link['url']) ? $link['url'] : '';
                                        $link_title = !empty($link['title']) ? $link['title'] : '';
                                        $link_target = !empty($link['target']) ? $link['target'] : '_self';
                                        ?>
                                        <?php if ($link_url && $link_title) : ?>
                                            <a class="dc26-toggle-panel__link" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>">
                                                <?php echo esc_html($link_title); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
