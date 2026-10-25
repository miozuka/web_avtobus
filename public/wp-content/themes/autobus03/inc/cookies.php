<?php
defined('ABSPATH') || exit();
function autobus_cookie_ym_id($value)
{
    $value = trim((string) $value);
    return preg_match('/^[1-9][0-9]{0,14}$/', $value) ? $value : '';
}
function autobus_cookie_ga_id($value)
{
    $value = strtoupper(trim((string) $value));
    return preg_match('/^G-[A-Z0-9]{4,20}$/', $value) ? $value : '';
}
function autobus_cookie_bool($value)
{
    return in_array($value, [true, 1, '1'], true);
}
function autobus_cookie_config()
{
    $ym = autobus_cookie_ym_id(get_theme_mod('autobus_cookie_ym_id', ''));
    $ga = autobus_cookie_ga_id(get_theme_mod('autobus_cookie_ga_id', ''));
    $ym = get_theme_mod('autobus_cookie_ym_enabled', false) ? $ym : '';
    $ga = get_theme_mod('autobus_cookie_ga_enabled', false) ? $ga : '';
    return [
        'ym' => $ym,
        'ga' => $ga,
        'scope' => hash('sha256', '1|' . $ym . '|' . $ga),
        'trackAllowed' => !is_user_logged_in() && !is_customize_preview(),
        'days' => 180,
    ];
}
add_action('customize_register', function ($wp_customize) {
    $wp_customize->add_section('autobus_cookie', [
        'title' => 'Cookie и аналитика',
        'panel' => 'autobus',
        'description' =>
            'Счётчики подключаются только после выбора посетителя. Вставьте ID и включите нужный сервис. Не добавляйте тот же счётчик через другие плагины.',
        'priority' => 90,
    ]);
    $fields = [
        'ym_enabled' => ['Включить Яндекс Метрику', 'checkbox', 'autobus_cookie_bool', false, ''],
        'ym_id' => [
            'Номер счётчика Яндекс Метрики',
            'text',
            'autobus_cookie_ym_id',
            '',
            'Только цифры, например 12345678. Вебвизор отключён.',
        ],
        'ga_enabled' => [
            'Включить Google Analytics 4',
            'checkbox',
            'autobus_cookie_bool',
            false,
            '',
        ],
        'ga_id' => [
            'Идентификатор Google Analytics 4',
            'text',
            'autobus_cookie_ga_id',
            '',
            'ID формата G-XXXXXXXXXX. Рекламные сигналы отключены.',
        ],
        'policy_url' => [
            'Ссылка на политику cookie',
            'url',
            'esc_url_raw',
            '',
            'По умолчанию — страница /cookie-policy/.',
        ],
    ];
    foreach ($fields as $key => $field) {
        $id = 'autobus_cookie_' . $key;
        $wp_customize->add_setting($id, ['default' => $field[3], 'sanitize_callback' => $field[2]]);
        $wp_customize->add_control($id, [
            'section' => 'autobus_cookie',
            'label' => $field[0],
            'type' => $field[1],
            'description' => $field[4],
        ]);
    }
});
add_action(
    'wp_enqueue_scripts',
    function () {
        foreach (['css', 'js'] as $type) {
            $path = '/assets/' . $type . '/cookies.' . $type;
            if ('css' === $type) {
                wp_enqueue_style(
                    'autobus-cookies',
                    get_template_directory_uri() . $path,
                    ['autobus-effects'],
                    (string) filemtime(get_template_directory() . $path),
                );
            } else {
                wp_enqueue_script(
                    'autobus-cookies',
                    get_template_directory_uri() . $path,
                    ['autobus-main'],
                    (string) filemtime(get_template_directory() . $path),
                    ['strategy' => 'defer', 'in_footer' => true],
                );
            }
        }
    },
    20,
);
add_action(
    'wp_footer',
    function () {
        $policy = get_theme_mod('autobus_cookie_policy_url', '') ?: home_url('/cookie-policy/');
        $agreement = get_theme_mod('autobus_agreement_url', '') ?: home_url('/terms-of-use/');
        ?>
    <aside class="cookie-notice" aria-labelledby="cookie-notice-title" hidden>
        <div class="cookie-notice-copy"><strong id="cookie-notice-title"><?php autobus_ui('ui_31dd58432b', 'Мы уважаем вашу конфиденциальность!'); ?></strong><p><?php autobus_ui('ui_775f6ddf59', 'Наш сайт использует файлы cookie.'); ?><br><?php autobus_ui('ui_4594c63c7d', 'Продолжая использование, вы соглашаетесь с нашим'); ?> <a href="<?php echo esc_url(
            $agreement,
        ); ?>"><?php autobus_ui('ui_9988e39f83', 'пользовательским соглашением.'); ?></a></p></div>
        <div class="cookie-actions"><button type="button" class="cookie-button" data-cookie-reject><?php autobus_ui('ui_5047a06986', 'Отклонить'); ?></button><button type="button" class="cookie-button cookie-primary" data-cookie-accept><?php autobus_ui('ui_8b9ab48183', 'Принять'); ?></button></div>
    </aside>
    <dialog class="cookie-settings" aria-labelledby="cookie-settings-title">
        <div class="cookie-settings-top"><h2 id="cookie-settings-title"><?php autobus_ui('ui_e9980e42a6', 'Настройки cookie'); ?></h2><button type="button" class="cookie-close" data-cookie-close aria-label="Закрыть настройки">×</button></div>
        <p><?php autobus_ui('ui_6e3b3f82d8', 'Выберите, какие сервисы разрешить. Изменить решение можно в любой момент через подвал сайта.'); ?></p>
        <div class="cookie-option"><div><strong><?php autobus_ui('ui_9d43a5d739', 'Необходимые'); ?></strong><p><?php autobus_ui('ui_a79b0dafb2', 'Сохраняют ваш выбор. Всегда включены.'); ?></p></div><span class="cookie-required"><?php autobus_ui('ui_d1129978ff', 'Включены'); ?></span></div>
        <label class="cookie-option"><div><strong><?php autobus_ui('ui_c4aa8ab35a', 'Яндекс Метрика'); ?></strong><p><?php autobus_ui('ui_0c7e11d9d3', 'Статистика посещений. Без записи действий Вебвизором.'); ?></p><span data-cookie-status="ym"></span></div><input type="checkbox" data-cookie-choice="ym" aria-label="Разрешить Яндекс Метрику"></label>
        <label class="cookie-option"><div><strong>Google Analytics</strong><p><?php autobus_ui('ui_0b82e43cf4', 'Статистика посещений. Рекламные сигналы отключены.'); ?></p><span data-cookie-status="ga"></span></div><input type="checkbox" data-cookie-choice="ga" aria-label="Разрешить Google Analytics"></label>
        <p class="cookie-policy-link"><a href="<?php echo esc_url(
            $policy,
        ); ?>">Подробнее об использовании cookie</a></p>
        <div class="cookie-actions"><button type="button" class="cookie-button cookie-primary" data-cookie-save>Сохранить выбор</button><button type="button" class="cookie-button" data-cookie-reject>Только необходимые</button></div>
    </dialog>
    <script type="application/json" id="autobus-cookie-config"><?php echo wp_json_encode(
        autobus_cookie_config(),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ); ?></script>
    <?php
    },
    5,
);
