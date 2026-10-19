<?php

namespace App\Filament\Clusters\Reservas\Pages;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Reservas\ReservasCluster;
use App\Models\Booking;
use App\Services\BookingStatusService;
use App\Support\LocalTime;
use BackedEnum;
use Carbon\CarbonImmutable;
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
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'reservas';

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

        // Day bounds in UTC, to compare against the contract's system timestamps.
        $from = CarbonImmutable::parse($day->toDateString(), LocalTime::zone())->startOfDay()->utc();
        $to = $from->addDay();

        // Scheduled for the day, plus anything actually handed over / returned that day (even if scheduled for another).
        $deliveries = $this->sorted(Booking::query()->with($with)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Delivered, BookingStatus::Completed])
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q
                    ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Delivered])
                    ->whereHas('rental', fn (Builder $q) => $q->whereDate('rental_start_date', $day)))
                ->orWhere(fn (Builder $q) => $q
                    ->whereIn('status', [BookingStatus::Delivered, BookingStatus::Completed])
                    ->whereHas('contract', fn (Builder $q) => $q->where('signed_at', '>=', $from)->where('signed_at', '<', $to))))
            ->get(), 'rental_start_date');
        $returns = $this->sorted(Booking::query()->with($with)
            ->whereIn('status', [BookingStatus::Delivered, BookingStatus::Completed])
            ->where(fn (Builder $q) => $q
                ->whereHas('rental', fn (Builder $q) => $q->whereDate('rental_end_date', $day))
                ->orWhereHas('contract', fn (Builder $q) => $q->where('returned_at', '>=', $from)->where('returned_at', '<', $to)))
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
