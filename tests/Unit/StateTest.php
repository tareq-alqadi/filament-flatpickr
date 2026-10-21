<?php

use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateRangePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateTimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MonthPicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MultiDatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\TimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\WeekPicker;
use TareqAlqadi\FilamentFlatpickr\StateCasts\PickerStateCast;

describe('a stored value in the browser', function () {
    it('is the same day in any picker timezone, at any time of day', function (string $timezone, string $now) {
        freezeAt($now);

        $picker = DatePicker::make('date')->timezone($timezone);

        expect($picker->toState('2024-01-15'))->toBe('2024-01-15')
            ->and($picker->toState('2024-01-15 00:00:00'))->toBe('2024-01-15')
            ->and($picker->toState(Carbon::parse('2024-01-15', 'UTC')))->toBe('2024-01-15');
    })->with([
        'ahead of the app' => 'Asia/Amman',
        'behind the app' => 'America/New_York',
    ])->with([
        'just after midnight' => '2024-03-01 00:30:00',
        'late at night' => '2024-03-01 23:30:00',
    ]);

    it('is the day a model stored in a timezone ahead of UTC', function () {
        appTimezone('Asia/Amman');

        // Filament fills the form from attributesToArray(), which gives a date cast as UTC: midnight in Amman is 21:00Z.
        expect(DatePicker::make('date')->toState('2024-01-14T21:00:00.000000Z'))->toBe('2024-01-15');
    });

    it('is the day a date object shows in its own timezone', function () {
        // 01:30 on 21 October in Hebron, still the 20th in UTC.
        freezeAt('2026-10-20 22:30:00');

        expect(DatePicker::make('date')->toState(Carbon::today('Asia/Hebron')))->toBe('2026-10-21')
            ->and(DatePicker::make('date')->toState(Carbon::now('Asia/Hebron')))->toBe('2026-10-21')
            ->and(DateRangePicker::make('date')->toState([Carbon::today('Asia/Hebron'), Carbon::today('Asia/Hebron')->addDay()]))->toBe(['2026-10-21', '2026-10-22'])
            ->and(MonthPicker::make('date')->toState(Carbon::parse('2026-11-01', 'Asia/Hebron')))->toBe('2026-11-01')
            ->and(DateTimePicker::make('date')->toState(Carbon::today('Asia/Hebron')))->toBe('2026-10-20 21:00:00');
    });

    it('is a moment in the picker timezone', function () {
        $picker = DateTimePicker::make('date')->timezone('America/New_York');

        expect($picker->toState('2024-01-15 10:00:00'))->toBe('2024-01-15 05:00:00')
            ->and($picker->toState('2024-01-15T10:00:00.000000Z'))->toBe('2024-01-15 05:00:00')
            ->and($picker->toState(Carbon::parse('2024-01-15 10:00:05')))->toBe('2024-01-15 05:00:05');
    });

    it('reads text in the format the field saves', function () {
        expect(DatePicker::make('date')->format('d/m/Y')->toState('05/02/2024'))->toBe('2024-02-05')
            ->and(DateTimePicker::make('date')->format('d/m/Y h:i A')->toState('05/02/2024 02:30 PM'))->toBe('2024-02-05 14:30:00');
    });

    it('is the first day of a stored month, without rolling over at the end of a month', function () {
        freezeAt('2024-01-31 12:00:00');

        $picker = MonthPicker::make('date');

        expect($picker->toState('2024-02'))->toBe('2024-02-01')
            ->and($picker->toState('2024-02-29'))->toBe('2024-02-01')
            ->and($picker->toState('2024-02-01 00:00:00'))->toBe('2024-02-01');
    });

    it('is a list for a range or multiple picker', function () {
        expect(DateRangePicker::make('date')->toState(['2024-01-01', Carbon::parse('2024-01-05')]))->toBe(['2024-01-01', '2024-01-05'])
            ->and(MultiDatePicker::make('date')->toState(['2024-01-01', 'nope', '2024-03-01']))->toBe(['2024-01-01', '2024-03-01'])
            ->and(DateRangePicker::make('date')->toState([]))->toBeNull();
    });

    it('is a time with seconds for a time picker', function () {
        $picker = TimePicker::make('date');

        expect($picker->toState('14:30'))->toBe('14:30:00')
            ->and($picker->toState('14:30:15'))->toBe('14:30:15')
            ->and($picker->toState('2024-01-15 14:30:00'))->toBe('14:30:00')
            ->and($picker->toState('2:30 PM'))->toBe('14:30:00');
    });

    it('is nothing for a value that is not a date, or one PHP would roll over', function () {
        expect(DatePicker::make('date')->toState('not a date'))->toBeNull()
            ->and(DatePicker::make('date')->toState('2024-02-31'))->toBeNull()
            ->and(TimePicker::make('date')->toState('25:00'))->toBeNull()
            ->and(DatePicker::make('date')->toState(['nope']))->toBeNull();
    });

    it('is the first date when a single picker is given a list', function () {
        expect(DatePicker::make('date')->toState(['2024-01-15', '2024-01-20']))->toBe('2024-01-15');
    });
});

