import { ensureCalendarMainWrapper } from '../utils/calendar-layout.js';
import { startOfDay, startOfMonth } from '../utils/dates.js';

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

    // Today can't be picked before minDate, after maxDate or on a disabled day. It is checked whenever the calendar
    // opens, as today moves on at midnight.
    const refresh = () => {
      if (todayButton) {
        todayButton.disabled = !fp.isEnabled(todayDate());
      }
    };

    const pickToday = () => {
      const date = todayDate();

      if (!fp.isEnabled(date)) {
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
