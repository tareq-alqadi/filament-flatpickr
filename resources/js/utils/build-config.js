import MonthSelect from 'flatpickr/dist/esm/plugins/monthSelect';
import WeekSelect from 'flatpickr/dist/esm/plugins/weekSelect/weekSelect';
import { chainHook } from './chain-hook.js';
import { formatSelectedDates, getMonthSelectDefaultDate } from './date-helpers.js';
import { attachFooterActions } from './footer-actions.js';
import { attachPresets } from './presets.js';

export function buildFlatpickrConfig(component, modalReposition) {
  const { attribs, packageConfig, locale } = component;

  const config = {
    mode: attribs.mode,
    time_24hr: true,
    altFormat: 'F j, Y',
    disableMobile: true,
    allowInvalidPreload: false,
    static: false,
    locale,
    ...packageConfig,
    plugins: [],
    onChange: (selectedDates) => {
      if (!selectedDates?.length) {
        component.state = null;

        return;
      }

      component.state = formatSelectedDates(selectedDates, {
        dateFormat: packageConfig.dateFormat,
        isRangePicker: attribs.rangePicker === true,
        isMultipleMode: attribs.mode === 'multiple',
      });
    },
  };

  if (attribs.monthSelect === true) {
    config.plugins.push(
      new MonthSelect({
        shorthand: false,
        dateFormat: packageConfig.dateFormat,
        altInput: true,
        altFormat: packageConfig.altFormat || 'F Y',
        altInputClass: packageConfig.altInputClass,
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

  config.onOpen = modalReposition.createOnOpenHook(config.onOpen);
  config.onClose = modalReposition.createOnCloseHook(config.onClose);

  config.onReady = chainHook(config.onReady, (_selectedDates, _dateStr, instance) => {
    attachPresets(instance, component);
    attachFooterActions(instance, component);
  });

  return config;
}
