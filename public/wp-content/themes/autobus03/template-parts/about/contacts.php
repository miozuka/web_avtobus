<section class="about-contacts section"><div class="container about-contact-grid"><article class="about-contact-card"><p class="eyebrow"><?php autobus_text(
    'ap_contact_eyebrow',
); ?></p><h2><?php autobus_text('ap_contact_title'); ?></h2><p class="muted"><?php autobus_text(
    'ap_contact_text',
); ?></p><div class="about-contact-details"><div><?php autobus_icon(
    'phone',
); ?><p><span><?php autobus_ui('ui_0dba0412ad', 'Приёмная'); ?></span><a href="<?php echo esc_url(
    autobus_phone_link(autobus_value('phone')),
); ?>"><?php autobus_text('phone'); ?></a></p></div><div><?php autobus_icon(
    'phone',
); ?><p><span><?php autobus_ui('ui_c05b9d1586', 'Горячая линия'); ?></span><a href="<?php echo esc_url(
    autobus_phone_link(autobus_value('hotline')),
); ?>"><?php autobus_text('hotline'); ?></a></p></div><div><?php autobus_icon(
    'pin',
); ?><p><span><?php autobus_ui('ui_e3f1bf535a', 'Адрес предприятия'); ?></span><?php autobus_text(
    'address',
); ?></p></div><div><?php autobus_icon(
    'mail',
); ?><p><span><?php autobus_ui('ui_fe69e769b7', 'Электронная почта'); ?></span><a href="<?php echo esc_url(
    'mailto:' . autobus_value('email'),
); ?>"><?php autobus_text(
    'email',
); ?></a></p></div></div></article><article class="about-feedback"><p class="pill"><?php
autobus_icon('government');
autobus_text('feedback_eyebrow');
?></p><h2><?php autobus_text('feedback_title'); ?></h2><p><?php autobus_text(
    'feedback_text',
); ?></p><a class="about-button" href="<?php echo esc_url(
    autobus_value('feedback_url'),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_text(
    'feedback_button',
); ?></a></article></div></section>
