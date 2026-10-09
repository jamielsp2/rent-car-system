<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Support\Money;
use Filament\Widgets\Widget;

/**
 * Extras sold with the rentals next to where the money went.
 */
class ExtrasAndCosts extends Widget
{
    use UsesReportPeriod;

    protected string $view = 'filament.reports.extras';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        $services = $this->report()->services($from, $to);
        $expenses = $this->report()->expenseConcepts($from, $to);

        return [
            'services' => $services->map(fn (array $s) => [...$s, 'amount' => Money::format($s['revenue']), 'share' => $s['revenue'] / max(1, $services->max('revenue')) * 100]),
            'servicesTotal' => Money::format($services->sum('revenue')),
            'expenses' => $expenses->map(fn (array $e) => [...$e, 'amount' => Money::format($e['total']), 'share' => $e['total'] / max(1, $expenses->max('total')) * 100]),
            'expensesTotal' => Money::format($expenses->sum('total')),
        ];
    }
}
