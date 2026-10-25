<section class="vacancy-opportunities"><div class="container">
<div class="vacancy-hh"><span class="vacancy-hh-logo" aria-hidden="true">hh</span><div><span class="vacancy-label">HeadHunter</span><h2><?php autobus_text(
    "vp_hh_title",
); ?></h2><p class="muted"><?php autobus_text(
    "vp_hh_text",
); ?></p></div><a class="vacancy-button vacancy-hh-button" href="<?php echo esc_url(
    autobus_value("vp_hh_url"),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_text(
    "vp_hh_button",
); ?><span class="screen-reader-text"> <?php autobus_ui('ui_be10dbe8b1', '— открыть в новой вкладке'); ?></span></a></div>
<div class="vacancy-hr-line"><div><?php autobus_icon(
    "phone",
); ?><p><strong><?php autobus_text(
    "vp_hr_title",
); ?></strong><span><?php autobus_text(
    "vp_hr_text",
); ?></span></p></div><div class="vacancy-hr-links"><a href="<?php echo esc_url(
    autobus_phone_link(autobus_value("vp_hr_phone")),
); ?>"><?php
autobus_icon("phone");
autobus_text("vp_hr_phone");
?></a><?php foreach (
    ["telegram" => "Telegram", "max" => "MAX"]
    as $network => $label
):

    $url =
        autobus_value("vp_hr_" . $network . "_url") ?:
        autobus_value($network . "_url");
    if (!$url) {
        continue;
    }
    ?><a class="vacancy-contact-social" href="<?php echo esc_url(
    $url,
); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(
    $label . " предприятия — открыть в новой вкладке",
); ?>"><?php autobus_social_icon($network); ?></a><?php
endforeach; ?></div></div>
<div class="vacancy-layout"><aside class="vacancy-sidebar"><article class="vacancy-poster-card"><p class="vacancy-label"><?php autobus_text(
    "vp_poster_title",
); ?></p><a href="<?php echo esc_url(
    wp_get_attachment_url(absint(autobus_value("vp_poster"))),
); ?>" target="_blank" rel="noopener noreferrer" aria-label="Открыть объявление предприятия в полном размере"><?php autobus_image(
    "vp_poster",
    "large",
    ["loading" => "lazy"],
); ?></a><h2><?php
autobus_icon("check");
autobus_text("vp_guarantee_title");
?></h2><p class="muted"><?php autobus_text(
    "vp_guarantee_text",
); ?></p></article></aside>
<div class="vacancy-list"><header class="vacancy-list-heading"><h2><?php autobus_text(
    "vp_jobs_title",
); ?></h2><p class="muted"><?php autobus_text(
    "vp_jobs_note",
); ?></p></header><div class="vacancy-jobs-scroll" tabindex="0" role="region" aria-label="Список вакансий, прокрутка вверх и вниз"><?php
$jobs = autobus_about_posts("agm_job");
foreach ($jobs as $job) {
    get_template_part("template-parts/vacancy/job-card", null, ["job" => $job]);
}
if (!$jobs): ?><p class="muted"><?php autobus_text(
    "vp_empty",
); ?></p><?php endif;
?></div></div></div>
</div></section>
