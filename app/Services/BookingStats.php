<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Figures for the bookings summary. A booking belongs to the period of its
 * pickup date; cancelled bookings never count as revenue.
 */
class BookingStats
{
    public const PRESETS = [
        '7d' => 'Últimos 7 días',
        '30d' => 'Últimos 30 días',
        'month' => 'Este mes',
        '90d' => 'Últimos 3 meses',
        'year' => 'Este año',
        '12m' => 'Últimos 12 meses',
        'custom' => 'Personalizado',
    ];

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function range(?array $filters): array
    {
        $today = LocalTime::today();
        $preset = $filters['period'] ?? '30d';

        [$from, $to] = match ($preset) {
            '7d' => [$today->subDays(6), $today],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            '90d' => [$today->subMonths(3)->addDay(), $today],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            '12m' => [$today->subMonths(12)->addDay(), $today],
            'custom' => [
                CarbonImmutable::parse($filters['from'] ?? $today->subDays(29)),
                CarbonImmutable::parse($filters['to'] ?? $today),
            ],
            default => [$today->subDays(29), $today],
        };

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * Bookings whose pickup falls in the period.
     */
    public function bookings(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Booking::query()->whereHas('rental', fn (Builder $q) => $q->whereBetween('rental_start_date', [$from, $to]));
    }

    public function active(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $this->bookings($from, $to)->where('status', '!=', BookingStatus::Cancelled);
    }

    /**
     * @return array{bookings: int, revenue: float, collected: float, average: float, average_days: float, cancelled: int, rented_now: int, pending: int, utilization: float}
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $active = $this->active($from, $to)->with(['rental', 'payment'])->get();
        $bookings = $active->count();
        $revenue = (float) $active->sum('amount');

        return [
            'bookings' => $bookings,
            'revenue' => $revenue,
            'collected' => (float) $active->filter(fn (Booking $b) => $b->payment?->status === PaymentStatus::Completed)->sum('amount'),
            'average' => $bookings ? $revenue / $bookings : 0.0,
            'average_days' => $bookings ? round($active->avg(fn (Booking $b) => $b->rental?->days() ?? 0), 1) : 0.0,
            'cancelled' => $this->bookings($from, $to)->where('status', BookingStatus::Cancelled)->count(),
            'rented_now' => Booking::query()->where('status', BookingStatus::Delivered)->count(),
            'pending' => Booking::query()->where('status', BookingStatus::Pending)->count(),
            'utilization' => $this->utilization($from, $to),
        ];
    }

    /**
     * Share of the fleet's available days that were rented in the period.
     */
    public function utilization(CarbonInterface $from, CarbonInterface $to): float
    {
        $cars = Car::query()->published()->count();
        $periodDays = max(1, (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);

        if (! $cars) {
            return 0.0;
        }

        $rentedDays = $this->overlapping($from, $to)->get()->sum(function (Booking $booking) use ($from, $to): int {
            $start = $booking->rental->rental_start_date->max($from);
            $end = $booking->rental->rental_end_date->min($to);

            return max(0, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));
        });

        return round(min(100, $rentedDays / ($cars * $periodDays) * 100), 1);
    }

    /**
     * Bookings and revenue per day (short periods) or per month (long ones).
     *
     * @return array{labels: string[], bookings: int[], revenue: float[]}
     */
    public function trend(CarbonInterface $from, CarbonInterface $to): array
    {
        $monthly = $from->diffInDays($to) > 62;
        $rows = $this->active($from, $to)->with('rental')->get()
            ->groupBy(fn (Booking $b) => $b->rental->rental_start_date->format($monthly ? 'Y-m' : 'Y-m-d'));

        $period = $monthly
            ? CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to)
            : CarbonPeriod::create($from, '1 day', $to);

        $labels = $bookings = $revenue = [];

        foreach ($period as $date) {
            $key = $date->format($monthly ? 'Y-m' : 'Y-m-d');
            $labels[] = $monthly ? ucfirst($date->translatedFormat('M Y')) : $date->format('d/m');
            $bookings[] = isset($rows[$key]) ? $rows[$key]->count() : 0;
            $revenue[] = isset($rows[$key]) ? round((float) $rows[$key]->sum('amount'), 2) : 0.0;
        }

        return compact('labels', 'bookings', 'revenue');
    }

    /**
     * @return Collection<string, int> status label => count
     */
    public function byStatus(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $counts = $this->bookings($from, $to)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(BookingStatus::cases())
            ->mapWithKeys(fn (BookingStatus $status) => [$status->getLabel() => (int) ($counts[$status->value] ?? 0)])
            ->filter();
    }

