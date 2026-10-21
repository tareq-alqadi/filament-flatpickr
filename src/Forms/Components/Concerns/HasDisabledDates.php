<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components\Concerns;

use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;
use TareqAlqadi\FilamentFlatpickr\Support\StrictDate;

/**
 * Days the calendar won't let the user pick, which the server checks too.
 */
trait HasDisabledDates
{
    /**
     * @var array<int, mixed>|Closure
     */
    protected array|Closure $disabledDates = [];

    /**
     * @var array<int, mixed>|Closure|null
     */
    protected array|Closure|null $enabledDates = null;

    /**
     * Days that can't be picked: date objects, text such as "2024-12-25", and `['from' => ..., 'to' => ...]` ranges.
     *
     * @param  array<int, mixed>|Closure  $dates
     */
    public function disabledDates(array|Closure $dates): static
    {
        $this->disabledDates = $dates;

        return $this;
    }

    /**
     * The only days that can be picked, in the same forms as disabledDates().
     *
     * @param  array<int, mixed>|Closure|null  $dates
     */
    public function enabledDates(array|Closure|null $dates): static
    {
        $this->enabledDates = $dates;

        return $this;
    }

    /**
     * The disabled days as `[from, to]` pairs of "Y-m-d" days. Entries that aren't dates are left out.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function getDisabledDates(): array
    {
        return $this->resolveDayRanges((array) $this->evaluate($this->disabledDates));
    }

    /**
     * @return array<int, array{0: string, 1: string}>|null
     */
    public function getEnabledDates(): ?array
    {
        $dates = $this->evaluate($this->enabledDates);

        return ($dates === null) ? null : $this->resolveDayRanges((array) $dates);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDisabledDatesConfig(): array
    {
        $toFlatpickr = fn (array $range): string|array => ($range[0] === $range[1])
            ? $range[0]
            : ['from' => $range[0], 'to' => $range[1]];

        $config = ['disable' => array_map($toFlatpickr, $this->getDisabledDates())];

        if (($enabled = $this->getEnabledDates()) !== null) {
            $config['enable'] = array_map($toFlatpickr, $enabled);
        }

        return $config;
    }

    /**
     * @param  array<int, CarbonInterface>  $dates
     */
    protected function validateDisabledDates(array $dates, Closure $fail): void
    {
        $disabled = $this->getDisabledDates();
        $enabled = $this->getEnabledDates();

        $isRuledOut = fn (CarbonInterface $date): bool => $this->isDayIn($date, $disabled)
            || (($enabled !== null) && ! $this->isDayIn($date, $enabled));

        if (collect($dates)->contains($isRuledOut)) {
            $fail('validation.in')->translate();
        }
    }

    /**
     * @param  array<int, mixed>  $entries
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
     * The day an entry names. A date object is a moment for a date and time picker, so the day it falls on in the
     * picker's timezone; otherwise it is the day it shows in its own timezone, so `today('Asia/Hebron')` is that day in
     * Hebron. Text such as "2024-12-25" is that day wherever the picker is.
     */
    protected function resolveDay(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return ($this->getKind() === PickerKind::DateTime)
                ? Carbon::instance($date)->setTimezone($this->getTimezone())->format('Y-m-d')
                : $date->format('Y-m-d');
        }

        if (! is_string($date) || blank($date)) {
            return null;
        }

        return (StrictDate::fromFormat('Y-m-d', $date, $this->getStateTimezone())
            ?? StrictDate::fromFormat($this->getFormat(), $date, $this->getStateTimezone())
            ?? StrictDate::parse($date, $this->getStateTimezone()))
            ?->format('Y-m-d');
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $ranges
     */
    protected function isDayIn(CarbonInterface $date, array $ranges): bool
    {
        $day = $date->format('Y-m-d');

        return collect($ranges)->contains(fn (array $range): bool => ($day >= $range[0]) && ($day <= $range[1]));
    }
}
