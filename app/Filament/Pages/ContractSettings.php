<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\ContractSettings as Settings;
use App\Services\ContractTerms;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Contract content: title, clauses, checklist and minimum photos.
 */
class ContractSettings extends Page
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'configuracion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Contrato de renta';

    protected static ?string $title = 'Contrato de renta';

    public ?array $data = [];

    public function mount(Settings $settings): void
    {
        $this->form->fill([
            'contract_title' => $settings->title(),
            'contract_company_rnc' => $settings->get('contract_company_rnc'),
            'contract_company_representative' => $settings->companyRepresentative(),
            'contract_min_photos' => $settings->minPhotos(),
            'contract_terms' => $settings->terms(),
            'contract_checklist_items' => implode("\n", $settings->checklistItems()),
            'contract_footer_note' => $settings->footerNote(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $variables = collect(ContractTerms::variables())
            ->map(fn (string $label, string $key) => "{{$key}} = {$label}")
            ->implode(' · ');

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Datos generales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contract_title')->label('Título del contrato')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('contract_company_rnc')->label('RNC de la empresa')->maxLength(60),
                        TextInput::make('contract_company_representative')
                            ->label('Representante de la empresa')
                            ->helperText('Nombre que aparece por defecto debajo de la firma de la empresa.')
                            ->maxLength(120),
                        TextInput::make('contract_min_photos')
                            ->label('Fotos mínimas del vehículo')
                            ->helperText('Obligatorias para firmar la entrega y la devolución. 0 = no obligatorio.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->required(),
                    ]),

                Section::make('Cláusulas')
                    ->schema([
                        RichEditor::make('contract_terms')
                            ->label('Términos y condiciones')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'h3', 'undo', 'redo'])
                            ->required(),
                        Text::make('Variables disponibles: ' . $variables),
                    ]),

                Section::make('Entrega del vehículo')
                    ->columns(2)
                    ->schema([
                        Textarea::make('contract_checklist_items')
                            ->label('Accesorios y documentos a verificar')
                            ->helperText('Uno por línea.')
                            ->rows(10),
                        Textarea::make('contract_footer_note')
                            ->label('Nota al pie del contrato')
                            ->rows(10),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Guardar')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['contract_terms'] = ContractTerms::clean((string) $data['contract_terms']);

        Setting::put(collect($data)
            ->mapWithKeys(fn ($value, string $key) => [Settings::PREFIX . $key => $value])
            ->all());

        Notification::make()->success()->title('Configuración guardada')->send();
    }
}
