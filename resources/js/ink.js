// Small helpers for the "paper and ink" visual language (ENQ-148).

export function prefersReducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
}

// Reads a design token from @theme so charts and scripts never hardcode colours.
export function themeColor(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(`--color-${name}`).trim();
}

// Matches the server side MoneyDisplay format: space between thousands, dot before kopecks.
function formatKopecks(kopecks, signed) {
    const sign = kopecks < 0 ? '−' : (signed && kopecks > 0 ? '+' : '');
    const absolute = Math.abs(kopecks);
    const rubles = Math.floor(absolute / 100).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    const cents = absolute % 100;

    return sign + rubles + (cents === 0 ? '' : '.' + cents.toString().padStart(2, '0'));
}

// Switches a money figure between its nominal and real value, sliding the digits instead of jumping.
export function moneyToggle({ nominal, real, signed = true, duration = 650 }) {
    return {
        showReal: false,
        value: nominal,
        frame: null,

        get display() {
            return formatKopecks(this.value, signed);
        },

        show(real) {
            this.showReal = real;
            this.slideTo(real ? this.target.real : this.target.nominal);
        },

        target: { nominal, real },

        slideTo(target) {
            cancelAnimationFrame(this.frame);

            if (prefersReducedMotion()) {
                this.value = target;

                return;
            }

            const from = this.value;
            const start = performance.now();

            const step = (now) => {
                const progress = Math.min(1, Math.max(0, (now - start) / duration));
                const eased = 1 - Math.pow(1 - progress, 3);

                // Whole rubles while moving, the exact amount on the last frame.
                this.value = progress < 1
                    ? Math.round((from + (target - from) * eased) / 100) * 100
                    : target;

                if (progress < 1) {
                    this.frame = requestAnimationFrame(step);
                }
            };

            this.frame = requestAnimationFrame(step);
        },

        destroy() {
            cancelAnimationFrame(this.frame);
        },
    };
}
