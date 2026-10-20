import MonthSelect from 'flatpickr/dist/esm/plugins/monthSelect';
import WeekSelect from 'flatpickr/dist/esm/plugins/weekSelect/weekSelect';
import { chainHook } from './chain-hook.js';
import { formatSelectedDates, getMonthSelectDefaultDate, isListMode } from './date-helpers.js';
import { attachFooterActions } from './footer-actions.js';
import { resolveLocale } from './locale.js';
import { attachPresets } from './presets.js';
import { usesNativeTheme } from './theme.js';

export function buildFlatpickrConfig(component, modalReposition) {
  const { attribs, packageConfig, locale } = component;

  const config = {
    mode: 'single',
    time_24hr: true,
    altFormat: 'F j, Y',
    disableMobile: true,
    allowInvalidPreload: false,
    static: false,
    ...packageConfig,
    // A locale from customConfig() wins over the app's.
    locale: resolveLocale(packageConfig.locale ?? locale),
    plugins: [],
    onChange: (selectedDates) => {
      if (!selectedDates?.length) {
        component.state = null;

        return;
      }

      component.state = formatSelectedDates(selectedDates, {
        dateFormat: packageConfig.dateFormat,
        isList: isListMode(attribs.mode),
      });
    },
  };

  if (attribs.monthSelect === true) {
    config.plugins.push(
      new MonthSelect({
        shorthand: false,
        dateFormat: packageConfig.dateFormat,
        altFormat: packageConfig.altFormat || 'F Y',
        theme: component.mode,
      }),
    );

    const defaultDate = getMonthSelectDefaultDate(component.state, packageConfig.dateFormat);

    if (defaultDate) {
      config.defaultDate = defaultDate;
    }
  } else if (attribs.weekSelect === true) {
    config.plugins.push(new WeekSelect({}));
  }

  // An inline calendar goes under the field, which Filament clips (.fi-fo-text-input { overflow: hidden }).
  if (config.inline && !config.static && component.$refs.inlineCalendar) {
    config.appendTo = component.$refs.inlineCalendar;
  }

  config.onOpen = modalReposition.createOnOpenHook(config.onOpen);
  config.onClose = modalReposition.createOnCloseHook(config.onClose);

  config.onReady = chainHook(config.onReady, (_selectedDates, _dateStr, instance) => {
    // The Filament theme's rules only style calendars of this field, so another theme on the page keeps its look.
    if (usesNativeTheme(component)) {
      instance.calendarContainer?.classList.add('fi-fp-calendar');
    }

    attachPresets(instance, component);
    attachFooterActions(instance, component);
  });

  return config;
}
