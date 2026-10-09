<?php

namespace App\Filament\Widgets\Reports;

use Carbon\CarbonImmutable;

class LeadTimeChart extends DemandChart
{
    protected ?string $heading = 'Anticipación de la reserva';

    protected ?string $description = 'Tiempo entre la reserva y la salida';

    protected string $color = '#8b5cf6';

    protected function buckets(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->report()->leadTime($from, $to);
    }
}
