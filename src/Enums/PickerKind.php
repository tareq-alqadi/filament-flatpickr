<?php

namespace TareqAlqadi\FilamentFlatpickr\Enums;

use Carbon\CarbonInterface;

/**
 * What a picker picks. Each picker class is one kind.
 */
enum PickerKind: string
{
    case Date = 'date';
    case DateTime = 'datetime';
    case Time = 'time';
    case Range = 'range';
    case Multiple = 'multiple';
    case Month = 'month';
    case Week = 'week';

    public function hasTime(): bool
    {
        return in_array($this, [self::DateTime, self::Time], true);
    }

    public function hasCalendar(): bool
    {
        return $this !== self::Time;
    }

    /**
     * Whether the state is a list of dates rather than one.
     */
    public function isList(): bool
    {
        return in_array($this, [self::Range, self::Multiple], true);
    }

    /**
     * The format of the state the browser holds, in the picker's timezone. It has no names in it, so it reads the
     * same in every locale.
     */
    public function stateFormat(): string
    {
        return match ($this) {
            self::DateTime => 'Y-m-d H:i:s',
            self::Time => 'H:i:s',
            default => 'Y-m-d',
        };
    }

    public function flatpickrMode(): string
    {
        return match ($this) {
            self::Range => 'range',
            self::Multiple => 'multiple',
            default => 'single',
        };
    }

    /**
     * The last moment a picked value covers: the moment itself, the end of the day, or the end of the month or week.
     * A value is within a minimum date when this is on or after it.
     */
    public function endOf(CarbonInterface $date): CarbonInterface
    {
        return match ($this) {
            self::DateTime, self::Time => $date,
            self::Month => $date->avoidMutation()->endOfMonth(),
            self::Week => $date->avoidMutation()->addDays(6)->endOfDay(),
            default => $date->avoidMutation()->endOfDay(),
        };
    }
}
