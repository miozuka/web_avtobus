<section class="vacancy-benefits"><div class="container"><p class="pill"><?php autobus_text(
    'vp_benefits_eyebrow',
); ?></p><h2><?php autobus_text('vp_benefits_title'); ?></h2><p class="muted"><?php autobus_text(
    'vp_benefits_text',
); ?></p><div class="vacancy-benefit-grid"><?php foreach (
    autobus_about_posts('agm_benefit')
    as $benefit
): ?><article class="vacancy-benefit" <?php autobus_edit_record($benefit->ID); ?>><span class="icon-box"><?php autobus_icon(
    autobus_meta($benefit, 'icon'),
); ?></span><div><h3><?php echo esc_html(
    $benefit->post_title,
); ?></h3><p class="muted"><?php echo esc_html(
    $benefit->post_excerpt,
); ?></p></div></article><?php endforeach; ?></div></div></section>
