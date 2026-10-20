import flatpickr from 'flatpickr';
import { parseStateDate } from './state-dates.js';

/**
 * A month picker's value as a local date. "2024-02" is the first of February where the user is, not midnight UTC,
 * which is still January west of UTC.
 */
export function parseMonthSelectDate(dateValue, dateFormat) {
  if (!dateValue) {
    return null;
  }

  return parseStateDate(dateValue, dateFormat) ?? parseStateDate(dateValue, 'Y-m');
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
