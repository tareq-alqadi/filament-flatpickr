<?php

namespace TareqAlqadi\FilamentFlatpickr\StateCasts;

use Carbon\CarbonInterface;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\Flatpickr;

/**
 * Keeps a Flatpickr's state as the text the picker shows, and gives it as dates: see Flatpickr::castsStateToDates().
 */
class FlatpickrStateCast implements StateCast
{
    public function __construct(
        protected Flatpickr $component,
    ) {}

    /**
     * The picker's text as dates in the picker's timezone.
     *
     * @return CarbonInterface|array<int, CarbonInterface>|string|null
     */
    public function get(mixed $state): CarbonInterface|array|string|null
    {
        return $this->component->parseState($state);
    }

    /**
     * The text the picker shows for a value given to the field: a stored value or default, which is in the app's
     * timezone, or a date from `$set()`.
     *
     * @return string|array<int, string>|null
     */
    public function set(mixed $state): string|array|null
    {
        return $this->component->formatStateForPicker($state);
    }
}
