<?php

namespace TareqAlqadi\FilamentFlatpickr\Forms\Components;

use TareqAlqadi\FilamentFlatpickr\Enums\PickerKind;

/**
 * Picks a time of day. Saved as "H:i", or "H:i:s" with seconds(), unless format() says otherwise.
 */
class TimePicker extends BasePicker
{
    use Concerns\HasTimeOptions;

    public function getKind(): PickerKind
    {
        return PickerKind::Time;
    }

    protected function getDefaultDisplayFormat(): string
    {
        return $this->hasSeconds() ? 'H:i:s' : 'H:i';
    }
}
