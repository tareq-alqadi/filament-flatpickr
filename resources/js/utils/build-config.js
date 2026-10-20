import MonthSelect from 'flatpickr/dist/esm/plugins/monthSelect';
import WeekSelect from 'flatpickr/dist/esm/plugins/weekSelect/weekSelect';
import { chainHook } from './chain-hook.js';
import { attachFooterActions } from './footer-actions.js';
import { resolveLocale } from './locale.js';
import { attachPresets } from './presets.js';
import { startOfWeek, stateFromDates, weekAnchor } from './state.js';
import { createStrictDateParser } from './strict-parser.js';

/**
 * The options flatpickr is created with: the field's, and the hooks that keep the state in step with the calendar.
 */
export function buildFlatpickrConfig(component, { locale, firstDayOfWeek, modalReposition }) {
  const { kind } = component;
  const l10n = resolveLocale(component.config.locale ?? locale, { firstDayOfWeek });

  const config = {
    ...component.config,
    locale: l10n,
    // Text typed in the field, or a date the server sends, is read in the field's locale without rolling over: a
    // typed "31/02/2024" is no date, as the server says, instead of 2 March.
    parseDate: createStrictDateParser({ l10n, noCalendar: component.config.noCalendar }),
    plugins: [],
    onChange: (selectedDates, _dateStr, instance) => {
      // A week shows its middle day, so its number is the same whichever day was clicked.
      if (kind === 'week' && selectedDates.length) {
        const anchor = weekAnchor(startOfWeek(selectedDates[0], instance.l10n.firstDayOfWeek), instance);

        if (anchor.getTime() !== selectedDates[0].getTime()) {
          instance.setDate(anchor, false);
        }
      }

      component.state = stateFromDates(instance.selectedDates, instance, kind);
    },
  };

  if (kind === 'month') {
    config.plugins.push(new MonthSelect({
      shorthand: false,
      dateFormat: config.dateFormat,
      altFormat: config.altFormat,
      theme: 'filament',
    }));
  }

  if (kind === 'week') {
    config.plugins.push(new WeekSelect());
  }

  // Filament clips the field (.fi-fo-text-input { overflow: hidden }), so an inline calendar goes under it.
  if (config.inline && !config.static && component.$refs.inlineCalendar) {
    config.appendTo = component.$refs.inlineCalendar;
  }

  config.onOpen = modalReposition.createOnOpenHook(config.onOpen);
  config.onClose = modalReposition.createOnCloseHook(config.onClose);

  config.onReady = chainHook(config.onReady, (_selectedDates, _dateStr, instance) => {
    instance.calendarContainer?.classList.add('fi-fp-calendar');

    attachPresets(instance, component);
    attachFooterActions(instance, component);
  });

  return config;
}
