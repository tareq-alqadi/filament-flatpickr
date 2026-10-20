# Filament Flatpickr

A [Flatpickr](https://flatpickr.js.org) date, time, range, month and week picker for Filament 4 and 5 forms.

## Installation

```bash
composer require tareq-alqadi/filament-flatpickr
php artisan filament:assets
```

Optionally publish the config and translations:

```bash
php artisan vendor:publish --tag=tareq-alqadi-filament-flatpickr-config
php artisan vendor:publish --tag=tareq-alqadi-filament-flatpickr-translations
```

## Usage

```php
use TareqAlqadi\FilamentFlatpickr\Forms\Components\Flatpickr;

Flatpickr::make('published_at');                       // a date
Flatpickr::make('starts_at')->enableTime();            // a date and time
Flatpickr::make('opens_at')->time();                   // a time
Flatpickr::make('period')->range();                    // a range of dates
Flatpickr::make('holidays')->multiple();               // several dates
Flatpickr::make('billing_month')->monthSelect();       // a month
Flatpickr::make('sprint_week')->weekSelect();          // a week
```

`->range()` and `->mode(FlatpickrMode::RANGE)` are the same, and so are `->multiple()` and
`->mode(FlatpickrMode::MULTIPLE)`.

### Saved values

| Picker | Saved as |
| --- | --- |
| Date, week | A `Carbon` date at the start of the day picked (for a week, the day picked in it) |
| Date and time | A `Carbon` moment in the app's timezone |
| Month | A `Carbon` date on the first day of the month |
| Range, multiple | An array of `Carbon` dates; cast the attribute to `array` |
| Time | A string, `"H:i"`, or `"H:i:s"` with `->enableSeconds()` |

A value typed into the picker (with `->allowInput()`) that isn't a date fails validation with Laravel's
`validation.date` message.

### Timezones

`->timezone('America/New_York')` sets the timezone the picker shows. Stored values are read and saved in
`config('app.timezone')`.

- A **date and time** is a moment: it is shown in the picker's timezone and saved as the same moment in the app's.
- A **date without a time** is a calendar day: 15 January is shown and saved as 15 January whatever the timezones.

### Reading the state as dates

While a form is live, `$get('field')` gives the text the picker shows. To get dates instead, as an enum select
gives enums:

```php
Flatpickr::make('starts_at')->castsStateToDates();

// $get('starts_at') is a Carbon date, an array of them for a range or multiple picker, or null.
// $set('starts_at', now()) takes dates too.
```

### Formats

`->dateFormat()` sets the format of the state and `->altFormat()` the format shown with `->altInput()`. Both use
[Flatpickr's format tokens](https://flatpickr.js.org/formatting/), which the field converts to PHP's when it reads and
writes values. A format you set is kept as it is. Without one, the field uses:

| Picker | `dateFormat` | `altFormat` |
| --- | --- | --- |
| Date | `Y-m-d` | `F j, Y` |
| Date and time | `Y-m-d H:i:S` | `F j, Y h:i K` (`F j, Y H:i` with `->use24hr()`) |
| Time | `h:i K` (`H:i` with `->use24hr()`) | the same |
| Month | always `Y-m` | `F Y` |
| Week | always `Y-m-d` | `\Week W` |

With `->enableSeconds()`, the time formats get seconds (`h:i:S K`, `H:i:S`).

The state is always written in English, whatever the calendar's locale, so month names and AM/PM in `dateFormat` stay
readable by the server. The calendar and `altFormat` use the locale.

### Limits

```php
Flatpickr::make('date')
    ->minDate('today')
    ->maxDate(now()->addMonth())
    ->disabledDates(['2024-12-25', ['from' => '2024-12-30', 'to' => '2025-01-01']])
    ->enabledDates([...]);   // only these dates can be picked
```

`minDate()` and `maxDate()` take text, any date object or a closure, such as `fn (Get $get) => $get('starts_at')`.
Dates given as objects are sent in the picker's format and timezone.

`disabledDates()` and `enabledDates()` take an array or a closure.

A date the calendar wouldn't let the user pick also fails validation on the server: before `minDate()` or after
`maxDate()` (Laravel's `validation.after_or_equal` and `validation.before_or_equal` messages), or on a day
`disabledDates()` or `enabledDates()` rules out (`validation.in`). A month picker may pick the month `minDate()` falls
in, and its days aren't checked.

### Shortcuts and buttons

```php
Flatpickr::make('date')
    ->clearable()        // an × button in the field that clears it
    ->presets()          // quick picks beside the calendar: Today, Tomorrow, … or Last 7 days, This month, … for a range
    ->footerActions();   // Today, Clear and Apply buttons under the calendar
```

`->todayButton()`, `->clearButton()` and `->applyButton()` add the footer buttons one at a time. Custom presets take a
token, text in the picker's format, or a date:

```php
->presets([
    ['label' => 'Launch day', 'date' => '2024-12-25'],
    ['label' => 'Next sprint', 'start' => now()->next('Monday'), 'end' => now()->next('Monday')->addDays(13)],
])
```

Tokens: `today`, `tomorrow`, `yesterday`, `in_7_days`, `last_7_days`, `last_30_days`, `next_month`, `start_of_month`,
`end_of_month`, `start_of_last_month`, `end_of_last_month`, `start_of_year`, `end_of_year`. Labels and the button text
are translated (English and Arabic are included).

### Themes

The default theme, `FlatpickrTheme::FILAMENT`, follows the panel's colors and dark mode. Flatpickr's own themes are
available too:

```php
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrTheme;

Flatpickr::make('date')->theme(FlatpickrTheme::MATERIAL_BLUE);
```

Set the default for every field with `default_theme` in the config. Flatpickr's own themes switch to its dark theme
in dark mode. They are global stylesheets, so use one of them per page; the Filament theme only styles its own
fields and can be mixed with any of them.

### Locales

The calendar uses the app's locale (`app()->getLocale()`), with every locale Flatpickr ships. A locale such as `pt_BR`
uses Flatpickr's `pt`, and an unknown locale falls back to English. To force one, pass it in `->customConfig()`:

```php
->customConfig(['locale' => 'fr'])
```

### Phones

Flatpickr is used on phones too, so presets, buttons, themes and month and week pickers work there. To give phones
the browser's own date input instead, use `->disableMobile(false)`; these features don't apply to it.

### Other options

`allowInput`, `altInput`, `altInputClass`, `allowInvalidPreload`, `animate`, `ariaDateFormat`, `clickOpens`,
`closeOnSelect`, `conjunction`, `defaultHour`, `defaultMinute`, `defaultSeconds`, `enableSeconds`, `hourIncrement`,
`inline`, `maxTime`, `minTime`, `minuteIncrement`, `monthSelectorType`, `nextArrow`, `noCalendar`, `position`,
`prevArrow`, `shorthandCurrentMonth`, `showMonths`, `static`, `use24hr`, `weekNumbers` and `wrap` set the Flatpickr
option of the same name. `->customConfig([...])` (an array or a closure) is merged over everything the field sets.

A `->readOnly()` field doesn't open the calendar.

## Upgrading

- A **time picker** now saves `"H:i"` (or `"H:i:s"` with seconds) instead of the text it shows, so a 12-hour picker
  saves `"14:30"` rather than `"2:30 PM"`.
- `disableMobile` defaults to `true`: phones get Flatpickr instead of the browser's date input.
- The default theme is `filament`; set `default_theme` in the config to keep another.
- A `dateFormat()` you set is no longer replaced when the picker has a time.
- A date and time picker's default format pads the seconds (`Y-m-d H:i:S`), and a time picker with
  `->enableSeconds()` saves the seconds picked instead of `:00`.
- `minDate()`, `maxDate()`, `disabledDates()` and `enabledDates()` are checked on the server too. They are kept as
  given until the form is built, so a closure is evaluated each time.
- `altInputClass` defaults to empty, so the visible input keeps the field's classes.
- The placeholder `filament-flatpickr` artisan command and the `Flatpickr::PACKAGE_NAME` constant were removed; use
  `FilamentFlatpickr::getPackageName()`.

## Development

```bash
composer install
composer test        # Pest
npm install
npm test             # Vitest
npm run build        # rebuilds resources/js/dist/flatpickr.js
```

The built script includes every Flatpickr locale, about 130 KB minified. It only loads on pages with a picker.

## License

MIT. See [LICENSE](LICENSE).
