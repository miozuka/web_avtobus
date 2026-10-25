(() => {
    'use strict';
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    document.querySelectorAll('[data-about-gallery]').forEach((gallery) => {
        const slides = [...gallery.querySelectorAll('[data-slide]')];
        const controls = gallery.querySelector('.about-gallery-controls');
        const dots = [...gallery.querySelectorAll('[data-slide-dot]')];

        const progress = gallery.querySelector('.about-gallery-progress span');
        let active = 0,
            timer,
            motionPaused = reduced.matches,
            focused = false,
            inView = false;
        if (!slides.length) return;
        function schedule() {
            clearTimeout(timer);
            const running =
                slides.length > 1 && !motionPaused && !focused && inView && !document.hidden;
            gallery.dataset.playing = String(running);
            if (running) {
                progress.style.animation = 'none';
                void progress.offsetWidth;
                progress.style.animation = '';
                timer = setTimeout(() => show(active + 1), 10000);
            }
        }
        function show(index) {
            active = (index + slides.length) % slides.length;
            const nextImage = slides[(active + 1) % slides.length].querySelector('img');
            if (nextImage) nextImage.loading = 'eager';
            slides.forEach((slide, i) => {
                slide.classList.toggle('is-active', i === active);
                slide.setAttribute('aria-hidden', String(i !== active));
                slide.inert = i !== active;
            });
            dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === active)));
            gallery.querySelector('[data-slide-count]').textContent =
                `${active + 1} / ${slides.length}`;
            schedule();
        }
        gallery.dataset.ready = 'true';
        show(0);
        if (slides.length > 1) {
            controls.hidden = false;
            gallery.querySelector('[data-slide-count]').hidden = false;
        }
        dots.forEach((dot) =>
            dot.addEventListener('click', () => show(Number(dot.dataset.slideDot))),
        );
        gallery.addEventListener('focusin', () => {
            focused = true;
            schedule();
        });
        gallery.addEventListener('focusout', (event) => {
            if (!gallery.contains(event.relatedTarget)) {
                focused = false;
                schedule();
            }
        });
        gallery.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                show(active - 1);
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                show(active + 1);
            }
        });
        document.addEventListener('visibilitychange', schedule);
        reduced.addEventListener('change', () => {
            motionPaused = reduced.matches;
            schedule();
        });
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(
                (entries) => {
                    inView = entries[0].isIntersecting;
                    schedule();
                },
                { threshold: 0.15 },
            ).observe(gallery);
        } else {
            inView = true;
            schedule();
        }
    });
})();
