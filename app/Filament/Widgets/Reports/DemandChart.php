<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use Carbon\CarbonImmutable;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * Bar chart of bookings counted into buckets; the busiest one is highlighted.
 */
abstract class DemandChart extends ChartWidget
{
    use UsesReportPeriod;

    protected ?string $maxHeight = '220px';

    protected string $color = '#206bc4';

    /**
     * @return array<string, int>
     */
    abstract protected function buckets(CarbonImmutable $from, CarbonImmutable $to): array;

    protected function getData(): array
    {
        [$from, $to] = $this->period();
        $buckets = $this->buckets($from, $to);
        $peak = max($buckets ?: [0]);

        return [
            'labels' => array_keys($buckets),
            'datasets' => [[
                'label' => 'Reservas',
                'data' => array_values($buckets),
                'backgroundColor' => array_map(fn (int $v) => $v === $peak && $peak > 0 ? $this->color : $this->color . '40', array_values($buckets)),
                'borderRadius' => 6,
                'maxBarThickness' => 36,
            ]],
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
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                },
            }
        JS);
    }
}
