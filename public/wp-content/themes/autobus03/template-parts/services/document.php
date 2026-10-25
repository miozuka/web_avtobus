<?php $document = wp_get_attachment_url(absint(autobus_value('sp_document'))); ?>
<section class="services-document"><div class="container"><div class="card document-card"><div><p class="pill"><?php
autobus_icon('document');
autobus_text('sp_document_eyebrow');
?></p><h2><?php autobus_text('sp_document_title'); ?></h2><p class="muted"><?php autobus_text(
    'sp_document_text',
); ?></p></div><?php if ($document): ?><a class="button" href="<?php echo esc_url(
    $document,
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_text(
    'sp_document_button',
); ?><span class="screen-reader-text"> <?php autobus_ui('ui_973a24fd3b', '— открыть документ в новой вкладке'); ?></span></a><?php endif; ?></div></div></section>
