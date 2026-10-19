<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * How customers can pay when they book, and the default security deposit.
 */
class PaymentSettings extends Page
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'configuracion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Pagos y depósito';

    protected static ?string $title = 'Pagos y depósito';

    protected static ?int $navigationSort = 3;

    protected const KEYS = [
        'payment_cod_status', 'payment_cod_description',
        'payment_bank_transfer_status', 'payment_bank_transfer_description',
        'rentcar_deposit_amount',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'payment_cod_status' => (bool) Setting::get('payment_cod_status'),
            'payment_cod_description' => Setting::get('payment_cod_description'),
            'payment_bank_transfer_status' => (bool) Setting::get('payment_bank_transfer_status'),
            'payment_bank_transfer_description' => Setting::get('payment_bank_transfer_description'),
            'rentcar_deposit_amount' => Setting::get('rentcar_deposit_amount', 0),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Formas de pago')
                    ->description('Lo que el cliente puede elegir al reservar en la web. El pago se confirma desde la reserva, en el panel.')
                    ->schema([
                        Toggle::make('payment_cod_status')->label('Pago contra entrega')->live(),
                        Textarea::make('payment_cod_description')
                            ->label('Instrucciones')
                            ->rows(2)
                            ->placeholder('Pagas al recibir el vehículo.')
                            ->visible(fn ($get) => $get('payment_cod_status')),
                        Toggle::make('payment_bank_transfer_status')->label('Transferencia bancaria')->live(),
                        Textarea::make('payment_bank_transfer_description')
                            ->label('Datos de la cuenta')
                            ->rows(4)
                            ->placeholder("Banco: ...\nCuenta: ...\nTitular: ...")
                            ->helperText('Se muestran al cliente al reservar y en la confirmación.')
                            ->visible(fn ($get) => $get('payment_bank_transfer_status')),
                    ]),

                Section::make('Depósito de garantía')
                    ->schema([
                        TextInput::make('rentcar_deposit_amount')
                            ->label('Depósito por defecto')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->helperText('Se aplica a los vehículos que no tengan su propio depósito. 0 = sin depósito. En moneda base.'),
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

        if (! $data['payment_cod_status'] && ! $data['payment_bank_transfer_status']) {
            Notification::make()->warning()->title('Activa al menos una forma de pago')
                ->body('Sin ninguna, los clientes no podrían completar una reserva en la web.')->send();

            return;
        }

        Setting::put(collect($data)->only(self::KEYS)->map(fn ($value) => is_bool($value) ? ($value ? '1' : '0') : ($value ?? ''))->all());

        Notification::make()->success()->title('Pagos actualizados')->send();
    }
}
