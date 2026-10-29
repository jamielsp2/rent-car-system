<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use App\Services\ReportStats;
use App\Support\LocalTime;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AlliesTest extends TestCase
{
    use DatabaseTransactions;

    protected function ally(): User
    {
        return User::query()->create([
            'first_name' => 'Aliado',
            'last_name' => 'Prueba',
            'email' => 'aliado-' . uniqid() . '@example.com',
            'password' => 'password-largo',
            'is_ally' => true,
        ]);
    }

    public function test_allies_sign_in_to_their_panel_only(): void
    {
        $ally = $this->ally();

        $this->assertTrue($ally->canAccessPanel(Filament::getPanel('aliados')));
        $this->assertFalse($ally->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_staff_do_not_sign_in_to_the_ally_panel(): void
    {
        $staff = User::query()->firstOrFail();
        $this->assertFalse($staff->is_ally);

        $this->assertTrue($staff->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse($staff->canAccessPanel(Filament::getPanel('aliados')));
    }

    public function test_fleet_report_only_includes_the_owners_cars(): void
    {
        $ally = $this->ally();
        $own = Car::query()->published()->whereHas('slug')->firstOrFail();
        $own->update(['owner_id' => $ally->getKey()]);

        $from = LocalTime::today()->subDays(30);
        $to = LocalTime::today();
        $rows = app(ReportStats::class)->fleet($from, $to, $ally->getKey());

        $this->assertSame([$own->getKey()], $rows->map(fn (array $row) => $row['car']->getKey())->values()->all());
    }

    public function test_deleting_an_ally_leaves_the_car_in_the_fleet(): void
    {
        $ally = $this->ally();
        $car = Car::query()->published()->whereHas('slug')->firstOrFail();
        $car->update(['owner_id' => $ally->getKey()]);

        $ally->delete();

        $this->assertNull($car->fresh()->owner_id);
    }
}
