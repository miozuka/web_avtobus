<?php
defined("ABSPATH") || exit();

// Управление доступом остаётся у настоящего администратора. Сотрудникам выдаются права на содержимое.
function agm_can_manage_staff()
{
    return current_user_can("manage_options") &&
        current_user_can("promote_users") &&
        in_array("administrator", wp_get_current_user()->roles, true);
}
function agm_retired_roles()
{
    return ["subscriber", "author", "editor", "contributor"];
}

add_action(
    "init",
    function () {
        foreach (agm_retired_roles() as $key) {
            // Занятую старую роль не удаляем: сначала администратор назначает сотрудникам новую.
            if (
                get_role($key) &&
                !get_users(["role" => $key, "number" => 1, "fields" => "ID"])
            ) {
                remove_role($key);
            }
        }
        if (!get_role(get_option("default_role"))) {
            foreach (wp_roles()->roles as $key => $role) {
                if (str_starts_with($key, "agm_")) {
                    update_option("default_role", $key);
                    break;
                }
            }
        }
    },
    35,
);

add_filter("editable_roles", function ($roles) {
    foreach (agm_retired_roles() as $key) {
        unset($roles[$key]);
    }
    return $roles;
});

function agm_role_groups()
{
    return [
        "news" => [
            "Новости",
            "posts",
            "Черновики, фотографии и видео внутри новости. Полный доступ включает публикацию и планировщик новостей.",
        ],
        "pages" => [
            "Страницы",
            "pages",
            "Обычные страницы и тексты документов. Автоматические страницы графиков меняются только через маршруты.",
        ],
        "jobs" => [
            "Вакансии и условия работы",
            "agm_jobs",
            "Карточки вакансий, зарплаты, требования и преимущества работы. Не открывает доступ к другим разделам сайта.",
        ],
        "cards" => [
            "Карточки разделов",
            "agm_items",
            "Тарифы, услуги, контакты, документы, вопросы пассажиров и сведения о предприятии. Вакансии настраиваются отдельно.",
        ],
    ];
}
function agm_role_selection($role)
{
    $values = [
        "overview" => !empty($role->capabilities["agm_view_overview"]),
        "media" => !empty($role->capabilities["upload_files"]),
        "appearance" => !empty($role->capabilities["edit_theme_options"]),
        "routes" => !empty($role->capabilities["publish_agm_routes"]),
    ];
    foreach (agm_role_groups() as $key => [$label, $plural]) {
        $values[$key] = !empty($role->capabilities["publish_" . $plural])
            ? "publish"
            : (!empty($role->capabilities["edit_" . $plural])
                ? "draft"
                : "none");
    }
    return $values;
}
function agm_role_caps_from_selection($values)
{
    $caps = ["read" => true];
    foreach (
        [
            "overview" => "agm_view_overview",
            "media" => "upload_files",
            "appearance" => "edit_theme_options",
        ]
        as $key => $cap
    ) {
        if (!empty($values[$key])) {
            $caps[$cap] = true;
        }
    }
    foreach (agm_role_groups() as $key => [$label, $plural]) {
        $level = $values[$key] ?? "none";
        if (!in_array($level, ["none", "draft", "publish"], true)) {
            return new WP_Error(
                "level",
                "Выберите доступ из предложенного списка.",
            );
        }
        if ($level !== "none") {
            $caps = array_merge(
                $caps,
                agm_section_caps($plural, $level === "publish"),
            );
        }
    }
    if (($values["news"] ?? "none") === "publish") {
        $caps["manage_categories"] = true;
    }
    if (!empty($values["routes"])) {
        $caps = array_merge(
            $caps,
            agm_section_caps("agm_routes", true),
            agm_section_caps("agm_route_plans", true),
        );
    }
    return $caps;
}
function agm_mark_role_override($key, $state)
{
    if (
        !in_array(
            $key,
            [
                "agm_manager",
                "agm_content_manager",
                "agm_site_editor",
                "agm_operator",
            ],
            true,
        )
    ) {
        return;
    }
    $overrides = get_option("agm_staff_role_overrides", []);
    $overrides[$key] = $state;
    update_option("agm_staff_role_overrides", $overrides, false);
}
function agm_role_save($key, $name, $values)
{
    if (!agm_can_manage_staff()) {
        return new WP_Error(
            "forbidden",
            "Управлять ролями может только администратор.",
        );
    }
    $name = sanitize_text_field($name);
    if ($name === "" || mb_strlen($name) > 80) {
        return new WP_Error(
            "name",
            "Введите название роли длиной до 80 символов.",
        );
    }
    if (
        $key &&
        ($key === "administrator" ||
            !str_starts_with($key, "agm_") ||
            !get_role($key))
    ) {
        return new WP_Error(
            "protected",
            "Эту роль нельзя изменять в редакторе сотрудников.",
        );
    }
    foreach (wp_roles()->roles as $other => $role) {
        if (
            $other !== $key &&
            mb_strtolower(translate_user_role($role["name"])) ===
                mb_strtolower($name)
        ) {
            return new WP_Error(
                "duplicate",
                "Роль с таким названием уже существует.",
            );
        }
    }
    $caps = agm_role_caps_from_selection($values);
    if (is_wp_error($caps)) {
        return $caps;
    }
    if (!$key) {
        do {
            $key =
                "agm_staff_" .
                strtolower(wp_generate_password(10, false, false));
        } while (get_role($key));
        if (!add_role($key, $name, $caps)) {
            return new WP_Error("save", "Не удалось создать роль.");
        }
    } else {
        $roles = wp_roles();
        $roles->roles[$key] = ["name" => $name, "capabilities" => $caps];
        $roles->role_names[$key] = $name;
        $roles->role_objects[$key] = new WP_Role($key, $caps);
        update_option($roles->role_key, $roles->roles);
    }
    agm_mark_role_override($key, "customized");
    return $key;
}
function agm_role_delete($key, $replacement = "")
{
    if (!agm_can_manage_staff()) {
        return new WP_Error(
            "forbidden",
            "Управлять ролями может только администратор.",
        );
    }
    if (
        $key === "administrator" ||
        !str_starts_with($key, "agm_") ||
        !get_role($key)
    ) {
        return new WP_Error("protected", "Эту роль удалить нельзя.");
    }
    $alternatives = array_filter(
        array_keys(wp_roles()->roles),
        fn($other) => $other !== $key && str_starts_with($other, "agm_"),
    );
    if (!$alternatives) {
        return new WP_Error(
            "last_role",
            "Сначала создайте другую рабочую роль. В сайте должна остаться хотя бы одна роль сотрудника.",
        );
    }
    $ids = get_users(["role" => $key, "fields" => "ID"]);
    if ($ids && !in_array($replacement, $alternatives, true)) {
        return new WP_Error(
            "replacement",
            "Выберите новую роль для сотрудников удаляемой роли.",
        );
    }
    foreach ($ids as $id) {
        $user = get_userdata($id);
        $user->remove_role($key);
        $user->add_role($replacement);
        WP_Session_Tokens::get_instance($id)->destroy_all();
    }
    if (get_option("default_role") === $key) {
        update_option("default_role", $replacement ?: reset($alternatives));
    }
    remove_role($key);
    agm_mark_role_override($key, "removed");
    return true;
}

