<?php

namespace App\Filament\Clusters\Reservas\Pages;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Reservas\ReservasCluster;
use App\Models\Booking;
use App\Services\BookingStatusService;
use App\Support\LocalTime;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * What has to go out and come back on a given day (today by default).
 */
class TodayHandovers extends Page
{
    protected static ?string $cluster = ReservasCluster::class;

    protected static ?string $slug = 'hoy';

    protected static ?string $title = 'Entregas y devoluciones';

    protected static ?string $breadcrumb = 'Hoy';

    protected static ?string $navigationLabel = 'Hoy';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.bookings.today';

    public string $date = '';

    public function mount(): void
    {
        $this->date = LocalTime::today()->toDateString();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Booking::query()
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->whereHas('rental', fn (Builder $q) => $q->whereDate('rental_start_date', LocalTime::today()))
            ->count()
            + Booking::query()
                ->where('status', BookingStatus::Delivered)
                ->whereHas('rental', fn (Builder $q) => $q->whereDate('rental_end_date', '<=', LocalTime::today()))
                ->count();

        return $count ? (string) $count : null;
    }

    public function shiftDay(int $days): void
    {
        $this->date = Carbon::parse($this->date)->addDays($days)->toDateString();
    }

    public function markDelivered(int $bookingId, BookingStatusService $statuses): void
    {
        $booking = Booking::query()->with('contract')->findOrFail($bookingId);

        try {
            $statuses->changeStatus($booking, BookingStatus::Delivered);
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('No se pudo marcar como entregado')
                ->body(collect($exception->errors())->flatten()->first())->persistent()->send();

            return;
        }

        Notification::make()->success()->title("{$booking->booking_number} entregado")->send();
    }

    protected function getViewData(): array
    {
        $day = Carbon::parse($this->date);
        $with = ['rental.car', 'contract', 'payment'];

        $deliveries = $this->sorted(Booking::query()->with($with)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Delivered])
            ->whereHas('rental', fn (Builder $q) => $q->whereDate('rental_start_date', $day))
            ->get(), 'rental_start_date');
        $returns = $this->sorted(Booking::query()->with($with)
            ->whereIn('status', [BookingStatus::Delivered, BookingStatus::Completed])
            ->whereHas('rental', fn (Builder $q) => $q->whereDate('rental_end_date', $day))
            ->get(), 'rental_end_date');

        return [
            'day' => $day,
            'isToday' => $day->isSameDay(LocalTime::today()),
            'today' => LocalTime::today()->toDateString(),
            'deliveries' => $deliveries,
            'deliveriesDone' => $deliveries->filter(fn (Booking $b) => static::delivered($b))->count(),
            'returns' => $returns,
            'returnsDone' => $returns->filter(fn (Booking $b) => static::returned($b))->count(),
            'overdue' => $this->sorted(Booking::query()->with($with)
                ->where('status', BookingStatus::Delivered)
                ->whereHas('rental', fn (Builder $q) => $q->where('rental_end_date', '<', LocalTime::now()))
                ->get(), 'rental_end_date'),
        ];
    }

    public static function delivered(Booking $booking): bool
    {
        return in_array($booking->status, [BookingStatus::Delivered, BookingStatus::Completed], true);
    }

    public static function returned(Booking $booking): bool
    {
        return $booking->status === BookingStatus::Completed || (bool) $booking->contract?->isReturned();
    }

    protected function sorted(Collection $bookings, string $column): Collection
    {
        return $bookings->sortBy(fn (Booking $b) => $b->rental?->{$column})->values();
    }
}
