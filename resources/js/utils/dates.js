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

/**
 * Now as the clock reads in a timezone, as a local date: the calendar draws local dates, so 09:00 in the timezone is
 * drawn as 09:00 wherever the user is. Now where the user is when there is no timezone, or the browser doesn't know it.
 */
export function nowIn(timezone) {
  const now = new Date();

  if (!timezone) {
    return now;
  }

  let parts;

  try {
    parts = new Intl.DateTimeFormat('en-US', {
      timeZone: timezone,
      hourCycle: 'h23',
      year: 'numeric',
      month: 'numeric',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric',
      second: 'numeric',
    }).formatToParts(now);
  } catch {
    return now;
  }

  const part = (type) => Number(parts.find((entry) => entry.type === type).value);

  return new Date(part('year'), part('month') - 1, part('day'), part('hour'), part('minute'), part('second'));
}
