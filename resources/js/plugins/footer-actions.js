import { ensureCalendarMainWrapper } from '../utils/calendar-layout.js';
import { startOfDay, startOfMonth } from '../utils/dates.js';

const secondsOfDay = (date) => date.getHours() * 3600 + date.getMinutes() * 60 + date.getSeconds();

/**
 * Clear, Today and Apply buttons under the calendar, for the labels given. `now` gives the moment Today picks.
 */
export function footerActionsPlugin({ today, clear, apply }, kind, now = () => new Date()) {
  return (fp) => {
    let todayButton = null;

    // A picker with a time gets now, a month picker this month, and others the start of today: flatpickr compares
    // "now" with a disabled day to the second, so it would let today through.
    const todayDate = () => {
      const date = now();

      if (fp.config.enableTime) {
        return date;
      }

      return kind === 'month' ? startOfMonth(date) : startOfDay(date);
    };

    // Today can't be picked before minDate, after maxDate, on a disabled day, or with a time outside minTime and
    // maxTime. Flatpickr's isEnabled() only compares days, while the server compares moments. It is checked whenever
    // the calendar opens, as now moves on.
    const canPick = (date) => {
      if (!fp.isEnabled(date)) {
        return false;
      }

      if (!fp.config.enableTime) {
        return true;
      }

      const { minDate, maxDate, minTime, maxTime } = fp.config;

      if ((minDate && date < minDate) || (maxDate && date > maxDate)) {
        return false;
      }

      const time = secondsOfDay(date);
      const min = minTime ? secondsOfDay(minTime) : null;
      const max = maxTime ? secondsOfDay(maxTime) : null;

      // A minTime later than the maxTime is a span over midnight.
      if (min !== null && max !== null && min > max) {
        return time >= min || time <= max;
      }

      return (min === null || time >= min) && (max === null || time <= max);
    };

    const refresh = () => {
      if (todayButton) {
        todayButton.disabled = !canPick(todayDate());
      }
    };

    const pickToday = () => {
      const date = todayDate();

      if (!canPick(date)) {
        return;
      }

      // Showing the month first keeps the week picker, which reads the day drawn as picked, on the right week.
      fp.jumpToDate(date);
      fp.setDate(kind === 'range' ? [date, date] : [date], true);
    };

    return {
      onReady() {
        const footer = document.createElement('div');

        footer.className = 'fi-fp-footer';

        const addButton = (label, className, onClick) => {
          const button = document.createElement('button');

          button.type = 'button';
          button.className = `fi-fp-footer-btn ${className}`;
          button.textContent = label;
          button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            onClick();
          });

          footer.appendChild(button);

          return button;
        };

        if (clear) {
          addButton(clear, 'fi-fp-footer-btn-muted', () => {
            fp.clear();
            fp.close();
          });
        }

        if (today) {
          todayButton = addButton(today, 'fi-fp-footer-btn-primary', pickToday);
        }

        if (apply) {
          addButton(apply, 'fi-fp-footer-btn-primary', () => fp.close());
        }

        ensureCalendarMainWrapper(fp).appendChild(footer);

        refresh();
      },

      onOpen: refresh,
    };
  };
}
