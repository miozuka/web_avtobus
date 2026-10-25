<?php
defined('ABSPATH') || exit();

// Карточки работают в стандартном редакторе WordPress, без спрятанных панелей блоков.
function agm_simple_card_types()
{
    return array_diff(array_keys(agm_types()), ['agm_route', 'agm_departure', 'agm_job']);
}
function agm_card_public_path($type)
{
    return match ($type) {
        'agm_fleet', 'agm_faq', 'agm_info' => '/',
        'agm_service', 'agm_tariff' => '/services/',
        'agm_benefit' => '/vacancy/',
        'agm_contact', 'agm_contact_leader' => '/contact/',
        default => '/about/',
    };
}
add_filter(
    'use_block_editor_for_post_type',
    function ($enabled, $type) {
        return in_array($type, agm_simple_card_types(), true) ? false : $enabled;
    },
    20,
    2,
);
add_action('edit_form_after_title', function ($post) {
    if (!in_array($post->post_type, agm_simple_card_types(), true)) {
        return;
    }
    echo '<input type="hidden" name="agm_card_editor" value="1">';
    $help = match ($post->post_type) {
        'agm_faq'
            => 'Верхнее поле — вопрос пассажира, редактор ниже — ответ. Можно добавлять абзацы и ссылки.',
        'agm_document'
            => 'Название документа напишите сверху. В настройках выберите PDF из медиатеки. Файл не нужно загружать на сервер вручную.',
        'agm_slide' => 'Выберите фотографию записи и галерею. Подпись пишите в кратком описании.',
        'agm_fleet'
            => 'Укажите модель автобуса, количество машин и фотографию. Общая численность обновится автоматически.',
        'agm_tariff'
            => 'Название, цена, единица оплаты и короткое пояснение. Знак рубля добавляется оформлением сайта.',
        'agm_contact'
            => 'Название службы, телефон и группа справочника. Флажок верхней карточки включает её дополнительные подписи.',
        default
            => 'Заполните название, необходимые настройки и описание. Кнопка «Обновить» меняет опубликованную карточку сразу; черновик посетителям не виден.',
    };
    echo '<div class="agm-admin-help"><strong>Как заполнить</strong><p>' .
        esc_html($help) .
        '</p><a href="' .
        esc_url(home_url(agm_card_public_path($post->post_type))) .
        '" target="_blank" rel="noopener">Открыть раздел на сайте</a></div>';
    if (
        in_array(
            $post->post_type,
            ['agm_faq', 'agm_document', 'agm_stat', 'agm_contact_leader'],
            true,
        )
    ) {
        echo '<textarea name="excerpt" hidden>' . esc_textarea($post->post_excerpt) . '</textarea>';
    }
});
add_action(
    'add_meta_boxes',
    function ($type) {
        if (!in_array($type, agm_simple_card_types(), true)) {
            return;
        }
        remove_meta_box('pageparentdiv', $type, 'side');
        if (!in_array($type, ['agm_fleet', 'agm_slide', 'agm_leader'], true)) {
            remove_meta_box('postimagediv', $type, 'side');
        }
        if (in_array($type, ['agm_faq', 'agm_document', 'agm_stat', 'agm_contact_leader'], true)) {
            remove_meta_box('postexcerpt', $type, 'normal');
        }
        add_meta_box(
            'agm_card_position',
            'Порядок и публикация',
            function ($post) {
                echo '<p><label for="agm-card-order">Порядок в разделе</label><input class="small-text" id="agm-card-order" name="menu_order" type="number" min="0" step="1" value="' .
                    max(0, (int) $post->menu_order) .
                    '"></p><p class="description">Меньшее число показывается раньше. Одинаковые числа не удаляют карточки.</p><p>Чтобы временно убрать карточку с сайта, выберите статус «Черновик» и сохраните. Для удаления используйте корзину — из неё можно восстановить материал.</p>';
            },
            $type,
            'side',
        );
    },
    40,
);
function agm_card_copy_draft($id)
{
    $source = get_post($id);
    if (!$source || !in_array($source->post_type, agm_simple_card_types(), true)) {
        return new WP_Error('type', 'Карточка не найдена.');
    }
    $obj = get_post_type_object($source->post_type);
    if (
        !current_user_can($obj->cap->create_posts) ||
        (!current_user_can('edit_post', $id) && $source->post_status !== 'publish')
    ) {
        return new WP_Error('forbidden', 'Нет прав на копирование этой карточки.');
    }
    $new = wp_insert_post(
        wp_slash([
            'post_type' => $source->post_type,
            'post_status' => 'draft',
            'post_title' => $source->post_title . ' — копия',
            'post_content' => $source->post_content,
            'post_excerpt' => $source->post_excerpt,
            'menu_order' => $source->menu_order + 1,
            'post_author' => get_current_user_id(),
        ]),
        true,
    );
    if (is_wp_error($new)) {
        return $new;
    }
    foreach (agm_field_schema($source->post_type) as $key => $field) {
        update_post_meta($new, '_autobus_' . $key, get_post_meta($id, '_autobus_' . $key, true));
    }
    if (has_post_thumbnail($id)) {
        set_post_thumbnail($new, get_post_thumbnail_id($id));
    }
    return $new;
}
add_action('admin_post_agm_card_copy', function () {
    $id = absint($_GET['post'] ?? 0);
    check_admin_referer('agm_card_copy_' . $id);
    $new = agm_card_copy_draft($id);
    if (is_wp_error($new)) {
        wp_die(esc_html($new->get_error_message()), '', ['response' => 403]);
    }
    wp_safe_redirect(get_edit_post_link($new, 'raw'));
    exit();
});
add_filter(
    'post_row_actions',
    function ($actions, $post) {
        if (!in_array($post->post_type, agm_simple_card_types(), true)) {
            return $actions;
        }
        $obj = get_post_type_object($post->post_type);
        if (
            current_user_can($obj->cap->create_posts) &&
            ($post->post_status === 'publish' || current_user_can('edit_post', $post->ID))
        ) {
            $actions['agm_copy'] =
                '<a href="' .
                esc_url(
                    wp_nonce_url(
                        admin_url('admin-post.php?action=agm_card_copy&post=' . $post->ID),
                        'agm_card_copy_' . $post->ID,
                    ),
                ) .
                '">Копия в черновик</a>';
        }
        $actions['agm_section'] =
            '<a href="' .
            esc_url(home_url(agm_card_public_path($post->post_type))) .
            '" target="_blank" rel="noopener">Посмотреть раздел</a>';
        return $actions;
    },
    20,
    2,
);
add_action('init', function () {
    foreach (agm_simple_card_types() as $type) {
        add_filter('manage_' . $type . '_posts_columns', function ($cols) use ($type) {
            unset($cols['author'], $cols['comments']);
            $cols['agm_card_order'] = 'Порядок';
            $cols['agm_card_info'] = 'Основные сведения';
            return $cols;
        });
        add_action(
            'manage_' . $type . '_posts_custom_column',
            function ($column, $id) use ($type) {
                if ($column === 'agm_card_order') {
                    echo (int) get_post($id)->menu_order;
                }
                if ($column === 'agm_card_info') {
                    foreach (agm_field_schema($type) as $key => [$label, $kind]) {
                        if (
                            !in_array(
                                $key,
                                [
                                    'phone',
                                    'price',
                                    'count',
                                    'role',
                                    'value',
                                    'date',
                                    'group',
                                    'file',
                                ],
                                true,
                            )
                        ) {
                            continue;
                        }
                        $value = get_post_meta($id, '_autobus_' . $key, true);
                        if ($key === 'group') {
                            $value =
                                [
                                    'modern' => 'Современные фото',
                                    'archive' => 'Архивные фото',
                                    'management' => 'Управление',
                                    'operations' => 'Движение и эксплуатация',
                                ][$value] ?? $value;
                        }
                        if ($key === 'file') {
                            $value = $value ? get_the_title((int) $value) : 'PDF не выбран';
                        }
                        if ($value !== '') {
                            echo '<div>' . esc_html($label . ': ' . $value) . '</div>';
                        }
                    }
                }
            },
            10,
            2,
        );
    }
});

