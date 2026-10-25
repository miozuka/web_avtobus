<section class="section schedule-section"><div class="container"><div class="schedule-card card">
    <div class="schedule-content"><p class="pill"><?php autobus_text(
        'schedule_eyebrow',
    ); ?></p><h2><?php autobus_text(
    'schedule_title',
); ?></h2><p class="subtitle"><?php autobus_text(
    'schedule_subtitle',
); ?></p><p class="muted"><?php autobus_text(
    'schedule_text',
); ?></p><a class="button" href="<?php echo esc_url(
    autobus_value('schedule_url'),
); ?>"><?php autobus_text('schedule_button'); ?></a></div>
    <div class="schedule-photo"><?php autobus_image('schedule_image', 'large', [
        'loading' => 'lazy',
    ]); ?></div>
</div></div></section>
