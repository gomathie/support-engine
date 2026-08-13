/**
 * Minimal debounce. Used by the course filters so typing in the search box
 * fires one request rather than one per keystroke.
 */
export function debounce(fn, wait = 300) {
    let timer;

    return function debounced(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
}
