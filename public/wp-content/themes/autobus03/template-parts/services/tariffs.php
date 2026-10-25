<section class="passenger-tariffs" id="regular-transport" aria-labelledby="tariffs-title"><div class="container">
    <p class="pill"><?php autobus_text(
        'sp_tariffs_eyebrow',
    ); ?></p><h2 id="tariffs-title"><?php autobus_text('sp_tariffs_title'); ?></h2>
    <div class="grid grid-three tariff-grid"><?php foreach (
        autobus_posts('agm_tariff')
        as $index => $tariff
    ): ?>
        <article <?php autobus_edit_record($tariff->ID); ?> class="card tariff-card<?php echo 1 === $index
            ? ' tariff-card-featured'
            : ''; ?>"><div class="tariff-top"><span class="tariff-badge"><?php echo esc_html(
    autobus_meta($tariff, 'badge'),
); ?></span><div class="icon-box"><?php autobus_icon(
    autobus_meta($tariff, 'icon'),
); ?></div></div><h3><?php echo esc_html(
    $tariff->post_title,
); ?></h3><p class="muted"><?php echo esc_html(
    $tariff->post_excerpt,
); ?></p><div class="tariff-bottom"><strong>₽ <?php echo esc_html(
    autobus_meta($tariff, 'price'),
); ?></strong><span><?php echo esc_html(autobus_meta($tariff, 'unit')); ?></span></div></article>
    <?php endforeach; ?></div>
</div></section>
