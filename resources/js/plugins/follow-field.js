/**
 * Keeps an open calendar beside its field. Flatpickr only places it again when the window is resized, so it stays
 * behind when the field moves another way: a modal or slide-over scrolling or sliding in, or the form changing above
 * it. While the calendar is open, each frame checks where the field is on the page, and how tall the calendar is, as
 * one above the field moves when a month needs another row.
 */
export function followFieldPlugin() {
  return (fp) => {
    let frame = null;
    let placedAt = null;

    const position = () => {
      const field = fp.altInput ?? fp.input;
      const { top, left, width, height } = field.getBoundingClientRect();

      // Where the page is scrolled doesn't count: the calendar is placed on the page, so it scrolls with the field.
      return [top + window.scrollY, left + window.scrollX, width, height, fp.calendarContainer?.offsetHeight].join();
    };

    const follow = () => {
      const current = position();

      if (placedAt !== null && current !== placedAt) {
        // Flatpickr has no public way to place the calendar again.
        fp._positionCalendar();
      }

      placedAt = current;
      frame = requestAnimationFrame(follow);
    };

    const stop = () => {
      if (frame !== null) {
        cancelAnimationFrame(frame);
      }

      frame = null;
      placedAt = null;
    };

    return {
      onOpen() {
        stop();
        follow();
      },

      onClose: stop,

      onDestroy: stop,
    };
  };
}
