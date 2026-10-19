<?php

namespace App\Filament\Clusters\Reservas;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

/**
 * Bookings module: summary, list, calendar and today's handovers, as tabs.
 */
class ReservasCluster extends Cluster
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'reservas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Reservas';

    protected static ?string $clusterBreadcrumb = 'Reservas';

    protected static ?string $slug = 'reservas';

    protected static ?int $navigationSort = 1;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
