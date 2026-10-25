<section class="feedback-section"><div class="container"><div class="feedback-card"><div><p class="pill"><?php autobus_text(
    'feedback_eyebrow',
); ?></p><h2><?php autobus_text('feedback_title'); ?></h2><p><?php autobus_text(
    'feedback_text',
); ?></p></div><a class="button button-white" href="<?php echo esc_url(
    autobus_value('feedback_url'),
); ?>" target="_blank" rel="noopener noreferrer"><?php autobus_text(
    'feedback_button',
); ?><span class="screen-reader-text"> <?php autobus_ui('ui_be10dbe8b1', '— открыть в новой вкладке'); ?></span></a></div></div></section>
