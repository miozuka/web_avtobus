<?php
/** Template Name: График маршрута */
get_header();
$route_page = get_queried_object();
$directions = get_posts([
    'post_type' => 'agm_departure',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'meta_key' => '_autobus_page_id',
    'meta_value' => $route_page->ID,
]);
?>
<main id="main" class="timetable-page"><div class="container timetable-container">
    <?php get_template_part('template-parts/timetable/header', null, ['route' => $route_page]); ?>
    <?php get_template_part('template-parts/timetable/graph', null, [
        'route' => $route_page,
        'directions' => $directions,
    ]); ?>
</div></main>
<?php get_footer(); ?>
