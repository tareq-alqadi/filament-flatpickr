<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateRangePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateTimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MonthPicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MultiDatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\TimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\WeekPicker;
use TareqAlqadi\FilamentFlatpickr\Support\FlatpickrFormat;
use TareqAlqadi\FilamentFlatpickr\Support\FlatpickrLocale;

it('sets up flatpickr for each kind of picker', function (Closure $picker, array $expected) {
    expect($picker()->getConfig())->toMatchArray($expected);
})->with([
    'date' => [fn () => DatePicker::make('date'), [
        'mode' => 'single', 'dateFormat' => 'Y-m-d', 'altFormat' => 'F j, Y', 'enableTime' => false, 'noCalendar' => false,
    ]],
    'date and time' => [fn () => DateTimePicker::make('date'), [
        'mode' => 'single', 'dateFormat' => 'Y-m-d H:i:S', 'altFormat' => 'F j, Y H:i', 'enableTime' => true, 'time_24hr' => true,
    ]],
    'date and time with seconds on a 12-hour clock' => [fn () => DateTimePicker::make('date')->seconds()->displayFormat('M j, Y h:i:s A'), [
        'altFormat' => 'M j, Y G:i:S K', 'enableSeconds' => true, 'time_24hr' => false,
    ]],
    'time' => [fn () => TimePicker::make('date'), [
        'mode' => 'single', 'dateFormat' => 'H:i:S', 'altFormat' => 'H:i', 'enableTime' => true, 'noCalendar' => true,
    ]],
    'range' => [fn () => DateRangePicker::make('date'), ['mode' => 'range', 'dateFormat' => 'Y-m-d']],
    'multiple' => [fn () => MultiDatePicker::make('date'), ['mode' => 'multiple', 'dateFormat' => 'Y-m-d']],
    'month' => [fn () => MonthPicker::make('date'), ['mode' => 'single', 'dateFormat' => 'Y-m-d', 'altFormat' => 'F Y']],
    'week' => [fn () => WeekPicker::make('date'), ['mode' => 'single', 'dateFormat' => 'Y-m-d', 'altFormat' => '\W\e\e\k W, Y']],
]);

it('always shows the value in a separate input that keeps the field classes', function () {
    expect(DatePicker::make('date')->getConfig())->toMatchArray(['altInput' => true, 'altInputClass' => '']);
});

it('gives the same config every time it is built', function () {
    $picker = DateTimePicker::make('date')->minDate('2024-01-01')->seconds();

    expect($picker->getConfig())->toBe($picker->getConfig());
});

it('keeps a read-only picker closed', function () {
    $config = DatePicker::make('date')->allowInput()->readOnly()->getConfig();

    expect($config['clickOpens'])->toBeFalse()
        ->and($config['allowInput'])->toBeFalse();
});

it('uses flatpickr on phones unless told otherwise', function () {
    expect(DatePicker::make('date')->getConfig()['disableMobile'])->toBeTrue()
        ->and(DatePicker::make('date')->nativeOnMobile()->getConfig()['disableMobile'])->toBeFalse();
});

it('merges options over the ones the field sets', function () {
    $config = DatePicker::make('date')->options(fn () => ['position' => 'above', 'mode' => 'single'])->getConfig();

    expect($config['position'])->toBe('above');
});

it('sends min and max dates as calendar days', function () {
    freezeAt('2024-01-15 23:30:00');

    $config = DatePicker::make('date')
        ->timezone('America/New_York')
        ->minDate(CarbonImmutable::parse('2024-01-10 15:00:00'))
        ->maxDate(fn () => '2024-02-01')
        ->getConfig();

    expect($config['minDate'])->toBe('2024-01-10')
        ->and($config['maxDate'])->toBe('2024-02-01')
        ->and(DatePicker::make('date')->minDate('today')->getConfig()['minDate'])->toBe('2024-01-15')
        ->and(DatePicker::make('date')->getConfig())->not->toHaveKey('minDate');
});

it('sends min and max dates of a date and time picker as moments in its timezone', function () {
    $config = DateTimePicker::make('date')->timezone('Asia/Amman')->minDate('2024-01-10 10:00:00')->getConfig();

    // Amman is UTC+3 all year.
    expect($config['minDate'])->toBe('2024-01-10 13:00:00');
});

