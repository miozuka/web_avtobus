(() => {
    'use strict';
    const root = document.documentElement;
    const body = document.body;
    const toggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.primary-navigation');
    const panel = document.querySelector('#access-panel');
    const accessToggle = document.querySelector('.access-toggle');
    body.classList.add('js');
    toggle.hidden = false;
    accessToggle.hidden = false;
    const mobile = window.matchMedia('(max-width: 1199px)');
    const header = document.querySelector('.site-header');
    const alignMenu = () => {
        if (header)
            nav.style.setProperty(
                '--menu-top',
                `${Math.max(0, header.getBoundingClientRect().bottom)}px`,
            );
    };
    window.addEventListener('resize', alignMenu, { passive: true });
    if ('ResizeObserver' in window && header) new ResizeObserver(alignMenu).observe(header);
    const setMenu = (open, restoreFocus = false) => {
        if (open) alignMenu();
        toggle.setAttribute('aria-expanded', String(open));
        toggle.querySelector('.screen-reader-text').textContent = open
            ? 'Закрыть меню'
            : 'Открыть меню';
        nav.classList.toggle('is-open', open);
        body.classList.toggle('menu-open', open);
        for (const element of [
            document.querySelector('main'),
            document.querySelector('footer'),
            panel,
        ]) {
            if (element) element.inert = open;
        }
        if (open) nav.querySelector('a')?.focus();
        if (restoreFocus) toggle.focus();
    };
    toggle.addEventListener('click', () =>
        setMenu(toggle.getAttribute('aria-expanded') !== 'true'),
    );
    nav.addEventListener('click', (event) => {
        if (event.target.closest('a')) setMenu(false);
    });
    mobile.addEventListener('change', () => setMenu(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (body.classList.contains('menu-open')) setMenu(false, true);
            if (!panel.hidden) {
                panel.hidden = true;
                accessToggle.setAttribute('aria-expanded', 'false');
                accessToggle.focus();
            }
        }
        if (event.key === 'Tab' && body.classList.contains('menu-open')) {
            const focusable = [toggle, ...nav.querySelectorAll('a, button')].filter(
                (element) => element.getClientRects().length,
            );
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    const defaults = { size: 'normal', contrast: 'normal', images: false, spacing: false };
    let settings = { ...defaults };
    try {
        settings = { ...defaults, ...JSON.parse(localStorage.getItem('autobus-access') || '{}') };
    } catch {
        /* Storage may be unavailable. */
    }
    const controls = {
        size: document.querySelector('#access-size'),
        contrast: document.querySelector('#access-contrast'),
        images: document.querySelector('#access-images'),
        spacing: document.querySelector('#access-spacing'),
    };
    const applySettings = () => {
        if (!['normal', 'large', 'extra'].includes(settings.size)) settings.size = 'normal';
        if (!['normal', 'light', 'dark', 'yellow'].includes(settings.contrast))
            settings.contrast = 'normal';
        root.dataset.textSize = settings.size;
        root.dataset.contrast = settings.contrast;
        root.dataset.hideImages = String(Boolean(settings.images));
        root.dataset.wideSpacing = String(Boolean(settings.spacing));
        controls.size.value = settings.size;
        controls.contrast.value = settings.contrast;
        controls.images.checked = Boolean(settings.images);
        controls.spacing.checked = Boolean(settings.spacing);
        try {
            localStorage.setItem('autobus-access', JSON.stringify(settings));
        } catch {
            /* Settings still apply for this visit. */
        }
    };
    applySettings();
    for (const [key, control] of Object.entries(controls)) {
        control.addEventListener('change', () => {
            settings[key] = control.type === 'checkbox' ? control.checked : control.value;
            applySettings();
        });
    }
    accessToggle.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        accessToggle.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) controls.size.focus();
    });
    document.querySelector('#access-reset').addEventListener('click', () => {
        settings = { ...defaults };
        applySettings();
    });
    document.querySelector('#access-close').addEventListener('click', () => {
        panel.hidden = true;
        accessToggle.setAttribute('aria-expanded', 'false');
        accessToggle.focus();
    });
})();

(() => {
    const button = document.querySelector('.back-to-top');
    if (!button) return;
    let scheduled = false;
    const update = () => {
        button.hidden = window.scrollY < 400;
        scheduled = false;
    };
    window.addEventListener(
        'scroll',
        () => {
            if (!scheduled) {
                scheduled = true;
                window.requestAnimationFrame(update);
            }
        },
        { passive: true },
    );
    window.addEventListener('pageshow', update);
    button.addEventListener('click', () => {
        const heading = document.querySelector('main h1') || document.querySelector('main');
        if (heading) {
            const hadTabindex = heading.hasAttribute('tabindex');
            if (!hadTabindex) {
                heading.setAttribute('tabindex', '-1');
                heading.addEventListener('blur', () => heading.removeAttribute('tabindex'), {
                    once: true,
                });
            }
            heading.focus({ preventScroll: true });
        }
        window.scrollTo({
            top: 0,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                ? 'instant'
                : 'smooth',
        });
    });
    update();
})();
