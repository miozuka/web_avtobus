<?php
defined('ABSPATH') || exit();
add_shortcode('autobus_sales_search', function () {
    return '<div class="passenger-search"><label for="sales-search">Поиск по адресу или остановке</label><input id="sales-search" data-sales-search type="search" placeholder="Улица или остановка" autocomplete="off"><p class="passenger-search-status" role="status" aria-live="polite"></p></div>';
});
