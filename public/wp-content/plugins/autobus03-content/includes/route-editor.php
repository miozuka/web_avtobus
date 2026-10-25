<?php
defined("ABSPATH") || exit();

// Сохраняем связи со страницами и направлениями, чтобы перенос не менял адреса графиков.
function agm_route_page_id($route_id)
{
    $id = absint(get_post_meta($route_id, "_autobus_timetable_page_id", true));
    if (!$id) {
        $url = get_post_meta($route_id, "_autobus_url", true);
        if (
            $url &&
            wp_parse_url($url, PHP_URL_HOST) ===
                wp_parse_url(home_url(), PHP_URL_HOST)
        ) {
            $id = url_to_postid($url);
        }
    }
    $owner = absint(get_post_meta($id, "_autobus_route_id", true));
    return $id &&
        "page" === get_post_type($id) &&
        "templates/timetable.php" === get_page_template_slug($id) &&
        (!$owner || $owner === (int) $route_id)
        ? $id
        : 0;
}
function agm_route_directions($page_id)
{
    return $page_id
        ? get_posts([
            "post_type" => "agm_departure",
            "post_status" => "any",
            "numberposts" => -1,
            "orderby" => "menu_order",
            "order" => "ASC",
            "meta_key" => "_autobus_page_id",
            "meta_value" => $page_id,
        ])
        : [];
}
function agm_direction_editor($index, $direction = null, $values = null)
{
    $prefix = "agm_directions[" . $index . "]";
    echo '<fieldset class="agm-direction"><legend>Направление движения</legend><input type="hidden" name="' .
        esc_attr($prefix . "[id]") .
        '" value="' .
        esc_attr($values["id"] ?? ($direction->ID ?? 0)) .
        '">';
    echo '<div class="agm-direction-top"><label>Название направления<input class="widefat" type="text" name="' .
        esc_attr($prefix . "[title]") .
        '" value="' .
        esc_attr($values["title"] ?? ($direction->post_title ?? "")) .
        '" placeholder="Например: 115-й микрорайон"></label><button class="button-link-delete" type="button" data-remove-direction>Удалить направление</button></div><div class="agm-direction-days">';
    foreach (["weekday", "weekend", "saturday", "sunday"] as $day) {
        agm_direction_times_input($prefix, $day, $direction, $values);
    }
    echo '</div><details><summary>Другие варианты дней</summary><div class="agm-direction-days">';
    foreach (
        array_diff(array_keys(agm_timetable_day_schema()), [
            "weekday",
            "weekend",
            "saturday",
            "sunday",
        ])
        as $day
    ) {
        agm_direction_times_input($prefix, $day, $direction, $values);
    }
    echo '</div></details><div class="agm-direction-order"><button class="button button-small" type="button" data-move-direction="up">Выше</button><button class="button button-small" type="button" data-move-direction="down">Ниже</button></div></fieldset>';
}
function agm_direction_times_input($prefix, $day, $direction, $values = null)
{
    $value =
        $values !== null
            ? $values[$day] ?? ""
            : ($direction
                ? get_post_meta($direction->ID, "_autobus_" . $day, true)
                : "");
    echo "<label>" .
        esc_html(agm_timetable_day_schema()[$day]) .
        '<textarea class="widefat" rows="4" name="' .
        esc_attr($prefix . "[" . $day . "]") .
        '" placeholder="Пример: 06:00 06:15 06:30" data-route-times>' .
        esc_textarea($value) .
        '</textarea><span class="description agm-time-status" data-route-time-status></span></label>';
}
add_filter(
    "use_block_editor_for_post_type",
    function ($enabled, $type) {
        return "agm_route" === $type ? false : $enabled;
    },
    10,
    2,
);
add_action(
    "init",
    function () {
        foreach (
            ["editor", "excerpt", "thumbnail", "custom-fields"]
            as $feature
        ) {
            remove_post_type_support("agm_route", $feature);
        }
    },
    20,
);
add_filter(
    "enter_title_here",
    function ($title, $post) {
        return "agm_route" === $post->post_type
            ? "Путь маршрута, например: 115 микрорайон — ул. Советская"
            : $title;
    },
    10,
    2,
);
add_action(
    "admin_menu",
    function () {
        remove_menu_page("edit.php?post_type=agm_departure");
    },
    99,
);
add_action("add_meta_boxes_agm_route", function ($post) {
    add_meta_box(
        "agm_route_schedule",
        "Направления и время отправления",
        function ($post) {
            wp_nonce_field("agm_route_save", "agm_route_nonce");
            echo '<input type="hidden" name="agm_route_editor" value="1"><p>Каждый заполненный вариант дней показывается на сайте отдельной кнопкой: <strong>«Будни», «Выходные», «Суббота», «Воскресенье»</strong> и другие. Общий выходной график больше не скрывается отдельными днями. Если он не нужен, очистите только его поле и сохраните маршрут. Пустое поле не добавляет кнопку; серый пример в нём не является расписанием. Рейсы «Ежедневно» также включаются в каждый заполненный график отдельных дней.</p><p>Время — через пробел или с новой строки: <strong>06:00 06:15 06:30</strong>. Сохранение кнопкой «Обновить» или «Опубликовать» сразу меняет график на сайте. Для изменения в будущем используйте «Маршруты → Планировщик».</p>';
            $page_id = agm_route_page_id($post->ID);
            if ($page_id && "publish" === get_post_status($page_id)) {
                echo '<p><a href="' .
                    esc_url(get_permalink($page_id)) .
                    '" target="_blank" rel="noopener">Посмотреть график маршрута</a></p>';
            }
            echo '<div id="agm-direction-list">';
            $directions = agm_route_directions($page_id);
            foreach ($directions as $index => $direction) {
                agm_direction_editor($index, $direction);
            }
            if (!$directions) {
                agm_direction_editor(0);
            }
            echo '</div><p><button type="button" class="button" id="agm-add-direction">Добавить направление</button></p><template id="agm-direction-template">';
            agm_direction_editor("__INDEX__");
            echo "</template>";
            echo '<details><summary>Примечание к графику</summary><p><textarea class="widefat" rows="3" name="agm_route_note">' .
                esc_textarea(
                    $page_id
                        ? get_post_meta($page_id, "_autobus_tt_note", true)
                        : "",
                ) .
                "</textarea></p></details>";
        },
        "agm_route",
        "normal",
        "default",
    );
});
add_action("admin_enqueue_scripts", function () {
    $screen = get_current_screen();
    if (
        !$screen ||
        "agm_route" !== $screen->post_type ||
        "post" !== $screen->base
    ) {
        return;
    }
    $base = dirname(__DIR__);
    wp_enqueue_script(
        "agm-route-editor",
        plugins_url("assets/route-editor.js", $base . "/autobus03-content.php"),
        [],
        filemtime($base . "/assets/route-editor.js"),
        true,
    );
    wp_enqueue_style(
        "agm-route-editor",
        plugins_url(
            "assets/route-editor.css",
            $base . "/autobus03-content.php",
        ),
        [],
        filemtime($base . "/assets/route-editor.css"),
    );
});

