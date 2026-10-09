<?php

namespace App\Filament\Clusters\Reservas\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Reservas\Pages\TodayHandovers;
use App\Filament\Clusters\Reservas\Widgets\Concerns\UsesBookingPeriod;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Car;
use App\Services\ReportStats;
use App\Support\Change;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

/**
 * Bookings at a glance: the period's volume and what needs attention now.
 */
class BookingsOverview extends Widget
{
    use UsesBookingPeriod;

    protected string $view = 'filament.bookings.summary.overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        [$prevFrom, $prevTo] = ReportStats::previous($from, $to);
        $stats = $this->stats();

        $now = $stats->summary($from, $to);
        $before = $stats->summary($prevFrom, $prevTo);
        $status = $stats->statusCounts($from, $to);
        $total = array_sum($status);
        $prevStatus = $stats->statusCounts($prevFrom, $prevTo);
        $prevTotal = array_sum($prevStatus);
        $cancelRate = $total ? $status['cancelled'] / $total * 100 : 0;
        $prevCancelRate = $prevTotal ? $prevStatus['cancelled'] / $prevTotal * 100 : 0;
        $lead = $stats->averageLeadDays($from, $to);
        $next = $stats->nextDays(7);
        $overdue = $stats->overdueCount();
        $rentedNow = Booking::query()->where('status', BookingStatus::Delivered)->count();
        $fleet = Car::query()->published()->count();
        // created_at is UTC; compare against the local day's bounds converted to UTC.
        $todayStart = CarbonImmutable::now(LocalTime::zone())->startOfDay()->utc();
        $receivedToday = Booking::query()->whereBetween('created_at', [$todayStart, $todayStart->addDay()])->count();

        return [
            'range' => $from->translatedFormat('j M Y') . ' – ' . $to->translatedFormat('j M Y'),
            'previous' => $prevFrom->translatedFormat('j M') . ' – ' . $prevTo->translatedFormat('j M Y'),
            'bookings' => [
                'value' => number_format($now['bookings']),
                'delta' => Change::delta($now['bookings'], $before['bookings']),
                'spark' => Change::sparkline($stats->trend($from, $to)['bookings']),
                'hint' => $status['cancelled'] ? "Sin contar {$status['cancelled']} canceladas" : 'Sin cancelaciones',
            ],
            'pending' => [
                'value' => $now['pending'],
                'url' => BookingResource::getUrl('index', ['filters' => ['status' => ['value' => BookingStatus::Pending->value]]]),
            ],
            'rented' => [
                'value' => $rentedNow,
                'fleet' => $fleet,
                'share' => $fleet ? min(100, $rentedNow / $fleet * 100) : 0,
                'overdue' => $overdue,
            ],
            'next' => [
                'pickups' => $next['pickups'],
                'returns' => $next['returns'],
                'url' => TodayHandovers::getUrl(),
            ],
            'metrics' => [
                ['label' => 'Confirmadas', 'value' => number_format($status['processing']), 'icon' => 'heroicon-o-check-badge', 'tone' => 'sky', 'delta' => Change::delta($status['processing'], $prevStatus['processing'])],
                ['label' => 'Completadas', 'value' => number_format($status['completed']), 'icon' => 'heroicon-o-flag', 'tone' => 'emerald', 'delta' => Change::delta($status['completed'], $prevStatus['completed'])],
                ['label' => 'Cancelaciones', 'value' => round($cancelRate, 1) . '%', 'icon' => 'heroicon-o-x-circle', 'tone' => 'rose', 'delta' => Change::delta($cancelRate, $prevCancelRate, true, true)],
                ['label' => 'Duración promedio', 'value' => number_format($now['average_days'], 1) . ' días', 'icon' => 'heroicon-o-clock', 'tone' => 'violet', 'delta' => Change::delta($now['average_days'], $before['average_days'])],
                ['label' => 'Reservan con', 'value' => number_format($lead, 1) . ' días', 'hint' => 'de anticipación', 'icon' => 'heroicon-o-calendar', 'tone' => 'amber', 'delta' => null],
                ['label' => 'Recibidas hoy', 'value' => number_format($receivedToday), 'hint' => 'reservas nuevas', 'icon' => 'heroicon-o-inbox-arrow-down', 'tone' => 'slate', 'delta' => null],
            ],
        ];
    }
}
