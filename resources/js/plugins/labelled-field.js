/**
 * Gives the input the user sees the id, autofocus and aria attributes of the field's own input, which flatpickr hides.
 * The field's label points at that id, so it names the input the user types in, and clicking it focuses that input.
 */
export function labelledFieldPlugin() {
  return (fp) => {
    let moved = [];

    const visible = () => fp.mobileInput ?? fp.altInput;

    const move = (from, to) => {
      moved.forEach((name) => {
        if (from.hasAttribute(name)) {
          to.setAttribute(name, from.getAttribute(name));
          from.removeAttribute(name);
        }
      });
    };

    return {
      onReady() {
        const field = visible();

        if (!field) {
          return;
        }

        moved = [...fp.input.attributes]
          .map(({ name }) => name)
          .filter((name) => name === 'id' || name === 'autofocus' || name.startsWith('aria-'));

        move(fp.input, field);
      },

      // Flatpickr calls this before it removes the input it added.
      onDestroy() {
        const field = visible();

        if (field) {
          move(field, fp.input);
        }

        moved = [];
      },
    };
  };
}
