<section id="about-history" class="about-history section"><div class="container"><div class="about-section-heading"><p class="eyebrow"><?php autobus_text(
    'ap_history_eyebrow',
); ?></p><h2><?php autobus_text('ap_history_title'); ?></h2><p class="muted"><?php autobus_text(
    'ap_history_text',
); ?></p></div><ol class="about-timeline"><?php foreach (
    autobus_about_posts('agm_history')
    as $event
): ?><li <?php autobus_edit_record($event->ID); ?> class="about-event<?php echo autobus_meta($event, 'highlight')
    ? ' is-highlighted'
    : ''; ?>"><article><p class="about-event-date"><?php echo esc_html(
    autobus_meta($event, 'date'),
); ?></p><h3><?php echo esc_html($event->post_title); ?></h3><p class="muted"><?php echo esc_html(
    $event->post_excerpt,
); ?></p></article></li><?php endforeach; ?></ol></div></section>
