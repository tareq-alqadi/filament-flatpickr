<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateRangePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\DateTimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MonthPicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\MultiDatePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\TimePicker;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\WeekPicker;

class PickerTestForm extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * @var Closure(): array<int, mixed>
     */
    public static ?Closure $components = null;

    public static array $fill = [];

    public ?array $data = [];

    public mixed $saved = null;

    public function mount(): void
    {
        // An empty fill() applies the fields' defaults.
        $this->form->fill(static::$fill ?: null);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components((static::$components)())
            ->statePath('data');
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

/**
 * @param  Closure(): (mixed|array<int, mixed>)  $components
 */
function testForm(Closure $components, array $fill = []): Testable
{
    PickerTestForm::$components = fn (): array => Arr::wrap($components());
    PickerTestForm::$fill = $fill;

    return Livewire::test(PickerTestForm::class);
}

it('renders the field', function () {
    testForm(fn () => DatePicker::make('date')->clearable()->presets()->footerActions())
        ->assertSeeHtml('filamentFlatpickr(')
        ->assertSeeHtml('fi-fp-field')
        ->assertSeeHtml('filament-flatpickr.css');
});

it('fills a stored value in the state format and saves it back', function (Closure $picker, mixed $stored, mixed $state) {
    testForm($picker, ['date' => $stored])
        ->assertSet('data.date', $state)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved.date', $stored);
})->with([
    'a day in a timezone behind the app' => [fn () => DatePicker::make('date')->timezone('America/New_York'), '2024-01-15', '2024-01-15'],
    'a moment in a timezone behind the app' => [fn () => DateTimePicker::make('date')->timezone('America/New_York'), '2024-01-15 10:00:00', '2024-01-15 05:00:00'],
    'the first 01:30 of the night the clocks go back' => [fn () => DateTimePicker::make('date')->timezone('America/New_York'), '2026-11-01 05:30:00', '2026-11-01 01:30:00'],
    'the second 01:30 of the night the clocks go back' => [fn () => DateTimePicker::make('date')->timezone('America/New_York'), '2026-11-01 06:30:00', '2026-11-01 01:30:00-05:00'],
    'a time' => [fn () => TimePicker::make('date'), '14:30', '14:30:00'],
    'a range' => [fn () => DateRangePicker::make('date'), ['2024-01-01', '2024-01-05'], ['2024-01-01', '2024-01-05']],
    'a month' => [fn () => MonthPicker::make('date')->format('Y-m'), '2024-02', '2024-02-01'],
    'a day in a format' => [fn () => DatePicker::make('date')->format('d/m/Y'), '15/01/2024', '2024-01-15'],
]);

it('saves what the browser picked', function (Closure $picker, mixed $state, mixed $saved) {
    testForm($picker)
        ->set('data.date', $state)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved.date', $saved);
})->with([
    'date' => [fn () => DatePicker::make('date'), '2024-01-15', '2024-01-15'],
    'date and time' => [fn () => DateTimePicker::make('date'), '2024-01-15 14:30:05', '2024-01-15 14:30:05'],
    'time' => [fn () => TimePicker::make('date'), '14:30:00', '14:30'],
    'range' => [fn () => DateRangePicker::make('date'), ['2024-01-05', '2024-01-01'], ['2024-01-01', '2024-01-05']],
    'multiple' => [fn () => MultiDatePicker::make('date'), ['2024-01-01', '2024-03-01'], ['2024-01-01', '2024-03-01']],
    'month' => [fn () => MonthPicker::make('date'), '2024-02-01', '2024-02-01'],
    'week' => [fn () => WeekPicker::make('date'), '2024-01-14', '2024-01-14'],
    'nothing' => [fn () => DatePicker::make('date'), null, null],
]);

it('gives $get() the value as it is saved', function () {
    testForm(fn () => [
        DateTimePicker::make('date')->timezone('America/New_York'),
        TextInput::make('copy')->dehydrateStateUsing(fn (Get $get): mixed => $get('date')),
    ])
        ->set('data.date', '2024-01-15 05:00:00')
        ->call('save')
        ->assertSet('saved.copy', '2024-01-15 10:00:00');
});

it('takes $set() and defaults as stored values or date objects', function () {
    testForm(fn () => DateTimePicker::make('date')->timezone('America/New_York')->default(Carbon::parse('2024-01-15 10:00:00')))
        ->assertSet('data.date', '2024-01-15 05:00:00');
});

it('reports a value that is not a date', function (Closure $picker, mixed $state) {
    testForm($picker)
        ->set('data.date', $state)
        ->call('save')
        ->assertHasErrors(['data.date']);
})->with([
    'text' => [fn () => DatePicker::make('date'), 'not a date'],
    'a day in another format' => [fn () => DatePicker::make('date'), '15/01/2024'],
    'a day that does not exist' => [fn () => DatePicker::make('date'), '2024-02-31'],
    'a time that does not exist' => [fn () => TimePicker::make('date'), '25:00:00'],
    'a time the clocks skip' => [fn () => DateTimePicker::make('date')->timezone('America/New_York'), '2026-03-08 02:30:00'],
    'a time with an offset the timezone does not have then' => [fn () => DateTimePicker::make('date')->timezone('America/New_York'), '2026-11-01 01:30:00+02:00'],
    'a range with an end that is not a date' => [fn () => DateRangePicker::make('date'), ['2024-01-01', 'nope']],
    'a range of three days' => [fn () => DateRangePicker::make('date'), ['2024-01-01', '2024-01-02', '2024-01-03']],
    'a list for a single picker' => [fn () => DatePicker::make('date'), ['2024-01-01']],
]);

it('reports a value the calendar would not let the user pick', function (Closure $picker, mixed $state, ?string $message) {
    $form = testForm($picker)->set('data.date', $state)->call('save');

    $message === null
        ? $form->assertHasNoErrors()
        : expect($form->errors()->first('data.date'))->toContain($message);
})->with([
    'on the min date' => [fn () => DatePicker::make('date')->minDate('2024-01-10'), '2024-01-10', null],
    'before the min date' => [fn () => DatePicker::make('date')->minDate('2024-01-10'), '2024-01-09', 'after or equal to January 10, 2024'],
    'on the max date' => [fn () => DatePicker::make('date')->maxDate('2024-01-10 15:00:00'), '2024-01-10', null],
    'after the max date' => [fn () => DatePicker::make('date')->maxDate('2024-01-10'), '2024-01-11', 'before or equal to January 10, 2024'],
    'a range ending after the max date' => [
        fn () => DateRangePicker::make('date')->maxDate(fn () => Carbon::parse('2024-01-10')),
        ['2024-01-05', '2024-01-12'],
        'before or equal to',
    ],
    'a moment before the min moment' => [
        fn () => DateTimePicker::make('date')->minDate('2024-01-10 12:00:00'),
        '2024-01-10 11:59:00',
        'after or equal to January 10, 2024 12:00',
    ],
    'a moment before the min moment in the picker timezone' => [
        fn () => DateTimePicker::make('date')->timezone('America/New_York')->minDate('2024-01-10 12:00:00'),
        '2024-01-10 06:59:00',
        'after or equal to January 10, 2024 07:00',
    ],
    'the month the min date falls in' => [fn () => MonthPicker::make('date')->minDate('2024-01-15'), '2024-01-01', null],
    'a month before the min date' => [fn () => MonthPicker::make('date')->minDate('2024-01-15'), '2023-12-01', 'after or equal to January 2024'],
    'a week the min date falls in' => [fn () => WeekPicker::make('date')->minDate('2024-01-17'), '2024-01-14', null],
    'a week before the min date' => [fn () => WeekPicker::make('date')->minDate('2024-01-17'), '2024-01-07', 'after or equal to'],
    'a disabled day' => [fn () => DatePicker::make('date')->disabledDates(['2024-01-10']), '2024-01-10', 'is invalid'],
    'a day in a disabled range' => [
        fn () => DatePicker::make('date')->disabledDates([['from' => '2024-01-08', 'to' => '2024-01-12']]),
        '2024-01-11',
        'is invalid',
    ],
    'a day after a disabled range' => [
        fn () => DatePicker::make('date')->disabledDates([['from' => '2024-01-08', 'to' => '2024-01-12']]),
        '2024-01-13',
        null,
    ],
    'a day disabled as a date object' => [
        fn () => DatePicker::make('date')->timezone('America/New_York')->disabledDates(fn () => [Carbon::parse('2024-01-10')]),
        '2024-01-10',
        'is invalid',
    ],
    'a moment on a disabled day' => [fn () => DateTimePicker::make('date')->disabledDates(['2024-01-10']), '2024-01-10 09:00:00', 'is invalid'],
    'a range starting on a disabled day' => [
        fn () => DateRangePicker::make('date')->disabledDates(['2024-01-10']),
        ['2024-01-10', '2024-01-12'],
        'is invalid',
    ],
    'an enabled day' => [fn () => DatePicker::make('date')->enabledDates(['2024-01-10']), '2024-01-10', null],
    'a day that is not enabled' => [fn () => DatePicker::make('date')->enabledDates(fn () => ['2024-01-10']), '2024-01-11', 'is invalid'],
    'a time on a minTime with seconds' => [fn () => TimePicker::make('date')->seconds()->minTime('09:00:30'), '09:00:30', null],
    'a time before a minTime with seconds' => [fn () => TimePicker::make('date')->seconds()->minTime('09:00:30'), '09:00:29', 'after or equal to'],
    'a moment on a min date with a part of a second, as the browser has it' => [
        fn () => DateTimePicker::make('date')->minDate(Carbon::parse('2026-10-21 10:00:00.500000')),
        '2026-10-21 10:00:01',
        null,
    ],
    'a moment before a min date with a part of a second' => [
        fn () => DateTimePicker::make('date')->minDate(Carbon::parse('2026-10-21 10:00:00.500000')),
        '2026-10-21 10:00:00',
        'after or equal to',
    ],
    'a moment on a max date with a part of a second' => [
        fn () => DateTimePicker::make('date')->maxDate(Carbon::parse('2026-10-21 10:00:00.500000')),
        '2026-10-21 10:00:00',
        null,
    ],
    'a time within minTime and maxTime' => [fn () => TimePicker::make('date')->minTime('09:00')->maxTime('17:00'), '09:00:00', null],
    'a time before minTime' => [fn () => TimePicker::make('date')->minTime('09:00'), '08:59:00', 'after or equal to 09:00'],
    'a time after maxTime' => [fn () => TimePicker::make('date')->maxTime('17:00'), '17:30:00', 'before or equal to 17:00'],
    'a time in a span over midnight' => [fn () => TimePicker::make('date')->minTime('22:00')->maxTime('06:00'), '23:30:00', null],
    'a time outside a span over midnight' => [fn () => TimePicker::make('date')->minTime('22:00')->maxTime('06:00'), '12:00:00', 'is invalid'],
    'a moment with a time before minTime' => [
        fn () => DateTimePicker::make('date')->minTime('09:00'),
        '2024-01-10 08:00:00',
        'after or equal to 09:00',
    ],
]);

it('requires a range to hold a date', function () {
    testForm(fn () => DateRangePicker::make('date')->required())
        ->set('data.date', [])
        ->call('save')
        ->assertHasErrors(['data.date' => 'required']);
});

it('sends the locale and translated labels', function () {
    app()->setLocale('ar');

    testForm(fn () => DatePicker::make('date')->clearable()->footerActions())
        ->assertSeeHtml('aria-label="مسح"')
        ->assertSeeHtml('يناير'); // يناير, January in Arabic, in the locale JSON.
});

it('hides the clear button on a read-only or disabled field', function (Closure $picker) {
    testForm($picker)
        ->assertSeeHtml('fi-fp-field')
        ->assertDontSeeHtml('fi-fp-clear-btn');
})->with([
    'read-only' => [fn () => DatePicker::make('date')->clearable()->readOnly()],
    'disabled' => [fn () => DatePicker::make('date')->clearable()->disabled()],
]);

it('lets a static calendar show and gives an inline one a place under the field', function () {
    testForm(fn () => DatePicker::make('date')->static())
        ->assertSeeHtml('fi-fp-field-static')
        ->assertDontSeeHtml('x-ref="inlineCalendar"');

    testForm(fn () => DatePicker::make('date')->inline())
        ->assertSeeHtml('x-ref="inlineCalendar"')
        ->assertDontSeeHtml('fi-fp-field-static');

    testForm(fn () => DatePicker::make('date')->static()->inline())
        ->assertSeeHtml('fi-fp-field-static')
        ->assertDontSeeHtml('x-ref="inlineCalendar"');
});
