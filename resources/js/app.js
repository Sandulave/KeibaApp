import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const setupMobileTableFades = () => {
    const wrappers = Array.from(document.querySelectorAll('.overflow-x-auto'))
        .filter((el) => el.querySelector('table') !== null)
        .filter((el) => el.dataset.mobileFadeReady !== '1');

    wrappers.forEach((scrollEl) => {
        scrollEl.dataset.mobileFadeReady = '1';

        const host = document.createElement('div');
        host.className = 'mobile-scroll-fade-host';
        scrollEl.parentNode?.insertBefore(host, scrollEl);
        host.appendChild(scrollEl);

        const left = document.createElement('div');
        left.className = 'mobile-scroll-fade mobile-scroll-fade-left hidden sm:hidden';
        left.innerHTML = '<span class="mobile-scroll-fade-arrow">←</span>';

        const right = document.createElement('div');
        right.className = 'mobile-scroll-fade mobile-scroll-fade-right hidden sm:hidden';
        right.innerHTML = '<span class="mobile-scroll-fade-arrow">→</span>';

        host.appendChild(left);
        host.appendChild(right);

        const update = () => {
            const max = scrollEl.scrollWidth - scrollEl.clientWidth;
            if (max <= 1) {
                left.classList.add('hidden');
                right.classList.add('hidden');
                return;
            }
            left.classList.toggle('hidden', scrollEl.scrollLeft <= 1);
            right.classList.toggle('hidden', scrollEl.scrollLeft >= max - 1);
        };

        scrollEl.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        requestAnimationFrame(update);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupMobileTableFades);
} else {
    setupMobileTableFades();
}
