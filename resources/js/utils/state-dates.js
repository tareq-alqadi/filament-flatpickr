import { createDateParser } from 'flatpickr/dist/esm/utils/dates';
import { tokenRegex } from 'flatpickr/dist/esm/utils/formatting';
import { english } from 'flatpickr/dist/esm/l10n/default';

const LOCALE_TOKENS = ['D', 'l', 'M', 'F', 'K'];

const englishTokens = {
  D: `(${english.weekdays.shorthand.join('|')})`,
  l: `(${english.weekdays.longhand.join('|')})`,
  M: `(${english.months.shorthand.join('|')})`,
  F: `(${english.months.longhand.join('|')})`,
  K: '(AM|PM|am|pm)',
};

/**
 * Reads a date of the field's state. The state is written in English (flatpickr.formatDate), which the server reads
 * and writes too, but the picker's own parser only knows its locale's month names and AM/PM: in Arabic it reads
 * "2:30 PM" as 2:30 AM. Flatpickr keeps those patterns in one shared table, so it is English while the state is read.
 */
export function parseStateDate(value, format, config = {}) {
  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value;
  }

  if (value === null || value === undefined || value === '') {
    return null;
  }

  const localeTokens = Object.fromEntries(LOCALE_TOKENS.map((token) => [token, tokenRegex[token]]));

  Object.assign(tokenRegex, englishTokens);

  try {
    const parse = createDateParser({
      config: { noCalendar: config.noCalendar, parseDate: config.parseDate, errorHandler: () => {} },
      l10n: english,
    });

    const parsed = parse(String(value), format);

    return parsed instanceof Date && !Number.isNaN(parsed.getTime()) ? parsed : null;
  } catch {
    return null;
  } finally {
    Object.assign(tokenRegex, localeTokens);
  }
}

/**
 * The state's dates, or null when one of them isn't a date in the format, such as text typed in the picker.
 */
export function parseStateDates(state, format, config = {}) {
  const values = Array.isArray(state) ? state : [state];
  const dates = values.map((value) => parseStateDate(value, format, config));

  return dates.length && dates.every(Boolean) ? dates : null;
}
