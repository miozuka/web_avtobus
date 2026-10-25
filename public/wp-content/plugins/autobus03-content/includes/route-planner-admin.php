<?php
defined("ABSPATH") || exit();

add_action("admin_menu", function () {
    add_submenu_page(
        "edit.php?post_type=agm_route",
        "Планировщик расписаний",
        "Планировщик",
        "publish_agm_routes",
        "agm-route-planner",
        "agm_plan_admin_page",
    );
});
add_action("admin_enqueue_scripts", function ($hook) {
    if ("agm_route_page_agm-route-planner" !== $hook) {
        return;
    }
    $base = dirname(__DIR__);
    foreach (["css" => "style", "js" => "script"] as $extension => $kind) {
        $path = "/assets/route-editor." . $extension;
        $url = plugins_url(
            "assets/route-editor." . $extension,
            $base . "/autobus03-content.php",
        );
        if ("style" === $kind) {
            wp_enqueue_style(
                "agm-route-editor",
                $url,
                [],
                filemtime($base . $path),
            );
        } else {
            wp_enqueue_script(
                "agm-route-editor",
                $url,
                [],
                filemtime($base . $path),
                true,
            );
        }
    }
});
add_action("add_meta_boxes_agm_route", function () {
    add_meta_box(
        "agm-route-planner-link",
        "Изменить позже",
        function ($post) {
            $pending = agm_plan_pending($post->ID);
            $plan = $pending ? $pending[0]->ID : 0;
            echo '<p>Чтобы сохранить действующее расписание до нужной даты, подготовьте изменения в планировщике.</p><p><a class="button" href="' .
                esc_url(
                    agm_plan_url(
                        $plan
                            ? ["plan" => $plan]
                            : ["route" => $post->ID, "prepare" => 1],
                    ),
                ) .
                '">' .
                ($plan
                    ? "Открыть отложенное изменение"
                    : "Подготовить изменение") .
                "</a></p>";
            echo '<p><a class="button" href="' .
                esc_url(agm_plan_url(["route" => $post->ID, "prepare" => 1])) .
                '">Добавить ещё изменение</a></p>';
            if ($plan) {
                echo "<p>Заданий: " .
                    count($pending) .
                    ". Ближайшее: <strong>" .
                    esc_html(
                        wp_date(
                            "d.m.Y H:i",
                            (int) get_post_meta($plan, "_agm_plan_due", true),
                        ),
                    ) .
                    "</strong>. Если изменить действующий маршрут сейчас, задание потребует повторной проверки.</p>";
            }
        },
        "agm_route",
        "side",
    );
});
add_action("admin_post_agm_save_route_plan", function () {
    check_admin_referer("agm_save_route_plan");
    $plan_id = absint($_POST["agm_plan_id"] ?? 0);
    $route_id = absint($_POST["agm_target"] ?? 0);
    if (isset($_POST["agm_cancel_plan"])) {
        $result = agm_plan_cancel($plan_id);
    } else {
        $day =
            isset($_POST["agm_plan_day"]) && is_string($_POST["agm_plan_day"])
                ? sanitize_text_field(wp_unslash($_POST["agm_plan_day"]))
                : "";
        $clock =
            isset($_POST["agm_plan_time"]) && is_string($_POST["agm_plan_time"])
                ? sanitize_text_field(wp_unslash($_POST["agm_plan_time"]))
                : "";
        $date_value =
            $day && $clock
                ? $day . "T" . $clock
                : (is_string($_POST["agm_due"] ?? "")
                    ? wp_unslash($_POST["agm_due"] ?? "")
                    : "");
        $due = agm_plan_parse_date($date_value);
        $payload = [
            "title" => wp_unslash($_POST["agm_plan_title"] ?? ""),
            "card" => wp_unslash($_POST["agm"] ?? []),
            "note" => wp_unslash($_POST["agm_route_note"] ?? ""),
            "rows" => wp_unslash($_POST["agm_directions"] ?? []),
        ];
        $result = is_wp_error($due)
            ? $due
            : agm_plan_save($route_id, $payload, $due, $plan_id);
    }
    if (is_wp_error($result)) {
        set_transient(
            "agm_plan_feedback_" . get_current_user_id(),
            [
                "error" => $result->get_error_message(),
                "form" => isset($payload) ? $payload : null,
                "due" => $date_value ?? "",
            ],
            300,
        );
        wp_safe_redirect(
            agm_plan_url(
                $plan_id
                    ? ["plan" => $plan_id]
                    : ["route" => $route_id, "prepare" => 1],
            ),
        );
    } else {
        set_transient(
            "agm_plan_feedback_" . get_current_user_id(),
            [
                "message" => isset($_POST["agm_cancel_plan"])
                    ? "Задание отменено. Действующий график не изменён."
                    : "Изменения запланированы. Действующий график пока не изменён.",
            ],
            300,
        );
        wp_safe_redirect(
            isset($_POST["agm_plan_more"]) && !isset($_POST["agm_cancel_plan"])
                ? agm_plan_url([
                    "route" => (int) get_post_meta(
                        $result,
                        "_agm_plan_target",
                        true,
                    ),
                    "prepare" => 1,
                    "copy" => $result,
                ])
                : agm_plan_url(),
        );
    }
    exit();
});
function agm_plan_admin_page()
{
    if (!current_user_can("publish_agm_routes")) {
        return;
    }
    $feedback = get_transient("agm_plan_feedback_" . get_current_user_id());
    delete_transient("agm_plan_feedback_" . get_current_user_id());
    echo '<div class="wrap agm-planner"><h1>Планировщик маршрутов и графиков</h1><p>Добавьте новый маршрут или подготовьте изменение существующего. Выберите день и время — до этой даты действует прежний график. Для одного маршрута можно задать несколько версий на разные даты: они применяются по очереди, каждая полностью заменяет предыдущую.</p><p><a class="button" href="' .
        esc_url(agm_news_planner_url()) .
        '">Планировщик новостей</a></p>';
    if ($feedback) {
        echo '<div class="notice ' .
            (isset($feedback["error"]) ? "notice-error" : "notice-success") .
            '"><p>' .
            esc_html($feedback["error"] ?? $feedback["message"]) .
            "</p></div>";
    }
    agm_planner_clock_panel();
    echo "<details><summary>Настройки запуска планировщика</summary><p>Основной пояс задаётся в «Настройки → Общие → Часовой пояс»: используйте UTC+8. В планировщике он подписан «Улан-Удэ».</p><p>На хостинге администратору нужно настроить запуск задач WordPress каждую минуту. Без этого задание может выполниться при следующем посещении сайта после выбранного времени. На выключенном локальном компьютере задачи не выполняются.</p></details>";
    $plan_id = absint($_GET["plan"] ?? 0);
    $route_id = absint($_GET["route"] ?? 0);
    $payload = null;
    $due = "";
    if ($plan_id) {
        $route_id = (int) get_post_meta($plan_id, "_agm_plan_target", true);
        if (
            "agm_route_plan" !== get_post_type($plan_id) ||
            !current_user_can("edit_post", $plan_id) ||
            !agm_plan_can_edit($route_id) ||
            !in_array(
                get_post_meta($plan_id, "_agm_plan_state", true),
                ["pending", "error"],
                true,
            )
        ) {
            echo "<p>Задание недоступно.</p></div>";
            return;
        }
        $payload = agm_plan_match_rows(
            $route_id,
            get_post_meta($plan_id, "_agm_plan_payload", true),
        );
        $due = wp_date(
            "Y-m-d\TH:i",
            (int) get_post_meta($plan_id, "_agm_plan_due", true),
        );
        $error = get_post_meta($plan_id, "_agm_plan_error", true);
        if ($error) {
            echo '<div class="notice notice-error inline"><p>' .
                esc_html($error) .
                "</p></div>";
        }
    } elseif (isset($_GET["prepare"])) {
        if (!agm_plan_can_edit($route_id)) {
            echo "<p>Нет доступа к маршруту.</p></div>";
            return;
        }
        $payload = $route_id
            ? agm_plan_snapshot($route_id)
            : [
                "title" => "",
                "card" => [
                    "number" => "",
                    "group" => "city",
                    "kind" => "",
                    "stops" => "",
                ],
                "rows" => [],
                "note" => "",
            ];
        if ($route_id && !$payload) {
            echo "<p>Маршрут не найден.</p></div>";
            return;
        }
        $copy = absint($_GET["copy"] ?? 0);
        if ($copy) {
            if (
                "agm_route_plan" !== get_post_type($copy) ||
                !current_user_can("edit_post", $copy) ||
                (int) get_post_meta($copy, "_agm_plan_target", true) !==
                    $route_id
            ) {
                echo "<p>Эта версия недоступна для копирования.</p></div>";
                return;
            }
            $payload = agm_plan_match_rows(
                $route_id,
                get_post_meta($copy, "_agm_plan_payload", true),
            );
            echo "<p>Скопирована подготовленная версия: <strong>" .
                esc_html(get_the_title($copy)) .
                "</strong>. Измените график и выберите новую дату.</p>";
        }
        $pending = $route_id ? agm_plan_pending($route_id) : [];
        if ($pending) {
            echo "<p>Для этого маршрута уже подготовлено заданий: <strong>" .
                count($pending) .
                "</strong>. Можно добавить ещё одно на другое время.</p>";
        }
    }
    if ($payload !== null) {
        if (!empty($feedback["form"])) {
            $payload = $feedback["form"];
            $due = $feedback["due"];
        }
        agm_plan_render_form($route_id, $plan_id, $payload, $due);
    } else {
        echo '<h2>Подготовить изменение</h2><form method="get"><input type="hidden" name="post_type" value="agm_route"><input type="hidden" name="page" value="agm-route-planner"><input type="hidden" name="prepare" value="1"><label for="agm-target-picker">Маршрут</label> <select id="agm-target-picker" name="route"><option value="0">Новый маршрут</option>';
        foreach (
            get_posts([
                "post_type" => "agm_route",
                "post_status" => ["publish", "draft", "pending"],
                "numberposts" => -1,
                "orderby" => "title",
                "order" => "ASC",
            ])
            as $route
        ) {
            if (!agm_plan_can_edit($route->ID)) {
                continue;
            }
            echo '<option value="' .
                (int) $route->ID .
                '">№' .
                esc_html(
                    get_post_meta($route->ID, "_autobus_number", true) .
                        " — " .
                        $route->post_title .
                        ("publish" === $route->post_status
                            ? ""
                            : " (ещё не опубликован)"),
                ) .
                "</option>";
        }
        echo '</select> <button class="button button-primary">Подготовить</button></form>';
    }
    echo '<h2>Задания</h2><table class="widefat striped agm-plans-table"><thead><tr><th scope="col">Маршрут</th><th scope="col">Когда</th><th scope="col">Состояние</th><th scope="col">Действия</th></tr></thead><tbody>';
    $plans = get_posts([
        "post_type" => "agm_route_plan",
        "post_status" => "draft",
        "numberposts" => 50,
        "orderby" => "ID",
        "order" => "DESC",
    ]);
    $upcoming = get_posts([
        "post_type" => "agm_route_plan",
        "post_status" => "draft",
        "numberposts" => -1,
        "meta_key" => "_agm_plan_state",
        "meta_value" => ["pending", "error"],
        "meta_compare" => "IN",
        "meta_query" => [],
        "orderby" => "ID",
        "order" => "DESC",
    ]);
    $plans = array_values(
        array_reduce(
            array_merge($upcoming, $plans),
            function ($all, $plan) {
                $all[$plan->ID] = $plan;
                return $all;
            },
            [],
        ),
    );
    usort($plans, function ($a, $b) {
        $a_active = in_array(
            get_post_meta($a->ID, "_agm_plan_state", true),
            ["pending", "error"],
            true,
        );
        $b_active = in_array(
            get_post_meta($b->ID, "_agm_plan_state", true),
            ["pending", "error"],
            true,
        );
        if ($a_active !== $b_active) {
            return $a_active ? -1 : 1;
        }
        return $a_active
            ? ((int) get_post_meta($a->ID, "_agm_plan_due", true) <=>
            (int) get_post_meta($b->ID, "_agm_plan_due", true) ?:
                $a->ID <=> $b->ID)
            : $b->ID <=> $a->ID;
    });
    $visible = 0;
    foreach ($plans as $plan) {
        $target = (int) get_post_meta($plan->ID, "_agm_plan_target", true);
        if (
            !current_user_can("edit_post", $plan->ID) ||
            !agm_plan_can_edit($target)
        ) {
            continue;
        }
        $visible++;
        $state = get_post_meta($plan->ID, "_agm_plan_state", true);
        $time = (int) get_post_meta($plan->ID, "_agm_plan_due", true);
        $labels = [
            "pending" =>
                $time < time() ? "Ожидает запуска WordPress" : "Запланировано",
            "applied" => "Применено",
            "cancelled" => "Отменено",
            "error" => "Нужна проверка",
        ];
        echo '<tr><td data-label="Маршрут">' .
            esc_html($plan->post_title) .
            (get_post_meta($plan->ID, "_agm_plan_new", true) || !$target
                ? " (новый маршрут)"
                : "") .
            '</td><td data-label="Когда" class="agm-plan-when">';
        agm_planner_time($time);
        echo '</td><td data-label="Состояние">' .
            esc_html($labels[$state] ?? $state);
        $error = get_post_meta($plan->ID, "_agm_plan_error", true);
        if ($error) {
            echo "<br>" . esc_html($error);
        }
        echo '</td><td data-label="Действия" class="agm-plan-actions">';
        if (in_array($state, ["pending", "error"], true)) {
            echo '<a href="' .
                esc_url(agm_plan_url(["plan" => $plan->ID])) .
                '">Изменить / отменить</a>';
            echo '<br><a href="' .
                esc_url(
                    agm_plan_url([
                        "route" => $target,
                        "prepare" => 1,
                        "copy" => $plan->ID,
                    ]),
                ) .
                '">Ещё версия на другую дату</a>';
        } elseif ("applied" === $state) {
            echo '<a href="' .
                esc_url(
                    get_edit_post_link(
                        (int) get_post_meta(
                            $plan->ID,
                            "_agm_plan_result",
                            true,
                        ),
                    ),
                ) .
                '">Открыть маршрут</a>';
        }
        echo "</td></tr>";
    }
    if (!$visible) {
        echo '<tr><td colspan="4">Пока нет заданий.</td></tr>';
    }
    echo "</tbody></table><p>Показаны все предстоящие задания и последние 50 записей истории. Выполненные и отменённые задания сохраняются для проверки.</p><h2>Публикация новости по времени</h2><p>Откройте новую запись, подготовьте текст, фотографии и видео. В панели публикации нажмите на дату («Немедленно» / Immediately), выберите будущие дату и время, затем «Запланировать» / Schedule. Черновик или запланированная новость не видны посетителям до публикации. До наступления даты можно отредактировать запись, перенести дату или вернуть её в черновики.</p></div>";
}
function agm_plan_render_form($route_id, $plan_id, $payload, $due)
{
    // При ошибке возвращаем заполненные поля; нестандартные данные запроса не выводим в HTML.
    $scalar = function ($value) {
        return is_scalar($value) ? (string) $value : "";
    };
    $payload["title"] = $scalar($payload["title"] ?? "");
    $payload["note"] = $scalar($payload["note"] ?? "");
    $payload["card"] = is_array($payload["card"] ?? null)
        ? array_map($scalar, $payload["card"])
        : [];
    $payload["rows"] = is_array($payload["rows"] ?? null)
        ? array_map(function ($row) use ($scalar) {
            return is_array($row)
                ? array_map($scalar, $row)
                : ["id" => 0, "title" => ""];
        }, array_values($payload["rows"]))
        : [];
    echo "<h2>" .
        ($plan_id
            ? "Изменить задание"
            : ($route_id
                ? "Новая версия действующего маршрута"
                : "Новый маршрут")) .
        '</h2><form class="agm-plan-form" method="post" action="' .
        esc_url(admin_url("admin-post.php")) .
        '">';
    wp_nonce_field("agm_save_route_plan");
    echo '<input type="hidden" name="action" value="agm_save_route_plan"><input type="hidden" name="agm_plan_id" value="' .
        (int) $plan_id .
        '"><input type="hidden" name="agm_target" value="' .
        (int) $route_id .
        '"><div class="agm-plan-dates"><div><label for="agm-plan-day"><strong>День применения</strong></label><input required type="date" id="agm-plan-day" name="agm_plan_day" value="' .
        esc_attr(substr($due, 0, 10)) .
        '"></div><div><label for="agm-plan-time"><strong>Время (' .
        esc_html(agm_planner_main_label()) .
        ')</strong></label><input required type="time" id="agm-plan-time" name="agm_plan_time" value="' .
        esc_attr(substr($due, 11, 5)) .
        '"></div></div>';
    agm_planner_time_preview(
        "agm-plan-day",
        "agm-plan-time",
        substr($due, 0, 10),
        substr($due, 11, 5),
    );
    echo '<p><label for="agm-plan-title"><strong>Путь маршрута</strong></label><input required class="widefat" id="agm-plan-title" name="agm_plan_title" value="' .
        esc_attr($payload["title"]) .
        '"></p>';
    foreach (
        [
            "number" => "Номер маршрута",
            "kind" => "Подпись типа (необязательно)",
            "stops" => "Остановки для поиска (необязательно)",
        ]
        as $key => $label
    ) {
        if ("kind" === $key) {
            echo '<details class="agm-plan-extra"><summary>Дополнительные настройки маршрута</summary>';
        }
        echo '<p><label for="agm_' .
            esc_attr($key) .
            '"><strong>' .
            esc_html($label) .
            '</strong></label><input class="widefat" id="agm_' .
            esc_attr($key) .
            '" name="agm[' .
            esc_attr($key) .
            ']" value="' .
            esc_attr($payload["card"][$key] ?? "") .
            '"></p>';
        if ("stops" === $key) {
            echo "</details>";
        }
    }
    echo '<p><label for="agm_group"><strong>Тип маршрута</strong></label> <select id="agm_group" name="agm[group]">';
    foreach (
        ["city" => "Городской маршрут", "summer" => "Дачный маршрут"]
        as $key => $label
    ) {
        echo '<option value="' .
            esc_attr($key) .
            '" ' .
            selected($payload["card"]["group"] ?? "city", $key, false) .
            ">" .
            esc_html($label) .
            "</option>";
    }
    echo '</select></p><p>Время через пробел или с новой строки: 06:00 06:15. Каждое заполненное поле дней будет показано отдельной кнопкой. Общие выходные, суббота и воскресенье друг друга не скрывают. Серые примеры в пустых полях не сохраняются как рейсы. Рейсы «Ежедневно» добавляются к заполненным графикам отдельных дней.</p><div id="agm-direction-list">';
    $rows = $payload["rows"] ?: [["id" => 0, "title" => ""]];
    foreach ($rows as $index => $row) {
        // Тот же редактор направлений, но значения берутся из подготовленной версии, а не из базы.
        agm_direction_editor($index, null, $row);
    }
    echo '</div><p><button type="button" class="button" id="agm-add-direction">Добавить направление</button></p><template id="agm-direction-template">';
    agm_direction_editor("__INDEX__");
    echo '</template><p><label for="agm-plan-note"><strong>Примечание к графику</strong></label><textarea id="agm-plan-note" class="widefat" rows="3" name="agm_route_note">' .
        esc_textarea($payload["note"] ?? "") .
        '</textarea></p><p><strong>В указанное время эта версия будет опубликована целиком.</strong> Проверьте все направления и дни. Сохранение задания после ручной правки действующего маршрута подтверждает замену на подготовленную версию.</p><p><button class="button button-primary" type="submit">' .
        ($plan_id ? "Сохранить задание" : "Запланировать изменение") .
        '</button> <button class="button" type="submit" name="agm_plan_more" value="1">Сохранить и добавить ещё</button> <a class="button" href="' .
        esc_url(agm_plan_url()) .
        '">К списку заданий</a>';
    if ($plan_id) {
        echo ' <button class="button" type="submit" name="agm_cancel_plan" value="1" formnovalidate>Отменить задание</button>';
    }
    echo "</p></form>";
}
