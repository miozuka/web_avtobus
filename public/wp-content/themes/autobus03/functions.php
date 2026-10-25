<?php
defined('ABSPATH') || exit();
foreach (
    [
        'setup',
        'enqueue',
        'about',
        'vacancy',
        'contact',
        'passenger',
        'customizer',
        'helpers',
        'seo',
        'cookies',
    ]
    as $module
) {
    require_once get_template_directory() . '/inc/' . $module . '.php';
}
