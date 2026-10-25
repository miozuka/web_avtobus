<section class="about-hero section"><div class="container"><div class="about-hero-grid"><div class="about-intro"><p class="pill"><?php autobus_text(
    'ap_eyebrow',
); ?></p><h1><?php autobus_text('ap_title'); ?></h1><p class="about-subtitle"><?php autobus_text(
    'ap_subtitle',
); ?></p><p class="muted"><?php autobus_text(
    'ap_text',
); ?></p><div class="about-hero-actions"><a class="about-button about-button-primary" href="#about-history"><?php autobus_text(
    'ap_history_button',
); ?></a><a class="about-button" href="#about-documents"><?php autobus_text(
    'ap_documents_button',
); ?></a></div></div><?php autobus_about_gallery(
    'modern',
); ?></div><div class="about-stats"><?php foreach (
    autobus_about_posts('agm_stat')
    as $stat
): ?><article class="about-stat" <?php autobus_edit_record($stat->ID); ?>><span class="icon-box"><?php autobus_icon(
    autobus_meta($stat, 'icon'),
); ?></span><strong><?php echo esc_html(
    autobus_meta($stat, 'value'),
); ?></strong><p class="muted"><?php echo esc_html(
    $stat->post_title,
); ?></p></article><?php endforeach; ?></div></div></section>
