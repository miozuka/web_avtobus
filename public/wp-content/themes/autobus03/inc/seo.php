<?php
defined('ABSPATH') || exit();
add_action(
    'wp_head',
    function () {
        if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) {
            return;
        }
        $description = is_front_page()
            ? autobus_value('seo_description')
            : (is_singular()
                ? wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 28, '')
                : get_bloginfo('description'));
        if (is_page('services')) {
            $description = autobus_value('sp_seo_description');
        }
        if (is_page('schedule')) {
            $description = autobus_value('rp_seo_description');
        }
        if (is_page('about')) {
            $description = autobus_value('ap_seo_description');
        }
        if (is_page('vacancy')) {
            $description = autobus_value('vp_seo_description');
        }
        if (is_page('contact')) {
            $description = autobus_value('cp_seo_description');
        }
        if (is_home()) {
            $description = autobus_value('np_text');
        }
        if (is_page_template('templates/timetable.php')) {
            $description =
                'График автобуса №' .
                get_post_meta(get_queried_object_id(), '_autobus_tt_number', true) .
                ': ' .
                get_the_title() .
                '. Отправления по направлениям в будни, субботу и воскресенье.';
        }
        $url = is_front_page()
            ? home_url('/')
            : (is_singular()
                ? get_permalink()
                : get_post_type_archive_link('post'));
        $image = is_front_page() ? absint(autobus_value('hero_image')) : get_post_thumbnail_id();
        if (is_home()) {
            $url = get_permalink(get_option('page_for_posts'));
            $image = get_post_thumbnail_id(absint(autobus_value('np_featured')));
        }
        if (is_page('services')) {
            $image = absint(autobus_value('sp_image'));
        }
        ?>
    <meta name="description" content="<?php echo esc_attr($description); ?>">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="<?php echo esc_attr(wp_get_document_title()); ?>">
    <meta property="og:description" content="<?php echo esc_attr($description); ?>">
    <meta property="og:type" content="<?php echo is_singular('post') ? 'article' : 'website'; ?>">
    <meta property="og:url" content="<?php echo esc_url($url); ?>">
    <?php if ($image): ?><meta property="og:image" content="<?php echo esc_url(
    wp_get_attachment_image_url($image, 'full'),
); ?>"><?php endif; ?>
    <?php
    },
    5,
);
