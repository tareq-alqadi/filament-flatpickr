<?php

namespace TareqAlqadi\FilamentFlatpickr\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Coolsam\FilamentFlatpickr\FilamentFlatpickr
 */
class FilamentFlatpickr extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TareqAlqadi\FilamentFlatpickr\FilamentFlatpickr::class;
    }
}
