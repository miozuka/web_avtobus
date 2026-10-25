<?php
defined('ABSPATH') || exit();

// При разделении прав сохраняем прежний доступ сотрудников к вакансиям.
// Миграция выполняется один раз; дальнейшие изменения делает администратор в редакторе ролей.
add_action('init', function () {
    if (get_option('agm_job_access_version') === '1') { return; }
    foreach (array_keys(wp_roles()->roles) as $key) {
        $role = get_role($key);
        foreach (agm_section_caps('agm_items', true) as $cap => $allowed) {
            if ($role->has_cap($cap)) {
                $role->add_cap(str_replace('_agm_items', '_agm_jobs', $cap), true);
            }
        }
    }
    update_option('agm_job_access_version', '1', false);
}, 7);