describe('a picked value saved', function () {
    it('is the same calendar day in the app timezone', function () {
        appTimezone('Asia/Amman');

        expect(DatePicker::make('date')->timezone('America/New_York')->fromState('2024-01-15'))->toBe('2024-01-15');
    });

    it('is the same moment in the app timezone', function () {
        expect(DateTimePicker::make('date')->timezone('America/New_York')->fromState('2024-01-15 05:00:00'))->toBe('2024-01-15 10:00:00');
    });

    it('is in the format the field saves', function (Closure $picker, mixed $state, mixed $saved) {
        expect($picker()->fromState($state))->toBe($saved);
    })->with([
        'date' => [fn () => DatePicker::make('date'), '2024-01-15', '2024-01-15'],
        'date in a format' => [fn () => DatePicker::make('date')->format('d/m/Y'), '2024-01-15', '15/01/2024'],
        'date and time' => [fn () => DateTimePicker::make('date'), '2024-01-15 14:30:05', '2024-01-15 14:30:05'],
        'time' => [fn () => TimePicker::make('date'), '14:30:00', '14:30'],
        'time with seconds' => [fn () => TimePicker::make('date')->seconds(), '14:30:15', '14:30:15'],
        'month' => [fn () => MonthPicker::make('date'), '2024-02-01', '2024-02-01'],
        'month in a format' => [fn () => MonthPicker::make('date')->format('Y-m'), '2024-02-01', '2024-02'],
        'week' => [fn () => WeekPicker::make('date'), '2024-01-14', '2024-01-14'],
        'multiple' => [fn () => MultiDatePicker::make('date'), ['2024-03-01', '2024-01-01'], ['2024-03-01', '2024-01-01']],
        'range' => [fn () => DateRangePicker::make('date'), ['2024-01-01', '2024-01-05'], ['2024-01-01', '2024-01-05']],
        'range picked backwards' => [fn () => DateRangePicker::make('date'), ['2024-01-05', '2024-01-01'], ['2024-01-01', '2024-01-05']],
        'range of one day' => [fn () => DateRangePicker::make('date'), ['2024-01-05'], ['2024-01-05', '2024-01-05']],
    ]);

    it('is what the browser sent when it is not a date, for validation to report', function () {
        expect(DatePicker::make('date')->fromState('not a date'))->toBe('not a date')
            ->and(DatePicker::make('date')->fromState('2024-02-31'))->toBe('2024-02-31')
            ->and(DateRangePicker::make('date')->fromState(['2024-01-01', '2024-01-02', '2024-01-03']))->toBe(['2024-01-01', '2024-01-02', '2024-01-03']);
    });
});

it('only reads what the browser holds in the state format', function () {
    expect(DatePicker::make('date')->parsePicked('15/01/2024'))->toBeNull()
        ->and(DatePicker::make('date')->parsePicked(['2024-01-15']))->toBeNull()
        ->and(DateRangePicker::make('date')->parsePicked('2024-01-15'))->toBeNull()
        ->and(DateTimePicker::make('date')->parsePicked('2024-01-15 25:00:00'))->toBeNull()
        ->and(MonthPicker::make('date')->parsePicked('2026-13-01'))->toBeNull()
        ->and(DatePicker::make('date')->parsePicked('2024-02-29')[0]->toDateString())->toBe('2024-02-29');
});

it('does not read a time the clocks skip in the picker timezone', function () {
    $picker = DateTimePicker::make('date')->timezone('America/New_York');

    expect($picker->parsePicked('2026-03-08 02:30:00'))->toBeNull()
        ->and($picker->parsePicked('2026-03-08 03:30:00')[0]->format('Y-m-d H:i:s'))->toBe('2026-03-08 03:30:00')
        ->and($picker->parsePicked('2026-11-01 01:30:00'))->not->toBeNull();
});

it('keeps the second time of the hour the clocks go back over', function () {
    // 01:30 on 1 November 2026 happens twice in New York: at 05:30 UTC, then at 06:30 UTC.
    $picker = DateTimePicker::make('date')->timezone('America/New_York');

    expect($picker->toState('2026-11-01 05:30:00'))->toBe('2026-11-01 01:30:00')
        ->and($picker->toState('2026-11-01 06:30:00'))->toBe('2026-11-01 01:30:00-05:00')
        ->and($picker->fromState('2026-11-01 01:30:00'))->toBe('2026-11-01 05:30:00')
        ->and($picker->fromState('2026-11-01 01:30:00-05:00'))->toBe('2026-11-01 06:30:00')
        ->and($picker->parsePicked('2026-11-01 01:30:00-05:00')[0]->format('H:i T'))->toBe('01:30 EST')
        ->and($picker->parsePicked('2026-11-01 01:30:00+02:00'))->toBeNull()
        ->and(DatePicker::make('date')->parsePicked('2026-11-01-05:00'))->toBeNull();
});

it('reads a day whose midnight the clocks skip', function () {
    // Beirut moves to summer time at midnight on 29 March 2026.
    appTimezone('Asia/Beirut');

    expect(DatePicker::make('date')->parsePicked('2026-03-29')[0]->toDateString())->toBe('2026-03-29')
        ->and(DatePicker::make('date')->fromState('2026-03-29'))->toBe('2026-03-29')
        ->and(DatePicker::make('date')->toState('2026-03-29'))->toBe('2026-03-29');
});

it('round-trips through the state cast', function () {
    $cast = new PickerStateCast(DateTimePicker::make('date')->timezone('America/New_York'));

    expect($cast->set('2024-01-15 10:00:00'))->toBe('2024-01-15 05:00:00')
        ->and($cast->get('2024-01-15 05:00:00'))->toBe('2024-01-15 10:00:00')
        ->and($cast->set(null))->toBeNull()
        ->and($cast->get(''))->toBeNull();
});
