(() => {
    'use strict';
    const button = document.querySelector('[data-news-share]');
    if (!button) return;
    const status = document.querySelector('.news-share-status');
    const fallback = document.querySelector('.news-share-fallback');
    const input = document.querySelector('#news-share-url');
    button.hidden = false;
    button.addEventListener('click', async () => {
        const url = input.value;
        status.textContent = '';
        if (navigator.share) {
            try {
                await navigator.share({ title: document.querySelector('h1').textContent, url });
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            }
        }
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(url);
                status.textContent = 'Ссылка скопирована';
                return;
            } catch {
                /* Let the reader copy the link manually. */
            }
        }
        fallback.hidden = false;
        input.focus();
        input.select();
        status.textContent = 'Скопируйте ссылку, чтобы поделиться новостью';
    });
})();
