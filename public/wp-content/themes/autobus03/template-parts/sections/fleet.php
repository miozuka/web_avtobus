<section class="section fleet-section"><div class="container">
    <?php
    autobus_section_title('fleet');
    $fleet = autobus_posts('agm_fleet');
    $count = array_sum(array_map(fn($post) => absint(autobus_meta($post, 'count')), $fleet));
    ?>
    <div class="fleet-summary card"><div class="fleet-number"><strong><?php echo esc_html(
        $count,
    ); ?></strong><div><b><?php autobus_text('fleet_unit'); ?></b><span><?php autobus_text(
    'fleet_note',
); ?></span></div></div><p><?php autobus_text('fleet_text'); ?></p></div>
    <div class="grid grid-three fleet-grid"><?php foreach ($fleet as $vehicle): ?>
        <article class="card fleet-card" <?php autobus_edit_record($vehicle->ID); ?>><div class="fleet-photo"><?php echo get_the_post_thumbnail(
            $vehicle->ID,
            'autobus-card',
            ['loading' => 'lazy'],
        ); ?><span class="count-pill"><?php echo esc_html(
    autobus_vehicle_count(autobus_meta($vehicle, 'count')),
); ?></span><span class="vehicle-badge"><?php
autobus_icon('bus');
echo esc_html(autobus_meta($vehicle, 'badge'));
?></span></div><div class="card-body"><h3><?php echo esc_html(
    $vehicle->post_title,
); ?></h3><p class="muted"><?php echo esc_html($vehicle->post_excerpt); ?></p></div></article>
    <?php endforeach; ?></div>
</div></section>
