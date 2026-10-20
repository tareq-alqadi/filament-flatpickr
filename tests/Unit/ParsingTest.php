<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\Flatpickr;
use TareqAlqadi\FilamentFlatpickr\StateCasts\FlatpickrStateCast;

/**
 * What the picker shows for a stored value, the way the form fills it.
 */
function shown(Flatpickr $component, mixed $stored): mixed
{
    return $component->formatStateForPicker($stored);
}

it('shows a stored day as that day in any picker timezone, at any time of day', function (string $timezone, string $now) {
    freezeAt($now);

    $component = picker()->timezone($timezone);

    expect(shown($component, '2024-01-15'))->toBe('2024-01-15')
        ->and(shown($component, '2024-01-15 00:00:00'))->toBe('2024-01-15')
        ->and(shown($component, Carbon::parse('2024-01-15', 'UTC')))->toBe('2024-01-15');
})->with([
    'ahead of the app' => 'Asia/Amman',
    'behind the app' => 'America/New_York',
])->with([
    'just after midnight' => '2024-03-01 00:30:00',
    'late at night' => '2024-03-01 23:30:00',
]);

it('shows a day stored by a model in a timezone ahead of UTC as that day', function () {
    $component = picker('Asia/Amman');

    // Filament fills the form from attributesToArray(), which gives a date cast as UTC: midnight in Amman is 21:00Z.
    expect(shown($component, '2024-01-14T21:00:00.000000Z'))->toBe('2024-01-15');
});

it('shows a stored moment in the picker timezone', function () {
    $component = picker()->enableTime()->timezone('America/New_York');

    expect(shown($component, '2024-01-15 10:00:00'))->toBe('2024-01-15 05:00:00')
        ->and(shown($component, '2024-01-15T10:00:00.000000Z'))->toBe('2024-01-15 05:00:00');
});

it('does not roll a stored month over into the next one at the end of a month', function () {
    freezeAt('2024-01-31 12:00:00');

    $component = picker()->monthSelect();

    expect(shown($component, '2024-02'))->toBe('2024-02')
        ->and(shown($component, '2024-02-01 00:00:00'))->toBe('2024-02')
        ->and($component->parseState('2024-02')->toDateString())->toBe('2024-02-01');
});

it('keeps the month picker in the timezone the user set', function () {
    $component = picker()->monthSelect()->timezone('Asia/Amman');

    expect($component->getTimezone())->toBe('Asia/Amman')
        ->and($component->getConfig()['dateFormat'])->toBe('Y-m');
});

it('shows stored range and multiple values as a list', function () {
    $component = picker()->range();

    expect(shown($component, ['2024-01-01', Carbon::parse('2024-01-05')]))->toBe(['2024-01-01', '2024-01-05']);
});

it('shows a stored time in the picker format and saves it as a time', function () {
    $twelveHour = picker()->time();
    $twentyFourHour = picker()->time()->use24hr();

    expect(shown($twelveHour, '14:30:00'))->toBe('2:30 PM')
        ->and(shown($twentyFourHour, '14:30:00'))->toBe('14:30')
        ->and(Flatpickr::dehydratePickerState($twelveHour, '2:30 PM'))->toBe('14:30')
        ->and(Flatpickr::dehydratePickerState($twentyFourHour, '14:30'))->toBe('14:30');
});

it('saves nothing for a time picker given something that is not a time', function () {
    $component = picker()->time()->use24hr();

    expect(Flatpickr::dehydratePickerState($component, 'not-a-time'))->toBeNull()
        ->and(Flatpickr::dehydratePickerState($component, '25:00'))->toBeNull()
        ->and(shown($component, 'not-a-time'))->toBe('not-a-time')
        ->and(shown(picker()->time(), '2024-01-15 14:30:00'))->toBe('2:30 PM');
});

it('gives null for a date PHP would roll over', function () {
    expect(picker()->parseState('2024-02-31'))->toBeNull()
        ->and(picker()->monthSelect()->parseState('2026-13'))->toBeNull()
        ->and(picker()->enableTime()->parseState('2024-01-15 25:00:00'))->toBeNull()
        ->and(picker()->parseState('2024-02-29')->toDateString())->toBe('2024-02-29')
        ->and(Flatpickr::dehydratePickerState(picker()->monthSelect(), '2026-13'))->toBeNull();
});

it('keeps the seconds of a time picker with seconds', function () {
    $twelveHour = picker()->time()->enableSeconds();
    $twentyFourHour = picker()->time()->use24hr()->enableSeconds();

    expect(shown($twelveHour, '14:30:15'))->toBe('2:30:15 PM')
        ->and(Flatpickr::dehydratePickerState($twelveHour, '2:30:15 PM'))->toBe('14:30:15')
        ->and(shown($twentyFourHour, '14:30:15'))->toBe('14:30:15')
        ->and(Flatpickr::dehydratePickerState($twentyFourHour, '14:30:15'))->toBe('14:30:15');
});

