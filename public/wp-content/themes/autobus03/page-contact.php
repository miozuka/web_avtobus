<?php get_header();
$contacts = autobus_about_posts('agm_contact');
?>
<main id="main" class="contact-page">
<section class="contact-hero"><div class="container"><div><p class="pill"><?php autobus_text(
    'cp_eyebrow',
); ?></p><h1><?php autobus_text('cp_title'); ?></h1><p class="muted"><?php autobus_text(
    'cp_text',
); ?></p></div><a class="contact-button contact-button-green" href="<?php echo esc_url(
    autobus_phone_link(autobus_value('hotline')),
); ?>"><?php autobus_icon('phone'); ?>Позвонить: <?php autobus_text(
    'hotline',
); ?></a></div></section>
<section class="contact-content"><div class="container">
<div class="contact-priority-grid"><?php foreach ($contacts as $contact):
    if (!autobus_meta($contact, 'featured')) {
        continue;
    } ?><article class="contact-priority" <?php autobus_edit_record($contact->ID); ?>><div class="contact-priority-top"><span class="icon-box"><?php autobus_icon(
    autobus_meta($contact, 'icon'),
); ?></span><?php if (
    autobus_meta($contact, 'badge')
): ?><span class="contact-badge"><?php echo esc_html(
    autobus_meta($contact, 'badge'),
); ?></span><?php endif; ?></div><p class="contact-label"><?php echo esc_html(
    autobus_meta($contact, 'label'),
); ?></p><h2><?php echo esc_html(
    autobus_meta($contact, 'card_title') ?: $contact->post_title,
); ?></h2><p class="muted"><?php echo esc_html(
    $contact->post_excerpt,
); ?></p><a class="contact-priority-phone" href="<?php echo esc_url(
    autobus_phone_link(autobus_contact_phone($contact)),
); ?>"><strong><?php echo esc_html(autobus_contact_phone($contact)); ?></strong><?php autobus_icon(
    'phone',
); ?></a></article><?php
endforeach; ?></div>
<div class="contact-columns"><div><article class="contact-panel contact-directory"><p class="eyebrow"><?php autobus_ui('ui_9ae73b3f36', 'Телефоны предприятия'); ?></p><h2><?php autobus_text(
    'cp_directory_title',
); ?></h2><?php foreach (
    ['management' => 'Руководство и управление', 'operations' => 'Движение и эксплуатация']
    as $group => $label
): ?><h3 class="contact-group-title"><?php echo esc_html(
    $label,
); ?></h3><ul class="contact-rows"><?php foreach ($contacts as $contact):
    if (autobus_meta($contact, 'group') !== $group) {
        continue;
    } ?><li <?php autobus_edit_record($contact->ID); ?>><div><strong><?php echo esc_html($contact->post_title); ?></strong><?php if (
    autobus_meta($contact, 'badge') === '24/7'
): ?><span class="contact-badge">24/7</span><?php endif; ?></div><a href="<?php echo esc_url(
    autobus_phone_link(autobus_contact_phone($contact)),
); ?>" aria-label="<?php echo esc_attr(
    'Позвонить: ' . $contact->post_title . ', ' . autobus_contact_phone($contact),
); ?>"><?php echo esc_html(autobus_contact_phone($contact)); ?></a></li><?php
endforeach; ?></ul><?php endforeach; ?></article>
<article class="contact-lost"><div><p class="eyebrow"><?php autobus_ui('ui_ebb4fe5a22', 'Бюро находок'); ?></p><h2><?php autobus_text(
    'cp_lost_title',
); ?></h2><p class="muted"><?php autobus_text(
    'cp_lost_text',
); ?></p></div><a class="contact-button contact-button-green" href="<?php echo esc_url(
    autobus_phone_link(autobus_value('hotline')),
); ?>"><?php
autobus_icon('phone');
autobus_text('hotline');
?></a></article></div>
<aside class="contact-side"><article class="contact-panel"><p class="eyebrow"><?php autobus_ui('ui_47030a8813', 'Администрация'); ?></p><h2><?php autobus_text(
    'cp_leaders_title',
); ?></h2><div class="contact-leaders"><?php foreach (
    autobus_about_posts('agm_contact_leader')
    as $leader
): ?><div class="contact-leader" <?php autobus_edit_record($leader->ID); ?>><span class="contact-initials" aria-hidden="true"><?php echo esc_html(
    autobus_meta($leader, 'initials'),
); ?></span><div><p class="contact-label"><?php echo esc_html(
    autobus_meta($leader, 'role'),
); ?></p><h3><?php echo esc_html(
    $leader->post_title,
); ?></h3></div></div><?php endforeach; ?></div></article>
<article class="contact-panel"><p class="eyebrow"><?php autobus_ui('ui_7dbd3b2a04', 'Офис и автобаза'); ?></p><h2><?php autobus_text(
    'cp_office_title',
); ?></h2><div class="contact-office-details"><div><?php autobus_icon(
    'pin',
); ?><p><span><?php autobus_ui('ui_e3f1bf535a', 'Адрес предприятия'); ?></span><strong><?php autobus_text(
    'address',
); ?></strong></p></div><div><?php autobus_icon(
    'mail',
); ?><p><span><?php autobus_ui('ui_fe69e769b7', 'Электронная почта'); ?></span><a href="<?php echo esc_url(
    'mailto:' . autobus_value('email'),
); ?>"><?php autobus_text('email'); ?></a></p></div><div><?php autobus_icon(
    'check',
); ?><p><span><?php autobus_ui('ui_267db1a8ce', 'Режим работы администрации'); ?></span><strong><?php autobus_text(
    'cp_hours',
); ?></strong><small><?php autobus_text('cp_break'); ?></small></p></div><div><?php autobus_icon(
    'bus',
); ?><p><span><?php autobus_ui('ui_a96955b2d2', 'Общественный транспорт'); ?></span><?php autobus_text(
    'cp_transport',
); ?></p></div></div><div class="contact-socials"><strong><?php autobus_ui('ui_469feb42df', 'Официальные каналы'); ?></strong><?php get_template_part(
    'template-parts/components/social-links',
); ?></div></article></aside></div>
<article class="contact-map-card"><div class="contact-map-heading"><div><p class="pill"><?php autobus_ui('ui_05afe842e3', 'Схема расположения'); ?></p><h2><?php autobus_text(
    'cp_map_title',
); ?></h2><p class="muted"><?php autobus_text(
    'address',
); ?></p></div><div class="contact-map-links"><a class="contact-button contact-button-green" href="<?php echo esc_url(
    autobus_value('cp_map_url'),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_ui('ui_9fa0331df8', 'Яндекс Карты'); ?></a><a class="contact-button" href="<?php echo esc_url(
    autobus_value('cp_map_2gis_url'),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_ui('ui_14cf8b9733', '2ГИС'); ?></a></div></div><iframe src="<?php echo esc_url(
    autobus_value('cp_map_embed'),
); ?>" title="Яндекс Карта: МУП Городские маршруты, Улан-Удэ, проспект Автомобилистов, 3А" loading="lazy" allowfullscreen></iframe></article>
</div></section>
<?php get_template_part('template-parts/sections/feedback'); ?>
</main><?php get_footer(); ?>
