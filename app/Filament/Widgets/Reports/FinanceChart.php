<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class FinanceChart extends ChartWidget
{
    use UsesReportPeriod;

    protected ?string $heading = 'Ingresos, gastos y utilidad';

    protected ?string $description = 'Ingresos netos (sin ITBIS) por fecha de salida, menos los gastos de la flota';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        [$from, $to] = $this->period();
        $finance = $this->report()->finance($from, $to);

        return [
            'labels' => $finance['labels'],
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'Utilidad',
                    'data' => $finance['profit'],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'pointBackgroundColor' => '#10b981',
                    'pointRadius' => 3,
                    'borderWidth' => 2.5,
                    'tension' => 0.35,
                    'fill' => true,
                    'order' => 1,
                ],
                [
                    'type' => 'bar',
                    'label' => 'Ingresos netos',
                    'data' => $finance['revenue'],
                    'backgroundColor' => '#206bc4',
                    'borderRadius' => 6,
                    'maxBarThickness' => 28,
                    'order' => 2,
                ],
                [
                    'type' => 'bar',
                    'label' => 'Gastos',
                    'data' => $finance['expenses'],
                    'backgroundColor' => '#fda4af',
                    'borderRadius' => 6,
                    'maxBarThickness' => 28,
                    'order' => 3,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': $' + c.parsed.y.toLocaleString(undefined, { maximumFractionDigits: 0 }) } },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { callback: (v) => '$' + v.toLocaleString() } },
                },
            }
        JS);
    }
}
