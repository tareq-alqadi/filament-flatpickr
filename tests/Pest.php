<?php

use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function appTimezone(string $timezone): void
{
    config()->set('app.timezone', $timezone);
    date_default_timezone_set($timezone);
}

function freezeAt(string $moment, string $timezone = 'UTC'): void
{
    Carbon::setTestNow(Carbon::parse($moment, $timezone));
}
