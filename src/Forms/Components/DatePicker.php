<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;

/**
 * Picks a day. Saved as "Y-m-d" unless format() says otherwise.
 */
class DatePicker extends BasePicker
{
    use Concerns\HasCalendarOptions;
    use Concerns\HasDateLimits;
    use Concerns\HasDisabledDates;
    use Concerns\HasPresets;

    public function getKind(): PickerKind
    {
        return PickerKind::Date;
    }
}
