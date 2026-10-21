<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components\Concerns;

use Carbon\CarbonInterface;
use Closure;
use TareqAlqadi\FilamentFlatpickr\Support\StrictDate;

trait HasTimeOptions
{
    protected bool|Closure $hasSeconds = false;

    protected int|Closure|null $hoursStep = null;

    protected int|Closure|null $minutesStep = null;

    protected string|Closure|null $minTime = null;

    protected string|Closure|null $maxTime = null;

    public function seconds(bool|Closure $condition = true): static
    {
        $this->hasSeconds = $condition;

        return $this;
    }

    public function hasSeconds(): bool
    {
        return (bool) $this->evaluate($this->hasSeconds);
    }

    /**
     * How far the hour arrows move.
     */
    public function hoursStep(int|Closure|null $step): static
    {
        $this->hoursStep = $step;

        return $this;
    }

    /**
     * How far the minute arrows move. Flatpickr's default is 5.
     */
    public function minutesStep(int|Closure|null $step): static
    {
        $this->minutesStep = $step;

        return $this;
    }

    /**
     * The earliest time of day, such as "09:00". A minTime() later than the maxTime() allows a span over midnight.
     */
    public function minTime(string|Closure|null $time): static
    {
        $this->minTime = $time;

        return $this;
    }

    public function maxTime(string|Closure|null $time): static
    {
        $this->maxTime = $time;

        return $this;
    }

    public function getMinTime(): ?CarbonInterface
    {
        return $this->parseTimeOfDay($this->evaluate($this->minTime));
    }

    public function getMaxTime(): ?CarbonInterface
    {
        return $this->parseTimeOfDay($this->evaluate($this->maxTime));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTimeConfig(): array
    {
        return array_filter([
            'enableSeconds' => $this->hasSeconds(),
            'hourIncrement' => $this->evaluate($this->hoursStep),
            'minuteIncrement' => $this->evaluate($this->minutesStep),
            // With seconds, as the server checks them.
            'minTime' => $this->getMinTime()?->format('H:i:s'),
            'maxTime' => $this->getMaxTime()?->format('H:i:s'),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * Fails a time of day before minTime() or after maxTime(), in the picker's timezone.
     *
     * @param  array<int, CarbonInterface>  $dates
     */
    protected function validateTimeLimits(array $dates, Closure $fail): void
    {
        $min = $this->getMinTime();
        $max = $this->getMaxTime();

        if (! $min && ! $max) {
            return;
        }

        $seconds = collect($dates)->map(fn (CarbonInterface $date): int => static::secondsSinceMidnight($date));
        $minSeconds = $min ? static::secondsSinceMidnight($min) : null;
        $maxSeconds = $max ? static::secondsSinceMidnight($max) : null;

        if (($minSeconds !== null) && ($maxSeconds !== null) && ($minSeconds > $maxSeconds)) {
            if ($seconds->contains(fn (int $second): bool => ($second < $minSeconds) && ($second > $maxSeconds))) {
                $fail('validation.in')->translate();
            }

            return;
        }

        if (($minSeconds !== null) && $seconds->contains(fn (int $second): bool => $second < $minSeconds)) {
            $fail('validation.after_or_equal')->translate(['date' => $this->formatTimeOfDay($min)]);
        }

        if (($maxSeconds !== null) && $seconds->contains(fn (int $second): bool => $second > $maxSeconds)) {
            $fail('validation.before_or_equal')->translate(['date' => $this->formatTimeOfDay($max)]);
        }
    }

    protected function parseTimeOfDay(?string $time): ?CarbonInterface
    {
        if (blank($time)) {
            return null;
        }

        return StrictDate::fromFormat('H:i:s', $time, 'UTC') ?? StrictDate::fromFormat('H:i', $time, 'UTC');
    }

    protected function formatTimeOfDay(CarbonInterface $time): string
    {
        return $time->format($this->hasSeconds() ? 'H:i:s' : 'H:i');
    }

    protected static function secondsSinceMidnight(CarbonInterface $time): int
    {
        return ($time->hour * 3600) + ($time->minute * 60) + $time->second;
    }
}
