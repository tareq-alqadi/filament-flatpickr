<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;

/**
 * Picks a day and a time: a moment, shown in timezone() and saved in the app's timezone as "Y-m-d H:i:s" unless
 * format() says otherwise.
 */
class DateTimePicker extends BasePicker
{
    use Concerns\HasCalendarOptions;
    use Concerns\HasDateLimits;
    use Concerns\HasDisabledDates;
    use Concerns\HasTimeOptions;

    public function getKind(): PickerKind
    {
        return PickerKind::DateTime;
    }

    protected function getDefaultDisplayFormat(): string
    {
        return $this->hasSeconds() ? 'F j, Y H:i:s' : 'F j, Y H:i';
    }
}
