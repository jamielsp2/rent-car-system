<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Clusters\Reservas\Widgets\BookingStatusChart;

/**
 * Bookings by status, as a ring with the total in the legend.
 */
class BookingMixChart extends BookingStatusChart
{
    protected ?string $heading = 'Estado de las reservas';

    protected ?string $description = 'Todas las reservas del período';

    protected ?string $maxHeight = '300px';

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
