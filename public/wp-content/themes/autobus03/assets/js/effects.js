/* Page-specific interface entrances; all content stays available without JavaScript. */
(() => {
    const main = document.querySelector('main');
    if (!main) return;
    const scenes = {
        'services-page': 'services',
        'schedule-page': 'schedule',
        'timetable-page': 'timetable',
        'news-page': 'news',
        'news-detail': 'article',
        'about-page': 'about',
        'vacancy-page': 'vacancy',
        'contact-page': 'contact',
    };
    main.dataset.scene ||=
        Object.entries(scenes).find(([name]) => main.classList.contains(name))?.[1] ||
        (main.querySelector('.hero') ? 'home' : 'document');
    const root = document.documentElement;
    root.dataset.pageScene = main.dataset.scene;
    const reduce = matchMedia('(prefers-reduced-motion: reduce)');
    const disabled = () =>
        reduce.matches || (root.dataset.contrast && root.dataset.contrast !== 'normal');
    if (disabled() || !('IntersectionObserver' in window)) return;

    const scene = main.dataset.scene;
    const stage =
        main
            .querySelector('h1')
            ?.closest(
                '.hero,.services-hero,.route-hero,.news-page-hero,.about-intro,.vacancy-hero,.contact-hero,.passenger-hero,.timetable-header,.news-article-header',
            ) || main.querySelector('h1')?.parentElement;
    const common =
        '.card:not(.timetable-graph):not(.timetable-header),.tariff-card,.enterprise-card,.news-featured,.news-list-card,.news-subscribe,.about-contact-card,.about-document,.about-event,.about-stat,.about-feedback,.vacancy-hh,.vacancy-hr-line,.vacancy-poster-card,.vacancy-job,.vacancy-map-card,.vacancy-benefit,.contact-priority,.contact-panel,.contact-lost,.contact-map-card,.passenger-panel,.passenger-card,.feedback-card,.route-card,.section-heading,.center-heading,.faq-item';
    const individual =
        scene === 'article'
            ? '.news-article-image,.news-article-text > p,.news-article-text > h2,.news-article-text > figure'
            : scene === 'about'
              ? '.about-gallery'
              : scene === 'timetable'
                ? '.timetable-controls,.timetable-hour,.timetable-note'
                : ['privacy', 'terms', 'document'].includes(scene)
                  ? '.entry-content > h2,.entry-content > h3,.entry-content > p'
                  : '';
    const selector = individual ? `${common},${individual}` : common;
    const candidates = [...main.querySelectorAll(selector)];
    const targets = candidates.filter(
        (el) => !el.parentElement.closest(selector) && el !== stage && !el.contains(stage),
    );
    const tracked = new Set();
    const timers = new Set();
    const layers = new Map();
    const headingLabels = new Map();
    const restoreHeadings = () => {
        headingLabels.forEach((label, heading) =>
            label === null
                ? heading.removeAttribute('aria-label')
                : heading.setAttribute('aria-label', label),
        );
        headingLabels.clear();
    };
    let observer, mutation;
    let stopped = false;
    const cleanElement = (el) => {
        el.classList.remove('interface-enter', 'scroll-reveal', 'is-revealed');
        el.style.removeProperty('--motion-delay');
        el.style.removeProperty('--motion-side');
        (layers.get(el) || []).forEach((child) => {
            child.classList.remove('motion-layer', 'motion-media');
            child.style.removeProperty('--layer-delay');
        });
        layers.delete(el);
        tracked.delete(el);
        if (el.classList.contains('motion-word'))
            el.replaceWith(document.createTextNode(el.textContent));
    };
    const later = (fn, delay) => {
        const id = setTimeout(() => {
            timers.delete(id);
            fn();
        }, delay);
        timers.add(id);
    };
    const prepare = (el, index) => {
        tracked.add(el);
        el.style.setProperty(
            '--motion-delay',
            `${(index % 4) * (scene === 'schedule' ? 55 : 85)}ms`,
        );
        el.style.setProperty('--motion-side', `${index % 2 ? -1 : 1}`);
        if (
            !['privacy', 'terms', 'document', 'timetable'].includes(scene) &&
            !el.classList.contains('motion-word')
        ) {
            const children = [
                ...el.querySelectorAll(
                    'h2,h3,p,.button,.vacancy-button,.contact-button,.passenger-button,.text-link,.route-number,.vacancy-salary,img',
                ),
            ]
                .filter(
                    (child) =>
                        !child.closest('[hidden]') &&
                        !child.parentElement.closest('h2,h3,p,.button,.text-link'),
                )
                .slice(0, 7);
            children.forEach((child, step) => {
                child.classList.add(child.tagName === 'IMG' ? 'motion-media' : 'motion-layer');
                child.style.setProperty('--layer-delay', `${Math.min(step, 4) * 65}ms`);
            });
            layers.set(el, children);
        }
    };
    const play = (el, opening = false) => {
        if (stopped) return;
        el.classList.add(opening ? 'interface-enter' : 'is-revealed');
        observer?.unobserve(el);
        later(() => cleanElement(el), 1800);
    };
    const stop = () => {
        stopped = true;
        observer?.disconnect();
        mutation?.disconnect();
        timers.forEach(clearTimeout);
        timers.clear();
        [...tracked].forEach(cleanElement);
        restoreHeadings();
    };
    try {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) play(entry.target);
                });
            },
            { threshold: 0, rootMargin: '0px 0px 12px 0px' },
        );
        if (stage && scrollY < 40) {
            const heading = stage.querySelector('h1');
            if (heading) {
                const kinetic = !['privacy', 'terms', 'document', 'timetable', 'article'].includes(
                    scene,
                );
                if (kinetic) {
                    headingLabels.set(heading, heading.getAttribute('aria-label'));
                    heading.setAttribute('aria-label', heading.textContent.trim());
                    later(restoreHeadings, 1800);
                }
                const walker = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT);
                const nodes = [];
                while (walker.nextNode()) nodes.push(walker.currentNode);
                let index = 0;
                nodes.forEach((node) => {
                    const fragment = document.createDocumentFragment();
                    node.textContent.split(/(\s+)/).forEach((part) => {
                        if (!part || /^\s+$/.test(part)) {
                            fragment.append(document.createTextNode(part));
                            return;
                        }
                        const word = document.createElement('span');
                        word.className = 'motion-word';
                        word.textContent = part;
                        prepare(word, index);
                        word.style.setProperty('--motion-delay', `${Math.min(index++, 12) * 45}ms`);
                        if (kinetic) {
                            word.textContent = '';
                            [...part].forEach((letter, position) => {
                                const glyph = document.createElement('span');
                                glyph.className = 'motion-glyph';
                                glyph.textContent = letter;
                                glyph.setAttribute('aria-hidden', 'true');
                                glyph.style.setProperty(
                                    '--glyph-delay',
                                    `${Math.min(position, 10) * 18}ms`,
                                );
                                word.append(glyph);
                            });
                        }
                        fragment.append(word);
                        play(word, true);
                    });
                    node.replaceWith(fragment);
                });
            }
            const opening = [
                ...stage.querySelectorAll(
                    '.pill,h1,p,.button,.about-button,.vacancy-button,.contact-button,.passenger-button,.timetable-number,.timetable-print',
                ),
            ]
                .filter(
                    (el) =>
                        el.tagName !== 'H1' &&
                        !el.closest('[hidden]') &&
                        el.getBoundingClientRect().top < innerHeight &&
                        el.getClientRects().length,
                )
                .slice(0, 8);
            opening.forEach((el, index) => {
                prepare(el, index);
                play(el, true);
            });
        }
        targets.forEach((el, index) => {
            if (!el.getClientRects().length || el.closest('[hidden]')) return;
            const box = el.getBoundingClientRect();
            // A restored scroll position is kept readable; only a fresh opening animates visible cards.
            if (box.top < innerHeight && box.bottom > 0 && box.left < innerWidth && box.right > 0) {
                if (scrollY < 40 && !el.contains(main.querySelector('h1'))) {
                    prepare(el, index);
                    play(el, true);
                }
                return;
            }
            if (box.bottom < 0) return;
            prepare(el, index);
            el.classList.add('scroll-reveal');
            observer.observe(el);
        });
        document.addEventListener('focusin', (event) => {
            [...tracked]
                .filter((el) => el.contains(event.target))
                .forEach((el) => {
                    observer.unobserve(el);
                    cleanElement(el);
                });
        });
        window.addEventListener('beforeprint', stop, { once: true });
        window.addEventListener('pagehide', stop, { once: true });
        reduce.addEventListener('change', () => {
            if (disabled()) stop();
        });
        mutation = new MutationObserver(() => {
            if (disabled()) stop();
        });
        mutation.observe(root, { attributes: true, attributeFilter: ['data-contrast'] });
    } catch {
        stop();
    }
})();

