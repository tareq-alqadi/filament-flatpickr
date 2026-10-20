import flatpickr from 'flatpickr';
import { buildFlatpickrConfig } from './utils/build-config.js';
import { disableBrowserHints } from './utils/disable-hints.js';
import { ModalReposition } from './utils/modal-reposition.js';
import { datesFromState, startOfWeek, stateFromDates, weekAnchor } from './utils/state.js';

/**
 * The Alpine component of a picker. `state` is entangled with Livewire and holds text in the state format (see
 * PickerKind::stateFormat()), which the calendar shows in the display format.
 */
export default function filamentFlatpickr({
  state,
  kind = 'date',
  config = {},
  locale = null,
  firstDayOfWeek = null,
  presets = [],
  footerActions = {},
}) {
  let modalReposition = null;
  let input = null;
  let onEmptied = null;

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

      const dates = datesFromState(value, (text, format) => this.fp.parseDate(text, format), this.fp.config.dateFormat);

      if (!dates?.length) {
        this.fp.clear(false);

        return;
      }

      this.fp.setDate(
        this.kind === 'week' ? [weekAnchor(startOfWeek(dates[0], this.fp.l10n.firstDayOfWeek), this.fp)] : dates,
        false,
      );
    },

    init() {
      modalReposition = new ModalReposition();

      const options = buildFlatpickrConfig(this, { locale, firstDayOfWeek, modalReposition });
      const allowTyping = options.allowInput === true && !this.$refs.input.hasAttribute('readonly');

      this.fp = flatpickr(this.$refs.input, options);
      disableBrowserHints(this.fp, { allowTyping });

      if (!this.fp.config.static && !this.fp.config.inline) {
        modalReposition.attach(this.fp);
      }

      // Text deleted from a field the user can type in empties the state.
      input = this.fp.altInput ?? this.$refs.input;
      onEmptied = () => {
        if (!input.value?.trim()) {
          this.state = null;
        }
      };

      input.addEventListener('input', onEmptied);
      input.addEventListener('blur', onEmptied);

      this.showState(this.state);

      this.$watch('state', (value) => this.showState(value));
    },

    destroy() {
      input?.removeEventListener('input', onEmptied);
      input?.removeEventListener('blur', onEmptied);
      input = null;
      onEmptied = null;

      modalReposition?.detach();
      modalReposition = null;

      this.fp?.destroy();
      this.fp = null;
    },
  };
}
