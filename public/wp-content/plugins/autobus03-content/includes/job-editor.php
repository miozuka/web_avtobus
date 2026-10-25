<?php
defined("ABSPATH") || exit();

// Сохраняем прежние поля карточки. Новые строки преимуществ превращаются в совместимый список.
function agm_sanitize_job_features($value)
{
    $lines = preg_split('/[;\r\n]+/u', sanitize_textarea_field($value));
    return implode(
        "; ",
        array_unique(
            array_filter(array_map("trim", $lines), fn($line) => $line !== ""),
        ),
    );
}
function agm_job_next_order()
{
    global $wpdb;
    return 1 +
        (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type=%s AND post_status NOT IN ('trash','auto-draft')",
                "agm_job",
            ),
        );
}
function agm_job_phone_default()
{
    return function_exists("autobus_value") ? autobus_value("vp_hr_phone") : "";
}
function agm_job_public_url($id)
{
    return home_url("/vacancy/#vacancy-" . absint($id));
}

add_filter(
    "register_post_type_args",
    function ($args, $type) {
        if ($type !== "agm_job") {
            return $args;
        }
        $args["labels"] = array_merge($args["labels"], [
            "name" => "Вакансии",
            "singular_name" => "Вакансия",
            "add_new" => "Добавить вакансию",
            "add_new_item" => "Новая вакансия",
            "edit_item" => "Редактирование вакансии",
            "all_items" => "Все вакансии",
            "not_found" => "Вакансий пока нет.",
        ]);
        // Карточка использует конкретные поля, поэтому пустой редактор текста и фото здесь не нужны.
        $args["supports"] = [
            "title",
            "excerpt",
            "page-attributes",
            "custom-fields",
        ];
        return $args;
    },
    40,
    2,
);
add_filter(
    "use_block_editor_for_post_type",
    fn($enabled, $type) => $type === "agm_job" ? false : $enabled,
    20,
    2,
);
add_filter(
    "enter_title_here",
    fn($title, $post) => $post->post_type === "agm_job"
        ? "Название должности, например: Водитель автобуса"
        : $title,
    20,
    2,
);

