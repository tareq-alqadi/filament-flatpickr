/**
 * Run a handler, then chain any existing Flatpickr hook (function or array).
 */
export function chainHook(existing, handler) {
  return (...args) => {
    handler(...args);

    if (Array.isArray(existing)) {
      existing.forEach((fn) => fn(...args));
    } else if (typeof existing === 'function') {
      existing(...args);
    }
  };
}
