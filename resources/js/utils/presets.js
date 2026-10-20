import { ensureCalendarMainWrapper } from './calendar-layout.js';

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());

const addDays = (date, days) => {
  const next = new Date(date);

  next.setDate(next.getDate() + days);

  return next;
};

/**
 * Turns a preset token or an explicit date into a Date relative to today.
 */
export function resolvePresetDate(value) {
  if (value instanceof Date) {
    return value;
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
    case 'next_month':
      return new Date(today.getFullYear(), today.getMonth() + 1, today.getDate());
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
      return new Date(value);
  }
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

      const dates = preset.date
        ? [resolvePresetDate(preset.date)]
        : [resolvePresetDate(preset.start), resolvePresetDate(preset.end)];

      fp.setDate(dates.filter((date) => !Number.isNaN(date.getTime())), true);
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
