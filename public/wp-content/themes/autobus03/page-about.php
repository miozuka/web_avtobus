<?php get_header(); ?>
<main id="main" class="about-page">
<?php foreach (['hero', 'leadership', 'history', 'archive', 'documents', 'contacts'] as $section) {
    get_template_part('template-parts/about/' . $section);
} ?>
</main>
<?php get_footer(); ?>
