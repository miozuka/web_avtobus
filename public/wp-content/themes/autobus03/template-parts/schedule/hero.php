<section class="route-hero card" aria-labelledby="schedule-heading"><div class="route-hero-copy">
    <p class="pill"><?php autobus_text('rp_eyebrow'); ?></p>
    <h1 id="schedule-heading"><?php echo nl2br(esc_html(autobus_value('rp_title'))); ?></h1>
    <p class="muted"><?php autobus_text('rp_text'); ?></p>
</div><div class="route-hero-actions">
    <a class="button button-primary" href="<?php echo esc_url(
        autobus_value('rp_live_url'),
    ); ?>" target="_blank" rel="noopener noreferrer"><?php
autobus_icon('bus');
autobus_text('rp_live_label');
?></a>
    <a class="button route-map-button" href="<?php echo esc_url(
        autobus_value('rp_map_url'),
    ); ?>" target="_blank" rel="noopener noreferrer"><?php
autobus_icon('pin');
autobus_text('rp_map_label');
?></a>
</div></section>
