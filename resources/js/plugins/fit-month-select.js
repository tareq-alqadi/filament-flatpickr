/**
 * Sizes the month dropdown to the month it shows. A select is as wide as its longest option, which would leave a gap
 * between a short month's name and the dropdown's arrow.
 */
export function fitMonthSelectPlugin() {
  return (fp) => {
    const fit = () => {
      const select = fp.monthsDropdownContainer;
      const month = select?.options[select.selectedIndex]?.text;

      if (!month) {
        return;
      }

      const style = getComputedStyle(select);
      const probe = document.createElement('span');

      probe.textContent = month;
      Object.assign(probe.style, {
        position: 'absolute',
        visibility: 'hidden',
        whiteSpace: 'pre',
        fontFamily: style.fontFamily,
        fontSize: style.fontSize,
        fontWeight: style.fontWeight,
        letterSpacing: style.letterSpacing,
      });

      document.body.append(probe);

      const width = probe.getBoundingClientRect().width + parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);

      probe.remove();

      if (width > 0) {
        select.style.width = `${Math.ceil(width)}px`;
      }
    };

    return {
      onReady: fit,
      // The stylesheet can load after the calendar is drawn, so the month is measured again in its font.
      onOpen: fit,
      onMonthChange: fit,
      onYearChange: fit,
    };
  };
}
