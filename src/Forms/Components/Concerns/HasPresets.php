<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components\Concerns;

use Closure;
use Illuminate\Support\Arr;
use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;

/**
 * Quick picks beside the calendar.
 */
trait HasPresets
{
    /**
     * Dates the browser works out when a preset is clicked, so they follow the day the user is on.
     */
    public const PRESET_TOKENS = [
        'today', 'tomorrow', 'yesterday', 'in_7_days', 'last_7_days', 'last_30_days', 'next_month',
        'start_of_month', 'end_of_month', 'start_of_last_month', 'end_of_last_month', 'start_of_year', 'end_of_year',
    ];

    /**
     * @var bool|array<int, array<string, mixed>>|Closure
     */
    protected bool|array|Closure $presets = false;

    /**
     * `true` gives the defaults: Today, Tomorrow, … or Last 7 days, This month, … for a range. An array sets your
     * own, each `['label' => ..., 'date' => ...]`, or `['label' => ..., 'start' => ..., 'end' => ...]` for a range.
     * A date is a token such as "today" (see PRESET_TOKENS), a date object, or text.
     *
     * @param  bool|array<int, array<string, mixed>>|Closure  $presets
     */
    public function presets(bool|array|Closure $presets = true): static
    {
        $this->presets = $presets;

        return $this;
    }

    /**
     * The presets with their dates as the browser reads them.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPresets(): array
    {
        $presets = $this->evaluate($this->presets);

        if (blank($presets) || ($presets === false)) {
            return [];
        }

        if ($presets === true) {
            return $this->getDefaultPresets();
        }

        return array_values(array_map(
            fn (array $preset): array => [
                ...$preset,
                ...array_map($this->resolvePresetDate(...), Arr::only($preset, ['date', 'start', 'end'])),
            ],
            $presets,
        ));
    }

    protected function resolvePresetDate(mixed $date): ?string
    {
        if (is_string($date) && in_array($date, static::PRESET_TOKENS, true)) {
            return $date;
        }

        $parsed = $this->parseValue($date);

        return $parsed ? $this->formatState($parsed) : null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function getDefaultPresets(): array
    {
        $label = fn (string $key): string => __("tareq-alqadi-filament-flatpickr::flatpickr.{$key}");

        if ($this->getKind() === PickerKind::Range) {
            return [
                ['label' => $label('today'), 'start' => 'today', 'end' => 'today'],
                ['label' => $label('yesterday'), 'start' => 'yesterday', 'end' => 'yesterday'],
                ['label' => $label('last_7_days'), 'start' => 'last_7_days', 'end' => 'today'],
                ['label' => $label('last_30_days'), 'start' => 'last_30_days', 'end' => 'today'],
                ['label' => $label('this_month'), 'start' => 'start_of_month', 'end' => 'end_of_month'],
                ['label' => $label('last_month'), 'start' => 'start_of_last_month', 'end' => 'end_of_last_month'],
                ['label' => $label('this_year'), 'start' => 'start_of_year', 'end' => 'end_of_year'],
            ];
        }

        return [
            ['label' => $label('today'), 'date' => 'today'],
            ['label' => $label('tomorrow'), 'date' => 'tomorrow'],
            ['label' => $label('in_7_days'), 'date' => 'in_7_days'],
            ['label' => $label('next_month'), 'date' => 'next_month'],
        ];
    }
}
