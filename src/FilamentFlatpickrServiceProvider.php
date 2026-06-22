<?php

namespace TareqAlqadi\FilamentFlatpickr;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TareqAlqadi\FilamentFlatpickr\Commands\FilamentFlatpickrCommand;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrTheme;

class FilamentFlatpickrServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('tareq-alqadi-filament-flatpickr')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(FilamentFlatpickrCommand::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            package: FilamentFlatpickr::getPackageName(),
        );
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $assets = [
            Css::make('flatpickr-css', $this->flatpickrDistPath('flatpickr.css'))->loadedOnRequest(),
            Css::make('month-select-style', $this->flatpickrDistPath('plugins/monthSelect/style.css'))->loadedOnRequest(),
            AlpineComponent::make('flatpickr', __DIR__.'/../resources/js/dist/flatpickr.js')->loadedOnRequest(),
        ];

        foreach (FlatpickrTheme::cases() as $theme) {
            $filename = match ($theme) {
                FlatpickrTheme::DEFAULT => 'light.css',
                default => "{$theme->value}.css",
            };

            $assets[] = Css::make(
                FilamentFlatpickr::getThemeStylesheetId($theme->value),
                $this->flatpickrDistPath("themes/{$filename}"),
            )->loadedOnRequest();
        }

        return $assets;
    }

    protected function flatpickrDistPath(string $path = ''): string
    {
        $base = __DIR__.'/../resources/assets/flatpickr/dist';

        return $path ? "{$base}/{$path}" : $base;
    }
}
