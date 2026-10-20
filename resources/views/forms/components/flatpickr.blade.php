@php
    $extraAlpineAttributes = $getExtraAlpineAttributes();
    $id = $getId();
    $isConcealed = $isConcealed();
    $isDisabled = $isDisabled();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixLabel = $getSuffixLabel();
    $statePath = $getStatePath();
    $livewireKey = $getLivewireKey();
    $config = array_merge($getConfig(), $getCustomConfig());
    $packageName = \TareqAlqadi\FilamentFlatpickr\FilamentFlatpickr::getPackageName();
    $stylesheetHrefs = [
        \Filament\Support\Facades\FilamentAsset::getStyleHref('flatpickr-css', $packageName),
    ];

    $usesNativeTheme = $usesNativeTheme();

    if ($isMonthSelect()) {
        $stylesheetHrefs[] = \Filament\Support\Facades\FilamentAsset::getStyleHref('month-select-style', $packageName);
    }

    if ($usesNativeTheme) {
        $stylesheetHrefs[] = $getThemeAsset();
    }

    $isClearable = $isClearable() && ! $isDisabled && ! $isReadOnly();

    $attribs = [
        'disabled' => $isDisabled,
        'theme' => $getTheme() == 'default' ? 'default' : $getTheme(),
        'themeAsset' => $getThemeAsset(),
        'lightThemeAsset' => $getLightThemeAsset(),
        'darkThemeAsset' => $getDarkThemeAsset(),
        'nativeTheme' => $usesNativeTheme,
        'monthSelect' => $isMonthSelect(),
        'weekSelect' => $isWeekSelect(),
        'mode' => $getMode(),
        'rangePicker' => $isRangePicker(),
        'presets' => $getPresets(),
        'todayButton' => $hasTodayButton(),
        'clearButton' => $hasClearButton(),
        'applyButton' => $hasApplyButton(),
        'labels' => $getActionLabels(),
    ];
@endphp
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="flatpickrDatepicker({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                packageConfig: @js($config),
                attribs: @js($attribs),
                locale: '{{ app()->getLocale() }}'
            })"
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$getDisabledDates(), $isDisabled, $isReadOnly(), $getMinDate(), $getMaxDate(), $getMode(), $isRangePicker(), $isMonthSelect()])), 0, 64) }}"
        x-ignore
        x-load
        x-load-css="@js($stylesheetHrefs)"
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flatpickr', package: \TareqAlqadi\FilamentFlatpickr\FilamentFlatpickr::getPackageName()) }}">
        @unless ($usesNativeTheme)
            <link
                x-ref="themeStylesheet"
                rel="stylesheet"
                href="{{ $getThemeAsset() }}"
            />
        @endunless
        <x-filament::input.wrapper :disabled="$isDisabled" :inline-prefix="$isPrefixInline" :inline-suffix="$isSuffixInline" :prefix="$prefixLabel"
            :prefix-actions="$prefixActions" :prefix-icon="$prefixIcon" :suffix="$suffixLabel" :suffix-actions="$suffixActions" :suffix-icon="$suffixIcon"
            :valid="!$errors->has($statePath)" @class([
                'fi-fo-text-input fi-fp-field',
                'fi-fp-field-clearable' => $isClearable,
            ]) :attributes="\Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())->class(array_filter([
                'overflow-hidden' => ! $isStatic(),
            ]))">
            <x-filament::input :attributes="\Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                ->merge($extraAlpineAttributes, escape: false)
                ->merge(
                    [
                        'autocapitalize' => $getAutocapitalize(),
                        'autocomplete' => $getAutocomplete(),
                        'autofocus' => $isAutofocused(),
                        'disabled' => $isDisabled,
                        'id' => $id,
                        'x-ref' => 'picker',
                        'inlinePrefix' =>
                            $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                        'inlineSuffix' =>
                            $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
                        'placeholder' => $getPlaceholder(),
                        'class' => $isAllowInput() ? null : 'cursor-pointer',
                        'readonly' => $isReadOnly(),
                        'required' => $isRequired() && !$isConcealed,
                        'type' => 'text',
                    ],
                    escape: false,
                )" />
            @if ($isClearable)
                <button
                    type="button"
                    x-show="state !== null && state !== '' && (! Array.isArray(state) || state.length > 0)"
                    x-cloak
                    x-on:click.stop="clear()"
                    title="{{ __('tareq-alqadi-filament-flatpickr::flatpickr.clear') }}"
                    aria-label="{{ __('tareq-alqadi-filament-flatpickr::flatpickr.clear') }}"
                    class="fi-fp-clear-btn"
                >
                    {{ svg('heroicon-m-x-mark', 'h-4 w-4') }}
                </button>
            @endif
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
