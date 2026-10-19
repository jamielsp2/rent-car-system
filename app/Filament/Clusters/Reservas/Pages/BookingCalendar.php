<?php

namespace App\Filament\Clusters\Reservas\Pages;

use App\Filament\Clusters\Reservas\ReservasCluster;
use App\Models\Car;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BookingCalendar extends Page
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'reservas';

    protected static ?string $cluster = ReservasCluster::class;

    protected static ?string $slug = 'calendario';

    protected static ?string $title = 'Calendario de reservas';

    protected static ?string $breadcrumb = 'Calendario';

    protected static ?string $navigationLabel = 'Calendario';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.bookings.calendar';

    protected function getViewData(): array
    {
        return [
            'cars' => Car::query()->orderBy('name')->pluck('name', 'id'),
            'statuses' => [
                'Pendiente' => '#f59f00',
                'Confirmada' => '#4299e1',
                'Entregado' => '#206bc4',
                'Completada' => '#2fb344',
                'Cancelada' => '#d63939',
            ],
        ];
    }
}
