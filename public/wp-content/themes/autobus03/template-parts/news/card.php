<article class="news-list-card" <?php autobus_edit_record(get_the_ID()); ?>><div class="news-list-image"><a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail(
    'autobus-card',
    ['loading' => 'lazy'],
); ?></a><?php
$categories = get_the_category();
if (
    $categories &&
    (int) $categories[0]->term_id !== (int) get_option('default_category')
): ?><span class="news-category"><?php echo esc_html($categories[0]->name); ?></span><?php endif;
?></div><div class="news-list-body"><time datetime="<?php echo esc_attr(
    get_the_date('c'),
); ?>"><?php echo esc_html(
    get_the_date('d.m.Y'),
); ?></time><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p class="muted"><?php echo esc_html(
    wp_trim_words(get_the_excerpt(), 18),
); ?></p><a class="text-link" href="<?php the_permalink(); ?>"><?php autobus_text(
    'np_read',
); ?><span class="screen-reader-text">: <?php the_title(); ?></span></a></div></article>
