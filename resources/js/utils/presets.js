import { ensureCalendarMainWrapper } from './calendar-layout.js';

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());

const addDays = (date, days) => {
  const next = new Date(date);

  next.setDate(next.getDate() + days);

  return next;
};

/**
 * Turns a preset token, or a date in the state format, into a local Date. A day such as "2024-12-25" is read where
 * the user is: `new Date()` would read it as midnight UTC, which is the day before west of UTC.
 */
export function resolvePresetDate(value, fp) {
  if (value instanceof Date) {
    return value;
  }

  if (value === null || value === undefined || value === '') {
    return null;
  }

  const today = startOfDay(new Date());

  switch (value) {
    case 'today':
      return today;
    case 'tomorrow':
      return addDays(today, 1);
    case 'yesterday':
      return addDays(today, -1);
    case 'in_7_days':
      return addDays(today, 7);
    case 'last_7_days':
      return addDays(today, -6);
    case 'last_30_days':
      return addDays(today, -29);
    case 'next_month': {
      // The same day next month, or its last day: 31 January gives 29 February, not 2 March.
      const lastDay = new Date(today.getFullYear(), today.getMonth() + 2, 0).getDate();

      return new Date(today.getFullYear(), today.getMonth() + 1, Math.min(today.getDate(), lastDay));
    }
    case 'start_of_month':
      return new Date(today.getFullYear(), today.getMonth(), 1);
    case 'end_of_month':
      return new Date(today.getFullYear(), today.getMonth() + 1, 0);
    case 'start_of_last_month':
      return new Date(today.getFullYear(), today.getMonth() - 1, 1);
    case 'end_of_last_month':
      return new Date(today.getFullYear(), today.getMonth(), 0);
    case 'start_of_year':
      return new Date(today.getFullYear(), 0, 1);
    case 'end_of_year':
      return new Date(today.getFullYear(), 11, 31);
    default:
      return parsePresetDate(value, fp);
  }
}

// The server sends a preset date given in code in the state format.
function parsePresetDate(value, fp) {
  const parsed = fp?.parseDate(value, fp.config.dateFormat);

  return parsed instanceof Date && !Number.isNaN(parsed.getTime()) ? parsed : null;
}

/**
 * The dates a preset picks, kept within minDate and maxDate: "Last 7 days" with a minDate three days ago starts on the
 * minDate. Null when the picker can't take them: a date that isn't one, is outside the limits or on a disabled day, or
 * a range wholly outside the limits.
 */
export function resolvePresetDates(preset, fp) {
  let dates = preset.date
    ? [resolvePresetDate(preset.date, fp)]
    : [resolvePresetDate(preset.start, fp), resolvePresetDate(preset.end, fp)];

  if (dates.some((date) => !date)) {
    return null;
  }

  if (dates.length === 2) {
    const { minDate, maxDate } = fp.config;
    let [start, end] = dates;

    if (minDate && start < startOfDay(minDate)) {
      start = startOfDay(minDate);
    }

    if (maxDate && end > maxDate) {
      end = startOfDay(maxDate);
    }

    if (start > end) {
      return null;
    }

    dates = [start, end];
  }

  return dates.every((date) => fp.isEnabled(date)) ? dates : null;
}

/**
 * Adds a sidebar of quick date shortcuts to the calendar.
 */
export function attachPresets(fp, component) {
  const presets = component.presets ?? [];

  if (!presets.length || !fp.calendarContainer) {
    return;
  }

  if (fp.calendarContainer.querySelector(':scope > .fi-fp-presets')) {
    return;
  }

  ensureCalendarMainWrapper(fp);
  fp.calendarContainer.classList.add('fi-fp-has-presets');

  const sidebar = document.createElement('div');

  sidebar.className = 'fi-fp-presets';

  let isApplyingPreset = false;

  const highlight = (active) => {
    sidebar.querySelectorAll('.fi-fp-preset-btn').forEach((item) => {
      item.classList.toggle('fi-fp-preset-active', item === active);
    });
  };

  const buttons = presets.map((preset) => {
    const button = document.createElement('button');

    button.type = 'button';
    button.className = 'fi-fp-preset-btn';
    button.textContent = preset.label;

    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const dates = resolvePresetDates(preset, fp);

      if (!dates) {
        return;
      }

      isApplyingPreset = true;

      try {
        fp.jumpToDate(dates[0]);
        fp.setDate(dates, true);
      } finally {
        isApplyingPreset = false;
      }
      highlight(button);

      if (fp.config.closeOnSelect) {
        fp.close();
      }
    });

    sidebar.appendChild(button);

    return { button, preset };
  });

  // A preset stays highlighted until the dates change another way, such as a day picked or the field cleared.
  fp.config.onChange.push(() => {
    if (!isApplyingPreset) {
      highlight(null);
    }
  });

  // A preset the limits rule out can't be clicked. It is checked whenever the calendar opens, as the presets move on
  // with today.
  const refresh = () => {
    buttons.forEach(({ button, preset }) => {
      button.disabled = !resolvePresetDates(preset, fp);
    });
  };

  refresh();
  fp.config.onOpen.push(refresh);

  fp.calendarContainer.prepend(sidebar);
}
