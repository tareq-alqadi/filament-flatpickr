import flatpickr from "flatpickr";
import ConfirmDate from "flatpickr/dist/esm/plugins/confirmDate/confirmDate";
import MonthSelect from "flatpickr/dist/esm/plugins/monthSelect";
import WeekSelect from "flatpickr/dist/esm/plugins/weekSelect/weekSelect";
import "flatpickr/dist/l10n/ar";

// import RangePlugin from "../../assets/flatpickr/dist/esm/plugins/rangePlugin.js";
export default function flatpickrDatepicker(args) {
  return {
    state: args.state,
    date: null,
    mode: "light",
    locale: args.locale ?? "en",
    attribs: args.attribs ?? {},
    packageConfig: args.packageConfig ?? {},
    fp: null,
    get darkStatus() {
      return document.querySelector("html").classList.contains("dark");
      //window.matchMedia('(prefers-color-scheme: dark)').matches;
    },
    get getMode() {
      if (localStorage.getItem("theme")) {
        return localStorage.getItem("theme");
      }
      this.mode = this.darkStatus ? "dark" : "light";
      return this.mode;
    },
    get darkLightAssetUrl() {
      return this.darkStatus
        ? this.attribs.darkThemeAsset
        : this.attribs.lightThemeAsset;
    },

    setState: function (value) {
      this.state = value;
    },
    setFlatpickrDate: function (value) {
      if (this.fp) {
        this.fp.setDate(value);
      }
    },

    init: function () {
      this.mode = this.darkStatus ? "dark" : "light";
      const config = {
        mode: this.attribs.mode,
        time_24hr: true,
        altFormat: "F j, Y",
        disableMobile: true,
        allowInvalidPreload: false,
        static: false,
        locale: this.locale,
        ...this.packageConfig,
        plugins: [
          new ConfirmDate({
            confirmText: "OK",
            showAlways: false,
            theme: this.mode,
          }),
        ],

        onChange: (selectedDates, dateStr, instance) => {
          if (this.attribs.rangePicker || this.attribs.mode === "multiple") {
            const formattedDates = selectedDates.map((date) => {
              return flatpickr.formatDate(
                new Date(date),
                this.packageConfig.dateFormat
              );
            });
            this.setState(formattedDates);
          } else {
            this.setState(dateStr);
          }
        },
        onClose(selectedDates, dateStr, instance) {
          instance.setDate(selectedDates, true)
        }

      };
      if (this.getMode === "dark") {
        let el = document.querySelector("#pickr-theme");
        if (el) {
          el.href = this.attribs.darkThemeAsset;
        }
      }
      if (this.attribs.monthSelect) {
        config.plugins.push(
          new MonthSelect({
            shorthand: false, //defaults to false
            dateFormat: this.packageConfig.dateFormat, //defaults to "F Y"
            altInput: true,
            altFormat: "F, Y", //defaults to "F Y"
            theme: this.mode, // defaults to "light"
          })
        );
      } else if (this.attribs.weekSelect) {
        config.plugins.push(new WeekSelect({}));
      } /* else if (this.attribs.rangePicker) {
                config.plugins.push(new RangePlugin({}))
            }*/

      this.fp = flatpickr(this.$refs.picker, config);
      this.setFlatpickrDate(this.state);

      // this.$refs.picker.addEventListener("change", (e) => {
      //   console.log('test');
      //   if (this.fp) {

      //     if (this.attribs.rangePicker || this.attribs.mode === "multiple") {
      //       const formattedDates = this.fp.selectedDates.map((date) => {
      //         return flatpickr.formatDate(
      //           new Date(date),
      //           this.packageConfig.dateFormat
      //         );
      //       });
      //       this.setState(formattedDates);
      //     } else {
      //       const formattedDate = flatpickr.formatDate(
      //         new Date(this.fp.selectedDates[0]),
      //         this.packageConfig.dateFormat
      //       );

      //       this.setState(formattedDate);
      //     }

      //     console.log(this.state);

      //   }
      // });

      window.addEventListener("theme-changed", (e) => {
        this.mode = e.detail.dark ? "dark" : "light";

        let href;
        if (this.mode === "dark") {
          href = this.attribs.darkThemeAsset;
        } else {
          href = this.attribs.themeAsset;
        }
        document.querySelector("#pickr-theme").href = href;
      });

      this.$watch("state", () => {
        if (this.state === undefined) {
          this.setFlatpickrDate(null);
          return;
        }
        this.setFlatpickrDate(this.state);
      });
    },
  };
}
