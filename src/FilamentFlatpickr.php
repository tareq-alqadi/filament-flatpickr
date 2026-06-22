<?php

namespace TareqAlqadi\FilamentFlatpickr;

class FilamentFlatpickr
{
    public static function getPackageName(): string
    {
        return 'tareq-alqadi/filament-flatpickr';
    }

    public static function getThemeStylesheetId(string $theme): string
    {
        return "flatpickr-{$theme}-theme";
    }
}