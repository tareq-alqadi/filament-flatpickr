import filamentFlatpickr from '../../resources/js/index.js';

/**
 * The flatpickr options the server sends for each kind of picker (BasePicker::getConfig()), without limits.
 */
export const KINDS = {
  date: { mode: 'single', dateFormat: 'Y-m-d', altFormat: 'F j, Y', enableTime: false, noCalendar: false },
  datetime: { mode: 'single', dateFormat: 'Y-m-d H:i:S', altFormat: 'F j, Y H:i', enableTime: true, noCalendar: false },
  time: { mode: 'single', dateFormat: 'H:i:S', altFormat: 'H:i', enableTime: true, noCalendar: true },
  range: { mode: 'range', dateFormat: 'Y-m-d', altFormat: 'F j, Y', enableTime: false, noCalendar: false },
  multiple: { mode: 'multiple', dateFormat: 'Y-m-d', altFormat: 'F j, Y', enableTime: false, noCalendar: false },
  month: { mode: 'single', dateFormat: 'Y-m-d', altFormat: 'F Y', enableTime: false, noCalendar: false },
  week: { mode: 'single', dateFormat: 'Y-m-d', altFormat: '\\W\\e\\e\\k W, Y', enableTime: false, noCalendar: false },
};

const mounted = [];

/**
 * Starts the Alpine component the way the Blade view does, with a stand-in for Alpine: `$refs`, and a `$watch` on the
 * state that fires after it changes, in a microtask as Alpine's does. Await `settle()` before checking what a watcher
 * did.
 */
export function mount({ kind = 'date', config = {}, state = null, ...options } = {}) {
  const input = document.createElement('input');
  const inlineCalendar = document.createElement('div');

  document.body.append(input, inlineCalendar);

  const watchers = [];

  const data = filamentFlatpickr({
    kind,
    config: { altInput: true, altInputClass: '', time_24hr: true, ...KINDS[kind], ...config },
    state,
    ...options,
  });

  const component = new Proxy(data, {
    set(target, key, value) {
      const old = target[key];

      target[key] = value;

      if (key === 'state' && JSON.stringify(old) !== JSON.stringify(value)) {
        queueMicrotask(() => watchers.forEach((watcher) => watcher(target.state, old)));
      }

      return true;
    },
  });

  data.$refs = { input, inlineCalendar };
  data.$watch = (key, watcher) => watchers.push(watcher);

  component.init();
  mounted.push(component);

  return component;
}

/**
 * Waits for the state watchers, and anything they set off, to run.
 */
export function settle() {
  return new Promise((resolve) => setTimeout(resolve, 0));
}

/**
 * Today at midnight, or a number of days from it.
 */
export function day(offset = 0) {
  const now = new Date();

  return new Date(now.getFullYear(), now.getMonth(), now.getDate() + offset);
}

export function ymd(date) {
  return local(date).slice(0, 10);
}

export function unmountAll() {
  mounted.splice(0).forEach((component) => component.destroy());
  document.body.innerHTML = '';
}

/**
 * A local date as "Y-m-d H:i:s", to compare without timezones getting in the way.
 */
export function local(date) {
  const pad = (value) => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} `
    + `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}
