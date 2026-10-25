<?php
$route = $args['route'];
$directions = $args['directions'];
$available = [];
$labels = agm_timetable_day_schema();
foreach ($labels as $key => $label) {
    $labels[$key] = autobus_value('tt_' . $key . '_label') ?: $label;
}
foreach ($directions as $direction) {
    $available[$direction->ID] = [];
    foreach (agm_direction_day_schema($direction) as $day => $label) {
        if (agm_sanitize_times(get_post_meta($direction->ID, '_autobus_' . $day, true))) {
            $available[$direction->ID][] = $day;
        }
    }
    if (!$available[$direction->ID]) {
        $available[$direction->ID][] = 'unspecified';
    }
}
$used = array_merge([], ...array_values($available));
$days = array_intersect_key($labels, array_flip($used));
?>
<section class="card timetable-graph" aria-label="График отправления"><div class="timetable-controls" hidden><p class="timetable-control-label"><?php autobus_text(
    'tt_directions_label',
); ?></p><div class="timetable-directions" role="group" aria-label="<?php echo esc_attr(
    autobus_value('tt_directions_label'),
); ?>"><?php foreach (
    $directions
    as $index => $direction
): ?><button type="button" class="timetable-direction<?php echo 0 === $index
    ? ' is-active'
    : ''; ?>" data-direction="<?php echo esc_attr(
    $direction->ID,
); ?>" aria-pressed="<?php echo 0 === $index
    ? 'true'
    : 'false'; ?>" aria-controls="timetable-panels"><?php echo esc_html(
    $direction->post_title,
); ?></button><?php endforeach; ?></div>
<select class="timetable-direction-select" aria-label="<?php echo esc_attr(
    autobus_value('tt_directions_label') ?: 'Направление движения',
); ?>"><?php foreach ($directions as $direction): ?><option value="<?php echo esc_attr(
    $direction->ID,
); ?>"><?php echo esc_html($direction->post_title); ?></option><?php endforeach; ?></select>
<div class="timetable-day-row"><div class="timetable-day-controls"><span class="timetable-control-label"><?php autobus_text(
    'tt_days_label',
); ?></span><div class="timetable-days" role="group" aria-label="<?php echo esc_attr(
    autobus_value('tt_days_label'),
); ?>"><?php foreach (
    $days
    as $key => $label
): ?><button type="button" class="timetable-day<?php echo 'weekday' === $key
    ? ' is-active'
    : ''; ?>" data-day="<?php echo esc_attr($key); ?>" aria-pressed="<?php echo 'weekday' === $key
    ? 'true'
    : 'false'; ?>" aria-controls="timetable-panels"><?php echo esc_html(
    $label,
); ?></button><?php endforeach; ?></div></div><p class="muted timetable-status" role="status" aria-live="polite" aria-atomic="true"></p></div></div>
<div id="timetable-panels"><?php foreach ($directions as $direction):
    foreach ($days as $day => $label):

        $times = preg_split(
            '/\s+/',
            autobus_departure_times($direction, $day),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );
        $hours = [];
        foreach ($times as $time) {
            $hours[substr($time, 0, 2)][] = $time;
        }
        ?>
<?php if (!in_array($day, $available[$direction->ID], true)) {
    continue;
} ?>
<div class="timetable-panel" data-direction="<?php echo esc_attr(
    $direction->ID,
); ?>" data-day="<?php echo esc_attr(
    $day,
); ?>"><h2 class="timetable-panel-heading"><?php echo esc_html(
    $direction->post_title . ' · ' . $label,
); ?></h2>
<?php if (!$times): ?><p class="muted timetable-empty"><?php autobus_text(
    'tt_empty_label',
); ?></p><?php else: ?><p class="timetable-control-label"><?php autobus_ui('ui_0d508099bc', 'Время отправления'); ?></p><div class="timetable-departures"><?php foreach (
    $hours
    as $hour => $departures
): ?><div class="timetable-hour"><ul class="timetable-times" aria-label="<?php echo esc_attr(
    'Отправления в ' . $hour . ' часов',
); ?>"><?php foreach ($departures as $time): ?><li><time datetime="<?php echo esc_attr(
    $time,
); ?>"><?php echo esc_html(
    $time,
); ?></time></li><?php endforeach; ?></ul></div><?php endforeach; ?></div><?php endif; ?>
</div><?php
    endforeach;
endforeach; ?></div>
<div class="timetable-note"><p class="muted"><?php echo esc_html(
    autobus_meta($route, 'tt_note'),
); ?></p><div><p class="muted"><?php echo esc_html(
    autobus_meta($route, 'tt_imported'),
); ?></p></div></div></section>
