<?php
defined('ABSPATH') || exit();

// Храним последние пять состояний каждого раздела. Паролей и технических настроек в этих формах нет.
function agm_settings_history($section)
{
    $history = get_option('agm_content_history_' . sanitize_key($section), []);
    return is_array($history) ? $history : [];
}
add_action('agm_content_saved', function ($section, $before) {
    $history = agm_settings_history($section);
    array_unshift($history, ['id' => wp_generate_uuid4(), 'time' => time(), 'user' => get_current_user_id(), 'values' => $before]);
    update_option('agm_content_history_' . sanitize_key($section), array_slice($history, 0, 5), false);
}, 10, 2);
function agm_settings_history_screen($section)
{
    if (!current_user_can('edit_theme_options')) { return; }
    $history = agm_settings_history($section);
    echo '<details class="agm-settings-history"><summary>Вернуть предыдущие настройки' . ($history ? ' · ' . count($history) : '') . '</summary><p>Сохраняются пять последних состояний этого раздела. Возврат меняет весь раздел; остальные настройки не затрагиваются. Для новостей и страниц используйте «Редакции» WordPress.</p>';
    if (!$history) { echo '<p>История появится после первого изменения настроек.</p>'; }
    foreach ($history as $entry) {
        $author = get_userdata($entry['user']);
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="agm-history-row" data-agm-confirm="Вернуть все настройки этого раздела к выбранному состоянию? Несохранённый текст формы будет потерян."><span>До изменения ' . esc_html(wp_date('d.m.Y H:i', $entry['time'])) . ($author ? ' · ' . esc_html($author->display_name) : '') . '</span><input type="hidden" name="action" value="agm_content_restore"><input type="hidden" name="section" value="' . esc_attr($section) . '"><input type="hidden" name="entry" value="' . esc_attr($entry['id']) . '"><input type="hidden" name="version" value="' . esc_attr(agm_content_version($section)) . '">';
        wp_nonce_field('agm_content_restore_' . $section);
        echo '<button class="button" type="submit">Вернуть это состояние</button></form>';
    }
    echo '</details>';
}
add_action('admin_post_agm_content_restore', function () {
    if (!current_user_can('edit_theme_options')) { wp_die('Нет доступа.', '', ['response' => 403]); }
    $section = sanitize_key($_POST['section'] ?? '');
    check_admin_referer('agm_content_restore_' . $section);
    $id = sanitize_text_field(wp_unslash($_POST['entry'] ?? ''));
    $entry = null;
    foreach (agm_settings_history($section) as $row) { if ($row['id'] === $id) { $entry = $row; break; } }
    if (!$entry) { wp_die('Состояние не найдено. Обновите страницу.', '', ['response' => 404, 'back_link' => true]); }
    // Восстановление проходит ту же проверку полей и конфликтов, что обычное сохранение.
    $result = agm_content_save($section, $entry['values'], wp_unslash($_POST['version'] ?? ''));
    if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), '', ['response' => 409, 'back_link' => true]); }
    wp_safe_redirect(admin_url('admin.php?page=agm-site-content&section=' . $section . '&saved=yes&restored=1'));
    exit;
});
