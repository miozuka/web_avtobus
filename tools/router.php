<?php
// Маршруты локального PHP-сервера. На хостинге используются правила Apache/Nginx.
$public = dirname(__DIR__) . '/public';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~(?:^|/)\.|^/wp-config(?:-[^/]*)?\.php$~', $path)) {
    http_response_code(403);
    exit();
}
$file = realpath($public . $path);
if (
    $file &&
    str_starts_with($file, realpath($public) . DIRECTORY_SEPARATOR) &&
    (is_file($file) || is_dir($file))
) {
    return false;
}
require $public . '/index.php';
