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
$date_ical    = $date_obj->format('Ymd');

// Parse heure (ex: "18h30", "18:30", "18.30") → HHii for iCal
$heure_ical = '';
if ($heure) {
    preg_match('/(\d{1,2})[h:.](\d{2})/i', $heure, $m);
    if (!empty($m)) {
        $heure_ical = sprintf('%02d%02d00', (int) $m[1], (int) $m[2]);
    }
}

// iCal download URL
$ics_url = add_query_arg(['dc26-ics' => $post_id], home_url('/'));

// Google Calendar
$gc_start = $heure_ical ? $date_ical . 'T' . $heure_ical : $date_ical;
$gc_end   = $heure_ical
    ? $date_ical . 'T' . sprintf('%02d%02d00', (int) date('H', strtotime('+2 hours', strtotime($date_obj->format('Y-m-d') . ' ' . $heure))), (int) date('i', strtotime('+2 hours', strtotime($date_obj->format('Y-m-d') . ' ' . $heure))))
    : date('Ymd', strtotime('+1 day', $date_obj->getTimestamp()));
$gc_url = 'https://calendar.google.com/calendar/render?' . http_build_query([
    'action'   => 'TEMPLATE',
    'text'     => $titre,
    'dates'    => $gc_start . '/' . $gc_end,
    'location' => $adresse,
]);

// Outlook Web
$ol_start = $date_obj->format('Y-m-d') . ($heure_ical ? 'T' . substr($heure_ical, 0, 2) . ':' . substr($heure_ical, 2, 2) . ':00' : '');
$ol_url   = 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query([
    'subject'  => $titre,
    'startdt'  => $ol_start,
    'location' => $adresse,
    'path'     => '/calendar/action/compose',
    'rru'      => 'addevent',
]);

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
    <p class="dc26-atc__label"><?php echo esc_html__('Ajouter à mon calendrier', 'dc26-oav'); ?></p>
    <div class="dc26-atc__buttons">
        <a class="dc26-atc__btn" href="<?php echo esc_url($gc_url); ?>" target="_blank" rel="noopener noreferrer">Google Calendar</a>
        <a class="dc26-atc__btn" href="<?php echo esc_url($ol_url); ?>" target="_blank" rel="noopener noreferrer">Outlook</a>
        <a class="dc26-atc__btn" href="<?php echo esc_url($ics_url); ?>">iCal (.ics)</a>
    </div>
</div>
