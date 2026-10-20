<?php

use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\Flatpickr;
use TareqAlqadi\FilamentFlatpickr\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * A picker as the app would build it, under a given app timezone.
 */
function picker(string $appTimezone = 'UTC'): Flatpickr
{
    config()->set('app.timezone', $appTimezone);
    date_default_timezone_set($appTimezone);

    return Flatpickr::make('date');
}

function freezeAt(string $moment, string $timezone = 'UTC'): void
{
    Carbon::setTestNow(Carbon::parse($moment, $timezone));
}
