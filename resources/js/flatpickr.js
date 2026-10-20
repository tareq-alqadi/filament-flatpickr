import flatpickr from 'flatpickr';
import { buildFlatpickrConfig } from './utils/build-config.js';
import { disableBrowserHints } from './utils/disable-hints.js';
import { isListMode, parseMonthSelectDate } from './utils/date-helpers.js';
import { ModalReposition } from './utils/modal-reposition.js';
import { parseStateDates } from './utils/state-dates.js';
import { applyThemeMode, currentThemeMode, isDarkMode, watchThemeMode } from './utils/theme.js';

export default function flatpickrDatepicker({
  state,
  packageConfig,
  attribs,
  locale,
}) {
  let modalReposition = null;
  let themeObserver = null;
  let onInput = null;
  let onBlur = null;
  let input = null;

  return {
    state,
    mode: 'light',
    locale: locale ?? 'en',
    attribs: attribs ?? {},
    packageConfig: packageConfig ?? {},
    fp: null,

    get darkStatus() {
      return isDarkMode();
    },

    get themeMode() {
      return this.mode;
    },

    get isMonthSelect() {
      return this.attribs.monthSelect === true;
    },

    inputElement() {
      return this.fp?.altInput ?? this.$refs.picker;
    },

    clear() {
      this.fp?.clear();
      this.state = null;
    },

    setFlatpickrDate(value) {
      if (!this.fp) {
        return;
      }

      if (!value) {
        this.fp.setDate(null);

        return;
      }

      if (this.isMonthSelect) {
        this.fp.setDate(parseMonthSelectDate(value, this.packageConfig.dateFormat));

        return;
      }

      // The state is English text; the picker's parser only reads its own locale. Text that isn't a date in the
      // format, such as something typed in the picker, is left to flatpickr, and so is a range or list given as text.
      const dates = isListMode(this.attribs.mode) && !Array.isArray(value)
        ? null
        : parseStateDates(value, this.packageConfig.dateFormat, this.fp.config);

      this.fp.setDate(dates ?? value);
    },

    init() {
      this.mode = currentThemeMode();
      modalReposition = new ModalReposition();

      const config = buildFlatpickrConfig(this, modalReposition);
      const allowTyping = config.allowInput === true && !this.$refs.picker.hasAttribute('readonly');
      this.fp = flatpickr(this.$refs.picker, config);
      disableBrowserHints(this.fp, { allowTyping });

      if (!this.fp.config.static && !this.fp.config.inline) {
        modalReposition.attach(this.fp);
      }

      input = this.inputElement();

      onInput = () => {
        if (!input.value?.trim()) {
          this.state = null;
        }
      };

      onBlur = () => {
        if (!input.value?.trim()) {
          this.state = null;
        }
      };

      input.addEventListener('input', onInput);
      input.addEventListener('blur', onBlur);

      applyThemeMode(this);
      themeObserver = watchThemeMode(this);

      this.setFlatpickrDate(this.state);

      this.$watch('state', (value) => {
        this.setFlatpickrDate(value ?? null);
      });
    },

    destroy() {
      if (input) {
        input.removeEventListener('input', onInput);
        input.removeEventListener('blur', onBlur);
      }

      input = null;
      onInput = null;
      onBlur = null;

      themeObserver?.disconnect();
      themeObserver = null;

      modalReposition?.detach();
      modalReposition = null;

      this.fp?.destroy();
      this.fp = null;
    },
  };
}
