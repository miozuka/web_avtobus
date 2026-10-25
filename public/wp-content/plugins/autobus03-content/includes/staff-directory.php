<?php
defined("ABSPATH") || exit();

// Доступ отключается отдельно от роли: материалы и настройки сотрудника остаются на месте.
function agm_staff_is_paused($user_id)
{
    $user = get_userdata((int) $user_id);
    return $user &&
        !in_array("administrator", $user->roles, true) &&
        get_user_meta($user->ID, "_agm_staff_paused", true) === "1";
}
function agm_staff_change_access($user_id, $operation)
{
    if (!agm_can_manage_staff()) {
        return new WP_Error(
            "forbidden",
            "Управлять доступом может только администратор.",
        );
    }
    $user = get_userdata((int) $user_id);
    if (
        !$user ||
        !in_array($operation, ["pause", "resume", "sessions"], true)
    ) {
        return new WP_Error("input", "Сотрудник или действие не найдены.");
    }
    if ($user->ID === get_current_user_id()) {
        return new WP_Error(
            "self",
            "Собственный доступ здесь отключить нельзя.",
        );
    }
    if (
        $operation !== "sessions" &&
        in_array("administrator", $user->roles, true)
    ) {
        return new WP_Error(
            "administrator",
            "Доступ администратора защищён от отключения.",
        );
    }
    if ($operation === "pause") {
        update_user_meta($user->ID, "_agm_staff_paused", "1");
        if (!agm_staff_is_paused($user->ID)) {
            return new WP_Error(
                "save",
                "Не удалось отключить доступ. Повторите действие.",
            );
        }
    } elseif ($operation === "resume") {
        delete_user_meta($user->ID, "_agm_staff_paused");
        if (agm_staff_is_paused($user->ID)) {
            return new WP_Error(
                "save",
                "Не удалось включить доступ. Повторите действие.",
            );
        }
    }
    WP_Session_Tokens::get_instance($user->ID)->destroy_all();
    return true;
}
add_filter(
    "authenticate",
    function ($user) {
        return $user instanceof WP_User && agm_staff_is_paused($user->ID)
            ? new WP_Error(
                "agm_staff_paused",
                "Доступ к управлению сайтом приостановлен. Обратитесь к администратору организации.",
            )
            : $user;
    },
    100,
);
// Проверка прав действует и для API, задач планировщика и уже открытых сеансов.
add_filter(
    "user_has_cap",
    function ($caps, $required, $args, $user) {
        return agm_staff_is_paused($user->ID)
            ? array_fill_keys(array_keys($caps), false)
            : $caps;
    },
    100,
    4,
);
add_filter(
    "determine_current_user",
    function ($user_id) {
        return $user_id && agm_staff_is_paused($user_id) ? 0 : $user_id;
    },
    100,
);
add_action(
    "wp_login",
    function ($login, $user) {
        update_user_meta($user->ID, "_agm_staff_last_login", time());
    },
    10,
    2,
);

