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

function mark(input) {
  if (!input) {
    return;
  }

  Object.entries(HINT_ATTRIBUTES).forEach(([name, value]) => {
    input.setAttribute(name, value);
  });
}

/**
 * Stops the browser autofill menu (saved names, emails, and so on) from opening over the calendar.
 * Chrome ignores autocomplete="off" on a focused editable field, so a field the user can type in is
 * read-only for the moment it receives focus, which is when the menu is decided.
 */
export function disableBrowserHints(fp, { allowTyping = false } = {}) {
  if (!fp) {
    return;
  }

  [fp.input, fp.altInput, fp.currentYearElement, fp.hourElement, fp.minuteElement, fp.secondElement]
    .forEach(mark);

  const field = fp.altInput ?? fp.input;

  if (!field || !allowTyping) {
    return;
  }

  field.addEventListener('focus', () => {
    field.setAttribute('readonly', 'readonly');

    window.setTimeout(() => {
      if (document.activeElement === field) {
        field.removeAttribute('readonly');
      }
    }, 50);
  });
}
