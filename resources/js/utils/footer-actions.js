import { ensureCalendarMainWrapper } from './calendar-layout.js';

/**
 * Adds a footer with Today, Clear and Apply shortcuts to the calendar.
 */
export function attachFooterActions(fp, component) {
  const { today: todayLabel, clear: clearLabel, apply: applyLabel } = component.footerActions ?? {};

  if ((!todayLabel && !clearLabel && !applyLabel) || !fp.calendarContainer) {
    return;
  }

  const targetContainer = ensureCalendarMainWrapper(fp) ?? fp.calendarContainer;

  if (targetContainer.querySelector(':scope > .fi-fp-footer')) {
    return;
  }

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

  if (clearLabel) {
    addButton(clearLabel, 'fi-fp-footer-btn-muted', () => {
      component.clear();
      fp.close();
    });
  }

  if (todayLabel) {
    // A picker with a time gets now, a month picker this month, and others the start of today: flatpickr compares
    // "now" with a disabled day to the second, so it would let today through.
    const today = () => {
      const now = new Date();

      if (fp.config.enableTime) {
        return now;
      }

      return component.kind === 'month'
        ? new Date(now.getFullYear(), now.getMonth(), 1)
        : new Date(now.getFullYear(), now.getMonth(), now.getDate());
    };

    const button = addButton(todayLabel, 'fi-fp-footer-btn-primary', () => {
      const date = today();

      if (!fp.isEnabled(date)) {
        return;
      }

      // Showing the month first keeps the week picker, which reads the day drawn as picked, on the right week.
      fp.jumpToDate(date);
      fp.setDate(component.kind === 'range' ? [date, date] : [date], true);
    });

    // Today can't be picked before minDate, after maxDate or on a disabled day. It is checked whenever the calendar
    // opens, as today moves on at midnight.
    const refresh = () => {
      button.disabled = !fp.isEnabled(today());
    };

    refresh();
    fp.config.onOpen.push(refresh);
  }

  if (applyLabel) {
    addButton(applyLabel, 'fi-fp-footer-btn-primary', () => {
      fp.close();
    });
  }

  targetContainer.appendChild(footer);
}
