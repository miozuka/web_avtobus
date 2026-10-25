<?php
defined('ABSPATH') || exit();
function agm_workspace_catalog()
{
    return [
        'post' => [
            'Новости',
            'Текст, фотографии, видео и публикации на заданную дату.',
            'megaphone',
        ],
        'agm_route' => [
            'Маршруты и графики',
            'Номера, направления и время отправления. Изменения сразу или по плану.',
            'location-alt',
        ],
        'agm_job' => [
            'Вакансии',
            'Карточки должностей, зарплаты, условия, телефоны и порядок показа.',
            'businessperson',
        ],
        'agm_tariff' => [
            'Тарифы',
            'Цены проезда и пояснения к тарифам на странице услуг.',
            'tickets-alt',
        ],
        'agm_service' => [
            'Услуги',
            'Описание услуг, стоимость, телефоны и ссылки на заказ.',
            'clipboard',
        ],
        'agm_contact' => [
            'Телефоны служб',
            'Справочник, верхние контактные карточки и режим работы.',
            'phone',
        ],
        'agm_contact_leader' => [
            'Руководство в контактах',
            'ФИО, должности и подписи контактных карточек.',
            'id',
        ],
        'agm_faq' => ['Вопросы пассажиров', 'Вопросы и ответы на главной странице.', 'editor-help'],
        'agm_fleet' => ['Автопарк', 'Модели автобусов, фотографии и количество машин.', 'car'],
        'agm_stat' => [
            'Показатели предприятия',
            'Числа и подписи на странице о предприятии.',
            'chart-bar',
        ],
        'agm_leader' => [
            'Руководство предприятия',
            'Фотографии, должности, описания и телефоны руководителей.',
            'groups',
        ],
        'agm_history' => [
            'История предприятия',
            'События, даты и выделение важных этапов.',
            'backup',
        ],
        'agm_slide' => [
            'Фотогалереи',
            'Современные и архивные фотографии, подписи и порядок.',
            'format-gallery',
        ],
        'agm_document' => [
            'Документы',
            'PDF-файлы предприятия и названия в списке документов.',
            'pdf',
        ],
        'agm_benefit' => [
            'Преимущества работы',
            'Короткие карточки условий работы на странице вакансий.',
            'awards',
        ],
        'agm_info' => [
            'Полезные сведения',
            'Карточки с описаниями и ссылками для посетителей.',
            'info',
        ],
    ];
}
add_filter(
    'register_post_type_args',
    function ($args, $type) {
        if (in_array($type, agm_simple_card_types(), true)) {
            $args['show_in_menu'] = false;
            $args['labels']['menu_name'] =
                agm_workspace_catalog()[$type][0] ?? $args['labels']['name'];
        }
        if (isset(agm_workspace_catalog()[$type])) {
            $name = agm_workspace_catalog()[$type][0];
            $args['labels']['add_new'] = 'Добавить карточку';
            $args['labels']['add_new_item'] = 'Добавить: ' . $name;
        }
        return $args;
    },
    50,
    2,
);
add_action(
    'admin_menu',
    function () {
        global $menu;
        foreach ($menu as &$item) {
            if (($item[2] ?? '') === 'agm-editor-guide') {
                $item[0] = 'Управление сайтом';
            }
        }
        unset($item);
        remove_action('toplevel_page_agm-editor-guide', 'agm_editor_guide');
        add_action('toplevel_page_agm-editor-guide', 'agm_workbench_screen');
        add_submenu_page(
            'agm-editor-guide',
            'Тексты и фотографии сайта',
            'Тексты и фотографии',
            'edit_theme_options',
            'agm-site-content',
            'agm_content_screen',
        );
        foreach (agm_passenger_pages() as $id) {
            if (current_user_can('edit_post', $id)) {
                add_submenu_page(
                    'agm-editor-guide',
                    'Проездные и льготы',
                    'Проездные и льготы',
                    'edit_pages',
                    'agm-passenger-content',
                    'agm_passenger_screen',
                );
                break;
            }
        }
    },
    115,
);
function agm_workspace_card($title, $text, $icon, $links, $count = '')
{
    echo '<article class="agm-task-card" data-task-card><div class="agm-task-card-head"><span class="dashicons dashicons-' .
        esc_attr($icon) .
        '" aria-hidden="true"></span><h2>' .
        esc_html($title) .
        '</h2></div><p>' .
        esc_html($text) .
        '</p>';
    if ($count !== '') {
        echo '<span class="agm-task-count">' . esc_html($count) . '</span>';
    }
    echo '<div class="agm-task-links">';
    foreach ($links as [$label, $url]) {
        echo '<a class="button" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
    echo '</div></article>';
}
function agm_workbench_screen()
{
    if (!current_user_can('read')) {
        wp_die('Нет доступа.', '', ['response' => 403]);
    }
    if (($_GET['view'] ?? '') === 'help') {
        agm_editor_guide();
        return;
    }
    $context = agm_workspace_page_context(sanitize_key($_GET['for'] ?? ''));
    echo '<div class="wrap agm-workspace"><header class="agm-workspace-heading"><div><p class="agm-admin-eyebrow">Рабочее место сотрудника</p><h1>Что нужно изменить на сайте?</h1><p>Выберите задачу. Здесь показаны только доступные вам разделы.</p></div><a class="button" href="' .
        esc_url(home_url('/')) .
        '" target="_blank" rel="noopener">Открыть сайт</a></header><div class="agm-admin-help"><strong>Подготовьте → проверьте → сохраните</strong><p>Черновик видите вы и редакторы. «Опубликовать» размещает материал на сайте. «Обновить» меняет опубликованное сразу. Для расписаний и новостей есть отдельные планировщики.</p></div><label for="agm-task-search"><strong>Найти нужную задачу</strong></label><input id="agm-task-search" type="search" placeholder="Например: телефон, фото, тариф или график"><p data-task-result role="status" aria-live="polite"></p>';
    echo '<div class="agm-workspace-context"><label for="agm-page-context">На какой странице меняете информацию?</label><select id="agm-page-context" data-workspace-context><option value="">Все разделы сайта</option>';
    foreach (
        [
            'home' => 'Главная',
            'services' => 'Услуги',
            'schedule' => 'Расписание',
            'news' => 'Новости',
            'about' => 'О предприятии',
            'vacancy' => 'Вакансии',
            'contact' => 'Контакты',
        ]
        as $key => $label
    ) {
        echo '<option value="' .
            esc_attr($key) .
            '" ' .
            selected(sanitize_key($_GET['for'] ?? ''), $key, false) .
            '>' .
            esc_html($label) .
            '</option>';
    }
    echo '</select></div>';
    if ($context) {
        $slug = sanitize_key($_GET['for'] ?? '');
        agm_workspace_card('Эта страница: тексты, блоки и настройки', 'Выберите нужную часть страницы. Кнопка «Посмотреть страницу» открывает готовый результат.', 'admin-page', array_merge(agm_editing_page_links($slug), [['Посмотреть страницу', home_url(agm_editing_pages()[$slug][1])]]));
    }
    echo '<div class="agm-task-grid">';
    foreach (agm_workspace_catalog() as $type => [$name, $text, $icon]) {
        if ($context && !in_array($type, $context['types'], true)) {
            continue;
        }
        $obj = get_post_type_object($type);
        if (!$obj || !current_user_can($obj->cap->edit_posts)) {
            continue;
        }
        $list = $type === 'post' ? 'edit.php' : 'edit.php?post_type=' . $type;
        $new = $type === 'post' ? 'post-new.php' : 'post-new.php?post_type=' . $type;
        $links = [['Открыть список', admin_url($list)]];
        if (current_user_can($obj->cap->create_posts)) {
            $links[] = ['Добавить', admin_url($new)];
        }
        if ($type === 'post' && current_user_can('publish_posts')) {
            $links[] = ['Планировщик', admin_url('edit.php?page=agm-news-planner')];
        }
        if ($type === 'agm_route' && current_user_can('publish_agm_routes')) {
            $links[] = [
                'Планировщик',
                admin_url('edit.php?post_type=agm_route&page=agm-route-planner'),
            ];
        }
        $count = wp_count_posts($type);
        agm_workspace_card(
            $name,
            $text,
            $icon,
            $links,
            'Опубликовано: ' . (int) $count->publish . ' · Черновики: ' . (int) $count->draft,
        );
    }
    if (current_user_can('edit_theme_options')) {
        agm_workspace_card(
            'Тексты, фотографии и общие контакты',
            'Телефоны, соцсети, заголовки, баннеры, логотип, карты и настройки cookie.',
            'admin-appearance',
            [
                [
                    'Открыть настройки',
                    admin_url(
                        'admin.php?page=agm-site-content' .
                            ($context ? '&section=' . $context['settings'] : ''),
                    ),
                ],
                [
                    'Контакты и соцсети',
                    admin_url('admin.php?page=agm-site-content&section=contacts'),
                ],
            ],
        );
        if (!$context) {
            agm_workspace_card(
                'Меню сайта',
                'Добавляйте ссылки и меняйте порядок основного меню и подвала стандартными средствами WordPress.',
                'menu',
                [['Изменить меню', admin_url('nav-menus.php')]],
            );
        }
    }
    foreach (!$context ? agm_passenger_pages() : [] as $slug => $id) {
        if (current_user_can('edit_post', $id)) {
            agm_workspace_card(
                'Проездные, льготы и пункты продажи',
                'Меняйте цены, тексты, адреса и ссылки в обычных полях. HTML редактировать не нужно.',
                'tickets',
                [['Открыть форму', admin_url('admin.php?page=agm-passenger-content')]],
            );
            break;
        }
    }
    $pages = get_post_type_object('page');
    if (!$context && current_user_can($pages->cap->edit_posts)) {
        agm_workspace_card(
            'Обычные страницы и документы',
            'Тексты соглашения, политики cookie и другие страницы. Для специальных разделов используйте карточки и настройки выше.',
            'admin-page',
            [
                ['Все страницы', admin_url('edit.php?post_type=page')],
                ['Новая страница', admin_url('post-new.php?post_type=page')],
            ],
        );
    }
    if (current_user_can('upload_files')) {
        agm_workspace_card(
            'Фотографии, видео и PDF',
            'Загрузите файл или выберите уже существующий. Заполняйте описание изображения для посетителей с нарушением зрения.',
            'admin-media',
            [
                ['Медиатека', admin_url('upload.php')],
                ['Загрузить файл', admin_url('media-new.php')],
            ],
        );
    }
    if (!$context && agm_can_manage_staff()) {
        agm_workspace_card(
            'Сотрудники и права',
            'Учётные записи, пароли, роли и временное отключение доступа.',
            'admin-users',
            [
                ['Сотрудники', admin_url('users.php')],
                ['Роли и права', admin_url('users.php?page=agm-staff-roles')],
            ],
        );
    }
    if (!$context && current_user_can('agm_view_overview')) {
        agm_workspace_card(
            'Сводка и ожидающие задания',
            'Проверьте отложенные новости и подготовленные версии графиков.',
            'chart-pie',
            [['Открыть обзор', admin_url('admin.php?page=agm-site-overview')]],
        );
    }
    echo '</div><p><a href="' .
        esc_url(admin_url('admin.php?page=agm-editor-guide&view=help')) .
        '">Подробная инструкция: публикация, восстановление и порядок материалов</a></p></div>';
}
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'edit') {
        return;
    }
    $type = $screen->post_type;
    $item = agm_workspace_catalog()[$type] ?? null;
    if (!$item) {
        return;
    }
    echo '<div class="agm-admin-help"><strong>' .
        esc_html($item[0]) .
        '</strong><p>' .
        esc_html($item[1]) .
        ' Черновики не видны посетителям; удалённое можно восстановить из корзины до её очистки.</p><a href="' .
        esc_url(admin_url('admin.php?page=agm-editor-guide')) .
        '">Все задачи управления сайтом</a></div>';
});
add_filter('block_editor_settings_all', function ($settings) {
    if (!current_user_can('manage_options')) {
        $settings['codeEditingEnabled'] = false;
    }
    return $settings;
});
add_filter('post_updated_messages', function ($messages) {
    foreach (agm_simple_card_types() as $type) {
        $messages[$type] = [
            1 => 'Карточка сохранена.',
            4 => 'Карточка сохранена.',
            6 => 'Карточка опубликована.',
            7 => 'Карточка сохранена.',
            8 => 'Карточка отправлена на проверку.',
            9 => 'Публикация карточки запланирована.',
            10 => 'Черновик карточки сохранён.',
        ];
    }
    return $messages;
});
add_action('admin_enqueue_scripts', function () {
    $s = get_current_screen();
    if (!$s) {
        return;
    }
    $file = dirname(__DIR__) . '/autobus03-content.php';
    wp_enqueue_style(
        'agm-workspace',
        plugins_url('assets/admin-workspace.css', $file),
        [],
        filemtime(dirname(__DIR__) . '/assets/admin-workspace.css'),
    );
    wp_enqueue_script(
        'agm-workspace',
        plugins_url('assets/admin-workspace.js', $file),
        ['jquery'],
        filemtime(dirname(__DIR__) . '/assets/admin-workspace.js'),
        true,
    );
    if (
        in_array($_GET['page'] ?? '', ['agm-site-content'], true) &&
        current_user_can('upload_files')
    ) {
        wp_enqueue_media();
    }
});

