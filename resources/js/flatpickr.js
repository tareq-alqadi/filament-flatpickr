import flatpickr from 'flatpickr';
import 'flatpickr/dist/l10n/ar';
import { buildFlatpickrConfig } from './utils/build-config.js';
import { disableBrowserHints } from './utils/disable-hints.js';
import { parseMonthSelectDate } from './utils/date-helpers.js';
import { ModalReposition } from './utils/modal-reposition.js';
import {
  applyDarkTheme,
  createThemeChangeHandler,
  isDarkMode,
  resolveInitialMode,
  resolveThemeMode,
} from './utils/theme.js';

export default function flatpickrDatepicker({
  state,
  packageConfig,
  attribs,
  locale,
}) {
  let modalReposition = null;
  let onThemeChanged = null;
  let onInput = null;
  let onBlur = null;

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
      return resolveThemeMode(this.mode);
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
        const parsedDate = parseMonthSelectDate(
          value,
          this.packageConfig.dateFormat,
          this.fp,
        );

        this.fp.setDate(parsedDate);
      } else {
        this.fp.setDate(value);
      }
    },

    init() {
      this.mode = resolveInitialMode();
      modalReposition = new ModalReposition();

      const config = buildFlatpickrConfig(this, modalReposition);
      const allowTyping = this.packageConfig.allowInput === true && !this.$refs.picker.hasAttribute('readonly');
      this.fp = flatpickr(this.$refs.picker, config);
      disableBrowserHints(this.fp, { allowTyping });

      if (!this.fp.config.static && !this.fp.config.inline) {
        modalReposition.attach(this.fp);
      }

      const input = this.inputElement();

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

      this.$nextTick(() => {
        applyDarkTheme(this);
      });

      this.setFlatpickrDate(this.state);

      onThemeChanged = createThemeChangeHandler(this);
      window.addEventListener('theme-changed', onThemeChanged);

      this.$watch('state', (value) => {
        this.setFlatpickrDate(value ?? null);
      });
    },

    destroy() {
      const input = this.inputElement();

      if (input) {
        input.removeEventListener('input', onInput);
        input.removeEventListener('blur', onBlur);
      }

      onInput = null;
      onBlur = null;

      if (onThemeChanged) {
        window.removeEventListener('theme-changed', onThemeChanged);
        onThemeChanged = null;
      }

      modalReposition?.detach();
      modalReposition = null;

      this.fp?.destroy();
      this.fp = null;
    },
  };
}
