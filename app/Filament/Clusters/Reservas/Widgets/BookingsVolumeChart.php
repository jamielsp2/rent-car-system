<?php

namespace App\Filament\Clusters\Reservas\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Reservas\Widgets\Concerns\UsesBookingPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class BookingsVolumeChart extends ChartWidget
{
    use UsesBookingPeriod;

    protected ?string $heading = 'Reservas en el tiempo';

    protected ?string $description = 'Por fecha de salida, según su estado';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected ?string $maxHeight = '300px';

    public const COLORS = [
        'pending' => '#f59e0b',
        'processing' => '#38bdf8',
        'delivered' => '#206bc4',
        'completed' => '#10b981',
        'cancelled' => '#fda4af',
    ];

    protected function getData(): array
    {
        [$from, $to] = $this->period();
        $volume = $this->stats()->volume($from, $to);

        return [
            'labels' => $volume['labels'],
            'datasets' => collect(BookingStatus::cases())
                ->map(fn (BookingStatus $status) => [
                    'label' => $status->getLabel(),
                    'data' => $volume['series'][$status->value],
                    'backgroundColor' => self::COLORS[$status->value],
                    'borderRadius' => 4,
                    'maxBarThickness' => 32,
                    'stack' => 'bookings',
                ])
                ->all(),
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
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                },
            }
        JS);
    }
}
