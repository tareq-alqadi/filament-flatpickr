import flatpickr from 'flatpickr';

/**
 * A month picker's value as a local date. "2024-02" is the first of February where the user is, not midnight UTC,
 * which is still January west of UTC.
 */
export function parseMonthSelectDate(dateValue, dateFormat, fp = null) {
  if (!dateValue) {
    return null;
  }

  const parse = fp ? fp.parseDate.bind(fp) : flatpickr.parseDate;

  for (const format of [dateFormat, 'Y-m']) {
    try {
      const parsed = parse(dateValue, format);

      if (parsed && !isNaN(parsed.getTime())) {
        return parsed;
      }
    } catch {
      // Not in this format.
    }
  }

  return null;
}

export function getMonthSelectDefaultDate(state, dateFormat) {
  if (!state) {
    return undefined;
  }

  return parseMonthSelectDate(state, dateFormat) ?? state;
}

/**
 * Whether the picker holds a list of dates: a range or several dates.
 */
export function isListMode(mode) {
  return mode === 'range' || mode === 'multiple';
}

export function formatSelectedDates(selectedDates, { dateFormat, isList }) {
  if (isList) {
    return selectedDates.map((date) => flatpickr.formatDate(date, dateFormat));
  }

  return flatpickr.formatDate(selectedDates[0], dateFormat);
}