add_filter(
    'wp_editor_settings',
    function ($settings, $id) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $type = $screen->post_type ?? '';
        if ($id === 'content' && in_array($type, agm_simple_card_types(), true)) {
            $settings['quicktags'] = false;
            if (!in_array($type, ['agm_faq', 'agm_service'], true)) {
                $settings['tinymce'] = false;
            }
        }
        return $settings;
    },
    20,
    2,
);
add_filter('admin_body_class', function ($classes) {
    $s = get_current_screen();
    if (
        $s &&
        in_array($s->base, ['post', 'edit'], true) &&
        in_array($s->post_type, agm_simple_card_types(), true)
    ) {
        $classes .=
            ' agm-simple-card-page' .
            (in_array($s->post_type, ['agm_faq', 'agm_service'], true) ? ' agm-full-editor' : '');
    }
    return $classes;
});

// Неполная карточка остаётся черновиком, чтобы сотрудник не получил пустой блок на сайте.
function agm_card_missing_fields($type, $title, $content, $fields, $image = 0)
{
    $missing = [];
    if (trim(wp_strip_all_tags($title)) === '') {
        $missing[] = 'Название карточки';
    }
    if ($type === 'agm_faq' && trim(wp_strip_all_tags($content)) === '') {
        $missing[] = 'Ответ на вопрос';
    }
    if ($type === 'agm_tariff' && trim((string) ($fields['price'] ?? '')) === '') {
        $missing[] = 'Цена';
    }
    if (
        $type === 'agm_document' &&
        get_post_mime_type(absint($fields['file'] ?? 0)) !== 'application/pdf'
    ) {
        $missing[] = 'PDF-документ';
    }
    if ($type === 'agm_slide' && !wp_attachment_is_image((int) $image)) {
        $missing[] = 'Фотография записи';
    }
    return $missing;
}
add_filter(
    'wp_insert_post_data',
    function ($data, $postarr) {
        $type = $data['post_type'] ?? '';
        $id = (int) ($postarr['ID'] ?? 0);
        if (
            !in_array($type, agm_simple_card_types(), true) ||
            empty($_POST['agm_card_editor']) ||
            empty($_POST['agm_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['agm_nonce'])), 'agm_save') ||
            !current_user_can('edit_post', $id)
        ) {
            return $data;
        }
        $key = 'agm_card_missing_' . get_current_user_id() . '_' . $id;
        delete_transient($key);
        if (!in_array($data['post_status'] ?? '', ['publish', 'future'], true)) {
            return $data;
        }
        $fields = wp_unslash($_POST['agm'] ?? []);
        if (!is_array($fields)) {
            $fields = [];
        }
        $missing = agm_card_missing_fields(
            $type,
            wp_unslash($data['post_title']),
            wp_unslash($data['post_content']),
            $fields,
            get_post_thumbnail_id($id),
        );
        if ($missing) {
            $data['post_status'] = 'draft';
            set_transient($key, $missing, 120);
        }
        return $data;
    },
    30,
    2,
);
add_filter(
    'redirect_post_location',
    function ($location, $id) {
        return get_transient('agm_card_missing_' . get_current_user_id() . '_' . $id)
            ? add_query_arg('message', 10, $location)
            : $location;
    },
    20,
    2,
);
add_action('admin_notices', function () {
    $s = get_current_screen();
    $p = $GLOBALS['post'] ?? null;
    if (!$s || $s->base !== 'post' || !$p) {
        return;
    }
    $key = 'agm_card_missing_' . get_current_user_id() . '_' . $p->ID;
    $missing = get_transient($key);
    if (!$missing) {
        return;
    }
    delete_transient($key);
    echo '<div class="notice notice-warning"><p><strong>Карточка сохранена в черновиках.</strong> Для публикации заполните: ' .
        esc_html(implode(', ', $missing)) .
        '. Данные не потеряны; после заполнения нажмите «Опубликовать».</p></div>';
});
foreach (agm_simple_card_types() as $type) {
    add_filter(
        'rest_pre_insert_' . $type,
        function ($prepared, $request) use ($type) {
            $old = $request['id'] ? get_post((int) $request['id']) : null;
            $status = $prepared->post_status ?? ($old->post_status ?? 'draft');
            if (!in_array($status, ['publish', 'future'], true)) {
                return $prepared;
            }
            $fields = [];
            foreach (agm_field_schema($type) as $key => $field) {
                $fields[$key] =
                    $request->get_param('meta')['_autobus_' . $key] ??
                    ($old ? get_post_meta($old->ID, '_autobus_' . $key, true) : '');
            }
            $missing = agm_card_missing_fields(
                $type,
                $prepared->post_title ?? ($old->post_title ?? ''),
                $prepared->post_content ?? ($old->post_content ?? ''),
                $fields,
                $request->get_param('featured_media') ??
                    ($old ? get_post_thumbnail_id($old->ID) : 0),
            );
            return $missing
                ? new WP_Error(
                    'agm_card_incomplete',
                    'Для публикации заполните: ' . implode(', ', $missing),
                    ['status' => 400],
                )
                : $prepared;
        },
        20,
        2,
    );
}

function agm_icon_choices($current = '')
{
    $icons = [
        'bus' => 'Автобус',
        'ad' => 'Объявление',
        'check' => 'Гарантия / щит',
        'medical' => 'Медицина',
        'ticket' => 'Билет',
        'card' => 'Проездной',
        'graduate' => 'Обучение и льготы',
        'wrench' => 'Ремонт',
        'eye' => 'Доступность',
        'pin' => 'Адрес',
        'mail' => 'Почта',
        'phone' => 'Телефон',
        'government' => 'Организация',
        'document' => 'Документ',
        'share' => 'Поделиться',
    ];
    if ($current !== '' && !isset($icons[$current])) {
        $icons[$current] = 'Сохранённый значок (можно оставить)';
    }
    return $icons;
}
