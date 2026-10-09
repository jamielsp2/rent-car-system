<?php

namespace App\Filament\Clusters\Reservas\Pages;

use App\Filament\Clusters\Reservas\ReservasCluster;
use App\Filament\Clusters\Reservas\Widgets\BookingFlow;
use App\Filament\Clusters\Reservas\Widgets\BookingsOverview;
use App\Filament\Clusters\Reservas\Widgets\BookingsVolumeChart;
use App\Filament\Clusters\Reservas\Widgets\MostBookedCars;
use App\Filament\Clusters\Reservas\Widgets\RecentBookings;
use App\Filament\Concerns\FiltersByPeriod;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

/**
 * Bookings only; money and fleet figures live in the Reports page.
 */
class BookingDashboard extends Dashboard
{
    use FiltersByPeriod;

    protected static ?string $cluster = ReservasCluster::class;

    protected static string $routePath = 'resumen';

    protected static ?string $title = 'Reservas';

    protected static ?string $breadcrumb = 'Resumen';

    protected static ?string $navigationLabel = 'Resumen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return static::$navigationIcon;
    }

    public function getWidgets(): array
    {
        return [
            BookingsOverview::class,
            BookingsVolumeChart::class,
            BookingFlow::class,
            RecentBookings::class,
            MostBookedCars::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 3];
    }
}
