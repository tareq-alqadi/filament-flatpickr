<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use BackedEnum;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use DateTimeInterface;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Contracts;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrMode;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrMonthSelectorType;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrPosition;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrTheme;
use TareqAlqadi\FilamentFlatpickr\FilamentFlatpickr;
use TareqAlqadi\FilamentFlatpickr\StateCasts\FlatpickrStateCast;

class Flatpickr extends Field implements Contracts\CanBeLengthConstrained
{
    use Concerns\CanBeAutocapitalized;
    use Concerns\CanBeAutocompleted;
    use Concerns\CanBeLengthConstrained;
    use Concerns\CanBeReadOnly;
    use Concerns\HasAffixes {
        prefixIcon as protected setAffixPrefixIcon;
        getPrefixIcon as protected getAffixPrefixIcon;
    }
    use Concerns\HasExtraInputAttributes;
    use Concerns\HasInputMode;
    use Concerns\HasPlaceholder;
    use HasExtraAlpineAttributes;

    /**
     * Flatpickr format tokens and the PHP date format characters that write and read the same thing.
     */
    protected const PHP_FORMAT_TOKENS = [
        'd' => 'd', 'D' => 'D', 'l' => 'l', 'j' => 'j', 'J' => 'jS', 'w' => 'w', 'W' => 'W',
        'F' => 'F', 'm' => 'm', 'n' => 'n', 'M' => 'M', 'U' => 'U', 'y' => 'y', 'Y' => 'Y', 'Z' => 'c',
        'H' => 'H', 'h' => 'g', 'G' => 'h', 'i' => 'i', 'S' => 's', 's' => 's', 'K' => 'A',
    ];

    protected string $view = 'tareq-alqadi-filament-flatpickr::forms.components.flatpickr';

    protected bool $monthSelect = false;

    protected bool|Closure $castsStateToDates = false;

    protected bool $time = false;

    protected bool $weekSelect = false;

    protected bool $rangePicker = false;

    protected FlatpickrMode $mode = FlatpickrMode::SINGLE;

    protected bool $multiplePicker = false;

    protected bool $altInput = false;

    protected array|Closure $config = [];

    /**
     * The display format the user set; null uses the default for the kind of picker.
     */
    protected ?string $altFormat = null;

    protected bool $enableTime = false;

    /**
     * The state format the user set; null uses the default for the kind of picker.
     */
    protected ?string $dateFormat = null;

    protected FlatpickrTheme $theme = FlatpickrTheme::DEFAULT;

    /**
     * Empty, so flatpickr gives the visible input the classes of the field's own input.
     */
    protected ?string $altInputClass = '';

    protected bool $allowInput = false;

    protected bool $allowInvalidPreload = false;

    protected ?string $ariaDateFormat = 'F j, Y';

    protected ?string $conjunction = ',';

    protected bool $clickOpens = true;

    protected int $defaultHour = 12;

    protected int $defaultMinute = 0;

    protected int $defaultSeconds = 0;

    protected array|Closure $disabledDates = [];

    protected array|Closure|null $enabledDates = null;

    protected bool $disableMobile = true;

    protected bool $enableSeconds = false;

    protected int $hourIncrement = 1;

    protected int $minuteIncrement = 5;

    protected bool $inline = false;

    protected string | Closure | null $timezone = null;

    protected DateTimeInterface|string|Closure|null $maxDate = null;

    protected DateTimeInterface|string|Closure|null $minDate = null;

    protected ?string $maxTime = null;

    protected ?string $minTime = null;

    protected ?string $nextArrow = '>';

    protected ?string $prevArrow = '<';

    protected bool $noCalendar = false;

    protected bool $shorthandCurrentMonth = false;

    protected bool $static = false;

    protected bool $use24hr = false;

    protected bool $weekNumbers = false;

    protected bool $wrap = false;

    protected int $showMonths = 1;

    protected FlatpickrPosition $position = FlatpickrPosition::AUTO;

    protected FlatpickrMonthSelectorType $monthSelectorType = FlatpickrMonthSelectorType::DROPDOWN;

    protected bool $animate = true;

    protected bool $closeOnSelect = true;

    protected bool|Closure $isClearable = false;

    protected bool|array|Closure|null $presets = null;

    protected bool|Closure $showTodayButton = false;

    protected bool|Closure $showClearButton = false;

    protected bool|Closure $showApplyButton = false;

    protected bool $hasCustomPrefixIcon = false;

