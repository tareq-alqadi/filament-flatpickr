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
  };

  if (clearButton) {
    addButton(labels.clear ?? 'Clear', 'fi-fp-footer-btn-muted', () => {
      component.clear();
      fp.close();
    });
  }

  if (todayButton) {
    addButton(labels.today ?? 'Today', 'fi-fp-footer-btn-primary', () => {
      const today = new Date();
      const dates = component.attribs.mode === 'range' ? [today, today] : [today];

      fp.setDate(dates, true);
      fp.jumpToDate(today);
    });
  }

  if (applyButton) {
    addButton(labels.apply ?? 'Apply', 'fi-fp-footer-btn-primary', () => {
      fp.close();
    });
  }

  targetContainer.appendChild(footer);
}
