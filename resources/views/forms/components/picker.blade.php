@php
    use Filament\Support\Facades\FilamentAsset;
    use TareqAlqadi\FilamentFlatpickr\FilamentFlatpickr;
    use TareqAlqadi\FilamentFlatpickr\Support\FlatpickrLocale;

    $id = $getId();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixLabel = $getSuffixLabel();
    $statePath = $getStatePath();
    $isStatic = $isStatic();
    $isInline = $isInline();
    $isClearable = $isClearable() && ! $isDisabled && ! $isReadOnly;
    $isClickable = ! $isInputAllowed() && ! $isDisabled && ! $isReadOnly;

    $picker = [
        'kind' => $getKind()->value,
        'config' => $getConfig(),
        'locale' => FlatpickrLocale::find($getLocale()),
        'firstDayOfWeek' => $getFirstDayOfWeek(),
        'presets' => $getPresets(),
        'footerActions' => $getFooterActions(),
    ];

    // The calendar is drawn once (wire:ignore), so anything it is built from redraws it when it changes.
    $pickerKey = substr(md5(serialize([$picker, $isDisabled, $isReadOnly, $isClearable])), 0, 16);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="filamentFlatpickr({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            ...@js($picker),
        })"
        wire:ignore
        wire:key="{{ $getLivewireKey() }}.{{ $pickerKey }}"
        x-ignore
        x-load
        x-load-css="@js([FilamentAsset::getStyleHref('filament-flatpickr', FilamentFlatpickr::getPackageName())])"
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('filament-flatpickr', FilamentFlatpickr::getPackageName()) }}"
    >
        <x-filament::input.wrapper
            :disabled="$isDisabled"
            :inline-prefix="$isPrefixInline"
            :inline-suffix="$isSuffixInline"
            :prefix="$prefixLabel"
            :prefix-actions="$prefixActions"
            :prefix-icon="$prefixIcon"
            :suffix="$suffixLabel"
            :suffix-actions="$suffixActions"
            :suffix-icon="$suffixIcon"
            :valid="! $errors->has($statePath)"
            @class([
                'fi-fo-text-input fi-fp-field',
                'fi-fp-field-clearable' => $isClearable,
                'fi-fp-field-clickable' => $isClickable,
                'fi-fp-field-static' => $isStatic,
            ])
            :attributes="\Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())"
        >
            <x-filament::input
                :attributes="
                    \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                        ->merge($getExtraAlpineAttributes(), escape: false)
                        ->merge([
                            'autofocus' => $isAutofocused(),
                            'disabled' => $isDisabled,
                            'id' => $id,
                            'x-ref' => 'input',
                            'inlinePrefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                            'inlineSuffix' => $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
                            'placeholder' => $getPlaceholder(),
                            'readonly' => $isReadOnly,
                            'required' => $isRequired() && ! $isConcealed(),
                            'type' => 'text',
                        ], escape: false)
                "
            />

            @if ($isClearable)
                <button
                    type="button"
                    x-show="hasValue"
                    x-cloak
                    x-on:click.stop="clear()"
                    title="{{ __('tareq-alqadi-filament-flatpickr::flatpickr.clear') }}"
                    aria-label="{{ __('tareq-alqadi-filament-flatpickr::flatpickr.clear') }}"
                    class="fi-fp-clear-btn"
                >
                    {{ svg('heroicon-m-x-mark') }}
                </button>
            @endif
        </x-filament::input.wrapper>

        @if ($isInline)
            {{-- Filament clips the field, so an inline calendar is drawn under it. --}}
            <div x-ref="inlineCalendar" class="fi-fp-inline-calendar"></div>
        @endif
    </div>
</x-dynamic-component>
