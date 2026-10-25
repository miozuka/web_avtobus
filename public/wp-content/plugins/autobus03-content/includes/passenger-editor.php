<?php
defined('ABSPATH') || exit();
function agm_passenger_pages()
{
    $pages = [];
    foreach (['ticket', 'travel_benefits'] as $slug) {
        $p = get_page_by_path($slug);
        if ($p) {
            $pages[$slug] = $p->ID;
        }
    }
    return apply_filters('agm_passenger_editable_pages', $pages);
}
function agm_passenger_class($node, $class)
{
    return $node instanceof DOMElement &&
        in_array($class, explode(' ', $node->getAttribute('class')), true);
}
function agm_passenger_first($xp, $query, $node)
{
    return $xp->query($query, $node)->item(0);
}
function agm_passenger_text($xp, $query, $node)
{
    $n = agm_passenger_first($xp, $query, $node);
    return $n ? trim($n->textContent) : '';
}
// Поля определяются по сохранённой странице на сервере. Пути к узлам из браузера не принимаются.
function agm_passenger_model($post)
{
    $doc = new DOMDocument('1.0', 'UTF-8');
    $before = libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="UTF-8"><div id="agm-passenger-root">' . $post->post_content . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($before);
    $xp = new DOMXPath($doc);
    $root = $xp->query('//*[@id="agm-passenger-root"]')->item(0);
    $groups = [];
    $cards = [];
    $sales = [];
    $excluded = [];
    foreach (
        $xp->query(
            './/*[contains(concat(" ",normalize-space(@class)," ")," passenger-grid ")]',
            $root,
        )
        as $grid
    ) {
        $rows = [];
        $heading = 'Виды проездных';
        $ancestor = $grid->parentNode;
        while ($ancestor !== $root && $ancestor instanceof DOMElement) {
            if ($ancestor->tagName === 'section') {
                $heading = agm_passenger_text($xp, './h2', $ancestor) ?: $heading;
                break;
            }
            $ancestor = $ancestor->parentNode;
        }
        foreach ($xp->query('./article', $grid) as $article) {
            $description = agm_passenger_first(
                $xp,
                './p[contains(@class,"muted") or contains(@class,"passenger-documents")]',
                $article,
            );
            $text = '';
            if ($description) {
                foreach ($description->childNodes as $child) {
                    if (!($child instanceof DOMElement && $child->tagName === 'strong')) {
                        $text .= $child->textContent;
                    }
                }
            }
            $rows[] = [
                'title' => agm_passenger_text($xp, './h2|./h3', $article),
                'badge' => agm_passenger_text($xp, './p[contains(@class,"pill")]', $article),
                'price' => agm_passenger_text(
                    $xp,
                    './strong[contains(@class,"passenger-price")]',
                    $article,
                ),
                'text' => trim($text),
                'link_text' => agm_passenger_text($xp, './/a', $article),
                'link_url' => ($a = agm_passenger_first($xp, './/a', $article))
                    ? $a->getAttribute('href')
                    : '',
            ];
        }
        if (!$rows && !$grid->hasAttribute('data-agm-card-class')) {
            continue;
        }
        $first = agm_passenger_first($xp, './article', $grid);
        $cards[] = [
            'title' => $heading,
            'node' => $grid,
            'class' => $first
                ? $first->getAttribute('class')
                : $grid->getAttribute('data-agm-card-class'),
            'heading' => $first
                ? (agm_passenger_first($xp, './h2', $first)
                    ? 'h2'
                    : 'h3')
                : ($grid->getAttribute('data-agm-card-heading') ?:
                'h3'),
            'documents' => $first
                ? (($d = agm_passenger_first(
                    $xp,
                    './p[contains(@class,"passenger-documents")]/strong',
                    $first,
                ))
                    ? $d->textContent
                    : '')
                : $grid->getAttribute('data-agm-card-documents'),
            'rows' => $rows,
        ];
        $excluded[] = $grid;
    }
    $salesNodes = [];
    foreach ($xp->query('.//*[@data-sales-group]', $root) as $group) {
        $rows = [];
        foreach ($xp->query('.//*[@data-sales-point]', $group) as $point) {
            $link = agm_passenger_first($xp, './/a', $point);
            $rows[] = [
                'address' => agm_passenger_text($xp, './/strong', $point),
                'note' => agm_passenger_text($xp, './/span', $point),
                'url' => $link ? $link->getAttribute('href') : '',
                'link_text' => $link ? trim($link->textContent) : 'На карте',
            ];
        }
        $sales[] = ['title' => agm_passenger_text($xp, './h3', $group), 'points' => $rows];
        $salesNodes[] = $group;
        $excluded[] = $group;
    }
    $fields = [];
    $links = [];
    foreach ($xp->query('.//text()', $root) as $node) {
        $value = trim($node->nodeValue);
        if ($value === '' || str_contains($value, '[autobus_sales_search]')) {
            continue;
        }
        $parent = $node->parentNode;
        $skip = false;
        $walk = $parent;
        while ($walk && $walk !== $root) {
            foreach ($excluded as $area) {
                if ($walk->isSameNode($area)) {
                    $skip = true;
                    break 2;
                }
            }
            if (
                $walk instanceof DOMElement &&
                in_array($walk->tagName, ['script', 'style', 'template'], true)
            ) {
                $skip = true;
                break;
            }
            $walk = $walk->parentNode;
        }
        if ($skip) {
            continue;
        }
        $section = $parent;
        $title = 'Дополнительная информация';
        $groupId = 'extra';
        while ($section && $section !== $root) {
            if (
                $section instanceof DOMElement &&
                in_array($section->tagName, ['article', 'section'], true)
            ) {
                $title = agm_passenger_text($xp, './h1|./h2|./h3', $section) ?: 'Текст раздела';
                $groupId = substr(hash('sha256', $section->getNodePath()), 0, 12);
                break;
            }
            $section = $section->parentNode;
        }
        $tag = $parent instanceof DOMElement ? $parent->tagName : '';
        $label = match (true) {
            in_array($tag, ['h1', 'h2', 'h3'], true) => 'Заголовок',
            agm_passenger_class($parent, 'pill') => 'Метка раздела',
            agm_passenger_class($parent, 'passenger-price') => 'Стоимость',
            $tag === 'a' => 'Текст ссылки',
            $tag === 'strong' => 'Выделенная подпись',
            default => 'Текст',
        };
        $id = substr(hash('sha256', $node->getNodePath()), 0, 20);
        $fields[$id] = [
            'node' => $node,
            'value' => $value,
            'kind' => 'text',
            'label' => $label,
            'group' => $groupId,
            'title' => $title,
        ];
        if (
            $tag === 'a' &&
            $parent->hasAttribute('href') &&
            !isset($links[$parent->getNodePath()])
        ) {
            $links[$parent->getNodePath()] = true;
            $linkId = substr(hash('sha256', $parent->getNodePath() . '/@href'), 0, 20);
            $fields[$linkId] = [
                'node' => $parent,
                'value' => $parent->getAttribute('href'),
                'kind' => 'url',
                'label' => 'Адрес ссылки',
                'group' => $groupId,
                'title' => $title,
            ];
        }
    }
    return [
        'doc' => $doc,
        'xp' => $xp,
        'root' => $root,
        'fields' => $fields,
        'cards' => $cards,
        'sales' => $sales,
        'sales_nodes' => $salesNodes,
        'sales_parent' => ($n = $xp
            ->query('.//text()[contains(.,"[autobus_sales_search]")]', $root)
            ->item(0))
            ? $n->parentNode
            : null,
    ];
}
function agm_passenger_data($model)
{
    return [
        'cards' => array_map(
            fn($group) => [
                'title' => $group['title'],
                'documents' => $group['documents'],
                'rows' => $group['rows'],
            ],
            $model['cards'],
        ),
        'sales' => $model['sales'],
    ];
}
function agm_passenger_link($value)
{
    if (!is_scalar($value)) {
        return new WP_Error('url', 'Некорректная ссылка.');
    }
    $value = trim((string) $value);
    if (
        $value !== '' &&
        !preg_match(
            '~^(https?://[^\s<>]+|tel:\+?[0-9() -]+|mailto:[^\s<>]+|/[^\s<>]*|\#[^\s<>]*)$~iu',
            $value,
        )
    ) {
        return new WP_Error('url', 'Ссылка должна начинаться с https://, /, tel: или mailto:.');
    }
    return esc_url_raw($value, ['http', 'https', 'tel', 'mailto']);
}
function agm_passenger_element($doc, $tag, $class, $text = '')
{
    $n = $doc->createElement($tag);
    if ($class) {
        $n->setAttribute('class', $class);
    }
    if ($text !== '') {
        $n->appendChild($doc->createTextNode($text));
    }
    return $n;
}
function agm_passenger_save($id, $version, $values, $data)
{
    $post = get_post($id);
    if (
        !$post ||
        !in_array((int) $id, array_map('intval', agm_passenger_pages()), true) ||
        !current_user_can('edit_post', $id)
    ) {
        return new WP_Error('forbidden', 'Нет прав на изменение этой страницы.');
    }
    if (!is_string($version) || !hash_equals(hash('sha256', $post->post_content), $version)) {
        return new WP_Error(
            'conflict',
            'Страница уже изменена в другом окне. Обновите форму перед сохранением.',
        );
    }
    $m = agm_passenger_model($post);
    if (
        !is_array($values) ||
        !is_array($data) ||
        !is_array($data['cards'] ?? null) ||
        count($data['cards']) !== count($m['cards'])
    ) {
        return new WP_Error('form', 'Форма получена не полностью. Ничего не сохранено.');
    }
    $changed = false;
    foreach ($m['fields'] as $key => $field) {
        if (!array_key_exists($key, $values) || !is_scalar($values[$key])) {
            return new WP_Error('field', 'Не получено одно из полей. Обновите страницу.');
        }
        $raw = (string) $values[$key];
        $value =
            $field['kind'] === 'url' ? agm_passenger_link($raw) : sanitize_textarea_field($raw);
        if (is_wp_error($value)) {
            return $value;
        }
        if (mb_strlen($value) > 20000) {
            return new WP_Error('length', 'Слишком длинный текст.');
        }
        if ($value === $field['value']) {
            continue;
        }
        $changed = true;
        if ($field['kind'] === 'url') {
            $field['node']->setAttribute('href', $value);
        } else {
            $original = $field['node']->nodeValue;
            preg_match('/^\s*/u', $original, $lead);
            preg_match('/\s*$/u', $original, $tail);
            $field['node']->nodeValue = ($lead[0] ?? '') . $value . ($tail[0] ?? '');
        }
    }
    $clean = ['cards' => [], 'sales' => []];
    foreach ($data['cards'] as $index => $group) {
        if (
            !is_array($group) ||
            !isset($m['cards'][$index]) ||
            !is_array($group['rows'] ?? null) ||
            count($group['rows']) > 100
        ) {
            return new WP_Error('cards', 'Проверьте список карточек.');
        }
        $rows = [];
        foreach ($group['rows'] as $row) {
            if (!is_array($row)) {
                return new WP_Error('card', 'Некорректная карточка.');
            }
            $item = [];
            foreach (['title', 'badge', 'price', 'text', 'link_text', 'link_url'] as $key) {
                if (!isset($row[$key]) || !is_scalar($row[$key])) {
                    return new WP_Error('card', 'Карточка получена не полностью.');
                }
                $value =
                    $key === 'link_url'
                        ? agm_passenger_link($row[$key])
                        : sanitize_textarea_field($row[$key]);
                if (is_wp_error($value)) {
                    return $value;
                }
                $item[$key] = $value;
            }
            if ($item['title'] === '') {
                return new WP_Error(
                    'title',
                    'Заполните название каждой карточки или удалите пустую карточку.',
                );
            }
            if (($item['link_url'] === '') !== ($item['link_text'] === '')) {
                return new WP_Error(
                    'link',
                    'У кнопки нужны и текст, и ссылка. Если кнопка не нужна, очистите оба поля.',
                );
            }
            $rows[] = $item;
        }
        $clean['cards'][] = [
            'title' => $m['cards'][$index]['title'],
            'documents' => sanitize_text_field(
                is_scalar($group['documents'] ?? '') ? $group['documents'] : 'Документы',
            ),
            'rows' => $rows,
        ];
    }
    if (!is_array($data['sales'] ?? null) || count($data['sales']) > 20) {
        return new WP_Error('sales', 'Некорректный список пунктов продажи.');
    }
    $total = 0;
    foreach ($data['sales'] as $group) {
        if (
            !is_array($group) ||
            !is_scalar($group['title'] ?? null) ||
            !is_array($group['points'] ?? null)
        ) {
            return new WP_Error('sales', 'Группа пунктов получена не полностью.');
        }
        $title = sanitize_text_field($group['title']);
        if ($title === '') {
            return new WP_Error('group', 'Заполните название группы пунктов продажи.');
        }
        $points = [];
        foreach ($group['points'] as $row) {
            if (++$total > 200) {
                return new WP_Error(
                    'sales',
                    'В одной странице поддерживается до 200 пунктов продажи.',
                );
            }
            $item = [];
            foreach (['address', 'note', 'url', 'link_text'] as $key) {
                if (!isset($row[$key]) || !is_scalar($row[$key])) {
                    return new WP_Error('point', 'Пункт продажи получен не полностью.');
                }
                $item[$key] =
                    $key === 'url'
                        ? agm_passenger_link($row[$key])
                        : sanitize_text_field($row[$key]);
                if (is_wp_error($item[$key])) {
                    return $item[$key];
                }
            }
            if ($item['address'] === '') {
                return new WP_Error(
                    'address',
                    'Укажите адрес каждого пункта или удалите пустую строку.',
                );
            }
            if ($item['url'] === '') {
                $item['url'] =
                    'https://yandex.ru/maps/?text=' . rawurlencode('Улан-Удэ, ' . $item['address']);
            }
            if ($item['link_text'] === '') {
                $item['link_text'] = 'На карте';
            }
            $points[] = $item;
        }
        $clean['sales'][] = ['title' => $title, 'points' => $points];
    }
    $original = agm_passenger_data($m);
    $changed = $changed || $clean !== $original;
    // Без правок не переписываем HTML и не создаём лишнюю версию страницы.
    if (!$changed) {
        return (int) $id;
    }
    $doc = $m['doc'];
    foreach ($m['cards'] as $index => $spec) {
        $grid = $spec['node'];
        $grid->setAttribute('data-agm-card-class', $spec['class']);
        $grid->setAttribute('data-agm-card-heading', $spec['heading']);
        $grid->setAttribute('data-agm-card-documents', $clean['cards'][$index]['documents']);
        while ($grid->firstChild) {
            $grid->removeChild($grid->firstChild);
        }
        foreach ($clean['cards'][$index]['rows'] as $row) {
            $card = agm_passenger_element($doc, 'article', $spec['class']);
            if ($row['badge'] !== '') {
                $card->appendChild(agm_passenger_element($doc, 'p', 'pill', $row['badge']));
            }
            $card->appendChild(agm_passenger_element($doc, $spec['heading'], '', $row['title']));
            if ($row['price'] !== '') {
                $card->appendChild(
                    agm_passenger_element($doc, 'strong', 'passenger-price', $row['price']),
                );
            }
            if ($row['text'] !== '' || $spec['documents'] !== '') {
                $p = agm_passenger_element(
                    $doc,
                    'p',
                    $spec['documents'] !== '' ? 'passenger-documents' : 'muted',
                );
                if ($spec['documents'] !== '') {
                    $p->appendChild(
                        agm_passenger_element(
                            $doc,
                            'strong',
                            '',
                            $clean['cards'][$index]['documents'],
                        ),
                    );
                }
                $p->appendChild($doc->createTextNode($row['text']));
                $card->appendChild($p);
            }
            if ($row['link_url'] !== '') {
                $actions = agm_passenger_element($doc, 'div', 'passenger-actions');
                $a = agm_passenger_element($doc, 'a', 'passenger-button', $row['link_text']);
                $a->setAttribute('href', $row['link_url']);
                $actions->appendChild($a);
                $card->appendChild($actions);
            }
            $grid->appendChild($card);
        }
    }
    if ($m['sales_parent']) {
        $first = $m['sales_nodes'][0] ?? null;
        $parent = $m['sales_parent'];
        $marker = $doc->createComment('sales');
        $parent->insertBefore($marker, $first);
        foreach ($m['sales_nodes'] as $n) {
            $n->parentNode->removeChild($n);
        }
        foreach ($clean['sales'] as $group) {
            $s = $doc->createElement('section');
            $s->setAttribute('data-sales-group', '');
            $s->appendChild(agm_passenger_element($doc, 'h3', '', $group['title']));
            $ul = agm_passenger_element($doc, 'ul', 'passenger-points');
            foreach ($group['points'] as $row) {
                $li = agm_passenger_element($doc, 'li', 'passenger-point');
                $li->setAttribute('data-sales-point', '');
                $div = $doc->createElement('div');
                $div->appendChild(agm_passenger_element($doc, 'strong', '', $row['address']));
                if ($row['note'] !== '') {
                    $div->appendChild(agm_passenger_element($doc, 'span', '', $row['note']));
                }
                $li->appendChild($div);
                $a = agm_passenger_element($doc, 'a', '', $row['link_text']);
                $a->setAttribute('href', $row['url']);
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener noreferrer');
                $a->setAttribute('aria-label', 'Найти на карте: ' . $row['address']);
                $li->appendChild($a);
                $ul->appendChild($li);
            }
            $s->appendChild($ul);
            $parent->insertBefore($s, $marker);
        }
        $parent->removeChild($marker);
    } elseif ($clean['sales']) {
        return new WP_Error('sales', 'На этой странице нет блока пунктов продажи.');
    }
    $html = '';
    foreach ($m['root']->childNodes as $n) {
        $html .= $doc->saveHTML($n);
    }
    return agm_passenger_write($id, $post->post_content, $html);
}
add_action('admin_post_agm_passenger_save', function () {
    check_admin_referer('agm_passenger_save');
    $id = absint($_POST['page_id'] ?? 0);
    $values = wp_unslash($_POST['texts'] ?? []);
    $raw = wp_unslash($_POST['repeater_json'] ?? '');
    $data = is_string($raw) ? json_decode($raw, true) : null;
    $version = wp_unslash($_POST['version'] ?? '');
    $result = agm_passenger_save($id, $version, $values, $data);
    $token = strtolower(wp_generate_password(16, false, false));
    if (is_wp_error($result)) {
        set_transient(
            'agm_passenger_error_' . get_current_user_id() . '_' . $token,
            [
                'message' => $result->get_error_message(),
                'values' => $values,
                'data' => $data,
                'version' => $version,
            ],
            300,
        );
    }
    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'agm-passenger-content',
                'page_id' => $id,
                'saved' => is_wp_error($result) ? 'error' : 'yes',
                'attempt' => $token,
            ],
            admin_url('admin.php'),
        ),
    );
    exit();
});
function agm_passenger_screen()
{
    $pages = agm_passenger_pages();
    $id = absint($_GET['page_id'] ?? reset($pages));
    if (!in_array($id, array_map('intval', $pages), true) || !current_user_can('edit_post', $id)) {
        wp_die('Нет прав на изменение этой страницы.', '', ['response' => 403]);
    }
    $post = get_post($id);
    $backup = get_post_meta($id, '_agm_passenger_previous', true);
    if (!empty($_GET['restore']) && is_array($backup) && is_string($backup['content'] ?? null)) {
        echo '<div class="wrap agm-workspace"><h1>Вернуть предыдущую версию</h1><section class="agm-admin-panel"><h2>' .
            esc_html($post->post_title) .
            '</h2><p>Будут возвращены тексты, карточки и пункты продажи из версии перед последним сохранением. Текущая версия тоже останется доступна для обратного возврата.</p><p>Сохранена перед правкой: ' .
            esc_html(wp_date('d.m.Y H:i', (int) $backup['time'])) .
            ' (Улан-Удэ).</p><form method="post" action="' .
            esc_url(admin_url('admin-post.php')) .
            '">';
        wp_nonce_field('agm_passenger_restore');
        echo '<input type="hidden" name="action" value="agm_passenger_restore"><input type="hidden" name="page_id" value="' .
            $id .
            '"><input type="hidden" name="version" value="' .
            esc_attr(hash('sha256', $post->post_content)) .
            '">';
        submit_button('Вернуть предыдущую версию', 'secondary');
        echo '<a class="button" href="' .
            esc_url(admin_url('admin.php?page=agm-passenger-content&page_id=' . $id)) .
            '">Отмена</a></form></section></div>';
        return;
    }
    $m = agm_passenger_model($post);
    $data = agm_passenger_data($m);
    $submitted = null;
    $error = null;
    echo '<div class="wrap agm-workspace"><h1>Проездные, льготы и пункты продажи</h1><p>Меняйте текст в обычных полях. Карточки и адреса можно добавлять, удалять и переставлять. Оформление страницы сохраняется.</p><nav class="agm-workspace-tabs">';
    foreach ($pages as $slug => $page) {
        if (current_user_can('edit_post', $page)) {
            echo '<a class="button ' .
                ($page === $id ? 'button-primary' : '') .
                '" href="' .
                esc_url(admin_url('admin.php?page=agm-passenger-content&page_id=' . $page)) .
                '">' .
                esc_html($slug === 'ticket' ? 'Проездные и продажа' : 'Льготы на проезд') .
                '</a>';
        }
    }
    echo '</nav>';
    if (is_array($backup)) {
        echo '<p><a class="button" href="' .
            esc_url(
                admin_url('admin.php?page=agm-passenger-content&page_id=' . $id . '&restore=1'),
            ) .
            '">Вернуть предыдущую версию</a> <span class="description">Перед последним изменением: ' .
            esc_html(wp_date('d.m.Y H:i', (int) $backup['time'])) .
            '</span></p>';
    }
    if (($_GET['saved'] ?? '') === 'yes') {
        echo '<div class="notice notice-success"><p>Страница сохранена. Проверьте тексты, стоимость и адреса на сайте.</p></div>';
    }
    if (($_GET['saved'] ?? '') === 'error') {
        $key =
            'agm_passenger_error_' .
            get_current_user_id() .
            '_' .
            sanitize_key($_GET['attempt'] ?? '');
        $error = get_transient($key);
        delete_transient($key);
        $submitted = $error['values'] ?? null;
        $data = is_array($error['data'] ?? null) ? $error['data'] : $data;
        echo '<div class="notice notice-error"><p>' .
            esc_html($error['message'] ?? 'Изменения не сохранены.') .
            '</p></div>';
    }
    echo '<form method="post" action="' .
        esc_url(admin_url('admin-post.php')) .
        '" data-unsaved-form data-passenger-form data-sales-available="' .
        ($m['sales_parent'] ? '1' : '0') .
        '">';
    wp_nonce_field('agm_passenger_save');
    echo '<input type="hidden" name="action" value="agm_passenger_save"><input type="hidden" name="page_id" value="' .
        $id .
        '"><input type="hidden" name="version" value="' .
        esc_attr($error['version'] ?? hash('sha256', $post->post_content)) .
        '"><input type="hidden" name="repeater_json" value="' .
        esc_attr(wp_json_encode($data)) .
        '" data-passenger-json><h2>Карточки и адреса</h2><div data-passenger-repeaters></div><noscript><p>Для добавления и перестановки карточек включите JavaScript в браузере. При сохранении без него их прежний список сохранится.</p></noscript><h2>Заголовки, пояснения и кнопки страницы</h2>';
    $open = null;
    foreach ($m['fields'] as $key => $field) {
        if ($open !== $field['group']) {
            if ($open !== null) {
                echo '</details>';
            }
            $open = $field['group'];
            echo '<details class="agm-admin-panel"><summary>' .
                esc_html($field['title']) .
                '</summary>';
        }
        $value =
            is_array($submitted) && isset($submitted[$key]) && is_scalar($submitted[$key])
                ? $submitted[$key]
                : $field['value'];
        echo '<div class="agm-setting-field"><label for="agm-text-' .
            esc_attr($key) .
            '"><strong>' .
            esc_html($field['label']) .
            '</strong></label><textarea id="agm-text-' .
            esc_attr($key) .
            '" name="texts[' .
            esc_attr($key) .
            ']" rows="' .
            ($field['kind'] === 'url' ? 1 : 2) .
            '">' .
            esc_textarea($value) .
            '</textarea></div>';
    }
    if ($open !== null) {
        echo '</details>';
    }
    echo '<div class="agm-admin-save">';
    submit_button('Сохранить страницу', 'primary', 'submit', false);
    echo ' <a class="button" target="_blank" rel="noopener" href="' .
        esc_url(get_permalink($id)) .
        '">Посмотреть страницу</a><span data-save-status class="description" aria-live="polite"></span></div></form></div>';
}

