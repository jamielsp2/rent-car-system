<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\BookingStatusService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * @property Booking $record
 */
class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return 'Editar "' . $this->record->rental?->car_name . '"';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('contract')
                ->label(fn () => $this->record->contract?->isSigned() ? 'Ver contrato' : 'Firmar contrato')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color(fn () => $this->record->contract?->isSigned() ? 'success' : 'primary')
                ->url(fn () => route('contracts.delivery', $this->record))
                ->openUrlInNewTab(),

            Action::make('return')
                ->label(fn () => $this->record->contract?->isReturned() ? 'Ver devolución' : 'Revisión de devolución')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color(fn () => $this->record->contract?->isReturned() ? 'success' : 'warning')
                ->visible(fn () => (bool) $this->record->contract?->isSigned())
                ->url(fn () => route('contracts.return', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Status and payment go through the business rules instead of a plain update.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(BookingStatusService::class);

        try {
            $service->changeStatus($record, self::enum(BookingStatus::class, $data['status']));

            if (! empty($data['payment_status'])) {
                $service->changePaymentStatus($record, self::enum(PaymentStatus::class, $data['payment_status']));
            }
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar')
                ->body(collect($exception->errors())->flatten()->first())
                ->persistent()
                ->send();

            throw new Halt();
        }

        return $record->refresh();
    }

    /**
     * Saving lives in the "Publicar" card.
     */
    protected function getFormActions(): array
    {
        return [];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Reserva actualizada';
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T
     */
    protected static function enum(string $enum, mixed $value): BackedEnum
    {
        return $value instanceof $enum ? $value : $enum::from($value);
    }
}
