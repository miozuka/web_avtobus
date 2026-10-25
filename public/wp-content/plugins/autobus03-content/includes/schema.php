<?php
defined("ABSPATH") || exit();
function agm_timetable_day_schema()
{
    return [
        "weekday" => "Будни",
        "weekend" => "Выходные",
        "saturday" => "Суббота",
        "sunday" => "Воскресенье",
        "weekday_saturday" => "Будни и суббота",
        "daily" => "Ежедневно",
        "unspecified" => "По графику",
    ];
}
function agm_direction_day_schema($direction)
{
    // Каждое заполненное поле имеет собственный вариант на сайте. Другие дни его не скрывают.
    return agm_timetable_day_schema();
}
function agm_sanitize_times($value)
{
    $times = [];
    foreach (preg_split("/[\s,;]+/u", (string) $value) as $token) {
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $token, $match)) {
            $times[] = sprintf("%02d:%02d", $match[1], $match[2]);
        }
    }
    $times = array_unique($times);
    sort($times);
    return implode("\n", $times);
}
// Типы материалов сайта. Права и поля нового типа задаются в соседних схемах плагина.
function agm_types()
{
    return [
        "agm_fleet" => "Автопарк",
        "agm_service" => "Услуги",
        "agm_tariff" => "Тарифы проезда",
        "agm_route" => "Маршруты",
        "agm_departure" => "Графики отправления",
        "agm_faq" => "Вопросы пассажиров",
        "agm_info" => "Сведения",
        "agm_stat" => "Предприятие: показатели",
        "agm_leader" => "Предприятие: руководство",
        "agm_history" => "Предприятие: история",
        "agm_slide" => "Предприятие: фотографии",
        "agm_document" => "Предприятие: документы",
        "agm_job" => "Вакансии предприятия",
        "agm_benefit" => "Преимущества работы",
        "agm_contact" => "Контакты: службы",
        "agm_contact_leader" => "Контакты: руководство",
    ];
}
function agm_field_schema($type)
{
    return match ($type) {
        "agm_job" => [
            "badge" => ["Метка вакансии", "text"],
            "salary" => ["Заработная плата: например от 100 000 ₽", "text"],
            "salary_note" => ["Пояснение к оплате", "text"],
            "schedule" => ["График работы", "text"],
            "features" => [
                "Короткие преимущества: по одному на строку",
                "job_features",
            ],
            "duties" => ["Обязанности", "textarea"],
            "requirements" => ["Требования к кандидату", "textarea"],
            "conditions" => ["Условия работы", "textarea"],
            "phone" => ["Телефон вакансии (необязательно)", "tel"],
            "url" => ["Ссылка на вакансию (необязательно)", "url"],
        ],
        "agm_contact" => [
            "phone" => ["Телефон", "tel"],
            "group" => ["Группа справочника", "text"],
            "featured" => ["Показывать в верхних карточках", "number"],
            "label" => ["Метка верхней карточки", "text"],
            "card_title" => ["Название верхней карточки", "text"],
            "badge" => ["Подпись режима работы", "text"],
            "icon" => ["Иконка", "icon"],
        ],
        "agm_contact_leader" => [
            "role" => ["Должность", "text"],
            "initials" => ["Инициалы", "text"],
        ],
        "agm_benefit" => ["icon" => ["Иконка", "icon"]],
        "agm_stat" => [
            "value" => ["Значение показателя", "text"],
            "icon" => ["Иконка", "icon"],
        ],
        "agm_leader" => [
            "role" => ["Должность", "text"],
            "phone" => ["Телефон (необязательно)", "tel"],
        ],
        "agm_history" => [
            "date" => ["Год или дата события", "text"],
            "highlight" => ["Выделить событие", "number"],
        ],
        "agm_slide" => [
            "group" => ["Галерея фотографий", "text"],
            "badge" => ["Метка на фотографии", "text"],
        ],
        "agm_document" => ["file" => ["PDF-документ из медиатеки", "document"]],
        "agm_fleet" => [
            "count" => ["Количество машин", "number"],
            "badge" => ["Метка на фотографии", "text"],
        ],
        "agm_service" => [
            "url" => ["Адрес ссылки на главной", "url"],
            "link_text" => ["Текст ссылки на главной", "text"],
            "icon" => ["Иконка", "icon"],
            "detail_title" => [
                "Заголовок на странице услуг (необязательно)",
                "text",
            ],
            "price" => ["Цена или диапазон, без знака рубля", "text"],
            "unit" => ["Единица цены", "text"],
            "phone" => ["Телефон для заказа", "tel"],
        ],
        "agm_tariff" => [
            "price" => ["Цена, без знака рубля", "text"],
            "unit" => ["Единица цены", "text"],
            "badge" => ["Метка тарифа", "text"],
            "icon" => ["Иконка", "icon"],
        ],
        "agm_departure" => array_merge(
            ["page_id" => ["Страница графика маршрута", "route_page"]],
            array_map(
                fn($label) => [
                    $label . ": время ЧЧ:ММ, через пробел или с новой строки",
                    "times",
                ],
                agm_timetable_day_schema(),
            ),
        ),
        "page" => [
            "tt_number" => ["Номер маршрута", "text"],
            "tt_badge" => ["Метка маршрута", "text"],
            "tt_description" => ["Подпись под направлением", "text"],
            "tt_note" => ["Примечание графика", "text"],
            "tt_source" => ["Ссылка на исходное расписание", "url"],
            "tt_imported" => ["Подпись даты данных", "text"],
        ],
        "agm_route" => [
            "number" => ["Номер маршрута, например 3К", "text"],
            "group" => ["Тип маршрута", "text"],
            "url" => ["Ссылка на подробное расписание", "url"],
            "transfer" => ["Пересадочный тариф: 1 — да, 0 — нет", "number"],
            "kind" => [
                "Подпись типа: Городской, Кольцевой или Пригородный (необязательно)",
                "text",
            ],
            "stops" => [
                "Дополнительные остановки и названия для поиска",
                "text",
            ],
        ],
        "agm_info" => [
            "url" => ["Адрес ссылки", "url"],
            "link_text" => ["Текст ссылки", "text"],
            "icon" => ["Иконка", "icon"],
        ],
        default => [],
    };
}