    /**
     * Bookings of the period per status, every status present.
     *
     * @return array<string, int> status value => count
     */
    public function statusCounts(CarbonInterface $from, CarbonInterface $to): array
    {
        $counts = $this->bookings($from, $to)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(BookingStatus::cases())
            ->mapWithKeys(fn (BookingStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * Bookings per day (short periods) or month (long ones), split by status.
     *
     * @return array{labels: string[], series: array<string, int[]>}
     */
    public function volume(CarbonInterface $from, CarbonInterface $to): array
    {
        $monthly = $from->diffInDays($to) > 62;
        $format = $monthly ? 'Y-m' : 'Y-m-d';
        $rows = $this->bookings($from, $to)->with('rental')->get()
            ->groupBy(fn (Booking $b) => $b->rental->rental_start_date->format($format));

        $period = $monthly
            ? CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to)
            : CarbonPeriod::create($from, '1 day', $to);

        $labels = [];
        $series = array_fill_keys(array_map(fn (BookingStatus $s) => $s->value, BookingStatus::cases()), []);

        foreach ($period as $date) {
            $key = $date->format($format);
            $labels[] = $monthly ? ucfirst($date->translatedFormat('M Y')) : $date->format('d/m');
            $counts = isset($rows[$key]) ? $rows[$key]->countBy(fn (Booking $b) => $b->status->value) : collect();

            foreach ($series as $status => $values) {
                $series[$status][] = (int) ($counts[$status] ?? 0);
            }
        }

        return compact('labels', 'series');
    }

    /**
     * Vehicles booked most often in the period.
     *
     * @return Collection<int, array{car: Car, name: string, bookings: int, days: int}>
     */
    public function mostBooked(CarbonInterface $from, CarbonInterface $to, int $limit = 6): Collection
    {
        return $this->active($from, $to)->with('rental.car')->get()
            ->filter(fn (Booking $b) => $b->rental)
            ->groupBy(fn (Booking $b) => $b->rental->car_id)
            ->map(fn (Collection $rows) => [
                'car' => $rows->first()->rental->car,
                'name' => $rows->first()->rental->car_name,
                'bookings' => $rows->count(),
                'days' => (int) $rows->sum(fn (Booking $b) => $b->rental->days()),
            ])
            ->sortByDesc(fn (array $row) => [$row['bookings'], $row['days']])
            ->take($limit)
            ->values();
    }

    /**
     * Average days between booking and pickup.
     */
    public function averageLeadDays(CarbonInterface $from, CarbonInterface $to): float
    {
        $days = $this->active($from, $to)->with('rental')->get()
            ->filter(fn (Booking $b) => $b->rental && $b->created_at)
            ->map(fn (Booking $b) => max(0, (int) $b->created_at->copy()->startOfDay()->diffInDays($b->rental->rental_start_date->copy()->startOfDay(), false)));

        return $days->isEmpty() ? 0.0 : round($days->avg(), 1);
    }

    /**
     * Pickups and returns expected in the coming days, from now.
     *
     * @return array{pickups: int, returns: int}
     */
    public function nextDays(int $days = 7): array
    {
        $now = LocalTime::now();
        $until = $now->addDays($days)->endOfDay();

        return [
            'pickups' => Booking::query()
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->whereHas('rental', fn (Builder $q) => $q->whereBetween('rental_start_date', [$now, $until]))
                ->count(),
            'returns' => Booking::query()
                ->where('status', BookingStatus::Delivered)
                ->whereHas('rental', fn (Builder $q) => $q->whereBetween('rental_end_date', [$now, $until]))
                ->count(),
        ];
    }

    /**
     * Vehicles out with a customer past their return date.
     */
    public function overdueCount(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Delivered)
            ->whereHas('rental', fn (Builder $q) => $q->where('rental_end_date', '<', LocalTime::now()))
            ->count();
    }

    /**
     * @return Collection<string, int> payment method label => count
     */
    public function paymentMethods(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->active($from, $to)->with('payment')->get()
            ->groupBy(fn (Booking $b) => $b->payment?->payment_channel?->getLabel() ?? 'Sin pago')
            ->map->count()
            ->sortDesc();
    }

    /**
     * Bookings that occupy any day of the period.
     */
    protected function overlapping(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Booking::query()
            ->with('rental')
            ->where('status', '!=', BookingStatus::Cancelled)
            ->whereHas('rental', fn (Builder $q) => $q->where('rental_start_date', '<', $to)->where('rental_end_date', '>', $from));
    }
}
