<?php
defined('ABSPATH') || exit();
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_home()) {
        $query->set('posts_per_page', 6);
        $query->set('ignore_sticky_posts', true);
        $featured = absint(autobus_value('np_featured'));
        $entry = $featured ? get_post($featured) : null;
        if ($entry && 'publish' === $entry->post_status && 'post' === $entry->post_type) {
            $query->set('post__not_in', [$featured]);
        }
    }
});
add_filter(
    'wp_nav_menu_objects',
    function ($items, $args) {
        if (
            'primary' === ($args->theme_location ?? '') &&
            is_page_template('templates/timetable.php')
        ) {
            foreach ($items as $item) {
                if (untrailingslashit($item->url) === untrailingslashit(home_url('/schedule/'))) {
                    $item->classes[] = 'current-menu-item';
                }
            }
        }
        if ('primary' === ($args->theme_location ?? '') && is_singular('post')) {
            $news_url = get_permalink(get_option('page_for_posts'));
            foreach ($items as $item) {
                if (untrailingslashit($item->url) === untrailingslashit($news_url)) {
                    $item->classes[] = 'current-menu-item';
                }
            }
        }
        return $items;
    },
    10,
    2,
);
add_action('after_setup_theme', function () {
    load_theme_textdomain('autobus03', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height' => 96,
        'width' => 96,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style(['assets/css/variables.css', 'assets/css/typography.css']);
    register_nav_menus([
        'primary' => 'Основное меню',
        'passengers' => 'Footer: пассажирам',
        'company' => 'Footer: предприятие',
    ]);
    add_image_size('autobus-card', 800, 500, true);
    add_image_size('autobus-banner', 1920, 900, true);
});
