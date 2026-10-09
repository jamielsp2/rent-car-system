<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Support\Money;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * Customers who spent the most in the period.
 */
class TopCustomers extends Widget
{
    use UsesReportPeriod;

    protected string $view = 'filament.reports.customers';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected function getViewData(): array
    {
        [$from, $to] = $this->period();
        $customers = $this->report()->topCustomers($from, $to);
        $max = max(1, $customers->max('revenue'));

        return [
            'customers' => $customers->map(fn (array $c) => [
                'name' => $c['name'],
                'initials' => Str::upper(collect(explode(' ', trim($c['name'])))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')),
                'email' => $c['email'],
                'url' => $c['customer_id'] ? CustomerResource::getUrl('view', ['record' => $c['customer_id']]) : null,
                'bookings' => $c['bookings'],
                'revenue' => Money::format($c['revenue']),
                'share' => $c['revenue'] / $max * 100,
                'last' => $c['last']?->translatedFormat('j M Y'),
            ]),
        ];
    }
}
