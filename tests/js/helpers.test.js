import { afterEach, describe, expect, it, vi } from 'vitest';
import arabic from '../../resources/dist/locales/ar.json';
import french from '../../resources/dist/locales/fr.json';
import { resolveLocale } from '../../resources/js/utils/locale.js';
import { resolvePresetDate } from '../../resources/js/plugins/presets.js';
import { nowIn, startOfWeek } from '../../resources/js/utils/dates.js';
import { datesFromState, isList } from '../../resources/js/utils/state.js';
import { createStrictDateParser } from '../../resources/js/utils/strict-parser.js';
import { local } from './mount.js';

describe('resolveLocale', () => {
  it('gives English for no locale', () => {
    expect(resolveLocale(null)).toEqual({});
  });

  it('turns the day suffixes back into the function flatpickr calls', () => {
    const locale = resolveLocale(french);

    expect(locale.ordinal(1)).toBe('er');
    expect(locale.ordinal(2)).toBe('');
    expect(locale).not.toHaveProperty('ordinals');
  });

  it('starts the week on the day the field sets', () => {
    expect(resolveLocale(french, { firstDayOfWeek: 0 }).firstDayOfWeek).toBe(0);
    expect(resolveLocale(french).firstDayOfWeek).toBe(1);
  });

  it('keeps a right-to-left range readable with a left-to-right mark before the second date', () => {
    expect(resolveLocale(arabic).rangeSeparator).toBe(`${arabic.rangeSeparator}‎`);
    expect(resolveLocale(french).rangeSeparator).toBe(french.rangeSeparator);
  });
});

describe('strict parser', () => {
  const parse = createStrictDateParser();

  it('gives nothing for a date flatpickr would roll over', () => {
    expect(parse('31/02/2024', 'd/m/Y')).toBeUndefined();
    expect(parse('12/31/2024', 'd/m/Y')).toBeUndefined();
    expect(parse('2024-13-01', 'Y-m-d')).toBeUndefined();
    expect(parse('2024-01-01 25:00:00', 'Y-m-d H:i:S')).toBeUndefined();
  });

  it('reads a date without leading zeros or in another letter case', () => {
    expect(local(parse('5/3/2024', 'd/m/Y'))).toBe('2024-03-05 00:00:00');
    expect(local(parse('29/02/2024', 'd/m/Y'))).toBe('2024-02-29 00:00:00');
    expect(local(createStrictDateParser({ noCalendar: true })('2:30 pm', 'h:i K')).slice(11)).toBe('14:30:00');
  });

  it('reads a date that stops before the time of the format', () => {
    expect(local(parse('2024-01-15', 'Y-m-d H:i'))).toBe('2024-01-15 00:00:00');
    expect(parse('2024-02-30', 'Y-m-d H:i')).toBeUndefined();
  });

  it('reads month names of its locale', () => {
    expect(local(createStrictDateParser({ l10n: { ...resolveLocale(french) } })('5 février 2024', 'j F Y'))).toBe('2024-02-05 00:00:00');
  });
});

describe('state', () => {
  const fp = {
    config: { dateFormat: 'Y-m-d' },
    l10n: { firstDayOfWeek: 0 },
    parseDate: createStrictDateParser(),
    isEnabled: (date) => date.getDate() !== 17,
  };

  it('knows which kinds hold a list', () => {
    expect(isList('range')).toBe(true);
    expect(isList('multiple')).toBe(true);
    expect(isList('date')).toBe(false);
    expect(isList('week')).toBe(false);
  });

  it('reads the dates of a state, and nothing for an entry that is not one', () => {
    expect(datesFromState(['2024-01-01', '2024-01-05'], fp, 'range').map(local)).toEqual(['2024-01-01 00:00:00', '2024-01-05 00:00:00']);
    expect(datesFromState(null, fp, 'date')).toEqual([]);
    expect(datesFromState([], fp, 'range')).toEqual([]);
    expect(datesFromState(['2024-01-01', 'nope'], fp, 'range')).toBeNull();
    expect(datesFromState(20240101, fp, 'date')).toBeNull();
  });

  it('selects the middle of a week, or the first day of it the limits allow', () => {
    expect(datesFromState('2024-01-07', fp, 'week').map(local)).toEqual(['2024-01-10 00:00:00']);
    // 17 January, the middle of the week starting on the 14th, is disabled.
    expect(datesFromState('2024-01-14', fp, 'week').map(local)).toEqual(['2024-01-14 00:00:00']);
  });

  it('finds the first day of a week', () => {
    expect(local(startOfWeek(new Date(2024, 0, 17, 15, 0), 0))).toBe('2024-01-14 00:00:00');
    expect(local(startOfWeek(new Date(2024, 0, 17), 1))).toBe('2024-01-15 00:00:00');
    expect(local(startOfWeek(new Date(2024, 0, 14), 1))).toBe('2024-01-08 00:00:00');
  });
});

describe('resolvePresetDate', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it.each([
    ['today', '2024-01-31'],
    ['tomorrow', '2024-02-01'],
    ['yesterday', '2024-01-30'],
    ['in_7_days', '2024-02-07'],
    ['last_7_days', '2024-01-25'],
    ['last_30_days', '2024-01-02'],
    ['next_month', '2024-02-29'],
    ['start_of_month', '2024-01-01'],
    ['end_of_month', '2024-01-31'],
    ['start_of_last_month', '2023-12-01'],
    ['end_of_last_month', '2023-12-31'],
    ['start_of_year', '2024-01-01'],
    ['end_of_year', '2024-12-31'],
  ])('resolves %s on 31 January 2024', (token, expected) => {
    vi.useFakeTimers({ now: new Date(2024, 0, 31, 15, 0), toFake: ['Date'] });

    expect(local(resolvePresetDate(token, null))).toBe(`${expected} 00:00:00`);
  });

  it('reads a date in the state format, and skips one that is not a date', () => {
    const fp = { config: { dateFormat: 'Y-m-d' }, parseDate: createStrictDateParser() };

    expect(local(resolvePresetDate('2024-12-25', fp))).toBe('2024-12-25 00:00:00');
    expect(resolvePresetDate('not a date', fp)).toBeNull();
    expect(resolvePresetDate('', fp)).toBeNull();
  });
});

describe('nowIn', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it('reads the clock of a timezone as a local date', () => {
    vi.useFakeTimers({ now: Date.UTC(2026, 9, 21, 6, 34, 31), toFake: ['Date'] });

    expect(local(nowIn('Asia/Hebron'))).toBe('2026-10-21 09:34:31');
    expect(local(nowIn('UTC'))).toBe('2026-10-21 06:34:31');
    expect(local(nowIn('America/New_York'))).toBe('2026-10-21 02:34:31');
  });

  it("is the user's clock without a timezone, or for one the browser does not know", () => {
    vi.useFakeTimers({ now: Date.UTC(2026, 9, 21, 6, 34, 31), toFake: ['Date'] });

    expect(nowIn(null).getTime()).toBe(Date.now());
    expect(nowIn('Nowhere/Atlantis').getTime()).toBe(Date.now());
  });
});

it('resolves preset tokens from the day now is on', () => {
  expect(local(resolvePresetDate('today', null, new Date(2024, 0, 31, 23, 0)))).toBe('2024-01-31 00:00:00');
  expect(local(resolvePresetDate('tomorrow', null, new Date(2024, 0, 31, 23, 0)))).toBe('2024-02-01 00:00:00');
});
