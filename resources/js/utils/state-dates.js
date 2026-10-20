import { createDateFormatter, createDateParser } from 'flatpickr/dist/esm/utils/dates';
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
 * A parser that only gives a date when it is the text it was read from. Flatpickr rolls a day, month or hour that
 * doesn't exist over, reading "31/02/2024" as 2 March and "25:00" as 1:00 the next day, which the server rejects: the
 * date written back in the format shows that. Leading zeros, spacing and letter case don't count.
 */
export function createStrictDateParser({ l10n = english, noCalendar = false } = {}) {
  const parse = createDateParser({ config: { noCalendar, errorHandler: () => {} }, l10n });
  const format = createDateFormatter({ l10n });
  const normalize = (text) => String(text)
    .trim()
    .replace(/\s+/g, ' ')
    .replace(/\d+/g, (digits) => String(Number(digits)))
    .toLowerCase();

  return (text, dateFormat) => {
    const parsed = parse(text, dateFormat);

    if (!(parsed instanceof Date) || Number.isNaN(parsed.getTime())) {
      return undefined;
    }

    // A timestamp or an ISO date can be written many ways, and flatpickr doesn't roll those over.
    if (typeof text !== 'string' || /[UZ]/.test(dateFormat.replace(/\\./g, '')) || /(Z|GMT)$/.test(text.trim())) {
      return parsed;
    }

    // Text may stop early, as flatpickr allows: "2024-01-15" in "Y-m-d H:i" is that day at midnight.
    const written = normalize(format(parsed, dateFormat));
    const given = normalize(text);
    const readsAsGiven = written === given
      || (written.startsWith(given) && /^[^\p{L}\p{N}]/u.test(written.slice(given.length)));

    return readsAsGiven ? parsed : undefined;
  };
}

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
    const parse = createStrictDateParser({ l10n: english, noCalendar: config.noCalendar });

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
