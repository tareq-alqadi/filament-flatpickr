const MONTH_SELECT_THEME_CLASS = 'flatpickr-monthSelect-theme-';

/**
 * Filament puts the `dark` class on <html> for dark mode, whether it was picked or follows the system.
 */
export function isDarkMode() {
  return document.documentElement.classList.contains('dark');
}

export function currentThemeMode() {
  return isDarkMode() ? 'dark' : 'light';
}

export function usesNativeTheme(component) {
  return component.attribs.nativeTheme === true;
}

function setThemeStylesheet(component, href) {
  const link = component.$refs.themeStylesheet;

  if (link && href && link.getAttribute('href') !== href) {
    link.setAttribute('href', href);
  }
}

/**
 * Matches the picker to the page's light or dark mode. The Filament theme does this in CSS; another theme swaps to
 * flatpickr's dark stylesheet.
 */
export function applyThemeMode(component) {
  component.mode = currentThemeMode();

  const calendar = component.fp?.calendarContainer;

  if (calendar && component.attribs.monthSelect === true) {
    calendar.classList.remove(`${MONTH_SELECT_THEME_CLASS}light`, `${MONTH_SELECT_THEME_CLASS}dark`);
    calendar.classList.add(`${MONTH_SELECT_THEME_CLASS}${component.mode}`);
  }

  if (usesNativeTheme(component)) {
    return;
  }

  setThemeStylesheet(
    component,
    component.mode === 'dark' ? component.attribs.darkThemeAsset : component.attribs.themeAsset,
  );
}

/**
 * Follows the `dark` class on <html>, so the picker changes with Filament's theme switcher and the system setting.
 */
export function watchThemeMode(component) {
  const observer = new MutationObserver(() => {
    if (currentThemeMode() !== component.mode) {
      applyThemeMode(component);
    }
  });

  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  return observer;
}