    /**
     * The options flatpickr is created with. Reading it changes nothing, so it can be called any number of times.
     */
    public function getConfig(): array
    {
        $isReadOnly = $this->isReadOnly();

        $config = [
            'monthSelect' => $this->isMonthSelect(),
            'weekSelect' => $this->isWeekSelect(),
            'mode' => $this->getFlatpickrMode(),
            'altInput' => $this->isAltInput(),
            'altFormat' => $this->getAltFormat(),
            'enableTime' => $this->isEnableTime(),
            'dateFormat' => $this->getDateFormat(),
            'theme' => $this->getTheme(),
            'altInputClass' => $this->getAltInputClass(),
            'allowInput' => $this->isAllowInput() && ! $isReadOnly,
            'allowInvalidPreload' => $this->allowInvalidPreload,
            'ariaDateFormat' => $this->ariaDateFormat,
            'conjunction' => $this->conjunction,
            'clickOpens' => $this->isClickOpens() && ! $isReadOnly,
            'defaultHour' => $this->defaultHour,
            'defaultMinute' => $this->defaultMinute,
            'defaultSeconds' => $this->defaultSeconds,
            'disable' => $this->serializeDates($this->getDisabledDates()),
            'disableMobile' => $this->disableMobile,
            'enableSeconds' => $this->enableSeconds,
            'hourIncrement' => $this->hourIncrement,
            'minuteIncrement' => $this->minuteIncrement,
            'inline' => $this->inline,
            'minDate' => $this->serializeDate($this->getMinDate()),
            'maxDate' => $this->serializeDate($this->getMaxDate()),
            'minTime' => $this->minTime,
            'maxTime' => $this->maxTime,
            'nextArrow' => $this->nextArrow,
            'prevArrow' => $this->prevArrow,
            'noCalendar' => $this->isNoCalendar(),
            'shorthandCurrentMonth' => $this->shorthandCurrentMonth,
            'time_24hr' => $this->use24hr,
            'weekNumbers' => $this->weekNumbers,
            'wrap' => $this->wrap,
            'showMonths' => $this->showMonths,
            'position' => $this->position->value,
            'monthSelectorType' => $this->monthSelectorType->value,
            'animate' => $this->animate,
            'closeOnSelect' => $this->closeOnSelect,
            'static' => $this->isStatic(),
        ];

        if (filled($enabledDates = $this->getEnabledDates())) {
            $config['enable'] = $this->serializeDates($enabledDates);
        }

        return $config;
    }

    public function isTime(): bool
    {
        return $this->time || $this->mode === FlatpickrMode::TIME;
    }

    public function time(bool $time = true): static
    {
        $this->time = $time;

        return $this;
    }

    public function prefixIcon(string | BackedEnum | Htmlable | Closure | null $icon, bool | Closure $isInline = false): static
    {
        $this->hasCustomPrefixIcon = true;

        return $this->setAffixPrefixIcon($icon, $isInline);
    }

    /**
     * A calendar, or a clock for a time picker, unless an icon was set.
     */
    public function getPrefixIcon(): string | BackedEnum | Htmlable | null
    {
        if ($this->hasCustomPrefixIcon) {
            return $this->getAffixPrefixIcon();
        }

        return $this->isTime() ? 'heroicon-o-clock' : 'heroicon-o-calendar-days';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->autocomplete(false);

        $this->prevArrow(svg('heroicon-m-chevron-left')->toHtml());
        $this->nextArrow(svg('heroicon-m-chevron-right')->toHtml());

        $this->theme(config('tareq-alqadi-filament-flatpickr.default_theme') ?? FlatpickrTheme::FILAMENT);

        $this->dehydrateStateUsing(static function (Flatpickr $component, $state) {
            return static::dehydratePickerState($component, $state);
        });

        // Text that isn't a date is kept for validation, so the rule below reports it instead of the field emptying.
        $this->mutateStateForValidationUsing(static function (Flatpickr $component, $state) {
            if (blank($state) || $component->isTime()) {
                return $state;
            }

            return static::dehydratePickerState($component, $state) ?? $state;
        });

        $this->rule(static fn (Flatpickr $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            if ($component->hasInvalidState()) {
                $fail('validation.date')->translate();

                return;
            }

            $component->validateDateLimits($fail);
        });

        // A stored value, in the app's timezone, becomes the text the picker shows. With castsStateToDates() the state
        // cast has done this already while the form was filled.
        $this->formatStateUsing(static function (Flatpickr $component, mixed $state) {
            if ($component->shouldCastStateToDates()) {
                return $state;
            }

            return $component->formatStateForPicker($state);
        });
    }

    /**
     * Makes `$get()` and the state give dates instead of the text the picker shows, the way an enum select gives its
     * enum: a date, a list of dates for range and multiple pickers, or null. `$set()` and defaults take dates too.
     */
    public function castsStateToDates(bool|Closure $condition = true): static
    {
        $this->castsStateToDates = $condition;

        return $this;
    }

    public function shouldCastStateToDates(): bool
    {
        return (bool) $this->evaluate($this->castsStateToDates);
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if (!$this->shouldCastStateToDates()) {
            return parent::getDefaultStateCasts();
        }

        return [...parent::getDefaultStateCasts(), new FlatpickrStateCast($this)];
    }

    /**
     * Reads a value of this picker, such as `$get('start_date')` while the form is live, as the user picked it: in the
     * picker's date format and timezone. A date without a time is the start of that day, a month the start of the
     * month, a week the day picked in it. Range and multiple pickers give a list of dates, a time picker its value as it
     * is, and a value that isn't a date gives null.
     *
     * @return CarbonInterface|array<int, CarbonInterface>|string|null
     */
    public function parseState(mixed $state): CarbonInterface|array|string|null
    {
        return $this->parseEach($state, fn (mixed $date): ?CarbonInterface => $this->parseDate($date));
    }

