<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\FiltersByPeriod;
use App\Filament\Widgets\Reports\BookingMixChart;
use App\Filament\Widgets\Reports\DurationChart;
use App\Filament\Widgets\Reports\ExtrasAndCosts;
use App\Filament\Widgets\Reports\FinanceChart;
use App\Filament\Widgets\Reports\FleetPerformance;
use App\Filament\Widgets\Reports\LeadTimeChart;
use App\Filament\Widgets\Reports\PaymentMixChart;
use App\Filament\Widgets\Reports\ReportsOverview;
use App\Filament\Widgets\Reports\TopCustomers;
use App\Filament\Widgets\Reports\UpcomingPickups;
use App\Filament\Widgets\Reports\WeekdayChart;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

/**
 * Home of the admin: business reports for the chosen period.
 */
class Reports extends Dashboard
{
    use FiltersByPeriod;

    protected static ?string $title = 'Informes';

    protected static ?string $navigationLabel = 'Informes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public function getSubheading(): ?string
    {
        return 'Cómo va el negocio: ingresos, utilidad, flota, demanda y clientes.';
    }

    public function getWidgets(): array
    {
        return [
            ReportsOverview::class,
            FinanceChart::class,
            BookingMixChart::class,
            FleetPerformance::class,
            WeekdayChart::class,
            LeadTimeChart::class,
            DurationChart::class,
            TopCustomers::class,
            UpcomingPickups::class,
            ExtrasAndCosts::class,
            PaymentMixChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 3];
    }
}
