<?php
defined('ABSPATH') || exit();
function autobus_value($key)
{
    return get_theme_mod('autobus_' . $key, '');
}
function autobus_text($key)
{
    autobus_field_text($key, autobus_value($key));
}
function autobus_image($key, $size = 'large', $attributes = [])
{
    $id = absint(autobus_value($key));
    if ($id) {
        if (function_exists('agm_visual_allowed') && agm_visual_allowed() && current_user_can('edit_theme_options')) { $attributes['data-agm-field'] = $key; }
        echo wp_get_attachment_image($id, $size, false, $attributes);
    }
}
function autobus_posts($type)
{
    return get_posts([
        'post_type' => $type,
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'post_status' => 'publish',
    ]);
}
function autobus_meta($post, $key)
{
    return get_post_meta($post->ID, '_autobus_' . $key, true);
}
function autobus_departure_times($direction, $day)
{
    $raw = get_post_meta($direction->ID, '_autobus_' . $day, true);
    if ($raw && !in_array($day, ['daily', 'unspecified'], true)) {
        $raw .= "\n" . get_post_meta($direction->ID, '_autobus_daily', true);
    }
    return agm_sanitize_times($raw);
}
function autobus_vehicle_count($number)
{
    $number = absint($number);
    $last_two = $number % 100;
    $last = $number % 10;
    $unit =
        $last_two >= 11 && $last_two <= 14
            ? 'машин'
            : (1 === $last
                ? 'машина'
                : ($last >= 2 && $last <= 4
                    ? 'машины'
                    : 'машин'));
    return $number . ' ' . $unit;
}
function autobus_phone_link($number)
{
    $digits = preg_replace('/\D+/', '', $number);
    return 'tel:+' . (6 === strlen($digits) ? '73012' . $digits : $digits);
}
function autobus_icon($name)
{
    $paths = [
        'bus' =>
            '<rect x="5" y="3" width="14" height="16" rx="3"/><path d="M5 11h14M8 6h8M8 19v2m8-2v2M8 15h.01M16 15h.01"/>',
        'ad' =>
            '<path d="m4 10 12-5v14L4 14zM4 10H2v4h2m3 1 2 6h3l-2-5M20 8l2-1m-2 5h2m-2 4 2 1"/>',
        'check' => '<path d="m12 3 8 3v6c0 5-8 9-8 9S4 17 4 12V6zM8 12l3 3 5-6"/>',
        'medical' =>
            '<rect x="5" y="3" width="14" height="18" rx="3"/><path d="M9 10h6m-3-3v6M9 17h6"/>',
        'ticket' => '<path d="M3 5h18v5a2 2 0 0 0 0 4v5H3v-5a2 2 0 0 0 0-4zM9 5v3m0 3v2m0 3v3"/>',
        'eye' =>
            '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'pin' =>
            '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'phone' =>
            '<path d="m5 3 4 5-2 3c2 3 3 4 6 6l3-2 5 4c-1 3-4 3-6 2C8 18 5 15 3 8 2 5 3 3 5 3Z"/>',
        'government' => '<path d="m3 8 9-5 9 5H3ZM4 21h16M5 18h14M6 11v7m6-7v7m6-7v7"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M6 15h4"/>',
        'graduate' => '<path d="m2 9 10-5 10 5-10 5-10-5ZM6 11v6l6 3 6-3v-6m4-2v7"/>',
        'wrench' =>
            '<path d="M14 3a6 6 0 0 0-7 7l-4 4a3 3 0 0 0 4 4l4-4a6 6 0 0 0 7-7l-4 4-3-3 3-5Z"/>',
        'document' => '<path d="M14 3H5v18h14V8l-5-5ZM14 3v5h5M8 12h8m-8 4h8"/>',
        'share' =>
            '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4M8.6 13.5l6.8 4"/>',
    ];
    echo '<svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        ($paths[$name] ?? $paths['bus']) .
        '</svg>';
}
function autobus_section_title($name)
{
    echo '<p class="eyebrow">';
    autobus_text($name . '_eyebrow');
    echo '</p><h2 class="section-title">';
    autobus_field_text($name . '_title', autobus_value($name . '_title'), true);
    echo '</h2>';
}
function autobus_social_icon($network)
{
    $paths = [
        'vk' =>
            '<path d="M12.4 18.6C5.6 18.6 1.7 14 1.5 6.3h3.4c.2 5.6 2.6 8 4.5 8.5V6.3h3.2v4.9c1.9-.2 3.8-2.5 4.5-4.9h3.2c-.5 2.9-2.6 5.2-4.1 6.1 1.5.8 3.9 2.8 4.8 6.2h-3.6c-.7-2.3-2.5-4.1-4.8-4.4v4.4h-.2Z"/>',
        'telegram' =>
            '<path d="m21.3 3.5-3.2 16.2c-.2 1.1-.9 1.4-1.8.9l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.4-5 9.1-8.2c.4-.4-.1-.6-.6-.3L5.7 13.4.9 11.9c-1-.3-1-1 .2-1.5L19.9 3c.9-.3 1.6.2 1.4.5Z"/>',
        'max' =>
            '<path fill-rule="evenodd" d="M12 2a10 10 0 0 0-8.7 14.9L2.5 21.5l4.6-.8A10 10 0 1 0 12 2Zm0 4.4a5.6 5.6 0 0 0-4.9 8.3l-.3 2.5 2.5-.3A5.6 5.6 0 1 0 12 6.4Z" clip-rule="evenodd"/>',
    ];
    if (!isset($paths[$network])) {
        return;
    }
    echo '<svg class="social-icon" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' .
        $paths[$network] .
        '</svg>';
}

// Служебная пометка для кнопки редактирования; посетителям ничего не выводится.
function autobus_edit_record($id)
{
    if (function_exists('agm_visual_record_attributes')) {
        agm_visual_record_attributes($id);
    }
}

// Маркер связывает видимую подпись с её полем. У посетителей маркеров нет.
function autobus_field_text($key, $value, $lines = false)
{
    $mark = function_exists('agm_visual_allowed') && agm_visual_allowed() && current_user_can('edit_theme_options');
    if ($mark) { echo '<!--agm-field:' . sanitize_key($key) . '-->'; }
    echo $lines ? nl2br(esc_html($value)) : esc_html($value);
    if ($mark) { echo '<!--/agm-field-->'; }
}
function autobus_ui($key, $fallback)
{
    autobus_field_text($key, get_theme_mod('autobus_' . $key, $fallback));
}
