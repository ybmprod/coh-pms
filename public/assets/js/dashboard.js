document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const root = document.documentElement;

    root.classList.add('dash-js-ready');

    function startLiveClocks() {
        const clocks = Array.from(document.querySelectorAll('[data-live-clock]'));
        if (clocks.length === 0) {
            return;
        }

        const formatters = new Map();
        const formatterFor = (timeZone) => {
            if (!formatters.has(timeZone)) {
                let formatter;
                try {
                    formatter = new Intl.DateTimeFormat('en-GB', {
                        timeZone,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hourCycle: 'h23'
                    });
                } catch (error) {
                    formatter = new Intl.DateTimeFormat('en-GB', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hourCycle: 'h23'
                    });
                }
                formatters.set(timeZone, formatter);
            }

            return formatters.get(timeZone);
        };

        const tick = () => {
            const now = new Date();
            clocks.forEach((clock) => {
                const timeZone = clock.getAttribute('data-live-clock') || 'Africa/Harare';
                clock.textContent = formatterFor(timeZone).format(now);
            });
        };

        tick();
        window.setInterval(tick, 1000);
    }

    function startCounters() {
        const counters = Array.from(document.querySelectorAll('.js-count'));
        if (counters.length === 0) {
            return;
        }

        if (reduceMotion || typeof window.requestAnimationFrame !== 'function') {
            return;
        }

        counters.forEach((counter) => {
            const target = Number.parseFloat(counter.getAttribute('data-count') || '0');
            if (!Number.isFinite(target) || target === 0) {
                return;
            }

            const decimals = Math.max(0, Math.min(2, Number.parseInt(counter.getAttribute('data-decimals') || '0', 10) || 0));
            const prefix = counter.getAttribute('data-prefix') || '';
            const formatter = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
            const duration = 900;
            const start = window.performance.now();

            const frame = (timestamp) => {
                const progress = Math.min(1, (timestamp - start) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                counter.textContent = prefix + formatter.format(target * eased);
                if (progress < 1) {
                    window.requestAnimationFrame(frame);
                } else {
                    counter.textContent = prefix + formatter.format(target);
                }
            };

            counter.textContent = prefix + formatter.format(0);
            window.requestAnimationFrame(frame);
        });
    }

    function startReveals() {
        const reveals = Array.from(document.querySelectorAll('.dashboard-reveal'));
        if (reveals.length === 0) {
            return;
        }

        if (reduceMotion || !('IntersectionObserver' in window)) {
            reveals.forEach((node) => node.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -30px 0px'
        });

        reveals.forEach((node, index) => {
            node.style.setProperty('--reveal-delay', `${Math.min(index * 45, 260)}ms`);
            observer.observe(node);
        });
    }

    startLiveClocks();
    startReveals();
    startCounters();
});
