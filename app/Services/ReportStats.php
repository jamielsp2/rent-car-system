<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarExpense;
use App\Models\Customer;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Figures for the admin reports. Bookings belong to the period of their pickup
 * date (as in the bookings summary); cancelled ones never count as revenue.
 * "Net" amounts leave out ITBIS, which is collected for the government.
 */
class ReportStats
{
    public function __construct(protected BookingStats $bookings) {}

    /**
     * The window of the same length right before the period, to compare against.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function previous(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = static::days($from, $to);

        return [$from->subDays($days)->startOfDay(), $from->subDay()->endOfDay()];
    }

    public static function days(CarbonInterface $from, CarbonInterface $to): int
    {
        return max(1, (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);
    }

    /**
     * Headline figures of a period.
     *
     * @return array<string, float|int>
     */
    public function figures(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $active = $this->bookings->active($from, $to)->with(['rental', 'payment'])->get();
        $total = $this->bookings->bookings($from, $to)->count();
        $count = $active->count();

        $revenue = (float) $active->sum('amount');
        $netRevenue = $revenue - (float) $active->sum('tax_amount');
        $rentalRevenue = (float) $active->sum(fn (Booking $b) => $b->rental?->price ?? 0);
        $rentedDays = (int) $active->sum(fn (Booking $b) => $b->rental?->days() ?? 0);
        $expenses = (float) CarExpense::query()->whereBetween('date', [$from, $to])->sum('amount');
        $fleet = Car::query()->published()->count();
        $firstBooking = $this->firstBookingByCustomer();

        return [
            'revenue' => $revenue,
            'net_revenue' => $netRevenue,
            'expenses' => $expenses,
            'profit' => $netRevenue - $expenses,
            'margin' => $netRevenue > 0 ? ($netRevenue - $expenses) / $netRevenue * 100 : 0.0,
            'bookings' => $count,
            'cancel_rate' => $total ? ($total - $count) / $total * 100 : 0.0,
            'average' => $count ? $revenue / $count : 0.0,
            'average_days' => $count ? $rentedDays / $count : 0.0,
            'utilization' => $this->bookings->utilization($from, $to),
            // Average daily rate actually charged, and rental income per available car per day.
            'adr' => $rentedDays ? $rentalRevenue / $rentedDays : 0.0,
            'revpac' => $fleet ? $rentalRevenue / ($fleet * static::days($from, $to)) : 0.0,
            'collected_rate' => $revenue > 0
                ? (float) $active->filter(fn (Booking $b) => $b->payment?->status === PaymentStatus::Completed)->sum('amount') / $revenue * 100
                : 0.0,
            'new_customers' => Customer::query()->whereBetween('created_at', [$from, $to])->count(),
            'repeat_rate' => $count
                ? $active->filter(fn (Booking $b) => ($first = $firstBooking[static::customerKey($b)] ?? null) && $b->created_at?->gt($first))->count() / $count * 100
                : 0.0,
        ];
    }

    /**
     * Net revenue, expenses and profit per day (short periods) or month (long ones).
     *
     * @return array{labels: string[], revenue: float[], expenses: float[], profit: float[], bookings: int[]}
     */
    public function finance(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $monthly = $from->diffInDays($to) > 62;
        $format = $monthly ? 'Y-m' : 'Y-m-d';

        $bookings = $this->bookings->active($from, $to)->with('rental')->get()
            ->groupBy(fn (Booking $b) => $b->rental->rental_start_date->format($format));
        $expenses = CarExpense::query()->whereBetween('date', [$from, $to])->get()
            ->groupBy(fn (CarExpense $e) => $e->date->format($format));

        $period = $monthly
            ? CarbonPeriod::create($from->startOfMonth(), '1 month', $to)
            : CarbonPeriod::create($from, '1 day', $to);

        $series = ['labels' => [], 'revenue' => [], 'expenses' => [], 'profit' => [], 'bookings' => []];

        foreach ($period as $date) {
            $key = $date->format($format);
            $rows = $bookings[$key] ?? collect();
            $revenue = round((float) $rows->sum('amount') - (float) $rows->sum('tax_amount'), 2);
            $spent = round((float) ($expenses[$key] ?? collect())->sum('amount'), 2);

            $series['labels'][] = $monthly ? ucfirst($date->translatedFormat('M Y')) : $date->format('d/m');
            $series['revenue'][] = $revenue;
            $series['expenses'][] = $spent;
            $series['profit'][] = round($revenue - $spent, 2);
            $series['bookings'][] = $rows->count();
        }

        return $series;
    }

