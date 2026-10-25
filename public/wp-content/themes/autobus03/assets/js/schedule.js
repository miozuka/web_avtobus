(() => {
    'use strict';
    const root = document.querySelector('.schedule-page');
    if (!root) return;
    const input = root.querySelector('#route-search');
    const filters = [...root.querySelectorAll('.route-filter')];
    const sections = [...root.querySelectorAll('.route-section')];
    const cards = [...root.querySelectorAll('.route-card')];
    const normalize = (text) =>
        text
            .toLocaleLowerCase('ru')
            .replaceAll('ё', 'е')
            .replace(/[aа]/g, 'а')
            .replace(/[kк]/g, 'к')
            .replace(/микрорайон|микр\.?|мкр\./g, 'мкр')
            .replace(/\s+/g, ' ')
            .trim();
    let filter = 'all';
    const update = () => {
        const query = normalize(input.value).replace(/^№\s*/, '');
        const exactNumber = cards.some((card) => normalize(card.dataset.number) === query);
        let visible = 0;
        for (const section of sections) {
            let count = 0;
            for (const card of section.querySelectorAll('.route-card')) {
                const category = filter === 'all' || filter === section.dataset.routeGroup;
                const matches =
                    !query ||
                    (exactNumber
                        ? normalize(card.dataset.number) === query
                        : normalize(`${card.dataset.number} ${card.dataset.search}`).includes(
                              query,
                          ));
                card.hidden = !(category && matches);
                if (!card.hidden) count++;
            }
            section.hidden = count === 0;
            section.querySelector('.route-group-count').textContent = String(count);
            visible += count;
        }
        root.querySelector('.route-empty').hidden = visible !== 0;
        root.querySelector('#route-status').textContent = `Найдено маршрутов: ${visible}`;
    };
    const select = (value) => {
        filter = value;
        for (const button of filters) {
            const active = button.dataset.filter === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        }
        update();
    };
    for (const button of filters)
        button.addEventListener('click', () => select(button.dataset.filter));
    input.addEventListener('input', update);
    root.querySelector('#route-reset').addEventListener('click', () => {
        input.value = '';
        select('all');
        input.focus();
    });
    root.querySelector('.route-tools').hidden = false;
    update();
})();