function agm_staff_role_description($key)
{
    $role = get_role($key);
    if (!$role) {
        return "Роль не найдена. Выберите действующую роль.";
    }
    if ($key === "administrator") {
        return "Полный доступ: содержимое, сотрудники, настройки и обновления сайта.";
    }
    $values = agm_role_selection($role);
    $parts = [];
    foreach (agm_role_groups() as $group => [$label]) {
        if (($values[$group] ?? "none") !== "none") {
            $parts[] =
                $label .
                ($values[$group] === "draft"
                    ? ": подготовка черновиков"
                    : ": редактирование и публикация");
        }
    }
    foreach (
        [
            "routes" => "Маршруты и планировщик",
            "media" => "Загрузка файлов",
            "appearance" => "Общие тексты и оформление",
            "overview" => "Просмотр сводки",
        ]
        as $group => $label
    ) {
        if (!empty($values[$group])) {
            $parts[] = $label;
        }
    }
    return $parts
        ? implode("; ", $parts) . "."
        : "Вход и собственный профиль. Разделы содержимого недоступны.";
}
function agm_staff_access_url($user_id, $operation)
{
    return add_query_arg(
        [
            "page" => "agm-staff-access",
            "user_id" => (int) $user_id,
            "operation" => $operation,
        ],
        admin_url("users.php"),
    );
}
add_action(
    "admin_menu",
    function () {
        if (agm_can_manage_staff()) {
            add_submenu_page(
                null,
                "Доступ сотрудника",
                "Доступ сотрудника",
                "promote_users",
                "agm-staff-access",
                "agm_staff_access_screen",
            );
        }
    },
    120,
);
add_filter("parent_file", function ($parent) {
    return in_array(
        get_current_screen()->id ?? "",
        ["users_page_agm-staff-access", "admin_page_agm-staff-access"],
        true,
    )
        ? "users.php"
        : $parent;
});
function agm_staff_access_screen()
{
    if (!agm_can_manage_staff()) {
        wp_die("Нет доступа.", "", ["response" => 403]);
    }
    $user = get_userdata(absint($_GET["user_id"] ?? 0));
    $operation = sanitize_key($_GET["operation"] ?? "");
    $labels = [
        "pause" => [
            "Приостановить доступ",
            "Сотрудник не сможет входить в управление сайтом. Открытые сеансы будут завершены. Его материалы и роль сохранятся. Перед отключением проверьте будущие задания этого сотрудника в планировщиках.",
        ],
        "resume" => [
            "Разрешить доступ",
            "Сотрудник сможет войти с прежним логином и паролем. Назначенная роль сохранится.",
        ],
        "sessions" => [
            "Завершить сеансы",
            "Сотруднику потребуется снова ввести логин и пароль на всех устройствах. Пароль, роль и материалы не изменятся.",
        ],
    ];
    if (
        !$user ||
        !isset($labels[$operation]) ||
        $user->ID === get_current_user_id() ||
        ($operation !== "sessions" &&
            in_array("administrator", $user->roles, true))
    ) {
        wp_die("Это действие недоступно.", "", ["response" => 400]);
    }
    echo '<div class="wrap agm-staff"><h1>' .
        esc_html($labels[$operation][0]) .
        '</h1><section class="agm-staff-box"><h2>' .
        esc_html($user->display_name) .
        "</h2><p>Логин: <strong>" .
        esc_html($user->user_login) .
        "</strong></p><p>" .
        esc_html($labels[$operation][1]) .
        '</p><form method="post" action="' .
        esc_url(admin_url("admin-post.php")) .
        '">';
    wp_nonce_field("agm_staff_access_" . $user->ID . "_" . $operation);
    echo '<input type="hidden" name="action" value="agm_staff_access"><input type="hidden" name="user_id" value="' .
        (int) $user->ID .
        '"><input type="hidden" name="operation" value="' .
        esc_attr($operation) .
        '">';
    submit_button(
        $labels[$operation][0],
        $operation === "pause" ? "secondary" : "primary",
    );
    echo '<a class="button" href="' .
        esc_url(admin_url("users.php")) .
        '">Отмена</a></form></section></div>';
}
add_action("admin_post_agm_staff_access", function () {
    if (!agm_can_manage_staff()) {
        wp_die("Нет доступа.", "", ["response" => 403]);
    }
    $id = absint($_POST["user_id"] ?? 0);
    $op = sanitize_key($_POST["operation"] ?? "");
    check_admin_referer("agm_staff_access_" . $id . "_" . $op);
    $result = agm_staff_change_access($id, $op);
    if (is_wp_error($result)) {
        set_transient(
            "agm_staff_directory_error_" . get_current_user_id(),
            $result->get_error_message(),
            60,
        );
    }
    wp_safe_redirect(
        add_query_arg(
            "staff_access",
            is_wp_error($result) ? "error" : $op,
            admin_url("users.php"),
        ),
    );
    exit();
});
add_filter("manage_users_columns", function ($columns) {
    if (!agm_can_manage_staff()) {
        return $columns;
    }
    unset($columns["posts"]);
    $columns["username"] = "Логин";
    $columns["name"] = "Имя сотрудника";
    $columns["agm_access"] = "Доступ";
    $columns["agm_last_login"] = "Последний вход";
    return $columns;
});
add_filter(
    "manage_users_custom_column",
    function ($value, $column, $id) {
        if (!agm_can_manage_staff()) {
            return $value;
        }
        if ($column === "agm_access") {
            return '<span class="agm-staff-status ' .
                (agm_staff_is_paused($id) ? "is-paused" : "is-active") .
                '">' .
                (agm_staff_is_paused($id) ? "Приостановлен" : "Разрешён") .
                "</span>";
        }
        if ($column === "agm_last_login") {
            $time = (int) get_user_meta($id, "_agm_staff_last_login", true);
            return $time
                ? esc_html(wp_date("d.m.Y H:i", $time))
                : '<span class="description">Нет данных</span>';
        }
        return $value;
    },
    10,
    3,
);
add_filter(
    "user_row_actions",
    function ($actions, $user) {
        if (!agm_can_manage_staff()) {
            return $actions;
        }
        if (isset($actions["edit"])) {
            $actions["edit"] =
                '<a href="' .
                esc_url(get_edit_user_link($user->ID)) .
                '">Изменить данные и роль</a>';
        }
        // Ссылка «Посмотреть» ведёт в пустой авторский архив, который сотрудникам этого сайта не нужен.
        unset($actions["view"]);
        if ($user->ID !== get_current_user_id()) {
            if (!in_array("administrator", $user->roles, true)) {
                $paused = agm_staff_is_paused($user->ID);
                $actions["agm_access"] =
                    '<a href="' .
                    esc_url(
                        agm_staff_access_url(
                            $user->ID,
                            $paused ? "resume" : "pause",
                        ),
                    ) .
                    '">' .
                    ($paused ? "Разрешить вход" : "Приостановить вход") .
                    "</a>";
            }
            $actions["agm_sessions"] =
                '<a href="' .
                esc_url(agm_staff_access_url($user->ID, "sessions")) .
                '">Завершить сеансы</a>';
        }
        return $actions;
    },
    10,
    2,
);
add_action("restrict_manage_users", function ($which) {
    if ($which !== "top" || !agm_can_manage_staff()) {
        return;
    }
    $selected = sanitize_key($_GET["staff_status"] ?? "");
    echo '<label class="screen-reader-text" for="agm-staff-status">Статус доступа</label><select id="agm-staff-status" name="staff_status">';
    foreach (
        [
            "" => "Любой доступ",
            "active" => "Вход разрешён",
            "paused" => "Вход приостановлен",
        ]
        as $key => $label
    ) {
        echo '<option value="' .
            esc_attr($key) .
            '" ' .
            selected($selected, $key, false) .
            ">" .
            esc_html($label) .
            "</option>";
    }
    echo "</select>";
    submit_button("Показать", "secondary", "agm_filter", false);
});
add_filter("users_list_table_query_args", function ($args) {
    if (!agm_can_manage_staff()) {
        return $args;
    }
    $status = sanitize_key($_GET["staff_status"] ?? "");
    if (!in_array($status, ["active", "paused"], true)) {
        return $args;
    }
    // Администратор не может быть приостановлен, в том числе после смены прежней рабочей роли.
    $paused = array_values(
        array_filter(
            get_users([
                "meta_key" => "_agm_staff_paused",
                "meta_value" => "1",
                "fields" => "ID",
            ]),
            "agm_staff_is_paused",
        ),
    );
    if ($status === "paused") {
        $args["include"] = $paused ?: [0];
    } elseif ($paused) {
        $args["exclude"] = array_unique(
            array_merge($args["exclude"] ?? [], $paused),
        );
    }
    return $args;
});
add_action("admin_notices", function () {
    if (!agm_can_manage_staff()) {
        return;
    }
    $screen = get_current_screen();
    if (
        !$screen ||
        !in_array(
            $screen->id,
            ["users", "user", "user-new", "user-edit", "profile"],
            true,
        )
    ) {
        return;
    }
    if (
        $screen->id === "users" &&
        in_array((string) ($_GET["action"] ?? ""), ["", "-1"], true)
    ) {
        $total = count_users();
        $paused = count(
            array_filter(
                get_users([
                    "meta_key" => "_agm_staff_paused",
                    "meta_value" => "1",
                    "fields" => "ID",
                ]),
                "agm_staff_is_paused",
            ),
        );
        echo '<section class="agm-staff-summary" aria-label="Сотрудники сайта"><div><strong>' .
            (int) $total["total_users"] .
            "</strong><span>Всего сотрудников</span></div><div><strong>" .
            (int) ($total["total_users"] - $paused) .
            "</strong><span>Вход разрешён</span></div><div><strong>" .
            (int) $paused .
            '</strong><span>Вход приостановлен</span></div><div><a class="button" href="' .
            esc_url(admin_url("users.php?page=agm-staff-roles")) .
            '">Роли и права</a></div></section>';
    }
    $notice = sanitize_key($_GET["staff_access"] ?? "");
    $messages = [
        "pause" => "Вход сотрудника приостановлен. Материалы сохранены.",
        "resume" =>
            "Вход сотрудника разрешён. Он может войти с прежним паролем.",
        "sessions" => "Открытые сеансы сотрудника завершены.",
        "error" => get_transient(
            "agm_staff_directory_error_" . get_current_user_id(),
        ),
    ];
    if ($notice && isset($messages[$notice])) {
        delete_transient("agm_staff_directory_error_" . get_current_user_id());
        echo '<div class="notice ' .
            ($notice === "error" ? "notice-error" : "notice-success") .
            ' is-dismissible"><p>' .
            esc_html($messages[$notice]) .
            "</p></div>";
    }
});
function agm_staff_profile_access_box($user)
{
    if (!agm_can_manage_staff()) {
        return;
    }
    echo '<h2>Доступ сотрудника</h2><table class="form-table"><tr><th>Состояние</th><td><strong>' .
        (agm_staff_is_paused($user->ID)
            ? "Вход приостановлен"
            : "Вход разрешён") .
        "</strong><p>Последний успешный вход: " .
        esc_html(
            ($time = (int) get_user_meta(
                $user->ID,
                "_agm_staff_last_login",
                true,
            ))
                ? wp_date("d.m.Y H:i", $time)
                : "нет данных; учёт начинается с установки этой версии",
        ) .
        ". Время Улан-Удэ.</p>";
    if ($user->ID !== get_current_user_id()) {
        if (!in_array("administrator", $user->roles, true)) {
            echo '<a class="button" href="' .
                esc_url(
                    agm_staff_access_url(
                        $user->ID,
                        agm_staff_is_paused($user->ID) ? "resume" : "pause",
                    ),
                ) .
                '">' .
                (agm_staff_is_paused($user->ID)
                    ? "Разрешить вход"
                    : "Приостановить вход") .
                "</a> ";
        }
        echo '<a class="button" href="' .
            esc_url(agm_staff_access_url($user->ID, "sessions")) .
            '">Завершить сеансы на всех устройствах</a>';
    }
    echo "</td></tr></table>";
}
add_action("edit_user_profile", "agm_staff_profile_access_box");
add_action("show_user_profile", "agm_staff_profile_access_box");

