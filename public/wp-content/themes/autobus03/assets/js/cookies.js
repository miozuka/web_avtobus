(() => {
    'use strict';
    const node = document.querySelector('#autobus-cookie-config'),
        notice = document.querySelector('.cookie-notice'),
        dialog = document.querySelector('.cookie-settings');
    if (!node || !notice || !dialog) return;
    const config = JSON.parse(node.textContent),
        name = 'autobus_cookie_choice',
        lifetime = config.days * 86400000;
    config.ym = /^[1-9][0-9]{0,14}$/.test(config.ym) ? config.ym : '';
    config.ga = /^G-[A-Z0-9]{4,20}$/.test(config.ga) ? config.ga : '';
    const loaded = { ym: false, ga: false };
    let current = null;
    const read = () => {
        try {
            const raw = document.cookie.split('; ').find((c) => c.startsWith(name + '='));
            if (!raw) return null;
            const value = JSON.parse(decodeURIComponent(raw.slice(name.length + 1)));
            if (
                value.v !== 1 ||
                value.scope !== config.scope ||
                typeof value.ym !== 'boolean' ||
                typeof value.ga !== 'boolean' ||
                !Number.isFinite(value.time) ||
                Date.now() - value.time >= lifetime ||
                value.time > Date.now() + 60000
            )
                return null;
            return value;
        } catch {
            return null;
        }
    };
    const write = (value) => {
        document.cookie =
            name +
            '=' +
            encodeURIComponent(JSON.stringify(value)) +
            '; Path=/; Max-Age=' +
            config.days * 86400 +
            '; SameSite=Lax' +
            (location.protocol === 'https:' ? '; Secure' : '');
    };
    const showNotice = (show) => {
        notice.hidden = !show;
        document.body.classList.toggle('cookie-notice-visible', show);
    };

    const addScript = (id, src) => {
        if (document.getElementById(id)) return;
        const script = document.createElement('script');
        script.id = id;
        script.async = true;
        script.src = src;
        document.head.append(script);
    };
    const start = (value) => {
        if (!config.trackAllowed || !value) return;
        if (config.ym && value.ym && !loaded.ym) {
            loaded.ym = true;
            window.ym =
                window.ym ||
                function () {
                    (window.ym.a = window.ym.a || []).push(arguments);
                };
            window.ym.l = Date.now();
            window.ym(Number(config.ym), 'init', {
                clickmap: false,
                trackLinks: false,
                accurateTrackBounce: true,
                webvisor: false,
            });
            addScript('autobus-ym-tag', 'https://mc.yandex.ru/metrika/tag.js');
        }
        if (config.ga && value.ga && !loaded.ga) {
            loaded.ga = true;
            window['ga-disable-' + config.ga] = false;
            window.dataLayer = window.dataLayer || [];
            window.gtag =
                window.gtag ||
                function () {
                    window.dataLayer.push(arguments);
                };
            window.gtag('consent', 'default', {
                analytics_storage: 'granted',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
            });
            window.gtag('js', new Date());
            window.gtag('config', config.ga, {
                allow_google_signals: false,
                allow_ad_personalization_signals: false,
            });
            addScript(
                'autobus-ga-tag',
                'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.ga),
            );
        }
    };
    const clearAnalyticsCookies = () => {
        const names = [
            '_ga',
            '_ga_' + config.ga.slice(2),
            '_ym_uid',
            '_ym_d',
            '_ym_isad',
            '_ym_visorc',
        ];
        const host = location.hostname.split('.');
        const domains = ['', ...host.map((_, i) => host.slice(i).join('.'))];
        for (const key of names)
            for (const domain of domains)
                document.cookie =
                    key +
                    '=; Max-Age=0; Path=/' +
                    (domain ? '; Domain=' + domain : '') +
                    '; SameSite=Lax';
    };
    const revoke = (value) => {
        const revoked = (loaded.ym && !value?.ym) || (loaded.ga && !value?.ga);
        if (!revoked) return false;
        if (loaded.ga && !value?.ga) window['ga-disable-' + config.ga] = true;
        if (loaded.ym && !value?.ym && window.ym) window.ym(Number(config.ym), 'destruct');
        clearAnalyticsCookies();
        return true;
    };
    const save = (ym, ga) => {
        const value = {
            v: 1,
            scope: config.scope,
            time: Date.now(),
            ym: !!config.ym && ym,
            ga: !!config.ga && ga,
        };
        write(value);
        const reload = revoke(value);
        current = value;
        showNotice(false);
        if (dialog.open) dialog.close();
        if (reload) {
            location.reload();
            return;
        }
        start(value);
    };
    const open = () => {
        current = read() || current;
        for (const provider of ['ym', 'ga']) {
            const input = dialog.querySelector('[data-cookie-choice="' + provider + '"]');
            input.disabled = !config[provider];
            input.checked = !!config[provider] && !!current?.[provider];
            dialog.querySelector('[data-cookie-status="' + provider + '"]').textContent = config[
                provider
            ]
                ? 'По вашему выбору'
                : 'Сейчас не подключена';
        }
        if (!dialog.open) dialog.showModal();
    };
    document.querySelectorAll('[data-cookie-open]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', open);
    });
    document
        .querySelectorAll('[data-cookie-accept]')
        .forEach((button) => button.addEventListener('click', () => save(true, true)));
    document
        .querySelectorAll('[data-cookie-reject]')
        .forEach((button) => button.addEventListener('click', () => save(false, false)));
    dialog
        .querySelector('[data-cookie-save]')
        .addEventListener('click', () =>
            save(
                dialog.querySelector('[data-cookie-choice="ym"]').checked,
                dialog.querySelector('[data-cookie-choice="ga"]').checked,
            ),
        );
    dialog.querySelector('[data-cookie-close]').addEventListener('click', () => dialog.close());
    window.addEventListener('focus', () => {
        const value = read();
        if (revoke(value)) {
            location.reload();
            return;
        }
        current = value;
        showNotice(!value);
        start(value);
    });
    current = read();
    showNotice(!current);
    start(current);
})();