    /**
     * Reads a stored value, such as a model attribute or a default, in the app's timezone, the way parseState() reads
     * what the picker shows.
     *
     * @return CarbonInterface|array<int, CarbonInterface>|string|null
     */
    public function parseStoredState(mixed $state): CarbonInterface|array|string|null
    {
        return $this->parseEach($state, fn (mixed $date): ?CarbonInterface => $this->parseStoredDate($date));
    }

    /**
     * The text the picker shows for a stored value, a date given in code or text typed in it. Text that isn't a date
     * is kept as it is, so validation reports it instead of the field emptying while the user types.
     *
     * @return string|array<int, string>|null
     */
    public function formatStateForPicker(mixed $state): string|array|null
    {
        if (blank($state)) {
            return null;
        }

        if ($this->isTime()) {
            return $this->formatTime($state, $this->getPhpDateFormat());
        }

        $parsed = $this->parseStoredState($state);

        return match (true) {
            $parsed instanceof CarbonInterface => $this->formatDate($parsed),
            is_array($parsed) && filled($parsed) => array_map(fn (CarbonInterface $date): string => $this->formatDate($date), $parsed),
            is_string($state) => $state,
            default => null,
        };
    }

    /**
     * Whether the field holds something that isn't a date: text typed in the picker, or a range or list with an entry
     * that isn't one.
     */
    public function hasInvalidState(): bool
    {
        $state = $this->getRawState();

        if (blank($state) || $this->isTime()) {
            return false;
        }

        if ($this->isRangePicker() || $this->isMultiplePicker()) {
            return collect(Arr::wrap($state))
                ->filter(fn (mixed $date): bool => filled($date))
                ->contains(fn (mixed $date): bool => $this->parseDate($date) === null);
        }

        return $this->parseDate($state) === null;
    }

    /**
     * @param  Closure(mixed): ?CarbonInterface  $parse
     * @return CarbonInterface|array<int, CarbonInterface>|string|null
     */
    protected function parseEach(mixed $state, Closure $parse): CarbonInterface|array|string|null
    {
        if (blank($state)) {
            return null;
        }

        if ($this->isTime()) {
            return $state;
        }

        $parseOne = function (mixed $date) use ($parse): ?CarbonInterface {
            $parsed = $parse($date);

            return $this->isMonthSelect() ? $parsed?->startOfMonth() : $parsed;
        };

        if ($this->isRangePicker() || $this->isMultiplePicker()) {
            return collect(Arr::wrap($state))
                ->map($parseOne)
                ->filter()
                ->values()
                ->all();
        }

        // A single picker given a list, such as after its mode changed, reads the first entry.
        if (is_array($state)) {
            $state = Arr::first($state);
        }

        return $parseOne($state);
    }

    /**
     * One date as the picker shows it: in the picker's format and timezone. A date given in code, or text in another
     * format Carbon reads, is a moment for a picker with a time, and otherwise the calendar day it falls on in the app's
     * timezone.
     */
    protected function parseDate(mixed $date): ?CarbonInterface
    {
        if ($date instanceof DateTimeInterface) {
            return $this->toPickerTimezone(Carbon::instance($date));
        }

        if (! is_scalar($date) || blank($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!'.$this->getPhpDateFormat(), (string) $date, $this->getTimezone());
        } catch (InvalidFormatException) {
            return $this->parseInAppTimezone((string) $date);
        }
    }

    /**
     * One stored date. A value in the ISO form databases and models give, like "2024-01-15", "2024-01-15 10:00:00" or
     * "2024-01-14T21:00:00.000000Z", is read in the app's timezone; anything else is read the way the picker shows it.
     */
    protected function parseStoredDate(mixed $date): ?CarbonInterface
    {
        if ($date instanceof DateTimeInterface) {
            return $this->toPickerTimezone(Carbon::instance($date));
        }

        if (! is_scalar($date) || blank($date)) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{1,2}(-\d{1,2})?([ T]|$)/', (string) $date)) {
            return $this->parseInAppTimezone((string) $date);
        }

        return $this->parseDate($date);
    }

    protected function parseInAppTimezone(string $date): ?CarbonInterface
    {
        try {
            $parsed = Carbon::parse($date, config('app.timezone'))->setTimezone(config('app.timezone'));
        } catch (InvalidFormatException) {
            return null;
        }

        return $this->toPickerTimezone($parsed);
    }

    /**
     * A date with a time is the same moment in the picker's timezone. A date without one is a calendar day: the day it
     * falls on where it was given, at the start of that day in the picker's timezone.
     */
    protected function toPickerTimezone(CarbonInterface $date): CarbonInterface
    {
        $date = $date->avoidMutation();

        return $this->isEnableTime()
            ? $date->setTimezone($this->getTimezone())
            : $date->startOfDay()->shiftTimezone($this->getTimezone());
    }

