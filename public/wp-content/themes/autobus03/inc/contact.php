<?php
defined('ABSPATH') || exit();
function autobus_contact_settings()
{
    return [
        'label' => 'Страница контактов',
        'fields' => [
            'cp_eyebrow' => 'Баннер: метка',
            'cp_title' => 'Баннер: заголовок',
            'cp_text' => 'Баннер: описание',
            'cp_directory_title' => 'Заголовок справочника',
            'cp_leaders_title' => 'Заголовок руководства',
            'cp_office_title' => 'Заголовок адреса',
            'cp_hours' => 'Режим работы администрации',
            'cp_break' => 'Перерыв и выходные',
            'cp_transport' => 'Как добраться транспортом',
            'cp_lost_title' => 'Бюро находок: заголовок',
            'cp_lost_text' => 'Бюро находок: текст',
            'cp_map_title' => 'Карта: заголовок',
            'cp_map_embed' => ['Яндекс Карта: встраивание', 'url'],
            'cp_map_url' => ['Яндекс Карта: ссылка', 'url'],
            'cp_map_2gis_url' => ['2ГИС: ссылка', 'url'],
            'cp_seo_description' => 'SEO: описание',
        ],
    ];
}
function autobus_contact_phone($post)
{
    return autobus_meta($post, 'phone');
}
