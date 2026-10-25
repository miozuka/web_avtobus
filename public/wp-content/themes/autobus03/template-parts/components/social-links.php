<?php
defined('ABSPATH') || exit();
$networks = ['vk' => 'ВКонтакте', 'telegram' => 'Telegram', 'max' => 'MAX'];
?>
<div class="social-links" aria-label="Социальные сети предприятия">
    <?php foreach ($networks as $network => $label):

        $url = autobus_value($network . '_url');
        if (!$url) {
            continue;
        }
        ?>
        <a href="<?php echo esc_url(
            $url,
        ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(
    $label . ' — открыть в новой вкладке',
); ?>" title="<?php echo esc_attr($label); ?>">
            <?php autobus_social_icon($network); ?>
        </a>
    <?php
    endforeach; ?>
</div>
