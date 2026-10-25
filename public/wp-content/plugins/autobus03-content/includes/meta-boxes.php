<?php
defined('ABSPATH') || exit();
add_action('add_meta_boxes', function () {
    $types = agm_types();
    if (
        isset($GLOBALS['post']) &&
        'templates/timetable.php' === get_page_template_slug($GLOBALS['post']->ID)
    ) {
        $types['page'] = 'График маршрута';
    }
    foreach ($types as $type => $label) {
        if (!agm_field_schema($type)) {
            continue;
        }
        add_meta_box(
            'agm_details',
            'Настройки карточки',
            function ($post) {
                wp_nonce_field('agm_save', 'agm_nonce');
                foreach (agm_field_schema($post->post_type) as $key => $field) {
                    if (
                        'agm_route' === $post->post_type &&
                        in_array($key, ['url', 'transfer'], true)
                    ) {
                        continue;
                    }
                    $value = get_post_meta($post->ID, '_autobus_' . $key, true);
                    echo '<div class="agm-card-field"><label for="agm_' .
                        esc_attr($key) .
                        '"><strong>' .
                        esc_html($field[0]) .
                        '</strong></label><br>';
                    if ('agm_route' === $post->post_type && 'group' === $key) {
                        echo '<select id="agm_group" name="agm[group]">';
                        foreach (
                            ['city' => 'Городской маршрут', 'summer' => 'Дачный маршрут']
                            as $group => $label
                        ) {
                            echo '<option value="' .
                                esc_attr($group) .
                                '" ' .
                                selected($value ?: 'city', $group, false) .
                                '>' .
                                esc_html($label) .
                                '</option>';
                        }
                        echo '</select>';
                    } elseif (
                        'group' === $key &&
                        in_array($post->post_type, ['agm_contact', 'agm_slide'], true)
                    ) {
                        $choices =
                            'agm_contact' === $post->post_type
                                ? [
                                    'management' => 'Руководство и управление',
                                    'operations' => 'Движение и эксплуатация',
                                ]
                                : [
                                    'modern' => 'Современные фотографии',
                                    'archive' => 'Архивные фотографии',
                                ];
                        echo '<select class="widefat" id="agm_group" name="agm[group]">';
                        foreach ($choices as $choice => $label) {
                            echo '<option value="' .
                                esc_attr($choice) .
                                '" ' .
                                selected($value, $choice, false) .
                                '>' .
                                esc_html($label) .
                                '</option>';
                        }
                        echo '</select>';
                    } elseif (
                        in_array($key, ['featured', 'highlight'], true) &&
                        'number' === $field[1]
                    ) {
                        echo '<input type="hidden" name="agm[' .
                            esc_attr($key) .
                            ']" value="0"><input id="agm_' .
                            esc_attr($key) .
                            '" type="checkbox" name="agm[' .
                            esc_attr($key) .
                            ']" value="1" ' .
                            checked((bool) $value, true, false) .
                            '> Да';
                    } elseif ('icon' === $field[1]) {
                        echo '<select id="agm_' .
                            esc_attr($key) .
                            '" name="agm[' .
                            esc_attr($key) .
                            ']">';
                        foreach (
                            function_exists('agm_icon_choices')
                                ? agm_icon_choices($value)
                                : [$value => $value]
                            as $icon => $label
                        ) {
                            echo '<option value="' .
                                esc_attr($icon) .
                                '" ' .
                                selected($value, $icon, false) .
                                '>' .
                                esc_html($label) .
                                '</option>';
                        }
                        echo '</select>';
                    } elseif ('route_page' === $field[1]) {
                        echo '<select class="widefat" id="agm_' .
                            esc_attr($key) .
                            '" name="agm[' .
                            esc_attr($key) .
                            ']">';
                        echo '<option value="0">Выберите маршрут</option>';
                        foreach (
                            get_posts([
                                'post_type' => 'page',
                                'post_status' => ['publish', 'draft'],
                                'numberposts' => -1,
                                'meta_key' => '_wp_page_template',
                                'meta_value' => 'templates/timetable.php',
                            ])
                            as $page
                        ) {
                            echo '<option value="' .
                                esc_attr($page->ID) .
                                '" ' .
                                selected($value, $page->ID, false) .
                                '>' .
                                esc_html($page->post_title) .
                                '</option>';
                        }
                        echo '</select>';
                    } elseif ('document' === $field[1]) {
                        echo '<div class="agm-document-field"><input id="agm_' .
                            esc_attr($key) .
                            '" type="hidden" name="agm[' .
                            esc_attr($key) .
                            ']" value="' .
                            absint($value) .
                            '"><p data-document-title>' .
                            esc_html($value ? get_the_title((int) $value) : 'PDF пока не выбран') .
                            '</p><button type="button" class="button agm-document-choose" data-input="agm_' .
                            esc_attr($key) .
                            '">Выбрать PDF</button> <button type="button" class="button-link-delete agm-document-clear" data-input="agm_' .
                            esc_attr($key) .
                            '">Убрать из карточки</button><p class="description">Выбирайте файл из медиатеки. Удаление из карточки не удаляет сам PDF.</p><p><a data-document-link href="' .
                            esc_url($value ? wp_get_attachment_url((int) $value) : '') .
                            '" target="_blank" rel="noopener" ' .
                            (!$value ? 'hidden' : '') .
                            '>Посмотреть выбранный документ</a></p></div>';
                    } elseif ('times' === $field[1]) {
                        echo '<textarea class="widefat" rows="12" id="agm_' .
                            esc_attr($key) .
                            '" name="agm[' .
                            esc_attr($key) .
                            ']">' .
                            esc_textarea($value) .
                            '</textarea>';
                    } else {
                        echo '<input class="widefat" id="agm_' .
                            esc_attr($key) .
                            '" type="' .
                            esc_attr($field[1]) .
                            '" name="agm[' .
                            esc_attr($key) .
                            ']" value="' .
                            esc_attr($value) .
                            '"' .
                            ('number' === $field[1] ? ' min="0" step="1"' : '') .
                            '>';
                    }
                    echo '</div>';
                }
                echo 'agm_route' === $post->post_type
                    ? '<p>Название записи — путь маршрута, например «115 микрорайон — ул. Советская». Направления и время добавьте ниже. Страница графика и ссылка создаются автоматически.</p>'
                    : '<p>Описание карточки — поле «Краткое описание карточки» (стандартный отрывок WordPress). Фото — «Изображение записи». Порядок — «Атрибуты записи → Порядок».</p>';
            },
            $type,
            'normal',
            'high',
        );
    }
});
