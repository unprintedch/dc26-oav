<?php
/**
 * Add to Calendar block template.
 * Reads `date` (date_picker, raw Ymd) + optional `heure` and `adresse` ACF fields.
 * Outputs nothing if no date is set.
 *
 * @param array $block      Block settings and attributes.
 * @param bool  $is_preview True in the block editor preview.
 * @param int   $post_id    Current post ID.
 */

$date_raw = get_field('date', $post_id, false); // raw Ymd storage format
$heure    = get_field('heure', $post_id) ?: '';
$adresse  = get_field('adresse', $post_id) ?: '';
$titre    = get_the_title($post_id);

if (!$date_raw) {
    if ($is_preview) {
        echo '<div class="dc26-atc dc26-atc--empty"><p>Aucune date définie sur ce post.</p></div>';
    }
    return;
}

$date_obj     = DateTime::createFromFormat('Ymd', $date_raw);
$date_display = date_i18n('l j F Y', $date_obj->getTimestamp());

// iCal download URL — fonctionne universellement (Google/Outlook/Apple
// importent tous un .ics), contrairement aux deep-links Google/Outlook Web
// retirés ici : outlook.live.com/calendar/deeplink ne marche que pour les
// comptes outlook.com/live personnels (pas les comptes pro O365) et son
// endpoint est régulièrement indisponible (503 constaté en prod).
$ics_url = add_query_arg(['dc26-ics' => $post_id], home_url('/'));

$block_id   = !empty($block['anchor']) ? $block['anchor'] : $block['id'];
$class_name = 'dc26-atc';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}
?>
<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr($class_name); ?>">
    <p class="dc26-atc__date">
        <svg class="dc26-atc__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <?php echo esc_html(ucfirst($date_display)); ?>
        <?php if ($heure) : ?><span class="dc26-atc__sep">·</span><?php echo esc_html($heure); ?><?php endif; ?>
        <?php if ($adresse) : ?><span class="dc26-atc__sep">·</span><?php echo esc_html($adresse); ?><?php endif; ?>
    </p>
    <a class="dc26-atc__cta" href="<?php echo esc_url($ics_url); ?>"><?php echo esc_html__('Ajouter à mon calendrier', 'dc26-oav'); ?></a>
</div>
