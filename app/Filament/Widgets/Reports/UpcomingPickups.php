<?php

namespace App\Filament\Widgets\Reports;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Models\Booking;
use App\Support\Money;
use Filament\Widgets\Widget;

/**
 * Money already booked for the next 30 days, whatever period is selected.
 */
class UpcomingPickups extends Widget
{
    use UsesReportPeriod;

    protected string $view = 'filament.reports.upcoming';

    protected function getViewData(): array
    {
        $upcoming = $this->report()->upcoming(30);

        return [
            'count' => $upcoming['count'],
            'revenue' => Money::format($upcoming['revenue']),
            'pending' => $upcoming['pending'],
            'next' => $upcoming['next']->map(fn (Booking $b) => [
                'day' => $b->rental->rental_start_date->format('d'),
                'month' => ucfirst(rtrim($b->rental->rental_start_date->translatedFormat('M'), '.')),
                'car' => $b->rental->car_name,
                'customer' => $b->customer_name,
                'amount' => Money::format($b->amount),
                'pending' => $b->status === BookingStatus::Pending,
                'url' => BookingResource::getUrl('edit', ['record' => $b]),
            ]),
        ];
    }
}
