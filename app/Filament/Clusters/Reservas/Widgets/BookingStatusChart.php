<?php

namespace App\Filament\Clusters\Reservas\Widgets;

use App\Filament\Clusters\Reservas\Widgets\Concerns\UsesBookingPeriod;
use Filament\Widgets\ChartWidget;

class BookingStatusChart extends ChartWidget
{
    use UsesBookingPeriod;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Reservas por estado';

    protected ?string $maxHeight = '260px';

    protected const COLORS = [
        'Pendiente' => '#f59f00',
        'Confirmada' => '#4299e1',
        'Entregado (Renta iniciada)' => '#206bc4',
        'Completada' => '#2fb344',
        'Cancelada' => '#d63939',
    ];

    protected function getData(): array
    {
        [$from, $to] = $this->period();
        $counts = $this->stats()->byStatus($from, $to);

        return [
            'labels' => $counts->keys()->all(),
            'datasets' => [[
                'data' => $counts->values()->all(),
                'backgroundColor' => $counts->keys()->map(fn (string $label) => self::COLORS[$label] ?? '#94a3b8')->all(),
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
