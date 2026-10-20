/**
 * Ensures the calendar's main content area (.flatpickr-months, .flatpickr-innerContainer, .flatpickr-time)
 * is wrapped in a flex-column container (.fi-fp-main) so a presets sidebar can sit cleanly beside it.
 */
export function ensureCalendarMainWrapper(fp) {
  if (!fp?.calendarContainer) {
    return null;
  }

  let main = fp.calendarContainer.querySelector(':scope > .fi-fp-main');

  if (main) {
    return main;
  }

  main = document.createElement('div');
  main.className = 'fi-fp-main';

  const children = Array.from(fp.calendarContainer.childNodes);

  children.forEach((child) => {
    if (
      child.nodeType === 1 &&
      (child.classList.contains('fi-fp-presets') || child.classList.contains('fi-fp-footer'))
    ) {
      return;
    }

    main.appendChild(child);
  });

  fp.calendarContainer.appendChild(main);

  return main;
}
