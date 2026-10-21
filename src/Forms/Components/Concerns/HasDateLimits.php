<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components\Concerns;

use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;

/**
 * The earliest and latest date the calendar lets the user pick, which the server checks too.
 */
trait HasDateLimits
{
    protected DateTimeInterface|string|Closure|null $minDate = null;

    protected DateTimeInterface|string|Closure|null $maxDate = null;

    /**
     * A date object, text such as "2024-01-15" or "today", or a closure such as `fn (Get $get) => $get('starts_on')`.
     */
    public function minDate(DateTimeInterface|string|Closure|null $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    public function maxDate(DateTimeInterface|string|Closure|null $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    /**
     * The minimum as the picker reads it: a moment for a date and time picker, otherwise the start of its day, or of
     * its month for a month picker. The browser gets moments to the second, so a part of a second rounds the minimum
     * up and the maximum down: 10:00:00.5 is a minimum of 10:00:01, which the browser can pick.
     */
    public function getMinDate(): ?CarbonInterface
    {
        $date = $this->parseValue($this->evaluate($this->minDate));

        return ($date && $date->micro) ? $date->avoidMutation()->startOfSecond()->addSecond() : $date;
    }

    public function getMaxDate(): ?CarbonInterface
    {
        return $this->parseValue($this->evaluate($this->maxDate))?->avoidMutation()->startOfSecond();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDateLimitsConfig(): array
    {
        return array_filter([
            'minDate' => ($min = $this->getMinDate()) ? $this->formatState($min) : null,
            'maxDate' => ($max = $this->getMaxDate()) ? $this->formatState($max) : null,
        ]);
    }

    /**
     * A month or week may be picked when any of its days is within the limits, as the calendar lets the user pick it.
     *
     * @param  array<int, CarbonInterface>  $dates
     */
    protected function validateDateLimits(array $dates, Closure $fail): void
    {
        $kind = $this->getKind();

        if (($min = $this->getMinDate()) && collect($dates)->contains(fn (CarbonInterface $date): bool => $kind->endOf($date)->lt($min))) {
            $fail('validation.after_or_equal')->translate(['date' => $this->formatForDisplay($min)]);
        }

        if (($max = $this->getMaxDate()) && collect($dates)->contains(fn (CarbonInterface $date): bool => $date->gt($max))) {
            $fail('validation.before_or_equal')->translate(['date' => $this->formatForDisplay($max)]);
        }
    }
}
