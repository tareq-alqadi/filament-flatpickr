import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import arabic from '../../resources/dist/locales/ar.json';
import french from '../../resources/dist/locales/fr.json';
import { day, local, mount, settle, unmountAll, ymd } from './mount.js';

const button = (component, selector) => component.fp.calendarContainer.querySelector(selector);

const todayButton = (component) => button(component, '.fi-fp-footer-btn-primary');

/**
 * Text typed in the field, read as flatpickr reads it when the field loses focus.
 */
const type = (component, text) => component.fp.setDate(text, true, component.fp.config.altFormat);

/**
 * A day clicked in the calendar: flatpickr shows its month, then picks it.
 */
const pick = (component, date) => {
  component.fp.jumpToDate(date);
  component.fp.setDate(date, true);
};

afterEach(() => {
  unmountAll();
});

describe('state', () => {
  it('shows the state it starts with in the display format', () => {
    const component = mount({ state: '2024-01-15' });

    expect(local(component.fp.selectedDates[0])).toBe('2024-01-15 00:00:00');
    expect(component.fp.altInput.value).toBe('January 15, 2024');
  });

  it('shows a state the calendar can not read as it is, instead of an empty field', async () => {
    const component = mount({ kind: 'datetime', state: '2024-02-31 10:00:00' });

    expect(component.fp.selectedDates).toHaveLength(0);
    expect(component.fp.altInput.value).toBe('2024-02-31 10:00:00');
    expect(component.state).toBe('2024-02-31 10:00:00');

    component.state = '2024-01-15 10:00:00';
    await settle();

    expect(component.fp.altInput.value).toBe('January 15, 2024 10:00');

    component.state = null;
    await settle();

    expect(component.fp.altInput.value).toBe('');
  });

  it('writes a picked date in the state format whatever the locale', () => {
    const component = mount({ locale: arabic, config: { altFormat: 'j F Y' } });

    component.fp.setDate(new Date(2024, 1, 5), true);

    expect(component.state).toBe('2024-02-05');
    expect(component.fp.altInput.value).toBe('5 فبراير 2024');
  });

  it('keeps a PM time on a 12-hour clock in a locale with its own AM/PM', async () => {
    const component = mount({ kind: 'time', state: '14:30:00', locale: arabic, config: { altFormat: 'h:i K', time_24hr: false } });

    expect(component.fp.selectedDates[0].getHours()).toBe(14);
    expect(component.fp.altInput.value).toBe('2:30 م');

    const picked = new Date();

    picked.setHours(15, 45, 0, 0);
    component.fp.setDate(picked, true);
    await settle();

    expect(component.state).toBe('15:45:00');
  });

  it('reads text typed in the display format and locale', () => {
    const component = mount({ locale: arabic, config: { altFormat: 'j F Y', allowInput: true } });

    type(component, '5 فبراير 2024');

    expect(component.state).toBe('2024-02-05');
  });

  it('reads typed month names in its own locale beside a picker in another', () => {
    const component = mount({ locale: arabic, config: { altFormat: 'j F Y', allowInput: true } });

    // The last picker created fills flatpickr's shared table of month names with French.
    mount({ locale: french, config: { altFormat: 'j F Y', allowInput: true } });

    type(component, '5 فبراير 2024');

    expect(component.state).toBe('2024-02-05');
  });

  it('does not turn a typed day that does not exist into another day', () => {
    const component = mount({ config: { altFormat: 'd/m/Y', allowInput: true } });

    type(component, '31/02/2024');

    expect(component.fp.selectedDates).toEqual([]);
    expect(component.state).toBeNull();

    type(component, '29/2/2024');

    expect(component.state).toBe('2024-02-29');
  });

  it('follows the state when the server changes it', async () => {
    const component = mount({ state: '2024-01-15' });

    component.state = '2024-03-10';
    await settle();

    expect(local(component.fp.selectedDates[0])).toBe('2024-03-10 00:00:00');

    component.state = null;
    await settle();

    expect(component.fp.selectedDates).toHaveLength(0);
    expect(component.fp.altInput.value).toBe('');
  });

  it('shows nothing for a state that is not in the state format', async () => {
    const component = mount({ state: '15/01/2024' });

    expect(component.fp.selectedDates).toHaveLength(0);

    component.state = '2024-02-31';
    await settle();

    expect(component.fp.selectedDates).toHaveLength(0);
  });

  it('does not read back the state it wrote itself', async () => {
    const component = mount({ kind: 'datetime', config: { enableSeconds: true } });
    const setDate = component.fp.setDate;
    let readBack = 0;

    component.fp.setDate = (...args) => {
      readBack++;

      return setDate(...args);
    };

    setDate(new Date(2024, 0, 15, 9, 30, 15), true);
    await settle();

    expect(component.state).toBe('2024-01-15 09:30:15');
    expect(readBack).toBe(0);
  });

  it('reads and writes a range as a list', async () => {
    const component = mount({ kind: 'range', state: ['2024-01-01', '2024-01-05'] });

    expect(component.fp.selectedDates.map(local)).toEqual(['2024-01-01 00:00:00', '2024-01-05 00:00:00']);

    component.fp.setDate([new Date(2024, 1, 1), new Date(2024, 1, 3)], true);
    await settle();

    expect(component.state).toEqual(['2024-02-01', '2024-02-03']);
    expect(component.fp.selectedDates).toHaveLength(2);
  });

  it('keeps an Arabic range readable', () => {
    const component = mount({ kind: 'range', locale: arabic, config: { altFormat: 'Y-m-d' }, state: ['2024-01-01', '2024-01-05'] });

    // The mark is the first strong character before the second date, so its digits stay left to right.
    expect(component.fp.altInput.value).toBe('2024-01-01 إلى ‎2024-01-05');
  });

  it('reads and writes a month as its first day', async () => {
    const component = mount({ kind: 'month', state: '2024-02-01' });

    expect(local(component.fp.selectedDates[0])).toBe('2024-02-01 00:00:00');
    expect(component.fp.altInput.value).toBe('February 2024');

    component.fp.setDate(new Date(2024, 2, 1), true);
    await settle();

    expect(component.state).toBe('2024-03-01');
  });

  it('writes a week as its first day and shows its middle', async () => {
    for (const [firstDayOfWeek, start, middle] of [[0, '2024-01-14', '2024-01-17'], [1, '2024-01-15', '2024-01-18']]) {
      const component = mount({ kind: 'week', firstDayOfWeek });

      pick(component, new Date(2024, 0, 20));
      await settle();

      expect(component.state).toBe(start);
      expect(ymd(component.fp.selectedDates[0])).toBe(middle);
      expect(component.fp.altInput.value).toBe('Week 3, 2024');
    }
  });

  it('shows a week the server set on a day the limits allow', () => {
    expect(ymd(mount({ kind: 'week', state: '2024-01-14' }).fp.selectedDates[0])).toBe('2024-01-17');
    expect(ymd(mount({ kind: 'week', state: '2024-01-14', config: { minDate: '2024-01-19' } }).fp.selectedDates[0])).toBe('2024-01-19');
  });

  it('empties the state when cleared', async () => {
    const component = mount({ state: '2024-01-15' });

    component.clear();
    await settle();

    expect(component.state).toBeNull();
    expect(component.hasValue).toBe(false);
    expect(component.fp.selectedDates).toHaveLength(0);
  });
});

