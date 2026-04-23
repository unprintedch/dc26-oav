<?php
/**
 * Login screen customization.
 *
 * @package dc26-oav
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue login screen styles and set custom logo.
 */
function dc26_login_screen_enqueue_assets(): void {
    $handle = 'dc26-login-screen';
    $css_path = 'css/login.css';

    wp_enqueue_style(
        $handle,
        get_theme_file_uri($css_path),
        array(),
        filemtime(get_theme_file_path($css_path))
    );

    $logo_id = 0;
    if (function_exists('get_field')) {
        $logo_id = (int) get_field('logo', 'option');
        if (!$logo_id) {
            $logo_id = (int) get_field('logo_white', 'option');
        }
    }

    if (!$logo_id) {
        $logo_id = (int) get_theme_mod('custom_logo');
    }

    if ($logo_id) {
        $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        if ($logo_url) {
            wp_add_inline_style(
                $handle,
                sprintf('.login h1 a{background-image:url(%s)}', esc_url($logo_url))
            );
        }
    }
}
add_action('login_enqueue_scripts', 'dc26_login_screen_enqueue_assets');

/**
 * Point the login logo to the site homepage.
 */
function dc26_login_screen_header_url(): string {
    return home_url('/');
}
add_filter('login_headerurl', 'dc26_login_screen_header_url');

/**
 * Set login logo title to the site name.
 */
function dc26_login_screen_header_text(): string {
    return get_bloginfo('name');
}
add_filter('login_headertext', 'dc26_login_screen_header_text');

/**
 * Customize the "Back to blog" link text on login screens.
 *
 * @param string $html Default login site link HTML.
 * @return string
 */
function dc26_login_screen_site_link_html(string $html): string {
    $url = esc_url(home_url('/'));

    return sprintf(
        '<p id="backtoblog"><a href="%1$s">%2$s</a></p>',
        $url,
        esc_html__("Retour au site de l'OAV", 'dc26-oav')
    );
}
add_filter('login_site_html', 'dc26_login_screen_site_link_html', 999);

/**
 * Customize the "Back to blog" link used by some plugins.
 *
 * @param string $html_link Default link HTML.
 * @return string
 */
function dc26_login_screen_site_link_html_link(string $html_link): string {
    $url = esc_url(home_url('/'));

    return sprintf(
        '<a href="%1$s">%2$s</a>',
        $url,
        esc_html__("Retour au site de l'OAV", 'dc26-oav')
    );
}
add_filter('login_site_html_link', 'dc26_login_screen_site_link_html_link', 999);
