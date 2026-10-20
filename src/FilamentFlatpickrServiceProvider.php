<?php

namespace TareqAlqadi\FilamentFlatpickr;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentFlatpickrServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tareq-alqadi-filament-flatpickr')
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            // Flatpickr's stylesheet, the Filament theme and the field's styles, built by `npm run build`.
            Css::make('filament-flatpickr', __DIR__.'/../resources/dist/filament-flatpickr.css')->loadedOnRequest(),
            AlpineComponent::make('filament-flatpickr', __DIR__.'/../resources/dist/filament-flatpickr.js'),
        ], package: FilamentFlatpickr::getPackageName());
    }
}
