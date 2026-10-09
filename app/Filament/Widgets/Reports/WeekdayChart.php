<?php

namespace App\Filament\Widgets\Reports;

use Carbon\CarbonImmutable;

class WeekdayChart extends DemandChart
{
    protected ?string $heading = 'Días con más salidas';

    protected ?string $description = 'Día de la semana en que se recoge el vehículo';

    protected string $color = '#206bc4';

    protected function buckets(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->report()->weekdays($from, $to);
    }
}
