<?php

namespace TareqAlqadi\FilamentFlatpickr\Facades;

use Illuminate\Support\Facades\Facade;


class FilamentFlatpickr extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TareqAlqadi\FilamentFlatpickr\FilamentFlatpickr::class;
    }
}
