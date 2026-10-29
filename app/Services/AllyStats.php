<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingCar;
use App\Models\Car;
use App\Support\LocalTime;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * What an ally's car is doing right now: rented, booked or free, and who has it.
 */
class AllyStats
{
    /** Bookings that keep a car busy (same rule as the website's availability). */
    public const BUSY = [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Delivered];

    /**
     * @return array{label: string, color: string, busy: bool, rental: ?BookingCar}
     */
    public function status(Car $car): array
    {
        $rental = $this->current($car);

        if ($rental && $rental->rental_start_date->startOfDay()->lte(LocalTime::today())) {
            $delivered = $rental->booking?->status === BookingStatus::Delivered;

            return [
                'label' => $delivered ? 'Rentado' : 'Reservado',
                'color' => $delivered ? 'danger' : 'warning',
                'busy' => true,
                'rental' => $rental,
            ];
        }

        return [
            'label' => 'Disponible',
            'color' => 'success',
            'busy' => false,
            'rental' => $rental,
        ];
    }

    /**
     * The rental running today, or the next one if the car is free.
     */
    public function current(Car $car): ?BookingCar
    {
        return BookingCar::query()
            ->with('booking')
            ->where('car_id', $car->getKey())
            ->whereHas('booking', fn ($q) => $q->whereIn('status', self::BUSY))
            ->whereDate('rental_end_date', '>=', LocalTime::today()->toDateString())
            ->orderBy('rental_start_date')
            ->first();
    }

    /**
     * Rental income of the owner's cars per day, for the sparklines.
     *
     * @return float[]
     */
    public function dailyIncome(int $ownerId, CarbonInterface $from, CarbonInterface $to): array
    {
        $days = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay()) as $day) {
            $days[$day->format('Y-m-d')] = 0.0;
        }

        BookingCar::query()
            ->whereHas('car', fn ($q) => $q->ownedBy($ownerId))
            ->whereHas('booking', fn ($q) => $q->where('status', '!=', BookingStatus::Cancelled))
            ->whereBetween('rental_start_date', [$from, $to])
            ->get(['rental_start_date', 'price'])
            ->each(function (BookingCar $rental) use (&$days): void {
                $key = $rental->rental_start_date->format('Y-m-d');

                if (array_key_exists($key, $days)) {
                    $days[$key] += (float) $rental->price;
                }
            });

        return array_values($days);
    }

    /**
     * Every booking of the car, newest first.
     *
     * @return Collection<int, BookingCar>
     */
    public function history(Car $car): Collection
    {
        return BookingCar::query()
            ->with('booking')
            ->where('car_id', $car->getKey())
            ->orderByDesc('rental_start_date')
            ->limit(100)
            ->get();
    }
}
