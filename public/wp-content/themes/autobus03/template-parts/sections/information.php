<section class="section information-section"><div class="container"><?php autobus_section_title(
    'info',
); ?><div class="grid grid-two"><?php foreach (
    autobus_posts('agm_info')
    as $info
): ?><article class="card info-card" <?php autobus_edit_record($info->ID); ?>><div class="icon-box"><?php autobus_icon(
    autobus_meta($info, 'icon'),
); ?></div><h3><?php echo esc_html($info->post_title); ?></h3><p class="muted"><?php echo esc_html(
    $info->post_excerpt,
); ?></p><a class="text-link blue" href="<?php echo esc_url(
    autobus_meta($info, 'url'),
); ?>"><?php echo esc_html(
    autobus_meta($info, 'link_text'),
); ?><span class="screen-reader-text">: <?php echo esc_html(
    $info->post_title,
); ?></span></a></article><?php endforeach; ?></div></div></section>
