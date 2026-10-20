<?php

namespace TareqAlqadi\FilamentFlatpickr\StateCasts;

use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\BasePicker;

/**
 * Between what the browser holds (text in the picker's state format and timezone) and the value saved (text in the
 * field's format and the app's timezone). `$get()` and the saved value give the latter.
 */
class PickerStateCast implements StateCast
{
    public function __construct(
        protected BasePicker $component,
    ) {}

    /**
     * @return string|array<int, string>|null
     */
    public function get(mixed $state): mixed
    {
        return $this->component->fromState($state);
    }

    /**
     * @return string|array<int, string>|null
     */
    public function set(mixed $state): string|array|null
    {
        return $this->component->toState($state);
    }
}
