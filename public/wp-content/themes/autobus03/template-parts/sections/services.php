<section class="section services-section" id="services"><div class="container"><div class="center-heading"><h2><?php autobus_text(
    'services_title',
); ?></h2><p class="muted"><?php autobus_text(
    'services_text',
); ?></p><span class="heading-ornament" aria-hidden="true">◇</span></div>
    <div class="grid grid-four"><?php foreach (
        autobus_posts('agm_service')
        as $service
    ): ?><article class="card service-card" <?php autobus_edit_record($service->ID); ?>><div class="icon-box"><?php autobus_icon(
    autobus_meta($service, 'icon'),
); ?></div><h3><?php echo esc_html(
    $service->post_title,
); ?></h3><p class="muted"><?php echo esc_html(
    $service->post_excerpt,
); ?></p><a class="text-link" href="<?php echo esc_url(
    autobus_meta($service, 'url'),
); ?>"><?php echo esc_html(
    autobus_meta($service, 'link_text'),
); ?><span class="screen-reader-text">: <?php echo esc_html(
    $service->post_title,
); ?></span></a></article><?php endforeach; ?></div>
</div></section>
