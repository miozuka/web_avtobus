<section class="enterprise-services" aria-labelledby="enterprise-title"><div class="container"><div class="enterprise-heading"><p class="pill"><?php autobus_text(
    'sp_enterprise_eyebrow',
); ?></p><h2 id="enterprise-title"><?php autobus_text(
    'sp_enterprise_title',
); ?></h2><p class="muted"><?php autobus_text('sp_enterprise_text'); ?></p></div>
    <div class="grid grid-four enterprise-grid"><?php foreach (
        autobus_posts('agm_service')
        as $service
    ):

        $title = autobus_meta($service, 'detail_title') ?: $service->post_title;
        $phone = autobus_meta($service, 'phone');
        ?>
        <article <?php autobus_edit_record($service->ID); ?> id="<?php echo esc_attr(
            'advertising' === $service->post_name ? 'service-promotion' : $service->post_name,
        ); ?>" class="card enterprise-card"><div class="icon-box"><?php autobus_icon(
    autobus_meta($service, 'icon'),
); ?></div><h3><?php echo esc_html(
    $title,
); ?></h3><div class="enterprise-description muted"><?php if ($service->post_content) {
    echo apply_filters('the_content', $service->post_content);
} else {
    echo '<p>' . esc_html($service->post_excerpt) . '</p>';
} ?></div><div class="enterprise-bottom"><p class="service-price">₽ <?php
echo esc_html(autobus_meta($service, 'price'));
if (autobus_meta($service, 'unit')): ?> <span><?php echo esc_html(
     autobus_meta($service, 'unit'),
 ); ?></span><?php endif;
?></p><?php if ($phone): ?><a class="service-call" href="<?php echo esc_url(
    autobus_phone_link($phone),
); ?>"><?php autobus_icon('phone'); ?><span><?php autobus_text(
    'sp_call_label',
); ?>: <?php echo esc_html(
    $phone,
); ?></span><span class="screen-reader-text"> — <?php echo esc_html(
    $title,
); ?></span></a><?php endif; ?></div></article>
    <?php
    endforeach; ?></div>
</div></section>

