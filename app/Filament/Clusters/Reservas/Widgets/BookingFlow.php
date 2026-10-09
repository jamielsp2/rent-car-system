<?php

namespace App\Filament\Clusters\Reservas\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Reservas\Widgets\Concerns\UsesBookingPeriod;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Widgets\Widget;

/**
 * Where the period's bookings stand, from request to return.
 */
class BookingFlow extends Widget
{
    use UsesBookingPeriod;

    protected string $view = 'filament.bookings.summary.flow';

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        $counts = $this->stats()->statusCounts($from, $to);
        $total = array_sum($counts);
        $advanced = $counts['processing'] + $counts['delivered'] + $counts['completed'];

        return [
            'total' => $total,
            // Share of requests that went ahead (confirmed or further).
            'conversion' => $total ? round($advanced / $total * 100) : 0,
            'steps' => collect(BookingStatus::cases())->map(fn (BookingStatus $status) => [
                'label' => $status->getLabel(),
                'count' => $counts[$status->value],
                'share' => $total ? $counts[$status->value] / $total * 100 : 0,
                'color' => BookingsVolumeChart::COLORS[$status->value],
                'url' => BookingResource::getUrl('index', ['filters' => ['status' => ['value' => $status->value]]]),
            ]),
        ];
    }
}
