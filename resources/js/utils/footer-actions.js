import { ensureCalendarMainWrapper } from './calendar-layout.js';

/**
 * Adds a footer with Today, Clear and Apply shortcuts to the calendar.
 */
export function attachFooterActions(fp, component) {
  const { todayButton, clearButton, applyButton, labels = {} } = component.attribs;

  if ((!todayButton && !clearButton && !applyButton) || !fp.calendarContainer) {
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

  if (clearButton) {
    addButton(labels.clear ?? 'Clear', 'fi-fp-footer-btn-muted', () => {
      component.clear();
      fp.close();
    });
  }

  if (todayButton) {
    // A picker without a time gets the start of today: flatpickr compares "now" with a disabled day to the second,
    // so it would let today through.
    const today = () => {
      const now = new Date();

      return fp.config.enableTime ? now : new Date(now.getFullYear(), now.getMonth(), now.getDate());
    };

    const button = addButton(labels.today ?? 'Today', 'fi-fp-footer-btn-primary', () => {
      const date = today();

      if (!fp.isEnabled(date)) {
        return;
      }

      fp.setDate(component.attribs.mode === 'range' ? [date, date] : [date], true);
      fp.jumpToDate(date);
    });

    // Today can't be picked before minDate, after maxDate or on a disabled day. It is checked whenever the calendar
    // opens, as today moves on at midnight.
    const refresh = () => {
      button.disabled = !fp.isEnabled(today());
    };

    refresh();
    fp.config.onOpen.push(refresh);
  }

  if (applyButton) {
    addButton(labels.apply ?? 'Apply', 'fi-fp-footer-btn-primary', () => {
      fp.close();
    });
  }

  targetContainer.appendChild(footer);
}