    protected function formatDate(CarbonInterface $date): string
    {
        return $date->format($this->getPhpDateFormat());
    }

    /**
     * A time from the picker, a stored time or a date, in a PHP format, or the value as it is when it isn't a time.
     */
    protected function formatTime(mixed $time, string $format): mixed
    {
        if ($time instanceof DateTimeInterface) {
            return $time->format($format);
        }

        if (! is_scalar($time)) {
            return $time;
        }

        try {
            return Carbon::createFromFormat('!'.$this->getPhpDateFormat(), (string) $time)->format($format);
        } catch (InvalidFormatException) {
            try {
                return Carbon::parse((string) $time)->format($format);
            } catch (InvalidFormatException) {
                return $time;
            }
        }
    }

    /**
     * The value saved for the picker's state. Dates go to the app's timezone, and a month is its first day. A time is
     * saved as "H:i", or "H:i:s" with seconds, whatever the picker shows. A value that isn't a date gives null.
     */
    public static function dehydratePickerState(Flatpickr $component, mixed $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        if ($component->isTime()) {
            return $component->formatTime($state, $component->isEnableSeconds() ? 'H:i:s' : 'H:i');
        }

        $parsed = $component->parseState($state);

        if (blank($parsed)) {
            return null;
        }

        return is_array($parsed)
            ? array_map(static fn (CarbonInterface $date): CarbonInterface => static::toAppTimezone($component, $date), $parsed)
            : static::toAppTimezone($component, $parsed);
    }

    /**
     * A date with a time is the same moment in the app's timezone. A date without one is a calendar day, which has no
     * timezone: it stays that day, so a day picked just after midnight isn't saved as the day before.
     */
    protected static function toAppTimezone(Flatpickr $component, CarbonInterface $date): CarbonInterface
    {
        $date = $date->avoidMutation();

        return $component->isEnableTime()
            ? $date->setTimezone(config('app.timezone'))
            : $date->shiftTimezone(config('app.timezone'));
    }

    /**
     * A date given in code as text flatpickr reads with the picker's format; text is sent as it is.
     */
    protected function serializeDate(mixed $date): mixed
    {
        if ($date instanceof DateTimeInterface) {
            return $this->formatDate($this->toPickerTimezone(Carbon::instance($date)));
        }

        return $date;
    }

    /**
     * Dates and `['from' => ..., 'to' => ...]` ranges, for flatpickr's `disable` and `enable` options.
     */
    protected function serializeDates(array $dates): array
    {
        return array_map(
            fn (mixed $date): mixed => is_array($date) ? array_map($this->serializeDate(...), $date) : $this->serializeDate($date),
            $dates,
        );
    }

    /**
     * A flatpickr format as the PHP date format that writes and reads the same text. Characters that aren't format
     * tokens are escaped, and so is anything escaped with a backslash.
     */
    public static function convertFormatToPhp(string $format): string
    {
        $converted = '';
        $length = strlen($format);

        for ($index = 0; $index < $length; $index++) {
            $character = $format[$index];

            if ($character === '\\') {
                $index++;

                if ($index < $length) {
                    $converted .= '\\'.$format[$index];
                }

                continue;
            }

            if (isset(static::PHP_FORMAT_TOKENS[$character])) {
                $converted .= static::PHP_FORMAT_TOKENS[$character];

                continue;
            }

            $converted .= ctype_alpha($character) ? '\\'.$character : $character;
        }

        return $converted;
    }

    public function getPhpDateFormat(): string
    {
        return static::convertFormatToPhp($this->getDateFormat());
    }

