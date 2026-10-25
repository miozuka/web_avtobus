<?php get_header(); ?>
<main id="main" class="section cookie-policy-page" data-scene="privacy"><div class="container cookie-policy-content">
<?php while (have_posts()):
    the_post(); ?><h1><?php the_title(); ?></h1><div class="entry-content"><?php the_content(); ?></div><?php
endwhile; ?>
<p><button type="button" class="button" data-cookie-open hidden><?php autobus_ui('ui_1679f69dca', 'Изменить выбор cookie'); ?></button></p>
</div></main>
<?php get_footer(); ?>
