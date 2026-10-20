import { afterEach, describe, expect, it } from 'vitest';
import { day, local, mount, settle, unmountAll, ymd } from './mount.js';

const twelveHourTime = {
  packageConfig: { dateFormat: 'h:i K', enableTime: true, noCalendar: true, time_24hr: false },
  attribs: { mode: 'time' },
};

const range = { packageConfig: { mode: 'range' }, attribs: { mode: 'range' } };

const button = (component, selector) => component.fp.calendarContainer.querySelector(selector);

const todayButton = (component) => button(component, '.fi-fp-footer-btn-primary');

afterEach(() => {
  unmountAll();
});

describe('state', () => {
  it('shows the state it starts with', () => {
    const component = mount({ state: '2024-01-15' });

    expect(local(component.fp.selectedDates[0])).toBe('2024-01-15 00:00:00');
  });

  it('writes a picked date to the state in the state format', () => {
    const component = mount({ locale: 'ar' });

    component.fp.setDate(new Date(2024, 0, 15), true);

    expect(component.state).toBe('2024-01-15');
  });

  it('keeps a 12-hour PM time in a locale with its own AM/PM', async () => {
    const component = mount({ ...twelveHourTime, state: '2:30 PM', locale: 'ar' });

    expect(component.fp.selectedDates[0].getHours()).toBe(14);

    const picked = new Date();

    picked.setHours(15, 45, 0, 0);
    component.fp.setDate(picked, true);
    await settle();

    expect(component.state).toBe('3:45 PM');
    expect(component.fp.selectedDates[0].getHours()).toBe(15);

    component.state = '2:30 PM';
    await settle();

    expect(component.fp.selectedDates[0].getHours()).toBe(14);
  });

  it('does not turn a typed day that does not exist into another day', () => {
    const component = mount({ packageConfig: { dateFormat: 'd/m/Y', allowInput: true } });

    component.fp.setDate('31/02/2024', true);

    expect(component.fp.selectedDates).toEqual([]);
    expect(component.state).toBeNull();

    component.fp.setDate('29/2/2024', true);

    expect(component.state).toBe('29/02/2024');
  });

  it('follows the state when the server changes it', async () => {
    const component = mount({ state: '2024-01-15' });

    component.state = '2024-03-10';
    await settle();

    expect(local(component.fp.selectedDates[0])).toBe('2024-03-10 00:00:00');

    component.state = null;
    await settle();

    expect(component.fp.selectedDates).toHaveLength(0);
  });

  it('does not read back the state it wrote itself', async () => {
    const component = mount({ packageConfig: { dateFormat: 'Y-m-d H:i:S', enableTime: true } });
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
    const component = mount({ ...range, state: ['2024-01-01', '2024-01-05'] });

    expect(component.fp.selectedDates.map(local)).toEqual(['2024-01-01 00:00:00', '2024-01-05 00:00:00']);

    component.fp.setDate([new Date(2024, 1, 1), new Date(2024, 1, 3)], true);
    await settle();

    expect(component.state).toEqual(['2024-02-01', '2024-02-03']);
    expect(component.fp.selectedDates).toHaveLength(2);
  });

  it('reads a month as the first of that month where the user is', () => {
    const component = mount({ state: '2024-02', packageConfig: { dateFormat: 'Y-m', altFormat: 'F Y' }, attribs: { monthSelect: true } });

    expect(local(component.fp.selectedDates[0])).toBe('2024-02-01 00:00:00');
  });

  it('empties the state when cleared', async () => {
    const component = mount({ state: '2024-01-15' });

    component.clear();
    await settle();

    expect(component.state).toBeNull();
    expect(component.fp.selectedDates).toHaveLength(0);
  });
});

describe('Today', () => {
  it('picks the start of today for a picker without a time', async () => {
    const component = mount({ attribs: { todayButton: true } });

    todayButton(component).click();
    await settle();

    expect(component.state).toBe(ymd(day()));
    expect(local(component.fp.selectedDates[0])).toBe(`${ymd(day())} 00:00:00`);
  });

  it('can not pick today when it is disabled, even with a time', async () => {
    for (const packageConfig of [{ disable: [ymd(day())] }, { disable: [ymd(day())], enableTime: true, dateFormat: 'Y-m-d H:i:S' }]) {
      const component = mount({ packageConfig, attribs: { todayButton: true } });

      expect(todayButton(component).disabled).toBe(true);

      todayButton(component).click();
      await settle();

      expect(component.state).toBeNull();
      expect(component.fp.selectedDates).toHaveLength(0);
    }
  });

  it('can not pick today before minDate or after maxDate', () => {
    expect(todayButton(mount({ packageConfig: { minDate: ymd(day(5)) }, attribs: { todayButton: true } })).disabled).toBe(true);
    expect(todayButton(mount({ packageConfig: { maxDate: ymd(day(-5)) }, attribs: { todayButton: true } })).disabled).toBe(true);
    expect(todayButton(mount({ packageConfig: { minDate: ymd(day()) }, attribs: { todayButton: true } })).disabled).toBe(false);
  });
});

