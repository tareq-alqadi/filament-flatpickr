import { addDays, isDate, startOfDay } from '../utils/dates.js';
import { ensureCalendarMainWrapper } from '../utils/calendar-layout.js';

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
  const year = today.getFullYear();
  const month = today.getMonth();

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
      // The same day next month, or its last day: 31 January gives 29 February, not 2 March.
      return new Date(year, month + 1, Math.min(today.getDate(), new Date(year, month + 2, 0).getDate()));
    case 'start_of_month':
      return new Date(year, month, 1);
    case 'end_of_month':
      return new Date(year, month + 1, 0);
    case 'start_of_last_month':
      return new Date(year, month - 1, 1);
    case 'end_of_last_month':
      return new Date(year, month, 0);
    case 'start_of_year':
      return new Date(year, 0, 1);
    case 'end_of_year':
      return new Date(year, 11, 31);
    default: {
      // The server sends a preset date given in code in the state format.
      const parsed = fp?.parseDate(value, fp.config.dateFormat);

      return isDate(parsed) ? parsed : null;
    }
  }
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
 * A sidebar of quick picks beside the calendar. A preset stays highlighted until the dates change another way, and
 * one the limits rule out can't be clicked: that is checked whenever the calendar opens, as presets move on with today.
 */
export function presetsPlugin(presets) {
  return (fp) => {
    let buttons = [];
    let isApplying = false;

    const highlight = (active) => {
      buttons.forEach(({ button }) => button.classList.toggle('fi-fp-preset-active', button === active));
    };

    const refresh = () => {
      buttons.forEach(({ button, preset }) => {
        button.disabled = !resolvePresetDates(preset, fp);
      });
    };

    const apply = (preset, button) => {
      const dates = resolvePresetDates(preset, fp);

      if (!dates) {
        return;
      }

      isApplying = true;

      try {
        // Showing the month first keeps the week picker, which reads the day drawn as picked, on the right week.
        fp.jumpToDate(dates[0]);
        fp.setDate(dates, true);
      } finally {
        isApplying = false;
      }

      highlight(button);

      if (fp.config.closeOnSelect) {
        fp.close();
      }
    };

    return {
      onReady() {
        const sidebar = document.createElement('div');

        sidebar.className = 'fi-fp-presets';

        buttons = presets.map((preset) => {
          const button = document.createElement('button');

          button.type = 'button';
          button.className = 'fi-fp-preset-btn';
          button.textContent = preset.label;
          button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            apply(preset, button);
          });

          sidebar.appendChild(button);

          return { button, preset };
        });

        ensureCalendarMainWrapper(fp);
        fp.calendarContainer.classList.add('fi-fp-has-presets');
        fp.calendarContainer.prepend(sidebar);

        refresh();
      },

      onOpen: refresh,

      onChange() {
        if (!isApplying) {
          highlight(null);
        }
      },
    };
  };
}
