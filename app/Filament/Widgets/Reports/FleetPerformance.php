<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Resources\Cars\CarResource;
use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Support\Money;
use Filament\Widgets\Widget;

/**
 * Ranking of the vehicles by profit in the period.
 */
class FleetPerformance extends Widget
{
    use UsesReportPeriod;

    protected string $view = 'filament.reports.fleet';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        $fleet = $this->report()->fleet($from, $to);
        $maxIncome = max(1, $fleet->max('income'));
        // Cars that didn't move money are listed by name below the ranking.
        [$active, $idle] = $fleet->partition(fn (array $row) => $row['rentals'] || $row['expenses']);

        return [
            'rows' => $active->values()->map(fn (array $row) => [
                'name' => $row['car']->name,
                'meta' => collect([$row['car']->make?->name, $row['car']->license_plate])->filter()->implode(' · '),
                'image' => $row['car']->cover_url,
                'url' => CarResource::getUrl('view', ['record' => $row['car']]),
                'income' => Money::format($row['income']),
                'income_share' => $row['income'] / $maxIncome * 100,
                'expenses' => $row['expenses'] ? Money::format($row['expenses']) : '—',
                'profit' => Money::format($row['profit']),
                'profit_value' => $row['profit'],
                'rentals' => $row['rentals'],
                'days' => $row['days'],
                'utilization' => round($row['utilization']),
            ]),
            'idle' => $idle->map(fn (array $row) => [
                'name' => $row['car']->name,
                'url' => CarResource::getUrl('view', ['record' => $row['car']]),
            ])->values(),
            'total' => Money::format($fleet->sum('profit')),
        ];
    }
}
