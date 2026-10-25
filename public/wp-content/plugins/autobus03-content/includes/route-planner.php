<?php
defined('ABSPATH') || exit();

// Подготовленные версии хранятся отдельно от действующих маршрутов и не доступны посетителям.
add_action('init', function () {
    register_post_type('agm_route_plan', [
        'public' => false,
        'show_ui' => false,
        'show_in_rest' => false,
        'supports' => [],
        'rewrite' => false,
    ]);
});
function agm_plan_url($args = [])
{
    return add_query_arg(
        array_merge(['post_type' => 'agm_route', 'page' => 'agm-route-planner'], $args),
        admin_url('edit.php'),
    );
}
function agm_plan_snapshot($route_id)
{
    $route = get_post($route_id);
    if (!$route || 'agm_route' !== $route->post_type) {
        return [];
    }
    $page_id = agm_route_page_id($route_id);
    $snapshot = [
        'title' => $route->post_title,
        'status' => $route->post_status,
        'order' => $route->menu_order,
        'page' => $page_id,
        'card' => [],
        'note' => get_post_meta($page_id, '_autobus_tt_note', true),
        'rows' => [],
    ];
    foreach (['number', 'group', 'kind', 'stops'] as $key) {
        $snapshot['card'][$key] = get_post_meta($route_id, '_autobus_' . $key, true);
    }
    foreach (agm_route_directions($page_id) as $direction) {
        $row = ['id' => $direction->ID, 'title' => $direction->post_title];
        foreach (agm_timetable_day_schema() as $key => $label) {
            $row[$key] = get_post_meta($direction->ID, '_autobus_' . $key, true);
        }
        $snapshot['rows'][] = $row;
    }
    return $snapshot;
}
function agm_plan_fingerprint($route_id)
{
    return hash('sha256', wp_json_encode(agm_plan_snapshot($route_id)));
}
function agm_plan_pending($route_id, $states = ['pending', 'error'])
{
    return get_posts([
        'post_type' => 'agm_route_plan',
        'post_status' => 'draft',
        'numberposts' => -1,
        'meta_key' => '_agm_plan_due',
        'orderby' => ['meta_value_num' => 'ASC', 'ID' => 'ASC'],
        'meta_query' => [
            ['key' => '_agm_plan_target', 'value' => $route_id],
            ['key' => '_agm_plan_state', 'value' => $states, 'compare' => 'IN'],
        ],
    ]);
}
function agm_plan_find_pending($route_id)
{
    $plans = agm_plan_pending($route_id);
    return $plans ? $plans[0]->ID : 0;
}
function agm_plan_delete_draft($route_id)
{
    if ('draft' !== get_post_status($route_id)) {
        return;
    }
    $page = agm_route_page_id($route_id);
    foreach (agm_route_directions($page) as $direction) {
        wp_delete_post($direction->ID, true);
    }
    if ($page) {
        wp_delete_post($page, true);
    }
    wp_delete_post($route_id, true);
}
function agm_plan_create_draft($payload)
{
    $route = agm_route_write_post([
        'post_type' => 'agm_route',
        'post_status' => 'draft',
        'post_title' => $payload['title'],
        'post_author' => get_current_user_id(),
    ]);
    if (is_wp_error($route)) {
        return $route;
    }
    foreach ($payload['card'] as $key => $value) {
        update_post_meta($route, '_autobus_' . $key, wp_slash($value));
    }
    $page = agm_save_route_schedule($route, $payload['rows'], $payload['note']);
    if (is_wp_error($page)) {
        agm_plan_delete_draft($route);
        return $page;
    }
    return $route;
}
// После запланированного изменения сохраняем связь с направлениями; ручные изменения не принимаем автоматически.
function agm_plan_match_rows($route_id, $payload)
{
    if (!is_array($payload) || !is_array($payload['rows'] ?? null)) {
        return $payload;
    }
    $active = agm_route_directions(agm_route_page_id($route_id));
    $ids = array_map('intval', wp_list_pluck($active, 'ID'));
    $used = [];
    foreach ($payload['rows'] as &$row) {
        if (!is_array($row)) {
            continue;
        }
        $id = absint($row['id'] ?? 0);
        if (in_array($id, $ids, true) && !isset($used[$id])) {
            $used[$id] = true;
            continue;
        }
        $row['id'] = 0;
        foreach ($active as $direction) {
            if (!isset($used[$direction->ID]) && $direction->post_title === ($row['title'] ?? '')) {
                $row['id'] = $direction->ID;
                $used[$direction->ID] = true;
                break;
            }
        }
    }
    unset($row);
    return $payload;
}
function agm_plan_rebase_pending($route_id, $previous, $current, $applied_id, &$cache_ids)
{
    foreach (agm_plan_pending($route_id, ['pending']) as $plan) {
        if (
            $plan->ID === $applied_id ||
            get_post_meta($plan->ID, '_agm_plan_base', true) !== $previous
        ) {
            continue;
        }
        $cache_ids[] = $plan->ID;
        $payload = agm_plan_match_rows(
            $route_id,
            get_post_meta($plan->ID, '_agm_plan_payload', true),
        );
        $clean = agm_plan_validate($route_id, $payload);
        if (is_wp_error($clean)) {
            continue;
        }
        update_post_meta($plan->ID, '_agm_plan_payload', wp_slash($clean));
        update_post_meta($plan->ID, '_agm_plan_base', $current);
        if (
            get_post_meta($plan->ID, '_agm_plan_payload', true) !== $clean ||
            get_post_meta($plan->ID, '_agm_plan_base', true) !== $current
        ) {
            throw new RuntimeException(
                'Не удалось сохранить последовательность заданий. Изменение не применено.',
            );
        }
    }
}
function agm_plan_parse_date($value)
{
    if (!is_string($value)) {
        return new WP_Error('date', 'Выберите дату и время.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, wp_timezone());
    $errors = DateTimeImmutable::getLastErrors();
    if (
        !$date ||
        ($errors && ($errors['warning_count'] || $errors['error_count'])) ||
        $date->format('Y-m-d\TH:i') !== $value
    ) {
        return new WP_Error('date', 'Проверьте дату и время.');
    }
    if ($date->getTimestamp() <= time() + 60) {
        return new WP_Error('date', 'Укажите время как минимум на одну минуту позже текущего.');
    }
    return $date->getTimestamp();
}
function agm_plan_validate($route_id, $payload)
{
    if (
        !is_array($payload) ||
        !is_array($payload['card'] ?? null) ||
        !is_array($payload['rows'] ?? null)
    ) {
        return new WP_Error('payload', 'Некорректные данные графика.');
    }
    if ($route_id && !in_array(get_post_status($route_id), ['publish', 'draft', 'pending'], true)) {
        return new WP_Error('route', 'Маршрут удалён или уже имеет отложенную публикацию.');
    }
    if ($route_id && 'agm_route' !== get_post_type($route_id)) {
        return new WP_Error('route', 'Маршрут не найден.');
    }
    foreach (['title', 'note'] as $key) {
        if (isset($payload[$key]) && !is_scalar($payload[$key])) {
            return new WP_Error('payload', 'Некорректный текст.');
        }
    }
    $clean = [
        'title' => sanitize_text_field($payload['title'] ?? ''),
        'card' => [],
        'note' => sanitize_textarea_field($payload['note'] ?? ''),
    ];
    foreach (['number', 'group', 'kind', 'stops'] as $key) {
        $value = $payload['card'][$key] ?? '';
        if (!is_scalar($value)) {
            return new WP_Error('payload', 'Некорректные настройки маршрута.');
        }
        $clean['card'][$key] = sanitize_text_field($value);
    }
    if (!$clean['title'] || !$clean['card']['number']) {
        return new WP_Error('title', 'Заполните путь и номер маршрута.');
    }
    if (!in_array($clean['card']['group'], ['city', 'summer'], true)) {
        return new WP_Error('group', 'Выберите тип маршрута.');
    }
    $allowed = wp_list_pluck(agm_route_directions(agm_route_page_id($route_id)), 'ID');
    $rows = agm_validate_direction_rows($payload['rows'], array_map('intval', $allowed));
    if (is_wp_error($rows)) {
        return $rows;
    }
    if (!$rows) {
        return new WP_Error('rows', 'Добавьте хотя бы одно направление с расписанием.');
    }
    $clean['rows'] = array_map(function ($row) {
        return ['id' => $row['id'], 'title' => $row['title']] + $row['times'];
    }, $rows);
    return $clean;
}
function agm_plan_verify_result($route_id, $payload)
{
    $actual = agm_plan_snapshot($route_id);
    $expected_card = $payload['card'];
    if (!$expected_card['kind']) {
        $expected_card['kind'] =
            'summer' === $expected_card['group'] ? 'Дачный сезон' : 'Городской';
    }
    if (
        $actual['title'] !== $payload['title'] ||
        'publish' !== $actual['status'] ||
        $actual['card'] !== $expected_card ||
        $actual['note'] !== $payload['note'] ||
        count($actual['rows']) !== count($payload['rows']) ||
        'publish' !== get_post_status($actual['page']) ||
        get_post_field('post_title', $actual['page']) !== $payload['title'] ||
        get_post_meta($actual['page'], '_autobus_tt_number', true) !== $expected_card['number']
    ) {
        return false;
    }
    foreach ($payload['rows'] as $index => $row) {
        $saved = $actual['rows'][$index];
        if (
            $saved['title'] !== $row['title'] ||
            'publish' !== get_post_status($saved['id']) ||
            ($row['id'] && $row['id'] !== $saved['id'])
        ) {
            return false;
        }
        foreach (agm_timetable_day_schema() as $day => $label) {
            if ($saved[$day] !== $row[$day]) {
                return false;
            }
        }
    }
    return true;
}
function agm_plan_can_edit($route_id = 0)
{
    return current_user_can('publish_agm_routes') &&
        ($route_id ? current_user_can('edit_post', $route_id) : current_user_can('edit_agm_routes'));
}
function agm_plan_lock($key)
{
    if (add_option($key, time(), '', false)) {
        return true;
    }
    $old = (int) get_option($key);
    if (!$old || $old > time() - 300) {
        return false;
    }
    // После аварийного завершения PHP блокировка истекает через пять минут.
    global $wpdb;
    $wpdb->delete($wpdb->options, ['option_name' => $key, 'option_value' => (string) $old]);
    wp_cache_delete($key, 'options');
    wp_cache_delete('notoptions', 'options');
    return add_option($key, time(), '', false);
}
// Версия события защищает от применения старого задания после переноса даты или отмены.
function agm_plan_save($route_id, $payload, $due, $plan_id = 0)
{
    if (!agm_plan_can_edit($route_id)) {
        return new WP_Error('permission', 'Недостаточно прав для планирования публикации.');
    }
    if (!is_int($due) || $due <= time() + 60) {
        return new WP_Error('date', 'Выберите дату в будущем.');
    }
    $clean = agm_plan_validate($route_id, $payload);
    if (is_wp_error($clean)) {
        return $clean;
    }
    if (
        $plan_id &&
        ('agm_route_plan' !== get_post_type($plan_id) ||
            !current_user_can('edit_post', $plan_id) ||
            (int) get_post_meta($plan_id, '_agm_plan_target', true) !== $route_id ||
            !in_array(get_post_meta($plan_id, '_agm_plan_state', true), ['pending', 'error'], true))
    ) {
        return new WP_Error('plan', 'Задание недоступно для изменения.');
    }
    $lock = 'agm_plan_target_lock_' . ($route_id ?: 'new_' . $plan_id);
    if (!agm_plan_lock($lock)) {
        return new WP_Error('busy', 'Задание сейчас обрабатывается. Повторите попытку.');
    }
    $created_route = 0;
    $extra_lock = '';
    $saved = false;
    try {
        if ($plan_id) {
            wp_cache_delete($plan_id, 'post_meta');
            if (
                !in_array(
                    get_post_meta($plan_id, '_agm_plan_state', true),
                    ['pending', 'error'],
                    true,
                )
            ) {
                return new WP_Error(
                    'state',
                    'Задание уже выполнено или отменено. Перезагрузите страницу.',
                );
            }
        }
        if (!$route_id) {
            // Черновик позволяет заранее поставить несколько версий ещё не опубликованного маршрута.
            $route_id = agm_plan_create_draft($clean);
            if (is_wp_error($route_id)) {
                return $route_id;
            }
            $created_route = $route_id;
            $draft_lock = 'agm_plan_target_lock_' . $route_id;
            if (!agm_plan_lock($draft_lock)) {
                return new WP_Error('busy', 'Маршрут сейчас сохраняется. Повторите попытку.');
            }
            $extra_lock = $draft_lock;
            $clean['rows'] = agm_plan_snapshot($route_id)['rows'];
        }
        foreach (agm_plan_pending($route_id) as $other) {
            if (
                $other->ID !== $plan_id &&
                (int) floor((int) get_post_meta($other->ID, '_agm_plan_due', true) / 60) ===
                    (int) floor($due / 60)
            ) {
                return new WP_Error(
                    'same_time',
                    'Для этого маршрута уже есть задание на эту минуту. Выберите другое время или измените существующее задание.',
                );
            }
        }
        $new = !$plan_id;
        if ($new) {
            $plan_id = wp_insert_post(
                wp_slash([
                    'post_type' => 'agm_route_plan',
                    'post_status' => 'draft',
                    'post_title' => $clean['title'],
                    'post_author' => get_current_user_id(),
                ]),
                true,
            );
            if (is_wp_error($plan_id)) {
                return $plan_id;
            }
        }
        $old_version = get_post_meta($plan_id, '_agm_plan_version', true);
        $version = wp_generate_uuid4();
        $scheduled = wp_schedule_single_event(
            $due,
            'agm_apply_route_plan',
            [$plan_id, $version],
            true,
        );
        if (is_wp_error($scheduled) || !$scheduled) {
            if ($new) {
                wp_delete_post($plan_id, true);
            }
            return new WP_Error(
                'cron',
                'Не удалось создать задачу WordPress. Предыдущее задание сохранено; обратитесь к администратору.',
            );
        }
        update_post_meta($plan_id, '_agm_plan_payload', wp_slash($clean));
        update_post_meta($plan_id, '_agm_plan_target', $route_id);
        update_post_meta($plan_id, '_agm_plan_due', $due);
        update_post_meta(
            $plan_id,
            '_agm_plan_base',
            $route_id ? agm_plan_fingerprint($route_id) : '',
        );
        update_post_meta($plan_id, '_agm_plan_version', $version);
        update_post_meta($plan_id, '_agm_plan_state', 'pending');
        if ($created_route) {
            update_post_meta($plan_id, '_agm_plan_new', 1);
        }
        delete_post_meta($plan_id, '_agm_plan_error');
        wp_update_post(
            wp_slash([
                'ID' => $plan_id,
                'post_title' => $clean['title'],
                'post_author' => get_current_user_id(),
            ]),
        );
        if ($old_version) {
            wp_clear_scheduled_hook('agm_apply_route_plan', [$plan_id, $old_version]);
        }
        $saved = true;
        return $plan_id;
    } finally {
        if ($created_route && !$saved) {
            agm_plan_delete_draft($created_route);
        }
        if ($extra_lock) {
            delete_option($extra_lock);
        }
        delete_option($lock);
    }
}
function agm_plan_cancel($plan_id)
{
    if (
        'agm_route_plan' !== get_post_type($plan_id) ||
        !current_user_can('edit_post', $plan_id) ||
        !current_user_can('publish_agm_routes')
    ) {
        return new WP_Error('permission', 'Задание недоступно.');
    }
    $route_id = (int) get_post_meta($plan_id, '_agm_plan_target', true);
    if (!agm_plan_can_edit($route_id)) {
        return new WP_Error('permission', 'Нет доступа к маршруту.');
    }
    $lock = 'agm_plan_target_lock_' . ($route_id ?: 'new_' . $plan_id);
    if (!agm_plan_lock($lock)) {
        return new WP_Error('busy', 'Задание сейчас обрабатывается. Повторите попытку.');
    }
    try {
        wp_cache_delete($plan_id, 'post_meta');
        if (
            !in_array(get_post_meta($plan_id, '_agm_plan_state', true), ['pending', 'error'], true)
        ) {
            return new WP_Error('state', 'Задание уже завершено.');
        }
        wp_clear_scheduled_hook('agm_apply_route_plan', [
            $plan_id,
            get_post_meta($plan_id, '_agm_plan_version', true),
        ]);
        update_post_meta($plan_id, '_agm_plan_state', 'cancelled');
        return true;
    } finally {
        delete_option($lock);
    }
}
function agm_apply_route_plan($plan_id, $version)
{
    wp_cache_delete($plan_id, 'post_meta');
    if (
        'agm_route_plan' !== get_post_type($plan_id) ||
        get_post_meta($plan_id, '_agm_plan_version', true) !== $version ||
        'pending' !== get_post_meta($plan_id, '_agm_plan_state', true) ||
        (int) get_post_meta($plan_id, '_agm_plan_due', true) > time()
    ) {
        return;
    }
    $route_id = (int) get_post_meta($plan_id, '_agm_plan_target', true);
    // При запуске после простоя обрабатываем версии по дате, даже если события cron пришли в другом порядке.
    $queue = $route_id ? agm_plan_pending($route_id, ['pending']) : [get_post($plan_id)];
    foreach ($queue as $plan) {
        if ((int) get_post_meta($plan->ID, '_agm_plan_due', true) > time()) {
            continue;
        }
        agm_apply_route_plan_one($plan->ID, get_post_meta($plan->ID, '_agm_plan_version', true));
    }
}
function agm_apply_route_plan_one($plan_id, $version)
{
    if (
        'agm_route_plan' !== get_post_type($plan_id) ||
        get_post_meta($plan_id, '_agm_plan_version', true) !== $version ||
        'pending' !== get_post_meta($plan_id, '_agm_plan_state', true) ||
        (int) get_post_meta($plan_id, '_agm_plan_due', true) > time()
    ) {
        return;
    }
    $route_id = (int) get_post_meta($plan_id, '_agm_plan_target', true);
    $lock = 'agm_plan_target_lock_' . ($route_id ?: 'new_' . $plan_id);
    if (!agm_plan_lock($lock)) {
        wp_schedule_single_event(time() + 60, 'agm_apply_route_plan', [$plan_id, $version]);
        return;
    }
    $new_route = 0;
    $transaction = false;
    $applied = false;
    $cache_ids = [$plan_id, $route_id, agm_route_page_id($route_id)];
    $cache_ids = array_merge(
        $cache_ids,
        wp_list_pluck(agm_route_directions(agm_route_page_id($route_id)), 'ID'),
    );
    global $wpdb;
    try {
        // Другой запрос мог перенести или отменить событие до получения блокировки.
        wp_cache_delete($plan_id, 'post_meta');
        if (
            get_post_meta($plan_id, '_agm_plan_version', true) !== $version ||
            'pending' !== get_post_meta($plan_id, '_agm_plan_state', true) ||
            (int) get_post_meta($plan_id, '_agm_plan_due', true) > time()
        ) {
            return;
        }
        $author = (int) get_post_field('post_author', $plan_id);
        if (
            !user_can($author, 'publish_agm_routes') ||
            !user_can($author, $route_id ? 'edit_post' : 'edit_agm_routes', $route_id)
        ) {
            throw new RuntimeException('У автора задания больше нет прав на публикацию.');
        }
        $previous = $route_id ? agm_plan_fingerprint($route_id) : '';
        if ($route_id && $previous !== get_post_meta($plan_id, '_agm_plan_base', true)) {
            throw new RuntimeException(
                'Действующий маршрут изменён после подготовки задания. Проверьте изменения и сохраните задание заново.',
            );
        }
        $payload = agm_plan_validate($route_id, get_post_meta($plan_id, '_agm_plan_payload', true));
        if (is_wp_error($payload)) {
            throw new RuntimeException($payload->get_error_message());
        }
        // Маршрут, страница и все направления изменяются одним действием базы данных.
        $tables = $wpdb->get_results(
            $wpdb->prepare(
                'SHOW TABLE STATUS WHERE Name IN (%s, %s)',
                $wpdb->posts,
                $wpdb->postmeta,
            ),
        );
        if (
            count($tables) !== 2 ||
            array_filter($tables, function ($table) {
                return 'innodb' !== strtolower($table->Engine);
            })
        ) {
            throw new RuntimeException(
                'Для безопасного применения задания таблицы записей и их настроек должны использовать InnoDB. Обратитесь к администратору.',
            );
        }
        if (false === $wpdb->query('START TRANSACTION')) {
            throw new RuntimeException('Не удалось начать обновление базы данных.');
        }
        $transaction = true;
        if (!$route_id) {
            $route_id = agm_route_write_post([
                'post_type' => 'agm_route',
                'post_status' => 'draft',
                'post_author' => $author,
                'post_title' => $payload['title'],
            ]);
            if (is_wp_error($route_id)) {
                throw new RuntimeException($route_id->get_error_message());
            }
            $new_route = $route_id;
            $cache_ids[] = $route_id;
        }
        // Не используем HTTP-форму при запуске cron: только ранее проверенную сохранённую версию.
        foreach ($payload['card'] as $key => $value) {
            update_post_meta($route_id, '_autobus_' . $key, wp_slash($value));
        }
        $updated = agm_route_write_post(['ID' => $route_id, 'post_title' => $payload['title']]);
        if (is_wp_error($updated)) {
            throw new RuntimeException($updated->get_error_message());
        }
        $page = agm_save_route_schedule($route_id, $payload['rows'], $payload['note']);
        if (is_wp_error($page)) {
            throw new RuntimeException($page->get_error_message());
        }
        $published = agm_route_write_post(['ID' => $route_id, 'post_status' => 'publish']);
        if (is_wp_error($published)) {
            throw new RuntimeException($published->get_error_message());
        }
        if (!agm_plan_verify_result($route_id, $payload)) {
            throw new RuntimeException(
                'Не удалось сохранить все данные расписания. Изменения не применены; проверьте базу данных и повторите задание.',
            );
        }
        update_post_meta($plan_id, '_agm_plan_result', $route_id);
        update_post_meta($plan_id, '_agm_plan_state', 'applied');
        update_post_meta($plan_id, '_agm_plan_applied', time());
        delete_post_meta($plan_id, '_agm_plan_error');
        if ($previous) {
            agm_plan_rebase_pending(
                $route_id,
                $previous,
                agm_plan_fingerprint($route_id),
                $plan_id,
                $cache_ids,
            );
        }
        if (false === $wpdb->query('COMMIT')) {
            throw new RuntimeException('Не удалось завершить обновление базы данных.');
        }
        $transaction = false;
        $applied = true;
        clean_post_cache($route_id);
        clean_post_cache($page);
    } catch (Throwable $error) {
        if ($transaction) {
            $page = agm_route_page_id(is_int($route_id) ? $route_id : 0);
            $cache_ids[] = $page;
            $cache_ids = array_merge($cache_ids, wp_list_pluck(agm_route_directions($page), 'ID'));
            $wpdb->query('ROLLBACK');
            foreach (array_filter(array_unique($cache_ids)) as $id) {
                clean_post_cache($id);
            }
            $new_route = 0;
        }
        if ($new_route) {
            $page = agm_route_page_id($new_route);
            foreach (agm_route_directions($page) as $direction) {
                wp_delete_post($direction->ID, true);
            }
            if ($page) {
                wp_delete_post($page, true);
            }
            wp_delete_post($new_route, true);
        }
        update_post_meta($plan_id, '_agm_plan_state', 'error');
        update_post_meta($plan_id, '_agm_plan_error', $error->getMessage());
    } finally {
        delete_option($lock);
    }
    if ($applied) {
        do_action('agm_route_plan_applied', $route_id, $plan_id);
    }
}
add_action('agm_apply_route_plan', 'agm_apply_route_plan', 10, 2);
add_action('before_delete_post', function ($id) {
    if ('agm_route_plan' === get_post_type($id)) {
        wp_clear_scheduled_hook('agm_apply_route_plan', [
            $id,
            get_post_meta($id, '_agm_plan_version', true),
        ]);
    }
});
