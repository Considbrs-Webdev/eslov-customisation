/**
 * Disable manual prev/next controls when a Splide slider cannot move further.
 * Listens for styleguide `slider:ready` (dispatched from c-slider init).
 */

/**
 * @param {HTMLButtonElement|Element} el
 * @param {boolean} isDisabled
 */
function setControlDisabled(el, isDisabled) {
    if ('disabled' in el) {
        el.disabled = isDisabled;
    }

    el.setAttribute('aria-disabled', String(isDisabled));
    el.classList.toggle('is-disabled', isDisabled);
}

/**
 * @param {CustomEvent} event
 */
function onSliderReady(event) {
    const { sliderElement, splide } = event.detail ?? {};
    if (!(sliderElement instanceof Element) || !splide) {
        return;
    }

    const buttonId = sliderElement.getAttribute('data-js-slider-buttons');
    if (!buttonId) {
        return;
    }

    const container = document.getElementById(buttonId);
    if (!container) {
        return;
    }

    const prev = container.querySelector('[data-js-slider-prev]');
    const next = container.querySelector('[data-js-slider-next]');
    if (!prev || !next) {
        return;
    }

    const updateControls = () => {
        if (splide.options?.type === 'loop') {
            setControlDisabled(prev, false);
            setControlDisabled(next, false);
            return;
        }

        const end = splide.Components?.Controller?.getEnd?.() ?? 0;
        setControlDisabled(prev, splide.index <= 0);
        setControlDisabled(next, splide.index >= end);
    };

    splide.on('mounted move updated refreshed', updateControls);
    updateControls();
}

export function initSliderNavState() {
    document.addEventListener('slider:ready', onSliderReady);
}