it('sends date objects as the day they show in their own timezone', function () {
    // 01:30 on 21 October in Hebron, still the 20th in UTC.
    freezeAt('2026-10-20 22:30:00');

    $config = DatePicker::make('date')
        ->minDate(Carbon::today('Asia/Hebron'))
        ->disabledDates([Carbon::today('Asia/Hebron')->addDay()])
        ->getConfig();

    expect($config['minDate'])->toBe('2026-10-21')
        ->and($config['disable'])->toBe(['2026-10-22'])
        ->and(DatePicker::make('date')->presets([['label' => 'Today', 'date' => Carbon::today('Asia/Hebron')]])->getPresets()[0]['date'])->toBe('2026-10-21')
        ->and(MonthPicker::make('date')->minDate(Carbon::parse('2026-11-01', 'Asia/Hebron'))->getConfig()['minDate'])->toBe('2026-11-01');
});

it('sends limits as precise as the server checks them', function () {
    expect(TimePicker::make('date')->seconds()->minTime('09:00:30')->getConfig()['minTime'])->toBe('09:00:30')
        ->and(DateTimePicker::make('date')->minDate(Carbon::parse('2026-10-21 10:00:00.500000'))->getConfig()['minDate'])->toBe('2026-10-21 10:00:01')
        ->and(DateTimePicker::make('date')->maxDate(Carbon::parse('2026-10-21 10:00:00.500000'))->getConfig()['maxDate'])->toBe('2026-10-21 10:00:00');
});

it('sends the min date of a month picker as its month', function () {
    expect(MonthPicker::make('date')->minDate('2024-01-15')->getConfig()['minDate'])->toBe('2024-01-01');
});

it('sends disabled and enabled days', function () {
    $config = DateTimePicker::make('date')
        ->timezone('Asia/Amman')
        ->disabledDates(fn () => [
            Carbon::parse('2024-01-10 23:00:00', 'UTC'),
            ['from' => '2024-02-01', 'to' => '2024-02-05'],
            'not a date',
        ])
        ->enabledDates([['from' => Carbon::parse('2024-01-01'), 'to' => '2024-12-31']])
        ->getConfig();

    expect($config['disable'])->toBe(['2024-01-11', ['from' => '2024-02-01', 'to' => '2024-02-05']])
        ->and($config['enable'])->toBe([['from' => '2024-01-01', 'to' => '2024-12-31']])
        ->and(DatePicker::make('date')->getConfig())->not->toHaveKey('enable');
});

it('sends time limits and steps', function () {
    $config = TimePicker::make('date')->minTime('09:00')->maxTime('17:30:00')->hoursStep(2)->minutesStep(15)->getConfig();

    expect($config)->toMatchArray(['minTime' => '09:00:00', 'maxTime' => '17:30:00', 'hourIncrement' => 2, 'minuteIncrement' => 15])
        ->and(TimePicker::make('date')->getConfig())->not->toHaveKeys(['minTime', 'hourIncrement']);
});

it('sends calendar options', function () {
    $picker = DatePicker::make('date')->numberOfMonths(2)->weekNumbers()->firstDayOfWeek(1);

    expect($picker->getConfig())->toMatchArray(['showMonths' => 2, 'weekNumbers' => true])
        ->and($picker->getFirstDayOfWeek())->toBe(1)
        ->and(DatePicker::make('date')->firstDayOfWeek(7)->getFirstDayOfWeek())->toBe(0);
});

it('lays the calendar out left to right unless it is set to rtl or the page direction', function () {
    expect(DatePicker::make('date')->getCalendarDirection())->toBe('ltr')
        ->and(DatePicker::make('date')->calendarDirection(fn () => 'rtl')->getCalendarDirection())->toBe('rtl')
        ->and(DatePicker::make('date')->calendarDirection(null)->getCalendarDirection())->toBeNull()
        ->and(DatePicker::make('date')->calendarDirection('auto')->getCalendarDirection())->toBeNull();
});

