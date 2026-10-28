/**
 * Brings the month picker's grid up to the calendar's year and selection. The monthSelect plugin only does this when
 * the user picks or changes year, so a date set without a change event, as a state from the server is, leaves the grid
 * on the year it was drawn in, with no month marked. Limits and today follow the plugin's rules.
 */
export function showPickedMonth(fp) {
  const months = fp.rContainer?.querySelectorAll('.flatpickr-monthSelect-month') ?? [];
  const picked = fp.selectedDates[0];
  const today = new Date();
  const { minDate, maxDate } = fp.config;

  months.forEach((month) => {
    month.dateObj.setFullYear(fp.currentYear);

    const date = month.dateObj;
    const sameMonth = (other) => other?.getFullYear() === date.getFullYear() && other.getMonth() === date.getMonth();

    month.classList.toggle('flatpickr-disabled', Boolean((minDate && date < minDate) || (maxDate && date > maxDate)));
    month.classList.toggle('today', sameMonth(today));
    month.classList.toggle('selected', sameMonth(picked));
  });
}
