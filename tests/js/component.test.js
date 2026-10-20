import { afterEach, describe, expect, it } from 'vitest';
import { local, mount, unmountAll } from './mount.js';

const twelveHourTime = {
  packageConfig: { dateFormat: 'h:i K', enableTime: true, noCalendar: true, time_24hr: false },
  attribs: { mode: 'time' },
};

const nextTick = () => new Promise((resolve) => setTimeout(resolve, 0));

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

  it('keeps a 12-hour PM time in a locale with its own AM/PM', () => {
    const component = mount({ ...twelveHourTime, state: '2:30 PM', locale: 'ar' });

    expect(component.fp.selectedDates[0].getHours()).toBe(14);

    const picked = new Date();

    picked.setHours(15, 45, 0, 0);
    component.fp.setDate(picked, true);

    expect(component.state).toBe('3:45 PM');
    expect(component.fp.selectedDates[0].getHours()).toBe(15);
  });

  it('follows the state when the server changes it', () => {
    const component = mount({ state: '2024-01-15' });

    component.state = '2024-03-10';

    expect(local(component.fp.selectedDates[0])).toBe('2024-03-10 00:00:00');

    component.state = null;

    expect(component.fp.selectedDates).toHaveLength(0);
  });

  it('reads and writes a range as a list', () => {
    const component = mount({ state: ['2024-01-01', '2024-01-05'], packageConfig: { mode: 'range' }, attribs: { mode: 'range' } });

    expect(component.fp.selectedDates.map(local)).toEqual(['2024-01-01 00:00:00', '2024-01-05 00:00:00']);

    component.fp.setDate([new Date(2024, 1, 1), new Date(2024, 1, 3)], true);

    expect(component.state).toEqual(['2024-02-01', '2024-02-03']);
  });

  it('reads a month as the first of that month where the user is', () => {
    const component = mount({ state: '2024-02', packageConfig: { dateFormat: 'Y-m', altFormat: 'F Y' }, attribs: { monthSelect: true } });

    expect(local(component.fp.selectedDates[0])).toBe('2024-02-01 00:00:00');
  });

  it('empties the state when cleared', () => {
    const component = mount({ state: '2024-01-15' });

    component.clear();

    expect(component.state).toBeNull();
    expect(component.fp.selectedDates).toHaveLength(0);
  });
});

describe('calendar', () => {
  it('marks the calendar for the Filament theme only', () => {
    expect(mount().fp.calendarContainer.classList.contains('fi-fp-calendar')).toBe(true);
    expect(mount({ attribs: { nativeTheme: false } }).fp.calendarContainer.classList.contains('fi-fp-calendar')).toBe(false);
  });

  it('adds presets that set the state', () => {
    const component = mount({ attribs: { presets: [{ label: 'Launch', date: '2024-12-25' }] } });
    const button = component.fp.calendarContainer.querySelector('.fi-fp-preset-btn');

    expect(button.textContent).toBe('Launch');

    button.click();

    expect(component.state).toBe('2024-12-25');
  });

  it('adds footer buttons with their labels', () => {
    const component = mount({
      state: '2024-01-15',
      attribs: { todayButton: true, clearButton: true, applyButton: true, labels: { today: 'اليوم', clear: 'مسح', apply: 'تطبيق' } },
    });

    const labels = [...component.fp.calendarContainer.querySelectorAll('.fi-fp-footer-btn')].map((button) => button.textContent);

    expect(labels).toEqual(['مسح', 'اليوم', 'تطبيق']);

    component.fp.calendarContainer.querySelector('.fi-fp-footer-btn-muted').click();

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
    await nextTick();

    expect(component.mode).toBe('dark');
    expect(link.getAttribute('href')).toBe('dark.css');

    document.documentElement.classList.remove('dark');
    await nextTick();

    expect(link.getAttribute('href')).toBe('light.css');
  });

  it('stops following the page once destroyed', async () => {
    const component = mount();

    component.destroy();
    document.documentElement.classList.add('dark');
    await nextTick();

    expect(component.mode).toBe('light');
  });
});