    public function mode(FlatpickrMode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * The kind of picker: range, multiple, time or single.
     */
    public function getMode(): string
    {
        return match (true) {
            $this->isRangePicker() => FlatpickrMode::RANGE->value,
            $this->isMultiplePicker() => FlatpickrMode::MULTIPLE->value,
            $this->isTime() => FlatpickrMode::TIME->value,
            default => FlatpickrMode::SINGLE->value,
        };
    }

    /**
     * The mode flatpickr is given; it has no time mode, as a time picker is a single picker without a calendar.
     */
    public function getFlatpickrMode(): string
    {
        $mode = $this->getMode();

        return $mode === FlatpickrMode::TIME->value ? FlatpickrMode::SINGLE->value : $mode;
    }

    public function altInputClass(?string $altInputClass = ''): static
    {
        $this->altInputClass = $altInputClass;

        return $this;
    }

    public function getAltInputClass(): ?string
    {
        return $this->altInputClass;
    }

    public function allowInput(bool $allowInput = true): static
    {
        $this->allowInput = $allowInput;

        return $this;
    }

    public function isAllowInput(): bool
    {
        return $this->allowInput;
    }

    public function isAllowInvalidPreload(): bool
    {
        return $this->allowInvalidPreload;
    }

    public function allowInvalidPreload(bool $allowInvalidPreload = true): static
    {
        $this->allowInvalidPreload = $allowInvalidPreload;

        return $this;
    }

    public function getAriaDateFormat(): ?string
    {
        return $this->ariaDateFormat;
    }

    public function ariaDateFormat(?string $ariaDateFormat): static
    {
        $this->ariaDateFormat = $ariaDateFormat;

        return $this;
    }

    public function getConjunction(): ?string
    {
        return $this->conjunction;
    }

    public function conjunction(?string $conjunction): static
    {
        $this->conjunction = $conjunction;

        return $this;
    }

    public function isClickOpens(): bool
    {
        return $this->clickOpens;
    }

    public function clickOpens(bool $clickOpens = true): static
    {
        $this->clickOpens = $clickOpens;

        return $this;
    }

    public function getDefaultHour(): int
    {
        return $this->defaultHour;
    }

    public function defaultHour(int $defaultHour): static
    {
        $this->defaultHour = $defaultHour;

        return $this;
    }

    public function getDefaultMinute(): int
    {
        return $this->defaultMinute;
    }

    public function defaultMinute(int $defaultMinute): static
    {
        $this->defaultMinute = $defaultMinute;

        return $this;
    }

    public function getDefaultSeconds(): int
    {
        return $this->defaultSeconds;
    }

    public function defaultSeconds(int $defaultSeconds): static
    {
        $this->defaultSeconds = $defaultSeconds;

        return $this;
    }

    public function getDisabledDates(): array
    {
        return (array) $this->evaluate($this->disabledDates);
    }

    /**
     * Dates that can't be picked: dates, text, and `['from' => ..., 'to' => ...]` ranges.
     */
    public function disabledDates(array|Closure $disabledDates = []): static
    {
        $this->disabledDates = $disabledDates;

        return $this;
    }

    public function getEnabledDates(): ?array
    {
        $enabledDates = $this->evaluate($this->enabledDates);

        return $enabledDates === null ? null : (array) $enabledDates;
    }

    /**
     * The only dates that can be picked, in the same forms as disabledDates().
     */
    public function enabledDates(array|Closure $enabledDates = []): static
    {
        $this->enabledDates = $enabledDates;

        return $this;
    }

    public function isDisableMobile(): bool
    {
        return $this->disableMobile;
    }

    /**
     * Flatpickr is used on phones too unless this is turned off, when phones get the browser's own date input and
     * presets, footer buttons, themes and the month and week pickers don't apply there.
     */
    public function disableMobile(bool $disableMobile = true): static
    {
        $this->disableMobile = $disableMobile;

        return $this;
    }

    public function isEnableSeconds(): bool
    {
        return $this->enableSeconds;
    }

    public function enableSeconds(bool $enableSeconds = true): static
    {
        $this->enableSeconds = $enableSeconds;

        return $this;
    }

    public function getHourIncrement(): int
    {
        return $this->hourIncrement;
    }

    public function hourIncrement(int $hourIncrement = 1): static
    {
        $this->hourIncrement = $hourIncrement;

        return $this;
    }

    public function getMinuteIncrement(): int
    {
        return $this->minuteIncrement;
    }

    public function minuteIncrement(int $minuteIncrement = 5): static
    {
        $this->minuteIncrement = $minuteIncrement;

        return $this;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function timezone(string | Closure | null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->evaluate($this->timezone) ?? config('app.timezone');
    }

    public function getMaxDate(): CarbonInterface|string|null
    {
        return $this->resolveDateLimit($this->evaluate($this->maxDate));
    }

    public function maxDate(DateTimeInterface|string|Closure|null $maxDate = 'now'): static
    {
        $this->maxDate = $maxDate;

        return $this;
    }

    public function getMinDate(): CarbonInterface|string|null
    {
        return $this->resolveDateLimit($this->evaluate($this->minDate));
    }

    public function minDate(DateTimeInterface|string|Closure|null $minDate): static
    {
        $this->minDate = $minDate;

        return $this;
    }

    /**
     * A limit as a date in the app's timezone. Text Carbon can't read is sent to flatpickr as it is.
     */
    protected function resolveDateLimit(mixed $date): CarbonInterface|string|null
    {
        if (blank($date)) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date);
        }

        try {
            return Carbon::parse((string) $date, config('app.timezone'))->setTimezone(config('app.timezone'));
        } catch (InvalidFormatException) {
            return (string) $date;
        }
    }

