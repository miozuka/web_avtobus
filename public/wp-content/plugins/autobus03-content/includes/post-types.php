<?php
defined('ABSPATH') || exit();
add_action('init', function () {
    add_post_type_support('page', 'custom-fields');
    foreach (agm_types() as $type => $label) {
        register_post_type($type, [
            'labels' => [
                'name' => $label,
                'singular_name' => $label,
                'add_new_item' => 'Добавить: ' . $label,
                'edit_item' => 'Редактировать: ' . $label,
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-site-alt3',
            'supports' => [
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'page-attributes',
                'custom-fields',
            ],
        ]);
        foreach (agm_field_schema($type) as $key => $field) {
            register_post_meta($type, '_autobus_' . $key, [
                'single' => true,
                'type' => in_array($field[1], ['number', 'route_page', 'document'], true)
                    ? 'integer'
                    : 'string',
                'show_in_rest' => true,
                'sanitize_callback' => match ($field[1]) {
                    'number', 'route_page', 'document' => 'absint',
                    'url' => 'esc_url_raw',
                    'times' => 'agm_sanitize_times',
                    'textarea' => 'sanitize_textarea_field',
                    'job_features' => 'agm_sanitize_job_features',
                    default => 'sanitize_text_field',
                },
                'auth_callback' => function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', $post_id);
                },
            ]);
        }
    }
    foreach (agm_field_schema('page') as $key => $field) {
        register_post_meta('page', '_autobus_' . $key, [
            'single' => true,
            'type' => 'string',
            'show_in_rest' => true,
            'sanitize_callback' => 'url' === $field[1] ? 'esc_url_raw' : 'sanitize_text_field',
            'auth_callback' => function ($allowed, $meta_key, $post_id) {
                return current_user_can('edit_post', $post_id);
            },
        ]);
    }
});