it('saves a picked day as the same calendar day in the app timezone', function () {
    $component = picker('Asia/Amman')->timezone('America/New_York');

    $saved = Flatpickr::dehydratePickerState($component, '2024-01-15');

    expect($saved)->toBeInstanceOf(CarbonInterface::class)
        ->and($saved->toDateString())->toBe('2024-01-15')
        ->and($saved->getTimezone()->getName())->toBe('Asia/Amman');
});

it('saves a picked moment as the same moment in the app timezone', function () {
    $component = picker()->enableTime()->timezone('America/New_York');

    $saved = Flatpickr::dehydratePickerState($component, '2024-01-15 05:00:00');

    expect($saved->toDateTimeString())->toBe('2024-01-15 10:00:00')
        ->and($saved->getTimezone()->getName())->toBe('UTC');
});

it('saves both ends of a range set through mode()', function () {
    $component = picker()->mode(\TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrMode::RANGE);

    $saved = Flatpickr::dehydratePickerState($component, ['2024-01-01', '2024-01-05']);

    expect($saved)->toHaveCount(2)
        ->and($saved[1]->toDateString())->toBe('2024-01-05');
});

it('reads picker text in a custom format', function () {
    $component = picker()->dateFormat('d/m/Y');

    expect($component->parseState('05/02/2024')->toDateString())->toBe('2024-02-05')
        ->and(shown($component, '2024-02-05'))->toBe('05/02/2024');
});

it('reads a 12-hour picker with a date', function () {
    $component = picker()->enableTime()->dateFormat('Y-m-d h:i K');

    expect($component->parseState('2024-02-05 2:30 PM')->toDateTimeString())->toBe('2024-02-05 14:30:00')
        ->and(shown($component, '2024-02-05 14:30:00'))->toBe('2024-02-05 2:30 PM');
});

it('gives null for a value that is not a date', function () {
    expect(picker()->parseState('not a date'))->toBeNull()
        ->and(picker()->parseState(['not', 'a', 'date']))->toBeNull();
});

it('round-trips dates through the state cast', function () {
    $component = picker()->enableTime()->timezone('America/New_York')->castsStateToDates();
    $cast = new FlatpickrStateCast($component);

    $text = $cast->set('2024-01-15 10:00:00');
    $date = $cast->get($text);

    expect($text)->toBe('2024-01-15 05:00:00')
        ->and($date->toDateTimeString())->toBe('2024-01-15 05:00:00')
        ->and($cast->set($date))->toBe($text)
        ->and($cast->set('not a date'))->toBe('not a date');
});

it('gives the state cast lists, nothing, and text in the picker format', function () {
    $cast = new FlatpickrStateCast(picker()->range()->castsStateToDates());
    $custom = new FlatpickrStateCast(picker()->dateFormat('d/m/Y')->castsStateToDates());

    expect($cast->set(['2024-01-01', Carbon::parse('2024-01-05')]))->toBe(['2024-01-01', '2024-01-05'])
        ->and($cast->set(null))->toBeNull()
        ->and($cast->get(null))->toBeNull()
        ->and(array_map(fn ($date) => $date->toDateString(), $cast->get(['2024-01-01', '2024-01-05'])))->toBe(['2024-01-01', '2024-01-05'])
        ->and($custom->set('05/02/2024'))->toBe('05/02/2024')
        ->and($custom->get('05/02/2024')->toDateString())->toBe('2024-02-05');
});

it('reads the first date when a single picker is given a list', function () {
    expect(picker()->parseState(['2024-01-15', '2024-01-20'])->toDateString())->toBe('2024-01-15');
});

it('shows and saves the day picked in a week', function () {
    $component = picker()->weekSelect();

    expect(shown($component, '2024-01-17 10:00:00'))->toBe('2024-01-17')
        ->and(Flatpickr::dehydratePickerState($component, '2024-01-17')->toDateString())->toBe('2024-01-17')
        ->and($component->getConfig()['altInput'])->toBeTrue()
        ->and(picker()->weekSelect()->enableTime()->isEnableTime())->toBeFalse();
});

it('shows a stored month as the month and saves its first day', function () {
    $component = picker()->monthSelect();

    expect(shown($component, '2024-02-29'))->toBe('2024-02')
        ->and(Flatpickr::dehydratePickerState($component, '2024-02')->toDateString())->toBe('2024-02-01');
});

it('pads the seconds of a stored moment', function () {
    expect(shown(picker()->enableTime(), '2024-01-15 10:00:05'))->toBe('2024-01-15 10:00:05');
});