    /**
     * Fails a picked date before minDate() or after maxDate(), or on a day disabledDates() or enabledDates() rules
     * out: the calendar keeps to them, but a request can hold anything. A month picker may pick the month minDate()
     * falls in, and its days aren't checked.
     */
    protected function validateDateLimits(Closure $fail): void
    {
        if ($this->isTime()) {
            return;
        }

        $dates = collect(Arr::wrap($this->parseState($this->getRawState())));

        if ($dates->isEmpty()) {
            return;
        }

        if (($min = $this->getMinDate()) instanceof CarbonInterface) {
            $min = $this->toPickerTimezone($min);
            $min = $this->isMonthSelect() ? $min->startOfMonth() : $min;

            if ($dates->contains(fn (CarbonInterface $date): bool => $date->lt($min))) {
                $fail('validation.after_or_equal')->translate(['date' => $this->formatDate($min)]);
            }
        }

        if (($max = $this->getMaxDate()) instanceof CarbonInterface) {
            $max = $this->toPickerTimezone($max);

            if ($dates->contains(fn (CarbonInterface $date): bool => $date->gt($max))) {
                $fail('validation.before_or_equal')->translate(['date' => $this->formatDate($max)]);
            }
        }

        if ($this->isMonthSelect()) {
            return;
        }

        $disabled = $this->resolveDayRanges($this->getDisabledDates());
        $enabled = filled($enabledDates = $this->getEnabledDates()) ? $this->resolveDayRanges($enabledDates) : null;

        $isRuledOut = fn (CarbonInterface $date): bool => $this->isDayIn($date, $disabled)
            || ($enabled !== null && ! $this->isDayIn($date, $enabled));

        if ($dates->contains($isRuledOut)) {
            $fail('validation.in')->translate();
        }
    }

    /**
     * Entries of disabledDates() or enabledDates() as `[from, to]` days ("Y-m-d") in the picker's timezone, the way
     * flatpickr reads them. Entries that aren't dates are left out.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    protected function resolveDayRanges(array $entries): array
    {
        return collect($entries)
            ->map(function (mixed $entry): ?array {
                [$from, $to] = is_array($entry)
                    ? [$this->resolveDay($entry['from'] ?? null), $this->resolveDay($entry['to'] ?? null)]
                    : [$this->resolveDay($entry), $this->resolveDay($entry)];

                return ($from && $to) ? [$from, $to] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The day a date given in code falls on in the picker, as serializeDate() sends it. Text is read in the picker's
     * format, or else as a day in the picker's timezone, like "2024-12-25" or "today".
     */
    protected function resolveDay(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return $this->toPickerTimezone(Carbon::instance($date))->format('Y-m-d');
        }