function agm_staff_notice_redirect($result, $success)
{
    if (is_wp_error($result)) {
        set_transient(
            "agm_staff_error_" . get_current_user_id(),
            $result->get_error_message(),
            60,
        );
    }
    wp_safe_redirect(
        add_query_arg(
            "staff_notice",
            is_wp_error($result) ? "error" : $success,
            admin_url("users.php?page=agm-staff-roles"),
        ),
    );
    exit();
}
add_action("admin_post_agm_role_save", function () {
    if (!agm_can_manage_staff()) {
        wp_die("Нет доступа.", "", ["response" => 403]);
    }
    check_admin_referer("agm_role_save");
    $values = $_POST["permissions"] ?? [];
    if (!is_array($values)) {
        agm_staff_notice_redirect(
            new WP_Error("input", "Некорректные права роли."),
            "saved",
        );
    }
    foreach ($values as $key => $value) {
        if (!is_scalar($value)) {
            agm_staff_notice_redirect(
                new WP_Error("input", "Некорректные права роли."),
                "saved",
            );
        }
    }
    agm_staff_notice_redirect(
        agm_role_save(
            sanitize_key($_POST["role_key"] ?? ""),
            sanitize_text_field(wp_unslash($_POST["role_name"] ?? "")),
            wp_unslash($values),
        ),
        "saved",
    );
});
add_action("admin_post_agm_role_delete", function () {
    if (!agm_can_manage_staff()) {
        wp_die("Нет доступа.", "", ["response" => 403]);
    }
    check_admin_referer("agm_role_delete");
    agm_staff_notice_redirect(
        agm_role_delete(
            sanitize_key($_POST["role_key"] ?? ""),
            sanitize_key($_POST["replacement"] ?? ""),
        ),
        "deleted",
    );
});