// Одна предыдущая версия доступна сотруднику без редактора кода. Обычные ревизии WordPress также сохраняются.
function agm_passenger_write($id, $old_content, $new_content)
{
    $old_backup = get_post_meta($id, '_agm_passenger_previous', true);
    $backup = ['content' => $old_content, 'time' => time(), 'user' => get_current_user_id()];
    update_post_meta($id, '_agm_passenger_previous', wp_slash($backup));
    if (get_post_meta($id, '_agm_passenger_previous', true) !== $backup) {
        return new WP_Error(
            'backup',
            'Не удалось сохранить предыдущую версию. Страница не изменена.',
        );
    }
    $result = wp_update_post(wp_slash(['ID' => (int) $id, 'post_content' => $new_content]), true);
    if (is_wp_error($result)) {
        if ($old_backup) {
            update_post_meta($id, '_agm_passenger_previous', wp_slash($old_backup));
        } else {
            delete_post_meta($id, '_agm_passenger_previous');
        }
    }
    return $result;
}
function agm_passenger_restore($id, $version)
{
    $post = get_post($id);
    if (
        !$post ||
        !in_array((int) $id, array_map('intval', agm_passenger_pages()), true) ||
        !current_user_can('edit_post', $id)
    ) {
        return new WP_Error('forbidden', 'Нет доступа к восстановлению.');
    }
    if (!is_string($version) || !hash_equals(hash('sha256', $post->post_content), $version)) {
        return new WP_Error(
            'conflict',
            'Страница уже изменилась. Обновите форму перед восстановлением.',
        );
    }
    $backup = get_post_meta($id, '_agm_passenger_previous', true);
    if (!is_array($backup) || !is_string($backup['content'] ?? null)) {
        return new WP_Error('backup', 'Предыдущей версии пока нет.');
    }
    return agm_passenger_write($id, $post->post_content, $backup['content']);
}
add_action('admin_post_agm_passenger_restore', function () {
    check_admin_referer('agm_passenger_restore');
    $id = absint($_POST['page_id'] ?? 0);
    $result = agm_passenger_restore($id, wp_unslash($_POST['version'] ?? ''));
    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()), '', ['response' => 400]);
    }
    wp_safe_redirect(
        admin_url('admin.php?page=agm-passenger-content&page_id=' . $id . '&saved=yes'),
    );
    exit();
});
