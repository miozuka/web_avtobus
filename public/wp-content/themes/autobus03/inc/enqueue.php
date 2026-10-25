<?php
defined('ABSPATH') || exit();
add_action('wp_enqueue_scripts', function () {
    $previous = [];
    foreach (
        [
            'variables',
            'reset',
            'typography',
            'components',
            'home',
            'footer',
            'accessibility',
            'responsive',
        ]
        as $name
    ) {
        $path = '/assets/css/' . $name . '.css';
        $handle = 'autobus-' . $name;
        wp_enqueue_style(
            $handle,
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
        $previous = [$handle];
    }
    if (is_home() || is_singular('post')) {
        $path = '/assets/css/news.css';
        wp_enqueue_style(
            'autobus-news',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
        if (is_singular('post')) {
            wp_enqueue_script(
                'autobus-news',
                get_template_directory_uri() . '/assets/js/news.js',
                [],
                (string) filemtime(get_template_directory() . '/assets/js/news.js'),
                ['strategy' => 'defer', 'in_footer' => true],
            );
        }
    }
    if (is_page(['ticket', 'travel_benefits'])) {
        $path = '/assets/css/passenger.css';
        wp_enqueue_style(
            'autobus-passenger',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
        wp_enqueue_script(
            'autobus-passenger',
            get_template_directory_uri() . '/assets/js/passenger.js',
            [],
            (string) filemtime(get_template_directory() . '/assets/js/passenger.js'),
            ['strategy' => 'defer', 'in_footer' => true],
        );
    }
    if (is_page('contact')) {
        $path = '/assets/css/contact.css';
        wp_enqueue_style(
            'autobus-contact',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
    }
    if (is_page('vacancy')) {
        $path = '/assets/css/vacancy.css';
        wp_enqueue_style(
            'autobus-vacancy',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
    }
    if (is_page('about')) {
        $path = '/assets/css/about.css';
        wp_enqueue_style(
            'autobus-about',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
        wp_enqueue_script(
            'autobus-about',
            get_template_directory_uri() . '/assets/js/about.js',
            [],
            (string) filemtime(get_template_directory() . '/assets/js/about.js'),
            ['strategy' => 'defer', 'in_footer' => true],
        );
    }
    if (is_page('schedule')) {
        $path = '/assets/css/schedule.css';
        wp_enqueue_style(
            'autobus-schedule',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
        wp_enqueue_script(
            'autobus-schedule',
            get_template_directory_uri() . '/assets/js/schedule.js',
            [],
            (string) filemtime(get_template_directory() . '/assets/js/schedule.js'),
            ['strategy' => 'defer', 'in_footer' => true],
        );
    }
    if (is_page_template('templates/timetable.php')) {
        $path = '/assets/css/timetable.css';
        wp_enqueue_style(
            'autobus-timetable',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
        wp_enqueue_script(
            'autobus-timetable',
            get_template_directory_uri() . '/assets/js/timetable.js',
            [],
            (string) filemtime(get_template_directory() . '/assets/js/timetable.js'),
            ['strategy' => 'defer', 'in_footer' => true],
        );
    }
    if (is_page('services')) {
        $path = '/assets/css/services.css';
        wp_enqueue_style(
            'autobus-services',
            get_template_directory_uri() . $path,
            $previous,
            (string) filemtime(get_template_directory() . $path),
        );
    }
    $compact_deps = array_filter(
        [
            'autobus-responsive',
            'autobus-services',
            'autobus-schedule',
            'autobus-timetable',
            'autobus-news',
            'autobus-about',
            'autobus-vacancy',
            'autobus-contact',
            'autobus-passenger',
        ],
        fn($handle) => wp_style_is($handle, 'enqueued'),
    );
    wp_enqueue_style(
        'autobus-compact',
        get_template_directory_uri() . '/assets/css/compact.css',
        array_values($compact_deps),
        (string) filemtime(get_template_directory() . '/assets/css/compact.css'),
    );
    wp_enqueue_style(
        'autobus-responsive-polish',
        get_template_directory_uri() . '/assets/css/responsive-polish.css',
        ['autobus-compact'],
        (string) filemtime(get_template_directory() . '/assets/css/responsive-polish.css'),
    );
    wp_enqueue_style(
        'autobus-effects',
        get_template_directory_uri() . '/assets/css/effects.css',
        ['autobus-responsive-polish'],
        (string) filemtime(get_template_directory() . '/assets/css/effects.css'),
    );
    wp_enqueue_script(
        'autobus-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        (string) filemtime(get_template_directory() . '/assets/js/main.js'),
        ['strategy' => 'defer', 'in_footer' => true],
    );
    wp_enqueue_script(
        'autobus-effects',
        get_template_directory_uri() . '/assets/js/effects.js',
        ['autobus-main'],
        (string) filemtime(get_template_directory() . '/assets/js/effects.js'),
        ['strategy' => 'defer', 'in_footer' => true],
    );
});
