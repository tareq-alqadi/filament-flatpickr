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
     * @return CarbonInterface|array<int, CarbonInterface>|string|null
     */
    public function get(mixed $state): CarbonInterface|array|string|null
    {
        return $this->component->parseState($state);
    }

    /**
     * The text the picker shows for a date, a stored value or text typed in it. Text that isn't a date is kept as it
     * is, so validation reports it instead of the field emptying while the user types.
     *
     * @return string|array<int, string>|null
     */
    public function set(mixed $state): string|array|null
    {
        if (blank($state)) {
            return null;
        }

        $parsed = $this->component->parseState($state);
        $format = $this->component->getDateFormat();

        return match (true) {
            $parsed instanceof CarbonInterface => $parsed->format($format),
            is_array($parsed) && filled($parsed) => array_map(static fn (CarbonInterface $date): string => $date->format($format), $parsed),
            is_string($parsed) => $parsed,
            default => $state,
        };
    }
}
