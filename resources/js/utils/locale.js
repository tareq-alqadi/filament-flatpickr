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

const RIGHT_TO_LEFT_LETTERS = /[֐-ࣿיִ-﷿ﹰ-﻿]/;

/**
 * The flatpickr locale for a Laravel locale such as "ar", "pt_BR" or "zh-TW": the full locale, then its language,
 * then English.
 */
export function resolveLocale(locale) {
  // A locale object given in customConfig() is used as it is.
  if (locale && typeof locale === 'object') {
    return locale;
  }

  return withReadableRanges(findLocale(locale));
}

/**
 * Digits after an Arabic word count as Arabic numbers, so in "2024-01-01 إلى 2024-01-05" the browser shows the second
 * date as "05-01-2024". A left-to-right mark after the word keeps that date as it is written.
 */
function withReadableRanges(locale) {
  const separator = locale.rangeSeparator;

  if (!separator || !RIGHT_TO_LEFT_LETTERS.test(separator)) {
    return locale;
  }

  return { ...locale, rangeSeparator: `${separator}‎` };
}

function findLocale(locale) {

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
