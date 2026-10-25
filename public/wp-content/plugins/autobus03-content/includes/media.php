<?php
defined('ABSPATH') || exit();
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ($screen && 'agm_document' === $screen->post_type && 'post' === $screen->base) {
        wp_enqueue_media();
        wp_enqueue_script(
            'agm-documents',
            plugins_url('assets/documents.js', dirname(__DIR__) . '/autobus03-content.php'),
            ['media-editor'],
            filemtime(dirname(__DIR__) . '/assets/documents.js'),
            true,
        );
    }
});
