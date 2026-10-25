<?php
defined('ABSPATH') || exit();

function agm_planner_main_label()
{
    return in_array(wp_timezone_string(), ['Asia/Irkutsk', '+08:00'], true)
        ? 'Улан-Удэ'
        : wp_timezone_string();
}
function agm_planner_time_value($timestamp)
{
    return wp_date('d.m.Y H:i', $timestamp, wp_timezone());
}
function agm_planner_time($timestamp)
{
    echo '<span class="agm-planner-main-time">' .
        esc_html(agm_planner_main_label() . ': ' . agm_planner_time_value($timestamp)) .
        '</span>';
}
function agm_planner_clock_panel()
{
    $now = time();
    echo '<div class="agm-planner-clocks"><div><span>Время сайта — ' .
        esc_html(agm_planner_main_label()) .
        ' (UTC' .
        esc_html(wp_date('P', $now, wp_timezone())) .
        ')</span><strong>' .
        esc_html(agm_planner_time_value($now)) .
        '</strong></div></div><p class="description">День и время задания указывайте по времени Улан-Удэ.</p>';
}
function agm_planner_time_preview($day_id, $clock_id, $day, $clock)
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $day . 'T' . $clock, wp_timezone());
    $valid = $date && $date->format('Y-m-d\TH:i') === $day . 'T' . $clock;
    echo '<p class="agm-planner-time-preview" role="status" aria-live="polite" data-planner-time-preview data-day-input="' .
        esc_attr($day_id) .
        '" data-clock-input="' .
        esc_attr($clock_id) .
        '" data-site-label="' .
        esc_attr(agm_planner_main_label()) .
        '">';
    if ($valid) {
        agm_planner_time($date->getTimestamp());
    } else {
        echo 'Выберите день и время задания по времени Улан-Удэ.';
    }
    echo '</p>';
}
add_action('admin_enqueue_scripts', function ($hook) {
    if (
        !in_array($hook, ['agm_route_page_agm-route-planner', 'posts_page_agm-news-planner'], true)
    ) {
        return;
    }
    $base = dirname(__DIR__);
    foreach (['css', 'js'] as $ext) {
        $url = plugins_url('assets/planner-time.' . $ext, $base . '/autobus03-content.php');
        $version = filemtime($base . '/assets/planner-time.' . $ext);
        if ('css' === $ext) {
            wp_enqueue_style('agm-planner-time', $url, [], $version);
        } else {
            wp_enqueue_script('agm-planner-time', $url, [], $version, true);
        }
    }
});
