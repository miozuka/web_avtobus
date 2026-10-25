<article class="card news-card" <?php autobus_edit_record(get_the_ID()); ?>><a class="news-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail(
    'autobus-card',
    ['loading' => 'lazy'],
); ?></a><div class="card-body"><time datetime="<?php echo esc_attr(
    get_the_date('c'),
); ?>"><?php echo esc_html(
    get_the_date('d.m.Y'),
); ?></time><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p class="muted"><?php echo esc_html(
    wp_trim_words(get_the_excerpt(), 18),
); ?></p><a class="text-link" href="<?php the_permalink(); ?>"><?php autobus_text(
    'news_read',
); ?><span class="screen-reader-text">: <?php the_title(); ?></span></a></div></article>
