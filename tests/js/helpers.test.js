import { afterEach, describe, expect, it, vi } from 'vitest';
import l10n from 'flatpickr/dist/esm/l10n/index.js';
import { formatSelectedDates, isListMode, parseMonthSelectDate } from '../../resources/js/utils/date-helpers.js';
import { resolveLocale } from '../../resources/js/utils/locale.js';
import { resolvePresetDate } from '../../resources/js/utils/presets.js';
import { local } from './mount.js';

describe('resolveLocale', () => {
  it('keeps a right-to-left range readable with a left-to-right mark before the second date', () => {
    const arabic = resolveLocale('ar');

    expect(arabic.months).toBe(l10n.ar.months);
    expect(arabic.rangeSeparator).toBe(`${l10n.ar.rangeSeparator}‎`);
    expect(resolveLocale('fr').rangeSeparator).toBe(l10n.fr.rangeSeparator);
  });

  it.each([
    ['ru', 'ru'],
    ['pt_BR', 'pt'],
    ['zh-TW', 'zh_tw'],
    ['zh_CN', 'zh'],
    ['el', 'gr'],
    ['vi', 'vn'],
    ['nb', 'no'],
    ['xx', 'default'],
    [null, 'default'],
  ])('gives flatpickr\'s locale for %s', (locale, expected) => {
    expect(resolveLocale(locale)).toBe(l10n[expected]);
  });

  it('uses a locale object as it is', () => {
    const custom = { firstDayOfWeek: 1 };

    expect(resolveLocale(custom)).toBe(custom);
  });
});

describe('date helpers', () => {
  it('writes the state in English whatever the format', () => {
    const date = new Date(2024, 1, 5, 14, 30);

    expect(formatSelectedDates([date], { dateFormat: 'h:i K', isList: false })).toBe('2:30 PM');
    expect(formatSelectedDates([date], { dateFormat: 'F j, Y', isList: false })).toBe('February 5, 2024');
    expect(formatSelectedDates([date, date], { dateFormat: 'Y-m-d', isList: true })).toEqual(['2024-02-05', '2024-02-05']);
  });

  it('knows which modes hold a list', () => {
    expect(isListMode('range')).toBe(true);
    expect(isListMode('multiple')).toBe(true);
    expect(isListMode('single')).toBe(false);
    expect(isListMode('time')).toBe(false);
  });

  it('reads a month as its first day where the user is', () => {
    expect(local(parseMonthSelectDate('2024-02', 'Y-m'))).toBe('2024-02-01 00:00:00');
    expect(parseMonthSelectDate('', 'Y-m')).toBeNull();
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

  it('reads a date given in the picker format, and skips one that is not a date', () => {
    const fp = { config: { dateFormat: 'd/m/Y' } };

    expect(local(resolvePresetDate('25/12/2024', fp))).toBe('2024-12-25 00:00:00');
    expect(resolvePresetDate('not a date', fp)).toBeNull();
    expect(resolvePresetDate('', fp)).toBeNull();
  });
});
