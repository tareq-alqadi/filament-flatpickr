<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;

/**
 * Picks any number of days. Saved as a list, so cast the attribute to `array`.
 */
class MultiDatePicker extends BasePicker
{
    use Concerns\HasCalendarOptions;
    use Concerns\HasDateLimits;
    use Concerns\HasDisabledDates;

    public function getKind(): PickerKind
    {
        return PickerKind::Multiple;
    }
}
