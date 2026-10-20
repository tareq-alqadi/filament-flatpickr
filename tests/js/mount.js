import flatpickrDatepicker from '../../resources/js/flatpickr.js';

const mounted = [];

/**
 * Starts the Alpine component the way the Blade view does, with a stand-in for Alpine: `$refs`, and a `$watch` on the
 * state that fires after it changes, in a microtask as Alpine's does. Await `settle()` before checking what a watcher
 * did.
 */
export function mount({ state = null, packageConfig = {}, attribs = {}, locale = 'en', themeStylesheet = null } = {}) {
  const input = document.createElement('input');
  const inlineCalendar = document.createElement('div');

  document.body.append(input, inlineCalendar);

  const watchers = [];

  const data = flatpickrDatepicker({
    state,
    packageConfig: { dateFormat: 'Y-m-d', ...packageConfig },
    attribs: { mode: 'single', nativeTheme: true, ...attribs },
    locale,
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

  data.$refs = { picker: input, themeStylesheet, inlineCalendar };
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
  document.documentElement.classList.remove('dark');
}

/**
 * A local date as "Y-m-d H:i:s", to compare without timezones getting in the way.
 */
export function local(date) {
  const pad = (value) => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} `
    + `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}
