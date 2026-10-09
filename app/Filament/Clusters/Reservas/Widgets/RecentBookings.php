<?php

namespace App\Filament\Clusters\Reservas\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Support\LocalTime;
use Filament\Widgets\Widget;

/**
 * The latest requests, newest first, whatever period is selected.
 */
class RecentBookings extends Widget
{
    protected string $view = 'filament.bookings.summary.recent';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected function getViewData(): array
    {
        return [
            'bookings' => Booking::query()->with('rental.car')->latest('created_at')->latest('id')->limit(6)->get()
                ->map(fn (Booking $b) => [
                    'number' => $b->booking_number,
                    'customer' => $b->customer_name ?: 'Cliente',
                    'car' => $b->rental?->car_name,
                    'image' => $b->rental?->car?->cover_url,
                    'dates' => $b->rental
                        ? $b->rental->rental_start_date->translatedFormat('j M') . ' → ' . $b->rental->rental_end_date->translatedFormat('j M')
                        : null,
                    'days' => $b->rental?->days(),
                    'status' => $b->status,
                    'when' => $b->created_at?->setTimezone(LocalTime::zone())->locale('es')->diffForHumans(),
                    'url' => BookingResource::getUrl('edit', ['record' => $b]),
                ]),
            'allUrl' => BookingResource::getUrl('index'),
        ];
    }
}
