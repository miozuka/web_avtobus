(() => {
    'use strict';
    const make = (tag, text, cls) => {
        const n = document.createElement(tag);
        if (text) n.textContent = text;
        if (cls) n.className = cls;
        return n;
    };
    const normal = (s) => s.toLocaleLowerCase('ru').replaceAll('ё', 'е').trim();
    const search = document.querySelector('#agm-task-search');
    if (search) {
        const cards = [...document.querySelectorAll('[data-task-card]')],
            result = document.querySelector('[data-task-result]');
        search.addEventListener('input', () => {
            const q = normal(search.value);
            for (const card of cards) card.hidden = !normal(card.textContent).includes(q);
            const count = cards.filter((c) => !c.hidden).length;
            result.textContent = q
                ? count
                    ? 'Найдено задач: ' + count
                    : 'Ничего не найдено. Попробуйте другое слово.'
                : '';
        });
    }
    document.querySelector('[data-workspace-context]')?.addEventListener('change', (event) => {
        const url = new URL('admin.php', location.href);
        url.searchParams.set('page', 'agm-editor-guide');
        if (event.target.value) url.searchParams.set('for', event.target.value);
        location.href = url.href;
    });
    const settingsSearch = document.querySelector('#agm-setting-search');
    if (settingsSearch)
        settingsSearch.addEventListener('input', () => {
            for (const item of document.querySelectorAll('.agm-settings-nav li'))
                item.hidden = !normal(item.dataset.agmSearch || item.textContent).includes(normal(settingsSearch.value));
        });
    for (const input of document.querySelectorAll('[data-link-input]'))
        input.addEventListener('change', () => {
            if (input.value.includes('<iframe')) {
                const doc = new DOMParser().parseFromString(input.value, 'text/html'),
                    src = doc.querySelector('iframe')?.getAttribute('src');
                if (src) input.value = src;
            }
        });
    for (const field of document.querySelectorAll('.agm-media-field')) {
        const input = field.querySelector('input'),
            preview = field.querySelector('.agm-media-preview');
        field.querySelector('[data-media-choose]').addEventListener('click', () => {
            if (!window.wp?.media) return;
            const picker = wp.media({
                title: 'Выберите файл для сайта',
                button: { text: 'Использовать файл' },
                multiple: false,
                library: {
                    type:
                        field.dataset.mediaType === 'document'
                            ? ['application/pdf', 'image']
                            : field.dataset.mediaType,
                },
            });
            picker.on('open', () => picker.content.mode('browse'));
            picker.on('select', () => {
                const file = picker.state().get('selection').first().toJSON();
                input.value = file.id;
                preview.replaceChildren();
                if (file.type === 'image') {
                    const img = make('img');
                    img.src = file.sizes?.thumbnail?.url || file.url;
                    img.alt = file.alt || '';
                    preview.append(img);
                } else preview.textContent = file.title;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            picker.open();
        });
        field.querySelector('[data-media-clear]').addEventListener('click', () => {
            input.value = '0';
            preview.replaceChildren();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
    const passenger = document.querySelector('[data-passenger-form]');
    if (passenger) {
        const json = passenger.querySelector('[data-passenger-json]'),
            container = passenger.querySelector('[data-passenger-repeaters]');
        let data;
        try {
            data = JSON.parse(json.value);
            if (!Array.isArray(data.cards) || !Array.isArray(data.sales)) throw Error();
        } catch {
            container.append(
                make(
                    'p',
                    'Форма карточек не загрузилась. Обновите страницу; не сохраняйте неполную форму.',
                ),
            );
            passenger.querySelector('[type=submit]').disabled = true;
            return;
        }
        const sync = () => {
            json.value = JSON.stringify(data);
            json.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const field = (parent, label, obj, key, options = {}) => {
            const wrap = make('label', label, 'agm-repeater-field'),
                input = make(options.long ? 'textarea' : 'input');
            if (!options.long) input.type = 'text';
            else input.rows = 3;
            input.value = obj[key] || '';
            input.dataset.repeaterKey = key;
            if (options.required) input.required = true;
            input.addEventListener('input', () => {
                obj[key] = input.value;
                const summary = parent.querySelector(':scope > summary');
                if (summary && ['title', 'address'].includes(key))
                    summary.textContent = input.value || 'Новая карточка';
                sync();
            });
            wrap.append(input);
            parent.append(wrap);
            return input;
        };
        const action = (label, callback, cls = 'button') => {
            const b = make('button', label, cls);
            b.type = 'button';
            b.addEventListener('click', callback);
            return b;
        };
        const controls = (parent, rows, index, label) => {
            const bar = make('div', '', 'agm-repeater-actions');
            const up = action('Выше', () => {
                [rows[index - 1], rows[index]] = [rows[index], rows[index - 1]];
                sync();
                render();
            });
            up.disabled = index === 0;
            const down = action('Ниже', () => {
                [rows[index + 1], rows[index]] = [rows[index], rows[index + 1]];
                sync();
                render();
            });
            down.disabled = index === rows.length - 1;
            bar.append(
                up,
                down,
                action(
                    'Убрать',
                    () => {
                        if (
                            !window.confirm(
                                'Убрать «' +
                                    label +
                                    '»? Изменение применится после сохранения страницы.',
                            )
                        )
                            return;
                        rows.splice(index, 1);
                        sync();
                        render();
                    },
                    'button-link-delete',
                ),
            );
            parent.append(bar);
        };
        function render() {
            container.replaceChildren();
            data.cards.forEach((group, gi) => {
                const area = make('details', '', 'agm-admin-panel');
                area.open = true;
                area.append(make('summary', group.title + ' · ' + group.rows.length));
                if (group.documents)
                    field(area, 'Подпись списка документов', group, 'documents', {
                        required: true,
                    });
                group.rows.forEach((row, ri) => {
                    const box = make('details', '', 'agm-repeater-row');
                    box.open = !row.title;
                    box.dataset.cardGroup = gi;
                    box.dataset.cardIndex = ri;
                    box.append(make('summary', row.title || 'Новая карточка'));
                    field(box, 'Название карточки', row, 'title', { required: true });
                    field(box, 'Метка над заголовком (необязательно)', row, 'badge');
                    field(box, 'Стоимость вместе с валютой (необязательно)', row, 'price');
                    field(
                        box,
                        group.documents ? 'Документы и пояснение' : 'Описание',
                        row,
                        'text',
                        { long: true },
                    );
                    const links = make('details', '', 'agm-repeater-extra');
                    links.append(make('summary', 'Кнопка карточки (необязательно)'));
                    field(links, 'Надпись на кнопке', row, 'link_text');
                    field(links, 'Ссылка кнопки', row, 'link_url');
                    box.append(links);
                    controls(box, group.rows, ri, row.title || 'карточку');
                    area.append(box);
                });
                area.append(
                    action('Добавить карточку', () => {
                        group.rows.push({
                            title: '',
                            badge: '',
                            price: '',
                            text: '',
                            link_text: '',
                            link_url: '',
                        });
                        sync();
                        render();
                    }),
                );
                container.append(area);
            });
            if (passenger.dataset.salesAvailable === '1') {
                container.append(make('h2', 'Пункты продажи'));
                data.sales.forEach((group, gi) => {
                    const area = make('details', '', 'agm-admin-panel');
                    area.open = true;
                    area.dataset.salesGroup = gi;
                    area.append(make('summary', group.title + ' · ' + group.points.length));
                    field(area, 'Название группы', group, 'title', { required: true });
                    group.points.forEach((point, pi) => {
                        const box = make('details', '', 'agm-repeater-row');
                        box.open = !point.address;
                        box.dataset.salesPoint = pi;
                        box.append(make('summary', point.address || 'Новый пункт продажи'));
                        field(box, 'Адрес', point, 'address', { required: true });
                        field(box, 'Остановка или пояснение', point, 'note');
                        const extra = make('details', '', 'agm-repeater-extra');
                        extra.append(make('summary', 'Ссылка на карту'));
                        field(extra, 'Ссылка (пусто — найти адрес в Яндекс Картах)', point, 'url');
                        field(extra, 'Текст ссылки', point, 'link_text');
                        extra.append(
                            make(
                                'p',
                                'Если меняете адрес, проверьте старую ссылку или очистите её: сайт построит новую.',
                                'description',
                            ),
                        );
                        box.append(extra);
                        controls(box, group.points, pi, point.address || 'пункт');
                        area.append(box);
                    });
                    area.append(
                        action('Добавить пункт продажи', () => {
                            group.points.push({
                                address: '',
                                note: '',
                                url: '',
                                link_text: 'На карте',
                            });
                            sync();
                            render();
                        }),
                    );
                    controls(area, data.sales, gi, group.title || 'группу');
                    container.append(area);
                });
                container.append(
                    action('Добавить группу пунктов', () => {
                        data.sales.push({ title: 'Новая группа', points: [] });
                        sync();
                        render();
                    }),
                );
            }
        }
        render();
    }
    const forms = [...document.querySelectorAll('[data-unsaved-form]')];
    const nativeForm = document.querySelector('form#post');
    if (
        nativeForm &&
        (document.body.classList.contains('agm-simple-card-page') ||
            document.querySelector('#agm-direction-list,.agm-job-editor'))
    )
        forms.push(nativeForm);
    const featured = document.querySelector('#agm_featured');
    if (featured && document.querySelector('#post_type')?.value === 'agm_contact') {
        const update = () => {
            for (const key of ['label', 'card_title', 'badge', 'icon']) {
                const element = document.querySelector('#agm_' + key);
                const row = element?.closest('.agm-card-field') || element?.closest('p');
                if (row) row.hidden = !featured.checked;
            }
        };
        featured.addEventListener('change', update);
        update();
    }

    for (const form of forms) {
        form.addEventListener('input', () => {
            form.dataset.dirty = '1';
            const status = form.querySelector('[data-save-status]');
            if (status) status.textContent = 'Есть несохранённые изменения';
        });
        form.addEventListener('change', () => {
            form.dataset.dirty = '1';
            const status = form.querySelector('[data-save-status]');
            if (status) status.textContent = 'Есть несохранённые изменения';
        });
        form.addEventListener('submit', (event) => {
            queueMicrotask(() => {
                form.dataset.submitted = event.defaultPrevented ? '0' : '1';
            });
        });
        form.addEventListener(
            'invalid',
            (event) => {
                let node = event.target.parentElement;
                while (node && node !== form) {
                    if (node.tagName === 'DETAILS') node.open = true;
                    node = node.parentElement;
                }
            },
            true,
        );
    }
    window.addEventListener('beforeunload', (event) => {
        if (forms.some((form) => form.dataset.dirty === '1' && form.dataset.submitted !== '1')) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();
