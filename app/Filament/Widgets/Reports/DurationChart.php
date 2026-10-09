<?php

namespace App\Filament\Widgets\Reports;

use Carbon\CarbonImmutable;

class DurationChart extends DemandChart
{
    protected ?string $heading = 'Duración de las rentas';

    protected ?string $description = 'Cuántos días se quedan con el vehículo';

    protected string $color = '#0d9488';

    protected function buckets(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->report()->durations($from, $to);
    }
}
