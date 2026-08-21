<?php
declare(strict_types=1);

/**
 * ONE-TIME migration endpoint for the documentation/examen members-only rework.
 * Remove this file (and its require in functions.php) once run successfully.
 *
 * POST /wp-json/dc26/v1/migrate-members-only
 * Requires manage_options (Basic Auth via Application Password).
 */

add_action('rest_api_init', 'dc26_register_migrate_members_only_route');

function dc26_register_migrate_members_only_route(): void {
    register_rest_route('dc26/v1', '/migrate-members-only', [
        'methods'             => 'POST',
        'callback'            => 'dc26_rest_migrate_members_only',
        'permission_callback' => function (): bool {
            return current_user_can('manage_options');
        },
    ]);
}

function dc26_rest_migrate_members_only(): WP_REST_Response {
    $report = [
        'acf_sync'     => [],
        'documentation' => [],
        'examen'        => [],
    ];

    // ── ACF field groups ─────────────────────────────────────────────────
    $acf_json_dir = get_stylesheet_directory() . '/acf-json/';
    $acf_groups   = [
        'group_dc26_documentation_force_private',
        'group_dc26_documentation_type_visibility',
    ];

    if (function_exists('acf_import_field_group')) {
        foreach ($acf_groups as $key) {
            $json_path = $acf_json_dir . $key . '.json';
            if (!file_exists($json_path)) {
                $report['acf_sync'][] = ['key' => $key, 'status' => 'json_not_found'];
                continue;
            }
            $group = json_decode((string) file_get_contents($json_path), true);
            if (!$group) {
                $report['acf_sync'][] = ['key' => $key, 'status' => 'invalid_json'];
                continue;
            }
            $result = acf_import_field_group($group);
            $report['acf_sync'][] = $result
                ? ['key' => $key, 'status' => 'imported', 'id' => $result['ID']]
                : ['key' => $key, 'status' => 'import_failed'];
        }
    } else {
        $report['acf_sync'][] = ['status' => 'acf_not_active'];
    }

    // ── documentation ────────────────────────────────────────────────────
    if (function_exists('dc26_documentation_apply_visibility')) {
        $doc_ids = get_posts([
            'post_type'      => 'documentation',
            'post_status'    => ['publish', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($doc_ids as $post_id) {
            $before_meta   = (bool) get_post_meta($post_id, '_members_only', true);
            $before_status = get_post_status($post_id);

            dc26_documentation_apply_visibility($post_id);

            $after_meta   = (bool) get_post_meta($post_id, '_members_only', true);
            $after_status = get_post_status($post_id);

            if ($before_meta !== $after_meta || $before_status !== $after_status) {
                $report['documentation'][] = [
                    'id'            => $post_id,
                    'title'         => get_the_title($post_id),
                    'members_only'  => [$before_meta, $after_meta],
                    'status'        => [$before_status, $after_status],
                ];
            }
        }
    } else {
        $report['documentation'][] = ['error' => 'dc26_documentation_apply_visibility_not_found'];
    }

    // ── examen ────────────────────────────────────────────────────────────
    $examen_ids = get_posts([
        'post_type'      => 'examen',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($examen_ids as $post_id) {
        update_post_meta($post_id, '_members_only', true);
        wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);
        $report['examen'][] = [
            'id'    => $post_id,
            'title' => get_the_title($post_id),
        ];
    }

    return new WP_REST_Response($report);
}
