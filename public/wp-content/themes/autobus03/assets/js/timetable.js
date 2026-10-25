(() => {
    'use strict';
    const root = document.querySelector('.timetable-page');
    if (!root) return;
    const directions = [...root.querySelectorAll('.timetable-direction')];
    const directionSelect = root.querySelector('.timetable-direction-select');
    const days = [...root.querySelectorAll('.timetable-day')];
    const panels = [...root.querySelectorAll('.timetable-panel')];
    let direction = directions[0]?.dataset.direction;
    let day = 'weekday';
    const update = () => {
        const available = panels
            .filter((panel) => panel.dataset.direction === direction)
            .map((panel) => panel.dataset.day);
        if (!available.includes(day)) day = available[0];
        for (const button of directions) {
            const active = button.dataset.direction === direction;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        }
        for (const button of days) {
            const active = button.dataset.day === day;
            button.hidden = !available.includes(button.dataset.day);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        }
        for (const panel of panels)
            panel.hidden = panel.dataset.direction !== direction || panel.dataset.day !== day;
        if (directionSelect) directionSelect.value = direction;
        const label = days.find((button) => button.dataset.day === day)?.textContent.trim() || '';
        const activePanel = panels.find(
            (panel) => panel.dataset.direction === direction && panel.dataset.day === day,
        );
        const count = activePanel?.querySelectorAll('time').length || 0;
        const lastTwo = count % 100;
        const last = count % 10;
        const noun =
            lastTwo >= 11 && lastTwo <= 14
                ? 'отправлений'
                : last === 1
                  ? 'отправление'
                  : last >= 2 && last <= 4
                    ? 'отправления'
                    : 'отправлений';
        root.querySelector('.timetable-status').textContent = `${label} · ${count} ${noun}`;
    };
    for (const button of directions)
        button.addEventListener('click', () => {
            direction = button.dataset.direction;
            update();
        });
    for (const button of days)
        button.addEventListener('click', () => {
            day = button.dataset.day;
            update();
        });
    directionSelect?.addEventListener('change', () => {
        direction = directionSelect.value;
        update();
    });
    const print = root.querySelector('.timetable-print');
    print.hidden = false;
    print.addEventListener('click', () => window.print());
    root.classList.add('timetable-interactive');
    root.querySelector('.timetable-controls').hidden = false;
    update();
})();
