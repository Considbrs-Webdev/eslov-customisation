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

        const controller = splide.Components?.Controller;
        if (!controller) {
            return;
        }

        // getEnd() is the last slide index, not the last navigable page when perMove/perPage > 1.
        setControlDisabled(prev, controller.getPrev() < 0);
        setControlDisabled(next, controller.getNext() < 0);
    };

    splide.on('mounted moved refreshed updated', updateControls);
    updateControls();
}

export function initSliderNavState() {
    document.addEventListener('slider:ready', onSliderReady);
}
