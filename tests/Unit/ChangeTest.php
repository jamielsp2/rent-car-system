<?php

namespace Tests\Unit;

use App\Support\Change;
use PHPUnit\Framework\TestCase;

class ChangeTest extends TestCase
{
    public function test_percentage_change_and_direction(): void
    {
        $up = Change::delta(150, 100);
        $this->assertSame('+50%', $up['text']);
        $this->assertTrue($up['good']);

        $down = Change::delta(50, 100);
        $this->assertSame('-50%', $down['text']);
        $this->assertFalse($down['good']);

        // For costs and cancellations, going down is the good news.
        $this->assertTrue(Change::delta(50, 100, lowerIsBetter: true)['good']);
    }

    public function test_a_new_figure_has_no_percentage(): void
    {
        $this->assertSame('Nuevo', Change::delta(5, 0)['text']);
        $this->assertNull(Change::delta(0, 0));
    }

    public function test_percentage_points_for_rates(): void
    {
        $this->assertSame('+5 pts', Change::delta(25, 20, absolute: true)['text']);
    }

    public function test_sparkline_needs_data(): void
    {
        $this->assertNull(Change::sparkline([0, 0, 0]));
        $this->assertNull(Change::sparkline([5]));
        $this->assertStringStartsWith('M0,', Change::sparkline([1, 3, 2])['line']);
    }
}
