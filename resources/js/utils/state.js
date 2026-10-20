import { addDays, isDate, startOfWeek } from './dates.js';

/**
 * Whether the picker holds a list of dates: a range or several dates.
 */
export function isList(kind) {
  return kind === 'range' || kind === 'multiple';
}

/**
 * The day a week picker selects for the week starting on a date: its middle, so the week number shown is the same
 * whichever day was clicked, or else the first day of it the limits allow.
 */
export function weekAnchor(start, fp) {
  return [3, 0, 1, 2, 4, 5, 6].map((offset) => addDays(start, offset)).find((date) => fp.isEnabled(date)) ?? start;
}

/**
 * The state for the picked dates: text in the state format, which has no names in it and reads the same in every
 * locale; a list of it for a range or multiple picker; or null. A week picker holds the first day of the week.
 */
export function stateFromDates(dates, fp, kind) {
  if (!dates?.length) {
    return null;
  }

  const format = (date) => fp.formatDate(date, fp.config.dateFormat);

  if (kind === 'week') {
    return format(startOfWeek(dates[0], fp.l10n.firstDayOfWeek));
  }

  return isList(kind) ? dates.map(format) : format(dates[0]);
}

/**
 * The dates the calendar selects for a state: none for an empty one, or null when an entry isn't a date in the state
 * format. A week selects its anchor.
 */
export function datesFromState(state, fp, kind) {
  if (state === null || state === undefined || state === '' || (Array.isArray(state) && !state.length)) {
    return [];
  }

  const dates = (Array.isArray(state) ? state : [state])
    .map((value) => (typeof value === 'string' ? fp.parseDate(value, fp.config.dateFormat) : undefined));

  if (!dates.every(isDate)) {
    return null;
  }

  return kind === 'week' ? [weekAnchor(startOfWeek(dates[0], fp.l10n.firstDayOfWeek), fp)] : dates;
}

/**
 * Whether two lists of dates are the same moments.
 */
export function sameDates(first, second) {
  return first.length === second.length && first.every((date, index) => date.getTime() === second[index].getTime());
}
