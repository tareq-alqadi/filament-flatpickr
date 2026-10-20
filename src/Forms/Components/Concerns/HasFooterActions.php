<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components\Concerns;

use Closure;

trait HasFooterActions
{
    protected bool|Closure $hasTodayButton = false;

    protected bool|Closure $hasClearButton = false;

    protected bool|Closure $hasApplyButton = false;

    /**
     * Adds a button under the calendar that picks today, or now for a picker with a time.
     */
    public function todayButton(bool|Closure $condition = true): static
    {
        $this->hasTodayButton = $condition;

        return $this;
    }

    /**
     * Adds a button under the calendar that clears the field.
     */
    public function clearButton(bool|Closure $condition = true): static
    {
        $this->hasClearButton = $condition;

        return $this;
    }

    /**
     * Adds a button under the calendar that closes it, useful with a time or multiple picker.
     */
    public function applyButton(bool|Closure $condition = true): static
    {
        $this->hasApplyButton = $condition;

        return $this;
    }

    /**
     * Adds the Today, Clear and Apply buttons.
     */
    public function footerActions(bool|Closure $condition = true): static
    {
        $this->hasTodayButton = $condition;
        $this->hasClearButton = $condition;
        $this->hasApplyButton = $condition;

        return $this;
    }

    public function hasTodayButton(): bool
    {
        return (bool) $this->evaluate($this->hasTodayButton);
    }

    public function hasClearButton(): bool
    {
        return (bool) $this->evaluate($this->hasClearButton);
    }

    public function hasApplyButton(): bool
    {
        return (bool) $this->evaluate($this->hasApplyButton);
    }

    /**
     * The footer buttons to show, with their labels.
     *
     * @return array<string, string>
     */
    public function getFooterActions(): array
    {
        return array_filter([
            'today' => $this->hasTodayButton() ? __('tareq-alqadi-filament-flatpickr::flatpickr.today') : null,
            'clear' => $this->hasClearButton() ? __('tareq-alqadi-filament-flatpickr::flatpickr.clear') : null,
            'apply' => $this->hasApplyButton() ? __('tareq-alqadi-filament-flatpickr::flatpickr.apply') : null,
        ]);
    }
}
