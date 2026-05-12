<?php
declare(strict_types=1);

add_action('init', function (): void {

    register_block_style('core/list', [
        'name'  => 'check',
        'label' => __('Liste avec coches', 'dc26-oav'),
    ]);

    register_block_style('core/heading', [
        'name'  => 'badge-title',
        'label' => __('Badge titre', 'dc26-oav'),
    ]);

    register_block_style('core/details', [
        'name'  => 'big-details',
        'label' => __('Grand accordéon', 'dc26-oav'),
    ]);
});

// Classe body pour les posts événements (masque la date de publication côté CSS)
add_filter('body_class', function (array $classes): array {
    if (!is_singular('post')) {
        return $classes;
    }
    $event_slugs = ['evenements', '5-a-7', 'formation'];
    $cats = get_the_category();
    foreach ($cats as $cat) {
        if (in_array($cat->slug, $event_slugs, true)) {
            $classes[] = 'is-event-post';
            break;
        }
    }
    return $classes;
});

// Endpoint .ics — ?dc26-ics=POST_ID
add_action('template_redirect', function (): void {
    $post_id = filter_input(INPUT_GET, 'dc26-ics', FILTER_VALIDATE_INT);
    if (!$post_id) {
        return;
    }

    $post = get_post($post_id);
    if (!$post || !is_user_logged_in() && $post->post_status !== 'publish') {
        wp_die('Événement introuvable.', 404);
    }

    $date_raw = get_field('date', $post_id, false); // Ymd
    if (!$date_raw) {
        wp_die('Aucune date définie.', 404);
    }

    $titre   = get_the_title($post_id);
    $heure   = get_field('heure', $post_id) ?: '';
    $adresse = get_field('adresse', $post_id) ?: '';
    $url     = get_permalink($post_id);

    $date_obj = DateTime::createFromFormat('Ymd', $date_raw, new DateTimeZone('Europe/Zurich'));

    // Parse heure
    $has_time = false;
    if ($heure && preg_match('/(\d{1,2})[h:.](\d{2})/i', $heure, $m)) {
        $date_obj->setTime((int) $m[1], (int) $m[2]);
        $has_time = true;
    }

    if ($has_time) {
        $dtstart = $date_obj->format('Ymd\THis');
        $end_obj = clone $date_obj;
        $end_obj->modify('+2 hours');
        $dtend   = $end_obj->format('Ymd\THis');
        $tzid    = 'TZID=Europe/Zurich:';
    } else {
        $dtstart = $date_obj->format('Ymd');
        $end_obj = clone $date_obj;
        $end_obj->modify('+1 day');
        $dtend   = $end_obj->format('Ymd');
        $tzid    = 'VALUE=DATE:';
    }

    $uid  = $post_id . '-' . $date_raw . '@oav.ch';
    $now  = gmdate('Ymd\THis\Z');
    $slug = sanitize_title($titre);

    $ics = implode("\r\n", [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//OAV//OAV Events//FR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . $now,
        'DTSTART;' . $tzid . $dtstart,
        'DTEND;' . $tzid . $dtend,
        'SUMMARY:' . $titre,
        'LOCATION:' . $adresse,
        'URL:' . $url,
        'END:VEVENT',
        'END:VCALENDAR',
        '',
    ]);

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $slug . '.ics"');
    header('Cache-Control: no-cache, no-store');
    echo $ics;
    exit;
});
