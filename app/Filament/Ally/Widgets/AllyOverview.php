<?php

namespace App\Filament\Ally\Widgets;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Models\Car;
use App\Services\AllyStats;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Overall profitability of the ally's cars in the chosen period.
 */
class AllyOverview extends StatsOverviewWidget
{
    use UsesReportPeriod;

    protected function getStats(): array
    {
        [$from, $to] = $this->period();
        $ownerId = auth()->id();

        $rows = $this->report()->fleet($from, $to, $ownerId);
        $income = (float) $rows->sum('income');
        $expenses = (float) $rows->sum('expenses');
        $profit = $income - $expenses;

        $allies = app(AllyStats::class);
        $cars = Car::query()->ownedBy($ownerId)->get();
        $rented = $cars->filter(fn (Car $car) => $allies->status($car)['busy'])->count();

        return [
            Stat::make('Ingresos por renta', Money::format($income))
                ->description('Parte de las rentas que cae en el período'),
            Stat::make('Gastos', Money::format($expenses))
                ->description('Gastos registrados en tus vehículos'),
            Stat::make('Rentabilidad', Money::format($profit))
                ->description($income > 0 ? round($profit / $income * 100) . '% de margen' : 'Sin ingresos en el período')
                ->color($profit >= 0 ? 'success' : 'danger'),
            Stat::make('Rentados ahora', "{$rented} de {$cars->count()}")
                ->description('Vehículos con renta en curso o reservados'),
        ];
    }
}
