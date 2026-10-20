/**
 * Calls back when the text of the field is deleted. Flatpickr only reads typed text when the field loses focus, so
 * without this a value deleted from the field is still saved if the form is submitted with the keyboard.
 */
export function textDeletedPlugin(callback) {
  return (fp) => {
    let field = null;

    const onInput = () => {
      if (!field.value?.trim()) {
        callback();
      }
    };

    return {
      onReady() {
        field = fp.altInput ?? fp.input;
        field.addEventListener('input', onInput);
        field.addEventListener('blur', onInput);
      },

      onDestroy() {
        field?.removeEventListener('input', onInput);
        field?.removeEventListener('blur', onInput);
        field = null;
      },
    };
  };
}
