<?php
/**
 * Plugin Name: Городские маршруты — содержимое
 * Description: Редактор маршрутов и расписаний, вакансии, контакты, документы и карточки сайта. Данные сохраняются при смене темы.
 * Version: 1.13.0
 */
defined('ABSPATH') || exit();
foreach (
    [
        'schema',
        'staff-access',
        'job-access',
        'staff-manager',
        'staff-directory',
        'post-types',
        'job-editor',
        'media',
        'meta-boxes',
        'save-content',
        'route-editor',
        'route-planner',
        'planner-time',
        'route-planner-admin',
        'news-planner',
        'editor-guide',
        'admin-interface',
        'card-workflow',
        'interface-copy',
        'site-content',
        'passenger-editor',
        'admin-workspace',
        'home-layout',
        'editing-navigation',
        'settings-history',
        'frontend-editor',
    ]
    as $module
) {
    require_once __DIR__ . '/includes/' . $module . '.php';
}
