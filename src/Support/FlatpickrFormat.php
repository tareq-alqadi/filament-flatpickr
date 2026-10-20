<?php

namespace TareqAlqadi\FilamentFlatpickr\Support;

use InvalidArgumentException;

/**
 * Converts PHP date formats, which the fields take, to flatpickr's format tokens.
 */
final class FlatpickrFormat
{
    /**
     * PHP date format characters and the flatpickr tokens that write and read the same text.
     */
    private const TOKENS = [
        'd' => 'd', 'D' => 'D', 'j' => 'j', 'l' => 'l', 'w' => 'w', 'W' => 'W',
        'F' => 'F', 'm' => 'm', 'M' => 'M', 'n' => 'n', 'y' => 'y', 'Y' => 'Y',
        'H' => 'H', 'h' => 'G', 'g' => 'h', 'i' => 'i', 's' => 'S', 'A' => 'K', 'U' => 'U',
    ];

    /**
     * A PHP date format as flatpickr's tokens. `jS` (a day with its suffix, "1st") becomes flatpickr's `J`, and
     * escaped characters stay escaped.
     *
     * @throws InvalidArgumentException for a format character flatpickr can't write.
     */
    public static function fromPhp(string $format): string
    {
        $converted = '';
        $length = strlen($format);

        for ($index = 0; $index < $length; $index++) {
            $character = $format[$index];

            if ($character === '\\') {
                $index++;

                if ($index < $length) {
                    $converted .= '\\'.$format[$index];
                }

                continue;
            }

            if (($character === 'j') && (($format[$index + 1] ?? null) === 'S')) {
                $converted .= 'J';
                $index++;

                continue;
            }

            if (isset(self::TOKENS[$character])) {
                $converted .= self::TOKENS[$character];

                continue;
            }

            if (ctype_alpha($character)) {
                throw new InvalidArgumentException(
                    "The date format character [{$character}] in [{$format}] can't be shown by flatpickr. Use "
                    .implode(', ', array_keys(self::TOKENS)).' or jS, and escape other letters with a backslash.',
                );
            }

            $converted .= $character;
        }

        return $converted;
    }

    /**
     * Whether a PHP date format shows the time on a 12-hour clock.
     */
    public static function uses12HourClock(string $format): bool
    {
        $unescaped = preg_replace('/\\\\./s', '', $format);

        return strpbrk($unescaped, 'ghA') !== false;
    }

    /**
     * Text to put in a PHP date format as it is, such as a translated "Week".
     */
    public static function escape(string $text): string
    {
        return preg_replace('/[A-Za-z\\\\]/', '\\\\$0', $text);
    }
}
