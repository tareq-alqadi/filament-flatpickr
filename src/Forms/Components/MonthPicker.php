<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;

/**
 * Picks a month. Saved as its first day, "Y-m-d", unless format() says otherwise.
 */
class MonthPicker extends BasePicker
{
    use Concerns\HasDateLimits;

    public function getKind(): PickerKind
    {
        return PickerKind::Month;
    }

    protected function getDefaultDisplayFormat(): string
    {
        return 'F Y';
    }
}
