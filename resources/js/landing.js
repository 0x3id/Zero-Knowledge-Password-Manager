/**
 * Landing page animations for ZeroKnowledgePM.
 *
 * - Scroll-reveal transitions (IntersectionObserver).
 * - Count-up statistics (requestAnimationFrame easing).
 * - Rotating typing effect for the hero tagline.
 */

const LANDING_ROOT = document.documentElement;

/** Reveal elements when they enter the viewport. */
function initReveal() {
    const elements = document.querySelectorAll('.reveal');
    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    elements.forEach((el) => observer.observe(el));
}

/**
 * Animate a stat counter from 0 to its target value.
 *
 * @param {HTMLElement} el - Element containing the target in `data-count`.
 * @param {number} duration - Animation duration in ms.
 * @returns {void}
 */
function animateCountUp(el, duration = 1600) {
    const target = parseInt(el.dataset.count ?? '0', 10);
    if (Number.isNaN(target) || target <= 0) {
        el.textContent = target.toLocaleString('en-US');
        return;
    }

    const start = performance.now();

    function frame(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(target * eased).toLocaleString('en-US');
        if (progress < 1) requestAnimationFrame(frame);
    }

    requestAnimationFrame(frame);
}

/** Start the count-up animations once the stats bar is visible. */
function initStats() {
    const items = document.querySelectorAll('.stat-item [data-count]');
    if (!items.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animateCountUp(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    items.forEach((el) => observer.observe(el));
}

/**
 * Rotating typewriter effect for the hero tagline.
 *
 * @returns {void}
 */
function initTyping() {
    const output = document.getElementById('landing-typed');
    if (!output) return;

    const phrases = [
        window.zkpmT?.('Landing typed passwords') ?? 'Passwords',
        window.zkpmT?.('Landing typed passkeys') ?? 'Passkeys',
        window.zkpmT?.('Landing typed secrets') ?? 'Secrets',
        window.zkpmT?.('Landing typed accounts') ?? 'Accounts',
    ];

    let phraseIndex = 0;
    let charIndex = 0;
    let deleting = false;

    function tick() {
        const phrase = phrases[phraseIndex];

        if (!deleting) {
            charIndex += 1;
            output.textContent = phrase.slice(0, charIndex);
            if (charIndex === phrase.length) {
                deleting = true;
                setTimeout(tick, 1900);
                return;
            }
            setTimeout(tick, 70);
        } else {
            charIndex -= 1;
            output.textContent = phrase.slice(0, charIndex);
            if (charIndex === 0) {
                deleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                setTimeout(tick, 350);
                return;
            }
            setTimeout(tick, 34);
        }
    }

    tick();
}

/**
 * Boot the landing page animations.
 *
 * @returns {void}
 */
export function initLanding() {
    if (!document.querySelector('.reveal') && !document.querySelector('.stat-item')) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion) {
        // Accessibility: skip motion entirely — reveal everything and
        // snap the counters to their final values.
        document.querySelectorAll('.reveal').forEach((el) => el.classList.add('revealed'));
        document.querySelectorAll('.stat-item [data-count]').forEach((el) => {
            el.textContent = parseInt(el.dataset.count ?? '0', 10).toLocaleString('en-US');
        });
        return;
    }

    initReveal();
    initStats();
    initTyping();
}
