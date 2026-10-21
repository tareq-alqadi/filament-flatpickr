# Filament Flatpickr

[Flatpickr](https://flatpickr.js.org) date, time, range, month and week pickers for Filament 4 and 5 forms.

## Installation

```bash
composer require tareq-alqadi/filament-flatpickr
php artisan filament:assets
```

## Pickers

```php
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateRangePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateTimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MonthPicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MultiDatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\TimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\WeekPicker;

DatePicker::make('published_on');
DateTimePicker::make('starts_at');
TimePicker::make('opens_at');
DateRangePicker::make('period');
MultiDatePicker::make('holidays');
MonthPicker::make('billing_month');
WeekPicker::make('sprint_week');
```

The classes share their names with Filament's own `DatePicker`, `DateTimePicker` and `TimePicker`, so import the
ones you want, or alias one set (`use ... DatePicker as FlatpickrDatePicker;`).

### Saved values

Each picker saves text, which Eloquent's date casts and date columns accept.

| Picker | Saved as |
| --- | --- |
| `DatePicker` | `"2024-01-15"` |
| `DateTimePicker` | `"2024-01-15 14:30:00"`, in the app's timezone |
| `TimePicker` | `"14:30"`, or `"14:30:15"` with `->seconds()` |
| `DateRangePicker` | `["2024-01-01", "2024-01-05"]`, in order; cast the attribute to `array` |
| `MultiDatePicker` | `["2024-01-01", "2024-03-01"]`; cast the attribute to `array` |
| `MonthPicker` | the month's first day, `"2024-02-01"` |
| `WeekPicker` | the week's first day, `"2024-01-14"` |

`->format('d/m/Y')` changes what is saved, and how text given to the field is read. `$get('field')` gives the value
as it would be saved. Defaults and `$set()` take text in that format, anything Carbon reads, or date objects.

A range of one day starts and ends on it. A week starts on the locale's first day, or `->firstDayOfWeek()`.

### Formats

`->displayFormat()` sets what the field shows. Both formats use [PHP's date characters](https://www.php.net/manual/en/datetime.format.php),
as Filament's own pickers do. Month and day names follow the locale.

| Picker | Default display format |
| --- | --- |
| `DatePicker`, `DateRangePicker`, `MultiDatePicker` | `F j, Y` (January 15, 2024) |
| `DateTimePicker` | `F j, Y H:i` (`F j, Y H:i:s` with seconds) |
| `TimePicker` | `H:i` (`H:i:s` with seconds) |
| `MonthPicker` | `F Y` |
| `WeekPicker` | `\W\e\e\k W, Y` (Week 3, 2024), with "Week" translated |

A display format with `g`, `h` or `A` gives the time a 12-hour clock: `->displayFormat('M j, Y g:i A')`. A display
format can use `d D j jS l w W F m M n y Y H h g i s A U`; any other letter needs a backslash before it.

### Typing

`->allowInput()` lets the user type a value in the display format, as well as pick one. Text that isn't a date,
or is a date that doesn't exist such as 31 February, empties the field instead of becoming another date.

### Timezones

`->timezone('America/New_York')` sets the timezone a `DateTimePicker` shows. Its values are read and saved in
`config('app.timezone')`. Days, months and weeks are the same in every timezone.

A browser can only work with times its own clock has. On the night the user's clocks move to summer time, a time in
the skipped hour can't be picked, and a saved one is shown as it is stored, such as `2026-03-08 02:30:00`, instead of
in the display format. The server rejects a time the picker's own timezone skips.

On the night the clocks go back, a time such as 01:30 happens twice. A saved value that is the second one keeps its
offset in the field's state (`"2026-11-01 01:30:00-05:00"`), so it saves as the same moment. A time picked in the
browser is the first one.

### Limits

Every picker with a calendar takes:

```php
DatePicker::make('date')
    ->minDate('today')
    ->maxDate(now()->addMonth())
    ->minDate(fn (Get $get) => $get('starts_on'));
```

Limits are worked out again when the form is submitted. On a `DateTimePicker`, `->minDate('now')` therefore moves on
while the user fills the form, and a time picked as "now" is in the past by then. A part of a second in a limit
rounds to the second the browser can pick.

`DatePicker`, `DateTimePicker`, `DateRangePicker` and `MultiDatePicker` also take days that can't be picked:

```php
->disabledDates(['2024-12-25', ['from' => '2024-12-30', 'to' => '2025-01-01']])
->enabledDates([...]) // only these days can be picked
```

`DateTimePicker` and `TimePicker` take times of day:

```php
TimePicker::make('opens_at')->minTime('09:00')->maxTime('17:00');
TimePicker::make('night_shift')->minTime('22:00')->maxTime('06:00'); // a span over midnight
```

The server checks every limit too, as a request can hold anything: Laravel's `after_or_equal`, `before_or_equal`
and `in` messages. A month or week may be picked when any of its days is within the limits. Text that isn't a date
fails with the `date` message.

### Presets and buttons

```php
DateRangePicker::make('period')
    ->clearable()        // an × in the field that clears it
    ->presets()          // quick picks beside the calendar: Last 7 days, This month, …
    ->footerActions();   // Today, Clear and Apply buttons under the calendar
```

`DatePicker` and `DateRangePicker` take presets. Each picker takes `->todayButton()`, `->clearButton()` and
`->applyButton()` one at a time. Your own presets take a token, a date object or text:

```php
->presets([
    ['label' => 'Launch day', 'date' => '2024-12-25'],
    ['label' => 'Next sprint', 'start' => now()->next('Monday'), 'end' => now()->next('Monday')->addDays(13)],
])
```

Tokens, worked out in the browser on the day the user is on: `today`, `tomorrow`, `yesterday`, `in_7_days`,
`last_7_days`, `last_30_days`, `next_month`, `start_of_month`, `end_of_month`, `start_of_last_month`,
`end_of_last_month`, `start_of_year`, `end_of_year`. A preset or Today the limits rule out can't be clicked, and a
range preset starting before `minDate()` starts on it.

Labels are translated; English and Arabic are included. Publish the translations to change them:

```bash
php artisan vendor:publish --tag=tareq-alqadi-filament-flatpickr-translations
```

### Calendar

```php
DatePicker::make('date')
    ->numberOfMonths(2)
    ->weekNumbers()
    ->firstDayOfWeek(1)   // Monday
    ->inline()            // always open, under the field
    ->static();           // opens under the field instead of over the page, for scrolling containers
```

The calendar follows the panel's colors, dark mode and right-to-left layout.

### Languages

The calendar uses the app's locale, with every locale flatpickr ships; only the one in use is sent to the browser. A
locale such as `pt_BR` uses flatpickr's `pt`, and one flatpickr doesn't have falls back to English. `->locale('fr')`
sets it for one field.

### Phones

Phones get the same calendar. `->nativeOnMobile()` gives them the browser's own date input instead, without presets,
buttons, or the month and week pickers.

### Other flatpickr options

```php
DatePicker::make('date')->options(['position' => 'above', 'closeOnSelect' => false]);
```

`options()` takes an array or a closure and is merged over what the field sets. It reaches the browser as JSON, so it
can't hold functions such as hooks or plugins, and limits set in it are only checked in the browser.

A `->readOnly()` field doesn't open.

## Upgrading from 4.x

5.0 replaces the `Flatpickr` field with one class per kind of picker, and keeps the value the browser holds in a
fixed format, which removes a family of locale, format and timezone bugs.

- **Fields.** Replace `Flatpickr::make()` with the picker for its kind:

  | 4.x | 5.0 |
  | --- | --- |
  | `Flatpickr::make('x')` | `DatePicker::make('x')` |
  | `->enableTime()` | `DateTimePicker::make('x')` |
  | `->time()`, `->mode(FlatpickrMode::TIME)` | `TimePicker::make('x')` |
  | `->range()`, `->mode(FlatpickrMode::RANGE)` | `DateRangePicker::make('x')` |
  | `->multiple()`, `->mode(FlatpickrMode::MULTIPLE)` | `MultiDatePicker::make('x')` |
  | `->monthSelect()` | `MonthPicker::make('x')` |
  | `->weekSelect()` | `WeekPicker::make('x')` |

- **Saved values** are text in `format()` instead of Carbon objects, and a week saves its first day instead of the
  day clicked.
- **Formats** take PHP's date characters. `->dateFormat()` is now `->format()` (what is saved) and `->altFormat()`
  is now `->displayFormat()` (what is shown). Flatpickr tokens convert like this: `S` → `s`, `G` → `h`, `h` → `g`,
  `K` → `A`, `J` → `jS`.
- **12-hour clock.** `->use24hr()` is gone: the display format decides, and the default is 24-hour. Use
  `->displayFormat('h:i A')` for a 12-hour time picker.
- **Renamed:** `->enableSeconds()` → `->seconds()`, `->hourIncrement()` → `->hoursStep()`, `->minuteIncrement()` →
  `->minutesStep()`, `->showMonths()` → `->numberOfMonths()`, `->customConfig()` → `->options()`,
  `->disableMobile(false)` → `->nativeOnMobile()`.
- **Removed:** `->castsStateToDates()` (`$get()` gives the saved value), `->theme()` and the config file (only the
  Filament theme remains), `->mode()`, `->altInput()` (always on), `->altInputClass()`, and the setters for
  `allowInvalidPreload`, `ariaDateFormat`, `conjunction`, `clickOpens`, `defaultHour`, `defaultMinute`,
  `defaultSeconds`, `nextArrow`, `prevArrow`, `noCalendar`, `shorthandCurrentMonth`, `wrap`, `position`,
  `monthSelectorType`, `animate` and `closeOnSelect`. Pass any of those flatpickr options through `->options()`.
- **Removed classes:** `FlatpickrMode`, `FlatpickrTheme`, `FlatpickrPosition`, `FlatpickrMonthSelectorType`,
  `FlatpickrStateCast` and the `FilamentFlatpickr` facade.
- **Presets** are on `DatePicker` and `DateRangePicker`. A preset date given as text is read like a default: in
  `format()`, or anything Carbon reads.
- **Assets.** Run `php artisan filament:assets` again. You can delete `public/css/tareq-alqadi/filament-flatpickr`
  and `public/js/tareq-alqadi/filament-flatpickr` first, as the old theme files are no longer used.

## Development

```bash
composer install
composer test        # Pest
npm install
npm test             # Vitest
npm run build        # rebuilds resources/dist: the script, the stylesheet and the locales
```

## License

MIT. See [LICENSE](LICENSE).
