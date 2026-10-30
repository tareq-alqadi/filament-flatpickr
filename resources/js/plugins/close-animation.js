/**
 * Lets the calendar animate out. Flatpickr hides it the moment it closes, so this keeps it shown, marked
 * .fi-fp-closing, until the theme's closing animation ends. Opening it again first takes it straight back.
 */
export function closeAnimationPlugin() {
  return (fp) => {
    let finish = null;

    const settle = () => {
      finish?.();
    };

    return {
      onOpen() {
        settle();
      },

      onClose() {
        const calendar = fp.calendarContainer;

        if (!fp.config.animate || fp.config.inline || !calendar) {
          return;
        }

        settle();

        // The calendar keeps .open, so it stays shown and placed, and .fi-fp-closing swaps its animation for the
        // closing one.
        calendar.classList.add('open', 'fi-fp-closing');

        const onEnd = (event) => {
          if (event.target === calendar) {
            settle();
          }
        };

        // In case no animation runs, such as when the stylesheet hasn't loaded.
        const timeout = setTimeout(settle, 400);

        calendar.addEventListener('animationend', onEnd);

        finish = () => {
          finish = null;
          clearTimeout(timeout);
          calendar.removeEventListener('animationend', onEnd);
          calendar.classList.remove('fi-fp-closing');

          if (!fp.isOpen) {
            calendar.classList.remove('open');
          }
        };
      },

      onDestroy() {
        settle();
      },
    };
  };
}