add_action(
    "admin_menu",
    function () {
        if (!agm_can_manage_staff()) {
            return;
        }
        global $menu, $submenu;
        foreach ($menu as &$entry) {
            if (($entry[2] ?? "") === "users.php") {
                $entry[0] = "Сотрудники";
            }
        }
        unset($entry);
        foreach ($submenu["users.php"] ?? [] as $index => $entry) {
            if ($entry[2] === "users.php") {
                $submenu["users.php"][$index][0] = "Все сотрудники";
            }
            if ($entry[2] === "user-new.php") {
                $submenu["users.php"][$index][0] = "Добавить сотрудника";
            }
        }
        add_submenu_page(
            "users.php",
            "Роли и права сотрудников",
            "Роли и права",
            "promote_users",
            "agm-staff-roles",
            "agm_staff_roles_screen",
        );
    },
    110,
);

function agm_staff_roles_screen()
{
    if (!agm_can_manage_staff()) {
        wp_die("Нет доступа.", "", ["response" => 403]);
    }
    $url = admin_url("users.php?page=agm-staff-roles");
    $key = sanitize_key($_GET["role"] ?? "");
    $role = $key && $key !== "new" ? get_role($key) : null;
    if ($key && $key !== "new" && (!$role || !str_starts_with($key, "agm_"))) {
        wp_die("Эта роль защищена или отсутствует.", "", ["response" => 400]);
    }
    echo '<div class="wrap agm-staff"><h1>Роли и права сотрудников</h1><p>Роль определяет, какие разделы доступны человеку. Для входа ему всё равно нужна своя учётная запись с логином и паролем.</p><div class="agm-staff-toolbar"><a class="button" href="' .
        esc_url(admin_url("users.php")) .
        '">Все сотрудники</a><a class="button" href="' .
        esc_url(admin_url("user-new.php")) .
        '">Добавить сотрудника</a><a class="button button-primary" href="' .
        esc_url(add_query_arg("role", "new", $url)) .
        '">Добавить роль</a></div>';
    $notice = sanitize_key($_GET["staff_notice"] ?? "");
    if (in_array($notice, ["saved", "deleted"], true)) {
        echo '<div class="notice notice-success"><p>' .
            ($notice === "saved"
                ? "Роль сохранена. Права действуют для всех сотрудников с этой ролью."
                : "Роль удалена. Учётные записи и материалы сохранены.") .
            "</p></div>";
    }
    if ($notice === "error") {
        $error = get_transient("agm_staff_error_" . get_current_user_id());
        delete_transient("agm_staff_error_" . get_current_user_id());
        echo '<div class="notice notice-error"><p>' .
            esc_html($error ?: "Действие не выполнено.") .
            "</p></div>";
    }
    if ($key) {
        if (!empty($_GET["delete"]) && $role) {
            $count = count(get_users(["role" => $key, "fields" => "ID"]));
            echo '<section class="agm-staff-box"><h2>Удаление роли «' .
                esc_html(wp_roles()->role_names[$key]) .
                "»</h2><p>Сотрудников с этой ролью: <strong>" .
                (int) $count .
                '</strong>. Удаление роли не удаляет людей и их материалы.</p><form method="post" action="' .
                esc_url(admin_url("admin-post.php")) .
                '">';
            wp_nonce_field("agm_role_delete");
            echo '<input type="hidden" name="action" value="agm_role_delete"><input type="hidden" name="role_key" value="' .
                esc_attr($key) .
                '">';
            if ($count) {
                echo '<p><label for="agm-replacement">Какую роль назначить этим сотрудникам</label></p><select id="agm-replacement" name="replacement" required><option value="">Выберите роль</option>';
                foreach (wp_roles()->roles as $other => $definition) {
                    if ($other !== $key && str_starts_with($other, "agm_")) {
                        echo '<option value="' .
                            esc_attr($other) .
                            '">' .
                            esc_html($definition["name"]) .
                            "</option>";
                    }
                }
                echo '</select><p class="description">После переноса сотрудники войдут заново, чтобы применились новые права.</p>';
            }
            submit_button("Удалить роль", "delete");
            echo '<a class="button" href="' .
                esc_url($url) .
                '">Отмена</a></form></section></div>';
            return;
        }
        $values = $role ? agm_role_selection($role) : ["overview" => true];
        echo '<section class="agm-staff-box"><h2>' .
            ($role ? "Изменить роль" : "Новая роль") .
            '</h2><form method="post" action="' .
            esc_url(admin_url("admin-post.php")) .
            '">';
        wp_nonce_field("agm_role_save");
        echo '<input type="hidden" name="action" value="agm_role_save"><input type="hidden" name="role_key" value="' .
            esc_attr($role ? $key : "") .
            '"><p><label for="agm-role-name"><strong>Название роли</strong></label></p><input id="agm-role-name" class="regular-text" name="role_name" value="' .
            esc_attr($role ? wp_roles()->role_names[$key] : "") .
            '" maxlength="80" required placeholder="Например: Специалист по новостям"><p class="description">Это название будет видно при назначении роли сотруднику.</p><div class="agm-staff-permissions">';
        foreach (agm_role_groups() as $group => [$label, $plural, $help]) {
            echo '<div class="agm-staff-permission"><label for="agm-permission-' .
                esc_attr($group) .
                '"><strong>' .
                esc_html($label) .
                '</strong></label><select id="agm-permission-' .
                esc_attr($group) .
                '" name="permissions[' .
                esc_attr($group) .
                ']">';
            foreach (
                [
                    "none" => "Нет доступа",
                    "draft" => "Подготовка черновиков",
                    "publish" => "Редактирование и публикация",
                ]
                as $level => $text
            ) {
                echo '<option value="' .
                    esc_attr($level) .
                    '" ' .
                    selected($values[$group] ?? "none", $level, false) .
                    ">" .
                    esc_html($text) .
                    "</option>";
            }
            echo '</select><p class="description">' .
                esc_html($help) .
                "</p></div>";
        }
        foreach (
            [
                "routes" => [
                    "Маршруты и планировщик расписаний",
                    "Создание и изменение маршрутов, направлений и графиков, несколько отложенных версий.",
                ],
                "media" => [
                    "Загрузка изображений, видео и документов",
                    "Позволяет добавлять файлы в медиатеку.",
                ],
                "appearance" => [
                    "Общие тексты, меню и настройки оформления",
                    "Телефоны, ссылки, заголовки и фотографии через «Настроить». Доступ относится ко всему оформлению сайта.",
                ],
                "overview" => [
                    "Просмотр сводки сайта",
                    "Количество материалов и ожидающие новости и изменения расписаний.",
                ],
            ]
            as $group => [$label, $help]
        ) {
            echo '<div class="agm-staff-permission"><label><input type="checkbox" name="permissions[' .
                esc_attr($group) .
                ']" value="1" ' .
                checked(!empty($values[$group]), true, false) .
                "> <strong>" .
                esc_html($label) .
                '</strong></label><p class="description">' .
                esc_html($help) .
                "</p></div>";
        }
        echo '</div><p class="description">Вход и изменение своего профиля разрешены каждой роли. Управление сотрудниками, плагинами и техническими настройками остаётся у администратора. «Подготовка черновиков» не разрешает изменять опубликованные материалы.</p>';
        submit_button($role ? "Сохранить роль" : "Создать роль");
        echo '<a class="button" href="' .
            esc_url($url) .
            '">Отмена</a></form></section></div>';
        return;
    }
    echo '<table class="widefat striped agm-staff-table"><thead><tr><th>Роль</th><th>Сотрудников</th><th>Разрешённые действия</th><th>Управление</th></tr></thead><tbody>';
    foreach (wp_roles()->roles as $roleKey => $definition) {
        if (in_array($roleKey, agm_retired_roles(), true)) {
            continue;
        }
        $count = count(get_users(["role" => $roleKey, "fields" => "ID"]));
        $summary = [];
        if ($roleKey === "administrator") {
            $summary[] =
                "Полный доступ, включая сотрудников и технические настройки";
        } else {
            $values = agm_role_selection(get_role($roleKey));
            foreach (agm_role_groups() as $group => [$label]) {
                if (($values[$group] ?? "none") !== "none") {
                    $summary[] =
                        $label .
                        ($values[$group] === "draft"
                            ? ": черновики"
                            : ": публикация");
                }
            }
            foreach (
                [
                    "routes" => "Маршруты и планировщик",
                    "media" => "Загрузка файлов",
                    "appearance" => "Оформление и общие тексты",
                    "overview" => "Сводка сайта",
                ]
                as $group => $label
            ) {
                if (!empty($values[$group])) {
                    $summary[] = $label;
                }
            }
            if (!$summary) {
                $summary[] = "Вход и собственный профиль";
            }
        }
        echo "<tr><td><strong>" .
            esc_html(translate_user_role($definition["name"])) .
            '</strong></td><td><a href="' .
            esc_url(add_query_arg("role", $roleKey, admin_url("users.php"))) .
            '">' .
            (int) $count .
            "</a></td><td>" .
            esc_html(implode("; ", $summary)) .
            "</td><td>";
        if (str_starts_with($roleKey, "agm_")) {
            echo '<a class="button" href="' .
                esc_url(add_query_arg("role", $roleKey, $url)) .
                '">Изменить</a> <a class="button" href="' .
                esc_url(
                    add_query_arg(["role" => $roleKey, "delete" => 1], $url),
                ) .
                '">Удалить</a>';
        } else {
            echo '<span class="description">Защищённая роль</span>';
        }
        echo "</td></tr>";
    }
    echo '</tbody></table><p class="description">Администратор защищён от удаления и изменения прав. Для остальных ролей настройки применяются ко всем назначенным сотрудникам.</p></div>';
}

