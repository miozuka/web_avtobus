<?php
defined("ABSPATH") || exit();
$job = $args["job"] ?? null;
if (!($job instanceof WP_Post) || $job->post_type !== "agm_job") {
    return;
}
$phone = autobus_meta($job, "phone") ?: autobus_value("vp_hr_phone");
$details = [];
foreach (
    [
        "duties" => "Обязанности",
        "requirements" => "Требования",
        "conditions" => "Условия работы",
    ]
    as $key => $label
) {
    $text = autobus_meta($job, $key);
    if (trim($text) !== "") {
        $details[$label] = array_filter(
            array_map("trim", preg_split('/\r\n|\r|\n/', $text)),
            fn($line) => $line !== "",
        );
    }
}
?>
<article <?php autobus_edit_record($job->ID); ?> class="vacancy-job" id="vacancy-<?php echo absint($job->ID); ?>">
<div class="vacancy-job-top">
<?php if (
    autobus_meta($job, "badge")
): ?><p class="vacancy-label"><?php echo esc_html(
    autobus_meta($job, "badge"),
); ?></p><?php endif; ?>
<?php if (
    autobus_meta($job, "schedule")
): ?><span class="muted"><?php echo esc_html(
    autobus_meta($job, "schedule"),
); ?></span><?php endif; ?>
</div>
<div class="vacancy-job-grid"><div><h3><?php echo esc_html(
    $job->post_title,
); ?></h3><p class="muted"><?php echo esc_html($job->post_excerpt); ?></p>
<?php if (
    autobus_meta($job, "features")
): ?><ul class="vacancy-features"><?php foreach (
    explode(";", autobus_meta($job, "features"))
    as $feature
):
    if (!trim($feature)) {
        continue;
    } ?><li><?php echo esc_html(trim($feature)); ?></li><?php
endforeach; ?></ul><?php endif; ?>
</div><div class="vacancy-job-actions"><p class="vacancy-label"><?php autobus_ui('ui_ee74ace4b7', 'Заработная плата'); ?></p><strong class="vacancy-salary"><?php echo esc_html(
    autobus_meta($job, "salary") ?: "Уточняйте в отделе кадров",
); ?></strong>
<?php if (
    autobus_meta($job, "salary_note")
): ?><p class="muted"><?php echo esc_html(
    autobus_meta($job, "salary_note"),
); ?></p><?php endif; ?>
<?php if ($phone): ?><a class="vacancy-button" href="<?php echo esc_url(
    autobus_phone_link($phone),
); ?>"><?php autobus_icon("phone"); ?>Позвонить: <?php echo esc_html(
    $phone,
); ?></a><?php endif; ?>
<?php if (
    autobus_meta($job, "url")
): ?><a class="vacancy-job-details" href="<?php echo esc_url(
    autobus_meta($job, "url"),
); ?>" target="_blank" rel="noopener noreferrer"><?php echo $details
    ? "Открыть вакансию на другом сайте"
    : "Подробнее о вакансии"; ?></a><?php endif; ?>
</div></div>
<?php if (
    $details
): ?><details class="vacancy-job-expanded"><summary><?php autobus_ui('ui_665c76cb8b', 'Подробнее о вакансии'); ?></summary><div class="vacancy-job-expanded-grid"><?php foreach (
    $details
    as $label => $lines
): ?><section><h4><?php echo esc_html($label); ?></h4><ul><?php foreach (
    $lines
    as $line
): ?><li><?php echo esc_html(
    $line,
); ?></li><?php endforeach; ?></ul></section><?php endforeach; ?></div></details><?php endif; ?>
</article>
