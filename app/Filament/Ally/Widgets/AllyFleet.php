<?php

namespace App\Filament\Ally\Widgets;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Models\Car;
use App\Services\AllyStats;
use App\Support\Money;
use Filament\Widgets\Widget;

/**
 * The ally's vehicles, ranked by profit, styled like the admin's fleet ranking.
 * Each car opens its own booking history.
 */
class AllyFleet extends Widget
{
    use UsesReportPeriod;

    protected string $view = 'filament.ally.fleet';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        $ownerId = auth()->id();

        $stats = $this->report()->fleet($from, $to, $ownerId)
            ->keyBy(fn (array $row) => $row['car']->getKey());
        $maxIncome = max(1, (float) $stats->max('income'));
        $allies = app(AllyStats::class);

        $rows = Car::query()->ownedBy($ownerId)->with('make')->orderBy('name')->get()
            ->map(function (Car $car) use ($stats, $allies, $maxIncome): array {
                $status = $allies->status($car);
                $rental = $status['rental'];
                $row = $stats[$car->getKey()] ?? null;

                return [
                    'name' => $car->name,
                    'meta' => collect([$car->make?->name, $car->license_plate])->filter()->implode(' · '),
                    'image' => $car->cover_url,
                    'price' => Money::format($car->rental_rate),
                    'min_days' => (int) $car->min_rental_days,
                    'status' => $status['label'],
                    'status_color' => $status['color'],
                    'renter' => $rental?->booking?->customer_name,
                    'dates' => $rental ? $rental->rental_start_date->format('d/m/Y') . ' → ' . $rental->rental_end_date->format('d/m/Y') : null,
                    'income' => Money::format($row['income'] ?? 0),
                    'income_value' => (float) ($row['income'] ?? 0),
                    'income_share' => (float) ($row['income'] ?? 0) / $maxIncome * 100,
                    'expenses' => ($row['expenses'] ?? 0) ? Money::format($row['expenses']) : '—',
                    'profit' => Money::format($row['profit'] ?? 0),
                    'profit_value' => (float) ($row['profit'] ?? 0),
                    'utilization' => round($row['utilization'] ?? 0),
                    'rentals' => $row['rentals'] ?? 0,
                    'history' => $allies->history($car)->take(20)->map(fn ($r) => [
                        'booking' => $r->booking?->booking_number,
                        'customer' => $r->booking?->customer_name,
                        'from' => $r->rental_start_date->format('d/m/Y H:i'),
                        'to' => $r->rental_end_date->format('d/m/Y H:i'),
                        'price' => Money::format($r->price),
                        'status' => $r->booking?->status?->getLabel(),
                    ])->all(),
                ];
            })
            ->sortByDesc('profit_value')
            ->values();

        return [
            'rows' => $rows,
            'total' => Money::format($stats->sum('profit')),
        ];
    }
}
