/**
 * Whether the picker holds a list of dates: a range or several dates.
 */
export function isList(kind) {
  return kind === 'range' || kind === 'multiple';
}

/**
 * The first day of the week a date is in, as the calendar's rows show it.
 */
export function startOfWeek(date, firstDayOfWeek = 0) {
  const start = new Date(date.getFullYear(), date.getMonth(), date.getDate());

  start.setDate(start.getDate() - ((start.getDay() - firstDayOfWeek + 7) % 7));

  return start;
}

/**
 * The day a week picker selects for the week starting on a date: its middle, so the week number shown is the same
 * whichever day was clicked, or else the first day of it the limits allow.
 */
export function weekAnchor(start, fp) {
  const day = (offset) => new Date(start.getFullYear(), start.getMonth(), start.getDate() + offset);
  const candidates = [3, 0, 1, 2, 4, 5, 6].map(day);

  return candidates.find((date) => fp.isEnabled(date)) ?? start;
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
 * The dates of a state: none for an empty one, or null when an entry isn't a date in the state format.
 */
export function datesFromState(state, parse, format) {
  if (state === null || state === undefined || state === '' || (Array.isArray(state) && !state.length)) {
    return [];
  }

  const dates = (Array.isArray(state) ? state : [state])
    .map((value) => (typeof value === 'string' ? parse(value, format) : undefined));

  return dates.every((date) => date instanceof Date && !Number.isNaN(date.getTime())) ? dates : null;
}
