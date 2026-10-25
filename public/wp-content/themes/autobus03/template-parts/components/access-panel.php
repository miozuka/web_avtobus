<section class="access-panel" id="access-panel" aria-label="Настройки доступности" hidden>
    <div class="container access-controls">
        <strong><?php autobus_ui('ui_733b1d6372', 'Настройки чтения'); ?></strong>
        <label><?php autobus_ui('ui_b1e23e1ff0', 'Размер текста'); ?><select id="access-size"><option value="normal"><?php autobus_ui('ui_a2c84dc5a6', 'Обычный'); ?></option><option value="large"><?php autobus_ui('ui_b84572428f', 'Крупный'); ?></option><option value="extra"><?php autobus_ui('ui_f964aa89b8', 'Очень крупный'); ?></option></select></label>
        <label><?php autobus_ui('ui_fe3b049738', 'Контраст'); ?><select id="access-contrast"><option value="normal"><?php autobus_ui('ui_a2c84dc5a6', 'Обычный'); ?></option><option value="light"><?php autobus_ui('ui_3136ed02c1', 'Чёрный на белом'); ?></option><option value="dark"><?php autobus_ui('ui_304b9b34d9', 'Белый на чёрном'); ?></option><option value="yellow"><?php autobus_ui('ui_6e1224d3ce', 'Жёлтый на чёрном'); ?></option></select></label>
        <label><input id="access-images" type="checkbox"> <?php autobus_ui('ui_e564cebceb', 'Скрыть изображения'); ?></label>
        <label><input id="access-spacing" type="checkbox"> <?php autobus_ui('ui_388f8ed164', 'Увеличить интервал'); ?></label>
        <button type="button" id="access-reset"><?php autobus_ui('ui_058f162d29', 'Сбросить'); ?></button>
        <button type="button" id="access-close" aria-label="Закрыть настройки доступности"><?php autobus_ui('ui_df3cff7ae7', 'Закрыть ×'); ?></button>
    </div>
</section>
