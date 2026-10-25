<?php
defined('ABSPATH') || exit();

// Формы остаются штатными: сохранение, проверка прав и планировщики работают через WordPress.
function agm_visual_allowed()
{
    return is_user_logged_in() && (current_user_can('edit_pages') || current_user_can('edit_posts') || current_user_can('edit_agm_routes') || current_user_can('edit_agm_items') || current_user_can('edit_agm_jobs') || current_user_can('edit_theme_options'));
}
function agm_visual_record_attributes($id)
{
    $id = absint($id);
    if (agm_visual_allowed() && current_user_can('edit_post', $id)) {
        echo ' data-agm-record="' . $id . '"';
    }
}
function agm_visual_page_record()
{
    $id = get_queried_object_id();
    if (is_page_template('templates/timetable.php')) {
        $route = absint(get_post_meta($id, '_autobus_route_id', true));
        if (!$route) {
            $found = get_posts(['post_type' => 'agm_route', 'numberposts' => 1, 'meta_key' => '_autobus_timetable_page_id', 'meta_value' => $id]);
            $route = $found ? $found[0]->ID : 0;
        }
        return $route;
    }
    return is_singular() ? $id : 0;
}
function agm_visual_tasks()
{
    $tasks = [];
    $record = agm_visual_page_record();
    if ($record && current_user_can('edit_post', $record)) {
        $tasks[] = ['label' => get_post_type($record) === 'agm_route' ? 'Маршрут и время отправления' : 'Содержимое этой страницы', 'url' => get_edit_post_link($record, 'raw'), 'group' => 'Эта страница'];
    }
    if (current_user_can('edit_theme_options')) {
        foreach (agm_content_sections() as $key => $section) {
            $tasks[] = ['label' => $section['label'], 'url' => admin_url('admin.php?page=agm-site-content&section=' . $key), 'group' => 'Тексты и фотографии'];
        }
        $tasks[] = ['label' => 'Порядок и видимость блоков главной', 'url' => admin_url('admin.php?page=agm-home-layout'), 'group' => 'Оформление'];
        $tasks[] = ['label' => 'Пункты меню и ссылки', 'url' => admin_url('nav-menus.php'), 'group' => 'Оформление'];
    }
    foreach (agm_workspace_catalog() as $type => [$name]) {
        $obj = get_post_type_object($type);
        if (!$obj || !current_user_can($obj->cap->edit_posts)) {
            continue;
        }
        $tasks[] = ['label' => $name . ': список и порядок', 'url' => admin_url('edit.php' . ($type === 'post' ? '' : '?post_type=' . $type)), 'group' => 'Материалы'];
        if (current_user_can($obj->cap->create_posts)) {
            $tasks[] = ['label' => 'Добавить: ' . $name, 'url' => admin_url('post-new.php' . ($type === 'post' ? '' : '?post_type=' . $type)), 'group' => 'Новые материалы'];
        }
    }
    if (current_user_can('publish_agm_routes')) {
        $tasks[] = ['label' => 'Планировщик маршрутов', 'url' => admin_url('edit.php?post_type=agm_route&page=agm-route-planner'), 'group' => 'По дате и времени'];
    }
    if (current_user_can('publish_posts')) {
        $tasks[] = ['label' => 'Планировщик новостей', 'url' => admin_url('edit.php?page=agm-news-planner'), 'group' => 'По дате и времени'];
    }
    if (current_user_can('edit_pages')) {
        $tasks[] = ['label' => 'Другие страницы и соглашения', 'url' => admin_url('edit.php?post_type=page'), 'group' => 'Материалы'];
    }
    if (current_user_can('upload_files')) {
        $tasks[] = ['label' => 'Фотографии, видео и документы', 'url' => admin_url('upload.php'), 'group' => 'Медиатека'];
    }
    return $tasks;
}
add_action('admin_bar_menu', function ($bar) {
    if (!is_admin() && agm_visual_allowed()) {
        $bar->add_node(['id' => 'agm-visual-edit', 'title' => '✎ Редактировать сайт', 'href' => '#agm-edit', 'meta' => ['title' => 'Включить кнопки редактирования на странице']]);
    }
}, 90);
add_action('wp_enqueue_scripts', function () {
    if (!agm_visual_allowed() || is_customize_preview()) {
        return;
    }
    // Страницы сотрудников не должны попадать в общий кэш с кнопками редактирования.
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    $file = dirname(__DIR__) . '/autobus03-content.php';
    wp_enqueue_style('agm-visual-editor', plugins_url('assets/frontend-editor.css', $file), [], filemtime(dirname(__DIR__) . '/assets/frontend-editor.css'));
    wp_enqueue_script('agm-visual-editor', plugins_url('assets/frontend-editor.js', $file), [], filemtime(dirname(__DIR__) . '/assets/frontend-editor.js'), true);
    $records = [];
    foreach (array_merge(array_keys(agm_workspace_catalog()), ['page']) as $type) {
        // Передаём только ID и ссылки доступных опубликованных карточек, без черновиков и личных данных.
        foreach (get_posts(['post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1]) as $post) {
            if (current_user_can('edit_post', $post->ID)) {
                $records[$post->ID] = ['label' => wp_strip_all_tags($post->post_title), 'url' => get_edit_post_link($post->ID, 'raw'), 'type' => $post->post_type];
            }
        }
    }
    wp_enqueue_style('agm-element-editor', plugins_url('assets/element-editor.css', $file), ['agm-visual-editor'], filemtime(dirname(__DIR__) . '/assets/element-editor.css'));
    wp_enqueue_script('agm-element-editor', plugins_url('assets/element-editor.js', $file), ['agm-visual-editor'], filemtime(dirname(__DIR__) . '/assets/element-editor.js'), true);
    wp_add_inline_script('agm-visual-editor', 'window.agmVisualEditor=' . wp_json_encode([
        'tasks' => agm_visual_tasks(), 'records' => $records,
        'fields' => agm_element_fields(), 'menuItems' => agm_element_menu_items(),
        'settings' => current_user_can('edit_theme_options') ? admin_url('admin.php?page=agm-site-content&section=') : '',
        'admin' => admin_url(), 'record' => agm_visual_page_record(),
        'context' => agm_editing_context(),
        'pages' => agm_editing_pages(),
        'types' => agm_workspace_page_context(agm_editing_context())['types'] ?? [],
        'css' => plugins_url('assets/frontend-editor.css', $file),
    ]) . ';', 'before');
});
add_action('wp_footer', function () {
    if (!agm_visual_allowed() || is_customize_preview()) {
        return;
    }
    ?>
    <button type="button" id="agm-edit-toggle" aria-pressed="false" aria-controls="agm-edit-tools">✎ Редактировать сайт</button>
    <button type="button" id="agm-edit-tasks-toggle" aria-expanded="false" aria-controls="agm-edit-tools" hidden>Все задачи</button>
    <aside id="agm-edit-tools" aria-label="Редактирование сайта" hidden>
        <header><strong>Редактирование сайта</strong><button type="button" data-agm-collapse aria-label="Свернуть список задач">−</button></header>
        <p>Выберите элемент на странице или найдите его по названию. Изменения появятся после сохранения.</p>
        <label for="agm-edit-search">Что нужно изменить?</label><input id="agm-edit-search" type="search" placeholder="Текст, фото, график, меню…">
        <div class="agm-task-scopes" role="group" aria-label="Задачи редактирования"><button type="button" data-agm-scope="page" aria-pressed="true">Эта страница</button><button type="button" data-agm-scope="all" aria-pressed="false">Весь сайт</button></div><p id="agm-edit-result-count" role="status" aria-live="polite"></p>
        <div id="agm-edit-task-list"></div><p id="agm-edit-empty" role="status" hidden>Задача не найдена. Попробуйте другое слово.</p>
    </aside>
    <dialog id="agm-edit-dialog" aria-labelledby="agm-edit-dialog-title">
        <header><div><strong id="agm-edit-dialog-title">Редактирование</strong><span id="agm-edit-status" role="status" aria-live="polite"></span></div><button type="button" data-agm-close aria-label="Закрыть редактор">×</button></header>
        <div class="agm-edit-dialog-actions"><button type="button" data-agm-save hidden>Сохранить изменения</button><a id="agm-edit-full" target="_blank" rel="noopener">Открыть в отдельной вкладке</a><button type="button" data-agm-refresh>Обновить просмотр сайта</button></div>
        <iframe id="agm-edit-frame" title="Форма редактирования сайта" src="about:blank"></iframe>
    </dialog>
    <?php
});
