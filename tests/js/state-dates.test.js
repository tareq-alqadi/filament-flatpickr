import { describe, expect, it } from 'vitest';
import { tokenRegex } from 'flatpickr/dist/esm/utils/formatting';
import { Arabic } from 'flatpickr/dist/esm/l10n/ar';
import { parseStateDate, parseStateDates } from '../../resources/js/utils/state-dates.js';
import { local } from './mount.js';

describe('parseStateDate', () => {
  // What an Arabic picker leaves in flatpickr's shared table of patterns.
  const withArabicPatterns = (callback) => {
    const original = { ...tokenRegex };

    tokenRegex.K = `(${Arabic.amPM.join('|')})`;
    tokenRegex.F = `(${Arabic.months.longhand.join('|')})`;

    try {
      return callback();
    } finally {
      Object.assign(tokenRegex, original);
    }
  };

  it('reads English AM/PM and month names while a picker uses another locale', () => {
    withArabicPatterns(() => {
      expect(local(parseStateDate('2:30 PM', 'h:i K', { noCalendar: true })).slice(11)).toBe('14:30:00');
      expect(local(parseStateDate('2:30:15 PM', 'h:i:S K', { noCalendar: true })).slice(11)).toBe('14:30:15');
      expect(local(parseStateDate('2024-02-05 2:30 PM', 'Y-m-d h:i K'))).toBe('2024-02-05 14:30:00');
      expect(local(parseStateDate('February 2024', 'F Y'))).toBe('2024-02-01 00:00:00');
    });
  });

  it('leaves the picker locale patterns as they were', () => {
    withArabicPatterns(() => {
      const before = { ...tokenRegex };

      parseStateDate('2:30 PM', 'h:i K');

      expect(tokenRegex).toEqual(before);
    });
  });

  it('reads a month as its first day where the user is', () => {
    expect(local(parseStateDate('2024-02', 'Y-m'))).toBe('2024-02-01 00:00:00');
  });

  it('gives null for nothing or text that is not a date', () => {
    expect(parseStateDate(null, 'Y-m-d')).toBeNull();
    expect(parseStateDate('', 'Y-m-d')).toBeNull();
    expect(parseStateDate('not a date', 'Y-m-d')).toBeNull();
    expect(parseStateDate(new Date('nope'), 'Y-m-d')).toBeNull();
  });

  it('passes a date through', () => {
    const date = new Date(2024, 0, 15);

    expect(parseStateDate(date, 'Y-m-d')).toBe(date);
  });
});

describe('parseStateDates', () => {
  it('reads a list, or gives null when one entry is not a date', () => {
    expect(parseStateDates(['2024-01-01', '2024-01-05'], 'Y-m-d').map(local))
      .toEqual(['2024-01-01 00:00:00', '2024-01-05 00:00:00']);
    expect(parseStateDates(['2024-01-01', 'nope'], 'Y-m-d')).toBeNull();
    expect(parseStateDates([], 'Y-m-d')).toBeNull();
  });
});
