<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use BackedEnum;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\Concerns\HasFooterActions;
use TareqAlqadi\FilamentFlatpickr\StateCasts\PickerStateCast;
use TareqAlqadi\FilamentFlatpickr\Support\FlatpickrFormat;
use TareqAlqadi\FilamentFlatpickr\Support\StrictDate;

/**
 * A flatpickr field. The browser holds the picked value as text in a fixed format (see PickerKind::stateFormat()),
 * and the state cast turns it into the value saved, in format() and the app's timezone.
 */
abstract class BasePicker extends Field
{
    use CanBeReadOnly;
    use HasAffixes {
        prefixIcon as protected setAffixPrefixIcon;
        getPrefixIcon as protected getAffixPrefixIcon;
    }
    use HasExtraAlpineAttributes;
    use HasExtraInputAttributes;
    use HasFooterActions;
    use HasPlaceholder;

    protected string $view = 'tareq-alqadi-filament-flatpickr::forms.components.picker';

    protected string|Closure|null $format = null;

    protected string|Closure|null $displayFormat = null;

    protected string|Closure|null $timezone = null;

    protected string|Closure|null $locale = null;

    protected bool|Closure $isInputAllowed = false;

    protected bool|Closure $isInline = false;

    protected bool|Closure $isStatic = false;

    protected bool|Closure $isClearable = false;

    protected bool|Closure $isNativeOnMobile = false;

    /**
     * @var array<string, mixed>|Closure
     */
    protected array|Closure $options = [];

    protected bool $hasCustomPrefixIcon = false;

