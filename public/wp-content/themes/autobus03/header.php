<?php defined('ABSPATH') || exit(); ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo(
    'charset',
); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php autobus_ui('ui_2b0acf6fbd', 'Перейти к содержимому'); ?></a>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand"><?php if (has_custom_logo()) {
            the_custom_logo();
        } else {
            autobus_icon('bus');
        } ?><a class="brand-name" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo(
    'name',
); ?></a></div>
        <button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" hidden><span class="menu-lines" aria-hidden="true"></span><span class="screen-reader-text"><?php autobus_ui('ui_72374bf6be', 'Открыть меню'); ?></span></button>
        <nav id="primary-navigation" class="primary-navigation" aria-label="Основное меню"><?php wp_nav_menu(
            [
                'theme_location' => 'primary',
                'container' => false,
                'menu_class' => 'nav-list',
                'fallback_cb' => false,
                'depth' => 2,
            ],
        ); ?></nav>
        <button class="access-toggle" title="Версия для слабовидящих" type="button" aria-controls="access-panel" aria-expanded="false" hidden><?php autobus_icon(
            'eye',
        ); ?><span><?php autobus_ui('ui_4a019f4967', 'Версия для слабовидящих'); ?></span></button>
    </div>
</header>
<?php get_template_part('template-parts/components/access-panel'); ?>
