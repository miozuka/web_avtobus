<?php get_header(); ?>
<main id="main" class="vacancy-page">
<?php foreach (['hero', 'opportunities', 'map', 'benefits'] as $section) {
    get_template_part('template-parts/vacancy/' . $section);
} ?>
</main>
<?php get_footer(); ?>
