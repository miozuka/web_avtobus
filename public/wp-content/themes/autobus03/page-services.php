<?php get_header(); ?>
<main id="main" class="services-page">
    <?php foreach (['hero', 'tariffs', 'enterprise', 'document'] as $section) {
        get_template_part('template-parts/services/' . $section);
    } ?>
</main>
<?php get_footer(); ?>
