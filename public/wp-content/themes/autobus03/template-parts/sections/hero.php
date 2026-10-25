<section class="hero" aria-labelledby="hero-title">
    <?php autobus_image('hero_image', 'full', [
        'class' => 'hero-image',
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'decoding' => 'async',
    ]); ?>
    <div class="container hero-content"><p class="pill"><?php autobus_text(
        'hero_eyebrow',
    ); ?></p><h1 id="hero-title"><?php echo nl2br(
    esc_html(autobus_value('hero_title')),
); ?></h1><p class="hero-lead"><?php autobus_text(
    'hero_text',
); ?></p><p class="hero-note"><?php autobus_text('hero_note'); ?></p></div>
</section>