describe('Today', () => {
  it('picks the start of today for a picker without a time', async () => {
    const component = mount({ footerActions: { today: 'Today' } });

    todayButton(component).click();
    await settle();

    expect(component.state).toBe(ymd(day()));
    expect(local(component.fp.selectedDates[0])).toBe(`${ymd(day())} 00:00:00`);
  });

  it('picks this month for a month picker and a range of today for a range picker', async () => {
    const month = mount({ kind: 'month', footerActions: { today: 'Today' } });
    const range = mount({ kind: 'range', footerActions: { today: 'Today' } });

    todayButton(month).click();
    todayButton(range).click();
    await settle();

    expect(month.state).toBe(`${ymd(day()).slice(0, 7)}-01`);
    expect(range.state).toEqual([ymd(day()), ymd(day())]);
  });

  it('can not pick today when it is disabled, even with a time', async () => {
    for (const kind of ['date', 'datetime']) {
      const component = mount({ kind, config: { disable: [ymd(day())] }, footerActions: { today: 'Today' } });

      expect(todayButton(component).disabled).toBe(true);

      todayButton(component).click();
      await settle();

      expect(component.state).toBeNull();
      expect(component.fp.selectedDates).toHaveLength(0);
    }
  });

  it("picks now in the timezone of the state, not the user's", async () => {
    vi.useFakeTimers({ now: Date.UTC(2026, 9, 21, 22, 34, 31), toFake: ['Date'] });

    try {
      // A day apart, so one of them is not where the user is.
      const ahead = mount({ kind: 'datetime', timezone: 'Pacific/Kiritimati', footerActions: { today: 'Today' } });
      const behind = mount({ kind: 'datetime', timezone: 'Pacific/Honolulu', footerActions: { today: 'Today' } });
      const date = mount({ timezone: 'Pacific/Kiritimati', footerActions: { today: 'Today' } });

      todayButton(ahead).click();
      todayButton(behind).click();
      todayButton(date).click();
      await settle();

      expect(ahead.state).toBe('2026-10-22 12:34:31');
      expect(behind.state).toBe('2026-10-21 12:34:31');
      expect(date.state).toBe('2026-10-22');
    } finally {
      vi.useRealTimers();
    }
  });

  it('can not pick today before minDate or after maxDate', () => {
    const footerActions = { today: 'Today' };

    expect(todayButton(mount({ config: { minDate: ymd(day(5)) }, footerActions })).disabled).toBe(true);
    expect(todayButton(mount({ config: { maxDate: ymd(day(-5)) }, footerActions })).disabled).toBe(true);
    expect(todayButton(mount({ config: { minDate: ymd(day()) }, footerActions })).disabled).toBe(false);
  });
});