    /**
     * Each vehicle in the period: rental income (no ITBIS or extras) split by the
     * days that fall inside it, occupancy, expenses and profit. Best first.
     *
     * @return Collection<int, array{car: Car, income: float, days: int, rentals: int, utilization: float, expenses: float, profit: float}>
     */
    public function fleet(CarbonImmutable $from, CarbonImmutable $to, ?int $ownerId = null): Collection
    {
        $periodDays = static::days($from, $to);
        $rentals = $this->overlapping($from, $to)->get()->groupBy(fn (Booking $b) => $b->rental->car_id);
        $expenses = CarExpense::query()->whereBetween('date', [$from, $to])
            ->selectRaw('car_id, SUM(amount) as total')->groupBy('car_id')->pluck('total', 'car_id');

        return Car::query()->with('make')->when($ownerId, fn (Builder $q) => $q->ownedBy($ownerId))->orderBy('order')->get()
            ->map(function (Car $car) use ($rentals, $expenses, $from, $to, $periodDays): array {
                $income = 0.0;
                $days = 0;

                foreach ($rentals[$car->getKey()] ?? [] as $booking) {
                    $rental = $booking->rental;
                    $inside = $this->daysBetween($rental->rental_start_date->max($from), $rental->rental_end_date->min($to));
                    $income += $rental->price * $inside / max(1, $rental->days());
                    $days += $inside;
                }

                $spent = (float) ($expenses[$car->getKey()] ?? 0);

                return [
                    'car' => $car,
                    'income' => $income,
                    'days' => $days,
                    'rentals' => count($rentals[$car->getKey()] ?? []),
                    'utilization' => min(100, $days / $periodDays * 100),
                    'expenses' => $spent,
                    'profit' => $income - $spent,
                ];
            })
            // Cars out of the fleet only show while they still move money.
            ->filter(fn (array $row) => $row['car']->moderation_status === 'approved' || $row['income'] || $row['expenses'])
            ->sortByDesc('profit')
            ->values();
    }

