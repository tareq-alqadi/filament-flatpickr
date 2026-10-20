<?php

use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrMode;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrTheme;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\Flatpickr;

it('gives the same config every time it is built', function () {
    $component = picker()->enableTime()->monthSelect(false);

    $first = $component->getConfig();

    expect($component->getConfig())->toBe($first)
        ->and($component->getDateFormat())->toBe('Y-m-d H:i:s');
});

it('keeps a date format the user set on a picker with a time', function () {
    $component = picker()->enableTime()->dateFormat('d/m/Y h:i K');

    expect($component->getConfig()['dateFormat'])->toBe('d/m/Y h:i K');
});

it('uses the default formats for each kind of picker', function (Closure $configure, string $dateFormat, string $altFormat) {
    $component = $configure(picker());

    expect($component->getDateFormat())->toBe($dateFormat)
        ->and($component->getAltFormat())->toBe($altFormat);
})->with([
    'date' => [fn (Flatpickr $c) => $c, 'Y-m-d', 'F j, Y'],
    'date and time' => [fn (Flatpickr $c) => $c->enableTime(), 'Y-m-d H:i:s', 'F j, Y h:i K'],
    'date and 24h time' => [fn (Flatpickr $c) => $c->enableTime()->use24hr(), 'Y-m-d H:i:s', 'F j, Y H:i'],
    'time' => [fn (Flatpickr $c) => $c->time(), 'h:i K', 'h:i K'],
    '24h time' => [fn (Flatpickr $c) => $c->time()->use24hr(), 'H:i', 'H:i'],
    'month' => [fn (Flatpickr $c) => $c->monthSelect(), 'Y-m', 'F Y'],
    'week' => [fn (Flatpickr $c) => $c->weekSelect(), 'Y-m-d', '\Week W'],
]);

it('treats mode(RANGE) the same as range()', function () {
    $viaMode = picker()->mode(FlatpickrMode::RANGE);
    $viaRange = picker()->range();

    expect($viaMode->getMode())->toBe('range')
        ->and($viaMode->isRangePicker())->toBeTrue()
        ->and($viaMode->getConfig()['mode'])->toBe($viaRange->getConfig()['mode'])
        ->and($viaMode->parseState(['2024-01-01', '2024-01-05']))->toHaveCount(2);
});

it('sends a time picker to flatpickr as a single picker without a calendar', function () {
    $config = picker()->time()->getConfig();

    expect($config['mode'])->toBe('single')
        ->and($config['noCalendar'])->toBeTrue()
        ->and($config['enableTime'])->toBeTrue();
});

it('sends enabled dates under the key flatpickr reads', function () {
    $config = picker()->enabledDates(['2024-01-01', '2024-01-02'])->getConfig();

    expect($config)->toHaveKey('enable')
        ->and($config)->not->toHaveKey('enabled')
        ->and($config['enable'])->toBe(['2024-01-01', '2024-01-02']);
});

it('uses flatpickr on phones by default', function () {
    expect(picker()->getConfig()['disableMobile'])->toBeTrue()
        ->and(picker()->disableMobile(false)->getConfig()['disableMobile'])->toBeFalse();
});

it('keeps a read-only picker closed', function () {
    $config = picker()->allowInput()->readOnly()->getConfig();

    expect($config['clickOpens'])->toBeFalse()
        ->and($config['allowInput'])->toBeFalse();
});

it('sends min and max dates as calendar days in the picker format', function () {
    freezeAt('2024-01-15 23:30:00');

    $config = picker()->timezone('America/New_York')->minDate('2024-01-10')->maxDate('2024-02-01')->getConfig();

    expect($config['minDate'])->toBe('2024-01-10')
        ->and($config['maxDate'])->toBe('2024-02-01');
});

it('sends dates given in code in the picker format and timezone', function () {
    $config = picker()
        ->enableTime()
        ->timezone('Asia/Amman')
        ->disabledDates([
            Carbon::parse('2024-01-10 10:00:00', 'UTC'),
            ['from' => Carbon::parse('2024-02-01', 'UTC'), 'to' => '2024-02-05'],
            'today',
        ])
        ->getConfig();

    expect($config['disable'])->toBe([
        '2024-01-10 13:00:00',
        ['from' => '2024-02-01 03:00:00', 'to' => '2024-02-05'],
        'today',
    ]);
});

it('accepts setters that used to break', function () {
    $component = picker()
        ->defaultHour(9)
        ->monthSelect(false)
        ->customConfig(fn () => ['weekNumbers' => true]);

    expect($component->getDefaultHour())->toBe(9)
        ->and($component->getCustomConfig())->toBe(['weekNumbers' => true]);
});

it('shows a calendar icon, a clock icon for time, and keeps an icon the user set', function () {
    expect(picker()->getPrefixIcon())->toBe('heroicon-o-calendar-days')
        ->and(picker()->time()->getPrefixIcon())->toBe('heroicon-o-clock')
        ->and(picker()->monthSelect()->getPrefixIcon())->toBe('heroicon-o-calendar-days')
        ->and(picker()->weekSelect()->getPrefixIcon())->toBe('heroicon-o-calendar-days')
        ->and(picker()->prefixIcon('heroicon-o-star')->time()->getPrefixIcon())->toBe('heroicon-o-star')
        ->and(picker()->prefixIcon(null)->getPrefixIcon())->toBeNull();
});

it('reads the default theme from the config, as an enum or a string', function () {
    config()->set('tareq-alqadi-filament-flatpickr.default_theme', FlatpickrTheme::AIRBNB);
    expect(picker()->getTheme())->toBe('airbnb');

    config()->set('tareq-alqadi-filament-flatpickr.default_theme', 'dark');
    expect(picker()->getTheme())->toBe('dark');
});

it('leaves the alt input class empty so flatpickr copies the field classes', function () {
    expect(picker()->getConfig()['altInputClass'])->toBe('')
        ->and(picker()->clearable()->getConfig()['altInputClass'])->toBe('');
});

it('picks range presets for a range set through mode()', function () {
    $presets = picker()->mode(FlatpickrMode::RANGE)->presets()->getPresets();

    expect($presets[0])->toHaveKeys(['start', 'end']);
});

it('sends preset dates given in code in the picker format', function () {
    $presets = picker()
        ->presets([
            ['label' => 'Launch', 'date' => Carbon::parse('2024-12-25 00:00:00', 'UTC')],
            ['label' => 'Soon', 'date' => 'tomorrow'],
        ])
        ->getPresets();

    expect($presets[0]['date'])->toBe('2024-12-25')
        ->and($presets[1]['date'])->toBe('tomorrow');
});

it('converts flatpickr formats to php formats', function (string $flatpickr, string $php) {
    expect(Flatpickr::convertFormatToPhp($flatpickr))->toBe($php);
})->with([
    ['Y-m-d', 'Y-m-d'],
    ['Y-m-d H:i:S', 'Y-m-d H:i:s'],
    ['h:i K', 'g:i A'],
    ['d/m/Y G:i', 'd/m/Y h:i'],
    ['\Week W', '\W\e\e\k W'],
]);
