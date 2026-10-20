<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;
use TareqAlqadi\FilamentFlatpickr\Support\FlatpickrFormat;

/**
 * Picks a week. Saved as its first day, "Y-m-d", with the week starting on the locale's first day or
 * firstDayOfWeek().
 */
class WeekPicker extends BasePicker
{
    use Concerns\HasCalendarOptions;
    use Concerns\HasDateLimits;

    public function getKind(): PickerKind
    {
        return PickerKind::Week;
    }

    protected function getDefaultDisplayFormat(): string
    {
        return FlatpickrFormat::escape(__('tareq-alqadi-filament-flatpickr::flatpickr.week')).' W, Y';
    }
}
