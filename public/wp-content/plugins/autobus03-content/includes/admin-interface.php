<?php
defined("ABSPATH") || exit();

// Эти группы меняют только меню. Данные остаются в прежних типах записей WordPress.
add_filter(
    "register_post_type_args",
    function ($args, $type) {
        $parents = [
            "agm_leader" => "agm_stat",
            "agm_history" => "agm_stat",
            "agm_slide" => "agm_stat",
            "agm_document" => "agm_stat",
            "agm_contact_leader" => "agm_contact",
            "agm_benefit" => "agm_job",
        ];
        if (isset($parents[$type])) {
            $args["show_in_menu"] = "edit.php?post_type=" . $parents[$type];
        }
        $menus = [
            "agm_stat" => "О предприятии",
            "agm_contact" => "Контакты",
            "agm_job" => "Вакансии",
        ];
        if (isset($menus[$type])) {
            $args["labels"]["menu_name"] = $menus[$type];
        }
        return $args;
    },
    20,
    2,
);

add_filter("post_type_labels_post", function ($labels) {
    foreach (
        [
            "name" => "Новости",
            "singular_name" => "Новость",
            "menu_name" => "Новости",
            "add_new" => "Добавить новость",
            "add_new_item" => "Добавить новость",
            "edit_item" => "Редактировать новость",
            "all_items" => "Все новости",
        ]
        as $key => $label
    ) {
        $labels->$key = $label;
    }
    return $labels;
});

add_action("admin_menu", function () {
    add_menu_page(
        "Обзор сайта",
        "Обзор сайта",
        "agm_view_overview",
        "agm-site-overview",
        "agm_site_overview",
        "dashicons-chart-area",
        2,
    );
});

function agm_site_overview()
{
    if (!current_user_can("agm_view_overview")) {
        wp_die("Нет доступа к обзору сайта.", "", ["response" => 403]);
    }
    echo '<div class="wrap agm-editor-home"><h1>Обзор сайта</h1><p>Сводка показывает содержимое и ожидающие задания. Это окно только для просмотра.</p><div class="agm-overview-counts">';
    foreach (
        [
            "post" => "Новости",
            "agm_route" => "Маршруты",
            "agm_job" => "Вакансии",
            "agm_document" => "Документы",
        ]
        as $type => $label
    ) {
        echo "<div><strong>" .
            (int) wp_count_posts($type)->publish .
            "</strong><span>" .
            esc_html($label) .
            "</span></div>";
    }
    echo '</div><h2>Новости, ожидающие публикации</h2><table class="widefat striped"><thead><tr><th>Новость</th><th>Время Улан-Удэ</th></tr></thead><tbody>';
    $news = get_posts([
        "post_type" => "post",
        "post_status" => "future",
        "numberposts" => -1,
        "orderby" => "date",
        "order" => "ASC",
    ]);
    foreach ($news as $post) {
        echo "<tr><td>" .
            esc_html($post->post_title) .
            "</td><td>" .
            esc_html(
                wp_date("d.m.Y H:i", strtotime($post->post_date_gmt . " UTC")),
            ) .
            "</td></tr>";
    }
    if (!$news) {
        echo '<tr><td colspan="2">Нет ожидающих новостей.</td></tr>';
    }
    echo '</tbody></table><h2>Подготовленные изменения расписания</h2><table class="widefat striped"><thead><tr><th>Маршрут</th><th>Дата и состояние</th><th>Подготовленный график</th></tr></thead><tbody>';
    $plans = get_posts([
        "post_type" => "agm_route_plan",
        "post_status" => "draft",
        "numberposts" => -1,
        "meta_key" => "_agm_plan_due",
        "orderby" => "meta_value_num",
        "order" => "ASC",
        "meta_query" => [
            [
                "key" => "_agm_plan_state",
                "value" => ["pending", "error"],
                "compare" => "IN",
            ],
        ],
    ]);
    foreach ($plans as $plan) {
        $payload = get_post_meta($plan->ID, "_agm_plan_payload", true);
        echo "<tr><td>" .
            esc_html(
                $payload["title"] ??
                    get_the_title(
                        (int) get_post_meta(
                            $plan->ID,
                            "_agm_plan_target",
                            true,
                        ),
                    ),
            ) .
            "</td><td>" .
            esc_html(
                wp_date(
                    "d.m.Y H:i",
                    (int) get_post_meta($plan->ID, "_agm_plan_due", true),
                ),
            ) .
            "<br>" .
            ("error" === get_post_meta($plan->ID, "_agm_plan_state", true)
                ? "Требует проверки"
                : "Ожидает применения") .
            "</td><td><details><summary>Посмотреть</summary>";
        foreach ($payload["rows"] ?? [] as $row) {
            echo "<p><strong>" .
                esc_html($row["title"] ?? "") .
                "</strong></p>";
            foreach (agm_timetable_day_schema() as $day => $label) {
                if (!empty($row[$day])) {
                    echo "<p>" .
                        esc_html(
                            $label . ": " . str_replace("\n", " ", $row[$day]),
                        ) .
                        "</p>";
                }
            }
        }
        echo "</details></td></tr>";
    }
    if (!$plans) {
        echo '<tr><td colspan="3">Нет ожидающих изменений.</td></tr>';
    }
    echo "</tbody></table><p>Планировщики используют время Улан-Удэ. Для запуска без посетителей на хостинге нужно выполнять wp-cron.php каждую минуту.</p></div>";
}

