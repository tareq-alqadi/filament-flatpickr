export function isDarkMode() {
  return document.querySelector('html')?.classList.contains('dark') ?? false;
}

export function resolveInitialMode() {
  const storedTheme = localStorage.getItem('theme');

  if (storedTheme) {
    return storedTheme;
  }

  return isDarkMode() ? 'dark' : 'light';
}

export function resolveThemeMode(mode) {
  const storedTheme = localStorage.getItem('theme');

  if (storedTheme) {
    return storedTheme;
  }

  return mode ?? (isDarkMode() ? 'dark' : 'light');
}

export function setThemeStylesheet(component, href) {
  const link = component.$refs.themeStylesheet;

  if (link) {
    link.href = href;
  }
}

export function applyDarkTheme(component) {
  if (component.themeMode !== 'dark') {
    return;
  }

  setThemeStylesheet(component, component.attribs.darkThemeAsset);
}

export function createThemeChangeHandler(component) {
  return (event) => {
    component.mode = event.detail.dark ? 'dark' : 'light';

    setThemeStylesheet(
      component,
      component.mode === 'dark'
        ? component.attribs.darkThemeAsset
        : component.attribs.themeAsset,
    );
  };
}