describe('presets', () => {
  const lastSevenDays = { label: 'Last 7 days', start: 'last_7_days', end: 'today' };

  it('sets the state', () => {
    const component = mount({ presets: [{ label: 'Launch', date: '2024-12-25' }] });
    const preset = button(component, '.fi-fp-preset-btn');

    expect(preset.textContent).toBe('Launch');

    preset.click();

    expect(component.state).toBe('2024-12-25');
  });

  it('starts a range on minDate when it would start before it', () => {
    const component = mount({ kind: 'range', config: { minDate: ymd(day(-3)) }, presets: [lastSevenDays] });

    button(component, '.fi-fp-preset-btn').click();

    expect(component.state).toEqual([ymd(day(-3)), ymd(day())]);
  });

  it('can not pick a preset the limits rule out', () => {
    const component = mount({
      kind: 'range',
      config: { minDate: ymd(day(1)) },
      presets: [lastSevenDays, { label: 'Next week', start: 'tomorrow', end: 'in_7_days' }],
    });

    const [past, future] = component.fp.calendarContainer.querySelectorAll('.fi-fp-preset-btn');

    expect(past.disabled).toBe(true);
    expect(future.disabled).toBe(false);

    past.click();

    expect(component.state).toBeNull();
  });

  it('stops highlighting a preset once the dates change another way', () => {
    const component = mount({ presets: [{ label: 'Today', date: 'today' }] });
    const preset = button(component, '.fi-fp-preset-btn');

    preset.click();

    expect(preset.classList.contains('fi-fp-preset-active')).toBe(true);

    component.fp.setDate(new Date(2024, 0, 3), true);

    expect(preset.classList.contains('fi-fp-preset-active')).toBe(false);

    preset.click();
    component.clear();

    expect(preset.classList.contains('fi-fp-preset-active')).toBe(false);
  });
});

