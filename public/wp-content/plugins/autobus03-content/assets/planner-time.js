(() => {
    'use strict';
    document.querySelectorAll('[data-planner-time-preview]').forEach((preview) => {
        const day = document.getElementById(preview.dataset.dayInput);
        const clock = document.getElementById(preview.dataset.clockInput);
        if (!day || !clock) return;
        function update() {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(day.value) || !/^\d{2}:\d{2}$/.test(clock.value)) {
                preview.textContent = 'Выберите день и время задания по времени Улан-Удэ.';
                return;
            }
            // Ввод уже задан в часовом поясе сайта; часы компьютера его не изменяют.
            const [year, month, date] = day.value.split('-');
            const main = document.createElement('span');
            main.className = 'agm-planner-main-time';
            main.textContent = `${preview.dataset.siteLabel}: ${date}.${month}.${year} ${clock.value}`;
            preview.replaceChildren(main);
        }
        day.addEventListener('input', update);
        day.addEventListener('change', update);
        clock.addEventListener('input', update);
        clock.addEventListener('change', update);
        update();
    });
})();
