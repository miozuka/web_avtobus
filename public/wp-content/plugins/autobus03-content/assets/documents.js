(() => {
    'use strict';
    document.addEventListener('click', (event) => {
        const choose = event.target.closest('.agm-document-choose'),
            clear = event.target.closest('.agm-document-clear');
        const button = choose || clear;
        if (!button) return;
        const input = document.getElementById(button.dataset.input),
            field = button.closest('.agm-document-field');
        const update = (file) => {
            input.value = file?.id || 0;
            if (field) {
                field.querySelector('[data-document-title]').textContent =
                    file?.title || 'PDF пока не выбран';
                const link = field.querySelector('[data-document-link]');
                link.hidden = !file;
                link.href = file?.url || '';
            }
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };
        if (clear) {
            update(null);
            return;
        }
        const picker = wp.media({
            title: 'Выбрать PDF-документ',
            button: { text: 'Использовать документ' },
            library: { type: 'application/pdf' },
            multiple: false,
        });
        picker.on('open', () => picker.content.mode('browse'));
        picker.on('select', () => update(picker.state().get('selection').first().toJSON()));
        picker.open();
    });
})();
