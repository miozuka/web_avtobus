<?php get_header(); ?>
<main id="main" class="section cookie-policy-page" data-scene="terms"><div class="container cookie-policy-content">
<?php while (have_posts()):
    the_post(); ?><h1><?php the_title(); ?></h1><div class="entry-content"><?php the_content(); ?></div><?php
endwhile; ?>
</div></main>
<?php get_footer(); ?>