/* Глубина реагирует на курсор и прокрутку; данные и обычные ссылки не меняются. */
(() => {
    const main = document.querySelector('main');
    const root = document.documentElement;
    const reduce = matchMedia('(prefers-reduced-motion: reduce)');
    const pointer = matchMedia('(hover: hover) and (pointer: fine)');
    const disabled = () =>
        reduce.matches || (root.dataset.contrast && root.dataset.contrast !== 'normal');
    if (!main || disabled() || !('IntersectionObserver' in window)) return;
    const controller = new AbortController();
    const options = { signal: controller.signal };
    const settling = new Set();
    const visible = new Set();
    const cards = [];
    const photos = [];
    let frame = 0;
    let stopped = false;
    const clamp = (value, limit = 1) => Math.max(-limit, Math.min(limit, value));
    const listen = (target, event, handler, extra = {}) =>
        target.addEventListener(event, handler, { ...options, ...extra });
    const request = () => {
        if (!frame && !stopped && !document.hidden) frame = requestAnimationFrame(update);
    };
    const resetCard = (state) => {
        state.tx = state.ty = 0;
        state.element.classList.remove('depth-hover');
        state.box = null;
        settling.add(state);
        request();
    };
    function update() {
        frame = 0;
        if (stopped || document.hidden) return;
        let moving = false;
        settling.forEach((state) => {
            state.x += (state.tx - state.x) * 0.16;
            state.y += (state.ty - state.y) * 0.16;
            const resting = Math.abs(state.tx - state.x) + Math.abs(state.ty - state.y) < 0.015;
            if (resting) {
                state.x = state.tx;
                state.y = state.ty;
                settling.delete(state);
            } else moving = true;
            state.element.style.setProperty('--depth-rx', `${state.x.toFixed(3)}deg`);
            state.element.style.setProperty('--depth-ry', `${state.y.toFixed(3)}deg`);
        });
        visible.forEach((state) => {
            if (state.image.closest('[inert],[aria-hidden="true"]')) return;
            const box = state.surface.getBoundingClientRect();
            const distance = clamp(
                (innerHeight / 2 - box.top - box.height / 2) / ((innerHeight + box.height) / 2),
            );
            const amplitude = Math.min(pointer.matches ? 15 : 8, box.height * 0.022);
            const x = pointer.matches ? state.cx * 9 : 0;
            const y = distance * amplitude + (pointer.matches ? state.cy * 2 : 0);
            state.x += (x - state.x) * 0.12;
            state.y += (y - state.y) * 0.12;
            if (Math.abs(x - state.x) + Math.abs(y - state.y) > 0.08) moving = true;
            else {
                state.x = x;
                state.y = y;
            }
            state.image.style.setProperty('--photo-x', `${state.x.toFixed(2)}px`);
            state.image.style.setProperty('--photo-y', `${state.y.toFixed(2)}px`);
        });
        if (moving) request();
    }
    const surfaces = main.querySelectorAll(
        '.fleet-card,.news-card,.news-list-card,.tariff-card,.enterprise-card,.vacancy-benefit,.contact-priority,.about-stat,.info-card,.service-card',
    );
    surfaces.forEach((element) => {
        const state = { element, x: 0, y: 0, tx: 0, ty: 0, box: null };
        element.classList.add('depth-card');
        cards.push(state);
        listen(
            element,
            'pointermove',
            (event) => {
                if (
                    !pointer.matches ||
                    event.pointerType !== 'mouse' ||
                    element.matches(':focus-within') ||
                    document.querySelector('dialog[open]')
                )
                    return;
                const box = (state.box ||= element.getBoundingClientRect());
                const strength = element.closest('.news-strip') ? 1.4 : 3.2;
                state.tx = -clamp(((event.clientY - box.top) / box.height) * 2 - 1) * strength;
                state.ty = clamp(((event.clientX - box.left) / box.width) * 2 - 1) * strength;
                element.classList.add('depth-hover');
                settling.add(state);
                request();
            },
            { passive: true },
        );
        listen(element, 'pointerleave', () => resetCard(state));
        listen(element, 'pointercancel', () => resetCard(state));
        listen(element, 'focusin', () => resetCard(state));
    });
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                const state = photos.find((photo) => photo.image === entry.target);
                if (!state) return;
                if (entry.isIntersecting) visible.add(state);
                else visible.delete(state);
            });
            request();
        },
        { rootMargin: '60px' },
    );
    main.querySelectorAll('.hero-image,.schedule-photo img,.about-slide img').forEach((image) => {
        const surface = image.closest('.hero,.schedule-photo,.about-gallery');
        if (!surface) return;
        const state = { image, surface, x: 0, y: 0, cx: 0, cy: 0 };
        photos.push(state);
        image.classList.add('depth-photo');
        observer.observe(image);
        if (image.classList.contains('hero-image')) {
            surface.classList.add('depth-hero');
            listen(
                surface,
                'pointermove',
                (event) => {
                    if (!pointer.matches || event.pointerType !== 'mouse') return;
                    const box = surface.getBoundingClientRect();
                    state.cx = clamp(((event.clientX - box.left) / box.width) * 2 - 1);
                    state.cy = clamp(((event.clientY - box.top) / box.height) * 2 - 1);
                    request();
                },
                { passive: true },
            );
            listen(surface, 'pointerleave', () => {
                state.cx = state.cy = 0;
                request();
            });
        }
    });
    listen(
        document,
        'scroll',
        () => {
            cards.forEach((state) => {
                if (state.element.classList.contains('depth-hover')) resetCard(state);
            });
            request();
        },
        { passive: true, capture: true },
    );
    listen(
        window,
        'resize',
        () => {
            cards.forEach(resetCard);
            request();
        },
        { passive: true },
    );
    listen(document, 'visibilitychange', () => {
        if (document.hidden) {
            cancelAnimationFrame(frame);
            frame = 0;
        } else {
            cards.forEach(resetCard);
            request();
        }
    });
    listen(pointer, 'change', () => {
        cards.forEach(resetCard);
        request();
    });
    listen(document, 'click', request); // При смене фотографии галереи пересчитываем её положение.
    const stop = () => {
        if (stopped) return;
        stopped = true;
        cancelAnimationFrame(frame);
        observer.disconnect();
        mutation.disconnect();
        controller.abort();
        cards.forEach(({ element }) => {
            element.classList.remove('depth-card', 'depth-hover');
            element.style.removeProperty('--depth-rx');
            element.style.removeProperty('--depth-ry');
        });
        photos.forEach(({ image, surface }) => {
            image.classList.remove('depth-photo');
            surface.classList.remove('depth-hero');
            image.style.removeProperty('--photo-x');
            image.style.removeProperty('--photo-y');
        });
        settling.clear();
        visible.clear();
    };
    const mutation = new MutationObserver(() => {
        if (disabled()) stop();
    });
    mutation.observe(root, { attributes: true, attributeFilter: ['data-contrast'] });
    listen(reduce, 'change', () => {
        if (disabled()) stop();
    });
    listen(window, 'beforeprint', stop);
    listen(window, 'pagehide', stop);
})();
