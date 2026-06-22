import { chainHook } from './chain-hook.js';

const REPOSITION_DELAYS_MS = [50, 150, 300];

const FILAMENT_MODAL_SELECTOR =
  '.fi-modal, .fi-modal-window, .fi-modal-slide-over, dialog, [role="dialog"]';

const FILAMENT_TRANSITION_TARGET_SELECTOR =
  '.fi-modal-window, .fi-modal-slide-over, [role="dialog"]';

export class ModalReposition {
  #fp = null;

  #timeoutIds = [];

  #calendarObserver = null;

  #onModalOpened = null;

  #onTransitionEnd = null;

  attach(fp) {
    this.#fp = fp;

    const pickerEl = fp.element;

    if (!pickerEl?.closest?.(FILAMENT_MODAL_SELECTOR)) {
      return;
    }

    if (fp.calendarContainer) {
      this.#calendarObserver = new MutationObserver(() => {
        if (fp.isOpen) {
          this.scheduleReposition();
        }
      });

      this.#calendarObserver.observe(fp.calendarContainer, {
        attributes: true,
        attributeFilter: ['class'],
      });
    }

    this.#onModalOpened = (event) => {
      const containingModal = pickerEl.closest('[data-fi-modal-id]');

      if (containingModal && event.detail?.id !== containingModal.dataset.fiModalId) {
        return;
      }

      if (fp.isOpen) {
        this.scheduleReposition();
      }
    };

    document.addEventListener('x-modal-opened', this.#onModalOpened);

    this.#onTransitionEnd = (event) => {
      if (!fp.isOpen) {
        return;
      }

      const ourModal = pickerEl.closest('.fi-modal');

      if (!ourModal?.contains(event.target)) {
        return;
      }

      if (event.target.closest?.(FILAMENT_TRANSITION_TARGET_SELECTOR)) {
        this.scheduleReposition();
      }
    };

    document.addEventListener('transitionend', this.#onTransitionEnd, true);
  }

  detach() {
    this.clearTimeouts();

    this.#calendarObserver?.disconnect();
    this.#calendarObserver = null;

    if (this.#onModalOpened) {
      document.removeEventListener('x-modal-opened', this.#onModalOpened);
      this.#onModalOpened = null;
    }

    if (this.#onTransitionEnd) {
      document.removeEventListener('transitionend', this.#onTransitionEnd, true);
      this.#onTransitionEnd = null;
    }

    this.#fp = null;
  }

  scheduleReposition(instance = this.#fp) {
    this.clearTimeouts();

    const run = () => {
      if (!instance?.isOpen) {
        return;
      }

      requestAnimationFrame(() => {
        requestAnimationFrame(() => instance._positionCalendar());
      });
    };

    run();

    REPOSITION_DELAYS_MS.forEach((ms) => {
      this.#timeoutIds.push(setTimeout(run, ms));
    });
  }

  clearTimeouts() {
    this.#timeoutIds.forEach((id) => clearTimeout(id));
    this.#timeoutIds = [];
  }

  createOnOpenHook(existingOnOpen) {
    return chainHook(existingOnOpen, (_selectedDates, _dateStr, instance) => {
      this.scheduleReposition(instance);
    });
  }

  createOnCloseHook(existingOnClose) {
    return chainHook(existingOnClose, () => {
      this.clearTimeouts();
    });
  }
}
