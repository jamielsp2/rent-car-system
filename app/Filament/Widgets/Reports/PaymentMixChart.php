<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Clusters\Reservas\Widgets\PaymentMethodsChart;

class PaymentMixChart extends PaymentMethodsChart
{
    protected ?string $heading = 'Formas de pago';

    protected ?string $description = 'Cómo pagan las reservas del período';

    protected ?string $maxHeight = '240px';

    protected function getData(): array
    {
        $data = parent::getData();
        $data['datasets'][0]['backgroundColor'] = ['#206bc4', '#10b981', '#8b5cf6', '#f59e0b', '#94a3b8'];

        return $data;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '68%',
            'borderWidth' => 0,
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'padding' => 14]]],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
