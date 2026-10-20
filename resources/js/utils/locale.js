import l10n from 'flatpickr/dist/esm/l10n/index.js';

// Laravel locales whose flatpickr locale has another name.
const ALIASES = {
  el: 'gr',
  vi: 'vn',
  kk: 'kz',
  nb: 'no',
  ca: 'cat',
  zh_cn: 'zh',
  zh_hans: 'zh',
  zh_hk: 'zh_tw',
  zh_hant: 'zh_tw',
  sr_latn: 'sr',
};

/**
 * The flatpickr locale for a Laravel locale such as "ar", "pt_BR" or "zh-TW": the full locale, then its language,
 * then English.
 */
export function resolveLocale(locale) {
  // A locale object given in customConfig() is used as it is.
  if (locale && typeof locale === 'object') {
    return locale;
  }

  const key = String(locale ?? '')
    .toLowerCase()
    .replace(/-/g, '_');

  const language = key.split('_')[0];

  for (const candidate of [key, ALIASES[key], language, ALIASES[language]]) {
    if (candidate && l10n[candidate]) {
      return l10n[candidate];
    }
  }

  return l10n.default;
}
