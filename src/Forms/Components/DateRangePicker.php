<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;

/**
 * Picks a first and last day. Saved as a list of two days in order, so cast the attribute to `array`. A range of one
 * day starts and ends on it.
 */
class DateRangePicker extends BasePicker
{
    use Concerns\HasCalendarOptions;
    use Concerns\HasDateLimits;
    use Concerns\HasDisabledDates;
    use Concerns\HasPresets;

    public function getKind(): PickerKind
    {
        return PickerKind::Range;
    }
}
