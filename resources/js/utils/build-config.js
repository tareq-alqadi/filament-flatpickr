import MonthSelect from 'flatpickr/dist/esm/plugins/monthSelect';
import WeekSelect from 'flatpickr/dist/esm/plugins/weekSelect/weekSelect';
import { browserHintsPlugin } from '../plugins/browser-hints.js';
import { followFieldPlugin } from '../plugins/follow-field.js';
import { footerActionsPlugin } from '../plugins/footer-actions.js';
import { presetsPlugin } from '../plugins/presets.js';
import { textDeletedPlugin } from '../plugins/text-deleted.js';
import { resolveLocale } from './locale.js';
import { datesFromState, sameDates, stateFromDates } from './state.js';
import { createStrictDateParser } from './strict-parser.js';

/**
 * The options flatpickr is created with: the field's, which the server sends as JSON, the plugins for what the field
 * adds to the calendar, and the hook that writes the picked dates to the state.
 */
export function buildFlatpickrConfig(component, { locale, firstDayOfWeek }) {
  const { kind, config: options } = component;
  const l10n = resolveLocale(options.locale ?? locale, { firstDayOfWeek });

  // In the order their hooks run. The month and week plugins draw the calendar the others add to.
  const plugins = [];

  if (kind === 'month') {
    plugins.push(new MonthSelect({ shorthand: false, dateFormat: options.dateFormat, altFormat: options.altFormat, theme: 'filament' }));
  }

  if (kind === 'week') {
    plugins.push(new WeekSelect());
  }

  plugins.push(
    browserHintsPlugin({ allowTyping: options.allowInput === true && !component.$refs.input.hasAttribute('readonly') }),
    textDeletedPlugin(() => {
      component.state = null;
    }),
  );

  if (!options.static && !options.inline) {
    plugins.push(followFieldPlugin());
  }

  if (component.presets?.length) {
    plugins.push(presetsPlugin(component.presets));
  }

  if (Object.values(component.footerActions ?? {}).some(Boolean)) {
    plugins.push(footerActionsPlugin(component.footerActions, kind));
  }

  return {
    ...options,
    locale: l10n,
    // Text typed in the field, or a date the server sends, is read in the field's locale without rolling over: a
    // typed "31/02/2024" is no date, as the server says, instead of 2 March.
    parseDate: createStrictDateParser({ l10n, noCalendar: options.noCalendar }),
    // Flatpickr runs the hooks of the last plugin first.
    plugins: plugins.reverse(),
    // Filament clips the field (.fi-fo-text-input { overflow: hidden }), so an inline calendar goes under it.
    appendTo: options.inline && !options.static ? component.$refs.inlineCalendar : undefined,
    onReady: (_selectedDates, _dateStr, fp) => {
      fp.calendarContainer.classList.add('fi-fp-calendar');
    },
    onChange: (selectedDates, _dateStr, fp) => {
      const state = stateFromDates(selectedDates, fp, kind);
      const dates = datesFromState(state, fp, kind);

      // The calendar shows the state as it reads back: a week its middle day, so its number is the same whichever day
      // was clicked.
      if (dates?.length && !sameDates(dates, selectedDates)) {
        fp.setDate(dates, false);
      }

      component.state = state;
    },
  };
}
