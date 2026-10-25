<section class="vacancy-map-section"><div class="container"><div class="vacancy-map-card"><div class="vacancy-map-heading"><div><h2><?php autobus_text(
    'vp_map_title',
); ?></h2><p class="muted"><?php autobus_text(
    'vp_map_text',
); ?></p></div><div class="vacancy-map-links"><a class="vacancy-map-primary" href="<?php echo esc_url(
    autobus_value('vp_map_url'),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_ui('ui_5c3a10576f', 'Открыть в Яндекс Картах'); ?></a><a href="<?php echo esc_url(
    autobus_value('vp_map_2gis_url') ?: 'https://2gis.ru/ulanude/firm/70000001029167064',
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_ui('ui_14cf8b9733', '2ГИС'); ?></a></div></div><div class="vacancy-map"><iframe src="<?php echo esc_url(
    autobus_value('vp_map_embed'),
); ?>" title="Яндекс Карта: отдел кадров МУП «Городские маршруты», Улан-Удэ, проспект Автомобилистов, 3А" loading="lazy" allowfullscreen></iframe></div><div class="vacancy-map-address"><?php autobus_icon(
    'pin',
); ?><div><strong><?php autobus_text(
    'vp_map_address',
); ?></strong><p class="muted"><?php autobus_text(
    'vp_map_note',
); ?></p></div></div></div></div></section>
