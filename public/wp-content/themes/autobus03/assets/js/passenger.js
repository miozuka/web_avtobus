(() => {
    const search = document.querySelector('[data-sales-search]');
    if (!search) return;
    const wrapper = search.closest('.passenger-search'),
        groups = [...document.querySelectorAll('[data-sales-group]')],
        points = [...document.querySelectorAll('[data-sales-point]')],
        status = wrapper.querySelector('[role="status"]');
    const normalize = (value) =>
        value
            .toLocaleLowerCase('ru')
            .replace(/ё/g, 'е')
            .replace(/[.,]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    const update = () => {
        const terms = normalize(search.value).split(' ').filter(Boolean);
        let count = 0;
        points.forEach((point) => {
            const matches = terms.every((term) => normalize(point.textContent).includes(term));
            point.hidden = !matches;
            if (matches) count++;
        });
        groups.forEach(
            (group) =>
                (group.hidden = ![...group.querySelectorAll('[data-sales-point]')].some(
                    (point) => !point.hidden,
                )),
        );
        status.textContent = count
            ? `Найдено пунктов: ${count}`
            : 'Адрес не найден. Попробуйте другое название улицы.';
    };
    wrapper.classList.add('is-ready');
    search.addEventListener('input', update);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            search.value = '';
            update();
        }
    });
    update();
})();
