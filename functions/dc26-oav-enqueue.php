<?php
declare(strict_types=1);

/**
 * Enqueue OAV child-theme assets (CSS + JS from dc26-oav/build/).
 * Parent assets are enqueued by dc26-base — this just adds OAV-specific overrides.
 */
function dc26_oav_enqueue_styles(): void {
    $style_path = get_stylesheet_directory() . '/build/style.css';
    wp_enqueue_style(
        'dc26-oav-front-styles',
        get_stylesheet_directory_uri() . '/build/style.css',
        ['dc26-front-styles'],
        file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0'
    );

    $script_path = get_stylesheet_directory() . '/build/app.js';
    wp_enqueue_script(
        'dc26-oav-front-scripts',
        get_stylesheet_directory_uri() . '/build/app.js',
        ['dc26-front-scripts'],
        file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'dc26_oav_enqueue_styles');
