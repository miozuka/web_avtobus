<?php
defined('ABSPATH') || exit();
get_header();
?>
<main id="main" class="news-detail"><div class="container news-detail-container">
<?php while (have_posts()):

    the_post();
    $news_id = get_the_ID();
    ?>
<article class="news-article" <?php autobus_edit_record(get_the_ID()); ?>>
<header class="news-article-header"><div class="news-article-meta"><time datetime="<?php echo esc_attr(
    get_the_date('c'),
); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time><?php
$categories = get_the_category();
foreach ($categories as $category):
    if ((int) $category->term_id === (int) get_option('default_category')) {
        continue;
    } ?><span><?php echo esc_html($category->name); ?></span><?php
endforeach;
?></div><h1><?php the_title(); ?></h1></header>
<?php the_post_thumbnail('large', ['class' => 'news-article-image', 'loading' => 'eager']); ?>
<div class="entry-content news-article-text"><?php
$content = apply_filters('the_content', get_the_content());
// Imported article headings belong below the page title. Keep the stored source intact.
$content = preg_replace('/<(\/?)(h1)(\s[^>]*)?>/i', '<$1h2$3>', $content);
// VK emoji are text, so render their Unicode instead of requesting remote image files.
$content = preg_replace_callback(
    '/<img\b[^>]*>/i',
    function ($image) {
        if (
            !preg_match('~https?://vk\.(?:com|ru)/emoji/e/([a-f0-9]+)\.png~i', $image[0], $match) ||
            strlen($match[1]) % 2
        ) {
            return $image[0];
        }
        $emoji = hex2bin($match[1]);
        return $emoji && mb_check_encoding($emoji, 'UTF-8') ? esc_html($emoji) : $image[0];
    },
    $content,
);
$cover_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
if ($cover_url) {
    $removed_cover = false;
    $content = preg_replace_callback(
        '/<img\b[^>]*>/i',
        function ($image) use ($cover_url, &$removed_cover) {
            if (!$removed_cover && str_contains($image[0], esc_url($cover_url))) {
                $removed_cover = true;
                return '';
            }
            return $image[0];
        },
        $content,
    );
}
echo $content;
?></div>
<?php
$route_url = '';
$route_number = '';
if (
    preg_match('/(?:расписани|движени|усилени)/ui', get_the_title()) &&
    preg_match('/№\s*(\d+[А-ЯA-Z]?)/ui', get_the_title(), $match)
) {
    $route_number = mb_strtoupper($match[1]);
    $routes = get_posts([
        'post_type' => 'agm_route',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);
    foreach ($routes as $route) {
        if (mb_strtoupper(autobus_meta($route, 'number')) === $route_number) {
            $route_url = autobus_meta($route, 'url');
            break;
        }
    }
}
?>
<div class="news-article-actions"><?php if (
    $route_url
): ?><a class="news-action news-action-primary" href="<?php echo esc_url(
    $route_url,
); ?>"><?php autobus_icon('bus'); ?><span><?php autobus_ui('ui_57e7cfa7db', 'Расписание маршрута №'); ?><?php echo esc_html(
    $route_number,
); ?></span></a><?php endif; ?><button class="news-action" type="button" data-news-share hidden><?php autobus_icon(
    'share',
); ?><span><?php autobus_ui('ui_99ce26e806', 'Поделиться'); ?></span></button><a class="news-action" href="<?php echo esc_url(
    get_permalink(get_option('page_for_posts')),
); ?>"><?php autobus_ui('ui_019126a86f', 'Все новости'); ?></a></div>
<div class="news-share-fallback" hidden><label for="news-share-url"><?php autobus_ui('ui_9172388ce6', 'Ссылка на новость'); ?></label><input id="news-share-url" type="url" readonly value="<?php the_permalink(); ?>"></div><p class="news-share-status" role="status" aria-live="polite"></p>
</article>
<?php
endwhile; ?>
<?php
$related = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 3,
    'post__not_in' => [$news_id ?? 0],
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);
if ($related->have_posts()): ?>
<section class="news-related" aria-labelledby="news-related-title"><h2 id="news-related-title"><?php autobus_ui('ui_b7acf7489b', 'Другие новости'); ?></h2><div class="news-page-grid"><?php while (
    $related->have_posts()
):
    $related->the_post();
    get_template_part('template-parts/news/card');
endwhile; ?></div></section>
<?php wp_reset_postdata();endif;
?>
</div></main>
<?php get_footer(); ?>
