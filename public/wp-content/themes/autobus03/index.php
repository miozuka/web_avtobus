<?php get_header(); ?>
<main id="main" class="section"><div class="container"><h1><?php echo is_home()
    ? esc_html(get_the_title(get_option('page_for_posts')))
    : esc_html(
        get_the_archive_title(),
    ); ?></h1><div class="grid grid-three news-archive"><?php while (have_posts()):
    the_post();
    get_template_part('template-parts/cards/news');
endwhile; ?></div><?php the_posts_pagination(); ?></div></main>
<?php get_footer(); ?>