describe('presets', () => {
  const lastSevenDays = { label: 'Last 7 days', start: 'last_7_days', end: 'today' };

  it('sets the state', () => {
    const component = mount({ attribs: { presets: [{ label: 'Launch', date: '2024-12-25' }] } });
    const preset = button(component, '.fi-fp-preset-btn');

    expect(preset.textContent).toBe('Launch');

    preset.click();

    expect(component.state).toBe('2024-12-25');
  });

  it('starts a range on minDate when it would start before it', () => {
    const component = mount({ ...range, packageConfig: { ...range.packageConfig, minDate: ymd(day(-3)) }, attribs: { ...range.attribs, presets: [lastSevenDays] } });

    button(component, '.fi-fp-preset-btn').click();

    expect(component.state).toEqual([ymd(day(-3)), ymd(day())]);
  });

  it('can not pick a preset the limits rule out', () => {
    const component = mount({
      ...range,
      packageConfig: { ...range.packageConfig, minDate: ymd(day(1)) },
      attribs: { ...range.attribs, presets: [lastSevenDays, { label: 'Next week', start: 'tomorrow', end: 'in_7_days' }] },
    });

    const [past, future] = component.fp.calendarContainer.querySelectorAll('.fi-fp-preset-btn');

    expect(past.disabled).toBe(true);
    expect(future.disabled).toBe(false);

    past.click();

    expect(component.state).toBeNull();
  });

  it('stops highlighting a preset once the dates change another way', () => {
    const component = mount({ attribs: { presets: [{ label: 'Today', date: 'today' }] } });
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
  it('marks the calendar for the Filament theme only', () => {
    expect(mount().fp.calendarContainer.classList.contains('fi-fp-calendar')).toBe(true);
    expect(mount({ attribs: { nativeTheme: false } }).fp.calendarContainer.classList.contains('fi-fp-calendar')).toBe(false);
  });

  it('draws an inline calendar under the field', () => {
    const component = mount({ packageConfig: { inline: true } });

    expect(component.fp.calendarContainer.parentElement).toBe(component.$refs.inlineCalendar);
  });

  it('adds footer buttons with their labels', async () => {
    const component = mount({
      state: '2024-01-15',
      attribs: { todayButton: true, clearButton: true, applyButton: true, labels: { today: 'اليوم', clear: 'مسح', apply: 'تطبيق' } },
    });

    const labels = [...component.fp.calendarContainer.querySelectorAll('.fi-fp-footer-btn')].map((item) => item.textContent);

    expect(labels).toEqual(['مسح', 'اليوم', 'تطبيق']);

    button(component, '.fi-fp-footer-btn-muted').click();
    await settle();

    expect(component.state).toBeNull();
  });

  it('turns off browser autofill on the field', () => {
    const component = mount();

    expect(component.fp.input.getAttribute('autocomplete')).toBe('off');
    expect(component.fp.input.getAttribute('data-1p-ignore')).toBe('true');
  });
});

describe('dark mode', () => {
  it('swaps a flatpickr theme to its dark stylesheet and back', async () => {
    const link = document.createElement('link');

    link.setAttribute('href', 'light.css');

    const component = mount({
      attribs: { nativeTheme: false, themeAsset: 'light.css', darkThemeAsset: 'dark.css' },
      themeStylesheet: link,
    });

    document.documentElement.classList.add('dark');
    await settle();

    expect(component.mode).toBe('dark');
    expect(link.getAttribute('href')).toBe('dark.css');

    document.documentElement.classList.remove('dark');
    await settle();

    expect(link.getAttribute('href')).toBe('light.css');
  });

  it('stops following the page once destroyed', async () => {
    const component = mount();

    component.destroy();
    document.documentElement.classList.add('dark');
    await settle();

    expect(component.mode).toBe('light');
  });
});
