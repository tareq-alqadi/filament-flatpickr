/**
 * Local dates, as the calendar draws them. A day is its midnight where the user is.
 */
export function isDate(value) {
  return value instanceof Date && !Number.isNaN(value.getTime());
}

export function startOfDay(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

export function startOfMonth(date) {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

export function addDays(date, days) {
  const next = new Date(date);

  next.setDate(next.getDate() + days);

  return next;
}

/**
 * The first day of the week a date is in, as the calendar's rows show it.
 */
export function startOfWeek(date, firstDayOfWeek = 0) {
  const start = startOfDay(date);

  return addDays(start, -((start.getDay() - firstDayOfWeek + 7) % 7));
}
