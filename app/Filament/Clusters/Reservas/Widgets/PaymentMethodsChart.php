<?php

namespace App\Filament\Clusters\Reservas\Widgets;

use App\Filament\Clusters\Reservas\Widgets\Concerns\UsesBookingPeriod;
use Filament\Widgets\ChartWidget;

class PaymentMethodsChart extends ChartWidget
{
    use UsesBookingPeriod;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Formas de pago';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        [$from, $to] = $this->period();
        $methods = $this->stats()->paymentMethods($from, $to);

        return [
            'labels' => $methods->keys()->all(),
            'datasets' => [[
                'data' => $methods->values()->all(),
                'backgroundColor' => ['#206bc4', '#10b981', '#2fb344', '#f59f00', '#94a3b8'],
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
