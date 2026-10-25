<?php defined('ABSPATH') || exit(); ?>
<footer class="site-footer"><div class="container"><div class="footer-grid"><div class="footer-brand"><div class="brand"><a class="brand-name" href="<?php echo esc_url(
    home_url('/'),
); ?>"><?php bloginfo('name'); ?></a></div><p><?php autobus_text(
    'footer_text',
); ?></p><div class="footer-actions"><?php get_template_part(
    'template-parts/components/social-links',
); ?><div class="footer-external-links"><?php if (
    autobus_value('feedback_url')
): ?><a class="gosuslugi-link" href="<?php echo esc_url(
    autobus_value('feedback_url'),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_icon(
    'government',
); ?><span><?php autobus_ui('ui_3625210b1b', 'Госуслуги'); ?></span><span class="screen-reader-text"> <?php autobus_ui('ui_be10dbe8b1', '— открыть в новой вкладке'); ?></span></a><?php endif; ?></div></div></div>
<nav aria-label="Информация пассажирам"><h2><?php autobus_text(
    'footer_passengers',
); ?></h2><?php wp_nav_menu([
    'theme_location' => 'passengers',
    'container' => false,
    'fallback_cb' => false,
]); ?></nav>
<nav aria-label="Информация о предприятии"><h2><?php autobus_text(
    'footer_company',
); ?></h2><?php wp_nav_menu([
    'theme_location' => 'company',
    'container' => false,
    'fallback_cb' => false,
]); ?></nav>
<div class="footer-contacts"><h2><?php autobus_text('footer_contacts'); ?></h2><p><?php
autobus_icon('pin');
autobus_text('address');
?></p><p><?php autobus_icon('phone'); ?><a href="<?php echo esc_url(
    autobus_phone_link(autobus_value('phone')),
); ?>"><?php autobus_text('phone'); ?></a></p><p><?php autobus_icon(
    'mail',
); ?><a href="<?php echo esc_url('mailto:' . autobus_value('email')); ?>"><?php autobus_text(
    'email',
); ?></a></p><div class="hotline"><span><?php autobus_ui('ui_c05b9d1586', 'Горячая линия'); ?></span><a class="hotline-number" href="<?php echo esc_url(
    autobus_phone_link(autobus_value('hotline')),
); ?>"><strong><?php autobus_text('hotline'); ?></strong></a><span><?php autobus_text(
    'hotline_text',
); ?></span></div></div></div>
<div class="footer-bottom"><p>© <?php echo esc_html(wp_date('Y')); ?> <?php autobus_text(
     'footer_copyright',
 ); ?></p><div><?php
if (autobus_value('privacy_url')): ?><a href="<?php echo esc_url(
    autobus_value('privacy_url'),
); ?>"><?php autobus_ui('ui_4041aa1240', 'Политика конфиденциальности'); ?></a><?php endif;
if (autobus_value('agreement_url')): ?><a href="<?php echo esc_url(
    autobus_value('agreement_url'),
); ?>"><?php autobus_ui('ui_a5a96f3c51', 'Пользовательское соглашение'); ?></a><?php endif;
?></div></div>
</div></footer>
<button class="back-to-top" type="button" aria-label="Вернуться к началу страницы" title="Наверх" hidden><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 14 6-6 6 6"/></svg></button>
<?php wp_footer(); ?></body></html>