add_action("admin_enqueue_scripts", function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === "users_page_agm-staff-roles") {
        wp_enqueue_style(
            "agm-staff-admin",
            plugins_url(
                "assets/staff-admin.css",
                dirname(__DIR__) . "/autobus03-content.php",
            ),
            [],
            "1.6.0",
        );
    }
});

// Нельзя случайно удалить последнего администратора или сменить ему роль на обычную.
add_filter(
    "map_meta_cap",
    function ($caps, $cap, $userId, $args) {
        if (
            !in_array($cap, ["delete_user", "promote_user"], true) ||
            empty($args[0])
        ) {
            return $caps;
        }
        $target = get_userdata((int) $args[0]);
        if (
            $target &&
            in_array("administrator", $target->roles, true) &&
            count(
                get_users([
                    "role" => "administrator",
                    "number" => 2,
                    "fields" => "ID",
                ]),
            ) < 2
        ) {
            return ["do_not_allow"];
        }
        return $caps;
    },
    20,
    4,
);
add_filter(
    "rest_pre_insert_user",
    function ($prepared, $request) {
        $target = get_userdata((int) $request["id"]);
        $roles = $request->get_param("roles");
        if (
            $target &&
            $roles !== null &&
            in_array("administrator", $target->roles, true) &&
            !in_array("administrator", $roles, true) &&
            count(
                get_users([
                    "role" => "administrator",
                    "number" => 2,
                    "fields" => "ID",
                ]),
            ) < 2
        ) {
            return new WP_Error(
                "last_administrator",
                "На сайте должен остаться хотя бы один администратор.",
                ["status" => 403],
            );
        }
        return $prepared;
    },
    10,
    2,
);