    /**
     * Pickups per weekday, Monday first.
     *
     * @return array<string, int>
     */
    public function weekdays(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $counts = $this->activeWithRental($from, $to)->countBy(fn (Booking $b) => $b->rental->rental_start_date->dayOfWeekIso);

        return collect(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'])
            ->mapWithKeys(fn (string $day, int $i) => [$day => (int) ($counts[$i + 1] ?? 0)])
            ->all();
    }

    /**
     * How far ahead customers book (from booking to pickup).
     *
     * @return array<string, int>
     */
    public function leadTime(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $buckets = ['Mismo día' => 0, '1-3 días' => 0, '4-7 días' => 0, '8-30 días' => 0, 'Más de 30' => 0];

        foreach ($this->activeWithRental($from, $to) as $booking) {
            $days = $booking->created_at ? $this->daysBetween($booking->created_at, $booking->rental->rental_start_date) : 0;

            $buckets[match (true) {
                $days < 1 => 'Mismo día',
                $days <= 3 => '1-3 días',
                $days <= 7 => '4-7 días',
                $days <= 30 => '8-30 días',
                default => 'Más de 30',
            }]++;
        }

        return $buckets;
    }

    /**
     * Rentals grouped by length.
     *
     * @return array<string, int>
     */
    public function durations(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $buckets = ['1-2 días' => 0, '3-6 días' => 0, '1-2 semanas' => 0, '2+ semanas' => 0];

        foreach ($this->activeWithRental($from, $to) as $booking) {
            $days = $booking->rental->days();

            $buckets[match (true) {
                $days <= 2 => '1-2 días',
                $days <= 6 => '3-6 días',
                $days <= 13 => '1-2 semanas',
                default => '2+ semanas',
            }]++;
        }

        return $buckets;
    }

    /**
     * Customers who spent the most in the period.
     *
     * @return Collection<int, array{name: string, email: ?string, customer_id: ?int, bookings: int, revenue: float, last: ?CarbonInterface}>
     */
    public function topCustomers(CarbonImmutable $from, CarbonImmutable $to, int $limit = 6): Collection
    {
        return $this->activeWithRental($from, $to)
            ->groupBy(fn (Booking $b) => static::customerKey($b))
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()->customer_name ?: 'Cliente',
                'email' => $rows->first()->customer_email,
                'customer_id' => $rows->first()->customer_id ? (int) $rows->first()->customer_id : null,
                'bookings' => $rows->count(),
                'revenue' => (float) $rows->sum('amount'),
                'last' => $rows->max(fn (Booking $b) => $b->rental->rental_start_date),
            ])
            ->sortByDesc('revenue')
            ->take($limit)
            ->values();
    }

    /**
     * Extras sold with the rentals.
     *
     * @return Collection<int, array{name: string, count: int, revenue: float}>
     */
    public function services(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $this->bookings->active($from, $to)->with(['rental', 'services'])->get()
            ->flatMap(fn (Booking $b) => $b->services->map(fn ($service) => [
                'name' => $service->name,
                'revenue' => $service->totalFor($b->rental?->days() ?? 1),
            ]))
            ->groupBy('name')
            ->map(fn (Collection $rows, string $name) => ['name' => $name, 'count' => $rows->count(), 'revenue' => (float) $rows->sum('revenue')])
            ->sortByDesc('revenue')
            ->values();
    }

    /**
     * Pickups booked for the coming days, whatever the period filter says.
     *
     * @return array{count: int, revenue: float, pending: int, next: Collection<int, Booking>}
     */
    public function upcoming(int $days = 30): array
    {
        $now = LocalTime::now();
        $bookings = Booking::query()
            ->with('rental')
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->whereHas('rental', fn (Builder $q) => $q->whereBetween('rental_start_date', [$now, $now->addDays($days)->endOfDay()]))
            ->get()
            ->sortBy(fn (Booking $b) => $b->rental->rental_start_date)
            ->values();

        return [
            'count' => $bookings->count(),
            'revenue' => (float) $bookings->sum('amount'),
            'pending' => $bookings->where('status', BookingStatus::Pending)->count(),
            'next' => $bookings->take(5),
        ];
    }

    /**
     * Expenses of the period grouped by concept.
     *
     * @return Collection<int, array{name: string, total: float}>
     */
    public function expenseConcepts(CarbonImmutable $from, CarbonImmutable $to, int $limit = 5): Collection
    {
        return CarExpense::query()->whereBetween('date', [$from, $to])->get()
            ->groupBy(fn (CarExpense $e) => mb_convert_case(trim($e->name), MB_CASE_TITLE))
            ->map(fn (Collection $rows, string $name) => ['name' => $name, 'total' => (float) $rows->sum('amount')])
            ->sortByDesc('total')
            ->take($limit)
            ->values();
    }

    protected function activeWithRental(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $this->bookings->active($from, $to)->with('rental')->get()->filter(fn (Booking $b) => $b->rental);
    }

    /**
     * Non-cancelled bookings that occupy any day of the period.
     */
    protected function overlapping(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return Booking::query()
            ->with('rental')
            ->where('status', '!=', BookingStatus::Cancelled)
            ->whereHas('rental', fn (Builder $q) => $q->where('rental_start_date', '<', $to)->where('rental_end_date', '>', $from));
    }

    protected function daysBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return max(0, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay(), false));
    }

    /**
     * When each customer first booked, to tell repeat customers apart.
     *
     * @return Collection<string, CarbonInterface>
     */
    protected function firstBookingByCustomer(): Collection
    {
        return Booking::query()
            ->where('status', '!=', BookingStatus::Cancelled)
            ->get(['customer_email', 'customer_name', 'created_at'])
            ->groupBy(fn (Booking $b) => static::customerKey($b))
            ->map(fn (Collection $rows) => $rows->min('created_at'));
    }

    protected static function customerKey(Booking $booking): string
    {
        return mb_strtolower(trim((string) ($booking->customer_email ?: $booking->customer_name)));
    }
}