add_filter(
    'login_redirect',
    function ($redirect, $requested, $user) {
        if (
            $user instanceof WP_User &&
            (!$requested ||
                in_array(
                    rtrim($requested, '/'),
                    [rtrim(admin_url(), '/'), rtrim(admin_url('index.php'), '/')],
                    true,
                ))
        ) {
            return admin_url('admin.php?page=agm-editor-guide');
        }
        return $redirect;
    },
    20,
    3,
);

// Вместо длинного списка из десятка служебных пунктов сотрудники выбирают раздел в рабочем месте.
add_filter(
    'parent_file',
    function ($parent) {
        $s = get_current_screen();
        return $s && in_array($s->post_type, agm_simple_card_types(), true)
            ? 'agm-editor-guide'
            : $parent;
    },
    30,
);
add_filter(
    'submenu_file',
    function ($file) {
        $s = get_current_screen();
        return $s && in_array($s->post_type, agm_simple_card_types(), true)
            ? 'agm-editor-guide'
            : $file;
    },
    30,
);

function agm_workspace_page_context($slug)
{
    return [
        'home' => [
            'types' => ['post', 'agm_fleet', 'agm_service', 'agm_faq', 'agm_info'],
            'settings' => 'hero',
        ],
        'services' => ['types' => ['agm_service', 'agm_tariff'], 'settings' => 'services_page'],
        'schedule' => ['types' => ['agm_route'], 'settings' => 'schedule_page'],
        'news' => ['types' => ['post'], 'settings' => 'news_page'],
        'about' => [
            'types' => ['agm_stat', 'agm_leader', 'agm_history', 'agm_slide', 'agm_document'],
            'settings' => 'about_page',
        ],
        'vacancy' => ['types' => ['agm_job', 'agm_benefit'], 'settings' => 'vacancy_page'],
        'contact' => [
            'types' => ['agm_contact', 'agm_contact_leader'],
            'settings' => 'contact_page',
        ],
    ][$slug] ?? null;
}
function agm_business_page_editor_url($id)
{
    if (!current_user_can('edit_post', $id) || get_post_type($id) !== 'page') {
        return '';
    }
    if (in_array((int) $id, array_map('intval', agm_passenger_pages()), true)) {
        return admin_url('admin.php?page=agm-passenger-content&page_id=' . (int) $id);
    }
    $post = get_post($id);
    $slug = (int) $id === (int) get_option('page_on_front') ? 'home' : $post->post_name;
    return agm_workspace_page_context($slug)
        ? admin_url('admin.php?page=agm-editor-guide&for=' . $slug)
        : '';
}
add_filter(
    'get_edit_post_link',
    function ($link, $id) {
        return agm_business_page_editor_url($id) ?: $link;
    },
    20,
    2,
);
add_action('load-post.php', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || ($_GET['action'] ?? 'edit') !== 'edit') {
        return;
    }
    $url = agm_business_page_editor_url(absint($_GET['post'] ?? 0));
    if ($url) {
        wp_safe_redirect($url);
        exit();
    }
});
add_filter(
    'page_row_actions',
    function ($actions, $post) {
        if (agm_business_page_editor_url($post->ID)) {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    },
    20,
    2,
);
