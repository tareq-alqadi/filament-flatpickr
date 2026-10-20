import flatpickrDatepicker from '../../resources/js/flatpickr.js';

const mounted = [];

/**
 * Starts the Alpine component the way the Blade view does, with a stand-in for Alpine: `$refs`, and a `$watch` on the
 * state that fires when it changes, as `$entangle` does.
 */
export function mount({ state = null, packageConfig = {}, attribs = {}, locale = 'en', themeStylesheet = null } = {}) {
  const input = document.createElement('input');

  document.body.appendChild(input);

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
        watchers.forEach((watcher) => watcher(value, old));
      }

      return true;
    },
  });

  data.$refs = { picker: input, themeStylesheet };
  data.$watch = (key, watcher) => watchers.push(watcher);

  component.init();
  mounted.push(component);

  return component;
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
