<?php
defined('ABSPATH') || exit();

function agm_news_planner_url($id = 0)
{
    return add_query_arg(
        array_filter(['page' => 'agm-news-planner', 'news' => $id]),
        admin_url('edit.php'),
    );
}
function agm_news_plan_access($id)
{
    $post = get_post($id);
    return $post &&
        'post' === $post->post_type &&
        current_user_can('publish_posts') &&
        current_user_can('edit_post', $id);
}
// Используем штатную отложенную публикацию WordPress: одна новость, один адрес и одна задача.
function agm_news_plan_save($id, $due = null, $cancel = false)
{
    if (!agm_news_plan_access($id)) {
        return new WP_Error('permission', 'Нет доступа к этой новости.');
    }
    $lock = 'agm_news_plan_lock_' . $id;
    if (!agm_plan_lock($lock)) {
        return new WP_Error('busy', 'Новость сейчас сохраняется. Повторите попытку.');
    }
    try {
        clean_post_cache($id);
        $post = get_post($id);
        if (!in_array($post->post_status, ['draft', 'pending', 'future'], true)) {
            return new WP_Error(
                'status',
                'Эта новость уже опубликована или удалена. Планировщик работает только с новыми, ещё не опубликованными новостями.',
            );
        }
        if ($cancel) {
            if ('future' !== $post->post_status) {
                return new WP_Error('status', 'У новости нет ожидающей публикации.');
            }
            $result = wp_update_post(
                [
                    'ID' => $id,
                    'post_status' => 'draft',
                    'edit_date' => true,
                    'post_date' => current_time('mysql'),
                    'post_date_gmt' => current_time('mysql', true),
                ],
                true,
            );
            if (is_wp_error($result)) {
                return $result;
            }
            wp_clear_scheduled_hook('publish_future_post', [$id]);
            return $id;
        }
        if (!is_int($due) || $due <= time() + 120) {
            return new WP_Error('date', 'Укажите будущие день и время, минимум через две минуты.');
        }
        if (!trim($post->post_title)) {
            return new WP_Error('title', 'Сначала добавьте название новости и сохраните черновик.');
        }
        if (!trim($post->post_content)) {
            return new WP_Error(
                'content',
                'Сначала добавьте текст, фото или видео и сохраните черновик.',
            );
        }
        $result = wp_update_post(
            [
                'ID' => $id,
                'post_status' => 'future',
                'edit_date' => true,
                'post_date' => wp_date('Y-m-d H:i:s', $due),
                'post_date_gmt' => gmdate('Y-m-d H:i:s', $due),
            ],
            true,
        );
        if (is_wp_error($result)) {
            return $result;
        }
        if (wp_next_scheduled('publish_future_post', [$id]) !== $due) {
            wp_clear_scheduled_hook('publish_future_post', [$id]);
            $scheduled = wp_schedule_single_event($due, 'publish_future_post', [$id], true);
            if (is_wp_error($scheduled) || !$scheduled) {
                // При недоступном планировщике оставляем материал скрытым и сохраняем весь его текст.
                wp_update_post(['ID' => $id, 'post_status' => 'draft']);
                return new WP_Error(
                    'cron',
                    'WordPress не смог создать задачу. Новость оставлена в черновиках. Обратитесь к администратору и запланируйте её снова.',
                );
            }
        }
        return $id;
    } finally {
        delete_option($lock);
    }
}
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php',
        'Планировщик новостей',
        'Планировщик новостей',
        'publish_posts',
        'agm-news-planner',
        'agm_news_planner_page',
    );
});
add_action('admin_enqueue_scripts', function ($hook) {
    if ('posts_page_agm-news-planner' !== $hook) {
        return;
    }
    $base = dirname(__DIR__);
    wp_enqueue_style(
        'agm-news-planner',
        plugins_url('assets/news-planner.css', $base . '/autobus03-content.php'),
        [],
        filemtime($base . '/assets/news-planner.css'),
    );
});
add_action('add_meta_boxes_post', function ($post) {
    if (
        !current_user_can('publish_posts') ||
        !in_array($post->post_status, ['auto-draft', 'draft', 'pending', 'future'], true)
    ) {
        return;
    }
    add_meta_box(
        'agm-news-planner-link',
        'Опубликовать в нужное время',
        function ($post) {
            echo '<p>Подготовьте новость и сохраните черновик. Затем выберите день и время в планировщике.</p><p><a class="button" href="' .
                esc_url(agm_news_planner_url('auto-draft' === $post->post_status ? 0 : $post->ID)) .
                '">Открыть планировщик новостей</a></p>';
        },
        'post',
        'side',
    );
});
add_action('admin_post_agm_news_plan_save', function () {
    check_admin_referer('agm_news_plan_save');
    $id = absint($_POST['agm_news_id'] ?? 0);
    $cancel = isset($_POST['agm_news_cancel']);
    $day =
        isset($_POST['agm_news_day']) && is_string($_POST['agm_news_day'])
            ? sanitize_text_field(wp_unslash($_POST['agm_news_day']))
            : '';
    $clock =
        isset($_POST['agm_news_time']) && is_string($_POST['agm_news_time'])
            ? sanitize_text_field(wp_unslash($_POST['agm_news_time']))
            : '';
    $due = $cancel ? null : agm_plan_parse_date($day . 'T' . $clock);
    $result = is_wp_error($due) ? $due : agm_news_plan_save($id, $due, $cancel);
    $notice = is_wp_error($result)
        ? ['error' => $result->get_error_message(), 'id' => $id, 'day' => $day, 'clock' => $clock]
        : [
            'message' => $cancel
                ? 'Публикация отменена. Новость сохранена в черновиках.'
                : 'Новость будет опубликована ' .
                    wp_date('d.m.Y', $due) .
                    ' в ' .
                    wp_date('H:i', $due) .
                    '.',
        ];
    set_transient('agm_news_plan_notice_' . get_current_user_id(), $notice, 180);
    wp_safe_redirect(agm_news_planner_url(is_wp_error($result) ? $id : 0));
    exit();
});
function agm_news_planner_page()
{
    if (!current_user_can('publish_posts')) {
        return;
    }
    $id = absint($_GET['news'] ?? 0);
    $notice = get_transient('agm_news_plan_notice_' . get_current_user_id());
    delete_transient('agm_news_plan_notice_' . get_current_user_id());
    echo '<div class="wrap agm-news-planner"><h1>Планировщик новостей</h1><p class="agm-news-intro">Подготовьте новость, выберите день и время. Она автоматически появится на сайте после наступления этой даты.</p><p><a class="button" href="' .
        esc_url(admin_url('post-new.php')) .
        '">Добавить новую новость</a> <a class="button" href="' .
        esc_url(agm_plan_url()) .
        '">Планировщик расписаний</a></p>';
    if ($notice) {
        echo '<div class="notice ' .
            (isset($notice['error']) ? 'notice-error' : 'notice-success') .
            '"><p>' .
            esc_html($notice['error'] ?? $notice['message']) .
            '</p></div>';
    }
    agm_planner_clock_panel();
    $available = array_filter(
        get_posts([
            'post_type' => 'post',
            'post_status' => ['draft', 'pending', 'future'],
            'numberposts' => -1,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]),
        function ($post) {
            return agm_news_plan_access($post->ID);
        },
    );
    $selected = $id && agm_news_plan_access($id) ? get_post($id) : null;
    if ($selected && !in_array($selected->post_status, ['draft', 'pending', 'future'], true)) {
        $selected = null;
    }
    $day =
        $selected && 'future' === $selected->post_status ? substr($selected->post_date, 0, 10) : '';
    $clock =
        $selected && 'future' === $selected->post_status ? substr($selected->post_date, 11, 5) : '';
    if (isset($notice['error']) && (int) ($notice['id'] ?? 0) === $id) {
        $day = $notice['day'];
        $clock = $notice['clock'];
    }
    echo '<section class="agm-news-plan-box"><h2>Назначить публикацию</h2><p>Сначала добавьте текст, фотографии или видео через «Добавить новую новость» и нажмите «Сохранить черновик». Затем выберите её здесь.</p>';
    if ($available) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('agm_news_plan_save');
        echo '<input type="hidden" name="action" value="agm_news_plan_save"><label for="agm-news-id">Новость</label><select required id="agm-news-id" name="agm_news_id"><option value="">Выберите подготовленную новость</option>';
        foreach ($available as $post) {
            echo '<option value="' .
                (int) $post->ID .
                '" ' .
                selected($id, $post->ID, false) .
                '>' .
                esc_html(
                    ($post->post_title ?: 'Без названия') .
                        ('future' === $post->post_status ? ' — уже запланирована' : ' — черновик'),
                ) .
                '</option>';
        }
        echo '</select><div class="agm-news-date-row"><div><label for="agm-news-day">День публикации</label><input required type="date" id="agm-news-day" name="agm_news_day" value="' .
            esc_attr($day) .
            '"></div><div><label for="agm-news-time">Время (' .
            esc_html(agm_planner_main_label()) .
            ')</label><input required type="time" id="agm-news-time" name="agm_news_time" value="' .
            esc_attr($clock) .
            '"></div></div>';
        agm_planner_time_preview('agm-news-day', 'agm-news-time', $day, $clock);
        echo '<p><button class="button button-primary" type="submit">' .
            ($selected && 'future' === $selected->post_status
                ? 'Сохранить день и время'
                : 'Запланировать публикацию') .
            '</button>';
        if ($selected) {
            echo ' <a class="button" href="' .
                esc_url(get_edit_post_link($id)) .
                '">Изменить текст, фото и видео</a>';
        }
        echo '</p></form>';
    } else {
        echo '<p>Пока нет подготовленных новостей. Создайте новость и сохраните её как черновик.</p>';
    }
    echo '</section><h2>Ожидают публикации</h2><div class="agm-news-plan-list">';
    $future = array_filter(
        get_posts([
            'post_type' => 'post',
            'post_status' => 'future',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'ASC',
        ]),
        function ($post) {
            return agm_news_plan_access($post->ID);
        },
    );
    foreach ($future as $post) {
        $timestamp = strtotime($post->post_date_gmt . ' UTC');
        echo '<article class="agm-news-plan-item"><div><h3>' .
            esc_html($post->post_title) .
            '</h3><p>';
        agm_planner_time($timestamp);
        echo '</p><p>' .
            ($timestamp < time() ? 'Ожидает запуска задач сайта' : 'Запланирована') .
            '</p></div><div class="agm-news-plan-actions"><a class="button" href="' .
            esc_url(agm_news_planner_url($post->ID)) .
            '">Изменить день и время</a><a class="button" href="' .
            esc_url(get_edit_post_link($post->ID)) .
            '">Редактировать новость</a><form method="post" action="' .
            esc_url(admin_url('admin-post.php')) .
            '">';
        wp_nonce_field('agm_news_plan_save');
        echo '<input type="hidden" name="action" value="agm_news_plan_save"><input type="hidden" name="agm_news_id" value="' .
            (int) $post->ID .
            '"><button class="button" type="submit" name="agm_news_cancel" value="1">Отменить публикацию</button></form></div></article>';
    }
    if (!$future) {
        echo '<p>Запланированных новостей пока нет.</p>';
    }
    echo '</div><p>После публикации новость появится в разделе «Новости» и среди последних новостей на главной. Отмена возвращает её в черновики: текст, фото и видео сохраняются.</p><p class="description">Для запуска по времени на хостинге должен работать планировщик WordPress каждую минуту. Без него публикация может произойти при следующем посещении сайта. На выключенном компьютере локальный сайт не выполняет задания.</p></div>';
}
