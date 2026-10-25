<section class="section faq-section"><div class="container faq-layout"><div><?php autobus_section_title(
    'faq',
); ?><div class="faq-list"><?php foreach (
    autobus_posts('agm_faq')
    as $faq
): ?><details class="faq-item" <?php autobus_edit_record($faq->ID); ?>><summary><?php echo esc_html(
    $faq->post_title,
); ?><span aria-hidden="true"></span></summary><div class="faq-answer"><?php echo apply_filters(
    'the_content',
    $faq->post_content,
); ?></div></details><?php endforeach; ?></div></div>
    <aside class="card about-card"><p class="eyebrow"><?php autobus_text(
        'about_eyebrow',
    ); ?></p><h2><?php autobus_text('about_title'); ?></h2><p class="muted"><?php autobus_text(
    'about_text',
); ?></p><?php autobus_image('about_image', 'medium_large', [
    'loading' => 'lazy',
]); ?><a class="button button-soft" href="<?php echo esc_url(
    autobus_value('about_url'),
); ?>"><?php autobus_text('about_button'); ?></a></aside>
</div></section>
