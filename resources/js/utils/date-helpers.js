import flatpickr from 'flatpickr';

export function parseMonthSelectDate(dateValue, dateFormat, fp = null) {
  if (!dateValue) {
    return null;
  }

  if (dateFormat === 'Y-m') {
    const parsed = new Date(`${dateValue}-01T00:00:00Z`);

    if (!isNaN(parsed.getTime())) {
      return parsed;
    }
  }

  if (fp) {
    try {
      const parsed = fp.parseDate(dateValue, dateFormat);

      if (parsed && !isNaN(parsed.getTime())) {
        return parsed;
      }
    } catch {
      // flatpickr parseDate failed
    }
  }

  return null;
}

export function getMonthSelectDefaultDate(state, dateFormat) {
  if (!state) {
    return undefined;
  }

  if (dateFormat === 'Y-m') {
    const parsed = new Date(`${state}-01T00:00:00Z`);

    if (!isNaN(parsed.getTime())) {
      return parsed;
    }
  }

  return state;
}

export function formatSelectedDates(selectedDates, { dateFormat, isRangePicker, isMultipleMode }) {
  if (isRangePicker || isMultipleMode) {
    return selectedDates.map((date) => flatpickr.formatDate(date, dateFormat));
  }

  return flatpickr.formatDate(selectedDates[0], dateFormat);
}
