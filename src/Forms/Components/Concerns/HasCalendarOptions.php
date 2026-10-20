<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components\Concerns;

use Closure;

trait HasCalendarOptions
{
    protected int|Closure|null $firstDayOfWeek = null;

    protected int|Closure $numberOfMonths = 1;

    protected bool|Closure $hasWeekNumbers = false;

    /**
     * 0 for Sunday to 6 for Saturday. The locale's first day by default.
     */
    public function firstDayOfWeek(int|Closure|null $day): static
    {
        $this->firstDayOfWeek = $day;

        return $this;
    }

    public function getFirstDayOfWeek(): ?int
    {
        $day = $this->evaluate($this->firstDayOfWeek);

        return ($day === null) ? null : ((int) $day % 7);
    }

    /**
     * Shows several months side by side.
     */
    public function numberOfMonths(int|Closure $number): static
    {
        $this->numberOfMonths = $number;

        return $this;
    }

    public function weekNumbers(bool|Closure $condition = true): static
    {
        $this->hasWeekNumbers = $condition;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCalendarConfig(): array
    {
        return [
            'showMonths' => max(1, (int) $this->evaluate($this->numberOfMonths)),
            'weekNumbers' => (bool) $this->evaluate($this->hasWeekNumbers),
        ];
    }
}
