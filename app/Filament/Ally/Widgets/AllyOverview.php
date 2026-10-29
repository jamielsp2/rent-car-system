<?php

namespace App\Filament\Ally\Widgets;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Models\Car;
use App\Services\AllyStats;
use App\Services\ReportStats;
use App\Support\Change;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

/**
 * Headline figures of the ally's cars, styled like the admin's overview and
 * each compared with the period right before.
 */
class AllyOverview extends Widget
{
    use UsesReportPeriod;

    protected string $view = 'filament.ally.overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        [$prevFrom, $prevTo] = ReportStats::previous($from, $to);
        $ownerId = auth()->id();

        $now = $this->summary($from, $to, $ownerId);
        $before = $this->summary($prevFrom, $prevTo, $ownerId);
        $delta = fn (string $key, bool $lowerIsBetter = false) => Change::delta($now[$key], $before[$key], $lowerIsBetter);

        $allies = app(AllyStats::class);
        $cars = Car::query()->ownedBy($ownerId)->get();
        $rented = $cars->filter(fn (Car $car) => $allies->status($car)['busy'])->count();
        $sparkline = Change::sparkline($allies->dailyIncome($ownerId, $from, $to));

        return [
            'range' => $from->translatedFormat('j M Y') . ' – ' . $to->translatedFormat('j M Y'),
            'previous' => $prevFrom->translatedFormat('j M') . ' – ' . $prevTo->translatedFormat('j M Y'),
            'hero' => [
                'label' => 'Ingresos por renta',
                'value' => Money::format($now['income']),
                'hint' => 'Parte de las rentas que cae en el período',
                'delta' => $delta('income'),
                'spark' => $sparkline,
            ],
            'profit' => [
                'label' => 'Rentabilidad',
                'value' => Money::format($now['profit']),
                'hint' => 'Margen ' . round($now['margin']) . '% · gastos ' . Money::format($now['expenses']),
                'delta' => $delta('profit'),
                'negative' => $now['profit'] < 0,
            ],
            'occupancy' => [
                'value' => round($now['utilization']),
                'delta' => Change::delta($now['utilization'], $before['utilization'], absolute: true),
                'rented' => $rented,
                'total' => $cars->count(),
            ],
            'metrics' => [
                ['label' => 'Gastos de tus vehículos', 'value' => Money::format($now['expenses']), 'hint' => 'Mantenimiento y reparaciones', 'delta' => $delta('expenses', true), 'icon' => 'heroicon-o-wrench-screwdriver'],
                ['label' => 'Margen', 'value' => round($now['margin']) . '%', 'hint' => 'Utilidad sobre ingresos', 'delta' => Change::delta($now['margin'], $before['margin'], absolute: true), 'icon' => 'heroicon-o-chart-pie'],
                ['label' => 'Rentas del período', 'value' => number_format($now['rentals']), 'hint' => 'Veces que se rentó tu flota', 'delta' => $delta('rentals'), 'icon' => 'heroicon-o-key'],
                ['label' => 'Vehículos', 'value' => number_format($cars->count()), 'hint' => "{$rented} rentados ahora", 'delta' => null, 'icon' => 'heroicon-o-truck'],
            ],
        ];
    }

    /**
     * @return array{income: float, expenses: float, profit: float, margin: float, rentals: int, utilization: float}
     */
    protected function summary(CarbonImmutable $from, CarbonImmutable $to, int $ownerId): array
    {
        $rows = $this->report()->fleet($from, $to, $ownerId);
        $income = (float) $rows->sum('income');
        $expenses = (float) $rows->sum('expenses');

        return [
            'income' => $income,
            'expenses' => $expenses,
            'profit' => $income - $expenses,
            'margin' => $income > 0 ? ($income - $expenses) / $income * 100 : 0.0,
            'rentals' => (int) $rows->sum('rentals'),
            'utilization' => $rows->isEmpty() ? 0.0 : (float) $rows->avg('utilization'),
        ];
    }
}
