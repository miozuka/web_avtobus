<section class="about-leadership section"><div class="container"><div class="about-leadership-card"><figure class="about-mayor"><?php autobus_image(
    'quote_image',
    'large',
    ['loading' => 'lazy'],
); ?><figcaption><strong><?php autobus_text('quote_author'); ?></strong><span><?php autobus_text(
    'quote_role',
); ?></span></figcaption></figure><div><p class="eyebrow"><?php echo esc_html(
    autobus_value('ap_quote_eyebrow') ?: 'Слово мэра Улан-Удэ',
); ?></p><blockquote>«<?php autobus_text('quote_text'); ?>»</blockquote><?php
$leaders = autobus_about_posts('agm_leader');
if ($leaders): ?><h2 class="about-leaders-title"><?php autobus_text(
    'ap_leaders_title',
); ?></h2><div class="about-leaders"><?php foreach (
    $leaders
    as $leader
): ?><article <?php autobus_edit_record($leader->ID); ?>><p class="eyebrow"><?php echo esc_html(
    autobus_meta($leader, 'role'),
); ?></p><h3><?php echo esc_html($leader->post_title); ?></h3><?php
if ($leader->post_excerpt): ?><p class="muted"><?php echo esc_html(
    $leader->post_excerpt,
); ?></p><?php endif;
if (autobus_meta($leader, 'phone')): ?><a href="<?php echo esc_url(
    autobus_phone_link(autobus_meta($leader, 'phone')),
); ?>"><?php echo esc_html(autobus_meta($leader, 'phone')); ?></a><?php endif;
?></article><?php endforeach; ?></div><?php endif;
?></div></div></div></section>
