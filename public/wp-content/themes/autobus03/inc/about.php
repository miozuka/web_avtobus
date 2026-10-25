<?php
defined('ABSPATH') || exit();
function autobus_about_settings()
{
    return [
        'label' => 'Страница предприятия',
        'fields' => [
            'ap_eyebrow' => 'Баннер: метка',
            'ap_title' => 'Баннер: заголовок',
            'ap_subtitle' => 'Баннер: подзаголовок',
            'ap_text' => 'Баннер: описание',
            'ap_history_button' => 'Кнопка истории',
            'ap_documents_button' => 'Кнопка документов',
            'ap_quote_eyebrow' => 'Цитата мэра: метка',
            'ap_leaders_title' => 'Заголовок руководства',
            'ap_history_eyebrow' => 'История: метка',
            'ap_history_title' => 'История: заголовок',
            'ap_history_text' => 'История: описание',
            'ap_archive_eyebrow' => 'Архив: метка',
            'ap_archive_title' => 'Архив: заголовок',
            'ap_archive_text' => 'Архив: описание',
            'ap_documents_eyebrow' => 'Документы: метка',
            'ap_documents_title' => 'Документы: заголовок',
            'ap_documents_text' => 'Документы: описание',
            'ap_contact_eyebrow' => 'Контакты: метка',
            'ap_contact_title' => 'Контакты: название',
            'ap_contact_text' => 'Контакты: описание',
            'ap_seo_description' => 'SEO: описание страницы',
        ],
    ];
}
function autobus_about_posts($type)
{
    return get_posts([
        'post_type' => $type,
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);
}
function autobus_about_gallery($group)
{
    $slides = array_filter(
        autobus_about_posts('agm_slide'),
        fn($slide) => autobus_meta($slide, 'group') === $group && has_post_thumbnail($slide),
    );
    if (!$slides) {
        return;
    }
    $label = 'archive' === $group ? 'Архивные фотографии Улан-Удэ' : 'Предприятие сегодня';
    ?>
    <div class="about-gallery" data-about-gallery role="region" aria-roledescription="карусель" aria-label="<?php echo esc_attr(
        $label,
    ); ?>">
    <?php foreach (array_values($slides) as $index => $slide): ?>
    <figure <?php autobus_edit_record($slide->ID); ?> class="about-slide<?php echo 0 === $index
        ? ' is-active'
        : ''; ?>" data-slide aria-label="<?php echo esc_attr(
    $index + 1 . ' из ' . count($slides),
); ?>"><?php echo wp_get_attachment_image(get_post_thumbnail_id($slide), 'large', false, [
    'loading' => 'archive' === $group ? 'lazy' : 'eager',
]); ?><figcaption><?php if (
    autobus_meta($slide, 'badge')
): ?><span class="pill"><?php echo esc_html(
    autobus_meta($slide, 'badge'),
); ?></span><?php endif; ?><h2><?php echo esc_html(
    $slide->post_title,
); ?></h2><p><?php echo esc_html($slide->post_excerpt); ?></p></figcaption></figure>
    <?php endforeach; ?>
    <span class="about-gallery-counter" data-slide-count hidden></span>
    <div class="about-gallery-controls" hidden><div class="about-gallery-dots" role="group" aria-label="Выбор фотографии"><?php foreach (
        array_values($slides)
        as $index => $slide
    ): ?><button type="button" data-slide-dot="<?php echo esc_attr(
    $index,
); ?>" aria-label="<?php echo esc_attr(
    'Фотография ' . ($index + 1) . ': ' . $slide->post_title,
); ?>" aria-pressed="<?php echo 0 === $index
    ? 'true'
    : 'false'; ?>"></button><?php endforeach; ?></div></div>
    <div class="about-gallery-progress" aria-hidden="true"><span></span></div>
    </div>
    <?php
}
