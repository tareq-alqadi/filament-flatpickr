import { createDateFormatter, createDateParser } from 'flatpickr/dist/esm/utils/dates';
import { tokenRegex } from 'flatpickr/dist/esm/utils/formatting';
import { english } from 'flatpickr/dist/esm/l10n/default';

/**
 * The patterns flatpickr reads names and AM/PM with, for a locale. Flatpickr keeps them in one table that every picker
 * on the page shares, and each new picker fills it with its own locale.
 */
function localeTokens(l10n) {
  const [am, pm] = l10n.amPM;

  return {
    D: `(${l10n.weekdays.shorthand.join('|')})`,
    l: `(${l10n.weekdays.longhand.join('|')})`,
    M: `(${l10n.months.shorthand.join('|')})`,
    F: `(${l10n.months.longhand.join('|')})`,
    K: `(${am}|${pm}|${am.toLowerCase()}|${pm.toLowerCase()})`,
  };
}

/**
 * A parser that only gives a date when it is the text it was read from. Flatpickr rolls a day, month or hour that
 * doesn't exist over, reading "31/02/2024" as 2 March and "25:00" as 1:00 the next day, which the server rejects: the
 * date written back in the format shows that. Leading zeros, spacing and letter case don't count. Names are read in
 * its own locale, whichever picker filled flatpickr's shared table last.
 */
export function createStrictDateParser({ l10n = english, noCalendar = false } = {}) {
  const locale = { ...english, ...l10n };
  const tokens = localeTokens(locale);
  const parseInLocale = createDateParser({ config: { noCalendar, errorHandler: () => {} }, l10n: locale });
  const format = createDateFormatter({ l10n: locale });

  const parse = (text, dateFormat) => {
    const shared = Object.fromEntries(Object.keys(tokens).map((token) => [token, tokenRegex[token]]));

    Object.assign(tokenRegex, tokens);

    try {
      return parseInLocale(text, dateFormat);
    } catch {
      return undefined;
    } finally {
      Object.assign(tokenRegex, shared);
    }
  };

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
