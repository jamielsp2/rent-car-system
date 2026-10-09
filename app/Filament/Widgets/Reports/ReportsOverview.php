<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Services\ReportStats;
use App\Support\Change;
use App\Support\Money;
use Filament\Widgets\Widget;

/**
 * Headline figures of the period, each compared with the period right before it.
 */
class ReportsOverview extends Widget
{
    use UsesReportPeriod;

    protected string $view = 'filament.reports.overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        [$prevFrom, $prevTo] = ReportStats::previous($from, $to);

        $report = $this->report();
        $now = $report->figures($from, $to);
        $before = $report->figures($prevFrom, $prevTo);
        $finance = $report->finance($from, $to);

        $delta = fn (string $key, bool $lowerIsBetter = false) => Change::delta($now[$key], $before[$key], $lowerIsBetter);

        return [
            'range' => $from->translatedFormat('j M Y') . ' – ' . $to->translatedFormat('j M Y'),
            'previous' => $prevFrom->translatedFormat('j M') . ' – ' . $prevTo->translatedFormat('j M Y'),
            'hero' => [
                'revenue' => [
                    'label' => 'Ingresos facturados',
                    'value' => Money::format($now['revenue']),
                    'hint' => 'Netos sin ITBIS: ' . Money::format($now['net_revenue']),
                    'delta' => $delta('revenue'),
                    'spark' => Change::sparkline($finance['revenue']),
                ],
                'profit' => [
                    'label' => 'Utilidad neta',
                    'value' => Money::format($now['profit']),
                    'hint' => 'Margen ' . round($now['margin']) . '% · gastos ' . Money::format($now['expenses']),
                    'delta' => $delta('profit'),
                    'spark' => Change::sparkline($finance['profit']),
                    'negative' => $now['profit'] < 0,
                ],
                'bookings' => [
                    'label' => 'Reservas',
                    'value' => number_format($now['bookings']),
                    'hint' => 'Ticket promedio ' . Money::format($now['average']),
                    'delta' => $delta('bookings'),
                    'spark' => Change::sparkline($finance['bookings']),
                ],
            ],
            'utilization' => [
                'value' => round($now['utilization'], 1),
                'delta' => Change::delta($now['utilization'], $before['utilization'], absolute: true),
            ],
            'metrics' => [
                ['label' => 'Tarifa diaria promedio', 'value' => Money::format($now['adr']), 'hint' => 'Precio real cobrado por día', 'delta' => $delta('adr'), 'icon' => 'heroicon-o-tag'],
                ['label' => 'Ingreso por vehículo / día', 'value' => Money::format($now['revpac']), 'hint' => 'Renta entre la flota disponible', 'delta' => $delta('revpac'), 'icon' => 'heroicon-o-truck'],
                ['label' => 'Duración promedio', 'value' => number_format($now['average_days'], 1) . ' días', 'hint' => 'Por renta', 'delta' => $delta('average_days'), 'icon' => 'heroicon-o-clock'],
                ['label' => 'Cobrado', 'value' => round($now['collected_rate']) . '%', 'hint' => 'De lo facturado, con pago completado', 'delta' => Change::delta($now['collected_rate'], $before['collected_rate'], absolute: true), 'icon' => 'heroicon-o-banknotes'],
                ['label' => 'Cancelaciones', 'value' => round($now['cancel_rate'], 1) . '%', 'hint' => 'De todas las reservas', 'delta' => Change::delta($now['cancel_rate'], $before['cancel_rate'], true, true), 'icon' => 'heroicon-o-x-circle'],
                ['label' => 'Clientes que repiten', 'value' => round($now['repeat_rate']) . '%', 'hint' => 'Reservas de clientes que ya rentaron', 'delta' => Change::delta($now['repeat_rate'], $before['repeat_rate'], absolute: true), 'icon' => 'heroicon-o-arrow-path'],
                ['label' => 'Clientes nuevos', 'value' => number_format($now['new_customers']), 'hint' => 'Cuentas creadas en la web', 'delta' => $delta('new_customers'), 'icon' => 'heroicon-o-user-plus'],
                ['label' => 'Gastos de la flota', 'value' => Money::format($now['expenses']), 'hint' => 'Mantenimiento, seguros, reparaciones', 'delta' => $delta('expenses', true), 'icon' => 'heroicon-o-wrench-screwdriver'],
            ],
        ];
    }
}
