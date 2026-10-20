import { ensureCalendarMainWrapper } from './calendar-layout.js';
import { parseStateDate } from './state-dates.js';

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());

const addDays = (date, days) => {
  const next = new Date(date);

  next.setDate(next.getDate() + days);

  return next;
};

/**
 * Turns a preset token, or a date in the picker's format, into a local Date. A day such as "2024-12-25" is read where
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

// A preset date given in code comes in the state's format, which is English whatever the picker's locale.
function parsePresetDate(value, fp) {
  const parsed = fp ? parseStateDate(value, fp.config.dateFormat, fp.config) : null;

  if (parsed) {
    return parsed;
  }

  const fallback = new Date(value);

  return Number.isNaN(fallback.getTime()) ? null : fallback;
}

/**
 * Adds a sidebar of quick date shortcuts to the calendar.
 */
export function attachPresets(fp, component) {
  const presets = component.attribs.presets ?? [];

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

  presets.forEach((preset) => {
    const button = document.createElement('button');

    button.type = 'button';
    button.className = 'fi-fp-preset-btn';
    button.textContent = preset.label;

    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const dates = (
        preset.date
          ? [resolvePresetDate(preset.date, fp)]
          : [resolvePresetDate(preset.start, fp), resolvePresetDate(preset.end, fp)]
      ).filter(Boolean);

      if (!dates.length) {
        return;
      }

      fp.setDate(dates, true);
      fp.jumpToDate(dates[0]);

      sidebar.querySelectorAll('.fi-fp-preset-btn').forEach((item) => {
        item.classList.toggle('fi-fp-preset-active', item === button);
      });

      if (fp.config.closeOnSelect) {
        fp.close();
      }
    });

    sidebar.appendChild(button);
  });

  fp.calendarContainer.prepend(sidebar);
}
