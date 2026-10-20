<?php

use Carbon\CarbonInterface;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\Livewire;
use TareqAlqadi\FilamentFlatpickr\Enums\FlatpickrTheme;
use TareqAlqadi\FilamentFlatpickr\Forms\Components\Flatpickr;

class FlatpickrTestForm extends Component implements HasForms
{
    use InteractsWithForms;

    public static ?Closure $field = null;

    public static array $fill = [];

    public ?array $data = [];

    public mixed $saved = null;

    public function mount(): void
    {
        $this->form->fill(static::$fill);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([(static::$field)()])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->saved = $this->form->getState()['date'] ?? null;
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

function testForm(Closure $field, array $fill = []): \Livewire\Features\SupportTesting\Testable
{
    FlatpickrTestForm::$field = $field;
    FlatpickrTestForm::$fill = $fill;

    return Livewire::test(FlatpickrTestForm::class);
}

it('renders the field', function () {
    testForm(fn () => Flatpickr::make('date')->clearable()->presets()->footerActions())
        ->assertSeeHtml('flatpickrDatepicker(')
        ->assertSeeHtml('fi-fp-field');
});

it('fills a stored day and saves it back as the same day', function () {
    testForm(fn () => Flatpickr::make('date')->timezone('America/New_York'), ['date' => '2024-01-15'])
        ->assertSet('data.date', '2024-01-15')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', fn ($saved) => $saved instanceof Carbon && $saved->toDateString() === '2024-01-15');
});

it('reports text that is not a date instead of wiping it', function () {
    testForm(fn () => Flatpickr::make('date')->allowInput())
        ->set('data.date', 'not a date')
        ->call('save')
        ->assertHasErrors(['data.date'])
        ->assertSet('data.date', 'not a date');
});

it('reports a range with an end that is not a date', function () {
    testForm(fn () => Flatpickr::make('date')->range())
        ->set('data.date', ['2024-01-01', 'nope'])
        ->call('save')
        ->assertHasErrors(['data.date']);
});

it('reports a date the picker rules out', function (Closure $field, mixed $state, ?string $message) {
    $form = testForm($field)->set('data.date', $state)->call('save');

    $message === null
        ? $form->assertHasNoErrors()
        : expect($form->errors()->first('data.date'))->toContain($message);
})->with([
    'on the min date' => [fn () => Flatpickr::make('date')->minDate('2024-01-10'), '2024-01-10', null],
    'before the min date' => [fn () => Flatpickr::make('date')->minDate('2024-01-10'), '2024-01-09', 'after or equal to 2024-01-10'],
    'on the max date' => [fn () => Flatpickr::make('date')->maxDate('2024-01-10'), '2024-01-10', null],
    'after the max date' => [fn () => Flatpickr::make('date')->maxDate('2024-01-10'), '2024-01-11', 'before or equal to 2024-01-10'],
    'a range ending after the max date' => [
        fn () => Flatpickr::make('date')->range()->maxDate(fn () => Carbon::parse('2024-01-10')),
        ['2024-01-05', '2024-01-12'],
        'before or equal to 2024-01-10',
    ],
    'a moment before the min moment' => [
        fn () => Flatpickr::make('date')->enableTime()->minDate('2024-01-10 12:00:00'),
        '2024-01-10 11:59:00',
        'after or equal to 2024-01-10 12:00:00',
    ],
    'the month the min date falls in' => [fn () => Flatpickr::make('date')->monthSelect()->minDate('2024-01-15'), '2024-01', null],
    'a month before the min date' => [fn () => Flatpickr::make('date')->monthSelect()->minDate('2024-01-15'), '2023-12', 'after or equal to 2024-01'],
    'a picker timezone behind the app' => [
        fn () => Flatpickr::make('date')->timezone('America/New_York')->minDate('2024-01-10'),
        '2024-01-10',
        null,
    ],
    'a disabled day' => [fn () => Flatpickr::make('date')->disabledDates(['2024-01-10']), '2024-01-10', 'is invalid'],
    'a day in a disabled range' => [
        fn () => Flatpickr::make('date')->disabledDates([['from' => '2024-01-08', 'to' => '2024-01-12']]),
        '2024-01-11',
        'is invalid',
    ],
    'a day after a disabled range' => [
        fn () => Flatpickr::make('date')->disabledDates([['from' => '2024-01-08', 'to' => '2024-01-12']]),
        '2024-01-13',
        null,
    ],
    'a day disabled as a date object in another timezone' => [
        fn () => Flatpickr::make('date')->timezone('America/New_York')->disabledDates(fn () => [Carbon::parse('2024-01-10')]),
        '2024-01-10',
        'is invalid',
    ],
    'a moment on a disabled day' => [
        fn () => Flatpickr::make('date')->enableTime()->disabledDates(['2024-01-10']),
        '2024-01-10 09:00:00',
        'is invalid',
    ],
    'a range starting on a disabled day' => [
        fn () => Flatpickr::make('date')->range()->disabledDates(['2024-01-10']),
        ['2024-01-10', '2024-01-12'],
        'is invalid',
    ],
    'an enabled day' => [fn () => Flatpickr::make('date')->enabledDates(['2024-01-10']), '2024-01-10', null],
    'a day that is not enabled' => [fn () => Flatpickr::make('date')->enabledDates(fn () => ['2024-01-10']), '2024-01-11', 'is invalid'],
    'a day in another format that is not enabled' => [
        fn () => Flatpickr::make('date')->dateFormat('d/m/Y')->enabledDates(['10/01/2024']),
        '11/01/2024',
        'is invalid',
    ],
]);

it('saves each kind of picker', function (Closure $field, mixed $state, mixed $saved) {
    testForm($field)
        ->set('data.date', $state)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', function (mixed $value) use ($saved): bool {
            $normalize = fn (mixed $date): mixed => $date instanceof CarbonInterface ? $date->toDateTimeString() : $date;

            return (is_array($value) ? array_map($normalize, $value) : $normalize($value)) === $saved;
        });
})->with([
    'date' => [fn () => Flatpickr::make('date'), '2024-01-15', '2024-01-15 00:00:00'],
    'date and time' => [fn () => Flatpickr::make('date')->enableTime(), '2024-01-15 14:30:05', '2024-01-15 14:30:05'],
    'range' => [fn () => Flatpickr::make('date')->range(), ['2024-01-01', '2024-01-05'], ['2024-01-01 00:00:00', '2024-01-05 00:00:00']],
    'multiple' => [fn () => Flatpickr::make('date')->multiple(), ['2024-01-01', '2024-03-01'], ['2024-01-01 00:00:00', '2024-03-01 00:00:00']],
    'month' => [fn () => Flatpickr::make('date')->monthSelect(), '2024-02', '2024-02-01 00:00:00'],
    'week' => [fn () => Flatpickr::make('date')->weekSelect(), '2024-01-17', '2024-01-17 00:00:00'],
    'time' => [fn () => Flatpickr::make('date')->time(), '2:30 PM', '14:30'],
    'time with seconds' => [fn () => Flatpickr::make('date')->time()->use24hr()->enableSeconds(), '14:30:15', '14:30:15'],
]);

