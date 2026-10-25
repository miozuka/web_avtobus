<?php
// Подключение настраивается при установке. В передаваемом комплекте действующих паролей нет.
$settingsFile = getenv('WEB_AVTOBUS_CONFIG') ?: __DIR__ . '/wp-config-local.php';
if (!is_file($settingsFile)) {
    http_response_code(503);
    exit('Сайт ещё не настроен. Откройте README.md в папке web_avtobus и выполните установку.');
}
$settings = require $settingsFile;
define('DB_NAME', $settings['db_name']);
define('DB_USER', $settings['db_user']);
define('DB_PASSWORD', $settings['db_password']);
define('DB_HOST', $settings['db_host']);
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
foreach ($settings['keys'] as $name => $value) {
    if (!$value || str_contains($value, 'ЗАМЕНИТЕ')) { http_response_code(503); exit('Создайте новые ключи WordPress по инструкции установки.'); }
    define($name, $value);
}
$table_prefix = $settings['table_prefix'];
define('WP_DEBUG', false);
define('DISALLOW_FILE_EDIT', true);
define('WP_ENVIRONMENT_TYPE', $settings['environment'] ?? 'production');
// Простой однопоточный PHP-сервер не обрабатывает фоновые запросы. На Nginx/Apache этого ограничения нет.
if (PHP_SAPI === 'cli-server' && !defined('DISABLE_WP_CRON')) define('DISABLE_WP_CRON', true);
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
require_once ABSPATH . 'wp-settings.php';
