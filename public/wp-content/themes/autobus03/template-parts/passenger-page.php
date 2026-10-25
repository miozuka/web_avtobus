<main id="main" class="passenger-page" data-scene="<?php echo is_page('travel_benefits')
    ? 'benefits'
    : 'ticket'; ?>"><div class="container"><?php while (have_posts()):
    the_post();
    the_content();
endwhile; ?></div></main>
