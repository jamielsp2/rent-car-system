<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\CustomerStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Customer;
use App\Models\Service;
use App\Services\BookingCheckout;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Booking made by staff. Same rules as the website: availability, minimum
 * days, price and invoice all come from BookingCheckout.
 *
 * @property Booking $record
 */
class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('customer_id')
                    ->label('Cliente')
                    ->options(fn () => Customer::query()->orderBy('name')->get()->mapWithKeys(
                        fn (Customer $customer) => [$customer->getKey() => $customer->name . ' · ' . $customer->email],
                    ))
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')->label('Nombre')->required()->maxLength(191),
                        TextInput::make('email')->label('Correo')->email()->required()->maxLength(191),
                        TextInput::make('phone')->label('Teléfono')->tel()->maxLength(30),
                    ])
                    ->createOptionUsing(fn (array $data) => Customer::query()->create([
                        ...$data,
                        'status' => CustomerStatus::Activated,
                    ])->getKey())
                    ->columnSpanFull(),

                Select::make('car_id')
                    ->label('Vehículo')
                    ->options(fn () => Car::query()->published()->get()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('payment_method')
                    ->label('Forma de pago')
                    ->options([
                        PaymentMethod::CashOnDelivery->value => PaymentMethod::CashOnDelivery->getLabel(),
                        PaymentMethod::BankTransfer->value => PaymentMethod::BankTransfer->getLabel(),
                    ])
                    ->default(PaymentMethod::CashOnDelivery->value)
                    ->required()
                    ->selectablePlaceholder(false),

                DateTimePicker::make('from')
                    ->label('Entrega')
                    ->required()
                    ->seconds(false),

                DateTimePicker::make('to')
                    ->label('Devolución')
                    ->required()
                    ->seconds(false)
                    ->after('from'),

                CheckboxList::make('services')
                    ->label('Servicios adicionales')
                    ->options(fn () => Service::query()->published()->orderBy('name')->get()->pluck('name', 'id'))
                    ->columns(2)
                    ->columnSpanFull(),

                Textarea::make('note')
                    ->label('Nota')
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $car = Car::query()->findOrFail($data['car_id']);
        $account = Customer::query()->findOrFail($data['customer_id']);

        try {
            return app(BookingCheckout::class)->book(
                $car,
                Carbon::parse($data['from']),
                Carbon::parse($data['to']),
                [
                    'name' => $account->name,
                    'email' => $account->email,
                    'phone' => (string) $account->phone,
                    'note' => $data['note'] ?? null,
                ],
                PaymentMethod::from($data['payment_method']),
                array_map('intval', $data['services'] ?? []),
                $account,
            );
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('No se pudo crear la reserva')
                ->body(collect($exception->errors())->flatten()->first())
                ->persistent()
                ->send();

            throw new Halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return BookingResource::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Reserva creada';
    }
}
