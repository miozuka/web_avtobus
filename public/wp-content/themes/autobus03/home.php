<?php
defined('ABSPATH') || exit();
get_header();
$featured_id = absint(autobus_value('np_featured'));
$featured = $featured_id ? get_post($featured_id) : null;
$page_number = max(1, get_query_var('paged'));
?>
<main id="main" class="news-page">
<section class="news-page-hero"><div class="container"><p class="pill"><?php autobus_text(
    'np_eyebrow',
); ?></p><h1><?php autobus_text('np_title'); ?></h1><p class="muted"><?php autobus_text(
    'np_text',
); ?></p></div></section>
<div class="container news-page-content">
<?php if (
    1 === $page_number &&
    $featured &&
    'publish' === $featured->post_status &&
    'post' === $featured->post_type
):

    global $post;
    $post = $featured;
    setup_postdata($post);
    ?>
<article class="news-featured" <?php autobus_edit_record(get_the_ID()); ?>>
<a class="news-featured-image" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>"><?php the_post_thumbnail(
    'large',
    ['loading' => 'eager'],
); ?><span class="news-featured-label"><?php autobus_text('np_featured_label'); ?></span></a>
<div class="news-featured-body"><div class="news-meta"><time datetime="<?php echo esc_attr(
    get_the_date('c'),
); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time><span><?php autobus_text(
    'np_author',
); ?></span></div><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p class="muted"><?php echo esc_html(
    wp_trim_words(get_the_excerpt(), 45),
); ?></p><a class="text-link" href="<?php the_permalink(); ?>"><?php autobus_text(
    'np_read',
); ?><span class="screen-reader-text">: <?php the_title(); ?></span></a></div>
</article>
<?php wp_reset_postdata();
endif; ?>
<div class="news-page-grid">
<?php while (have_posts()):
    the_post();
    get_template_part('template-parts/news/card');
endwhile; ?>
</div>
<?php if (!$GLOBALS['wp_query']->post_count): ?><p><?php autobus_text(
    'np_empty',
); ?></p><?php endif; ?>
<?php the_posts_pagination([
    'mid_size' => 3,
    'end_size' => 2,
    'prev_text' => 'Назад',
    'next_text' => 'Далее',
    'screen_reader_text' => 'Страницы новостей',
]); ?>
<section class="news-subscribe"><div><p class="pill"><?php autobus_text(
    'np_subscribe_eyebrow',
); ?></p><h2><?php autobus_text('np_subscribe_title'); ?></h2><p class="muted"><?php autobus_text(
    'np_subscribe_text',
); ?></p></div><div class="news-subscribe-links">
<?php foreach (
    ['telegram' => 'Канал в Telegram', 'vk' => 'Сообщество ВКонтакте', 'max' => 'Канал в MAX']
    as $network => $label
):
    if (!autobus_value($network . '_url')) {
        continue;
    } ?>
<a class="news-social news-social-<?php echo esc_attr($network); ?>" href="<?php echo esc_url(
    autobus_value($network . '_url'),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_social_icon(
    $network,
); ?><span><?php echo esc_html(
    $label,
); ?></span><span class="screen-reader-text"> <?php autobus_ui('ui_be10dbe8b1', '— открыть в новой вкладке'); ?></span></a>
<?php
endforeach; ?></div></section>
</div></main>
<?php get_footer(); ?>