    abstract public function getKind(): PickerKind;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(static fn (BasePicker $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            $component->validatePicked($value, $fail);
        });
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [...parent::getDefaultStateCasts(), new PickerStateCast($this)];
    }

    /**
     * The format of the saved value, and of text given to the field. PHP date format characters.
     */
    public function format(string|Closure|null $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->evaluate($this->format) ?? $this->getDefaultFormat();
    }

    protected function getDefaultFormat(): string
    {
        return match ($this->getKind()) {
            PickerKind::DateTime => 'Y-m-d H:i:s',
            PickerKind::Time => $this->hasSeconds() ? 'H:i:s' : 'H:i',
            default => 'Y-m-d',
        };
    }

    /**
     * The format the field shows the value in. PHP date format characters; month and day names follow the locale, and
     * a format with `g`, `h` or `A` gives the calendar a 12-hour clock.
     */
    public function displayFormat(string|Closure|null $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    public function getDisplayFormat(): string
    {
        return $this->evaluate($this->displayFormat) ?? $this->getDefaultDisplayFormat();
    }

    protected function getDefaultDisplayFormat(): string
    {
        return 'F j, Y';
    }

    /**
     * The timezone a date and time picker shows. Values are read and saved in the app's timezone. Dates without a
     * time are calendar days, the same in every timezone.
     */
    public function timezone(string|Closure|null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->evaluate($this->timezone) ?? config('app.timezone');
    }

    /**
     * The timezone of the state the browser holds.
     */
    public function getStateTimezone(): string
    {
        return $this->getKind() === PickerKind::DateTime ? $this->getTimezone() : config('app.timezone');
    }

    /**
     * The calendar's language, such as "fr" or "pt_BR". The app's locale by default.
     */
    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? app()->getLocale();
    }

    /**
     * Lets the user type a value in the display format, as well as pick one.
     */
    public function allowInput(bool|Closure $condition = true): static
    {
        $this->isInputAllowed = $condition;

        return $this;
    }

    public function isInputAllowed(): bool
    {
        return (bool) $this->evaluate($this->isInputAllowed);
    }

    /**
     * Shows the calendar under the field, always open.
     */
    public function inline(bool|Closure $condition = true): static
    {
        $this->isInline = $condition;

        return $this;
    }

    public function isInline(): bool
    {
        return (bool) $this->evaluate($this->isInline) && ! $this->isStatic();
    }

    /**
     * Opens the calendar under the field instead of floating it over the page, which keeps it in place in a
     * scrolling container.
     */
    public function static(bool|Closure $condition = true): static
    {
        $this->isStatic = $condition;

        return $this;
    }

    public function isStatic(): bool
    {
        return (bool) $this->evaluate($this->isStatic);
    }

    /**
     * Shows a button in the field that clears it.
     */
    public function clearable(bool|Closure $condition = true): static
    {
        $this->isClearable = $condition;

        return $this;
    }

    public function isClearable(): bool
    {
        return (bool) $this->evaluate($this->isClearable);
    }

    /**
     * Gives phones the browser's own date input instead of the calendar. Presets, footer buttons, and the month and
     * week pickers don't apply to it.
     */
    public function nativeOnMobile(bool|Closure $condition = true): static
    {
        $this->isNativeOnMobile = $condition;

        return $this;
    }

    public function isNativeOnMobile(): bool
    {
        return (bool) $this->evaluate($this->isNativeOnMobile);
    }

    /**
     * Flatpickr options merged over the ones the field sets, such as `['position' => 'above']`. They reach the browser
     * as JSON, so they can't hold functions such as hooks or plugins. Limits set here only apply in the browser.
     *
     * @param  array<string, mixed>|Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return (array) $this->evaluate($this->options);
    }

    public function prefixIcon(string|BackedEnum|Htmlable|Closure|null $icon, bool|Closure $isInline = false): static
    {
        $this->hasCustomPrefixIcon = true;

        return $this->setAffixPrefixIcon($icon, $isInline);
    }

    /**
     * A calendar, or a clock for a time picker, unless an icon was set.
     */
    public function getPrefixIcon(): string|BackedEnum|Htmlable|null
    {
        if ($this->hasCustomPrefixIcon) {
            return $this->getAffixPrefixIcon();
        }

        return $this->getKind() === PickerKind::Time ? 'heroicon-o-clock' : 'heroicon-o-calendar-days';
    }

    public function hasSeconds(): bool
    {
        return false;
    }

    public function getFirstDayOfWeek(): ?int
    {
        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPresets(): array
    {
        return [];
    }

    /**
     * The options flatpickr is created with.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $kind = $this->getKind();
        $isReadOnly = $this->isReadOnly();
        $displayFormat = $this->getDisplayFormat();

        return [
            'mode' => $kind->flatpickrMode(),
            'dateFormat' => FlatpickrFormat::fromPhp($kind->stateFormat()),
            'altInput' => true,
            'altFormat' => FlatpickrFormat::fromPhp($displayFormat),
            'altInputClass' => '',
            'enableTime' => $kind->hasTime(),
            'noCalendar' => ! $kind->hasCalendar(),
            'time_24hr' => ! FlatpickrFormat::uses12HourClock($displayFormat),
            'allowInput' => $this->isInputAllowed() && ! $isReadOnly,
            'clickOpens' => ! $isReadOnly,
            'disableMobile' => ! $this->isNativeOnMobile(),
            'inline' => $this->isInline(),
            'static' => $this->isStatic(),
            'prevArrow' => svg('heroicon-m-chevron-left')->toHtml(),
            'nextArrow' => svg('heroicon-m-chevron-right')->toHtml(),
            ...$this->getDateLimitsConfig(),
            ...$this->getDisabledDatesConfig(),
            ...$this->getTimeConfig(),
            ...$this->getCalendarConfig(),
            ...$this->getOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDateLimitsConfig(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDisabledDatesConfig(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTimeConfig(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCalendarConfig(): array
    {
        return [];
    }

    /**
     * Reads a value given in code or stored: a date object, text in format(), or anything Carbon reads, in the app's
     * timezone. It gives the moment in the picker's timezone for a date and time picker, the start of the day for a
     * date, the start of the month for a month picker, and null when the value isn't a date.
     *
     * The day of a date object is the one it shows in its own timezone, so `today('Asia/Hebron')` is that day in Hebron
     * and not the one before in UTC. The day of text with an offset, such as a model's date as JSON
     * ("2024-01-14T21:00:00.000000Z"), is the one it falls on in the app's timezone.
     */
    public function parseValue(mixed $value): ?CarbonInterface
    {
        $date = match (true) {
            $value instanceof DateTimeInterface => Carbon::instance($value),
            is_string($value) && filled($value), is_int($value) => $this->parseText((string) $value)?->setTimezone(config('app.timezone')),
            default => null,
        };

        if (! $date) {
            return null;
        }

        $date = $date->avoidMutation();
        $kind = $this->getKind();

        if (in_array($kind, [PickerKind::DateTime, PickerKind::Time], true)) {
            return ($kind === PickerKind::DateTime) ? $date->setTimezone($this->getTimezone()) : $date;
        }

        $day = StrictDate::fromFormat('Y-m-d', $date->format('Y-m-d'), config('app.timezone'));

        return ($kind === PickerKind::Month) ? $day?->startOfMonth() : $day;
    }

    protected function parseText(string $value): ?CarbonInterface
    {
        $timezone = config('app.timezone');
        $formats = [$this->getFormat()];

        if ($this->getKind() === PickerKind::Time) {
            $formats = [...$formats, 'H:i:s', 'H:i'];
        }

        foreach ($formats as $format) {
            if ($date = StrictDate::fromFormat($format, $value, $timezone)) {
                return $date;
            }
        }

        return StrictDate::parse($value, $timezone);
    }

    /**
     * A date as the browser holds it.
     */
    public function formatState(CarbonInterface $date): string
    {
        return $date->format($this->getKind()->stateFormat());
    }

    /**
     * A date as the field shows it, such as in a validation message.
     */
    public function formatForDisplay(CarbonInterface $date): string
    {
        return $date->translatedFormat($this->getDisplayFormat());
    }

    /**
     * What the browser holds for a value given in code or stored: text in the state format, a list of them for a range
     * or multiple picker, or null. Entries that aren't dates are left out.
     *
     * @return string|array<int, string>|null
     */
    public function toState(mixed $value): string|array|null
    {
        if (blank($value)) {
            return null;
        }

        if ($this->getKind()->isList()) {
            $dates = collect(Arr::wrap($value))
                ->map(fn (mixed $entry): ?CarbonInterface => $this->parseValue($entry))
                ->filter()
                ->map(fn (CarbonInterface $date): string => $this->formatState($date))
                ->values()
                ->all();

            return $dates ?: null;
        }

        $date = $this->parseValue(is_array($value) ? Arr::first($value) : $value);

        return $date ? $this->formatState($date) : null;
    }

    /**
     * The value saved for what the browser holds: text in format() and the app's timezone, or a list of them. A range
     * is in order, and a range of one day starts and ends on it. Something that isn't a date in the state format is
     * given as it is, which validation reports.
     *
     * @return string|array<int, string>|null
     */
    public function fromState(mixed $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        $dates = $this->parsePicked($state);

        if ($dates === null) {
            return $state;
        }

        $kind = $this->getKind();

        if ($kind === PickerKind::Range) {
            usort($dates, fn (CarbonInterface $a, CarbonInterface $b): int => $a <=> $b);
            $dates = [$dates[0], $dates[array_key_last($dates)]];
        }

        $format = $this->getFormat();

        $values = array_map(
            fn (CarbonInterface $date): string => ($kind === PickerKind::DateTime)
                ? $date->avoidMutation()->setTimezone(config('app.timezone'))->format($format)
                : $date->format($format),
            $dates,
        );

        return $kind->isList() ? $values : $values[0];
    }

    /**
     * The dates the browser holds, in the state's timezone, or null when it holds something that isn't one: text in
     * another format, a date that doesn't exist, or a list for a single picker.
     *
     * @return array<int, CarbonInterface>|null
     */
    public function parsePicked(mixed $state): ?array
    {
        $kind = $this->getKind();
        $values = $kind->isList() ? $state : [$state];

        if (! is_array($values) || ! array_is_list($values) || (($kind === PickerKind::Range) && (count($values) > 2))) {
            return null;
        }

        $dates = [];

        foreach ($values as $value) {
            $date = is_string($value)
                ? StrictDate::fromFormat($kind->stateFormat(), $value, $this->getStateTimezone())
                : null;

            if (! $date) {
                return null;
            }

            $dates[] = ($kind === PickerKind::Month) ? $date->startOfMonth() : $date;
        }

        return $dates;
    }

    /**
     * Fails a value that isn't a date, or one the calendar wouldn't let the user pick: a request can hold anything.
     */
    public function validatePicked(mixed $state, Closure $fail): void
    {
        if (blank($state)) {
            return;
        }

        $dates = $this->parsePicked($state);

        if ($dates === null) {
            $fail('validation.date')->translate();

            return;
        }

        $this->validateDateLimits($dates, $fail);
        $this->validateDisabledDates($dates, $fail);
        $this->validateTimeLimits($dates, $fail);
    }

    /**
     * @param  array<int, CarbonInterface>  $dates
     */
    protected function validateDateLimits(array $dates, Closure $fail): void {}

    /**
     * @param  array<int, CarbonInterface>  $dates
     */
    protected function validateDisabledDates(array $dates, Closure $fail): void {}

    /**
     * @param  array<int, CarbonInterface>  $dates
     */
    protected function validateTimeLimits(array $dates, Closure $fail): void {}
}
