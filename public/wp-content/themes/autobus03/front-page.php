<?php get_header(); ?>
<main id="main">
<?php foreach (
    (function_exists('agm_home_layout') ? agm_home_layout() : ['hero', 'schedule', 'fleet', 'quote', 'services', 'news', 'faq', 'information', 'feedback'])
    as $section
) {
    get_template_part('template-parts/sections/' . $section);
} ?>
</main>
<?php get_footer(); ?>
