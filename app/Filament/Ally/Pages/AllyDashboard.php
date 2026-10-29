<?php

namespace App\Filament\Ally\Pages;

use App\Filament\Ally\Widgets\AllyFleet;
use App\Filament\Ally\Widgets\AllyOverview;
use App\Filament\Concerns\FiltersByPeriod;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

/**
 * Home of the ally: how their own cars perform, car by car.
 */
class AllyDashboard extends Dashboard
{
    use FiltersByPeriod;

    protected static ?string $title = 'Mis vehículos';

    protected static ?string $navigationLabel = 'Mis vehículos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public function getSubheading(): ?string
    {
        return 'Rentabilidad de tus vehículos, si están rentados y quién los rentó.';
    }

    public function getWidgets(): array
    {
        return [
            AllyOverview::class,
            AllyFleet::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
