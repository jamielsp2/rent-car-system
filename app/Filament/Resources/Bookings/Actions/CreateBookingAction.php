<?php

namespace App\Filament\Resources\Bookings\Actions;

use App\Enums\BookingStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentMethod;
use App\Filament\Forms\Components\VehiclePicker;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Customer;
use App\Models\Service;
use App\Services\BookingCheckout;
use App\Services\CustomerMatcher;
use App\Services\RentalQuote;
use App\Support\Media;
use App\Support\LocalTime;
use App\Support\Money;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin-side booking: pick or create the customer, choose the dates, and pick
 * one of the vehicles that are free in that period. The booking itself goes
 * through BookingCheckout, the same path as the public site (it re-checks
 * availability under a lock).
 */
class CreateBookingAction
{
    public static function make(): Action
    {
        return Action::make('newBooking')
            ->label('Nueva reserva')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Nueva reserva')
            ->modalSubmitActionLabel('Crear reserva')
            ->modalWidth('4xl')
            ->schema([
                Select::make('customer_id')
                    ->label('Cliente')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Customer::query()
                        ->where(fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('national_id', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Customer $customer) => [$customer->getKey() => static::customerLabel($customer)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value) => ($customer = Customer::query()->find($value)) ? static::customerLabel($customer) : null)
                    ->createOptionForm([
                        TextInput::make('name')->label('Nombre')->required()->maxLength(120),
                        TextInput::make('email')->label('Correo')->email()->required()->maxLength(120),
                        TextInput::make('phone')->label('Teléfono')->required()->maxLength(25),
                        Select::make('document_type')->label('Tipo de documento')->options(DocumentType::class)->default(DocumentType::Cedula->value)->selectablePlaceholder(false),
                        TextInput::make('national_id')->label('Número de documento')->maxLength(30),
                        FileUpload::make('document_front')
                            ->label('Foto del documento — frente')
                            ->helperText('Opcional. JPG, PNG o WEBP, máx. 5 MB.')
                            ->image()
                            ->disk(CustomerMatcher::DOCUMENT_DISK)
                            ->visibility('private')
                            ->storeFiles(false)
                            ->maxSize(5120),
                        FileUpload::make('document_back')
                            ->label('Foto del documento — reverso')
                            ->helperText('Opcional. JPG, PNG o WEBP, máx. 5 MB.')
                            ->image()
                            ->disk(CustomerMatcher::DOCUMENT_DISK)
                            ->visibility('private')
                            ->storeFiles(false)
                            ->maxSize(5120),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        $matcher = app(CustomerMatcher::class);
                        $customer = $matcher->forContact($data['name'], $data['email'], $data['phone'], $data['national_id'] ?? null, $data['document_type'] ?? null);
                        $matcher->storeDocumentPhotos($customer, $data['document_front'] ?? null, $data['document_back'] ?? null);

                        return $customer->getKey();
                    })
                    ->helperText('¿No está? Usa "Crear" para registrarlo aquí mismo.'),

                Grid::make(3)->schema([
                    DatePicker::make('from')
                        ->label('Salida')
                        ->required()
                        ->native(false)
                        ->minDate(LocalTime::today()->toDateString())
                        ->live(),
                    DatePicker::make('to')
                        ->label('Devolución')
                        ->required()
                        ->native(false)
                        ->minDate(LocalTime::today()->toDateString())
                        ->live(),
                    TimePicker::make('pickup_time')
                        ->label('Hora')
                        ->required()
                        ->seconds(false)
                        ->default('10:00')
                        ->live(),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('vehicle_search')
                        ->label('Buscar por nombre')
                        ->placeholder('Ej. Corolla, Grand Cherokee…')
                        ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                        ->live(debounce: 300)
                        ->dehydrated(false),
                    Select::make('vehicle_make')
                        ->label('Marca')
                        ->placeholder('Todas las marcas')
                        ->options(fn (Get $get) => static::baseAvailableCars($get)
                            ->pluck('make')
                            ->filter()
                            ->unique('id')
                            ->sortBy('name')
                            ->mapWithKeys(fn ($make) => [$make->getKey() => $make->name])
                            ->all())
                        ->searchable()
                        ->live()
                        ->dehydrated(false),
                ]),

                VehiclePicker::make('car_id')
                    ->label('Vehículos disponibles en esas fechas')
                    ->required()
                    ->live()
                    ->vehicles(fn (Get $get) => static::vehicleList($get))
                    ->validationMessages(['required' => 'Selecciona un vehículo disponible.']),

                CheckboxList::make('services')
                    ->label('Servicios adicionales')
                    ->options(fn () => Service::query()->published()->orderBy('name')->pluck('name', 'id')->all())
                    ->columns(2)
                    ->live(),

                Placeholder::make('quote')
                    ->label('Total estimado')
                    ->content(fn (Get $get) => static::quoteSummary($get)),

                Grid::make(3)->schema([
                    Select::make('payment_method')
                        ->label('Forma de pago')
                        ->options(collect([PaymentMethod::CashOnDelivery, PaymentMethod::BankTransfer])
                            ->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->getLabel()])
                            ->all())
                        ->default(PaymentMethod::CashOnDelivery->value)
                        ->required()
                        ->selectablePlaceholder(false),
                    Select::make('status')
                        ->label('Estado inicial')
                        ->options(collect([BookingStatus::Confirmed, BookingStatus::Pending])
                            ->mapWithKeys(fn (BookingStatus $status) => [$status->value => $status->getLabel()])
                            ->all())
                        ->default(BookingStatus::Confirmed->value)
                        ->required()
                        ->selectablePlaceholder(false),
                    TextInput::make('note')->label('Nota')->maxLength(1000),
                ]),
            ])
            ->action(function (array $data, Action $action): void {
                $customer = Customer::query()->findOrFail($data['customer_id']);
                $car = Car::query()->findOrFail($data['car_id']);
                $from = Carbon::parse("{$data['from']} {$data['pickup_time']}");
                $to = Carbon::parse("{$data['to']} {$data['pickup_time']}");

                try {
                    /** @var Booking $booking */
                    $booking = app(BookingCheckout::class)->book(
                        $car,
                        $from,
                        $to,
                        [
                            'name' => $customer->name,
                            'email' => $customer->email,
                            'phone' => (string) $customer->phone,
                            'note' => $data['note'] ?? null,
                        ],
                        PaymentMethod::from($data['payment_method']),
                        array_map('intval', $data['services'] ?? []),
                    );
                } catch (ValidationException $exception) {
                    Notification::make()->danger()
                        ->title('No se pudo crear la reserva')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->send();

                    return;
                }

                if ($data['status'] !== BookingStatus::Pending->value) {
                    $booking->update(['status' => $data['status']]);
                }

                Notification::make()->success()
                    ->title("Reserva {$booking->booking_number} creada")
                    ->actions([
                        Action::make('ver')
                            ->label('Ver reserva')
                            ->button()
                            ->url(EditBooking::getUrl(['record' => $booking])),
                    ])
                    ->send();
            });
    }

    protected static function customerLabel(Customer $customer): string
    {
        return trim($customer->name
            . ' · ' . $customer->email
            . ($customer->phone ? ' · ' . $customer->phone : '')
            . ($customer->national_id ? ' · ' . ($customer->document_type?->getLabel() ?? 'Cédula') . ' ' . $customer->national_id : ''));
    }

    /**
     * Start and end of the rental, or null while the dates are missing or invalid.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    protected static function period(Get $get): ?array
    {
        $from = $get('from');
        $to = $get('to');

        if (! $from || ! $to) {
            return null;
        }

        $time = $get('pickup_time') ?: '10:00';
        $start = Carbon::parse("{$from} {$time}");
        $end = Carbon::parse("{$to} {$time}");

        return $end->gt($start) ? [$start, $end] : null;
    }

    /**
     * Published cars with no overlapping active booking in the chosen period.
     *
     * @return \Illuminate\Support\Collection<int, Car>
     */
    /**
     * Published cars free in the chosen period, before the name and make filters.
     */
    protected static function baseAvailableCars(Get $get)
    {
        $period = static::period($get);

        if (! $period) {
            return collect();
        }

        [$from, $to] = $period;

        return Car::query()->published()->availableBetween($from, $to)->with('make')->orderBy('name')->get();
    }

    /**
     * Free cars filtered by the name search and the make (marca) dropdown.
     */
    protected static function availableCars(Get $get)
    {
        $cars = static::baseAvailableCars($get);

        $search = Str::lower(trim((string) $get('vehicle_search')));
        $makeId = $get('vehicle_make');

        if ($search !== '') {
            // Case-insensitive match on the model name.
            $cars = $cars->filter(fn (Car $car) => Str::contains(Str::lower((string) $car->name), $search));
        }

        if (filled($makeId)) {
            $cars = $cars->filter(fn (Car $car) => (string) $car->make_id === (string) $makeId);
        }

        return $cars->values();
    }

    /**
     * Rows for the vehicle list: name, make, price, cover photo and all photos.
     *
     * @return array<int, array{id: int, name: string, make: string, price: string, cover: ?string, photos: string[]}>
     */
    protected static function vehicleList(Get $get): array
    {
        return static::availableCars($get)->map(fn (Car $car) => [
            'id' => (int) $car->getKey(),
            'name' => (string) $car->name,
            'make' => (string) ($car->make?->name ?? ''),
            'price' => static::carPrice($car, $get),
            'cover' => Media::url(collect($car->images)->first()),
            'photos' => collect($car->images)->map(fn ($path) => Media::url($path))->filter()->values()->all(),
        ])->values()->all();
    }

    protected static function carPrice(Car $car, Get $get): string
    {
        $period = static::period($get);

        if (! $period) {
            return '';
        }

        $quote = app(RentalQuote::class)->quote($car, $period[0], $period[1], array_map('intval', $get('services') ?? []));

        return "{$quote['days']} día(s) · " . Money::format($quote['rental']) . ' · tarifa ' . Money::format($quote['rate']) . '/día';
    }

    protected static function quoteSummary(Get $get): string
    {
        $carId = $get('car_id');
        $period = static::period($get);

        if (! $carId || ! $period) {
            return 'Elige fechas y un vehículo.';
        }

        $car = Car::query()->find($carId);

        if (! $car) {
            return '—';
        }

        $quote = app(RentalQuote::class)->quote($car, $period[0], $period[1], array_map('intval', $get('services') ?? []));

        return Money::format($quote['total']) . " ({$quote['days']} día(s), depósito " . Money::format($quote['deposit']) . ')';
    }
}
