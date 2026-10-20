<?php

namespace TareqAlqadi\FilamentFlatpickr\Support;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Reads dates without PHP's rollover, which reads "2024-02-31" as 2 March, "2026-13" as January 2027 and "25:00" as
 * 1:00 the next day. A date that doesn't exist gives null.
 */
final class StrictDate
{
    /**
     * Text in a PHP date format. Parts the format leaves out are the start of their unit, not now.
     */
    public static function fromFormat(string $format, string $value, string $timezone): ?CarbonInterface
    {
        return self::attempt(fn (): ?CarbonInterface => Carbon::createFromFormat('!'.$format, $value, $timezone) ?: null);
    }

    /**
     * Any text Carbon reads, such as "2024-01-15 10:00:00", "2024-01-14T21:00:00.000000Z" or "today".
     */
    public static function parse(string $value, string $timezone): ?CarbonInterface
    {
        return self::attempt(fn (): CarbonInterface => Carbon::parse($value, $timezone));
    }

    /**
     * @param  Closure(): ?CarbonInterface  $parse
     */
    private static function attempt(Closure $parse): ?CarbonInterface
    {
        try {
            $date = $parse();
        } catch (InvalidFormatException) {
            return null;
        }

        $errors = Carbon::getLastErrors();

        return ($errors && (($errors['warning_count'] > 0) || ($errors['error_count'] > 0))) ? null : $date;
    }
}
