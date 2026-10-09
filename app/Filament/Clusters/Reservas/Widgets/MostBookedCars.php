<?php

namespace App\Filament\Clusters\Reservas\Widgets;

use App\Filament\Clusters\Reservas\Widgets\Concerns\UsesBookingPeriod;
use App\Filament\Resources\Cars\CarResource;
use Filament\Widgets\Widget;

/**
 * The vehicles customers ask for the most.
 */
class MostBookedCars extends Widget
{
    use UsesBookingPeriod;

    protected string $view = 'filament.bookings.summary.cars';

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        $cars = $this->stats()->mostBooked($from, $to);
        $max = max(1, $cars->max('bookings'));

        return [
            'cars' => $cars->map(fn (array $row) => [
                'name' => $row['name'],
                'image' => $row['car']->cover_url,
                'url' => $row['car']->exists ? CarResource::getUrl('view', ['record' => $row['car']]) : null,
                'bookings' => $row['bookings'],
                'days' => $row['days'],
                'share' => $row['bookings'] / $max * 100,
            ]),
        ];
    }
}
