<?php
// Установка только в новую базу. Содержимое существующих таблиц не перезаписывается.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit();
}
$root = dirname(__DIR__);
$options = getopt('', ['db-host:', 'db-name:', 'db-user:', 'url:', 'create-database', 'admin-user:', 'admin-email:', 'environment:']);
$host = $options['db-host'] ?? '';
$name = $options['db-name'] ?? '';
$user = $options['db-user'] ?? '';
$url = rtrim($options['url'] ?? '', '/');
$password = getenv('WEB_AVTOBUS_DB_PASSWORD') ?: '';
$adminPassword = getenv('WEB_AVTOBUS_ADMIN_PASSWORD') ?: '';
$adminLogin = $options['admin-user'] ?? 'admin';
$adminEmail = $options['admin-email'] ?? '';
$environment = $options['environment'] ?? 'local';
if (strlen($adminPassword) < 12 || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $adminLogin) || !in_array($environment, ['local','staging','production'], true)) throw new RuntimeException('Задайте новый логин, email и пароль администратора от 12 символов.');
$manifest = json_decode(
    file_get_contents($root . '/database/manifest.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
if (
    !$host ||
    !$user ||
    !preg_match('/^[a-zA-Z0-9_]+$/', $name) ||
    !filter_var($url, FILTER_VALIDATE_URL) ||
    !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ||
    parse_url($url, PHP_URL_QUERY) ||
    parse_url($url, PHP_URL_FRAGMENT) ||
    parse_url($url, PHP_URL_USER) ||
    parse_url($url, PHP_URL_PASS)
) {
    throw new RuntimeException(
        'Укажите адрес базы, новое имя базы, пользователя и полный адрес сайта без параметров.',
    );
}
// Не допускаем подключение к другой установленной базе через внешнюю конфигурацию.
if (getenv('WEB_AVTOBUS_CONFIG')) {
    throw new RuntimeException('Для установки новой копии уберите переменную WEB_AVTOBUS_CONFIG из этого окна терминала.');
}
if (is_file($root . '/public/wp-config-local.php')) {
    throw new RuntimeException('Копия уже настроена. Не запускайте импорт повторно.');
}
[$server, $port] = array_pad(explode(':', $host, 2), 2, '3306');
if (!ctype_digit($port)) {
    throw new RuntimeException('Порт базы должен быть числом.');
}
$localHost = in_array(strtolower($server), ['localhost', '127.0.0.1'], true)
    ? 'localhost'
    : strtolower($server);
if (
    $name === ($manifest['source_database'] ?? null) &&
    $localHost === ($manifest['source_host'] ?? null) &&
    (int) $port === ($manifest['source_port'] ?? null)
) {
    throw new RuntimeException('Выбрана исходная база рабочего сайта. Создайте отдельную базу.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($server, $user, $password, '', (int) $port);
$db->set_charset('utf8mb4');
if (isset($options['create-database'])) {
    $db->query(
        'CREATE DATABASE IF NOT EXISTS `' .
            $name .
            '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    );
}
$db->select_db($name);
if ($db->query('SHOW TABLES')->num_rows > 0) {
    throw new RuntimeException('В выбранной базе уже есть таблицы. Для импорта нужна пустая база.');
}
$db->multi_query(file_get_contents($root . '/database/database.sql'));
do {
    if ($result = $db->store_result()) {
        $result->free();
    }
} while ($db->more_results() && $db->next_result());
foreach ($manifest['rows'] as $table => $count) {
    if (
        !preg_match('/^[a-zA-Z0-9_]+$/', $table) ||
        (int) $db->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0] !== $count
    ) {
        throw new RuntimeException('Проверка количества записей после импорта не пройдена.');
    }
}
$db->close();
$keys = [];
foreach (
    [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    ]
    as $key
) {
    $keys[$key] = bin2hex(random_bytes(48));
}
$config = [
    'db_name' => $name,
    'db_user' => $user,
    'db_password' => $password,
    'db_host' => $server . ':' . $port,
    'table_prefix' => $manifest['table_prefix'],
    'keys' => $keys,
    'environment' => $environment,
];
$written = file_put_contents(
    $root . '/public/wp-config-local.php',
    "<?php\n// Личные настройки этой установки. Не публикуйте файл и не добавляйте его в Git.\nreturn " .
        var_export($config, true) .
        ";\n",
);
if ($written === false) throw new RuntimeException('Не удалось сохранить подключение. Проверьте права записи в public.');
define('DISABLE_WP_CRON', true);
require $root . '/public/wp-load.php';
function replace_site_address($value, string $old, string $new)
{
    if (is_string($value)) {
        return str_replace($old, $new, $value);
    }
    if (is_array($value)) {
        $updated = [];
        foreach ($value as $key => $item) {
            $updated[is_string($key) ? str_replace($old, $new, $key) : $key] = replace_site_address(
                $item,
                $old,
                $new,
            );
        }
        return $updated;
    }
    if ($value instanceof stdClass) {
        foreach (get_object_vars($value) as $key => $item) {
            $value->$key = replace_site_address($item, $old, $new);
        }
        return $value;
    }
    if (is_object($value)) {
        throw new RuntimeException(
            'Найден нестандартный сериализованный объект. Для замены адреса используйте WP-CLI search-replace.',
        );
    }
    return $value;
}
// Настройки WordPress содержат сериализованные массивы: обычная замена текста в SQL их повреждает.
$old = rtrim($manifest['home'], '/');
$wpdb->query('START TRANSACTION');
try {
    foreach (array_keys($manifest['rows']) as $table) {
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table`", ARRAY_A);
        $primary = array_column(
            array_filter($columns, fn($column) => $column['Key'] === 'PRI'),
            'Field',
        );
        if (!$primary) {
            throw new RuntimeException('У таблицы нет первичного ключа: ' . $table);
        }
        foreach ($wpdb->get_results("SELECT * FROM `$table`", ARRAY_A) as $row) {
            $changed = [];
            foreach ($row as $field => $value) {
                if ($field === 'guid' || !is_string($value) || !str_contains($value, $old)) {
                    continue;
                }
                $changed[$field] = is_serialized($value)
                    ? serialize(
                        replace_site_address(
                            unserialize($value, ['allowed_classes' => ['stdClass']]),
                            $old,
                            $url,
                        ),
                    )
                    : str_replace($old, $url, $value);
            }
            if (
                $changed &&
                $wpdb->update($table, $changed, array_intersect_key($row, array_flip($primary))) ===
                    false
            ) {
                throw new RuntimeException('Не удалось заменить адрес сайта в ' . $table);
            }
        }
    }
    $wpdb->query('COMMIT');
} catch (Throwable $error) {
    $wpdb->query('ROLLBACK');
    throw $error;
}
// Пароль и почта администратора задаются заново; исходные доступы в SQL отсутствуют.
$adminId = (int) $manifest['admin_id'];
if ($wpdb->update($wpdb->users, ['user_login' => $adminLogin, 'user_nicename' => sanitize_title($adminLogin), 'user_email' => $adminEmail, 'user_activation_key' => ''], ['ID' => $adminId]) === false) throw new RuntimeException('Не удалось настроить администратора.');
clean_user_cache($adminId);
wp_set_password($adminPassword, $adminId);
delete_user_meta($adminId, 'default_password_nag');
update_option('admin_email', $adminEmail);
update_option('WPLANG', 'ru_RU');
// Роль по умолчанию сохраняется из переданной базы вместе с настройками организации.
// Удалённый стандартный черновик не должен оставаться выбранной страницей политики.
update_option('wp_page_for_privacy_policy', 0);
foreach (get_taxonomies() as $taxonomy) {
    $termIds = $wpdb->get_col($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s", $taxonomy));
    if ($termIds) wp_update_term_count_now($termIds, $taxonomy);
}
wp_cache_flush();
flush_rewrite_rules();
echo "Сайт восстановлен. Новый адрес: $url\nЗапустите tools/start.ps1 для локальной проверки.\n";
