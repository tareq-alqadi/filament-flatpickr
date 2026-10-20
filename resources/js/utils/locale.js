const RIGHT_TO_LEFT_LETTERS = /[֐-ࣿיִ-﷿ﹰ-﻿]/;

/**
 * The flatpickr locale for the one the server sent, or English for none. The server sends a locale as JSON, with the
 * day suffixes worked out (`ordinals`), as JSON can't hold the function flatpickr uses for them.
 */
export function resolveLocale(locale, { firstDayOfWeek = null } = {}) {
  const resolved = { ...(locale && typeof locale === 'object' ? locale : {}) };

  if (Array.isArray(resolved.ordinals)) {
    const { ordinals } = resolved;

    resolved.ordinal = (nth) => ordinals[nth - 1] ?? '';
    delete resolved.ordinals;
  }

  if (firstDayOfWeek !== null && firstDayOfWeek !== undefined) {
    resolved.firstDayOfWeek = firstDayOfWeek;
  }

  return withReadableRanges(resolved);
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