it('shows a calendar icon, a clock for a time picker, and an icon the user set', function () {
    expect(DatePicker::make('date')->getPrefixIcon())->toBe('heroicon-o-calendar-days')
        ->and(TimePicker::make('date')->getPrefixIcon())->toBe('heroicon-o-clock')
        ->and(TimePicker::make('date')->prefixIcon('heroicon-o-star')->getPrefixIcon())->toBe('heroicon-o-star')
        ->and(DatePicker::make('date')->prefixIcon(null)->getPrefixIcon())->toBeNull();
});

it('gives single and range pickers their own default presets', function () {
    expect(DatePicker::make('date')->presets()->getPresets()[0])->toHaveKey('date')
        ->and(DateRangePicker::make('date')->presets()->getPresets())->toHaveCount(7)
        ->and(DateRangePicker::make('date')->presets()->getPresets()[0])->toHaveKeys(['start', 'end'])
        ->and(DatePicker::make('date')->getPresets())->toBe([])
        ->and(TimePicker::make('date')->getPresets())->toBe([]);
});

it('sends preset dates given in code in the state format, and tokens as they are', function () {
    $presets = DatePicker::make('date')
        ->presets([
            ['label' => 'Launch', 'date' => Carbon::parse('2024-12-25 10:00:00')],
            ['label' => 'Later', 'date' => '2025-01-05'],
            ['label' => 'Soon', 'date' => 'tomorrow'],
        ])
        ->getPresets();

    expect(array_column($presets, 'date'))->toBe(['2024-12-25', '2025-01-05', 'tomorrow']);
});

it('translates the presets and buttons', function () {
    app()->setLocale('ar');

    expect(DatePicker::make('date')->footerActions()->getFooterActions())->toBe(['today' => 'اليوم', 'clear' => 'مسح', 'apply' => 'تطبيق'])
        ->and(DatePicker::make('date')->todayButton()->getFooterActions())->toBe(['today' => 'اليوم'])
        ->and(DatePicker::make('date')->presets()->getPresets()[1]['label'])->toBe('غدًا')
        ->and(WeekPicker::make('date')->getDisplayFormat())->toBe('الأسبوع W, Y');
});

it('converts PHP date formats to flatpickr tokens', function (string $php, string $flatpickr) {
    expect(FlatpickrFormat::fromPhp($php))->toBe($flatpickr);
})->with([
    ['Y-m-d', 'Y-m-d'],
    ['Y-m-d H:i:s', 'Y-m-d H:i:S'],
    ['g:i A', 'h:i K'],
    ['d/m/Y h:i', 'd/m/Y G:i'],
    ['\W\e\e\k W', '\W\e\e\k W'],
    ['jS F Y', 'J F Y'],
    ['D, M j y', 'D, M j y'],
    ['Y-m-d \a\t H:i', 'Y-m-d \a\t H:i'],
    ['U', 'U'],
]);

it('refuses a PHP format character flatpickr cannot show', function () {
    FlatpickrFormat::fromPhp('Y-m-d T');
})->throws(InvalidArgumentException::class, '[T]');

it('tells a 12-hour format from a 24-hour one', function () {
    expect(FlatpickrFormat::uses12HourClock('g:i A'))->toBeTrue()
        ->and(FlatpickrFormat::uses12HourClock('H:i'))->toBeFalse()
        ->and(FlatpickrFormat::uses12HourClock('\a\t H:i'))->toBeFalse();
});

it('finds the flatpickr locale for a Laravel locale', function (string $locale, ?string $month) {
    expect(FlatpickrLocale::find($locale)['months']['longhand'][0] ?? null)->toBe($month);
})->with([
    ['fr', 'janvier'],
    ['pt_BR', 'Janeiro'],
    ['de-AT', 'Jänner'],
    ['el', 'Ιανουάριος'],
    ['en', null],
    ['xx', null],
    ['../../etc', null],
]);

it('sends day suffixes in place of the function flatpickr uses for them', function () {
    expect(FlatpickrLocale::find('fr')['ordinals'][0])->toBe('er')
        ->and(FlatpickrLocale::find('fr'))->not->toHaveKey('ordinal');
});
