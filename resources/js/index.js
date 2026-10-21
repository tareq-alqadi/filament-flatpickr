import flatpickr from 'flatpickr';
import { buildFlatpickrConfig } from './utils/build-config.js';
import { datesFromState, stateFromDates } from './utils/state.js';

/**
 * The Alpine component of a picker. `state` is entangled with Livewire and holds text in the state format (see
 * PickerKind::stateFormat()), which the calendar shows in the display format. What the field adds to the calendar is
 * in its flatpickr plugins (see buildFlatpickrConfig()).
 */
export default function filamentFlatpickr({
  state,
  kind = 'date',
  config = {},
  locale = null,
  firstDayOfWeek = null,
  timezone = null,
  presets = [],
  footerActions = {},
}) {
  return {
    state,
    kind,
    config,
    presets,
    footerActions,
    fp: null,

    get hasValue() {
      return Array.isArray(this.state)
        ? this.state.length > 0
        : this.state !== null && this.state !== undefined && this.state !== '';
    },

    /**
     * The state for what the calendar shows, as onChange writes it.
     */
    pickedState() {
      return stateFromDates(this.fp?.selectedDates, this.fp, this.kind);
    },

    clear() {
      this.fp?.clear();
      this.state = null;
    },

    /**
     * Shows a state the server set. Text that isn't in the state format empties the calendar.
     */
    showState(value) {
      if (!this.fp) {
        return;
      }

      // The calendar wrote this state itself, so it already shows it. Reading it back could change the selection:
      // "now" picked by Today becomes midnight, which a disabled day then removes while the state keeps it.
      if (JSON.stringify(value ?? null) === JSON.stringify(this.pickedState())) {
        return;
      }

      const dates = datesFromState(value, this.fp, this.kind);

      if (dates?.length) {
        this.fp.setDate(dates, false);
      } else {
        this.fp.clear(false);
      }
    },

    init() {
      this.fp = flatpickr(this.$refs.input, buildFlatpickrConfig(this, { locale, firstDayOfWeek, timezone }));

      this.showState(this.state);

      this.$watch('state', (value) => this.showState(value));
    },

    destroy() {
      this.fp?.destroy();
      this.fp = null;
    },
  };
}
