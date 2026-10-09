<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Events for the admin bookings calendar (FullCalendar feed).
 */
class BookingCalendarController extends Controller
{
    protected const COLORS = [
        'pending' => '#f59f00',
        'processing' => '#4299e1',
        'delivered' => '#206bc4',
        'completed' => '#2fb344',
        'cancelled' => '#d63939',
    ];

    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'car' => ['nullable', 'integer'],
            'cancelled' => ['nullable', 'boolean'],
        ]);

        $bookings = Booking::query()
            ->with('rental')
            ->whereHas('rental', fn (Builder $q) => $q
                ->where('rental_start_date', '<', Carbon::parse($data['end']))
                ->where('rental_end_date', '>', Carbon::parse($data['start']))
                ->when($data['car'] ?? null, fn (Builder $q, $car) => $q->where('car_id', $car)))
            ->unless($request->boolean('cancelled'), fn (Builder $q) => $q->where('status', '!=', BookingStatus::Cancelled))
            ->get();

        return response()->json($bookings->map(fn (Booking $booking) => [
            'id' => $booking->getKey(),
            'title' => $booking->rental->car_name . ' · ' . $booking->customer_name,
            'start' => $booking->rental->rental_start_date->format('Y-m-d\TH:i:s'),
            'end' => $booking->rental->rental_end_date->format('Y-m-d\TH:i:s'),
            'color' => self::COLORS[$booking->status->value] ?? '#94a3b8',
            'url' => BookingResource::getUrl('edit', ['record' => $booking]),
            'extendedProps' => [
                'number' => $booking->booking_number,
                'status' => $booking->status->getLabel(),
                'phone' => $booking->customer_phone,
            ],
        ])->values());
    }
}
