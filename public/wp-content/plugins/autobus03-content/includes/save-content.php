<?php
defined('ABSPATH') || exit();
add_action('save_post', function ($post_id) {
    if (!empty($GLOBALS['agm_route_syncing'])) {
        return;
    }
    if (
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        wp_is_post_revision($post_id) ||
        !isset($_POST['agm_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['agm_nonce'])), 'agm_save') ||
        !current_user_can('edit_post', $post_id)
    ) {
        return;
    }
    $fields = isset($_POST['agm']) && is_array($_POST['agm']) ? wp_unslash($_POST['agm']) : [];
    foreach (agm_field_schema(get_post_type($post_id)) as $key => $field) {
        if ('agm_route' === get_post_type($post_id) && in_array($key, ['url', 'transfer'], true)) {
            continue;
        }
        $raw = isset($fields[$key]) && is_scalar($fields[$key]) ? $fields[$key] : '';
        $value = match ($field[1]) {
            'number', 'route_page', 'document' => absint($raw),
            'url' => esc_url_raw($raw),
            'times' => agm_sanitize_times($raw),
            'textarea' => sanitize_textarea_field($raw),
            'job_features' => agm_sanitize_job_features($raw),
            default => sanitize_text_field($raw),
        };
        update_post_meta($post_id, '_autobus_' . $key, $value);
    }
});
