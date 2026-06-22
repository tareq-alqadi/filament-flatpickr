import flatpickr from "flatpickr";
import MonthSelect from "flatpickr/dist/esm/plugins/monthSelect";
import WeekSelect from "flatpickr/dist/esm/plugins/weekSelect/weekSelect";
import "flatpickr/dist/l10n/ar";

export default function flatpickrDatepicker(args) {
  return {
    // Reactive state
    state: args.state,
    mode: "light",
    locale: args.locale ?? "en",
    attribs: args.attribs ?? {},
    packageConfig: args.packageConfig ?? {},
    
    // Internal state
    fp: null,
    _repositionTimeoutIds: [],
    _calendarObserver: null,
    _onModalOpened: null,
    _onTransitionEnd: null,

    // Computed properties
    get darkStatus() {
      return document.querySelector("html")?.classList.contains("dark") ?? false;
    },

    get themeMode() {
      const storedTheme = localStorage.getItem("theme");
      if (storedTheme) {
        return storedTheme;
      }
      this.mode = this.darkStatus ? "dark" : "light";
      return this.mode;
    },

    get themeAssetUrl() {
      return this.darkStatus
        ? this.attribs.darkThemeAsset
        : this.attribs.lightThemeAsset;
    },

    get isMonthSelect() {
      return this.attribs.monthSelect === true;
    },

    get isWeekSelect() {
      return this.attribs.weekSelect === true;
    },

    get isRangePicker() {
      return this.attribs.rangePicker === true;
    },

    get isMultipleMode() {
      return this.attribs.mode === "multiple";
    },

    /**
     * Parse a date string for month select format (Y-m)
     * @param {string} dateValue - Date string in Y-m format (e.g., "2024-01")
     * @returns {Date|null} Parsed date or null
     */
    parseMonthSelectDate(dateValue) {
      if (!dateValue) return null;
      
      // For 'Y-m' format, JavaScript Date can parse it directly
      if (this.packageConfig.dateFormat === 'Y-m') {
        const dateWithDay = `${dateValue}-01T00:00:00Z`;
        const parsed = new Date(dateWithDay);
        
        // Validate the parsed date
        if (!isNaN(parsed.getTime())) {
          return parsed;
        }
      }
      
      // Try parsing with flatpickr's parseDate as fallback
      if (this.fp) {
        try {
          const parsed = this.fp.parseDate(dateValue, this.packageConfig.dateFormat);
          if (parsed && !isNaN(parsed.getTime())) {
            return parsed;
          }
        } catch (e) {
          // flatpickr parseDate failed, continue to return null
        }
      }
      
      return null;
    },

    /**
     * Set the date in flatpickr instance
     * @param {string|Date|null} value - Date value to set
     */
    setFlatpickrDate(value) {
      if (!this.fp) return;
      
      if (!value) {
        this.fp.setDate(null);
        return;
      }

      // For month select, use custom parsing
      if (this.isMonthSelect) {
        const parsedDate = this.parseMonthSelectDate(value);
        this.fp.setDate(parsedDate);
      } else {
        this.fp.setDate(value);
      }
    },

    /**
     * Format selected dates according to the configured format
     * @param {Date[]} selectedDates - Array of selected Date objects
     * @returns {string|string[]} Formatted date(s)
     */
    formatSelectedDates(selectedDates) {
      if (this.isRangePicker || this.isMultipleMode) {
        return selectedDates.map((date) =>
          flatpickr.formatDate(date, this.packageConfig.dateFormat)
        );
      }
      return flatpickr.formatDate(selectedDates[0], this.packageConfig.dateFormat);
    },

    /**
     * Handle date change event
     */
    handleDateChange(selectedDates, dateStr, instance) {
      if (!selectedDates?.length) {
        this.state = null;
        return;
      }

      this.state = this.formatSelectedDates(selectedDates);
    },


    /**
     * Create month select plugin configuration
     * @returns {MonthSelect} MonthSelect plugin instance
     */
    createMonthSelectPlugin() {
      return new MonthSelect({
        shorthand: false,
        dateFormat: this.packageConfig.dateFormat,
        altInput: true,
        altFormat: "F, Y",
        theme: this.mode,
      });
    },

    /**
     * Get default date for month select
     * @returns {Date|string|undefined} Default date value
     */
    getMonthSelectDefaultDate() {
      if (!this.state) return undefined;
      
      // For 'Y-m' format, JavaScript Date can parse it directly
      if (this.packageConfig.dateFormat === 'Y-m') {
        const dateWithDay = `${this.state}-01T00:00:00Z`;
        const parsed = new Date(dateWithDay);
        
        // Validate the parsed date
        if (!isNaN(parsed.getTime())) {
          return parsed;
        }
      }
      
      return this.state;
    },

    /**
     * Apply dark theme stylesheet
     */
    applyDarkTheme() {
      if (this.themeMode === "dark") {
        const themeElement = document.querySelector("#pickr-theme");
        if (themeElement) {
          themeElement.href = this.attribs.darkThemeAsset;
        }
      }
    },

    /**
     * Reposition the calendar; retries through Filament modal animations (~300ms).
     */
    scheduleReposition(instance) {
      this.clearRepositionTimeouts();

      const run = () => {
        if (!instance?.isOpen) {
          return;
        }

        requestAnimationFrame(() => {
          requestAnimationFrame(() => instance._positionCalendar());
        });
      };

      run();

      [50, 150, 300].forEach((ms) => {
        this._repositionTimeoutIds.push(setTimeout(run, ms));
      });
    },

    clearRepositionTimeouts() {
      this._repositionTimeoutIds.forEach((id) => clearTimeout(id));
      this._repositionTimeoutIds = [];
    },

    isInsideFilamentModal(element) {
      return !!element?.closest?.(
        '.fi-modal, .fi-modal-window, .fi-modal-slide-over, dialog, [role="dialog"]',
      );
    },

    setupModalRepositionHooks(instance) {
      const pickerEl = instance.element;

      if (!this.isInsideFilamentModal(pickerEl)) {
        return;
      }

      if (instance.calendarContainer) {
        this._calendarObserver = new MutationObserver(() => {
          if (!instance.isOpen) {
            return;
          }

          this.scheduleReposition(instance);
        });

        this._calendarObserver.observe(instance.calendarContainer, {
          attributes: true,
          attributeFilter: ['class'],
        });
      }

      this._onModalOpened = (event) => {
        const containingModal = pickerEl.closest('[data-fi-modal-id]');

        if (containingModal && event.detail?.id !== containingModal.dataset.fiModalId) {
          return;
        }

        if (instance.isOpen) {
          this.scheduleReposition(instance);
        }
      };

      document.addEventListener('x-modal-opened', this._onModalOpened);

      this._onTransitionEnd = (event) => {
        if (!instance.isOpen) {
          return;
        }

        const ourModal = pickerEl.closest('.fi-modal');

        if (!ourModal?.contains(event.target)) {
          return;
        }

        if (
          event.target.closest?.(
            '.fi-modal-window, .fi-modal-slide-over, [role="dialog"]',
          )
        ) {
          this.scheduleReposition(instance);
        }
      };

      document.addEventListener('transitionend', this._onTransitionEnd, true);
    },

    teardownModalRepositionHooks() {
      this.clearRepositionTimeouts();

      this._calendarObserver?.disconnect();
      this._calendarObserver = null;

      if (this._onModalOpened) {
        document.removeEventListener('x-modal-opened', this._onModalOpened);
        this._onModalOpened = null;
      }

      if (this._onTransitionEnd) {
        document.removeEventListener('transitionend', this._onTransitionEnd, true);
        this._onTransitionEnd = null;
      }
    },

    /**
     * Chain with any onOpen handler from packageConfig / customConfig.
     */
    wrapOnOpen(existingOnOpen) {
      return (selectedDates, dateStr, instance) => {
        this.scheduleReposition(instance);

        if (Array.isArray(existingOnOpen)) {
          existingOnOpen.forEach((fn) => fn(selectedDates, dateStr, instance));
        } else if (typeof existingOnOpen === 'function') {
          existingOnOpen(selectedDates, dateStr, instance);
        }
      };
    },

    wrapOnClose(existingOnClose) {
      return (selectedDates, dateStr, instance) => {
        this.clearRepositionTimeouts();

        if (Array.isArray(existingOnClose)) {
          existingOnClose.forEach((fn) => fn(selectedDates, dateStr, instance));
        } else if (typeof existingOnClose === 'function') {
          existingOnClose(selectedDates, dateStr, instance);
        }
      };
    },

    destroy() {
      this.teardownModalRepositionHooks();
      this.fp?.destroy();
      this.fp = null;
    },

    /**
     * Handle theme change event
     */
    handleThemeChange(event) {
      this.mode = event.detail.dark ? "dark" : "light";
      const themeElement = document.querySelector("#pickr-theme");
      if (themeElement) {
        themeElement.href = this.mode === "dark"
          ? this.attribs.darkThemeAsset
          : this.attribs.themeAsset;
      }
    },

    init() {
      this.mode = this.darkStatus ? "dark" : "light";
      
      // Build flatpickr configuration
      const config = {
        mode: this.attribs.mode,
        time_24hr: true,
        altFormat: "F j, Y",
        disableMobile: true,
        allowInvalidPreload: false,
        static: false,
        locale: this.locale,
        ...this.packageConfig,
        plugins: [],
        onChange: (selectedDates, dateStr, instance) => {
          this.handleDateChange(selectedDates, dateStr, instance);
        },
      };

      // Apply dark theme if needed
      this.applyDarkTheme();

      // Add plugins based on configuration
      if (this.isMonthSelect) {
        config.plugins.push(this.createMonthSelectPlugin());
        const defaultDate = this.getMonthSelectDefaultDate();
        if (defaultDate) {
          config.defaultDate = defaultDate;
        }
      } else if (this.isWeekSelect) {
        config.plugins.push(new WeekSelect({}));
      }

      const existingOnOpen = config.onOpen;
      config.onOpen = this.wrapOnOpen(existingOnOpen);

      const existingOnClose = config.onClose;
      config.onClose = this.wrapOnClose(existingOnClose);

      // Initialize flatpickr
      this.fp = flatpickr(this.$refs.picker, config);

      if (!this.fp.config.static && !this.fp.config.inline) {
        this.setupModalRepositionHooks(this.fp);
      }
      
      // Set the initial date
      // For month select, defaultDate is already set in config, but we also set it here
      // to ensure it's properly displayed even if defaultDate didn't work
      this.setFlatpickrDate(this.state);

      // Setup event listeners
      window.addEventListener("theme-changed", (e) => {
        this.handleThemeChange(e);
      });

      // Watch for state changes using Alpine's reactive system
      this.$watch("state", (value) => {
        this.setFlatpickrDate(value ?? null);
      });
    },
  };
}
