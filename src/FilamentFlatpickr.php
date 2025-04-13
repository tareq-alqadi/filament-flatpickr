<?php

namespace TareqAlqadi\FilamentFlatpickr;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrMode;

class FilamentFlatpickr
{
    public static function getPackageName(): string
    {
        return 'tareq-alqadi/filament-flatpickr';
    }
}