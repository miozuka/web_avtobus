<?php
defined('ABSPATH') || exit();

// Используем существующие настройки темы: вторая копия текстов и фотографий не создаётся.
function agm_content_sections()
{
    if (!function_exists('autobus_settings_schema')) {
        return [];
    }
    $all = autobus_settings_schema();
    $all['info']['label'] = 'Главная: полезные сведения';
    $all['contacts']['label'] = 'Общие контакты и социальные сети';
    $all['identity'] = [
        'label' => 'Название, логотип и значок сайта',
        'fields' => [
            'site_name' => ['Название организации', 'site_name'],
            'site_description' => ['Краткое описание сайта', 'site_description'],
            'custom_logo' => ['Логотип', 'logo'],
            'site_icon' => ['Значок вкладки браузера', 'site_icon'],
        ],
    ];
    $all['interface'] = ['label' => 'Кнопки и подписи интерфейса', 'fields' => array_map(fn($copy) => $copy[0], agm_interface_copy())];
    $all['cookie'] = [
        'label' => 'Cookie и статистика',
        'fields' => [
            'cookie_ym_enabled' => ['Включить Яндекс Метрику', 'checkbox'],
            'cookie_ym_id' => ['Номер счётчика Яндекс Метрики', 'ym'],
            'cookie_ga_enabled' => ['Включить Google Analytics 4', 'checkbox'],
            'cookie_ga_id' => ['Идентификатор Google Analytics 4', 'ga'],
            'cookie_policy_url' => ['Ссылка на политику cookie', 'url'],
        ],
    ];
    // Эти старые поля больше не выводятся шаблонами; значения в базе сохраняются.
    $unused = [
        'vp_hr_mobile',
        'vp_hr_email',
        'vp_bonus_title',
        'vp_bonus_text',
        'vp_training_title',
        'vp_training_text',
        'vp_training_button',
        'tt_source_label',
        'partners_eyebrow', 'partners_title', 'partner_name', 'partner_text', 'partner_url', 'partner_image',
    ];
    foreach ($all as &$section) {
        foreach ($unused as $key) {
            unset($section['fields'][$key]);
        }
        $section['label'] = strtr($section['label'], [
            'SEO главной' => 'Главная: описание для поисковиков',
        ]);
        foreach ($section['fields'] as &$field) {
            if (is_array($field)) {
                $field[0] = strtr($field[0], [
                    'SEO:' => 'Для поисковиков:',
                    'Meta description' => 'Описание в поисковиках',
                    'copyright' => 'внизу сайта',
                    'H1' => 'главной страницы',
                ]);
            } else {
                $field = strtr($field, [
                    'SEO:' => 'Для поисковиков:',
                    'Meta description' => 'Описание в поисковиках',
                    'copyright' => 'внизу сайта',
                    'H1' => 'главной страницы',
                ]);
            }
        }
        unset($field);
    }
    unset($section);
    $all['services_page']['fields']['sp_image'][0] =
        'Изображение страницы услуг при отправке ссылки в соцсетях';
    return $all;
}
function agm_content_field_type($field)
{
    return is_array($field) ? $field[1] : 'textarea';
}
function agm_content_current($key, $field)
{
    return match (agm_content_field_type($field)) {
        'site_name' => get_option('blogname'),
        'site_description' => get_option('blogdescription'),
        'logo' => get_theme_mod('custom_logo', 0),
        'site_icon' => get_option('site_icon', 0),
        default => get_theme_mod('autobus_' . $key, agm_interface_copy()[$key][1] ?? ''),
    };
}
function agm_content_version($section)
{
    $values = [];
    foreach (agm_content_sections()[$section]['fields'] ?? [] as $key => $field) {
        $values[$key] = agm_content_current($key, $field);
    }
    return hash('sha256', wp_json_encode($values));
}
function agm_content_clean($raw, $type, $key)
{
    if (!is_scalar($raw)) {
        return new WP_Error('field', 'Некорректное значение поля.');
    }
    $raw = trim((string) $raw);
    if (in_array($type, ['image', 'document', 'logo', 'site_icon'], true)) {
        $id = absint($raw);
        if (!$id) {
            return 0;
        }
        if (
            get_post_type($id) !== 'attachment' ||
            ($type === 'document'
                ? get_post_mime_type($id) !== 'application/pdf' && !wp_attachment_is_image($id)
                : !wp_attachment_is_image($id))
        ) {
            return new WP_Error('media', 'Выберите подходящий файл из медиатеки.');
        }
        return $id;
    }
    if ($type === 'post') {
        return !$raw
            ? 0
            : (get_post_type((int) $raw) === 'post' && get_post_status((int) $raw) === 'publish'
                ? (int) $raw
                : new WP_Error('news', 'Главная новость должна быть опубликована.'));
    }
    if ($type === 'checkbox') {
        return in_array($raw, ['1', 'true'], true) ? 1 : 0;
    }
    if ($type === 'email') {
        return $raw && !is_email($raw)
            ? new WP_Error('email', 'Проверьте email: например info@example.ru.')
            : sanitize_email($raw);
    }
    if ($type === 'url') {
        if (
            str_ends_with($key, 'map_embed') &&
            preg_match('/\bsrc=["\x27]([^"\x27]+)["\x27]/i', $raw, $m)
        ) {
            $raw = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }
        if ($raw !== '' && !preg_match('~^(https?://[^\s<>]+|/[^\s<>]*|\#[^\s<>]*)$~iu', $raw)) {
            return new WP_Error(
                'url',
                'Нужна ссылка целиком, начиная с https://, либо путь страницы /contact/.',
            );
        }
        return esc_url_raw($raw, ['http', 'https']);
    }
    if ($type === 'ym') {
        return $raw && !autobus_cookie_ym_id($raw)
            ? new WP_Error('ym', 'Номер Метрики должен содержать только цифры.')
            : autobus_cookie_ym_id($raw);
    }
    if ($type === 'ga') {
        return $raw && !autobus_cookie_ga_id($raw)
            ? new WP_Error('ga', 'Идентификатор Google Analytics должен начинаться с G-.')
            : autobus_cookie_ga_id($raw);
    }
    if (mb_strlen($raw) > 20000) {
        return new WP_Error('length', 'Текст слишком длинный: сократите его до 20 000 символов.');
    }
    return in_array($type, ['site_name', 'site_description'], true)
        ? sanitize_text_field($raw)
        : sanitize_textarea_field($raw);
}
function agm_content_save($section, $values, $version)
{
    if (!current_user_can('edit_theme_options')) {
        return new WP_Error('forbidden', 'Нет доступа к общим настройкам сайта.');
    }
    $config = agm_content_sections()[$section] ?? null;
    if (!$config || !is_array($values)) {
        return new WP_Error('section', 'Раздел настроек не найден.');
    }
    if (!is_string($version) || !hash_equals(agm_content_version($section), $version)) {
        return new WP_Error(
            'conflict',
            'Эти настройки уже изменили в другом окне. Обновите страницу и повторите правку.',
        );
    }
    $before = [];
    foreach ($config['fields'] as $key => $field) { $before[$key] = agm_content_current($key, $field); }
    $clean = [];
    foreach ($config['fields'] as $key => $field) {
        if (!array_key_exists($key, $values)) {
            return new WP_Error(
                'incomplete',
                'Форма получена не полностью. Ничего не сохранено; обновите страницу.',
            );
        }
        $value = agm_content_clean($values[$key], agm_content_field_type($field), $key);
        if (is_wp_error($value)) {
            return new WP_Error(
                $value->get_error_code(),
                (is_array($field) ? $field[0] : $field) . ': ' . $value->get_error_message(),
            );
        }
        $clean[$key] = $value;
    }
    if (
        $section === 'cookie' &&
        ((!empty($clean['cookie_ym_enabled']) && empty($clean['cookie_ym_id'])) ||
            (!empty($clean['cookie_ga_enabled']) && empty($clean['cookie_ga_id'])))
    ) {
        return new WP_Error(
            'counter',
            'Чтобы включить сервис статистики, сначала укажите его идентификатор.',
        );
    }
    $mods = get_theme_mods() ?: [];
    $next = $mods;
    $options = [];
    foreach ($clean as $key => $value) {
        $type = agm_content_field_type($config['fields'][$key]);
        if (in_array($type, ['site_name', 'site_description', 'site_icon'], true)) {
            $options[
                match ($type) {
                    'site_name' => 'blogname',
                    'site_description' => 'blogdescription',
                    default => 'site_icon',
                }
            ] = $value;
        } else {
            $next[$type === 'logo' ? 'custom_logo' : 'autobus_' . $key] = $value;
        }
    }
    $oldOptions = [];
    foreach ($options as $key => $value) {
        $oldOptions[$key] = get_option($key);
    }
    $option = 'theme_mods_' . get_option('stylesheet');
    update_option($option, $next);
    foreach ($options as $key => $value) {
        update_option($key, $value);
    }
    if (get_option($option) !== $next) {
        update_option($option, $mods);
        foreach ($oldOptions as $key => $value) {
            update_option($key, $value);
        }
        return new WP_Error(
            'save',
            'Сервер не сохранил настройки. Повторите действие или обратитесь к администратору.',
        );
    }
    foreach ($options as $key => $value) {
        if ((string) get_option($key) !== (string) $value) {
            update_option($option, $mods);
            foreach ($oldOptions as $k => $v) {
                update_option($k, $v);
            }
            return new WP_Error('save', 'Сервер не сохранил настройки.');
        }
    }
    if (!hash_equals($version, agm_content_version($section))) { do_action('agm_content_saved', $section, $before); }
    return true;
}
add_action('admin_post_agm_content_save', function () {
    if (!current_user_can('edit_theme_options')) {
        wp_die('Нет доступа.', '', ['response' => 403]);
    }
    check_admin_referer('agm_content_save');
    $section = sanitize_key($_POST['section'] ?? '');
    $values = wp_unslash($_POST['values'] ?? []);
    $result = agm_content_save($section, $values, wp_unslash($_POST['version'] ?? ''));
    $token = strtolower(wp_generate_password(16, false, false));
    if (is_wp_error($result)) {
        set_transient(
            'agm_content_error_' . get_current_user_id() . '_' . $token,
            [
                'message' => $result->get_error_message(),
                'values' => $values,
                'version' => wp_unslash($_POST['version'] ?? ''),
            ],
            300,
        );
    }
    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'agm-site-content',
                'section' => $section,
                'saved' => is_wp_error($result) ? 'error' : 'yes',
                'attempt' => $token,
            ],
            admin_url('admin.php'),
        ),
    );
    exit();
});
function agm_content_screen()
{
    if (!current_user_can('edit_theme_options')) {
        wp_die('Нет доступа.', '', ['response' => 403]);
    }
    $all = agm_content_sections();
    if (!$all) {
        echo '<div class="wrap"><h1>Тексты и настройки сайта</h1><p>Для этих настроек должна быть активна тема autobus03.</p></div>';
        return;
    }
    $section = sanitize_key($_GET['section'] ?? 'contacts');
    if (!isset($all[$section])) {
        $section = 'contacts';
    }
    $config = $all[$section];
    $submitted = null;
    echo '<div class="wrap agm-workspace"><h1>Тексты, фотографии и настройки сайта</h1><p>Выберите раздел, измените нужные поля и сохраните. Изменения применяются сразу. Остальные разделы не затрагиваются.</p>';
    if (($_GET['saved'] ?? '') === 'yes') {
        echo '<div class="notice notice-success is-dismissible"><p>Настройки сохранены. Проверьте результат на сайте.</p></div>';
    }
    if (($_GET['saved'] ?? '') === 'error') {
        $token = sanitize_key($_GET['attempt'] ?? '');
        $error = get_transient('agm_content_error_' . get_current_user_id() . '_' . $token);
        if ($error) {
            $submitted = $error['values'];
            delete_transient('agm_content_error_' . get_current_user_id() . '_' . $token);
        }
        echo '<div class="notice notice-error"><p>' .
            esc_html($error['message'] ?? 'Не удалось сохранить настройки.') .
            '</p></div>';
    }
    echo '<div class="agm-settings-layout"><nav class="agm-settings-nav" aria-label="Разделы настроек"><label for="agm-setting-search">Найти раздел</label><input id="agm-setting-search" type="search" placeholder="Например, контакты"><ul>';
    foreach ($all as $key => $item) {
        echo '<li data-agm-search="' . esc_attr($item['label'] . ' ' . implode(' ', array_map(fn($field) => is_array($field) ? $field[0] : $field, $item['fields']))) . '"><a ' .
            ($key === $section ? 'aria-current="page"' : '') .
            ' href="' .
            esc_url(
                add_query_arg(
                    ['page' => 'agm-site-content', 'section' => $key],
                    admin_url('admin.php'),
                ),
            ) .
            '">' .
            esc_html($item['label']) .
            '</a></li>';
    }
    echo '</ul></nav><section class="agm-admin-panel"><h2>' .
        esc_html($config['label']) .
        '</h2><form method="post" action="' .
        esc_url(admin_url('admin-post.php')) .
        '" data-unsaved-form>';
    wp_nonce_field('agm_content_save');
    echo '<input type="hidden" name="action" value="agm_content_save"><input type="hidden" name="section" value="' .
        esc_attr($section) .
        '"><input type="hidden" name="version" value="' .
        esc_attr($error['version'] ?? agm_content_version($section)) .
        '">';
    if ($section === 'cookie') {
        echo '<p class="agm-admin-help">Статистика загружается только после согласия посетителя. Если организация ещё не использует эти сервисы, оставьте их выключенными. Пароли и код счётчиков сюда вставлять не нужно.</p>';
    }
    echo '<div class="agm-setting-search"><label for="agm-field-search">Найти поле в этом разделе</label><input type="search" id="agm-field-search" placeholder="Например: заголовок, телефон или ссылка"><p id="agm-field-count" role="status" aria-live="polite"></p></div>';
    foreach ($config['fields'] as $key => $field) {
        $label = is_array($field) ? $field[0] : $field;
        $type = agm_content_field_type($field);
        $value = $submitted[$key] ?? agm_content_current($key, $field);
        $id = 'agm-setting-' . $key;
        echo '<div class="agm-setting-field"><label for="' .
            esc_attr($id) .
            '"><strong>' .
            esc_html($label) .
            '</strong></label>';
        if (in_array($type, ['image', 'document', 'logo', 'site_icon'], true)) {
            echo '<div class="agm-media-field" data-media-type="' .
                ($type === 'document' ? 'document' : 'image') .
                '"><input id="' .
                esc_attr($id) .
                '" type="hidden" name="values[' .
                esc_attr($key) .
                ']" value="' .
                absint($value) .
                '"><div class="agm-media-preview">';
            if ($value) {
                echo $type === 'document'
                    ? esc_html(get_the_title((int) $value))
                    : wp_get_attachment_image((int) $value, 'thumbnail');
            }
            echo '</div><button type="button" class="button" data-media-choose ' .
                disabled(!current_user_can('upload_files'), true, false) .
                '>Выбрать ' .
                ($type === 'document' ? 'PDF или изображение' : 'изображение') .
                '</button> <button type="button" class="button-link-delete" data-media-clear>Убрать</button><p class="description">' .
                ($type === 'site_icon'
                    ? 'Рекомендуется квадратное изображение не меньше 512 × 512 пикселей.'
                    : 'Файл выбирается из медиатеки. Удаление здесь не удаляет сам файл.') .
                '</p></div>';
        } elseif ($type === 'checkbox') {
            echo '<input type="hidden" name="values[' .
                esc_attr($key) .
                ']" value="0"><label class="agm-inline-check"><input id="' .
                esc_attr($id) .
                '" name="values[' .
                esc_attr($key) .
                ']" type="checkbox" value="1" ' .
                checked((bool) $value, true, false) .
                '> Включено</label>';
        } elseif ($type === 'post') {
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]);
            echo '<select id="' .
                esc_attr($id) .
                '" name="values[' .
                esc_attr($key) .
                ']"><option value="0">Без выделенной новости</option>';
            foreach ($posts as $p) {
                echo '<option value="' .
                    (int) $p->ID .
                    '" ' .
                    selected((int) $value, $p->ID, false) .
                    '>' .
                    esc_html($p->post_title) .
                    '</option>';
            }
            echo '</select>';
        } elseif ($type === 'textarea') {
            echo '<textarea id="' .
                esc_attr($id) .
                '" name="values[' .
                esc_attr($key) .
                ']" rows="3">' .
                esc_textarea($value) .
                '</textarea>';
        } else {
            echo '<input id="' .
                esc_attr($id) .
                '" name="values[' .
                esc_attr($key) .
                ']" type="' .
                ($type === 'email' ? 'email' : 'text') .
                '" value="' .
                esc_attr($value) .
                '" ' .
                ($type === 'url' ? 'data-link-input' : '') .
                '>';
        }
        if ($type === 'url') {
            echo '<p class="description">Ссылка целиком, например https://example.ru/, или страница этого сайта /contact/. ' .
                (str_ends_with($key, 'map_embed')
                    ? 'Можно вставить код карты: форма извлечёт адрес автоматически.'
                    : '') .
                '</p>';
        }
        echo '</div>';
    }
    echo '<div class="agm-admin-save">';
    submit_button('Сохранить изменения', 'primary', 'submit', false);
    $preview = '/';
    foreach (agm_editing_pages() as [$pageLabel, $path, $sections]) { if (in_array($section, $sections, true)) { $preview = $path; break; } }
    echo ' <a class="button" target="_blank" rel="noopener" href="' .
        esc_url(home_url($preview)) .
        '">Открыть страницу сайта</a><span class="description" data-save-status aria-live="polite"></span></div></form>';
    agm_settings_history_screen($section);
    echo '</section></div></div>';
}

add_action(
    'customize_register',
    function ($customizer) {
        foreach (
            [
                'vp_hr_mobile',
                'vp_hr_email',
                'vp_bonus_title',
                'vp_bonus_text',
                'vp_training_title',
                'vp_training_text',
                'vp_training_button',
                'tt_source_label',
        'partners_eyebrow', 'partners_title', 'partner_name', 'partner_text', 'partner_url', 'partner_image',
            ]
            as $key
        ) {
            $customizer->remove_control('autobus_' . $key);
        }
        if ($c = $customizer->get_control('autobus_sp_image')) {
            $c->label = 'Изображение страницы услуг при отправке ссылки в соцсетях';
        }
    },
    110,
);
