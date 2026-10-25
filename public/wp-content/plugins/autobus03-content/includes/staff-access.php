<?php
defined("ABSPATH") || exit();

// Права разделены по содержимому и расписанию. Одного скрытого пункта меню для защиты недостаточно.
add_filter(
    "register_post_type_args",
    function ($args, $type) {
        if (!str_starts_with($type, "agm_")) {
            return $args;
        }
        $base = match ($type) {
            "agm_route" => ["agm_route", "agm_routes"],
            "agm_departure" => ["agm_departure", "agm_departures"],
            "agm_route_plan" => ["agm_route_plan", "agm_route_plans"],
            "agm_job", "agm_benefit" => ["agm_job", "agm_jobs"],
            default => ["agm_item", "agm_items"],
        };
        $args["capability_type"] = $base;
        $args["map_meta_cap"] = true;
        return $args;
    },
    10,
    2,
);

function agm_section_caps($plural, $publish = false)
{
    $caps = [
        "edit_" . $plural,
        "edit_others_" . $plural,
        "delete_" . $plural,
        "read_private_" . $plural,
    ];
    if ($publish) {
        $caps = array_merge($caps, [
            "publish_" . $plural,
            "edit_published_" . $plural,
            "edit_private_" . $plural,
            "delete_published_" . $plural,
            "delete_private_" . $plural,
            "delete_others_" . $plural,
        ]);
    }
    return array_fill_keys($caps, true);
}

// При изменении набора прав увеличьте номер версии. Пользователи и их материалы не удаляются.
add_action(
    "init",
    function () {
        if (get_option("agm_staff_access_version") === "1") {
            return;
        }
        $basic = ["read" => true, "agm_view_overview" => true];
        $drafts = array_merge(
            $basic,
            ["upload_files" => true],
            agm_section_caps("posts"),
            agm_section_caps("pages"),
            agm_section_caps("agm_items"),
            agm_section_caps("agm_jobs"),
        );
        $editor = array_merge(
            $basic,
            ["upload_files" => true, "edit_theme_options" => true],
            agm_section_caps("posts", true),
            agm_section_caps("pages", true),
            agm_section_caps("agm_items", true),
            agm_section_caps("agm_jobs", true),
        );
        $operator = array_merge(
            $basic,
            agm_section_caps("agm_routes", true),
            agm_section_caps("agm_route_plans", true),
        );
        foreach (
            [
                "agm_manager" => ["Руководитель", $basic],
                "agm_content_manager" => ["Контент-менеджер", $drafts],
                "agm_site_editor" => ["Редактор сайта", $editor],
                "agm_operator" => ["Оператор расписаний", $operator],
            ]
            as $name => [$label, $caps]
        ) {
            // Ручные настройки и удаление ролей администратором сохраняются при обновлении плагина.
            if (isset(get_option("agm_staff_role_overrides", [])[$name])) {
                continue;
            }
            $role = get_role($name);
            if (!$role) {
                add_role($name, $label, $caps);
            } else {
                foreach (array_keys($role->capabilities) as $cap) {
                    $role->remove_cap($cap);
                }
                foreach ($caps as $cap => $allowed) {
                    $role->add_cap($cap, $allowed);
                }
            }
        }
        $admin = get_role("administrator");
        if ($admin) {
            foreach (
                array_merge(
                    $editor,
                    $operator,
                    agm_section_caps("agm_departures", true),
                )
                as $cap => $allowed
            ) {
                $admin->add_cap($cap, $allowed);
            }
        }
        update_option("agm_staff_access_version", "1", false);
    },
    5,
);

// Страницы графиков создаются автоматически. Сотрудники меняют их через редактор маршрута.
add_filter(
    "map_meta_cap",
    function ($caps, $cap, $user_id, $args) {
        if (
            !in_array($cap, ["edit_post", "delete_post"], true) ||
            empty($args[0])
        ) {
            return $caps;
        }
        $post = get_post($args[0]);
        if (
            $post &&
            $post->post_type === "page" &&
            get_page_template_slug($post->ID) === "templates/timetable.php"
        ) {
            $user = get_userdata($user_id);
            if (!$user || !in_array("administrator", $user->roles, true)) {
                return ["do_not_allow"];
            }
        }
        return $caps;
    },
    10,
    4,
);

function agm_staff_link_allowed($path)
{
    parse_str(wp_parse_url($path, PHP_URL_QUERY) ?? "", $query);
    if (($query["page"] ?? "") === "agm-news-planner") {
        return current_user_can("publish_posts");
    }
    if (($query["page"] ?? "") === "agm-route-planner") {
        return current_user_can("publish_agm_routes");
    }
    $type = get_post_type_object($query["post_type"] ?? "post");
    return $type && current_user_can($type->cap->edit_posts);
}

add_action("pre_get_posts", function ($query) {
    if (
        !is_admin() ||
        !$query->is_main_query() ||
        $query->get("post_type") !== "page" ||
        current_user_can("manage_options")
    ) {
        return;
    }
    $existing = $query->get("meta_query") ?: [];
    $query->set("meta_query", [
        "relation" => "AND",
        $existing,
        ["key" => "_autobus_route_id", "compare" => "NOT EXISTS"],
    ]);
});

add_action(
    "customize_register",
    function ($customizer) {
        if (!current_user_can("manage_options")) {
            $customizer->remove_section("custom_css");
        }
    },
    100,
);