function agm_job_field(
    $post,
    $key,
    $label,
    $help = "",
    $type = "text",
    $placeholder = "",
) {
    $value = get_post_meta($post->ID, "_autobus_" . $key, true);
    if ($key === "features") {
        $value = implode(
            "\n",
            array_filter(
                array_map("trim", explode(";", $value)),
                fn($line) => $line !== "",
            ),
        );
    }
    echo '<div class="agm-job-field"><label for="agm_' .
        esc_attr($key) .
        '"><strong>' .
        esc_html($label) .
        "</strong></label>";
    if (in_array($type, ["textarea", "job_features"], true)) {
        echo '<textarea id="agm_' .
            esc_attr($key) .
            '" name="agm[' .
            esc_attr($key) .
            ']" rows="4" placeholder="' .
            esc_attr($placeholder) .
            '" data-job-field="' .
            esc_attr($key) .
            '">' .
            esc_textarea($value) .
            "</textarea>";
    } else {
        echo '<input id="agm_' .
            esc_attr($key) .
            '" type="' .
            esc_attr($type) .
            '" name="agm[' .
            esc_attr($key) .
            ']" value="' .
            esc_attr($value) .
            '" placeholder="' .
            esc_attr($placeholder) .
            '" data-job-field="' .
            esc_attr($key) .
            '">';
    }
    if ($help) {
        echo '<p class="description">' . esc_html($help) . "</p>";
    }
    echo "</div>";
}
add_action("add_meta_boxes_agm_job", function ($post) {
    foreach (
        ["agm_details", "postexcerpt", "pageparentdiv", "postcustom"]
        as $box
    ) {
        remove_meta_box($box, "agm_job", "normal");
    }
    remove_meta_box("postimagediv", "agm_job", "side");
    remove_meta_box("pageparentdiv", "agm_job", "side");
    add_meta_box(
        "agm_job_details",
        "Содержимое карточки вакансии",
        function ($post) {
            wp_nonce_field("agm_save", "agm_nonce");
            echo '<input type="hidden" name="agm_job_editor" value="1"><p>Название должности укажите вверху. Для публикации заполните название и короткое описание. Остальные поля можно оставить пустыми. Карточка появится в разделе «Вакансии» после публикации.</p><div class="agm-job-fields"><div class="agm-job-field agm-job-wide"><label for="agm_job_excerpt"><strong>Короткое описание <span class="agm-job-required">— обязательно для публикации</span></strong></label><textarea id="agm_job_excerpt" name="agm_job_excerpt" rows="3" data-job-field="description" placeholder="Чем будет заниматься сотрудник, в двух-трёх предложениях">' .
                esc_textarea($post->post_excerpt) .
                '</textarea><p class="description">Этот текст виден сразу в карточке. Обязанности и требования можно дополнить ниже.</p></div>';
            agm_job_field(
                $post,
                "badge",
                "Метка над названием",
                "Необязательно. Короткая категория вакансии.",
                "text",
                "Например: Работа на маршрутах",
            );
            agm_job_field(
                $post,
                "schedule",
                "График работы",
                "Необязательно. Укажите понятный режим работы.",
                "text",
                "Например: Сменный график 2/2",
            );
            agm_job_field(
                $post,
                "salary",
                "Заработная плата",
                "Пишите сумму или диапазон целиком, с единицей оплаты. Если поле пустое, на сайте предложено уточнить оплату в отделе кадров.",
                "text",
                "Например: от 55 000 ₽",
            );
            agm_job_field(
                $post,
                "salary_note",
                "Пояснение к оплате",
                "Необязательно. Например: до вычета налога, ежемесячно.",
            );
            echo '<div class="agm-job-wide">';
            agm_job_field(
                $post,
                "features",
                "Короткие преимущества",
                "Каждое преимущество — с новой строки. На сайте это небольшие метки.",
                "job_features",
                "Социальный пакет\nСлужебный транспорт",
            );
            echo '</div><div class="agm-job-wide"><details class="agm-job-more"' .
                (get_post_meta($post->ID, "_autobus_duties", true) ||
                get_post_meta($post->ID, "_autobus_requirements", true) ||
                get_post_meta($post->ID, "_autobus_conditions", true)
                    ? " open"
                    : "") .
                '><summary>Обязанности, требования и условия — необязательно</summary><p class="description">На сайте эти сведения раскрываются кнопкой «Подробнее о вакансии». Каждый пункт пишите с новой строки.</p>';
            agm_job_field($post, "duties", "Обязанности", "", "textarea");
            agm_job_field(
                $post,
                "requirements",
                "Требования к кандидату",
                "",
                "textarea",
            );
            agm_job_field(
                $post,
                "conditions",
                "Условия работы",
                "",
                "textarea",
            );
            echo "</details></div>";
            agm_job_field(
                $post,
                "phone",
                "Телефон для этой вакансии",
                "Если пусто, используется общий телефон отдела кадров: " .
                    agm_job_phone_default() .
                    ".",
                "tel",
                "Необязательно",
            );
            agm_job_field(
                $post,
                "url",
                "Ссылка на подробную вакансию",
                "Необязательно. Адрес вакансии на HeadHunter или другом сайте.",
                "url",
                "https://...",
            );
            echo '<div class="agm-job-field"><label for="agm_job_order"><strong>Порядок карточки</strong></label><input type="number" id="agm_job_order" name="agm_job_order" min="0" step="1" value="' .
                esc_attr(
                    $post->post_status === "auto-draft"
                        ? agm_job_next_order()
                        : $post->menu_order,
                ) .
                '"><p class="description">Меньшее число — выше в списке. Новой карточке предлагается место после существующих.</p></div></div>';
        },
        "agm_job",
        "normal",
        "high",
    );
    add_meta_box(
        "agm_job_preview",
        "Предпросмотр карточки",
        function ($post) {
            echo '<p class="description">Показывает содержимое по мере заполнения. Окончательный вид зависит от ширины экрана.</p><div class="agm-job-preview" data-default-phone="' .
                esc_attr(agm_job_phone_default()) .
                '"><div class="agm-job-preview-top"><span data-job-preview="badge"></span><span data-job-preview="schedule"></span></div><h3 data-job-preview="title"></h3><p data-job-preview="description"></p><ul data-job-preview="features"></ul><div class="agm-job-preview-payment"><span>Заработная плата</span><strong data-job-preview="salary"></strong><p data-job-preview="salary_note"></p></div><span class="agm-job-preview-button" data-job-preview="phone"></span><p data-job-preview="url">Ссылка на подробную вакансию</p><p class="description" data-job-preview="details">Будут доступны подробные обязанности, требования или условия.</p></div>';
            if ($post->post_status === "publish") {
                echo '<p><a href="' .
                    esc_url(agm_job_public_url($post->ID)) .
                    '" target="_blank" rel="noopener">Посмотреть опубликованную карточку</a></p>';
            }
        },
        "agm_job",
        "normal",
        "default",
    );
});

