<?php
defined('ABSPATH') || exit();
// Схема полей для админки. Добавляя поле, предусмотрите его вывод в нужном шаблоне.
function autobus_settings_schema()
{
    return [
        'contact_page' => autobus_contact_settings(),
        'vacancy_page' => autobus_vacancy_settings(),
        'about_page' => autobus_about_settings(),
        'news_page' => [
            'label' => 'Страница новостей',
            'fields' => [
                'np_eyebrow' => 'Метка',
                'np_title' => 'Заголовок',
                'np_text' => 'Описание',
                'np_featured' => ['Главная новость', 'post'],
                'np_featured_label' => 'Метка главной новости',
                'np_author' => 'Подпись пресс-службы',
                'np_read' => 'Кнопка чтения',
                'np_empty' => 'Нет публикаций: текст',
                'np_subscribe_eyebrow' => 'Подписка: метка',
                'np_subscribe_title' => 'Подписка: заголовок',
                'np_subscribe_text' => 'Подписка: описание',
            ],
        ],
        'timetable_page' => [
            'label' => 'Страница графика маршрута',
            'fields' => [
                'tt_directions_label' => 'Подпись направлений',
                'tt_days_label' => 'Подпись дней недели',
                'tt_weekday_label' => 'Будни: подпись',
                'tt_weekend_label' => 'Выходные: подпись',
                'tt_saturday_label' => 'Суббота: подпись',
                'tt_sunday_label' => 'Воскресенье: подпись',
                'tt_daily_label' => 'Ежедневно: подпись',
                'tt_weekday_saturday_label' => 'Будни и суббота: подпись',
                'tt_unspecified_label' => 'Дни не указаны: подпись',
                'tt_print_label' => 'Кнопка печати',
                'tt_source_label' => 'Подпись ссылки на источник',
                'tt_empty_label' => 'Текст при отсутствии рейсов',
            ],
        ],
        'schedule_page' => [
            'label' => 'Страница расписания',
            'fields' => [
                'rp_eyebrow' => 'Баннер: метка',
                'rp_title' => 'Баннер: заголовок',
                'rp_text' => 'Баннер: описание',
                'rp_live_label' => 'Онлайн: подпись кнопки',
                'rp_live_url' => ['Онлайн: ссылка', 'url'],
                'rp_map_label' => 'Схема: подпись кнопки',
                'rp_map_url' => ['Схема: ссылка', 'url'],
                'rp_search' => 'Подсказка поиска',
                'rp_city_title' => 'Городские: заголовок',
                'rp_city_text' => 'Городские: пояснение',
                'rp_summer_title' => 'Дачные: заголовок',
                'rp_summer_text' => 'Дачные: пояснение',
                'rp_season' => 'Метка сезона',
                'rp_seo_description' => 'SEO: описание',
            ],
        ],
        'hero' => [
            'label' => 'Главная: фотобаннер',
            'fields' => [
                'hero_eyebrow' => 'Метка',
                'hero_title' => 'Заголовок H1',
                'hero_text' => 'Описание',
                'hero_note' => 'Примечание',
                'hero_image' => ['Фотография', 'image'],
            ],
        ],
        'schedule' => [
            'label' => 'Главная: расписание',
            'fields' => [
                'schedule_eyebrow' => 'Метка',
                'schedule_title' => 'Заголовок',
                'schedule_subtitle' => 'Подзаголовок',
                'schedule_text' => 'Описание',
                'schedule_button' => 'Текст кнопки',
                'schedule_url' => ['Ссылка', 'url'],
                'schedule_image' => ['Фотография', 'image'],
            ],
        ],
        'fleet' => [
            'label' => 'Главная: автопарк',
            'fields' => [
                'fleet_eyebrow' => 'Метка',
                'fleet_title' => 'Заголовок',
                'fleet_unit' => 'Подпись количества',
                'fleet_note' => 'Метка под количеством',
                'fleet_text' => 'Описание',
            ],
        ],
        'quote' => [
            'label' => 'Главная: цитата',
            'fields' => [
                'quote_eyebrow' => 'Метка',
                'quote_text' => 'Текст цитаты',
                'quote_author' => 'Автор',
                'quote_role' => 'Должность',
                'quote_badge' => 'Подпись',
                'quote_image' => ['Портрет', 'image'],
            ],
        ],
        'services' => [
            'label' => 'Главная: услуги и новости',
            'fields' => [
                'services_title' => 'Услуги: заголовок',
                'services_text' => 'Услуги: описание',
                'news_eyebrow' => 'Новости: метка',
                'news_title' => 'Новости: заголовок',
                'news_button' => 'Все новости: текст',
                'news_read' => 'Карточка новости: ссылка',
            ],
        ],
        'faq' => [
            'label' => 'Главная: вопросы и предприятие',
            'fields' => [
                'faq_eyebrow' => 'Вопросы: метка',
                'faq_title' => 'Вопросы: заголовок',
                'about_eyebrow' => 'Предприятие: метка',
                'about_title' => 'Предприятие: заголовок',
                'about_text' => 'Предприятие: текст',
                'about_button' => 'Предприятие: кнопка',
                'about_url' => ['Предприятие: ссылка', 'url'],
                'about_image' => ['Предприятие: фото', 'image'],
            ],
        ],
        'info' => [
            'label' => 'Главная: сведения и партнёры',
            'fields' => [
                'info_eyebrow' => 'Сведения: метка',
                'info_title' => 'Сведения: заголовок',
                'partners_eyebrow' => 'Партнёры: метка',
                'partners_title' => 'Партнёры: заголовок',
                'partner_name' => 'Название партнёра',
                'partner_text' => 'Описание партнёра',
                'partner_url' => ['Ссылка партнёра', 'url'],
                'partner_image' => ['Логотип партнёра', 'image'],
            ],
        ],
        'feedback' => [
            'label' => 'Главная: Госуслуги',
            'fields' => [
                'feedback_eyebrow' => 'Метка',
                'feedback_title' => 'Заголовок',
                'feedback_text' => 'Описание',
                'feedback_button' => 'Кнопка',
                'feedback_url' => ['Ссылка на форму', 'url'],
            ],
        ],
        'contacts' => [
            'label' => 'Контакты и footer',
            'fields' => [
                'address' => 'Адрес',
                'phone' => 'Телефон приёмной',
                'hotline' => 'Горячая линия',
                'hotline_text' => 'Описание горячей линии',
                'email' => ['Email', 'email'],
                'footer_text' => 'Описание предприятия',
                'footer_passengers' => 'Заголовок меню пассажиров',
                'footer_company' => 'Заголовок меню предприятия',
                'footer_contacts' => 'Заголовок контактов',
                'footer_copyright' => 'Подпись copyright',
                'vk_url' => ['ВКонтакте', 'url'],
                'telegram_url' => ['Telegram', 'url'],
                'max_url' => ['MAX', 'url'],
                'privacy_url' => ['Политика конфиденциальности', 'url'],
                'agreement_url' => ['Пользовательское соглашение', 'url'],
            ],
        ],
        'seo' => ['label' => 'SEO главной', 'fields' => ['seo_description' => 'Meta description']],
        'services_page' => [
            'label' => 'Страница услуг',
            'fields' => [
                'sp_eyebrow' => 'Баннер: метка',
                'sp_title' => 'Баннер: заголовок',
                'sp_text' => 'Баннер: описание',
                'sp_image' => ['Баннер: фотография', 'image'],
                'sp_tariffs_eyebrow' => 'Тарифы: метка',
                'sp_tariffs_title' => 'Тарифы: заголовок',
                'sp_enterprise_eyebrow' => 'Услуги: метка',
                'sp_enterprise_title' => 'Услуги: заголовок',
                'sp_enterprise_text' => 'Услуги: описание',
                'sp_call_label' => 'Подпись кнопки звонка',
                'sp_document_eyebrow' => 'Документ: метка',
                'sp_document_title' => 'Документ: заголовок',
                'sp_document_text' => 'Документ: описание',
                'sp_document_button' => 'Документ: кнопка',
                'sp_document' => ['Документ из медиатеки', 'document'],
                'sp_seo_description' => 'SEO: описание страницы',
            ],
        ],
    ];
}
add_action('customize_register', function ($customizer) {
    $customizer->add_panel('autobus', [
        'title' => 'Городские маршруты: содержимое',
        'priority' => 30,
    ]);
    foreach (autobus_settings_schema() as $section => $config) {
        $customizer->add_section('autobus_' . $section, [
            'title' => $config['label'],
            'panel' => 'autobus',
        ]);
        foreach ($config['fields'] as $key => $field) {
            $label = is_array($field) ? $field[0] : $field;
            $type = is_array($field) ? $field[1] : 'textarea';
            $sanitize = match ($type) {
                'image', 'document', 'number', 'post' => 'absint',
                'url' => 'esc_url_raw',
                'email' => 'sanitize_email',
                default => 'sanitize_textarea_field',
            };
            $customizer->add_setting('autobus_' . $key, [
                'sanitize_callback' => $sanitize,
                'default' => '',
            ]);
            $control = ['label' => $label, 'section' => 'autobus_' . $section, 'type' => $type];
            if ('post' === $type) {
                $control['type'] = 'select';
                $control['choices'] = [0 => 'Без главной новости'];
                foreach (get_posts(['numberposts' => -1, 'post_status' => 'publish']) as $entry) {
                    $control['choices'][$entry->ID] = $entry->post_title;
                }
            }
            if (in_array($type, ['image', 'document'], true)) {
                $customizer->add_control(
                    new WP_Customize_Media_Control(
                        $customizer,
                        'autobus_' . $key,
                        array_merge($control, ['mime_type' => 'image' === $type ? 'image' : '']),
                    ),
                );
            } else {
                $customizer->add_control('autobus_' . $key, $control);
            }
        }
    }
});