add_action("admin_enqueue_scripts", function () {
    if (!agm_can_manage_staff()) {
        return;
    }
    $screen = get_current_screen();
    if (
        !$screen ||
        !in_array(
            $screen->id,
            [
                "users",
                "user",
                "user-new",
                "user-edit",
                "profile",
                "users_page_agm-staff-roles",
                "users_page_agm-staff-access",
                "admin_page_agm-staff-access",
            ],
            true,
        )
    ) {
        return;
    }
    $file = dirname(__DIR__) . "/autobus03-content.php";
    wp_enqueue_style(
        "agm-staff-admin",
        plugins_url("assets/staff-admin.css", $file),
        [],
        filemtime(dirname(__DIR__) . "/assets/staff-admin.css"),
    );
    wp_enqueue_style(
        "agm-staff-directory",
        plugins_url("assets/staff-directory.css", $file),
        [],
        filemtime(dirname(__DIR__) . "/assets/staff-directory.css"),
    );
    wp_enqueue_script(
        "agm-staff-directory",
        plugins_url("assets/staff-directory.js", $file),
        [],
        filemtime(dirname(__DIR__) . "/assets/staff-directory.js"),
        true,
    );
    $roles = [];
    foreach (wp_roles()->roles as $key => $role) {
        if (in_array($key, agm_retired_roles(), true)) {
            continue;
        }
        $roles[$key] = [
            "name" => translate_user_role($role["name"]),
            "summary" => agm_staff_role_description($key),
            "values" =>
                $key === "administrator"
                    ? null
                    : agm_role_selection(get_role($key)),
        ];
    }
    wp_localize_script("agm-staff-directory", "agmStaffDirectory", [
        "screen" => $screen->id,
        "roles" => $roles,
    ]);
});

add_filter("admin_body_class", function ($classes) {
    $screen = get_current_screen();
    return agm_can_manage_staff() &&
        $screen &&
        in_array(
            $screen->id,
            [
                "users",
                "user",
                "user-new",
                "user-edit",
                "profile",
                "users_page_agm-staff-roles",
                "users_page_agm-staff-access",
                "admin_page_agm-staff-access",
            ],
            true,
        )
        ? $classes . " agm-staff-directory-page"
        : $classes;
});

// Назначение администратором явно возвращает полный доступ, без скрытого старого флага.
foreach (["set_user_role", "add_user_role"] as $hook) {
    add_action(
        $hook,
        function ($user_id, $role) {
            if ($role === "administrator") {
                delete_user_meta($user_id, "_agm_staff_paused");
            }
        },
        10,
        2,
    );
}
