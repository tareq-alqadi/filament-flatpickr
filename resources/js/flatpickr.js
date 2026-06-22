import flatpickr from 'flatpickr';
import 'flatpickr/dist/l10n/ar';

import { buildFlatpickrConfig } from './utils/build-config.js';
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
      this.fp = flatpickr(this.$refs.picker, config);

      if (!this.fp.config.static && !this.fp.config.inline) {
        modalReposition.attach(this.fp);
      }

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