describe('calendar', () => {
  it('marks the calendar for the Filament theme', () => {
    expect(mount().fp.calendarContainer.classList.contains('fi-fp-calendar')).toBe(true);
  });

  it('draws an inline calendar under the field', () => {
    const component = mount({ config: { inline: true } });

    expect(component.fp.calendarContainer.parentElement).toBe(component.$refs.inlineCalendar);
  });

  it('starts the week on the day the field sets', () => {
    expect(mount({ locale: arabic }).fp.l10n.firstDayOfWeek).toBe(arabic.firstDayOfWeek ?? 0);
    expect(mount({ locale: arabic, firstDayOfWeek: 1 }).fp.l10n.firstDayOfWeek).toBe(1);
  });

  it('adds footer buttons with their labels', async () => {
    const component = mount({ state: '2024-01-15', footerActions: { today: 'اليوم', clear: 'مسح', apply: 'تطبيق' } });

    const labels = [...component.fp.calendarContainer.querySelectorAll('.fi-fp-footer-btn')].map((item) => item.textContent);

    expect(labels).toEqual(['مسح', 'اليوم', 'تطبيق']);

    button(component, '.fi-fp-footer-btn-muted').click();
    await settle();

    expect(component.state).toBeNull();
  });

  it('turns off browser autofill on the field', () => {
    const component = mount();

    expect(component.fp.altInput.getAttribute('autocomplete')).toBe('off');
    expect(component.fp.altInput.getAttribute('data-1p-ignore')).toBe('true');
  });

  it('draws the footer of a month picker under its months', () => {
    const component = mount({ kind: 'month', footerActions: { today: 'Today' } });
    const main = component.fp.calendarContainer.querySelector(':scope > .fi-fp-main');

    expect(main.querySelector('.flatpickr-monthSelect-months')).not.toBeNull();
    expect(main.lastElementChild.classList.contains('fi-fp-footer')).toBe(true);
  });
});

describe('field', () => {
  it('empties the state when its text is deleted', () => {
    const component = mount({ state: '2024-01-15', config: { allowInput: true } });

    component.fp.altInput.value = '';
    component.fp.altInput.dispatchEvent(new Event('input'));

    expect(component.state).toBeNull();
  });

  describe('an open calendar', () => {
    let top;

    const open = (options) => {
      const component = mount(options);

      vi.spyOn(component.fp.altInput, 'getBoundingClientRect').mockImplementation(() => ({ top, left: 0, width: 200, height: 40 }));
      component.fp.open();

      return Object.assign(component, { place: vi.spyOn(component.fp, '_positionCalendar') });
    };

    beforeEach(() => {
      top = 100;
      vi.useFakeTimers({ toFake: ['requestAnimationFrame', 'cancelAnimationFrame'] });
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('follows its field when the field moves, as in a modal that scrolls', () => {
      const component = open();

      vi.advanceTimersToNextFrame();
      expect(component.place).not.toHaveBeenCalled();

      top = 60;
      vi.advanceTimersToNextFrame();
      expect(component.place).toHaveBeenCalledTimes(1);

      vi.advanceTimersToNextFrame();
      expect(component.place).toHaveBeenCalledTimes(1);
    });

    it('stops following when it closes or the field is removed', () => {
      const closed = open();
      const removed = open();

      closed.fp.close();
      removed.destroy();
      top = 60;
      vi.advanceTimersToNextFrame();

      expect(closed.place).not.toHaveBeenCalled();
      expect(removed.place).not.toHaveBeenCalled();
      expect(vi.getTimerCount()).toBe(0);
    });

    it('stays where flatpickr puts an inline or static calendar', () => {
      open({ config: { static: true } });

      expect(vi.getTimerCount()).toBe(0);
    });
  });
});
