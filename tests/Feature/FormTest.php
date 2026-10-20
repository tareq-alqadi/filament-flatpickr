<?php

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\Livewire;
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