        if (! is_scalar($date) || blank($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!'.$this->getPhpDateFormat(), (string) $date, $this->getTimezone())->format('Y-m-d');
        } catch (InvalidFormatException) {
            // Not in the picker's format.
        }

        try {
            return Carbon::parse((string) $date, $this->getTimezone())->setTimezone($this->getTimezone())->format('Y-m-d');
        } catch (InvalidFormatException) {
            return null;
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $ranges
     */
    protected function isDayIn(CarbonInterface $date, array $ranges): bool
    {
        $day = $date->format('Y-m-d');

        return collect($ranges)->contains(fn (array $range): bool => $day >= $range[0] && $day <= $range[1]);
    }

    public function getMaxTime(): ?string
    {
        return $this->maxTime;
    }

    public function maxTime(?string $maxTime): static
    {
        $this->maxTime = $maxTime;

        return $this;
    }

    public function getMinTime(): ?string
    {
        return $this->minTime;
    }

    public function minTime(?string $minTime): static
    {
        $this->minTime = $minTime;

        return $this;
    }

    public function getNextArrow(): ?string
    {
        return $this->nextArrow;
    }

    public function nextArrow(?string $nextArrow = '>'): static
    {
        $this->nextArrow = $nextArrow;

        return $this;
    }

    public function isNoCalendar(): bool
    {
        return $this->isTime() || $this->noCalendar;
    }

    public function noCalendar(bool $noCalendar = true): static
    {
        $this->noCalendar = $noCalendar;

        return $this;
    }

    public function isShorthandCurrentMonth(): bool
    {
        return $this->shorthandCurrentMonth;
    }

    public function shorthandCurrentMonth(bool $shorthandCurrentMonth = true): static
    {
        $this->shorthandCurrentMonth = $shorthandCurrentMonth;

        return $this;
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function static(bool $static = true): static
    {
        $this->static = $static;

        return $this;
    }

    public function isUse24hr(): bool
    {
        return $this->use24hr;
    }

    public function use24hr(bool $use24hr = true): static
    {
        $this->use24hr = $use24hr;

        return $this;
    }

    public function isWeekNumbers(): bool
    {
        return $this->weekNumbers;
    }

    public function weekNumbers(bool $weekNumbers = true): static
    {
        $this->weekNumbers = $weekNumbers;

        return $this;
    }

    public function isWrap(): bool
    {
        return $this->wrap;
    }

    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;

        return $this;
    }

    public function getShowMonths(): int
    {
        return $this->showMonths;
    }

    public function showMonths(int $showMonths = 1): static
    {
        $this->showMonths = $showMonths;

        return $this;
    }

    public function getPosition(): string
    {
        return $this->position->value;
    }

    public function position(FlatpickrPosition $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getMonthSelectorType(): string
    {
        return $this->monthSelectorType->value;
    }

    public function monthSelectorType(FlatpickrMonthSelectorType $monthSelectorType): static
    {
        $this->monthSelectorType = $monthSelectorType;

        return $this;
    }

    public function isAnimate(): bool
    {
        return $this->animate;
    }

    public function animate(bool $animate = true): static
    {
        $this->animate = $animate;

        return $this;
    }

    public function isCloseOnSelect(): bool
    {
        return $this->closeOnSelect;
    }

    public function closeOnSelect(bool $closeOnSelect = true): static
    {
        $this->closeOnSelect = $closeOnSelect;

        return $this;
    }

    public function getPrevArrow(): ?string
    {
        return $this->prevArrow;
    }

    public function prevArrow(?string $prevArrow): static
    {
        $this->prevArrow = $prevArrow;

        return $this;
    }

    public function weekSelect(bool $weekSelect = true): static
    {
        $this->weekSelect = $weekSelect;

        return $this;
    }

    public function isWeekSelect(): bool
    {
        return $this->weekSelect;
    }

    /**
     * Flatpickr options merged over the ones the field sets.
     *
     * @param  array<string, mixed>|Closure  $config
     */
    public function customConfig(array|Closure $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomConfig(): array
    {
        return (array) $this->evaluate($this->config);
    }

    public function isSinglePicker(): bool
    {
        return $this->getMode() === FlatpickrMode::SINGLE->value;
    }

    public function range(bool $rangePicker = true): static
    {
        $this->rangePicker = $rangePicker;

        return $this;
    }

    /**
     * Whether the picker picks a range, through range() or mode(FlatpickrMode::RANGE).
     */
    public function isRangePicker(): bool
    {
        return $this->rangePicker || $this->mode === FlatpickrMode::RANGE;
    }

    public function multiple(bool $multiplePicker = true): static
    {
        $this->multiplePicker = $multiplePicker;

        return $this;
    }

    /**
     * Whether the picker picks several dates, through multiple() or mode(FlatpickrMode::MULTIPLE).
     */
    public function isMultiplePicker(): bool
    {
        return ! $this->isRangePicker() && ($this->multiplePicker || $this->mode === FlatpickrMode::MULTIPLE);
    }

    public function monthSelect(bool $monthSelect = true): static
    {
        $this->monthSelect = $monthSelect;

        return $this;
    }

    public function isMonthSelect(): bool
    {
        return $this->monthSelect;
    }

    public function altInput(bool $altInput = true): static
    {
        $this->altInput = $altInput;

        return $this;
    }

    /**
     * Month and week pickers always show their value in a separate input, in the display format.
     */
    public function isAltInput(): bool
    {
        return $this->altInput || $this->isMonthSelect() || $this->isWeekSelect();
    }

    public function enableTime(bool $enableTime = true): static
    {
        $this->enableTime = $enableTime;

        return $this;
    }

    /**
     * Whether the picker picks a time: always for a time picker, never for a month or week picker.
     */
    public function isEnableTime(): bool
    {
        if ($this->isTime()) {
            return true;
        }

        if ($this->isMonthSelect() || $this->isWeekSelect()) {
            return false;
        }

        return $this->enableTime;
    }

    /**
     * The flatpickr format of the state. Month and week pickers always use "Y-m" and "Y-m-d"; otherwise a format set
     * with dateFormat() is kept as it is.
     */
    public function dateFormat(string $dateFormat): static
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function getDateFormat(): string
    {
        return match (true) {
            $this->isTime() => $this->dateFormat ?? $this->defaultTimeFormat(),
            $this->isMonthSelect() => 'Y-m',
            $this->isWeekSelect() => 'Y-m-d',
            $this->isEnableTime() => $this->dateFormat ?? 'Y-m-d H:i:S',
            default => $this->dateFormat ?? 'Y-m-d',
        };
    }

    public function altFormat(string $altFormat): static
    {
        $this->altFormat = $altFormat;

        return $this;
    }

    public function getAltFormat(): string
    {
        return match (true) {
            $this->isTime() => $this->altFormat ?? $this->defaultTimeFormat(),
            $this->isMonthSelect() => $this->altFormat ?? 'F Y',
            $this->isWeekSelect() => $this->altFormat ?? '\Week W',
            $this->isEnableTime() => $this->altFormat ?? 'F j, Y '.$this->defaultTimeFormat(),
            default => $this->altFormat ?? 'F j, Y',
        };
    }

    protected function defaultTimeFormat(): string
    {
        $seconds = $this->isEnableSeconds() ? ':S' : '';

        return $this->isUse24hr() ? "H:i{$seconds}" : "h:i{$seconds} K";
    }

    public function theme(FlatpickrTheme|string $theme): static
    {
        $this->theme = $theme instanceof FlatpickrTheme ? $theme : FlatpickrTheme::from($theme);

        return $this;
    }

    /**
     * Shows an inline button that clears the picked value.
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
     * Shows quick date shortcuts beside the calendar. `true` uses the defaults for the picker mode; an array sets
     * custom shortcuts, each `['label' => ..., 'date' => ...]` for one date or `['label' => ..., 'start' => ...,
     * 'end' => ...]` for a range. A date is a token such as "today", text in the picker's format, or a date object.
     *
     * @param  bool|array<int, array<string, mixed>>|Closure  $presets
     */
    public function presets(bool|array|Closure $presets = true): static
    {
        $this->presets = $presets;

        return $this;
    }

    public function hasPresets(): bool
    {
        return filled($this->getPresets());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPresets(): array
    {
        $presets = $this->evaluate($this->presets);

        if (blank($presets)) {
            return [];
        }

        if (! is_array($presets)) {
            return $this->isRangePicker() ? $this->defaultRangePresets() : $this->defaultSinglePresets();
        }

        return array_values(array_map(
            fn (array $preset): array => [
                ...$preset,
                ...array_map($this->serializeDate(...), Arr::only($preset, ['date', 'start', 'end'])),
            ],
            $presets,
        ));
    }

    /**
     * Adds a "Today" shortcut to the calendar footer.
     */
    public function todayButton(bool|Closure $condition = true): static
    {
        $this->showTodayButton = $condition;

        return $this;
    }

    public function hasTodayButton(): bool
    {
        return (bool) $this->evaluate($this->showTodayButton);
    }

    /**
     * Adds a "Clear" shortcut to the calendar footer.
     */
    public function clearButton(bool|Closure $condition = true): static
    {
        $this->showClearButton = $condition;

        return $this;
    }

    public function hasClearButton(): bool
    {
        return (bool) $this->evaluate($this->showClearButton);
    }

    /**
     * Adds an "Apply" button to the calendar footer that closes the calendar, useful with a time or multiple picker.
     */
    public function applyButton(bool|Closure $condition = true): static
    {
        $this->showApplyButton = $condition;

        return $this;
    }

    public function hasApplyButton(): bool
    {
        return (bool) $this->evaluate($this->showApplyButton);
    }

    /**
     * Adds the Today, Clear and Apply buttons to the calendar footer at once.
     */
    public function footerActions(bool|Closure $condition = true): static
    {
        $this->showTodayButton = $condition;
        $this->showClearButton = $condition;
        $this->showApplyButton = $condition;

        return $this;
    }

    public function hasFooterActions(): bool
    {
        return $this->hasTodayButton() || $this->hasClearButton() || $this->hasApplyButton();
    }

    /**
     * @return array{today: string, clear: string, apply: string}
     */
    public function getActionLabels(): array
    {
        return [
            'today' => $this->translate('today'),
            'clear' => $this->translate('clear'),
            'apply' => $this->translate('apply'),
        ];
    }

    protected function translate(string $key): string
    {
        return __("tareq-alqadi-filament-flatpickr::flatpickr.{$key}");
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function defaultSinglePresets(): array
    {
        return [
            ['label' => $this->translate('today'), 'date' => 'today'],
            ['label' => $this->translate('tomorrow'), 'date' => 'tomorrow'],
            ['label' => $this->translate('in_7_days'), 'date' => 'in_7_days'],
            ['label' => $this->translate('next_month'), 'date' => 'next_month'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function defaultRangePresets(): array
    {
        return [
            ['label' => $this->translate('today'), 'start' => 'today', 'end' => 'today'],
            ['label' => $this->translate('yesterday'), 'start' => 'yesterday', 'end' => 'yesterday'],
            ['label' => $this->translate('last_7_days'), 'start' => 'last_7_days', 'end' => 'today'],
            ['label' => $this->translate('last_30_days'), 'start' => 'last_30_days', 'end' => 'today'],
            ['label' => $this->translate('this_month'), 'start' => 'start_of_month', 'end' => 'end_of_month'],
            ['label' => $this->translate('last_month'), 'start' => 'start_of_last_month', 'end' => 'end_of_last_month'],
            ['label' => $this->translate('this_year'), 'start' => 'start_of_year', 'end' => 'end_of_year'],
        ];
    }

    public function getTheme(): string
    {
        return $this->theme->value;
    }

    public function getThemeAsset(): string
    {
        return $this->resolveThemeStylesheetHref($this->getTheme());
    }

    /**
     * The Filament theme handles dark mode itself, so it needs no separate dark stylesheet.
     */
    public function usesNativeTheme(): bool
    {
        return in_array($this->getTheme(), [FlatpickrTheme::DEFAULT->value, FlatpickrTheme::FILAMENT->value], true);
    }

    public function getDarkThemeAsset(): string
    {
        return FilamentAsset::getStyleHref(
            FilamentFlatpickr::getThemeStylesheetId(FlatpickrTheme::DARK->value),
            FilamentFlatpickr::getPackageName(),
        );
    }

    public function getLightThemeAsset(): string
    {
        return FilamentAsset::getStyleHref(
            FilamentFlatpickr::getThemeStylesheetId(FlatpickrTheme::LIGHT->value),
            FilamentFlatpickr::getPackageName(),
        );
    }

    protected function resolveThemeStylesheetHref(string $theme): string
    {
        $resolved = in_array($theme, [FlatpickrTheme::DEFAULT->value, FlatpickrTheme::FILAMENT->value], true)
            ? FlatpickrTheme::FILAMENT->value
            : $theme;

        return FilamentAsset::getStyleHref(
            FilamentFlatpickr::getThemeStylesheetId($resolved),
            FilamentFlatpickr::getPackageName(),
        );
    }
}
