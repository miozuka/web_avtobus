<section class="section news-section"><div class="container"><div class="section-heading"><p class="eyebrow"><?php autobus_text(
    'news_eyebrow',
); ?></p><h2 id="home-news-title" class="section-title"><?php echo esc_html(
    preg_replace('/\s+/u', ' ', trim(autobus_value('news_title'))),
); ?></h2><a class="text-link blue" href="<?php echo esc_url(
    get_permalink(get_option('page_for_posts')),
); ?>"><?php autobus_text(
    'news_button',
); ?></a></div><div class="news-strip" tabindex="0" role="region" aria-labelledby="home-news-title">
<?php
$news = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);
while ($news->have_posts()):
    $news->the_post();
    get_template_part('template-parts/cards/news');
endwhile;
wp_reset_postdata();
?>
</div></div></section>
