<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarPriceRange;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tax;
use Carbon\CarbonInterface;

/**
 * Price of renting a car for a period, with its taxes and extras.
 */
class RentalQuote
{
    /**
     * Taxes apply to the rental plus extras.
     *
     * @param  int[]  $serviceIds
     * @return array{days: int, rate: float, rental: float, services: array<int, array{id: int, name: string, amount: float}>, services_total: float, subtotal: float, taxes: array<int, array{name: string, amount: float}>, tax_total: float, total: float, deposit: float, min_days: int, meets_min_days: bool}
     */
    public function quote(Car $car, CarbonInterface $from, CarbonInterface $to, array $serviceIds = []): array
    {
        // Nights, not calendar days: 10th → 13th is 3 days.
        $days = max(1, (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()));
        $rate = $this->dailyRate($car, $days);
        $rental = round($rate * $days, 2);

        $services = $serviceIds
            ? Service::query()->published()->whereIn('id', $serviceIds)->orderBy('name')->get()
                ->map(fn (Service $service) => ['id' => $service->getKey(), 'name' => $service->name, 'amount' => $service->totalFor($days)])
                ->all()
            : [];
        $servicesTotal = round(array_sum(array_column($services, 'amount')), 2);
        $subtotal = round($rental + $servicesTotal, 2);

        $taxes = $this->taxes($subtotal, $days);
        $taxTotal = round(array_sum(array_column($taxes, 'amount')), 2);
        $minDays = max(1, (int) $car->min_rental_days);

        return [
            'days' => $days,
            'rate' => $rate,
            'rental' => $rental,
            'services' => $services,
            'services_total' => $servicesTotal,
            'subtotal' => $subtotal,
            'taxes' => $taxes,
            'tax_total' => $taxTotal,
            'total' => round($subtotal + $taxTotal, 2),
            'deposit' => $this->deposit($car),
            'min_days' => $minDays,
            'meets_min_days' => $days >= $minDays,
        ];
    }

    /**
     * A price range for the rental length wins over the car's normal rate.
     */
    public function dailyRate(Car $car, int $days): float
    {
        $range = CarPriceRange::query()
            ->where('car_id', $car->getKey())
            ->where('min_days', '<=', $days)
            ->where(fn ($q) => $q->whereNull('max_days')->orWhere('max_days', '>=', $days))
            ->orderByDesc('min_days')
            ->first();

        return (float) ($range?->price_per_day ?? $car->rental_rate);
    }

    /**
     * Null on the car means "use the general deposit"; zero means "no deposit".
     */
    public function deposit(Car $car): float
    {
        return (float) ($car->deposit_amount ?? Setting::get('rentcar_deposit_amount', 0));
    }

    /**
     * @return array<int, array{name: string, amount: float}>
     */
    protected function taxes(float $subtotal, int $days): array
    {
        // Every published tax applies (Catálogos → Impuestos).
        return Tax::query()
            ->where('status', 'published')
            ->orderBy('priority')
            ->get()
            ->map(function (Tax $tax) use ($subtotal, $days): array {
                $flat = $tax->amount_type === 'per_day' ? $tax->amount * $days : $tax->amount;

                return [
                    'name' => $tax->name,
                    'amount' => round($subtotal * $tax->percentage / 100 + $flat, 2),
                ];
            })
            ->all();
    }
}
