<?php
// Проверка установленной копии. Не создаёт и не удаляет материалы сайта.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit();
}
define('DISABLE_WP_CRON', true);
$root = dirname(__DIR__);
require $root . '/public/wp-load.php';
$site = wp_parse_url(home_url());
$_SERVER['SERVER_NAME'] = $site['host'];
$_SERVER['HTTP_HOST'] = $site['host'] . (isset($site['port']) ? ':' . $site['port'] : '');
$_SERVER['SERVER_PORT'] = $site['port'] ?? ($site['scheme'] === 'https' ? 443 : 80);
$_SERVER['REQUEST_URI'] = '/';
$issues = [];
if (get_stylesheet() !== 'autobus03' || !function_exists('agm_types')) {
    throw new RuntimeException('Не включена тема или плагин содержимого.');
}
$cases = [
    ['front-page.php', ['page_id' => get_option('page_on_front')]],
    ['home.php', ['post_type' => 'post', 'posts_per_page' => 6]],
];
$covered = [(int) get_option('page_on_front'), (int) get_option('page_for_posts')];
foreach (
    get_posts(['post_type' => 'page', 'numberposts' => -1, 'post__not_in' => $covered])
    as $page
) {
    $template = get_page_template_slug($page->ID);
    if (!$template) {
        $template = is_file(get_template_directory() . '/page-' . $page->post_name . '.php')
            ? 'page-' . $page->post_name . '.php'
            : 'page.php';
    }
    $cases[] = [$template, ['page_id' => $page->ID]];
}
foreach (get_posts(['post_type' => 'post', 'numberposts' => -1]) as $post) {
    $cases[] = ['single.php', ['p' => $post->ID]];
}
$images = [];
$links = [];
set_error_handler(function ($severity, $message, $file, $line) use (&$issues) {
    if (error_reporting() & $severity) {
        $issues[] = basename($file) . ':' . $line . ' ' . $message;
    }
    return false;
});
foreach ($cases as [$template, $query]) {
    query_posts($query);
    ob_start();
    include get_template_directory() . '/' . $template;
    $html = ob_get_clean();
    wp_reset_query();
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);
    if ($xpath->query('//main')->length !== 1 || $xpath->query('//main//h1')->length !== 1) {
        $issues[] = 'Неправильная структура страницы: ' . $template;
    }
    foreach ($xpath->query('//img[@src]') as $image) {
        $images[html_entity_decode($image->getAttribute('src'))] = true;
    }
    foreach ($xpath->query('//a[@href]') as $link) {
        $links[html_entity_decode($link->getAttribute('href'))] = true;
    }
}
restore_error_handler();
$base = wp_parse_url(home_url());
foreach (array_keys($images) as $url) {
    if (
        wp_parse_url($url, PHP_URL_HOST) === $base['host'] &&
        !is_file(ABSPATH . ltrim(rawurldecode(wp_parse_url($url, PHP_URL_PATH)), '/'))
    ) {
        $issues[] = 'Нет изображения: ' . $url;
    }
}
foreach (array_keys($links) as $url) {
    if (wp_parse_url($url, PHP_URL_HOST) !== $base['host']) {
        continue;
    }
    $path = wp_parse_url($url, PHP_URL_PATH) ?: '/';
    if ($path === '/' || str_contains($path, '/page/') || str_starts_with($path, '/wp-admin/')) {
        continue;
    }
    if (
        is_file(ABSPATH . ltrim(rawurldecode($path), '/')) ||
        untrailingslashit($url) === untrailingslashit(get_permalink(get_option('page_for_posts')))
    ) {
        continue;
    }
    if (!url_to_postid($url)) {
        $issues[] = 'Не найдена внутренняя страница: ' . $url;
    }
}
foreach ($issues as $issue) {
    echo $issue . "\n";
}
echo 'WordPress ' .
    get_bloginfo('version') .
    '; страниц: ' .
    count($cases) .
    '; изображений: ' .
    count($images) .
    '; ссылок: ' .
    count($links) .
    '; ошибок: ' .
    count($issues) .
    ".\n";
echo 'Новости: ' .
    wp_count_posts('post')->publish .
    '; маршруты: ' .
    wp_count_posts('agm_route')->publish .
    '; направления: ' .
    wp_count_posts('agm_departure')->publish .
    ".\n";
exit($issues ? 1 : 0);