add_action("wp_dashboard_setup", function () {
    remove_meta_box("dashboard_primary", "dashboard", "side");
    remove_meta_box("dashboard_quick_press", "dashboard", "side");
    wp_add_dashboard_widget("agm-site-start", "Управление сайтом", function () {
        echo '<p>Начните с нужного раздела. Черновики посетителям не видны.</p><div class="agm-dashboard-links">';
        $links = [
            "Новости" => "edit.php",
            "Маршруты и графики" => "edit.php?post_type=agm_route",
            "Вакансии" => "edit.php?post_type=agm_job",
            "Контакты" => "edit.php?post_type=agm_contact",
            "Тарифы" => "edit.php?post_type=agm_tariff",
        ];
        foreach ($links as $label => $path) {
            if (agm_staff_link_allowed($path)) {
                echo '<a href="' .
                    esc_url(admin_url($path)) .
                    '">' .
                    esc_html($label) .
                    "</a>";
            }
        }
        if (current_user_can("agm_view_overview")) {
            echo '<a href="' .
                esc_url(admin_url("admin.php?page=agm-site-overview")) .
                '">Обзор сайта</a>';
        }
        echo '<a href="' .
            esc_url(admin_url("admin.php?page=agm-editor-guide")) .
            '">Инструкция редактору</a></div>';
    });
});

add_action(
    "add_meta_boxes",
    function ($type) {
        if (!isset(agm_types()[$type])) {
            return;
        }
        remove_meta_box("postcustom", $type, "normal");
        global $wp_meta_boxes;
        if (isset($wp_meta_boxes[$type]["normal"]["core"]["postexcerpt"])) {
            $wp_meta_boxes[$type]["normal"]["core"]["postexcerpt"]["title"] =
                "Краткое описание карточки";
        }
    },
    30,
);

add_action("admin_notices", function () {
    $screen = get_current_screen();
    if (!$screen || $screen->base !== "post" || $screen->post_type !== "page") {
        return;
    }
    $post = $GLOBALS["post"] ?? null;
    if (!$post) {
        return;
    }
    if (get_page_template_slug($post->ID) === "templates/timetable.php") {
        $route = (int) get_post_meta($post->ID, "_autobus_route_id", true);
        echo '<div class="notice notice-info"><p>Этот график создаётся автоматически. Время и направления меняйте в разделе «Маршруты»: <a href="' .
            esc_url(get_edit_post_link($route)) .
            '">открыть маршрут</a>.</p></div>';
    } elseif (
        in_array(
            $post->post_name,
            ["services", "schedule", "about", "vacancy", "contact"],
            true,
        ) ||
        $post->ID === (int) get_option("page_on_front")
    ) {
        echo '<div class="notice notice-info"><p>Блоки этой страницы собираются из карточек и настроек темы. Где изменить текст или фотографию: <a href="' .
            esc_url(admin_url("admin.php?page=agm-editor-guide")) .
            '">инструкция редактору</a>.</p></div>';
    }
});

add_action("admin_enqueue_scripts", function ($hook) {
    if (
        !in_array(
            $hook,
            [
                "index.php",
                "toplevel_page_agm-editor-guide",
                "toplevel_page_agm-site-overview",
            ],
            true,
        )
    ) {
        return;
    }
    $file = dirname(__DIR__) . "/assets/admin-interface.css";
    wp_enqueue_style(
        "agm-admin-interface",
        plugins_url(
            "assets/admin-interface.css",
            dirname(__DIR__) . "/autobus03-content.php",
        ),
        [],
        (string) filemtime($file),
    );
});

// Сотрудникам не нужны пустые служебные разделы; администратору остаётся стандартное меню.
add_action('admin_menu', function () {
    if (!current_user_can('manage_options')) {
        remove_menu_page('tools.php');
        remove_menu_page('edit-comments.php');
    }
}, 110);
add_action('admin_bar_menu', function ($bar) {
    if (!current_user_can('manage_options')) $bar->remove_node('comments');
}, 110);
