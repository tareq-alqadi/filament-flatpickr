const HINT_ATTRIBUTES = {
  autocomplete: 'off',
  autocorrect: 'off',
  autocapitalize: 'off',
  spellcheck: 'false',
  'aria-autocomplete': 'none',
  'data-1p-ignore': 'true',
  'data-lpignore': 'true',
  'data-form-type': 'other',
};

/**
 * Marks an input so browsers and password managers don't offer to fill it. An attribute the field already has, such
 * as one set with autocapitalize(), is kept.
 */
function mark(input) {
  if (!input) {
    return;
  }

  Object.entries(HINT_ATTRIBUTES).forEach(([name, value]) => {
    if (!input.hasAttribute(name)) {
      input.setAttribute(name, value);
    }
  });
}

/**
 * Stops the browser autofill menu (saved names, emails, and so on) from opening over the calendar.
 * Chrome ignores autocomplete="off" on a focused editable field, so a field the user can type in is
 * read-only for the moment it receives focus, which is when the menu is decided. That is skipped on
 * touch screens, where a field that is read-only when focused doesn't open the keyboard.
 */
export function browserHintsPlugin({ allowTyping = false } = {}) {
  return (fp) => {
    let field = null;

    const onFocus = () => {
      field.setAttribute('readonly', 'readonly');

      window.setTimeout(() => {
        if (document.activeElement === field) {
          field.removeAttribute('readonly');
        }
      }, 50);
    };

    return {
      onReady() {
        const numericInputs = [
          ...(fp.yearElements ?? [fp.currentYearElement]),
          fp.hourElement,
          fp.minuteElement,
          fp.secondElement,
        ];

        [fp.input, fp.altInput, ...numericInputs].forEach(mark);

        numericInputs.forEach((input) => {
          if (input && !input.hasAttribute('lang')) {
            input.setAttribute('lang', 'en');
          }
        });

        if (allowTyping && window.matchMedia?.('(pointer: fine)').matches) {
          field = fp.altInput ?? fp.input;
          field.addEventListener('focus', onFocus);
        }
      },

      onDestroy() {
        field?.removeEventListener('focus', onFocus);
        field = null;
      },
    };
  };
}