function agm_route_write_post($data)
{
    $before = $GLOBALS["agm_route_syncing"] ?? false;
    $GLOBALS["agm_route_syncing"] = true;
    try {
        return !empty($data["ID"])
            ? wp_update_post(wp_slash($data), true)
            : wp_insert_post(wp_slash($data), true);
    } finally {
        $GLOBALS["agm_route_syncing"] = $before;
    }
}
// Одна проверка времени и принадлежности направлений для обычного редактора и планировщика.
function agm_validate_direction_rows($rows, $allowed_ids = [])
{
    $clean = [];
    $seen = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            return new WP_Error("directions", "Некорректное направление.");
        }
        if (!is_scalar($row["id"] ?? 0) || !is_scalar($row["title"] ?? "")) {
            return new WP_Error(
                "directions",
                "Проверьте название направления.",
            );
        }
        $id = absint($row["id"] ?? 0);
        if ($id && (!in_array($id, $allowed_ids, true) || isset($seen[$id]))) {
            return new WP_Error(
                "ownership",
                "Направление не принадлежит этому маршруту. Перезагрузите страницу.",
            );
        }
        $title = sanitize_text_field($row["title"] ?? "");
        $times = [];
        $has_times = false;
        foreach (agm_timetable_day_schema() as $day => $label) {
            $raw = $row[$day] ?? "";
            if (!is_scalar($raw)) {
                return new WP_Error("times", "Некорректное время отправления.");
            }
            foreach (
                preg_split(
                    "/[\s,;]+/u",
                    trim((string) $raw),
                    -1,
                    PREG_SPLIT_NO_EMPTY,
                )
                as $token
            ) {
                if (!preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $token)) {
                    return new WP_Error(
                        "times",
                        "График не изменён: укажите время в формате ЧЧ:ММ, например 06:15.",
                    );
                }
            }
            $times[$day] = agm_sanitize_times($raw);
            $has_times = $has_times || "" !== $times[$day];
        }
        if ("" === $title && !$has_times && !$id) {
            continue;
        }
        if ("" === $title) {
            return new WP_Error(
                "title",
                "График не изменён: заполните название направления.",
            );
        }
        if ($id) {
            $seen[$id] = true;
        }
        $clean[] = ["id" => $id, "title" => $title, "times" => $times];
    }
    return $clean;
}
function agm_save_route_schedule($route_id, $rows, $note = "")
{
    $route = get_post($route_id);
    if (!$route || "agm_route" !== $route->post_type) {
        return new WP_Error("route", "Маршрут не найден.");
    }
    $page_id = agm_route_page_id($route_id);
    $existing = agm_route_directions($page_id);
    $allowed_ids = array_map("intval", wp_list_pluck($existing, "ID"));
    $clean = agm_validate_direction_rows($rows, $allowed_ids);
    if (is_wp_error($clean)) {
        return $clean;
    }
    $seen = array_fill_keys(array_filter(wp_list_pluck($clean, "id")), true);
    $number = get_post_meta($route_id, "_autobus_number", true);
    if ("" === trim($number)) {
        return new WP_Error(
            "number",
            "График не изменён: заполните номер маршрута.",
        );
    }
    $status = "publish" === $route->post_status ? "publish" : "draft";
    $page_data = [
        "post_type" => "page",
        "post_title" => $route->post_title,
        "post_status" => $status,
    ];
    if ($page_id) {
        $page_data["ID"] = $page_id;
    } else {
        $parent = get_page_by_path("schedule");
        $page_data["post_parent"] = $parent ? $parent->ID : 0;
        $page_data["post_name"] = "route-" . $route_id;
    }
    $page_id = agm_route_write_post($page_data);
    if (is_wp_error($page_id)) {
        return $page_id;
    }
    update_post_meta($page_id, "_wp_page_template", "templates/timetable.php");
    update_post_meta($page_id, "_autobus_route_id", $route_id);
    update_post_meta($route_id, "_autobus_timetable_page_id", $page_id);
    update_post_meta($route_id, "_autobus_url", get_permalink($page_id));
    update_post_meta($page_id, "_autobus_tt_number", $number);
    update_post_meta(
        $page_id,
        "_autobus_tt_badge",
        "summer" === get_post_meta($route_id, "_autobus_group", true)
            ? "Дачный маршрут"
            : "Городской регулярный маршрут",
    );
    if (!get_post_meta($page_id, "_autobus_tt_description", true)) {
        update_post_meta(
            $page_id,
            "_autobus_tt_description",
            "г. Улан-Удэ · МУП «Городские маршруты»",
        );
    }
    update_post_meta(
        $page_id,
        "_autobus_tt_note",
        sanitize_textarea_field($note),
    );
    foreach ($clean as $order => $direction) {
        $data = [
            "post_type" => "agm_departure",
            "post_title" => $direction["title"],
            "post_status" => $status,
            "menu_order" => $order,
        ];
        if ($direction["id"]) {
            $data["ID"] = $direction["id"];
        }
        $id = agm_route_write_post($data);
        if (is_wp_error($id)) {
            return $id;
        }
        update_post_meta($id, "_autobus_page_id", $page_id);
        update_post_meta($id, "_autobus_route_id", $route_id);
        foreach ($direction["times"] as $day => $times) {
            update_post_meta($id, "_autobus_" . $day, $times);
        }
    }
    foreach (array_diff($allowed_ids, array_keys($seen)) as $id) {
        $before = $GLOBALS["agm_route_syncing"] ?? false;
        $GLOBALS["agm_route_syncing"] = true;
        try {
            wp_trash_post($id);
        } finally {
            $GLOBALS["agm_route_syncing"] = $before;
        }
    }
    if (!get_post_meta($route_id, "_autobus_kind", true)) {
        update_post_meta(
            $route_id,
            "_autobus_kind",
            "summer" === get_post_meta($route_id, "_autobus_group", true)
                ? "Дачный сезон"
                : "Городской",
        );
    }
    // Дата относится к сохранению расписания, а не к открытию страницы посетителем.
    update_post_meta($page_id, '_autobus_tt_imported', agm_schedule_updated_label());
    return $page_id;
}
function agm_schedule_updated_label($timestamp = null)
{
    return 'Обновлено: ' . wp_date('d.m.Y, H:i', $timestamp ?? time(), wp_timezone()) . ' · ' . agm_planner_main_label();
}
// После общего сохранения метаданных: обновление направлений не запускает повторное сохранение маршрута.
add_action(
    "save_post",
    function ($route_id) {
        if (
            "agm_route" !== get_post_type($route_id) ||
            wp_is_post_revision($route_id) ||
            (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) ||
            !current_user_can("edit_post", $route_id) ||
            empty($_POST["agm_route_editor"]) ||
            !isset($_POST["agm_route_nonce"]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST["agm_route_nonce"])),
                "agm_route_save",
            )
        ) {
            return;
        }
        $rows =
            isset($_POST["agm_directions"]) &&
            is_array($_POST["agm_directions"])
                ? wp_unslash($_POST["agm_directions"])
                : [];
        $result = agm_save_route_schedule(
            $route_id,
            $rows,
            wp_unslash($_POST["agm_route_note"] ?? ""),
        );
        if (is_wp_error($result)) {
            set_transient(
                "agm_route_error_" . get_current_user_id(),
                $result->get_error_message(),
                120,
            );
        }
    },
    20,
);
add_action("admin_notices", function () {
    $message = get_transient("agm_route_error_" . get_current_user_id());
    if ($message) {
        delete_transient("agm_route_error_" . get_current_user_id());
        echo '<div class="notice notice-error"><p>' .
            esc_html($message) .
            "</p></div>";
    }
});
add_action(
    "transition_post_status",
    function ($new, $old, $post) {
        if ("agm_route" !== $post->post_type || $new === $old) {
            return;
        }
        $page_id = agm_route_page_id($post->ID);
        $status = "publish" === $new ? "publish" : "draft";
        if ($page_id && get_post_status($page_id) !== $status) {
            agm_route_write_post(["ID" => $page_id, "post_status" => $status]);
        }
        foreach (agm_route_directions($page_id) as $direction) {
            if ($direction->post_status !== $status) {
                agm_route_write_post([
                    "ID" => $direction->ID,
                    "post_status" => $status,
                ]);
            }
        }
    },
    20,
    3,
);
