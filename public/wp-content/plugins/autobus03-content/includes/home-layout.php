<?php
defined('ABSPATH') || exit();
function agm_home_section_catalog()
{
    return ['hero' => 'Главный баннер', 'schedule' => 'Расписание', 'fleet' => 'Автопарк', 'quote' => 'Слово мэра', 'services' => 'Услуги', 'news' => 'Последние новости', 'faq' => 'Вопросы и предприятие', 'information' => 'Полезные сведения', 'feedback' => 'Обращение через Госуслуги'];
}
function agm_home_layout()
{
    $saved = get_option('agm_home_sections', null);
    if (!is_array($saved)) {
        return array_keys(agm_home_section_catalog());
    }
    return array_values(array_unique(array_intersect($saved, array_keys(agm_home_section_catalog()))));
}
add_action('admin_menu', function () {
    add_submenu_page('agm-editor-guide', 'Блоки главной страницы', 'Блоки главной', 'edit_theme_options', 'agm-home-layout', 'agm_home_layout_screen');
}, 120);
add_action('admin_post_agm_home_layout_save', function () {
    if (!current_user_can('edit_theme_options')) {
        wp_die('Нет доступа.', '', ['response' => 403]);
    }
    check_admin_referer('agm_home_layout');
    if (!hash_equals(hash('sha256', wp_json_encode(agm_home_layout())), sanitize_text_field(wp_unslash($_POST['version'] ?? '')))) {
        wp_die('Другой сотрудник уже изменил порядок. Обновите страницу и повторите изменения.', '', ['response' => 409]);
    }
    if (isset($_POST['reset'])) {
        delete_option('agm_home_sections');
    } else {
        $order = $_POST['order'] ?? [];
        $enabled = $_POST['enabled'] ?? [];
        if (!is_array($order) || !is_array($enabled)) {
            wp_die('Не удалось прочитать список блоков.', '', ['response' => 400]);
        }
        $keys = array_keys(agm_home_section_catalog());
        $order = array_values(array_unique(array_intersect(array_map('sanitize_key', $order), $keys)));
        if (count($order) !== count($keys)) {
            wp_die('Неполный список блоков. Обновите страницу.', '', ['response' => 400]);
        }
        $visible = array_values(array_intersect($order, array_map('sanitize_key', $enabled)));
        if (!$visible) {
            wp_die('Оставьте хотя бы один блок. Вернитесь назад и отметьте его.', '', ['response' => 400, 'back_link' => true]);
        }
        update_option('agm_home_sections', $visible, false);
    }
    wp_safe_redirect(admin_url('admin.php?page=agm-home-layout&saved=1'));
    exit;
});
function agm_home_layout_screen()
{
    if (!current_user_can('edit_theme_options')) {
        wp_die('Нет доступа.', '', ['response' => 403]);
    }
    $catalog = agm_home_section_catalog();
    $visible = agm_home_layout();
    $order = array_unique(array_merge($visible, array_keys($catalog)));
    echo '<div class="wrap agm-workspace"><h1>Блоки главной страницы</h1><p>Отметьте нужные блоки и задайте порядок стрелками. Скрытие не удаляет тексты, фотографии и карточки. После сохранения главная страница изменится сразу.</p>';
    if (isset($_GET['saved'])) {
        echo '<div class="notice notice-success"><p>Настройки главной страницы сохранены.</p></div>';
    }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-unsaved-form><input type="hidden" name="action" value="agm_home_layout_save"><input type="hidden" name="version" value="' . esc_attr(hash('sha256', wp_json_encode($visible))) . '">';
    wp_nonce_field('agm_home_layout');
    echo '<ul id="agm-home-blocks" style="max-width:760px">';
    foreach ($order as $key) {
        echo '<li style="display:flex;align-items:center;gap:12px;padding:16px;background:white;border:1px solid #c3c4c7;margin:8px 0"><input type="hidden" name="order[]" value="' . esc_attr($key) . '"><label style="flex:1"><input type="checkbox" name="enabled[]" value="' . esc_attr($key) . '" ' . checked(in_array($key, $visible, true), true, false) . '> ' . esc_html($catalog[$key]) . '</label><button class="button" type="button" data-agm-move="up" aria-label="Выше: ' . esc_attr($catalog[$key]) . '">↑</button><button class="button" type="button" data-agm-move="down" aria-label="Ниже: ' . esc_attr($catalog[$key]) . '">↓</button></li>';
    }
    echo '</ul><p role="status" id="agm-home-position"></p>';
    submit_button('Сохранить порядок и видимость', 'primary', 'save', false);
    echo ' <button class="button" name="reset" value="1">Вернуть исходный порядок</button></form></div>';
}
add_action('admin_enqueue_scripts', function () {
    if (($_GET['page'] ?? '') === 'agm-home-layout') {
        wp_enqueue_script('agm-home-layout', plugins_url('assets/home-layout.js', dirname(__DIR__) . '/autobus03-content.php'), [], '1.10.0', true);
    }
});
