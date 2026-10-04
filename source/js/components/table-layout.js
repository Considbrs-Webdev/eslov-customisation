/** Prefer one line, allow at most two only if that eliminates scrolling. */
function enhanceTable(component) {
    const inner = component.querySelector('.c-table__inner');
    const table = inner?.querySelector('.c-table__table');
    if (!inner || !table) return;

    const headings = [...table.querySelectorAll('thead .c-table__heading')];
    let frame = 0;
    let previousWidth = -1;

    const update = () => {
        frame = 0;
        if (!inner.clientWidth) return; // Hidden modal: measure when it opens.
        const scrollLeft = inner.scrollLeft;
        component.classList.remove('eslov-table--wrap');
        const overflows = () => table.getBoundingClientRect().width > inner.clientWidth + 1;

        // Multidimensional tables have their own collapse/expand layout.
        if (overflows() && headings.length && !component.classList.contains('c-table--multidimensional')) {
            component.classList.add('eslov-table--wrap');
            const hasLongHeading = headings.some(heading => {
                const lineHeight = parseFloat(getComputedStyle(heading).lineHeight);
                return heading.getBoundingClientRect().height > lineHeight * 2 + 1;
            });
            if (overflows() || hasLongHeading) component.classList.remove('eslov-table--wrap');
        }

        const scrolling = overflows();
        component.classList.toggle('eslov-table--fits', !scrolling);
        if (scrolling) {
            inner.tabIndex = 0;
            inner.setAttribute('role', 'region');
            inner.setAttribute('aria-label', 'Tabell, skrolla i sidled för att se alla kolumner');
        } else {
            inner.removeAttribute('tabindex');
            inner.removeAttribute('role');
            inner.removeAttribute('aria-label');
        }
        inner.scrollLeft = scrolling ? scrollLeft : 0;
    };

    const schedule = () => {
        if (!frame) frame = requestAnimationFrame(update);
    };
    new ResizeObserver(() => {
        const width = inner.clientWidth;
        if (width !== previousWidth) {
            previousWidth = width;
            schedule();
        }
    }).observe(inner);

    // Filtering and sorting replace the rows in the upstream component.
    const body = table.querySelector('tbody');
    if (body) new MutationObserver(schedule).observe(body, { childList: true, subtree: true, characterData: true });
    document.fonts?.ready.then(schedule);
    schedule();
}

export function initTableLayout() {
    const init = () => document.querySelectorAll('.c-table').forEach(enhanceTable);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
}
