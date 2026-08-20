<?php
// OAV-specific functions only — parent functions.php is loaded automatically by WordPress.
require_once get_stylesheet_directory() . '/functions/dc26-oav-enqueue.php';
require_once get_stylesheet_directory() . '/functions/dc26-login-screen.php';
require_once get_stylesheet_directory() . '/functions/dc26-member.php';
require_once get_stylesheet_directory() . '/functions/dc26-member-api.php';
require_once get_stylesheet_directory() . '/functions/dc26-gform-prefill.php';
require_once get_stylesheet_directory() . '/functions/dc26-documentation-visibility.php';
require_once get_stylesheet_directory() . '/functions/dc26-examen-api.php';
require_once get_stylesheet_directory() . '/functions/dc26-commissions.php';
require_once get_stylesheet_directory() . '/functions/dc26-query-variations.php';
require_once get_stylesheet_directory() . '/functions/dc26-oav-blocks.php';
require_once get_stylesheet_directory() . '/functions/dc26-facetwp.php';

// Override the dc26 block category label from "DC26 Blocks" (parent) to "OAV".
// Priority 20 > parent priority 10 so this runs after the category is added.
add_filter('block_categories_all', function (array $categories): array {
    foreach ($categories as &$cat) {
        if ($cat['slug'] === 'dc26') {
            $cat['title'] = __('OAV', 'dc26-oav');
        }
    }
    return $categories;
}, 20);
