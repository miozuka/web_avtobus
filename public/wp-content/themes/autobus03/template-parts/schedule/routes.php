<?php
$routes = get_posts([
    'post_type' => 'agm_route',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
]);
$groups = ['city' => [], 'summer' => []];
foreach ($routes as $route) {
    $group = 'summer' === autobus_meta($route, 'group') ? 'summer' : 'city';
    $groups[$group][] = $route;
}
$filters = [
    'all' => ['Все маршруты', count($routes)],
    'city' => ['Городские маршруты', count($groups['city'])],
    'summer' => ['Дачные маршруты', count($groups['summer'])],
];
?>
<div class="route-tools" hidden><div class="route-search card"><label class="screen-reader-text" for="route-search"><?php autobus_ui('ui_5a0c4ef537', 'Номер маршрута или название остановки'); ?></label><svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></svg><input id="route-search" type="search" placeholder="<?php echo esc_attr(
    autobus_value('rp_search'),
); ?>" autocomplete="off" aria-controls="route-results"></div>
<div class="route-filters" role="group" aria-label="Категория маршрутов"><?php foreach (
    $filters
    as $key => $filter
): ?><button class="route-filter<?php echo 'all' === $key
    ? ' is-active'
    : ''; ?>" type="button" data-filter="<?php echo esc_attr(
    $key,
); ?>" aria-pressed="<?php echo 'all' === $key ? 'true' : 'false'; ?>"><?php echo esc_html(
    $filter[0] . ' ' . $filter[1],
); ?></button><?php endforeach; ?></div>
<p class="screen-reader-text" id="route-status" role="status" aria-live="polite" aria-atomic="true"></p></div>
<div id="route-results"><?php foreach ($groups as $group => $items): ?>
<section class="route-section" data-route-group="<?php echo esc_attr(
    $group,
); ?>" aria-labelledby="<?php echo esc_attr(
    $group,
); ?>-routes-heading"><div class="route-section-heading"><div><h2 id="<?php echo esc_attr(
    $group,
); ?>-routes-heading"><?php autobus_text(
    'rp_' . $group . '_title',
); ?></h2><p class="muted"><?php autobus_text(
    'rp_' . $group . '_text',
); ?> <span class="route-group-count"><?php echo count($items); ?></span></p></div><?php if (
    'summer' === $group
): ?><span class="route-season"><?php autobus_text('rp_season'); ?></span><?php endif; ?></div>
<div class="route-grid"><?php foreach ($items as $route):

    $number = autobus_meta($route, 'number');
    $transfer = (bool) autobus_meta($route, 'transfer');
    $external =
        wp_parse_url(autobus_meta($route, 'url'), PHP_URL_HOST) !==
        wp_parse_url(home_url(), PHP_URL_HOST);
    ?>
<a <?php autobus_edit_record($route->ID); ?> class="route-card card<?php echo $transfer
    ? ' route-transfer'
    : ''; ?>" href="<?php echo esc_url(autobus_meta($route, 'url')); ?>" <?php if (
    $external
): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?> data-number="<?php echo esc_attr(
     $number,
 ); ?>" data-search="<?php echo esc_attr(
    $route->post_title . ' ' . autobus_meta($route, 'stops'),
); ?>" data-transfer="<?php echo $transfer ? 'true' : 'false'; ?>">
<span class="route-number"><?php echo esc_html(
    $number,
); ?></span><span class="route-card-copy"><span class="route-name"><?php echo esc_html(
    $route->post_title,
); ?></span><span class="route-labels"><span class="route-kind"><?php echo esc_html(
    autobus_meta($route, 'kind'),
); ?></span></span></span><span class="screen-reader-text"> <?php autobus_ui('ui_125560e6cb', '— расписание маршрута'); ?> <?php
echo esc_html($number);
echo $external ? ', новая вкладка' : '';
?></span></a>
<?php
endforeach; ?></div></section><?php endforeach; ?></div>
<div class="route-empty card" hidden><h2><?php autobus_ui('ui_90d1d4a324', 'Маршруты не найдены'); ?></h2><p class="muted"><?php autobus_ui('ui_7ce455e57c', 'Попробуйте другой номер, название остановки или выберите другую категорию.'); ?></p><button type="button" class="button button-primary" id="route-reset"><?php autobus_ui('ui_eec2d77a2f', 'Сбросить поиск'); ?></button></div>