// Описание и порядок сохраняются стандартными полями WordPress, а не второй копией в метаданных.
add_filter(
    "wp_insert_post_data",
    function ($data, $postarr) {
        if (($data["post_type"] ?? "") !== "agm_job") {
            return $data;
        }
        if (
            !empty($_POST["agm_job_editor"]) &&
            isset($_POST["agm_nonce"]) &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST["agm_nonce"])),
                "agm_save",
            ) &&
            !wp_is_post_revision($postarr["ID"] ?? 0) &&
            !(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        ) {
            $id = (int) ($postarr["ID"] ?? 0);
            if (
                $id
                    ? current_user_can("edit_post", $id)
                    : current_user_can(
                        get_post_type_object("agm_job")->cap->create_posts,
                    )
            ) {
                $data["post_excerpt"] = wp_slash(
                    sanitize_textarea_field(
                        wp_unslash($_POST["agm_job_excerpt"] ?? ""),
                    ),
                );
                $data["menu_order"] = absint($_POST["agm_job_order"] ?? 0);
            }
        }
        if (
            in_array($data["post_status"] ?? "", ["publish", "future"], true) &&
            (trim(wp_strip_all_tags($data["post_title"])) === "" ||
                trim(wp_strip_all_tags($data["post_excerpt"])) === "")
        ) {
            $data["post_status"] = "draft";
            if (is_admin() && get_current_user_id()) {
                set_transient(
                    "agm_job_validation_" . get_current_user_id(),
                    "Вакансия сохранена как черновик. Для публикации заполните название должности и короткое описание.",
                    60,
                );
            }
        }
        return $data;
    },
    30,
    2,
);
add_filter(
    "rest_pre_insert_agm_job",
    function ($prepared, $request) {
        $existing = !empty($request["id"])
            ? get_post((int) $request["id"])
            : null;
        $status = $prepared->post_status ?? ($existing->post_status ?? "draft");
        if (
            in_array($status, ["publish", "future"], true) &&
            (trim(
                wp_strip_all_tags(
                    $prepared->post_title ?? ($existing->post_title ?? ""),
                ),
            ) === "" ||
                trim(
                    wp_strip_all_tags(
                        $prepared->post_excerpt ??
                            ($existing->post_excerpt ?? ""),
                    ),
                ) === "")
        ) {
            return new WP_Error(
                "agm_job_required",
                "Для публикации нужны название должности и короткое описание.",
                ["status" => 400],
            );
        }
        return $prepared;
    },
    10,
    2,
);
add_action("admin_notices", function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== "agm_job") {
        return;
    }
    $message = get_transient("agm_job_validation_" . get_current_user_id());
    if ($message) {
        delete_transient("agm_job_validation_" . get_current_user_id());
        echo '<div class="notice notice-warning"><p>' .
            esc_html($message) .
            "</p></div>";
    }
});
add_filter("post_updated_messages", function ($messages) {
    global $post;
    $published =
        $post &&
        $post->post_type === "agm_job" &&
        $post->post_status === "publish";
    $messages["agm_job"] = [
        0 => "",
        1 => "Вакансия сохранена.",
        2 => "Поле обновлено.",
        3 => "Поле удалено.",
        4 => "Вакансия обновлена.",
        5 => "Предыдущая версия восстановлена.",
        6 => $published
            ? "Вакансия опубликована."
            : "Вакансия сохранена как черновик.",
        7 => "Вакансия сохранена.",
        8 => "Вакансия отправлена на проверку.",
        9 => "Публикация вакансии запланирована.",
        10 => "Черновик вакансии сохранён.",
    ];
    return $messages;
});