it('requires a range to hold a date', function () {
    testForm(fn () => Flatpickr::make('date')->range()->required())
        ->set('data.date', [])
        ->call('save')
        ->assertHasErrors(['data.date' => 'required']);
});

it('renders the app locale and translated labels', function () {
    app()->setLocale('ar');

    testForm(fn () => Flatpickr::make('date')->clearable()->footerActions())
        ->assertSeeHtml("locale: 'ar'")
        ->assertSeeHtml('aria-label="مسح"');
});

it('hides the clear button on a read-only or disabled field', function (Closure $field) {
    testForm($field)
        ->assertSeeHtml('fi-fp-field')
        ->assertDontSeeHtml('fi-fp-clear-btn');
})->with([
    'read-only' => [fn () => Flatpickr::make('date')->clearable()->readOnly()],
    'disabled' => [fn () => Flatpickr::make('date')->clearable()->disabled()],
]);

it('links the stylesheet of a flatpickr theme but not of the Filament theme', function () {
    testForm(fn () => Flatpickr::make('date')->theme(FlatpickrTheme::MATERIAL_BLUE))
        ->assertSeeHtml('x-ref="themeStylesheet"');

    testForm(fn () => Flatpickr::make('date'))
        ->assertDontSeeHtml('x-ref="themeStylesheet"');
});

it('accepts an empty optional picker', function () {
    testForm(fn () => Flatpickr::make('date'))
        ->set('data.date', null)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', null);
});

it('fills and saves dates when casting state to dates', function () {
    testForm(
        fn () => Flatpickr::make('date')->enableTime()->timezone('America/New_York')->castsStateToDates(),
        ['date' => '2024-01-15 10:00:00'],
    )
        ->assertSet('data.date', '2024-01-15 05:00:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', fn ($saved) => $saved->toDateTimeString() === '2024-01-15 10:00:00');
});
