<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\User;
use App\Services\BookingStats;
use App\Services\ReportStats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_reports_are_the_admin_home(): void
    {
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Informes')
            ->assertDontSee('Escritorio');
    }

    public function test_every_widget_renders_for_every_period(): void
    {
        $page = new Reports;

        foreach (array_keys(BookingStats::PRESETS) as $period) {
            foreach ($page->getWidgets() as $widget) {
                Livewire::test($widget, ['pageFilters' => ['period' => $period]])->assertOk();
            }
        }
    }

    public function test_profit_is_net_revenue_minus_expenses(): void
    {
        $stats = app(ReportStats::class);
        [$from, $to] = BookingStats::range(['period' => '12m']);
        $figures = $stats->figures($from, $to);

        $this->assertEqualsWithDelta($figures['net_revenue'] - $figures['expenses'], $figures['profit'], 0.01);
        $this->assertLessThanOrEqual($figures['revenue'], $figures['net_revenue']);
        $this->assertSame(7, count($stats->weekdays($from, $to)));
    }

    public function test_previous_period_has_the_same_length(): void
    {
        $from = CarbonImmutable::parse('2026-09-01')->startOfDay();
        $to = CarbonImmutable::parse('2026-09-30')->endOfDay();

        [$prevFrom, $prevTo] = ReportStats::previous($from, $to);

        $this->assertSame('2026-08-02', $prevFrom->toDateString());
        $this->assertSame('2026-08-31', $prevTo->toDateString());
    }
}
