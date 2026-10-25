<?php
defined('ABSPATH') || exit();

// Карта ведёт к существующим формам. Новую страницу добавляют здесь, не меняя права WordPress.
function agm_editing_pages()
{
    return [
        'home' => ['Главная', '/', ['hero', 'schedule', 'fleet', 'quote', 'services', 'faq', 'info', 'feedback', 'seo']],
        'services' => ['Услуги и тарифы', '/services/', ['services_page']],
        'schedule' => ['Расписание', '/schedule/', ['schedule_page', 'timetable_page']],
        'news' => ['Новости', '/news/', ['news_page']],
        'about' => ['О предприятии', '/about/', ['about_page', 'quote']],
        'vacancy' => ['Вакансии', '/vacancy/', ['vacancy_page']],
        'contact' => ['Контакты', '/contact/', ['contact_page', 'contacts']],
    ];
}
function agm_editing_context()
{
    if (is_front_page()) { return 'home'; }
    if (is_home() || is_singular('post')) { return 'news'; }
    if (is_page_template('templates/timetable.php')) { return 'schedule'; }
    $post = get_queried_object();
    return $post instanceof WP_Post && isset(agm_editing_pages()[$post->post_name]) ? $post->post_name : '';
}
function agm_editing_page_links($slug)
{
    $links = [];
    $context = agm_workspace_page_context($slug);
    if (!$context) { return $links; }
    if (current_user_can('edit_theme_options')) {
        foreach (agm_editing_pages()[$slug][2] as $section) {
            $settings = agm_content_sections()[$section] ?? null;
            if ($settings) { $links[] = [$settings['label'], admin_url('admin.php?page=agm-site-content&section=' . $section)]; }
        }
    }
    foreach ($context['types'] as $type) {
        $object = get_post_type_object($type);
        if ($object && current_user_can($object->cap->edit_posts)) {
            $links[] = [agm_workspace_catalog()[$type][0], admin_url('edit.php' . ($type === 'post' ? '' : '?post_type=' . $type))];
        }
    }
    if ($slug === 'home' && current_user_can('edit_theme_options')) {
        $links[] = ['Порядок и видимость блоков', admin_url('admin.php?page=agm-home-layout')];
    }
    if ($slug === 'schedule' && current_user_can('publish_agm_routes')) {
        $links[] = ['Запланировать изменение графика', admin_url('edit.php?post_type=agm_route&page=agm-route-planner')];
    }
    if ($slug === 'news' && current_user_can('publish_posts')) {
        $links[] = ['Запланировать новость', admin_url('edit.php?page=agm-news-planner')];
    }
    return $links;
}
function agm_editing_commands()
{
    $commands = [];
    foreach (agm_editing_pages() as $slug => [$label]) {
        if (agm_editing_page_links($slug)) {
            $commands[] = ['label' => $label . ': все настройки и карточки', 'group' => 'Страницы', 'url' => admin_url('admin.php?page=agm-editor-guide&for=' . $slug)];
        }
    }
    foreach (agm_workspace_catalog() as $type => [$label, $description]) {
        $object = get_post_type_object($type);
        if (!$object || !current_user_can($object->cap->edit_posts)) { continue; }
        $commands[] = ['label' => $label, 'group' => 'Материалы', 'hint' => $description, 'url' => admin_url('edit.php' . ($type === 'post' ? '' : '?post_type=' . $type))];
        if (current_user_can($object->cap->create_posts)) {
            $commands[] = ['label' => 'Добавить: ' . $label, 'group' => 'Создание', 'url' => admin_url('post-new.php' . ($type === 'post' ? '' : '?post_type=' . $type))];
        }
    }
    if (current_user_can('edit_theme_options')) {
        foreach (agm_content_sections() as $section => $config) {
            foreach ($config['fields'] as $key => $field) {
                $commands[] = ['label' => is_array($field) ? $field[0] : $field, 'group' => $config['label'], 'url' => admin_url('admin.php?page=agm-site-content&section=' . $section . '&agm_fields=' . $key)];
            }
        }
        $commands[] = ['label' => 'Меню: названия, ссылки и порядок', 'group' => 'Оформление', 'url' => admin_url('nav-menus.php')];
        $commands[] = ['label' => 'Порядок и видимость блоков главной', 'group' => 'Оформление', 'url' => admin_url('admin.php?page=agm-home-layout')];
    }
    if (current_user_can('upload_files')) {
        $commands[] = ['label' => 'Фотографии, видео и PDF', 'group' => 'Медиатека', 'url' => admin_url('upload.php')];
    }
    if (agm_can_manage_staff()) {
        $commands[] = ['label' => 'Сотрудники: добавить, пароль, роль и доступ', 'group' => 'Доступ', 'url' => admin_url('users.php')];
        $commands[] = ['label' => 'Роли и права сотрудников', 'group' => 'Доступ', 'url' => admin_url('users.php?page=agm-staff-roles')];
    }
    return $commands;
}
add_action('admin_menu', function () {
    add_submenu_page('agm-editor-guide', 'Страницы сайта', 'Страницы сайта', 'read', 'agm-page-map', 'agm_page_map_screen');
}, 125);
function agm_page_map_screen()
{
    if (!current_user_can('read')) { wp_die('Нет доступа.', '', ['response' => 403]); }
    echo '<div class="wrap agm-workspace"><h1>Страницы сайта</h1><p>' . (agm_visual_allowed() ? 'Найдите страницу, затем выберите, что хотите изменить. Тексты и фотографии меняются в полях, повторяющиеся блоки — в карточках.' : 'У вашей роли доступ к просмотру. Здесь можно открыть страницы сайта; для изменения содержимого обратитесь к редактору или администратору.') . '</p><div class="agm-task-grid">';
    foreach (agm_editing_pages() as $slug => [$label, $path]) {
        $links = agm_editing_page_links($slug);
        $links[] = ['Посмотреть страницу', home_url($path)];
        agm_workspace_card($label, 'Все изменения сохраняются через обычные формы WordPress.', 'admin-page', $links);
    }
    foreach (agm_passenger_pages() as $slug => $id) {
        $links = [['Посмотреть страницу', get_permalink($id)]];
        if (current_user_can('edit_post', $id)) { array_unshift($links, ['Изменить тексты и карточки', admin_url('admin.php?page=agm-passenger-content&page_id=' . (int) $id)]); }
        agm_workspace_card(get_the_title($id), 'Проездные, льготы и адреса пунктов продажи.', 'tickets-alt', $links);
    }
    echo '</div><p>Соглашение и другие обычные страницы находятся в разделе «Страницы». Новые страницы можно добавить там же.</p></div>';
}
add_action('in_admin_header', function () {
    if (!current_user_can('read') || wp_doing_ajax() || wp_is_json_request()) { return; }
    echo '<nav class="agm-admin-shortcuts" aria-label="Управление сайтом"><a href="' . esc_url(admin_url('admin.php?page=agm-editor-guide')) . '">Управление сайтом</a><a href="' . esc_url(admin_url('admin.php?page=agm-page-map')) . '">Страницы сайта</a><button type="button" class="button" data-agm-command-open>Найти настройку <kbd>Alt + K</kbd></button><a target="_blank" rel="noopener" href="' . esc_url(home_url('/')) . '">Посмотреть сайт ↗</a></nav>';
});
add_action('admin_enqueue_scripts', function () {
    $base = dirname(__DIR__) . '/autobus03-content.php';
    wp_enqueue_style('agm-editing-navigation', plugins_url('assets/editing-navigation.css', $base), [], filemtime(dirname(__DIR__) . '/assets/editing-navigation.css'));
    wp_enqueue_script('agm-editing-navigation', plugins_url('assets/editing-navigation.js', $base), [], filemtime(dirname(__DIR__) . '/assets/editing-navigation.js'), true);
    wp_add_inline_script('agm-editing-navigation', 'window.agmEditingCommands=' . wp_json_encode(agm_editing_commands()) . ';', 'before');
});
