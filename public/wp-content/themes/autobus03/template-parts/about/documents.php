<section id="about-documents" class="about-documents section"><div class="container"><p class="eyebrow"><?php autobus_text(
    'ap_documents_eyebrow',
); ?></p><h2><?php autobus_text(
    'ap_documents_title',
); ?></h2><p class="muted about-documents-text"><?php autobus_text(
    'ap_documents_text',
); ?></p><div class="about-document-grid"><?php foreach (
    autobus_about_posts('agm_document')
    as $document
):

    $file_id = absint(autobus_meta($document, 'file'));
    $url = wp_get_attachment_url($file_id);
    if (!$url) {
        continue;
    }
    $file = get_attached_file($file_id);
    ?><article class="about-document" <?php autobus_edit_record($document->ID); ?>><span class="about-document-icon"><?php autobus_icon(
    'document',
); ?></span><div><h3><?php echo esc_html(
    $document->post_title,
); ?></h3><p class="muted">PDF<?php if ($file && is_file($file)) {
    echo ' · ' . esc_html(size_format(filesize($file), 1));
} ?></p></div><div class="about-document-actions"><a class="about-document-view" href="<?php echo esc_url(
    $url,
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_ui('ui_41f4446817', 'Посмотреть'); ?><span class="screen-reader-text">: <?php echo esc_html(
    $document->post_title,
); ?> — открыть в новой вкладке</span></a><a href="<?php echo esc_url(
     $url,
 ); ?>" download><?php autobus_ui('ui_fe8f79f29d', 'Скачать'); ?><span class="screen-reader-text">: <?php echo esc_html(
    $document->post_title,
); ?></span></a></div></article><?php
endforeach; ?></div></div></section>
