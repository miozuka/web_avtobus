<?php $route = $args['route']; ?>
<section class="card timetable-header" aria-labelledby="timetable-title"><span class="timetable-number" aria-label="<?php echo esc_attr(
    'Маршрут ' . autobus_meta($route, 'tt_number'),
); ?>"><?php echo esc_html(
    autobus_meta($route, 'tt_number'),
); ?></span><div class="timetable-heading"><p class="pill"><?php echo esc_html(
    autobus_meta($route, 'tt_badge'),
); ?></p><h1 id="timetable-title"><?php echo esc_html(
    $route->post_title,
); ?></h1></div>
<button class="button timetable-print" type="button" hidden><svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M6 8V3h12v5M6 17H3V8h18v9h-3M6 13h12v8H6zM17 11h1"/></svg><?php autobus_text(
    'tt_print_label',
); ?></button></section>