function agm_job_duplicate($id)
{
    $source = get_post($id);
    $type = get_post_type_object("agm_job");
    if (
        !$source ||
        $source->post_type !== "agm_job" ||
        !in_array(
            $source->post_status,
            ["publish", "draft", "pending", "future", "private"],
            true,
        ) ||
        !current_user_can($type->cap->create_posts) ||
        !current_user_can("read_post", $id)
    ) {
        return new WP_Error(
            "forbidden",
            "Нет доступа к созданию копии этой вакансии.",
        );
    }
    $before = $_POST;
    $_POST = [];
    try {
        $copy = wp_insert_post(
            wp_slash([
                "post_type" => "agm_job",
                "post_status" => "draft",
                "post_title" => $source->post_title . " — копия",
                "post_excerpt" => $source->post_excerpt,
                "post_content" => $source->post_content,
                "menu_order" => agm_job_next_order(),
            ]),
            true,
        );
        if (is_wp_error($copy)) {
            return $copy;
        }
        foreach (agm_field_schema("agm_job") as $key => $field) {
            if (metadata_exists("post", $id, "_autobus_" . $key)) {
                update_post_meta(
                    $copy,
                    "_autobus_" . $key,
                    get_post_meta($id, "_autobus_" . $key, true),
                );
            }
        }
        return $copy;
    } finally {
        $_POST = $before;
    }
}
add_action("admin_post_agm_job_duplicate", function () {
    $id = absint($_GET["job"] ?? 0);
    check_admin_referer("agm_job_duplicate_" . $id);
    $copy = agm_job_duplicate($id);
    if (is_wp_error($copy)) {
        wp_die(esc_html($copy->get_error_message()), "", ["response" => 403]);
    }
    wp_safe_redirect(
        admin_url("post.php?post=" . absint($copy) . "&action=edit"),
    );
    exit();
});
add_filter(
    "post_row_actions",
    function ($actions, $post) {
        if ($post->post_type !== "agm_job" || $post->post_status === "trash") {
            return $actions;
        }
        if (
            current_user_can(
                get_post_type_object("agm_job")->cap->create_posts,
            ) &&
            current_user_can("read_post", $post->ID)
        ) {
            $actions["agm_job_copy"] =
                '<a href="' .
                esc_url(
                    wp_nonce_url(
                        admin_url(
                            "admin-post.php?action=agm_job_duplicate&job=" .
                                $post->ID,
                        ),
                        "agm_job_duplicate_" . $post->ID,
                    ),
                ) .
                '">Копия в черновик</a>';
        }
        if ($post->post_status === "publish") {
            $actions["agm_job_view"] =
                '<a href="' .
                esc_url(agm_job_public_url($post->ID)) .
                '" target="_blank" rel="noopener">Посмотреть карточку</a>';
        }
        return $actions;
    },
    20,
    2,
);
add_filter(
    "manage_agm_job_posts_columns",
    fn($columns) => [
        "cb" => $columns["cb"],
        "title" => "Должность",
        "agm_job_status" => "Состояние",
        "agm_job_salary" => "Оплата",
        "agm_job_schedule" => "График",
        "agm_job_order" => "Порядок",
        "date" => $columns["date"],
    ],
);
add_action(
    "manage_agm_job_posts_custom_column",
    function ($column, $id) {
        if ($column === "agm_job_order") {
            echo (int) get_post_field("menu_order", $id);
        } elseif ($column === "agm_job_status") {
            echo esc_html(
                [
                    "publish" => "На сайте",
                    "draft" => "Черновик",
                    "pending" => "На проверке",
                    "future" => "Запланирована",
                    "private" => "Личная",
                    "trash" => "В корзине",
                ][get_post_status($id)] ?? get_post_status($id),
            );
        } elseif (
            in_array($column, ["agm_job_salary", "agm_job_schedule"], true)
        ) {
            echo esc_html(
                get_post_meta(
                    $id,
                    "_autobus_" . str_replace("agm_job_", "", $column),
                    true,
                ) ?:
                "—",
            );
        }
    },
    10,
    2,
);
add_filter("manage_edit-agm_job_sortable_columns", function ($columns) {
    $columns["agm_job_order"] = "menu_order";
    return $columns;
});
add_action("admin_enqueue_scripts", function () {
    $screen = get_current_screen();
    if (
        !$screen ||
        $screen->post_type !== "agm_job" ||
        $screen->base !== "post"
    ) {
        return;
    }
    $base = dirname(__DIR__) . "/autobus03-content.php";
    wp_enqueue_style(
        "agm-job-editor",
        plugins_url("assets/job-editor.css", $base),
        [],
        (string) filemtime(dirname(__DIR__) . "/assets/job-editor.css"),
    );
    wp_enqueue_script(
        "agm-job-editor",
        plugins_url("assets/job-editor.js", $base),
        [],
        (string) filemtime(dirname(__DIR__) . "/assets/job-editor.js"),
        true,
    );
});
